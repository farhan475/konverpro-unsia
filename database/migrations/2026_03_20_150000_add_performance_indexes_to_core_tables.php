<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'is_active'], 'users_role_is_active_idx');
            $table->index(['university_id', 'role'], 'users_university_role_idx');
        });

        Schema::table('universities', function (Blueprint $table) {
            $table->index(['is_active', 'created_at'], 'universities_active_created_idx');
            $table->index(['is_partner', 'is_active'], 'universities_partner_active_idx');
        });

        Schema::table('study_programs', function (Blueprint $table) {
            $table->index(['university_id', 'is_active'], 'study_programs_university_active_idx');
            $table->index(['university_id', 'level'], 'study_programs_university_level_idx');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->index(['study_program_id', 'semester'], 'courses_program_semester_idx');
            $table->index(['study_program_id', 'is_mandatory'], 'courses_program_mandatory_idx');
            $table->index(['study_program_id', 'code'], 'courses_program_code_idx');
        });

        Schema::table('conversions', function (Blueprint $table) {
            $table->index(['university_id', 'status', 'created_at'], 'conversions_university_status_created_idx');
            $table->index(['student_id', 'created_at'], 'conversions_student_created_idx');
            $table->index(['study_program_id', 'created_at'], 'conversions_program_created_idx');
            $table->index(['payment_status', 'status'], 'conversions_payment_status_idx');
        });

        Schema::table('conversion_details', function (Blueprint $table) {
            $table->index(['conversion_id', 'status'], 'conversion_details_conversion_status_idx');
            $table->index(['conversion_id', 'target_course_id'], 'conversion_details_conversion_target_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['type', 'status', 'created_at'], 'transactions_type_status_created_idx');
            $table->index(['university_id', 'type', 'created_at'], 'transactions_university_type_created_idx');
            $table->index(['status', 'created_at'], 'transactions_status_created_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['event', 'created_at'], 'audit_logs_event_created_idx');
            $table->index(['user_id', 'created_at'], 'audit_logs_user_created_idx');
            $table->index(['auditable_type', 'auditable_id'], 'audit_logs_auditable_idx');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_event_created_idx');
            $table->dropIndex('audit_logs_user_created_idx');
            $table->dropIndex('audit_logs_auditable_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_type_status_created_idx');
            $table->dropIndex('transactions_university_type_created_idx');
            $table->dropIndex('transactions_status_created_idx');
        });

        Schema::table('conversion_details', function (Blueprint $table) {
            $table->dropIndex('conversion_details_conversion_status_idx');
            $table->dropIndex('conversion_details_conversion_target_idx');
        });

        Schema::table('conversions', function (Blueprint $table) {
            $table->dropIndex('conversions_university_status_created_idx');
            $table->dropIndex('conversions_student_created_idx');
            $table->dropIndex('conversions_program_created_idx');
            $table->dropIndex('conversions_payment_status_idx');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('courses_program_semester_idx');
            $table->dropIndex('courses_program_mandatory_idx');
            $table->dropIndex('courses_program_code_idx');
        });

        Schema::table('study_programs', function (Blueprint $table) {
            $table->dropIndex('study_programs_university_active_idx');
            $table->dropIndex('study_programs_university_level_idx');
        });

        Schema::table('universities', function (Blueprint $table) {
            $table->dropIndex('universities_active_created_idx');
            $table->dropIndex('universities_partner_active_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_is_active_idx');
            $table->dropIndex('users_university_role_idx');
        });
    }
};
