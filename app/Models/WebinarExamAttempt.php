<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebinarExamAttempt extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'webinar_id',
        'user_id',
        'nip',
        'nik',
        'nama_lengkap',
        'jabatan',
        'unit_kerja',
        'attempt_number',
        'raw_score',
        'final_score',
        'is_passed',
        'answers',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'raw_score' => 'float',
            'final_score' => 'float',
            'is_passed' => 'boolean',
            'answers' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function webinar(): BelongsTo
    {
        return $this->belongsTo(Webinar::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Menghitung skor akhir berdasarkan aturan
     */
    public static function calculateFinalScore(int $attemptNumber, float $rawScore, int $remedialCap = 90): float
    {
        if ($attemptNumber > 1) {
            return min($rawScore, (float) $remedialCap);
        }
        return $rawScore;
    }
}
