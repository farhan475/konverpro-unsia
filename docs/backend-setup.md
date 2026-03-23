# Backend Setup

## Prasyarat

- PHP 8.2+
- Composer 2+
- ekstensi database sesuai pilihan driver

## Install

```bash
composer install
cp .env.example .env
php artisan key:generate
```

## Database

Default local memakai SQLite.

Opsi cepat:

```bash
touch database/database.sqlite
php artisan migrate
php artisan db:seed
```

Jika memakai MySQL, sesuaikan:

- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

## Menjalankan Server

```bash
php artisan serve
```

## Queue dan Logs

Jika ingin mendekati flow development penuh:

```bash
php artisan queue:listen
php artisan pail
```

## Testing

```bash
php artisan test
```

## Catatan Environment

Field penting dari `.env.example`:

- `APP_URL`
- `APP_DEBUG`
- `DB_*`
- `QUEUE_CONNECTION`
- `FILESYSTEM_DISK`
- `MAIL_*`

## Operasional

- export backup menghasilkan file JSON
- restore backup masih partial restore dan harus digunakan dengan hati-hati
