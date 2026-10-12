@extends('layouts.dashboard')

@section('title', 'Dashboard Hasil Kuesioner & Evaluasi Kepuasan - BKPSDM Pasuruan')
@section('header_title', 'Dashboard Hasil Kuesioner & Evaluasi Kepuasan')

@section('content')
<div class="space-y-6" x-data="{ 
    activeTab: '{{ $tab }}' || 'all',
    searchQuery: '{{ $search ?? '' }}'
}">

    <!-- Banner Header & Tombol Tarikan Data -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-pasuruan-900 via-pasuruan-800 to-slate-900 text-white shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-2 relative z-10 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30 text-xs font-semibold">
                <i class="fa-solid fa-square-poll-vertical"></i>
                <span>Customer Satisfaction (CSAT) & Evaluasi Penyelenggaraan</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white">
                Dashboard Analisis Kuesioner Diklat & Webinar
            </h2>
            <p class="text-xs sm:text-sm text-pasuruan-100 leading-relaxed">
                Pemantauan real-time tingkat kepuasan ASN, efektivitas materi kurikulum, kualitas instruktur/widyaiswara, keandalan sistem LMS, dan kritik-saran konstruktif se-Kabupaten Pasuruan.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3 relative z-10 shrink-0">
            <a href="{{ route('admin.questionnaires.export_all') }}" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg transition-all flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-file-excel text-amber-300 text-sm"></i>
                <span>Tarik Rekap Semua Kuesioner (Excel)</span>
            </a>
        </div>

        <!-- Decorative background elements -->
        <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-pasuruan-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-1/3 -top-12 w-48 h-48 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>
    </div>

    <!-- Ringkasan Statistik Metrik Agregat -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Respon Tercatat -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">Total Responden Kuesioner</span>
                <div class="w-9 h-9 rounded-xl bg-pasuruan-50 text-pasuruan-700 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-users-viewfinder"></i>
                </div>
            </div>
            <div class="space-y-1">
                <div class="text-2xl font-black text-slate-900 tracking-tight">{{ number_format($totalResponses) }}</div>
                <div class="text-[11px] text-slate-500 flex items-center gap-2">
                    <span class="text-pasuruan-700 font-bold">{{ $totalWebinarResponses }} Webinar</span>
                    <span>&bull;</span>
                    <span class="text-amber-600 font-bold">{{ $totalCourseResponses }} Diklat MOOC</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Indeks Kepuasan Rata-rata -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">Rata-rata Skor CSAT (Diklat)</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-star"></i>
                </div>
            </div>
            <div class="space-y-1">
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-amber-500 tracking-tight">{{ $avgCourseRating }}</span>
                    <span class="text-xs text-slate-400 font-bold">/ 5.0</span>
                </div>
                <div class="text-[11px] text-emerald-600 font-bold flex items-center gap-1">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Kategori Sangat Memuaskan</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Total Masukan & Feedback Kualitatif -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">Kritik, Saran & Masukan ASN</span>
                <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-700 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-comments"></i>
                </div>
            </div>
            <div class="space-y-1">
                <div class="text-2xl font-black text-slate-900 tracking-tight">{{ number_format($totalFeedbackText) }}</div>
                <div class="text-[11px] text-slate-500">Ulasan & masukan peningkatan mutu</div>
            </div>
        </div>

        <!-- Card 4: Total Program Kegiatan -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500">Total Program Terpantau</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-cubes-stacked"></i>
                </div>
            </div>
            <div class="space-y-1">
                <div class="text-2xl font-black text-slate-900 tracking-tight">{{ $webinars->count() + $courses->count() }}</div>
                <div class="text-[11px] text-slate-500">{{ $webinars->count() }} Webinar & {{ $courses->count() }} Pelatihan MOOC</div>
            </div>
        </div>
    </div>

    <!-- Filter Tab & Search Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Tab Buttons -->
            <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl overflow-x-auto">
                <button type="button" @click="activeTab = 'all'" :class="activeTab === 'all' ? 'bg-white text-pasuruan-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'" class="px-4 py-2 rounded-lg text-xs transition-all cursor-pointer shrink-0">
                    <i class="fa-solid fa-list-ul mr-1.5 text-slate-400"></i>
                    <span>Semua Kegiatan ({{ $webinars->count() + $courses->count() }})</span>
                </button>
                <button type="button" @click="activeTab = 'webinar'" :class="activeTab === 'webinar' ? 'bg-white text-pasuruan-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'" class="px-4 py-2 rounded-lg text-xs transition-all cursor-pointer shrink-0">
                    <i class="fa-solid fa-video mr-1.5 text-pasuruan-600"></i>
                    <span>Webinar & Bimtek ({{ $webinars->count() }})</span>
                </button>
                <button type="button" @click="activeTab = 'course'" :class="activeTab === 'course' ? 'bg-white text-pasuruan-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'" class="px-4 py-2 rounded-lg text-xs transition-all cursor-pointer shrink-0">
                    <i class="fa-solid fa-graduation-cap mr-1.5 text-amber-500"></i>
                    <span>Pelatihan MOOC & Diklat ({{ $courses->count() }})</span>
                </button>
            </div>

            <!-- Search Form -->
            <form action="{{ route('admin.questionnaires.index') }}" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="tab" :value="activeTab">
                <div class="relative w-full md:w-72">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari judul kegiatan..." class="w-full pl-9 pr-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:border-pasuruan-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-pasuruan-700 hover:bg-pasuruan-800 text-white rounded-xl text-xs font-bold transition-colors cursor-pointer shrink-0">
                    Cari
                </button>
                @if($search)
                    <a href="{{ route('admin.questionnaires.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition-colors">
                        Reset
                    </a>
                @endif
            </form>
        </div>
    </div>

    <!-- Section 1: Daftar Hasil Kuesioner Webinar -->
    <div x-show="activeTab === 'all' || activeTab === 'webinar'" class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                <span class="w-2.5 h-2.5 rounded-full bg-pasuruan-700"></span>
                <h3>Evaluasi & Kuesioner Sesi Webinar Interaktif</h3>
            </div>
            <span class="text-xs text-slate-500 font-medium">{{ $webinars->count() }} Sesi Webinar</span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-bold">
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4 min-w-[240px]">Judul Webinar & Kategori</th>
                            <th class="py-3.5 px-4 min-w-[140px]">Jadwal Pelaksanaan</th>
                            <th class="py-3.5 px-4 text-center min-w-[110px]">Hadir / Peserta</th>
                            <th class="py-3.5 px-4 text-center min-w-[130px]">Responden Kuesioner</th>
                            <th class="py-3.5 px-4 text-center min-w-[100px]">Butir Soal</th>
                            <th class="py-3.5 px-4 text-right min-w-[180px]">Aksi & Laporan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($webinars as $idx => $webinar)
                            @php
                                $attendees = $webinar->attendances_count ?: 1;
                                $respCount = $webinar->questionnaire_responses_count;
                                $respPct = $attendees > 0 ? min(100, round(($respCount / $attendees) * 100)) : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3.5 px-4 text-center font-bold text-slate-400">{{ $idx + 1 }}</td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 leading-snug">{{ $webinar->title }}</div>
                                    <div class="flex items-center gap-2 mt-1 text-[11px]">
                                        <span class="px-2 py-0.5 rounded bg-pasuruan-50 text-pasuruan-700 font-semibold border border-pasuruan-100">
                                            {{ $webinar->category }}
                                        </span>
                                        <span class="text-slate-400">&bull; {{ $webinar->jp }} JP</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    <div class="font-medium text-[11px]">{{ $webinar->event_date ? \Carbon\Carbon::parse($webinar->event_date)->isoFormat('D MMM Y') : '-' }}</div>
                                    <div class="text-[10.5px] text-slate-400">{{ $webinar->start_time ? substr($webinar->start_time, 0, 5) . ' WIB' : '-' }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="font-bold text-slate-700">{{ $webinar->attendances_count }} ASN</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="inline-flex flex-col items-center">
                                        <span class="font-bold text-pasuruan-700 text-xs">{{ $respCount }} ASN</span>
                                        <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden mt-1">
                                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $respPct }}%"></div>
                                        </div>
                                        <span class="text-[9.5px] text-slate-400 mt-0.5">{{ $respPct }}% respons</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ $webinar->questions->count() }} Butir
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('admin.questionnaires.webinar', $webinar->id) }}" class="px-3 py-1.5 rounded-lg bg-pasuruan-50 hover:bg-pasuruan-100 text-pasuruan-700 font-bold text-[11px] border border-pasuruan-200 transition-colors flex items-center gap-1">
                                            <i class="fa-solid fa-chart-pie"></i>
                                            <span>Analisis Grafik</span>
                                        </a>
                                        <a href="{{ route('admin.questionnaires.webinar.export', $webinar->id) }}" title="Tarik Data Excel Kuesioner Sesi Ini" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition-colors">
                                            <i class="fa-solid fa-file-excel"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    <i class="fa-solid fa-clipboard-question text-3xl mb-2 text-slate-300 block"></i>
                                    <span>Belum ada data kegiatan webinar yang sesuai filter.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section 2: Daftar Hasil Kuesioner Pelatihan MOOC & Diklat -->
    <div x-show="activeTab === 'all' || activeTab === 'course'" class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                <h3>Evaluasi & Kuesioner Pelatihan Mandiri (MOOC / Bimtek)</h3>
            </div>
            <span class="text-xs text-slate-500 font-medium">{{ $courses->count() }} Pelatihan MOOC</span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-bold">
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4 min-w-[240px]">Judul Pelatihan & Tipe Program</th>
                            <th class="py-3.5 px-4 min-w-[130px]">Kategori & Bobot</th>
                            <th class="py-3.5 px-4 text-center min-w-[100px]">Total Enrolled</th>
                            <th class="py-3.5 px-4 text-center min-w-[130px]">Pengisi Kuesioner</th>
                            <th class="py-3.5 px-4 text-center min-w-[130px]">Skor Rata-rata</th>
                            <th class="py-3.5 px-4 text-right min-w-[180px]">Aksi & Laporan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($courses as $idx => $course)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3.5 px-4 text-center font-bold text-slate-400">{{ $idx + 1 }}</td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 leading-snug">{{ $course->title }}</div>
                                    <div class="flex items-center gap-2 mt-1 text-[10.5px] text-slate-400">
                                        <span class="uppercase font-bold text-amber-600">{{ str_replace('_', ' ', $course->type) }}</span>
                                        <span>&bull; Passing Grade: {{ $course->passing_grade }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    <div class="font-medium text-[11px]">{{ $course->category }}</div>
                                    <div class="text-[10.5px] text-slate-400">{{ $course->jp }} JP ({{ $course->jp * 45 }} Menit)</div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="font-bold text-slate-700">{{ $course->enrollments_count }} Peserta</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full font-bold text-[11px] {{ $course->questionnaire_responses_count > 0 ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $course->questionnaire_responses_count }} Responden
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($course->avg_score > 0)
                                        <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 font-bold text-xs">
                                            <i class="fa-solid fa-star text-amber-500"></i>
                                            <span>{{ $course->avg_score }} / 5</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 text-[11px] italic">Belum ada nilai</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('admin.questionnaires.course', $course->id) }}" class="px-3 py-1.5 rounded-lg bg-pasuruan-50 hover:bg-pasuruan-100 text-pasuruan-700 font-bold text-[11px] border border-pasuruan-200 transition-colors flex items-center gap-1">
                                            <i class="fa-solid fa-chart-pie"></i>
                                            <span>Analisis Detail</span>
                                        </a>
                                        <a href="{{ route('admin.questionnaires.course.export', $course->id) }}" title="Tarik Data Excel Kuesioner Pelatihan Ini" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition-colors">
                                            <i class="fa-solid fa-file-excel"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    <i class="fa-solid fa-graduation-cap text-3xl mb-2 text-slate-300 block"></i>
                                    <span>Belum ada data pelatihan MOOC yang sesuai filter.</span>
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
