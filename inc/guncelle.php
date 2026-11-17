<?php
declare(strict_types=1);

// Veritabanı sürümü. Kod güncellendiğinde gereken tablo/veri değişiklikleri
// ilk istekte otomatik uygulanır; kurulumu tekrarlamak gerekmez.
const DB_SURUM = 3;

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
    ayar_kaydet('db_surum', (string)DB_SURUM);
}
