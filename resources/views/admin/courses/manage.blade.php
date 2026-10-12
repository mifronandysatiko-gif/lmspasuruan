@extends('layouts.dashboard')

@section('title', 'Kelola Sesi & Upload Materi: ' . $course->title . ' - BKPSDM Pasuruan')
@section('header_title', 'Kelola Sesi Pembelajaran & Upload Materi')

@section('content')
<div class="space-y-6" x-data="{ 
    editModalOpen: false, 
    deleteModalOpen: false,
    quizModalOpen: false,
    importQuizModalOpen: false,
    editLessonModalOpen: false,
    deleteLessonModalOpen: false,
    activeLesson: null,
    editingQuestion: null,
    questionFormOpen: false,
    shareModal: {
        open: false,
        title: '{{ addslashes($course->title) }}',
        category: '{{ addslashes($course->category) }}',
        type: '{{ strtoupper(str_replace('_', ' ', $course->type)) }}',
        jp: {{ $course->jp }},
        passing_grade: {{ $course->passing_grade }},
        url: '{{ url('/peserta/mooc/' . $course->slug) }}',
        rawMessage: @json($course->formatted_share_message),
        whatsappUrl: 'https://api.whatsapp.com/send?text=' + encodeURIComponent(@json($course->formatted_share_message)),
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
    selectedLesson: {
        id: null,
        sequence: 1,
        title: '',
        duration_minutes: 15,
        jp: 2,
        video_url: '',
        content_summary: '',
        has_quiz: true,
        pdf_path: ''
    },
    openEditLesson(lesson) {
        this.selectedLesson = {
            id: lesson.id,
            sequence: lesson.sequence,
            title: lesson.title,
            duration_minutes: Math.round((lesson.duration_seconds || 900) / 60) || 15,
            jp: lesson.jp || (lesson.calculated_jp || Math.max(1, Math.round((lesson.duration_seconds || 900) / 2700))),
            video_url: lesson.video_url || '',
            content_summary: lesson.content_summary || '',
            has_quiz: !!lesson.has_quiz,
            pdf_path: lesson.pdf_path || ''
        };
        this.editLessonModalOpen = true;
    },
    openDeleteLesson(lesson) {
        this.selectedLesson = {
            id: lesson.id,
            sequence: lesson.sequence,
            title: lesson.title
        };
        this.deleteLessonModalOpen = true;
    },
    openQuizModal(lesson) {
        this.activeLesson = lesson;
        this.editingQuestion = null;
        this.questionFormOpen = false;
        this.quizModalOpen = true;
    },
    initNewQuestion() {
        this.editingQuestion = {
            id: null,
            question_text: '',
            options: { A: '', B: '', C: '', D: '', E: '' },
            correct_answer: 'A',
            order_number: (this.activeLesson && this.activeLesson.questions ? this.activeLesson.questions.length + 1 : 1)
        };
        this.questionFormOpen = true;
    },
    editQuestion(q) {
        let opts = q.options;
        if (Array.isArray(opts)) {
            let mapped = { A: '', B: '', C: '', D: '', E: '' };
            ['A', 'B', 'C', 'D', 'E'].forEach((k, idx) => {
                if (opts[idx] !== undefined) mapped[k] = opts[idx];
            });
            opts = mapped;
        } else if (typeof opts === 'object' && opts !== null) {
            opts = {
                A: opts.A || '',
                B: opts.B || '',
                C: opts.C || '',
                D: opts.D || '',
                E: opts.E || ''
            };
        } else {
            opts = { A: '', B: '', C: '', D: '', E: '' };
        }

        this.editingQuestion = {
            id: q.id,
            question_text: q.question_text || '',
            options: opts,
            correct_answer: q.correct_answer || 'A',
            order_number: q.order_number || 1
        };
        this.questionFormOpen = true;
    },
    cancelQuestionForm() {
        this.editingQuestion = null;
        this.questionFormOpen = false;
    },
    formActionUrl() {
        if (!this.activeLesson) return '';
        if (this.editingQuestion && this.editingQuestion.id) {
            return '{{ url('admin/courses/' . $course->id . '/lessons') }}/' + this.activeLesson.id + '/questions/' + this.editingQuestion.id;
        }
        return '{{ url('admin/courses/' . $course->id . '/lessons') }}/' + this.activeLesson.id + '/questions';
    },
    importActionUrl() {
        if (!this.activeLesson) return '';
        return '{{ url('admin/courses/' . $course->id . '/lessons') }}/' + this.activeLesson.id + '/questions/import';
    }
}">

    <!-- Top Navigation & Header Info -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.courses.index') }}" class="hover:text-pasuruan-700 font-semibold">&larr; Kembali ke Daftar Pelatihan</a>
                <span>/</span>
                <span class="text-slate-600 font-medium">{{ $course->category }}</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900">{{ $course->title }}</h2>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <button type="button" @click="openShare()" class="px-3.5 py-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5" title="Buat & Salin Link Sebaran Pelatihan">
                <i class="fa-solid fa-share-nodes"></i>
                <span>Bagikan Link</span>
            </button>
            <a href="{{ route('admin.courses.export_participants', $course->id) }}" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5" title="Unduh Rekap Nilai & Hasil Belajar Peserta ke Excel">
                <i class="fa-solid fa-file-excel text-emerald-200"></i>
                <span>Tarikan Data Excel Peserta</span>
            </a>
            <button type="button" @click="editModalOpen = true" class="px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5" title="Ubah Judul, Kategori, Passing Grade, dan Background Pelatihan">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Edit Informasi & Background</span>
            </button>
            <a href="{{ route('admin.courses.results', $course->id) }}" class="px-4 py-2 bg-pasuruan-700 hover:bg-pasuruan-800 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2">
                <i class="fa-solid fa-clipboard-user text-amber-300"></i>
                <span>Hasil Belajar & Nilai &rarr;</span>
            </a>
            <button type="button" @click="deleteModalOpen = true" class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold rounded-xl text-xs transition-colors border border-rose-200 flex items-center justify-center" title="Hapus Pelatihan Ini">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    </div>

    <!-- Course Parameter Banner -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-4 text-xs items-center">
        <div>
            <span class="text-[11px] text-slate-400 block font-medium">Tipe Program</span>
            <span class="font-extrabold text-pasuruan-700 uppercase">{{ str_replace('_', ' ', $course->type) }}</span>
        </div>
        <div>
            <span class="text-[11px] text-slate-400 block font-medium">Bobot Jam Pelajaran</span>
            <span class="font-extrabold text-slate-800">{{ $course->jp }} JP ({{ $course->jp * 45 }} Menit)</span>
        </div>
        <div>
            <span class="text-[11px] text-slate-400 block font-medium">Passing Grade Kelulusan</span>
            <span class="font-extrabold text-emerald-600">&ge; {{ $course->passing_grade }}</span>
        </div>
        <div>
            <span class="text-[11px] text-slate-400 block font-medium">Jangka Waktu Pelatihan</span>
            @if($course->hasTimeLimit())
                <span class="font-extrabold text-amber-700 block truncate" title="{{ $course->getTimeLimitDescription() }}">
                    <i class="fa-regular fa-clock text-amber-500 mr-0.5"></i> {{ $course->getTimeLimitDescription() }}
                </span>
            @else
                <span class="font-extrabold text-slate-600">Fleksibel</span>
            @endif
        </div>
        <div>
            <span class="text-[11px] text-slate-400 block font-medium">Nomor Surat Sertifikat</span>
            @if($course->certificate_number)
                <span class="font-extrabold text-emerald-700 font-mono text-[11px] block truncate" title="{{ $course->certificate_number }}">
                    <i class="fa-solid fa-stamp text-emerald-600 mr-0.5"></i> {{ $course->certificate_number }}
                </span>
            @else
                <span class="font-extrabold text-slate-500 text-[11px]">Otomatis (Unik)</span>
            @endif
        </div>
        <div>
            <span class="text-[11px] text-slate-400 block font-medium">Total Sesi Terunggah</span>
            <span class="font-extrabold text-slate-800">{{ $course->lessons->count() }} Sesi Materi</span>
        </div>
        <div>
            <span class="text-[11px] text-slate-400 block font-medium">Background / Banner</span>
            @if($course->banner_url)
                <div class="flex items-center gap-2 mt-1">
                    <img src="{{ $course->banner_url }}" alt="{{ $course->title }}" class="w-12 h-7 object-cover rounded border border-slate-200 shadow-2xs">
                    <span class="text-[11px] text-emerald-700 font-bold">Terpasang</span>
                </div>
            @else
                <span class="text-slate-400 text-xs italic">Default Pola</span>
            @endif
        </div>
    </div>

    <!-- Main 2-Column Layout: Form Upload Materi Sesi (Kiri) & Daftar Sesi Terunggah (Kanan) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Kolom Kiri: Form Upload Materi Sesi -->
        <div class="lg:col-span-6 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2 text-pasuruan-800 font-bold text-sm">
                        <i class="fa-solid fa-cloud-arrow-up text-amber-500"></i>
                        <span>Unggah Materi Sesi Pembelajaran Baru</span>
                    </div>
                    <span class="px-2.5 py-1 bg-pasuruan-50 text-pasuruan-700 font-bold text-[10px] rounded-lg">
                        Sesi ke-{{ $course->lessons->count() + 1 }}
                    </span>
                </div>

                <form action="{{ route('admin.courses.lessons.store', $course->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="sm:col-span-1 space-y-1.5">
                            <label class="font-bold text-slate-700">Urutan Sesi <span class="text-rose-500">*</span></label>
                            <input type="number" name="sequence" value="{{ $course->lessons->count() + 1 }}" min="1" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 font-bold text-center">
                        </div>
                        <div class="sm:col-span-3 space-y-1.5">
                            <label class="font-bold text-slate-700">Judul Sesi / Modul Pembelajaran <span class="text-rose-500">*</span></label>
                            <input type="text" name="title" required placeholder="Contoh: Tata Kelola Keamanan Informasi & SPBE Pasuruan" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="space-y-1.5">
                            <label class="font-bold text-slate-700 text-xs">Durasi Belajar <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="number" name="duration_minutes" value="45" min="5" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 text-xs">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">Mnt</span>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="font-bold text-slate-700 text-xs flex items-center justify-between">
                                <span>Alokasi JP</span>
                                <span class="text-[9px] text-indigo-600 font-bold">Lampiran</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="jp" value="2" min="1" placeholder="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 text-xs font-bold text-indigo-700">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">JP</span>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="font-bold text-slate-700 text-xs">Opsi Kuis</label>
                            <div class="pt-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="has_quiz" value="1" checked class="w-4 h-4 text-pasuruan-700 rounded border-slate-300 focus:ring-pasuruan-500">
                                    <span class="text-slate-700 font-semibold text-xs">Kuis Bab</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Video Pembelajaran (Anti-Skip) -->
                    <div class="space-y-1.5 p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                        <div class="flex items-center justify-between">
                            <label class="font-bold text-slate-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-film text-rose-500"></i>
                                <span>Video Pembelajaran (URL / Embed)</span>
                            </label>
                            <span class="text-[10px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded font-bold">
                                Anti-Skip Enforced
                            </span>
                        </div>
                        <input type="url" name="video_url" placeholder="https://www.youtube.com/watch?v=... atau link cloud MP4" class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-pasuruan-500 text-xs">
                        <p class="text-[10px] text-slate-500">
                            Sistem secara otomatis mengunci fungsi lompat video (forward scrub disabled) pada portal peserta agar durasi materi dipelajari penuh.
                        </p>
                    </div>

                    <!-- Dokumen Modul / PDF -->
                    <div class="space-y-1.5 p-4 rounded-xl bg-slate-50 border border-slate-200/80" x-data="{ selectedPdfName: '' }">
                        <label class="font-bold text-slate-800 flex items-center gap-1.5">
                            <i class="fa-solid fa-file-pdf text-rose-600"></i>
                            <span>Unggah Dokumen Materi / Bahan Tayang (PDF)</span>
                        </label>
                        <div class="mt-1 flex justify-center px-6 pt-4 pb-4 border-2 border-slate-300 border-dashed rounded-xl bg-white hover:border-pasuruan-400 transition-colors cursor-pointer relative"
                             @click="$refs.newPdfFileInput.click()">
                            <div class="space-y-1 text-center">
                                <i class="fa-solid fa-cloud-arrow-up text-2xl text-slate-400"></i>
                                <div class="flex text-xs text-slate-600 justify-center">
                                    <span class="font-bold text-pasuruan-700 hover:text-pasuruan-800" x-text="selectedPdfName || 'Pilih berkas PDF materi'"></span>
                                    <input x-ref="newPdfFileInput" type="file" name="pdf_file" accept=".pdf" class="hidden" @change="selectedPdfName = $event.target.files[0] ? $event.target.files[0].name : ''">
                                </div>
                                <p class="text-[10px] text-slate-400" x-show="!selectedPdfName">Format PDF hingga 50MB • Berkas regulasi, modul, atau slide paparan</p>
                                <p class="text-[10px] text-emerald-600 font-bold" x-show="selectedPdfName" x-cloak>
                                    <i class="fa-solid fa-circle-check"></i> File siap diunggah
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Ringkasan Materi Pokok -->
                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700">Ringkasan Materi Pokok Sesi</label>
                        <textarea name="content_summary" rows="3" placeholder="Poin-poin penting yang harus dipahami dan dikuasai peserta pada sesi ini..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500"></textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-3 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center justify-center gap-2">
                            <i class="fa-solid fa-circle-check text-amber-300"></i>
                            <span>Unggah & Simpan Sesi Materi</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Kolom Kanan: Daftar Sesi Aktif -->
        <div class="lg:col-span-6 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="font-bold text-sm text-slate-900">Daftar Sesi Materi Terjadwal</h3>
                        <p class="text-[11px] text-slate-500">Urutan modul materi yang tampil pada portal peserta ASN.</p>
                    </div>
                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 font-bold text-xs rounded-xl border border-emerald-200">
                        {{ $course->lessons->count() }} Sesi Siap
                    </span>
                </div>

                <div class="space-y-3">
                    @forelse($course->lessons as $lesson)
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:border-pasuruan-400 transition-all space-y-2.5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-pasuruan-700 text-white font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ $lesson->sequence }}
                                    </span>
                                    <div>
                                        <h4 class="font-bold text-xs text-slate-900">{{ $lesson->title }}</h4>
                                        <p class="text-[11px] text-slate-500">Estimasi Durasi: {{ round($lesson->duration_seconds / 60) }} Menit &bull; <span class="text-indigo-600 font-bold">{{ $lesson->calculated_jp }} JP</span></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    @if($lesson->has_quiz)
                                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded">
                                            Ada Kuis
                                        </span>
                                    @endif
                                    <button type="button" 
                                        @click="openQuizModal({{ json_encode([
                                            'id' => $lesson->id,
                                            'sequence' => $lesson->sequence,
                                            'title' => $lesson->title,
                                            'has_quiz' => $lesson->has_quiz,
                                            'questions' => $lesson->questions
                                        ]) }})"
                                        class="px-2.5 py-1 {{ $lesson->has_quiz ? 'bg-amber-50 hover:bg-amber-100 text-amber-900 border-amber-300' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-300' }} border rounded-lg text-xs font-bold transition-all shadow-2xs flex items-center gap-1.5 cursor-pointer"
                                        title="Kelola Butir Soal Kuis">
                                        <i class="fa-solid fa-list-check {{ $lesson->has_quiz ? 'text-amber-600' : 'text-slate-500' }}"></i>
                                        <span>Soal ({{ $lesson->questions->count() }})</span>
                                    </button>

                                    <button type="button" 
                                        @click="openEditLesson({{ json_encode($lesson) }})"
                                        class="p-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold transition-all shadow-2xs cursor-pointer"
                                        title="Ubah / Edit Materi Sesi Ini">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <button type="button" 
                                        @click="openDeleteLesson({{ json_encode($lesson) }})"
                                        class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 rounded-lg text-xs font-bold transition-all shadow-2xs cursor-pointer"
                                        title="Hapus Sesi Pembelajaran">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>

                            @if($lesson->content_summary)
                                <p class="text-[11px] text-slate-600 bg-white p-2.5 rounded-lg border border-slate-200/60 leading-relaxed">
                                    {{ $lesson->content_summary }}
                                </p>
                            @endif

                            <div class="pt-2 border-t border-slate-200/60 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500">
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center gap-1 text-sky-700 font-medium">
                                        <i class="fa-solid fa-video text-xs"></i> Video Tersemat
                                    </span>
                                    @if($lesson->pdf_url)
                                        <a href="{{ $lesson->pdf_url }}" target="_blank" class="flex items-center gap-1 text-rose-700 hover:text-rose-900 font-bold truncate max-w-[200px]" title="Buka / Unduh Berkas PDF">
                                            <i class="fa-solid fa-file-pdf text-xs"></i> {{ $lesson->pdf_name }}
                                        </a>
                                    @else
                                        <span class="flex items-center gap-1 text-rose-700 font-medium truncate max-w-[180px]">
                                            <i class="fa-solid fa-file-pdf text-xs"></i> {{ $lesson->pdf_name }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-emerald-600 font-bold flex items-center gap-1 text-[10px]">
                                    <i class="fa-solid fa-check-circle"></i> Sesi Aktif
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-300 text-slate-500">
                            <i class="fa-solid fa-folder-open text-3xl text-slate-300 mb-2"></i>
                            <h5 class="font-bold text-xs text-slate-700">Belum ada sesi materi yang diunggah</h5>
                            <p class="text-[11px] text-slate-400 mt-1">Gunakan formulir di sebelah kiri untuk mengunggah materi sesi/bab pertama.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Info Help Box -->
                <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs space-y-1">
                    <div class="flex items-center gap-1.5 font-bold">
                        <i class="fa-solid fa-lightbulb text-amber-600"></i>
                        <span>Ketentuan Penyelenggaraan Pembelajaran</span>
                    </div>
                    <p class="text-[11px] text-amber-800 leading-relaxed">
                        Materi yang diunggah akan otomatis diakses berurutan (sequential gating) oleh peserta ASN. Peserta dapat mengunggah laporan tugas portofolio / hasil belajar di akhir sesi untuk dinilai oleh Widyaiswara pengampu.
                    </p>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Edit Pelatihan & Ganti Background -->
    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2 text-amber-700 font-bold text-sm">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <span>Ubah / Edit Informasi & Background Pelatihan</span>
                </div>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('admin.courses.update', $course->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <div class="space-y-1.5">
                    <label class="font-bold text-slate-700">Nama / Judul Program Pelatihan <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" value="{{ $course->title }}" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <i class="fa-solid fa-building-columns text-pasuruan-700"></i>
                            <span>Penyelenggara Kegiatan & Bidang Pelaksana</span>
                        </label>
                        <a href="{{ route('admin.users.index') }}" target="_blank" class="text-[10px] text-pasuruan-700 hover:underline font-semibold flex items-center gap-1">
                            <i class="fa-solid fa-users-gear"></i> Kelola Bidang di Menu Users &rarr;
                        </a>
                    </div>
                    <select name="organizer_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 text-xs font-medium">
                        @foreach($organizers as $org)
                            <option value="{{ $org->id }}" {{ ($course->organizer_id ?? auth()->id()) == $org->id ? 'selected' : '' }}>
                                {{ $org->name }} — {{ $org->profile->unit_kerja ?? 'BKPSDM Kabupaten Pasuruan' }} ({{ $org->role === 'super_admin' ? 'Super Admin' : 'Penyelenggara Diklat' }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400">Pilih akun Penyelenggara / PIC kegiatan ini. Data unit kerja penyelenggara ini akan dicetak pada e-sertifikat kelulusan ("Diselenggarakan oleh ...").</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700">Tipe Pelatihan <span class="text-rose-500">*</span></label>
                        <select name="type" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                            <option value="mooc" {{ $course->type === 'mooc' ? 'selected' : '' }}>MOOC Mandiri (Asynchronous)</option>
                            <option value="teknis" {{ $course->type === 'teknis' ? 'selected' : '' }}>Bimtek & Diklat Teknis</option>
                            <option value="teknis_fungsional" {{ $course->type === 'teknis_fungsional' ? 'selected' : '' }}>Teknis Fungsional</option>
                            <option value="kepemimpinan" {{ $course->type === 'kepemimpinan' ? 'selected' : '' }}>Pelatihan Kepemimpinan (PKP/PKA)</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700">Kategori / Rumpun Diklat <span class="text-rose-500">*</span></label>
                        <input type="text" name="category" value="{{ $course->category }}" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700">Bobot Jam Pelajaran (JP) <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="number" name="jp" value="{{ $course->jp }}" min="1" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">JP (x45 mnt)</span>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700">Passing Grade Kelulusan (&ge;) <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="number" name="passing_grade" value="{{ $course->passing_grade }}" min="50" max="100" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">Skor Minimal</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="font-bold text-slate-700">Deskripsi & Capaian Pembelajaran <span class="text-rose-500">*</span></label>
                    <textarea name="description" rows="3" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">{{ $course->description }}</textarea>
                </div>

                <!-- Pengaturan Batas Waktu & Jangka Waktu Pelatihan -->
                <div class="space-y-3 p-4 bg-amber-50/60 rounded-xl border border-amber-200" x-data="{ timeLimitType: '{{ $course->time_limit_type ?: 'none' }}' }">
                    <div class="flex items-center justify-between">
                        <label class="block font-bold text-amber-950 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <i class="fa-regular fa-clock text-amber-600"></i>
                            <span>Batas Waktu Pelaksanaan & Masa Pengerjaan</span>
                        </label>
                        <span class="text-[10px] text-amber-800 bg-amber-100 px-2 py-0.5 rounded font-bold">Auto-Fail & Lock</span>
                    </div>

                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700 text-xs">Pilih Skema Jangka Waktu</label>
                        <select name="time_limit_type" x-model="timeLimitType" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-semibold focus:outline-none focus:border-pasuruan-500">
                            <option value="none">Tanpa Batas Waktu (Fleksibel)</option>
                            <option value="relative_days">Durasi Hari sejak Peserta Mulai (e.g. 14 hari pengerjaan)</option>
                            <option value="fixed_date">Rentang Tanggal Tertentu (Tanggal Mulai & Batas Akhir)</option>
                            <option value="both">Kombinasi (Rentang Tanggal + Maksimal Hari sejak Mulai)</option>
                        </select>
                    </div>

                    <!-- Input Durasi Hari -->
                    <div x-show="timeLimitType === 'relative_days' || timeLimitType === 'both'" class="space-y-1.5 pt-1">
                        <label class="font-bold text-slate-700 text-xs">Batas Masa Pengerjaan (Hari) <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="number" name="duration_days" value="{{ $course->duration_days }}" min="1" placeholder="Contoh: 14 atau 30" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">Hari Kalender</span>
                        </div>
                        <p class="text-[10px] text-slate-500">Dihitung otomatis sejak peserta pertama kali menekan mulai belajar. Jika hari habis dan belum selesai, status menjadi Tidak Lulus.</p>
                    </div>

                    <!-- Input Rentang Tanggal -->
                    <div x-show="timeLimitType === 'fixed_date' || timeLimitType === 'both'" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div class="space-y-1">
                            <label class="font-bold text-slate-700 text-xs">Tanggal Pembukaan (Mulai)</label>
                            <input type="date" name="start_date" value="{{ $course->start_date ? $course->start_date->format('Y-m-d') : '' }}" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                            <p class="text-[9px] text-slate-400">Sebelum tanggal ini, pendaftaran belum dibuka.</p>
                        </div>
                        <div class="space-y-1">
                            <label class="font-bold text-slate-700 text-xs">Batas Akhir Pelatihan (Selesai)</label>
                            <input type="date" name="end_date" value="{{ $course->end_date ? $course->end_date->format('Y-m-d') : '' }}" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                            <p class="text-[9px] text-slate-400">Lewat tanggal ini, pendaftaran ditutup & peserta yang belum selesai otomatis Tidak Lulus.</p>
                        </div>
                    </div>
                </div>

                <!-- Unggah Background Baru -->
                <div class="space-y-1.5 p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
                    <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                        <i class="fa-regular fa-image text-emerald-600"></i>
                        <span>Unggah Background / Banner Baru (Opsional)</span>
                    </label>
                    <input type="file" name="banner" accept="image/png,image/jpeg,image/jpg,image/webp" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                    <p class="text-[10px] text-slate-400">Biarkan kosong jika tidak ingin mengubah background saat ini.</p>
                </div>

                <!-- Pengaturan Nomor Surat & Lampiran E-Sertifikat -->
                <div class="space-y-3 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                    <div class="flex items-center justify-between">
                        <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <i class="fa-solid fa-stamp text-amber-600"></i>
                            <span>Pengaturan Nomor & Lampiran E-Sertifikat</span>
                        </label>
                        <span class="text-[10px] text-pasuruan-700 bg-pasuruan-50 border border-pasuruan-200 px-2 py-0.5 rounded font-bold">2 Halaman (Muka & Transkrip JP)</span>
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-slate-700 text-xs flex items-center justify-between">
                            <span>Pilihan Template E-Sertifikat</span>
                            <span class="text-[10px] text-slate-400 font-normal">Opsional</span>
                        </label>
                        <select name="certificate_template_id" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-semibold focus:outline-none focus:border-pasuruan-500">
                            <option value="">-- Gunakan Template Default Sistem --</option>
                            @if(isset($templates))
                                @foreach($templates as $tpl)
                                    <option value="{{ $tpl->id }}" {{ $course->certificate_template_id == $tpl->id ? 'selected' : '' }}>
                                        {{ $tpl->name }} ({{ $tpl->target_badge }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <p class="text-[9px] text-slate-400">Pilih template khusus jika pelatihan ini menggunakan blanko/desain sertifikat tersendiri.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="font-bold text-slate-700 text-xs">Nomor Urut Awal (Start Number)</label>
                            <input type="number" min="1" name="certificate_start_number" value="{{ $course->certificate_start_number }}" placeholder="Contoh: 1 atau 101" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono font-bold focus:outline-none focus:border-pasuruan-500">
                            <p class="text-[9px] text-slate-400">Nomor urut sertifikat peserta diklat ini dimulai dari angka ini.</p>
                        </div>
                        <div class="space-y-1">
                            <label class="font-bold text-slate-700 text-xs">Pola Format Nomor (Opsional)</label>
                            <input type="text" name="certificate_format" value="{{ $course->certificate_format }}" placeholder="893.3/{NOMOR}/BKPSDM-PAS/{TAHUN}" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono focus:outline-none focus:border-pasuruan-500">
                            <p class="text-[9px] text-slate-400">Kosongkan jika ingin memakai format default sistem.</p>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-slate-700 text-xs">Nomor SK / Surat Statis Seragam (Opsional)</label>
                        <input type="text" name="certificate_number" value="{{ $course->certificate_number }}" placeholder="Contoh: 893.3/045/BKPSDM-PAS/2026" class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono focus:outline-none focus:border-pasuruan-500">
                        <p class="text-[10px] text-slate-500 leading-relaxed">
                            Jika diisi, seluruh peserta yang lulus mendapat nomor surat yang sama persis. Jika dikosongkan, penomoran urut dinamis otomatis diterapkan.
                        </p>
                    </div>

                    <div class="space-y-1.5 pt-2 border-t border-slate-200">
                        <label class="font-bold text-slate-700 text-xs flex items-center justify-between">
                            <span class="flex items-center gap-1.5">
                                <i class="fa-solid fa-file-invoice text-indigo-600"></i>
                                <span>Ganti Blanko Lampiran Khusus Pelatihan (Hal. 2)</span>
                            </span>
                            @if($course->certificate_lampiran_url)
                                <a href="{{ $course->certificate_lampiran_url }}" target="_blank" class="text-[10px] text-indigo-600 hover:underline flex items-center gap-1 font-bold">
                                    <i class="fa-solid fa-eye"></i> Lihat Blanko Saat Ini
                                </a>
                            @endif
                        </label>
                        <input type="file" name="certificate_lampiran_file" accept="image/png,image/jpeg,image/jpg,image/webp" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                        <p class="text-[10px] text-slate-400 leading-relaxed">
                            Biarkan kosong jika tetap menggunakan blanko lampiran yang sudah ada atau blanko dinas default.
                        </p>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus Pelatihan -->
    <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-rose-200 my-auto">
                <div class="flex items-center gap-3 text-rose-600">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm text-slate-900">Konfirmasi Hapus Pelatihan</h3>
                        <p class="text-xs text-rose-600 font-medium">Tindakan ini permanen dan tidak dapat dibatalkan</p>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700 space-y-1">
                    <p class="text-slate-500 text-[11px]">Anda akan menghapus program pelatihan:</p>
                    <p class="font-extrabold text-slate-900">{{ $course->title }}</p>
                    <p class="text-[10px] text-slate-400 pt-1">Seluruh data materi sesi ({{ $course->lessons->count() }} materi), riwayat enrollment, progres belajar, dan nilai peserta terkait akan dihapus secara otomatis.</p>
                </div>

                <form action="{{ route('admin.courses.destroy', $course->id) }}" method="POST" class="pt-2 flex items-center justify-end gap-2 text-xs">
                    @csrf
                    <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-trash"></i>
                        <span>Ya, Hapus Pelatihan Ini</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Kelola Bank Soal Kuis Sesi Pembelajaran -->
    <div x-show="quizModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-2xl w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto">
            <!-- Header Modal -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-base shrink-0">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm text-slate-900">
                            Bank Soal Kuis Sesi <span x-text="activeLesson ? activeLesson.sequence : ''"></span>
                        </h3>
                        <p class="text-xs text-slate-500 truncate max-w-md" x-text="activeLesson ? activeLesson.title : ''"></p>
                    </div>
                </div>
                <button type="button" @click="quizModalOpen = false; cancelQuestionForm();" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Header Action: Tombol Buat Soal Baru & Import Excel -->
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="text-xs text-slate-500">
                    Total: <strong class="text-slate-800" x-text="activeLesson && activeLesson.questions ? activeLesson.questions.length : 0"></strong> Soal Kuis
                </span>
                <div class="flex items-center gap-2">
                    <button type="button" x-show="!questionFormOpen && !importQuizModalOpen" @click="importQuizModalOpen = true" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-file-excel text-emerald-200"></i>
                        <span>Import dari Excel</span>
                    </button>
                    <button type="button" x-show="!questionFormOpen && !importQuizModalOpen" @click="initNewQuestion()" class="px-3 py-1.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white rounded-xl text-xs font-bold shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-plus text-amber-300"></i>
                        <span>Tambah Butir Soal Baru</span>
                    </button>
                </div>
            </div>

            <!-- Box Form Import Soal dari File Excel / CSV -->
            <div x-show="importQuizModalOpen" x-cloak class="p-4 bg-emerald-50/80 rounded-xl border border-emerald-200 space-y-4 text-xs">
                <div class="flex items-center justify-between pb-2 border-b border-emerald-200/70">
                    <div class="flex items-center gap-2 font-bold text-emerald-900">
                        <i class="fa-solid fa-file-excel text-emerald-600 text-base"></i>
                        <span>Import Butir Soal Kuis dari File Excel (.xlsx / .csv)</span>
                    </div>
                    <button type="button" @click="importQuizModalOpen = false" class="text-emerald-700 hover:text-emerald-900 font-bold">
                        Batal
                    </button>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-lg border border-emerald-200">
                    <div class="space-y-0.5">
                        <p class="font-bold text-slate-800 text-xs">Unduh Format / Template Soal Kuis Excel</p>
                        <p class="text-[11px] text-slate-500">Gunakan template ini untuk mengisi No, Pertanyaan, Pilihan A-E, dan Kunci Jawaban.</p>
                    </div>
                    <a href="{{ route('admin.courses.questions.template') }}" class="px-3 py-1.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-bold rounded-lg text-xs flex items-center gap-1.5 transition-colors shrink-0 self-start sm:self-auto">
                        <i class="fa-solid fa-download"></i> Unduh Template Excel
                    </a>
                </div>

                <form :action="importActionUrl()" method="POST" enctype="multipart/form-data" class="space-y-3.5">
                    @csrf
                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700 block">Pilih File Excel / CSV <span class="text-rose-500">*</span></label>
                        <input type="file" name="excel_file" accept=".xlsx,.xls,.csv,.txt" required class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-700 file:text-white hover:file:bg-emerald-800 cursor-pointer bg-white p-2 rounded-xl border border-slate-300">
                        <p class="text-[10px] text-slate-500">Mendukung format file <strong>.xlsx, .xls, .csv</strong>. Sistem akan membaca kolom Pertanyaan, Pilihan A-E, serta Kunci Jawaban.</p>
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-slate-700 block">Metode Import:</label>
                        <div class="flex flex-col sm:flex-row gap-3 pt-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="mode" value="append" checked class="text-emerald-600 focus:ring-emerald-500">
                                <span class="text-slate-700 font-medium">Tambahkan ke butir soal yang sudah ada</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="mode" value="replace" class="text-rose-600 focus:ring-rose-500">
                                <span class="text-rose-700 font-medium">Ganti / timpa semua soal lama</span>
                            </label>
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2 border-t border-emerald-200/50">
                        <button type="button" @click="importQuizModalOpen = false" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 font-bold rounded-lg transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>Unggah & Impor Soal</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Form Tambah / Edit Soal Kuis -->
            <template x-if="questionFormOpen && editingQuestion">
                <div class="p-4 bg-amber-50/50 rounded-xl border border-amber-200 space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-amber-200/70">
                        <h4 class="font-bold text-xs text-amber-900 flex items-center gap-1.5">
                            <i class="fa-solid fa-pen-to-square text-amber-600"></i>
                            <span x-text="editingQuestion.id ? 'Edit Pertanyaan Kuis' : 'Buat Pertanyaan Kuis Baru'"></span>
                        </h4>
                        <button type="button" @click="cancelQuestionForm()" class="text-xs text-slate-500 hover:text-slate-700 font-medium">
                            Batal
                        </button>
                    </div>

                    <form :action="formActionUrl()" method="POST" class="space-y-3.5 text-xs">
                        @csrf
                        
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                            <div class="sm:col-span-1 space-y-1">
                                <label class="font-bold text-slate-700">No. Urut</label>
                                <input type="number" name="order_number" x-model="editingQuestion.order_number" min="1" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-center font-bold">
                            </div>
                            <div class="sm:col-span-3 space-y-1">
                                <label class="font-bold text-slate-700">Kunci Jawaban Benar <span class="text-rose-500">*</span></label>
                                <div class="flex items-center gap-3 pt-1.5">
                                    <template x-for="k in ['A', 'B', 'C', 'D', 'E']" :key="k">
                                        <label class="flex items-center gap-1 cursor-pointer">
                                            <input type="radio" name="correct_answer" :value="k" x-model="editingQuestion.correct_answer" class="text-emerald-600 focus:ring-emerald-500">
                                            <span class="font-mono font-bold text-xs" :class="editingQuestion.correct_answer === k ? 'text-emerald-700 font-extrabold' : 'text-slate-600'" x-text="k"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="font-bold text-slate-700">Pertanyaan / Soal <span class="text-rose-500">*</span></label>
                            <textarea name="question_text" x-model="editingQuestion.question_text" rows="2" required placeholder="Tuliskan butir soal evaluasi pembelajaran..." class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-pasuruan-500 text-xs"></textarea>
                        </div>

                        <!-- Pilihan Opsi Jawaban -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-700 block">Pilihan Jawaban (A-E)</label>
                                <span class="text-[10px] text-slate-400">Minimal isi pilihan A dan B</span>
                            </div>
                            
                            <div class="space-y-2">
                                <template x-for="optKey in ['A', 'B', 'C', 'D', 'E']" :key="optKey">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded flex items-center justify-center font-mono font-bold text-xs shrink-0"
                                              :class="editingQuestion.correct_answer === optKey ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-200 text-slate-700'"
                                              x-text="optKey"></span>
                                        <input type="text" 
                                               :name="'options[' + optKey + ']'" 
                                               x-model="editingQuestion.options[optKey]" 
                                               :placeholder="'Pilihan jawaban ' + optKey + (optKey === 'A' || optKey === 'B' ? ' (Wajib)' : ' (Opsional)')"
                                               :required="optKey === 'A' || optKey === 'B'"
                                               class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2">
                            <button type="button" @click="cancelQuestionForm()" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 font-bold rounded-lg transition-colors">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-xs transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span x-text="editingQuestion.id ? 'Simpan Perubahan Soal' : 'Simpan Butir Soal'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </template>

            <!-- List Soal yang Tersedia -->
            <div x-show="!questionFormOpen" class="space-y-3 max-h-96 overflow-y-auto pr-1">
                <template x-if="activeLesson && activeLesson.questions && activeLesson.questions.length > 0">
                    <div class="space-y-3">
                        <template x-for="(q, idx) in activeLesson.questions" :key="q.id">
                            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2 hover:bg-white hover:border-slate-300 transition-colors">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-2">
                                        <span class="w-5 h-5 rounded-full bg-pasuruan-100 text-pasuruan-800 font-bold text-[10px] flex items-center justify-center shrink-0 mt-0.5" x-text="q.order_number || (idx + 1)"></span>
                                        <p class="font-bold text-xs text-slate-800 leading-snug" x-text="q.question_text"></p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button type="button" @click="editQuestion(q)" class="p-1.5 text-amber-600 hover:text-amber-700 hover:bg-amber-50 rounded-lg transition-colors" title="Edit Soal">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form :action="'{{ url('admin/courses/' . $course->id . '/lessons') }}/' + activeLesson.id + '/questions/' + q.id + '/delete'" method="POST" onsubmit="return confirm('Yakin ingin menghapus butir soal kuis ini?')">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus Soal">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Opsi Jawaban Preview -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 pl-7 text-[11px]">
                                    <template x-for="(optVal, optKey) in (q.options || {})" :key="optKey">
                                        <div class="flex items-center gap-1.5" :class="q.correct_answer === optKey ? 'text-emerald-700 font-bold' : 'text-slate-600'">
                                            <span class="w-4 h-4 rounded text-[10px] flex items-center justify-center font-mono font-bold shrink-0"
                                                  :class="q.correct_answer === optKey ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700'"
                                                  x-text="optKey"></span>
                                            <span class="truncate" x-text="optVal"></span>
                                            <i x-show="q.correct_answer === optKey" class="fa-solid fa-check text-emerald-600 text-xs ml-1" title="Kunci Jawaban"></i>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!activeLesson || !activeLesson.questions || activeLesson.questions.length === 0">
                    <div class="p-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-300 text-slate-500 space-y-2">
                        <i class="fa-solid fa-clipboard-question text-3xl text-slate-300"></i>
                        <p class="font-bold text-xs text-slate-700">Belum ada butir soal kuis untuk sesi ini</p>
                        <p class="text-[11px] text-slate-400">Klik tombol "Tambah Butir Soal Baru" di atas untuk membuat pertanyaan evaluasi.</p>
                    </div>
                </template>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end">
                <button type="button" @click="quizModalOpen = false; cancelQuestionForm();" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors cursor-pointer">
                    Selesai / Tutup
                </button>
            </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Sesi Pembelajaran -->
    <div x-show="editLessonModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5 text-pasuruan-800 font-bold text-base">
                        <i class="fa-solid fa-pen-to-square text-amber-500"></i>
                        <span>Ubah / Edit Sesi Pembelajaran</span>
                    </div>
                    <button @click="editLessonModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form :action="'{{ url('admin/courses/' . $course->id . '/lessons') }}/' + selectedLesson.id + '/update'" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="sm:col-span-1 space-y-1.5">
                            <label class="font-bold text-slate-700">Urutan Sesi <span class="text-rose-500">*</span></label>
                            <input type="number" name="sequence" x-model="selectedLesson.sequence" min="1" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 font-bold text-center">
                        </div>
                        <div class="sm:col-span-3 space-y-1.5">
                            <label class="font-bold text-slate-700">Judul Sesi / Modul Pembelajaran <span class="text-rose-500">*</span></label>
                            <input type="text" name="title" x-model="selectedLesson.title" required placeholder="Contoh: Tata Kelola Keamanan Informasi" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="space-y-1.5">
                            <label class="font-bold text-slate-700 text-xs">Durasi Belajar <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="number" name="duration_minutes" x-model="selectedLesson.duration_minutes" min="5" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 text-xs">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">Mnt</span>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="font-bold text-slate-700 text-xs flex items-center justify-between">
                                <span>Alokasi JP</span>
                                <span class="text-[9px] text-indigo-600 font-bold">Lampiran</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="jp" x-model="selectedLesson.jp" min="1" placeholder="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 text-xs font-bold text-indigo-700">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">JP</span>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="font-bold text-slate-700 text-xs">Opsi Kuis</label>
                            <div class="pt-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="has_quiz" value="1" :checked="selectedLesson.has_quiz" class="w-4 h-4 text-pasuruan-700 rounded border-slate-300 focus:ring-pasuruan-500">
                                    <span class="text-slate-700 font-semibold text-xs">Kuis Bab</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Video Pembelajaran -->
                    <div class="space-y-1.5 p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                        <div class="flex items-center justify-between">
                            <label class="font-bold text-slate-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-film text-rose-500"></i>
                                <span>Video Pembelajaran (URL / Embed)</span>
                            </label>
                            <span class="text-[10px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded font-bold">
                                Anti-Skip Enforced
                            </span>
                        </div>
                        <input type="url" name="video_url" x-model="selectedLesson.video_url" placeholder="https://www.youtube.com/watch?v=... atau link embed" class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-pasuruan-500 text-xs">
                    </div>

                    <!-- Ganti Dokumen Modul / PDF -->
                    <div class="space-y-1.5 p-4 rounded-xl bg-slate-50 border border-slate-200/80" x-data="{ editPdfName: '' }">
                        <label class="font-bold text-slate-800 flex items-center gap-1.5">
                            <i class="fa-solid fa-file-pdf text-rose-600"></i>
                            <span>Ganti Berkas Dokumen Materi / PDF (Opsional)</span>
                        </label>
                        <input type="file" name="pdf_file" accept=".pdf" @change="editPdfName = $event.target.files[0] ? $event.target.files[0].name : ''" class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-pasuruan-50 file:text-pasuruan-700 hover:file:bg-pasuruan-100 cursor-pointer">
                        <template x-if="selectedLesson.pdf_path && !editPdfName">
                            <p class="text-[10px] text-slate-500">Berkas saat ini: <strong class="text-slate-700" x-text="selectedLesson.pdf_path.split('/').pop()"></strong> (Kosongkan jika tidak ingin mengganti)</p>
                        </template>
                        <template x-if="editPdfName">
                            <p class="text-[10px] text-emerald-600 font-bold" x-cloak><i class="fa-solid fa-circle-check"></i> Berkas baru terpilih: <span x-text="editPdfName"></span></p>
                        </template>
                    </div>

                    <!-- Ringkasan Materi Pokok -->
                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700">Ringkasan Materi Pokok Sesi</label>
                        <textarea name="content_summary" x-model="selectedLesson.content_summary" rows="3" placeholder="Poin-poin penting materi sesi..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500"></textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button" @click="editLessonModalOpen = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2">
                            <i class="fa-solid fa-floppy-disk text-amber-300"></i>
                            <span>Simpan Perubahan Sesi</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus Sesi -->
    <div x-show="deleteLessonModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200 my-auto text-center">
                <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto text-2xl">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-base font-extrabold text-slate-900">Hapus Sesi Pembelajaran?</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Apakah Anda yakin ingin menghapus <strong>Sesi <span x-text="selectedLesson.sequence"></span>: <span x-text="selectedLesson.title"></span></strong>?<br>
                    <span class="text-rose-600 font-medium">Semua butir soal kuis di dalam sesi ini juga akan dihapus permanen.</span>
                </p>

                <form :action="'{{ url('admin/courses/' . $course->id . '/lessons') }}/' + selectedLesson.id + '/delete'" method="POST" class="pt-2 flex items-center justify-center gap-2.5">
                    @csrf
                    <button type="button" @click="deleteLessonModalOpen = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-xs transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-trash"></i>
                        <span>Ya, Hapus Sesi</span>
                    </button>
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
