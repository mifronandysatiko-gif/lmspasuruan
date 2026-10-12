@extends('layouts.app')

@section('title', $appName . ' - BKPSDM Kabupaten Pasuruan')

@section('content')

<!-- 1. Hero Section Profesional Pemkab Pasuruan -->
<section class="relative bg-gradient-to-b from-pasuruan-900 via-pasuruan-800 to-pasuruan-700 text-white overflow-hidden py-16 lg:py-24">
    <!-- Decorative background elements -->
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:16px_16px]"></div>
    <div class="absolute -bottom-10 -right-10 w-96 h-96 rounded-full bg-sky-500/10 blur-3xl pointer-events-none"></div>
    <div class="absolute -top-10 -left-10 w-96 h-96 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <div class="lg:col-span-7 space-y-6 text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/15 text-sky-200 text-xs font-semibold backdrop-blur-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span>Portal Resmi Pengembangan Kompetensi Terpadu BKPSDM</span>
                </div>

                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight text-white">
                    Pusat Pembelajaran Digital & Webinar Terbuka <br class="hidden sm:block">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-300 via-emerald-300 to-amber-300">
                        Kabupaten Pasuruan
                    </span>
                </h1>

                <p class="text-base sm:text-lg text-slate-200 leading-relaxed font-normal max-w-2xl">
                    {{ $appTagline }}. Memfasilitasi diklat teknis fungsional, MOOC mandiri tanpa jeda, serta seminar daring interaktif dengan verifikasi e-sertifikat instan dan akuntabel.
                </p>

                <!-- Quick Action Buttons -->
                <div class="flex flex-wrap items-center gap-4 pt-2">
                    <a href="{{ route('peserta.katalog') }}" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl font-bold text-pasuruan-900 bg-amber-400 hover:bg-amber-300 shadow-lg shadow-amber-400/20 transition-all transform hover:-translate-y-0.5">
                        <i class="fa-solid fa-graduation-cap text-base"></i>
                        <span>Katalog MOOC & Diklat</span>
                    </a>
                    <a href="#webinar" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl font-bold text-white bg-white/15 hover:bg-white/20 border border-white/25 backdrop-blur-xs transition-all">
                        <i class="fa-solid fa-video text-rose-400"></i>
                        <span>Jadwal Webinar Terbuka</span>
                    </a>
                    <a href="{{ route('validation.show') }}" class="inline-flex items-center gap-2 px-5 py-3.5 rounded-xl font-semibold text-emerald-200 hover:text-white hover:bg-emerald-900/40 border border-emerald-500/30 transition-all">
                        <i class="fa-solid fa-qrcode text-emerald-400"></i>
                        <span>Validasi Sertifikat</span>
                    </a>
                </div>
            </div>

            <!-- Visual Graphic / Interactive Demo Card -->
            <div class="lg:col-span-5">
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-white/15">
                        <div class="flex items-center gap-2.5">
                            <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                            <span class="text-xs text-slate-300 font-mono ml-2">Alur Gerbang Berurutan</span>
                        </div>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="p-2.5 bg-white/10 rounded-lg flex items-center justify-between border border-white/10">
                            <span class="flex items-center gap-2 font-medium">
                                <i class="fa-solid fa-user-check text-emerald-400"></i> 1. Pendaftaran Webinar
                            </span>
                            <span class="text-emerald-300 font-bold">Terbuka</span>
                        </div>

                        <div class="p-2.5 bg-white/10 rounded-lg flex items-center justify-between border border-white/10">
                            <span class="flex items-center gap-2 font-medium">
                                <i class="fa-solid fa-clipboard-user text-sky-400"></i> 2. Presensi Siaran Langsung
                            </span>
                            <span class="text-sky-300 font-bold">Aktif saat Live</span>
                        </div>

                        <div class="p-2.5 bg-white/10 rounded-lg flex items-center justify-between border border-white/10">
                            <span class="flex items-center gap-2 font-medium">
                                <i class="fa-solid fa-square-poll-vertical text-purple-300"></i> 3. Kuesioner Evaluasi
                            </span>
                            <span class="text-slate-300">Jendela Jam</span>
                        </div>

                        <div class="p-2.5 bg-white/10 rounded-lg flex items-center justify-between border border-white/10">
                            <span class="flex items-center gap-2 font-medium">
                                <i class="fa-solid fa-file-pen text-amber-300"></i> 4. Post-Test & Remedial
                            </span>
                            <span class="text-amber-300">Passing Grade</span>
                        </div>

                        <div class="p-2.5 bg-white/15 rounded-lg flex items-center justify-between border border-emerald-400/40">
                            <span class="flex items-center gap-2 font-bold text-white">
                                <i class="fa-solid fa-stamp text-amber-400"></i> 5. E-Sertifikat Otomatis
                            </span>
                            <span class="bg-amber-400 text-pasuruan-900 px-2 py-0.5 rounded font-extrabold text-[10px]">Instant QR</span>
                        </div>
                    </div>

                    <div class="pt-2 text-center">
                        <a href="{{ route('peserta.katalog') }}" class="block w-full py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl text-xs transition-colors shadow-sm">
                            Akses Katalog Pembelajaran &rarr;
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- 2. Statistik Capaian Pelatihan -->
<section class="bg-white border-b border-slate-200 py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <div class="text-3xl font-extrabold text-pasuruan-800">{{ number_format($stats['total_asn'], 0, ',', '.') }}</div>
                <div class="text-xs text-slate-500 font-semibold mt-1 uppercase tracking-wider">Aparatur ASN Terdaftar</div>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <div class="text-3xl font-extrabold text-emerald-600">{{ $stats['total_webinar'] }} Sesi</div>
                <div class="text-xs text-slate-500 font-semibold mt-1 uppercase tracking-wider">Webinar & Bimtek Terbuka</div>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <div class="text-3xl font-extrabold text-sky-600">{{ $stats['total_courses'] }} Kursus</div>
                <div class="text-xs text-slate-500 font-semibold mt-1 uppercase tracking-wider">Modul MOOC Mandiri</div>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <div class="text-3xl font-extrabold text-amber-600">{{ number_format($stats['total_certificates'], 0, ',', '.') }}</div>
                <div class="text-xs text-slate-500 font-semibold mt-1 uppercase tracking-wider">E-Sertifikat Terbit Resmi</div>
            </div>

        </div>
    </div>
