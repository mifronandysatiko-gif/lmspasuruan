<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseLessonCompletion extends Model
{
    use HasFactory;

    protected $table = 'course_lesson_completions';

    protected $fillable = [
        'user_id',
        'nip',
        'nik',
        'nama_lengkap',
        'jabatan',
        'unit_kerja',
        'course_id',
        'course_lesson_id',
        'is_video_completed',
        'is_pdf_read',
        'raw_score',
        'quiz_score',
        'attempt_count',
        'is_passed',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_video_completed' => 'boolean',
            'is_pdf_read' => 'boolean',
            'is_passed' => 'boolean',
            'raw_score' => 'float',
            'quiz_score' => 'float',
            'attempt_count' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'course_lesson_id');
    }
}
