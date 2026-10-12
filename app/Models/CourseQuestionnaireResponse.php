<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseQuestionnaireResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'user_id',
        'nip',
        'nik',
        'nama_lengkap',
        'jabatan',
        'unit_kerja',
        'rating_materi',
        'rating_sistem',
        'rating_manfaat',
        'rating_instruktur',
        'feedback_text',
        'answers',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'rating_materi' => 'integer',
            'rating_sistem' => 'integer',
            'rating_manfaat' => 'integer',
            'rating_instruktur' => 'integer',
            'answers' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Rata-rata rating kepuasan (skala 1 - 5)
     */
    public function getAverageRatingAttribute(): float
    {
        $sum = ($this->rating_materi ?? 5) +
               ($this->rating_sistem ?? 5) +
               ($this->rating_manfaat ?? 5) +
               ($this->rating_instruktur ?? 5);

        return round($sum / 4, 2);
    }
}
