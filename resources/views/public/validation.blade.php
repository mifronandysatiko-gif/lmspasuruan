@extends('layouts.app')

@section('title', 'Verifikasi Keabsahan E-Sertifikat - BKPSDM Kab. Pasuruan')

@section('content')
<style>
@media print {
    /* Reset halaman cetak */
    html, body {
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        color: #0f172a !important;
        font-size: 12pt !important;
    }

    /* Sembunyikan semua elemen halaman luar */
    body * {
        visibility: hidden !important;
    }

    /* Hilangkan elemen navigasi, banner pengumuman, form pencarian, dan footer sistem */
    nav, header, footer, .no-print, .print-hidden, form, #navbar, .navbar, .marquee-container {
        display: none !important;
    }

    /* Tampilkan HANYA kotak status dokumen sertifikat */
    #print-certificate-card,
    #print-certificate-card * {
        visibility: visible !important;
    }

    #print-certificate-card {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 2px solid #059669 !important;
        border-radius: 12px !important;
        box-shadow: none !important;
        background: #ffffff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    /* Sembunyikan tombol cetak dan tombol salin pada lembar cetak */
    .print-hidden,
    #print-certificate-card .print-hidden,
    #print-certificate-card button {
        display: none !important;
    }

    @page {
        size: A4 portrait;
        margin: 1.2cm 1.5cm;
    }
}
</style>

