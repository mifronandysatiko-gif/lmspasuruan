<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebinarAttendance extends Model
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
        'attended_at',
    ];

    protected function casts(): array
    {
        return [
            'attended_at' => 'datetime',
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
}
