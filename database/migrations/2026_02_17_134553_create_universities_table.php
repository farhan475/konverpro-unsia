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
        Schema::create('universities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->nullable()->index();
            $table->string('logo_path')->nullable();
            $table->string('website')->nullable();
            $table->enum('billing_mode',['subsidy','independent'])->default('independent');
            $table->decimal('balance', 15,2)->default(0);
            $table->decimal('cost_per_check', 10,2)->default(0);
            $table->decimal('student_registration_fee', 10,2)->default(0);
            $table->json('settings')->nullable(); 
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true); 
            $table->boolean('is_partner')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('universities');
    }
};