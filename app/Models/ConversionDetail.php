<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversionDetail extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'conversion_id', 'src_code', 'src_name', 'src_grade', 'src_sks',
        'target_course_id', 'match_score', 'status', 'admin_notes'
    ];

    protected $casts = [
        'match_score' => 'float', 
        'src_sks' => 'integer',
    ];


    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }

    public function targetCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'target_course_id');
    }
}