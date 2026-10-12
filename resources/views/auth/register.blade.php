@extends('layouts.app')

@section('title', 'Daftar Akun Peserta - LMS Kabupaten Pasuruan')

@section('content')
<div class="min-h-[85vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl w-full space-y-6" 
         x-data="{ 
            accountType: '{{ old('account_type', 'asn_pasuruan') }}',
            nip: '{{ old('nip', '') }}',
            nik: '{{ old('nik', '') }}',
            name: '{{ old('name', '') }}',
            gelar_depan: '{{ old('gelar_depan', '') }}',
            gelar_belakang: '{{ old('gelar_belakang', '') }}',
            jabatan: '{{ old('jabatan', '') }}',
            unit_kerja: '{{ old('unit_kerja', '') }}',
            email: '{{ old('email', '') }}',
            phone_number: '{{ old('phone_number', '') }}',
            
            nipStatus: 'idle', // 'idle' | 'checking' | 'verified' | 'not_found' | 'already_registered' | 'inactive' | 'error'
            nipMessage: '',
            verifiedPegawai: null,

            init() {
                if (this.accountType === 'asn_pasuruan' && this.nip && this.nip.length >= 8) {
                    this.verifyNip();
                }
            },

            async verifyNip() {
                const cleanNip = (this.nip || '').trim();
                if (!cleanNip) {
                    this.nipStatus = 'error';
                    this.nipMessage = 'Silakan ketikkan Nomor Induk Pegawai (NIP).';
                    return;
                }

                this.nipStatus = 'checking';
                this.nipMessage = 'Memverifikasi NIP ke Master Data Pegawai BKPSDM Kab. Pasuruan...';
                this.verifiedPegawai = null;

                try {
                    const response = await fetch('{{ route('api.check_nip') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ nip: cleanNip })
                    });

                    const data = await response.json();

                    if (data.valid && data.pegawai) {
                        this.nipStatus = 'verified';
                        this.nipMessage = data.message;
                        this.verifiedPegawai = data.pegawai;

                        // Autofill data ke formulir
                        if (data.pegawai.nik) this.nik = data.pegawai.nik;
                        if (data.pegawai.nama) this.name = data.pegawai.nama;
                        if (data.pegawai.gelar_depan !== undefined) this.gelar_depan = data.pegawai.gelar_depan;
                        if (data.pegawai.gelar_belakang !== undefined) this.gelar_belakang = data.pegawai.gelar_belakang;
                        if (data.pegawai.jabatan) this.jabatan = data.pegawai.jabatan;
                        if (data.pegawai.unit_kerja) this.unit_kerja = data.pegawai.unit_kerja;
                        if (!this.email && data.pegawai.email) this.email = data.pegawai.email;
                        if (!this.phone_number && data.pegawai.no_hp) this.phone_number = data.pegawai.no_hp;
                    } else if (data.status === 'already_registered') {
                        this.nipStatus = 'already_registered';
                        this.nipMessage = data.message;
                    } else if (data.status === 'not_found') {
                        this.nipStatus = 'not_found';
                        this.nipMessage = data.message || 'NIP tidak terdaftar dalam Master Data Pegawai Pemerintah Kabupaten Pasuruan.';
                    } else {
                        this.nipStatus = 'error';
                        this.nipMessage = data.message || 'Pemeriksaan NIP gagal.';
                    }
                } catch (err) {
                    this.nipStatus = 'error';
                    this.nipMessage = 'Gagal menghubungi server verifikasi NIP. Silakan periksa koneksi Anda.';
                }
            },

            resetNipState() {
                this.nipStatus = 'idle';
                this.nipMessage = '';
                this.verifiedPegawai = null;
            }
         }">
        
        <div class="text-center">
            <picture>
                <source srcset="{{ asset('images/logo-pasuruan.webp') }}" type="image/webp">
                <img src="{{ asset('images/logo-pasuruan.png') }}" alt="Logo Kabupaten Pasuruan" class="h-16 w-auto mx-auto object-contain mb-3 drop-shadow-md">
            </picture>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                Pendaftaran Akun Peserta Diklat
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                LMS Terpadu BKPSDM Pemerintah Kabupaten Pasuruan
            </p>
        </div>

        <!-- Tab Pemilihan Jenis Akun (3 Pilihan) -->
        <div class="bg-slate-200/90 p-1.5 rounded-2xl grid grid-cols-3 gap-1.5 text-xs font-bold shadow-inner">
            <button type="button" 
                    @click="accountType = 'asn_pasuruan'; resetNipState(); if(nip.length >= 8) verifyNip();" 
                    :class="accountType === 'asn_pasuruan' ? 'bg-white text-emerald-800 shadow-sm ring-1 ring-emerald-400/50' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'" 
                    class="py-3 px-2 rounded-xl transition-all flex flex-col sm:flex-row items-center justify-center gap-2 text-center">
                <i class="fa-solid fa-id-badge text-base text-emerald-600"></i>
                <span class="leading-tight">ASN Kab. Pasuruan</span>
            </button>
            <button type="button" 
                    @click="accountType = 'asn_luar'" 
                    :class="accountType === 'asn_luar' ? 'bg-white text-amber-800 shadow-sm ring-1 ring-amber-400/50' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'" 
                    class="py-3 px-2 rounded-xl transition-all flex flex-col sm:flex-row items-center justify-center gap-2 text-center">
                <i class="fa-solid fa-landmark text-base text-amber-600"></i>
                <span class="leading-tight">ASN Luar Pasuruan</span>
            </button>
            <button type="button" 
                    @click="accountType = 'umum'" 
                    :class="accountType === 'umum' ? 'bg-white text-sky-800 shadow-sm ring-1 ring-sky-400/50' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'" 
                    class="py-3 px-2 rounded-xl transition-all flex flex-col sm:flex-row items-center justify-center gap-2 text-center">
                <i class="fa-solid fa-users text-base text-sky-600"></i>
                <span class="leading-tight">Peserta Umum</span>
            </button>
        </div>

        <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
            <!-- Tampilan Error Validasi Server -->
            @if ($errors->any())
                <div class="mb-5 p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 flex items-start gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600 text-sm mt-0.5 shrink-0"></i>
                    <div>
                        <div class="font-bold mb-1">Pendaftaran tidak dapat diproses:</div>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Opsi Masuk / Daftar dengan Google -->
            <div class="mb-5">
                <a href="{{ route('auth.google') }}" class="w-full py-2.5 px-4 bg-white hover:bg-slate-50 border border-slate-300 hover:border-slate-400 rounded-xl text-xs font-bold text-slate-700 shadow-2xs hover:shadow transition-all flex items-center justify-center gap-3 group">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span class="group-hover:text-slate-900 font-semibold">Daftar Cepat dengan Akun Google</span>
                </a>

                <div class="relative flex py-3.5 items-center">
                    <div class="flex-grow border-t border-slate-200"></div>
                    <span class="flex-shrink mx-3 text-[11px] text-slate-400 font-semibold uppercase tracking-wider">atau isi formulir manual</span>
                    <div class="flex-grow border-t border-slate-200"></div>
                </div>
            </div>

            <form action="{{ route('register.post') }}" method="POST" class="space-y-5"
                  @submit="if (accountType === 'asn_pasuruan' && nipStatus !== 'verified') { $event.preventDefault(); alert('Mohon lakukan verifikasi NIP terlebih dahulu hingga terverifikasi resmi.'); }">
                @csrf
                <input type="hidden" name="account_type" :value="accountType">

                <!-- 1. Alert Informasi Kategori Pendaftaran -->
                <div x-show="accountType === 'asn_pasuruan'" class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-900 flex items-start gap-2.5">
                    <i class="fa-solid fa-shield-halved text-emerald-600 text-sm mt-0.5 shrink-0"></i>
                    <div class="space-y-1">
                        <span class="font-bold">Khusus Aparatur Sipil Negara (ASN) Pemerintah Kabupaten Pasuruan</span>
                        <p class="text-emerald-800 text-[11px] leading-relaxed">
                            Wajib memasukkan 18 digit NIP resmi. Sistem akan memverifikasi ke Master Data Pegawai BKPSDM secara langsung. Identitas, unit kerja (OPD), dan jabatan Anda akan otomatis terisi.
                        </p>
                    </div>
                </div>

                <div x-show="accountType === 'asn_luar'" x-cloak class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 flex items-start gap-2.5">
                    <i class="fa-solid fa-building-columns text-amber-600 text-sm mt-0.5 shrink-0"></i>
                    <div class="space-y-1">
                        <span class="font-bold">ASN Kementerian, Lembaga, & Pemerintah Daerah Lain</span>
                        <p class="text-amber-800 text-[11px] leading-relaxed">
                            Untuk ASN di luar Pemkab Pasuruan yang berpartisipasi dalam program pelatihan terbuka/webinar. Pengisian identitas, NIP, dan instansi asal dilakukan secara manual.
                        </p>
                    </div>
                </div>

                <div x-show="accountType === 'umum'" x-cloak class="p-3.5 bg-sky-50 border border-sky-200 rounded-xl text-xs text-sky-900 flex items-start gap-2.5">
                    <i class="fa-solid fa-globe text-sky-600 text-sm mt-0.5 shrink-0"></i>
                    <div class="space-y-1">
                        <span class="font-bold">Peserta Umum / Non-ASN</span>
                        <p class="text-sky-800 text-[11px] leading-relaxed">
                            Dapat mengikuti agenda Webinar dan Bimbingan Teknis daring terbuka. NIP dan Pangkat bersifat opsional.
                        </p>
                    </div>
                </div>

                <!-- 2. Bagian NIP (Diletakkan di atas khusus untuk ASN Pasuruan agar flow verifikasi natural) -->
                <div x-show="accountType === 'asn_pasuruan'" class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <i class="fa-solid fa-id-card text-pasuruan-700"></i>
                                Nomor Induk Pegawai (NIP 18 Digit) <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[11px] text-slate-500 font-medium">Database Pegawai Pasuruan</span>
                        </div>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <input type="text" 
                                       name="nip" 
                                       x-model="nip"
                                       @input="if(nipStatus !== 'idle') resetNipState();"
                                       @keydown.enter.prevent="verifyNip()"
                                       placeholder="Ketik 18 digit NIP Anda..." 
                                       maxlength="30"
                                       class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-pasuruan-700 font-mono tracking-wider font-semibold">
                                <div x-show="nipStatus === 'checking'" class="absolute right-3 top-3 text-slate-400">
                                    <i class="fa-solid fa-circle-notch fa-spin text-sm"></i>
                                </div>
                            </div>
                            <button type="button" 
                                    @click="verifyNip()" 
                                    :disabled="nipStatus === 'checking' || !nip"
                                    class="px-4 py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 disabled:opacity-50 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shrink-0 shadow-xs">
                                <i class="fa-solid fa-magnifying-glass text-xs" x-show="nipStatus !== 'checking'"></i>
                                <i class="fa-solid fa-circle-notch fa-spin text-xs" x-show="nipStatus === 'checking'" x-cloak></i>
                                <span>Verifikasi NIP</span>
                            </button>
                        </div>
                    </div>

                    <!-- Notifikasi Status Verifikasi NIP -->
                    <!-- Status: Checking -->
                    <div x-show="nipStatus === 'checking'" x-cloak class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-800 flex items-center gap-2">
                        <i class="fa-solid fa-circle-notch fa-spin text-blue-600"></i>
                        <span x-text="nipMessage"></span>
                    </div>

                    <!-- Status: Verified (Valid di Database Pegawai) -->
                    <div x-show="nipStatus === 'verified'" x-cloak class="p-3.5 bg-emerald-50 border border-emerald-300 rounded-xl text-xs text-emerald-900 space-y-2 animate-fadeIn">
                        <div class="flex items-center gap-2 font-bold text-emerald-800">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                            <span>NIP Terverifikasi Resmi di Database Pegawai Pemkab Pasuruan</span>
                        </div>
                        <template x-if="verifiedPegawai">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 border-t border-emerald-200/80 text-[11px]">
                                <div>
                                    <span class="text-slate-500 block">Nama Pegawai:</span>
                                    <span class="font-bold text-slate-800" x-text="verifiedPegawai.formatted_name || verifiedPegawai.nama"></span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Perangkat Daerah (OPD):</span>
                                    <span class="font-bold text-slate-800" x-text="verifiedPegawai.unit_kerja || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Jabatan:</span>
                                    <span class="font-bold text-slate-800" x-text="verifiedPegawai.jabatan || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Pangkat / Golongan:</span>
                                    <span class="font-bold text-slate-800" x-text="verifiedPegawai.pangkat_golongan || '-'"></span>
                                </div>
                            </div>
                        </template>
                        <p class="text-[11px] text-emerald-700 italic">
                            Data profil telah disinkronkan otomatis. Silakan lengkapi password dan email Anda di bawah ini untuk menyelesaikan pendaftaran.
                        </p>
                    </div>

                    <!-- Status: Not Found (Tidak ada di data_pegawai) -->
                    <div x-show="nipStatus === 'not_found'" x-cloak class="p-3.5 bg-rose-50 border border-rose-300 rounded-xl text-xs text-rose-900 space-y-2 animate-fadeIn">
                        <div class="flex items-start gap-2.5">
                            <i class="fa-solid fa-circle-xmark text-rose-600 text-base mt-0.5 shrink-0"></i>
                            <div class="space-y-1">
                                <span class="font-bold text-rose-800 block">NIP Tidak Ditemukan - Pendaftaran Ditolak</span>
                                <p class="text-rose-700 text-[11px] leading-relaxed">
                                    NIP <strong class="font-mono text-rose-900" x-text="nip"></strong> tidak tercatat dalam Master Data Pegawai Pemerintah Kabupaten Pasuruan. Pendaftaran sebagai ASN Pasuruan <strong>tidak dapat dilanjutkan</strong>.
                                </p>
                            </div>
                        </div>
                        <div class="p-2.5 bg-white/80 rounded-lg border border-rose-200 text-[11px] text-slate-700">
                            <strong>Solusi:</strong>
                            <ul class="list-disc list-inside mt-0.5 space-y-0.5 text-slate-600">
                                <li>Periksa kembali angka NIP yang Anda masukkan agar tidak ada salah ketik.</li>
                                <li>Jika Anda merupakan ASN di luar Pemkab Pasuruan, klik tab <button type="button" @click="accountType = 'asn_luar'" class="text-amber-700 font-bold underline">ASN Luar Pasuruan</button>.</li>
                                <li>Bagi ASN Pemkab Pasuruan yang datanya belum tercatat, silakan menghubungi admin BKPSDM.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Status: Already Registered -->
                    <div x-show="nipStatus === 'already_registered'" x-cloak class="p-3.5 bg-amber-50 border border-amber-300 rounded-xl text-xs text-amber-900 space-y-2 animate-fadeIn">
                        <div class="flex items-start gap-2.5">
                            <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base mt-0.5 shrink-0"></i>
                            <div class="space-y-1">
                                <span class="font-bold text-amber-800 block">Akun Sudah Terdaftar</span>
                                <p class="text-amber-800 text-[11px] leading-relaxed" x-text="nipMessage"></p>
                            </div>
                        </div>
                        <div class="pt-1">
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg text-xs transition-all">
                                <i class="fa-solid fa-right-to-bracket"></i>
                                <span>Menuju Halaman Login</span>
                            </a>
                        </div>
                    </div>

                    <!-- Status: General Error -->
                    <div x-show="nipStatus === 'error'" x-cloak class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-800 flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                        <span x-text="nipMessage"></span>
                    </div>
                </div>

                <!-- NIP untuk ASN Luar Pasuruan (Manual) -->
                <div x-show="accountType === 'asn_luar'" x-cloak>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nomor Induk Pegawai (NIP 18 Digit) <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           name="nip" 
                           x-model="nip"
                           placeholder="Contoh: 198xxxxxxxxxxxxxxx" 
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pasuruan-700 font-mono">
                    <p class="text-[11px] text-slate-500 mt-1">NIP instansi asal di luar Pemerintah Kabupaten Pasuruan.</p>
                </div>

                <!-- NIP untuk Peserta Umum (Opsional) -->
                <div x-show="accountType === 'umum'" x-cloak>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nomor Induk Pegawai / NIP (Opsional)
                    </label>
                    <input type="text" 
                           name="nip" 
                           x-model="nip"
                           placeholder="Dikosongkan jika peserta umum non-instansi" 
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pasuruan-700 font-mono">
                </div>

                <!-- NIK (Nomor Induk Kependudukan 16 Digit) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-address-card text-pasuruan-700"></i>
                            NIK (Nomor Induk Kependudukan - 16 Digit)
                        </label>
                        <span x-show="accountType === 'asn_pasuruan' && nipStatus === 'verified' && verifiedPegawai?.nik" x-cloak class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded">
                            <i class="fa-solid fa-check text-[9px] mr-1"></i>NIK Terverifikasi
                        </span>
                    </div>
                    <input type="text" 
                           name="nik" 
                           x-model="nik"
                           maxlength="20"
                           placeholder="Contoh: 3514xxxxxxxxxxxx (16 Digit NIK KTP)" 
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pasuruan-700 font-mono tracking-wider font-semibold">
                    <p class="text-[11px] text-slate-500 mt-1">NIK diperlukan untuk rekapitulasi data diklat dan keaslian e-sertifikat.</p>
                </div>

                <!-- Nama Lengkap & Gelar -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Nama Lengkap (Wajib - Dicetak Pada Sertifikat) <span class="text-rose-500">*</span>
                        </label>
                        <span x-show="accountType === 'asn_pasuruan' && nipStatus === 'verified'" x-cloak class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded">
                            <i class="fa-solid fa-lock text-[9px] mr-1"></i>Sesuai Data Pegawai
                        </span>
                    </div>
                    <input type="text" 
                           name="name" 
                           x-model="name"
                           required 
                           :readonly="accountType === 'asn_pasuruan' && nipStatus === 'verified'"
                           placeholder="Contoh: Budi Santoso" 
                           :class="accountType === 'asn_pasuruan' && nipStatus === 'verified' ? 'bg-slate-100 text-slate-800 cursor-not-allowed' : 'bg-slate-50 text-slate-900'"
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-pasuruan-700 font-medium">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Gelar Depan (Opsional)
                        </label>
                        <input type="text" 
                               name="gelar_depan" 
                               x-model="gelar_depan"
                               :readonly="accountType === 'asn_pasuruan' && nipStatus === 'verified'"
                               placeholder="Contoh: Dr. / Ir." 
                               :class="accountType === 'asn_pasuruan' && nipStatus === 'verified' ? 'bg-slate-100 text-slate-800 cursor-not-allowed' : 'bg-slate-50 text-slate-900'"
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Gelar Belakang (Opsional)
                        </label>
                        <input type="text" 
                               name="gelar_belakang" 
                               x-model="gelar_belakang"
                               :readonly="accountType === 'asn_pasuruan' && nipStatus === 'verified'"
                               placeholder="Contoh: S.Kom, M.Si" 
                               :class="accountType === 'asn_pasuruan' && nipStatus === 'verified' ? 'bg-slate-100 text-slate-800 cursor-not-allowed' : 'bg-slate-50 text-slate-900'"
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none">
                    </div>
                </div>

                <!-- Jabatan & Unit Kerja -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Jabatan
                        </label>
                        <input type="text" 
                               name="jabatan" 
                               x-model="jabatan"
                               :readonly="accountType === 'asn_pasuruan' && nipStatus === 'verified'"
                               placeholder="Contoh: Pranata Komputer Ahli Pertama" 
                               :class="accountType === 'asn_pasuruan' && nipStatus === 'verified' ? 'bg-slate-100 text-slate-800 cursor-not-allowed' : 'bg-slate-50 text-slate-900'"
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none">
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Unit Kerja / Instansi <span class="text-rose-500">*</span>
                            </label>
                            <span x-show="accountType === 'asn_pasuruan' && nipStatus === 'verified'" x-cloak class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded">
                                <i class="fa-solid fa-lock text-[9px] mr-1"></i>OPD Resmi
                            </span>
                        </div>
                        <input type="text" 
                               name="unit_kerja" 
                               x-model="unit_kerja"
                               required 
                               :readonly="accountType === 'asn_pasuruan' && nipStatus === 'verified'"
                               placeholder="Contoh: Diskominfo Kab. Pasuruan" 
                               :class="accountType === 'asn_pasuruan' && nipStatus === 'verified' ? 'bg-slate-100 text-slate-800 cursor-not-allowed' : 'bg-slate-50 text-slate-900'"
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none font-medium">
                    </div>
                </div>

                <!-- Email & Kontak -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Alamat Email Aktif <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" 
                               name="email" 
                               x-model="email"
                               required 
                               placeholder="email@instansi.go.id" 
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pasuruan-700">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nomor WhatsApp / HP
                        </label>
                        <input type="text" 
                               name="phone_number" 
                               x-model="phone_number"
                               placeholder="08xxxxxxxxxx" 
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none">
                    </div>
                </div>

                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600 flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-info text-sky-600 text-sm mt-0.5 shrink-0"></i>
                    <div class="space-y-0.5 text-[11px] leading-relaxed">
                        <span class="font-bold text-slate-800">Akses Masuk (Login) Akun Peserta:</span>
                        <p>Setelah pendaftaran selesai, Anda dapat langsung masuk ke portal LMS menggunakan <strong>NIP</strong> atau dengan <strong>Akun Google</strong> Anda secara instan.</p>
                    </div>
                </div>

                <!-- Tombol Submit Pendaftaran -->
                <div class="pt-2">
                    <button type="submit" 
                            data-loading-text="Mendaftarkan Akun Anda..."
                            :disabled="accountType === 'asn_pasuruan' && nipStatus !== 'verified'"
                            :class="accountType === 'asn_pasuruan' && nipStatus !== 'verified' ? 'bg-slate-300 text-slate-500 cursor-not-allowed shadow-none' : 'bg-pasuruan-700 hover:bg-pasuruan-800 text-white shadow-md hover:shadow-lg'"
                            class="w-full py-3.5 px-4 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-circle-check text-sm" x-show="accountType !== 'asn_pasuruan' || nipStatus === 'verified'"></i>
                        <i class="fa-solid fa-lock text-sm" x-show="accountType === 'asn_pasuruan' && nipStatus !== 'verified'" x-cloak></i>
                        <span x-text="accountType === 'asn_pasuruan' && nipStatus !== 'verified' ? 'Verifikasi NIP Terlebih Dahulu Untuk Mendaftar' : 'Selesaikan Pendaftaran Akun'"></span>
                    </button>

                    <!-- Hint untuk ASN Pasuruan jika tombol masih terkunci -->
                    <div x-show="accountType === 'asn_pasuruan' && nipStatus !== 'verified'" x-cloak class="mt-2 text-center text-[11px] text-slate-500">
                        <i class="fa-solid fa-circle-info text-amber-500 mr-1"></i>
                        Tombol pendaftaran akan aktif setelah NIP terverifikasi dalam Master Data Pegawai Pemkab Pasuruan.
                    </div>
                </div>

                <div class="text-center pt-2 text-xs text-slate-500">
                    Sudah memiliki akun terdaftar?
                    <a href="{{ route('login') }}" class="font-bold text-pasuruan-700 hover:underline ml-1">
                        Masuk di sini
                    </a>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection
