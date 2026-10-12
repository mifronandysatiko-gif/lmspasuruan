<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseLessonQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_lesson_id',
        'question_text',
        'options',
        'correct_answer',
        'order_number',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'order_number' => 'integer',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'course_lesson_id');
    }
}
