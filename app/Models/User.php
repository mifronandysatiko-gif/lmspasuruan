<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nip',
        'nik',
        'jabatan',
        'unit_kerja',
        'username',
        'email',
        'password',
        'role',
        'is_active',
        'first_login_completed',
        'first_login_profile_updated',
        'first_login_password_changed',
        'google_id',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'first_login_completed' => 'boolean',
            'first_login_profile_updated' => 'boolean',
            'first_login_password_changed' => 'boolean',
        ];
    }

    /**
     * Cek apakah pengguna (khusus peserta) wajib menyelesaikan pengaktifan profil dan kata sandi awal.
     */
    public function needsFirstLoginSetup(): bool
    {
        return $this->isPeserta() && !$this->first_login_completed;
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
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

    public function courseEnrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isPenyelenggara(): bool
    {
        return $this->role === 'penyelenggara';
    }

    public function isWidyaiswara(): bool
    {
        return $this->role === 'widyaiswara';
    }

    public function isMentor(): bool
    {
        return $this->role === 'mentor';
    }

    public function isPeserta(): bool
    {
        return $this->role === 'peserta';
    }

    public function isAsn(): bool
    {
        return !empty($this->nip);
    }

    public function isPesertaUmum(): bool
    {
        return $this->role === 'peserta' && empty($this->nip);
    }

    public function isPesertaAsn(): bool
    {
        return $this->role === 'peserta' && !empty($this->nip);
    }

    public function isAsnPasuruan(): bool
    {
        return !empty($this->nip);
    }

    public function getNikAttribute(): ?string
    {
        return $this->attributes['nik'] ?? $this->profile?->nik;
    }

    public function getJabatanAttribute(): ?string
    {
        return $this->attributes['jabatan'] ?? $this->profile?->jabatan;
    }

    public function getUnitKerjaAttribute(): ?string
    {
        return $this->attributes['unit_kerja'] ?? $this->profile?->unit_kerja;
    }

    public function getFormattedFullNameAttribute(): string
    {
        return $this->profile?->formatted_full_name ?? $this->name;
    }
}
