<?php
declare(strict_types=1);

/*
 * Servis takip: sektöre özel alanlar, kontrol listeleri ve yardımcılar.
 * Alan tipi: text | number | select | date | textarea. "anahtar" => true olan alan
 * geçmiş kayıtları bulmak için kullanılır (plaka, seri no gibi).
 */
function sektorler(): array
{
    $yakit = ['Benzin', 'Dizel', 'LPG', 'Hibrit', 'Elektrik'];
    return [
        'arac' => [
            'ad' => 'Oto servis', 'ikon' => '🚗', 'kayit' => 'Araç', 'liste' => 'Araç kayıtları',
            'alanlar' => [
                ['plaka', 'Plaka', 'text', null, true, true],
                ['marka', 'Marka', 'text'], ['model', 'Model', 'text'], ['yil', 'Model yılı', 'number'],
                ['km', 'Kilometre', 'number'], ['sasi', 'Şasi no', 'text'], ['yakit', 'Yakıt', 'select', $yakit],
            ],
            'baslik' => ['plaka', 'marka', 'model'],
            'kontrol' => ['Motor yağı', 'Yağ filtresi', 'Hava filtresi', 'Polen filtresi', 'Yakıt filtresi', 'Ön fren balatası', 'Arka fren balatası',
                'Fren hidroliği', 'Antifriz / soğutma', 'Akü', 'Triger / V kayışı', 'Silecekler', 'Lastik diş & basınç', 'Farlar & stop lambaları', 'Arıza tespit (OBD) taraması'],
            'hizli' => [['iscilik', 'İşçilik'], ['parca', 'Motor yağı'], ['parca', 'Yağ filtresi'], ['parca', 'Hava filtresi'], ['parca', 'Polen filtresi'],
                ['parca', 'Yakıt filtresi'], ['parca', 'Fren balatası'], ['iscilik', 'Arıza tespit (OBD)']],
            'bakim' => 'Örn: 120.000 km veya 05.2027',
        ],
        'agir' => [
            'ad' => 'Ağır vasıta servisi', 'ikon' => '🚛', 'kayit' => 'Araç', 'liste' => 'Araç kayıtları',
            'alanlar' => [
                ['plaka', 'Plaka', 'text', null, true, true],
                ['tip', 'Araç tipi', 'select', ['Tır çekici', 'Kamyon', 'Dorse / Treyler', 'Otobüs', 'Minibüs', 'Kamyonet', 'İş makinesi', 'Forklift']],
                ['marka', 'Marka', 'text'], ['model', 'Model', 'text'], ['yil', 'Model yılı', 'number'],
                ['km', 'Km / çalışma saati', 'text'], ['dorse', 'Dorse plakası', 'text'], ['sasi', 'Şasi no', 'text'],
            ],
            'baslik' => ['plaka', 'marka', 'model'],
            'kontrol' => ['Motor yağı & filtresi', 'Yakıt filtresi / separatör', 'Hava filtresi', 'Hava kurutucu filtresi', 'Havalı fren kaçak kontrolü',
                'Fren balata / kampana', 'Şanzıman yağı', 'Diferansiyel yağı', 'Gresleme', 'AdBlue sistemi', 'Soğutma sistemi', 'Lastik & jant',
                'Aydınlatma', 'Takograf kalibrasyonu'],
            'hizli' => [['iscilik', 'İşçilik'], ['parca', 'Motor yağı'], ['parca', 'Yağ filtresi'], ['parca', 'Yakıt filtresi'], ['parca', 'Hava kurutucu filtresi'],
                ['iscilik', 'Gresleme'], ['parca', 'Fren balatası'], ['iscilik', 'Takograf kalibrasyonu']],
            'bakim' => 'Örn: 450.000 km veya 05.2027',
        ],
        'sanayi' => [
            'ad' => 'Atölye iş emri', 'ikon' => '🏭', 'kayit' => 'İş emri', 'liste' => 'İş emirleri',
            'alanlar' => [
                ['parca', 'Parça / iş adı', 'text', null, true],
                ['malzeme', 'Malzeme', 'select', ['Çelik', 'Paslanmaz', 'Alüminyum', 'Pirinç / Bronz', 'Döküm', 'Plastik / Teflon', 'Müşteri malzemesi', 'Diğer']],
                ['adet', 'Adet', 'number'], ['olcu', 'Ölçü / tolerans', 'text'], ['resim', 'Teknik resim no', 'text', null, false, true],
                ['makine', 'Tezgâh', 'select', ['Torna', 'CNC torna', 'CNC freze', 'Freze', 'Kaynak', 'Pres / büküm', 'Taşlama', 'Lazer', 'Diğer']],
                ['siparis', 'Müşteri sipariş no', 'text'],
            ],
            'baslik' => ['parca', 'adet'],
            'kontrol' => ['Ölçü kontrolü', 'Tolerans kontrolü', 'Yüzey kalitesi', 'Çapak alma', 'Isıl işlem', 'Kaplama / boya', 'Paketleme'],
            'hizli' => [['iscilik', 'Torna işçiliği (saat)'], ['iscilik', 'CNC işçiliği (saat)'], ['iscilik', 'Kaynak işçiliği'], ['parca', 'Malzeme'],
                ['diger', 'Isıl işlem'], ['diger', 'Kaplama']],
            'bakim' => '',
        ],
        'cihaz' => [
            'ad' => 'Cihaz servisi', 'ikon' => '🔥', 'kayit' => 'Cihaz', 'liste' => 'Cihaz kayıtları',
            'alanlar' => [
                ['cihaz', 'Cihaz', 'select', ['Kombi', 'Klima', 'Şofben / Termosifon', 'Buzdolabı', 'Çamaşır makinesi', 'Bulaşık makinesi', 'Fırın / Ocak', 'Televizyon', 'Diğer'], true],
                ['marka', 'Marka', 'text'], ['model', 'Model', 'text'], ['seri', 'Seri no', 'text', null, false, true],
                ['adres', 'Servis adresi', 'text'], ['garanti_durum', 'Garanti', 'select', ['Garanti dışı', 'Garantili']],
            ],
            'baslik' => ['cihaz', 'marka', 'model'],
            'kontrol' => ['Genel görsel kontrol', 'Filtre temizliği', 'Su / gaz basıncı', 'Baca / egzoz kontrolü', 'Yanma analizi', 'Eşanjör temizliği',
                'Elektrik bağlantıları', 'Kaçak testi', 'Çalışma testi'],
            'hizli' => [['iscilik', 'Servis işçiliği'], ['iscilik', 'Periyodik bakım'], ['parca', 'Yedek parça'], ['diger', 'Gaz dolumu'], ['diger', 'Yol / ulaşım']],
            'bakim' => 'Örn: 10.2027 (yıllık bakım)',
        ],
        'yerinde' => [
            'ad' => 'Yerinde iş', 'ikon' => '🏠', 'kayit' => 'İş', 'liste' => 'İş kayıtları',
            'alanlar' => [
                ['is', 'Yapılacak iş', 'text', null, true],
                ['adres', 'İş adresi', 'text'], ['olcu', 'Metrekare / miktar', 'text'],
            ],
            'baslik' => ['is', 'adres'],
            'kontrol' => ['Keşif yapıldı', 'Malzeme alındı', 'İş tamamlandı', 'Alan temizlendi', 'Müşteri kontrol etti'],
            'hizli' => [['iscilik', 'İşçilik'], ['parca', 'Malzeme'], ['diger', 'Keşif'], ['diger', 'Yol / ulaşım']],
            'bakim' => '',
        ],
        'nakliyat' => [
            'ad' => 'Taşıma işi', 'ikon' => '🚚', 'kayit' => 'Taşıma', 'liste' => 'Taşıma kayıtları',
            'alanlar' => [
                ['nereden', 'Nereden', 'text', null, true], ['nereye', 'Nereye', 'text', null, true],
                ['tasima_tarihi', 'Taşıma tarihi', 'date'], ['kat', 'Kat bilgisi', 'text'],
                ['asansor', 'Asansör', 'select', ['Gerekmiyor', 'Bina asansörü', 'Dış cephe asansörü']],
                ['arac_plaka', 'Taşıyan araç plakası', 'text'],
                ['esyalar', 'Eşya listesi', 'textarea'],
            ],
            'baslik' => ['nereden', 'nereye'],
            'kontrol' => ['Ambalajlama', 'Söküm', 'Yükleme', 'Taşıma', 'Montaj', 'Eşya kontrolü (hasarsız teslim)'],
            'hizli' => [['iscilik', 'Taşıma ücreti'], ['parca', 'Ambalaj malzemesi'], ['diger', 'Asansör'], ['iscilik', 'Söküm / montaj'], ['iscilik', 'Hammaliye']],
            'bakim' => '',
        ],
    ];
}

