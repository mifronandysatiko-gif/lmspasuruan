<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'certificate_template_id',
        'title',
        'slug',
        'type',
        'category',
        'description',
        'banner_path',
        'jp',
        'passing_grade',
        'max_remedial',
        'remedial_cap_score',
        'status',
        'time_limit_type',
        'duration_days',
        'start_date',
        'end_date',
        'certificate_number',
        'certificate_start_number',
        'certificate_format',
        'certificate_lampiran_path',
        'share_message',
    ];

    protected function casts(): array
    {
        return [
            'jp' => 'integer',
            'passing_grade' => 'integer',
            'max_remedial' => 'integer',
            'remedial_cap_score' => 'integer',
            'duration_days' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'certificate_start_number' => 'integer',
        ];
    }

    protected $appends = [
        'banner_url',
        'certificate_lampiran_url',
    ];

    public function getCertificateLampiranUrlAttribute(): ?string
    {
        if ($this->certificate_lampiran_path && file_exists(storage_path('app/public/' . $this->certificate_lampiran_path))) {
            return asset('storage/' . $this->certificate_lampiran_path);
        }
        return null;
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function certificateTemplate(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'certificate_template_id');
    }

    public function resolveCertificateTemplate(): CertificateTemplate
    {
        if ($this->certificate_template_id && $this->certificateTemplate) {
            return $this->certificateTemplate;
        }
        $specific = CertificateTemplate::where('course_id', $this->id)->first();
        if ($specific) {
            return $specific;
        }
        return CertificateTemplate::where('is_default', true)->first() ?: (CertificateTemplate::first() ?: new CertificateTemplate());
    }

    public function getOrganizerUnitKerjaAttribute(): string
    {
        $unit = $this->organizer?->unit_kerja ?? $this->organizer?->profile?->unit_kerja;
        if (!empty(trim($unit ?? ''))) {
            return trim($unit);
        }
        return 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia Kabupaten Pasuruan';
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class)->orderBy('sequence');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function questionnaireResponses(): HasMany
    {
        return $this->hasMany(CourseQuestionnaireResponse::class);
    }

    public function getBannerUrlAttribute(): ?string
    {
        if (empty($this->banner_path)) {
            return null;
        }

        if (str_starts_with($this->banner_path, 'http://') || str_starts_with($this->banner_path, 'https://')) {
            return $this->banner_path;
        }

        if (file_exists(public_path($this->banner_path))) {
            return asset($this->banner_path);
        }

        if (file_exists(public_path('storage/' . $this->banner_path))) {
            return asset('storage/' . $this->banner_path);
        }

        return null;
    }

    public function hasTimeLimit(): bool
    {
        return !empty($this->time_limit_type) && $this->time_limit_type !== 'none';
    }

    public function isRegistrationClosed(): bool
    {
        if ($this->end_date && Carbon::now('Asia/Jakarta')->greaterThan($this->end_date)) {
            return true;
        }
        return false;
    }

    public function isNotStartedYet(): bool
    {
        if ($this->start_date && Carbon::now('Asia/Jakarta')->lessThan($this->start_date)) {
            return true;
        }
        return false;
    }

    public function getTimeLimitDescription(): string
    {
        switch ($this->time_limit_type) {
            case 'relative_days':
                return "{$this->duration_days} Hari sejak mulai";
            case 'fixed_date':
                $start = $this->start_date ? ($this->start_date->format('H:i') !== '00:00' ? $this->start_date->format('d M Y, H:i \W\I\B') : $this->start_date->isoFormat('D MMM Y')) : null;
                $end = $this->end_date ? ($this->end_date->format('H:i') !== '00:00' ? $this->end_date->format('d M Y, H:i \W\I\B') : $this->end_date->isoFormat('D MMM Y')) : null;
                if ($start && $end) {
                    return "{$start} s.d. {$end}";
                } elseif ($end) {
                    return "Batas: {$end}";
                }
                return 'Jadwal Tertentu';
            case 'both':
                $end = $this->end_date ? ($this->end_date->format('H:i') !== '00:00' ? $this->end_date->format('d M Y, H:i \W\I\B') : $this->end_date->isoFormat('D MMM Y')) : '';
                return "Maks. {$this->duration_days} Hari (s.d. {$end})";
            default:
                return 'Fleksibel (Tanpa Batas)';
        }
    }

    public function getParticipantStatus(?User $user): array
    {
        if (!$user) {
            return [
                'status' => 'guest',
                'label' => 'Terbuka',
                'badge_class' => 'bg-slate-100 text-slate-700 border border-slate-200',
                'icon' => 'fa-door-open',
                'color' => 'slate',
                'progress' => 0,
            ];
        }

        $enrollment = CourseEnrollment::where('course_id', $this->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$enrollment) {
            if ($this->isRegistrationClosed()) {
                return [
                    'status' => 'closed',
                    'label' => 'Pendaftaran Ditutup',
                    'badge_class' => 'bg-slate-200 text-slate-700 border border-slate-300 font-bold',
                    'icon' => 'fa-calendar-xmark',
                    'color' => 'slate',
                    'progress' => 0,
                ];
            }

            if ($this->isNotStartedYet()) {
                $startFormatted = $this->start_date ? ($this->start_date->format('H:i') !== '00:00' ? $this->start_date->format('d M Y, H:i \W\I\B') : $this->start_date->isoFormat('D MMMM Y')) : '-';
                return [
                    'status' => 'upcoming',
                    'label' => 'Belum Dibuka (Mulai ' . $startFormatted . ')',
                    'badge_class' => 'bg-amber-100 text-amber-800 border border-amber-300 font-bold',
                    'icon' => 'fa-calendar-day',
                    'color' => 'amber',
                    'progress' => 0,
                ];
            }

            return [
                'status' => 'unregistered',
                'label' => 'Belum Terdaftar',
                'badge_class' => 'bg-slate-100 text-slate-600 border border-slate-200',
                'icon' => 'fa-clock',
                'color' => 'slate',
                'progress' => 0,
            ];
        }

        // Cek kedaluwarsa waktu pengerjaan
        $enrollment->checkAndEnforceExpiry();

        if ($enrollment->status === 'failed') {
            return [
                'status' => 'failed',
                'label' => 'Tidak Lulus (Batas Waktu Berakhir)',
                'badge_class' => 'bg-rose-600 text-white shadow-xs font-extrabold',
                'icon' => 'fa-circle-xmark',
                'color' => 'rose',
                'progress' => $enrollment->progress_percentage,
                'enrollment' => $enrollment,
            ];
        }

        if ($enrollment->status === 'completed' || $enrollment->status === 'passed' || $enrollment->progress_percentage >= 100) {
            return [
                'status' => 'completed',
                'label' => 'Lulus Bersertifikat',
                'badge_class' => 'bg-emerald-600 text-white shadow-xs font-extrabold',
                'icon' => 'fa-certificate',
                'color' => 'emerald',
                'progress' => 100,
                'enrollment' => $enrollment,
            ];
        }

        $remainingDays = $enrollment->remainingDays();
        $deadlineText = $enrollment->deadline_at ? $enrollment->deadline_at->isoFormat('D MMM Y') : ($this->end_date ? $this->end_date->isoFormat('D MMM Y') : null);

        if (!empty($enrollment->assignment_file)) {
            return [
                'status' => 'assignment_submitted',
                'label' => 'Tugas Terkirim (Menunggu Penilaian)',
                'badge_class' => 'bg-purple-600 text-white shadow-xs font-bold',
                'icon' => 'fa-file-arrow-up',
                'color' => 'purple',
                'progress' => $enrollment->progress_percentage,
                'remaining_days' => $remainingDays,
                'deadline_text' => $deadlineText,
                'enrollment' => $enrollment,
            ];
        }

        if ($enrollment->progress_percentage > 0) {
            return [
                'status' => 'in_progress',
                'label' => 'Sedang Berjalan (' . $enrollment->progress_percentage . '%)' . ($remainingDays !== null ? " • Sisa {$remainingDays} hari" : ''),
                'badge_class' => 'bg-blue-600 text-white shadow-xs font-bold',
                'icon' => 'fa-play',
                'color' => 'blue',
                'progress' => $enrollment->progress_percentage,
                'remaining_days' => $remainingDays,
                'deadline_text' => $deadlineText,
                'enrollment' => $enrollment,
            ];
        }

        return [
            'status' => 'enrolled',
            'label' => 'Terdaftar (0%)' . ($remainingDays !== null ? " • Sisa {$remainingDays} hari" : ''),
            'badge_class' => 'bg-cyan-600 text-white shadow-xs font-bold',
            'icon' => 'fa-circle-check',
            'color' => 'cyan',
            'progress' => 0,
            'remaining_days' => $remainingDays,
            'deadline_text' => $deadlineText,
            'enrollment' => $enrollment,
        ];
    }

    public function getFormattedShareMessageAttribute(): string
    {
        $url = url('/peserta/mooc/' . $this->slug);
        $typeStr = strtoupper(str_replace('_', ' ', $this->type ?: 'MOOC'));
        $desc = trim(strip_tags($this->description ?? ''));

        $msg = "📢 *PELATIHAN MANDIRI ({$typeStr}) BKPSDM KABUPATEN PASURUAN*\n\n" .
               "📌 *Program:* {$this->title}\n" .
               "📚 *Rumpun / Kategori:* {$this->category}\n" .
               "⏱ *Bobot:* {$this->jp} JP\n" .
               "🎯 *Passing Grade Kelulusan:* ≥ {$this->passing_grade}\n";

        if (!empty($desc)) {
            $msg .= "\n📝 *Deskripsi & Ringkasan Materi:*\n{$desc}\n";
        }

        $msg .= "\n🔗 *Tautan Akses Ruang Belajar:* {$url}\n\n" .
                "_Catatan: Khusus ASN Pemerintah Kabupaten Pasuruan. Silakan login untuk memulai materi pembelajaran._";

        return $msg;
    }
}
