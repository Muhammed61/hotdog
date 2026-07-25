# Katki ve Git Akisi

Bu repo icin hedef; okunabilir commit gecmisi, duzenli surumleme ve kontrollu degisiklik yonetimidir.

## Branch Adlandirma

Yeni calismalarda asagidaki adlandirma yapisini kullan:

- `feature/kisa-aciklama`
- `fix/kisa-aciklama`
- `refactor/kisa-aciklama`
- `docs/kisa-aciklama`
- `release/vx.y.z`

Ornekler:

- `feature/cafe-split-payment`
- `fix/report-total-bug`
- `docs/readme-update`

## Commit Formati

Commit mesajlari `Conventional Commits` mantigiyla yazilir:

```text
type(scope): kisa aciklama
```

Ornekler:

```text
feat(cafe): parcali odeme ekrani eklendi
fix(report): gunluk toplam hesaplama duzeltildi
refactor(stock): stok dusum mantigi sadelelestirildi
docs(readme): kurulum adimlari guncellendi
chore(release): v0.1.0
```

## Kullanilacak Commit Tipleri

- `feat`: Yeni ozellik
- `fix`: Hata duzeltmesi
- `refactor`: Davranis degistirmeden kod iyilestirme
- `docs`: Dokumantasyon degisikligi
- `style`: Yalnizca bicimsel degisiklik
- `test`: Test ekleme veya duzeltme
- `chore`: Bakim, ayar veya yardimci degisiklik
- `perf`: Performans iyilestirmesi
- `build`: Paketleme veya derleme degisikligi
- `ci`: Otomasyon ve pipeline degisikligi
- `revert`: Geri alma commit'i

## Scope Onerileri

Scope, degisikligin ana alanini belirtir. Onerilen scope'lar:

- `auth`
- `dashboard`
- `cafe`
- `table`
- `stock`
- `warehouse`
- `cash-register`
- `report`
- `settings`
- `user`
- `db`
- `readme`
- `release`

## Commit Yazim Kurallari

- Her commit tek bir amaca hizmet etsin.
- Ilgili olmayan degisiklikleri ayni commit'te birlestirme.
- Commit basligini kisa ve net tut.
- Ilk satirda ne degistigini yaz, nedenini gerekirse commit govdesinde acikla.
- Mümkun oldugunda kucuk ve geri alinabilir commit'ler at.

## Onerilen Akis

1. `main` branch'inden yeni branch ac.
2. Degisikligi yap.
3. Gerekli test veya manuel kontrolu tamamla.
4. Uygun formatta commit at.
5. Branch'i GitHub'a gonder.
6. Pull request ac veya dogrudan kontrollu birlestirme yap.

## Pull Request Kontrol Listesi

Pull request acmadan once sunlari kontrol et:

- Degisiklik amaci net mi
- Gereksiz dosya eklendi mi
- `.env`, log, gecici dosya veya gizli veri var mi
- `CHANGELOG.md` guncellenmeli mi
- Surum artisi gerekli mi

## Surumleme Kurali

Bu repo `Semantic Versioning` kullanir:

- `MAJOR`: Geriye donuk uyumsuz degisiklik
- `MINOR`: Yeni ozellik, geriye uyumlu
- `PATCH`: Hata duzeltmesi veya kucuk iyilestirme

## Surum Cikarma Adimlari

Yeni surum yayinlanacagi zaman:

1. `CHANGELOG.md` icindeki `Unreleased` alani guncellenir.
2. `VERSION` dosyasi yeni degerle guncellenir.
3. `chore(release): vX.Y.Z` commit'i atilir.
4. Asagidaki komutla etiket olusturulur:

```bash
git tag -a vX.Y.Z -m "vX.Y.Z"
```

5. Commit ve etiket GitHub'a gonderilir:

```bash
git push origin main
git push origin --tags
```

## Commit Sablonu Kullanimi

Depodaki commit sablonunu aktif etmek icin:

```bash
git config commit.template .gitmessage.txt
```