function servis_durumlari(): array
{
    return [
        'kabul' => ['Kabul edildi', 'p-blue'], 'islemde' => ['İşlemde', 'p-amber'], 'parca' => ['Parça / malzeme bekleniyor', 'p-amber'],
        'hazir' => ['Hazır, teslim bekliyor', 'p-green'], 'teslim' => ['Teslim edildi', 'p-green'], 'iptal' => ['İptal', 'p-red'],
    ];
}

function servis_durum_pill(string $d): string
{
    [$ad, $c] = servis_durumlari()[$d] ?? [$d, 'p-blue'];
    return '<span class="pill ' . $c . '">' . e($ad) . '</span>';
}

const ODEME_YONTEMLERI = ['nakit' => 'Nakit', 'kart' => 'Kredi kartı', 'havale' => 'Havale / EFT', 'diger' => 'Diğer'];
const KALEM_TURLERI = ['iscilik' => 'İşçilik', 'parca' => 'Parça / malzeme', 'diger' => 'Diğer'];
const GIDER_KATEGORILERI = ['Malzeme / parça alımı', 'Kira', 'Elektrik / su / doğalgaz', 'Personel', 'Yakıt', 'Vergi / SGK', 'Alet / ekipman', 'Diğer'];
const GELIR_KATEGORILERI = ['Diğer satış', 'Hurda satışı', 'Diğer'];

