@extends('layouts.dashboard')

@section('title', 'E-Sertifikat Saya - LMS Kabupaten Pasuruan')
@section('header_title', 'Portofolio & E-Sertifikat Tervalidasi')

@section('content')
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

@media print {
    body * {
        visibility: hidden !important;
    }
    aside, header, nav, .sidebar, button, .modal-backdrop, .no-print {
        display: none !important;
    }
    #print-peserta-cert,
    #print-peserta-cert * {
        visibility: visible !important;
    }
    #print-peserta-cert {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 297mm !important;
        margin: 0 !important;
        padding: 0 !important;
        background: transparent !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .cert-page {
        width: 297mm !important;
        height: 210mm !important;
        max-width: 297mm !important;
        max-height: 210mm !important;
        box-sizing: border-box !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        margin: 0 !important;
        overflow: hidden !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        background-size: 100% 100% !important;
        background-repeat: no-repeat !important;
        background-position: center !important;
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }
    .page-break {
        page-break-after: always !important;
        break-after: page !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        display: block !important;
    }
    @page {
        size: A4 landscape;
        margin: 0;
    }
}
</style>
<div class="space-y-8" x-data="{
    selectedCert: null,
    modalScale: 0.65,
    init() {
        this.$watch('selectedCert', (val) => {
            if (val) {
                this.$nextTick(() => {
                    this.updateModalScale();
                    setTimeout(() => this.updateModalScale(), 100);
                    setTimeout(() => this.updateModalScale(), 300);
                });
            }
        });
        window.addEventListener('resize', () => {
            this.updateModalScale();
        });
    },
    updateModalScale() {
        const wrapper = this.$refs.modalCanvasWrapper || document.getElementById('modal-cert-canvas-wrapper') || document.querySelector('.modal-canvas-container');
        if (wrapper) {
            const w = wrapper.clientWidth;
            if (w > 0) {
                this.modalScale = w / 1122.52;
            }
        }
    },
    getCoord(key, axis, defaultVal) {
        if (!this.selectedCert) return defaultVal;
        const coords = this.selectedCert.layout_coordinates || this.selectedCert.template?.layout_coordinates || {};
        return coords[key]?.[axis] ?? defaultVal;
    },
    getPercentX(key, defaultVal) {
        const val = this.getCoord(key, 'x', defaultVal);
        return ((val / 842) * 100).toFixed(2) + '%';
    },
    getPercentY(key, defaultVal) {
        const val = this.getCoord(key, 'y', defaultVal);
        return ((val / 595) * 100).toFixed(2) + '%';
    },
    getCertBgUrl() {
        return this.selectedCert?.background_url || this.selectedCert?.template?.background_url || null;
    },
    formatDate(dateStr) {
        if (!dateStr) return '{{ now()->translatedFormat("d F Y") }}';
        try {
            const d = new Date(dateStr);
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
        } catch (e) {
            return dateStr;
        }
    }
}">
    
    <!-- Top Bar -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900">Daftar E-Sertifikat Pengembangan Kompetensi</h2>
            <p class="text-xs text-slate-500">Seluruh dokumen telah ditandatangani secara elektronik dan terverifikasi publik</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3.5 py-1.5 bg-amber-50 text-amber-700 font-bold text-xs rounded-xl border border-amber-200">
                <i class="fa-solid fa-stamp mr-1 text-amber-600"></i> Total: {{ $certificates->count() }} Sertifikat
            </span>
        </div>
    </div>

    <!-- Certificates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($certificates as $cert)
            <div class="bg-white rounded-2xl border border-amber-200/80 p-6 shadow-xs hover:shadow-md transition-all flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-amber-500/5 rounded-full pointer-events-none"></div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[10px] font-mono font-bold px-2.5 py-1 rounded bg-amber-50 text-amber-800 border border-amber-200">
                                {{ $cert->certificate_number }}
                            </span>
                            @if($cert->course)
                                <span class="text-[9px] font-extrabold px-2 py-0.5 rounded uppercase bg-purple-50 text-purple-700 border border-purple-200">
                                    {{ str_replace('_', ' ', $cert->course->type) }}
                                </span>
                            @elseif($cert->webinar)
                                <span class="text-[9px] font-extrabold px-2 py-0.5 rounded uppercase bg-sky-50 text-sky-700 border border-sky-200">
                                    WEBINAR
                                </span>
                            @endif
                            @if($cert->hasLampiran())
                                <span class="text-[9px] font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    <i class="fa-solid fa-file-invoice mr-1"></i>2 Hal (Lampiran JP)
                                </span>
                            @endif
                        </div>
                        <span class="text-xs font-extrabold text-amber-600">
                            {{ $cert->jp }} JP ({{ $cert->jp * 45 }} Mnt)
                        </span>
                    </div>

                    <div>
                        <h3 class="font-bold text-base text-slate-900 leading-snug">
                            {{ $cert->training_title ?? ($cert->webinar->title ?? ($cert->course->title ?? 'Pelatihan Kompetensi BKPSDM')) }}
                        </h3>
                        <p class="text-xs text-pasuruan-800 font-semibold mt-1 flex items-center gap-1.5">
                            <i class="fa-solid fa-building-columns text-pasuruan-600 text-[11px]"></i>
                            <span>Diselenggarakan oleh: <strong>{{ $cert->organizer_unit_kerja }}</strong></span>
                        </p>
                        <p class="text-xs text-slate-500 mt-1">
                            Penerima: <strong>{{ $user->profile->formatted_full_name ?? $user->name }}</strong>
                            @if($user->nip)
                                • NIP: <span class="font-mono">{{ $user->nip }}</span>
                            @endif
                        </p>
                    </div>

                    <!-- Certificate Mini Preview Thumbnail -->
                    @if($cert->background_url)
                        <div class="relative rounded-xl overflow-hidden border border-amber-300/80 shadow-xs h-28 sm:h-32 bg-cover bg-center flex items-end p-3.5 group transition-all"
                             style="background-image: url('{{ $cert->background_url }}'); background-size: cover; background-position: center;">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/40 to-transparent"></div>
                            <div class="relative z-10 w-full flex items-center justify-between text-white text-xs">
                                <div>
                                    <div class="font-bold text-amber-300 flex items-center gap-1.5 text-xs">
                                        <i class="fa-solid fa-stamp"></i> Template Blanko Resmi Aktif
                                    </div>
                                    <div class="text-[10px] text-slate-300">Terbit: {{ \Carbon\Carbon::parse($cert->issued_at)->isoFormat('D MMMM Y') }}</div>
                                    @if($cert->hasLampiran())
                                        <div class="text-[9.5px] text-amber-200/90 font-medium">Lengkap dengan Lampiran Sesi & Jam Pelajaran</div>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-600/90 text-[10px] font-bold text-white flex items-center gap-1 shadow-xs">
                                        <i class="fa-solid fa-circle-check"></i> Valid BSrE
                                    </span>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Certificate Mini Preview Mock Fallback -->
                        <div class="p-4 rounded-xl bg-gradient-to-br from-slate-900 to-pasuruan-900 text-white flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-amber-400 text-pasuruan-900 flex items-center justify-center font-bold text-lg">
                                    <i class="fa-solid fa-award"></i>
                                </div>
                                <div class="text-[11px]">
                                    <div class="font-bold text-amber-300">BKPSDM KAB. PASURUAN</div>
                                    <div class="text-slate-300">Terbit: {{ \Carbon\Carbon::parse($cert->issued_at)->isoFormat('D MMMM Y') }}</div>
                                    @if($cert->hasLampiran())
                                        <div class="text-[10px] text-amber-200/90 font-medium">Lengkap dengan Lampiran Sesi & Jam Pelajaran</div>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <i class="fa-solid fa-qrcode text-3xl text-slate-300"></i>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Card Action Buttons -->
                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between gap-2">
                    <a href="{{ route('validation.show', $cert->hash_token) }}" target="_blank" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-qrcode text-emerald-600"></i> Validasi Barcode
                    </a>

                    <button type="button" @click="selectedCert = {{ json_encode($cert) }}" class="px-4 py-2 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-file-arrow-down text-amber-400"></i> Cetak / Unduh PDF
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-2 py-16 bg-white rounded-2xl border border-slate-200 text-center text-slate-400 space-y-3">
                <i class="fa-solid fa-stamp text-4xl text-slate-300"></i>
                <p class="text-sm">Anda belum memiliki sertifikat yang diterbitkan.</p>
                <a href="{{ route('peserta.katalog') }}" class="text-xs font-bold text-pasuruan-700 hover:underline">
                    Ikuti pelatihan atau webinar terbuka untuk mendapatkan sertifikat
                </a>
            </div>
        @endforelse
    </div>

    <!-- Modal Cetak / Preview Sertifikat (Multi-Page Support) -->
    <div x-show="selectedCert !== null" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-3xl max-w-4xl w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-200 relative my-auto">
            <button @click="selectedCert = null" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 no-print">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>

            <!-- Container Cetak Sertifikat -->
            <div id="print-peserta-cert" class="space-y-6">
                
                <!-- ==================== HALAMAN 1: MUKA SERTIFIKAT KELULUSAN ==================== -->
                <!-- 1A. JIKA MENGGUNAKAN BLANKO CUSTOM DARI SUPER ADMIN / UPLOAD -->
                <template x-if="getCertBgUrl()">
                    <div x-ref="modalCanvasWrapper" id="modal-cert-canvas-wrapper" class="modal-canvas-container relative w-full rounded-2xl overflow-hidden select-none shadow-xs border border-slate-300 bg-slate-900" :style="'height: ' + (793.70 * modalScale) + 'px;'">
                        <div class="absolute top-0 left-0 origin-top-left overflow-hidden bg-white select-none"
                             :style="'width: 1122.52px; height: 793.70px; transform: scale(' + modalScale + '); background-image: url(' + getCertBgUrl() + '); background-size: 100% 100%; background-repeat: no-repeat; background-position: center;'">
                            
                            <!-- 1. Nomor Sertifikat Dinamis (numX, numY) -->
                            <div class="absolute -translate-x-1/2 -translate-y-1/2 text-center select-none z-10 whitespace-nowrap"
                                 :style="`left: ${getPercentX('certificate_number', 424)}; top: ${getPercentY('certificate_number', 189)};`">
                                <div class="cert-print-num text-center whitespace-nowrap">
                                    Nomor: <span x-text="selectedCert?.certificate_number"></span>
                                </div>
                            </div>

                            <!-- 2. Nama Peserta Dinamis (nameX, nameY) -->
                            <div class="absolute -translate-x-1/2 -translate-y-1/2 text-center select-none z-10 w-[85%]"
                                 :style="`left: ${getPercentX('full_name', 420)}; top: ${getPercentY('full_name', 228)};`">
                                <p class="cert-print-name-label">Diberikan Kepada:</p>
                                <h2 class="cert-print-name"
                                    x-text="selectedCert?.nama_lengkap || '{{ $user->profile->formatted_full_name ?? $user->name }}'">
                                </h2>
                                <p class="cert-print-nip"
                                   x-text="'NIP. ' + (selectedCert?.nip || '{{ $user->nip ?? '-' }}') + ' • ' + (selectedCert?.unit_kerja || '{{ $user->profile->unit_kerja ?? 'Pemerintah Kabupaten Pasuruan' }}')">
                                </p>
                            </div>

                            <!-- 3. Judul Pelatihan & Total JP Dinamis (titleX, titleY) -->
                            <div class="absolute -translate-x-1/2 -translate-y-1/2 text-center select-none z-10 w-[85%]"
                                 :style="`left: ${getPercentX('training_title', 418)}; top: ${getPercentY('training_title', 305)};`">
                                <p class="cert-print-title-label">Telah Mengikuti dan Dinyatakan <strong class="text-emerald-700 font-bold">LULUS</strong> Pada:</p>
                                <h4 class="cert-print-title"
                                    x-text="selectedCert?.training_title">
                                </h4>
                                <p class="cert-print-organizer">
                                    Diselenggarakan oleh <strong class="font-bold text-slate-900" x-text="selectedCert?.organizer_unit_kerja || 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia Kabupaten Pasuruan'"></strong>
                                </p>
                                <p class="cert-print-jp"
                                   x-text="'Akumulasi Jam Pelajaran: ' + selectedCert?.jp + ' JP'">
                                </p>
                            </div>

                            <!-- 4. QR Code Barcode Validasi (qrX, qrY) -->
                            <div class="absolute -translate-x-1/2 -translate-y-1/2 select-none z-10"
                                 :style="`left: ${getPercentX('qr_code', 155)}; top: ${getPercentY('qr_code', 475)};`">
                                <div class="flex items-center gap-2.5 bg-transparent p-0 border-0 shadow-none">
                                    <div class="bg-transparent p-0 border-0 shadow-none shrink-0">
                                        <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + encodeURIComponent('{{ url('/validasi') }}/' + selectedCert?.hash_token)" 
                                             class="cert-print-qr-img" alt="QR Barcode Validasi">
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
                                        <div class="font-bold text-slate-900 font-mono select-all tracking-tight"
                                             x-text="selectedCert?.hash_token">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 5. Tanda Tangan Pejabat & Tanggal Terbit (issueX, issueY) -->
                            <div class="absolute -translate-x-1/2 -translate-y-1/2 select-none z-10 text-center"
                                 :style="`left: ${getPercentX('issued_at', 675)}; top: ${getPercentY('issued_at', 475)};`">
                                <div class="cert-print-sig-text text-center space-y-0.5 leading-snug bg-transparent p-0 border-0 shadow-none">
                                    <div x-text="(selectedCert?.template?.issue_city || '{{ $defaultTemplate?->issue_city ?? "Bangil, Pasuruan" }}') + ', ' + formatDate(selectedCert?.issued_at)"></div>
                                    <div class="font-bold text-slate-900" x-text="selectedCert?.template?.signer_position || '{{ $defaultTemplate?->signer_position ?? "Kepala BKPSDM Pasuruan" }}'"></div>
                                    
                                    <div class="py-0.5 my-0.5 flex items-center justify-center min-h-[44px]">
                                        <template x-if="selectedCert?.template?.signature_url">
                                            <img :src="selectedCert.template.signature_url" class="cert-print-sig-img" alt="TTD Pejabat">
                                        </template>
                                        <template x-if="!selectedCert?.template?.signature_url">
                                            <img src="{{ asset('storage/specimens/spesimen_ttd_pejabat.png') }}" class="cert-print-sig-img" alt="TTD Pejabat">
                                        </template>
                                    </div>

                                    <div class="cert-print-sig-name" 
                                         x-text="selectedCert?.template?.signer_name || '{{ $defaultTemplate?->signer_name ?? "Dr. H. Akhmad Khasani, M.Si" }}'">
                                    </div>
                                    <template x-if="selectedCert?.template?.signer_nip || '{{ $defaultTemplate?->signer_nip }}'">
                                        <div class="cert-print-sig-nip" 
                                             x-text="'NIP. ' + (selectedCert?.template?.signer_nip || '{{ $defaultTemplate?->signer_nip }}')">
                                        </div>
                                    </template>
                                    <div class="cert-print-tte-badge">
                                        <i class="fa-solid fa-shield-check text-emerald-600"></i>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </template>

                <!-- 1B. FALLBACK VEKTOR JIKA BELUM ADA BLANKO GAMBAR YANG DIUNGGAH -->
                <template x-if="!getCertBgUrl()">
                    <div class="modal-canvas-container relative w-full rounded-2xl overflow-hidden select-none shadow-xs border border-slate-300 bg-slate-900" :style="'height: ' + (793.70 * modalScale) + 'px;'">
                        <div class="absolute top-0 left-0 origin-top-left overflow-hidden bg-amber-50/40 select-none border-4 border-double border-amber-600 p-12 flex flex-col justify-between"
                             :style="'width: 1122.52px; height: 793.70px; transform: scale(' + modalScale + ');'">
                            <div class="text-center space-y-4">
                                <div class="flex items-center justify-center gap-2 text-pasuruan-900 font-extrabold text-sm tracking-widest uppercase">
                                    <i class="fa-solid fa-graduation-cap text-amber-600 text-lg"></i>
                                    <span>Pemerintah Kabupaten Pasuruan</span>
                                </div>
                                <div class="text-[11px] text-slate-500 uppercase tracking-widest font-semibold">
                                    Badan Kepegawaian dan Pengembangan Sumber Daya Manusia (BKPSDM)
                                </div>

                                <div class="py-4">
                                    <h2 class="text-2xl font-extrabold text-slate-900 uppercase tracking-wider font-title-cert">
                                        SERTIFIKAT KELULUSAN
                                    </h2>
                                    <p class="text-xs font-mono text-pasuruan-800 font-bold mt-1" x-text="'Nomor: ' + selectedCert?.certificate_number"></p>
                                </div>

                                <div class="space-y-1">
                                    <p class="text-xs text-slate-500">Diberikan kepada:</p>
                                    <h3 class="text-2xl font-extrabold text-amber-950 font-serif-cert" x-text="selectedCert?.nama_lengkap || '{{ $user->profile->formatted_full_name ?? $user->name }}'"></h3>
                                    <p class="text-xs text-slate-600" x-text="'NIP. ' + (selectedCert?.nip || '{{ $user->nip ?? '-' }}') + ' • ' + (selectedCert?.unit_kerja || '{{ $user->profile->unit_kerja ?? 'Pemerintah Kabupaten Pasuruan' }}')"></p>
                                </div>

                                <div class="text-xs text-slate-600 max-w-xl mx-auto leading-relaxed pt-3">
                                    Telah mengikuti dan dinyatakan <strong class="text-emerald-700">LULUS</strong> pada kegiatan:
                                    <div class="font-bold text-slate-900 text-sm mt-1" x-text="selectedCert?.training_title"></div>
                                    <div class="text-xs text-slate-700 font-medium mt-1">
                                        Diselenggarakan oleh <strong class="font-bold text-slate-900" x-text="selectedCert?.organizer_unit_kerja || 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia Kabupaten Pasuruan'"></strong>
                                    </div>
                                    <div class="text-amber-700 font-bold text-xs mt-1" x-text="'Akumulasi Jam Pelajaran: ' + selectedCert?.jp + ' JP'"></div>
                                </div>
                            </div>

                            <!-- Footer TTE & Barcode Fallback -->
                            <div class="pt-6 flex items-center justify-between border-t border-amber-200 text-xs">
                                <div class="flex items-center gap-2.5 bg-transparent p-0 border-0 shadow-none">
                                    <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + encodeURIComponent('{{ url('/validasi') }}/' + selectedCert?.hash_token)" 
                                         class="h-14 w-14 object-contain shrink-0 bg-transparent p-0 border-0 shadow-none" alt="QR Validasi">
                                    <div class="text-left text-[9px] text-slate-500 font-mono space-y-0.5">
                                        <div class="font-bold text-slate-900">VERIFIKASI RESMI TTE</div>
                                        <div class="text-emerald-700 font-bold text-[8.5px]">STATUS: TERDAFTAR</div>
                                        <div class="text-[8px] text-slate-800 font-bold font-mono select-all tracking-tight" x-text="selectedCert?.hash_token"></div>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <div class="text-[10px] text-slate-600" x-text="(selectedCert?.template?.issue_city || 'Bangil, Pasuruan') + ', ' + formatDate(selectedCert?.issued_at)"></div>
                                    <div class="font-bold text-slate-900 text-xs mt-0.5" x-text="selectedCert?.template?.signer_position || 'Kepala BKPSDM Kab. Pasuruan'"></div>
                                    <div class="py-1">
                                        <template x-if="selectedCert?.template?.signature_url">
                                            <img :src="selectedCert.template.signature_url" class="h-9 w-auto ml-auto object-contain" alt="TTD">
                                        </template>
                                        <template x-if="!selectedCert?.template?.signature_url">
                                            <img src="{{ asset('storage/specimens/spesimen_ttd_pejabat.png') }}" class="h-9 w-auto ml-auto object-contain" alt="TTD">
                                        </template>
                                    </div>
                                    <div class="font-bold font-serif-cert text-slate-900 underline text-xs" x-text="selectedCert?.template?.signer_name || 'Dr. H. Akhmad Khasani, M.Si'"></div>
                                    <div class="text-[9px] font-mono text-slate-600" x-text="'NIP. ' + (selectedCert?.template?.signer_nip || '19710504 199703 1 004')"></div>
                                    <div class="text-[9px] text-emerald-700 font-bold mt-0.5 flex items-center justify-end gap-1">
                                        <i class="fa-solid fa-shield-check text-emerald-600"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ==================== HALAMAN 2: LAMPIRAN TRANSKRIP SESI & JP ==================== -->
                <template x-if="selectedCert?.has_lampiran">
                    <div>
                        <!-- Page break visual divider for preview on screen -->
                        <div class="page-break my-4 border-t-2 border-dashed border-slate-300 print:hidden text-center text-xs text-indigo-700 font-bold py-2 bg-indigo-50/50 rounded-xl">
                            <i class="fa-solid fa-file-invoice mr-1"></i> Halaman 2: Lampiran Sertifikat (Transkrip Materi Sesi & Jam Pelajaran)
                        </div>

                        <div class="modal-canvas-container relative w-full rounded-2xl overflow-hidden select-none shadow-xs border border-slate-300 bg-slate-900" :style="'height: ' + (793.70 * modalScale) + 'px;'">
                            <div class="absolute top-0 left-0 origin-top-left overflow-hidden bg-white select-none p-16 flex flex-col justify-center"
                                 :class="selectedCert?.lampiran_background_url ? 'border-2 border-slate-600' : 'border-4 border-double border-slate-700'"
                                 :style="'width: 1122.52px; height: 793.70px; transform: scale(' + modalScale + '); ' + (selectedCert?.lampiran_background_url ? 'background-image: url(' + selectedCert.lampiran_background_url + '); background-size: 100% 100%; background-repeat: no-repeat; background-position: center;' : '')">
                                
                                <div class="w-full relative z-10">
                                    <div class="text-center pb-5">
                                        <h3 class="text-lg font-extrabold uppercase tracking-wider text-slate-900 font-title-cert">
                                            TRANSKRIP MATERI & ALOKASI JAM PELAJARAN (JP)
                                        </h3>
                                        <p class="text-xs text-slate-600 mt-1">Lampiran Sertifikat Kelulusan Nomor: <span class="font-mono font-bold" x-text="selectedCert?.certificate_number"></span></p>
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
                                            <template x-if="selectedCert?.course?.lessons && selectedCert.course.lessons.length > 0">
                                                <template x-for="(lesson, idx) in selectedCert.course.lessons" :key="lesson.id || idx">
                                                    <tr class="hover:bg-slate-50/60">
                                                        <td class="border border-slate-300 px-3 py-2 text-center font-bold text-slate-600" x-text="idx + 1"></td>
                                                        <td class="border border-slate-300 px-4 py-2 font-medium text-slate-900" x-text="lesson.title"></td>
                                                        <td class="border border-slate-300 px-3 py-2 text-center font-bold text-slate-800" x-text="(lesson.calculated_jp || lesson.jp || 2) + ' JP'"></td>
                                                    </tr>
                                                </template>
                                            </template>
                                            <template x-if="!selectedCert?.course?.lessons || selectedCert.course.lessons.length === 0">
                                                <tr class="hover:bg-slate-50/60">
                                                    <td class="border border-slate-300 px-3 py-2 text-center font-bold text-slate-600">1</td>
                                                    <td class="border border-slate-300 px-4 py-2 font-medium text-slate-900" x-text="selectedCert?.training_title"></td>
                                                    <td class="border border-slate-300 px-3 py-2 text-center font-bold text-slate-800" x-text="selectedCert?.jp + ' JP'"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-amber-50/80 font-extrabold text-slate-900 border-t-2 border-slate-800">
                                                <td colspan="2" class="border border-slate-400 px-4 py-2.5 text-right uppercase tracking-wider text-xs">
                                                    Total Alokasi Jam Pelajaran (JP)
                                                </td>
                                                <td class="border border-slate-400 px-3 py-2.5 text-center font-black text-amber-900 text-xs" x-text="selectedCert?.jp + ' JP'"></td>
                                            </tr>
                                        </tfoot>
                                    </table>

                                    <div class="pt-6 flex items-center justify-between text-[11px] text-slate-500">
                                        <div>
                                            Pemerintah Kabupaten Pasuruan • BKPSDM
                                        </div>
                                        <div>
                                            Dokumen Resmi Sah Terbitan Sistem LMS
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </template>

            </div>

            <div class="flex items-center justify-between pt-2 no-print border-t border-slate-100">
                <div class="text-xs text-slate-500">
                    <template x-if="selectedCert?.has_lampiran">
                        <span class="text-indigo-700 font-bold flex items-center gap-1.5">
                            <i class="fa-solid fa-file-invoice"></i> Sertifikat ini terdiri dari 2 Halaman (Muka & Lampiran JP)
                        </span>
                    </template>
                    <template x-if="!selectedCert?.has_lampiran">
                        <span class="text-slate-500">Dokumen e-sertifikat 1 halaman</span>
                    </template>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="selectedCert = null" class="px-4 py-2 text-slate-600 hover:bg-slate-100 text-xs font-bold rounded-xl transition-colors">
                        Tutup
                    </button>
                    <a :href="'{{ url('/peserta/sertifikat') }}/' + selectedCert?.id + '/print'" target="_blank" class="px-5 py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white text-xs font-bold rounded-xl shadow-xs flex items-center gap-2 transition-colors">
                        <i class="fa-solid fa-print"></i> Cetak / Unduh Dokumen PDF
                    </a>
                </div>
            </div>
            </div>
        </div>
    </div>

</div>
@endsection
