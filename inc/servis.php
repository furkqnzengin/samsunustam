<?php
declare(strict_types=1);

/*
 * Servis takip: sektöre özel alanlar, kontrol listeleri ve yardımcılar.
 * Alan tipi: text | number | select | date | textarea. "anahtar" => true olan alan
 * geçmiş kayıtları bulmak için kullanılır (plaka, seri no gibi).
 */
function sektorler(): array
{
    $yakit = ['Benzin', 'Dizel', 'LPG', 'Benzin + LPG', 'Hibrit', 'Elektrik'];
    $renk = ['Beyaz', 'Siyah', 'Gri', 'Gümüş', 'Kırmızı', 'Mavi', 'Lacivert', 'Yeşil', 'Sarı', 'Turuncu', 'Kahverengi', 'Bej', 'Bordo', 'Diğer'];
    $vites = ['Manuel', 'Otomatik', 'Yarı otomatik'];
    return [
        'arac' => [
            'ad' => 'Oto servis', 'ikon' => '🚗', 'kayit' => 'Araç', 'liste' => 'Araç kayıtları', 'kartlar' => 'Araç Kartları',
            'alanlar' => [
                ['plaka', 'Plaka', 'text', null, true, true],
                ['marka', 'Marka', 'datalist', 'oto'], ['seri', 'Seri', 'text'], ['model', 'Model / paket', 'text'],
                ['yil', 'Model yılı', 'number'], ['renk', 'Renk', 'select', $renk], ['yakit', 'Yakıt tipi', 'select', $yakit],
                ['vites', 'Vites', 'select', $vites], ['motor_gucu', 'Motor gücü', 'text'], ['motor_hacmi', 'Motor hacmi', 'text'],
                ['sasi', 'Şasi no', 'text'], ['motor_no', 'Motor no', 'text'],
                ['muayene', 'Muayene bitiş', 'date'], ['trafik', 'Trafik sigortası bitiş', 'date'], ['kasko', 'Kasko bitiş', 'date'],
            ],
            'baslik' => ['plaka', 'marka', 'seri'],
            'servis' => ['km' => 'Kilometre', 'yakit' => true, 'ekspertiz' => true, 'sigorta' => true],
            'kontrol' => ['Motor yağı', 'Yağ filtresi', 'Hava filtresi', 'Polen filtresi', 'Yakıt filtresi', 'Ön fren balatası', 'Arka fren balatası',
                'Fren hidroliği', 'Antifriz / soğutma', 'Akü', 'Triger / V kayışı', 'Silecekler', 'Lastik diş & basınç', 'Farlar & stop lambaları', 'Arıza tespit (OBD) taraması'],
            'hizli' => [['iscilik', 'İşçilik'], ['parca', 'Motor yağı'], ['parca', 'Yağ filtresi'], ['parca', 'Hava filtresi'], ['parca', 'Polen filtresi'],
                ['parca', 'Yakıt filtresi'], ['parca', 'Fren balatası'], ['iscilik', 'Arıza tespit (OBD)']],
            'bakim' => 'Örn: 120.000 km veya 05.2027',
        ],
        'agir' => [
            'ad' => 'Ağır vasıta servisi', 'ikon' => '🚛', 'kayit' => 'Araç', 'liste' => 'Araç kayıtları', 'kartlar' => 'Araç Kartları',
            'alanlar' => [
                ['plaka', 'Plaka', 'text', null, true, true],
                ['tip', 'Araç tipi', 'select', ['Tır çekici', 'Kamyon', 'Dorse / Treyler', 'Otobüs', 'Minibüs', 'Kamyonet', 'İş makinesi', 'Forklift']],
                ['marka', 'Marka', 'datalist', 'agir'], ['seri', 'Seri / model', 'text'], ['yil', 'Model yılı', 'number'],
                ['dorse', 'Dorse plakası', 'text'], ['sasi', 'Şasi no', 'text'], ['motor_no', 'Motor no', 'text'],
                ['muayene', 'Muayene bitiş', 'date'], ['takograf', 'Takograf muayene bitiş', 'date'],
                ['trafik', 'Trafik sigortası bitiş', 'date'], ['kasko', 'Kasko bitiş', 'date'],
            ],
            'baslik' => ['plaka', 'marka', 'seri'],
            'servis' => ['km' => 'Km / çalışma saati', 'yakit' => true, 'ekspertiz' => true, 'sigorta' => true],
            'kontrol' => ['Motor yağı & filtresi', 'Yakıt filtresi / separatör', 'Hava filtresi', 'Hava kurutucu filtresi', 'Havalı fren kaçak kontrolü',
                'Fren balata / kampana', 'Şanzıman yağı', 'Diferansiyel yağı', 'Gresleme', 'AdBlue sistemi', 'Soğutma sistemi', 'Lastik & jant',
                'Aydınlatma', 'Takograf kalibrasyonu'],
            'hizli' => [['iscilik', 'İşçilik'], ['parca', 'Motor yağı'], ['parca', 'Yağ filtresi'], ['parca', 'Yakıt filtresi'], ['parca', 'Hava kurutucu filtresi'],
                ['iscilik', 'Gresleme'], ['parca', 'Fren balatası'], ['iscilik', 'Takograf kalibrasyonu']],
            'bakim' => 'Örn: 450.000 km veya 05.2027',
        ],
        'sanayi' => [
            'ad' => 'Atölye iş emri', 'ikon' => '🏭', 'kayit' => 'İş emri', 'liste' => 'İş emirleri', 'kartlar' => 'Parça Kartları',
            'alanlar' => [
                ['parca', 'Parça / iş adı', 'text', null, true], ['resim', 'Teknik resim no', 'text', null, false, true],
                ['malzeme', 'Malzeme', 'select', ['Çelik', 'Paslanmaz', 'Alüminyum', 'Pirinç / Bronz', 'Döküm', 'Plastik / Teflon', 'Müşteri malzemesi', 'Diğer']],
                ['adet', 'Adet', 'number'], ['olcu', 'Ölçü / tolerans', 'text'],
                ['makine', 'Tezgâh', 'select', ['Torna', 'CNC torna', 'CNC freze', 'Freze', 'Kaynak', 'Pres / büküm', 'Taşlama', 'Lazer', 'Diğer']],
                ['siparis', 'Müşteri sipariş no', 'text'],
            ],
            'baslik' => ['parca', 'adet'],
            'servis' => [],
            'kontrol' => ['Ölçü kontrolü', 'Tolerans kontrolü', 'Yüzey kalitesi', 'Çapak alma', 'Isıl işlem', 'Kaplama / boya', 'Paketleme'],
            'hizli' => [['iscilik', 'Torna işçiliği (saat)'], ['iscilik', 'CNC işçiliği (saat)'], ['iscilik', 'Kaynak işçiliği'], ['parca', 'Malzeme'],
                ['diger', 'Isıl işlem'], ['diger', 'Kaplama']],
            'bakim' => '',
        ],
        'cihaz' => [
            'ad' => 'Cihaz servisi', 'ikon' => '🔥', 'kayit' => 'Cihaz', 'liste' => 'Cihaz kayıtları', 'kartlar' => 'Cihaz Kartları',
            'alanlar' => [
                ['cihaz', 'Cihaz', 'select', ['Kombi', 'Klima', 'Şofben / Termosifon', 'Buzdolabı', 'Çamaşır makinesi', 'Bulaşık makinesi', 'Fırın / Ocak', 'Televizyon', 'Diğer'], true],
                ['marka', 'Marka', 'datalist', 'cihaz'], ['model', 'Model', 'text'], ['seri', 'Seri no', 'text', null, false, true],
                ['adres', 'Servis adresi', 'text'], ['kurulum', 'Kurulum tarihi', 'date'], ['garanti_bitis', 'Garanti bitiş', 'date'],
                ['garanti_durum', 'Garanti', 'select', ['Garanti dışı', 'Garantili']],
            ],
            'baslik' => ['cihaz', 'marka', 'model'],
            'servis' => [],
            'kontrol' => ['Genel görsel kontrol', 'Filtre temizliği', 'Su / gaz basıncı', 'Baca / egzoz kontrolü', 'Yanma analizi', 'Eşanjör temizliği',
                'Elektrik bağlantıları', 'Kaçak testi', 'Çalışma testi'],
            'hizli' => [['iscilik', 'Servis işçiliği'], ['iscilik', 'Periyodik bakım'], ['parca', 'Yedek parça'], ['diger', 'Gaz dolumu'], ['diger', 'Yol / ulaşım']],
            'bakim' => 'Örn: 10.2027 (yıllık bakım)',
        ],
        'yerinde' => [
            'ad' => 'Yerinde iş', 'ikon' => '🏠', 'kayit' => 'İş', 'liste' => 'İş kayıtları', 'kartlar' => 'Adres / İş Kartları',
            'alanlar' => [
                ['is', 'Yapılacak iş', 'text', null, true],
                ['adres', 'İş adresi', 'text'], ['olcu', 'Metrekare / miktar', 'text'],
            ],
            'baslik' => ['is', 'adres'],
            'servis' => [],
            'kontrol' => ['Keşif yapıldı', 'Malzeme alındı', 'İş tamamlandı', 'Alan temizlendi', 'Müşteri kontrol etti'],
            'hizli' => [['iscilik', 'İşçilik'], ['parca', 'Malzeme'], ['diger', 'Keşif'], ['diger', 'Yol / ulaşım']],
            'bakim' => '',
        ],
        'nakliyat' => [
            'ad' => 'Taşıma işi', 'ikon' => '🚚', 'kayit' => 'Taşıma', 'liste' => 'Taşıma kayıtları', 'kartlar' => 'Taşıma Kartları',
            'alanlar' => [
                ['nereden', 'Nereden', 'text', null, true], ['nereye', 'Nereye', 'text', null, true],
                ['tasima_tarihi', 'Taşıma tarihi', 'date'], ['kat', 'Kat bilgisi', 'text'],
                ['asansor', 'Asansör', 'select', ['Gerekmiyor', 'Bina asansörü', 'Dış cephe asansörü']],
                ['arac_plaka', 'Taşıyan araç plakası', 'text'],
                ['esyalar', 'Eşya listesi', 'textarea'],
            ],
            'baslik' => ['nereden', 'nereye'],
            'servis' => [],
            'kontrol' => ['Ambalajlama', 'Söküm', 'Yükleme', 'Taşıma', 'Montaj', 'Eşya kontrolü (hasarsız teslim)'],
            'hizli' => [['iscilik', 'Taşıma ücreti'], ['parca', 'Ambalaj malzemesi'], ['diger', 'Asansör'], ['iscilik', 'Söküm / montaj'], ['iscilik', 'Hammaliye']],
            'bakim' => '',
        ],
    ];
}

