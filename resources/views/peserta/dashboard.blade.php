@extends('layouts.dashboard')

@section('title', 'Dashboard Peserta - LMS Kabupaten Pasuruan')
@section('header_title', 'Dashboard Pembelajaran Peserta')

@section('content')
<div class="space-y-8">
    
    <!-- 1. Hero Welcome Banner Peserta -->
    <div class="bg-gradient-to-r from-pasuruan-800 via-pasuruan-700 to-sky-800 rounded-2xl p-6 sm:p-8 text-white shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="space-y-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 text-sky-200 text-xs font-semibold backdrop-blur-xs">
                <i class="fa-solid fa-id-card"></i> {{ $user->nip ? 'Aparatur Sipil Negara (ASN)' : 'Peserta Umum Diklat' }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Selamat Datang, {{ $user->profile->formatted_full_name ?? $user->name }}
            </h2>
            <p class="text-xs sm:text-sm text-slate-200">
                {{ $user->profile->jabatan ?? 'Peserta Terdaftar' }} • <strong>{{ $user->profile->unit_kerja ?? 'Pemerintah Kabupaten Pasuruan' }}</strong>
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('peserta.webinar.index') }}" class="px-5 py-2.5 bg-rose-500 hover:bg-rose-600 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-2">
                <i class="fa-solid fa-video"></i> Agenda Webinar Terbuka
            </a>
            <a href="{{ route('peserta.certificates') }}" class="px-4 py-2.5 bg-white/15 hover:bg-white/20 text-white font-semibold text-xs rounded-xl border border-white/20 transition-all">
                <i class="fa-solid fa-stamp mr-1 text-amber-300"></i> Sertifikat Saya
            </a>
        </div>
    </div>

    <!-- 2. Ringkasan Statistik Peserta -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        
        <!-- Total JP Terkumpul -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900">{{ $totalJp }} JP</div>
                <div class="text-xs text-slate-500 font-medium">Akumulasi Jam Pelajaran</div>
            </div>
        </div>

        <!-- Webinar Sedang Live -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shrink-0 relative">
                <i class="fa-solid fa-tower-broadcast"></i>
                @if($liveWebinarsCount > 0)
                    <span class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-rose-500 border-2 border-white animate-ping"></span>
                @endif
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900 flex items-center gap-2">
                    <span>{{ $liveWebinarsCount }}</span>
                    @if($liveWebinarsCount > 0)
                        <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-rose-500 text-white animate-pulse">LIVE</span>
                    @endif
                </div>
                <div class="text-xs text-slate-500 font-medium">Webinar Sedang Live</div>
            </div>
        </div>

        <!-- Webinar Terdaftar Saya -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-video"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900">{{ $registeredWebinarsCount }}</div>
                <div class="text-xs text-slate-500 font-medium">Webinar Saya Terdaftar</div>
            </div>
        </div>

        <!-- Total E-Sertifikat Dimiliki -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-certificate"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900">{{ $certificates->count() }}</div>
                <div class="text-xs text-slate-500 font-medium">E-Sertifikat Tervalidasi</div>
            </div>
        </div>

    </div>

    <!-- 3. Daftar Webinar (Sedang Live & Terdaftar) -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-2.5">
                    @if($liveWebinarsCount > 0)
                        <span class="w-3 h-3 rounded-full bg-rose-600 animate-ping"></span>
                    @else
                        <span class="w-3 h-3 rounded-full bg-pasuruan-700"></span>
                    @endif
                    <h3 class="font-extrabold text-lg text-slate-900 flex items-center gap-2">
                        <span>Webinar Saya (Sedang Live & Terdaftar)</span>
                        <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-pasuruan-50 text-pasuruan-800 border border-pasuruan-200">
                            {{ $activeWebinars->count() }}
                        </span>
                    </h3>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Menampilkan sesi siaran langsung dan seluruh agenda webinar yang telah Anda ikuti
                </p>
            </div>

            <a href="{{ route('peserta.webinar.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-xl shadow-xs hover:shadow transition-all shrink-0">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Cari & Daftar Webinar Baru &rarr;</span>
            </a>
        </div>

        @if($activeWebinars->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($activeWebinars as $webinar)
                    @php
                        $webinarStatus = $webinar->getParticipantStatus($user);
                    @endphp
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col justify-between shadow-xs hover:border-pasuruan-400 hover:shadow-md transition-all group">
                        <div>
                            <!-- Header Banner Acara -->
                            <div class="relative h-44 w-full overflow-hidden bg-slate-900">
                                @if($webinar->banner_url)
                                    <img src="{{ $webinar->banner_url }}" alt="{{ $webinar->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                @else
                                    <div class="w-full h-full bg-gradient-to-r from-pasuruan-950 to-slate-900 flex items-center justify-center text-white/40">
                                        <i class="fa-solid fa-tower-broadcast text-4xl"></i>
                                    </div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/30 to-transparent"></div>

                                <!-- Badges di atas banner -->
                                <div class="absolute top-3 left-3 right-3 flex items-start justify-between gap-2">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wider {{ $webinar->is_live ? 'bg-rose-600 text-white animate-pulse' : 'bg-slate-900/80 text-slate-200 backdrop-blur-xs' }}">
                                            {{ $webinar->is_live ? 'LIVE SEKARANG' : 'TERDAFTAR' }}
                                        </span>
                                        @if($webinar->youtube_link)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-rose-600/90 text-white flex items-center gap-1 backdrop-blur-xs">
                                                <i class="fa-brands fa-youtube"></i> YouTube
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Status Partisipasi Peserta -->
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] {{ $webinarStatus['badge_class'] }} flex items-center gap-1.5 shadow-sm">
                                        <i class="fa-solid {{ $webinarStatus['icon'] }}"></i>
                                        <span>{{ $webinarStatus['label'] }}</span>
                                    </span>
                                </div>

                                <!-- JP & Waktu di bawah banner -->
                                <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between text-white text-xs font-bold drop-shadow">
                                    <span class="flex items-center gap-1">
                                        <i class="fa-regular fa-calendar text-amber-400"></i>
                                        <span>{{ \Carbon\Carbon::parse($webinar->event_date)->isoFormat('D MMM Y') }}</span>
                                    </span>
                                    <span class="bg-amber-500/90 text-pasuruan-950 px-2 py-0.5 rounded font-extrabold text-[11px]">
                                        {{ $webinar->jp }} JP
                                    </span>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="p-5 space-y-3">
                                <a href="{{ route('peserta.webinar.room', $webinar->slug) }}" class="block">
                                    <h4 class="font-bold text-base text-slate-900 group-hover:text-pasuruan-800 transition-colors line-clamp-2 leading-snug">
                                        {{ $webinar->title }}
                                    </h4>
                                </a>
                                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                    {{ $webinar->description }}
                                </p>

                                <div class="pt-2 flex items-center justify-between text-[11px] text-slate-500 font-medium border-t border-slate-100">
                                    <span><i class="fa-regular fa-clock mr-1 text-slate-400"></i> {{ substr($webinar->start_time, 0, 5) }} - {{ substr($webinar->end_time, 0, 5) }} WIB</span>
                                    <span class="line-clamp-1">Oleh: {{ $webinar->organizer->name }}</span>
                                </div>
                                <div class="flex items-center justify-between text-[11px] font-semibold text-emerald-800 bg-emerald-50/80 px-2.5 py-1.5 rounded-lg border border-emerald-200">
                                    <span><i class="fa-solid fa-graduation-cap text-emerald-600 mr-1"></i> Passing Grade: &ge; {{ $webinar->passing_grade }}</span>
                                    <span class="text-amber-800 font-bold text-[10px]">Plafon Remedial: {{ $webinar->remedial_cap_score ?? 90 }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div class="p-5 pt-0">
                            @if($webinarStatus['status'] === 'certified')
                                <div class="grid grid-cols-2 gap-2">
                                    <a href="{{ route('peserta.webinar.room', $webinar->slug) }}" class="py-2.5 text-center font-bold text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-colors flex items-center justify-center gap-1">
                                        <span>Masuk Sesi</span>
                                    </a>
                                    <a href="{{ route('validation.show', $webinarStatus['certificate']->hash_token) }}" target="_blank" class="py-2.5 text-center font-bold text-xs bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-xs transition-colors flex items-center justify-center gap-1">
                                        <i class="fa-solid fa-certificate"></i>
                                        <span>E-Sertifikat</span>
                                    </a>
                                </div>
                            @elseif($webinarStatus['status'] === 'unregistered')
                                <form action="{{ route('peserta.webinar.register', $webinar->id) }}" method="POST" class="w-full">
                                    @csrf
                                    <button type="submit" class="block w-full py-2.5 text-center font-bold text-xs bg-pasuruan-700 hover:bg-pasuruan-800 text-white rounded-xl shadow-xs transition-colors flex items-center justify-center gap-1.5 cursor-pointer">
                                        <i class="fa-solid fa-user-plus"></i>
                                        <span>Daftar & Masuk Sesi</span>
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('peserta.webinar.room', $webinar->slug) }}" class="block w-full py-2.5 text-center font-bold text-xs {{ $webinar->is_live ? 'bg-rose-600 hover:bg-rose-700 animate-pulse' : 'bg-pasuruan-700 hover:bg-pasuruan-800' }} text-white rounded-xl shadow-xs transition-colors flex items-center justify-center gap-1.5">
                                    <i class="fa-solid {{ $webinar->is_live ? 'fa-tower-broadcast' : 'fa-door-open' }}"></i>
                                    <span>Masuk Sesi Webinar</span>
                                    <span>&rarr;</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State jika belum ada webinar live atau terdaftar -->
            <div class="bg-white p-12 rounded-2xl border border-dashed border-slate-300 text-center space-y-4">
                <div class="w-16 h-16 rounded-2xl bg-slate-50 text-slate-400 flex items-center justify-center mx-auto text-2xl">
                    <i class="fa-solid fa-video-slash"></i>
                </div>
                <div class="space-y-1 max-w-md mx-auto">
                    <h4 class="font-bold text-base text-slate-900">Belum Ada Webinar yang Diikuti atau Sedang Live</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Saat ini tidak ada sesi siaran langsung yang sedang berjalan dan Anda belum terdaftar pada agenda webinar manapun. Silakan lihat agenda webinar terbuka dan daftarkan diri Anda.
                    </p>
                </div>
                <a href="{{ route('peserta.webinar.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-xl shadow-xs hover:shadow transition-all">
                    <i class="fa-solid fa-compass"></i>
                    <span>Jelajahi Agenda Webinar Terbuka</span>
                </a>
            </div>
        @endif
    </div>

    <!-- 4. E-Sertifikat Terbaru Peserta -->
    @if($recentCertificates->isNotEmpty())
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-base text-slate-900">E-Sertifikat Terverifikasi Terakhir</h3>
                <p class="text-xs text-slate-500">Diterbitkan langsung dengan kode QR dan terdaftar publik</p>
            </div>
            <a href="{{ route('peserta.certificates') }}" class="text-xs font-bold text-pasuruan-700 hover:underline">
                Lihat Semua Sertifikat &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($recentCertificates as $cert)
                <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/40 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-amber-500 text-white flex items-center justify-center text-xl shrink-0 shadow-sm">
                            <i class="fa-solid fa-stamp"></i>
                        </div>
                        <div>
                            <div class="text-xs font-mono font-bold text-pasuruan-800">{{ $cert->certificate_number }}</div>
                            <div class="text-xs font-bold text-slate-900 line-clamp-1 mt-0.5">{{ $cert->training_title }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                {{ $cert->jp }} JP • Terbit {{ \Carbon\Carbon::parse($cert->issued_at)->isoFormat('D MMMM Y') }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('validation.show', $cert->hash_token) }}" target="_blank" class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-lg border border-slate-300 shadow-xs flex items-center gap-1.5">
                            <i class="fa-solid fa-qrcode text-emerald-600"></i> Validasi
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
