# Backend Modules

## Auth

File utama:

- `app/Http/Controllers/Api/AuthController.php`
- `app/Services/AuthService.php`

Fungsi:

- login
- logout
- current user
- role-aware access

## Public Marketplace

File utama:

- `app/Http/Controllers/Api/PublicController.php`

Fungsi:

- daftar kampus aktif
- marketplace listing
- template transkrip

## Conversion Submission

File utama:

- `app/Http/Controllers/Api/ConversionController.php`
- `app/Services/ConversionSubmissionService.php`
- `app/Services/ConversionService.php`

Fungsi:

- upload transkrip publik
- pembuatan user mahasiswa bila perlu
- pembuatan record conversion
- dispatch proses matching

## Admin Conversion Review

File utama:

- `app/Http/Controllers/Api/Admin/ConversionController.php`
- `app/Services/Admin/ConversionReviewService.php`

Fungsi:

- dashboard stats kampus
- list dan detail conversion
- review detail
- finalisasi
- payload official document

## Campus Curriculum

File utama:

- `app/Http/Controllers/Api/CampusCurriculumController.php`
- `app/Services/Campus/CurriculumImportService.php`

Fungsi:

- daftar prodi
- daftar mata kuliah
- import kurikulum

## Campus Settings

File utama:

- `app/Http/Controllers/Api/CampusSettingsController.php`
- `app/Services/Campus/CampusSettingsService.php`

Fungsi:

- profil kampus
- program studi
- pengaturan akademik
- kamus sinonim
- billing history
- topup request

## Super Admin Campuses

File utama:

- `app/Http/Controllers/Api/SuperAdmin/CampusController.php`
- `app/Services/SuperAdmin/CampusManagementService.php`

Fungsi:

- CRUD kampus
- update workspace fields
- adjust balance

## Super Admin Users

File utama:

- `app/Http/Controllers/Api/SuperAdmin/UserController.php`
- `app/Services/SuperAdmin/UserManagementService.php`

Fungsi:

- CRUD user
- asosiasi role dan kampus

## Super Admin Finance

File utama:

- `app/Http/Controllers/Api/SuperAdmin/FinanceController.php`
- `app/Services/SuperAdmin/TopupManagementService.php`

Fungsi:

- daftar topup
- approve / reject topup

## Super Admin Reporting

File utama:

- `app/Http/Controllers/Api/SuperAdmin/ReportController.php`

Fungsi:

- overview report
- revenue chart
- audit logs

## Notification Templates

File utama:

- `app/Http/Controllers/Api/SuperAdmin/NotificationTemplateController.php`
- `app/Services/SuperAdmin/NotificationTemplateService.php`

Fungsi:

- CRUD template notifikasi

## System Backup

File utama:

- `app/Http/Controllers/Api/SuperAdmin/SystemController.php`
- `app/Services/SuperAdmin/SystemBackupService.php`

Fungsi:

- export snapshot JSON
- partial restore dari file backup
- validasi format backup, ukuran file, dan section yang boleh dipulihkan