</section>

<!-- 3. Section Webinar & Bimtek Terbuka (Fokus Sequential Gating) -->
<section id="webinar" class="py-16 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10">
            <div>
                <span class="text-xs font-bold text-pasuruan-700 uppercase tracking-widest">Event Terbuka ASN & Publik</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Webinar & Bimbingan Teknis Terkini</h2>
                <p class="text-slate-600 text-sm mt-1">Daftar sekarang, ikuti sesi interaktif, dan raih sertifikat ber-barcode resmi.</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="{{ route('peserta.katalog') }}" class="inline-flex items-center gap-2 text-sm font-bold text-pasuruan-700 hover:text-pasuruan-900">
                    Lihat Semua Agenda <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($webinars as $webinar)
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-md transition-all flex flex-col group">
                    <!-- Top Card Badge / Banner -->
                    <div class="relative h-44 bg-gradient-to-br from-pasuruan-800 to-slate-900 p-6 flex flex-col justify-between text-white overflow-hidden">
                        @if($webinar->banner_url)
                            <img src="{{ $webinar->banner_url }}" alt="{{ $webinar->title }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/60 to-slate-900/40"></div>
                        @endif
                        <div class="relative z-10 flex items-center justify-between">
                            <span class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider {{ $webinar->is_live ? 'bg-rose-500 text-white animate-pulse' : 'bg-white/20 text-sky-200 backdrop-blur-xs' }}">
                                {{ $webinar->is_live ? '● Sedang Live' : 'Mendatang' }}
                            </span>
                            <span class="bg-amber-400 text-pasuruan-900 text-xs font-extrabold px-2.5 py-1 rounded-md">
                                {{ $webinar->jp }} JP ({{ $webinar->jp * 45 }} Mnt)
                            </span>
                        </div>
                        <div class="relative z-10">
                            <div class="text-xs text-sky-300 font-medium">
                                <i class="fa-solid fa-calendar-day mr-1"></i> {{ \Carbon\Carbon::parse($webinar->event_date)->isoFormat('dddd, D MMMM Y') }}
                            </div>
                            <div class="text-xs text-slate-300 mt-0.5">
                                <i class="fa-solid fa-clock mr-1"></i> {{ substr($webinar->start_time, 0, 5) }} - {{ substr($webinar->end_time, 0, 5) }} WIB
                            </div>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                        <div>
                            <h3 class="font-bold text-base text-slate-900 group-hover:text-pasuruan-700 line-clamp-2 transition-colors">
                                {{ $webinar->title }}
                            </h3>
                            <p class="text-xs text-slate-500 line-clamp-2 mt-2 leading-relaxed">
                                {{ $webinar->description }}
                            </p>
                        </div>

                        <div class="pt-4 border-t border-slate-100 space-y-3">
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span>Penyelenggara:</span>
                                <span class="font-semibold text-slate-700">{{ $webinar->organizer->profile->unit_kerja ?? 'BKPSDM Pasuruan' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span>Syarat Kelulusan:</span>
                                <span class="font-semibold text-emerald-600">Passing Grade &ge; {{ $webinar->passing_grade }} (Plafon: {{ $webinar->remedial_cap_score ?? 90 }})</span>
                            </div>

                            <a href="{{ route('peserta.webinar.room', $webinar->slug) }}" class="block w-full py-2.5 px-4 text-center rounded-xl font-bold text-xs {{ $webinar->is_live ? 'bg-pasuruan-700 hover:bg-pasuruan-800 text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }} transition-colors">
                                Masuk Ruang Webinar (Sequential Gate) &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- 4. Section Katalog MOOC & Pelatihan Mandiri -->
<section class="py-16 bg-white border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-xs font-bold text-emerald-600 uppercase tracking-widest">Self-Paced Learning</span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Pelatihan Mandiri MOOC & Kepemimpinan</h2>
            <p class="text-slate-600 text-sm mt-2">Dapat diakses kapan pun dan di mana pun oleh seluruh aparatur Pemkab Pasuruan tanpa kendala jadwal.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($courses as $course)
                <div class="rounded-2xl border border-slate-200 bg-white shadow-xs hover:border-pasuruan-500 hover:shadow-md transition-all flex flex-col justify-between overflow-hidden group">
                    <div>
                        @if($course->banner_url)
                            <div class="relative h-44 w-full overflow-hidden bg-slate-900">
                                <img src="{{ $course->banner_url }}" alt="{{ $course->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-900/40 to-transparent"></div>
                                <div class="absolute top-3 left-3 right-3 flex items-center justify-between">
                                    <span class="text-[11px] font-bold px-2.5 py-1 rounded-md bg-white/95 text-sky-900 shadow-xs">
                                        {{ $course->category }}
                                    </span>
                                    <span class="text-xs font-bold text-amber-300 px-2.5 py-1 rounded-md bg-black/60 backdrop-blur-xs">
                                        <i class="fa-solid fa-hourglass-half mr-1"></i> {{ $course->jp }} JP
                                    </span>
                                </div>
                            </div>
                        @endif

                        <div class="p-6">
                            @if(!$course->banner_url)
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-[11px] font-bold px-2.5 py-1 rounded-md bg-sky-50 text-sky-700 border border-sky-200">
                                        {{ $course->category }}
                                    </span>
                                    <span class="text-xs font-bold text-amber-600">
                                        <i class="fa-solid fa-hourglass-half mr-1"></i> {{ $course->jp }} JP
                                    </span>
                                </div>
                            @endif
                            <h3 class="font-bold text-base text-slate-900 group-hover:text-pasuruan-700 line-clamp-2 transition-colors">
                                {{ $course->title }}
                            </h3>
                            <p class="text-xs text-slate-500 line-clamp-3 mt-2 leading-relaxed">
                                {{ $course->description }}
                            </p>
                        </div>
                    </div>

                    <div class="px-6 pb-6 pt-0 space-y-2.5">
                        <div class="flex items-center justify-between text-xs text-slate-500 pt-3 border-t border-slate-100">
                            <span>Passing Grade: <strong class="text-emerald-600">&ge; {{ $course->passing_grade }}</strong></span>
                            <span class="text-amber-700 font-semibold text-[11px]">Plafon: {{ $course->remedial_cap_score ?? 90 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                                <i class="fa-solid fa-shield-halved text-emerald-500"></i> Video Anti-Skip
                            </div>
                            <a href="{{ route('peserta.mooc.show', $course->slug) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-pasuruan-50 hover:bg-pasuruan-100 text-pasuruan-700 font-bold text-xs transition-colors">
                                Mulai Belajar <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- 5. Section Quick Validation Search & Callout -->
<section class="py-14 bg-gradient-to-r from-pasuruan-800 to-pasuruan-900 text-white">
    <div class="max-w-4xl mx-auto px-4 text-center space-y-6">
        <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center mx-auto text-amber-400 text-2xl border border-white/15 shadow-inner">
            <i class="fa-solid fa-shield-check"></i>
        </div>
        <h2 class="text-2xl sm:text-3xl font-extrabold">Verifikasi Keaslian E-Sertifikat Mandiri</h2>
        <p class="text-sm text-slate-300 max-w-xl mx-auto">
            Setiap dokumen sertifikat pelatihan BKPSDM Kabupaten Pasuruan dilengkapi kode hash unik dan barcode yang dapat diaudit langsung tanpa memerlukan login.
        </p>

        <form action="{{ route('validation.show') }}" method="GET" class="max-w-md mx-auto flex items-center gap-2 bg-white p-1.5 rounded-2xl shadow-xl">
            <input type="text" name="token" placeholder="Masukkan Kode Hash / Nomor Sertifikat..." class="flex-1 px-4 py-2.5 text-xs text-slate-800 focus:outline-none font-mono" value="PAS-2026-CERT-001" required>
            <button type="submit" class="px-5 py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold rounded-xl text-xs transition-colors shrink-0">
                <i class="fa-solid fa-magnifying-glass mr-1.5"></i> Periksa
            </button>
        </form>
    </div>
</section>

@endsection
