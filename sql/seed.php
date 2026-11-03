<?php
// Başlangıç verileri: kurulumda bir kez eklenir, sonra yönetim panelinden düzenlenir.
return [
    'kategoriler' => [
        ['Tamir & Montaj', '🔧', 'Evdeki her arıza için doğru usta.', ['Elektrikçi', 'Su Tesisatçısı', 'Tıkanıklık Açma', 'Su Kaçağı Tespiti', 'Beyaz Eşya Servisi', 'Televizyon Tamiri', 'Uydu Kurulumu', 'Avize Montajı', 'Mobilya Montajı', 'Kapı & Kilit Tamiri', 'Çilingir', 'Güvenlik Kamerası']],
        ['Tadilat & Dekor', '🏠', 'Küçük dokunuştan anahtar teslim tadilata.', ['Boya Badana', 'Fayans Döşeme', 'Mutfak Dolabı', 'Banyo Tadilatı', 'Duşakabin', 'Alçı & Kartonpiyer', 'Asma Tavan', 'Laminat Parke', 'Duvar Kağıdı', 'Mantolama', 'Çatı Tamiri', 'İç Mimar', 'Anahtar Teslim Tadilat']],
        ['Kombi & Klima', '🔥', 'Kışa hazır, yaza serin.', ['Kombi Bakımı', 'Kombi Tamiri', 'Petek Temizleme', 'Doğalgaz Tesisatı', 'Klima Montajı', 'Klima Bakımı', 'Demirdöküm Servisi', 'ECA Servisi', 'Vaillant Servisi', 'Baymak Servisi', 'Bosch Servisi', 'Termosifon']],
        ['Temizlik', '🧽', 'Ev, ofis, inşaat sonrası; pırıl pırıl.', ['Ev Temizliği', 'Boş Ev Temizliği', 'İnşaat Sonrası Temizlik', 'Ofis Temizliği', 'Merdiven Temizliği', 'Halı Yıkama', 'Koltuk Yıkama', 'Yatak Yıkama', 'Cam Temizliği', 'Dış Cephe Temizliği', 'İlaçlama', 'Su Deposu Temizliği']],
        ['Nakliyat', '🚚', 'Tek parçadan komple eve.', ['Evden Eve Nakliyat', 'Şehirler Arası Nakliyat', 'Parça Eşya Taşıma', 'Koltuk Taşıma', 'Beyaz Eşya Taşıma', 'Ofis Taşıma', 'Asansörlü Taşıma', 'Eşya Depolama', 'Kamyonet Kiralama']],
        ['Mobilya & Cam', '🪚', 'Ölçüye özel, sağlam işçilik.', ['Marangoz', 'Mobilya Tamiri', 'Özel Mobilya Yapımı', 'Gardırop Yapımı', 'Kapı Yapımı', 'Döşemeci', 'Cam Balkon', 'PVC Pencere', 'Sineklik', 'Panjur & Kepenk', 'Alüminyum Doğrama', 'Cam Değişimi']],
        ['Oto & Araç', '🚗', 'Yolda kalma, ustan hazır.', ['Oto Çekici', 'Oto Tamir', 'Oto Elektrik', 'Oto Boya', 'Lastik Tamiri', 'Oto Kuaför', 'Akü Değişimi', 'Oto Cam']],
        ['Bahçe & Dış Mekan', '🌿', 'Bahçen de bakım ister.', ['Bahçe Bakımı', 'Ağaç Kesimi', 'Çim Ekimi', 'Peyzaj', 'Havuz Bakımı', 'Kamelya & Pergola']],
    ],
    'ilceler' => ['Atakum', 'İlkadım', 'Canik', 'Tekkeköy', 'Bafra', 'Çarşamba', 'Terme', 'Vezirköprü', 'Havza', 'Kavak', 'Ladik', 'Alaçam', '19 Mayıs', 'Asarcık', 'Ayvacık', 'Salıpazarı', 'Yakakent'],
    'paketler' => [
        ['Standart', 'Profilin sitede listelenir.', "Profil sayfası\nAra ve WhatsApp butonları\nİş talepleri\nYorumlar ve puan\nİstatistikler", 0],
        ['Premium', 'Aramalarda en üstte görün, daha çok müşteriye ulaş.', "Standart paketteki her şey\nArama sonuçlarında üst sıralarda\nAna sayfada öne çıkan ustalarda\nPremium rozeti\nÖncelikli destek", 1],
    ],
    // Ana sayfadaki arama cümlesinde ilk görünecek hizmetler
    'populer_hizmetler' => ['Elektrikçi', 'Su Tesisatçısı', 'Kombi Tamiri', 'Boya Badana', 'Evden Eve Nakliyat', 'Ev Temizliği', 'Çilingir'],
];
