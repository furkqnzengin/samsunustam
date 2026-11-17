<?php
declare(strict_types=1);

// Veritabanı sürümü. Kod güncellendiğinde gereken tablo/veri değişiklikleri
// ilk istekte otomatik uygulanır; kurulumu tekrarlamak gerekmez.
const DB_SURUM = 2;

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
    if ((int)ayar('db_surum', '1') >= DB_SURUM) return;
    try {
        db()->query('SELECT 1 FROM ustalar LIMIT 1');
    } catch (Throwable) {
        return; // henüz kurulum yapılmamış
    }
    // Sürüm 2: sanayi kategorileri ve usta dükkan adresi
    if (!row("SHOW COLUMNS FROM ustalar LIKE 'adres'")) {
        try {
            db()->exec('ALTER TABLE ustalar ADD COLUMN adres VARCHAR(255) NULL AFTER whatsapp');
        } catch (Throwable) {
            // aynı anda başka bir istek eklemiş olabilir
        }
    }
    seed_kategoriler(true);
    ayar_kaydet('db_surum', (string)DB_SURUM);
}
