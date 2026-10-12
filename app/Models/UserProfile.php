<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nik',
        'full_name',
        'gelar_depan',
        'gelar_belakang',
        'jabatan',
        'pangkat_golongan',
        'unit_kerja',
        'kategori_peserta',
        'photo_path',
        'phone_number',
        'sync_source',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Nama murni tanpa gelar depan dan belakang.
     */
    public function getPureNameAttribute(): string
    {
        return DataPegawai::extractPureName($this->full_name, $this->gelar_depan, $this->gelar_belakang);
    }

    /**
     * Nama lengkap bergelar tanpa duplikasi gelar.
     */
    public function getFormattedFullNameAttribute(): string
    {
        $pureName = $this->pure_name;
        $name = !empty($pureName) ? $pureName : trim((string) $this->full_name);

        if (!empty($this->gelar_depan)) {
            $name = trim($this->gelar_depan) . ' ' . $name;
        }

        if (!empty($this->gelar_belakang)) {
            $name = $name . ', ' . trim($this->gelar_belakang);
        }

        return $name;
    }
}
