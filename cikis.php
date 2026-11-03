<?php
require __DIR__ . '/inc/init.php';
csrf_check();
cikis_yap();
flash('Çıkış yaptınız.', 'info');
redirect('');
