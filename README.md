# KonverPro API

Backend KonverPro adalah API Laravel untuk marketplace konversi SKS, operasional admin kampus, dan control room super admin.

Refactor ini memfokuskan backend pada:

- struktur controller, request, service, dan support yang lebih jelas
- response JSON yang lebih konsisten
- error handling API terpusat
- endpoint yang sinkron dengan kebutuhan frontend aktif

## Stack

- PHP 8.2
- Laravel 12
- Laravel Sanctum
- SQLite untuk local default, dapat dipindah ke MySQL
- PHPUnit untuk feature tests

## Modul Utama

- auth
- public marketplace
- conversion submission
- admin conversion review
- campus curriculum
- campus settings
- super admin campuses
- super admin users
- super admin finance
- super admin reports
- super admin notification templates
- super admin backup / restore

## Struktur Folder

```text
app/
  Http/
    Controllers/Api/
    Middleware/
    Requests/
  Models/
  Services/
    Admin/
    Campus/
    SuperAdmin/
  Support/
bootstrap/
routes/
database/
docs/
  backend-architecture.md
  backend-modules.md
  backend-api.md
  backend-setup.md
```

## Setup Cepat

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

Test:

```bash
php artisan test
```

## Dokumentasi

- [Arsitektur Backend](docs/backend-architecture.md)
- [Daftar Modul](docs/backend-modules.md)
- [Dokumentasi API](docs/backend-api.md)
- [Setup Backend](docs/backend-setup.md)

## Catatan

- response sukses memakai helper `App\\Support\\ApiResponse` pada controller utama yang paling banyak dipakai frontend
- exception API dirender konsisten melalui `bootstrap/app.php`
- endpoint backup mengirim file JSON stream
- import backup saat ini masih partial restore, bukan full destructive restore
- restore backup hanya menerima snapshot JSON dengan validasi format dan ukuran file
