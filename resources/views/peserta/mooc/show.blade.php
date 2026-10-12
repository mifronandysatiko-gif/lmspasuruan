@extends('layouts.dashboard')

@section('title', $course->title . ' - Ruang Belajar MOOC')
@section('header_title', 'Ruang Belajar MOOC Mandiri')

@section('content')
@php
    $daysLeft = $enrollment->remainingDays();
    $deadline = $enrollment->deadline_at ?: $course->end_date;
    $isFailed = $enrollment->status === 'failed';
    $totalLessons = $course->lessons->count();
    $completedLessonsCount = $lessonCompletions ? $lessonCompletions->where('is_passed', true)->count() : 0;

    // Siapkan data seluruh sesi beserta status penyelesaiannya untuk Alpine.js
    $lessonsJson = $course->lessons->map(function($lesson) use ($lessonCompletions) {
        $comp = $lessonCompletions->get($lesson->id);

        return [
            'id' => $lesson->id,
            'sequence' => $lesson->sequence,
            'title' => $lesson->title,
            'duration_seconds' => $lesson->duration_seconds,
            'duration_minutes' => round(($lesson->duration_seconds ?: 600) / 60),
            'video_url' => $lesson->video_url,
            'embed_video_url' => $lesson->embed_video_url,
            'is_direct_video' => $lesson->is_direct_video,
            'pdf_path' => $lesson->pdf_path,
            'pdf_url' => $lesson->pdf_url,
            'pdf_name' => $lesson->pdf_name,
            'content_summary' => $lesson->content_summary ?: 'Pelajari materi dan regulasi pokok pada sesi ini secara seksama.',
            'has_quiz' => (bool) $lesson->has_quiz,
            'questions' => $lesson->questions->map(function($q) {
                return [
                    'id' => $q->id,
                    'question_text' => $q->question_text,
                    'options' => $q->options,
                    'order_number' => $q->order_number,
                ];
            }),
            'is_video_completed' => (bool) ($comp?->is_video_completed ?? false),
            'is_pdf_read' => (bool) ($comp?->is_pdf_read ?? false),
            'is_passed' => (bool) ($comp?->is_passed ?? false),
            'quiz_score' => $comp?->quiz_score !== null ? (float) $comp->quiz_score : null,
        ];
    })->values();

    // Tentukan indeks sesi aktif awal (sesi pertama yang belum tuntas, atau sesi ke-0)
    $initialIndex = 0;
    foreach ($lessonsJson as $idx => $l) {
        if (!$l['is_passed']) {
            $initialIndex = $idx;
            break;
        }
    }
@endphp

