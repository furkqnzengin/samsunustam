<?php
declare(strict_types=1);

// Veritabanı sürümü. Kod güncellendiğinde gereken tablo/veri değişiklikleri
// ilk istekte otomatik uygulanır; kurulumu tekrarlamak gerekmez.
const DB_SURUM = 6;

/** schema.sql'deki tüm CREATE TABLE IF NOT EXISTS ifadelerini çalıştırır (eksik tabloları oluşturur). */
function sema_uygula(): void
{
    $sql = preg_replace('/^--.*$/m', '', file_get_contents(ROOT . '/sql/schema.sql'));
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) db()->exec($stmt);
}

function kolon_ekle(string $tablo, string $kolon, string $tanim): void
{
    if (row("SHOW COLUMNS FROM `$tablo` LIKE " . db()->quote($kolon))) return;
    try {
        db()->exec("ALTER TABLE `$tablo` ADD COLUMN `$kolon` $tanim");
    } catch (Throwable) {
        // aynı anda başka bir istek eklemiş olabilir
    }
}

/** Seed dosyasındaki kategori ve hizmetlerden eksik olanları ekler (var olanlara dokunmaz). */
function seed_kategoriler(bool $siraGuncelle = false): void
{
    $seed = require ROOT . '/sql/seed.php';
    foreach ($seed['kategoriler'] as $ki => [$kad, $ikon, $acik, $hizmetler]) {
        $slug = slugify($kad);
        $kid = val('SELECT id FROM kategoriler WHERE slug = ?', [$slug]);
        if (!$kid) {
            q('INSERT INTO kategoriler (ad, slug, ikon, aciklama, sira) VALUES (?,?,?,?,?)', [$kad, $slug, $ikon, $acik, $ki]);
            $kid = db()->lastInsertId();
        } elseif ($siraGuncelle) {
            q('UPDATE kategoriler SET sira = ? WHERE id = ?', [$ki, $kid]);
        }
        foreach ($hizmetler as $hi => $h) {
            q('INSERT IGNORE INTO hizmetler (kategori_id, ad, slug, sira) VALUES (?,?,?,?)', [(int)$kid, $h, slugify($h), $hi]);
        }
    }
}

/** Eski servis kayıtlarından müşteri ve araç/cihaz kartları oluşturur (sürüm 6). */
function servisleri_kartlara_tasi(): void
{
    foreach (rows('SELECT * FROM servisler WHERE musteri_id IS NULL OR kart_id IS NULL ORDER BY id') as $s) {
        $mid = $s['musteri_id'];
        if (!$mid) {
            $mid = $s['musteri_tel']
                ? val('SELECT id FROM musteriler WHERE usta_id = ? AND telefon = ? LIMIT 1', [$s['usta_id'], $s['musteri_tel']])
                : val('SELECT id FROM musteriler WHERE usta_id = ? AND ad_soyad = ? AND telefon IS NULL LIMIT 1', [$s['usta_id'], $s['musteri_ad']]);
            if (!$mid) {
                q('INSERT INTO musteriler (usta_id, tip, ad_soyad, unvan, telefon) VALUES (?,?,?,?,?)',
                    [$s['usta_id'], $s['musteri_firma'] ? 'kurumsal' : 'bireysel', $s['musteri_ad'], $s['musteri_firma'], $s['musteri_tel']]);
                $mid = (int)db()->lastInsertId();
            }
        }
        $kid = $s['kart_id'];
        if (!$kid) {
            $kid = $s['anahtar'] ? val('SELECT id FROM kartlar WHERE usta_id = ? AND anahtar = ? LIMIT 1', [$s['usta_id'], $s['anahtar']]) : null;
            if (!$kid) {
                q('INSERT INTO kartlar (usta_id, musteri_id, sektor, anahtar, baslik, ozel) VALUES (?,?,?,?,?,?)',
                    [$s['usta_id'], $mid, $s['sektor'], $s['anahtar'], $s['baslik'], $s['ozel']]);
                $kid = (int)db()->lastInsertId();
            }
        }
        q('UPDATE servisler SET musteri_id = ?, kart_id = ? WHERE id = ?', [$mid, $kid, $s['id']]);
    }
}

function db_guncelle(): void
{
    $s = (int)ayar('db_surum', '1');
    if ($s >= DB_SURUM) return;
    try {
        db()->query('SELECT 1 FROM ustalar LIMIT 1');
    } catch (Throwable) {
        return; // henüz kurulum yapılmamış
    }
    if ($s < 2) {
        // Sürüm 2: sanayi kategorileri ve usta dükkan adresi
        kolon_ekle('ustalar', 'adres', 'VARCHAR(255) NULL AFTER whatsapp');
        seed_kategoriler(true);
    }
    if ($s < 3) {
        // Sürüm 3: servis takip ve gelir-gider
        kolon_ekle('ustalar', 'servis_sektor', 'VARCHAR(20) NULL');
        kolon_ekle('ustalar', 'rapor_notu', 'TEXT NULL');
        sema_uygula();
    }
    if ($s < 4) {
        // Sürüm 4: iş ilanları ve iş arayanlar
        sema_uygula();
    }
    if ($s < 5) {
        // Sürüm 5: "beni hatırla" girişleri
        sema_uygula();
    }
    if ($s < 6) {
        // Sürüm 6: müşteri/araç kartları, personel, servis kabul alanları
        sema_uygula();
        foreach (['firma_unvan' => 'VARCHAR(150) NULL', 'firma_logo' => 'VARCHAR(255) NULL', 'vergi_dairesi' => 'VARCHAR(80) NULL',
                  'vergi_no' => 'VARCHAR(20) NULL', 'kabul_metni' => 'TEXT NULL', 'teslim_metni' => 'TEXT NULL',
                  'kdv_varsayilan' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0'] as $k => $t) kolon_ekle('ustalar', $k, $t);
        foreach (['musteri_id' => 'INT UNSIGNED NULL', 'kart_id' => 'INT UNSIGNED NULL', 'danisman' => 'VARCHAR(100) NULL',
                  'teknisyen' => 'VARCHAR(100) NULL', 'getiren' => 'VARCHAR(255) NULL', 'km' => 'INT UNSIGNED NULL',
                  'yakit' => 'TINYINT UNSIGNED NULL', 'ekspertiz' => 'TEXT NULL', 'sigorta' => 'TEXT NULL', 'musteri_notu' => 'TEXT NULL',
                  'etiket' => 'VARCHAR(60) NULL', 'imza_kabul_usta' => 'MEDIUMTEXT NULL', 'imza_kabul_musteri' => 'MEDIUMTEXT NULL',
                  'imza_teslim_usta' => 'MEDIUMTEXT NULL', 'imza_teslim_musteri' => 'MEDIUMTEXT NULL'] as $k => $t) kolon_ekle('servisler', $k, $t);
        kolon_ekle('servis_kalemleri', 'birim', "VARCHAR(20) NOT NULL DEFAULT 'Adet'");
        kolon_ekle('servis_kalemleri', 'kdv', 'TINYINT UNSIGNED NULL');
        servisleri_kartlara_tasi();
    }
    ayar_kaydet('db_surum', (string)DB_SURUM);
}
