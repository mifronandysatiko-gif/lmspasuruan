<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Webinar extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'certificate_template_id',
        'title',
        'slug',
        'description',
        'banner_path',
        'youtube_link',
        'meeting_link',
        'jp',
        'event_date',
        'start_time',
        'end_time',
        'is_live',
        'questionnaire_start_time',
        'questionnaire_end_time',
        'exam_start_time',
        'exam_end_time',
        'passing_grade',
        'max_remedial',
        'remedial_cap_score',
        'certificate_number',
        'certificate_start_number',
        'certificate_format',
        'share_message',
    ];

    protected function casts(): array
    {
        return [
            'is_live' => 'boolean',
            'jp' => 'integer',
            'passing_grade' => 'integer',
            'max_remedial' => 'integer',
            'remedial_cap_score' => 'integer',
            'event_date' => 'date',
            'certificate_start_number' => 'integer',
        ];
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
        $specific = CertificateTemplate::where('webinar_id', $this->id)->first();
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

    public function registrations(): HasMany
    {
        return $this->hasMany(WebinarRegistration::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(WebinarAttendance::class);
    }

    public function examAttempts(): HasMany
    {
        return $this->hasMany(WebinarExamAttempt::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(WebinarQuestion::class);
    }

    public function quizQuestions(): HasMany
    {
        return $this->hasMany(WebinarQuestion::class)->where('type', 'quiz')->orderBy('order_number');
    }

    public function questionnaireQuestions(): HasMany
    {
        return $this->hasMany(WebinarQuestion::class)->where('type', 'questionnaire')->orderBy('order_number');
    }

    public function questionnaireResponses(): HasMany
    {
        return $this->hasMany(WebinarQuestionnaireResponse::class);
    }

    public function getStartDateTimeAttribute(): ?Carbon
    {
        if (!$this->event_date || !$this->start_time) {
            return null;
        }
        $dateStr = $this->event_date instanceof Carbon ? $this->event_date->format('Y-m-d') : substr((string) $this->event_date, 0, 10);
        return Carbon::parse("{$dateStr} {$this->start_time}", 'Asia/Jakarta');
    }

    public function getEndDateTimeAttribute(): ?Carbon
    {
        if (!$this->event_date || !$this->end_time) {
            return null;
        }
        $dateStr = $this->event_date instanceof Carbon ? $this->event_date->format('Y-m-d') : substr((string) $this->event_date, 0, 10);
        return Carbon::parse("{$dateStr} {$this->end_time}", 'Asia/Jakarta');
    }

    public function getQuestionnaireStartDateTimeAttribute(): ?Carbon
    {
        if (!$this->event_date || !$this->questionnaire_start_time) {
            return null;
        }
        $dateStr = $this->event_date instanceof Carbon ? $this->event_date->format('Y-m-d') : substr((string) $this->event_date, 0, 10);
        return Carbon::parse("{$dateStr} {$this->questionnaire_start_time}", 'Asia/Jakarta');
    }

    public function getQuestionnaireEndDateTimeAttribute(): ?Carbon
    {
        if (!$this->event_date || !$this->questionnaire_end_time) {
            return null;
        }
        $dateStr = $this->event_date instanceof Carbon ? $this->event_date->format('Y-m-d') : substr((string) $this->event_date, 0, 10);
        return Carbon::parse("{$dateStr} {$this->questionnaire_end_time}", 'Asia/Jakarta');
    }

    public function getExamStartDateTimeAttribute(): ?Carbon
    {
        if (!$this->event_date || !$this->exam_start_time) {
            return null;
        }
        $dateStr = $this->event_date instanceof Carbon ? $this->event_date->format('Y-m-d') : substr((string) $this->event_date, 0, 10);
        return Carbon::parse("{$dateStr} {$this->exam_start_time}", 'Asia/Jakarta');
    }

    public function getExamEndDateTimeAttribute(): ?Carbon
    {
        if (!$this->event_date || !$this->exam_end_time) {
            return null;
        }
        $dateStr = $this->event_date instanceof Carbon ? $this->event_date->format('Y-m-d') : substr((string) $this->event_date, 0, 10);
        return Carbon::parse("{$dateStr} {$this->exam_end_time}", 'Asia/Jakarta');
    }

    public function isNotStartedYet(): bool
    {
        $start = $this->start_datetime;
        if ($start && Carbon::now('Asia/Jakarta')->lessThan($start)) {
            return true;
        }
        return false;
    }

    public function isFinished(): bool
    {
        $end = $this->end_datetime;
        if ($end && Carbon::now('Asia/Jakarta')->greaterThan($end)) {
            return true;
        }
        return false;
    }

    public function isWithinLiveSchedule(): bool
    {
        $now = Carbon::now('Asia/Jakarta');
        $start = $this->start_datetime;
        $end = $this->end_datetime;
        if ($start && $end) {
            return $now->between($start, $end);
        }
        return false;
    }

    public function isQuestionnaireOpen(): bool
    {
        $now = Carbon::now('Asia/Jakarta');
        $qStart = $this->questionnaire_start_datetime;
        $qEnd = $this->questionnaire_end_datetime;

        if ($qStart && $qEnd) {
            return $now->between($qStart, $qEnd);
        }

        if ($qStart && $now->lessThan($qStart)) {
            return false;
        }

        if ($qEnd && $now->greaterThan($qEnd)) {
            return false;
        }

        return true;
    }

    public function isExamOpen(): bool
    {
        $now = Carbon::now('Asia/Jakarta');
        $eStart = $this->exam_start_datetime;
        $eEnd = $this->exam_end_datetime;

        if ($eStart && $eEnd) {
            return $now->between($eStart, $eEnd);
        }

        if ($eStart && $now->lessThan($eStart)) {
            return false;
        }

        if ($eEnd && $now->greaterThan($eEnd)) {
            return false;
        }

        return true;
    }

    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        if (empty($this->youtube_link)) {
            return null;
        }

        $link = trim($this->youtube_link);
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=|live\/)|youtu\.be\/)([^"&?\/\s]{11})/', $link, $matches)) {
            return "https://www.youtube-nocookie.com/embed/{$matches[1]}?autoplay=0&rel=0";
        }

        return null;
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

    public function getParticipantStatus(?User $user): array
    {
        if (!$user) {
            return [
                'status' => 'guest',
                'label' => 'Terbuka',
                'badge_class' => 'bg-slate-100 text-slate-700 border border-slate-200',
                'icon' => 'fa-door-open',
                'color' => 'slate',
            ];
        }

        // 1. Cek sertifikat
        $cert = Certificate::where('webinar_id', $this->id)->where('user_id', $user->id)->first();
        if ($cert) {
            return [
                'status' => 'certified',
                'label' => 'E-Sertifikat Terbit',
                'badge_class' => 'bg-emerald-600 text-white shadow-xs font-extrabold',
                'icon' => 'fa-certificate',
                'color' => 'emerald',
                'certificate' => $cert,
            ];
        }

        // 2. Cek evaluasi kelulusan
        $exam = WebinarExamAttempt::where('webinar_id', $this->id)
            ->where('user_id', $user->id)
            ->where('is_passed', true)
            ->first();
        if ($exam) {
            return [
                'status' => 'passed',
                'label' => 'LULUS Evaluasi',
                'badge_class' => 'bg-blue-600 text-white shadow-xs font-extrabold',
                'icon' => 'fa-award',
                'color' => 'blue',
                'score' => $exam->final_score,
            ];
        }

        // 3. Cek kehadiran
        $att = WebinarAttendance::where('webinar_id', $this->id)
            ->where('user_id', $user->id)
            ->first();
        if ($att) {
            return [
                'status' => 'attended',
                'label' => 'Hadir Tercatat',
                'badge_class' => 'bg-indigo-600 text-white shadow-xs font-bold',
                'icon' => 'fa-clipboard-check',
                'color' => 'indigo',
                'attended_at' => $att->attended_at,
            ];
        }

        // 4. Cek pendaftaran webinar (Tahap 1)
        $reg = WebinarRegistration::where('webinar_id', $this->id)
            ->where('user_id', $user->id)
            ->first();
        if ($reg) {
            if ($this->is_live) {
                return [
                    'status' => 'registered_live',
                    'label' => 'Terdaftar • Sesi LIVE',
                    'badge_class' => 'bg-rose-600 text-white shadow-xs font-extrabold animate-pulse',
                    'icon' => 'fa-tower-broadcast',
                    'color' => 'rose',
                    'registered_at' => $reg->registered_at,
                ];
            }

            if ($this->isNotStartedYet()) {
                $startFormatted = $this->start_datetime ? $this->start_datetime->format('d M Y, H:i') : null;
                return [
                    'status' => 'registered_upcoming',
                    'label' => 'Terdaftar • Mulai ' . ($startFormatted ? "{$startFormatted} WIB" : '-'),
                    'badge_class' => 'bg-amber-100 text-amber-800 border border-amber-300 font-bold',
                    'icon' => 'fa-calendar-clock',
                    'color' => 'amber',
                    'registered_at' => $reg->registered_at,
                ];
            }

            if ($this->isFinished()) {
                return [
                    'status' => 'registered_finished',
                    'label' => 'Terdaftar • Sesi Berakhir',
                    'badge_class' => 'bg-slate-200 text-slate-700 border border-slate-300 font-bold',
                    'icon' => 'fa-calendar-check',
                    'color' => 'slate',
                    'registered_at' => $reg->registered_at,
                ];
            }

            return [
                'status' => 'registered',
                'label' => 'Sudah Terdaftar',
                'badge_class' => 'bg-sky-600 text-white shadow-xs font-bold',
                'icon' => 'fa-circle-check',
                'color' => 'sky',
                'registered_at' => $reg->registered_at,
            ];
        }

        return [
            'status' => 'unregistered',
            'label' => 'Belum Terdaftar',
            'badge_class' => 'bg-slate-100 text-slate-700 border border-slate-200 font-bold',
            'icon' => 'fa-door-open',
            'color' => 'slate',
        ];
    }

    public function getFormattedShareMessageAttribute(): string
    {
        $url = url('/peserta/webinar/' . $this->slug);
        $schedule = ($this->event_date ? $this->event_date->isoFormat('D MMMM Y') : '-') . ' (' . ($this->start_time ? substr($this->start_time, 0, 5) : '08:30') . ' WIB)';
        $desc = trim(strip_tags($this->description ?? ''));

        $msg = "📢 *UNDANGAN WEBINAR BKPSDM KABUPATEN PASURUAN*\n\n" .
               "📌 *Tema:* {$this->title}\n" .
               "⏱ *Bobot:* {$this->jp} JP\n" .
               "📅 *Jadwal:* {$schedule}\n";

        if (!empty($desc)) {
            $msg .= "\n📝 *Deskripsi & Ringkasan Materi:*\n{$desc}\n";
        }

        $msg .= "\n🔗 *Tautan Pendaftaran & Akses:* {$url}\n\n" .
                "_Catatan: Silakan login dengan akun ASN/Peserta Anda untuk mendaftar & presensi kehadiran._";

        return $msg;
    }
}

