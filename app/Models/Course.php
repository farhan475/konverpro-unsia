<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Course extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'study_program_id', 'code', 'name', 'sks', 'semester',
        'is_mandatory', 'keywords', 'level'
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'keywords' => 'array', //convert ke array
        'sks' => 'integer',
        'semester' => 'integer'
    ];

    
    
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }
}
