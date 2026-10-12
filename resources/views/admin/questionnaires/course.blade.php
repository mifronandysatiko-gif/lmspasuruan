@extends('layouts.dashboard')

@section('title', 'Analisis Kuesioner Pelatihan: ' . $course->title . ' - BKPSDM Pasuruan')
@section('header_title', 'Analisis Kuesioner Pelatihan Mandiri (MOOC)')

@section('content')
<div class="space-y-6">

    <!-- Navigasi & Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 text-xs">
            <a href="{{ route('admin.questionnaires.index') }}" class="text-slate-500 hover:text-pasuruan-700 font-bold transition-colors flex items-center gap-1">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali ke Dashboard Kuesioner</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-pasuruan-800 font-bold truncate max-w-xs sm:max-w-md">{{ $course->title }}</span>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.questionnaires.course.export', $course->id) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-file-excel text-amber-300 text-sm"></i>
                <span>Unduh Hasil Kuesioner (Excel)</span>
            </a>
        </div>
    </div>

    <!-- Banner Header Info Pelatihan -->
    <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-4 border-b border-slate-100">
            <div class="space-y-2 max-w-2xl">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="px-2.5 py-0.5 rounded-full font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase">
                        {{ str_replace('_', ' ', $course->type) }}
                    </span>
                    <span class="text-slate-400">&bull;</span>
                    <span class="text-slate-600 font-medium">{{ $course->category }}</span>
                    <span class="text-slate-400">&bull;</span>
                    <span class="text-slate-600 font-medium">{{ $course->jp }} JP ({{ $course->jp * 45 }} Menit)</span>
                    <span class="text-slate-400">&bull;</span>
                    <span class="text-slate-600 font-medium">Passing Grade: &ge; {{ $course->passing_grade }}</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 leading-snug">
                    {{ $course->title }}
                </h1>
            </div>

            <!-- Kartu Skor Indeks Kepuasan Rata-rata (CSAT) -->
            <div class="flex items-center gap-5 bg-gradient-to-br from-amber-50 to-orange-50/60 p-5 rounded-2xl border border-amber-200 shadow-xs shrink-0">
                <div class="text-center space-y-0.5">
                    <span class="text-[11px] text-amber-900/80 font-bold uppercase tracking-wider block">Skor CSAT Mutu</span>
                    <div class="flex items-baseline justify-center gap-1">
                        <span class="text-3xl font-black text-amber-600 tracking-tight">{{ $overallAvg }}</span>
                        <span class="text-xs text-amber-800 font-bold">/ 5.0</span>
                    </div>
                    <div class="flex items-center justify-center gap-0.5 text-amber-400 text-xs mt-1">
                        @for($s = 1; $s <= 5; $s++)
                            <i class="fa-solid fa-star {{ $s <= round($overallAvg) ? 'text-amber-500' : 'text-slate-200' }}"></i>
                        @endfor
                    </div>
                </div>

                <div class="w-px h-12 bg-amber-200"></div>

                <div class="space-y-1 text-xs">
                    <div>
                        <span class="text-slate-500 font-medium">Total Responden:</span>
                        <strong class="text-slate-900 ml-1">{{ $totalResp }} ASN</strong>
                    </div>
                    <div>
                        <span class="text-slate-500 font-medium">Peserta Terdaftar:</span>
                        <strong class="text-slate-900 ml-1">{{ $course->enrollments_count }} ASN</strong>
                    </div>
                    <div class="text-[11px] text-emerald-700 font-bold flex items-center gap-1 pt-0.5">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Tingkat Kepuasan Tinggi</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-xs text-slate-600 leading-relaxed">
            Evaluasi kepuasan peserta pada pelatihan MOOC ini diukur melalui 4 pilar standar pengembangan kompetensi ASN Lembaga Administrasi Negara (LAN RI) dan BKN.
        </div>
    </div>

    <!-- Section 1: Rincian 4 Pilar Evaluasi & Distribusi Rating -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Kolom Kiri: 4 Dimensi Pilar Evaluasi -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                    <i class="fa-solid fa-chart-simple text-pasuruan-700"></i>
                    <h2>Rincian 4 Dimensi Evaluasi Mutu Diklat</h2>
                </div>
                <span class="text-xs text-slate-400">Skala 1 - 5</span>
            </div>

            <div class="space-y-4">
                <!-- Dimensi 1: Kesesuaian Materi -->
                <div class="space-y-1.5 p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0">1</span>
                            <div>
                                <span class="font-bold text-slate-800 block">Kesesuaian & Kualitas Materi Pembelajaran</span>
                                <span class="text-[11px] text-slate-500">Materi relevan, sistematis, aplikatif, dan sesuai standar kompetensi</span>
                            </div>
                        </div>
                        <div class="text-right shrink-0 font-black text-slate-900 text-sm">
                            {{ $avgMateri }} <span class="text-xs text-slate-400 font-normal">/ 5</span>
                        </div>
                    </div>
                    <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden mt-2">
                        <div class="h-full bg-blue-600 rounded-full transition-all duration-500" style="width: {{ ($avgMateri / 5) * 100 }}%"></div>
                    </div>
                </div>

                <!-- Dimensi 2: Kemudahan LMS -->
                <div class="space-y-1.5 p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">2</span>
                            <div>
                                <span class="font-bold text-slate-800 block">Kemudahan & Keandalan Platform LMS</span>
                                <span class="text-[11px] text-slate-500">Aplikasi responsif, pemutaran lancar, dan navigasi jelas</span>
                            </div>
                        </div>
                        <div class="text-right shrink-0 font-black text-slate-900 text-sm">
                            {{ $avgSistem }} <span class="text-xs text-slate-400 font-normal">/ 5</span>
                        </div>
                    </div>
                    <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden mt-2">
                        <div class="h-full bg-emerald-600 rounded-full transition-all duration-500" style="width: {{ ($avgSistem / 5) * 100 }}%"></div>
                    </div>
                </div>

                <!-- Dimensi 3: Kemanfaatan Tugas ASN -->
                <div class="space-y-1.5 p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-xs shrink-0">3</span>
                            <div>
                                <span class="font-bold text-slate-800 block">Kemanfaatan terhadap Pelaksanaan Tugas ASN</span>
                                <span class="text-[11px] text-slate-500">Mendukung penyelesaian tugas harian, pelayanan publik, dan target kinerja</span>
                            </div>
                        </div>
                        <div class="text-right shrink-0 font-black text-slate-900 text-sm">
                            {{ $avgManfaat }} <span class="text-xs text-slate-400 font-normal">/ 5</span>
                        </div>
                    </div>
                    <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden mt-2">
                        <div class="h-full bg-purple-600 rounded-full transition-all duration-500" style="width: {{ ($avgManfaat / 5) * 100 }}%"></div>
                    </div>
                </div>

                <!-- Dimensi 4: Kualitas Modul & Penyajian -->
                <div class="space-y-1.5 p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xs shrink-0">4</span>
                            <div>
                                <span class="font-bold text-slate-800 block">Kualitas Modul Pembelajaran & Penyajian</span>
                                <span class="text-[11px] text-slate-500">Audio/video jernih, kejelasan bahan ajar, dan instruksi penugasan</span>
                            </div>
                        </div>
                        <div class="text-right shrink-0 font-black text-slate-900 text-sm">
                            {{ $avgInstruktur }} <span class="text-xs text-slate-400 font-normal">/ 5</span>
                        </div>
                    </div>
                    <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden mt-2">
                        <div class="h-full bg-amber-500 rounded-full transition-all duration-500" style="width: {{ ($avgInstruktur / 5) * 100 }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Sebaran Distribusi Rating Bintang -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                    <i class="fa-solid fa-star-half-stroke text-amber-500"></i>
                    <h2>Distribusi Skor Kepuasan</h2>
                </div>
            </div>

            <div class="space-y-3">
                @for($bintang = 5; $bintang >= 1; $bintang--)
                    @php
                        $count = $ratingDistribution[$bintang] ?? 0;
                        $pct = $totalResp > 0 ? round(($count / $totalResp) * 100) : 0;
                    @endphp
                    <div class="space-y-1 text-xs">
                        <div class="flex items-center justify-between text-[11px]">
                            <div class="flex items-center gap-1 font-bold text-slate-700">
                                <span>{{ $bintang }}</span>
                                <i class="fa-solid fa-star text-amber-400 text-[10px]"></i>
                            </div>
                            <span class="font-medium text-slate-600">{{ $count }} Responden ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-amber-400 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endfor
            </div>

            <div class="pt-4 border-t border-slate-100 text-center text-xs text-slate-500 space-y-1">
                <i class="fa-solid fa-shield-check text-emerald-600 text-lg"></i>
                <p class="font-medium">Data evaluasi terintegrasi langsung dengan modul akreditasi diklat BKPSDM Pasuruan.</p>
            </div>
        </div>

    </div>

    <!-- Section 2: Dinding Ulasan, Kritik & Saran Kualitatif ASN -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                <i class="fa-solid fa-comments text-pasuruan-700"></i>
                <h2>Ulasan, Kritik & Saran Perbaikan ASN ({{ $feedbacks->count() }} Masukan)</h2>
            </div>
        </div>

        @if($feedbacks->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-400">
                <i class="fa-solid fa-comment-slash text-3xl mb-2 text-slate-300 block"></i>
                <span>Belum ada catatan kritik atau saran teks tertulis untuk pelatihan ini.</span>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($feedbacks as $fb)
                    @php
                        $userFb = $fb->user;
                        $nama = $fb->nama_lengkap ?: $userFb?->profile?->formatted_full_name ?: $userFb?->name ?: 'Peserta ASN';
                        $jabatan = $fb->jabatan ?: $userFb?->profile?->jabatan ?: $userFb?->jabatan ?: '-';
                        $unitKerja = $fb->unit_kerja ?: $userFb?->profile?->unit_kerja ?: $userFb?->unit_kerja ?: '-';
                        $waktu = $fb->submitted_at ? $fb->submitted_at->format('d M Y, H:i') : ($fb->created_at ? $fb->created_at->format('d M Y, H:i') : '-');
                    @endphp
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-3 flex flex-col justify-between">
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-0.5 text-amber-400 text-xs">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fa-solid fa-star {{ $i <= round($fb->average_rating) ? 'text-amber-500' : 'text-slate-200' }}"></i>
                                    @endfor
                                    <span class="text-[11px] font-bold text-amber-600 ml-1">({{ $fb->average_rating }})</span>
                                </div>
                                <span class="text-[10px] text-slate-400">{{ $waktu }}</span>
                            </div>

                            <p class="text-xs text-slate-700 italic leading-relaxed bg-amber-50/40 p-3 rounded-xl border border-amber-100/80">
                                "{{ $fb->feedback_text }}"
                            </p>
                        </div>

                        <div class="pt-2 border-t border-slate-100 flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-pasuruan-100 text-pasuruan-800 flex items-center justify-center font-bold text-xs shrink-0">
                                {{ strtoupper(substr($nama, 0, 1)) }}
                            </div>
                            <div class="overflow-hidden">
                                <div class="font-bold text-xs text-slate-900 truncate">{{ $nama }}</div>
                                <div class="text-[10px] text-slate-400 truncate">{{ $jabatan }} &bull; {{ $unitKerja }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Section 3: Tabel Rekap Seluruh Responden ASN -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                <i class="fa-solid fa-list-check text-pasuruan-700"></i>
                <h2>Data Lengkap Responden Evaluasi ({{ $responses->count() }} Orang)</h2>
            </div>
            <a href="{{ route('admin.questionnaires.course.export', $course->id) }}" class="text-xs font-bold text-pasuruan-700 hover:text-pasuruan-900 flex items-center gap-1">
                <i class="fa-solid fa-file-excel"></i>
                <span>Unduh Lengkap (.CSV/Excel)</span>
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-bold">
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4 min-w-[200px]">Identitas Pegawai (ASN)</th>
                            <th class="py-3.5 px-4 min-w-[180px]">Jabatan & Unit Kerja</th>
                            <th class="py-3.5 px-4 text-center min-w-[80px]">Materi</th>
                            <th class="py-3.5 px-4 text-center min-w-[80px]">Sistem</th>
                            <th class="py-3.5 px-4 text-center min-w-[80px]">Manfaat</th>
                            <th class="py-3.5 px-4 text-center min-w-[80px]">Modul</th>
                            <th class="py-3.5 px-4 text-center min-w-[100px]">Rata-rata</th>
                            <th class="py-3.5 px-4 min-w-[200px]">Masukan / Saran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($responses as $idx => $resp)
                            @php
                                $u = $resp->user;
                                $nip = $resp->nip ?: $u?->nip ?: '-';
                                $nama = $resp->nama_lengkap ?: $u?->profile?->formatted_full_name ?: $u?->name ?: '-';
                                $jabatan = $resp->jabatan ?: $u?->profile?->jabatan ?: $u?->jabatan ?: '-';
                                $unitKerja = $resp->unit_kerja ?: $u?->profile?->unit_kerja ?: $u?->unit_kerja ?: '-';
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3.5 px-4 text-center font-bold text-slate-400">{{ $idx + 1 }}</td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900">{{ $nama }}</div>
                                    <div class="font-mono text-[11px] text-slate-500 mt-0.5">NIP. {{ $nip }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    <div class="font-medium text-[11px]">{{ $jabatan }}</div>
                                    <div class="text-[10.5px] text-slate-400 mt-0.5">{{ $unitKerja }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="font-bold text-blue-700">{{ $resp->rating_materi }}/5</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="font-bold text-emerald-700">{{ $resp->rating_sistem }}/5</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="font-bold text-purple-700">{{ $resp->rating_manfaat }}/5</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="font-bold text-amber-700">{{ $resp->rating_instruktur }}/5</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-lg font-black text-xs bg-amber-50 text-amber-700 border border-amber-200">
                                        {{ $resp->average_rating }} / 5
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 text-[11px] max-w-xs">
                                    {{ $resp->feedback_text ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-slate-400">
                                    <i class="fa-solid fa-inbox text-3xl mb-2 text-slate-300 block"></i>
                                    <span>Belum ada peserta yang mengisi kuesioner evaluasi pada pelatihan ini.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
