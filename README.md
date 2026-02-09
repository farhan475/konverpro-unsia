KonverPro UNSIA API

backend API untuk project KonverPro UNSIA, dibangun menggunakan Laravel 12.

Backend ini berfungsi sebagai penyedia REST API untuk frontend (Next.js), menangani autentikasi, manajemen user, dan data dashboard Super Admin.

Laravel digunakan secara API-only (tanpa Blade).

Tech Stack

Laravel 12
PHP >= 8.3
MySQL
Laravel Sanctum (API Authentication)

Project Structure
app/
routes/api.php
database/


Semua endpoint ada di routes/api.php.

Setup Project
Clone repository
git clone git@github.com:farhan475/konverpro-unsia.git
cd konverpro-unsia

Install dependency
composer install

Setup environment
cp .env.example .env
php artisan key:generate


Edit database di .env.

Migrate database
php artisan migrate

Run server
php artisan serve


API akan jalan di:

http://127.0.0.1:8000

Authentication

Project ini menggunakan Laravel Sanctum.

Workflow Git

main → stable
dev → development

Feature branch:

git switch -c feature-auth

Development Rules

• Laravel hanya API
• Tidak pakai Blade
• Jangan sudo git / composer
• Gunakan eager loading
• Endpoint RESTful
• Validasi pakai FormRequest