@extends('layouts.dashboard')

@section('title', 'Profil & Pengaturan Akun - LMS Kabupaten Pasuruan')
@section('header_title', 'Profil & Pengaturan Akun')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{
    activeTab: '{{ request('tab', ($user->needsFirstLoginSetup() && $user->first_login_profile_updated ? 'security' : 'profile')) }}',
    avatarPreview: null,
    handleAvatarSelect(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                this.avatarPreview = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    }
}">


    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm space-y-1 shadow-xs">
            <div class="font-bold flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                <span>Terdapat kendala pada isian Anda:</span>
            </div>
            <ul class="list-disc list-inside text-xs space-y-0.5 ml-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($user->needsFirstLoginSetup())
        <!-- Onboarding Checklist Banner untuk Peserta Baru Pertama Kali Login -->
        <div class="rounded-3xl p-6 bg-gradient-to-br from-pasuruan-900 via-pasuruan-800 to-amber-950/50 border-2 border-amber-400 text-white shadow-xl relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 opacity-10 text-9xl pointer-events-none text-amber-400">
                <i class="fa-solid fa-user-shield"></i>
            </div>

            <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-400 text-pasuruan-950 flex items-center justify-center text-xl shrink-0 shadow-md">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-400 text-pasuruan-950">Aktivasi Akun Baru</span>
                            <span class="text-xs text-amber-300 font-semibold">• Selesaikan 2 Langkah Wajib</span>
                        </div>
                        <h2 class="text-lg sm:text-xl font-black text-white">Selamat Datang di LMS Kabupaten Pasuruan!</h2>
                        <p class="text-xs text-slate-300 max-w-2xl leading-relaxed">
                            Sebagai peserta baru, Anda wajib melengkapi data profil dan memperbarui kata sandi akun terlebih dahulu. Sebelum kedua langkah ini selesai, seluruh menu pembelajaran di sidebar akan terkunci.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Steps Progress Checklist -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-5 pt-4 border-t border-white/10 relative z-10">
                <!-- Step 1: Profil -->
                <div class="p-3.5 rounded-2xl border transition-all flex items-center justify-between gap-3 {{ $user->first_login_profile_updated ? 'bg-emerald-950/60 border-emerald-500/50 text-emerald-200' : 'bg-white/10 border-amber-400/50 text-white' }}">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 {{ $user->first_login_profile_updated ? 'bg-emerald-500 text-white' : 'bg-amber-400 text-pasuruan-950' }}">
                            @if($user->first_login_profile_updated)
                                <i class="fa-solid fa-check"></i>
                            @else
                                1
                            @endif
                        </div>
                        <div>
                            <p class="text-xs font-bold">Langkah 1: Lengkapi & Simpan Profil</p>
                            <p class="text-[11px] {{ $user->first_login_profile_updated ? 'text-emerald-300' : 'text-slate-300' }}">
                                {{ $user->first_login_profile_updated ? '✓ Data profil telah disimpan' : 'Wajib klik tombol Simpan Profil' }}
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="activeTab = 'profile'" class="px-3 py-1.5 rounded-lg text-[11px] font-bold shrink-0 transition-colors {{ $user->first_login_profile_updated ? 'bg-emerald-800/60 hover:bg-emerald-800 text-emerald-100' : 'bg-amber-400 hover:bg-amber-300 text-pasuruan-950' }}">
                        {{ $user->first_login_profile_updated ? 'Ubah Ulang' : 'Buka Tab Profil' }}
                    </button>
                </div>

                <!-- Step 2: Ganti Password -->
                <div class="p-3.5 rounded-2xl border transition-all flex items-center justify-between gap-3 {{ $user->first_login_password_changed ? 'bg-emerald-950/60 border-emerald-500/50 text-emerald-200' : 'bg-white/10 border-amber-400/50 text-white' }}">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 {{ $user->first_login_password_changed ? 'bg-emerald-500 text-white' : 'bg-amber-400 text-pasuruan-950' }}">
                            @if($user->first_login_password_changed)
                                <i class="fa-solid fa-check"></i>
                            @else
                                2
                            @endif
                        </div>
                        <div>
                            <p class="text-xs font-bold">Langkah 2: Perbarui Kata Sandi</p>
                            <p class="text-[11px] {{ $user->first_login_password_changed ? 'text-emerald-300' : 'text-slate-300' }}">
                                {{ $user->first_login_password_changed ? '✓ Kata sandi baru telah aktif' : 'Wajib ganti sandi awal default' }}
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="activeTab = 'security'" class="px-3 py-1.5 rounded-lg text-[11px] font-bold shrink-0 transition-colors {{ $user->first_login_password_changed ? 'bg-emerald-800/60 hover:bg-emerald-800 text-emerald-100' : 'bg-amber-400 hover:bg-amber-300 text-pasuruan-950' }}">
                        {{ $user->first_login_password_changed ? 'Ubah Lagi' : 'Buka Tab Keamanan' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Profile Header Card -->
    <div class="bg-gradient-to-br from-pasuruan-900 via-pasuruan-800 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 opacity-10 text-9xl pointer-events-none">
            <i class="fa-solid fa-id-card"></i>
        </div>

        <div class="relative z-10 flex flex-col sm:flex-row items-center sm:items-start gap-6 text-center sm:text-left">
            <!-- Avatar Display -->
            <div class="relative group shrink-0">
                <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl overflow-hidden bg-amber-400/20 border-2 border-amber-400/50 flex items-center justify-center text-3xl font-extrabold text-amber-300 shadow-lg">
                    @if($user->profile && $user->profile->photo_path && file_exists(storage_path('app/public/' . $user->profile->photo_path)))
                        <img src="{{ asset('storage/' . $user->profile->photo_path) }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                    @else
                        <span>{{ substr($user->name, 0, 1) }}</span>
                    @endif
                </div>
            </div>

            <!-- Identity Info -->
            <div class="flex-1 space-y-2">
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wider {{ $user->isAsn() ? 'bg-amber-400 text-amber-950' : 'bg-sky-400 text-sky-950' }}">
                        {{ $user->isAsn() ? 'Aparatur Sipil Negara (ASN)' : 'Peserta Umum / Non-ASN' }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono bg-white/15 text-slate-200 uppercase">
                        Peran: {{ str_replace('_', ' ', $user->role) }}
                    </span>
                    @if($user->profile && $user->profile->sync_source === 'api_simpeg')
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 flex items-center gap-1">
                            <i class="fa-solid fa-link"></i> Terverifikasi SIMPEG Pasuruan
                        </span>
                    @endif
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                    {{ $user->profile?->formatted_full_name ?? $user->name }}
                </h1>

                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-y-1 gap-x-4 text-xs text-slate-300">
                    @if($user->nip)
                        <div class="flex items-center gap-1.5 font-mono">
                            <i class="fa-solid fa-id-badge text-amber-400"></i>
                            <span>NIP: {{ $user->nip }}</span>
                        </div>
                    @endif
                    @if($user->profile?->nik)
                        <div class="flex items-center gap-1.5 font-mono">
                            <i class="fa-solid fa-address-card text-amber-400"></i>
                            <span>NIK: {{ $user->profile->nik }}</span>
                        </div>
                    @endif
                    <div class="flex items-center gap-1.5">
                        <i class="fa-solid fa-envelope text-amber-400"></i>
                        <span>{{ $user->email }}</span>
                    </div>
                    @if($user->profile?->unit_kerja)
                        <div class="flex items-center gap-1.5">
                            <i class="fa-solid fa-building text-amber-400"></i>
                            <span>{{ $user->profile->unit_kerja }}</span>
                        </div>
                    @endif
                    @if($user->profile?->jabatan)
                        <div class="flex items-center gap-1.5">
                            <i class="fa-solid fa-briefcase text-amber-400"></i>
                            <span>{{ $user->profile->jabatan }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200">
        <button type="button" @click="activeTab = 'profile'"
                class="px-5 py-3 text-xs font-bold transition-all border-b-2 flex items-center gap-2"
                :class="activeTab === 'profile' ? 'border-pasuruan-700 text-pasuruan-800' : 'border-transparent text-slate-500 hover:text-slate-800'">
            <i class="fa-solid fa-user-pen"></i>
            <span>Edit Biodata & Kontak</span>
            @if($user->needsFirstLoginSetup())
                @if($user->first_login_profile_updated)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-800">
                        <i class="fa-solid fa-check mr-1"></i> Selesai
                    </span>
                @else
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-900 animate-pulse">
                        Langkah 1
                    </span>
                @endif
            @endif
        </button>
        <button type="button" @click="activeTab = 'security'"
                class="px-5 py-3 text-xs font-bold transition-all border-b-2 flex items-center gap-2"
                :class="activeTab === 'security' ? 'border-pasuruan-700 text-pasuruan-800' : 'border-transparent text-slate-500 hover:text-slate-800'">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Ganti Kata Sandi & Keamanan</span>
            @if($user->needsFirstLoginSetup())
                @if($user->first_login_password_changed)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-800">
                        <i class="fa-solid fa-check mr-1"></i> Selesai
                    </span>
                @else
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-900 animate-pulse">
                        Langkah 2
                    </span>
                @endif
            @endif
        </button>
    </div>

    <!-- TAB 1: EDIT BIODATA & KONTAK -->
    <div x-show="activeTab === 'profile'" class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
        <div class="mb-6 pb-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Perbarui Informasi Profil</h2>
                <p class="text-xs text-slate-500">Sesuaikan data identitas dan kontak untuk penerbitan e-sertifikat resmi.</p>
            </div>
            <span class="text-xs font-mono text-slate-400 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-200">
                User ID #{{ $user->id }}
            </span>
        </div>

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6 text-xs">
            @csrf

            <!-- Foto Avatar / Profil -->
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                <label class="block font-bold text-slate-800">Foto Profil / Avatar</label>
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-xl overflow-hidden bg-slate-200 border border-slate-300 flex items-center justify-center shrink-0">
                        <template x-if="avatarPreview">
                            <img :src="avatarPreview" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!avatarPreview">
                            @if($user->profile && $user->profile->photo_path && file_exists(storage_path('app/public/' . $user->profile->photo_path)))
                                <img src="{{ asset('storage/' . $user->profile->photo_path) }}" class="w-full h-full object-cover">
                            @else
                                <i class="fa-solid fa-user text-2xl text-slate-400"></i>
                            @endif
                        </template>
                    </div>
                    <div class="flex-1 space-y-1">
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" @change="handleAvatarSelect($event)"
                               class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-pasuruan-100 file:text-pasuruan-800 hover:file:bg-pasuruan-200 cursor-pointer">
                        <p class="text-[11px] text-slate-400">Format: JPG, PNG, atau WebP. Ukuran maksimal 2 MB.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Nama Lengkap -->
                <div class="space-y-1.5 md:col-span-2">
                    <label class="block font-bold text-slate-700">Nama Lengkap (Tanpa Gelar) <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-700 focus:bg-white transition-all">
                </div>

                <!-- NIK (Nomor Induk Kependudukan) -->
                <div class="space-y-1.5 md:col-span-2">
                    <label class="block font-bold text-slate-700 flex items-center justify-between">
                        <span>Nomor Induk Kependudukan (NIK - 16 Digit)</span>
                        <span class="text-[10px] text-slate-400 font-normal">KTP Elektronik</span>
                    </label>
                    <input type="text" name="nik" value="{{ old('nik', $user->profile?->nik) }}" maxlength="20" placeholder="Contoh: 3514xxxxxxxxxxxx"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono tracking-wider font-semibold focus:ring-2 focus:ring-pasuruan-700 focus:bg-white transition-all">
                    <p class="text-[11px] text-slate-400">NIK tercatat pada database kepegawaian & rekap diklat.</p>
                </div>

                <!-- Gelar Depan -->
                <div class="space-y-1.5">
                    <label class="block font-bold text-slate-700">Gelar Depan (Opsional)</label>
                    <input type="text" name="gelar_depan" value="{{ old('gelar_depan', $user->profile?->gelar_depan) }}" placeholder="Contoh: Dr., Drs., Ir."
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-700 focus:bg-white transition-all">
                </div>

                <!-- Gelar Belakang -->
                <div class="space-y-1.5">
                    <label class="block font-bold text-slate-700">Gelar Belakang (Opsional)</label>
                    <input type="text" name="gelar_belakang" value="{{ old('gelar_belakang', $user->profile?->gelar_belakang) }}" placeholder="Contoh: S.Kom., M.Si."
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-700 focus:bg-white transition-all">
                </div>

                <!-- Email -->
                <div class="space-y-1.5">
                    <label class="block font-bold text-slate-700">Alamat Email Aktif <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-700 focus:bg-white transition-all">
                </div>

                <!-- Nomor HP / WhatsApp -->
                <div class="space-y-1.5">
                    <label class="block font-bold text-slate-700">Nomor WhatsApp / Kontak Handphone</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number', $user->profile?->phone_number) }}" placeholder="Contoh: 081234567890"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-700 focus:bg-white transition-all">
                </div>

                <!-- Unit Kerja / OPD / Instansi -->
                <div class="space-y-1.5">
                    <label class="block font-bold text-slate-700">Perangkat Daerah / Instansi / Unit Kerja</label>
                    <input type="text" name="unit_kerja" value="{{ old('unit_kerja', $user->profile?->unit_kerja) }}"
                           @if($user->isAsn() && $user->profile?->sync_source === 'api_simpeg') readonly class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl font-medium text-slate-600 cursor-not-allowed" @else class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-700 focus:bg-white transition-all" @endif>
                    @if($user->isAsn() && $user->profile?->sync_source === 'api_simpeg')
                        <span class="text-[10px] text-slate-400 block"><i class="fa-solid fa-lock text-slate-400"></i> Terkunci: Disinkronkan otomatis dengan SIMPEG BKPSDM.</span>
                    @endif
                </div>

                <!-- Jabatan -->
                <div class="space-y-1.5">
                    <label class="block font-bold text-slate-700">Jabatan Pekerjaan</label>
                    <input type="text" name="jabatan" value="{{ old('jabatan', $user->profile?->jabatan) }}"
                           @if($user->isAsn() && $user->profile?->sync_source === 'api_simpeg') readonly class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl font-medium text-slate-600 cursor-not-allowed" @else class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-700 focus:bg-white transition-all" @endif>
                </div>
            </div>

            <!-- Notice box -->
            <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200 text-amber-900 text-xs flex items-start gap-3">
                <i class="fa-solid fa-circle-info text-amber-600 text-base mt-0.5 shrink-0"></i>
                <div class="space-y-1">
                    <p class="font-bold">Ketepatan Data Sertifikat Digital:</p>
                    <p class="text-slate-600 text-[11px] leading-relaxed">
                        Nama lengkap dan gelar yang tercantum di sini akan langsung dicetak pada blanko e-sertifikat yang Anda peroleh saat lulus pelatihan webinar maupun MOOC.
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end pt-3 border-t border-slate-100">
                <button type="submit" class="px-6 py-2.5 bg-pasuruan-800 hover:bg-pasuruan-900 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk text-amber-300"></i>
                    <span>Simpan Perubahan Profil</span>
                </button>
            </div>
        </form>
    </div>

    <!-- TAB 2: KEAMANAN & GANTI KATA SANDI -->
    <div x-show="activeTab === 'security'" class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
        <div class="mb-6 pb-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-900">Ubah Kata Sandi Akun</h2>
            <p class="text-xs text-slate-500">Perbarui kata sandi secara berkala untuk menjaga keamanan data pembelajaran dan sertifikat Anda.</p>
        </div>

        <form action="{{ route('profile.password') }}" method="POST" class="max-w-xl space-y-5 text-xs">
            @csrf

            <!-- Kata Sandi Saat Ini -->
            <div class="space-y-1.5">
                <label class="block font-bold text-slate-700">Kata Sandi Saat Ini <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-key text-xs"></i>
                    </div>
                    <input type="password" name="current_password" required placeholder="Masukkan kata sandi yang sedang aktif"
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-700 focus:bg-white transition-all">
                </div>
                @if($user->needsFirstLoginSetup())
                    <p class="text-[11px] text-amber-700 font-semibold bg-amber-50 p-2.5 rounded-xl border border-amber-200 mt-1 flex items-start gap-2">
                        <i class="fa-solid fa-circle-info text-amber-600 mt-0.5 shrink-0"></i>
                        <span>Untuk akun baru yang belum pernah mengganti sandi, gunakan <strong>{{ $user->nip ? 'NIP Anda (' . $user->nip . ')' : 'kata sandi awal akun Anda (default: password123)' }}</strong> sebagai Kata Sandi Saat Ini.</span>
                    </p>
                @else
                    <p class="text-[11px] text-slate-400">Dibutuhkan untuk memverifikasi bahwa ini memang Anda.</p>
                @endif
            </div>

            <!-- Kata Sandi Baru -->
            <div class="space-y-1.5">
                <label class="block font-bold text-slate-700">Kata Sandi Baru <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-lock text-xs"></i>
                    </div>
                    <input type="password" name="password" required minlength="6" placeholder="Minimal 6 karakter"
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-700 focus:bg-white transition-all">
                </div>
            </div>

            <!-- Konfirmasi Kata Sandi Baru -->
            <div class="space-y-1.5">
                <label class="block font-bold text-slate-700">Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-shield-check text-xs"></i>
                    </div>
                    <input type="password" name="password_confirmation" required minlength="6" placeholder="Ulangi kata sandi baru"
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-700 focus:bg-white transition-all">
                </div>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 text-xs space-y-1.5">
                <div class="font-bold flex items-center gap-1.5 text-slate-900">
                    <i class="fa-solid fa-shield-halved text-emerald-600"></i>
                    <span>Ketentuan Keamanan Sandi:</span>
                </div>
                <ul class="list-disc list-inside text-[11px] text-slate-500 space-y-0.5 ml-1">
                    <li>Panjang minimal 6 karakter (disarankan 8 karakter atau lebih).</li>
                    <li>Gunakan kombinasi huruf, angka, atau simbol agar lebih aman.</li>
                    <li>Jangan beritahukan kata sandi Anda kepada siapa pun termasuk pihak yang mengatasnamakan panitia.</li>
                </ul>
            </div>

            <div class="pt-2">
                <button type="submit" class="px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2">
                    <i class="fa-solid fa-lock text-amber-300"></i>
                    <span>Perbarui Kata Sandi</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
