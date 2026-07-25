# Kafe Stok Takip

Kafe operasyonlarini tek panelden yonetmek icin gelistirilmis Laravel tabanli bir stok, satis, masa, siparis ve raporlama uygulamasi.

Bu proje; stok yonetimi, depo hareketleri, cafe siparis sureci, parcali odeme, kasa takibi, kullanici rolleri ve raporlama ihtiyaclarini tek sistemde toplar.

## Baslica Ozellikler

- Rol bazli giris ve yetkilendirme
- Kafe masa ve siparis yonetimi
- Adisyon, parcali odeme ve odeme kapatma akisi
- Stok, urun ve kategori yonetimi
- Depo giris hareketleri ve stok raporlari
- Kasa hareketleri ve nakit takibi
- Gunluk, stok, cafe ve kullanici aktivite raporlari
- Sistem ayarlari ve temel gorev takibi

## Roller

Sistemde su roller bulunur:

- `admin`
- `manager`
- `waiter`
- `cashier`
- `warehouse_manager`

Her rol kendi yetki alanina uygun ekrana yonlendirilir ve sadece izin verilen modullere erisebilir.

## Teknoloji Yigini

- PHP
- Laravel 8
- MySQL
- Blade
- Laravel Mix
- JavaScript
- Bootstrap tabanli arayuz yapisi

## Kurulum

### Gereksinimler

- PHP 8.0+
- Composer
- Node.js ve npm
- MySQL veya uyumlu bir veritabani
- XAMPP benzeri yerel gelistirme ortami

### 1. Projeyi klonla

```bash
git clone https://github.com/Muhammed61/cafe-stok-takip.git
cd cafe-stok-takip
```

### 2. PHP bagimliliklarini kur

```bash
composer install
```

### 3. Ortam dosyasini hazirla

```bash
copy .env.example .env
php artisan key:generate
```

Ardindan `.env` dosyasi icinde veritabani ve diger ortam ayarlarini guncelle.

### 4. Veritabanini hazirla

```bash
php artisan migrate --seed
```

### 5. On yuz bagimliliklarini kur

```bash
npm install
npm run dev
```

### 6. Uygulamayi calistir

```bash
php artisan serve
```

Tarayicida su adresi ac:

```text
http://127.0.0.1:8000
```

## Varsayilan Kullanicilar

Seed islemi sonrasinda test amacli varsayilan kullanicilar olusur:

- `admin@cafe.com` / `123456`
- `manager@cafe.com` / `123456`
- `garson@cafe.com` / `123456`
- `kasiyer@cafe.com` / `123456`
- `depo@cafe.com` / `123456`

Ilk kurulumdan sonra bu sifrelerin degistirilmesi onerilir.

## Proje Modulleri

### Kafe Yonetimi

- Masa bazli siparis olusturma
- Siparis durum guncelleme
- Urun arama ve ekleme
- Siparis tasima ve birlestirme
- Adisyon yazdirma
- Parcali odeme ve odeme iptal akislari

### Stok ve Depo

- Urun ve kategori yonetimi
- Manuel stok giris ve cikis hareketleri
- Depo urun kaydi
- Dusuk stok takibi
- Stok hareket raporlari

### Kasa ve Raporlama

- Kasa giris ve cikis hareketleri
- Satis raporlari
- Cafe gelir raporlari
- Kar ve hareket raporlari
- Kullanici aktivite kayitlari

## Gelistirme Komutlari

```bash
php artisan serve
php artisan migrate
php artisan db:seed
npm run dev
npm run watch
```

## GitHub Calisma Duzeni

Bu repo icin commit ve surumleme kurallari tanimlanmistir:

- Commit kurallari: [CONTRIBUTING.md](./CONTRIBUTING.md)
- Surum gecmisi: [CHANGELOG.md](./CHANGELOG.md)
- Guncel uygulama surumu: [VERSION](./VERSION)
- Commit sablonu: [.gitmessage.txt](./.gitmessage.txt)

Onerilen ilk ayar:

```bash
git config commit.template .gitmessage.txt
```

## Surumleme Yaklasimi

Bu proje `Semantic Versioning` mantigiyla surumlenir:

- `MAJOR`: Geriye donuk uyumsuz degisiklik
- `MINOR`: Geriye uyumlu yeni ozellik
- `PATCH`: Hata duzeltmesi ve kucuk iyilestirme

Yeni surum yayinlanmadan once:

1. `CHANGELOG.md` guncellenir.
2. `VERSION` dosyasi guncellenir.
3. `chore(release): vX.Y.Z` commit'i atilir.
4. Git etiketi olusturulur.

## Guvenlik

- `.env` dosyasi repoya dahil edilmez.
- Gercek API anahtarlari, sifreler ve gizli bilgiler sadece ortam degiskenlerinde tutulmalidir.
- Uretim ortaminda varsayilan kullanici sifreleri kullanilmamalidir.

## Repo Durumu

- Repo durumu: `private`
- Ana branch: `main`

## Not

Bu repo aktif gelistirme altindadir. Yeni ozellikler, duzeltmeler ve surum notlari icin ilgili dokumanlari takip et.