/** Marka önerileri (yazarak seçilebilen liste). */
function marka_listesi(string $tur): array
{
    return match ($tur) {
        'oto' => ['Alfa Romeo', 'Audi', 'BMW', 'BYD', 'Chery', 'Chevrolet', 'Citroën', 'Cupra', 'Dacia', 'DS', 'Fiat', 'Ford', 'Honda', 'Hyundai', 'Jaguar', 'Jeep',
            'Kia', 'Land Rover', 'Lexus', 'Mazda', 'Mercedes-Benz', 'MG', 'Mini', 'Mitsubishi', 'Nissan', 'Opel', 'Peugeot', 'Porsche', 'Renault', 'Seat',
            'Skoda', 'Subaru', 'Suzuki', 'Tesla', 'Togg', 'Toyota', 'Volkswagen', 'Volvo'],
        'agir' => ['BMC', 'DAF', 'Ford Trucks', 'Hyundai', 'Isuzu', 'Iveco', 'Karsan', 'MAN', 'Mercedes-Benz', 'Mitsubishi Fuso', 'Otokar', 'Renault Trucks', 'Scania', 'Temsa', 'Volvo'],
        'cihaz' => ['Airfel', 'Arçelik', 'Ariston', 'Baymak', 'Beko', 'Bosch', 'Buderus', 'Daikin', 'Demirdöküm', 'ECA', 'LG', 'Mitsubishi Electric', 'Profilo',
            'Samsung', 'Siemens', 'Toshiba', 'Vaillant', 'Vestel', 'Viessmann'],
        default => [],
    };
}

