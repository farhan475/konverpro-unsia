# Backend Feature Checklist

Status audit per 17 Maret 2026 setelah pass keamanan, topup manual, dan sinkronisasi kontrak frontend.

## Sudah Aktif

- [x] Login dan logout berbasis Sanctum
- [x] Listing kampus publik untuk landing page
- [x] Marketplace kampus dan simulasi konversi
- [x] Submit transkrip dan pembuatan data konversi
- [x] Polling detail konversi publik tanpa membocorkan identitas mahasiswa
- [x] Detail konversi admin kampus melalui endpoint terproteksi
- [x] Dashboard dan daftar konversi admin kampus
- [x] Review detail mata kuliah dan finalisasi konversi
- [x] Import kurikulum per prodi
- [x] CRUD prodi dan kamus padanan mata kuliah
- [x] Profil kampus dan riwayat billing dasar
- [x] Akad settings per prodi tersimpan di backend
- [x] Request topup manual dari campus admin
- [x] List dan proses approve/reject topup oleh super admin
- [x] Payload composer dokumen resmi per konversi
- [x] CRUD kampus super admin
- [x] Adjust saldo kampus oleh super admin
- [x] CRUD user super admin
- [x] CRUD global settings
- [x] CRUD template notifikasi
- [x] Laporan revenue dasar
- [x] Overview dashboard super admin dengan growth, heatmap, dan insight dasar
- [x] Backup export dan restore parsial
- [x] Guard role untuk route campus admin dan super admin

## Sudah Ada Tapi Masih Perlu Diperdalam

- [ ] Dashboard super admin sudah punya overview kaya, tapi visual dan indikator operasionalnya masih bisa diperdalam
- [ ] Audit log endpoint sudah ada, tapi penulisan log aktivitas penting belum aktif
- [ ] Backup dan restore masih terbatas pada sebagian konfigurasi sistem
- [ ] Akad settings masih bertumpu pada kolom `study_programs.settings`, belum dipisah ke model atau tabel khusus
- [ ] Profile kampus masih menyimpan banyak data non-relasional di `settings`
- [ ] Preview file transkrip asli dan signed URL file belum siap
- [ ] Dokumen resmi sudah punya payload composer dasar, tetapi multi-template, nomor surat lanjutan, dan workflow tanda tangan masih belum lengkap
- [ ] Notification template sudah bisa dikelola, tapi pengiriman email/SMTP nyata belum diaktifkan

## Belum Ada

- [ ] Flow registrasi mitra seperti referensi dashboard super admin
- [ ] Payment gateway mahasiswa atau Midtrans
- [ ] Approval onboarding kampus multi-step
- [ ] Composer dokumen akademik lanjutan per prodi
- [ ] Perhitungan biaya yang lebih rinci per prodi, partner, atau skema subsidi
- [ ] Dashboard operasional untuk SLA review, kampus paling aktif, dan funnel konversi

## Kandidat Rapikan atau Hapus

- [ ] Model kosong `app/Models/Academic.php`
- [ ] Model kosong `app/Models/FinanceAudit.php`
- [ ] Test bawaan Laravel yang tidak memberi nilai domain bisnis bisa diringkas setelah coverage domain lebih kuat
- [ ] Dokumentasi README bawaan Laravel bisa diganti dengan dokumentasi produk dan kontrak API internal

## Catatan Teknis

- Topup manual sekarang menjadi alur resmi sementara. Payment gateway bisa ditambahkan nanti tanpa membongkar struktur request dan approval yang sudah ada.
- Endpoint publik `GET /api/conversions/{id}` sengaja dipertahankan untuk polling landing page, tetapi payload sensitif mahasiswa sudah disanitasi.
- Endpoint admin `GET /api/admin/conversions/{id}` menjadi sumber detail lengkap untuk dashboard kampus.
