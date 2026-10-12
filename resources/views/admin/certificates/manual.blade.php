@extends('layouts.dashboard')

@section('title', 'Penerbitan Sertifikat Manual - Super Admin LMS Pasuruan')
@section('header_title', 'Penerbitan Sertifikat Manual')

@section('content')
<div class="space-y-8" x-data="certificateManualApp()">
    
    <!-- 1. Header Banner & Quick Stats -->
    <div class="bg-gradient-to-r from-pasuruan-900 via-pasuruan-800 to-amber-900 rounded-3xl p-6 sm:p-8 text-white shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="space-y-2 max-w-2xl">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 text-amber-300 text-xs font-semibold backdrop-blur-xs border border-white/10">
                <i class="fa-solid fa-stamp text-amber-400"></i> Otoritas Khusus Super Admin
            </span>
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Penerbitan Sertifikat Manual Pegawai
            </h2>
            <p class="text-xs sm:text-sm text-slate-200 leading-relaxed">
                Buat dan terbitkan e-sertifikat resmi ber-barcode untuk pegawai pilihan sesuai sesi webinar atau pelatihan yang telah diselenggarakan, lengkap dengan penentuan <strong>tanggal sertifikat manual</strong>.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3 shrink-0">
            <a href="{{ route('admin.certificates.designer') }}" class="px-4 py-2.5 bg-white/15 hover:bg-white/20 text-white font-semibold text-xs rounded-xl border border-white/20 transition-all flex items-center gap-2">
                <i class="fa-solid fa-compass-drafting text-purple-300"></i> Desainer Template
            </a>
            <a href="{{ route('admin.certificates.export') }}" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow-xs transition-all flex items-center gap-2">
                <i class="fa-solid fa-file-excel"></i> Ekspor Excel
            </a>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-stamp"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900">{{ number_format($totalCertificates) }}</div>
                <div class="text-xs text-slate-500 font-medium">Total Sertifikat Terbit</div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-tower-broadcast"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900">{{ number_format($totalWebinars) }}</div>
                <div class="text-xs text-slate-500 font-medium">Sesi Webinar Tersedia</div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900">{{ number_format($totalCourses) }}</div>
                <div class="text-xs text-slate-500 font-medium">Sesi Pelatihan / MOOC</div>
            </div>
        </div>
    </div>


    @if(isset($errors) && $errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs space-y-1 shadow-xs">
            <strong class="font-bold flex items-center gap-2 text-rose-950">
                <i class="fa-solid fa-circle-xmark text-rose-600"></i> Mohon lengkapi formulir dengan benar:
            </strong>
            <ul class="list-disc list-inside ml-2 space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 2. Formulir Penerbitan Sertifikat Baru (Card Utama) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-pasuruan-700 text-white flex items-center justify-center text-xs">
                        <i class="fa-solid fa-plus"></i>
                    </span>
                    <span>Formulir Penerbitan E-Sertifikat Baru</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Pilih kegiatan, tentukan tanggal secara manual, dan pilih pegawai yang menerima sertifikat.</p>
            </div>

            <!-- Tab Tipe Kegiatan -->
            <div class="inline-flex p-1 rounded-xl bg-slate-200/80 text-xs font-bold shrink-0">
                <button type="button" @click="setTrainingType('webinar')"
                        :class="trainingType === 'webinar' ? 'bg-white text-pasuruan-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="px-4 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-tower-broadcast text-rose-500"></i> Sesi Webinar
                </button>
                <button type="button" @click="setTrainingType('course')"
                        :class="trainingType === 'course' ? 'bg-white text-pasuruan-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="px-4 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-graduation-cap text-sky-600"></i> Pelatihan / MOOC
                </button>
            </div>
        </div>

        <form action="{{ route('admin.certificates.manual_store') }}" method="POST" class="p-6 sm:p-8 space-y-6 text-xs">
            @csrf
            <input type="hidden" name="training_type" :value="trainingType">

            <!-- LANGKAH 1: PILIH SESI KEGIATAN -->
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-pasuruan-800 text-white flex items-center justify-center text-[10px] font-bold">1</span>
                    <label class="font-extrabold text-slate-800 uppercase tracking-wider text-xs">
                        Pilih Sesi Kegiatan Yang Sudah Ada <span class="text-rose-500">*</span>
                    </label>
                </div>

                <!-- Dropdown Webinar -->
                <div x-show="trainingType === 'webinar'" class="space-y-2">
                    <select name="webinar_id" x-model="selectedWebinarId" @change="onSelectWebinar()"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-pasuruan-700">
                        <option value="">-- Pilih Sesi Webinar --</option>
                        @foreach($webinars as $wb)
                            <option value="{{ $wb->id }}" data-jp="{{ $wb->jp }}" data-template="{{ $wb->certificate_template_id }}" data-date="{{ $wb->event_date?->format('Y-m-d') }}">
                                [WEBINAR] {{ $wb->title }} ({{ $wb->jp }} JP • {{ $wb->event_date?->isoFormat('D MMM Y') ?? 'Tanggal belum diset' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Dropdown Course -->
                <div x-show="trainingType === 'course'" class="space-y-2">
                    <select name="course_id" x-model="selectedCourseId" @change="onSelectCourse()"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-pasuruan-700">
                        <option value="">-- Pilih Sesi Pelatihan / MOOC --</option>
                        @foreach($courses as $cs)
                            <option value="{{ $cs->id }}" data-jp="{{ $cs->jp }}" data-template="{{ $cs->certificate_template_id }}">
                                [PELATIHAN - {{ strtoupper($cs->type) }}] {{ $cs->title }} ({{ $cs->jp }} JP)
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- LANGKAH 2: TANGGAL MANUAL & PARAMETER SERTIFIKAT (PERMINTAAN USER) -->
            <div class="p-5 rounded-2xl bg-amber-50/70 border border-amber-200 space-y-4">
                <div class="flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-amber-600 text-white flex items-center justify-center text-[10px] font-bold">2</span>
                    <h4 class="font-extrabold text-amber-950 uppercase tracking-wider text-xs">
                        Pengaturan Sertifikat & Tanggal Manual
                    </h4>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Tanggal Sertifikat Manual -->
                    <div>
                        <label class="block font-bold text-slate-800 uppercase tracking-wider mb-1.5 text-[11px]">
                            Tanggal Sertifikat (Manual) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="date" name="issued_at" x-model="issuedAt" required
                                   class="w-full px-3.5 py-2.5 bg-white border border-amber-300 rounded-xl text-xs text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500 shadow-xs">
                        </div>
                        <p class="text-[10px] text-amber-800 mt-1">Tanggal ini tercetak pada blanko e-sertifikat & QR code.</p>
                    </div>

                    <!-- Jam Pelajaran (JP) -->
                    <div>
                        <label class="block font-bold text-slate-800 uppercase tracking-wider mb-1.5 text-[11px]">
                            Jam Pelajaran (JP) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="jp" x-model="jp" min="1" max="500" required
                               class="w-full px-3.5 py-2.5 bg-white border border-amber-300 rounded-xl text-xs text-slate-900 font-bold focus:outline-none focus:ring-2 focus:ring-amber-500 shadow-xs">
                        <p class="text-[10px] text-slate-500 mt-1">Otomatis terisi sesuai kegiatan, dapat disesuaikan.</p>
                    </div>

                    <!-- Template Sertifikat -->
                    <div>
                        <label class="block font-bold text-slate-800 uppercase tracking-wider mb-1.5 text-[11px]">
                            Desain Blanko Template
                        </label>
                        <select name="template_id" x-model="selectedTemplateId"
                                class="w-full px-3.5 py-2.5 bg-white border border-amber-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 shadow-xs">
                            <option value="">-- Ikuti Template Bawaan Sesi --</option>
                            @foreach($templates as $tmpl)
                                <option value="{{ $tmpl->id }}">
                                    {{ $tmpl->name }} {{ $tmpl->is_default ? '(Default)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-500 mt-1">Pilih blanko spesifik jika diperlukan.</p>
                    </div>
                </div>

                <!-- Custom Certificate Number (Optional) -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1 text-[11px]">
                        Nomor Sertifikat Khusus (Opsional)
                    </label>
                    <input type="text" name="custom_certificate_number" placeholder="Kosongkan untuk penomoran otomatis sekuensial (Contoh: 893.3/0045/BKPSDM-PAS/2026)"
                           class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 font-mono focus:outline-none focus:ring-2 focus:ring-pasuruan-700">
                    <p class="text-[10px] text-slate-500 mt-1">Jika diisi, nomor ini akan dipakai untuk penerbitan tunggal (1 pegawai). Jika menerbitkan untuk banyak pegawai, sistem akan men-generate nomor urut otomatis.</p>
                </div>
            </div>

            <!-- LANGKAH 3: PILIH PEGAWAI / PESERTA -->
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="w-5 h-5 rounded-full bg-pasuruan-800 text-white flex items-center justify-center text-[10px] font-bold">3</span>
                        <label class="font-extrabold text-slate-800 uppercase tracking-wider text-xs">
                            Pilih Pegawai / Peserta Penerima Sertifikat <span class="text-rose-500">*</span>
                        </label>
                    </div>

                    <!-- Mode Seleksi Pegawai -->
                    <div class="inline-flex p-1 rounded-xl bg-slate-100 text-[11px] font-bold shrink-0">
                        <button type="button" @click="selectionMode = 'registered'"
                                :class="selectionMode === 'registered' ? 'bg-white text-pasuruan-800 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                                class="px-3 py-1 rounded-lg transition-all cursor-pointer">
                            <i class="fa-solid fa-list-check mr-1"></i> Peserta Terdaftar di Sesi
                        </button>
                        <button type="button" @click="selectionMode = 'manual_search'"
                                :class="selectionMode === 'manual_search' ? 'bg-white text-pasuruan-800 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                                class="px-3 py-1 rounded-lg transition-all cursor-pointer">
                            <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari dari Master Pegawai
                        </button>
                    </div>
                </div>

                <!-- MODE A: DAFTAR PESERTA TERDAFTAR DI SESI -->
                <div x-show="selectionMode === 'registered'" class="space-y-3">
                    <div class="flex items-center justify-between gap-2 p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <span class="text-slate-600 font-medium">
                            Peserta terdaftar: <strong class="text-slate-900" x-text="sessionParticipants.length + ' orang'"></strong>
                        </span>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="selectAllSessionParticipants()" class="px-3 py-1 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-[11px] transition-colors cursor-pointer">
                                Pilih Semua
                            </button>
                            <button type="button" @click="selectUncertifiedOnly()" class="px-3 py-1 rounded-lg bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-bold text-[11px] transition-colors cursor-pointer">
                                Pilih yang Belum Bersertifikat
                            </button>
                            <button type="button" @click="clearSessionSelection()" class="px-3 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-[11px] transition-colors cursor-pointer">
                                Hapus Pilihan
                            </button>
                        </div>
                    </div>

                    <!-- List Peserta Sesi -->
                    <div class="max-h-60 overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-100 bg-white">
                        <template x-if="sessionParticipants.length === 0">
                            <div class="p-8 text-center text-slate-400">
                                <p>Pilih sesi kegiatan di Langkah 1 untuk memuat daftar peserta terdaftar, atau beralih ke tab <strong>Cari dari Master Pegawai</strong>.</p>
                            </div>
                        </template>

                        <template x-for="p in sessionParticipants" :key="p.id">
                            <label class="p-3 flex items-center justify-between gap-3 hover:bg-slate-50 cursor-pointer transition-colors">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" :value="p.id" @change="toggleUser(p)" :checked="isUserSelected(p.id)"
                                           class="rounded border-slate-300 text-pasuruan-700 focus:ring-pasuruan-700 w-4 h-4">
                                    <div>
                                        <div class="font-bold text-slate-900 text-xs" x-text="p.name"></div>
                                        <div class="text-[11px] text-slate-500 font-mono">
                                            <span x-text="'NIP: ' + p.nip"></span> • <span x-text="p.unit_kerja"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="shrink-0">
                                    <template x-if="p.has_certificate">
                                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px] flex items-center gap-1">
                                            <i class="fa-solid fa-circle-check"></i> Sudah Bersertifikat (<span x-text="p.certificate_number"></span>)
                                        </span>
                                    </template>
                                    <template x-if="!p.has_certificate">
                                        <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-bold text-[10px]">
                                            Belum Ada Sertifikat
                                        </span>
                                    </template>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                <!-- MODE B: CARI MANUAL DARI 16.000+ MASTER PEGAWAI -->
                <div x-show="selectionMode === 'manual_search'" class="space-y-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <input type="text" x-model="searchQuery" @input.debounce.300ms="searchUsers()"
                               placeholder="Ketik Nama Pegawai, NIP, atau Instansi Unit Kerja (Ketik min. 2 huruf)..."
                               class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pasuruan-700">
                    </div>

                    <!-- Dropdown Hasil Pencarian -->
                    <div x-show="searchResults.length > 0" class="max-h-56 overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-100 bg-white shadow-md">
                        <template x-for="u in searchResults" :key="u.id">
                            <div class="p-3 flex items-center justify-between gap-3 hover:bg-slate-50 transition-colors">
                                <div>
                                    <div class="font-bold text-slate-900 text-xs" x-text="u.name"></div>
                                    <div class="text-[11px] text-slate-500">
                                        <span class="font-mono" x-text="'NIP: ' + u.nip"></span> • <span x-text="u.unit_kerja"></span>
                                    </div>
                                </div>
                                <button type="button" @click="addUser(u)"
                                        :disabled="isUserSelected(u.id)"
                                        :class="isUserSelected(u.id) ? 'bg-slate-200 text-slate-500 cursor-not-allowed' : 'bg-pasuruan-700 hover:bg-pasuruan-800 text-white cursor-pointer'"
                                        class="px-3 py-1.5 rounded-lg font-bold text-xs transition-colors shrink-0">
                                    <span x-text="isUserSelected(u.id) ? 'Sudah Dipilih' : '+ Tambahkan'"></span>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- DAFTAR PEGAWAI TERPILIH (CHIPS/TAGS) -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-800 text-xs">
                            Total Pegawai Yang Dipilih: <strong class="text-pasuruan-700 font-extrabold" x-text="selectedUsers.length + ' Orang'"></strong>
                        </span>
                        <button type="button" @click="clearAllSelected()" x-show="selectedUsers.length > 0" class="text-rose-600 hover:underline text-[11px] font-semibold">
                            Hapus Semua Pilihan
                        </button>
                    </div>

                    <div class="flex flex-wrap gap-2 max-h-40 overflow-y-auto p-1">
                        <template x-if="selectedUsers.length === 0">
                            <span class="text-slate-400 italic text-[11px]">Belum ada pegawai yang dipilih. Silakan pilih peserta di atas.</span>
                        </template>

                        <template x-for="u in selectedUsers" :key="u.id">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-white border border-slate-300 text-slate-800 shadow-xs text-[11px]">
                                <span class="font-bold" x-text="u.name"></span>
                                <span class="text-slate-500 font-mono text-[10px]" x-text="'(' + u.nip + ')'"></span>
                                <button type="button" @click="removeUser(u.id)" class="text-slate-400 hover:text-rose-600 cursor-pointer ml-1">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                                <!-- Hidden input for form submission -->
                                <input type="hidden" name="user_ids[]" :value="u.id">
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- SUBMIT BUTTON -->
            <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
                <button type="submit" 
                        :disabled="selectedUsers.length === 0 || (!selectedWebinarId && !selectedCourseId)"
                        :class="selectedUsers.length === 0 || (!selectedWebinarId && !selectedCourseId) ? 'bg-slate-300 cursor-not-allowed text-slate-500' : 'bg-pasuruan-800 hover:bg-pasuruan-900 text-white cursor-pointer shadow-md hover:shadow-lg'"
                        class="px-6 py-3 rounded-xl font-bold text-xs transition-all flex items-center gap-2">
                    <i class="fa-solid fa-stamp text-amber-300"></i>
                    <span>Terbitkan E-Sertifikat Sekarang (<span x-text="selectedUsers.length"></span> Pegawai)</span>
                </button>
            </div>
        </form>
    </div>

    <!-- 3. Daftar Riwayat E-Sertifikat Terbit -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden space-y-4 p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-certificate text-amber-500"></i>
                    <span>Daftar Riwayat E-Sertifikat Terbit</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Memuat seluruh sertifikat yang telah diterbitkan baik secara otomatis maupun manual.</p>
            </div>

            <!-- Search Form -->
            <form action="{{ route('admin.certificates.manual') }}" method="GET" class="flex items-center gap-2 w-full sm:w-auto">
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, NIP, no sertifikat..."
                       class="px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pasuruan-700 w-full sm:w-64">
                <button type="submit" class="px-4 py-2 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-xl transition-colors shrink-0">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                @if($search)
                    <a href="{{ route('admin.certificates.manual') }}" class="p-2 text-slate-400 hover:text-slate-600 text-xs">Reset</a>
                @endif
            </form>
        </div>

        <!-- Tabel Sertifikat -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">No. Sertifikat & QR</th>
                        <th class="py-3 px-4">Pegawai / Peserta</th>
                        <th class="py-3 px-4">Kegiatan Diklat</th>
                        <th class="py-3 px-4">Tanggal Sertifikat</th>
                        <th class="py-3 px-4">JP</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($certificates as $cert)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4">
                                <div class="font-mono font-bold text-pasuruan-800">{{ $cert->certificate_number }}</div>
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $cert->hash_token }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ $cert->nama_lengkap }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">NIP. {{ $cert->nip ?: '-' }}</div>
                                <div class="text-[10.5px] text-slate-400 line-clamp-1">{{ $cert->unit_kerja }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-1.5 mb-0.5">
                                    @if($cert->webinar_id)
                                        <span class="px-1.5 py-0.5 rounded bg-rose-50 text-rose-600 font-bold text-[9px] border border-rose-200">WEBINAR</span>
                                    @else
                                        <span class="px-1.5 py-0.5 rounded bg-sky-50 text-sky-600 font-bold text-[9px] border border-sky-200">PELATIHAN</span>
                                    @endif
                                </div>
                                <div class="font-bold text-slate-800 line-clamp-1">{{ $cert->training_title }}</div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-bold text-slate-800">
                                    {{ \Carbon\Carbon::parse($cert->issued_at)->isoFormat('D MMMM Y') }}
                                </span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-900 font-bold text-[11px]">
                                    {{ $cert->jp }} JP
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('admin.certificates.print', $cert->id) }}" target="_blank"
                                       class="px-2.5 py-1 rounded-lg bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs transition-colors shadow-xs"
                                       title="Cetak / Unduh PDF Dokumen E-Sertifikat">
                                        <i class="fa-solid fa-file-pdf mr-1"></i> Unduh PDF
                                    </a>
                                    <a href="{{ route('validation.show', $cert->hash_token) }}" target="_blank"
                                       class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs transition-colors border border-emerald-200"
                                       title="Lihat Validasi Dokumen & QR">
                                        <i class="fa-solid fa-qrcode mr-1"></i> Validasi
                                    </a>
                                    <form action="{{ route('admin.certificates.manual_destroy', $cert->id) }}" method="POST"
                                          onsubmit="return confirm('Apakah Anda yakin ingin membatalkan dan menghapus sertifikat nomor {{ $cert->certificate_number }} atas nama {{ $cert->nama_lengkap }}?');"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 text-xs transition-colors" title="Hapus / Batalkan Sertifikat">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                Belum ada sertifikat yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pt-4 border-t border-slate-100">
            {{ $certificates->links() }}
        </div>
    </div>

</div>

<script>
function certificateManualApp() {
    return {
        trainingType: 'webinar',
        selectedWebinarId: '',
        selectedCourseId: '',
        issuedAt: '{{ date('Y-m-d') }}',
        jp: 2,
        selectedTemplateId: '',
        selectionMode: 'registered',
        sessionParticipants: [],
        searchQuery: '',
        searchResults: [],
        selectedUsers: [],

        setTrainingType(type) {
            this.trainingType = type;
            this.sessionParticipants = [];
            if (type === 'webinar') {
                this.selectedCourseId = '';
                if (this.selectedWebinarId) this.onSelectWebinar();
            } else {
                this.selectedWebinarId = '';
                if (this.selectedCourseId) this.onSelectCourse();
            }
        },

        onSelectWebinar() {
            if (!this.selectedWebinarId) {
                this.sessionParticipants = [];
                return;
            }
            const selectEl = document.querySelector('select[name="webinar_id"]');
            const selectedOpt = selectEl.options[selectEl.selectedIndex];
            if (selectedOpt) {
                if (selectedOpt.dataset.jp) this.jp = selectedOpt.dataset.jp;
                if (selectedOpt.dataset.template) this.selectedTemplateId = selectedOpt.dataset.template;
                if (selectedOpt.dataset.date) this.issuedAt = selectedOpt.dataset.date;
            }
            this.loadParticipants('webinar', this.selectedWebinarId);
        },

        onSelectCourse() {
            if (!this.selectedCourseId) {
                this.sessionParticipants = [];
                return;
            }
            const selectEl = document.querySelector('select[name="course_id"]');
            const selectedOpt = selectEl.options[selectEl.selectedIndex];
            if (selectedOpt) {
                if (selectedOpt.dataset.jp) this.jp = selectedOpt.dataset.jp;
                if (selectedOpt.dataset.template) this.selectedTemplateId = selectedOpt.dataset.template;
            }
            this.loadParticipants('course', this.selectedCourseId);
        },

        loadParticipants(type, id) {
            fetch(`{{ route('admin.certificates.manual_participants') }}?type=${type}&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    this.sessionParticipants = data.participants || [];
                })
                .catch(err => {
                    console.error('Gagal memuat peserta:', err);
                });
        },

        searchUsers() {
            if (this.searchQuery.trim().length < 2) {
                this.searchResults = [];
                return;
            }
            fetch(`{{ route('admin.certificates.manual_search_users') }}?q=${encodeURIComponent(this.searchQuery.trim())}`)
                .then(res => res.json())
                .then(data => {
                    this.searchResults = data || [];
                })
                .catch(err => {
                    console.error('Gagal mencari pegawai:', err);
                });
        },

        isUserSelected(id) {
            return this.selectedUsers.some(u => u.id == id);
        },

        addUser(user) {
            if (!this.isUserSelected(user.id)) {
                this.selectedUsers.push({
                    id: user.id,
                    name: user.name,
                    nip: user.nip || '-'
                });
            }
        },

        removeUser(id) {
            this.selectedUsers = this.selectedUsers.filter(u => u.id != id);
        },

        toggleUser(p) {
            if (this.isUserSelected(p.id)) {
                this.removeUser(p.id);
            } else {
                this.addUser(p);
            }
        },

        selectAllSessionParticipants() {
            this.sessionParticipants.forEach(p => {
                this.addUser(p);
            });
        },

        selectUncertifiedOnly() {
            this.sessionParticipants.forEach(p => {
                if (!p.has_certificate) {
                    this.addUser(p);
                }
            });
        },

        clearSessionSelection() {
            const sessionIds = this.sessionParticipants.map(p => p.id);
            this.selectedUsers = this.selectedUsers.filter(u => !sessionIds.includes(u.id));
        },

        clearAllSelected() {
            this.selectedUsers = [];
        }
    };
}
</script>
@endsection
