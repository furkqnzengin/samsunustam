<?php
declare(strict_types=1);

// İş ilanları ve iş arayanlar için ortak tanımlar

const CALISMA_SEKILLERI = ['Tam zamanlı', 'Yarı zamanlı', 'Günlük / yevmiye', 'Proje bazlı', 'Çırak / stajyer'];
const TECRUBE_SEVIYELERI = ['Tecrübe aranmıyor', '1 yıldan az', '1-3 yıl', '3-5 yıl', '5 yıl ve üzeri'];
const ILAN_SURE_GUN = 30;   // ustanın eleman ilanı
const ARAYAN_SURE_GUN = 60; // iş arayan ilanı

function ilan_durum_pill(string $d): string
{
    [$ad, $c] = [
        'aktif' => ['Yayında', 'p-green'], 'pasif' => ['Durduruldu', 'p-blue'], 'doldu' => ['Pozisyon doldu', 'p-amber'], 'gizli' => ['Yönetici gizledi', 'p-red'],
        'beklemede' => ['Onay bekliyor', 'p-amber'], 'yayinda' => ['Yayında', 'p-green'], 'kaldirildi' => ['Kaldırıldı', 'p-blue'],
        'yeni' => ['Yeni', 'p-amber'], 'goruldu' => ['Görüldü', 'p-blue'], 'olumlu' => ['Görüşülecek', 'p-green'], 'olumsuz' => ['Uygun değil', 'p-red'],
    ][$d] ?? [$d, 'p-blue'];
    return '<span class="pill ' . $c . '">' . e($ad) . '</span>';
}

/** Yayındaki eleman ilanları için ortak sorgu. */
function ilan_sql(string $where = '1=1'): string
{
    return "SELECT l.*, u.ad_soyad, u.slug AS usta_slug, u.meslek AS usta_meslek, u.foto, u.telefon AS usta_tel, u.whatsapp AS usta_wa, u.adres AS usta_adres,
              h.ad AS hizmet_ad, i.ad AS ilce_ad
            FROM ilanlar l JOIN ustalar u ON u.id = l.usta_id
            LEFT JOIN hizmetler h ON h.id = l.hizmet_id LEFT JOIN ilceler i ON i.id = l.ilce_id
            WHERE l.durum = 'aktif' AND l.bitis >= CURDATE() AND u.durum = 'aktif' AND $where";
}

function ilan_sayisi(): int
{
    return (int)val("SELECT COUNT(*) FROM ilanlar l JOIN ustalar u ON u.id = l.usta_id WHERE l.durum = 'aktif' AND l.bitis >= CURDATE() AND u.durum = 'aktif'");
}

/** Bir sonraki tarihi Y-m-d olarak döndürür. */
function gun_sonra(int $gun): string
{
    return date('Y-m-d', strtotime("+$gun day"));
}

/** İş arayan ilanını yönetme linki (tam adres). */
function arayan_link(string $token): string
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($host ? ($https ? 'https://' : 'http://') . $host : '') . url('is-ara.php?t=' . $token);
}
