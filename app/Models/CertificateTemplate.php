<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_default',
        'webinar_id',
        'course_id',
        'background_path',
        'lampiran_background_path',
        'layout_coordinates',
        'number_format',
        'start_number',
        'signer_name',
        'signer_nip',
        'signer_position',
        'issue_city',
        'signature_path',
        'stamp_path',
        'tte_barcode_path',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'layout_coordinates' => 'array',
            'start_number' => 'integer',
        ];
    }

    protected $appends = [
        'background_url',
        'lampiran_background_url',
        'signature_url',
        'stamp_url',
        'tte_barcode_url',
        'target_badge',
    ];

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'template_id');
    }

    public function webinar(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Webinar::class, 'webinar_id');
    }

    public function course(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function getTargetBadgeAttribute(): string
    {
        if ($this->is_default) {
            return 'Default (Semua Kegiatan)';
        }
        if ($this->webinar_id && $this->webinar) {
            return 'Khusus Webinar: ' . $this->webinar->title;
        }
        if ($this->course_id && $this->course) {
            return 'Khusus Pelatihan: ' . $this->course->title;
        }
        return 'Umum / Tidak Terikat';
    }

    public function getBackgroundUrlAttribute(): ?string
    {
        if (!empty($this->background_path)) {
            $rel = ltrim($this->background_path, '/');
            if (file_exists(public_path('storage/' . $rel)) || file_exists(storage_path('app/public/' . $rel))) {
                return asset('storage/' . $rel);
            }
        }
        return null;
    }

    public function getLampiranBackgroundUrlAttribute(): ?string
    {
        if (!empty($this->lampiran_background_path)) {
            $rel = ltrim($this->lampiran_background_path, '/');
            if (file_exists(public_path('storage/' . $rel)) || file_exists(storage_path('app/public/' . $rel))) {
                return asset('storage/' . $rel);
            }
        }
        return null;
    }

    public function getSignatureUrlAttribute(): ?string
    {
        if (!empty($this->signature_path)) {
            $rel = ltrim($this->signature_path, '/');
            if (file_exists(public_path('storage/' . $rel)) || file_exists(storage_path('app/public/' . $rel))) {
                return asset('storage/' . $rel);
            }
        }
        return null;
    }

    public function getStampUrlAttribute(): ?string
    {
        if (!empty($this->stamp_path)) {
            $rel = ltrim($this->stamp_path, '/');
            if (file_exists(public_path('storage/' . $rel)) || file_exists(storage_path('app/public/' . $rel))) {
                return asset('storage/' . $rel);
            }
        }
        return null;
    }

    public function getTteBarcodeUrlAttribute(): ?string
    {
        if (!empty($this->tte_barcode_path)) {
            $rel = ltrim($this->tte_barcode_path, '/');
            if (file_exists(public_path('storage/' . $rel)) || file_exists(storage_path('app/public/' . $rel))) {
                return asset('storage/' . $rel);
            }
        }
        return null;
    }

    public function getSampleCertificateNumberAttribute(): string
    {
        $format = $this->number_format ?: '893.3/{NOMOR}/BKPSDM-PAS/{TAHUN}';
        return str_replace(['{NOMOR}', '{TAHUN}'], ['0412', date('Y')], $format);
    }

    public function getSampleVerificationCodeAttribute(): string
    {
        return 'PAS-' . date('Y') . '-9B4F-7K2X-M8PQ-3W1R-5J6T';
    }

    public function getSampleValidationUrlAttribute(): string
    {
        return url('/validasi/' . $this->sample_verification_code);
    }

    public function getSampleQrUrlAttribute(): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($this->sample_validation_url);
    }
}
