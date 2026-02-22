<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversions', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('trx_id')->unique(); // ID unik manusiawi: TRX-2024-0001
        
        // Relasi Utama
        $table->foreignUuid('student_id')->constrained('users');
        $table->foreignUuid('university_id')->constrained('universities');
        $table->foreignUuid('study_program_id')->constrained('study_programs');
        
        // File Transkrip
        $table->string('original_file_path'); // File Excel/PDF asli dari mhs
        $table->string('generated_result_path')->nullable(); // File PDF Hasil Konversi
        
        // Hasil Hitungan
        $table->integer('total_sks_accepted')->default(0);
        $table->integer('total_sks_taken')->default(0);
        
        // Status Workflow
        $table->enum('status', ['draft', 'processing', 'review', 'approved', 'rejected'])->default('draft');
        
        // Payment Logic
        $table->enum('payment_status', ['free', 'pending', 'paid', 'expired'])->default('pending');
        $table->string('payment_token')->nullable(); // Midtrans Snap Token
        
        // IMPROVISASI: Snapshot Data (Penting untuk Audit)
        // Menyimpan nama prodi & kampus SAAT transaksi terjadi.
        // Jika tahun depan nama prodi berubah, data historis ini tidak ikut berubah.
        $table->json('snapshot_data')->nullable(); 
        
        $table->timestamps();
        $table->softDeletes(); // WAJIB
    });
    
    // Detail per Mata Kuliah
    Schema::create('conversion_details', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('conversion_id')->constrained()->cascadeOnDelete();
        
        // Data Sumber (Mahasiswa)
        $table->string('src_code')->nullable();
        $table->string('src_name');
        $table->string('src_grade');
        $table->integer('src_sks');
        
        // Data Target (Hasil Match)
        $table->foreignUuid('target_course_id')->nullable()->constrained('courses');
        
        // Score Matching
        $table->float('match_score')->default(0); // 0.0 - 1.0
        $table->string('status')->default('pending');
        $table->text('admin_notes')->nullable(); // Catatan jika direvisi dosen
        
        $table->timestamps();
        $table->softDeletes(); // WAJIB
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversions');
    }
};