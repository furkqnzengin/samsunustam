<?php
// Bu dosyayı "config.php" adıyla kopyalayın ve hosting bilgilerinizle doldurun.
// config.php GitHub'a gönderilmez (şifreler içerir).
return [
    // cPanel > MySQL Veritabanları bölümünde oluşturduğunuz bilgiler
    'db_host' => 'localhost',
    'db_name' => 'kullanici_samsunustam',
    'db_user' => 'kullanici_dbuser',
    'db_pass' => 'veritabani-sifresi',

    // Site ana dizinde ise boş bırakın. Alt klasördeyse örn: '/samsunustam'
    'base' => '',

    // Hata ayıklama: canlı sitede mutlaka false olmalı
    'debug' => false,
];
