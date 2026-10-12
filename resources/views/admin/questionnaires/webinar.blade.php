@extends('layouts.dashboard')

@section('title', 'Analisis Kuesioner Webinar: ' . $webinar->title . ' - BKPSDM Pasuruan')
@section('header_title', 'Analisis Kuesioner Webinar & Bimtek')

@section('content')
@php
    $attendeesCount = $webinar->attendances_count ?: ($webinar->attendances->count() ?: 1);
    $totalResp = $responses->count();
    $respPercentage = $attendeesCount > 0 ? min(100, round(($totalResp / $attendeesCount) * 100)) : 0;
@endphp

<div class="space-y-6">

    <!-- Navigasi & Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 text-xs">
            <a href="{{ route('admin.questionnaires.index') }}" class="text-slate-500 hover:text-pasuruan-700 font-bold transition-colors flex items-center gap-1">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali ke Dashboard Kuesioner</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-pasuruan-800 font-bold truncate max-w-xs sm:max-w-md">{{ $webinar->title }}</span>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.questionnaires.webinar.export', $webinar->id) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-file-excel text-amber-300 text-sm"></i>
                <span>Unduh Hasil Kuesioner (Excel)</span>
            </a>
        </div>
    </div>

    <!-- Banner Header Info Webinar -->
    <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div class="space-y-1.5 max-w-3xl">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="px-2.5 py-0.5 rounded-full font-bold bg-pasuruan-100 text-pasuruan-800 border border-pasuruan-200">
                        {{ $webinar->category }}
                    </span>
                    <span class="text-slate-400">&bull;</span>
                    <span class="text-slate-600 font-medium">{{ $webinar->jp }} JP ({{ $webinar->jp * 45 }} Menit)</span>
                    <span class="text-slate-400">&bull;</span>
                    <span class="text-slate-600 font-medium">{{ $webinar->event_date ? \Carbon\Carbon::parse($webinar->event_date)->isoFormat('D MMMM Y') : '-' }}{{ $webinar->start_time ? ', ' . substr($webinar->start_time, 0, 5) . ' WIB' : '' }}</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 leading-snug">
                    {{ $webinar->title }}
                </h1>
            </div>

            <!-- Metrik Partisipasi Responden -->
            <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200 shrink-0">
                <div class="text-center">
                    <span class="text-[10px] text-slate-500 font-bold block uppercase">Kehadiran</span>
                    <span class="text-lg font-black text-slate-800">{{ $webinar->attendances_count }}</span>
                    <span class="text-[10px] text-slate-400 block">ASN Hadir</span>
                </div>
                <div class="w-px h-8 bg-slate-200"></div>
                <div class="text-center">
                    <span class="text-[10px] text-pasuruan-700 font-bold block uppercase">Kuesioner</span>
                    <span class="text-lg font-black text-pasuruan-700">{{ $totalResp }}</span>
                    <span class="text-[10px] text-emerald-600 font-bold block">{{ $respPercentage }}% Respon</span>
                </div>
                <div class="w-px h-8 bg-slate-200"></div>
                <div class="text-center">
                    <span class="text-[10px] text-slate-500 font-bold block uppercase">Instrumen</span>
                    <span class="text-lg font-black text-slate-800">{{ $questions->count() }}</span>
                    <span class="text-[10px] text-slate-400 block">Butir Soal</span>
                </div>
            </div>
        </div>

        <div class="text-xs text-slate-600 leading-relaxed">
            Berikut adalah rekapitulasi data hasil pengisian kuesioner evaluasi oleh peserta ASN yang telah mengikuti seluruh rangkaian sesi webinar dan absensi resmi.
        </div>
    </div>

    <!-- Section: Analisis Distribusi Jawaban per Butir Pertanyaan Kuesioner -->
    <div class="space-y-4">
        <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
            <i class="fa-solid fa-chart-pie text-pasuruan-700"></i>
            <h2>Distribusi & Analisis Jawaban Kuesioner</h2>
        </div>

        @if($questions->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-400 space-y-2">
                <i class="fa-solid fa-clipboard-question text-3xl text-slate-300 block"></i>
                <p class="font-bold text-xs text-slate-700">Belum ada butir pertanyaan kuesioner yang dikonfigurasi pada sesi webinar ini.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($questions as $idx => $q)
                    @php
                        $stat = $questionStats[$q->id] ?? null;
                        $totalAns = $stat['total_answered'] ?? 0;
                        $dist = $stat['distribution'] ?? [];
                        $textAnswers = $stat['text_answers'] ?? [];
                    @endphp

                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4 flex flex-col justify-between">
                        <div class="space-y-2">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-pasuruan-700 text-white font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ $idx + 1 }}
                                    </span>
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pertanyaan #{{ $idx + 1 }}</span>
                                </div>
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-[10.5px] font-bold rounded-full border border-slate-200">
                                    {{ $totalAns }} Terjawab
                                </span>
                            </div>
                            
                            <h3 class="font-bold text-xs text-slate-900 leading-snug">
                                {{ $q->question_text }}
                            </h3>
                        </div>

                        <!-- Opsi Pilihan Berganda / Skala Distribusi -->
                        @if(!empty($dist))
                            <div class="space-y-2.5 pt-2 border-t border-slate-100">
                                @foreach($dist as $optionLabel => $count)
                                    @php
                                        $pct = $totalAns > 0 ? round(($count / $totalAns) * 100, 1) : 0;
                                    @endphp
                                    <div class="space-y-1 text-xs">
                                        <div class="flex items-center justify-between text-[11px]">
                                            <span class="font-medium text-slate-700">{{ $optionLabel }}</span>
                                            <span class="font-bold text-slate-900">{{ $count }} ASN <span class="text-slate-400 font-normal">({{ $pct }}%)</span></span>
                                        </div>
                                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-pasuruan-600 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Ulasan Teks Bebas jika ada -->
                        @if(!empty($textAnswers))
                            <div class="space-y-2 pt-2 border-t border-slate-100">
                                <span class="text-[11px] font-bold text-slate-500 block">Masukan & Respon Teks Terbuka:</span>
                                <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                                    @foreach(array_slice($textAnswers, 0, 5) as $ans)
                                        <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100 text-[11px] space-y-1">
                                            <p class="text-slate-700 italic">"{{ $ans['answer'] }}"</p>
                                            <div class="text-[10px] text-slate-400 flex items-center justify-between">
                                                <span>{{ $ans['user']?->name ?? 'Peserta ASN' }}</span>
                                                <span>{{ $ans['time'] ? \Carbon\Carbon::parse($ans['time'])->format('d/m H:i') : '' }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Section: Tabel Rekapitulasi Seluruh Responden ASN -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                <i class="fa-solid fa-list-check text-pasuruan-700"></i>
                <h2>Daftar Lengkap Responden ASN ({{ $responses->count() }} Orang)</h2>
            </div>
            <a href="{{ route('admin.questionnaires.webinar.export', $webinar->id) }}" class="text-xs font-bold text-pasuruan-700 hover:text-pasuruan-900 flex items-center gap-1">
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
                            <th class="py-3.5 px-4 min-w-[140px]">Waktu Submit</th>
                            <th class="py-3.5 px-4 min-w-[250px]">Ringkasan Jawaban</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($responses as $idx => $resp)
                            @php
                                $userResp = $resp->user;
                                $nip = $resp->nip ?: $userResp?->nip ?: '-';
                                $nama = $resp->nama_lengkap ?: $userResp?->profile?->formatted_full_name ?: $userResp?->name ?: '-';
                                $jabatan = $resp->jabatan ?: $userResp?->profile?->jabatan ?: $userResp?->jabatan ?: '-';
                                $unitKerja = $resp->unit_kerja ?: $userResp?->profile?->unit_kerja ?: $userResp?->unit_kerja ?: '-';
                                $ansArray = is_array($resp->answers) ? $resp->answers : [];
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
                                <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                    {{ $resp->submitted_at ? $resp->submitted_at->format('d M Y, H:i') : ($resp->created_at ? $resp->created_at->format('d M Y, H:i') : '-') }} WIB
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex flex-wrap gap-1.5 max-w-sm">
                                        @foreach($questions as $q)
                                            @php
                                                $val = $ansArray[$q->id] ?? ($ansArray[(string)$q->id] ?? '-');
                                                $displayVal = is_array($val) ? implode(', ', $val) : (string)$val;
                                            @endphp
                                            <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-[10.5px] text-slate-700" title="{{ $q->question_text }}: {{ $displayVal }}">
                                                <strong class="text-pasuruan-700">Q{{ $q->order_number ?: ($idx + 1) }}:</strong> {{ Str::limit($displayVal, 20) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400">
                                    <i class="fa-solid fa-inbox text-3xl mb-2 text-slate-300 block"></i>
                                    <span>Belum ada peserta yang mengisi kuesioner pada sesi webinar ini.</span>
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
