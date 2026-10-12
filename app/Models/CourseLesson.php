<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CourseLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'sequence',
        'video_url',
        'duration_seconds',
        'jp',
        'pdf_path',
        'content_summary',
        'has_quiz',
    ];

    protected $appends = ['calculated_jp'];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'duration_seconds' => 'integer',
            'jp' => 'integer',
            'has_quiz' => 'boolean',
        ];
    }

    public function getCalculatedJpAttribute(): int
    {
        if (!is_null($this->jp) && $this->jp > 0) {
            return (int) $this->jp;
        }

        // Hitung estimasi dari durasi detik (45 menit = 2700 detik = 1 JP)
        if ($this->duration_seconds && $this->duration_seconds > 0) {
            return max(1, (int) round($this->duration_seconds / 2700));
        }

        return 2; // Default 2 JP per modul pembelajaran
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(CourseLessonQuestion::class, 'course_lesson_id')->orderBy('order_number');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(CourseLessonCompletion::class, 'course_lesson_id');
    }

    /**
     * Dapatkan ID YouTube jika URL berupa video YouTube
     */
    public function getYoutubeIdAttribute(): ?string
    {
        $url = trim($this->video_url ?? '');
        if (empty($url)) {
            return null;
        }

        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/|youtube\.com\/shorts\/)([^"&?\/ ]{11})/i', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Menghasilkan URL Embed Video yang siap diputar di <iframe> (YouTube, Vimeo, Embed HTML)
     */
    public function getEmbedVideoUrlAttribute(): string
    {
        $url = trim($this->video_url ?? '');
        if (empty($url)) {
            return '';
        }

        // 1. YouTube
        $ytId = $this->youtube_id;
        if ($ytId) {
            return "https://www.youtube.com/embed/{$ytId}?enablejsapi=1&rel=0&playsinline=1&modestbranding=1";
        }

        // 2. Vimeo
        if (preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)/i', $url, $matches)) {
            $vimeoId = end($matches);
            return "https://player.vimeo.com/video/{$vimeoId}";
        }

        // 3. Direct embed
        return $url;
    }

    /**
     * Cek apakah URL video adalah file video MP4/WebM langsung
     */
    public function getIsDirectVideoAttribute(): bool
    {
        $url = strtolower(trim($this->video_url ?? ''));
        return str_ends_with($url, '.mp4') || str_ends_with($url, '.webm') || str_ends_with($url, '.ogg') || str_ends_with($url, '.mov');
    }

    /**
     * Dapatkan URL file PDF yang valid
     */
    public function getPdfUrlAttribute(): ?string
    {
        if (empty($this->pdf_path)) {
            return null;
        }

        if (str_starts_with($this->pdf_path, 'http://') || str_starts_with($this->pdf_path, 'https://')) {
            return $this->pdf_path;
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($this->pdf_path)) {
            return \Illuminate\Support\Facades\Storage::url($this->pdf_path);
        }

        return asset('storage/' . ltrim($this->pdf_path, '/'));
    }

    /**
     * Dapatkan nama file PDF yang ramah dibaca
     */
    public function getPdfNameAttribute(): string
    {
        if (empty($this->pdf_path)) {
            return 'Dokumen_Materi.pdf';
        }

        return basename($this->pdf_path);
    }
}
