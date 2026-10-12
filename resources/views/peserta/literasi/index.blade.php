@extends('layouts.dashboard')

@section('page_title', 'Literasi Pengembangan Kompetensi ASN')
@section('page_subtitle', 'Pusat Referensi Jurnal Ilmiah, Karya Tulis Inovasi, Policy Brief, dan Modul Kedinasan Kabupaten Pasuruan')

@section('content')
<div class="space-y-6">

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 text-lg"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if(!$isAsn)
        <!-- Pemberitahuan Khusus Peserta Umum -->
        <div class="p-6 rounded-2xl bg-gradient-to-r from-amber-500/10 via-pasuruan-800/5 to-transparent border border-amber-300 shadow-xs space-y-3">
            <div class="flex items-center gap-3 text-amber-900 font-bold text-base">
                <i class="fa-solid fa-id-card-clip text-amber-600 text-xl"></i>
                <span>Repositori Literasi Khusus Aparatur Sipil Negara (ASN)</span>
            </div>
            <p class="text-xs text-slate-700 leading-relaxed max-w-4xl">
                Laman literasi ini merupakan bank data karya tulis ilmiah inovasi daerah, policy brief, dan modul kedinasan internal Pemerintah Kabupaten Pasuruan. Anda terdaftar sebagai <strong>Peserta Umum</strong>. Akses pengunduhan naskah lengkap hanya terbuka bagi akun berstatus ASN resmi. Jika Anda adalah ASN Pemkab Pasuruan, silakan perbarui data NIP Anda melalui Super Admin BKPSDM.
            </p>
        </div>
    @endif

    <!-- 1. Hero & Search Banner -->
    <div class="bg-gradient-to-br from-pasuruan-900 via-pasuruan-800 to-sky-900 rounded-2xl p-6 sm:p-8 text-white shadow-md relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 opacity-10 text-white pointer-events-none">
            <i class="fa-solid fa-book-bookmark text-9xl"></i>
        </div>

        <div class="relative z-10 max-w-3xl space-y-4">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-amber-300 border border-white/20 text-xs font-semibold">
                <i class="fa-solid fa-graduation-cap"></i>
                <span>Manajemen Pengetahuan BKPSDM Pasuruan</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight leading-snug">
                Literasi & Karya Ilmiah Pengembangan Kompetensi ASN
            </h1>
            <p class="text-xs sm:text-sm text-slate-200 leading-relaxed">
                Jelajahi riset kebijakan, inovasi pelayanan publik, jurnal terakreditasi, social learning, experience learning dan modul pembelajaran mandiri.
            </p>

            <!-- Search Form -->
            <form action="{{ route('peserta.literasi.index') }}" method="GET" class="pt-2 flex flex-col sm:flex-row gap-2">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul jurnal, topik penelitian, penulis, atau OPD..." class="w-full pl-10 pr-4 py-2.5 bg-white text-slate-900 placeholder-slate-400 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-amber-400 shadow-xs">
                </div>
                <div class="flex gap-2">
                    <select name="year" class="px-3 py-2.5 bg-white text-slate-800 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-amber-400 shadow-xs">
                        <option value="">Semua Tahun</option>
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="px-5 py-2.5 bg-amber-400 hover:bg-amber-500 text-pasuruan-950 font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-2">
                        <span>Cari</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>

                    @if(request('q') || request('category') || request('year') || request('sort'))
                        <a href="{{ route('peserta.literasi.index') }}" class="px-3 py-2.5 bg-white/20 hover:bg-white/30 text-white rounded-xl text-xs flex items-center justify-center transition-colors" title="Reset Filter">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Category Navigation Pills & Sorter -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <!-- Category Filter Pills -->
        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-1 md:pb-0 scrollbar-none text-xs font-bold">
            <a href="{{ route('peserta.literasi.index', array_merge(request()->except('category', 'page'), ['category' => 'all'])) }}" class="px-3.5 py-2 rounded-xl transition-colors shrink-0 flex items-center gap-2 {{ (!request('category') || request('category') === 'all') ? 'bg-pasuruan-800 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                <span>Semua Publikasi</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ (!request('category') || request('category') === 'all') ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600' }}">{{ $stats['total'] }}</span>
            </a>

            <a href="{{ route('peserta.literasi.index', array_merge(request()->except('category', 'page'), ['category' => 'jurnal'])) }}" class="px-3.5 py-2 rounded-xl transition-colors shrink-0 flex items-center gap-2 {{ request('category') === 'jurnal' ? 'bg-indigo-700 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                <i class="fa-solid fa-book-journal-whills"></i>
                <span>Jurnal Ilmiah</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ request('category') === 'jurnal' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600' }}">{{ $stats['jurnal'] }}</span>
            </a>

            <a href="{{ route('peserta.literasi.index', array_merge(request()->except('category', 'page'), ['category' => 'karya_ilmiah'])) }}" class="px-3.5 py-2 rounded-xl transition-colors shrink-0 flex items-center gap-2 {{ request('category') === 'karya_ilmiah' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                <i class="fa-solid fa-lightbulb"></i>
                <span>Karya Tulis & Inovasi</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ request('category') === 'karya_ilmiah' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600' }}">{{ $stats['karya_ilmiah'] }}</span>
            </a>

            <a href="{{ route('peserta.literasi.index', array_merge(request()->except('category', 'page'), ['category' => 'policy_brief'])) }}" class="px-3.5 py-2 rounded-xl transition-colors shrink-0 flex items-center gap-2 {{ request('category') === 'policy_brief' ? 'bg-amber-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                <i class="fa-solid fa-file-contract"></i>
                <span>Policy Brief</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ request('category') === 'policy_brief' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600' }}">{{ $stats['policy_brief'] }}</span>
            </a>

            <a href="{{ route('peserta.literasi.index', array_merge(request()->except('category', 'page'), ['category' => 'modul_diklat'])) }}" class="px-3.5 py-2 rounded-xl transition-colors shrink-0 flex items-center gap-2 {{ request('category') === 'modul_diklat' ? 'bg-sky-700 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                <i class="fa-solid fa-file-lines"></i>
                <span>Modul Kedinasan</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ request('category') === 'modul_diklat' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600' }}">{{ $stats['modul'] }}</span>
            </a>
        </div>

        <!-- Sorter -->
        <div class="flex items-center gap-2 shrink-0 self-end md:self-auto text-xs">
            <span class="text-slate-500 font-medium">Urutkan:</span>
            <div class="flex bg-slate-100 p-0.5 rounded-lg">
                <a href="{{ route('peserta.literasi.index', array_merge(request()->all(), ['sort' => 'latest'])) }}" class="px-2.5 py-1 rounded font-semibold text-[11px] {{ (!request('sort') || request('sort') === 'latest') ? 'bg-white text-pasuruan-800 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Terbaru
                </a>
                <a href="{{ route('peserta.literasi.index', array_merge(request()->all(), ['sort' => 'popular'])) }}" class="px-2.5 py-1 rounded font-semibold text-[11px] {{ request('sort') === 'popular' ? 'bg-white text-pasuruan-800 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Terpopuler
                </a>
            </div>
        </div>
    </div>

    <!-- 3. Documents Grid -->
    @if($documents->isEmpty())
        <div class="bg-white rounded-2xl p-12 border border-slate-200 text-center space-y-3">
            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-2xl">
                <i class="fa-solid fa-book-open"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800">Belum ada dokumen literasi yang cocok</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto">
                Coba gunakan kata kunci pencarian yang lain atau ubah filter kategori untuk menemukan publikasi yang diinginkan.
            </p>
            <div class="pt-2">
                <a href="{{ route('peserta.literasi.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-pasuruan-800 text-white rounded-xl text-xs font-bold hover:bg-pasuruan-900 transition-colors">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Tampilkan Semua Publikasi</span>
                </a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($documents as $doc)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs hover:shadow-md transition-all flex flex-col justify-between overflow-hidden group">
                    @if($doc->cover_url)
                        <div class="relative h-44 w-full overflow-hidden bg-slate-900">
                            <img src="{{ $doc->cover_url }}" alt="{{ $doc->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-900/40 to-transparent"></div>
                            <div class="absolute top-3 left-3 right-3 flex items-center justify-between">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-extrabold uppercase tracking-wide bg-white/95 text-slate-800 backdrop-blur-xs shadow-xs">
                                    {{ $doc->category_label }}
                                </span>
                                <span class="text-[11px] font-bold text-white bg-black/50 px-2 py-0.5 rounded backdrop-blur-xs flex items-center gap-1">
                                    <i class="fa-regular fa-calendar"></i>
                                    <span>{{ $doc->publication_year }}</span>
                                </span>
                            </div>
                        </div>
                    @endif

                    <div class="p-5 space-y-3.5 flex-1 flex flex-col justify-between">
                        <div class="space-y-3">
                            @if(!$doc->cover_url)
                                <!-- Top Metadata -->
                                <div class="flex items-center justify-between gap-2">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-extrabold uppercase tracking-wide border {{ $doc->category_badge_class }}">
                                        {{ $doc->category_label }}
                                    </span>
                                    <span class="text-[11px] font-bold text-slate-400 flex items-center gap-1">
                                        <i class="fa-regular fa-calendar"></i>
                                        <span>{{ $doc->publication_year }}</span>
                                    </span>
                                </div>
                            @endif

                            <!-- Title -->
                            <h2 class="text-sm font-bold text-slate-900 leading-snug line-clamp-2 group-hover:text-pasuruan-800 transition-colors">
                                <a href="{{ route('peserta.literasi.show', $doc->slug) }}">
                                    {{ $doc->title }}
                                </a>
                            </h2>

                            <!-- Author & Institution -->
                            <div class="space-y-1 text-xs">
                                <div class="flex items-center gap-1.5 text-slate-700 font-semibold truncate">
                                    <i class="fa-solid fa-user-pen text-pasuruan-600 text-[11px] shrink-0"></i>
                                    <span class="truncate">{{ $doc->author }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-slate-500 text-[11px] truncate">
                                    <i class="fa-solid fa-building-columns text-slate-400 text-[11px] shrink-0"></i>
                                    <span class="truncate">{{ $doc->institution }}</span>
                                </div>
                            </div>

                            <!-- Description Snippet -->
                            <p class="text-xs text-slate-600 leading-relaxed line-clamp-3">
                                {{ $doc->description }}
                            </p>
                        </div>
                    </div>

                    <!-- Card Footer & Actions -->
                    <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-3 text-slate-400 font-medium text-[11px]">
                            <span title="Dilihat {{ $doc->view_count }} kali" class="flex items-center gap-1">
                                <i class="fa-regular fa-eye"></i> {{ $doc->view_count }}
                            </span>
                            <span title="Diunduh {{ $doc->download_count }} kali" class="flex items-center gap-1">
                                <i class="fa-solid fa-download text-emerald-600"></i> {{ $doc->download_count }}
                            </span>
                            <span class="text-slate-300">•</span>
                            <span>{{ $doc->formatted_file_size }}</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('peserta.literasi.show', $doc->slug) }}" class="px-3 py-1.5 bg-white hover:bg-slate-100 text-pasuruan-800 border border-slate-200 rounded-lg text-xs font-bold transition-colors shadow-2xs" title="Lihat Detail & Abstrak">
                                Detail
                            </a>

                            @if($isAsn)
                                <a href="{{ route('peserta.literasi.download', $doc->id) }}" class="px-3 py-1.5 bg-pasuruan-800 hover:bg-pasuruan-900 text-white rounded-lg text-xs font-bold transition-colors shadow-2xs flex items-center gap-1" title="Unduh PDF">
                                    <i class="fa-solid fa-arrow-down-to-bracket"></i>
                                    <span>Unduh</span>
                                </a>
                            @else
                                <span class="px-2.5 py-1.5 bg-slate-200 text-slate-500 rounded-lg text-[11px] font-semibold flex items-center gap-1 cursor-not-allowed" title="Unduh hanya untuk ASN">
                                    <i class="fa-solid fa-lock text-[10px]"></i>
                                    <span>ASN</span>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="pt-4">
            {{ $documents->links() }}
        </div>
    @endif

</div>
@endsection