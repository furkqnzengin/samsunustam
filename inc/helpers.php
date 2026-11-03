<?php
declare(strict_types=1);

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    global $CFG;
    return $CFG['base'] . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function post(string $k, string $default = ''): string
{
    $v = $_POST[$k] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function get(string $k, string $default = ''): string
{
    $v = $_GET[$k] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function post_ids(string $k): array
{
    $v = $_POST[$k] ?? [];
    if (!is_array($v)) return [];
    return array_values(array_unique(array_filter(array_map('intval', $v), fn($i) => $i > 0)));
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    $t = $_POST['csrf'] ?? $_GET['csrf'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        http_response_code(400);
        exit('Oturum süresi doldu, sayfayı yenileyip tekrar deneyin.');
    }
}

/* ---------- Bildirimler ---------- */
function flash(string $msg, string $type = 'ok'): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function flash_html(): string
{
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as [$type, $msg]) {
        $out .= '<div class="flash flash-' . e($type) . '">' . e($msg) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

/* ---------- Türkçe metin ---------- */
function tr_lower(string $s): string
{
    return mb_strtolower(strtr($s, ['I' => 'ı', 'İ' => 'i']), 'UTF-8');
}

function tr_upper(string $s): string
{
    return mb_strtoupper(strtr($s, ['i' => 'İ', 'ı' => 'I']), 'UTF-8');
}

function slugify(string $s): string
{
    $s = strtr(tr_lower($s), ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', '&' => ' ve ']);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: 'usta';
}

/** Yer adına bulunma eki: Atakum'da, Terme'de, Tekkeköy'de */
function ek_de(string $ad): string
{
    $w = tr_lower($ad);
    $chars = preg_split('//u', $w, -1, PREG_SPLIT_NO_EMPTY);
    $vowel = 'a';
    foreach (array_reverse($chars) as $c) {
        if (str_contains('aeıioöuü', $c)) { $vowel = $c; break; }
    }
    $last = end($chars);
    $front = str_contains('eiöü', $vowel);
    $hard = str_contains('fstkçşhp', (string)$last);
    return $ad . "'" . ($hard ? 't' : 'd') . ($front ? 'e' : 'a');
}

function initials(string $ad): string
{
    $parts = preg_split('/\s+/u', trim($ad));
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return tr_upper($first . $last);
}

function sayi(int|float|string|null $n): string
{
    return number_format((float)$n, 0, ',', '.');
}

function puan_fmt(int|float|string|null $p): string
{
    return number_format((float)$p, 1, '.', '');
}

function yildiz(int|float|string $p): string
{
    $p = (int)round((float)$p);
    return str_repeat('★', $p) . str_repeat('☆', 5 - $p);
}

function zaman_once(string $dt): string
{
    $d = time() - strtotime($dt);
    if ($d < 60) return 'az önce';
    if ($d < 3600) return floor($d / 60) . ' dk önce';
    if ($d < 86400) return floor($d / 3600) . ' saat önce';
    if ($d < 172800) return 'dün';
    if ($d < 604800) return floor($d / 86400) . ' gün önce';
    if ($d < 2592000) return floor($d / 604800) . ' hafta önce';
    return date('d.m.Y', strtotime($dt));
}

function tarih_tr(?string $dt = null): string
{
    $aylar = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
    $t = $dt ? strtotime($dt) : time();
    return date('j', $t) . ' ' . $aylar[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
}

/* ---------- Telefon ---------- */
function tel_temizle(string $t): string
{
    $d = preg_replace('/\D+/', '', $t);
    if (str_starts_with($d, '90') && strlen($d) === 12) $d = '0' . substr($d, 2);
    if (strlen($d) === 10 && $d[0] === '5') $d = '0' . $d;
    return $d;
}

function tel_gecerli(string $t): bool
{
    return (bool)preg_match('/^0[2-5]\d{9}$/', tel_temizle($t));
}

function tel_goster(string $t): string
{
    $d = tel_temizle($t);
    if (strlen($d) !== 11) return $t;
    return substr($d, 0, 4) . ' ' . substr($d, 4, 3) . ' ' . substr($d, 7, 2) . ' ' . substr($d, 9, 2);
}

function tel_maskele(string $t): string
{
    $d = tel_temizle($t);
    return strlen($d) === 11 ? substr($d, 0, 4) . ' *** ** ' . substr($d, 9, 2) : '***';
}

function wa_link(string $t, string $mesaj = ''): string
{
    $d = tel_temizle($t);
    $intl = '9' . $d; // 05xx -> 905xx
    return 'https://wa.me/' . $intl . ($mesaj !== '' ? '?text=' . rawurlencode($mesaj) : '');
}

/* ---------- Ayarlar ---------- */
function ayar(string $k, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        try {
            $cache = [];
            foreach (rows('SELECT anahtar, deger FROM ayarlar') as $r) $cache[$r['anahtar']] = (string)$r['deger'];
        } catch (Throwable) {
            $cache = [];
        }
    }
    return ($cache[$k] ?? '') !== '' ? $cache[$k] : $default;
}

function ayar_kaydet(string $k, string $v): void
{
    q('INSERT INTO ayarlar (anahtar, deger) VALUES (?, ?) ON DUPLICATE KEY UPDATE deger = VALUES(deger)', [$k, $v]);
}

/* ---------- İstatistik ---------- */
function olay_kaydet(int $ustaId, string $tur): void
{
    q('INSERT INTO olaylar (usta_id, tur, tarih, adet) VALUES (?, ?, CURDATE(), 1) ON DUPLICATE KEY UPDATE adet = adet + 1', [$ustaId, $tur]);
}

function arama_kaydet(int $hizmetId): void
{
    q('INSERT INTO aramalar (hizmet_id, tarih, adet) VALUES (?, CURDATE(), 1) ON DUPLICATE KEY UPDATE adet = adet + 1', [$hizmetId]);
}

function ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/** Basit hız sınırı: aynı oturumdan belirli işlemi kısa sürede çok yapmayı engeller. */
function hiz_siniri(string $anahtar, int $limit, int $saniye): bool
{
    $now = time();
    $list = array_filter($_SESSION['rl'][$anahtar] ?? [], fn($t) => $t > $now - $saniye);
    if (count($list) >= $limit) return false;
    $list[] = $now;
    $_SESSION['rl'][$anahtar] = $list;
    return true;
}

/* ---------- Dosya yükleme ---------- */
/**
 * Resim yükler, uploads/ altındaki göreli yolu döndürür. Hata olursa string mesaj fırlatır.
 */
function resim_yukle(array $file, string $klasor, int $maxMb = 5): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Dosya yüklenemedi.');
    if ($file['size'] > $maxMb * 1024 * 1024) throw new RuntimeException("Dosya en fazla {$maxMb} MB olabilir.");
    $info = @getimagesize($file['tmp_name']);
    $izinli = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($izinli[$info[2]])) throw new RuntimeException('Sadece JPG, PNG veya WEBP resim yükleyebilirsiniz.');
    $ext = $izinli[$info[2]];
    $dir = ROOT . '/uploads/' . $klasor;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $ad = bin2hex(random_bytes(12)) . '.' . $ext;
    $hedef = $dir . '/' . $ad;
    if (!resim_kucult($file['tmp_name'], $hedef, $info[2], 1600)) {
        if (!move_uploaded_file($file['tmp_name'], $hedef)) throw new RuntimeException('Dosya kaydedilemedi.');
    }
    return $klasor . '/' . $ad;
}

/** GD varsa büyük resmi küçültür; yoksa false döner ve dosya olduğu gibi taşınır. */
function resim_kucult(string $kaynak, string $hedef, int $tip, int $max): bool
{
    if (!function_exists('imagecreatetruecolor')) return false;
    [$w, $h] = getimagesize($kaynak);
    if ($w <= $max && $h <= $max) return false;
    $oran = $max / max($w, $h);
    $nw = (int)($w * $oran); $nh = (int)($h * $oran);
    $src = match ($tip) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($kaynak),
        IMAGETYPE_PNG => @imagecreatefrompng($kaynak),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($kaynak) : false,
        default => false,
    };
    if (!$src) return false;
    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $ok = match ($tip) {
        IMAGETYPE_JPEG => imagejpeg($dst, $hedef, 85),
        IMAGETYPE_PNG => imagepng($dst, $hedef, 6),
        IMAGETYPE_WEBP => imagewebp($dst, $hedef, 85),
    };
    return $ok;
}

function dosya_sil(?string $goreli): void
{
    if (!$goreli) return;
    $yol = realpath(ROOT . '/uploads/' . $goreli);
    $kok = realpath(ROOT . '/uploads');
    if ($yol && $kok && str_starts_with($yol, $kok . DIRECTORY_SEPARATOR) && is_file($yol)) unlink($yol);
}

function upload_url(?string $goreli): string
{
    return $goreli ? url('uploads/' . $goreli) : '';
}

/* ---------- Usta yardımcıları ---------- */
function puan_guncelle(int $ustaId): void
{
    q("UPDATE ustalar u SET
        puan = COALESCE((SELECT ROUND(AVG(puan),1) FROM yorumlar WHERE usta_id = u.id AND durum = 'yayinda'), 0),
        yorum_sayisi = (SELECT COUNT(*) FROM yorumlar WHERE usta_id = u.id AND durum = 'yayinda')
       WHERE u.id = ?", [$ustaId]);
}

function benzersiz_slug(string $ad, ?int $haricId = null): string
{
    $base = slugify($ad);
    $slug = $base;
    $i = 2;
    while (val('SELECT id FROM ustalar WHERE slug = ? AND id <> ?', [$slug, $haricId ?? 0])) {
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

/** Ustanın şu an aktif öne çıkarılan paketi var mı? */
function premium_mu(array $u): bool
{
    return !empty($u['one_cikar']) && (empty($u['paket_bitis']) || $u['paket_bitis'] >= date('Y-m-d'));
}

/** Liste kartlarında kullanılan ortak usta sorgusu (aktif ustalar). */
function usta_kart_sql(string $where = '1=1', string $order = ''): string
{
    $order = $order ?: "(p.one_cikar = 1 AND (u.paket_bitis IS NULL OR u.paket_bitis >= CURDATE())) DESC, u.puan DESC, u.yorum_sayisi DESC, u.id DESC";
    return "SELECT u.*, p.one_cikar, p.ad AS paket_ad,
              (SELECT GROUP_CONCAT(i.ad ORDER BY i.sira SEPARATOR ', ') FROM usta_ilce ui JOIN ilceler i ON i.id = ui.ilce_id WHERE ui.usta_id = u.id) AS ilce_adlari,
              (SELECT CONCAT(y.metin, '||', y.ad, '||', COALESCE(y.mahalle,'')) FROM yorumlar y WHERE y.usta_id = u.id AND y.durum = 'yayinda' AND y.puan >= 4 ORDER BY y.id DESC LIMIT 1) AS son_yorum
            FROM ustalar u LEFT JOIN paketler p ON p.id = u.paket_id
            WHERE u.durum = 'aktif' AND $where
            ORDER BY $order";
}
