# Yeni site provision etme

40 mikro siteyi yönetilebilir tutmak için tek komutla yeni site açılır.

## CLI komutu

```bash
cd backend
php artisan multi-cms:provision-site \
    --domain=kaplan-manchester.com \
    --name="Kaplan Manchester" \
    --brand=kaplan \
    --city=Manchester --country=GB \
    --locales=tr,en,ar \
    --revalidate-url=https://kaplan-manchester.com/api/revalidate
```

Bu komut:
1. **Site** kaydı oluşturur (brand, city, country, default_locales)
2. **5 standart sayfa** açar (`/`, `/courses`, `/accommodation`, `/city-guide`, `/pricing`)
3. Her sayfayı **belirtilen her locale için** çoğaltır (default: tr,en)
4. Her sayfaya iskelet **bloklar** koyar (hero_school, course_grid, footer_mega, ...) Kaplan tarzı placeholder içerikle
5. Otomatik `revalidate_secret` üretir (32 byte hex)
6. Çıktıda frontend kurulum talimatlarını listeler

## Frontend tarafı (manuel — site-d için)

```bash
# 1. frontend-template'i clone'la
cd ~/Desktop/multi-cms
cp -R site-a frontend-manchester  # ya da template'in herhangi bir clone'u
rm -rf frontend-manchester/.git frontend-manchester/.next frontend-manchester/node_modules

cd frontend-manchester
git init
git add -A
git commit -m "init: Kaplan Manchester frontend"

# 2. GitHub repo aç + push
gh repo create multi-cms-fe-kaplan-manchester --private --source=. --remote=origin --push

# 3. .env.local
cat > .env.local <<EOFENV
NEXT_PUBLIC_API_URL=https://multi-cms-backend.onrender.com
SITE_DOMAIN=kaplan-manchester.com
REVALIDATE_SECRET=<provision çıktısındaki secret>
EOFENV

# 4. Vercel
# - vercel.com/new → repo'yu import
# - Aynı env'leri Vercel project settings → environment variables
# - Custom domain: kaplan-manchester.com → DNS A 76.76.21.21

# 5. Filament admin
# - Sites → Kaplan Manchester → revalidate_url'ini Vercel deploy URL'iyle güncelle
```

## Yeni site checklist

- [ ] `php artisan multi-cms:provision-site --domain=... --brand=...` (backend)
- [ ] Frontend repo clone + push
- [ ] Vercel project + env vars + custom domain
- [ ] Filament'te Site.revalidate_url'ini güncelle
- [ ] DNS'i Vercel'e yönlendir
- [ ] Browser'da `/tr` ve `/en` aç, içerik akışını gör

## Locale ekleme/silme

Site açıldıktan sonra yeni locale eklemek istersen:

1. Filament → Site → düzenle → `default_locales` listesine ekle
2. Filament → Pages → Create → o site + yeni locale + `/` slug + içerik
3. Ya da: provision komutunu **--force ile yeniden çalıştır** ve `--locales=tr,en,ar` gibi tüm dilleri belirt (mevcut içerik **silinir**, dikkat).
