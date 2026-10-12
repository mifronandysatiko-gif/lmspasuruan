@extends('layouts.dashboard')

@section('title', 'Dashboard Pengelola - LMS Kabupaten Pasuruan')
@section('header_title', 'Dashboard Pengelola & Administrator')

@section('content')
<div class="space-y-8">
    
    <!-- Top Welcome Banner Admin -->
    <div class="bg-gradient-to-r from-pasuruan-900 via-pasuruan-800 to-sky-900 rounded-2xl p-6 sm:p-8 text-white shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="space-y-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 text-amber-300 text-xs font-semibold backdrop-blur-xs">
                <i class="fa-solid fa-user-shield"></i> {{ strtoupper(str_replace('_', ' ', $user->role)) }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Panel Kontrol Eksekutif LMS Pasuruan
            </h2>
            <p class="text-xs sm:text-sm text-slate-200">
                Pemantauan real-time aktivitas pembelajaran, sesi webinar interaktif, serta validasi e-sertifikat kedinasan.
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('admin.settings') }}" class="px-4 py-2.5 bg-amber-400 hover:bg-amber-300 text-pasuruan-900 font-bold text-xs rounded-xl shadow-xs transition-all flex items-center gap-2">
                <i class="fa-solid fa-sliders"></i> Konfigurasi Sistem
            </a>
            <a href="{{ route('admin.webinars.index') }}" class="px-4 py-2.5 bg-white/15 hover:bg-white/20 text-white font-semibold text-xs rounded-xl border border-white/20 transition-all flex items-center gap-2">
                <i class="fa-solid fa-video text-rose-400"></i> Kelola Webinar
            </a>
        </div>
    </div>

    <!-- Statistik Metrik Sistem (PRD Section 1 & 2) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900">{{ $totalUsers }}</div>
                <div class="text-xs text-slate-500 font-medium">Total Pengguna Terdaftar</div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-id-badge"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900">{{ $totalAsn }}</div>
                <div class="text-xs text-slate-500 font-medium">Aparatur Sipil Negara (ASN)</div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-video"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900">{{ $totalWebinars }}</div>
                <div class="text-xs text-slate-500 font-medium">Event Webinar / Bimtek</div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-certificate"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900">{{ $totalCertificates }}</div>
                <div class="text-xs text-slate-500 font-medium">E-Sertifikat Diterbitkan</div>
            </div>
        </div>
    </div>

    <!-- Pusat Tarikan Data & Rekapitulasi Excel (Super Admin & Penyelenggara) -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-base text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-file-excel text-emerald-600"></i>
                    <span>Pusat Tarikan Data & Rekapitulasi Kedinasan (Excel / .xlsx)</span>
                </h3>
                <p class="text-xs text-slate-500">Ekspor laporan lengkap seluruh kegiatan beserta identitas lengkap ASN (NIP, NIK, Jabatan, Unit Kerja, Nilai & Sertifikat)</p>
            </div>
            <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 text-[11px] font-bold border border-emerald-200 shrink-0">
                <i class="fa-solid fa-bolt text-amber-500 mr-1"></i> Format Siap Cetak & Rekap
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Card 1: Tarikan Data Webinar -->
            <div class="p-4 rounded-xl bg-gradient-to-br from-emerald-50/70 to-teal-50/40 border border-emerald-200/80 flex flex-col justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold uppercase text-emerald-800 tracking-wider">Webinar & Bimtek</span>
                        <i class="fa-solid fa-video text-emerald-600"></i>
                    </div>
                    <h4 class="font-bold text-xs text-slate-900">Rekap Seluruh Agenda Webinar</h4>
                    <p class="text-[11px] text-slate-500">Daftar agenda webinar, jadwal, JP, tautan streaming, dan jumlah kehadiran.</p>
                </div>
                <a href="{{ route('admin.webinars.export') }}" class="w-full py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-2xs transition-colors flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-download"></i>
                    <span>Unduh Excel Webinar</span>
                </a>
            </div>

            <!-- Card 2: Tarikan Data Pelatihan MOOC -->
            <div class="p-4 rounded-xl bg-gradient-to-br from-sky-50/70 to-blue-50/40 border border-sky-200/80 flex flex-col justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold uppercase text-sky-800 tracking-wider">Pelatihan & Modul MOOC</span>
                        <i class="fa-solid fa-graduation-cap text-sky-600"></i>
                    </div>
                    <h4 class="font-bold text-xs text-slate-900">Rekap Seluruh Kursus & Diklat</h4>
                    <p class="text-[11px] text-slate-500">Daftar program pelatihan, tipe diklat, passing grade, JP, dan total pendaftar ASN.</p>
                </div>
                <a href="{{ route('admin.courses.export') }}" class="w-full py-2 px-3 bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs rounded-xl shadow-2xs transition-colors flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-download"></i>
                    <span>Unduh Excel Pelatihan</span>
                </a>
            </div>

            <!-- Card 3: Tarikan Data E-Sertifikat -->
            <div class="p-4 rounded-xl bg-gradient-to-br from-amber-50/70 to-yellow-50/40 border border-amber-200/80 flex flex-col justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold uppercase text-amber-800 tracking-wider">E-Sertifikat Resmi</span>
                        <i class="fa-solid fa-award text-amber-600"></i>
                    </div>
                    <h4 class="font-bold text-xs text-slate-900">Rekap Seluruh Sertifikat Terbit</h4>
                    <p class="text-[11px] text-slate-500">Daftar nomor surat, identitas lengkap penerima, JP, nama kegiatan, dan link validasi online.</p>
                </div>
                <a href="{{ route('admin.certificates.export') }}" class="w-full py-2 px-3 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow-2xs transition-colors flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-download"></i>
                    <span>Unduh Excel E-Sertifikat</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Content Row: Active Webinars & Audit Trail -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Sesi Webinar & Kontrol Penyelenggara -->
        <div class="lg:col-span-7 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-bold text-base text-slate-900">Status Sesi Webinar Terkini</h3>
                    <p class="text-xs text-slate-500">Pemicu siaran langsung dan pemantauan peserta</p>
                </div>
                <a href="{{ route('admin.webinars.index') }}" class="text-xs font-bold text-pasuruan-700 hover:underline">
                    Lihat Semua &rarr;
                </a>
            </div>

            <div class="space-y-3">
                @foreach($webinars as $wb)
                    <div class="p-4 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $wb->is_live ? 'bg-rose-500 text-white animate-pulse' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $wb->is_live ? 'LIVE SEKARANG' : 'OFFLINE' }}
                                </span>
                                <span class="text-xs font-bold text-amber-600">{{ $wb->jp }} JP</span>
                            </div>
                            <h4 class="font-bold text-xs text-slate-900">{{ $wb->title }}</h4>
                            <p class="text-[11px] text-slate-400">
                                Total Presensi: <strong>{{ $wb->attendances_count }} Peserta Hadir</strong>
                            </p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('admin.webinars.manage', $wb->id) }}" class="px-3.5 py-1.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-lg transition-colors">
                                Kontrol Sesi &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Audit Trail Log -->
        <div class="lg:col-span-5 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-bold text-base text-slate-900">Audit Trail Sistem (Log Aktivitas)</h3>
                    <p class="text-xs text-slate-500">Pencatatan akuntabilitas tindakan admin & sistem</p>
                </div>
            </div>

            <div class="space-y-3">
                @foreach($recentAudits as $audit)
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-bold text-pasuruan-800 text-[11px]">{{ $audit->action }}</span>
                            <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($audit->created_at)->diffForHumans() }}</span>
                        </div>
                        <div class="text-[11px] text-slate-600">
                            Oleh: <strong>{{ $audit->user->name ?? 'System Worker' }}</strong> • IP: {{ $audit->ip_address }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

</div>
@endsection
