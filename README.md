# Samsun Ustam

Samsun'daki ustaları listeleyen yerel usta rehberi. Düz PHP + MySQL ile yazıldı. Herhangi bir Türk hosting firmasının (cPanel, Plesk vb.) standart paylaşımlı hostinginde çalışır, ek kurulum gerektirmez.

## Özellikler

**Ziyaretçiler**
- Ana sayfada "Bana bir ___ lazım, ___'da" cümlesiyle arama, kategori gezgini, haftanın en çok arananları, mevsimsel öneriler
- Hizmete, kategoriye ve ilçeye göre usta arama (Premium ustalar üstte)
- Usta profili: puan, yorumlar, iş fotoğrafları, Ara / WhatsApp butonları
- Yorum yazma (yönetici onayından sonra yayınlanır) ve ustaya iş talebi gönderme

**Usta paneli** (`/usta`)
- Kayıt ol → yönetici onayı → yayına çıkış
- Profil, hizmetler, ilçeler, profil fotoğrafı, ustalık belgesi
- İş fotoğrafları (en fazla 12)
- Gelen iş talepleri, yorumlara yanıt
- Görüntülenme / arama / WhatsApp istatistikleri
- Üyelik paketleri ("Bilgi almak istiyorum" talebi)

**Yönetim paneli** (`/yonetim`)
- Usta başvurularını onaylama / reddetme, askıya alma, silme
- Belgeli rozeti verme, paket atama (bitiş tarihli)
- Yorum onaylama / gizleme
- Kategori ve hizmet ekleme / düzenleme
- Üyelik paketleri (fiyat alanı boş bırakılırsa fiyat gösterilmez)
- Site iletişim bilgileri

## Gereksinimler

- PHP 8.1 veya üzeri (pdo_mysql, fileinfo; resim küçültme için GD önerilir)
- MySQL 5.7+ veya MariaDB 10.3+
- Apache (.htaccess desteği). Nginx kullanılıyorsa aşağıdaki "Nginx" notuna bakın.

## Hostinge kurulum (cPanel)

1. **Veritabanı oluştur:** cPanel → *MySQL Veritabanları*
   - Yeni veritabanı oluştur (ör. `kullanici_samsunustam`)
   - Yeni kullanıcı oluştur, güçlü bir şifre ver
   - Kullanıcıyı veritabanına ekle, **TÜM YETKİLER**i ver
2. **Dosyaları yükle:** GitHub'dan ZIP olarak indir (Code → Download ZIP). cPanel → *Dosya Yöneticisi* → `public_html` içine yükleyip çıkar. `tasarim` klasörünü yüklemene gerek yok.
3. **Ayar dosyası:** `config.sample.php` dosyasını kopyala, adını `config.php` yap ve 1. adımdaki veritabanı bilgilerini yaz.
4. **Kurulum:** Tarayıcıda `https://alanadin.com/kurulum/` adresini aç. Yönetici adını, e-postasını ve şifresini girip kurulumu başlat.
5. **Güvenlik:** Kurulum bitince `kurulum` klasörünü sunucudan **sil**.
6. **SSL:** cPanel → *SSL/TLS Status* üzerinden ücretsiz SSL'i (AutoSSL / Let's Encrypt) aç. Ardından `.htaccess` dosyasındaki HTTPS yönlendirme satırlarının başındaki `#` işaretlerini kaldır.
7. **Ayarlar:** Yönetim paneli → *Site Ayarları* bölümünden telefon, WhatsApp ve e-posta bilgilerini gir.

`uploads` klasörünün yazılabilir olması gerekir (genelde varsayılan olarak öyledir, değilse izni 755 yapın).

## Klasör yapısı

```
index.php, ara.php, usta.php   Herkese açık sayfalar
kayit.php, giris.php, cikis.php
git.php                        Ara/WhatsApp tıklama sayacı
gizlilik.php                   Gizlilik & KVKK metni (yayından önce hukukçuya kontrol ettirin)
usta/                          Usta paneli
yonetim/                       Yönetim paneli
kurulum/                       Tek seferlik kurulum (kurulumdan sonra silin)
inc/                           Ortak kod (dışarıdan erişime kapalı)
sql/                           Veritabanı şeması ve başlangıç verileri
assets/                        CSS
uploads/                       Yüklenen fotoğraflar (belgeler dışarıya kapalı)
tasarim/                       İlk statik tasarım taslakları (sadece referans)
```

## Güvenlik notları

- Şifreler `password_hash` ile saklanır, tüm formlarda CSRF koruması vardır.
- Tüm veritabanı sorguları hazırlanmış ifadelerle (prepared statements) çalışır.
- Yüklenen resimler içerik kontrolünden geçer ve rastgele isimle kaydedilir. `uploads` içinde PHP çalıştırılamaz.
- Ustalık belgeleri sadece yönetici panelinden görüntülenebilir.
- Yorum, talep ve kayıt formlarında bot tuzağı ve oturum bazlı hız sınırı vardır.

## Nginx

Nginx `.htaccess` okumaz. Şu yolların dışarıdan erişimini kapatın: `/inc/`, `/sql/`, `/tasarim/`, `/uploads/belgeler/`, `/config.php`. Ayrıca `/uploads/` altında PHP çalıştırmayı engelleyin.

## Yerelde çalıştırma

```bash
cp config.sample.php config.php   # bilgileri doldur, 'debug' => true yapabilirsin
php -S 127.0.0.1:8080
# tarayıcıda http://127.0.0.1:8080/kurulum/
```