const KALEM_BIRIMLERI = ['Adet', 'Saat', 'Gün', 'Lt', 'Kg', 'Mt', 'm²', 'Takım', 'Set', 'Paket', 'Hizmet'];
const KDV_ORANLARI = [0, 1, 10, 20];
const EKSPERTIZ_PARCALAR = [
    'on_tampon' => 'Ön tampon', 'kaput' => 'Motor kaputu', 'tavan' => 'Tavan', 'bagaj' => 'Bagaj kapağı', 'arka_tampon' => 'Arka tampon',
    'sol_on_camurluk' => 'Sol ön çamurluk', 'sol_on_kapi' => 'Sol ön kapı', 'sol_arka_kapi' => 'Sol arka kapı', 'sol_arka_camurluk' => 'Sol arka çamurluk',
    'sag_on_camurluk' => 'Sağ ön çamurluk', 'sag_on_kapi' => 'Sağ ön kapı', 'sag_arka_kapi' => 'Sağ arka kapı', 'sag_arka_camurluk' => 'Sağ arka çamurluk',
];
const HASAR_TURLERI = ['cizik' => ['Çizik', '#FFB020'], 'gocuk' => ['Göçük', '#FF8431'], 'kirik' => ['Kırık / çatlak', '#E5484D']];

function servis_durumlari(): array
{
    return [
        'kabul' => ['Servise alınıyor', 'p-blue'], 'islemde' => ['Bakım / onarımda', 'p-amber'], 'parca' => ['Parça bekliyor', 'p-amber'],
        'hazir' => ['Teslime hazır', 'p-green'], 'teslim' => ['Teslim edildi', 'p-green'], 'iptal' => ['İptal', 'p-red'],
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

/** Servisin tutarlarını hesaplar. Birim fiyatlar KDV hariçtir; satır KDV'si boşsa (eski kayıtlar) servisin genel KDV'si kullanılır. */
function servis_hesap(array $s, array $kalemler, array $odemeler): array
{
    $ara = $kdvsiz = $kdv = 0.0;
    foreach ($kalemler as $k) {
        $net = round((float)$k['adet'] * (float)$k['birim_fiyat'], 2);
        $ara += $net;
        $kdv += $net * (int)($k['kdv'] ?? $s['kdv']) / 100;
    }
    $isk = min($ara, (float)$s['iskonto']);
    $oran = $ara > 0 ? ($ara - $isk) / $ara : 0;
    $kdv = round($kdv * $oran, 2);
    $toplam = round($ara - $isk + $kdv, 2);
    $odenen = round(array_sum(array_map(fn($o) => (float)$o['tutar'], $odemeler)), 2);
    return ['ara' => $ara, 'iskonto' => $isk, 'kdv' => $kdv, 'toplam' => $toplam, 'odenen' => $odenen, 'kalan' => round($toplam - $odenen, 2)];
}

/** Liste ve özetler için: her servisin KDV dahil toplamını SQL ile hesaplayan alt sorgu. */
function servis_tutar_sql(): string
{
    return "(SELECT ROUND(IF(SUM(k.adet * k.birim_fiyat) > 0, SUM(k.adet * k.birim_fiyat * (1 + COALESCE(k.kdv, s.kdv) / 100)) * (SUM(k.adet * k.birim_fiyat) - LEAST(SUM(k.adet * k.birim_fiyat), s.iskonto)) / SUM(k.adet * k.birim_fiyat), 0), 2) FROM servis_kalemleri k WHERE k.servis_id = s.id)";
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

/** Usta paneli liste satırları için ortak servis sorgusu (toplam, ödenen, müşteri, kart). */
function servis_liste_sql(string $where): string
{
    $t = servis_tutar_sql(); $o = servis_odenen_sql();
    return "SELECT * FROM (SELECT s.*, $t AS toplam, $o AS odenen, k.ozel AS kart_ozel, k.baslik AS kart_baslik
            FROM servisler s LEFT JOIN kartlar k ON k.id = s.kart_id WHERE $where) x";
}

/** Listelerde plaka (varsa) ya da başlık rozeti. */
function satir_rozet(array $r): string
{
    $ozel = json_decode((string)($r['kart_ozel'] ?? $r['ozel'] ?? ''), true) ?: [];
    return !empty($ozel['plaka']) ? '<span class="plaka k"><i>TR</i>' . e(tr_upper($ozel['plaka'])) . '</span>' : '<b>' . e($r['baslik']) . '</b>';
}

/** CSV (Excel) çıktısı gönderir ve çıkar. */
function csv_gonder(string $dosya, array $baslik, array $satirlar): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $dosya . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $baslik, ';');
    foreach ($satirlar as $r) fputcsv($out, $r, ';');
    exit;
}
