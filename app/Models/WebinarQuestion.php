<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebinarQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'webinar_id',
        'type',
        'input_type',
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

    public function isTextQuestion(): bool
    {
        return $this->input_type === 'text' || $this->input_type === 'essay';
    }

    public function isOptionsQuestion(): bool
    {
        return !$this->isTextQuestion();
    }

    public function webinar(): BelongsTo
    {
        return $this->belongsTo(Webinar::class);
    }
}
