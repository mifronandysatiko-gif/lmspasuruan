<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LiteracyDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'author',
        'institution',
        'publication_year',
        'edition_volume',
        'isbn_issn',
        'description',
        'keywords',
        'file_path',
        'file_size_kb',
        'cover_image',
        'view_count',
        'download_count',
        'is_published',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'publication_year' => 'integer',
            'file_size_kb' => 'integer',
            'view_count' => 'integer',
            'download_count' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    protected $appends = [
        'category_label',
        'category_badge_class',
        'cover_url',
        'formatted_file_size',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'jurnal' => 'Jurnal Ilmiah',
            'karya_ilmiah' => 'Karya Ilmiah & Inovasi',
            'policy_brief' => 'Policy Brief',
            'modul_diklat' => 'Modul Kedinasan',
            'buku_panduan' => 'Buku Panduan & Pedoman',
            default => ucfirst(str_replace('_', ' ', $this->category)),
        };
    }

    public function getCategoryBadgeClassAttribute(): string
    {
        return match ($this->category) {
            'jurnal' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            'karya_ilmiah' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'policy_brief' => 'bg-amber-100 text-amber-800 border-amber-200',
            'modul_diklat' => 'bg-sky-100 text-sky-800 border-sky-200',
            'buku_panduan' => 'bg-purple-100 text-purple-800 border-purple-200',
            default => 'bg-slate-100 text-slate-800 border-slate-200',
        };
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (empty($this->cover_image)) {
            return null;
        }

        if (str_starts_with($this->cover_image, 'http://') || str_starts_with($this->cover_image, 'https://')) {
            return $this->cover_image;
        }

        if (file_exists(public_path($this->cover_image))) {
            return asset($this->cover_image);
        }

        if (file_exists(public_path('storage/' . $this->cover_image))) {
            return asset('storage/' . $this->cover_image);
        }

        return null;
    }

    public function getFormattedFileSizeAttribute(): string
    {
        if ($this->file_size_kb >= 1024) {
            return round($this->file_size_kb / 1024, 2) . ' MB';
        }
        return $this->file_size_kb . ' KB';
    }

    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}