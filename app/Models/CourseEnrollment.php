<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseEnrollment extends Model
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
        'enrolled_at',
        'deadline_at',
        'progress_percentage',
        'status',
        'final_score',
        'assignment_file',
        'assignment_notes',
        'instructor_feedback',
        'assignment_submitted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'deadline_at' => 'datetime',
            'progress_percentage' => 'integer',
            'final_score' => 'float',
            'assignment_submitted_at' => 'datetime',
            'completed_at' => 'datetime',
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
     * Periksa dan tegakkan batas waktu pengerjaan.
     * Jika waktu telah habis dan peserta belum lulus, ubah status menjadi 'failed'.
     */
    public function checkAndEnforceExpiry(): bool
    {
        if (in_array($this->status, ['completed', 'passed', 'failed'])) {
            return $this->status === 'failed';
        }

        $now = Carbon::now('Asia/Jakarta');
        $isExpired = false;

        if ($this->deadline_at && $now->greaterThan($this->deadline_at)) {
            $isExpired = true;
        } elseif ($this->course && $this->course->end_date && $now->greaterThan($this->course->end_date)) {
            $isExpired = true;
        }

        if ($isExpired) {
            $this->update(['status' => 'failed']);
            return true;
        }

        return false;
    }

    public function isExpired(): bool
    {
        if ($this->status === 'failed') {
            return true;
        }
        return $this->checkAndEnforceExpiry();
    }

    public function remainingDays(): ?int
    {
        $deadline = $this->deadline_at ?: ($this->course?->end_date);
        if (!$deadline) {
            return null;
        }
        $now = Carbon::now('Asia/Jakarta');
        if ($now->greaterThan($deadline)) {
            return 0;
        }
        return (int) ceil($now->floatDiffInDays($deadline));
    }

    public function remainingTimeDescription(): ?string
    {
        $deadline = $this->deadline_at ?: ($this->course?->end_date);
        if (!$deadline) {
            return null;
        }
        $now = Carbon::now('Asia/Jakarta');
        if ($now->greaterThan($deadline)) {
            return 'Waktu Telah Habis';
        }

        $diffHours = (int) $now->diffInHours($deadline);
        if ($diffHours < 24) {
            $diffMinutes = (int) $now->diffInMinutes($deadline);
            if ($diffHours > 0) {
                return "Sisa {$diffHours} jam " . ($diffMinutes % 60) . " menit";
            }
            return "Sisa {$diffMinutes} menit";
        }

        $days = (int) ceil($now->floatDiffInDays($deadline));
        return "Sisa {$days} hari";
    }
}
