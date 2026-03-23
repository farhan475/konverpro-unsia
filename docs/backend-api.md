# Backend API

## Auth

- `POST /api/login`
- `POST /api/logout`
- `GET /api/user`

## Public

- `GET /api/public/campuses`
- `GET /api/public/marketplace`
- `GET /api/public/template`
- `POST /api/conversions`
- `GET /api/conversions/{id}`

## Admin Conversion

- `GET /api/admin/conversions`
- `GET /api/admin/conversions/{id}`
- `GET /api/admin/conversions/{id}/official-document`
- `POST /api/admin/review-detail/{detailId}`
- `POST /api/admin/finalize/{conversionId}`
- `GET /api/admin/dashboard-stats`

## Campus Curriculum

- `GET /api/curriculum/prodi`
- `GET /api/curriculum/prodi/{prodiId}/courses`
- `POST /api/curriculum/import`

## Campus Settings

- `GET /api/campus/settings/profile`
- `POST /api/campus/settings/profile`
- `GET /api/campus/settings/prodi`
- `POST /api/campus/settings/prodi`
- `PUT /api/campus/settings/prodi/{id}`
- `DELETE /api/campus/settings/prodi/{id}`
- `GET /api/campus/settings/prodi/{id}/academic-settings`
- `PUT /api/campus/settings/prodi/{id}/academic-settings`
- `GET /api/campus/settings/dictionary`
- `PUT /api/campus/settings/dictionary/{courseId}`
- `GET /api/campus/settings/billing-history`
- `POST /api/campus/settings/topups`

## Super Admin

### Campuses

- `GET /api/super-admin/campuses`
- `POST /api/super-admin/campuses`
- `PUT /api/super-admin/campuses/{id}`
- `DELETE /api/super-admin/campuses/{id}`
- `POST /api/super-admin/campuses/{id}/adjust-balance`

### Users

- `GET /api/super-admin/users`
- `POST /api/super-admin/users`
- `PUT /api/super-admin/users/{id}`
- `DELETE /api/super-admin/users/{id}`

### Finance

- `GET /api/super-admin/topups`
- `POST /api/super-admin/topups/{id}/process`

### Settings

- `GET /api/super-admin/settings`
- `POST /api/super-admin/settings`

### Notification Templates

- `GET /api/super-admin/notification-templates`
- `POST /api/super-admin/notification-templates`
- `PUT /api/super-admin/notification-templates/{id}`
- `DELETE /api/super-admin/notification-templates/{id}`

### Reports

- `GET /api/super-admin/reports/audit-logs`
- `GET /api/super-admin/reports/revenue`
- `GET /api/super-admin/reports/overview`

### System

- `GET /api/super-admin/system/backup`
- `POST /api/super-admin/system/restore`

## Response Notes

Sukses:

- `message` opsional
- `data` dipakai untuk payload utama
- beberapa endpoint memakai field tambahan top-level bila diperlukan, misalnya login atau report tertentu

Error:

- validasi: `message` + `errors`
- auth / authorization / not found / server error: `message`
