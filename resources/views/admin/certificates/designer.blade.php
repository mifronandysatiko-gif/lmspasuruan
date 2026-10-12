@extends('layouts.dashboard')

@section('title', 'Visual Certificate Designer - Super Admin')
@section('header_title', 'Visual Coordinate Certificate Designer')

@section('content')
@php
    $coords = $template->layout_coordinates ?? [
        'certificate_number' => ['x' => 420, 'y' => 210, 'font_size' => 15],
        'full_name' => ['x' => 420, 'y' => 295, 'font_size' => 26],
        'nip' => ['x' => 420, 'y' => 330, 'font_size' => 14],
        'training_title' => ['x' => 420, 'y' => 380, 'font_size' => 18],
        'jp' => ['x' => 420, 'y' => 415, 'font_size' => 14],
        'issued_at' => ['x' => 630, 'y' => 480, 'font_size' => 13],
        'qr_code' => ['x' => 150, 'y' => 460, 'size' => 90],
    ];

    $hasBgFile = false;
    $bgAssetUrl = '';
    if ($template && $template->background_path) {
        $storagePath = storage_path('app/public/' . $template->background_path);
        if (file_exists($storagePath)) {
            $hasBgFile = true;
            $bgAssetUrl = asset('storage/' . $template->background_path);
        }
    }
@endphp

