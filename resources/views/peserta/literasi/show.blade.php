@extends('layouts.dashboard')

@section('page_title', 'Detail Dokumen Literasi')
@section('page_subtitle', $document->title)

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
        <a href="{{ route('peserta.dashboard') }}" class="hover:text-pasuruan-800 transition-colors">Dashboard</a>
        <span>/</span>
        <a href="{{ route('peserta.literasi.index') }}" class="hover:text-pasuruan-800 transition-colors">Literasi Kompetensi</a>
        <span>/</span>
        <span class="text-slate-800 font-bold truncate max-w-xs sm:max-w-md">{{ $document->title }}</span>
    </div>

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: Document Metadata & Download Card -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-5">
                @if($document->cover_url)
                    <div class="rounded-xl overflow-hidden shadow-xs border border-slate-200 aspect-[4/3] bg-slate-900">
                        <img src="{{ $document->cover_url }}" alt="{{ $document->title }}" class="w-full h-full object-cover">
                    </div>
                @endif

                <!-- Category Badge & Year -->
                <div class="flex items-center justify-between">
                    <span class="px-3 py-1 rounded-md text-xs font-extrabold uppercase tracking-wide border {{ $document->category_badge_class }}">
                        {{ $document->category_label }}
                    </span>
                    <span class="text-xs font-bold text-slate-500">
                        Tahun {{ $document->publication_year }}
                    </span>
                </div>

                <!-- Document Header -->
                <div>
                    <h1 class="text-lg font-extrabold text-slate-900 leading-snug">
                        {{ $document->title }}
                    </h1>
                    @if($document->edition_volume)
                        <p class="text-xs text-pasuruan-700 font-semibold mt-1">
                            {{ $document->edition_volume }}
                        </p>
                    @endif
                </div>

                <div class="border-t border-slate-100 pt-4 space-y-3 text-xs">
                    <div>
                        <span class="text-slate-400 font-semibold block text-[10px] uppercase">Penulis / Peneliti</span>
                        <span class="text-slate-800 font-bold flex items-center gap-1.5 mt-0.5">
                            <i class="fa-solid fa-user-pen text-pasuruan-600"></i>
                            {{ $document->author }}
                        </span>
                    </div>

                    <div>
                        <span class="text-slate-400 font-semibold block text-[10px] uppercase">Instansi / Unit Kerja Penerbit</span>
                        <span class="text-slate-800 font-semibold flex items-center gap-1.5 mt-0.5">
                            <i class="fa-solid fa-building-columns text-slate-500"></i>
                            {{ $document->institution }}
                        </span>
                    </div>

                    @if($document->isbn_issn)
                    <div>
                        <span class="text-slate-400 font-semibold block text-[10px] uppercase">Nomor Standar Resmi</span>
                        <span class="text-slate-800 font-mono font-bold mt-0.5 block">
                            {{ $document->isbn_issn }}
                        </span>
                    </div>
                    @endif

                    <div class="grid grid-cols-2 gap-2 pt-1 text-[11px]">
                        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            <span class="text-slate-400 block text-[10px]">Ukuran Berkas</span>
                            <span class="font-bold text-slate-700">{{ $document->formatted_file_size }}</span>
                        </div>
                        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            <span class="text-slate-400 block text-[10px]">Total Unduhan</span>
                            <span class="font-bold text-emerald-600 flex items-center gap-1">
                                <i class="fa-solid fa-arrow-down"></i> {{ $document->download_count }} kali
                            </span>
                        </div>
                    </div>

                    <div>
                        <span class="text-slate-400 font-semibold block text-[10px] uppercase">Diupload Oleh</span>
                        <span class="text-slate-600 text-[11px] flex items-center gap-1.5 mt-0.5">
                            <i class="fa-solid fa-circle-check text-sky-500"></i>
                            {{ $document->uploader ? $document->uploader->name : 'Pengelola Diklat' }} 
                            <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono">
                                {{ $document->uploader && $document->uploader->isSuperAdmin() ? 'Super Admin' : 'Penyelenggara' }}
                            </span>
                        </span>
                    </div>
                </div>

                <!-- Action Button Download -->
                <div class="pt-2">
                    @if($isAsn)
                        <a href="{{ route('peserta.literasi.download', $document->id) }}" class="w-full py-3 bg-pasuruan-800 hover:bg-pasuruan-900 text-white font-bold text-xs rounded-xl shadow-md transition-colors flex items-center justify-center gap-2">
                            <i class="fa-solid fa-file-pdf text-rose-400 text-sm"></i>
                            <span>Unduh Naskah Dokumen Lengkap (PDF)</span>
                        </a>
                        <p class="text-[10px] text-slate-400 text-center mt-2">
                            Dokumen resmi aparatur berhak cipta Pemerintah Kabupaten Pasuruan.
                        </p>
                    @else
                        <div class="p-3 bg-slate-100 rounded-xl text-center space-y-1">
                            <div class="text-xs font-bold text-slate-700 flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-lock text-amber-500"></i>
                                <span>Akses Unduhan Khusus ASN</span>
                            </div>
                            <p class="text-[10px] text-slate-500 leading-tight">
                                Pengunduhan naskah PDF lengkap dikhususkan bagi aparatur ASN aktif Pemkab Pasuruan.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Related Documents Widget -->
            @if($relatedDocuments->isNotEmpty())
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-layer-group text-pasuruan-600"></i>
                        <span>Publikasi Terkait ({{ $document->category_label }})</span>
                    </h3>

                    <div class="divide-y divide-slate-100 text-xs">
                        @foreach($relatedDocuments as $rel)
                            <a href="{{ route('peserta.literasi.show', $rel->slug) }}" class="block py-2.5 group hover:bg-slate-50 px-2 rounded-lg transition-colors">
                                <h4 class="font-bold text-slate-800 group-hover:text-pasuruan-800 line-clamp-2 leading-snug">
                                    {{ $rel->title }}
                                </h4>
                                <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-500">
                                    <span>{{ $rel->author }}</span>
                                    <span>•</span>
                                    <span>{{ $rel->publication_year }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column: Abstract, Executive Summary & PDF Preview -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Abstract Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-quote-left text-pasuruan-700"></i>
                        <span>Abstrak & Ringkasan Eksekutif</span>
                    </h2>
                    <span class="text-[11px] text-slate-400">
                        Terbit: {{ $document->created_at->format('d M Y') }}
                    </span>
                </div>

                <div class="text-slate-700 text-xs leading-relaxed space-y-3 text-justify">
                    {!! nl2br(e($document->description)) !!}
                </div>

                @if($document->keywords)
                    <div class="pt-4 border-t border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Kata Kunci (Keywords):</span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach(explode(',', $document->keywords) as $tag)
                                <span class="px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium">
                                    #{{ trim($tag) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- PDF Preview / Reader Container -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center font-bold text-xs">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-slate-900">Pratinjau Dokumen Naskah</h3>
                            <p class="text-[11px] text-slate-500">Format dokumen standar digital PDF</p>
                        </div>
                    </div>

                    @if($isAsn)
                        <a href="{{ route('peserta.literasi.download', $document->id) }}" class="text-xs font-bold text-pasuruan-800 hover:underline flex items-center gap-1">
                            <span>Unduh Asli</span>
                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                        </a>
                    @endif
                </div>

                <div class="p-8 rounded-xl bg-slate-50 border border-slate-200 flex flex-col items-center justify-center text-center space-y-3">
                    <div class="w-16 h-16 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center text-3xl shadow-xs">
                        <i class="fa-solid fa-file-pdf"></i>
                    </div>
                    <div class="max-w-md space-y-1">
                        <h4 class="font-bold text-xs text-slate-800">{{ $document->title }}.pdf</h4>
                        <p class="text-[11px] text-slate-500">
                            Ukuran Berkas: {{ $document->formatted_file_size }} • Status: Terverifikasi oleh BKPSDM
                        </p>
                    </div>

                    @if($isAsn)
                        <div class="pt-2">
                            <a href="{{ route('peserta.literasi.download', $document->id) }}" class="px-5 py-2.5 bg-pasuruan-800 hover:bg-pasuruan-900 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-2">
                                <i class="fa-solid fa-download"></i>
                                <span>Unduh & Buka Berkas PDF Lengkap</span>
                            </a>
                        </div>
                    @else
                        <p class="text-[11px] text-amber-600 font-semibold">
                            Silakan masuk menggunakan akun ASN untuk membuka dokumen lengkap.
                        </p>
                    @endif
                </div>
            </div>

        </div>

    </div>

</div>
@endsection