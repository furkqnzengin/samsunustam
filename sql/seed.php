<?php
// Başlangıç verileri. Sürüm güncellemelerinde yeni eklenen kategori ve hizmetler mevcut siteye de eklenir.
return [
    'kategoriler' => [
        ['Tamir & Montaj', '🔧', 'Evdeki her arıza için doğru usta.', ['Elektrikçi', 'Su Tesisatçısı', 'Tıkanıklık Açma', 'Su Kaçağı Tespiti', 'Beyaz Eşya Servisi', 'Televizyon Tamiri', 'Uydu Kurulumu', 'Avize Montajı', 'Mobilya Montajı', 'Kapı & Kilit Tamiri', 'Çilingir', 'Güvenlik Kamerası']],
        ['Tadilat & Dekor', '🏠', 'Küçük dokunuştan anahtar teslim tadilata.', ['Boya Badana', 'Fayans Döşeme', 'Mutfak Dolabı', 'Banyo Tadilatı', 'Duşakabin', 'Alçı & Kartonpiyer', 'Asma Tavan', 'Laminat Parke', 'Duvar Kağıdı', 'Mantolama', 'Çatı Tamiri', 'İç Mimar', 'Anahtar Teslim Tadilat']],
        ['Kombi & Klima', '🔥', 'Kışa hazır, yaza serin.', ['Kombi Bakımı', 'Kombi Tamiri', 'Petek Temizleme', 'Doğalgaz Tesisatı', 'Klima Montajı', 'Klima Bakımı', 'Demirdöküm Servisi', 'ECA Servisi', 'Vaillant Servisi', 'Baymak Servisi', 'Bosch Servisi', 'Termosifon']],
        ['Temizlik', '🧽', 'Ev, ofis, inşaat sonrası; pırıl pırıl.', ['Ev Temizliği', 'Boş Ev Temizliği', 'İnşaat Sonrası Temizlik', 'Ofis Temizliği', 'Merdiven Temizliği', 'Halı Yıkama', 'Koltuk Yıkama', 'Yatak Yıkama', 'Cam Temizliği', 'Dış Cephe Temizliği', 'İlaçlama', 'Su Deposu Temizliği']],
        ['Nakliyat', '🚚', 'Tek parçadan komple eve.', ['Evden Eve Nakliyat', 'Şehirler Arası Nakliyat', 'Parça Eşya Taşıma', 'Koltuk Taşıma', 'Beyaz Eşya Taşıma', 'Ofis Taşıma', 'Asansörlü Taşıma', 'Eşya Depolama', 'Kamyonet Kiralama']],
        ['Mobilya & Cam', '🪚', 'Ölçüye özel, sağlam işçilik.', ['Marangoz', 'Mobilya Tamiri', 'Özel Mobilya Yapımı', 'Gardırop Yapımı', 'Kapı Yapımı', 'Döşemeci', 'Cam Balkon', 'PVC Pencere', 'Sineklik', 'Panjur & Kepenk', 'Alüminyum Doğrama', 'Cam Değişimi']],
        ['Oto & Araç', '🚗', 'Yolda kalma, ustan hazır.', ['Oto Çekici', 'Oto Tamir', 'Oto Elektrik', 'Oto Boya', 'Lastik Tamiri', 'Oto Kuaför', 'Akü Değişimi', 'Oto Cam',
            'Araç Periyodik Bakım', 'Motor Tamiri', 'Kaporta', 'Şanzıman Tamiri', 'Fren & Balata', 'Rot Balans', 'Egzoz', 'Radyatör', 'Oto Klima', 'Oto Döşeme',
            'Dizel Pompa & Enjektör', 'Turbo Tamiri', 'LPG Montaj & Bakım', 'Oto Ekspertiz', 'Motosiklet Tamiri', 'Oto Yıkama']],
        ['Ağır Vasıta & Tır', '🚛', 'Tır, kamyon, otobüs ve iş makinesi bakımı.', ['Tır Bakım Servisi', 'Kamyon Tamiri', 'Dorse & Treyler Tamiri', 'Ağır Vasıta Elektrik',
            'Havalı Fren Sistemleri', 'Ağır Vasıta Şanzıman', 'Diferansiyel Tamiri', 'Takograf Servisi', 'Ağır Vasıta Lastik', 'Otobüs & Minibüs Tamiri',
            'İş Makinesi Tamiri', 'Forklift Servisi', 'Tır Kurtarma & Çekici']],
        ['Sanayi & Atölye', '🏭', 'Torna, kaynak, imalat ve endüstriyel tamir.', ['Tornacı', 'Frezeci', 'CNC İşleme', 'Kaynakçı', 'Argon Kaynak', 'Demir Doğrama',
            'Ferforje', 'Sac Büküm & Kesim', 'Lazer Kesim', 'Motor Rektefiye', 'Krank & Silindir Taşlama', 'Hidrolik Tamiri', 'Hidrolik Hortum Pres',
            'Bobinaj (Motor Sarım)', 'Endüstriyel Elektrik & Pano', 'Jeneratör Servisi', 'Kompresör Servisi', 'Kalıpçı', 'Elektrostatik Boya', 'Paslanmaz İmalat']],
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
