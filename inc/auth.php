<?php
declare(strict_types=1);

function kullanici(): ?array
{
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $u = row('SELECT id, ad, eposta, telefon, rol FROM kullanicilar WHERE id = ?', [(int)$_SESSION['uid']]);
            if (!$u) unset($_SESSION['uid']);
        }
    }
    return $u;
}

function giris_yap(array $k, bool $hatirla = false): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$k['id'];
    q('UPDATE kullanicilar SET son_giris = NOW() WHERE id = ?', [$k['id']]);
    if ($hatirla) hatirla_olustur((int)$k['id']);
}

function cikis_yap(): void
{
    hatirla_sil();
    $_SESSION = [];
    session_regenerate_id(true);
}

/* ---------- "Beni hatırla" ----------
 * Tarayıcıya "selector:dogrulayici" çerezi yazılır; veritabanında sadece doğrulayıcının
 * SHA-256 özeti tutulur. Kullanıldıkça süre 30 gün uzar; çıkış ya da şifre değişikliği iptal eder. */
const HATIRLA_CEREZ = 'su_hatirla';
const HATIRLA_GUN = 30;

function hatirla_cerez_yaz(string $deger, int $bitis): void
{
    global $CFG, $https;
    setcookie(HATIRLA_CEREZ, $deger, [
        'expires' => $bitis, 'path' => ($CFG['base'] ?: '') . '/',
        'httponly' => true, 'secure' => (bool)$https, 'samesite' => 'Lax',
    ]);
}

function hatirla_olustur(int $uid): void
{
    $selector = bin2hex(random_bytes(9));
    $dogrulayici = bin2hex(random_bytes(32));
    q('INSERT INTO hatirla_tokenlari (kullanici_id, selector, dogrulayici, bitis) VALUES (?, ?, ?, NOW() + INTERVAL ' . HATIRLA_GUN . ' DAY)',
        [$uid, $selector, hash('sha256', $dogrulayici)]);
    hatirla_cerez_yaz("$selector:$dogrulayici", time() + HATIRLA_GUN * 86400);
}

/** Oturum yoksa ve geçerli bir hatırla çerezi varsa kullanıcıyı tekrar içeri alır. */
function hatirla_dene(): void
{
    if (!empty($_SESSION['uid']) || empty($_COOKIE[HATIRLA_CEREZ])) return;
    $parca = explode(':', (string)$_COOKIE[HATIRLA_CEREZ]);
    if (count($parca) !== 2 || !preg_match('/^[a-f0-9]{18}$/', $parca[0]) || !preg_match('/^[a-f0-9]{64}$/', $parca[1])) {
        hatirla_cerez_yaz('', time() - 3600);
        return;
    }
    try {
        $r = row('SELECT * FROM hatirla_tokenlari WHERE selector = ? AND bitis > NOW()', [$parca[0]]);
    } catch (Throwable) {
        return; // kurulum öncesi: tablo yok
    }
    if (!$r || !hash_equals($r['dogrulayici'], hash('sha256', $parca[1]))) {
        if ($r) q('DELETE FROM hatirla_tokenlari WHERE id = ?', [$r['id']]); // çalınmış olabilir: iptal et
        hatirla_cerez_yaz('', time() - 3600);
        return;
    }
    // Süreyi uzat. (Doğrulayıcı değiştirilmez: aynı anda açılan iki sekme birbirini dışarı atmasın.)
    q('UPDATE hatirla_tokenlari SET bitis = NOW() + INTERVAL ' . HATIRLA_GUN . ' DAY WHERE id = ?', [$r['id']]);
    hatirla_cerez_yaz($parca[0] . ':' . $parca[1], time() + HATIRLA_GUN * 86400);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$r['kullanici_id'];
    if (random_int(1, 50) === 1) q('DELETE FROM hatirla_tokenlari WHERE bitis < NOW()');
}

function hatirla_sil(): void
{
    $c = (string)($_COOKIE[HATIRLA_CEREZ] ?? '');
    if ($c !== '') {
        try { q('DELETE FROM hatirla_tokenlari WHERE selector = ?', [explode(':', $c)[0]]); } catch (Throwable) {}
        hatirla_cerez_yaz('', time() - 3600);
    }
}

/** Şifre değişince kullanıcının tüm "hatırla" girişlerini iptal eder; isteğe bağlı olarak bu cihaz için yenisini açar. */
function hatirla_hepsini_sil(int $uid, bool $buCihazdaKalsin = false): void
{
    q('DELETE FROM hatirla_tokenlari WHERE kullanici_id = ?', [$uid]);
    if ($buCihazdaKalsin && !empty($_COOKIE[HATIRLA_CEREZ])) hatirla_olustur($uid);
}

function giris_gerekli(string $rol): array
{
    $u = kullanici();
    if (!$u) {
        $_SESSION['sonra'] = $_SERVER['REQUEST_URI'] ?? '';
        flash('Devam etmek için giriş yapın.', 'info');
        redirect('giris.php');
    }
    if ($u['rol'] !== $rol) {
        redirect($u['rol'] === 'admin' ? 'yonetim/' : 'usta/');
    }
    return $u;
}

/** Giriş yapmış ustanın usta kaydı. */
function aktif_usta(): array
{
    $k = giris_gerekli('usta');
    $u = row('SELECT u.*, p.ad AS paket_ad, p.one_cikar FROM ustalar u LEFT JOIN paketler p ON p.id = u.paket_id WHERE u.kullanici_id = ?', [$k['id']]);
    if (!$u) {
        cikis_yap();
        redirect('giris.php');
    }
    return $u;
}
