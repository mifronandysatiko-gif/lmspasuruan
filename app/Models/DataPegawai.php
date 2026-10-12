<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DataPegawai extends Model
{
    use HasFactory;

    protected $table = 'data_pegawai';

    protected $fillable = [
        'nip',
        'nik',
        'nama',
        'gelar_depan',
        'gelar_belakang',
        'jabatan',
        'unit_kerja',
        'pangkat_golongan',
        'email',
        'no_hp',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relasi ke akun User terdaftar berdasarkan NIP yang sama.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'nip', 'nip');
    }

    /**
     * Nama murni tanpa gelar depan dan belakang.
     */
    public function getPureNameAttribute(): string
    {
        return self::extractPureName($this->nama, $this->gelar_depan, $this->gelar_belakang);
    }

    /**
     * Helper statis untuk mengekstrak nama murni dari string nama yang mungkin mengandung gelar dari SIMPEG/Excel.
     */
    public static function extractPureName(?string $rawName, ?string $gelarDepan = null, ?string $gelarBelakang = null): string
    {
        if (empty($rawName)) {
            return '';
        }

        $clean = trim($rawName);

        // 1. Bersihkan gelar depan di awal nama
        if (!empty($gelarDepan)) {
            $gd = trim($gelarDepan, " .");
            $clean = preg_replace('/^' . preg_quote($gd, '/') . '\.?\s+/i', '', $clean);
        }

        // 2. Bersihkan gelar belakang di akhir nama
        if (!empty($gelarBelakang)) {
            $gb = trim($gelarBelakang, " ,.");
            $clean = preg_replace('/,\s*' . preg_quote($gb, '/') . '$/i', '', $clean);
        }

        // 3. Jika nama masih mengandung koma dan bagian setelah koma adalah indikasi gelar
        if (str_contains($clean, ',')) {
            $parts = explode(',', $clean);
            $candidateName = trim($parts[0]);
            $titlesPart = trim(implode(',', array_slice($parts, 1)));

            if (!empty($gelarBelakang) && (stripos($titlesPart, trim($gelarBelakang)) !== false || stripos(trim($gelarBelakang), $titlesPart) !== false)) {
                $clean = $candidateName;
            } elseif (preg_match('/^(S\.|M\.|Dr\.|Ir\.|A\.Md|SE|SH|SM|ST|MM|MP|MH|Sp\.|Ners|Ns)/i', $titlesPart)) {
                $clean = $candidateName;
            }
        }

        return trim($clean, " ,.");
    }

    /**
     * Nama lengkap dengan gelar depan dan gelar belakang (tanpa duplikasi gelar).
     */
    public function getFormattedFullNameAttribute(): string
    {
        $pureName = $this->pure_name;
        $fullName = !empty($pureName) ? $pureName : trim($this->nama);

        if (!empty($this->gelar_depan)) {
            $fullName = trim($this->gelar_depan) . ' ' . $fullName;
        }

        if (!empty($this->gelar_belakang)) {
            $fullName = $fullName . ', ' . trim($this->gelar_belakang);
        }

        return $fullName;
    }
}
