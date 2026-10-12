<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Sertifikat - {{ $cert->certificate_number }} - {{ $cert->nama_lengkap }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo-pasuruan.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Cinzel:wght@600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            color: #0f172a;
        }

        .font-serif-cert {
            font-family: 'Playfair Display', Georgia, serif;
        }

        .font-title-cert {
            font-family: 'Cinzel', serif;
        }

        /* Screen & Print Unified Certificate Styling */
        .cert-container {
            padding: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .cert-page {
            width: 297mm;
            height: 210mm;
            max-width: 297mm;
            max-height: 210mm;
            box-sizing: border-box;
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-position: center;
            background-color: #ffffff;
            position: relative;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 24px;
        }

        /* 100% Identical Typography & Elements for both Screen Preview and Print */
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

        /* Print Specific Media Rules (Page setup & color adjust) */
        @media print {
            body, html {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }
            .no-print {
                display: none !important;
            }
            .cert-container {
                padding: 0 !important;
                margin: 0 !important;
                background: transparent !important;
                display: block !important;
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
</head>
<body>

@php
    $coords = $cert->layout_coordinates ?? [];
    $numX = isset($coords['certificate_number']['x']) ? round(($coords['certificate_number']['x'] / 842) * 100, 2) . '%' : '50.36%';
    $numY = isset($coords['certificate_number']['y']) ? round(($coords['certificate_number']['y'] / 595) * 100, 2) . '%' : '31.76%';
    $nameX = isset($coords['full_name']['x']) ? round(($coords['full_name']['x'] / 842) * 100, 2) . '%' : '49.88%';
    $nameY = isset($coords['full_name']['y']) ? round(($coords['full_name']['y'] / 595) * 100, 2) . '%' : '38.32%';
    $titleX = isset($coords['training_title']['x']) ? round(($coords['training_title']['x'] / 842) * 100, 2) . '%' : '49.64%';
    $titleY = isset($coords['training_title']['y']) ? round(($coords['training_title']['y'] / 595) * 100, 2) . '%' : '51.26%';
    $qrX = isset($coords['qr_code']['x']) ? round(($coords['qr_code']['x'] / 842) * 100, 2) . '%' : '18.41%';
    $qrY = isset($coords['qr_code']['y']) ? round(($coords['qr_code']['y'] / 595) * 100, 2) . '%' : '79.83%';
    $issueX = isset($coords['issued_at']['x']) ? round(($coords['issued_at']['x'] / 842) * 100, 2) . '%' : '80.17%';
    $issueY = isset($coords['issued_at']['y']) ? round(($coords['issued_at']['y'] / 595) * 100, 2) . '%' : '79.83%';

    $bgUrl = $cert->background_url;
    $lampiranBgUrl = $cert->lampiran_background_url;
    $hasLampiran = $cert->has_lampiran;
    $template = $cert->template ?: $defaultTemplate;
@endphp

<!-- Control Bar / Toolbar (Layar Monitor Saja, Tidak Tercetak) -->
<div class="no-print bg-white border-b border-slate-200 sticky top-0 z-50 px-6 py-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        @if(auth()->check() && auth()->user()->isPeserta())
            <a href="{{ route('peserta.certificates') }}" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors" title="Kembali ke Portofolio Sertifikat">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
        @else
            <a href="{{ route('admin.certificates.manual') }}" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors" title="Kembali ke Menu Penerbitan Sertifikat">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
        @endif
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-900 font-mono font-bold text-xs">{{ $cert->certificate_number }}</span>
                <span class="text-xs font-bold text-slate-900">{{ $cert->nama_lengkap }}</span>
                @if($cert->nip)
                    <span class="text-[11px] text-slate-500 font-mono">(NIP. {{ $cert->nip }})</span>
                @endif
            </div>
            <p class="text-[11px] text-slate-500 mt-0.5">
                {{ $cert->training_title }} • {{ $cert->jp }} JP • {{ $hasLampiran ? '2 Halaman (Muka & Lampiran)' : '1 Halaman' }}
            </p>
        </div>
    </div>

    <div class="flex items-center gap-2.5">
        <a href="{{ route('validation.show', $cert->hash_token) }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs transition-colors border border-emerald-200 flex items-center gap-1.5">
            <i class="fa-solid fa-qrcode"></i> Validasi Barcode
        </a>
        <button type="button" onclick="window.close()" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
            Tutup
        </button>
        <button type="button" onclick="window.print()" class="px-5 py-2 rounded-xl bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs shadow-sm transition-colors flex items-center gap-2">
            <i class="fa-solid fa-print"></i>
            <span>Cetak / Simpan PDF (A4)</span>
        </button>
    </div>
</div>

<div class="no-print max-w-4xl mx-auto mt-4 px-4">
    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-xs flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-info text-amber-600 text-sm"></i>
            <span><strong>Petunjuk Unduh PDF:</strong> Pilih opsi <strong>"Save as PDF" / "Simpan sebagai PDF"</strong> dan pastikan orientasi <strong>Landscape</strong> dengan ukuran kertas <strong>A4</strong> (Margin: <em>None</em>).</span>
        </div>
        <button type="button" onclick="window.print()" class="text-amber-800 font-bold underline shrink-0 hover:text-amber-950">Cetak Sekarang &rarr;</button>
    </div>
</div>

<!-- Container Sertifikat -->
<div class="cert-container">

    <!-- ==================== HALAMAN 1: MUKA SERTIFIKAT ==================== -->
    @if($bgUrl)
        <!-- 1A. MENGGUNAKAN BLANKO RESMI / GAMBAR LATAR -->
        <div class="cert-page select-none" style="background-image: url('{{ $bgUrl }}');">
            
            <!-- 1. Nomor Sertifikat Dinamis -->
            <div class="absolute -translate-x-1/2 -translate-y-1/2 text-center select-none z-10 whitespace-nowrap"
                 style="left: {{ $numX }}; top: {{ $numY }};">
                <div class="cert-print-num text-center whitespace-nowrap">
                    Nomor: {{ $cert->certificate_number }}
                </div>
            </div>

            <!-- 2. Nama Penerima Dinamis -->
            <div class="absolute -translate-x-1/2 -translate-y-1/2 text-center select-none z-10 w-[85%]"
                 style="left: {{ $nameX }}; top: {{ $nameY }};">
                <p class="cert-print-name-label">Diberikan Kepada:</p>
                <h2 class="cert-print-name">
                    {{ $cert->nama_lengkap }}
                </h2>
                <p class="cert-print-nip">
                    NIP. {{ $cert->nip ?: '-' }} • {{ $cert->unit_kerja ?: 'Pemerintah Kabupaten Pasuruan' }}
                </p>
            </div>

            <!-- 3. Judul Pelatihan & Total JP -->
            <div class="absolute -translate-x-1/2 -translate-y-1/2 text-center select-none z-10 w-[85%]"
                 style="left: {{ $titleX }}; top: {{ $titleY }};">
                <p class="cert-print-title-label">Telah Mengikuti dan Dinyatakan <strong class="text-emerald-700 font-bold">LULUS</strong> Pada:</p>
                <h4 class="cert-print-title">
                    {{ $cert->training_title }}
                </h4>
                <p class="cert-print-organizer">
                    Diselenggarakan oleh <strong class="font-bold text-slate-900">{{ $cert->organizer_unit_kerja }}</strong>
                </p>
                <p class="cert-print-jp">
                    Akumulasi Jam Pelajaran: {{ $cert->jp }} JP
                </p>
            </div>

            <!-- 4. QR Code Barcode Validasi (Transparan Penuh Sesuai Request) -->
            <div class="absolute -translate-x-1/2 -translate-y-1/2 select-none z-10"
                 style="left: {{ $qrX }}; top: {{ $qrY }};">
                <div class="flex items-center gap-2.5 bg-transparent p-0 border-0 shadow-none">
                    <div class="bg-transparent p-0 border-0 shadow-none shrink-0">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode(url('/validasi/' . $cert->hash_token)) }}" 
                             class="cert-print-qr-img" alt="QR Barcode Validasi">
                    </div>
                    <div class="cert-print-qr-text text-left space-y-0.5">
                        <div class="flex items-center gap-1 text-emerald-800 font-bold">
                            <i class="fa-solid fa-shield-check text-emerald-600"></i>
                            <span>VALIDASI RESMI SERTIFIKAT</span>
                        </div>
                        <div class="text-slate-600 font-medium">Pindai / Kunjungi Link:</div>
                        <div class="font-bold text-slate-900 truncate max-w-[130px]">
                            {{ request()->getHost() }}/validasi
                        </div>
                        <div class="text-slate-600 font-medium pt-0.5">Kode Verifikasi:</div>
                        <div class="font-bold text-slate-900 font-mono select-all tracking-tight">
                            {{ $cert->hash_token }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Tanda Tangan Pejabat & Tanggal Terbit (Transparan Penuh Sesuai Request) -->
            <div class="absolute -translate-x-1/2 -translate-y-1/2 select-none z-10 text-center"
                 style="left: {{ $issueX }}; top: {{ $issueY }};">
                <div class="cert-print-sig-text text-center space-y-0.5 bg-transparent p-0 border-0 shadow-none">
                    <div>
                        {{ $template?->issue_city ?: 'Bangil, Pasuruan' }}, {{ \Carbon\Carbon::parse($cert->issued_at)->isoFormat('D MMMM Y') }}
                    </div>
                    <div class="font-bold text-slate-900">
                        {{ $template?->signer_position ?: 'Kepala BKPSDM Pasuruan' }}
                    </div>
                    
                    <div class="py-0.5 my-0.5 flex items-center justify-center min-h-[44px]">
                        @if($template?->signature_url)
                            <img src="{{ $template->signature_url }}" class="cert-print-sig-img" alt="TTD Pejabat">
                        @else
                            <img src="{{ asset('storage/specimens/spesimen_ttd_pejabat.png') }}" class="cert-print-sig-img" alt="TTD Pejabat">
                        @endif
                    </div>

                    <div class="cert-print-sig-name">
                        {{ $template?->signer_name ?: 'Dr. H. Akhmad Khasani, M.Si' }}
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
            </div>

        </div>
    @else
        <!-- 1B. FALLBACK VEKTOR JIKA TIDAK ADA BLANKO GAMBAR -->
        <div class="cert-page border-4 border-double border-amber-600 p-12 bg-amber-50/30 text-center space-y-4 relative flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-center gap-2 text-pasuruan-900 font-extrabold text-sm tracking-widest uppercase">
                    <i class="fa-solid fa-graduation-cap text-amber-600 text-lg"></i>
                    <span>Pemerintah Kabupaten Pasuruan</span>
                </div>
                <div class="text-[11px] text-slate-500 uppercase tracking-widest font-semibold mt-0.5">
                    Badan Kepegawaian dan Pengembangan Sumber Daya Manusia (BKPSDM)
                </div>

                <div class="py-4">
                    <h2 class="text-2xl font-extrabold text-slate-900 uppercase tracking-wider font-title-cert">
                        SERTIFIKAT KELULUSAN
                    </h2>
                    <p class="text-xs font-mono text-pasuruan-800 font-bold mt-1">Nomor: {{ $cert->certificate_number }}</p>
                </div>

                <div class="space-y-1">
                    <p class="text-xs text-slate-500">Diberikan kepada:</p>
                    <h3 class="text-2xl font-extrabold text-amber-950 font-serif-cert">{{ $cert->nama_lengkap }}</h3>
                    <p class="text-xs text-slate-600">NIP. {{ $cert->nip ?: '-' }} • {{ $cert->unit_kerja ?: 'Pemerintah Kabupaten Pasuruan' }}</p>
                </div>

                <div class="text-xs text-slate-600 max-w-xl mx-auto leading-relaxed pt-3">
                    Telah mengikuti dan dinyatakan <strong class="text-emerald-700">LULUS</strong> pada kegiatan:
                    <div class="font-bold text-slate-900 text-sm mt-1">{{ $cert->training_title }}</div>
                    <div class="text-xs text-slate-700 font-medium mt-1">
                        Diselenggarakan oleh <strong class="font-bold text-slate-900">{{ $cert->organizer_unit_kerja }}</strong>
                    </div>
                    <div class="text-amber-700 font-bold text-xs mt-1">Akumulasi Jam Pelajaran: {{ $cert->jp }} JP</div>
                </div>
            </div>

            <!-- Footer TTE & Barcode Fallback -->
            <div class="pt-6 flex items-center justify-between border-t border-amber-200 text-xs">
                <div class="flex items-center gap-2.5 bg-transparent p-0 border-0 shadow-none">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode(url('/validasi/' . $cert->hash_token)) }}" 
                         class="h-14 w-14 object-contain shrink-0 bg-transparent p-0 border-0 shadow-none" alt="QR Validasi">
                    <div class="text-left text-[9px] text-slate-500 font-mono space-y-0.5">
                        <div class="font-bold text-slate-900">VERIFIKASI RESMI TTE</div>
                        <div class="text-emerald-700 font-bold text-[8.5px]">STATUS: TERDAFTAR</div>
                        <div class="text-[8px] text-slate-800 font-bold font-mono select-all tracking-tight">{{ $cert->hash_token }}</div>
                    </div>
                </div>

                <div class="text-right">
                    <div class="text-[10px] text-slate-600">{{ $template?->issue_city ?: 'Bangil, Pasuruan' }}, {{ \Carbon\Carbon::parse($cert->issued_at)->isoFormat('D MMMM Y') }}</div>
                    <div class="font-bold text-slate-900 text-xs mt-0.5">{{ $template?->signer_position ?: 'Kepala BKPSDM Kab. Pasuruan' }}</div>
                    <div class="py-1">
                        @if($template?->signature_url)
                            <img src="{{ $template->signature_url }}" class="h-9 w-auto ml-auto object-contain" alt="TTD">
                        @else
                            <img src="{{ asset('storage/specimens/spesimen_ttd_pejabat.png') }}" class="h-9 w-auto ml-auto object-contain" alt="TTD">
                        @endif
                    </div>
                    <div class="font-bold font-serif-cert text-slate-900 underline text-xs">{{ $template?->signer_name ?: 'Dr. H. Akhmad Khasani, M.Si' }}</div>
                    <div class="text-[9px] font-mono text-slate-600">NIP. {{ $template?->signer_nip ?: '19710504 199703 1 004' }}</div>
                    <div class="text-[9px] text-emerald-700 font-bold mt-0.5 flex items-center justify-end gap-1">
                        <i class="fa-solid fa-shield-check text-emerald-600"></i>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- ==================== HALAMAN 2: LAMPIRAN TRANSKRIP JP (JIKA ADA) ==================== -->
    @if($hasLampiran)
        <div class="page-break"></div>

        <div class="cert-page bg-white text-slate-900 flex flex-col justify-center relative p-12 md:p-16"
             style="{{ $lampiranBgUrl ? "background-image: url('{$lampiranBgUrl}'); border: 2px solid #475569;" : 'border: 4px double #334155;' }}">
            
            <div class="w-full">
                <div class="text-center pb-4">
                    <h3 class="text-base font-extrabold uppercase tracking-wider text-slate-900 font-title-cert">
                        TRANSKRIP MATERI & ALOKASI JAM PELAJARAN (JP)
                    </h3>
                    <p class="text-xs text-slate-600 mt-0.5">Lampiran Sertifikat Kelulusan Nomor: <span class="font-mono font-bold">{{ $cert->certificate_number }}</span></p>
                </div>

                <table class="w-full text-xs border-2 border-slate-800 border-collapse bg-white/95">
                    <thead>
                        <tr class="bg-slate-100 text-slate-900 font-bold uppercase text-[10px] tracking-wider border-b-2 border-slate-800">
                            <th class="border border-slate-400 px-3 py-2.5 text-center w-12">No</th>
                            <th class="border border-slate-400 px-4 py-2.5 text-left">Mata Pelatihan / Sesi Pembelajaran</th>
                            <th class="border border-slate-400 px-3 py-2.5 text-center w-32">Alokasi (JP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($cert->course && $cert->course->lessons && $cert->course->lessons->count() > 0)
                            @foreach($cert->course->lessons as $idx => $lesson)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="border border-slate-300 px-3 py-2 text-center font-bold text-slate-600">{{ $idx + 1 }}</td>
                                    <td class="border border-slate-300 px-4 py-2 font-medium text-slate-900">{{ $lesson->title }}</td>
                                    <td class="border border-slate-300 px-3 py-2 text-center font-bold text-slate-800">{{ $lesson->calculated_jp ?? ($lesson->jp ?? 2) }} JP</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td class="border border-slate-300 px-3 py-2 text-center font-bold text-slate-600">1</td>
                                <td class="border border-slate-300 px-4 py-2 font-medium text-slate-900">{{ $cert->training_title }}</td>
                                <td class="border border-slate-300 px-3 py-2 text-center font-bold text-slate-800">{{ $cert->jp }} JP</td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="bg-amber-50/80 font-extrabold text-slate-900 border-t-2 border-slate-800">
                            <td colspan="2" class="border border-slate-400 px-4 py-2.5 text-right uppercase tracking-wider text-xs">
                                Total Alokasi Jam Pelajaran (JP)
                            </td>
                            <td class="border border-slate-400 px-3 py-2.5 text-center font-black text-amber-900 text-xs">
                                {{ $cert->jp }} JP
                            </td>
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
    @endif

</div>

</body>
</html>
