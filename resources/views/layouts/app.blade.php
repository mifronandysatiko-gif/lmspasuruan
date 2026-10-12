<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'LMS Kabupaten Pasuruan - BKPSDM')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-pasuruan.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo-pasuruan.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS CDN with custom branding config -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        pasuruan: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            500: '#1d6fa5',
                            700: '#0f3d62',
                            800: '#0b2e4b',
                            900: '#071f33',
                        },
                        berakhlak: {
                            emerald: '#059669',
                            amber: '#d97706',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .marquee-container {
            overflow: hidden;
            white-space: nowrap;
        }
        .marquee-content {
            display: inline-block;
            animation: marquee 30s linear infinite;
        }
        @keyframes marquee {
            0%   { transform: translate(0, 0); }
            100% { transform: translate(-100%, 0); }
        }

        /* Universal Button Loading & Processing States */
        .btn-spinner {
            display: inline-block;
            width: 1em;
            height: 1em;
            vertical-align: -0.15em;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: btn-spin 0.65s linear infinite;
        }
        @keyframes btn-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .is-loading,
        [aria-busy="true"] {
            position: relative !important;
            pointer-events: none !important;
            cursor: wait !important;
            opacity: 0.82 !important;
            user-select: none !important;
        }
        .is-loading:not(.no-pulse) {
            box-shadow: 0 0 0 3px rgba(15, 61, 98, 0.22) !important;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased min-h-screen flex flex-col selection:bg-pasuruan-700 selection:text-white">

    <!-- 1. Running Announcement Marquee (Section 3.1 PRD) -->
    @php
        $marquee = \App\Models\SystemSetting::get('marquee_announcement', 'PENGUMUMAN RESMI BKPSDM: Bimbingan Teknis SPBE dan Pelatihan Kepemimpinan Pasuruan dibuka hingga 30 September 2026.');
    @endphp
    <div class="bg-pasuruan-900 text-slate-200 text-xs py-2 px-4 border-b border-pasuruan-800 flex items-center justify-between z-50">
        <div class="max-w-7xl mx-auto w-full flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 bg-amber-500/20 text-amber-300 font-semibold px-2 py-0.5 rounded text-[11px] uppercase tracking-wider shrink-0 border border-amber-500/30">
                <i class="fa-solid fa-bullhorn animate-pulse"></i> Pengumuman
            </span>
            <div class="overflow-hidden relative w-full text-xs text-slate-300 font-medium">
                <div class="marquee-content flex gap-8">
                    <span>{{ $marquee }}</span>
                    <span class="text-amber-400">★</span>
                    <span>Layanan Bantuan Teknis BKPSDM: 0812-3456-7890 (WhatsApp)</span>
                    <span class="text-amber-400">★</span>
                    <span>Verifikasi E-Sertifikat resmi dapat diakses publik tanpa login.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Header & Main Navbar Resmi Pemkab Pasuruan -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-sm" x-data="{ mobileMenu: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                
                <!-- Logo & Identitas Daerah -->
                <a href="{{ route('home') }}" class="flex items-center gap-3.5 group">
                    <picture>
                        <source srcset="{{ asset('images/logo-pasuruan.webp') }}" type="image/webp">
                        <img src="{{ asset('images/logo-pasuruan.png') }}" alt="Logo Kabupaten Pasuruan" class="h-12 w-auto object-contain group-hover:scale-105 transition-transform duration-200 drop-shadow-xs">
                    </picture>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-xl text-pasuruan-800 tracking-tight">LMS PASURUAN</span>
                            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-1.5 py-0.5 rounded border border-emerald-300">BKPSDM</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium tracking-normal">Badan Kepegawaian & Pengembangan SDM Kab. Pasuruan</p>
                    </div>
                </a>

                <!-- Navigasi Desktop -->
                <nav class="hidden md:flex items-center gap-1">
                    <a href="{{ route('home') }}" class="px-3.5 py-2 text-sm font-semibold text-slate-700 hover:text-pasuruan-700 hover:bg-slate-100 rounded-lg transition-colors">
                        Beranda
                    </a>
                    <a href="{{ route('peserta.katalog') }}" class="px-3.5 py-2 text-sm font-semibold text-slate-700 hover:text-pasuruan-700 hover:bg-slate-100 rounded-lg transition-colors">
                        Pelatihan
                    </a>
                    <a href="{{ route('home') }}#webinar" class="px-3.5 py-2 text-sm font-semibold text-slate-700 hover:text-pasuruan-700 hover:bg-slate-100 rounded-lg transition-colors">
                        Webinar
                    </a>
                    <a href="{{ route('validation.show') }}" class="px-3.5 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-qrcode text-emerald-600"></i> Validasi Sertifikat
                    </a>
                </nav>

                <!-- Tombol Masuk / Dashboard -->
                <div class="hidden md:flex items-center gap-3">
                    @auth
                        <div class="flex items-center gap-3">
                            <a href="{{ Auth::user()->role === 'peserta' ? route('peserta.dashboard') : route('admin.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-white bg-pasuruan-700 hover:bg-pasuruan-800 rounded-lg shadow-sm transition-all">
                                <i class="fa-solid fa-gauge-high"></i> Dashboard ({{ ucfirst(Auth::user()->role) }})
                            </a>
                            <a href="{{ route('logout') }}" class="px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Keluar">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            </a>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-semibold text-pasuruan-700 hover:text-pasuruan-900 hover:bg-slate-100 rounded-lg transition-colors">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold text-white bg-pasuruan-700 hover:bg-pasuruan-800 rounded-lg shadow-sm hover:shadow transition-all">
                            <i class="fa-solid fa-user-plus text-amber-300"></i> Daftar Akun
                        </a>
                    @endauth
                </div>

                <!-- Hamburger Mobile -->
                <button @click="mobileMenu = !mobileMenu" type="button" class="md:hidden p-2 text-slate-600 hover:text-slate-900 rounded-lg">
                    <i class="fa-solid fa-bars text-xl" x-show="!mobileMenu"></i>
                    <i class="fa-solid fa-xmark text-xl" x-show="mobileMenu" x-cloak></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu Drawer -->
        <div x-show="mobileMenu" x-cloak class="md:hidden border-t border-slate-200 bg-white px-4 pt-3 pb-6 space-y-2">
            <a href="{{ route('home') }}" class="block px-3 py-2 text-base font-semibold text-slate-800 hover:bg-slate-50 rounded-md">Beranda</a>
            <a href="{{ route('peserta.katalog') }}" class="block px-3 py-2 text-base font-semibold text-slate-800 hover:bg-slate-50 rounded-md">Pelatihan</a>
            <a href="{{ route('home') }}#webinar" class="block px-3 py-2 text-base font-semibold text-slate-800 hover:bg-slate-50 rounded-md">Webinar & Bimtek</a>
            <a href="{{ route('validation.show') }}" class="block px-3 py-2 text-base font-semibold text-emerald-700 hover:bg-emerald-50 rounded-md">Validasi Sertifikat</a>
            <div class="pt-3 border-t border-slate-100 flex flex-col gap-2">
                @auth
                    <a href="{{ Auth::user()->role === 'peserta' ? route('peserta.dashboard') : route('admin.dashboard') }}" class="w-full text-center py-2.5 bg-pasuruan-700 text-white font-bold rounded-lg">
                        Buka Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="w-full text-center py-2 text-slate-700 font-semibold border border-slate-200 rounded-lg">Masuk</a>
                    <a href="{{ route('register') }}" class="w-full text-center py-2 bg-pasuruan-700 text-white font-bold rounded-lg">Daftar Akun Baru</a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-lg shadow-sm flex items-start gap-3">
                <i class="fa-solid fa-circle-check text-emerald-500 text-lg mt-0.5"></i>
                <div class="text-sm text-emerald-900 font-medium">
                    {{ session('success') }}
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-lg shadow-sm flex items-start gap-3">
                <i class="fa-solid fa-circle-exclamation text-rose-500 text-lg mt-0.5"></i>
                <div class="text-sm text-rose-900 font-medium">
                    {{ session('error') }}
                </div>
            </div>
        </div>
    @endif

    @if(session('warning'))
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-lg shadow-sm flex items-start gap-3">
                <i class="fa-solid fa-triangle-exclamation text-amber-500 text-lg mt-0.5"></i>
                <div class="text-sm text-amber-900 font-medium">
                    {{ session('warning') }}
                </div>
            </div>
        </div>
    @endif

    <!-- Content Slot -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- 3. Footer Resmi BKPSDM Pemkab Pasuruan (Section 3.1 & 3.2 PRD) -->
    <footer class="bg-slate-900 text-slate-300 border-t border-slate-800 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                
                <!-- Kolom 1: Profil LMS & Logo -->
                <div class="md:col-span-2 space-y-4">
                    <div class="flex items-center gap-3">
                        <picture>
                            <source srcset="{{ asset('images/logo-pasuruan.webp') }}" type="image/webp">
                            <img src="{{ asset('images/logo-pasuruan.png') }}" alt="Logo Kabupaten Pasuruan" class="h-11 w-auto object-contain drop-shadow-sm">
                        </picture>
                        <div>
                            <span class="font-bold text-lg text-white">LMS KABUPATEN PASURUAN</span>
                            <p class="text-xs text-slate-400">Platform Pembelajaran Terpadu ASN & Diklat Daring</p>
                        </div>
                    </div>
                    <p class="text-sm text-slate-400 leading-relaxed max-w-lg">
                        Mendukung peningkatan indeks profesionalitas ASN di lingkungan Pemerintah Kabupaten Pasuruan melalui pembelajaran mandiri MOOC, Bimtek daring terbuka, dan Pelatihan Kepemimpinan terstruktur.
                    </p>
                    <div class="flex items-center gap-3 pt-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-800 text-emerald-400 text-xs font-semibold rounded-full border border-slate-700">
                            <i class="fa-solid fa-shield-halved"></i> Terverifikasi BerAKHLAK
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-800 text-sky-400 text-xs font-semibold rounded-full border border-slate-700">
                            <i class="fa-solid fa-certificate"></i> E-Sertifikat Mandiri
                        </span>
                    </div>
                </div>

                <!-- Kolom 2: Navigasi Cepat -->
                <div>
                    <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-4 border-b border-slate-800 pb-2">Tautan Penting</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ route('peserta.katalog') }}" class="hover:text-amber-400 transition-colors"><i class="fa-solid fa-angle-right text-xs mr-2 text-slate-500"></i>Katalog MOOC</a></li>
                        <li><a href="{{ route('home') }}#webinar" class="hover:text-amber-400 transition-colors"><i class="fa-solid fa-angle-right text-xs mr-2 text-slate-500"></i>Jadwal Webinar Terbuka</a></li>
                        <li><a href="{{ route('validation.show') }}" class="hover:text-amber-400 transition-colors"><i class="fa-solid fa-angle-right text-xs mr-2 text-slate-500"></i>Cek Keaslian Sertifikat</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-amber-400 transition-colors"><i class="fa-solid fa-angle-right text-xs mr-2 text-slate-500"></i>Portal Masuk Pengajar/Coach</a></li>
                    </ul>
                </div>

                <!-- Kolom 3: Layanan Bantuan / Helpdesk BKPSDM -->
                <div>
                    <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-4 border-b border-slate-800 pb-2">Pusat Bantuan BKPSDM</h4>
                    <div class="space-y-3 text-sm text-slate-400">
                        <div class="flex items-start gap-2.5">
                            <i class="fa-solid fa-location-dot text-amber-400 mt-1"></i>
                            <span>Gedung Lettu Imam Adi Lantai 3, Komplek Perkantoran Pemerintah Kabupaten Pasuruan Jl. Raya Raci KM 9, Pasuruan</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <i class="fa-brands fa-whatsapp text-emerald-400 text-base"></i>
                            <span>WhatsApp: 0822-3332-4100</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-envelope text-sky-400"></i>
                            <span>bkpsdm@pasuruankab.go.id</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom Legal Bar -->
            <div class="mt-12 pt-6 border-t border-slate-800 text-center text-xs text-slate-500 flex flex-col md:flex-row items-center justify-between gap-3">
                <p>&copy; 2026 Badan Kepegawaian dan Pengembangan Sumber Daya Manusia (BKPSDM) Pemerintah Kabupaten Pasuruan.</p>
                <p class="text-slate-400 font-mono">Versi Aplikasi 1.0.0</p>
            </div>
        </div>
    </footer>

    <!-- Universal Button Loading & Processing Feedback Engine -->
    <script src="{{ asset('js/button-loading.js') }}"></script>
</body>
</html>
