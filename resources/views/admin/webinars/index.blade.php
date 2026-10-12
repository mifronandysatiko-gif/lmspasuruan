@extends('layouts.dashboard')

@section('title', 'Manajemen Webinar & Bimtek - LMS Pasuruan')
@section('header_title', 'Manajemen Event Webinar & Bimbingan Teknis')

@section('content')
<div class="space-y-6" x-data="{ 
    createModalOpen: false,
    editModalOpen: false,
    deleteModalOpen: false,
    shareModal: {
        open: false,
        title: '',
        jp: 4,
        schedule: '',
        url: '',
        rawMessage: '',
        whatsappUrl: '',
        copied: false
    },
    selectedWebinar: {
        id: null,
        title: '',
        description: '',
        jp: 4,
        event_date: '',
        start_time: '08:30',
        end_time: '12:00',
        meeting_link: '',
        youtube_link: '',
        questionnaire_start_time: '10:00',
        questionnaire_end_time: '12:30',
        exam_start_time: '10:30',
        exam_end_time: '14:00',
        passing_grade: 75,
        remedial_cap_score: 90,
        max_remedial: 5,
        certificate_number: '',
        certificate_start_number: '',
        certificate_format: '',
        certificate_template_id: '',
        share_message: '',
        update_url: ''
    },
    deleteWebinar: {
        id: null,
        title: '',
        delete_url: ''
    },
    openShare(wb) {
        let url = '{{ url('/peserta/webinar') }}/' + wb.slug;
        let schedule = (wb.event_date ? (typeof wb.event_date === 'string' ? wb.event_date.substring(0, 10) : wb.event_date) : '-') + ' (' + (wb.start_time ? wb.start_time.substring(0, 5) : '08:30') + ' WIB)';
        let desc = (wb.description || '').trim();
        let msg = `📢 *UNDANGAN WEBINAR BKPSDM KABUPATEN PASURUAN*\n\n` +
                  `📌 *Tema:* ${wb.title}\n` +
                  `⏱ *Bobot:* ${wb.jp} JP\n` +
                  `📅 *Jadwal:* ${schedule}\n`;
        if (desc) {
            msg += `\n📝 *Deskripsi & Ringkasan Materi:*\n${desc}\n`;
        }
        msg += `\n🔗 *Tautan Pendaftaran & Akses:* ${url}\n\n` +
               `_Catatan: Silakan login dengan akun ASN/Peserta Anda untuk mendaftar & presensi kehadiran._`;

        this.shareModal = {
            open: true,
            title: wb.title,
            jp: wb.jp,
            schedule: schedule,
            url: url,
            rawMessage: msg,
            whatsappUrl: 'https://api.whatsapp.com/send?text=' + encodeURIComponent(msg),
            copied: false
        };
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
    openEdit(wb) {
        this.selectedWebinar = {
            id: wb.id,
            title: wb.title,
            description: wb.description || '',
            jp: wb.jp,
            event_date: wb.event_date ? (typeof wb.event_date === 'string' ? wb.event_date.substring(0, 10) : '') : '',
            start_time: wb.start_time ? wb.start_time.substring(0, 5) : '08:30',
            end_time: wb.end_time ? wb.end_time.substring(0, 5) : '12:00',
            meeting_link: wb.meeting_link || '',
            youtube_link: wb.youtube_link || '',
            questionnaire_start_time: wb.questionnaire_start_time ? wb.questionnaire_start_time.substring(0, 5) : '10:00',
            questionnaire_end_time: wb.questionnaire_end_time ? wb.questionnaire_end_time.substring(0, 5) : '12:30',
            exam_start_time: wb.exam_start_time ? wb.exam_start_time.substring(0, 5) : '10:30',
            exam_end_time: wb.exam_end_time ? wb.exam_end_time.substring(0, 5) : '14:00',
            passing_grade: wb.passing_grade || 75,
            remedial_cap_score: wb.remedial_cap_score || 90,
            max_remedial: wb.max_remedial || 5,
            organizer_id: wb.organizer_id || '',
            certificate_number: wb.certificate_number || '',
            certificate_start_number: wb.certificate_start_number || '',
            certificate_format: wb.certificate_format || '',
            certificate_template_id: wb.certificate_template_id || '',
            share_message: wb.share_message || '',
            update_url: '{{ url('/admin/webinars') }}/' + wb.id + '/update'
        };
        this.editModalOpen = true;
        this.$nextTick(() => {
            document.querySelectorAll('#editModal .timepicker-24h').forEach(el => {
                if (el._flatpickr && el.value) {
                    el._flatpickr.setDate(el.value, false, 'H:i');
                }
            });
        });
    },
    openDelete(wb) {
        this.deleteWebinar = {
            id: wb.id,
            title: wb.title,
            delete_url: '{{ url('/admin/webinars') }}/' + wb.id + '/delete'
        };
        this.deleteModalOpen = true;
    }
}">
    
    <!-- Top Bar -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900">Daftar Sesi Webinar & Bimtek Terbuka</h2>
            <p class="text-xs text-slate-500">Kelola sesi, buat tautan sebaran kegiatan, unduh tarikan data Excel, picu status LIVE dan kelola bank soal</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <a href="{{ route('admin.webinars.export') }}" class="px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-2" title="Unduh Rekap Seluruh Agenda Webinar ke Format Excel (.xlsx / .csv)">
                <i class="fa-solid fa-file-excel text-emerald-200"></i>
                <span>Tarikan Data Excel (Semua Webinar)</span>
            </a>
            <button type="button" @click="createModalOpen = true" class="px-4 py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-2">
                <i class="fa-solid fa-plus-circle text-amber-300"></i>
                <span>Buat Sesi Webinar Baru</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Total Webinar & Bimtek</span>
                <div class="w-8 h-8 rounded-lg bg-pasuruan-50 text-pasuruan-700 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-tower-broadcast"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 mt-2">{{ $webinars->count() }}</div>
            <p class="text-[11px] text-slate-400 mt-0.5">Sesi Terdaftar</p>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Sesi Sedang LIVE</span>
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-circle-dot animate-pulse"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-rose-600 mt-2">{{ $webinars->where('is_live', true)->count() }}</div>
            <p class="text-[11px] text-rose-500 mt-0.5">Presensi Terbuka</p>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Total Kehadiran</span>
                <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-clipboard-user"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-sky-700 mt-2">{{ $webinars->sum('attendances_count') }}</div>
            <p class="text-[11px] text-sky-600 mt-0.5">Peserta Tercatat</p>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Akumulasi Jam (JP)</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-amber-700 mt-2">{{ $webinars->sum('jp') }} JP</div>
            <p class="text-[11px] text-amber-600 mt-0.5">Pengembangan Kompetensi</p>
        </div>
    </div>

    <!-- Table of Webinars -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-600 uppercase text-[10px] font-bold border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Judul Event & Penyelenggara</th>
                        <th class="py-3 px-4">Jadwal & Jam JP</th>
                        <th class="py-3 px-4">Status Siaran</th>
                        <th class="py-3 px-4">Jendela Waktu (Kuesioner / Ujian)</th>
                        <th class="py-3 px-4">Peserta Hadir</th>
                        <th class="py-3 px-4 text-center">Aksi Kontrol</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($webinars as $wb)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-4">
                                <h4 class="font-bold text-xs text-slate-900">{{ $wb->title }}</h4>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    {{ $wb->organizer->name }} • {{ $wb->organizer->profile->unit_kerja ?? 'BKPSDM' }}
                                </p>
                                @if($wb->certificate_number)
                                    <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 text-[10px] font-mono font-bold" title="Nomor Surat Tetap">
                                        <i class="fa-solid fa-stamp text-emerald-600"></i> {{ $wb->certificate_number }}
                                    </span>
                                @else
                                    <span class="inline-block mt-1 text-[10px] text-slate-400 italic">No. Surat: Otomatis (Unik)</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-medium text-slate-800">{{ \Carbon\Carbon::parse($wb->event_date)->isoFormat('D MMM Y') }}</div>
                                <div class="text-[11px] text-amber-600 font-bold mt-0.5">{{ $wb->jp }} JP ({{ $wb->jp * 45 }} Mnt)</div>
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($wb->is_live)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300 animate-pulse">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span> LIVE AKTIF
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-300">
                                        OFFLINE
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-[11px] text-slate-600 whitespace-nowrap">
                                <div>Kuesioner: <strong>{{ substr($wb->questionnaire_start_time, 0, 5) }} - {{ substr($wb->questionnaire_end_time, 0, 5) }}</strong></div>
                                <div>Ujian: <strong>{{ substr($wb->exam_start_time, 0, 5) }} - {{ substr($wb->exam_end_time, 0, 5) }}</strong></div>
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-md bg-sky-50 text-sky-800 font-bold font-mono text-xs border border-sky-200">
                                    {{ $wb->attendances_count }} Orang
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" @click="openShare({{ json_encode($wb) }})" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-sky-600 hover:bg-sky-700 text-white font-bold rounded-lg text-xs transition-colors shadow-2xs cursor-pointer" title="Buat & Salin Link Sebaran Kegiatan">
                                        <i class="fa-solid fa-share-nodes"></i>
                                        <span>Bagikan</span>
                                    </button>
                                    <a href="{{ route('admin.webinars.export_participants', $wb->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition-colors shadow-2xs" title="Unduh Data Presensi & Nilai Peserta Webinar ke Excel">
                                        <i class="fa-solid fa-file-excel text-emerald-200"></i>
                                        <span>Excel</span>
                                    </a>
                                    <a href="{{ route('admin.webinars.manage', $wb->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold rounded-lg text-xs transition-colors shadow-2xs" title="Panel Kontrol Sesi & Bank Soal">
                                        <i class="fa-solid fa-sliders text-amber-400"></i>
                                        <span>Kontrol</span>
                                    </a>
                                    <button type="button" @click="openEdit({{ json_encode($wb) }})" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-lg text-xs transition-colors shadow-2xs" title="Ubah / Perbaiki Informasi Webinar">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        <span>Edit</span>
                                    </button>
                                    <button type="button" @click="openDelete({{ json_encode($wb) }})" class="inline-flex items-center gap-1 px-2 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-xs transition-colors shadow-2xs" title="Hapus Sesi Webinar">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                Belum ada agenda webinar atau bimtek yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL: BUAT SESI WEBINAR BARU -->
    <div x-show="createModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-2xl w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2 text-pasuruan-800 font-bold text-sm">
                    <div class="w-8 h-8 rounded-lg bg-pasuruan-100 flex items-center justify-center text-pasuruan-700">
                        <i class="fa-solid fa-tower-broadcast"></i>
                    </div>
                    <span>Buat Sesi Webinar & Bimtek Baru</span>
                </div>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('admin.webinars.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
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
                            <option value="{{ $org->id }}" {{ (auth()->id() == $org->id) ? 'selected' : '' }}>
                                {{ $org->name }} — {{ $org->profile->unit_kerja ?? 'BKPSDM Kabupaten Pasuruan' }} ({{ $org->role === 'super_admin' ? 'Super Admin' : 'Penyelenggara Diklat' }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400">Pilih akun Penyelenggara / PIC kegiatan ini. Data penyelenggara dan bidang ini diambil dari Profil Pengguna.</p>
                </div>

                <!-- Judul Event -->
                <div class="space-y-1">
                    <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px]">
                        Judul Webinar / Bimbingan Teknis <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" required placeholder="Contoh: Bimbingan Teknis Manajemen ASN & Netralitas Pemilu 2026" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs">
                </div>

                <!-- Deskripsi -->
                <div class="space-y-1">
                    <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px]">
                        Deskripsi & Ringkasan Materi <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="description" rows="3" required placeholder="Jelaskan tujuan dan cakupan materi webinar..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs leading-relaxed"></textarea>
                </div>

                <!-- Baris Jadwal: Tanggal, JP, Jam Mulai & Jam Selesai -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1">Tanggal Event <span class="text-rose-500">*</span></label>
                        <input type="date" name="event_date" required value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1">Bobot JP <span class="text-rose-500">*</span></label>
                        <input type="number" name="jp" required min="1" max="50" value="4" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1 flex items-center justify-between">
                            <span>Jam Mulai (24 Jam) <span class="text-rose-500">*</span></span>
                            <i class="fa-regular fa-clock text-pasuruan-700 text-[10px]"></i>
                        </label>
                        <input type="text" name="start_time" x-timepicker24 required value="08:30" placeholder="08:30" class="timepicker-24h w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1 flex items-center justify-between">
                            <span>Jam Selesai (24 Jam) <span class="text-rose-500">*</span></span>
                            <i class="fa-regular fa-clock text-pasuruan-700 text-[10px]"></i>
                        </label>
                        <input type="text" name="end_time" x-timepicker24 required value="12:00" placeholder="12:00" class="timepicker-24h w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                    </div>
                </div>

                <!-- Media & Siaran: YouTube Live & Zoom/Meet -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <i class="fa-brands fa-youtube text-rose-600"></i>
                            <span>Link Live YouTube (Opsional)</span>
                        </label>
                        <input type="url" name="youtube_link" placeholder="https://www.youtube.com/watch?v=... atau https://youtu.be/..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs">
                        <p class="text-[10px] text-slate-400">Siaran langsung YouTube akan otomatis tayang di ruang peserta.</p>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <i class="fa-solid fa-video text-blue-600"></i>
                            <span>Tautan Zoom / Meet (Opsional)</span>
                        </label>
                        <input type="url" name="meeting_link" placeholder="https://zoom.us/j/9876543210?pwd=..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs">
                        <p class="text-[10px] text-slate-400">Link ruang telekonferensi alternatif.</p>
                    </div>
                </div>

                <!-- Background Acara / Banner Webinar -->
                <div class="space-y-1 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                        <i class="fa-regular fa-image text-emerald-600"></i>
                        <span>Background Acara / Banner Webinar (Opsional)</span>
                    </label>
                    <input type="file" name="banner" accept="image/png,image/jpeg,image/jpg,image/webp" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                    <p class="text-[10px] text-slate-400">Format: JPG, PNG, WebP (Maks. 5 MB). Gambar akan menjadi latar ruang sesi dan kartu katalog.</p>
                </div>

                <!-- Pengaturan Jendela Waktu Gating -->
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
                                <input type="text" name="questionnaire_start_time" x-timepicker24 value="10:00" placeholder="10:00" class="timepicker-24h px-2.5 py-1.5 bg-white border border-purple-300 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                                <input type="text" name="questionnaire_end_time" x-timepicker24 value="12:30" placeholder="12:30" class="timepicker-24h px-2.5 py-1.5 bg-white border border-purple-300 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <span class="text-[11px] font-bold text-amber-800">Jendela Evaluasi / Post-Test (Buka - Tutup)</span>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" name="exam_start_time" x-timepicker24 value="10:30" placeholder="10:30" class="timepicker-24h px-2.5 py-1.5 bg-white border border-amber-300 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                                <input type="text" name="exam_end_time" x-timepicker24 value="14:00" placeholder="14:00" class="timepicker-24h px-2.5 py-1.5 bg-white border border-amber-300 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Parameter Evaluasi & Remedial PRD -->
                <div class="grid grid-cols-3 gap-3 pt-1">
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1">Passing Grade</label>
                        <input type="number" name="passing_grade" value="75" min="10" max="100" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1">Plafon Remedial</label>
                        <input type="number" name="remedial_cap_score" value="90" min="10" max="100" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-bold text-amber-700">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1">Maksimal Remedial</label>
                        <input type="number" name="max_remedial" value="5" min="1" max="10" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono">
                    </div>
                </div>

                <!-- Pengaturan Nomor Surat E-Sertifikat -->
                <div class="space-y-3 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                    <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                        <i class="fa-solid fa-stamp text-amber-600"></i>
                        <span>Pengaturan Penomoran E-Sertifikat</span>
                    </label>

                    <div class="space-y-1">
                        <label class="font-bold text-slate-700 text-xs flex items-center justify-between">
                            <span>Pilihan Template E-Sertifikat</span>
                            <span class="text-[10px] text-slate-400 font-normal">Opsional</span>
                        </label>
                        <select name="certificate_template_id" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-semibold focus:outline-none focus:border-pasuruan-500">
                            <option value="">-- Gunakan Template Default Sistem --</option>
                            @if(isset($templates))
                                @foreach($templates as $tpl)
                                    <option value="{{ $tpl->id }}">{{ $tpl->name }} ({{ $tpl->target_badge }})</option>
                                @endforeach
                            @endif
                        </select>
                        <p class="text-[9px] text-slate-400">Pilih template khusus jika webinar ini menggunakan blanko/desain sertifikat tersendiri.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="font-bold text-slate-700 text-xs">Nomor Urut Awal (Start Number)</label>
                            <input type="number" min="1" name="certificate_start_number" placeholder="Contoh: 1 atau 101" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono font-bold focus:outline-none focus:border-pasuruan-500">
                            <p class="text-[9px] text-slate-400">Nomor sertifikat urut peserta pertama dimulai dari angka ini.</p>
                        </div>
                        <div class="space-y-1">
                            <label class="font-bold text-slate-700 text-xs">Pola Format Nomor (Opsional)</label>
                            <input type="text" name="certificate_format" placeholder="893.3/{NOMOR}/BKPSDM-PAS/{TAHUN}" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono focus:outline-none focus:border-pasuruan-500">
                            <p class="text-[9px] text-slate-400">Kosongkan jika menggunakan format default dinas.</p>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-slate-700 text-xs">Nomor SK / Surat Statis Seragam (Opsional)</label>
                        <input type="text" name="certificate_number" placeholder="Contoh: 893.3/120/BKPSDM-PAS/2026" class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono focus:outline-none focus:border-pasuruan-500">
                        <p class="text-[10px] text-slate-500 leading-relaxed">
                            Jika diisi, seluruh peserta webinar ini menerima nomor surat yang sama persis. Jika dikosongkan, penomoran urut otomatis diterapkan.
                        </p>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-check"></i>
                        <span>Simpan & Buka Panel Kontrol</span>
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: UBAH / EDIT INFORMASI WEBINAR -->
    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-2xl w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2 text-amber-700 font-bold text-sm">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <span>Ubah / Perbaiki Informasi Sesi Webinar</span>
                </div>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form :action="selectedWebinar.update_url" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
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
                    <select name="organizer_id" x-model="selectedWebinar.organizer_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs font-medium">
                        @foreach($organizers as $org)
                            <option value="{{ $org->id }}">
                                {{ $org->name }} — {{ $org->profile->unit_kerja ?? 'BKPSDM Kabupaten Pasuruan' }} ({{ $org->role === 'super_admin' ? 'Super Admin' : 'Penyelenggara Diklat' }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400">Pilih akun Penyelenggara / PIC kegiatan ini. Data penyelenggara dan bidang ini diambil dari Profil Pengguna.</p>
                </div>

                <!-- Judul Event -->
                <div class="space-y-1">
                    <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px]">
                        Judul Webinar / Bimbingan Teknis <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" x-model="selectedWebinar.title" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs">
                </div>

                <!-- Deskripsi -->
                <div class="space-y-1">
                    <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px]">
                        Deskripsi & Ringkasan Materi <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="description" x-model="selectedWebinar.description" rows="3" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs leading-relaxed"></textarea>
                </div>

                <!-- Baris Jadwal: Tanggal, JP, Jam Mulai & Jam Selesai -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1">Tanggal Event <span class="text-rose-500">*</span></label>
                        <input type="date" name="event_date" x-model="selectedWebinar.event_date" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1">Bobot JP <span class="text-rose-500">*</span></label>
                        <input type="number" name="jp" x-model="selectedWebinar.jp" required min="1" max="50" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1 flex items-center justify-between">
                            <span>Jam Mulai (24 Jam) <span class="text-rose-500">*</span></span>
                            <i class="fa-regular fa-clock text-pasuruan-700 text-[10px]"></i>
                        </label>
                        <input type="text" name="start_time" x-timepicker24 x-model="selectedWebinar.start_time" required placeholder="08:30" class="timepicker-24h w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1 flex items-center justify-between">
                            <span>Jam Selesai (24 Jam) <span class="text-rose-500">*</span></span>
                            <i class="fa-regular fa-clock text-pasuruan-700 text-[10px]"></i>
                        </label>
                        <input type="text" name="end_time" x-timepicker24 x-model="selectedWebinar.end_time" required placeholder="12:00" class="timepicker-24h w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                    </div>
                </div>

                <!-- Media & Siaran: YouTube Live & Zoom/Meet -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <i class="fa-brands fa-youtube text-rose-600"></i>
                            <span>Link Live YouTube</span>
                        </label>
                        <input type="url" name="youtube_link" x-model="selectedWebinar.youtube_link" placeholder="https://www.youtube.com/watch?v=..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs">
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <i class="fa-solid fa-video text-blue-600"></i>
                            <span>Tautan Zoom / Meet</span>
                        </label>
                        <input type="url" name="meeting_link" x-model="selectedWebinar.meeting_link" placeholder="https://zoom.us/j/..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-pasuruan-700/20 text-xs">
                    </div>
                </div>

                <!-- Background Acara / Banner Webinar -->
                <div class="space-y-1 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                        <i class="fa-regular fa-image text-emerald-600"></i>
                        <span>Unggah Background Acara Baru (Opsional)</span>
                    </label>
                    <input type="file" name="banner" accept="image/png,image/jpeg,image/jpg,image/webp" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                    <p class="text-[10px] text-slate-400">Biarkan kosong jika tidak ingin mengubah background acara saat ini.</p>
                </div>

                <!-- Pengaturan Jendela Waktu Gating -->
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
                                <input type="text" name="questionnaire_start_time" x-timepicker24 x-model="selectedWebinar.questionnaire_start_time" placeholder="10:00" class="timepicker-24h px-2.5 py-1.5 bg-white border border-purple-300 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                                <input type="text" name="questionnaire_end_time" x-timepicker24 x-model="selectedWebinar.questionnaire_end_time" placeholder="12:30" class="timepicker-24h px-2.5 py-1.5 bg-white border border-purple-300 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <span class="text-[11px] font-bold text-amber-800">Jendela Evaluasi / Post-Test (Buka - Tutup)</span>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" name="exam_start_time" x-timepicker24 x-model="selectedWebinar.exam_start_time" placeholder="10:30" class="timepicker-24h px-2.5 py-1.5 bg-white border border-amber-300 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                                <input type="text" name="exam_end_time" x-timepicker24 x-model="selectedWebinar.exam_end_time" placeholder="14:00" class="timepicker-24h px-2.5 py-1.5 bg-white border border-amber-300 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Parameter Evaluasi & Remedial PRD -->
                <div class="grid grid-cols-3 gap-3 pt-1">
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1">Passing Grade</label>
                        <input type="number" name="passing_grade" x-model="selectedWebinar.passing_grade" min="10" max="100" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1">Plafon Remedial</label>
                        <input type="number" name="remedial_cap_score" x-model="selectedWebinar.remedial_cap_score" min="10" max="100" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono font-bold text-amber-700">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1">Maksimal Remedial</label>
                        <input type="number" name="max_remedial" x-model="selectedWebinar.max_remedial" min="1" max="10" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono">
                    </div>
                </div>

                <!-- Pengaturan Nomor Surat E-Sertifikat -->
                <div class="space-y-3 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                    <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                        <i class="fa-solid fa-stamp text-amber-600"></i>
                        <span>Pengaturan Penomoran E-Sertifikat</span>
                    </label>

                    <div class="space-y-1">
                        <label class="font-bold text-slate-700 text-xs flex items-center justify-between">
                            <span>Pilihan Template E-Sertifikat</span>
                            <span class="text-[10px] text-slate-400 font-normal">Opsional</span>
                        </label>
                        <select name="certificate_template_id" x-model="selectedWebinar.certificate_template_id" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-semibold focus:outline-none focus:border-pasuruan-500">
                            <option value="">-- Gunakan Template Default Sistem --</option>
                            @if(isset($templates))
                                @foreach($templates as $tpl)
                                    <option value="{{ $tpl->id }}">{{ $tpl->name }} ({{ $tpl->target_badge }})</option>
                                @endforeach
                            @endif
                        </select>
                        <p class="text-[9px] text-slate-400">Pilih template khusus jika webinar ini menggunakan blanko/desain sertifikat tersendiri.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="font-bold text-slate-700 text-xs">Nomor Urut Awal (Start Number)</label>
                            <input type="number" min="1" name="certificate_start_number" x-model="selectedWebinar.certificate_start_number" placeholder="Contoh: 1 atau 101" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono font-bold focus:outline-none focus:border-pasuruan-500">
                            <p class="text-[9px] text-slate-400">Nomor sertifikat urut peserta pertama dimulai dari angka ini.</p>
                        </div>
                        <div class="space-y-1">
                            <label class="font-bold text-slate-700 text-xs">Pola Format Nomor (Opsional)</label>
                            <input type="text" name="certificate_format" x-model="selectedWebinar.certificate_format" placeholder="893.3/{NOMOR}/BKPSDM-PAS/{TAHUN}" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono focus:outline-none focus:border-pasuruan-500">
                            <p class="text-[9px] text-slate-400">Kosongkan jika menggunakan format default dinas.</p>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-slate-700 text-xs">Nomor SK / Surat Statis Seragam (Opsional)</label>
                        <input type="text" name="certificate_number" x-model="selectedWebinar.certificate_number" placeholder="Contoh: 893.3/120/BKPSDM-PAS/2026" class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono focus:outline-none focus:border-pasuruan-500">
                        <p class="text-[10px] text-slate-500 leading-relaxed">
                            Jika diisi, seluruh peserta webinar ini menerima nomor surat yang sama persis. Jika dikosongkan, penomoran urut otomatis diterapkan.
                        </p>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
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

    <!-- MODAL 3: KONFIRMASI HAPUS WEBINAR -->
    <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
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
                    <p class="font-extrabold text-slate-900" x-text="deleteWebinar.title"></p>
                    <p class="text-[10px] text-slate-400 pt-1">Seluruh data presensi, riwayat pengerjaan kuis, dan instrumen soal pada sesi ini akan turut terhapus secara otomatis.</p>
                </div>

                <form :action="deleteWebinar.delete_url" method="POST" class="pt-2 flex items-center justify-end gap-2 text-xs">
                    @csrf
                    <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 font-bold rounded-xl transition-colors">
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

    <!-- MODAL 4: BAGIKAN TAUTAN WEBINAR -->
    <div x-show="shareModal.open" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5 text-sky-800 font-bold text-sm">
                        <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center">
                            <i class="fa-solid fa-share-nodes"></i>
                        </div>
                        <span>Bagikan Tautan Sesi Webinar</span>
                    </div>
                    <button type="button" @click="shareModal.open = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Event Quick Info -->
                <div class="p-3.5 bg-sky-50/70 border border-sky-200 rounded-xl space-y-1">
                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-sky-200 text-sky-900" x-text="shareModal.jp + ' JP (' + (shareModal.jp * 45) + ' Menit)'"></span>
                    <h4 class="font-extrabold text-xs text-slate-900" x-text="shareModal.title"></h4>
                    <p class="text-[11px] text-slate-600 flex items-center gap-1.5 pt-0.5">
                        <i class="fa-regular fa-calendar text-sky-600"></i>
                        <span x-text="shareModal.schedule"></span>
                    </p>
                </div>

                <!-- Direct URL Input with Copy Button -->
                <div class="space-y-1.5">
                    <label class="font-bold text-xs text-slate-700 block">Tautan Langsung (Direct URL Peserta):</label>
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
                    <textarea readonly rows="4" :value="shareModal.rawMessage" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-600 leading-relaxed resize-none focus:outline-none select-all"></textarea>
                </div>

                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-[11px] flex items-start gap-2">
                    <i class="fa-solid fa-circle-info text-amber-600 text-xs mt-0.5"></i>
                    <span>Tautan ini aman disebarkan ke grup OPD/WhatsApp ASN. Peserta yang belum login akan otomatis diarahkan untuk masuk terlebih dahulu.</span>
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
