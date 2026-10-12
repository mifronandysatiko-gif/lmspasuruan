<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadershipLogbook extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nip',
        'nik',
        'nama_lengkap',
        'jabatan',
        'unit_kerja',
        'coach_id',
        'mentor_id',
        'week_number',
        'milestone_title',
        'activity_date',
        'activity_description',
        'evidence_file_name',
        'status',
        'coach_feedback',
        'mentor_feedback',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'week_number' => 'integer',
            'activity_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }
}
