<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class University extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    
    protected $fillable = [
        'name',
        'slug',
        'code',
        'logo_path',
        'website',
        'billing_mode',
        'balance',
        'cost_per_check',
        'student_registration_fee',
        'settings',
        'config',
        'is_active',
        'is_partner',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'cost_per_check' => 'decimal:2',
        'student_registration_fee' => 'decimal:2',
        'settings' => 'array',
        'config' => 'array',
        'is_active' => 'boolean',
        'is_partner' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function studyPrograms()
    {
        return $this->hasMany(StudyProgram::class);
    }

    public function conversions()
    {
        return $this->hasMany(Conversion::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // --- Helper Logic (Business Domain) ---
    
    public function canSubsidyCheck(): bool
    {
        // Jika mode independent, kampus TIDAK subsidi (return false)
        if ($this->billing_mode === 'independent') {
            return false; 
        }
        
        // Cek apakah saldo cukup
        return $this->balance >= $this->cost_per_check;
    }
    
    public function hasSufficientBalance(): bool
    {
        if ($this->billing_mode === 'independent') return false; // Mandiri tidak butuh saldo
        
        // KOREKSI: Menggunakan cost_per_check
        return $this->balance >= $this->cost_per_check; 
    }

    public function getLogoUrlAttribute(): string
    {
        return $this->logo_path 
            ? asset('storage/' . $this->logo_path) 
            : 'https://ui-avatars.com/api/?name='.urlencode($this->name);
    }
}