@extends('layouts.dashboard')

@section('title', 'Logbook Aksi Perubahan - Diklat Kepemimpinan')
@section('header_title', 'Laboratorium Kepemimpinan (Aksi Perubahan)')

@section('content')
@php
    $isFailed = ($enrollment && $enrollment->status === 'failed');
@endphp

<div class="space-y-8">
    
    <!-- Top Banner Diklat Kepemimpinan -->
    <div class="bg-gradient-to-r from-amber-700 via-amber-800 to-pasuruan-900 rounded-2xl p-6 sm:p-8 text-white shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 text-amber-200 text-xs font-semibold backdrop-blur-xs">
                    <i class="fa-solid fa-award"></i> {{ $leadershipCourse ? $leadershipCourse->title : 'Pelatihan Kepemimpinan Pengawas (PKP) Angkatan IV 2026' }}
                </span>
                @if($isFailed)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-500/80 text-white text-xs font-bold shadow-xs">
                        <i class="fa-solid fa-ban"></i> Status: Tidak Lulus (Waktu Habis)
                    </span>
                @elseif($enrollment && $enrollment->status === 'passed')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500 text-white text-xs font-bold shadow-xs">
                        <i class="fa-solid fa-circle-check"></i> Status: Lulus & Bersertifikat
                    </span>
                @elseif($enrollment && $enrollment->deadline_at)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-400/20 text-amber-100 text-xs font-semibold border border-amber-300/30">
                        <i class="fa-regular fa-clock"></i> Batas Waktu: {{ $enrollment->deadline_at->isoFormat('D MMMM Y') }} (Sisa {{ $enrollment->remainingDays() }} Hari)
                    </span>
                @endif
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Logbook Mingguan & Capaian Milestone Aksi Perubahan
            </h2>
            <p class="text-xs sm:text-sm text-slate-200 max-w-2xl">
                Tahap 2: Off-Campus / Laboratorium Kepemimpinan. Unggah bukti dukung capaian milestone mingguan untuk dievaluasi oleh Coach dan disetujui Mentor. E-Sertifikat resmi terbit setelah aksi perubahan disetujui.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3 shrink-0">
            @if($enrollment && $enrollment->status === 'passed')
                <a href="{{ route('peserta.certificates') }}" class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-2">
                    <i class="fa-solid fa-certificate text-amber-200"></i>
                    <span>E-Sertifikat Kepemimpinan &rarr;</span>
                </a>
            @else
                <span class="px-4 py-2 bg-white/15 text-white font-bold text-xs rounded-xl border border-white/20">
                    Bobot: 72 JP
                </span>
            @endif
        </div>
    </div>

    @if($enrollment && $enrollment->status === 'passed')
        <div class="p-4 bg-emerald-50 border border-emerald-300 rounded-2xl flex items-center justify-between gap-4 text-emerald-900 shadow-2xs">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold">Selamat! Seluruh Milestone Aksi Perubahan Anda Telah Disetujui Coach & Mentor</h4>
                    <p class="text-[11px] text-emerald-700">Pelatihan Kepemimpinan Anda dinyatakan Lulus dan E-Sertifikat resmi ber-barcode validasi telah diterbitkan.</p>
                </div>
            </div>
            <a href="{{ route('peserta.certificates') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl transition-colors shrink-0 shadow-xs flex items-center gap-1.5">
                <i class="fa-solid fa-download text-amber-300"></i>
                <span>Lihat E-Sertifikat</span>
            </a>
        </div>
    @endif

    @if($isFailed)
        <!-- Alert Waktu Pengerjaan Habis & Status Tidak Lulus -->
        <div class="p-5 bg-rose-50 border-2 border-rose-300 rounded-2xl flex items-start gap-4 shadow-2xs">
            <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="space-y-1">
                <h4 class="text-sm font-black text-rose-900">Masa Pengerjaan Pelatihan Kepemimpinan Telah Berakhir</h4>
                <p class="text-xs text-rose-700 leading-relaxed">
                    Batas waktu pengerjaan aksi perubahan dan pengunggahan milestone telah melewati batas akhir ({{ $enrollment->deadline_at ? $enrollment->deadline_at->isoFormat('D MMMM Y, H:i') : '-' }} WIB). 
                    Sesuai ketentuan LMS, status Anda dinyatakan <strong>Tidak Lulus (Kadaluarsa)</strong> dan form penambahan logbook baru telah dinonaktifkan.
                </p>
            </div>
        </div>
    @endif

    <!-- Coach & Mentor Info Cards (PRD Section 5.3) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Coach (Widyaiswara) -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-700 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-user-tie"></i>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-sky-700 bg-sky-50 px-2 py-0.5 rounded border border-sky-200">
                    Coach (Widyaiswara Ahli)
                </span>
                <h4 class="text-sm font-bold text-slate-900 mt-1">{{ $coach->name ?? 'Dr. H. Ahmad Fauzi, M.Pd' }}</h4>
                <p class="text-xs text-slate-500">{{ $coach->profile->jabatan ?? 'Widyaiswara Ahli Utama BKPSDM Pasuruan' }}</p>
            </div>
        </div>

        <!-- Mentor (Atasan Langsung) -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-building-user"></i>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                    Mentor (Atasan Langsung)
                </span>
                <h4 class="text-sm font-bold text-slate-900 mt-1">{{ $mentor->name ?? 'Ir. Bambang Trihatmojo, M.M' }}</h4>
                <p class="text-xs text-slate-500">{{ $mentor->profile->jabatan ?? 'Kepala Bidang Bappeda Kabupaten Pasuruan' }}</p>
            </div>
        </div>
    </div>

    <!-- Form Tambah Logbook & Upload Bukti Milestone -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4" x-data="{ showForm: false }">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-base text-slate-900">Input Logbook Mingguan & Bukti Dukung</h3>
                <p class="text-xs text-slate-500">Unggah foto kegiatan, surat keputusan, atau draf regulasi milestone</p>
            </div>
            @if($isFailed)
                <span class="px-3.5 py-1.5 bg-rose-100 text-rose-700 font-bold text-xs rounded-xl flex items-center gap-1.5">
                    <i class="fa-solid fa-lock"></i> Pengerjaan Terkunci
                </span>
            @else
                <button type="button" @click="showForm = !showForm" class="px-4 py-2 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                    <i class="fa-solid" :class="showForm ? 'fa-xmark' : 'fa-plus'"></i>
                    <span x-text="showForm ? 'Tutup Form' : 'Tambah Logbook Baru'"></span>
                </button>
            @endif
        </div>

        @if($isFailed)
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600 flex items-center gap-2.5">
                <i class="fa-solid fa-ban text-rose-500 text-base"></i>
                <span>Pengisian logbook aksi perubahan dinonaktifkan karena telah melewati batas waktu pengerjaan.</span>
            </div>
        @else

        <form x-show="showForm" x-cloak action="{{ route('peserta.kepemimpinan.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 pt-2">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Minggu Ke- <span class="text-rose-500">*</span></label>
                    <input type="number" name="week_number" min="1" max="20" value="4" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tanggal Pelaksanaan <span class="text-rose-500">*</span></label>
                    <input type="date" name="activity_date" value="{{ date('Y-m-d') }}" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Judul Capaian Milestone <span class="text-rose-500">*</span></label>
                    <input type="text" name="milestone_title" placeholder="Contoh: Milestone 4: Sosialisasi Aplikasi..." required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Deskripsi Kegiatan & Progres Output <span class="text-rose-500">*</span></label>
                <textarea name="activity_description" rows="3" required placeholder="Jelaskan secara ringkas aktivitas aksi perubahan yang telah dilaksanakan pada minggu ini..." class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Unggah Dokumen Bukti Dukung (PDF / Gambar / ZIP)</label>
                <input type="file" name="evidence_file" accept=".pdf,.jpg,.jpeg,.png,.docx,.doc,.zip" class="w-full text-[11px] text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-pasuruan-100 file:text-pasuruan-700 hover:file:bg-pasuruan-200 cursor-pointer">
            </div>

            <div class="flex items-center justify-between pt-2">
                <div class="text-[11px] text-slate-400">
                    <i class="fa-solid fa-paperclip mr-1"></i> Berkas bukti dukung akan ditelaah oleh Coach & Mentor
                </div>
                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors">
                    Kirim untuk Ditinjau Coach & Mentor &rarr;
                </button>
            </div>
        </form>
        @endif
    </div>

    <!-- Timeline Riwayat Logbook & Bimbingan Bertingkat -->
    <div class="space-y-4">
        <h3 class="font-bold text-base text-slate-900">Riwayat Bimbingan & Validasi Milestone</h3>

        @foreach($logbooks as $item)
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-pasuruan-700 text-white flex items-center justify-center font-bold text-xs">
                            M{{ $item->week_number }}
                        </span>
                        <div>
                            <h4 class="font-bold text-sm text-slate-900">{{ $item->milestone_title }}</h4>
                            <p class="text-[11px] text-slate-400">
                                <i class="fa-regular fa-calendar mr-1"></i> {{ \Carbon\Carbon::parse($item->activity_date)->isoFormat('dddd, D MMMM Y') }}
                            </p>
                        </div>
                    </div>

                    <div>
                        @if($item->status === 'approved')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                <i class="fa-solid fa-circle-check text-emerald-600"></i> Disetujui (Approved)
                            </span>
                        @elseif($item->status === 'revision')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                <i class="fa-solid fa-circle-exclamation text-rose-600"></i> Perlu Revisi
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                <i class="fa-solid fa-clock text-amber-600"></i> Menunggu Telaah
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Deskripsi Kegiatan -->
                <p class="text-xs text-slate-600 leading-relaxed">
                    {{ $item->activity_description }}
                </p>

                <!-- Lampiran Bukti Output -->
                @if($item->evidence_file_name)
                    <div class="p-3 bg-slate-50 rounded-xl flex items-center justify-between text-xs">
                        <span class="text-slate-600 flex items-center gap-2 font-medium">
                            <i class="fa-solid fa-file-pdf text-rose-500 text-sm"></i>
                            <strong>Bukti Output:</strong> {{ $item->evidence_file_name }}
                        </span>
                        <a href="{{ asset('storage/evidence/' . $item->evidence_file_name) }}" target="_blank" class="text-xs text-pasuruan-700 font-bold hover:underline inline-flex items-center gap-1 bg-white px-3 py-1 rounded-lg border border-slate-200 shadow-2xs">
                            <i class="fa-solid fa-download text-slate-500"></i> Unduh Berkas
                        </a>
                    </div>
                @endif

                <!-- Catatan Bimbingan Bertingkat (Coach & Mentor) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <!-- Catatan Coach -->
                    <div class="p-3.5 rounded-xl bg-sky-50/70 border border-sky-200 text-xs space-y-1">
                        <div class="flex items-center justify-between text-[11px] font-bold text-sky-900">
                            <span>Catatan Coach (Widyaiswara):</span>
                            <i class="fa-solid fa-comment-dots text-sky-600"></i>
                        </div>
                        <p class="text-slate-700 italic">
                            "{{ $item->coach_feedback ?? 'Belum ada catatan dari Coach.' }}"
                        </p>
                    </div>

                    <!-- Catatan Mentor -->
                    <div class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200 text-xs space-y-1">
                        <div class="flex items-center justify-between text-[11px] font-bold text-emerald-900">
                            <span>Catatan Mentor (Atasan Langsung):</span>
                            <i class="fa-solid fa-signature text-emerald-600"></i>
                        </div>
                        <p class="text-slate-700 italic">
                            "{{ $item->mentor_feedback ?? 'Belum ada catatan dari Mentor.' }}"
                        </p>
                    </div>
                </div>

            </div>
        @endforeach
    </div>

</div>
@endsection