/** Ustanın hizmetlerine bakarak varsayılan sektörü bulur. */
function usta_sektor(array $u): string
{
    if (!empty($u['servis_sektor']) && isset(sektorler()[$u['servis_sektor']])) return $u['servis_sektor'];
    $r = rows('SELECT k.slug AS ks, h.slug AS hs FROM usta_hizmet uh JOIN hizmetler h ON h.id = uh.hizmet_id JOIN kategoriler k ON k.id = h.kategori_id WHERE uh.usta_id = ?', [$u['id']]);
    $puan = [];
    foreach ($r as $x) {
        $s = match ($x['ks']) {
            'oto-ve-arac' => 'arac',
            'agir-vasita-ve-tir' => 'agir',
            'sanayi-ve-atolye' => 'sanayi',
            'kombi-ve-klima' => 'cihaz',
            'nakliyat' => 'nakliyat',
            'tamir-ve-montaj' => in_array($x['hs'], ['beyaz-esya-servisi', 'televizyon-tamiri'], true) ? 'cihaz' : 'yerinde',
            default => 'yerinde',
        };
        $puan[$s] = ($puan[$s] ?? 0) + 1;
    }
    arsort($puan);
    return array_key_first($puan) ?? 'yerinde';
}

/* ---------- Para ---------- */
function para(int|float|string|null $n): string
{
    return number_format((float)$n, 2, ',', '.') . ' ₺';
}

/** "1.250,50", "1250,5", "1250.50" gibi girişleri sayıya çevirir. */
function para_oku(string $s): float
{
    $s = trim(str_replace(['₺', ' ', 'TL', 'tl'], '', $s));
    if ($s === '') return 0.0;
    if (str_contains($s, ',')) $s = str_replace(['.', ','], ['', '.'], $s);   // 1.250,50
    elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $s)) $s = str_replace('.', '', $s); // 2.500 veya 1.250.000 (binlik ayırıcı)
    return round(max(0, (float)$s), 2);
}

function anahtar_norm(string $s): string
{
    return preg_replace('/[^A-Z0-9ÇĞİÖŞÜ]/u', '', tr_upper($s));
}

/** Kayıt başlığını sektör alanlarından oluşturur: "55 ABC 123 · Ford Focus" */
function servis_baslik(string $sektor, array $ozel): string
{
    $t = sektorler()[$sektor];
    $p = array_values(array_filter(array_map(fn($k) => trim((string)($ozel[$k] ?? '')), $t['baslik']), 'strlen'));
    if (!$p) return $t['kayit'];
    if ($sektor === 'nakliyat') return implode(' → ', $p);
    if ($sektor === 'sanayi' && count($p) === 2) return $p[0] . ' × ' . $p[1];
    $ilk = array_shift($p);
    return $p ? $ilk . ' · ' . implode(' ', $p) : $ilk;
}

/** Servisin tutarlarını hesaplar. */
function servis_hesap(array $s, array $kalemler, array $odemeler): array
{
    $ara = 0.0;
    foreach ($kalemler as $k) $ara += round((float)$k['adet'] * (float)$k['birim_fiyat'], 2);
    $isk = min($ara, (float)$s['iskonto']);
    $matrah = $ara - $isk;
    $kdv = round($matrah * (int)$s['kdv'] / 100, 2);
    $toplam = round($matrah + $kdv, 2);
    $odenen = round(array_sum(array_map(fn($o) => (float)$o['tutar'], $odemeler)), 2);
    return ['ara' => $ara, 'iskonto' => $isk, 'kdv' => $kdv, 'toplam' => $toplam, 'odenen' => $odenen, 'kalan' => round($toplam - $odenen, 2)];
}

/** Liste ve özetler için: her servisin toplam ve ödenen tutarını SQL ile hesaplayan alt sorgu. */
function servis_tutar_sql(): string
{
    return "(SELECT ROUND((COALESCE(SUM(k.adet * k.birim_fiyat),0) - LEAST(COALESCE(SUM(k.adet * k.birim_fiyat),0), s.iskonto)) * (1 + s.kdv / 100), 2) FROM servis_kalemleri k WHERE k.servis_id = s.id)";
}

function servis_odenen_sql(): string
{
    return "(SELECT COALESCE(SUM(o.tutar),0) FROM servis_odemeleri o WHERE o.servis_id = s.id)";
}

function rapor_url(array $s): string
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($host ? ($https ? 'https://' : 'http://') . $host : '') . url('rapor.php?t=' . $s['rapor_token']);
}
