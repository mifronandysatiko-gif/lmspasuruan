@extends('layouts.dashboard')

@section('title', $webinar->title . ' - Ruang Webinar')
@section('header_title', 'Alur Gerbang Berurutan (Sequential Gating) Webinar')

@section('content')
<div class="space-y-8" x-data="{
    // State
    attendance: {{ $attendance ? 'true' : 'false' }},
    attendanceTime: '{{ $attendance && $attendance->attended_at ? \Carbon\Carbon::parse($attendance->attended_at)->isoFormat('D MMMM Y, HH:mm:ss') . ' WIB' : '' }}',
    questionnaireCompleted: {{ $questionnaireCompleted ? 'true' : 'false' }},
    passed: {{ $passed ? 'true' : 'false' }},
    attemptCount: {{ $attemptCount }},
    canRemedial: {{ $canRemedial ? 'true' : 'false' }},
    isFailedPermanently: {{ $isFailedPermanently ? 'true' : 'false' }},
    maxRemedial: {{ $webinar->max_remedial ?? 5 }},
    remedialCap: {{ $webinar->remedial_cap_score ?? 90 }},
    passingGrade: {{ $webinar->passing_grade ?? 80 }},
    isLive: {{ $webinar->is_live ? 'true' : 'false' }},
    isQuestionnaireOpen: {{ $webinar->isQuestionnaireOpen() ? 'true' : 'false' }},
    isExamOpen: {{ $webinar->isExamOpen() ? 'true' : 'false' }},
    attempts: {{ Js::from($attempts->map(function($att) {
        return [
            'attempt_number' => $att->attempt_number,
            'raw_score' => $att->raw_score,
            'final_score' => $att->final_score,
            'is_passed' => (bool)$att->is_passed,
            'time' => \Carbon\Carbon::parse($att->created_at)->format('H:i') . ' WIB',
        ];
    })) }},
    certificate: {{ Js::from($certificate ? [
        'certificate_number' => $certificate->certificate_number,
        'training_title' => $certificate->training_title,
        'hash_token' => $certificate->hash_token,
        'validation_url' => route('validation.show', $certificate->hash_token),
        'download_url' => route('peserta.certificates'),
    ] : null) }},

    // UI state
    isSubmittingAttend: false,
    isSubmittingQuestionnaire: false,
    isSubmittingExam: false,
    shareModalOpen: false,
    shareCopied: false,
    toast: { show: false, message: '', type: 'success' },

    showToast(msg, type = 'success') {
        this.toast.message = msg;
        this.toast.type = type;
        this.toast.show = true;
        setTimeout(() => { this.toast.show = false; }, 6000);
    },

    copyShareLink() {
        navigator.clipboard.writeText('{{ route('peserta.webinar.room', $webinar->slug) }}');
        this.shareCopied = true;
        setTimeout(() => this.shareCopied = false, 3000);
    },

    async submitAttend(e) {
        this.isSubmittingAttend = true;
        try {
            const res = await fetch('{{ route('peserta.webinar.attend', $webinar->id) }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.attendance = true;
                this.attendanceTime = data.attended_at_formatted;
                this.showToast(data.message, 'success');
            } else {
                this.showToast(data.message || 'Gagal melakukan presensi kehadiran.', 'error');
            }
        } catch (err) {
            this.showToast('Terjadi gangguan jaringan saat melakukan presensi.', 'error');
        } finally {
            this.isSubmittingAttend = false;
        }
    },

    async submitQuestionnaire(e) {
        this.isSubmittingQuestionnaire = true;
        const formData = new FormData(e.target);
        try {
            const res = await fetch('{{ route('peserta.webinar.questionnaire', $webinar->id) }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.questionnaireCompleted = true;
                this.showToast(data.message, 'success');
            } else {
                this.showToast(data.message || 'Gagal mengirimkan kuesioner evaluasi.', 'error');
            }
        } catch (err) {
            this.showToast('Terjadi gangguan jaringan saat mengirim kuesioner.', 'error');
        } finally {
            this.isSubmittingQuestionnaire = false;
        }
    },

    async submitExam(e) {
        this.isSubmittingExam = true;
        const formData = new FormData(e.target);
        try {
            const res = await fetch('{{ route('peserta.webinar.exam_submit', $webinar->id) }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.attempts.push(data.attempt);
                this.attemptCount = data.attempt.attempt_number;
                this.passed = data.is_passed;
                this.canRemedial = data.can_remedial;
                this.isFailedPermanently = data.is_failed_permanently;
                if (data.certificate) {
                    this.certificate = data.certificate;
                }
                this.showToast(data.message, data.is_passed ? 'success' : 'warning');
            } else {
                this.showToast(data.message || 'Gagal mengirimkan jawaban evaluasi.', 'error');
            }
        } catch (err) {
            this.showToast('Terjadi gangguan jaringan saat mengirim evaluasi.', 'error');
        } finally {
            this.isSubmittingExam = false;
        }
    }
}">
    
    <!-- Navigasi Kembali ke Agenda Webinar -->
    <div class="flex items-center justify-between pb-1">
        <a href="{{ route('peserta.katalog', ['category' => 'webinar']) }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-pasuruan-800 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-2xs transition-colors">
            <i class="fa-solid fa-arrow-left text-pasuruan-700"></i>
            <span>Kembali ke Daftar Semua Agenda Webinar</span>
        </a>
        <div class="text-[11px] font-semibold text-slate-500">
            Status Sesi: <span class="font-bold {{ $webinar->is_live ? 'text-rose-600' : 'text-slate-700' }}">{{ $webinar->is_live ? '● Sedang LIVE' : 'Terjadwal' }}</span>
        </div>
    </div>

    <!-- Top Header Webinar with Event Background Banner -->
    <div class="relative overflow-hidden rounded-2xl p-6 sm:p-8 text-white shadow-lg bg-slate-900 bg-cover bg-center border border-slate-800"
         style="background-color: #071f33; {{ $webinar->banner_url ? 'background-image: url(\'' . $webinar->banner_url . '\');' : 'background-image: linear-gradient(135deg, #071f33 0%, #0b2e4b 60%, #030e17 100%);' }}">
        
        <!-- Dark Overlay Layer (Guarantees high contrast for white text under all conditions) -->
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-900/90 to-slate-950/85 backdrop-blur-[2px]"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="space-y-2.5 max-w-3xl">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wider {{ $webinar->is_live ? 'bg-rose-600 text-white animate-pulse shadow-xs' : 'bg-white/20 text-slate-100 backdrop-blur-xs' }}">
                        {{ $webinar->is_live ? '● Sesi Sedang Live' : 'Sesi Terjadwal (Menunggu Siaran)' }}
                    </span>
                    @if($webinar->youtube_embed_url)
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-rose-600 text-white flex items-center gap-1 shadow-xs">
                            <i class="fa-brands fa-youtube text-xs"></i> Live YouTube
                        </span>
                    @endif
                    <span class="text-xs text-sky-200 font-semibold bg-sky-950/60 px-2.5 py-0.5 rounded-md border border-sky-800/40">
                        <i class="fa-solid fa-building-columns mr-1 text-sky-400"></i> Penyelenggara: {{ $webinar->organizer->name }} • {{ $webinar->organizer->profile->unit_kerja ?? 'BKPSDM Kabupaten Pasuruan' }}
                    </span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black tracking-tight leading-snug text-white drop-shadow-md">{{ $webinar->title }}</h2>
                <p class="text-xs text-slate-200 flex flex-wrap items-center gap-3 font-medium drop-shadow-xs">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-calendar-day text-amber-400"></i> {{ \Carbon\Carbon::parse($webinar->event_date)->isoFormat('dddd, D MMMM Y') }}</span>
                    <span>•</span>
                    <span class="flex items-center gap-1"><i class="fa-solid fa-clock text-amber-400"></i> {{ substr($webinar->start_time, 0, 5) }} - {{ substr($webinar->end_time, 0, 5) }} WIB</span>
                    <span>•</span>
                    <span class="flex items-center gap-1"><i class="fa-solid fa-hourglass-half text-amber-400"></i> <strong>{{ $webinar->jp }} JP ({{ $webinar->jp * 45 }} Menit)</strong></span>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3 shrink-0 relative z-10">
                <button type="button" @click="shareModalOpen = true" class="px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer" title="Bagikan Tautan Webinar Ini ke Rekan ASN">
                    <i class="fa-solid fa-share-nodes"></i>
                    <span>Bagikan</span>
                </button>
                @if($webinar->youtube_link)
                    <a href="{{ $webinar->youtube_link }}" target="_blank" class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center gap-2">
                        <i class="fa-brands fa-youtube text-sm"></i> Buka YouTube
                    </a>
                @endif
                @if($webinar->meeting_link)
                    <a href="{{ $webinar->meeting_link }}" target="_blank" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center gap-2">
                        <i class="fa-solid fa-video"></i> Buka Zoom / Meet
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Siaran Langsung YouTube Live (Jika Tersedia) -->
    @if($webinar->youtube_embed_url)
    <div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden p-4 sm:p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
                </span>
                <div>
                    <h3 class="font-extrabold text-sm text-white flex items-center gap-2">
                        <i class="fa-brands fa-youtube text-rose-500 text-lg"></i>
                        <span>Siaran Langsung Webinar BKPSDM Pasuruan</span>
                    </h3>
                    <p class="text-[11px] text-slate-400">Ikuti pemaparan narasumber secara langsung melalui layar berikut</p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs">
                @if($webinar->is_live)
                    <span class="px-2.5 py-1 rounded bg-rose-500/20 text-rose-400 border border-rose-500/30 font-bold uppercase text-[10px]">
                        ON AIR
                    </span>
                @else
                    <span class="px-2.5 py-1 rounded bg-slate-800 text-slate-400 font-bold uppercase text-[10px]">
                        OFFLINE
                    </span>
                @endif
                <a href="{{ $webinar->youtube_link }}" target="_blank" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-bold transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> Buka di Tab Baru
                </a>
            </div>
        </div>

        <!-- 16:9 Responsive YouTube Player Container -->
        <div class="relative w-full aspect-video rounded-xl overflow-hidden shadow-2xl bg-black border border-slate-800">
            <iframe src="{{ $webinar->youtube_embed_url }}" 
                    title="{{ $webinar->title }}" 
                    class="w-full h-full absolute inset-0"
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                    referrerpolicy="strict-origin-when-cross-origin" 
                    allowfullscreen>
            </iframe>
        </div>
    </div>
    @endif

    <!-- 1. WIZARD STEP TRACKER: 5 TAHAP LINIER SESUAI PRD BAB 4.2 -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-6">
            <div>
                <h3 class="font-bold text-sm text-slate-900">Alur LMS Kabupaten Pasuruan</h3>
                <p class="text-xs text-slate-500">Sistem mengunci tahapan berikutnya sampai tahap saat ini tuntas dipenuhi.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
            
            <!-- Tahap 1: Pendaftaran -->
            <div class="p-3 rounded-xl border bg-emerald-50 border-emerald-300 text-center space-y-1">
                <div class="w-7 h-7 rounded-full bg-emerald-600 text-white flex items-center justify-center mx-auto text-xs font-bold shadow-xs">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div class="text-xs font-bold text-emerald-900">1. Pendaftaran</div>
                <div class="text-[10px] text-emerald-700 font-medium">
                    Terkonfirmasi @if(isset($registration) && $registration->registered_at) ({{ \Carbon\Carbon::parse($registration->registered_at)->isoFormat('D MMM, HH:mm') }}) @endif
                </div>
            </div>

            <!-- Tahap 2: Presensi Live -->
            <div class="p-3 rounded-xl border text-center space-y-1"
                 :class="attendance ? 'bg-emerald-50 border-emerald-300' : (isLive ? 'bg-sky-50 border-sky-400 ring-2 ring-sky-300' : 'bg-slate-50 border-slate-200')">
                <div class="w-7 h-7 rounded-full flex items-center justify-center mx-auto text-xs font-bold"
                     :class="attendance ? 'bg-emerald-600 text-white' : (isLive ? 'bg-sky-600 text-white animate-bounce' : 'bg-slate-300 text-slate-600')">
                    <i class="fa-solid" :class="attendance ? 'fa-check' : 'fa-clipboard-user'"></i>
                </div>
                <div class="text-xs font-bold" :class="attendance ? 'text-emerald-900' : 'text-slate-800'">2. Presensi Hadir</div>
                <div class="text-[10px]" :class="attendance ? 'text-emerald-700 font-medium' : (isLive ? 'text-sky-700 font-bold' : 'text-slate-400')"
                     x-text="attendance ? 'Hadir Tercatat' : (isLive ? 'Bisa Diisi Sekarang' : 'Tunggu Penyelenggara')">
                </div>
            </div>

            <!-- Tahap 3: Kuesioner -->
            <div class="p-3 rounded-xl border text-center space-y-1"
                 :class="questionnaireCompleted ? 'bg-emerald-50 border-emerald-300' : (attendance ? 'bg-purple-50 border-purple-300 ring-2 ring-purple-200' : 'bg-slate-50 border-slate-200 opacity-60')">
                <div class="w-7 h-7 rounded-full flex items-center justify-center mx-auto text-xs font-bold"
                     :class="questionnaireCompleted ? 'bg-emerald-600 text-white' : (attendance ? 'bg-purple-600 text-white' : 'bg-slate-300 text-slate-500')">
                    <i class="fa-solid" :class="questionnaireCompleted ? 'fa-check' : (attendance ? 'fa-star' : 'fa-lock')"></i>
                </div>
                <div class="text-xs font-bold" :class="questionnaireCompleted ? 'text-emerald-900' : 'text-slate-800'">3. Kuesioner</div>
                <div class="text-[10px]" :class="questionnaireCompleted ? 'text-emerald-700 font-medium' : (attendance ? 'text-purple-700 font-semibold' : 'text-slate-400')"
                     x-text="questionnaireCompleted ? 'Sudah Diisi' : (attendance ? 'Jendela Terbuka' : 'Terkunci (Isi Presensi)')">
                </div>
            </div>

            <!-- Tahap 4: Evaluasi & Remedial -->
            <div class="p-3 rounded-xl border text-center space-y-1"
                 :class="passed ? 'bg-emerald-50 border-emerald-300' : (questionnaireCompleted ? 'bg-amber-50 border-amber-400 ring-2 ring-amber-200' : 'bg-slate-50 border-slate-200 opacity-60')">
                <div class="w-7 h-7 rounded-full flex items-center justify-center mx-auto text-xs font-bold"
                     :class="passed ? 'bg-emerald-600 text-white' : (questionnaireCompleted ? 'bg-amber-500 text-white' : 'bg-slate-300 text-slate-500')">
                    <i class="fa-solid" :class="passed ? 'fa-check' : (questionnaireCompleted ? 'fa-file-pen' : 'fa-lock')"></i>
                </div>
                <div class="text-xs font-bold" :class="passed ? 'text-emerald-900' : 'text-slate-800'">4. Evaluasi & Remedial</div>
                <div class="text-[10px]" :class="passed ? 'text-emerald-700 font-bold' : (questionnaireCompleted ? 'text-amber-700' : 'text-slate-400')"
                     x-text="passed ? 'LULUS (Score Capped)' : (questionnaireCompleted ? 'Siap Dikerjakan' : 'Terkunci (Isi Kuesioner)')">
                </div>
            </div>

            <!-- Tahap 5: E-Sertifikat -->
            <div class="p-3 rounded-xl border text-center space-y-1"
                 :class="certificate ? 'bg-gradient-to-br from-amber-100 to-amber-200 border-amber-400 shadow-sm' : 'bg-slate-50 border-slate-200 opacity-60'">
                <div class="w-7 h-7 rounded-full flex items-center justify-center mx-auto text-xs font-bold"
                     :class="certificate ? 'bg-amber-600 text-white' : 'bg-slate-300 text-slate-500')">
                    <i class="fa-solid" :class="certificate ? 'fa-stamp' : 'fa-lock'"></i>
                </div>
                <div class="text-xs font-bold" :class="certificate ? 'text-amber-950' : 'text-slate-800'">5. E-Sertifikat</div>
                <div class="text-[10px]" :class="certificate ? 'text-amber-800 font-bold' : 'text-slate-400'"
                     x-text="certificate ? 'Terbit & Valid' : 'Terkunci (Lulus Ujian)'">
                </div>
            </div>

        </div>
    </div>

    <!-- 2. KONTEN MASING-MASING TAHAP GATING -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Area Interaktif Utama -->
        <div class="lg:col-span-8 space-y-6">

            <!-- TAHAP 2: PRESENSI KEHADIRAN -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-xs">2</div>
                        <div>
                            <h3 class="font-bold text-sm text-slate-900">Tahap 2: Presensi / Daftar Hadir Peserta</h3>
                            <p class="text-xs text-slate-500">Syarat: Tombol aktif HANYA setelah Penyelenggara menekan "Mulai Sesi"</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold" :class="attendance ? 'text-emerald-600' : (isLive ? 'text-sky-600' : 'text-slate-400')"
                          x-text="attendance ? 'Telah Hadir' : (isLive ? 'Siaran LIVE' : 'Belum Dibuka')">
                    </span>
                </div>

                <!-- Box Hadir Tercatat -->
                <div x-show="attendance" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-2xl"></i>
                        <div>
                            <div class="text-xs font-bold text-emerald-900">Kehadiran Anda Telah Terverifikasi</div>
                            <div class="text-[11px] text-emerald-700">Waktu presensi: <span x-text="attendanceTime"></span></div>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-emerald-600 text-white rounded-lg text-xs font-bold">Sah</span>
                </div>

                <!-- Box Siaran LIVE belum hadir -->
                <div x-show="!attendance && isLive" class="p-5 rounded-xl bg-sky-50 border border-sky-200 space-y-3">
                    <div class="flex items-center gap-2.5 text-sky-900 font-bold text-xs">
                        <i class="fa-solid fa-tower-broadcast text-rose-500 animate-pulse text-base"></i>
                        <span>Sesi Sedang Berlangsung (LIVE). Silakan lakukan konfirmasi kehadiran sekarang.</span>
                    </div>
                    <form @submit.prevent="submitAttend($event)">
                        <button type="submit" :disabled="isSubmittingAttend" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-2 cursor-pointer disabled:opacity-75">
                            <i class="fa-solid" :class="isSubmittingAttend ? 'fa-spinner fa-spin' : 'fa-clipboard-check'"></i>
                            <span x-text="isSubmittingAttend ? 'Mencatat Kehadiran...' : 'Konfirmasi Hadir Sekarang'"></span>
                        </button>
                    </form>
                </div>

                <!-- Box Belum LIVE -->
                <div x-show="!attendance && !isLive" class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center text-xs text-slate-500 space-y-2">
                    <i class="fa-solid fa-clock text-amber-500 text-xl"></i>
                    <p class="font-bold text-slate-800">Presensi Hadir Dibuka Saat Sesi Dimulai (LIVE)</p>
                    <p class="text-[11px] text-slate-500 max-w-md mx-auto leading-relaxed">
                        Status pendaftaran Anda <strong>Terkonfirmasi</strong>. Tombol konfirmasi kehadiran akan otomatis terbuka saat Penyelenggara memulai sesi siaran langsung (Status LIVE aktif).
                    </p>
                </div>
            </div>

            <!-- TAHAP 3: KUESIONER EVALUASI -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4"
                 :class="!attendance ? 'opacity-80 bg-slate-50/50' : ''">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-xs">3</div>
                        <div>
                            <h3 class="font-bold text-sm text-slate-900">Tahap 3: Pengisian Kuesioner Kepuasan Penyelenggaraan</h3>
                            <p class="text-xs text-slate-500">
                                Jendela waktu pengisian: <strong>{{ substr($webinar->questionnaire_start_time, 0, 5) }} - {{ substr($webinar->questionnaire_end_time, 0, 5) }} WIB</strong>
                            </p>
                        </div>
                    </div>
                    <span class="text-xs font-bold"
                          :class="questionnaireCompleted ? 'text-emerald-600' : (attendance ? 'text-purple-600' : 'text-slate-400')"
                          x-text="questionnaireCompleted ? 'Selesai' : (attendance ? 'Wajib Diisi' : 'Terkunci')">
                    </span>
                </div>

                <!-- Jika belum presensi (terkunci) -->
                <div x-show="!attendance" class="p-4 bg-amber-50/60 border border-amber-200 rounded-xl text-xs text-amber-900 flex items-center gap-3">
                    <i class="fa-solid fa-lock text-amber-500 text-lg shrink-0"></i>
                    <div>
                        <div class="font-bold">Tahap 3 Masih Terkunci</div>
                        <div class="text-[11px] text-amber-700">Silakan selesaikan <strong>Tahap 2: Presensi Hadir</strong> di atas terlebih dahulu untuk membuka formulir kuesioner kepuasan ini.</div>
                    </div>
                </div>

                <!-- Jika kuesioner sudah terisi -->
                <div x-show="attendance && questionnaireCompleted" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-2xl"></i>
                        <div>
                            <div class="text-xs font-bold text-emerald-900">Kuesioner Kepuasan Telah Terisi Lengkap</div>
                            <div class="text-[11px] text-emerald-700">Terima kasih atas ulasan Anda. <strong>Tahap 4: Kuis Evaluasi</strong> kini telah terbuka dan dapat dikerjakan.</div>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-emerald-600 text-white rounded-lg text-xs font-bold">Terisi</span>
                </div>

                <!-- Jika jendela belum buka / sudah lewat -->
                <div x-show="attendance && !questionnaireCompleted && !isQuestionnaireOpen" class="p-4 bg-amber-50/60 border border-amber-200 rounded-xl text-xs text-amber-900 flex items-center gap-3">
                    <i class="fa-solid fa-clock text-amber-600 text-lg shrink-0"></i>
                    <div>
                        <div class="font-bold">Jendela Pengisian Kuesioner Belum Terbuka / Telah Berakhir</div>
                        <div class="text-[11px] text-amber-700">
                            Jadwal kuesioner: <strong>{{ substr($webinar->questionnaire_start_time, 0, 5) }} - {{ substr($webinar->questionnaire_end_time, 0, 5) }} WIB</strong>.
                        </div>
                    </div>
                </div>

                <!-- Form Pengisian Kuesioner -->
                <div x-show="attendance && !questionnaireCompleted && isQuestionnaireOpen">
                    <form @submit.prevent="submitQuestionnaire($event)" class="p-5 bg-purple-50/40 border border-purple-200 rounded-xl space-y-4">
                        @if($webinar->questionnaireQuestions->isEmpty())
                            <div class="text-xs text-slate-500 py-2">
                                Belum ada pertanyaan kuesioner yang dikonfigurasi oleh penyelenggara. Klik tombol di bawah untuk melanjutkan langsung ke evaluasi.
                            </div>
                        @else
                            @foreach($webinar->questionnaireQuestions as $kIndex => $q)
                                <div class="p-3.5 bg-white rounded-xl border border-purple-100 space-y-2.5 text-xs">
                                    <label class="font-bold text-slate-900 block leading-relaxed">
                                        {{ $kIndex + 1 }}. {{ $q->question_text }} <span class="text-rose-500">*</span>
                                    </label>
                                    @if($q->isTextQuestion() || $q->input_type === 'text')
                                        <div class="pt-1">
                                            <textarea name="answers[{{ $q->id }}]" rows="3" required placeholder="Tuliskan ulasan, masukan, tanggapan, atau kritik & saran Anda di sini secara lengkap..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-purple-600/20 text-xs leading-relaxed"></textarea>
                                        </div>
                                    @else
                                        <div class="flex flex-wrap gap-3 pt-1">
                                            @if(is_array($q->options))
                                                @foreach($q->options as $opt)
                                                    <label class="flex items-center gap-1.5 text-slate-700 hover:text-purple-900 cursor-pointer bg-slate-50 hover:bg-purple-50 px-3 py-1.5 rounded-lg border border-slate-200 hover:border-purple-300 transition-colors">
                                                        <input type="radio" name="answers[{{ $q->id }}]" value="{{ $opt }}" required class="text-purple-700 focus:ring-purple-600">
                                                        <span class="text-xs">{{ $opt }}</span>
                                                    </label>
                                                @endforeach
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @endif

                        <div class="pt-2">
                            <button type="submit" :disabled="isSubmittingQuestionnaire" class="px-6 py-2.5 bg-purple-700 hover:bg-purple-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-2 cursor-pointer disabled:opacity-75">
                                <i class="fa-solid" :class="isSubmittingQuestionnaire ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                                <span x-text="isSubmittingQuestionnaire ? 'Mengirim Kuesioner...' : 'Kirim Kuesioner & Buka Evaluasi \u2192'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAHAP 4: EVALUASI / POST-TEST & ENGINE REMEDIAL (PRD 4.3) -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4"
                 :class="!questionnaireCompleted ? 'opacity-80 bg-slate-50/50' : ''">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xs">4</div>
                        <div>
                            <h3 class="font-bold text-sm text-slate-900">Tahap 4: Kuis Evaluasi & Remedial</h3>
                            <p class="text-xs text-slate-500">
                                Passing Grade: <strong>&ge; {{ $webinar->passing_grade }}</strong> • Maksimal Remedial: <strong>{{ $webinar->max_remedial }}x</strong> • Plafon Remedial: <strong>{{ $webinar->remedial_cap_score }}</strong>
                            </p>
                        </div>
                    </div>
                    <span class="text-xs font-bold"
                          :class="passed ? 'text-emerald-600' : (questionnaireCompleted ? 'text-amber-600' : 'text-slate-400')"
                          x-text="passed ? 'LULUS' : (questionnaireCompleted ? 'Siap Dikerjakan' : 'Terkunci')">
                    </span>
                </div>

                <!-- Jika Kuesioner belum selesai (terkunci) -->
                <div x-show="!questionnaireCompleted" class="p-4 bg-amber-50/60 border border-amber-200 rounded-xl text-xs text-amber-900 flex items-center gap-3">
                    <i class="fa-solid fa-lock text-amber-500 text-lg shrink-0"></i>
                    <div>
                        <div class="font-bold">Tahap 4 (Kuis Evaluasi) Masih Terkunci</div>
                        <div class="text-[11px] text-amber-700">Silakan lengkapi dan kirimkan <strong>Tahap 3: Kuesioner Kepuasan</strong> di atas terlebih dahulu untuk membuka soal evaluasi.</div>
                    </div>
                </div>

                <div x-show="questionnaireCompleted" class="space-y-4">
                    <!-- Riwayat Percobaan Ujian (Attempt History) -->
                    <template x-if="attempts.length > 0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-slate-50 text-slate-600 uppercase text-[10px] font-bold border-b border-slate-200">
                                    <tr>
                                        <th class="py-2.5 px-3">Percobaan</th>
                                        <th class="py-2.5 px-3">Skor</th>
                                        <th class="py-2.5 px-3">Skor Akhir</th>
                                        <th class="py-2.5 px-3">Status</th>
                                        <th class="py-2.5 px-3">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="(att, aIdx) in attempts" :key="aIdx">
                                        <tr :class="att.is_passed ? 'bg-emerald-50/50' : ''">
                                            <td class="py-2.5 px-3 font-bold">
                                                <span x-text="'Percobaan ke-' + att.attempt_number"></span>
                                                <template x-if="att.attempt_number > 1">
                                                    <span class="text-[10px] text-amber-600">(Remedial)</span>
                                                </template>
                                            </td>
                                            <td class="py-2.5 px-3 font-mono" x-text="att.raw_score"></td>
                                            <td class="py-2.5 px-3 font-mono font-bold"
                                                :class="att.is_passed ? 'text-emerald-600' : 'text-rose-600'">
                                                <span x-text="att.final_score"></span>
                                                <template x-if="att.attempt_number > 1 && att.raw_score > remedialCap">
                                                    <span class="text-[9px] text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded ml-1 font-sans" x-text="'Dibatasi Plafon ' + remedialCap"></span>
                                                </template>
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                                      :class="att.is_passed ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                                      x-text="att.is_passed ? 'LULUS' : 'REMEDIAL'"></span>
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-400" x-text="att.time"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>

                    <!-- Kotak Pengerjaan Ujian / Remedial -->
                    <template x-if="passed">
                        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-300 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-circle-check text-emerald-600 text-2xl"></i>
                                <div>
                                    <div class="text-xs font-bold text-emerald-900">Selamat! Anda Telah LULUS Ujian Evaluasi!</div>
                                    <div class="text-[11px] text-emerald-700">Skor akhir Anda telah memenuhi syarat penerbitan E-Sertifikat otomatis pada Tahap 5 di bawah.</div>
                                </div>
                            </div>
                            <span class="px-3 py-1 bg-emerald-600 text-white rounded-lg text-xs font-bold">Lulus</span>
                        </div>
                    </template>

                    <template x-if="!passed && isFailedPermanently">
                        <div class="p-4 rounded-xl bg-rose-50 border border-rose-300 text-rose-800 text-xs">
                            <i class="fa-solid fa-circle-xmark mr-1 text-rose-600"></i>
                            <strong>Gagal Permanen:</strong> Anda telah mencapai batas maksimal <span x-text="maxRemedial"></span> kali remedial. E-Sertifikat tidak dapat diterbitkan.
                        </div>
                    </template>

                    <template x-if="!passed && !isFailedPermanently && !isExamOpen">
                        <div class="p-4 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 text-xs flex items-center gap-3">
                            <i class="fa-solid fa-clock text-amber-600 text-xl shrink-0"></i>
                            <div>
                                <div class="font-bold">Jendela Pengerjaan Kuis Evaluasi Belum Dibuka / Telah Berakhir</div>
                                <div class="text-[11px] text-amber-700">
                                    Jadwal evaluasi / post-test: <strong>{{ substr($webinar->exam_start_time, 0, 5) }} - {{ substr($webinar->exam_end_time, 0, 5) }} WIB</strong>.
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="!passed && !isFailedPermanently && isExamOpen">
                        <!-- Form Pengerjaan Kuis Evaluasi Dinamis -->
                        <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-200/80 pb-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-md bg-amber-500 text-white flex items-center justify-center text-xs font-bold">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </span>
                                    <span class="text-xs font-bold text-slate-800"
                                          x-text="attemptCount === 0 ? 'Lembar Soal Kuis Evaluasi (Percobaan 1)' : 'Lembar Remedial (Percobaan ke-' + (attemptCount + 1) + ')'">
                                    </span>
                                </div>
                                <span class="text-[11px] text-amber-700 bg-amber-100 px-2.5 py-0.5 rounded-full font-bold"
                                      x-text="'Sisa Kesempatan: ' + (maxRemedial - attemptCount) + 'x'">
                                </span>
                            </div>

                            <form @submit.prevent="submitExam($event)" class="space-y-4">
                                @if($webinar->quizQuestions->isEmpty())
                                    <div class="p-4 bg-white rounded-xl border border-slate-200 text-xs text-slate-500 text-center">
                                        Penyelenggara belum menginputkan soal kuis evaluasi.
                                    </div>
                                @else
                                    @foreach($webinar->quizQuestions as $qIdx => $q)
                                        <div class="p-4 bg-white rounded-xl border border-slate-200/90 shadow-2xs space-y-3">
                                            <div class="flex items-start gap-2.5">
                                                <span class="w-6 h-6 rounded-md bg-pasuruan-100 text-pasuruan-800 font-bold text-xs flex items-center justify-center shrink-0">
                                                    {{ $qIdx + 1 }}
                                                </span>
                                                <p class="font-bold text-slate-900 text-xs leading-relaxed pt-0.5">
                                                    {{ $q->question_text }}
                                                </p>
                                            </div>

                                            <!-- Opsi Jawaban Radio A, B, C, D -->
                                            <div class="space-y-2 pl-8">
                                                @if(is_array($q->options))
                                                    @foreach($q->options as $optKey => $optVal)
                                                        <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-50 border border-transparent hover:border-slate-200 cursor-pointer text-xs transition-colors">
                                                            <input type="radio" name="answers[{{ $q->id }}]" value="{{ $optKey }}" required class="mt-0.5 text-pasuruan-700 focus:ring-pasuruan-600">
                                                            <span class="leading-relaxed">
                                                                <strong class="font-mono text-slate-800">{{ $optKey }}.</strong> {{ $optVal }}
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                                <div class="pt-2">
                                    <button type="submit" :disabled="isSubmittingExam" class="w-full py-3 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center justify-center gap-2 cursor-pointer disabled:opacity-75">
                                        <i class="fa-solid" :class="isSubmittingExam ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                                        <span x-text="isSubmittingExam ? 'Menyimpan & Memproses Nilai...' : 'Kirim Jawaban & Selesaikan Evaluasi (Percobaan ke-' + (attemptCount + 1) + ') \u2192'"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </template>
                </div>
            </div>

            <!-- TAHAP 5: PENERBITAN E-SERTIFIKAT (PRD BAB 6) -->
            <div class="bg-white p-6 rounded-2xl border-2 shadow-xs space-y-4"
                 :class="certificate ? 'border-amber-400 bg-gradient-to-br from-amber-50/20 to-white' : 'border-slate-200 opacity-60'">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center font-bold text-xs">5</div>
                        <div>
                            <h3 class="font-bold text-sm text-slate-900">Tahap 5: Penerbitan E-Sertifikat Digital</h3>
                            <p class="text-xs text-slate-500">Syarat: Presensi Valid + Kuesioner Terisi + Evaluasi LULUS</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold"
                          :class="certificate ? 'text-amber-700' : 'text-slate-400'"
                          x-text="certificate ? 'Siap Diunduh' : 'Belum Terbit'">
                    </span>
                </div>

                <template x-if="certificate">
                    <div class="p-5 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 text-white flex flex-col sm:flex-row items-center justify-between gap-6 shadow-md">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center text-3xl shadow-inner shrink-0">
                                <i class="fa-solid fa-stamp"></i>
                            </div>
                            <div class="space-y-1">
                                <span class="bg-black/20 text-amber-200 text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">
                                    Resmi & Terverifikasi
                                </span>
                                <h4 class="font-bold text-sm" x-text="certificate.training_title"></h4>
                                <p class="text-xs text-amber-100 font-mono" x-text="'No: ' + certificate.certificate_number"></p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto">
                            <a :href="certificate.validation_url" target="_blank" class="flex-1 sm:flex-none px-4 py-2.5 bg-white text-slate-800 font-bold text-xs rounded-xl shadow-xs hover:bg-slate-50 transition-colors flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-qrcode text-emerald-600"></i> Validasi Barcode
                            </a>
                            <a :href="certificate.download_url" class="flex-1 sm:flex-none px-4 py-2.5 bg-pasuruan-900 hover:bg-pasuruan-950 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-download text-amber-300"></i> Unduh PDF
                            </a>
                        </div>
                    </div>
                </template>

                <template x-if="!certificate">
                    <div class="p-4 bg-slate-50 rounded-xl text-xs text-slate-400 text-center">
                        <i class="fa-solid fa-lock mr-1"></i> E-Sertifikat akan terbit otomatis saat seluruh gerbang 1 s.d 4 berstatus valid.
                    </div>
                </template>
            </div>

        </div>

        <!-- Sidebar Informasi Regulasi & Parameter Pelaksanaan -->
        <div class="lg:col-span-4 space-y-6">
            
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
                <h4 class="font-bold text-sm text-slate-900 border-b border-slate-100 pb-2">
                    Regulasi Evaluasi & Remedial
                </h4>

                <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                    <div class="p-3 bg-slate-50 rounded-xl space-y-1">
                        <span class="font-bold text-slate-800 block">1. Nilai Percobaan Pertama</span>
                        <p class="text-[11px] text-slate-500">Skor mentah murni tanpa penyesuaian: Skor Akhir = Skor Mentah.</p>
                    </div>

                    <div class="p-3 bg-amber-50/60 border border-amber-200 rounded-xl space-y-1">
                        <span class="font-bold text-amber-900 block">2. Algoritma Remedial (Plafon {{ $webinar->remedial_cap_score ?? 90 }})</span>
                        <p class="text-[11px] text-amber-800">
                            Peserta yang remedial memiliki plafon nilai maksimal <strong>{{ $webinar->remedial_cap_score ?? 90 }}</strong>:
                            <br>
                            <code class="font-mono font-bold text-amber-950">Skor Akhir = min(Skor Mentah, {{ $webinar->remedial_cap_score ?? 90 }})</code>
                        </p>
                    </div>

                    <div class="p-3 bg-rose-50/60 border border-rose-200 rounded-xl space-y-1">
                        <span class="font-bold text-rose-900 block">3. Batas {{ $webinar->max_remedial ?? 5 }}x Remedial</span>
                        <p class="text-[11px] text-rose-800">Jika hingga percobaan ke-{{ $webinar->max_remedial ?? 5 }} belum mencapai batas kelulusan (&ge; {{ $webinar->passing_grade }}), status evaluasi terkunci permanen.</p>
                    </div>
                </div>
            </div>

            <!-- Panel Bantuan Teknis BKPSDM -->
            <div class="bg-gradient-to-br from-pasuruan-800 to-pasuruan-900 text-white p-6 rounded-2xl shadow-xs space-y-3">
                <div class="flex items-center gap-2 text-amber-300 font-bold text-xs">
                    <i class="fa-brands fa-whatsapp text-emerald-400 text-base"></i>
                    <span>Bantuan Helpdesk Sesi</span>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Mengalami kendala jaringan saat presensi atau evaluasi? Hubungi Tim Teknis BKPSDM Kabupaten Pasuruan di <strong>0812-3456-7890</strong>.
                </p>
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
                    $scheduleStr = ($webinar->event_date ? $webinar->event_date->isoFormat('D MMMM Y') : '-') . ' (' . ($webinar->start_time ? substr($webinar->start_time, 0, 5) : '08:30') . ' WIB)';
                    $rawMsg = "📢 *UNDANGAN WEBINAR BKPSDM KABUPATEN PASURUAN*\n\n" .
                              "📌 *Tema:* {$webinar->title}\n" .
                              "⏱ *Bobot:* {$webinar->jp} JP (" . ($webinar->jp * 45) . " Menit)\n" .
                              "📅 *Jadwal:* {$scheduleStr}\n\n" .
                              "🔗 *Tautan Pendaftaran & Akses:* " . route('peserta.webinar.room', $webinar->slug) . "\n\n" .
                              "_Catatan: Silakan login dengan akun ASN/Peserta Anda untuk mendaftar & presensi kehadiran._";
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

    <!-- Floating Toast Notification for Immediate Feedback Without Reloading -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:translate-x-4"
         x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-200 transform"
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