<style>
    .font-serif-cert {
        font-family: 'Playfair Display', Georgia, serif;
    }
    .font-title-cert {
        font-family: 'Cinzel', serif;
    }
    .cert-print-num {
        font-size: 12pt;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }
    .cert-print-name-label {
        font-size: 11pt;
        color: #475569;
        font-weight: 500;
        line-height: 1.2;
        margin-bottom: 2px;
    }
    .cert-print-name {
        font-size: 22pt;
        font-weight: 800;
        color: #451a03;
        font-family: 'Playfair Display', Georgia, serif;
        white-space: nowrap;
        line-height: 1.2;
        margin-bottom: 2px;
    }
    .cert-print-nip {
        font-size: 11.5pt;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        color: #334155;
        white-space: nowrap;
        line-height: 1.2;
    }
    .cert-print-title-label {
        font-size: 11pt;
        color: #475569;
        font-weight: 500;
        line-height: 1.2;
        margin-bottom: 3px;
    }
    .cert-print-title {
        font-size: 15.5pt;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.25;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    .cert-print-organizer {
        font-size: 11pt;
        color: #334155;
        font-weight: 500;
        line-height: 1.2;
        margin-top: 3px;
    }
    .cert-print-jp {
        font-size: 12pt;
        font-weight: 700;
        color: #92400e;
        line-height: 1.2;
        margin-top: 2px;
    }
    .cert-print-qr-img {
        width: 60px;
        height: 60px;
        object-fit: contain;
    }
    .cert-print-qr-text {
        font-size: 8pt;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        line-height: 1.25;
    }
    .cert-print-sig-text {
        font-size: 10pt;
        color: #1e293b;
        line-height: 1.3;
    }
    .cert-print-sig-img {
        height: 48px;
        width: auto;
        object-fit: contain;
        margin-left: auto;
        margin-right: auto;
    }
    .cert-print-sig-name {
        font-size: 12pt;
        font-weight: 700;
        font-family: 'Playfair Display', Georgia, serif;
        color: #0f172a;
        text-decoration: underline;
        line-height: 1.2;
    }
    .cert-print-sig-nip {
        font-size: 9.5pt;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        color: #334155;
        line-height: 1.2;
    }
    .cert-print-tte-badge {
        font-size: 8pt;
        font-weight: 700;
        color: #065f46;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        margin-top: 2px;
    }
</style>

<div class="space-y-6" x-data="{
    activePage: '{{ session('active_page', 'page1') }}',
    numX: {{ $coords['certificate_number']['x'] ?? 420 }},
    numY: {{ $coords['certificate_number']['y'] ?? 205 }},
    nameX: {{ $coords['full_name']['x'] ?? 420 }},
    nameY: {{ $coords['full_name']['y'] ?? 290 }},
    titleX: {{ $coords['training_title']['x'] ?? 420 }},
    titleY: {{ $coords['training_title']['y'] ?? 375 }},
    qrX: {{ $coords['qr_code']['x'] ?? 160 }},
    qrY: {{ $coords['qr_code']['y'] ?? 480 }},
    issueX: {{ $coords['issued_at']['x'] ?? 680 }},
    issueY: {{ $coords['issued_at']['y'] ?? 480 }},
    selectedElement: 'name',
    canvasScale: 0.65,
    isDragging: false,
    dragStartX: 0,
    dragStartY: 0,
    dragInitialElemX: 0,
    dragInitialElemY: 0,
    init() {
        this.$nextTick(() => {
            this.updateScale();
        });
        window.addEventListener('resize', () => {
            this.updateScale();
        });
    },
    updateScale() {
        const wrapper = this.$refs.canvasWrapper || document.getElementById('designer-canvas-wrapper');
        if (wrapper) {
            const w = wrapper.clientWidth;
            if (w > 0) {
                this.canvasScale = w / 1122.52;
            }
        }
    },
    getCurrentX() {
        if (this.selectedElement === 'number') return this.numX;
        if (this.selectedElement === 'name') return this.nameX;
        if (this.selectedElement === 'title') return this.titleX;
        if (this.selectedElement === 'qr') return this.qrX;
        if (this.selectedElement === 'issue') return this.issueX;
        return 420;
    },
    getCurrentY() {
        if (this.selectedElement === 'number') return this.numY;
        if (this.selectedElement === 'name') return this.nameY;
        if (this.selectedElement === 'title') return this.titleY;
        if (this.selectedElement === 'qr') return this.qrY;
        if (this.selectedElement === 'issue') return this.issueY;
        return 290;
    },
    startDrag(elemKey, event) {
        this.selectedElement = elemKey;
        this.isDragging = true;
        this.dragStartX = event.clientX;
        this.dragStartY = event.clientY;
        this.dragInitialElemX = this.getCurrentX();
        this.dragInitialElemY = this.getCurrentY();

        const onMouseMove = (e) => {
            if (!this.isDragging) return;
            const deltaPixelX = (e.clientX - this.dragStartX) / this.canvasScale;
            const deltaPixelY = (e.clientY - this.dragStartY) / this.canvasScale;

            const deltaPtX = Math.round(deltaPixelX * (842 / 1122.52));
            const deltaPtY = Math.round(deltaPixelY * (595 / 793.70));

            const newX = Math.max(0, Math.min(842, this.dragInitialElemX + deltaPtX));
            const newY = Math.max(0, Math.min(595, this.dragInitialElemY + deltaPtY));

            if (this.selectedElement === 'number') { this.numX = newX; this.numY = newY; }
            else if (this.selectedElement === 'name') { this.nameX = newX; this.nameY = newY; }
            else if (this.selectedElement === 'title') { this.titleX = newX; this.titleY = newY; }
            else if (this.selectedElement === 'qr') { this.qrX = newX; this.qrY = newY; }
            else if (this.selectedElement === 'issue') { this.issueX = newX; this.issueY = newY; }
        };

        const onMouseUp = () => {
            this.isDragging = false;
            window.removeEventListener('mousemove', onMouseMove);
            window.removeEventListener('mouseup', onMouseUp);
        };

        window.addEventListener('mousemove', onMouseMove);
        window.addEventListener('mouseup', onMouseUp);
    },
    uploadModalOpen: false,
    uploadLampiranModalOpen: false,
    specimenModalOpen: false,
    createTemplateModalOpen: false,
    targetModalOpen: false,
    newTemplateTargetType: 'default',
    editTargetType: '{{ $template->is_default ? "default" : ($template->webinar_id ? "webinar" : ($template->course_id ? "course" : "default")) }}',
    uploadedFilePreview: null,
    uploadedLampiranFilePreview: null,
    savedBackgroundUrl: '{{ $bgAssetUrl }}',
    savedLampiranBgUrl: '{{ $template?->lampiran_background_url }}',
    savedSigUrl: '{{ $template?->signature_url }}',
    savedTteUrl: '{{ $template?->tte_barcode_url }}',
    sigPreview: null,
    ttePreview: null,
    handleLampiranFileSelect(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.uploadedLampiranFilePreview = e.target.result;
                this.activePage = 'page2';
            };
            reader.readAsDataURL(file);
        }
    },
    handleFileSelect(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.uploadedFilePreview = e.target.result;
                this.activePage = 'page1';
            };
            reader.readAsDataURL(file);
        }
    },
    handleSpecimenFile(event, type) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                if (type === 'sig') this.sigPreview = e.target.result;
                if (type === 'tte') this.ttePreview = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    },
    hasActiveBg() {
        return !!(this.uploadedFilePreview || this.savedBackgroundUrl);
    },
    hasActiveLampiranBg() {
        return !!(this.uploadedLampiranFilePreview || this.savedLampiranBgUrl);
    },
    getCanvasBgStyle() {
        const bg = this.uploadedFilePreview || this.savedBackgroundUrl;
        if (bg) {
            return `background-image: url('${bg}'); background-size: 100% 100%; background-repeat: no-repeat; background-position: center;`;
        }
        return '';
    },
    getLampiranCanvasBgStyle() {
        const bg = this.uploadedLampiranFilePreview || this.savedLampiranBgUrl;
        if (bg) {
            return `background-image: url('${bg}'); background-size: 100% 100%; background-repeat: no-repeat; background-position: center;`;
        }
        return '';
    },
    resetToDefault() {
        this.numX = 420; this.numY = 205;
        this.nameX = 420; this.nameY = 290;
        this.titleX = 420; this.titleY = 375;
        this.qrX = 160; this.qrY = 480;
        this.issueX = 680; this.issueY = 480;
    }
}">


    @if(isset($errors) && $errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm space-y-1 shadow-xs">
            <div class="font-bold flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                <span>Terjadi kesalahan pada upload blanko:</span>
            </div>
            <ul class="list-disc list-inside text-xs space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Top Header -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-amber-600 uppercase tracking-wider mb-1">
                <i class="fa-solid fa-compass-drafting"></i> Visual Coordinate Mapping & Template Multi-Kegiatan
            </div>
            <h2 class="text-xl font-extrabold text-slate-900">Desainer Blanko E-Sertifikat & Koordinat Barcode</h2>
            <p class="text-xs text-slate-500">Atur template khusus untuk webinar/pelatihan tertentu atau template default seluruh kegiatan ASN.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <a href="{{ route('admin.certificates.export') }}" class="px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5" title="Unduh Rekap Seluruh E-Sertifikat Resmi yang Diterbitkan ke Excel">
                <i class="fa-solid fa-file-excel text-emerald-200"></i>
                <span class="hidden sm:inline">Tarikan Data Excel</span>
            </a>
            <button type="button" @click="createTemplateModalOpen = true" class="px-3.5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                <i class="fa-solid fa-plus-circle text-amber-300"></i>
                <span>+ Buat Template Baru</span>
            </button>
            <button type="button" @click="targetModalOpen = true" class="px-3.5 py-2.5 bg-indigo-700 hover:bg-indigo-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5" title="Atur Peruntukan Template (Default / Khusus Webinar / Khusus Pelatihan)">
                <i class="fa-solid fa-sliders text-indigo-300"></i>
                <span>Atur Peruntukan Template</span>
            </button>
            @if($templates->count() > 1)
                <form action="{{ route('admin.certificates.delete_template', $template->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus template ini? Kegiatan yang menggunakan template ini akan dialihkan ke template default.');" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-2.5 text-rose-600 hover:text-white hover:bg-rose-600 bg-rose-50 border border-rose-200 font-bold text-xs rounded-xl transition-colors" title="Hapus Template Ini">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Template Switcher Bar -->
    <div class="bg-gradient-to-r from-pasuruan-900 to-slate-900 text-white p-4 rounded-2xl border border-pasuruan-800 shadow-md flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-400 text-slate-950 flex items-center justify-center text-lg font-black shrink-0">
                <i class="fa-solid fa-stamp"></i>
            </div>
            <div>
                <div class="text-[11px] font-bold text-amber-300 uppercase tracking-widest flex items-center gap-1.5">
                    <span>Pilih Template yang Sedang Diedit:</span>
                </div>
                <div class="flex items-center gap-2 mt-1">
                    <select onchange="window.location.href = '{{ route('admin.certificates.designer') }}?template_id=' + this.value"
                            class="px-3 py-1.5 bg-slate-800/90 border border-slate-700 rounded-xl text-xs font-bold text-white focus:outline-none focus:ring-2 focus:ring-amber-400 cursor-pointer">
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}" {{ $tpl->id === $template->id ? 'selected' : '' }}>
                                {{ $tpl->name }} — [{{ $tpl->target_badge }}]
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs">
            <div class="px-3 py-1.5 rounded-xl bg-white/10 border border-white/15 flex items-center gap-2">
                <span class="text-slate-300">Peruntukan:</span>
                @if($template->is_default)
                    <span class="px-2 py-0.5 rounded-md font-bold text-[10px] bg-amber-400 text-slate-900">
                        ⭐ DEFAULT (Semua Kegiatan)
                    </span>
                @elseif($template->webinar_id && $template->webinar)
                    <span class="px-2 py-0.5 rounded-md font-bold text-[10px] bg-sky-400 text-slate-900" title="{{ $template->webinar->title }}">
                        🎯 Khusus Webinar: {{ Str::limit($template->webinar->title, 25) }}
                    </span>
                @elseif($template->course_id && $template->course)
                    <span class="px-2 py-0.5 rounded-md font-bold text-[10px] bg-purple-300 text-slate-900" title="{{ $template->course->title }}">
                        🎓 Khusus Pelatihan: {{ Str::limit($template->course->title, 25) }}
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded-md font-bold text-[10px] bg-slate-300 text-slate-900">
                        Umum
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="resetToDefault()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs rounded-xl transition-colors" title="Reset koordinat ke posisi standar tengah">
                    <i class="fa-solid fa-rotate-left mr-1"></i> Reset Posisi
                </button>
                <button type="button" @click="specimenModalOpen = true" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-xs rounded-xl transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-file-signature"></i>
                    <span>Nomor & TTD</span>
                </button>
                <button type="button" @click="uploadModalOpen = true" class="px-3 py-1.5 bg-pasuruan-700 hover:bg-pasuruan-600 text-white font-bold text-xs rounded-xl transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-cloud-arrow-up text-amber-300"></i>
                    <span>Blanko Hal. 1</span>
                </button>
                <button type="button" @click="uploadLampiranModalOpen = true" class="px-3 py-1.5 bg-sky-700 hover:bg-sky-600 text-white font-bold text-xs rounded-xl transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-file-invoice text-sky-300"></i>
                    <span>Blanko Hal. 2</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Designer Workspace Grid: Interactive Canvas Preview & Coordinate Sliders -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Interactive Preview Box (Canvas Representation) -->
        <div class="lg:col-span-8 space-y-4">

            <!-- Page Switcher Tabs: Halaman 1 (Muka) vs Halaman 2 (Lampiran) -->
            <div class="bg-white p-2 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl">
                    <button type="button" 
                            @click="activePage = 'page1'"
                            :class="activePage === 'page1' ? 'bg-pasuruan-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60'"
                            class="px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2">
                        <i class="fa-solid fa-certificate" :class="activePage === 'page1' ? 'text-amber-400' : 'text-slate-400'"></i>
                        <span>Halaman 1: Muka Sertifikat</span>
                    </button>
                    <button type="button" 
                            @click="activePage = 'page2'"
                            :class="activePage === 'page2' ? 'bg-pasuruan-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60'"
                            class="px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2">
                        <i class="fa-solid fa-file-invoice" :class="activePage === 'page2' ? 'text-sky-300' : 'text-slate-400'"></i>
                        <span>Halaman 2: Lampiran Transkrip & JP</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold"
                              :class="activePage === 'page2' ? 'bg-sky-400 text-slate-950' : 'bg-slate-200 text-slate-700'">
                            Preview
                        </span>
                    </button>
                </div>
                
                <div class="flex items-center gap-2 text-xs pr-2">
                    <template x-if="activePage === 'page1'">
                        <span class="text-slate-500 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Mode: <strong class="text-slate-800">Sumbu Koordinat Teks & QR</strong></span>
                        </span>
                    </template>
                    <template x-if="activePage === 'page2'">
                        <span class="text-slate-500 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-sky-500 animate-pulse"></span>
                            <span>Mode: <strong class="text-slate-800">Pratinjau Tata Letak Kurikulum & TTE</strong></span>
                        </span>
                    </template>
                </div>
            </div>

            <div class="bg-slate-800 p-3 rounded-2xl shadow-lg border border-slate-700">
                <div class="flex items-center justify-between text-xs text-slate-300 pb-2 px-2 border-b border-slate-700/80 mb-3">
                    <span class="font-mono flex items-center gap-2">
                        <i class="fa-solid fa-expand text-amber-400"></i>
                        <span x-text="activePage === 'page1' ? 'Kanvas Interaktif Muka (Sumbu X: 0 - 842, Sumbu Y: 0 - 595)' : 'Kanvas Pratinjau Lampiran Kurikulum & Transkrip (A4 Landscape)'"></span>
                    </span>
                    <div class="flex items-center gap-2">
                        <template x-if="activePage === 'page1'">
                            <div class="flex items-center gap-2">
                                <span class="text-amber-400 font-bold text-[11px]" x-text="hasActiveBg() ? 'Latar Belakang Gambar Terpasang' : 'Latar Belakang Vektor Default'"></span>
                                <span class="text-xs px-2 py-0.5 rounded bg-slate-700 text-slate-300 font-mono">
                                    Elemen Aktif: <strong class="text-white uppercase" x-text="selectedElement"></strong>
                                </span>
                            </div>
                        </template>
                        <template x-if="activePage === 'page2'">
                            <div class="flex items-center gap-2">
                                <span class="text-sky-300 font-bold text-[11px]" x-text="hasActiveLampiranBg() ? 'Blanko Lampiran Gambar Terpasang' : 'Template Vektor Standar BKPSDM'"></span>
                                <button type="button" @click="uploadLampiranModalOpen = true" class="text-[11px] px-2.5 py-0.5 rounded bg-sky-700 hover:bg-sky-600 text-white font-semibold transition-colors flex items-center gap-1.5">
                                    <i class="fa-solid fa-cloud-arrow-up text-amber-300"></i> Ganti Blanko Hal 2
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Canvas Representation Container: Panggung Koordinat Presisi Absolut (100% Identik dengan Print A4) -->
                <div x-show="activePage === 'page1'"
                     x-ref="canvasWrapper"
                     id="designer-canvas-wrapper"
                     class="relative w-full rounded-xl overflow-hidden select-none shadow-inner bg-slate-900 flex items-center justify-center transition-all duration-300"
                     :style="'height: ' + (793.70 * canvasScale) + 'px;'">
                    
                    <!-- Virtual Master A4 Canvas (1122.52px x 793.70px) -->
                    <div id="designer-cert-canvas"
                         class="absolute top-0 left-0 origin-top-left overflow-hidden bg-white select-none"
                         :style="'width: 1122.52px; height: 793.70px; transform: scale(' + canvasScale + '); ' + getCanvasBgStyle()">
                        
                        <!-- Watermark Background (only if no custom image) -->
                        <template x-if="!hasActiveBg()">
                            <div class="absolute inset-0 flex items-center justify-center opacity-5 pointer-events-none">
                                <i class="fa-solid fa-graduation-cap text-9xl text-amber-900"></i>
                            </div>
                        </template>

                        <!-- Header Blanko Statis (Ditampilkan jika tidak menggunakan background gambar custom) -->
                        <template x-if="!hasActiveBg()">
                            <div class="absolute top-8 inset-x-0 text-center space-y-1 pointer-events-none">
                                <div class="text-sm font-extrabold text-pasuruan-900 uppercase tracking-widest">
                                    Pemerintah Kabupaten Pasuruan
                                </div>
                                <div class="text-xs text-slate-600 uppercase tracking-wider font-semibold">
                                    Badan Kepegawaian dan Pengembangan Sumber Daya Manusia
                                </div>
                                <h3 class="text-xl font-title-cert font-extrabold text-slate-900 uppercase tracking-wider pt-2">
                                    SERTIFIKAT KELULUSAN
                                </h3>
                            </div>
                        </template>

                        <!-- Crosshair Alignment Guide Lines (Sumbu X & Y) -->
                        <template x-if="selectedElement">
                            <div>
                                <!-- Garis Vertikal (Sumbu X) -->
                                <div class="absolute top-0 bottom-0 pointer-events-none border-l-2 border-dashed border-red-500/80 z-20"
                                     :style="'left: ' + ((getCurrentX() / 842) * 100).toFixed(2) + '%;'"></div>
                                <!-- Garis Horizontal (Sumbu Y) -->
                                <div class="absolute left-0 right-0 pointer-events-none border-t-2 border-dashed border-red-500/80 z-20"
                                     :style="'top: ' + ((getCurrentY() / 595) * 100).toFixed(2) + '%;'"></div>
                            </div>
                        </template>

                        <!-- 1. Nomor Sertifikat Dinamis (numX, numY) -->
                        <div class="absolute -translate-x-1/2 -translate-y-1/2 cursor-grab active:cursor-grabbing select-none z-10 whitespace-nowrap transition-none group"
                             :style="'left: ' + ((numX / 842) * 100).toFixed(2) + '%; top: ' + ((numY / 595) * 100).toFixed(2) + '%;'"
                             @mousedown.prevent="startDrag('number', $event)"
                             @click="selectedElement = 'number'">
                            <div class="cert-print-num text-center whitespace-nowrap px-2 py-0.5"
                                 :class="selectedElement === 'number' ? 'ring-2 ring-blue-500 ring-offset-2 rounded' : 'group-hover:ring-1 group-hover:ring-blue-400 group-hover:ring-offset-1 rounded'">
                                Nomor: {{ $template?->sample_certificate_number ?? '893.3/0412/BKPSDM-PAS/2026' }}
                            </div>
                            <!-- Floating Badge -->
                            <div x-show="selectedElement === 'number'" x-cloak
                                 class="absolute -top-7 left-1/2 -translate-x-1/2 px-2 py-0.5 rounded bg-blue-600 text-white font-mono font-bold text-[10px] shadow-md pointer-events-none whitespace-nowrap z-50 flex items-center gap-1">
                                <span>No. [X:<span x-text="numX"></span>, Y:<span x-text="numY"></span>]</span>
                            </div>
                        </div>

                        <!-- 2. Nama Peserta Dinamis (nameX, nameY) -->
                        <div class="absolute -translate-x-1/2 -translate-y-1/2 cursor-grab active:cursor-grabbing select-none z-10 w-[85%] text-center transition-none group"
                             :style="'left: ' + ((nameX / 842) * 100).toFixed(2) + '%; top: ' + ((nameY / 595) * 100).toFixed(2) + '%;'"
                             @mousedown.prevent="startDrag('name', $event)"
                             @click="selectedElement = 'name'">
                            <div class="px-2 py-1"
                                 :class="selectedElement === 'name' ? 'ring-2 ring-emerald-500 ring-offset-2 rounded-lg' : 'group-hover:ring-1 group-hover:ring-emerald-400 group-hover:ring-offset-1 rounded-lg'">
                                <p class="cert-print-name-label">Diberikan Kepada:</p>
                                <h2 class="cert-print-name">
                                    Rahmat Hidayat, S.Kom
                                </h2>
                                <p class="cert-print-nip">
                                    NIP. 19920817 201903 1 008 • Diskominfo Pasuruan
                                </p>
                            </div>
                            <!-- Floating Badge -->
                            <div x-show="selectedElement === 'name'" x-cloak
                                 class="absolute -top-7 left-1/2 -translate-x-1/2 px-2.5 py-0.5 rounded bg-emerald-600 text-white font-mono font-bold text-[10px] shadow-md pointer-events-none whitespace-nowrap z-50 flex items-center gap-1">
                                <span>Nama [X:<span x-text="nameX"></span>, Y:<span x-text="nameY"></span>]</span>
                            </div>
                        </div>

                        <!-- 3. Judul Pelatihan & Total JP Dinamis (titleX, titleY) -->
                        <div class="absolute -translate-x-1/2 -translate-y-1/2 cursor-grab active:cursor-grabbing select-none z-10 w-[85%] text-center transition-none group"
                             :style="'left: ' + ((titleX / 842) * 100).toFixed(2) + '%; top: ' + ((titleY / 595) * 100).toFixed(2) + '%;'"
                             @mousedown.prevent="startDrag('title', $event)"
                             @click="selectedElement = 'title'">
                            <div class="px-2 py-1"
                                 :class="selectedElement === 'title' ? 'ring-2 ring-amber-500 ring-offset-2 rounded-lg' : 'group-hover:ring-1 group-hover:ring-amber-400 group-hover:ring-offset-1 rounded-lg'">
                                <p class="cert-print-title-label">Telah Mengikuti dan Dinyatakan <strong class="text-emerald-700 font-bold">LULUS</strong> Pada:</p>
                                <h4 class="cert-print-title">
                                    Bimtek Tata Kelola SPBE & Keamanan Siber Pasuruan
                                </h4>
                                <p class="cert-print-organizer">
                                    Diselenggarakan oleh <strong class="font-bold text-slate-900">Bidang Pengembangan Kompetensi BKPSDM</strong>
                                </p>
                                <p class="cert-print-jp">
                                    Akumulasi Jam Pelajaran: 4 JP
                                </p>
                            </div>
                            <!-- Floating Badge -->
                            <div x-show="selectedElement === 'title'" x-cloak
                                 class="absolute -top-7 left-1/2 -translate-x-1/2 px-2.5 py-0.5 rounded bg-amber-600 text-white font-mono font-bold text-[10px] shadow-md pointer-events-none whitespace-nowrap z-50 flex items-center gap-1">
                                <span>Judul [X:<span x-text="titleX"></span>, Y:<span x-text="titleY"></span>]</span>
                            </div>
                        </div>

                        <!-- 4. QR Code Barcode Validasi (qrX, qrY) -->
                        <div class="absolute -translate-x-1/2 -translate-y-1/2 cursor-grab active:cursor-grabbing select-none z-10 transition-none group"
                             :style="'left: ' + ((qrX / 842) * 100).toFixed(2) + '%; top: ' + ((qrY / 595) * 100).toFixed(2) + '%;'"
                             @mousedown.prevent="startDrag('qr', $event)"
                             @click="selectedElement = 'qr'">
                            <div class="flex items-center gap-2.5 bg-transparent p-1 border-0 shadow-none"
                                 :class="selectedElement === 'qr' ? 'ring-2 ring-purple-500 ring-offset-2 rounded-lg' : 'group-hover:ring-1 group-hover:ring-purple-400 group-hover:ring-offset-1 rounded-lg'">
                                <div class="bg-transparent p-0 border-0 shadow-none shrink-0">
                                    <img src="{{ $template?->sample_qr_url }}" class="cert-print-qr-img" alt="QR Barcode Validasi">
                                </div>
                                <div class="cert-print-qr-text text-left space-y-0.5">
                                    <div class="flex items-center gap-1 text-emerald-800 font-bold">
                                        <i class="fa-solid fa-shield-check text-emerald-600"></i>
                                        <span>VALIDASI RESMI SERTIFIKAT</span>
                                    </div>
                                    <div class="text-slate-600 font-medium">Pindai / Kunjungi Link:</div>
                                    <div class="font-bold text-slate-900 truncate max-w-[130px]">
                                        {{ request()?->getHost() ?? 'lms.pasuruankab.go.id' }}/validasi
                                    </div>
                                    <div class="text-slate-600 font-medium pt-0.5">Kode Verifikasi:</div>
                                    <div class="font-bold text-slate-900 font-mono select-all tracking-tight">
                                        {{ $template?->sample_verification_code }}
                                    </div>
                                </div>
                            </div>
                            <!-- Floating Badge -->
                            <div x-show="selectedElement === 'qr'" x-cloak
                                 class="absolute -top-7 left-1/2 -translate-x-1/2 px-2.5 py-0.5 rounded bg-purple-600 text-white font-mono font-bold text-[10px] shadow-md pointer-events-none whitespace-nowrap z-50 flex items-center gap-1">
                                <span>QR [X:<span x-text="qrX"></span>, Y:<span x-text="qrY"></span>]</span>
                            </div>
                        </div>

                        <!-- 5. Tanda Tangan Pejabat & Tanggal Terbit (issueX, issueY) -->
                        <div class="absolute -translate-x-1/2 -translate-y-1/2 cursor-grab active:cursor-grabbing select-none z-10 text-center transition-none group"
                             :style="'left: ' + ((issueX / 842) * 100).toFixed(2) + '%; top: ' + ((issueY / 595) * 100).toFixed(2) + '%;'"
                             @mousedown.prevent="startDrag('issue', $event)"
                             @click="selectedElement = 'issue'">
                            <div class="cert-print-sig-text text-center space-y-0.5 bg-transparent p-1 border-0 shadow-none"
                                 :class="selectedElement === 'issue' ? 'ring-2 ring-sky-500 ring-offset-2 rounded-lg' : 'group-hover:ring-1 group-hover:ring-sky-400 group-hover:ring-offset-1 rounded-lg'">
                                <div>{{ $template?->issue_city ?? 'Bangil, Pasuruan' }}, {{ now()->translatedFormat('d F Y') }}</div>
                                <div class="font-bold text-slate-900">{{ $template?->signer_position ?? 'Kepala BKPSDM Pasuruan' }}</div>
                                
                                <div class="py-0.5 my-0.5 flex items-center justify-center min-h-[44px]">
                                    <template x-if="sigPreview || savedSigUrl">
                                        <img :src="sigPreview || savedSigUrl" class="cert-print-sig-img" alt="Tanda Tangan Pejabat">
                                    </template>
                                    <template x-if="!(sigPreview || savedSigUrl)">
                                        @if(file_exists(public_path('storage/specimens/spesimen_ttd_pejabat.png')))
                                            <img src="{{ asset('storage/specimens/spesimen_ttd_pejabat.png') }}" class="cert-print-sig-img" alt="Tanda Tangan Pejabat">
                                        @else
                                            <div class="h-[48px] flex items-center justify-center text-emerald-700 font-bold text-xs italic">
                                                [Spesimen TTD Pejabat]
                                            </div>
                                        @endif
                                    </template>
                                </div>

                                <div class="cert-print-sig-name">
                                    {{ $template?->signer_name ?? 'Dr. H. Akhmad Khasani, M.Si' }}
                                </div>
                                @if($template?->signer_nip)
                                    <div class="cert-print-sig-nip">
                                        NIP. {{ $template->signer_nip }}
                                    </div>
                                @endif
                                <div class="cert-print-tte-badge">
                                    <i class="fa-solid fa-shield-check text-emerald-600"></i>
                                </div>
                            </div>
                            <!-- Floating Badge -->
                            <div x-show="selectedElement === 'issue'" x-cloak
                                 class="absolute -top-7 left-1/2 -translate-x-1/2 px-2.5 py-0.5 rounded bg-sky-600 text-white font-mono font-bold text-[10px] shadow-md pointer-events-none whitespace-nowrap z-50 flex items-center gap-1">
                                <span>TTD [X:<span x-text="issueX"></span>, Y:<span x-text="issueY"></span>]</span>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- =================================================================== -->
                <!-- HALAMAN 2: KANVAS LAMPIRAN (TRANSKRIP SESI & ALOKASI JAM PELAJARAN) -->
                <!-- =================================================================== -->
                <div x-show="activePage === 'page2'" x-cloak
                     class="relative w-full rounded-xl overflow-hidden select-none shadow-inner bg-slate-900 flex items-center justify-center transition-all duration-300"
                     :style="'height: ' + (793.70 * canvasScale) + 'px;'">
                    
                    <div class="absolute top-0 left-0 origin-top-left overflow-hidden bg-white select-none p-16 flex flex-col justify-center"
                         :style="'width: 1122.52px; height: 793.70px; transform: scale(' + canvasScale + '); ' + getLampiranCanvasBgStyle()">
                        
                        <div class="w-full relative z-10">
                            <div class="text-center pb-5">
                                <h3 class="text-lg font-extrabold uppercase tracking-wider text-slate-900 font-title-cert">
                                    TRANSKRIP MATERI & ALOKASI JAM PELAJARAN (JP)
                                </h3>
                                <p class="text-xs text-slate-600 mt-1">Lampiran Sertifikat Kelulusan Nomor: <span class="font-mono font-bold">{{ $template?->sample_certificate_number ?? '893.3/0412/BKPSDM-PAS/2026' }}</span></p>
                            </div>

                            <table class="w-full text-xs border-2 border-slate-800 border-collapse bg-white/95">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-900 font-bold uppercase text-[11px] tracking-wider border-b-2 border-slate-800">
                                        <th class="border border-slate-400 px-3 py-2.5 text-center w-12">No</th>
                                        <th class="border border-slate-400 px-4 py-2.5 text-left">Mata Pelatihan / Sesi Pembelajaran</th>
                                        <th class="border border-slate-400 px-3 py-2.5 text-center w-32">Alokasi (JP)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="border border-slate-300 px-3 py-2 text-center font-bold text-slate-600">1</td>
                                        <td class="border border-slate-300 px-4 py-2 font-medium text-slate-900">Pembekalan Orientasi Kepemimpinan & Etika Pelayanan Publik ASN</td>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-bold text-slate-800">6 JP</td>
                                    </tr>
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="border border-slate-300 px-3 py-2 text-center font-bold text-slate-600">2</td>
                                        <td class="border border-slate-300 px-4 py-2 font-medium text-slate-900">Manajemen Kinerja Berbasis Digital & Pemanfaatan Data Terpadu</td>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-bold text-slate-800">8 JP</td>
                                    </tr>
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="border border-slate-300 px-3 py-2 text-center font-bold text-slate-600">3</td>
                                        <td class="border border-slate-300 px-4 py-2 font-medium text-slate-900">Inovasi Layanan & Kolaborasi Antar Lembaga (Agile Governance)</td>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-bold text-slate-800">10 JP</td>
                                    </tr>
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="border border-slate-300 px-3 py-2 text-center font-bold text-slate-600">4</td>
                                        <td class="border border-slate-300 px-4 py-2 font-medium text-slate-900">Studi Kasus Implementasi, Evaluasi Tindak Lanjut & Post-Test</td>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-bold text-slate-800">8 JP</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-amber-50/90 font-extrabold text-slate-900 border-t-2 border-slate-800">
                                        <td colspan="2" class="border border-slate-400 px-4 py-2.5 text-right uppercase tracking-wider text-xs">
                                            Total Alokasi Jam Pelajaran (JP)
                                        </td>
                                        <td class="border border-slate-400 px-3 py-2.5 text-center font-black text-amber-900 text-xs">
                                            32 JP
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>

                            <div class="pt-6 flex items-center justify-between text-xs text-slate-500">
                                <div>Pemerintah Kabupaten Pasuruan • BKPSDM</div>
                                <div>Dokumen Resmi Sah Terbitan Sistem LMS</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Bottom helper banner -->
            <div class="flex items-center justify-between text-xs text-slate-500 bg-slate-50 p-3 rounded-xl border border-slate-200">
                <template x-if="activePage === 'page1'">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-arrows-up-down-left-right text-pasuruan-700"></i>
                        <span>Klik elemen pada kanvas muka atau geser slider di panel kanan untuk melihat pergeseran posisi seketika (*real-time*).</span>
                    </div>
                </template>
                <template x-if="activePage === 'page2'">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-file-circle-check text-sky-700"></i>
                        <span>Pratinjau Halaman 2 tampil polos khusus memuat tabel materi pembelajaran & alokasi JP tanpa tulisan kop, identitas, barcode, atau tanda tangan.</span>
                    </div>
                </template>
                <span class="font-mono text-[11px] text-slate-400 hidden sm:inline" x-text="activePage === 'page1' ? 'Unit: Points / Pixels' : 'Format: A4 Landscape'"></span>
            </div>
        </div>
        <!-- Right Column: Coordinates (Hal. 1) & Lampiran Settings (Hal. 2) -->
        <div class="lg:col-span-4 space-y-5">

            <!-- ============================================== -->
            <!-- HALAMAN 1: PANEL SUMBU KOORDINAT (X, Y)       -->
            <!-- ============================================== -->
            <div x-show="activePage === 'page1'" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-5">
                <div class="border-b border-slate-100 pb-3">
                <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-sliders text-pasuruan-700"></i>
                    <span>Panel Sumbu Koordinat (X, Y)</span>
                </h3>
                <p class="text-xs text-slate-500">Geser slider atau ketik angka pixel untuk memindahkan elemen</p>
            </div>

            <form action="{{ route('admin.certificates.save_coordinates') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="template_id" value="{{ $template->id }}">
                <input type="hidden" name="layout_coordinates" :value="JSON.stringify({
                    certificate_number: { x: numX, y: numY, font_size: 15, align: 'center' },
                    full_name: { x: nameX, y: nameY, font_size: 26, align: 'center', font_weight: 'bold' },
                    nip: { x: nameX, y: nameY + 25, font_size: 14, align: 'center' },
                    training_title: { x: titleX, y: titleY, font_size: 18, align: 'center', font_weight: 'bold' },
                    jp: { x: titleX, y: titleY + 30, font_size: 14, align: 'center' },
                    qr_code: { x: qrX, y: qrY, size: 90 },
                    issued_at: { x: issueX, y: issueY, font_size: 13, align: 'center' }
                })">

                <!-- 1. Nama Peserta Coordinates -->
                <div class="p-3 rounded-xl border transition-colors cursor-pointer space-y-2"
                     :class="selectedElement === 'name' ? 'bg-emerald-50/70 border-emerald-300 ring-1 ring-emerald-300' : 'bg-slate-50 border-slate-200'"
                     @click="selectedElement = 'name'">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-800">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span>Nama Lengkap & NIP</span>
                        </span>
                        <span class="text-emerald-700 font-mono text-[11px]">X:<span x-text="nameX"></span>, Y:<span x-text="nameY"></span></span>
                    </div>
                    <div class="space-y-1.5 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-500 w-16">Sumbu X:</span>
                            <input type="range" min="50" max="800" x-model.number="nameX" class="flex-1 accent-emerald-600">
                            <input type="number" min="0" max="842" x-model.number="nameX" class="w-14 px-1.5 py-0.5 text-xs text-right font-mono border border-slate-200 rounded bg-white">
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-500 w-16">Sumbu Y:</span>
                            <input type="range" min="50" max="550" x-model.number="nameY" class="flex-1 accent-emerald-600">
                            <input type="number" min="0" max="595" x-model.number="nameY" class="w-14 px-1.5 py-0.5 text-xs text-right font-mono border border-slate-200 rounded bg-white">
                        </div>
                    </div>
                </div>

                <!-- 2. Nomor Sertifikat Coordinates -->
                <div class="p-3 rounded-xl border transition-colors cursor-pointer space-y-2"
                     :class="selectedElement === 'number' ? 'bg-blue-50/70 border-blue-300 ring-1 ring-blue-300' : 'bg-slate-50 border-slate-200'"
                     @click="selectedElement = 'number'">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-800">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                            <span>Nomor Sertifikat</span>
                        </span>
                        <span class="text-blue-700 font-mono text-[11px]">X:<span x-text="numX"></span>, Y:<span x-text="numY"></span></span>
                    </div>
                    <div class="space-y-1.5 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-500 w-16">Sumbu X:</span>
                            <input type="range" min="50" max="800" x-model.number="numX" class="flex-1 accent-blue-600">
                            <input type="number" min="0" max="842" x-model.number="numX" class="w-14 px-1.5 py-0.5 text-xs text-right font-mono border border-slate-200 rounded bg-white">
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-500 w-16">Sumbu Y:</span>
                            <input type="range" min="50" max="550" x-model.number="numY" class="flex-1 accent-blue-600">
                            <input type="number" min="0" max="595" x-model.number="numY" class="w-14 px-1.5 py-0.5 text-xs text-right font-mono border border-slate-200 rounded bg-white">
                        </div>
                    </div>
                </div>

                <!-- 3. Judul Pelatihan & JP Coordinates -->
                <div class="p-3 rounded-xl border transition-colors cursor-pointer space-y-2"
                     :class="selectedElement === 'title' ? 'bg-amber-50/70 border-amber-300 ring-1 ring-amber-300' : 'bg-slate-50 border-slate-200'"
                     @click="selectedElement = 'title'">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-800">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <span>Judul Diklat & JP</span>
                        </span>
                        <span class="text-amber-700 font-mono text-[11px]">X:<span x-text="titleX"></span>, Y:<span x-text="titleY"></span></span>
                    </div>
                    <div class="space-y-1.5 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-500 w-16">Sumbu X:</span>
                            <input type="range" min="50" max="800" x-model.number="titleX" class="flex-1 accent-amber-600">
                            <input type="number" min="0" max="842" x-model.number="titleX" class="w-14 px-1.5 py-0.5 text-xs text-right font-mono border border-slate-200 rounded bg-white">
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-500 w-16">Sumbu Y:</span>
                            <input type="range" min="50" max="550" x-model.number="titleY" class="flex-1 accent-amber-600">
                            <input type="number" min="0" max="595" x-model.number="titleY" class="w-14 px-1.5 py-0.5 text-xs text-right font-mono border border-slate-200 rounded bg-white">
                        </div>
                    </div>
                </div>

                <!-- 4. Posisi Barcode QR Code Validasi -->
                <div class="p-3 rounded-xl border transition-colors cursor-pointer space-y-2"
                     :class="selectedElement === 'qr' ? 'bg-purple-50/70 border-purple-300 ring-1 ring-purple-300' : 'bg-slate-50 border-slate-200'"
                     @click="selectedElement = 'qr'">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-800">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                            <span>Posisi Barcode QR Validasi</span>
                        </span>
                        <span class="text-purple-700 font-mono text-[11px]">X:<span x-text="qrX"></span>, Y:<span x-text="qrY"></span></span>
                    </div>
                    <div class="space-y-1.5 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-500 w-16">Sumbu X:</span>
                            <input type="range" min="50" max="750" x-model.number="qrX" class="flex-1 accent-purple-600">
                            <input type="number" min="0" max="842" x-model.number="qrX" class="w-14 px-1.5 py-0.5 text-xs text-right font-mono border border-slate-200 rounded bg-white">
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-500 w-16">Sumbu Y:</span>
                            <input type="range" min="50" max="550" x-model.number="qrY" class="flex-1 accent-purple-600">
                            <input type="number" min="0" max="595" x-model.number="qrY" class="w-14 px-1.5 py-0.5 text-xs text-right font-mono border border-slate-200 rounded bg-white">
                        </div>
                    </div>
                </div>

                <!-- 5. Tanda Tangan Digital & Tanggal Terbit -->
                <div class="p-3 rounded-xl border transition-colors cursor-pointer space-y-2"
                     :class="selectedElement === 'issue' ? 'bg-sky-50/70 border-sky-300 ring-1 ring-sky-300' : 'bg-slate-50 border-slate-200'"
                     @click="selectedElement = 'issue'">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-800">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span>
                            <span>Tanda Tangan & Tanggal</span>
                        </span>
                        <span class="text-sky-700 font-mono text-[11px]">X:<span x-text="issueX"></span>, Y:<span x-text="issueY"></span></span>
                    </div>
                    <div class="space-y-1.5 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-500 w-16">Sumbu X:</span>
                            <input type="range" min="50" max="800" x-model.number="issueX" class="flex-1 accent-sky-600">
                            <input type="number" min="0" max="842" x-model.number="issueX" class="w-14 px-1.5 py-0.5 text-xs text-right font-mono border border-slate-200 rounded bg-white">
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-500 w-16">Sumbu Y:</span>
                            <input type="range" min="50" max="550" x-model.number="issueY" class="flex-1 accent-sky-600">
                            <input type="number" min="0" max="595" x-model.number="issueY" class="w-14 px-1.5 py-0.5 text-xs text-right font-mono border border-slate-200 rounded bg-white">
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center justify-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan Pemetaan Koordinat ke Basis Data</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- ============================================== -->
        <!-- HALAMAN 2: PANEL PENGATURAN LAMPIRAN SERTIFIKAT -->
        <!-- ============================================== -->
        <div x-show="activePage === 'page2'" x-cloak class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-5">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-file-invoice text-sky-600"></i>
                        <span>Pengaturan Lampiran (Hal. 2)</span>
                    </h3>
                    <p class="text-xs text-slate-500">Transkrip materi sesi diklat, bobot JP & verifikasi</p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono bg-sky-100 text-sky-800">
                    Hal. 2 / 2
                </span>
            </div>

            <!-- Status Blanko Lampiran Background -->
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/70 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800">Latar Belakang Blanko:</span>
                    <template x-if="hasActiveLampiranBg()">
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-emerald-100 text-emerald-800 font-bold flex items-center gap-1">
                            <i class="fa-solid fa-circle-check"></i> Kustom Terpasang
                        </span>
                    </template>
                    <template x-if="!hasActiveLampiranBg()">
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-200 text-slate-700 font-medium">
                            Template Vektor
                        </span>
                    </template>
                </div>

                <!-- Thumbnail pratinjau blanko jika ada -->
                <template x-if="hasActiveLampiranBg()">
                    <div class="relative rounded-lg overflow-hidden border border-slate-200 bg-white aspect-[1.414/1] flex items-center justify-center">
                        <img :src="uploadedLampiranFilePreview || savedLampiranBgUrl" class="w-full h-full object-cover" alt="Preview Blanko Lampiran">
                    </div>
                </template>

                <p class="text-[11px] text-slate-600 leading-relaxed">
                    Anda dapat mengunggah berkas gambar berbingkai resmi sebagai dasar halaman lampiran transkrip materi.
                </p>

                <button type="button" @click="uploadLampiranModalOpen = true" class="w-full py-2.5 bg-sky-700 hover:bg-sky-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center justify-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-up text-amber-300"></i>
                    <span>Unggah / Ganti Blanko Lampiran</span>
                </button>
            </div>

            <!-- Informasi Sinkronisasi Kurikulum & Jam Pelajaran (JP) -->
            <div class="p-4 rounded-xl bg-amber-50/70 border border-amber-200 text-slate-800 space-y-2">
                <div class="flex items-center gap-2 text-xs font-bold text-amber-900">
                    <i class="fa-solid fa-graduation-cap text-amber-700"></i>
                    <span>Otomatisasi Kurikulum Diklat</span>
                </div>
                <ul class="text-[11px] text-slate-700 space-y-1.5 list-disc list-inside">
                    <li><strong>Daftar Mata Pelatihan:</strong> Otomatis diekstrak dari judul bab/sesi pelajaran pada kursus terkait.</li>
                    <li><strong>Bobot JP Terhitung:</strong> Mengacu standar LAN RI (1 JP = 45 Menit) berdasarkan total durasi materi & kuis.</li>
                    <li><strong>Total Akumulasi:</strong> Dijumlahkan otomatis di baris rekapitulasi paling bawah.</li>
                </ul>
            </div>

            <!-- Format Polos Khusus Tabel -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 space-y-2">
                <div class="flex items-center gap-2 text-xs font-bold text-slate-900">
                    <i class="fa-solid fa-table-list text-pasuruan-700"></i>
                    <span>Format Lampiran Polos (Hanya Tabel)</span>
                </div>
                <p class="text-[11px] text-slate-600 leading-relaxed">
                    Halaman lampiran tampil polos khusus menyajikan tabel kurikulum pembelajaran & Jam Pelajaran (JP). Seluruh barcode verifikasi dan tanda tangan pejabat difokuskan pada Halaman 1 (Muka Sertifikat).
                </p>
            </div>

            <!-- Tombol Navigasi Cepat -->
            <div class="pt-2 border-t border-slate-100">
                <button type="button" @click="activePage = 'page1'" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors flex items-center justify-center gap-2">
                    <i class="fa-solid fa-arrow-left text-pasuruan-700"></i>
                    <span>Kembali ke Sumbu Koordinat (Hal. 1)</span>
                </button>
            </div>
        </div>

    </div>

    <!-- MODAL: UNGGAH BLANKO BARU -->
    <div x-show="uploadModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-8 space-y-5 shadow-2xl border border-slate-100 my-auto" @click.away="uploadModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900">Unggah Blanko E-Sertifikat Baru</h3>
                        <p class="text-[11px] text-slate-500">Ganti template gambar latar belakang sertifikat digital BKPSDM</p>
                    </div>
                </div>
                <button type="button" @click="uploadModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('admin.certificates.upload_blanko') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="template_id" value="{{ $template->id }}">

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Template Blanko <span class="text-rose-500">*</span></label>
                    <input type="text" name="template_name" value="{{ $template ? $template->name : 'Blanko Resmi E-Sertifikat BKPSDM 2026' }}" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:ring-2 focus:ring-pasuruan-600 focus:bg-white">
                </div>

                <div class="space-y-2">
                    <label class="block font-bold text-slate-700">Berkas Gambar Blanko (PNG / JPG / WebP) <span class="text-rose-500">*</span></label>
                    
                    <div class="border-2 border-dashed border-slate-300 hover:border-pasuruan-600 rounded-2xl p-6 text-center transition-colors bg-slate-50/60 relative cursor-pointer group">
                        <input type="file" name="blanko_file" accept="image/png,image/jpeg,image/webp" required @change="handleFileSelect($event)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        
                        <template x-if="!uploadedFilePreview">
                            <div class="space-y-2">
                                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto text-xl group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-image"></i>
                                </div>
                                <div>
                                    <span class="font-bold text-slate-800 block text-xs">Klik atau seret file blanko ke sini</span>
                                    <span class="text-[11px] text-slate-400 block mt-0.5">Rekomendasi rasio A4 Landscape (300 DPI ~ 3508 x 2480 px), maks. 10 MB</span>
                                </div>
                            </div>
                        </template>

                        <template x-if="uploadedFilePreview">
                            <div class="space-y-2">
                                <img :src="uploadedFilePreview" class="h-32 object-contain mx-auto rounded-lg shadow-xs border border-slate-200">
                                <span class="text-[11px] text-emerald-600 font-bold block flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-circle-check"></i> Pratinjau gambar siap diterapkan
                                </span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-sky-50 border border-sky-200 text-sky-900 text-[11px] space-y-1">
                    <div class="font-bold flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-info text-sky-600"></i>
                        <span>Kesesuaian Tata Letak Otomatis:</span>
                    </div>
                    <p class="leading-relaxed text-sky-800">
                        Setelah berkas diunggah, canvas di sebelah kiri akan langsung menampilkan blanko baru dan Anda dapat menyesuaikan letak koordinat teks/barcode menggunakan slider X dan Y.
                    </p>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="uploadModalOpen = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-pasuruan-800 hover:bg-pasuruan-900 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up text-amber-300"></i>
                        <span>Unggah & Terapkan Blanko</span>
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- MODAL: UNGGAH BLANKO LAMPIRAN (HALAMAN 2 - TRANSKRIP SESI & JP) -->
    <div x-show="uploadLampiranModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-8 space-y-5 shadow-2xl border border-slate-100 my-auto" @click.away="uploadLampiranModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900">Unggah Blanko Lampiran Sertifikat (Hal. 2)</h3>
                        <p class="text-[11px] text-slate-500">Khusus MOOC, BIMTEK, dan Diklat Kepemimpinan (Transkrip Sesi & JP)</p>
                    </div>
                </div>
                <button type="button" @click="uploadLampiranModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('admin.certificates.upload_lampiran_blanko') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="template_id" value="{{ $template->id }}">

                <div class="space-y-2">
                    <label class="block font-bold text-slate-700">Berkas Gambar Blanko Lampiran (PNG / JPG / WebP) <span class="text-rose-500">*</span></label>
                    
                    <div class="border-2 border-dashed border-indigo-200 hover:border-indigo-600 rounded-2xl p-6 text-center transition-colors bg-indigo-50/40 relative cursor-pointer group">
                        <input type="file" name="lampiran_blanko_file" accept="image/png,image/jpeg,image/webp" required @change="handleLampiranFileSelect($event)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        
                        <template x-if="!uploadedLampiranFilePreview && !savedLampiranBgUrl">
                            <div class="space-y-2">
                                <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center mx-auto text-xl group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-file-image"></i>
                                </div>
                                <div>
                                    <span class="font-bold text-slate-800 block text-xs">Klik atau seret file blanko lampiran ke sini</span>
                                    <span class="text-[11px] text-slate-400 block mt-0.5">Rasio A4 Landscape (300 DPI ~ 3508 x 2480 px), maks. 10 MB</span>
                                </div>
                            </div>
                        </template>

                        <template x-if="uploadedLampiranFilePreview || savedLampiranBgUrl">
                            <div class="space-y-2">
                                <img :src="uploadedLampiranFilePreview || savedLampiranBgUrl" class="h-32 object-contain mx-auto rounded-lg shadow-xs border border-slate-200">
                                <span class="text-[11px] text-indigo-700 font-bold block flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-circle-check text-emerald-600"></i> Blanko lampiran aktif / siap diperbarui
                                </span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 text-[11px] space-y-1.5 leading-relaxed">
                    <div class="font-bold flex items-center gap-1.5 text-slate-900">
                        <i class="fa-solid fa-circle-info text-indigo-600"></i>
                        <span>Ketentuan Halaman Lampiran (Hal. 2):</span>
                    </div>
                    <p>
                        Halaman lampiran otomatis memuat kop instansi, judul pelatihan, tabel rincian sesi mata diklat, alokasi Jam Pelajaran (JP), total JP, serta tanda tangan elektronik pejabat yang berwenang.
                    </p>
                    <p class="text-[10px] text-slate-500">
                        * Penyelenggara juga dapat mengunggah template lampiran khusus per pelatihan pada menu Pengelolaan Pelatihan.
                    </p>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="uploadLampiranModalOpen = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span>Simpan Blanko Lampiran</span>
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- MODAL: PENGATURAN FORMAT NOMOR SURAT & UPLOAD SPESIMEN TTD -->
    <div x-show="specimenModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-2xl w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100 my-auto" @click.away="specimenModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 sticky top-0 bg-white z-10">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-base">
                        <i class="fa-solid fa-file-signature"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900">Pengaturan Nomor Surat & Tanda Tangan Pejabat</h3>
                        <p class="text-[11px] text-slate-500">Sesuaikan pola penomoran sertifikat serta unggah gambar spesimen tanda tangan pejabat</p>
                    </div>
                </div>
                <button type="button" @click="specimenModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('admin.certificates.save_specimens') }}" method="POST" enctype="multipart/form-data" class="space-y-5 text-xs">
                @csrf
                <input type="hidden" name="template_id" value="{{ $template->id }}">

                <!-- Format Penomoran & Nomor Awal -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2 bg-amber-50/50 p-4 rounded-xl border border-amber-200 space-y-2">
                        <label class="block font-bold text-slate-800">
                            Pola / Format Penomoran Surat Sertifikat <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="number_format" value="{{ old('number_format', $template?->number_format ?? '893.3/{NOMOR}/BKPSDM-PAS/{TAHUN}') }}" required class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-mono text-slate-900 font-bold focus:ring-2 focus:ring-amber-500 text-xs">
                        <p class="text-[11px] text-slate-600 leading-relaxed">
                            Gunakan tag <code class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-900 font-bold font-mono">{NOMOR}</code> untuk nomor urut dan <code class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-900 font-bold font-mono">{TAHUN}</code> untuk tahun.
                        </p>
                    </div>

                    <div class="bg-sky-50/50 p-4 rounded-xl border border-sky-200 space-y-2">
                        <label class="block font-bold text-slate-800">
                            Nomor Awal Default <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" min="1" name="start_number" value="{{ old('start_number', $template?->start_number ?? 1) }}" required class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-mono text-slate-900 font-bold focus:ring-2 focus:ring-sky-500 text-xs">
                        <p class="text-[10px] text-slate-500 leading-relaxed">
                            Nomor awal jika pelatihan/webinar tidak mengkustomisasi urutan nomor.
                        </p>
                    </div>
                </div>

                <!-- Info Pejabat Penandatangan -->
                <div class="space-y-3">
                    <h4 class="font-bold text-slate-800 flex items-center gap-1.5 text-xs uppercase tracking-wide">
                        <i class="fa-solid fa-user-tie text-pasuruan-700"></i>
                        <span>Data Pejabat Penandatangan Sertifikat</span>
                    </h4>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap & Gelar <span class="text-rose-500">*</span></label>
                            <input type="text" name="signer_name" value="{{ old('signer_name', $template?->signer_name ?? 'Dr. H. Akhmad Khasani, M.Si') }}" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:bg-white font-medium">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">NIP Pejabat</label>
                            <input type="text" name="signer_nip" value="{{ old('signer_nip', $template?->signer_nip ?? '19710504 199703 1 004') }}" placeholder="197xxxxxxxxxxxxxxx" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:bg-white font-mono">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Jabatan Penandatangan <span class="text-rose-500">*</span></label>
                            <input type="text" name="signer_position" value="{{ old('signer_position', $template?->signer_position ?? 'Kepala Badan Kepegawaian dan Pengembangan SDM') }}" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:bg-white font-medium">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Kota Tempat Penerbitan <span class="text-rose-500">*</span></label>
                            <input type="text" name="issue_city" value="{{ old('issue_city', $template?->issue_city ?? 'Bangil, Pasuruan') }}" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:bg-white font-medium">
                        </div>
                    </div>
                </div>

                <!-- Upload Spesimen Gambar Tanda Tangan -->
                <div class="space-y-3">
                    <h4 class="font-bold text-slate-800 flex items-center gap-1.5 text-xs uppercase tracking-wide">
                        <i class="fa-solid fa-signature text-amber-600"></i>
                        <span>Upload Berkas Gambar Spesimen Tanda Tangan (Stempel Ditiadakan)</span>
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- 1. TTD Pejabat (Gambar Saja) -->
                        <div class="p-4 rounded-xl border-2 border-dashed border-amber-300 bg-amber-50/40 text-center space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-800 block text-xs">Spesimen Tanda Tangan Pejabat</span>
                                <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold">Wajib Gambar</span>
                            </div>
                            <div class="h-28 bg-white rounded-xl border border-slate-200 flex items-center justify-center p-2 relative overflow-hidden shadow-2xs">
                                <template x-if="sigPreview || savedSigUrl">
                                    <img :src="sigPreview || savedSigUrl" class="h-full object-contain mx-auto" alt="Pratinjau TTD">
                                </template>
                                <template x-if="!(sigPreview || savedSigUrl)">
                                    <div class="text-slate-400 space-y-1">
                                        <i class="fa-solid fa-signature text-2xl block"></i>
                                        <span class="text-[10px] block">Belum ada gambar tanda tangan</span>
                                    </div>
                                </template>
                            </div>
                            <input type="file" name="signature_file" accept="image/png,image/webp,image/jpeg" @change="handleSpecimenFile($event, 'sig')" class="text-[11px] w-full text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-600 file:text-white hover:file:bg-amber-700 cursor-pointer">
                            <p class="text-[10px] text-slate-500 text-left">
                                * Disarankan format PNG transparan agar menyatu dengan blanko sertifikat.
                            </p>
                        </div>

                        <!-- 2. Logo / Barcode Badge TTE BSrE (Opsional) -->
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 text-center space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-800 block text-xs">Badge / Barcode TTE BSrE</span>
                                <span class="px-2 py-0.5 rounded bg-slate-200 text-slate-700 text-[10px] font-semibold">Opsional</span>
                            </div>
                            <div class="h-28 bg-white rounded-xl border border-slate-200 flex items-center justify-center p-2 relative overflow-hidden shadow-2xs">
                                <template x-if="ttePreview || savedTteUrl">
                                    <img :src="ttePreview || savedTteUrl" class="h-full object-contain mx-auto" alt="Pratinjau Badge TTE">
                                </template>
                                <template x-if="!(ttePreview || savedTteUrl)">
                                    <div class="text-slate-400 space-y-1">
                                        <i class="fa-solid fa-shield-check text-2xl text-emerald-500 block"></i>
                                        <span class="text-[10px] block">Menggunakan Barcode QR Dinamis</span>
                                    </div>
                                </template>
                            </div>
                            <input type="file" name="tte_barcode_file" accept="image/png,image/webp,image/jpeg" @change="handleSpecimenFile($event, 'tte')" class="text-[11px] w-full text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-200 file:text-slate-800 hover:file:bg-slate-300 cursor-pointer">
                            <p class="text-[10px] text-slate-500 text-left">
                                * Validasi dokumen utama tetap divalidasi langsung oleh QR Barcode berkeamanan tinggi.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="specimenModalOpen = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk text-white"></i>
                        <span>Simpan Pengaturan & Terapkan Tanda Tangan</span>
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- MODAL: BUAT TEMPLATE BARU -->
    <div x-show="createTemplateModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100 my-auto" @click.away="createTemplateModalOpen = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 sticky top-0 bg-white z-10">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-base">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-base text-slate-900">Buat Template Sertifikat Baru</h3>
                            <p class="text-[11px] text-slate-500">Tambahkan varian template e-sertifikat untuk kegiatan spesifik atau template default baru</p>
                        </div>
                    </div>
                    <button type="button" @click="createTemplateModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form action="{{ route('admin.certificates.store_template') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Template <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="Contoh: Template Sertifikat Webinar Series 2026" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-slate-900 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Peruntukan Template <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <label class="p-3 rounded-xl border cursor-pointer transition-all flex flex-col justify-between"
                                   :class="newTemplateTargetType === 'default' ? 'border-emerald-500 bg-emerald-50/50 text-emerald-950 font-bold ring-2 ring-emerald-500/20' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs">⭐ Default</span>
                                    <input type="radio" name="target_type" value="default" x-model="newTemplateTargetType" class="text-emerald-600">
                                </div>
                                <span class="text-[10px] text-slate-500 font-normal">Semua kegiatan umum</span>
                            </label>

                            <label class="p-3 rounded-xl border cursor-pointer transition-all flex flex-col justify-between"
                                   :class="newTemplateTargetType === 'webinar' ? 'border-sky-500 bg-sky-50/50 text-sky-950 font-bold ring-2 ring-sky-500/20' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs">🎯 Khusus Webinar</span>
                                    <input type="radio" name="target_type" value="webinar" x-model="newTemplateTargetType" class="text-sky-600">
                                </div>
                                <span class="text-[10px] text-slate-500 font-normal">Satu webinar terpilih</span>
                            </label>

                            <label class="p-3 rounded-xl border cursor-pointer transition-all flex flex-col justify-between"
                                   :class="newTemplateTargetType === 'course' ? 'border-purple-500 bg-purple-50/50 text-purple-950 font-bold ring-2 ring-purple-500/20' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs">🎓 Khusus Diklat</span>
                                    <input type="radio" name="target_type" value="course" x-model="newTemplateTargetType" class="text-purple-600">
                                </div>
                                <span class="text-[10px] text-slate-500 font-normal">Satu pelatihan terpilih</span>
                            </label>
                        </div>
                    </div>

                    <!-- Pilihan Webinar Jika Target Webinar -->
                    <div x-show="newTemplateTargetType === 'webinar'" class="space-y-1">
                        <label class="block font-bold text-slate-700">Pilih Webinar Terkait <span class="text-rose-500">*</span></label>
                        <select name="webinar_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-slate-900 font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none">
                            <option value="">-- Pilih Kegiatan Webinar --</option>
                            @foreach($webinars as $wb)
                                <option value="{{ $wb->id }}">{{ $wb->title }} ({{ $wb->date ? \Carbon\Carbon::parse($wb->date)->translatedFormat('d M Y') : '-' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Pilihan Pelatihan Jika Target Pelatihan -->
                    <div x-show="newTemplateTargetType === 'course'" class="space-y-1">
                        <label class="block font-bold text-slate-700">Pilih Pelatihan / Diklat Terkait <span class="text-rose-500">*</span></label>
                        <select name="course_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-slate-900 font-medium focus:ring-2 focus:ring-purple-500 focus:outline-none">
                            <option value="">-- Pilih Kegiatan Pelatihan / Diklat --</option>
                            @foreach($courses as $c)
                                <option value="{{ $c->id }}">{{ $c->title }} ({{ $c->category }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                        <div class="p-3 rounded-xl border border-slate-200 space-y-1.5">
                            <label class="block font-bold text-slate-700">Blanko Hal. 1 (Opsional)</label>
                            <input type="file" name="blanko_file" accept="image/png,image/webp,image/jpeg" class="text-[11px] w-full text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-slate-200 file:text-slate-800 hover:file:bg-slate-300">
                            <p class="text-[10px] text-slate-500">Jika dikosongkan, akan menyalin gambar blanko default saat ini.</p>
                        </div>
                        <div class="p-3 rounded-xl border border-slate-200 space-y-1.5">
                            <label class="block font-bold text-slate-700">Blanko Hal. 2 Lampiran (Opsional)</label>
                            <input type="file" name="lampiran_blanko_file" accept="image/png,image/webp,image/jpeg" class="text-[11px] w-full text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-slate-200 file:text-slate-800 hover:file:bg-slate-300">
                            <p class="text-[10px] text-slate-500">Jika dikosongkan, akan menyalin gambar lampiran default.</p>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="createTemplateModalOpen = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2">
                            <i class="fa-solid fa-plus-circle"></i>
                            <span>Buat & Lanjutkan ke Desainer</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: ATUR PERUNTUKAN TEMPLATE -->
    <div x-show="targetModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100 my-auto" @click.away="targetModalOpen = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 sticky top-0 bg-white z-10">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-base">
                            <i class="fa-solid fa-sliders"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-base text-slate-900">Atur Peruntukan Template</h3>
                            <p class="text-[11px] text-slate-500">Ubah nama dan peruntukan template: <strong>{{ $template->name }}</strong></p>
                        </div>
                    </div>
                    <button type="button" @click="targetModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form action="{{ route('admin.certificates.update_target') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <input type="hidden" name="template_id" value="{{ $template->id }}">

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Template <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ $template->name }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-slate-900 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Peruntukan Template <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <label class="p-3 rounded-xl border cursor-pointer transition-all flex flex-col justify-between"
                                   :class="editTargetType === 'default' ? 'border-emerald-500 bg-emerald-50/50 text-emerald-950 font-bold ring-2 ring-emerald-500/20' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs">⭐ Default</span>
                                    <input type="radio" name="target_type" value="default" x-model="editTargetType" class="text-emerald-600">
                                </div>
                                <span class="text-[10px] text-slate-500 font-normal">Semua kegiatan umum</span>
                            </label>

                            <label class="p-3 rounded-xl border cursor-pointer transition-all flex flex-col justify-between"
                                   :class="editTargetType === 'webinar' ? 'border-sky-500 bg-sky-50/50 text-sky-950 font-bold ring-2 ring-sky-500/20' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs">🎯 Khusus Webinar</span>
                                    <input type="radio" name="target_type" value="webinar" x-model="editTargetType" class="text-sky-600">
                                </div>
                                <span class="text-[10px] text-slate-500 font-normal">Satu webinar terpilih</span>
                            </label>

                            <label class="p-3 rounded-xl border cursor-pointer transition-all flex flex-col justify-between"
                                   :class="editTargetType === 'course' ? 'border-purple-500 bg-purple-50/50 text-purple-950 font-bold ring-2 ring-purple-500/20' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs">🎓 Khusus Diklat</span>
                                    <input type="radio" name="target_type" value="course" x-model="editTargetType" class="text-purple-600">
                                </div>
                                <span class="text-[10px] text-slate-500 font-normal">Satu pelatihan terpilih</span>
                            </label>
                        </div>
                    </div>

                    <!-- Pilihan Webinar Jika Target Webinar -->
                    <div x-show="editTargetType === 'webinar'" class="space-y-1">
                        <label class="block font-bold text-slate-700">Pilih Webinar Terkait <span class="text-rose-500">*</span></label>
                        <select name="webinar_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-slate-900 font-medium focus:ring-2 focus:ring-sky-500 focus:outline-none">
                            <option value="">-- Pilih Kegiatan Webinar --</option>
                            @foreach($webinars as $wb)
                                <option value="{{ $wb->id }}" {{ $template->webinar_id == $wb->id ? 'selected' : '' }}>
                                    {{ $wb->title }} ({{ $wb->date ? \Carbon\Carbon::parse($wb->date)->translatedFormat('d M Y') : '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Pilihan Pelatihan Jika Target Pelatihan -->
                    <div x-show="editTargetType === 'course'" class="space-y-1">
                        <label class="block font-bold text-slate-700">Pilih Pelatihan / Diklat Terkait <span class="text-rose-500">*</span></label>
                        <select name="course_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-slate-900 font-medium focus:ring-2 focus:ring-purple-500 focus:outline-none">
                            <option value="">-- Pilih Kegiatan Pelatihan / Diklat --</option>
                            @foreach($courses as $c)
                                <option value="{{ $c->id }}" {{ $template->course_id == $c->id ? 'selected' : '' }}>
                                    {{ $c->title }} ({{ $c->category }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-900 text-[11px] leading-relaxed">
                        <i class="fa-solid fa-circle-info text-amber-600 mr-1"></i>
                        Jika disetel sebagai <strong>Default</strong>, template ini otomatis dipakai oleh seluruh webinar atau pelatihan yang belum memiliki template khusus.
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="targetModalOpen = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2">
                            <i class="fa-solid fa-check"></i>
                            <span>Simpan Perubahan Peruntukan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection