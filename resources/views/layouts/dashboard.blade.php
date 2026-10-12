<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal LMS BKPSDM Pasuruan')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-pasuruan.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo-pasuruan.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Cinzel:wght@600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        'serif-cert': ['"Playfair Display"', 'Georgia', 'serif'],
                        'title-cert': ['"Cinzel"', 'serif'],
                    },
                    colors: {
                        pasuruan: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            500: '#1d6fa5',
                            700: '#0f3d62',
                            800: '#0b2e4b',
                            900: '#071f33',
                            950: '#030e17',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Flatpickr (Strict 24-Hour Time & DateTime Picker) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }

        /* Custom Modern Styling for 24h Flatpickr */
        .flatpickr-calendar {
            border-radius: 0.875rem !important;
            box-shadow: 0 12px 28px -5px rgba(15, 61, 98, 0.18), 0 8px 12px -6px rgba(0, 0, 0, 0.08) !important;
            border: 1px solid #cbd5e1 !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            padding: 4px !important;
        }
        .flatpickr-time {
            border-radius: 0.75rem !important;
            border-top: 1px solid #f1f5f9 !important;
            background: #f8fafc !important;
            height: 44px !important;
            line-height: 44px !important;
        }
        .flatpickr-time input {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
            font-weight: 700 !important;
            font-size: 14px !important;
            color: #0f3d62 !important;
            border-radius: 6px !important;
        }
        .flatpickr-time input:hover, .flatpickr-time input:focus {
            background: #e0effe !important;
        }
        .flatpickr-time .flatpickr-time-separator {
            color: #0f3d62 !important;
            font-weight: 800 !important;
        }
        .flatpickr-time .numInputWrapper span.arrowUp:after {
            border-bottom-color: #0f3d62 !important;
        }
        .flatpickr-time .numInputWrapper span.arrowDown:after {
            border-top-color: #0f3d62 !important;
        }
        .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange {
            background: #0f3d62 !important;
            border-color: #0f3d62 !important;
            font-weight: 700 !important;
        }
        .flatpickr-day:hover {
            background: #e0effe !important;
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
    <script>
        document.addEventListener('alpine:init', () => {
            // Alpine Directive: x-timepicker24
            Alpine.directive('timepicker24', (el) => {
                if (typeof flatpickr !== 'undefined') {
                    const fp = flatpickr(el, {
                        enableTime: true,
                        noCalendar: true,
                        dateFormat: "H:i",
                        time_24hr: true,
                        minuteIncrement: 5,
                        allowInput: true,
                        static: false,
                        onChange: function(selectedDates, dateStr) {
                            el.value = dateStr;
                            el.dispatchEvent(new Event('input', { bubbles: true }));
                            el.dispatchEvent(new Event('change', { bubbles: true }));
                        },
                        onClose: function(selectedDates, dateStr) {
                            if (dateStr) {
                                el.value = dateStr;
                                el.dispatchEvent(new Event('input', { bubbles: true }));
                                el.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }
                    });

                    // Sync when input value changes
                    el.addEventListener('focus', () => {
                        if (el.value && fp) {
                            fp.setDate(el.value, false, "H:i");
                        }
                    });
                }
            });

            // Alpine Directive: x-datetimepicker24
            Alpine.directive('datetimepicker24', (el) => {
                if (typeof flatpickr !== 'undefined') {
                    const fp = flatpickr(el, {
                        enableTime: true,
                        dateFormat: "Y-m-d H:i",
                        time_24hr: true,
                        minuteIncrement: 5,
                        allowInput: true,
                        onChange: function(selectedDates, dateStr) {
                            el.value = dateStr;
                            el.dispatchEvent(new Event('input', { bubbles: true }));
                            el.dispatchEvent(new Event('change', { bubbles: true }));
                        },
                        onClose: function(selectedDates, dateStr) {
                            if (dateStr) {
                                el.value = dateStr;
                                el.dispatchEvent(new Event('input', { bubbles: true }));
                                el.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }
                    });

                    el.addEventListener('focus', () => {
                        if (el.value && fp) {
                            fp.setDate(el.value, false, "Y-m-d H:i");
                        }
                    });
                }
            });
        });

        // Global fallback initialization for standard timepicker elements
        function initGlobalTimepickers() {
            if (typeof flatpickr !== 'undefined') {
                flatpickr('.timepicker-24h:not([data-fp-initialized])', {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    time_24hr: true,
                    minuteIncrement: 5,
                    allowInput: true,
                    onReady: function(selectedDates, dateStr, instance) {
                        instance.element.setAttribute('data-fp-initialized', 'true');
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        instance.element.value = dateStr;
                        instance.element.dispatchEvent(new Event('input', { bubbles: true }));
                        instance.element.dispatchEvent(new Event('change', { bubbles: true }));
                    },
                    onClose: function(selectedDates, dateStr, instance) {
                        if (dateStr) {
                            instance.element.value = dateStr;
                            instance.element.dispatchEvent(new Event('input', { bubbles: true }));
                            instance.element.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                });

                flatpickr('.datetimepicker-24h:not([data-fp-initialized])', {
                    enableTime: true,
                    dateFormat: "Y-m-d H:i",
                    time_24hr: true,
                    minuteIncrement: 5,
                    allowInput: true,
                    onReady: function(selectedDates, dateStr, instance) {
                        instance.element.setAttribute('data-fp-initialized', 'true');
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        instance.element.value = dateStr;
                        instance.element.dispatchEvent(new Event('input', { bubbles: true }));
                        instance.element.dispatchEvent(new Event('change', { bubbles: true }));
                    },
                    onClose: function(selectedDates, dateStr, instance) {
                        if (dateStr) {
                            instance.element.value = dateStr;
                            instance.element.dispatchEvent(new Event('input', { bubbles: true }));
                            instance.element.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                });
            }
        }
        document.addEventListener('DOMContentLoaded', initGlobalTimepickers);
    </script>
</head>
<body class="bg-slate-100 text-slate-800 font-sans antialiased min-h-screen flex selection:bg-pasuruan-700 selection:text-white" x-data="{ sidebarOpen: false }">

    @php
        $authUser = Auth::user();
        $userRole = $authUser?->role ?? 'peserta';
        $isFirstLoginLocked = $authUser && $authUser->needsFirstLoginSetup();
    @endphp

    <!-- 1. Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden"></div>

    <!-- 2. Sidebar Navigasi Responsif -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" 
           class="fixed top-0 bottom-0 left-0 z-50 w-72 bg-pasuruan-900 text-slate-300 transition-transform duration-300 ease-in-out flex flex-col border-r border-pasuruan-800 shadow-xl">
        
        <!-- Sidebar Brand Header -->
        <div class="h-20 flex items-center justify-between px-6 border-b border-pasuruan-800 bg-pasuruan-950/50">
            <a href="{{ $isFirstLoginLocked ? route('profile.show') : route('home') }}" class="flex items-center gap-3 group">
                <picture>
                    <source srcset="{{ asset('images/logo-pasuruan.webp') }}" type="image/webp">
                    <img src="{{ asset('images/logo-pasuruan.png') }}" alt="Logo Kabupaten Pasuruan" class="h-10 w-auto object-contain group-hover:scale-105 transition-transform duration-200 drop-shadow-sm">
                </picture>
                <div>
                    <span class="font-extrabold text-base text-white tracking-tight">LMS PASURUAN</span>
                    <p class="text-[11px] text-sky-300 font-medium">BKPSDM Kab. Pasuruan</p>
                </div>
            </a>
            <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Role Badge Display -->
        <div class="px-5 py-3.5 mx-4 mt-4 rounded-xl bg-pasuruan-800/80 border border-pasuruan-700/60 flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-slate-700 text-amber-400 flex items-center justify-center font-bold text-sm shrink-0 shadow-inner">
                {{ substr($authUser->name, 0, 1) }}
            </div>
            <div class="overflow-hidden">
                <p class="text-xs font-bold text-white truncate">{{ $authUser->name }}</p>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span class="text-[11px] font-medium uppercase tracking-wider text-sky-300">
                        {{ str_replace('_', ' ', $userRole) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Navigasi Menu Items -->
        <!-- Navigasi Menu Items -->
        <nav class="flex-1 px-4 py-4 space-y-1.5 overflow-y-auto">

            @if($userRole === 'peserta')
                <div class="px-3 pb-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                    {{ $authUser->isPesertaUmum() ? 'Menu Peserta Umum' : 'Menu Pembelajaran ASN' }}
                </div>
                
                @if(\App\Models\SidebarMenuSetting::isMenuVisible('peserta', 'peserta.dashboard'))
                    @if($isFirstLoginLocked)
                    <a href="javascript:void(0)" onclick="alert('Pemberitahuan: Akun Anda baru pertama kali aktif. Silakan simpan data profil dan perbarui kata sandi Anda terlebih dahulu di menu Profil & Keamanan.'); return false;" class="flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm font-semibold opacity-40 cursor-not-allowed text-slate-400 hover:text-slate-400 select-none" title="Menu terkunci hingga profil & kata sandi diperbarui">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-gauge-high w-5 text-center text-slate-500"></i>
                            <span>Dashboard {{ $authUser->isPesertaUmum() ? 'Umum' : 'Peserta' }}</span>
                        </div>
                        <i class="fa-solid fa-lock text-xs text-amber-400"></i>
                    </a>
                    @else
                    <a href="{{ route('peserta.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('peserta.dashboard') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                        <i class="fa-solid fa-gauge-high w-5 text-center {{ request()->routeIs('peserta.dashboard') ? 'text-amber-400' : 'text-slate-400' }}"></i>
                        <span>Dashboard {{ $authUser->isPesertaUmum() ? 'Umum' : 'Peserta' }}</span>
                    </a>
                    @endif
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('peserta', 'peserta.webinar'))
                    @if($isFirstLoginLocked)
                    <a href="javascript:void(0)" onclick="alert('Pemberitahuan: Akun Anda baru pertama kali aktif. Silakan simpan data profil dan perbarui kata sandi Anda terlebih dahulu di menu Profil & Keamanan.'); return false;" class="flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm font-semibold opacity-40 cursor-not-allowed text-slate-400 hover:text-slate-400 select-none" title="Menu terkunci hingga profil & kata sandi diperbarui">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-tower-broadcast w-5 text-center text-slate-500"></i>
                            <span>Webinar</span>
                        </div>
                        <i class="fa-solid fa-lock text-xs text-amber-400"></i>
                    </a>
                    @else
                    <a href="{{ route('peserta.webinar.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('peserta.webinar.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                        <i class="fa-solid fa-tower-broadcast w-5 text-center text-rose-400"></i>
                        <div class="flex items-center justify-between w-full">
                            <span>Webinar</span>
                            <span class="bg-rose-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">Agenda</span>
                        </div>
                    </a>
                    @endif
                @endif

                @if($authUser->isAsn())
                    @if(\App\Models\SidebarMenuSetting::isMenuVisible('peserta', 'peserta.mooc'))
                        @if($isFirstLoginLocked)
                        <a href="javascript:void(0)" onclick="alert('Pemberitahuan: Akun Anda baru pertama kali aktif. Silakan simpan data profil dan perbarui kata sandi Anda terlebih dahulu di menu Profil & Keamanan.'); return false;" class="flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm font-semibold opacity-40 cursor-not-allowed text-slate-400 hover:text-slate-400 select-none" title="Menu terkunci hingga profil & kata sandi diperbarui">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-book-open-reader w-5 text-center text-slate-500"></i>
                                <span>Katalog Pelatihan & Diklat</span>
                            </div>
                            <i class="fa-solid fa-lock text-xs text-amber-400"></i>
                        </a>
                        @else
                        <a href="{{ route('peserta.katalog') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ (request()->routeIs('peserta.katalog*') || request()->routeIs('peserta.mooc.*')) ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                            <i class="fa-solid fa-book-open-reader w-5 text-center {{ (request()->routeIs('peserta.katalog*') || request()->routeIs('peserta.mooc.*')) ? 'text-amber-400' : 'text-slate-400' }}"></i>
                            <span>Katalog Pelatihan & Diklat</span>
                        </a>
                        @endif
                    @endif

                    @if(\App\Models\SidebarMenuSetting::isMenuVisible('peserta', 'peserta.kepemimpinan'))
                        @if($isFirstLoginLocked)
                        <a href="javascript:void(0)" onclick="alert('Pemberitahuan: Akun Anda baru pertama kali aktif. Silakan simpan data profil dan perbarui kata sandi Anda terlebih dahulu di menu Profil & Keamanan.'); return false;" class="flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm font-semibold opacity-40 cursor-not-allowed text-slate-400 hover:text-slate-400 select-none" title="Menu terkunci hingga profil & kata sandi diperbarui">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-award w-5 text-center text-slate-500"></i>
                                <span>Diklat Kepemimpinan</span>
                            </div>
                            <i class="fa-solid fa-lock text-xs text-amber-400"></i>
                        </a>
                        @else
                        <a href="{{ route('peserta.kepemimpinan') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('peserta.kepemimpinan*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                            <i class="fa-solid fa-award w-5 text-center {{ request()->routeIs('peserta.kepemimpinan*') ? 'text-amber-400' : 'text-slate-400' }}"></i>
                            <span>Diklat Kepemimpinan</span>
                        </a>
                        @endif
                    @endif

                    @if(\App\Models\SidebarMenuSetting::isMenuVisible('peserta', 'peserta.literasi'))
                        @if($isFirstLoginLocked)
                        <a href="javascript:void(0)" onclick="alert('Pemberitahuan: Akun Anda baru pertama kali aktif. Silakan simpan data profil dan perbarui kata sandi Anda terlebih dahulu di menu Profil & Keamanan.'); return false;" class="flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm font-semibold opacity-40 cursor-not-allowed text-slate-400 hover:text-slate-400 select-none" title="Menu terkunci hingga profil & kata sandi diperbarui">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-book-bookmark w-5 text-center text-slate-500"></i>
                                <span>Literasi Kompetensi ASN</span>
                            </div>
                            <i class="fa-solid fa-lock text-xs text-amber-400"></i>
                        </a>
                        @else
                        <a href="{{ route('peserta.literasi.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('peserta.literasi.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                            <i class="fa-solid fa-book-bookmark w-5 text-center {{ request()->routeIs('peserta.literasi.*') ? 'text-amber-400' : 'text-slate-400' }}"></i>
                            <span>Literasi Kompetensi ASN</span>
                        </a>
                        @endif
                    @endif
                @else
                    <div class="px-3.5 py-2 rounded-lg text-xs text-slate-500 bg-pasuruan-950/40 border border-pasuruan-800/40 space-y-1">
                        <div class="flex items-center gap-1.5 text-amber-400 font-bold text-[10px] uppercase">
                            <i class="fa-solid fa-lock"></i> MOOC & Diklat Kedinasan
                        </div>
                        <p class="text-[10px] text-slate-400">Khusus aparatur ASN Pemkab Pasuruan.</p>
                    </div>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('peserta', 'peserta.certificates'))
                <div class="pt-4 px-3 pb-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Hasil Belajar</div>

                    @if($isFirstLoginLocked)
                    <a href="javascript:void(0)" onclick="alert('Pemberitahuan: Akun Anda baru pertama kali aktif. Silakan simpan data profil dan perbarui kata sandi Anda terlebih dahulu di menu Profil & Keamanan.'); return false;" class="flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm font-semibold opacity-40 cursor-not-allowed text-slate-400 hover:text-slate-400 select-none" title="Menu terkunci hingga profil & kata sandi diperbarui">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-stamp w-5 text-center text-slate-500"></i>
                            <span>E-Sertifikat Saya</span>
                        </div>
                        <i class="fa-solid fa-lock text-xs text-amber-400"></i>
                    </a>
                    @else
                    <a href="{{ route('peserta.certificates') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('peserta.certificates') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                        <i class="fa-solid fa-stamp w-5 text-center text-amber-400"></i>
                        <span>E-Sertifikat Saya</span>
                    </a>
                    @endif
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('peserta', 'peserta.profile'))
                <div class="pt-4 px-3 pb-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pengaturan Akun</div>

                <a href="{{ route('profile.show') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('profile.*') ? 'bg-pasuruan-700 text-white shadow-sm ring-1 ring-amber-400/50' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-user-gear w-5 text-center {{ request()->routeIs('profile.*') ? 'text-amber-400' : 'text-slate-400' }}"></i>
                        <span>Profil & Keamanan</span>
                    </div>
                    @if($isFirstLoginLocked)
                        <span class="bg-amber-400 text-pasuruan-950 text-[10px] font-extrabold px-2 py-0.5 rounded-full animate-pulse shadow-xs">Langkah Wajib</span>
                    @endif
                </a>
                @endif

            @elseif($userRole === 'super_admin')
                <!-- Menu Khusus Super Admin BKPSDM -->
                <div class="px-3 pb-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tata Kelola & Pegawai</div>

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('super_admin', 'super_admin.dashboard'))
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.dashboard') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-chart-pie w-5 text-center text-amber-400"></i>
                    <span>Dashboard Utama</span>
                </a>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('super_admin', 'super_admin.users'))
                <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.users.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-users-gear w-5 text-center text-sky-400"></i>
                    <span>Data Pegawai & Akun</span>
                </a>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('super_admin', 'super_admin.api_sync'))
                <a href="{{ route('admin.api_sync.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.api_sync.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-network-wired w-5 text-center text-emerald-400"></i>
                    <div class="flex items-center justify-between w-full">
                        <span>Integrasi API PINTAR</span>
                        <span class="bg-emerald-500/20 text-emerald-400 text-[10px] font-bold px-1.5 py-0.5 rounded border border-emerald-500/30">API</span>
                    </div>
                </a>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('super_admin', 'super_admin.sidebar_menu'))
                <a href="{{ route('admin.sidebar_menu.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.sidebar_menu.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-list-check w-5 text-center text-amber-400"></i>
                    <div class="flex items-center justify-between w-full">
                        <span>Kelola Menu Sidebar</span>
                        <span class="bg-amber-500/20 text-amber-300 text-[10px] font-bold px-1.5 py-0.5 rounded border border-amber-500/30">Role</span>
                    </div>
                </a>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('super_admin', 'super_admin.certificates_designer'))
                <a href="{{ route('admin.certificates.designer') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.certificates.designer') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-compass-drafting w-5 text-center text-purple-400"></i>
                    <span>Visual Certificate Designer</span>
                </a>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('super_admin', 'super_admin.certificates_manual'))
                <a href="{{ route('admin.certificates.manual') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.certificates.manual*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-stamp w-5 text-center text-amber-400"></i>
                    <div class="flex items-center justify-between w-full">
                        <span>Penerbitan Sertifikat Manual</span>
                        <span class="bg-amber-500/20 text-amber-300 text-[10px] font-bold px-1.5 py-0.5 rounded border border-amber-500/30">Manual</span>
                    </div>
                </a>
                @endif

                <div class="pt-3 px-3 pb-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Manajemen Program Diklat</div>

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('super_admin', 'super_admin.webinars'))
                <a href="{{ route('admin.webinars.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.webinars.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-calendar-check w-5 text-center text-rose-400"></i>
                    <span>Manajemen Webinar</span>
                </a>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('super_admin', 'super_admin.courses'))
                <a href="{{ route('admin.courses.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.courses.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-graduation-cap w-5 text-center text-emerald-400"></i>
                    <span>Pelatihan MOOC & Materi</span>
                </a>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('super_admin', 'super_admin.questionnaires'))
                <a href="{{ route('admin.questionnaires.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.questionnaires.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-square-poll-vertical w-5 text-center text-teal-400"></i>
                    <div class="flex items-center justify-between w-full">
                        <span>Hasil Kuesioner</span>
                        <span class="bg-teal-500/20 text-teal-300 text-[10px] font-bold px-1.5 py-0.5 rounded border border-teal-500/30">CSAT</span>
                    </div>
                </a>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('super_admin', 'super_admin.literasi'))
                <a href="{{ route('admin.literasi.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.literasi.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-book-bookmark w-5 text-center text-indigo-400"></i>
                    <span>Kelola Pustaka & Jurnal</span>
                </a>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('super_admin', 'super_admin.kepemimpinan'))
                <a href="{{ route('admin.kepemimpinan.review') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.kepemimpinan.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-comments text-center text-sky-400"></i>
                    <span>Review Aksi Perubahan</span>
                </a>
                @endif

            @elseif($userRole === 'penyelenggara')
                <!-- Menu Khusus Penyelenggara Diklat (Operator Event & Pelatihan) -->
                <div class="px-3 pb-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Operasional Penyelenggara</div>

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('penyelenggara', 'penyelenggara.dashboard'))
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.dashboard') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-chart-pie w-5 text-center text-purple-400"></i>
                    <span>Dashboard Penyelenggara</span>
                </a>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('penyelenggara', 'penyelenggara.webinars'))
                <a href="{{ route('admin.webinars.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.webinars.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-calendar-check w-5 text-center text-rose-400"></i>
                    <span>Manajemen Webinar & Sesi Live</span>
                </a>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('penyelenggara', 'penyelenggara.courses'))
                <a href="{{ route('admin.courses.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.courses.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-graduation-cap w-5 text-center text-emerald-400"></i>
                    <span>Pelatihan MOOC & Upload Materi</span>
                </a>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('penyelenggara', 'penyelenggara.questionnaires'))
                <a href="{{ route('admin.questionnaires.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.questionnaires.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-square-poll-vertical w-5 text-center text-teal-400"></i>
                    <div class="flex items-center justify-between w-full">
                        <span>Hasil Kuesioner</span>
                        <span class="bg-teal-500/20 text-teal-300 text-[10px] font-bold px-1.5 py-0.5 rounded border border-teal-500/30">CSAT</span>
                    </div>
                </a>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('penyelenggara', 'penyelenggara.literasi'))
                <a href="{{ route('admin.literasi.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.literasi.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-book-bookmark w-5 text-center text-indigo-400"></i>
                    <span>Kelola Pustaka & Jurnal</span>
                </a>
                @endif

            @elseif($userRole === 'widyaiswara')
                <!-- Menu Khusus Widyaiswara (Coach / Pengampu) -->
                <div class="px-3 pb-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pengampu & Evaluator</div>

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('widyaiswara', 'widyaiswara.dashboard'))
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.dashboard') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-chalkboard-user w-5 text-center text-sky-400"></i>
                    <span>Dashboard Widyaiswara</span>
                </a>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('widyaiswara', 'widyaiswara.courses'))
                <a href="{{ route('admin.courses.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.courses.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-clipboard-check w-5 text-center text-emerald-400"></i>
                    <span>Hasil Belajar & Nilai Tugas</span>
                </a>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('widyaiswara', 'widyaiswara.questionnaires'))
                <a href="{{ route('admin.questionnaires.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.questionnaires.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-square-poll-vertical w-5 text-center text-teal-400"></i>
                    <div class="flex items-center justify-between w-full">
                        <span>Hasil Kuesioner</span>
                        <span class="bg-teal-500/20 text-teal-300 text-[10px] font-bold px-1.5 py-0.5 rounded border border-teal-500/30">CSAT</span>
                    </div>
                </a>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('widyaiswara', 'widyaiswara.kepemimpinan'))
                <a href="{{ route('admin.kepemimpinan.review') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.kepemimpinan.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-comments w-5 text-center text-amber-400"></i>
                    <span>Bimbingan Aksi Perubahan</span>
                </a>
                @endif

            @elseif($userRole === 'mentor')
                <!-- Menu Khusus Mentor (Atasan Langsung) -->
                <div class="px-3 pb-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Bimbingan Atasan Langsung</div>

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('mentor', 'mentor.dashboard'))
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.dashboard') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-user-tie w-5 text-center text-emerald-400"></i>
                    <span>Dashboard Mentor</span>
                </a>
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('mentor', 'mentor.kepemimpinan'))
                <a href="{{ route('admin.kepemimpinan.review') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.kepemimpinan.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-signature w-5 text-center text-emerald-400"></i>
                    <span>Persetujuan Logbook Bawahan</span>
                </a>
                @endif
            @endif

            @if($userRole !== 'peserta')
                @if(\App\Models\SidebarMenuSetting::isMenuVisible($userRole, $userRole . '.profile', true))
                <div class="pt-4 px-3 pb-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pengaturan Akun</div>
                <a href="{{ route('profile.show') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold {{ request()->routeIs('profile.*') ? 'bg-pasuruan-700 text-white shadow-sm' : 'text-slate-300 hover:bg-pasuruan-800 hover:text-white' }} transition-colors">
                    <i class="fa-solid fa-user-gear w-5 text-center {{ request()->routeIs('profile.*') ? 'text-amber-400' : 'text-slate-400' }}"></i>
                    <span>Profil & Keamanan</span>
                </a>
                @endif
            @endif

            @if(\App\Models\SidebarMenuSetting::isMenuVisible('common', 'common.home') || \App\Models\SidebarMenuSetting::isMenuVisible('common', 'common.validation'))
                <div class="pt-4 px-3 pb-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Publik</div>
                
                @if(\App\Models\SidebarMenuSetting::isMenuVisible('common', 'common.home'))
                    @if($isFirstLoginLocked)
                    <a href="javascript:void(0)" onclick="alert('Pemberitahuan: Akun Anda baru pertama kali aktif. Silakan simpan data profil dan perbarui kata sandi Anda terlebih dahulu.'); return false;" class="flex items-center justify-between px-3.5 py-2 rounded-lg text-sm opacity-40 cursor-not-allowed text-slate-500 select-none">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-arrow-left w-5 text-center"></i>
                            <span>Kembali ke Beranda</span>
                        </div>
                        <i class="fa-solid fa-lock text-xs text-amber-400"></i>
                    </a>
                    @else
                    <a href="{{ route('home') }}" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-sm text-slate-400 hover:bg-pasuruan-800 hover:text-white transition-colors">
                        <i class="fa-solid fa-arrow-left w-5 text-center"></i>
                        <span>Kembali ke Beranda</span>
                    </a>
                    @endif
                @endif

                @if(\App\Models\SidebarMenuSetting::isMenuVisible('common', 'common.validation'))
                    @if($isFirstLoginLocked)
                    <a href="javascript:void(0)" onclick="alert('Pemberitahuan: Akun Anda baru pertama kali aktif. Silakan simpan data profil dan perbarui kata sandi Anda terlebih dahulu.'); return false;" class="flex items-center justify-between px-3.5 py-2 rounded-lg text-sm opacity-40 cursor-not-allowed text-slate-500 select-none">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-qrcode w-5 text-center"></i>
                            <span>Validasi Sertifikat Publik</span>
                        </div>
                        <i class="fa-solid fa-lock text-xs text-amber-400"></i>
                    </a>
                    @else
                    <a href="{{ route('validation.show') }}" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-sm text-slate-400 hover:bg-pasuruan-800 hover:text-emerald-400 transition-colors">
                        <i class="fa-solid fa-qrcode w-5 text-center"></i>
                        <span>Validasi Sertifikat Publik</span>
                    </a>
                    @endif
                @endif
            @endif
        </nav>

        <!-- Sidebar Footer Logout -->
        <div class="p-4 border-t border-pasuruan-800 bg-pasuruan-950/30">
            <a href="{{ route('logout') }}" class="flex items-center justify-center gap-2 w-full py-2 px-4 rounded-lg text-xs font-bold text-rose-300 bg-rose-950/50 hover:bg-rose-900/60 border border-rose-800/40 transition-colors">
                <i class="fa-solid fa-power-off"></i> Keluar Sesi
            </a>
        </div>
    </aside>

    <!-- 3. Main Dashboard Layout Area -->
    <div class="flex-1 lg:pl-72 flex flex-col min-h-screen">
        
        <!-- Top Navbar in Dashboard -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8 shadow-xs">
            
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 text-slate-600 hover:text-slate-900 rounded-lg">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h1 class="text-base font-bold text-slate-800 hidden sm:block">
                    @yield('header_title', 'Portal Pembelajaran ASN Pasuruan')
                </h1>
            </div>

            <!-- User Profile & Direct Logout in Topbar -->
            <div class="flex items-center gap-3">
                <a href="{{ route('profile.show') }}" title="Buka Profil & Pengaturan Akun" class="flex items-center gap-2.5 hover:opacity-85 transition-opacity group">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-bold text-slate-800 group-hover:text-pasuruan-700 transition-colors">{{ $authUser?->name ?? 'Pengguna' }}</p>
                        <div class="flex items-center justify-end gap-1.5 mt-0.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span class="text-[10px] text-pasuruan-700 font-bold uppercase tracking-wider">
                                {{ str_replace('_', ' ', $userRole) }}
                            </span>
                            @if($authUser?->nip)
                                <span class="text-[10px] text-slate-400 font-mono">• {{ $authUser->nip }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="w-9 h-9 rounded-full bg-pasuruan-700 text-white flex items-center justify-center font-bold text-sm shadow-xs group-hover:ring-2 group-hover:ring-pasuruan-400 transition-all overflow-hidden">
                        @if($authUser?->profile?->photo_path && file_exists(storage_path('app/public/' . $authUser->profile->photo_path)))
                            <img src="{{ asset('storage/' . $authUser->profile->photo_path) }}" class="w-full h-full object-cover">
                        @else
                            {{ substr($authUser?->name ?? 'P', 0, 1) }}
                        @endif
                    </div>
                </a>

                <div class="h-6 w-px bg-slate-200"></div>

                <a href="{{ route('logout') }}" title="Keluar dari sesi" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors text-xs font-semibold flex items-center gap-1">
                    <i class="fa-solid fa-power-off"></i>
                    <span class="hidden md:inline">Keluar</span>
                </a>
            </div>
        </header>

        <!-- Flash Alert in Dashboard -->
        @if(session('success'))
            <div class="flash-alert-item px-4 sm:px-6 lg:px-8 pt-5">
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 p-4 rounded-2xl shadow-xs flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-lg shrink-0"></i>
                        <div class="text-xs sm:text-sm font-medium">{{ session('success') }}</div>
                    </div>
                    <button type="button" onclick="this.closest('.flash-alert-item').remove()" class="text-emerald-500 hover:text-emerald-700 p-1 shrink-0 transition-colors" title="Tutup">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="flash-alert-item px-4 sm:px-6 lg:px-8 pt-5">
                <div class="bg-rose-50 border border-rose-200 text-rose-900 p-4 rounded-2xl shadow-xs flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-exclamation text-rose-600 text-lg shrink-0"></i>
                        <div class="text-xs sm:text-sm font-medium">{{ session('error') }}</div>
                    </div>
                    <button type="button" onclick="this.closest('.flash-alert-item').remove()" class="text-rose-500 hover:text-rose-700 p-1 shrink-0 transition-colors" title="Tutup">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        @endif

        @if(session('warning'))
            <div class="flash-alert-item px-4 sm:px-6 lg:px-8 pt-5">
                <div class="bg-amber-50 border border-amber-200 text-amber-900 p-4 rounded-2xl shadow-xs flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg shrink-0"></i>
                        <div class="text-xs sm:text-sm font-medium">{{ session('warning') }}</div>
                    </div>
                    <button type="button" onclick="this.closest('.flash-alert-item').remove()" class="text-amber-500 hover:text-amber-700 p-1 shrink-0 transition-colors" title="Tutup">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        @endif

        <!-- Main Workspace Content -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            @yield('content')
        </main>

        <!-- Bottom Dashboard Footer -->
        <footer class="bg-white border-t border-slate-200 py-3.5 px-6 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div>
                &copy; 2026 Badan Kepegawaian dan Pengembangan Sumber Daya Manusia Kabupaten Pasuruan
            </div>
        </footer>

    </div>

    <!-- Universal Button Loading & Processing Feedback Engine -->
    <script src="{{ asset('js/button-loading.js') }}"></script>
</body>
</html>
