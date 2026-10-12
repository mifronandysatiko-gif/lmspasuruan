@extends('layouts.dashboard')

@section('title', 'Hasil Pembelajaran & Evaluasi Peserta: ' . $course->title . ' - BKPSDM Pasuruan')
@section('header_title', 'Hasil Pembelajaran & Evaluasi Peserta Diklat')

@section('content')
<div class="space-y-6" x-data="{ 
    gradeModalOpen: false,
    filterStatus: 'all',
    searchQuery: '',
    shareModal: {
        open: false,
        title: '{{ addslashes($course->title) }}',
        category: '{{ addslashes($course->category) }}',
        type: '{{ strtoupper(str_replace('_', ' ', $course->type)) }}',
        jp: {{ $course->jp }},
        passing_grade: {{ $course->passing_grade }},
        url: '{{ url('/peserta/mooc/' . $course->slug) }}',
        rawMessage: `📢 *PELATIHAN MANDIRI (MOOC) BKPSDM KABUPATEN PASURUAN*\n\n` +
                    `📌 *Program:* {{ addslashes($course->title) }}\n` +
                    `📚 *Rumpun / Kategori:* {{ addslashes($course->category) }}\n` +
                    `🏷 *Tipe Program:* {{ strtoupper(str_replace('_', ' ', $course->type)) }}\n` +
                    `⏱ *Bobot:* {{ $course->jp }} JP ({{ $course->jp * 45 }} Menit)\n` +
                    `🎯 *Passing Grade Kelulusan:* ≥ {{ $course->passing_grade }}\n\n` +
                    `🔗 *Tautan Akses Ruang Belajar:* {{ url('/peserta/mooc/' . $course->slug) }}\n\n` +
                    `_Catatan: Khusus ASN Pemerintah Kabupaten Pasuruan. Silakan login untuk memulai materi pembelajaran._`,
        whatsappUrl: 'https://api.whatsapp.com/send?text=' + encodeURIComponent(`📢 *PELATIHAN MANDIRI (MOOC) BKPSDM KABUPATEN PASURUAN*\n\n📌 *Program:* {{ addslashes($course->title) }}\n📚 *Rumpun / Kategori:* {{ addslashes($course->category) }}\n🏷 *Tipe Program:* {{ strtoupper(str_replace('_', ' ', $course->type)) }}\n⏱ *Bobot:* {{ $course->jp }} JP ({{ $course->jp * 45 }} Menit)\n🎯 *Passing Grade Kelulusan:* ≥ {{ $course->passing_grade }}\n\n🔗 *Tautan Akses Ruang Belajar:* {{ url('/peserta/mooc/' . $course->slug) }}\n\n_Catatan: Khusus ASN Pemerintah Kabupaten Pasuruan. Silakan login untuk memulai materi pembelajaran._`),
        copied: false
    },
    openShare() {
        this.shareModal.open = true;
    },
    copyShareUrl() {
        navigator.clipboard.writeText(this.shareModal.url);
        this.shareModal.copied = true;
        setTimeout(() => { this.shareModal.copied = false; }, 3000);
    },
    copyShareMessage() {
        navigator.clipboard.writeText(this.shareModal.rawMessage);
        this.shareModal.copied = true;
        setTimeout(() => { this.shareModal.copied = false; }, 3000);
    },
    selectedEnrollment: {
        id: null,
        userName: '',
        userNip: '',
        userNik: '',
        userJabatan: '',
        userOpd: '',
        assignmentFile: '',
        assignmentNotes: '',
        currentScore: '',
        currentFeedback: '',
        currentStatus: 'passed'
    },
    openGradeModal(enr) {
        this.selectedEnrollment = enr;
        this.gradeModalOpen = true;
    }
}">

    <!-- Top Navigation & Breadcrumbs -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.courses.index') }}" class="hover:text-pasuruan-700 font-semibold">&larr; Katalog Pelatihan</a>
                <span>/</span>
                <a href="{{ route('admin.courses.manage', $course->id) }}" class="hover:text-pasuruan-700 font-semibold">Kelola Sesi</a>
                <span>/</span>
                <span class="text-slate-600 font-medium">Hasil Pembelajaran</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900">{{ $course->title }}</h2>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <button type="button" @click="openShare()" class="px-3.5 py-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5" title="Buat & Salin Link Sebaran Pelatihan">
                <i class="fa-solid fa-share-nodes"></i>
                <span>Bagikan Link</span>
            </button>
            <a href="{{ route('admin.courses.export_participants', $course->id) }}" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5" title="Unduh Rekap Nilai & Hasil Belajar Peserta ke Excel">
                <i class="fa-solid fa-file-excel text-emerald-200"></i>
                <span>Tarikan Data Excel</span>
            </a>
            <a href="{{ route('admin.courses.manage', $course->id) }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 transition-colors flex items-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up text-pasuruan-700"></i>
                <span>Kelola Materi Sesi</span>
            </a>
        </div>
    </div>

    <!-- Stats Summary for Results -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <div class="text-xs text-slate-400 font-medium">Peserta ASN Terdaftar</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $enrollments->count() }}</div>
            <p class="text-[11px] text-slate-500 mt-0.5">Seluruh ASN peserta</p>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <div class="text-xs text-slate-400 font-medium">Tugas/Laporan Terkumpul</div>
            <div class="text-2xl font-black text-sky-700 mt-1">{{ $enrollments->whereNotNull('assignment_submitted_at')->count() }}</div>
            <p class="text-[11px] text-sky-600 mt-0.5">Unggahan hasil belajar</p>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <div class="text-xs text-slate-400 font-medium">Peserta Dinyatakan Lulus</div>
            <div class="text-2xl font-black text-emerald-700 mt-1">{{ $enrollments->where('status', 'passed')->count() }}</div>
            <p class="text-[11px] text-emerald-600 mt-0.5">Nilai &ge; {{ $course->passing_grade }}</p>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <div class="text-xs text-slate-400 font-medium">Menunggu Telaah Widyaiswara</div>
            <div class="text-2xl font-black text-amber-600 mt-1">
                {{ $enrollments->whereNotNull('assignment_submitted_at')->whereNull('final_score')->count() }}
            </div>
            <p class="text-[11px] text-amber-700 mt-0.5">Perlu evaluasi & feedback</p>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            <button @click="filterStatus = 'all'" :class="filterStatus === 'all' ? 'bg-pasuruan-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors">
                Semua Peserta ({{ $enrollments->count() }})
            </button>
            <button @click="filterStatus = 'pending'" :class="filterStatus === 'pending' ? 'bg-pasuruan-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors">
                Menunggu Penilaian
            </button>
            <button @click="filterStatus = 'passed'" :class="filterStatus === 'passed' ? 'bg-pasuruan-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors">
                Telah Lulus
            </button>
            <button @click="filterStatus = 'revision'" :class="filterStatus === 'revision' ? 'bg-pasuruan-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors">
                Perlu Perbaikan
            </button>
        </div>

        <div class="relative w-full md:w-72">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" x-model="searchQuery" placeholder="Cari nama atau NIP ASN..." class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-pasuruan-500 focus:bg-white transition-colors">
        </div>
    </div>

    <!-- Table of Participants Learning Outcomes & Assignments -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-bold uppercase text-[10px] tracking-wider">
                        <th class="py-3.5 px-4">Peserta ASN & Identitas</th>
                        <th class="py-3.5 px-4">Jabatan & Unit Kerja</th>
                        <th class="py-3.5 px-4">Progres Modul</th>
                        <th class="py-3.5 px-4">Berkas Hasil Belajar / Tugas</th>
                        <th class="py-3.5 px-4">Nilai & Catatan Pengampu</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($enrollments as $enrollment)
                        @php
                            $user = $enrollment->user;
                            $profile = $user->profile;
                            $isPendingGrade = ($enrollment->assignment_submitted_at && is_null($enrollment->final_score));
                        @endphp
                        <tr x-show="(filterStatus === 'all' || 
                                    (filterStatus === 'pending' && {{ $isPendingGrade ? 'true' : 'false' }}) ||
                                    (filterStatus === 'passed' && '{{ $enrollment->status }}' === 'passed') ||
                                    (filterStatus === 'revision' && '{{ $enrollment->status }}' === 'revision')) && 
                                    ('{{ strtolower($user->name) }} {{ strtolower($user->nip ?? '') }} {{ strtolower($profile?->nik ?? '') }}'.includes(searchQuery.toLowerCase()))" 
                            class="hover:bg-slate-50/70 transition-colors">
                            
                            <!-- Kolom 1: Peserta ASN & Identitas -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-pasuruan-700 text-white font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-900">{{ $profile?->formatted_full_name ?? $user->name }}</h4>
                                        <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                            <span class="text-[11px] text-slate-600 font-mono font-semibold">{{ $user->nip ? 'NIP: ' . $user->nip : 'Non-ASN' }}</span>
                                            @if($profile?->nik)
                                                <span class="text-[10px] text-slate-500 font-mono bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200" title="Nomor Induk Kependudukan">
                                                    NIK: {{ $profile->nik }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom 2: Jabatan & Unit Kerja -->
                            <td class="py-3.5 px-4 max-w-xs">
                                <p class="font-semibold text-slate-800 text-xs truncate">{{ $profile?->jabatan ?? '-' }}</p>
                                <p class="text-[11px] text-slate-500 truncate">{{ $profile?->unit_kerja ?? 'Pemerintah Kabupaten Pasuruan' }}</p>
                            </td>

                            <!-- Kolom 3: Progres Modul -->
                            <td class="py-3.5 px-4">
                                <div class="w-32 space-y-1">
                                    <div class="flex items-center justify-between text-[11px] font-bold">
                                        <span class="text-slate-600">{{ $enrollment->progress_percentage }}%</span>
                                        <span class="text-slate-400 font-normal">Tuntas</span>
                                    </div>
                                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                        <div class="bg-pasuruan-700 h-full rounded-full" style="width: {{ $enrollment->progress_percentage }}%"></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom 4: Berkas Tugas / Hasil Belajar -->
                            <td class="py-3.5 px-4">
                                @if($enrollment->assignment_file)
                                    <div class="space-y-1.5 max-w-xs">
                                        <a href="{{ asset('storage/assignments/' . $enrollment->assignment_file) }}" target="_blank" class="hover:underline flex items-center gap-1.5 text-rose-600 font-bold text-xs" title="Klik untuk mengunduh/melihat berkas laporan">
                                            <i class="fa-solid fa-file-pdf text-sm"></i>
                                            <span class="truncate">{{ $enrollment->assignment_file }}</span>
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400 shrink-0"></i>
                                        </a>
                                        <div class="text-[10px] text-slate-400">
                                            Diunggah: {{ $enrollment->assignment_submitted_at ? $enrollment->assignment_submitted_at->format('d M Y, H:i') : '-' }} WIB
                                        </div>
                                        @if($enrollment->assignment_notes)
                                            <p class="text-[11px] text-slate-600 bg-slate-50 p-1.5 rounded border border-slate-200/60 italic line-clamp-2">
                                                "{{ $enrollment->assignment_notes }}"
                                            </p>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-400 italic text-[11px] flex items-center gap-1">
                                        <i class="fa-solid fa-clock text-slate-300"></i> Belum ada tugas diunggah
                                    </span>
                                @endif
                            </td>

                            <!-- Kolom 5: Nilai & Feedback Pengampu -->
                            <td class="py-3.5 px-4">
                                @if(!is_null($enrollment->final_score))
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-black {{ $enrollment->final_score >= $course->passing_grade ? 'text-emerald-700' : 'text-rose-600' }}">
                                                {{ $enrollment->final_score }}
                                            </span>
                                            <span class="text-[10px] text-slate-400">/ 100</span>
                                        </div>
                                        @if($enrollment->instructor_feedback)
                                            <p class="text-[10px] text-slate-600 max-w-xs truncate">
                                                <i class="fa-solid fa-comment-dots text-slate-400 mr-1"></i> {{ $enrollment->instructor_feedback }}
                                            </p>
                                        @endif
                                    </div>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-500 text-[10px] font-medium">
                                        Belum Dinilai
                                    </span>
                                @endif
                            </td>

                            <!-- Kolom 6: Status -->
                            <td class="py-3.5 px-4">
                                @if($enrollment->status === 'passed')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-circle-check"></i> Lulus
                                    </span>
                                @elseif($enrollment->status === 'revision')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fa-solid fa-rotate"></i> Revisi
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        <i class="fa-solid fa-spinner animate-spin text-[8px]"></i> Belajar
                                    </span>
                                @endif
                            </td>

                            <!-- Kolom 7: Aksi -->
                            <td class="py-3.5 px-4 text-center">
                                <button type="button" 
                                        @click="openGradeModal({
                                            id: {{ $enrollment->id }},
                                            userName: '{{ addslashes($profile?->formatted_full_name ?? $user->name) }}',
                                            userNip: '{{ $user->nip ?? '-' }}',
                                            userNik: '{{ $profile?->nik ?? '-' }}',
                                            userJabatan: '{{ addslashes($profile?->jabatan ?? '-') }}',
                                            userOpd: '{{ addslashes($profile?->unit_kerja ?? 'Pemerintah Kabupaten Pasuruan') }}',
                                            assignmentFile: '{{ addslashes($enrollment->assignment_file ?? '') }}',
                                            assignmentNotes: '{{ addslashes($enrollment->assignment_notes ?? '') }}',
                                            currentScore: '{{ $enrollment->final_score ?? '' }}',
                                            currentFeedback: '{{ addslashes($enrollment->instructor_feedback ?? '') }}',
                                            currentStatus: '{{ $enrollment->status ?? 'passed' }}'
                                        })"
                                        class="px-3 py-1.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-lg shadow-2xs transition-colors inline-flex items-center gap-1.5">
                                    <i class="fa-solid fa-pencil text-amber-300"></i>
                                    <span>Beri Nilai</span>
                                </button>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500">
                                <i class="fa-solid fa-user-xmark text-3xl text-slate-300 mb-2"></i>
                                <p class="font-bold text-xs text-slate-700">Belum ada peserta ASN yang terdaftar pada pelatihan ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Penilaian & Feedback Widyaiswara (Interactive) -->
    <div x-show="gradeModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2 text-pasuruan-800 font-bold text-sm">
                    <i class="fa-solid fa-certificate text-amber-500"></i>
                    <span>Form Evaluasi & Penilaian Hasil Belajar</span>
                </div>
                <button @click="gradeModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Participant Quick Info Card -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                    <span class="font-bold text-slate-900 text-sm" x-text="selectedEnrollment.userName"></span>
                    <div class="flex items-center gap-2 font-mono text-[11px] text-slate-600">
                        <span>NIP: <strong class="text-slate-800" x-text="selectedEnrollment.userNip"></strong></span>
                        <span>•</span>
                        <span>NIK: <strong class="text-slate-800" x-text="selectedEnrollment.userNik"></strong></span>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                    <span><strong class="text-slate-600">Jabatan:</strong> <span x-text="selectedEnrollment.userJabatan"></span></span>
                    <span>•</span>
                    <span><strong class="text-slate-600">Unit Kerja:</strong> <span x-text="selectedEnrollment.userOpd"></span></span>
                </div>
                <template x-if="selectedEnrollment.assignmentFile">
                    <div class="pt-2 border-t border-slate-200 mt-2 text-[11px] space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 font-medium">Berkas Laporan:</span>
                            <a :href="'{{ asset('storage/assignments') }}/' + selectedEnrollment.assignmentFile" target="_blank" class="px-2.5 py-1 bg-white hover:bg-slate-100 text-pasuruan-800 font-bold rounded-lg border border-slate-300 shadow-2xs inline-flex items-center gap-1 text-[10px] transition-colors">
                                <i class="fa-solid fa-download text-rose-500"></i>
                                <span>Unduh / Buka Dokumen</span>
                            </a>
                        </div>
                        <div class="font-bold text-rose-600 flex items-center gap-1 text-xs">
                            <i class="fa-solid fa-file-pdf"></i>
                            <span x-text="selectedEnrollment.assignmentFile"></span>
                        </div>
                        <template x-if="selectedEnrollment.assignmentNotes">
                            <p class="text-[10px] text-slate-600 italic bg-white p-2 rounded-lg border border-slate-200" x-text="'Catatan Peserta: ' + selectedEnrollment.assignmentNotes"></p>
                        </template>
                    </div>
                </template>
            </div>

            <form :action="'{{ url('/admin/courses/' . $course->id . '/grade') }}/' + selectedEnrollment.id" method="POST" class="space-y-4 text-xs">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700">Nilai Akhir (Skor 0 - 100) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.1" min="0" max="100" name="final_score" x-model="selectedEnrollment.currentScore" required placeholder="Contoh: 88.5" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 font-extrabold text-pasuruan-700 text-sm">
                    </div>

                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700">Keputusan Kelulusan <span class="text-rose-500">*</span></label>
                        <select name="status" x-model="selectedEnrollment.currentStatus" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 font-bold">
                            <option value="passed">Lulus & Terbitkan Sertifikat</option>
                            <option value="revision">Perlu Perbaikan / Revisi</option>
                            <option value="in_progress">Masih Dalam Proses</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="font-bold text-slate-700">Catatan Telaah & Feedback Widyaiswara</label>
                    <textarea name="instructor_feedback" rows="3" x-model="selectedEnrollment.currentFeedback" placeholder="Berikan catatan konstruktif terkait laporan hasil pembelajaran yang diserahkan..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500"></textarea>
                </div>

                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-2.5 text-emerald-900">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-sm shrink-0"></i>
                    <p class="text-[11px] leading-snug">
                        Nilai &ge; <strong>{{ $course->passing_grade }}</strong> dengan status <em>Lulus</em> akan secara otomatis mengesahkan perolehan <strong>{{ $course->jp }} JP</strong> bagi peserta ASN.
                    </p>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" @click="gradeModalOpen = false" class="px-4 py-2.5 text-slate-600 hover:bg-slate-100 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk text-amber-300"></i>
                        <span>Simpan Hasil Penilaian</span>
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- Modal Bagikan Link Pelatihan -->
    <div x-show="shareModal.open" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5 text-sky-800 font-bold text-sm">
                        <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center">
                            <i class="fa-solid fa-share-nodes"></i>
                        </div>
                        <span>Bagikan Tautan Pelatihan Kedinasan</span>
                    </div>
                    <button type="button" @click="shareModal.open = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Program Quick Info -->
                <div class="p-3.5 bg-sky-50/70 border border-sky-200 rounded-xl space-y-1.5">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-sky-200 text-sky-900" x-text="shareModal.type"></span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-amber-200 text-amber-900" x-text="shareModal.jp + ' JP'"></span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-200 text-emerald-900" x-text="'Min. Skor ' + shareModal.passing_grade"></span>
                    </div>
                    <h4 class="font-extrabold text-xs text-slate-900" x-text="shareModal.title"></h4>
                    <p class="text-[11px] text-slate-600" x-text="shareModal.category"></p>
                </div>

                <!-- Direct URL Input with Copy Button -->
                <div class="space-y-1.5">
                    <label class="font-bold text-xs text-slate-700 block">Tautan Langsung Ruang Belajar (Direct URL):</label>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly :value="shareModal.url" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono text-slate-700 focus:outline-none select-all">
                        <button type="button" @click="copyShareUrl()" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5 shrink-0 cursor-pointer">
                            <i class="fa-solid" :class="shareModal.copied ? 'fa-check text-emerald-300' : 'fa-copy'"></i>
                            <span x-text="shareModal.copied ? 'Tersalin!' : 'Salin'"></span>
                        </button>
                    </div>
                </div>

                <!-- WhatsApp Message Preview -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="font-bold text-xs text-slate-700">Format Pesan Undangan (Siap Sebar):</label>
                        <button type="button" @click="copyShareMessage()" class="text-[11px] font-bold text-sky-700 hover:text-sky-900 flex items-center gap-1">
                            <i class="fa-solid fa-copy"></i>
                            <span>Salin Teks Lengkap</span>
                        </button>
                    </div>
                    <textarea readonly rows="5" :value="shareModal.rawMessage" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-600 leading-relaxed resize-none focus:outline-none select-all"></textarea>
                </div>

                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-[11px] flex items-start gap-2">
                    <i class="fa-solid fa-circle-info text-amber-600 text-xs mt-0.5"></i>
                    <span>Tautan ini aman disebarkan ke grup OPD/WhatsApp ASN. Peserta yang belum login akan otomatis diarahkan untuk masuk terlebih dahulu, lalu otomatis membuka ruang materi pelatihan ini.</span>
                </div>

                <!-- Footer Actions -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                    <button type="button" @click="shareModal.open = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 font-bold text-xs rounded-xl transition-colors">
                        Tutup
                    </button>
                    <a :href="shareModal.whatsappUrl" target="_blank" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                        <i class="fa-brands fa-whatsapp text-sm"></i>
                        <span>Sebar ke WhatsApp</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
