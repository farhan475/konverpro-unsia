# Backend Architecture

## Tujuan

Backend KonverPro melayani tiga mode operasi:

- publik untuk marketplace dan submission awal
- admin kampus untuk operasional review dan konfigurasi kampus
- super admin untuk kontrol pusat sistem

## Layering

### Routes

Semua route API berada di `routes/api.php` dan dikelompokkan menurut:

- public
- protected auth
- admin conversion
- campus management
- super admin

### Controllers

Controller bertugas menjaga boundary HTTP:

- menerima request
- memanggil request validator
- delegasi ke service
- mengembalikan response JSON

### Requests

Request validation dipisahkan di `app/Http/Requests/*` untuk:

- login
- submission conversion
- topup
- campus workspace
- user management
- settings
- backup import

### Services

Service layer dipisahkan sesuai domain:

- `App\Services\AuthService`
- `App\Services\ConversionSubmissionService`
- `App\Services\ConversionService`
- `App\Services\Admin\ConversionReviewService`
- `App\Services\Campus\*`
- `App\Services\SuperAdmin\*`

### Support

Support layer berisi utilitas lintas domain:

- `ApiResponse` untuk response JSON
- `AuditLogSupport` untuk pencatatan audit
- `AcademicSettingsSupport` untuk payload academic settings dan official document

## Response Strategy

Refactor ini menambahkan:

- `App\Support\ApiResponse`
- centralized API exception rendering di `bootstrap/app.php`

Tujuannya:

- error validasi, auth, authorization, not found, dan 500 memiliki format konsisten
- controller sukses mengembalikan envelope yang lebih stabil untuk FE

## Logging dan Audit

- audit log dipakai pada aksi penting seperti kampus, topup, dan beberapa alur manajemen
- error server tetap di-report melalui mekanisme Laravel `report()`

## Testing

Feature tests yang tersedia mencakup:

- academic settings
- topup request
- visibility payload conversion
- role guard
- overview report
- audit log
- campus workspace