<div class="space-y-6" x-data="{ 
    lessons: {{ Js::from($lessonsJson) }},
    activeLessonIndex: {{ $initialIndex }},
    quizOpen: false,
    questionnaireOpen: false,
    gatingModalOpen: false,
    antiSkipWarning: false,
    isFailed: {{ $isFailed ? 'true' : 'false' }},
    isSaving: false,
    progressPercentage: {{ $enrollment->progress_percentage ?? 0 }},
    userQuestionnaire: {{ Js::from($userQuestionnaire ? [
        'rating_materi' => (int) $userQuestionnaire->rating_materi,
        'rating_sistem' => (int) $userQuestionnaire->rating_sistem,
        'rating_manfaat' => (int) $userQuestionnaire->rating_manfaat,
        'rating_instruktur' => (int) $userQuestionnaire->rating_instruktur,
        'feedback_text' => $userQuestionnaire->feedback_text,
        'average_rating' => $userQuestionnaire->average_rating,
    ] : null) }},
    assignmentFile: '{{ $enrollment->assignment_file ?? '' }}',
    assignmentNotes: '{{ addslashes($enrollment->assignment_notes ?? '') }}',
    hasAssignment: {{ $enrollment->assignment_file ? 'true' : 'false' }},
    toast: { show: false, message: '', type: 'success' },

    showToast(msg, type = 'success') {
        this.toast.message = msg;
        this.toast.type = type;
        this.toast.show = true;
        setTimeout(() => { this.toast.show = false; }, 6000);
    },

    currentLesson() {
        return this.lessons[this.activeLessonIndex] || this.lessons[0] || {};
    },

    selectLesson(index) {
        if (index >= 0 && index < this.lessons.length) {
            this.activeLessonIndex = index;
            window.scrollTo({ top: 120, behavior: 'smooth' });
        }
    },

    async markVideoCompleted() {
        const cur = this.currentLesson();
        if (!cur.id) return;
        cur.is_video_completed = true;

        try {
            const res = await fetch('{{ url('peserta/mooc/' . $course->slug . '/complete-lesson') }}/' + cur.id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ type: 'video' })
            });
            const data = await res.json();
            if (data && data.progress_percentage !== undefined) {
                this.progressPercentage = data.progress_percentage;
            }

            // Jika sesi tanpa kuis dan pdf sudah dibaca, tandai lulus
            if ((!cur.has_quiz || !cur.questions || cur.questions.length === 0) && cur.is_pdf_read) {
                cur.is_passed = true;
            }
        } catch (e) {
            console.error('Gagal mencatat video selesai:', e);
        }
    },

    async markPdfRead() {
        const cur = this.currentLesson();
        if (!cur.id) return;
        cur.is_pdf_read = true;

        try {
            const res = await fetch('{{ url('peserta/mooc/' . $course->slug . '/complete-lesson') }}/' + cur.id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ type: 'pdf' })
            });
            const data = await res.json();
            if (data && data.progress_percentage !== undefined) {
                this.progressPercentage = data.progress_percentage;
            }

            // Jika sesi tanpa kuis dan video sudah ditonton, tandai lulus
            if ((!cur.has_quiz || !cur.questions || cur.questions.length === 0) && cur.is_video_completed) {
                cur.is_passed = true;
            }
        } catch (e) {
            console.error('Gagal mencatat PDF dibaca:', e);
        }
    },

    checkGatingAndOpenQuiz() {
        if (this.isFailed) {
            alert('Masa pengerjaan pelatihan ini telah berakhir. Aktivitas kuis dikunci karena status dinyatakan Tidak Lulus.');
            return false;
        }

        const cur = this.currentLesson();
        if (!cur.is_video_completed || !cur.is_pdf_read) {
            this.antiSkipWarning = true;
            this.gatingModalOpen = true;
            setTimeout(() => this.antiSkipWarning = false, 5000);
            return false;
        }

        this.quizOpen = true;
    },

    async submitQuiz(e) {
        this.isSaving = true;
        const form = e.target;
        const formData = new FormData(form);
        try {
            const res = await fetch('{{ route('peserta.mooc.quiz', $course->slug) }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });
            const data = await res.json();
            if (res.ok && data.success) {
                const cur = this.currentLesson();
                cur.is_passed = data.is_passed;
                cur.quiz_score = data.quiz_score;
                cur.is_video_completed = true;
                cur.is_pdf_read = true;
                this.progressPercentage = data.progress_percentage;
                this.quizOpen = false;
                this.showToast(data.message, data.is_passed ? 'success' : 'warning');
            } else {
                this.showToast(data.message || 'Gagal mengirimkan kuis evaluasi.', 'error');
            }
        } catch (err) {
            this.showToast('Terjadi gangguan jaringan saat mengirim kuis evaluasi.', 'error');
        } finally {
            this.isSaving = false;
        }
    },

    async submitQuestionnaire(e) {
        this.isSaving = true;
        const form = e.target;
        const formData = new FormData(form);
        try {
            const res = await fetch('{{ route('peserta.mooc.questionnaire', $course->slug) }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.userQuestionnaire = data.ratings;
                this.questionnaireOpen = false;
                this.showToast(data.message, 'success');
            } else {
                this.showToast(data.message || 'Gagal menyimpan kuesioner evaluasi.', 'error');
            }
        } catch (err) {
            this.showToast('Terjadi gangguan jaringan saat mengirim kuesioner.', 'error');
        } finally {
            this.isSaving = false;
        }
    },

    async submitAssignment(e) {
        this.isSaving = true;
        const form = e.target;
        const formData = new FormData(form);
        try {
            const res = await fetch('{{ route('peserta.mooc.assignment', $course->slug) }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.assignmentFile = data.assignment_file;
                this.assignmentNotes = data.assignment_notes;
                this.hasAssignment = true;
                this.showToast(data.message, 'success');
            } else {
                this.showToast(data.message || 'Gagal mengunggah berkas tugas.', 'error');
            }
        } catch (err) {
            this.showToast('Terjadi gangguan jaringan saat mengunggah tugas.', 'error');
        } finally {
            this.isSaving = false;
        }
    }
}">
    
    <!-- Top Breadcrumbs & Course Header Banner Card -->
    <div class="relative rounded-3xl overflow-hidden shadow-lg p-6 sm:p-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 border border-slate-700/50"
         style="background-color: #071f33; min-height: 140px;">
        @if($course->banner_url)
            <img src="{{ $course->banner_url }}" alt="{{ $course->title }}" class="absolute inset-0 w-full h-full object-cover object-center pointer-events-none select-none">
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-900/90 to-slate-950/85 pointer-events-none"></div>
        @else
            <div class="absolute inset-0 bg-gradient-to-r from-pasuruan-950 via-pasuruan-900 to-slate-950 pointer-events-none"></div>
        @endif

        <div class="relative z-10 space-y-2.5 max-w-3xl">
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-white/20 text-slate-100 backdrop-blur-xs border border-white/20">
                    <i class="fa-solid fa-graduation-cap mr-1 text-amber-300"></i> {{ strtoupper(str_replace('_', ' ', $course->type)) }}
                </span>
                <span class="text-xs text-sky-200 font-semibold bg-sky-950/70 px-2.5 py-0.5 rounded-md border border-sky-800/40">
                    {{ $course->category }}
                </span>
                @if($deadline)
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold {{ $isFailed ? 'bg-rose-600 text-white' : ($daysLeft !== null && $daysLeft <= 3 ? 'bg-rose-500 text-white animate-pulse' : 'bg-amber-400 text-slate-900') }}">
                        <i class="fa-regular fa-clock mr-1"></i> Batas: {{ $deadline->format('H:i') !== '00:00' ? $deadline->format('d M Y, H:i \W\I\B') : $deadline->isoFormat('D MMM Y') }}
                        @if($isFailed)
                            (Kadaluarsa)
                        @elseif($enrollment)
                            ({{ $enrollment->remainingTimeDescription() }})
                        @endif
                    </span>
                @endif
            </div>
            <h2 class="text-xl sm:text-2xl font-black tracking-tight leading-snug text-white drop-shadow-md">{{ $course->title }}</h2>
            <p class="text-xs text-slate-200 flex flex-wrap items-center gap-3 font-medium drop-shadow-xs">
                <span class="flex items-center gap-1"><i class="fa-solid fa-hourglass-half text-amber-400"></i> <strong>{{ $course->jp }} JP ({{ $course->jp * 45 }} Menit)</strong></span>
                <span>•</span>
                <span class="flex items-center gap-1"><i class="fa-solid fa-award text-emerald-400"></i> Passing Grade &ge; <strong>{{ $course->passing_grade }}</strong></span>
                <span>•</span>
                <span class="flex items-center gap-1"><i class="fa-solid fa-layer-group text-sky-400"></i> {{ $course->lessons->count() }} Sesi Modul</span>
                <span>•</span>
                <span class="flex items-center gap-1 font-bold text-emerald-300">
                    <i class="fa-solid fa-chart-line"></i> Progres: <span x-text="progressPercentage + '%'">{{ $enrollment->progress_percentage }}%</span>
                </span>
            </p>
        </div>

        <div class="relative z-10 flex flex-wrap items-center gap-3 shrink-0">
            <a href="{{ route('peserta.katalog') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white font-bold text-xs rounded-xl border border-white/20 transition-all flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Katalog
            </a>
        </div>
    </div>

    @if($isFailed)
    <!-- ALERT KADALUARSA / TIDAK LULUS KARENA MELEWATI BATAS WAKTU -->
    <div class="bg-rose-600 text-white p-5 rounded-2xl shadow-lg border border-rose-700 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-start sm:items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center text-2xl shrink-0">
                <i class="fa-solid fa-triangle-exclamation text-amber-300"></i>
            </div>
            <div>
                <h3 class="font-extrabold text-sm sm:text-base">Masa Pengerjaan Pelatihan Telah Berakhir (Tidak Lulus)</h3>
                <p class="text-xs text-rose-100 leading-relaxed mt-0.5">
                    Batas waktu pengerjaan modul pelatihan ini telah berakhir pada <strong>{{ $deadline ? ($deadline->format('H:i') !== '00:00' ? $deadline->format('d M Y, H:i \W\I\B') : $deadline->isoFormat('D MMMM Y')) : '-' }}</strong>. Seluruh aktivitas belajar, pengunggahan tugas, dan kuis evaluasi dikunci.
                </p>
            </div>
        </div>
        <a href="{{ route('peserta.katalog') }}" class="px-4 py-2.5 bg-white text-rose-700 hover:bg-rose-50 font-bold text-xs rounded-xl shadow-xs shrink-0 transition-colors">
            Kembali ke Katalog
        </a>
    </div>
    @endif

    <!-- Alert Status Kelulusan & Persetujuan Laporan Tugas Akhir -->
    @if($enrollment->status === 'passed')
        <div class="p-4 bg-emerald-50 border border-emerald-300 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-emerald-900 shadow-2xs">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold">Selamat! Laporan Tugas Akhir Anda Telah Disetujui & Dinyatakan Lulus</h4>
                    <p class="text-[11px] text-emerald-700">Nilai Akhir: <strong>{{ $enrollment->final_score }} / 100</strong> • E-Sertifikat resmi dan perolehan {{ $course->jp }} JP Anda telah disahkan.</p>
                </div>
            </div>
            <a href="{{ route('peserta.certificates') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl transition-colors shrink-0 shadow-xs flex items-center gap-1.5">
                <i class="fa-solid fa-certificate text-amber-300"></i>
                <span>Lihat / Unduh E-Sertifikat &rarr;</span>
            </a>
        </div>
    @elseif($enrollment->status === 'revision')
        <div class="p-4 bg-amber-50 border border-amber-300 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-amber-900 shadow-2xs">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-amber-500 text-white flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold">Laporan Tugas Akhir Memerlukan Perbaikan / Revisi</h4>
                    <p class="text-[11px] text-amber-800">Catatan Pengampu: "{{ $enrollment->instructor_feedback ?? 'Silakan perbaiki berkas laporan sesuai arahan dan unggah kembali di bawah.' }}"</p>
                </div>
            </div>
            <a href="#assignment-box" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl transition-colors shrink-0 shadow-xs flex items-center gap-1.5">
                <i class="fa-solid fa-upload"></i>
                <span>Unggah Revisi &darr;</span>
            </a>
        </div>
    @elseif($enrollment->assignment_file)
        <div class="p-4 bg-sky-50 border border-sky-300 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-sky-950 shadow-2xs">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-sky-500 text-white flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-file-circle-check"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold">Laporan Tugas Akhir Telah Terkirim (Menunggu Persetujuan)</h4>
                    <p class="text-[11px] text-sky-700">Berkas Anda sedang dalam antrean telaah oleh Penyelenggara / Super Admin / Widyaiswara. E-Sertifikat akan terbit setelah disetujui.</p>
                </div>
            </div>
            <span class="px-3 py-1.5 bg-sky-100 text-sky-800 font-bold text-xs rounded-xl border border-sky-200">
                <i class="fa-solid fa-clock mr-1 text-sky-600"></i> Dalam Antrean Telaah
            </span>
        </div>
    @elseif($completedLessonsCount >= $totalLessons && $totalLessons > 0)
        <div class="p-4 bg-amber-50 border border-amber-300 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-amber-950 shadow-2xs">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-amber-500 text-white flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-file-arrow-up"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold">Seluruh Sesi Modul Selesai! Langkah Terakhir: Unggah Laporan</h4>
                    <p class="text-[11px] text-amber-800">Silakan unggah Berkas Laporan Tugas Akhir / Portofolio di bawah untuk ditelaah dan disetujui agar E-Sertifikat resmi dapat diterbitkan.</p>
                </div>
            </div>
            <a href="#assignment-box" class="px-4 py-2 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-xl transition-colors shrink-0 shadow-xs flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-down"></i>
                <span>Unggah Laporan &darr;</span>
            </a>
        </div>
    @endif

    <!-- Floating Notification Toast jika mencoba kuis sebelum tuntas -->
    <div x-show="antiSkipWarning" x-cloak 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-4"
         x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed top-6 right-6 z-50 max-w-md p-4 bg-amber-600 text-white rounded-2xl text-xs font-bold flex items-start gap-3 shadow-2xl border border-amber-400">
        <i class="fa-solid fa-triangle-exclamation text-xl shrink-0 mt-0.5"></i>
        <div class="space-y-1">
            <div class="font-extrabold text-white text-sm">Prasyarat Pembelajaran Belum Lengkap:</div>
            <p class="text-amber-100 font-normal leading-relaxed">Silakan putar/tonton video materi dan konfirmasi bacaan dokumen materi sebelum membuka Kuis Evaluasi!</p>
        </div>
        <button type="button" @click="antiSkipWarning = false" class="text-amber-200 hover:text-white ml-auto p-1">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>

    <!-- Main Learning Layout: Video & Content + Module Playlist Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Kolom Kiri: Player Video Asli & Materi Bacaan -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- Video Player Asli (Real Video / YouTube Player) -->
            <div class="bg-slate-900 rounded-2xl overflow-hidden shadow-md relative border border-slate-800">
                <!-- Header Sesi Video -->
                <div class="p-3.5 bg-slate-950 border-b border-slate-800 flex items-center justify-between text-xs text-white">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-pasuruan-700 text-amber-300 font-bold rounded text-[10px]" x-text="'Sesi ' + (currentLesson().sequence || 1)"></span>
                        <h3 class="font-bold text-white truncate max-w-md" x-text="currentLesson().title || 'Materi Pelajaran'"></h3>
                    </div>
                    <div class="text-[11px] text-slate-400 flex items-center gap-1.5 shrink-0">
                        <i class="fa-regular fa-clock text-amber-400"></i>
                        <span x-text="(currentLesson().duration_minutes || 15) + ' Menit'"></span>
                    </div>
                </div>

                <!-- Video Frame Area -->
                <div class="aspect-video bg-black flex items-center justify-center relative">
                    <!-- 1. Jika URL Video Langsung (MP4 / WebM) -->
                    <template x-if="currentLesson().is_direct_video && currentLesson().video_url">
                        <video :src="currentLesson().video_url" 
                               controls 
                               controlsList="nodownload"
                               class="w-full h-full object-contain"
                               @ended="markVideoCompleted()">
                        </video>
                    </template>

                    <!-- 2. Jika URL Video YouTube / Vimeo / Embed -->
                    <template x-if="!currentLesson().is_direct_video && currentLesson().embed_video_url">
                        <iframe :src="currentLesson().embed_video_url" 
                                class="w-full h-full border-0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                allowfullscreen>
                        </iframe>
                    </template>

                    <!-- 3. Jika Belum Ada Tautan Video -->
                    <template x-if="!currentLesson().video_url">
                        <div class="text-center p-8 space-y-3 text-slate-400">
                            <i class="fa-solid fa-film text-4xl text-slate-600"></i>
                            <p class="font-bold text-xs text-slate-300">Video pembelajaran belum diunggah untuk sesi ini</p>
                            <p class="text-[11px] text-slate-500">Silakan pelajari dokumen bahan bacaan di bawah ini.</p>
                        </div>
                    </template>
                </div>

                <!-- Action Bar Bawah Video -->
                <div class="p-3.5 bg-slate-950 border-t border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-2">
                        <button type="button" 
                                @click="markVideoCompleted()" 
                                :class="currentLesson().is_video_completed ? 'bg-emerald-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700'"
                                class="px-3.5 py-1.5 rounded-lg font-bold text-xs transition-all flex items-center gap-2 shadow-xs cursor-pointer">
                            <i class="fa-solid" :class="currentLesson().is_video_completed ? 'fa-circle-check text-white' : 'fa-check text-slate-400'"></i>
                            <span x-text="currentLesson().is_video_completed ? 'Video Telah Ditonton' : 'Tandai Video Selesai Ditonton'"></span>
                        </button>
                    </div>

                    <div class="text-[11px]">
                        <span class="text-slate-400">Status Video: </span>
                        <strong :class="currentLesson().is_video_completed ? 'text-emerald-400' : 'text-amber-400'" 
                                x-text="currentLesson().is_video_completed ? 'Selesai (Tuntas)' : 'Belum Selesai'"></strong>
                    </div>
                </div>
            </div>

            <!-- Tab Materi Bacaan PDF & Ringkasan Bab -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4 shadow-2xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-base text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-book-open-reader text-pasuruan-700"></i>
                        <span>Materi Bacaan & Dokumen Referensi</span>
                    </h3>
                    <button type="button" 
                            @click="markPdfRead()" 
                            :class="currentLesson().is_pdf_read ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-700 border-slate-300 hover:bg-slate-200'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold border transition-colors flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid" :class="currentLesson().is_pdf_read ? 'fa-circle-check text-emerald-600' : 'fa-check text-slate-400'"></i>
                        <span x-text="currentLesson().is_pdf_read ? 'Dokumen Terbaca Selesai' : 'Tandai Dokumen Sudah Dibaca'"></span>
                    </button>
                </div>

                <!-- Box Dokumen PDF Asli -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-file-pdf"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900" x-text="currentLesson().pdf_name || 'Dokumen_Materi.pdf'"></h4>
                            <p class="text-[11px] text-slate-500">Berkas Paparan / Modul Pembelajaran Resmi BKPSDM</p>
                        </div>
                    </div>
                    <template x-if="currentLesson().pdf_url">
                        <a :href="currentLesson().pdf_url" 
                           target="_blank"
                           @click="markPdfRead()" 
                           class="px-3.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs rounded-lg border border-slate-300 shadow-2xs flex items-center gap-1.5 shrink-0 transition-colors">
                            <i class="fa-solid fa-download text-slate-500"></i>
                            <span>Buka / Unduh PDF</span>
                        </a>
                    </template>
                </div>

                <!-- Ringkasan Materi Pokok -->
                <div class="text-xs text-slate-600 leading-relaxed bg-slate-50/50 p-4 rounded-xl border border-slate-100 space-y-1.5">
                    <span class="font-bold text-slate-800 block text-xs">Poin-Poin Pembelajaran:</span>
                    <p x-text="currentLesson().content_summary"></p>
                </div>

                <!-- Tombol Action Kuis Evaluasi Kelulusan Sesi Ini -->
                <div class="pt-4 border-t border-slate-100 space-y-3">
                    <!-- Status Prasyarat Box -->
                    <div class="p-3.5 rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
                         :class="(currentLesson().is_video_completed && currentLesson().is_pdf_read) ? 'bg-emerald-50/70 border-emerald-300 text-emerald-900' : 'bg-amber-50/60 border-amber-200 text-amber-900'">
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid" :class="(currentLesson().is_video_completed && currentLesson().is_pdf_read) ? 'fa-circle-check text-emerald-600 text-lg' : 'fa-lock text-amber-600 text-lg'"></i>
                            <div>
                                <div class="font-bold" x-text="(currentLesson().is_video_completed && currentLesson().is_pdf_read) ? 'Prasyarat Terpenuhi: Siap Melanjutkan' : 'Prasyarat Penyelesaian Sesi ' + (currentLesson().sequence || 1) + ':'"></div>
                                <div class="text-[11px] text-slate-600 flex flex-wrap items-center gap-2.5 mt-0.5">
                                    <span class="flex items-center gap-1 font-medium">
                                        <i class="fa-solid text-xs" :class="currentLesson().is_video_completed ? 'fa-check text-emerald-600 font-bold' : 'fa-xmark text-rose-500 font-bold'"></i>
                                        <span :class="currentLesson().is_video_completed ? 'text-emerald-800 font-bold' : 'text-slate-600'">1. Video Selesai</span>
                                    </span>
                                    <span>&bull;</span>
                                    <span class="flex items-center gap-1 font-medium">
                                        <i class="fa-solid text-xs" :class="currentLesson().is_pdf_read ? 'fa-check text-emerald-600 font-bold' : 'fa-xmark text-rose-500 font-bold'"></i>
                                        <span :class="currentLesson().is_pdf_read ? 'text-emerald-800 font-bold' : 'text-slate-600'">2. Dokumen Dibaca</span>
                                    </span>
                                    <template x-if="currentLesson().is_passed">
                                        <span>&bull; <strong class="text-emerald-700"><i class="fa-solid fa-award"></i> Lulus Sesi (Nilai: <span x-text="currentLesson().quiz_score !== null ? currentLesson().quiz_score : '100'"></span>)</strong></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-1">
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-800 px-2.5 py-1 rounded-lg border border-emerald-200 font-bold text-[11px]">
                                <i class="fa-solid fa-graduation-cap text-emerald-600"></i> Passing Grade: &ge; {{ $course->passing_grade }}
                            </span>
                            <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-800 px-2.5 py-1 rounded-lg border border-amber-200 font-bold text-[11px]">
                                <i class="fa-solid fa-arrow-up-right-dots text-amber-600"></i> Plafon Remedial: {{ $course->remedial_cap_score ?? 90 }}
                            </span>
                            <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg border border-slate-200 font-semibold text-[11px]">
                                <i class="fa-solid fa-rotate-right text-slate-500"></i> Maks. Remedial: {{ $course->max_remedial ?? 5 }}x
                            </span>
                        </div>

                        @if($isFailed)
                            <button type="button" disabled
                                    class="w-full sm:w-auto px-6 py-2.5 font-bold text-xs rounded-xl bg-slate-200 text-slate-400 cursor-not-allowed flex items-center justify-center gap-2">
                                <i class="fa-solid fa-ban text-rose-500"></i>
                                <span>Kuis Terkunci (Batas Waktu Berakhir)</span>
                            </button>
                        @else
                            <template x-if="currentLesson().has_quiz && currentLesson().questions && currentLesson().questions.length > 0">
                                <button type="button" @click="checkGatingAndOpenQuiz()" 
                                        :class="(currentLesson().is_video_completed && currentLesson().is_pdf_read) ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-md ring-2 ring-emerald-400/40' : 'bg-pasuruan-700 hover:bg-pasuruan-800 text-white shadow-xs'"
                                        class="w-full sm:w-auto px-6 py-2.5 font-bold text-xs rounded-xl transition-all flex items-center justify-center gap-2 cursor-pointer">
                                    <i class="fa-solid fa-pen-to-square text-amber-300"></i>
                                    <span x-text="currentLesson().is_passed ? 'Kerjakan Ulang Kuis Sesi ' + currentLesson().sequence : 'Buka Kuis Evaluasi Sesi ' + currentLesson().sequence + ' &rarr;'"></span>
                                </button>
                            </template>

                            <template x-if="!currentLesson().has_quiz || !currentLesson().questions || currentLesson().questions.length === 0">
                                <div class="text-xs font-bold text-emerald-700 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                                    <span>Sesi ini tidak mewajibkan kuis evaluasi.</span>
                                </div>
                            </template>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Kartu Unggah Berkas & Laporan Hasil Belajar Sesi (Syarat Wajib Terbit E-Sertifikat) -->
            <div id="assignment-box" class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5 shadow-2xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-pasuruan-100 text-pasuruan-800 flex items-center justify-center font-bold text-sm">
                            <i class="fa-solid fa-file-arrow-up"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-slate-900">Laporan Tugas Akhir / Portofolio Pelatihan</h3>
                            <p class="text-[11px] text-slate-500">Wajib diunggah setelah menuntaskan seluruh sesi modul untuk ditelaah dan disetujui Pengelola.</p>
                        </div>
                    </div>
                    <div>
                        @if($enrollment->status === 'passed')
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1">
                                <i class="fa-solid fa-circle-check text-emerald-600"></i> Disetujui & Lulus (Nilai: {{ $enrollment->final_score }})
                            </span>
                        @elseif($enrollment->status === 'revision')
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300 flex items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation text-rose-600"></i> Perlu Perbaikan (Revisi)
                            </span>
                        @elseif($enrollment->assignment_file)
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800 border border-sky-300 flex items-center gap-1">
                                <i class="fa-solid fa-clock text-sky-600"></i> Menunggu Persetujuan
                            </span>
                        @else
                            <span class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full text-[10px] font-bold border border-amber-300 flex items-center gap-1">
                                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Wajib Diunggah
                            </span>
                        @endif
                    </div>
                </div>

                <template x-if="hasAssignment">
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-xl shrink-0">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900" x-text="assignmentFile"></h4>
                                    <p class="text-[10px] text-slate-400">
                                        Status: Berkas telah tersimpan & menunggu telaah
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a :href="'{{ asset('storage/assignments') }}/' + assignmentFile" target="_blank" class="text-xs font-bold text-pasuruan-700 hover:text-pasuruan-900 bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-2xs flex items-center gap-1.5 transition-colors">
                                    <i class="fa-solid fa-download text-slate-500"></i>
                                    <span>Unduh / Buka Berkas</span>
                                </a>
                            </div>
                        </div>

                        <template x-if="assignmentNotes">
                            <div class="text-xs text-slate-600 bg-white p-3 rounded-lg border border-slate-200">
                                <span class="font-bold text-[11px] text-slate-500 block mb-0.5">Catatan Pengantar Anda:</span>
                                <span x-text="assignmentNotes"></span>
                            </div>
                        </template>

                        @if($enrollment->instructor_feedback)
                            <div class="p-3 bg-amber-50/80 border border-amber-200 rounded-lg text-xs space-y-1">
                                <div class="flex items-center justify-between font-bold text-amber-900 text-[11px]">
                                    <span>Catatan Telaah Penyelenggara / Widyaiswara:</span>
                                    <i class="fa-solid fa-comment-dots text-amber-600"></i>
                                </div>
                                <p class="text-amber-800 italic">"{{ $enrollment->instructor_feedback }}"</p>
                            </div>
                        @endif
                    </div>
                </template>

                @if(!$isFailed && $enrollment->status !== 'passed')
                    <form @submit.prevent="submitAssignment($event)" enctype="multipart/form-data" class="space-y-3 text-xs pt-1">
                        @csrf
                        <div class="space-y-1.5">
                            <label class="font-bold text-slate-700" x-text="hasAssignment ? 'Perbarui / Unggah Ulang Berkas Laporan Tugas' : 'Unggah Dokumen Laporan (PDF / DOCX / ZIP)'"></label>
                            <input type="file" name="assignment_file" accept=".pdf,.docx,.doc,.zip,.rar" class="w-full text-[11px] text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-pasuruan-100 file:text-pasuruan-700 hover:file:bg-pasuruan-200 cursor-pointer">
                        </div>

                        <div class="space-y-1.5">
                            <label class="font-bold text-slate-700">Catatan Pengantar Peserta</label>
                            <textarea name="assignment_notes" rows="2" placeholder="Catatan singkat terkait laporan tugas akhir / hasil implementasi..." class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500" x-model="assignmentNotes"></textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" :disabled="isSaving" class="px-5 py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-2 cursor-pointer disabled:opacity-75">
                                <i class="fa-solid" :class="isSaving ? 'fa-spinner fa-spin text-white' : 'fa-paper-plane text-amber-300'"></i>
                                <span x-text="isSaving ? 'Mengunggah Berkas...' : (hasAssignment ? 'Kirim Pembaruan Laporan' : 'Kirim Laporan Tugas & Ajukan Telaah')"></span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            <!-- Kartu Kuesioner Evaluasi & Kepuasan Pelatihan Mandiri (CSAT Diklat) -->
            <div id="questionnaire-box" class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5 shadow-2xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-sm">
                            <i class="fa-solid fa-star"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-slate-900">Evaluasi Kepuasan & Mutu Pelatihan</h3>
                            <p class="text-[11px] text-slate-500">Penilaian terhadap materi, kemudahan platform, kemanfaatan kompetensi ASN, dan mutu modul.</p>
                        </div>
                    </div>
                    <div>
                        <template x-if="userQuestionnaire">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1">
                                <i class="fa-solid fa-circle-check text-emerald-600"></i> Kuesioner Terisi (Skor: <span x-text="userQuestionnaire.average_rating"></span>/5)
                            </span>
                        </template>
                        <template x-if="!userQuestionnaire">
                            <span class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full text-[10px] font-bold border border-amber-300 flex items-center gap-1">
                                <i class="fa-solid fa-clock text-amber-600"></i> Belum Diisi
                            </span>
                        </template>
                    </div>
                </div>

                <template x-if="userQuestionnaire">
                    <div class="p-4 rounded-xl bg-amber-50/50 border border-amber-200/80 space-y-3">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                            <div class="bg-white p-2.5 rounded-lg border border-amber-100">
                                <span class="text-[10px] text-slate-500 block">Kesesuaian Materi</span>
                                <div class="font-bold text-amber-600 text-sm mt-0.5">
                                    <i class="fa-solid fa-star text-amber-400"></i> <span x-text="userQuestionnaire.rating_materi"></span>/5
                                </div>
                            </div>
                            <div class="bg-white p-2.5 rounded-lg border border-amber-100">
                                <span class="text-[10px] text-slate-500 block">Kemudahan LMS</span>
                                <div class="font-bold text-amber-600 text-sm mt-0.5">
                                    <i class="fa-solid fa-star text-amber-400"></i> <span x-text="userQuestionnaire.rating_sistem"></span>/5
                                </div>
                            </div>
                            <div class="bg-white p-2.5 rounded-lg border border-amber-100">
                                <span class="text-[10px] text-slate-500 block">Manfaat Tugas ASN</span>
                                <div class="font-bold text-amber-600 text-sm mt-0.5">
                                    <i class="fa-solid fa-star text-amber-400"></i> <span x-text="userQuestionnaire.rating_manfaat"></span>/5
                                </div>
                            </div>
                            <div class="bg-white p-2.5 rounded-lg border border-amber-100">
                                <span class="text-[10px] text-slate-500 block">Kualitas Modul</span>
                                <div class="font-bold text-amber-600 text-sm mt-0.5">
                                    <i class="fa-solid fa-star text-amber-400"></i> <span x-text="userQuestionnaire.rating_instruktur"></span>/5
                                </div>
                            </div>
                        </div>

                        <template x-if="userQuestionnaire.feedback_text">
                            <div class="text-xs text-slate-700 bg-white p-3 rounded-lg border border-amber-200">
                                <span class="font-bold text-[11px] text-slate-500 block mb-0.5">Masukan / Saran Anda:</span>
                                <em>"<span x-text="userQuestionnaire.feedback_text"></span>"</em>
                            </div>
                        </template>

                        <div class="flex justify-end pt-1">
                            <button type="button" @click="questionnaireOpen = true" class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-lg border border-slate-300 shadow-2xs flex items-center gap-1.5 transition-colors cursor-pointer">
                                <i class="fa-solid fa-pen text-slate-500"></i>
                                <span>Perbarui Penilaian Kuesioner</span>
                            </button>
                        </div>
                    </div>
                </template>
                <template x-if="!userQuestionnaire">
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Bantu kami meningkatkan kualitas penyelenggaraan pelatihan kedinasan Pemerintah Kabupaten Pasuruan dengan mengisi kuesioner evaluasi singkat (4 aspek penilaian kepuasan).
                        </p>
                        <div class="flex justify-end">
                            <button type="button" @click="questionnaireOpen = true" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-2 cursor-pointer">
                                <i class="fa-solid fa-star text-white"></i>
                                <span>Isi Kuesioner Evaluasi Sekarang</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

        </div>

        <!-- Kolom Kanan: Playlist Modul / Bab -->
        <div class="lg:col-span-4 space-y-6">
            
            <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4 shadow-2xs sticky top-20">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-bold text-sm text-slate-900">Struktur Modul Pelajaran</h3>
                    <span class="text-xs text-pasuruan-700 font-bold" x-text="lessons.length + ' Sesi Modul'"></span>
                </div>

                <div class="space-y-2.5 max-h-[550px] overflow-y-auto pr-1">
                    <template x-for="(les, idx) in lessons" :key="les.id">
                        <div @click="selectLesson(idx)"
                             class="p-3.5 rounded-xl border transition-all cursor-pointer space-y-2"
                             :class="activeLessonIndex === idx ? 'border-pasuruan-600 bg-pasuruan-50/70 shadow-sm ring-1 ring-pasuruan-500/50' : (les.is_passed ? 'border-emerald-200 bg-emerald-50/30 hover:border-emerald-400' : 'border-slate-200 bg-white hover:border-slate-300')">
                            
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded"
                                      :class="activeLessonIndex === idx ? 'bg-pasuruan-700 text-white' : (les.is_passed ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700')"
                                      x-text="'Bab ' + les.sequence"></span>
                                
                                <span class="text-[11px] text-slate-400 font-medium" x-text="les.duration_minutes + ' Mnt'"></span>
                            </div>

                            <h4 class="font-bold text-xs leading-snug" 
                                :class="activeLessonIndex === idx ? 'text-pasuruan-900' : 'text-slate-800'"
                                x-text="les.title"></h4>

                            <div class="flex items-center justify-between text-[11px] pt-1.5 border-t"
                                 :class="activeLessonIndex === idx ? 'border-pasuruan-200/80' : 'border-slate-100'">
                                <span class="text-slate-500 text-[10px]">
                                    <i class="fa-solid fa-video text-xs mr-1 text-slate-400"></i>
                                    <span x-text="les.has_quiz ? 'Video & Kuis' : 'Video Pembelajaran'"></span>
                                </span>

                                <template x-if="les.is_passed">
                                    <span class="text-emerald-700 font-bold text-[10px] flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check text-emerald-600"></i>
                                        <span x-text="les.quiz_score !== null ? 'Tuntas (' + les.quiz_score + ')' : 'Tuntas'"></span>
                                    </span>
                                </template>

                                <template x-if="!les.is_passed && activeLessonIndex === idx">
                                    <span class="text-pasuruan-700 font-bold text-[10px] flex items-center gap-1">
                                        <i class="fa-solid fa-circle-play text-pasuruan-700"></i>
                                        <span>Sedang Dipelajari</span>
                                    </span>
                                </template>

                                <template x-if="!les.is_passed && activeLessonIndex !== idx">
                                    <span class="text-slate-400 text-[10px]">
                                        <span>Belum Selesai</span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Kartu Info Alur Penerbitan E-Sertifikat -->
            <div class="p-5 rounded-2xl bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200 space-y-2.5 shadow-2xs">
                <div class="flex items-center gap-2 text-amber-900 font-bold text-xs">
                    <i class="fa-solid fa-certificate text-amber-600 text-base"></i>
                    <span>Ketentuan Penerbitan E-Sertifikat</span>
                </div>
                <div class="text-[11px] text-amber-950/80 leading-relaxed space-y-1.5">
                    <p>E-Sertifikat resmi ber-barcode QR Code diterbitkan setelah memenuhi 3 syarat:</p>
                    <ol class="list-decimal list-inside space-y-1 text-[10.5px]">
                        <li>Menuntaskan video/materi & lulus seluruh kuis sesi (&ge; {{ $course->passing_grade }}).</li>
                        <li>Mengunggah Laporan Tugas Akhir / Hasil Belajar.</li>
                        <li>Mendapatkan persetujuan kelulusan dari Penyelenggara / Super Admin / Widyaiswara pengampu.</li>
                    </ol>
                </div>
            </div>

        </div>

    </div>

    <!-- Modal Peringatan Prasyarat (Gating Modal) -->
    <div x-show="gatingModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200 my-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2 text-amber-800 font-bold text-sm">
                        <i class="fa-solid fa-shield-halved text-amber-500 text-base"></i>
                        <span>Kuis Evaluasi Sesi Masih Terkunci</span>
                    </div>
                    <button type="button" @click="gatingModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <p class="text-xs text-slate-600 leading-relaxed">
                    Untuk memastikan kompetensi tercapai dengan baik, Anda wajib menuntaskan materi sesi ini sebelum membuka Kuis Evaluasi:
                </p>

                <div class="space-y-2.5 text-xs">
                    <div class="p-3 rounded-xl border flex items-center justify-between" :class="currentLesson().is_video_completed ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : 'bg-slate-50 border-slate-200 text-slate-700'">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid" :class="currentLesson().is_video_completed ? 'fa-circle-check text-emerald-600 text-base' : 'fa-circle-xmark text-slate-400 text-base'"></i>
                            <span class="font-medium">1. Tonton Video Pembelajaran</span>
                        </div>
                        <button type="button" @click="markVideoCompleted()" x-show="!currentLesson().is_video_completed" class="px-2.5 py-1 bg-pasuruan-700 hover:bg-pasuruan-800 text-white rounded-lg text-[10px] font-bold transition-colors">
                            Tandai Selesai
                        </button>
                    </div>

                    <div class="p-3 rounded-xl border flex items-center justify-between" :class="currentLesson().is_pdf_read ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : 'bg-slate-50 border-slate-200 text-slate-700'">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid" :class="currentLesson().is_pdf_read ? 'fa-circle-check text-emerald-600 text-base' : 'fa-circle-xmark text-slate-400 text-base'"></i>
                            <span class="font-medium">2. Konfirmasi Membaca Berkas PDF</span>
                        </div>
                        <button type="button" @click="markPdfRead()" x-show="!currentLesson().is_pdf_read" class="px-2.5 py-1 bg-pasuruan-700 hover:bg-pasuruan-800 text-white rounded-lg text-[10px] font-bold transition-colors">
                            Tandai Dibaca
                        </button>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                    <button type="button" @click="gatingModalOpen = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Interaktif Kuis Evaluasi Sesi Aktif -->
    <div x-show="quizOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2 text-pasuruan-800 font-bold text-sm">
                        <i class="fa-solid fa-file-pen text-amber-500"></i>
                        <span>Kuis Evaluasi: <span x-text="'Sesi ' + (currentLesson().sequence || 1) + ' - ' + currentLesson().title"></span></span>
                    </div>
                    <button @click="quizOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="submitQuiz($event)" class="space-y-4">
                    @csrf
                    <input type="hidden" name="lesson_id" :value="currentLesson().id">

                    <!-- Banner Informasi Regulasi Passing Grade & Remedial -->
                    <div class="p-3 bg-gradient-to-r from-emerald-50 via-teal-50 to-amber-50 border border-emerald-200/80 rounded-xl flex items-center justify-between text-xs text-slate-700">
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-circle-info text-emerald-600 text-sm shrink-0"></i>
                            <div>
                                <span class="font-bold text-slate-900">Ketentuan Evaluasi Sesi:</span>
                                <span class="text-[11px] text-slate-600 block">Passing Grade <strong class="text-emerald-700">&ge; {{ $course->passing_grade }}</strong> &bull; Plafon Remedial Maks. <strong class="text-amber-700">{{ $course->remedial_cap_score ?? 90 }}</strong> &bull; Batas <strong class="text-slate-800">{{ $course->max_remedial ?? 5 }}x</strong></span>
                            </div>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-white/90 border border-emerald-200 text-emerald-800 shrink-0 shadow-2xs">
                            Bobot: {{ $course->jp }} JP
                        </span>
                    </div>

                    <div class="space-y-4 text-xs max-h-96 overflow-y-auto pr-1">
                        <template x-if="currentLesson().questions && currentLesson().questions.length > 0">
                            <div class="space-y-3.5">
                                <template x-for="(q, idx) in currentLesson().questions" :key="q.id">
                                    <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2.5">
                                        <div class="flex items-start gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-pasuruan-700 text-white font-bold text-[10px] flex items-center justify-center shrink-0 mt-0.5" x-text="idx + 1"></span>
                                            <p class="font-bold text-slate-800 leading-snug" x-text="q.question_text"></p>
                                        </div>
                                        <div class="space-y-2 pl-7">
                                            <template x-for="(optVal, optKey) in (q.options || {})" :key="optKey">
                                                <label class="flex items-center gap-2.5 text-slate-700 cursor-pointer p-1.5 rounded-lg hover:bg-white transition-colors">
                                                    <input type="radio" :name="'answers[' + q.id + ']'" :value="optKey" required class="text-pasuruan-700 focus:ring-pasuruan-500">
                                                    <span class="font-mono font-bold text-slate-500 text-xs w-4" x-text="optKey + '.'"></span>
                                                    <span class="text-xs" x-text="optVal"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="!currentLesson().questions || currentLesson().questions.length === 0">
                            <div class="p-6 text-center bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                                <i class="fa-solid fa-circle-info text-2xl text-slate-400"></i>
                                <p class="font-bold text-xs text-slate-700">Belum ada pertanyaan kuis yang dikonfigurasi untuk sesi ini.</p>
                                <p class="text-[11px] text-slate-500">Anda dapat langsung menyelesaikan sesi ini tanpa pengisian kuis.</p>
                            </div>
                        </template>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-[11px] text-slate-500">Passing Grade: <strong class="text-emerald-700 font-bold">&ge; {{ $course->passing_grade }}</strong> <span class="text-slate-400 mx-1">&bull;</span> Plafon: <strong class="text-amber-700">{{ $course->remedial_cap_score ?? 90 }}</strong></span>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="quizOpen = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 text-xs font-bold rounded-xl transition-colors">
                                Batal
                            </button>
                            <button type="submit" :disabled="isSaving" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer disabled:opacity-75">
                                <i class="fa-solid" :class="isSaving ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                                <span x-text="isSaving ? 'Memeriksa Jawaban...' : 'Kirim Jawaban Kuis'"></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Pengisian Kuesioner Evaluasi Kepuasan Pelatihan -->
    <div x-show="questionnaireOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2 text-pasuruan-800 font-bold text-sm">
                        <i class="fa-solid fa-star text-amber-500"></i>
                        <span>Kuesioner Evaluasi Kepuasan Pelatihan</span>
                    </div>
                    <button @click="questionnaireOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="submitQuestionnaire($event)" class="space-y-4">
                    @csrf

                    <p class="text-xs text-slate-600">
                        Mohon berikan penilaian objektif Anda terhadap pelatihan <strong>"{{ $course->title }}"</strong> untuk evaluasi perbaikan BKPSDM Kabupaten Pasuruan.
                    </p>

                    <div class="space-y-4 text-xs max-h-96 overflow-y-auto pr-1">
                        <!-- 1. Kesesuaian Materi -->
                        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-800">1. Kesesuaian & Kualitas Materi Pembelajaran</label>
                                <span class="text-[10px] text-slate-400 font-medium">(Skala 1 - 5)</span>
                            </div>
                            <p class="text-[11px] text-slate-500">Materi relevan, runtut, mudah dipahami, dan sesuai standar kompetensi jabatan.</p>
                            <div class="flex items-center justify-between pt-1 px-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <label class="flex flex-col items-center gap-1 cursor-pointer">
                                        <input type="radio" name="rating_materi" value="{{ $i }}" {{ ($userQuestionnaire?->rating_materi ?? 5) == $i ? 'checked' : '' }} required class="text-amber-500 focus:ring-amber-400">
                                        <span class="text-[11px] font-bold text-slate-700">{{ $i }} <i class="fa-solid fa-star text-amber-400 text-[10px]"></i></span>
                                        <span class="text-[9px] text-slate-400">{{ $i == 1 ? 'Kurang' : ($i == 5 ? 'Sangat Baik' : '') }}</span>
                                    </label>
                                @endfor
                            </div>
                        </div>

                        <!-- 2. Kemudahan Platform LMS -->
                        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-800">2. Kemudahan & Kenyamanan Platform LMS</label>
                                <span class="text-[10px] text-slate-400 font-medium">(Skala 1 - 5)</span>
                            </div>
                            <p class="text-[11px] text-slate-500">Aplikasi responsif, pemutaran video lancar, navigasi jelas, dan unduh dokumen cepat.</p>
                            <div class="flex items-center justify-between pt-1 px-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <label class="flex flex-col items-center gap-1 cursor-pointer">
                                        <input type="radio" name="rating_sistem" value="{{ $i }}" {{ ($userQuestionnaire?->rating_sistem ?? 5) == $i ? 'checked' : '' }} required class="text-amber-500 focus:ring-amber-400">
                                        <span class="text-[11px] font-bold text-slate-700">{{ $i }} <i class="fa-solid fa-star text-amber-400 text-[10px]"></i></span>
                                        <span class="text-[9px] text-slate-400">{{ $i == 1 ? 'Kurang' : ($i == 5 ? 'Sangat Baik' : '') }}</span>
                                    </label>
                                @endfor
                            </div>
                        </div>

                        <!-- 3. Kemanfaatan bagi Tugas ASN -->
                        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-800">3. Kemanfaatan terhadap Pelaksanaan Tugas ASN</label>
                                <span class="text-[10px] text-slate-400 font-medium">(Skala 1 - 5)</span>
                            </div>
                            <p class="text-[11px] text-slate-500">Materi bermanfaat langsung untuk menunjang tupoksi & kinerja di Perangkat Daerah.</p>
                            <div class="flex items-center justify-between pt-1 px-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <label class="flex flex-col items-center gap-1 cursor-pointer">
                                        <input type="radio" name="rating_manfaat" value="{{ $i }}" {{ ($userQuestionnaire?->rating_manfaat ?? 5) == $i ? 'checked' : '' }} required class="text-amber-500 focus:ring-amber-400">
                                        <span class="text-[11px] font-bold text-slate-700">{{ $i }} <i class="fa-solid fa-star text-amber-400 text-[10px]"></i></span>
                                        <span class="text-[9px] text-slate-400">{{ $i == 1 ? 'Kurang' : ($i == 5 ? 'Sangat Baik' : '') }}</span>
                                    </label>
                                @endfor
                            </div>
                        </div>

                        <!-- 4. Kualitas Modul & Penyajian -->
                        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-800">4. Kualitas Modul & Penyajian Materi</label>
                                <span class="text-[10px] text-slate-400 font-medium">(Skala 1 - 5)</span>
                            </div>
                            <p class="text-[11px] text-slate-500">Kualitas tayangan video, intonasi pengajar/narasumber, dan kejelasan bahan tayang.</p>
                            <div class="flex items-center justify-between pt-1 px-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <label class="flex flex-col items-center gap-1 cursor-pointer">
                                        <input type="radio" name="rating_instruktur" value="{{ $i }}" {{ ($userQuestionnaire?->rating_instruktur ?? 5) == $i ? 'checked' : '' }} required class="text-amber-500 focus:ring-amber-400">
                                        <span class="text-[11px] font-bold text-slate-700">{{ $i }} <i class="fa-solid fa-star text-amber-400 text-[10px]"></i></span>
                                        <span class="text-[9px] text-slate-400">{{ $i == 1 ? 'Kurang' : ($i == 5 ? 'Sangat Baik' : '') }}</span>
                                    </label>
                                @endfor
                            </div>
                        </div>

                        <!-- 5. Masukan / Kritik & Saran -->
                        <div class="space-y-1.5">
                            <label class="font-bold text-slate-700">Kritik, Saran & Masukan Peningkatan (Opsional)</label>
                            <textarea name="feedback_text" rows="3" placeholder="Tuliskan masukan atau saran Anda terkait materi, narasumber, atau penyelenggaraan diklat..." class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 text-xs">{{ $userQuestionnaire?->feedback_text ?? '' }}</textarea>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="questionnaireOpen = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 text-xs font-bold rounded-xl transition-colors">
                            Batal
                        </button>
                        <button type="submit" :disabled="isSaving" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-slate-950 text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                            <template x-if="isSaving">
                                <i class="fa-solid fa-circle-notch fa-spin"></i>
                            </template>
                            <template x-if="!isSaving">
                                <i class="fa-solid fa-paper-plane"></i>
                            </template>
                            <span x-text="isSaving ? 'Menyimpan Kuesioner...' : 'Simpan Kuesioner Evaluasi'">Simpan Kuesioner Evaluasi</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Floating Toast Notification -->
    <div x-show="toast.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:translate-x-4"
         x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:translate-x-0"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:translate-x-4"
         x-cloak
         class="fixed bottom-6 right-6 z-50 max-w-md w-full shadow-2xl rounded-2xl p-4 flex items-start gap-3 border backdrop-blur-md"
         :class="{
            'bg-emerald-950/95 border-emerald-500/50 text-emerald-100': toast.type === 'success',
            'bg-amber-950/95 border-amber-500/50 text-amber-100': toast.type === 'warning',
            'bg-rose-950/95 border-rose-500/50 text-rose-100': toast.type === 'error'
         }">
        <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
             :class="{
                'bg-emerald-500/20 text-emerald-400': toast.type === 'success',
                'bg-amber-500/20 text-amber-400': toast.type === 'warning',
                'bg-rose-500/20 text-rose-400': toast.type === 'error'
             }">
            <i class="fa-solid" :class="{
                'fa-circle-check text-lg': toast.type === 'success',
                'fa-triangle-exclamation text-lg': toast.type === 'warning',
                'fa-circle-xmark text-lg': toast.type === 'error'
            }"></i>
        </div>
        <div class="flex-1 pr-2">
            <h5 class="text-xs font-bold capitalize" x-text="toast.type === 'success' ? 'Berhasil Disimpan' : (toast.type === 'warning' ? 'Perhatian / Evaluasi' : 'Peringatan')"></h5>
            <p class="text-[11px] leading-relaxed mt-0.5 opacity-90" x-text="toast.message"></p>
        </div>
        <button type="button" @click="toast.show = false" class="text-slate-400 hover:text-white transition-colors cursor-pointer text-sm">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

</div>
@endsection

