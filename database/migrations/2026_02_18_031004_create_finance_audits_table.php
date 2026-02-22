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
        // Transaksi Keuangan
    Schema::create('transactions', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('invoice_number')->unique(); // INV dan menyesuaikan format billing gateway
        
        // yang menerbitkan invoice, bisa kampus atau sistem (untuk refund)
        $table->foreignUuid('university_id')->nullable()->constrained();
        $table->foreignUuid('user_id')->nullable()->constrained();
        $table->foreignUuid('conversion_id')->nullable()->constrained(); // Linking ke konversi(jika ada)
        
        $table->enum('type', ['topup', 'subsidy_deduction', 'student_payment']);
        $table->decimal('amount', 15, 2);
        $table->enum('status', ['pending', 'success', 'failed', 'refund'])->default('pending');
        
        // Gateway Info
        $table->string('payment_method')->nullable(); // misal: gopay, bca_va
        $table->json('gateway_response')->nullable(); // Simpan raw response JSON disini untuk keperluan audit dan troubleshooting
        
        $table->timestamps();
        $table->softDeletes(); 
    });

    // Audit Log System (Security)
    Schema::create('audit_logs', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
        
        $table->string('event'); // created, updated, deleted, login, download_report
        $table->string('auditable_type'); 
        $table->uuid('auditable_id'); // ID data yg diubah
        
        $table->json('old_values')->nullable();
        $table->json('new_values')->nullable();
        
        $table->string('ip_address')->nullable();
        $table->string('user_agent')->nullable();
        
        $table->timestamps();
        // Audit log gak butuh soft delete, di-prune (hapus otomatis) tiap tahun.
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_audits');
    }
};