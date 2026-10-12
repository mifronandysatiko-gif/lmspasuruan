@extends('layouts.dashboard')

@section('title', 'Kontrol Sesi & Bank Soal - ' . $webinar->title)
@section('header_title', 'Panel Kontrol Penyelenggara Webinar')

@section('content')
<div class="space-y-8" x-data="{
    activeTab: 'questions_quiz',
    createModalOpen: false,
    importModalOpen: false,
    importType: 'quiz',
    createQuestionType: 'quiz',
    createQuestionInputType: 'options',
    editModalOpen: false,
    editWebinarModalOpen: false,
    deleteWebinarModalOpen: false,
    shareModalOpen: false,
    shareCopied: false,
    editQuestion: {
        id: null,
        type: 'quiz',
        input_type: 'options',
        question_text: '',
        options: { A: '', B: '', C: '', D: '' },
        questionnaire_options: '',
        correct_answer: 'A',
        order_number: 1
    },
    copyShareLink() {
        navigator.clipboard.writeText('{{ route('peserta.webinar.room', $webinar->slug) }}');
        this.shareCopied = true;
        setTimeout(() => this.shareCopied = false, 3000);
    },
    openEdit(q) {
        this.editQuestion.id = q.id;
        this.editQuestion.type = q.type;
        this.editQuestion.input_type = q.input_type || ((Array.isArray(q.options) && q.options.length > 0) ? 'options' : 'text');
        this.editQuestion.question_text = q.question_text;
        this.editQuestion.order_number = q.order_number;
        if (q.type === 'quiz') {
            this.editQuestion.options = {
                A: (q.options && q.options.A) ? q.options.A : '',
                B: (q.options && q.options.B) ? q.options.B : '',
                C: (q.options && q.options.C) ? q.options.C : '',
                D: (q.options && q.options.D) ? q.options.D : ''
            };
            this.editQuestion.correct_answer = q.correct_answer || 'A';
        } else {
            this.editQuestion.questionnaire_options = Array.isArray(q.options) ? q.options.join(', ') : '';
        }
        this.editModalOpen = true;
    }
}">
    
    <!-- Top Bar Detail Webinar & Trigger "Mulai Sesi" -->
    <div class="bg-gradient-to-r from-pasuruan-900 to-pasuruan-800 rounded-2xl p-6 sm:p-8 text-white shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="space-y-2">
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $webinar->is_live ? 'bg-rose-500 text-white animate-pulse' : 'bg-white/20 text-slate-200' }}">
                    {{ $webinar->is_live ? '● STATUS LIVE: AKTIF' : 'STATUS: OFFLINE' }}
                </span>
                <span class="text-xs text-sky-200">Bobot: {{ $webinar->jp }} JP ({{ $webinar->jp * 45 }} Menit)</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight">{{ $webinar->title }}</h2>
            <p class="text-xs text-slate-300">
                Penyelenggara: <strong>{{ $webinar->organizer->name }}</strong> ({{ $webinar->organizer->profile->unit_kerja ?? 'BKPSDM' }}) • 
                Passing Grade: <strong class="text-amber-300">&ge; {{ $webinar->passing_grade }}</strong> • 
                Plafon Remedial: <strong class="text-amber-300">{{ $webinar->remedial_cap_score }}</strong> • 
                No. Sertifikat: <strong class="text-emerald-300 font-mono">{{ $webinar->certificate_number ?: 'Otomatis (Unik)' }}</strong>
            </p>
        </div>

        <!-- Tombol Aksi & Pemicu Mulai Sesi -->
        <div class="shrink-0 w-full md:w-auto flex flex-wrap items-center gap-2.5">
            <button type="button" @click="shareModalOpen = true" class="px-3.5 py-3 bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs rounded-xl transition-colors flex items-center justify-center gap-1.5 shadow-sm cursor-pointer" title="Buat & Salin Tautan Sebaran Kegiatan">
                <i class="fa-solid fa-share-nodes"></i>
                <span>Bagikan Link</span>
            </button>
            <a href="{{ route('admin.webinars.export_participants', $webinar->id) }}" class="px-3.5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl transition-colors flex items-center justify-center gap-1.5 shadow-sm" title="Tarikan Data Excel Presensi & Nilai Peserta">
                <i class="fa-solid fa-file-excel text-emerald-200"></i>
                <span>Tarikan Data Excel</span>
            </a>
            <button type="button" @click="editWebinarModalOpen = true" class="px-3.5 py-3 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl transition-colors flex items-center justify-center gap-1.5 shadow-sm" title="Ubah Judul, Jadwal, dan Informasi Webinar">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Edit Info</span>
            </button>
            <a href="{{ route('peserta.webinar.room', $webinar->slug) }}" target="_blank" class="px-3.5 py-3 bg-white/10 hover:bg-white/20 text-white font-bold text-xs rounded-xl transition-colors flex items-center justify-center gap-1.5 border border-white/20">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span>Ruang Peserta</span>
            </a>
            <form action="{{ route('admin.webinars.toggle_live', $webinar->id) }}" method="POST">
                @csrf
                @if($webinar->is_live)
                    <button type="submit" class="w-full md:w-auto px-5 py-3 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-lg transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-stop text-sm"></i>
                        <span>Hentikan Sesi (Tutup Presensi)</span>
                    </button>
                @else
                    <button type="submit" class="w-full md:w-auto px-5 py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-extrabold text-xs rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 animate-pulse">
                        <i class="fa-solid fa-play text-sm"></i>
                        <span>Mulai Sesi (Buka Presensi)</span>
                    </button>
                @endif
            </form>
            <button type="button" @click="deleteWebinarModalOpen = true" class="p-3 bg-rose-950/60 hover:bg-rose-900 text-rose-300 hover:text-white font-bold text-xs rounded-xl transition-colors flex items-center justify-center border border-rose-800/60" title="Hapus Sesi Webinar Ini">
                <i class="fa-solid fa-trash text-sm"></i>
            </button>
        </div>
    </div>

    <!-- SEKSI 1: MANAJEMEN BANK SOAL EVALUASI & KUESIONER (FITUR BARU SUPER ADMIN & PENYELENGGARA) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-pasuruan-700"></i>
                    <span>Kelola Instrumen Kuis Evaluasi & Kuesioner Kepuasan</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Atur jumlah pertanyaan, opsi pilihan ganda, dan tetapkan kunci jawaban yang benar untuk penilaian otomatis peserta.
                </p>
            </div>

            <!-- Tombol Tambah Soal / Pertanyaan & Import Excel -->
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="importType = (activeTab === 'questions_questionnaire' ? 'questionnaire' : 'quiz'); importModalOpen = true;" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-file-excel text-emerald-200"></i>
                    <span>Import dari Excel</span>
                </button>
                <button type="button" @click="createQuestionType = 'quiz'; createModalOpen = true;" class="px-4 py-2 bg-pasuruan-700 hover:bg-pasuruan-800 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-plus-circle text-amber-400"></i>
                    <span>Tambah Soal Evaluasi</span>
                </button>
                <button type="button" @click="createQuestionType = 'questionnaire'; createModalOpen = true;" class="px-4 py-2 bg-purple-700 hover:bg-purple-800 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-plus-circle text-purple-200"></i>
                    <span>Tambah Pertanyaan Kuesioner</span>
                </button>
            </div>
        </div>

        <!-- Tab Navigasi -->
        <div class="flex border-b border-slate-100 bg-slate-50/50 px-6 gap-2 pt-2">
            <button type="button" @click="activeTab = 'questions_quiz'" :class="activeTab === 'questions_quiz' ? 'border-pasuruan-700 text-pasuruan-800 bg-white font-bold shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-5 py-3 text-xs border-b-2 rounded-t-xl transition-all flex items-center gap-2">
                <i class="fa-solid fa-feather-pointed text-amber-500"></i>
                <span>Soal Kuis Evaluasi (Post-Test)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                    {{ $webinar->quizQuestions->count() }} Soal
                </span>
            </button>

            <button type="button" @click="activeTab = 'questions_questionnaire'" :class="activeTab === 'questions_questionnaire' ? 'border-purple-600 text-purple-800 bg-white font-bold shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-5 py-3 text-xs border-b-2 rounded-t-xl transition-all flex items-center gap-2">
                <i class="fa-solid fa-star text-purple-500"></i>
                <span>Pertanyaan Kuesioner Kepuasan</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">
                    {{ $webinar->questionnaireQuestions->count() }} Pertanyaan
                </span>
            </button>

            <button type="button" @click="activeTab = 'questionnaire_responses'" :class="activeTab === 'questionnaire_responses' ? 'border-sky-600 text-sky-800 bg-white font-bold shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-5 py-3 text-xs border-b-2 rounded-t-xl transition-all flex items-center gap-2">
                <i class="fa-solid fa-comments text-sky-500"></i>
                <span>Respons Ulasan Peserta</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800">
                    {{ $webinar->questionnaireResponses->count() }} Respon
                </span>
            </button>
        </div>

        <!-- Isi Tab 1: Soal Kuis Evaluasi (Post-Test) -->
        <div x-show="activeTab === 'questions_quiz'" class="p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-amber-50/70 border border-amber-200 rounded-xl p-3.5 text-xs text-amber-900">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-info text-amber-600 text-base"></i>
                    <span>Soal di bawah ini akan diujikan kepada peserta pada <strong>Tahap 4 (Evaluasi Akhir)</strong>. Nilai peserta dihitung otomatis: <code>(Jawaban Benar / Total Soal) &times; 100</code> dan dibatasi plafon <code>{{ $webinar->remedial_cap_score }}</code> jika remedial.</span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="font-bold">Passing Grade: {{ $webinar->passing_grade }}</span>
                    <button type="button" @click="importType = 'quiz'; importModalOpen = true;" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-[11px] flex items-center gap-1 shadow-2xs">
                        <i class="fa-solid fa-file-excel"></i> Import Excel
                    </button>
                </div>
            </div>

            @if($webinar->quizQuestions->isEmpty())
                <div class="py-12 text-center text-slate-400 space-y-3">
                    <i class="fa-solid fa-folder-open text-4xl text-slate-300"></i>
                    <p class="text-xs">Belum ada soal kuis evaluasi yang dibuat untuk webinar ini.</p>
                    <div class="flex items-center justify-center gap-2">
                        <button type="button" @click="createQuestionType = 'quiz'; createModalOpen = true;" class="px-4 py-2 bg-pasuruan-700 text-white text-xs font-bold rounded-xl shadow-xs">
                            Buat Soal Pertama Sekarang
                        </button>
                        <button type="button" @click="importType = 'quiz'; importModalOpen = true;" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs flex items-center gap-1.5">
                            <i class="fa-solid fa-file-excel"></i> Import dari Excel
                        </button>
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($webinar->quizQuestions as $index => $q)
                        <div class="p-5 rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition-shadow space-y-3">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-900 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ $q->order_number ?? ($index + 1) }}
                                    </span>
                                    <div class="space-y-1">
                                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 leading-relaxed">{{ $q->question_text }}</h4>
                                        <div class="flex items-center gap-2 text-[11px] text-slate-400">
                                            <span>Tipe: Pilihan Ganda</span> • 
                                            <span>Kunci Jawaban: <strong class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 font-mono">Opsi {{ $q->correct_answer }}</strong></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button type="button" @click="openEdit({{ json_encode($q) }})" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-colors flex items-center gap-1">
                                        <i class="fa-solid fa-pen-to-square text-slate-500"></i>
                                        <span>Ubah</span>
                                    </button>
                                    <form action="{{ route('admin.webinars.questions.delete', [$webinar->id, $q->id]) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus soal ini?');">
                                        @csrf
                                        <button type="submit" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus Soal">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Pilihan Jawaban A, B, C, D -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                                @if(is_array($q->options))
                                    @foreach($q->options as $optKey => $optVal)
                                        <div class="p-2.5 rounded-lg text-xs flex items-center justify-between {{ $optKey === $q->correct_answer ? 'bg-emerald-50/80 border border-emerald-300 text-emerald-950 font-semibold' : 'bg-slate-50 border border-slate-200 text-slate-700' }}">
                                            <div class="flex items-center gap-2">
                                                <span class="w-5 h-5 rounded flex items-center justify-center font-mono font-bold text-[11px] {{ $optKey === $q->correct_answer ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700' }}">
                                                    {{ $optKey }}
                                                </span>
                                                <span>{{ $optVal }}</span>
                                            </div>
                                            @if($optKey === $q->correct_answer)
                                                <span class="text-[10px] uppercase font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded shrink-0">
                                                    <i class="fa-solid fa-check mr-1"></i> Kunci Benar
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Isi Tab 2: Pertanyaan Kuesioner Kepuasan -->
        <div x-show="activeTab === 'questions_questionnaire'" class="p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-purple-50/70 border border-purple-200 rounded-xl p-3.5 text-xs text-purple-900">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-info text-purple-600 text-base"></i>
                    <span>Pertanyaan di bawah ini wajib diisi oleh peserta pada <strong>Tahap 3 (Kuesioner Kepuasan)</strong> setelah melakukan presensi hadir sebelum dapat membuka kuis evaluasi.</span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="font-bold">Total: {{ $webinar->questionnaireQuestions->count() }} Pertanyaan</span>
                    <button type="button" @click="importType = 'questionnaire'; importModalOpen = true;" class="px-2.5 py-1 bg-purple-700 hover:bg-purple-800 text-white font-bold rounded-lg text-[11px] flex items-center gap-1 shadow-2xs">
                        <i class="fa-solid fa-file-excel"></i> Import Excel
                    </button>
                </div>
            </div>

            @if($webinar->questionnaireQuestions->isEmpty())
                <div class="py-12 text-center text-slate-400 space-y-3">
                    <i class="fa-solid fa-comment-dots text-4xl text-slate-300"></i>
                    <p class="text-xs">Belum ada pertanyaan kuesioner kepuasan yang dibuat untuk webinar ini.</p>
                    <div class="flex items-center justify-center gap-2">
                        <button type="button" @click="createQuestionType = 'questionnaire'; createModalOpen = true;" class="px-4 py-2 bg-purple-700 text-white text-xs font-bold rounded-xl shadow-xs">
                            Buat Pertanyaan Kuesioner Pertama
                        </button>
                        <button type="button" @click="importType = 'questionnaire'; importModalOpen = true;" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs flex items-center gap-1.5">
                            <i class="fa-solid fa-file-excel"></i> Import dari Excel
                        </button>
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($webinar->questionnaireQuestions as $index => $q)
                        <div class="p-5 rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition-shadow space-y-3">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <span class="w-7 h-7 rounded-lg bg-purple-100 text-purple-900 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ $q->order_number ?? ($index + 1) }}
                                    </span>
                                    <div class="space-y-1">
                                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 leading-relaxed">{{ $q->question_text }}</h4>
                                        <div class="flex items-center gap-2">
                                            @if($q->isTextQuestion() || $q->input_type === 'text')
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800 border border-sky-200 flex items-center gap-1">
                                                    <i class="fa-solid fa-pen-clip text-sky-600"></i>
                                                    <span>Isian Ketikan Teks Bebas / Esai</span>
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200 flex items-center gap-1">
                                                    <i class="fa-solid fa-list-check text-purple-600"></i>
                                                    <span>Pilihan Skala Respon ({{ is_array($q->options) ? count($q->options) : 0 }} Pilihan)</span>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button type="button" @click="openEdit({{ json_encode($q) }})" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-colors flex items-center gap-1">
                                        <i class="fa-solid fa-pen-to-square text-slate-500"></i>
                                        <span>Ubah</span>
                                    </button>
                                    <form action="{{ route('admin.webinars.questions.delete', [$webinar->id, $q->id]) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus pertanyaan kuesioner ini?');">
                                        @csrf
                                        <button type="submit" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus Pertanyaan">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            @if($q->isTextQuestion() || $q->input_type === 'text')
                                <div class="p-3 bg-slate-50 rounded-lg border border-dashed border-slate-300 text-slate-500 text-xs flex items-center gap-2">
                                    <i class="fa-regular fa-comment-dots text-slate-400"></i>
                                    <span>Peserta menjawab instrumen ini dalam bentuk teks ketikan / ulasan bebas (Kolom Uraian/Esai).</span>
                                </div>
                            @else
                                <!-- Pilihan Jawaban Skala -->
                                <div class="flex flex-wrap gap-2 pt-2 border-t border-slate-100">
                                    @if(is_array($q->options))
                                        @foreach($q->options as $opt)
                                            <span class="px-3 py-1 bg-purple-50 text-purple-800 rounded-lg text-xs border border-purple-200 font-medium">
                                                ● {{ $opt }}
                                            </span>
                                        @endforeach
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Isi Tab 3: Hasil Respons Ulasan Peserta -->
        <div x-show="activeTab === 'questionnaire_responses'" class="p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h4 class="text-xs font-bold text-slate-900">Ulasan & Masukan Peserta yang Telah Dikirimkan</h4>
                    <p class="text-[11px] text-slate-500">Respons real-time peserta webinar</p>
                </div>
                <span class="px-3 py-1 bg-sky-50 text-sky-800 text-xs font-bold rounded-lg">
                    {{ $webinar->questionnaireResponses->count() }} Peserta Telah Mengisi
                </span>
            </div>

            @if($webinar->questionnaireResponses->isEmpty())
                <div class="py-10 text-center text-slate-400 text-xs">
                    Belum ada peserta yang mengirimkan pengisian kuesioner kepuasan.
                </div>
            @else
                <div class="space-y-3">
                    @foreach($webinar->questionnaireResponses as $resp)
                        @php
                            $respUser = $resp->user;
                            $respProf = $respUser?->profile;
                        @endphp
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3 text-xs">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-200/80">
                                <div class="space-y-1">
                                    <div class="font-bold text-slate-900 text-sm flex flex-wrap items-center gap-2">
                                        <i class="fa-solid fa-user-circle text-purple-700 text-base"></i>
                                        <span>{{ $respProf?->formatted_full_name ?? $respUser->name }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $respUser->isAsn() ? 'bg-emerald-100 text-emerald-800' : 'bg-sky-100 text-sky-800' }}">
                                            {{ $respUser->isAsn() ? 'ASN' : 'Peserta Umum' }}
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-600">
                                        <span class="font-mono"><strong class="text-slate-500 font-sans">NIP:</strong> {{ $respUser->nip ?? '-' }}</span>
                                        <span class="font-mono"><strong class="text-slate-500 font-sans">NIK:</strong> {{ $respProf?->nik ?? '-' }}</span>
                                        <span><strong class="text-slate-500">Jabatan:</strong> {{ $respProf?->jabatan ?? '-' }}</span>
                                        <span><strong class="text-slate-500">Unit Kerja:</strong> {{ $respProf?->unit_kerja ?? 'Pemerintah Kabupaten Pasuruan' }}</span>
                                    </div>
                                </div>
                                <span class="text-slate-500 text-[10px] font-mono bg-white px-2.5 py-1 rounded-lg border border-slate-200 shrink-0 self-start sm:self-auto">
                                    <i class="fa-regular fa-clock mr-1"></i>{{ \Carbon\Carbon::parse($resp->submitted_at)->format('d M Y, H:i') }} WIB
                                </span>
                            </div>
                            <div class="space-y-2 pl-4 border-l-2 border-purple-400">
                                @if(is_array($resp->answers))
                                    @foreach($resp->answers as $qId => $ansVal)
                                        @php
                                            $questionRef = $webinar->questionnaireQuestions->firstWhere('id', $qId);
                                            $isTextQ = $questionRef && ($questionRef->isTextQuestion() || $questionRef->input_type === 'text');
                                        @endphp
                                        <div class="text-[11px] space-y-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-slate-600 font-semibold">{{ $questionRef ? $questionRef->question_text : 'Pertanyaan #' . $qId }}:</span>
                                                @if($isTextQ)
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-sky-100 text-sky-800">Uraian Esai</span>
                                                @endif
                                            </div>
                                            @if($isTextQ)
                                                <div class="p-2.5 bg-white rounded-lg border border-purple-200 text-slate-800 font-medium whitespace-pre-wrap leading-relaxed shadow-2xs">
                                                    {{ $ansVal }}
                                                </div>
                                            @else
                                                <span class="inline-block px-2.5 py-1 rounded-md bg-purple-100 text-purple-900 font-bold text-xs">
                                                    {{ $ansVal }}
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- SEKSI 2: PENGATURAN MEDIA STREAMING (YOUTUBE LIVE) & BACKGROUND BANNER ACARA -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <i class="fa-brands fa-youtube text-rose-600"></i>
                    <span>Pengaturan Media Siaran YouTube Live & Background Acara</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Konfigurasikan siaran langsung YouTube, tautan telekonferensi, dan unggah background banner resmi webinar.
                </p>
            </div>
            @if($webinar->youtube_link)
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 flex items-center gap-1.5 self-start md:self-auto">
                    <span class="w-2 h-2 rounded-full bg-rose-600 animate-pulse"></span>
                    <span>YouTube Live Terhubung</span>
                </span>
            @endif
        </div>

        <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Form Pengaturan Media & Upload Banner -->
            <div class="lg:col-span-6 space-y-5">
                <form action="{{ route('admin.webinars.update_media', $webinar->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                    @csrf

                    <!-- YouTube Live Link -->
                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <i class="fa-brands fa-youtube text-rose-600 text-sm"></i>
                            <span>Link Live Streaming YouTube</span>
                        </label>
                        <input type="url" name="youtube_link" value="{{ $webinar->youtube_link }}" placeholder="Contoh: https://www.youtube.com/watch?v=... atau https://youtu.be/..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs">
                        <p class="text-[10px] text-slate-500">Mendukung format link YouTube Watch, YouTu.be, Live Stream, dan Embed. Pemutar video akan tampil langsung di ruang peserta.</p>
                    </div>

                    <!-- Tautan Telekonferensi Zoom/Meet -->
                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <i class="fa-solid fa-video text-blue-600 text-sm"></i>
                            <span>Tautan Ruang Meeting (Zoom / Google Meet)</span>
                        </label>
                        <input type="url" name="meeting_link" value="{{ $webinar->meeting_link }}" placeholder="https://zoom.us/j/9876543210?pwd=..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs">
                        <p class="text-[10px] text-slate-500">Link ruang telekonferensi alternatif bagi peserta atau narasumber.</p>
                    </div>

                    <!-- Upload Background Banner Acara -->
                    <div class="space-y-1.5 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                        <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <i class="fa-regular fa-image text-emerald-600 text-sm"></i>
                            <span>Unggah Background Acara / Banner Webinar</span>
                        </label>
                        <input type="file" name="banner" accept="image/png,image/jpeg,image/jpg,image/webp" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                        <p class="text-[10px] text-slate-400">Format gambar: JPG, PNG, WebP (Rasio ideal 16:9 atau 21:9, Maks. 5 MB). Gambar ini akan diterapkan sebagai latar ruang webinar dan kartu katalog.</p>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-5 py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Simpan Media & Background Banner</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Preview Live Streaming & Banner Acara -->
            <div class="lg:col-span-6 space-y-4">
                <!-- Preview Banner -->
                <div class="space-y-1.5">
                    <div class="text-[11px] font-bold text-slate-700 uppercase tracking-wider flex items-center justify-between">
                        <span>Latar Background Acara Saat Ini:</span>
                        <span class="text-[10px] text-slate-400 font-mono">{{ $webinar->banner_path ?: 'Default Template' }}</span>
                    </div>
                    <div class="relative w-full h-36 rounded-xl overflow-hidden border border-slate-200 shadow-inner bg-slate-900 flex items-center justify-center">
                        @if($webinar->banner_url)
                            <img src="{{ $webinar->banner_url }}" alt="Banner {{ $webinar->title }}" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent flex items-end p-3">
                                <span class="text-white text-[11px] font-bold drop-shadow">{{ $webinar->title }}</span>
                            </div>
                        @else
                            <div class="text-slate-400 text-xs flex items-center gap-2">
                                <i class="fa-regular fa-image text-xl"></i>
                                <span>Menggunakan Background Default Pasuruan</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Preview YouTube Player -->
                @if($webinar->youtube_embed_url)
                    <div class="space-y-1.5">
                        <div class="text-[11px] font-bold text-rose-700 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-brands fa-youtube"></i>
                            <span>Pratinjau Live Streaming YouTube:</span>
                        </div>
                        <div class="relative w-full aspect-video rounded-xl overflow-hidden shadow-md bg-black border border-slate-800">
                            <iframe src="{{ $webinar->youtube_embed_url }}" 
                                    title="Preview YouTube" 
                                    class="w-full h-full absolute inset-0" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                            </iframe>
                        </div>
                    </div>
                @else
                    <div class="p-4 rounded-xl border border-dashed border-slate-300 bg-slate-50/70 text-center space-y-1 text-slate-400">
                        <i class="fa-brands fa-youtube text-2xl text-slate-300"></i>
                        <p class="text-xs">Belum ada link YouTube Live. Masukkan link di formulir sebelah kiri untuk menampilkan video streaming di sini.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- SEKSI 3: JENDELA WAKTU & MONITORING PRESENSI (EXISTING) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Form Pengaturan Jendela Waktu (Kuesioner & Evaluasi) -->
        <div class="lg:col-span-5 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="font-bold text-sm text-slate-900">Jendela Waktu & Parameter Kelulusan</h3>
                <p class="text-xs text-slate-500">Atur batas jam pengerjaan kuesioner dan kuis evaluasi</p>
            </div>

            <form action="{{ route('admin.webinars.update_schedule', $webinar->id) }}" method="POST" class="space-y-4">
                @csrf
                
                <!-- Jendela Kuesioner -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-purple-800 uppercase tracking-wider flex items-center justify-between">
                        <span><i class="fa-solid fa-clock mr-1"></i> Jendela Kuesioner (Buka - Tutup)</span>
                        <span class="text-[10px] text-purple-600 bg-purple-100 px-1.5 py-0.5 rounded font-mono">24 Jam</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="questionnaire_start_time" x-timepicker24 value="{{ substr($webinar->questionnaire_start_time, 0, 5) }}" placeholder="10:00" class="timepicker-24h px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                        <input type="text" name="questionnaire_end_time" x-timepicker24 value="{{ substr($webinar->questionnaire_end_time, 0, 5) }}" placeholder="12:30" class="timepicker-24h px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                    </div>
                </div>

                <!-- Jendela Ujian / Evaluasi -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-amber-800 uppercase tracking-wider flex items-center justify-between">
                        <span><i class="fa-solid fa-clock mr-1"></i> Jendela Evaluasi / Post-Test (Buka - Tutup)</span>
                        <span class="text-[10px] text-amber-600 bg-amber-100 px-1.5 py-0.5 rounded font-mono">24 Jam</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="exam_start_time" x-timepicker24 value="{{ substr($webinar->exam_start_time, 0, 5) }}" placeholder="10:30" class="timepicker-24h px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                        <input type="text" name="exam_end_time" x-timepicker24 value="{{ substr($webinar->exam_end_time, 0, 5) }}" placeholder="14:00" class="timepicker-24h px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                    </div>
                </div>

                <!-- Passing Grade & Remedial Plafon -->
                <div class="grid grid-cols-2 gap-3 pt-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Passing Grade</label>
                        <input type="number" name="passing_grade" value="{{ $webinar->passing_grade }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Plafon Remedial</label>
                        <input type="number" name="remedial_cap_score" value="{{ $webinar->remedial_cap_score }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-bold text-amber-700">
                    </div>
                </div>

                <div class="pt-3">
                    <button type="submit" class="w-full py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors">
                        Perbarui Jendela Waktu & Parameter
                    </button>
                </div>
            </form>
        </div>

        <!-- Monitoring Peserta Hadir & Hasil Evaluasi -->
        <div class="lg:col-span-7 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-bold text-sm text-slate-900">Monitoring Presensi & Kelulusan Peserta</h3>
                    <p class="text-xs text-slate-500">Daftar kehadiran tercatat saat sesi siaran langsung</p>
                </div>
                <span class="px-2.5 py-1 rounded bg-sky-50 text-sky-800 font-bold text-xs">
                    {{ $webinar->attendances->count() }} Peserta Hadir
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 text-slate-600 uppercase text-[10px] font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-3">Peserta & NIP</th>
                            <th class="py-3 px-3">NIK (KTP)</th>
                            <th class="py-3 px-3">Jabatan</th>
                            <th class="py-3 px-3">Unit Kerja / Instansi</th>
                            <th class="py-3 px-3">Waktu Presensi</th>
                            <th class="py-3 px-3">Status Ujian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($webinar->attendances as $att)
                            @php
                                $attUser = $att->user;
                                $attProf = $attUser?->profile;
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-900">{{ $attProf?->formatted_full_name ?? $attUser->name }}</div>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="text-[10px] text-slate-500 font-mono font-semibold">{{ $attUser->nip ? 'NIP: ' . $attUser->nip : 'Non-ASN' }}</span>
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold {{ $attUser->isAsn() ? 'bg-emerald-100 text-emerald-800' : 'bg-sky-100 text-sky-800' }}">
                                            {{ $attUser->isAsn() ? 'ASN' : 'Umum' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-3 font-mono text-slate-700 font-semibold">
                                    {{ $attProf?->nik ?? '-' }}
                                </td>
                                <td class="py-3 px-3 text-slate-700">
                                    {{ $attProf?->jabatan ?? '-' }}
                                </td>
                                <td class="py-3 px-3 text-slate-600">
                                    {{ $attProf?->unit_kerja ?? 'Pemerintah Kabupaten Pasuruan' }}
                                </td>
                                <td class="py-3 px-3 text-slate-500 font-mono">
                                    {{ \Carbon\Carbon::parse($att->attended_at)->format('H:i:s') }} WIB
                                </td>
                                <td class="py-3 px-3">
                                    @php
                                        $latestExam = $webinar->examAttempts->where('user_id', $att->user_id)->last();
                                    @endphp
                                    @if($latestExam && $latestExam->is_passed)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            LULUS ({{ $latestExam->final_score }})
                                        </span>
                                    @elseif($latestExam)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                            Remedial ({{ $latestExam->final_score }})
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-[10px]">Belum Ujian</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">
                                    Belum ada peserta yang melakukan presensi kehadiran.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODAL IMPORT SOAL & KUESIONER WEBINAR DARI EXCEL -->
    <div x-show="importModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-base shrink-0">
                            <i class="fa-solid fa-file-excel"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-slate-900">
                                <span x-text="importType === 'quiz' ? 'Import Soal Kuis Evaluasi (Post-Test)' : 'Import Pertanyaan Kuesioner Kepuasan'"></span>
                            </h3>
                            <p class="text-xs text-slate-500">Unggah file Excel (.xlsx / .csv) untuk memasukkan butir pertanyaan secara massal</p>
                        </div>
                    </div>
                    <button type="button" @click="importModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Template Download Card -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-emerald-50/80 p-3.5 rounded-xl border border-emerald-200 text-xs">
                    <div class="space-y-0.5">
                        <p class="font-bold text-emerald-950">Unduh Format / Template Standar Excel</p>
                        <p class="text-[11px] text-emerald-800" x-text="importType === 'quiz' ? 'Format berisi kolom: No, Pertanyaan, Pilihan A-E, dan Kunci Jawaban.' : 'Format berisi kolom: No, Pertanyaan, Tipe Input, Pilihan A-D.'"></p>
                    </div>
                    <a :href="'{{ url('admin/webinars/questions/template') }}?type=' + importType" class="px-3.5 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-lg text-xs flex items-center gap-1.5 transition-colors shrink-0 self-start sm:self-auto shadow-xs">
                        <i class="fa-solid fa-download"></i> Unduh Template
                    </a>
                </div>

                <form action="{{ route('admin.webinars.questions.import', $webinar->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                    @csrf
                    <input type="hidden" name="type" :value="importType">

                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-800 block">Pilih File Excel / CSV <span class="text-rose-500">*</span></label>
                        <input type="file" name="excel_file" accept=".xlsx,.xls,.csv,.txt" required class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-700 file:text-white hover:file:bg-emerald-800 cursor-pointer bg-slate-50 p-2 rounded-xl border border-slate-300">
                        <p class="text-[10px] text-slate-500">Mendukung berkas Microsoft Excel (.xlsx, .xls) dan CSV (.csv).</p>
                    </div>

                    <div class="space-y-1.5 p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <label class="font-bold text-slate-700 block text-[11px] uppercase tracking-wider">Opsi Import:</label>
                        <div class="flex flex-col sm:flex-row gap-3 pt-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="mode" value="append" checked class="text-emerald-600 focus:ring-emerald-500">
                                <span class="text-slate-700 font-medium">Tambahkan ke butir yang sudah ada</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="mode" value="replace" class="text-rose-600 focus:ring-rose-500">
                                <span class="text-rose-700 font-medium">Ganti / timpa semua butir lama</span>
                            </label>
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                        <button type="button" @click="importModalOpen = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 font-bold rounded-xl transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>Mulai Import Data</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 1: TAMBAH SOAL / PERTANYAAN BARU -->
    <div x-show="createModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-plus-circle text-pasuruan-700"></i>
                    <span x-text="createQuestionType === 'quiz' ? 'Tambah Soal Kuis Evaluasi (Post-Test)' : 'Tambah Pertanyaan Kuesioner Kepuasan'"></span>
                </h3>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('admin.webinars.questions.store', $webinar->id) }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="type" :value="createQuestionType">

                <!-- Teks Pertanyaan -->
                <div class="space-y-1">
                    <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px]">
                        Isi Pertanyaan / Soal <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="question_text" rows="3" required placeholder="Tuliskan pertanyaan secara jelas..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs leading-relaxed"></textarea>
                </div>

                <!-- Bagian Khusus Kuis Evaluasi: Opsi A, B, C, D & Kunci Jawaban -->
                <template x-if="createQuestionType === 'quiz'">
                    <div class="space-y-3 p-4 bg-amber-50/50 rounded-xl border border-amber-200/80">
                        <div class="font-bold text-amber-900 text-xs flex items-center justify-between">
                            <span>Pilihan Jawaban & Kunci Jawaban Benar</span>
                            <span class="text-[10px] text-amber-700 font-normal">Pilih bulatan opsi yang merupakan jawaban benar</span>
                        </div>

                        <!-- Opsi A -->
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-1.5 shrink-0 font-bold text-slate-700 cursor-pointer" title="Jadikan Kunci Jawaban">
                                <input type="radio" name="correct_answer" value="A" checked class="text-emerald-600 focus:ring-emerald-500">
                                <span class="w-6 h-6 rounded bg-slate-200 text-slate-800 flex items-center justify-center font-mono font-bold text-xs">A</span>
                            </label>
                            <input type="text" name="options[A]" required placeholder="Jawaban opsi A..." class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                        </div>

                        <!-- Opsi B -->
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-1.5 shrink-0 font-bold text-slate-700 cursor-pointer" title="Jadikan Kunci Jawaban">
                                <input type="radio" name="correct_answer" value="B" class="text-emerald-600 focus:ring-emerald-500">
                                <span class="w-6 h-6 rounded bg-slate-200 text-slate-800 flex items-center justify-center font-mono font-bold text-xs">B</span>
                            </label>
                            <input type="text" name="options[B]" required placeholder="Jawaban opsi B..." class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                        </div>

                        <!-- Opsi C -->
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-1.5 shrink-0 font-bold text-slate-700 cursor-pointer" title="Jadikan Kunci Jawaban">
                                <input type="radio" name="correct_answer" value="C" class="text-emerald-600 focus:ring-emerald-500">
                                <span class="w-6 h-6 rounded bg-slate-200 text-slate-800 flex items-center justify-center font-mono font-bold text-xs">C</span>
                            </label>
                            <input type="text" name="options[C]" placeholder="Jawaban opsi C (opsional)..." class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                        </div>

                        <!-- Opsi D -->
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-1.5 shrink-0 font-bold text-slate-700 cursor-pointer" title="Jadikan Kunci Jawaban">
                                <input type="radio" name="correct_answer" value="D" class="text-emerald-600 focus:ring-emerald-500">
                                <span class="w-6 h-6 rounded bg-slate-200 text-slate-800 flex items-center justify-center font-mono font-bold text-xs">D</span>
                            </label>
                            <input type="text" name="options[D]" placeholder="Jawaban opsi D (opsional)..." class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                        </div>
                    </div>
                </template>

                <!-- Bagian Khusus Kuesioner: Pilihan Skala vs Isian Ketikan Teks -->
                <template x-if="createQuestionType === 'questionnaire'">
                    <div class="space-y-3 p-4 bg-purple-50/50 rounded-xl border border-purple-200/80">
                        <div class="space-y-1.5">
                            <label class="block font-bold text-purple-900 text-xs">
                                Format / Tipe Pertanyaan Kuesioner <span class="text-rose-500">*</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition-colors" :class="createQuestionInputType === 'options' ? 'bg-purple-100/70 border-purple-400 font-bold text-purple-950 shadow-2xs' : 'bg-white border-slate-200 text-slate-700'">
                                    <input type="radio" name="input_type" value="options" x-model="createQuestionInputType" class="text-purple-700 focus:ring-purple-600">
                                    <div class="text-[11px] leading-tight">
                                        <div>Pilihan Ganda / Skala</div>
                                        <span class="text-[10px] text-slate-500 font-normal">Radio button opsi respon</span>
                                    </div>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition-colors" :class="createQuestionInputType === 'text' ? 'bg-sky-100/70 border-sky-400 font-bold text-sky-950 shadow-2xs' : 'bg-white border-slate-200 text-slate-700'">
                                    <input type="radio" name="input_type" value="text" x-model="createQuestionInputType" class="text-sky-700 focus:ring-sky-600">
                                    <div class="text-[11px] leading-tight">
                                        <div>Isian Ketikan Teks / Esai</div>
                                        <span class="text-[10px] text-slate-500 font-normal">Kolom ketikan bebas peserta</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Jika Opsi Pilihan Skala -->
                        <div x-show="createQuestionInputType === 'options'" class="space-y-1 pt-1">
                            <label class="block font-bold text-purple-900 text-[11px]">
                                Daftar Pilihan Respon / Skala Penilaian
                            </label>
                            <p class="text-[10px] text-purple-700 mb-1">Tuliskan opsi jawaban dipisahkan dengan koma atau gunakan opsi standar:</p>
                            <input type="text" name="questionnaire_options" value="Sangat Baik, Baik, Cukup, Kurang" placeholder="Contoh: Sangat Puas, Puas, Cukup, Kurang" class="w-full px-3 py-2 bg-white border border-purple-300 rounded-lg text-xs">
                        </div>

                        <!-- Jika Isian Teks Bebas -->
                        <div x-show="createQuestionInputType === 'text'" class="p-3 bg-sky-50 rounded-lg border border-sky-200 text-[11px] text-sky-900 flex items-start gap-2">
                            <i class="fa-solid fa-circle-info text-sky-600 mt-0.5"></i>
                            <div>
                                <strong>Mode Isian Teks Bebas:</strong> Peserta akan diberikan kolom teks (textarea) untuk mengetikkan ulasan, masukan, kritik, atau saran secara bebas.
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Nomor Urut -->
                <div class="grid grid-cols-2 gap-3 pt-2">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider text-[11px] mb-1">Nomor Urut Soal</label>
                        <input type="number" name="order_number" min="1" value="{{ $webinar->quizQuestions->count() + 1 }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold rounded-xl shadow-xs transition-colors">
                        Simpan Pertanyaan Baru
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: UBAH SOAL / PERTANYAAN (EDIT MODAL) -->
    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-pasuruan-700"></i>
                    <span>Ubah Pertanyaan Bank Soal</span>
                </h3>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form :action="`{{ url('admin/webinars/' . $webinar->id . '/questions') }}/${editQuestion.id}`" method="POST" class="space-y-4 text-xs">
                @csrf

                <!-- Teks Pertanyaan -->
                <div class="space-y-1">
                    <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px]">
                        Isi Pertanyaan / Soal <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="question_text" x-model="editQuestion.question_text" rows="3" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs leading-relaxed"></textarea>
                </div>

                <!-- Bagian Khusus Kuis Evaluasi -->
                <template x-if="editQuestion.type === 'quiz'">
                    <div class="space-y-3 p-4 bg-amber-50/50 rounded-xl border border-amber-200/80">
                        <div class="font-bold text-amber-900 text-xs flex items-center justify-between">
                            <span>Pilihan Jawaban & Kunci Jawaban Benar</span>
                            <span class="text-[10px] text-amber-700 font-normal">Pilih radio untuk menetapkan kunci jawaban benar</span>
                        </div>

                        <!-- Opsi A -->
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-1.5 shrink-0 font-bold text-slate-700 cursor-pointer">
                                <input type="radio" name="correct_answer" value="A" :checked="editQuestion.correct_answer === 'A'" class="text-emerald-600 focus:ring-emerald-500">
                                <span class="w-6 h-6 rounded bg-slate-200 text-slate-800 flex items-center justify-center font-mono font-bold text-xs">A</span>
                            </label>
                            <input type="text" name="options[A]" x-model="editQuestion.options.A" required placeholder="Jawaban opsi A..." class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                        </div>

                        <!-- Opsi B -->
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-1.5 shrink-0 font-bold text-slate-700 cursor-pointer">
                                <input type="radio" name="correct_answer" value="B" :checked="editQuestion.correct_answer === 'B'" class="text-emerald-600 focus:ring-emerald-500">
                                <span class="w-6 h-6 rounded bg-slate-200 text-slate-800 flex items-center justify-center font-mono font-bold text-xs">B</span>
                            </label>
                            <input type="text" name="options[B]" x-model="editQuestion.options.B" required placeholder="Jawaban opsi B..." class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                        </div>

                        <!-- Opsi C -->
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-1.5 shrink-0 font-bold text-slate-700 cursor-pointer">
                                <input type="radio" name="correct_answer" value="C" :checked="editQuestion.correct_answer === 'C'" class="text-emerald-600 focus:ring-emerald-500">
                                <span class="w-6 h-6 rounded bg-slate-200 text-slate-800 flex items-center justify-center font-mono font-bold text-xs">C</span>
                            </label>
                            <input type="text" name="options[C]" x-model="editQuestion.options.C" placeholder="Jawaban opsi C..." class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                        </div>

                        <!-- Opsi D -->
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-1.5 shrink-0 font-bold text-slate-700 cursor-pointer">
                                <input type="radio" name="correct_answer" value="D" :checked="editQuestion.correct_answer === 'D'" class="text-emerald-600 focus:ring-emerald-500">
                                <span class="w-6 h-6 rounded bg-slate-200 text-slate-800 flex items-center justify-center font-mono font-bold text-xs">D</span>
                            </label>
                            <input type="text" name="options[D]" x-model="editQuestion.options.D" placeholder="Jawaban opsi D..." class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                        </div>
                    </div>
                </template>

                <!-- Bagian Khusus Kuesioner: Pilihan Skala vs Isian Ketikan Teks -->
                <template x-if="editQuestion.type === 'questionnaire'">
                    <div class="space-y-3 p-4 bg-purple-50/50 rounded-xl border border-purple-200/80">
                        <div class="space-y-1.5">
                            <label class="block font-bold text-purple-900 text-xs">
                                Format / Tipe Pertanyaan Kuesioner <span class="text-rose-500">*</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition-colors" :class="editQuestion.input_type === 'options' ? 'bg-purple-100/70 border-purple-400 font-bold text-purple-950 shadow-2xs' : 'bg-white border-slate-200 text-slate-700'">
                                    <input type="radio" name="input_type" value="options" x-model="editQuestion.input_type" class="text-purple-700 focus:ring-purple-600">
                                    <div class="text-[11px] leading-tight">
                                        <div>Pilihan Ganda / Skala</div>
                                        <span class="text-[10px] text-slate-500 font-normal">Radio button opsi respon</span>
                                    </div>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition-colors" :class="editQuestion.input_type === 'text' ? 'bg-sky-100/70 border-sky-400 font-bold text-sky-950 shadow-2xs' : 'bg-white border-slate-200 text-slate-700'">
                                    <input type="radio" name="input_type" value="text" x-model="editQuestion.input_type" class="text-sky-700 focus:ring-sky-600">
                                    <div class="text-[11px] leading-tight">
                                        <div>Isian Ketikan Teks / Esai</div>
                                        <span class="text-[10px] text-slate-500 font-normal">Kolom ketikan bebas peserta</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Jika Opsi Pilihan Skala -->
                        <div x-show="editQuestion.input_type === 'options'" class="space-y-1 pt-1">
                            <label class="block font-bold text-purple-900 text-[11px]">
                                Daftar Pilihan Respon / Skala Penilaian
                            </label>
                            <p class="text-[10px] text-purple-700 mb-1">Pisahkan antar opsi dengan tanda koma:</p>
                            <input type="text" name="questionnaire_options" x-model="editQuestion.questionnaire_options" placeholder="Contoh: Sangat Puas, Puas, Cukup, Kurang" class="w-full px-3 py-2 bg-white border border-purple-300 rounded-lg text-xs">
                        </div>

                        <!-- Jika Isian Teks Bebas -->
                        <div x-show="editQuestion.input_type === 'text'" class="p-3 bg-sky-50 rounded-lg border border-sky-200 text-[11px] text-sky-900 flex items-start gap-2">
                            <i class="fa-solid fa-circle-info text-sky-600 mt-0.5"></i>
                            <div>
                                <strong>Mode Isian Teks Bebas:</strong> Peserta akan diberikan kolom teks (textarea) untuk mengetikkan ulasan, masukan, kritik, atau saran secara bebas.
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Nomor Urut -->
                <div class="grid grid-cols-2 gap-3 pt-2">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider text-[11px] mb-1">Nomor Urut Soal</label>
                        <input type="number" name="order_number" x-model="editQuestion.order_number" min="1" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold rounded-xl shadow-xs transition-colors">
                        Perbarui Pertanyaan
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- SEKSI 4: ZONA BAHAYA / HAPUS SESI WEBINAR -->
    <div class="p-6 rounded-2xl bg-rose-50 border border-rose-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <h4 class="font-extrabold text-sm text-rose-900 flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                <span>Zona Bahaya: Hapus Sesi Webinar Ini</span>
            </h4>
            <p class="text-xs text-rose-700">
                Jika sesi webinar ini dibatalkan atau salah dibuat, Anda dapat menghapusnya. Semua data presensi, riwayat kuis, dan respon kuesioner terkait akan dihapus.
            </p>
        </div>
        <button type="button" @click="deleteWebinarModalOpen = true" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-2 shrink-0">
            <i class="fa-solid fa-trash"></i>
            <span>Hapus Sesi Webinar Ini</span>
        </button>
    </div>

    <!-- MODAL EDIT INFORMASI WEBINAR -->
    <div x-show="editWebinarModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-2xl w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2 text-amber-700 font-bold text-sm">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <span>Ubah / Perbaiki Informasi Sesi Webinar</span>
                </div>
                <button type="button" @click="editWebinarModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('admin.webinars.update', $webinar->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf

                <!-- Penyelenggara Kegiatan & Bidang Pelaksana -->
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <i class="fa-solid fa-building-columns text-pasuruan-700"></i>
                            <span>Penyelenggara Kegiatan & Bidang Pelaksana</span>
                        </label>
                        <a href="{{ route('admin.users.index') }}" target="_blank" class="text-[10px] text-pasuruan-700 hover:underline font-semibold flex items-center gap-1">
                            <i class="fa-solid fa-users-gear"></i> Kelola Bidang di Menu Users &rarr;
                        </a>
                    </div>
                    <select name="organizer_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs font-medium">
                        @foreach($organizers as $org)
                            <option value="{{ $org->id }}" {{ $webinar->organizer_id == $org->id ? 'selected' : '' }}>
                                {{ $org->name }} — {{ $org->profile->unit_kerja ?? 'BKPSDM Kabupaten Pasuruan' }} ({{ $org->role === 'super_admin' ? 'Super Admin' : 'Penyelenggara Diklat' }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400">Pilih PIC / Penyelenggara kegiatan ini. Nama Penyelenggara dan Unit Kerja/Bidang ini bersumber dari Profil Pengguna.</p>
                </div>

                <!-- Judul Event -->
                <div class="space-y-1">
                    <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px]">
                        Judul Webinar / Bimbingan Teknis <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" value="{{ $webinar->title }}" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs">
                </div>

                <!-- Deskripsi -->
                <div class="space-y-1">
                    <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px]">
                        Deskripsi & Ringkasan Materi <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="description" rows="3" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs leading-relaxed">{{ $webinar->description }}</textarea>
                </div>

                <!-- Baris Jadwal: Tanggal, JP, Jam Mulai & Jam Selesai -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1">Tanggal Event <span class="text-rose-500">*</span></label>
                        <input type="date" name="event_date" value="{{ \Carbon\Carbon::parse($webinar->event_date)->format('Y-m-d') }}" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1">Bobot JP <span class="text-rose-500">*</span></label>
                        <input type="number" name="jp" value="{{ $webinar->jp }}" required min="1" max="50" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1 flex items-center justify-between">
                            <span>Jam Mulai (24 Jam) <span class="text-rose-500">*</span></span>
                            <i class="fa-regular fa-clock text-pasuruan-700 text-[10px]"></i>
                        </label>
                        <input type="text" name="start_time" x-timepicker24 value="{{ substr($webinar->start_time, 0, 5) }}" required placeholder="08:30" class="timepicker-24h w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1 flex items-center justify-between">
                            <span>Jam Selesai (24 Jam) <span class="text-rose-500">*</span></span>
                            <i class="fa-regular fa-clock text-pasuruan-700 text-[10px]"></i>
                        </label>
                        <input type="text" name="end_time" x-timepicker24 value="{{ substr($webinar->end_time, 0, 5) }}" required placeholder="12:00" class="timepicker-24h w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                    </div>
                </div>

                <!-- Media: YouTube & Zoom -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <i class="fa-brands fa-youtube text-rose-600"></i>
                            <span>Link Live YouTube</span>
                        </label>
                        <input type="url" name="youtube_link" value="{{ $webinar->youtube_link }}" placeholder="https://www.youtube.com/watch?v=..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs">
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <i class="fa-solid fa-video text-blue-600"></i>
                            <span>Tautan Zoom / Meet</span>
                        </label>
                        <input type="url" name="meeting_link" value="{{ $webinar->meeting_link }}" placeholder="https://zoom.us/j/..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs">
                    </div>
                </div>

                <!-- Background Acara / Banner Webinar -->
                <div class="space-y-1 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                        <i class="fa-regular fa-image text-emerald-600"></i>
                        <span>Unggah Background Acara Baru (Opsional)</span>
                    </label>
                    <input type="file" name="banner" accept="image/png,image/jpeg,image/jpg,image/webp" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                    <p class="text-[10px] text-slate-400">Biarkan kosong jika tidak ingin mengubah gambar background saat ini.</p>
                </div>

                <!-- Pengaturan Jendela Waktu Sequential Gating -->
                <div class="p-3.5 bg-purple-50/50 rounded-xl border border-purple-200/80 space-y-3">
                    <div class="font-bold text-purple-900 text-xs flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <i class="fa-solid fa-clock text-purple-700"></i>
                            <span>Jendela Waktu Sequential Gating (Otomatis 24 Jam WIB)</span>
                        </div>
                        <span class="text-[10px] text-purple-600 bg-purple-100/80 px-2 py-0.5 rounded font-mono">Format 24 Jam</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <span class="text-[11px] font-bold text-purple-800">Jendela Kuesioner (Buka - Tutup)</span>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" name="questionnaire_start_time" x-timepicker24 value="{{ substr($webinar->questionnaire_start_time, 0, 5) }}" placeholder="10:00" class="timepicker-24h px-2.5 py-1.5 bg-white border border-purple-300 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                                <input type="text" name="questionnaire_end_time" x-timepicker24 value="{{ substr($webinar->questionnaire_end_time, 0, 5) }}" placeholder="12:30" class="timepicker-24h px-2.5 py-1.5 bg-white border border-purple-300 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <span class="text-[11px] font-bold text-amber-800">Jendela Evaluasi / Post-Test (Buka - Tutup)</span>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" name="exam_start_time" x-timepicker24 value="{{ substr($webinar->exam_start_time, 0, 5) }}" placeholder="10:30" class="timepicker-24h px-2.5 py-1.5 bg-white border border-amber-300 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                                <input type="text" name="exam_end_time" x-timepicker24 value="{{ substr($webinar->exam_end_time, 0, 5) }}" placeholder="14:00" class="timepicker-24h px-2.5 py-1.5 bg-white border border-amber-300 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Parameter Evaluasi & Remedial -->
                <div class="grid grid-cols-3 gap-3 pt-1">
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1">Passing Grade</label>
                        <input type="number" name="passing_grade" value="{{ $webinar->passing_grade }}" min="10" max="100" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1">Plafon Remedial</label>
                        <input type="number" name="remedial_cap_score" value="{{ $webinar->remedial_cap_score }}" min="10" max="100" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-bold text-amber-700">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1">Maksimal Remedial</label>
                        <input type="number" name="max_remedial" value="{{ $webinar->max_remedial }}" min="1" max="10" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono">
                    </div>
                </div>

                <!-- Pengaturan Nomor Surat E-Sertifikat -->
                <div class="space-y-1.5 p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
                    <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                        <i class="fa-solid fa-stamp text-amber-600"></i>
                        <span>Nomor Surat E-Sertifikat (Opsional)</span>
                    </label>
                    <input type="text" name="certificate_number" value="{{ $webinar->certificate_number }}" placeholder="Contoh: 893.3/120/BKPSDM-PAS/2026" class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono focus:outline-none focus:border-pasuruan-500">
                    <p class="text-[10px] text-slate-500 leading-relaxed">
                        <strong class="text-slate-700">Petunjuk Penomoran:</strong> Jika diisi, seluruh peserta webinar ini akan menerima sertifikat dengan nomor surat yang sama persis. Jika <strong>dikosongkan</strong>, nomor surat otomatis dibuat berbeda dan berurutan untuk setiap peserta.
                    </p>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editWebinarModalOpen = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan Perubahan Informasi</span>
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS WEBINAR DI HALAMAN MANAGE -->
    <div x-show="deleteWebinarModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-rose-200 my-auto">
                <div class="flex items-center gap-3 text-rose-600">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm text-slate-900">Konfirmasi Hapus Sesi Webinar</h3>
                        <p class="text-xs text-rose-600 font-medium">Tindakan ini permanen dan tidak dapat dibatalkan</p>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700 space-y-1">
                    <p class="text-slate-500 text-[11px]">Anda akan menghapus sesi webinar:</p>
                    <p class="font-extrabold text-slate-900">{{ $webinar->title }}</p>
                    <p class="text-[10px] text-slate-400 pt-1">Seluruh data presensi ({{ $webinar->attendances->count() }} orang), riwayat pengerjaan kuis, dan instrumen soal pada sesi ini akan turut terhapus secara otomatis.</p>
                </div>

                <form action="{{ route('admin.webinars.destroy', $webinar->id) }}" method="POST" class="pt-2 flex items-center justify-end gap-2 text-xs">
                    @csrf
                    <button type="button" @click="deleteWebinarModalOpen = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-trash"></i>
                        <span>Ya, Hapus Sesi Ini</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL BAGIKAN TAUTAN WEBINAR -->
    <div x-show="shareModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5 text-sky-800 font-bold text-sm">
                        <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center">
                            <i class="fa-solid fa-share-nodes"></i>
                        </div>
                        <span>Bagikan Tautan Sesi Webinar</span>
                    </div>
                    <button type="button" @click="shareModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Event Quick Info -->
                <div class="p-3.5 bg-sky-50/70 border border-sky-200 rounded-xl space-y-1">
                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-sky-200 text-sky-900">{{ $webinar->jp }} JP ({{ $webinar->jp * 45 }} Menit)</span>
                    <h4 class="font-extrabold text-xs text-slate-900">{{ $webinar->title }}</h4>
                    <p class="text-[11px] text-slate-600 flex items-center gap-1.5 pt-0.5">
                        <i class="fa-regular fa-calendar text-sky-600"></i>
                        <span>{{ $webinar->event_date ? $webinar->event_date->isoFormat('D MMMM Y') : '-' }} ({{ $webinar->start_time ? substr($webinar->start_time, 0, 5) : '08:30' }} WIB)</span>
                    </p>
                </div>

                <!-- Direct URL Input with Copy Button -->
                <div class="space-y-1.5">
                    <label class="font-bold text-xs text-slate-700 block">Tautan Langsung (Direct URL Peserta):</label>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ route('peserta.webinar.room', $webinar->slug) }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono text-slate-700 focus:outline-none select-all">
                        <button type="button" @click="copyShareLink()" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5 shrink-0 cursor-pointer">
                            <i class="fa-solid" :class="shareCopied ? 'fa-check text-emerald-300' : 'fa-copy'"></i>
                            <span x-text="shareCopied ? 'Tersalin!' : 'Salin'"></span>
                        </button>
                    </div>
                </div>

                @php
                    $rawMsg = $webinar->formatted_share_message;
                    $waUrl = "https://api.whatsapp.com/send?text=" . urlencode($rawMsg);
                @endphp

                <!-- WhatsApp Message Preview -->
                <div class="space-y-1.5" x-data="{
                    copyMessage() {
                        navigator.clipboard.writeText(`{{ addslashes($rawMsg) }}`);
                        shareCopied = true;
                        setTimeout(() => shareCopied = false, 3000);
                    }
                }">
                    <div class="flex items-center justify-between">
                        <label class="font-bold text-xs text-slate-700">Format Pesan Undangan (Siap Sebar):</label>
                        <button type="button" @click="copyMessage()" class="text-[11px] font-bold text-sky-700 hover:text-sky-900 flex items-center gap-1">
                            <i class="fa-solid fa-copy"></i>
                            <span>Salin Teks Lengkap</span>
                        </button>
                    </div>
                    <textarea readonly rows="4" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-600 leading-relaxed resize-none focus:outline-none select-all">{{ $rawMsg }}</textarea>
                </div>

                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-[11px] flex items-start gap-2">
                    <i class="fa-solid fa-circle-info text-amber-600 text-xs mt-0.5"></i>
                    <span>Tautan ini aman disebarkan ke grup OPD/WhatsApp ASN. Peserta yang belum login akan otomatis diarahkan untuk masuk terlebih dahulu.</span>
                </div>

                <!-- Footer Actions -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                    <button type="button" @click="shareModalOpen = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 font-bold text-xs rounded-xl transition-colors">
                        Tutup
                    </button>
                    <a href="{{ $waUrl }}" target="_blank" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                        <i class="fa-brands fa-whatsapp text-sm"></i>
                        <span>Sebar ke WhatsApp</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
