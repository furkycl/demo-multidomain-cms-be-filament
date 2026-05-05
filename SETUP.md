# backend — kurulum

Laravel 11 + Filament v3. Lokal'de SQLite ile, prod'da Postgres (Neon) ile çalışır.

## Hızlı kurulum

Parent klasördeki `SETUP.sh` her şeyi otomatik yapar:

```bash
cd ~/Desktop/multi-cms
./SETUP.sh
```

Script Laravel'i geçici dizine kurar, sonra bu klasördeki scaffold dosyalarını (Models, Migrations, Resources, Observers, Routes) Laravel'in üzerine kopyalar.

## Manuel kurulum (script çalışmazsa)

```bash
# 1. Laravel'i geçici dizine kur
cd /tmp
composer create-project --prefer-dist laravel/laravel laravel-base "^11.0"

# 2. Bizim scaffold'umuzu yedekle, Laravel'i içeri kopyala, scaffold'u tekrar uygula
cd ~/Desktop/multi-cms/backend
mkdir -p .scaffold-backup
cp -R . .scaffold-backup/ 2>/dev/null || true
rsync -a --exclude '.scaffold-backup' /tmp/laravel-base/ ./
rsync -a .scaffold-backup/ ./ --exclude vendor --exclude .scaffold-backup
rm -rf .scaffold-backup /tmp/laravel-base

# 3. Bağımlılıklar
composer install
composer require filament/filament:"^3.2" -W
composer require --dev pestphp/pest pestphp/pest-plugin-laravel

# 4. Env + key
cp .env.example .env
php artisan key:generate

# 5. Filament
php artisan filament:install --panels --no-interaction

# 6. SQLite + migrate + seed
touch database/database.sqlite
php artisan migrate --seed --force

# 7. Admin user
php artisan make:filament-user      # email + parola sorar

# 8. Çalıştır
php artisan serve
# → http://localhost:8000/admin
```

## Ortam değişkenleri (.env)

| Anahtar                  | Açıklama                                                      |
|--------------------------|---------------------------------------------------------------|
| `APP_KEY`                | `php artisan key:generate` üretir                             |
| `APP_URL`                | Lokal: `http://localhost:8000` — prod: Render URL'i           |
| `DB_CONNECTION`          | Lokal: `sqlite` — prod: `pgsql`                               |
| `DATABASE_URL`           | Prod'da Neon connection string (pgsql parser otomatik anlar)  |
| `FILESYSTEM_DISK`        | Lokal: `public` — prod: `r2`                                  |
| `R2_*` (5 anahtar)       | R2 bucket için (sadece prod)                                  |
| `FRONTEND_REVALIDATE_TIMEOUT` | Webhook timeout, default 5s                              |

`Site` başına `revalidate_url` ve `revalidate_secret` DB'de tutulur, env'de değil. Her site kendi webhook URL'ini taşır.

## Komutlar

```bash
php artisan serve                  # dev server
php artisan migrate:fresh --seed   # DB sıfırla
php artisan test                   # Pest
vendor/bin/pint                    # code style
php artisan tinker                 # REPL
```

## Production deploy

`render.yaml` ile Render'a otomatik deploy. Detaylar: `../docs/DEPLOYMENT.md`.
