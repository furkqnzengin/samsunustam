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

function giris_yap(array $k): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$k['id'];
    q('UPDATE kullanicilar SET son_giris = NOW() WHERE id = ?', [$k['id']]);
}

function cikis_yap(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
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