<div class="py-12 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto space-y-8">
    
    <!-- Top Title & Search bar (Disembunyikan saat cetak) -->
    <div class="text-center space-y-2 no-print">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold border border-emerald-300">
            <i class="fa-solid fa-shield-check text-emerald-600"></i> Layanan Publik Resmi BKPSDM
        </span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">
            Verifikasi & Validasi Dokumen E-Sertifikat
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 max-w-lg mx-auto">
            Pemeriksaan keabsahan dokumen tanda kelulusan pelatihan dan bimbingan teknis aparatur Pemerintah Kabupaten Pasuruan.
        </p>
    </div>

    <!-- Search Input Form (Disembunyikan saat cetak) -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs no-print">
        <form action="{{ route('validation.show') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-2">
            <div class="relative w-full">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-barcode text-base"></i>
                </div>
                <input type="text" name="token" value="{{ $token ?? '' }}" required placeholder="Masukkan Kode Verifikasi Acak (Contoh: PAS-2026-...) atau Nomor Sertifikat"
                       class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pasuruan-700 font-mono">
            </div>
            <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold rounded-xl text-xs transition-colors shrink-0 flex items-center justify-center gap-2">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Cek Keabsahan</span>
            </button>
        </form>
    </div>

    @if($status === 'valid' && $certificate)
        <!-- HASIL VALIDASI RESMI (Section 6.3 PRD) -->
        <div id="print-certificate-card" class="bg-white rounded-2xl border-2 border-emerald-500 overflow-hidden shadow-lg" x-data="{ copied: false }">
            
            <!-- Kop Dokumen Resmi Pemkab Pasuruan (Tampil Elegan Saat Dicetak) -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <picture>
                        <source srcset="{{ asset('images/logo-pasuruan.webp') }}" type="image/webp">
                        <img src="{{ asset('images/logo-pasuruan.png') }}" alt="Logo Kabupaten Pasuruan" class="h-10 w-auto object-contain shrink-0 drop-shadow-sm">
                    </picture>
                    <div>
                        <div class="text-[11px] font-bold text-amber-400 uppercase tracking-widest leading-none">Pemerintah Kabupaten Pasuruan</div>
                        <div class="text-xs sm:text-sm font-extrabold text-white mt-0.5">Badan Kepegawaian dan Pengembangan Sumber Daya Manusia</div>
                    </div>
                </div>
                <div class="text-right text-[10px] font-mono text-slate-300">
                    <div>SURAT BUKTI VALIDASI</div>
                    <div class="text-emerald-400 font-bold">E-SERTIFIKAT RESMI</div>
                </div>
            </div>

            <!-- Header Status Keabsahan -->
            <div class="bg-emerald-600 text-white px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-white text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-emerald-100">Status Dokumen:</div>
                        <div class="text-lg font-extrabold tracking-tight">ASLI / TERDAFTAR RESMI</div>
                    </div>
                </div>
                <div class="text-left sm:text-right space-y-1">
                    <div class="text-[11px] text-emerald-100 font-semibold">Kode Verifikasi Dokumen:</div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-xs sm:text-sm font-extrabold bg-emerald-700/80 px-2.5 py-1 rounded-lg tracking-wider text-white select-all border border-emerald-500">
                            {{ $certificate->hash_token }}
                        </span>
                        <button type="button" @click="navigator.clipboard.writeText('{{ $certificate->hash_token }}'); copied = true; setTimeout(() => copied = false, 2500)" 
                                class="px-2.5 py-1 bg-white/20 hover:bg-white/30 text-white text-[11px] rounded-lg transition-colors flex items-center gap-1 font-bold shrink-0 print-hidden" title="Salin Kode Verifikasi">
                            <i class="fa-solid" :class="copied ? 'fa-check text-emerald-300' : 'fa-copy'"></i>
                            <span x-text="copied ? 'Tersalin!' : 'Salin'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Rincian Data Sertifikat -->
            <div class="p-6 sm:p-8 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Data Penerima -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block border-b border-slate-200 pb-1.5">
                            Data Penerima Sertifikat
                        </span>
                        <div>
                            <div class="text-xs text-slate-500">Nama Lengkap & Gelar:</div>
                            <div class="text-base font-bold text-slate-900 mt-0.5">
                                {{ $certificate->nama_lengkap ?: ($certificate->user->profile->formatted_full_name ?? $certificate->user->name) }}
                            </div>
                        </div>

                        @if(!empty($certificate->nip) || !empty($certificate->user?->nip))
                        <div>
                            <div class="text-xs text-slate-500">Nomor Induk Pegawai (NIP):</div>
                            <div class="text-xs font-mono font-semibold text-slate-800 mt-0.5">
                                {{ $certificate->nip ?: $certificate->user?->nip }}
                            </div>
                        </div>
                        @endif

                        <div>
                            <div class="text-xs text-slate-500">Unit Kerja / Instansi:</div>
                            <div class="text-xs font-semibold text-slate-800 mt-0.5">
                                {{ $certificate->unit_kerja ?: ($certificate->user->profile->unit_kerja ?? '-') }}
                            </div>
                        </div>

                        @if(!empty($certificate->jabatan) || !empty($certificate->user?->profile?->jabatan))
                        <div>
                            <div class="text-xs text-slate-500">Jabatan:</div>
                            <div class="text-xs font-semibold text-slate-800 mt-0.5">
                                {{ $certificate->jabatan ?: ($certificate->user?->profile?->jabatan ?? '-') }}
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Data Diklat & Program -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block border-b border-slate-200 pb-1.5">
                            Informasi Program Pelatihan
                        </span>
                        <div>
                            <div class="text-xs text-slate-500">Nomor Registrasi Sertifikat:</div>
                            <div class="text-xs font-mono font-bold text-pasuruan-700 mt-0.5">
                                {{ $certificate->certificate_number }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Nama Pelatihan / Kegiatan:</div>
                            <div class="text-sm font-bold text-slate-900 mt-0.5">
                                {{ $certificate->training_title ?? ($certificate->webinar->title ?? ($certificate->course->title ?? 'Pelatihan Kompetensi ASN')) }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">Penyelenggara Kegiatan:</div>
                            <div class="text-xs font-semibold text-slate-800 mt-0.5">
                                {{ $certificate->organizer_unit_kerja }}
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <div>
                                <div class="text-xs text-slate-500">Akumulasi Jam (JP):</div>
                                <div class="text-xs font-extrabold text-amber-600 mt-0.5">
                                    {{ $certificate->jp }} JP
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">Tanggal Diterbitkan:</div>
                                <div class="text-xs font-semibold text-slate-800 mt-0.5">
                                    {{ \Carbon\Carbon::parse($certificate->issued_at)->isoFormat('D MMMM Y') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Pejabat & Tanda Tangan Digital -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="space-y-1 text-center sm:text-left">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Pejabat Pengesah:</span>
                        <div class="font-bold text-slate-900 text-sm">
                            {{ $certificate->template->signer_name ?? 'Dr. H. Akhmad Khasani, M.Si' }}
                        </div>
                        <div class="text-xs text-slate-600">
                            {{ $certificate->template->signer_position ?? 'Kepala BKPSDM Kabupaten Pasuruan' }}
                            @if(!empty($certificate->template->signer_nip))
                                • NIP. {{ $certificate->template->signer_nip }}
                            @endif
                        </div>
                        <div class="text-[11px] text-emerald-700 font-semibold flex items-center justify-center sm:justify-start gap-1 pt-0.5">
                            <i class="fa-solid fa-shield-check text-emerald-600"></i>
                            <span>Tanda Tangan Elektronik</span>
                        </div>
                    </div>

                    @if(!empty($certificate->template->signature_url))
                        <div class="shrink-0 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-2xs">
                            <img src="{{ $certificate->template->signature_url }}" class="h-12 w-auto object-contain" alt="Tanda Tangan Pejabat">
                        </div>
                    @endif
                </div>

                @if($certificate->hasLampiran())
                    <!-- Lampiran Transkrip Sesi Pembelajaran & Jam Pelajaran (JP) -->
                    <div class="p-5 rounded-2xl bg-indigo-50/60 border border-indigo-200 space-y-3">
                        <div class="flex items-center justify-between border-b border-indigo-200 pb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs font-bold">
                                    <i class="fa-solid fa-file-invoice"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-xs text-indigo-950 uppercase tracking-wide">Lampiran Sertifikat: Transkrip Materi & Jam Pelajaran (JP)</h4>
                                    <p class="text-[10px] text-indigo-700">Rincian kurikulum modul pembelajaran kedinasan yang telah diselesaikan</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg bg-indigo-200 text-indigo-950 font-extrabold text-[10px]">
                                {{ $certificate->jp }} JP Total
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-xs border border-indigo-200 rounded-xl overflow-hidden bg-white shadow-2xs">
                                <thead>
                                    <tr class="bg-indigo-100/70 text-indigo-950 font-bold text-[10px] uppercase">
                                        <th class="px-3 py-2 text-center w-12 border-b border-indigo-200">No</th>
                                        <th class="px-3 py-2 text-left border-b border-indigo-200">Mata Pelatihan / Materi Sesi Pembelajaran</th>
                                        <th class="px-3 py-2 text-center w-28 border-b border-indigo-200">Alokasi (JP)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-indigo-100">
                                    @if($certificate->course && $certificate->course->lessons->count() > 0)
                                        @foreach($certificate->course->lessons as $idx => $lesson)
                                            <tr class="hover:bg-indigo-50/40">
                                                <td class="px-3 py-1.5 text-center font-bold text-slate-600">{{ $idx + 1 }}</td>
                                                <td class="px-3 py-1.5 font-medium text-slate-900">{{ $lesson->title }}</td>
                                                <td class="px-3 py-1.5 text-center font-bold text-indigo-800">{{ $lesson->calculated_jp }} JP</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td class="px-3 py-1.5 text-center font-bold text-slate-600">1</td>
                                            <td class="px-3 py-1.5 font-medium text-slate-900">{{ $certificate->training_title }}</td>
                                            <td class="px-3 py-1.5 text-center font-bold text-indigo-800">{{ $certificate->jp }} JP</td>
                                        </tr>
                                    @endif
                                </tbody>
                                <tfoot>
                                    <tr class="bg-indigo-50 font-extrabold text-indigo-950 border-t border-indigo-200">
                                        <td colspan="2" class="px-3 py-2 text-right uppercase tracking-wider text-[11px]">Total Beban Belajar (Jam Pelajaran):</td>
                                        <td class="px-3 py-2 text-center font-black text-indigo-900 text-xs">{{ $certificate->jp }} JP</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Preview Blanko Sertifikat & QR Code Visual Box -->
                <div class="p-6 rounded-2xl bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200 flex flex-col sm:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <!-- Scannable QR Code -->
                        <div class="bg-white p-2 rounded-xl border border-amber-300 shadow-xs flex flex-col items-center justify-center text-center shrink-0">
                            <img src="{{ $certificate->qr_code_url }}" class="h-20 w-20 object-contain rounded" alt="QR Code Verifikasi">
                            <span class="text-[8px] font-mono text-slate-500 mt-1">SCAN UNTUK CEK</span>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-sm font-bold text-slate-900">E-Sertifikat Terverifikasi Sistem LMS</h4>
                            <p class="text-xs text-slate-600 max-w-sm">
                                Diterbitkan secara resmi oleh Badan Kepegawaian dan Pengembangan Sumber Daya Manusia (BKPSDM) Pemerintah Kabupaten Pasuruan.
                            </p>
                            <div class="text-[11px] text-slate-500 font-mono pt-1">
                                Tautan Verifikasi: <a href="{{ $certificate->validation_url }}" target="_blank" class="text-pasuruan-700 font-bold hover:underline">{{ $certificate->validation_url }}</a>
                            </div>
                        </div>
                    </div>

                    <div class="shrink-0 w-full sm:w-auto print-hidden">
                        <button onclick="window.print()" class="w-full sm:w-auto px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 shadow-xs flex items-center justify-center gap-2">
                            <i class="fa-solid fa-print text-pasuruan-700"></i> Cetak Bukti Verifikasi
                        </button>
                    </div>
                </div>

            </div>

            <!-- Footer Card Validasi -->
            <div class="bg-slate-50 border-t border-slate-200 px-6 py-3 text-center text-[11px] text-slate-500">
                Dokumen ini sah dan terverifikasi secara elektronik melalui Sistem LMS BKPSDM Pemerintah Kabupaten Pasuruan. 
                <span class="font-mono text-slate-600 block sm:inline sm:before:content-['•_']">Dicetak pada: {{ now()->translatedFormat('d F Y, H:i') }} WIB</span>
            </div>
        </div>

    @elseif($status === 'invalid')
        <!-- HASIL TIDAK DITEMUKAN -->
        <div class="bg-white rounded-2xl border-2 border-rose-400 p-8 text-center space-y-4 shadow-sm">
            <div class="w-16 h-16 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto text-2xl">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-900">Dokumen Sertifikat Tidak Ditemukan</h2>
            <p class="text-xs text-slate-600 max-w-md mx-auto">
                Kode hash atau nomor sertifikat <code class="font-mono text-rose-600 bg-rose-50 px-2 py-0.5 rounded break-all">{{ $token }}</code> tidak terdaftar pada basis data resmi BKPSDM Kabupaten Pasuruan. Pastikan Anda memasukkan kode verifikasi yang sesuai atau hubungi Helpdesk BKPSDM.
            </p>
        </div>
    @else
        <!-- DEFAULT INSTRUCTION STATE -->
        <div class="p-8 rounded-2xl bg-white border border-slate-200 text-center space-y-3">
            <i class="fa-solid fa-qrcode text-4xl text-slate-300"></i>
            <h3 class="text-sm font-bold text-slate-700">Pindai QR Code atau Masukkan Token</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto">
                Arahkan kamera smartphone ke QR Code yang tertera di sudut e-sertifikat Anda, atau masukkan nomor dokumen sertifikat pada kolom pencarian di atas.
            </p>
        </div>
    @endif

</div>
@endsection
