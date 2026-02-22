<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversion extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'trx_id', 'student_id', 'university_id', 'study_program_id',
        'original_file_path', 'generated_result_path',
        'total_sks_accepted', 'total_sks_taken',
        'status', 'payment_status', 'payment_token',
        'snapshot_data', 'admin_notes'
    ];
    
    protected $casts = [
        'total_sks_accepted' => 'integer',
        'snapshot_data' => 'array', // JSON Snapshot untuk audit trail
    ];


    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(ConversionDetail::class);
    }
    
    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    // --- Helpers ---

    /**
     * Cek apakah hasil konversi boleh dilihat mahasiswa
     * Logic: Jika mode mandiri, harus lunas dulu.
     */
    public function isVisibleToStudent(): bool
    {
        if ($this->university->billing_mode === 'subsidy') {
            return true; // Gratis, selalu terlihat
        }
        return $this->payment_status === 'paid';
    }
}