<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'certificate_number',
        'hash_token',
        'user_id',
        'nip',
        'nik',
        'nama_lengkap',
        'jabatan',
        'unit_kerja',
        'webinar_id',
        'course_id',
        'template_id',
        'training_title',
        'organizer_unit_kerja',
        'jp',
        'pdf_path',
        'issued_at',
    ];

    protected $appends = [
        'background_url',
        'lampiran_background_url',
        'has_lampiran',
        'layout_coordinates',
        'organizer_unit_kerja',
    ];

    protected function casts(): array
    {
        return [
            'jp' => 'integer',
            'issued_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function webinar(): BelongsTo
    {
        return $this->belongsTo(Webinar::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    /**
     * Menghasilkan nomor surat sertifikat sekuensial:
     * - Mendukung pengaturan nomor awal (certificate_start_number) per pelatihan / webinar / global template.
     * - Mendukung format nomor custom per kegiatan atau global.
     */
    public static function generateCertificateNumber($training = null): string
    {
        $template = ($training && method_exists($training, 'resolveCertificateTemplate'))
            ? $training->resolveCertificateTemplate()
            : (CertificateTemplate::where('is_default', true)->first() ?: CertificateTemplate::first());
        $defaultFormat = $template?->number_format ?: '893.3/{NOMOR}/BKPSDM-PAS/{TAHUN}';
        $year = date('Y');

        // 1. Tentukan format penomoran
        $format = $defaultFormat;
        if ($training && !empty(trim($training->certificate_format ?? ''))) {
            $format = trim($training->certificate_format);
        } elseif ($training && !empty(trim($training->certificate_number ?? '')) && str_contains($training->certificate_number, '{NOMOR}')) {
            $format = trim($training->certificate_number);
        }

        // 2. Tentukan nomor awal (start number)
        $startNum = null;
        if ($training && !empty($training->certificate_start_number) && $training->certificate_start_number > 0) {
            $startNum = (int) $training->certificate_start_number;
        } elseif ($template && !empty($template->start_number) && $template->start_number > 0) {
            $startNum = (int) $template->start_number;
        }

        // Jika training memiliki nomor statis tanpa {NOMOR} dan tanpa start_number
        if ($training && !empty(trim($training->certificate_number ?? '')) && !str_contains($training->certificate_number, '{NOMOR}') && empty($training->certificate_start_number)) {
            return trim($training->certificate_number);
        }

        // 3. Tentukan nomor urut berjalan sekuensial
        if ($training instanceof Course) {
            $issuedCount = self::where('course_id', $training->id)->count();
            $baseSeq = $startNum ?: 1;
            $seq = $baseSeq + $issuedCount;
        } elseif ($training instanceof Webinar) {
            $issuedCount = self::where('webinar_id', $training->id)->count();
            $baseSeq = $startNum ?: 1;
            $seq = $baseSeq + $issuedCount;
        } else {
            $baseSeq = $startNum ?: 1;
            $seq = max($baseSeq, ((int) (self::max('id') ?? 0)) + 1);
        }

        // Pastikan nomor unik
        do {
            $numStr = str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            $candidate = str_replace(['{NOMOR}', '{TAHUN}'], [$numStr, $year], $format);
            $exists = self::where('certificate_number', $candidate)->exists();
            if ($exists) {
                $seq++;
            }
        } while ($exists);

        return $candidate;
    }

    public function isCourseCertificate(): bool
    {
        return !empty($this->course_id) && !empty($this->course);
    }

    public function hasLampiran(): bool
    {
        if ($this->isCourseCertificate()) {
            return in_array($this->course->type, ['mooc', 'teknis', 'teknis_fungsional', 'kepemimpinan']);
        }
        return false;
    }

    public function getHasLampiranAttribute(): bool
    {
        return $this->hasLampiran();
    }

    public function getLampiranBackgroundUrlAttribute(): ?string
    {
        if ($this->course && $this->course->certificate_lampiran_url) {
            return $this->course->certificate_lampiran_url;
        }

        return $this->template?->lampiran_background_url 
            ?? CertificateTemplate::where('is_default', true)->first()?->lampiran_background_url 
            ?? CertificateTemplate::first()?->lampiran_background_url;
    }

    public function getBackgroundUrlAttribute(): ?string
    {
        return $this->template?->background_url 
            ?? CertificateTemplate::where('is_default', true)->first()?->background_url 
            ?? CertificateTemplate::first()?->background_url;
    }

    public function getLayoutCoordinatesAttribute(): ?array
    {
        return $this->template?->layout_coordinates 
            ?? CertificateTemplate::where('is_default', true)->first()?->layout_coordinates 
            ?? CertificateTemplate::first()?->layout_coordinates;
    }

    public function getOrganizerUnitKerjaAttribute(): string
    {
        if (!empty($this->attributes['organizer_unit_kerja'])) {
            return $this->attributes['organizer_unit_kerja'];
        }

        if ($this->webinar) {
            $unit = $this->webinar->organizer?->unit_kerja ?? $this->webinar->organizer?->profile?->unit_kerja;
            if (!empty(trim($unit ?? ''))) {
                return trim($unit);
            }
        }

        if ($this->course) {
            $unit = $this->course->organizer?->unit_kerja ?? $this->course->organizer?->profile?->unit_kerja;
            if (!empty(trim($unit ?? ''))) {
                return trim($unit);
            }
        }

        return 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia Kabupaten Pasuruan';
    }

    /**
     * Terbitkan E-Sertifikat resmi untuk Webinar jika belum pernah terbit
     */
    public static function issueForWebinar(Webinar $webinar, User $user): self
    {
        $existing = self::where('webinar_id', $webinar->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $template = $webinar->resolveCertificateTemplate();
        $certNum = self::generateCertificateNumber($webinar);
        $profile = $user->profile;
        $organizerUnit = $webinar->organizer?->unit_kerja 
            ?? $webinar->organizer?->profile?->unit_kerja 
            ?? 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia Kabupaten Pasuruan';

        return self::create([
            'certificate_number' => $certNum,
            'hash_token' => self::generateVerificationCode(),
            'user_id' => $user->id,
            'nip' => $user->nip,
            'nik' => $profile?->nik,
            'nama_lengkap' => $profile?->formatted_full_name ?? $user->name,
            'jabatan' => $profile?->jabatan,
            'unit_kerja' => $profile?->unit_kerja,
            'webinar_id' => $webinar->id,
            'course_id' => null,
            'template_id' => $template?->id ?: 1,
            'training_title' => $webinar->title,
            'organizer_unit_kerja' => $organizerUnit,
            'jp' => $webinar->jp,
            'pdf_path' => 'certificates/webinar_' . $webinar->id . '_user_' . $user->id . '.pdf',
            'issued_at' => now(),
        ]);
    }

    /**
     * Terbitkan E-Sertifikat resmi untuk Pelatihan Course (MOOC / BIMTEK Teknis / Kepemimpinan) jika belum pernah terbit
     */
    public static function issueForCourse(Course $course, User $user): self
    {
        $existing = self::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $template = $course->resolveCertificateTemplate();
        $certNum = self::generateCertificateNumber($course);
        $profile = $user->profile;
        $organizerUnit = $course->organizer?->unit_kerja 
            ?? $course->organizer?->profile?->unit_kerja 
            ?? 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia Kabupaten Pasuruan';

        return self::create([
            'certificate_number' => $certNum,
            'hash_token' => self::generateVerificationCode(),
            'user_id' => $user->id,
            'nip' => $user->nip,
            'nik' => $profile?->nik,
            'nama_lengkap' => $profile?->formatted_full_name ?? $user->name,
            'jabatan' => $profile?->jabatan,
            'unit_kerja' => $profile?->unit_kerja,
            'webinar_id' => null,
            'course_id' => $course->id,
            'template_id' => $template?->id ?: 1,
            'training_title' => $course->title,
            'organizer_unit_kerja' => $organizerUnit,
            'jp' => $course->jp,
            'pdf_path' => 'certificates/course_' . $course->id . '_user_' . $user->id . '.pdf',
            'issued_at' => now(),
        ]);
    }

    public static function generateVerificationCode(?int $year = null): string
    {
        $year = $year ?? (int) date('Y');
        $charset = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $blocks = [];
        for ($b = 0; $b < 5; $b++) {
            $block = '';
            for ($i = 0; $i < 4; $i++) {
                $block .= $charset[random_int(0, strlen($charset) - 1)];
            }
            $blocks[] = $block;
        }
        return "PAS-{$year}-" . implode('-', $blocks);
    }

    public function getValidationUrlAttribute(): string
    {
        return url('/validasi/' . $this->hash_token);
    }

    public function getQrCodeUrlAttribute(): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($this->validation_url);
    }

    public function getShortValidationLinkAttribute(): string
    {
        $host = request()?->getHost() ?? 'lms.pasuruankab.go.id';
        return $host . '/validasi/' . $this->hash_token;
    }
}
