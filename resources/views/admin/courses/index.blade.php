@extends('layouts.dashboard')

@section('title', 'Manajemen Pelatihan MOOC & Modul Pembelajaran - BKPSDM Pasuruan')
@section('header_title', 'Manajemen Pelatihan MOOC & Pembelajaran Kedinasan')

@section('content')
<div class="space-y-6" x-data="{ 
    createModalOpen: false, 
    editModalOpen: false,
    deleteModalOpen: false,
    filterType: 'all', 
    searchQuery: '',
    shareModal: {
        open: false,
        title: '',
        category: '',
        type: '',
        jp: 20,
        passing_grade: 80,
        url: '',
        rawMessage: '',
        whatsappUrl: '',
        copied: false
    },
    selectedCourse: {
        id: null,
        title: '',
        category: '',
        type: 'mooc',
        jp: 20,
        passing_grade: 80,
        max_remedial: 5,
        remedial_cap_score: 90,
        description: '',
        time_limit_type: 'none',
        duration_days: null,
        start_date: '',
        end_date: '',
        certificate_number: '',
        certificate_start_number: '',
        certificate_format: '',
        certificate_lampiran_url: '',
        certificate_template_id: '',
        share_message: '',
        update_url: ''
    },
    deleteCourse: {
        id: null,
        title: '',
        delete_url: ''
    },
    openShare(course) {
        let url = '{{ url('/peserta/mooc') }}/' + course.slug;
        let typeStr = (course.type || 'mooc').toUpperCase().replace('_', ' ');
        let desc = (course.description || '').trim();
        let msg = `📢 *PELATIHAN MANDIRI (${typeStr}) BKPSDM KABUPATEN PASURUAN*\n\n` +
                  `📌 *Program:* ${course.title}\n` +
                  `📚 *Rumpun / Kategori:* ${course.category}\n` +
                  `🏷 *Tipe Program:* ${typeStr}\n` +
                  `⏱ *Bobot:* ${course.jp} JP\n` +
                  `🎯 *Passing Grade Kelulusan:* ≥ ${course.passing_grade} (Remedial: Plafon ${course.remedial_cap_score || 90}, Maks. ${course.max_remedial || 5}x)\n`;
        if (desc) {
            msg += `\n📝 *Deskripsi & Ringkasan Materi:*\n${desc}\n`;
        }
        msg += `\n🔗 *Tautan Akses Ruang Belajar:* ${url}\n\n` +
               `_Catatan: Khusus ASN Pemerintah Kabupaten Pasuruan. Silakan login untuk memulai materi pembelajaran._`;

        this.shareModal = {
            open: true,
            title: course.title,
            category: course.category,
            type: typeStr,
            jp: course.jp,
            passing_grade: course.passing_grade,
            remedial_cap_score: course.remedial_cap_score || 90,
            max_remedial: course.max_remedial || 5,
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
    openEdit(course) {
        this.selectedCourse = {
            id: course.id,
            organizer_id: course.organizer_id || '',
            title: course.title,
            category: course.category,
            type: course.type,
            jp: course.jp,
            passing_grade: course.passing_grade,
            max_remedial: course.max_remedial || 5,
            remedial_cap_score: course.remedial_cap_score || 90,
            description: course.description || '',
            time_limit_type: course.time_limit_type || 'none',
            duration_days: course.duration_days || null,
            start_date: course.start_date ? (typeof course.start_date === 'string' ? course.start_date.replace('T', ' ').substring(0, 16) : '') : '',
            end_date: course.end_date ? (typeof course.end_date === 'string' ? course.end_date.replace('T', ' ').substring(0, 16) : '') : '',
            certificate_number: course.certificate_number || '',
            certificate_start_number: course.certificate_start_number || '',
            certificate_format: course.certificate_format || '',
            certificate_lampiran_url: course.certificate_lampiran_url || '',
            certificate_template_id: course.certificate_template_id || '',
            share_message: course.share_message || '',
            update_url: '{{ url('/admin/courses') }}/' + course.id + '/update'
        };
        this.editModalOpen = true;
        this.$nextTick(() => {
            document.querySelectorAll('#editModal .datetimepicker-24h').forEach(el => {
                if (el._flatpickr && el.value) {
                    el._flatpickr.setDate(el.value, false, 'Y-m-d H:i');
                }
            });
        });
    },
    openDelete(course) {
        this.deleteCourse = {
            id: course.id,
            title: course.title,
            delete_url: '{{ url('/admin/courses') }}/' + course.id + '/delete'
        };
        this.deleteModalOpen = true;
    }
}">

    <!-- Top Action & Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900">Katalog Pelatihan & Sesi Materi</h2>
            <p class="text-xs text-slate-500">Kelola kurikulum, buat tautan sebaran kegiatan, unduh tarikan data Excel, unggah modul (anti-skip & PDF) dan evaluasi nilai peserta ASN.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <a href="{{ route('admin.courses.export') }}" class="px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-2" title="Unduh Rekap Seluruh Program Pelatihan ke Format Excel (.xlsx / .csv)">
                <i class="fa-solid fa-file-excel text-emerald-200"></i>
                <span>Tarikan Data Excel (Semua Pelatihan)</span>
            </a>
            <button @click="createModalOpen = true" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white text-xs font-bold rounded-xl shadow-xs transition-colors shrink-0">
                <i class="fa-solid fa-plus-circle text-amber-400 text-sm"></i>
                <span>+ Buat Pelatihan / Sesi Baru</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Total Pelatihan</span>
                <div class="w-8 h-8 rounded-lg bg-pasuruan-50 text-pasuruan-700 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 mt-2">{{ $courses->count() }}</div>
            <p class="text-[11px] text-slate-400 mt-0.5">MOOC, Teknis & Kepemimpinan</p>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Total Sesi/Modul</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-emerald-700 mt-2">{{ $courses->sum('lessons_count') }}</div>
            <p class="text-[11px] text-emerald-600 mt-0.5">Video materi & modul PDF</p>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Peserta ASN Terdaftar</span>
                <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-sky-700 mt-2">{{ $courses->sum('enrollments_count') }}</div>
            <p class="text-[11px] text-sky-600 mt-0.5">Aparatur Pemkab Pasuruan</p>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Standar Passing Grade</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-award"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-amber-600 mt-2">&ge; 80</div>
            <p class="text-[11px] text-amber-700 mt-0.5">Ambang batas kelulusan sertifikat</p>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            <button @click="filterType = 'all'" :class="filterType === 'all' ? 'bg-pasuruan-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors">
                Semua Tipe ({{ $courses->count() }})
            </button>
            <button @click="filterType = 'mooc'" :class="filterType === 'mooc' ? 'bg-pasuruan-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors">
                MOOC Mandiri
            </button>
            <button @click="filterType = 'teknis'" :class="filterType === 'teknis' ? 'bg-pasuruan-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors">
                Diklat Teknis
            </button>
            <button @click="filterType = 'kepemimpinan'" :class="filterType === 'kepemimpinan' ? 'bg-pasuruan-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors">
                Kepemimpinan
            </button>
        </div>

        <div class="relative w-full md:w-72">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" x-model="searchQuery" placeholder="Cari judul pelatihan..." class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-pasuruan-500 focus:bg-white transition-colors">
        </div>
    </div>

    <!-- Course Cards / List Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($courses as $course)
            <div x-show="(filterType === 'all' || filterType === '{{ $course->type }}') && ('{{ strtolower($course->title) }}'.includes(searchQuery.toLowerCase()))" 
                 class="bg-white rounded-2xl border border-slate-200 shadow-2xs hover:shadow-md transition-all overflow-hidden flex flex-col justify-between group">
                
                <div>
                    <!-- Card Header / Banner Display -->
                    <div class="h-36 bg-pasuruan-950 p-5 flex flex-col justify-between relative overflow-hidden">
                        @if($course->banner_url)
                            <img src="{{ $course->banner_url }}" alt="{{ $course->title }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/95 via-slate-900/60 to-slate-950/40"></div>
                        @else
                            <div class="absolute inset-0 bg-gradient-to-br from-pasuruan-800 to-slate-950"></div>
                            <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-white/5 rounded-full blur-xl pointer-events-none"></div>
                        @endif

                        <div class="relative z-10 flex items-center justify-between">
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-white/20 text-white backdrop-blur-xs border border-white/20">
                                {{ strtoupper(str_replace('_', ' ', $course->type)) }}
                            </span>
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-amber-400 text-slate-900 shadow-xs">
                                {{ $course->jp }} JP ({{ $course->jp * 45 }} Mnt)
                            </span>
                        </div>
                        <h4 class="relative z-10 text-xs font-bold text-sky-200 truncate drop-shadow-xs">{{ $course->category }}</h4>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 space-y-3">
                        <h3 class="font-extrabold text-sm text-slate-900 group-hover:text-pasuruan-700 transition-colors line-clamp-2">
                            {{ $course->title }}
                        </h3>
                        <p class="text-xs text-slate-500 line-clamp-2">
                            {{ $course->description }}
                        </p>

                        <div class="flex items-center justify-between text-[11px] pt-1">
                            <span class="text-slate-400">Penyelenggara:</span>
                            <span class="px-2 py-0.5 rounded-md bg-pasuruan-50 text-pasuruan-800 border border-pasuruan-200 font-semibold text-[10px] truncate max-w-[170px]" title="{{ $course->organizer_unit_kerja }}">
                                <i class="fa-solid fa-building-columns text-pasuruan-600 mr-1"></i>{{ $course->organizer->name ?? 'BKPSDM' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-400">Jangka Waktu:</span>
                            @if($course->hasTimeLimit())
                                <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-900 border border-amber-200 font-bold flex items-center gap-1 text-[10px]">
                                    <i class="fa-regular fa-clock text-amber-600"></i>
                                    <span>{{ $course->getTimeLimitDescription() }}</span>
                                </span>
                            @else
                                <span class="text-slate-400 font-medium italic text-[10px]">Fleksibel</span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-400">No. Sertifikat:</span>
                            @if($course->certificate_number)
                                <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono font-bold text-[10px] truncate max-w-[170px]" title="{{ $course->certificate_number }}">
                                    <i class="fa-solid fa-stamp text-emerald-600 mr-1"></i>{{ $course->certificate_number }}
                                </span>
                            @else
                                <span class="text-slate-400 font-medium italic text-[10px]">Otomatis (Berbeda Tiap Peserta)</span>
                            @endif
                        </div>

                        <div class="pt-3 border-t border-slate-100 grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-[10px] text-slate-400 block">Sesi Materi</span>
                                <span class="font-extrabold text-slate-800">{{ $course->lessons_count }} Sesi</span>
                            </div>
                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-[10px] text-slate-400 block">Peserta ASN</span>
                                <span class="font-extrabold text-pasuruan-700">{{ $course->enrollments_count }} Org</span>
                            </div>
                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-[10px] text-slate-400 block">Passing Grade</span>
                                <span class="font-extrabold text-emerald-600">&ge; {{ $course->passing_grade }}</span>
                                <span class="text-[9px] text-amber-700 block font-semibold">Plafon: {{ $course->remedial_cap_score ?? 90 }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Actions -->
                <div class="p-5 pt-0 border-t border-slate-100 flex items-center gap-1.5 mt-2">
                    <button type="button" @click="openShare({{ json_encode($course) }})" class="p-2 bg-sky-50 hover:bg-sky-100 text-sky-700 font-bold rounded-xl text-xs transition-colors border border-sky-200 flex items-center justify-center cursor-pointer" title="Buat & Salin Link Sebaran Pelatihan">
                        <i class="fa-solid fa-share-nodes"></i>
                    </button>
                    <a href="{{ route('admin.courses.export_participants', $course->id) }}" class="p-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-xl text-xs transition-colors border border-emerald-200 flex items-center justify-center" title="Unduh Rekap Nilai & Hasil Belajar Peserta ke Excel">
                        <i class="fa-solid fa-file-excel"></i>
                    </a>
                    <a href="{{ route('admin.courses.manage', $course->id) }}" class="flex-1 py-2 px-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white text-xs font-bold rounded-xl text-center transition-colors shadow-2xs flex items-center justify-center gap-1" title="Unggah Materi Pelajaran">
                        <i class="fa-solid fa-cloud-arrow-up text-amber-300"></i>
                        <span>Materi</span>
                    </a>
                    <a href="{{ route('admin.courses.results', $course->id) }}" class="py-2 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl text-center transition-colors border border-slate-300 flex items-center justify-center gap-1" title="Hasil Belajar & Nilai">
                        <i class="fa-solid fa-clipboard-check text-emerald-600"></i>
                        <span>Nilai</span>
                    </a>
                    <button type="button" @click="openEdit({{ json_encode($course) }})" class="p-2 bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold rounded-xl text-xs transition-colors border border-amber-200 flex items-center justify-center" title="Ubah / Edit Informasi & Background Pelatihan">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button type="button" @click="openDelete({{ json_encode($course) }})" class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold rounded-xl text-xs transition-colors border border-rose-200 flex items-center justify-center" title="Hapus Pelatihan">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>

            </div>
        @empty
            <div class="col-span-full p-12 text-center bg-white rounded-2xl border border-slate-200 text-slate-500">
                <i class="fa-solid fa-box-open text-4xl text-slate-300 mb-3"></i>
                <h4 class="font-bold text-sm text-slate-700">Belum ada data pelatihan/kursus</h4>
                <p class="text-xs text-slate-400 mt-1">Klik tombol "+ Buat Pelatihan / Sesi Baru" untuk menambahkan program pelatihan kedinasan.</p>
            </div>
        @endforelse
    </div>

    <!-- Modal Buat Pelatihan / Sesi Baru -->
    <div x-show="createModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5 text-pasuruan-800 font-bold text-base">
                    <i class="fa-solid fa-folder-plus text-amber-500"></i>
                    <span>Buat Program Pelatihan / Diklat Baru</span>
                </div>
                <button @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('admin.courses.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <div class="space-y-1.5">
                    <label class="font-bold text-slate-700">Nama / Judul Program Pelatihan <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" required placeholder="Contoh: Digitalisasi Arsip & Tata Kelola SPBE Pasuruan" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
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
                            <option value="{{ $org->id }}" {{ (auth()->id() == $org->id) ? 'selected' : '' }}>
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
                            <option value="mooc">MOOC Mandiri (Asynchronous)</option>
                            <option value="teknis">Bimtek & Diklat Teknis</option>
                            <option value="kepemimpinan">Pelatihan Kepemimpinan (PKP/PKA)</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700">Kategori / Rumpun Diklat <span class="text-rose-500">*</span></label>
                        <input type="text" name="category" required placeholder="Contoh: SPBE & Teknologi Informasi" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700">Bobot JP <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="number" name="jp" value="20" min="1" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 text-xs">
                            <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">JP</span>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700">Passing Grade (&ge;) <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="number" name="passing_grade" value="80" min="10" max="100" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 text-xs font-mono font-bold text-emerald-700">
                            <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">Min</span>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700">Plafon Remedial <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="number" name="remedial_cap_score" value="90" min="10" max="100" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 text-xs font-mono font-bold text-amber-700">
                            <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">Maks</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="font-bold text-slate-700">Batas Maksimal Percobaan Remedial <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="max_remedial" value="5" min="1" max="10" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 text-xs">
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">Kali Percobaan Remedial</span>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="font-bold text-slate-700">Deskripsi & Capaian Pembelajaran <span class="text-rose-500">*</span></label>
                    <textarea name="description" rows="3" required placeholder="Jelaskan tujuan kompetensi, sasaran peserta ASN, dan hasil yang diharapkan..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500"></textarea>
                </div>

                <!-- Pengaturan Batas Waktu & Jangka Waktu Pelatihan -->
                <div class="space-y-3 p-4 bg-amber-50/60 rounded-xl border border-amber-200" x-data="{ timeLimitType: 'none' }">
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
                            <input type="number" name="duration_days" min="1" placeholder="Contoh: 14 atau 30" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">Hari Kalender</span>
                        </div>
                        <p class="text-[10px] text-slate-500">Dihitung otomatis sejak peserta pertama kali menekan mulai belajar. Jika hari habis dan belum selesai, status menjadi Tidak Lulus.</p>
                    </div>

                    <!-- Input Rentang Tanggal & Waktu 24 Jam -->
                    <div x-show="timeLimitType === 'fixed_date' || timeLimitType === 'both'" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div class="space-y-1">
                            <label class="font-bold text-slate-700 text-xs flex items-center justify-between">
                                <span class="flex items-center gap-1"><i class="fa-regular fa-calendar text-pasuruan-700"></i> Mulai Dibuka (Tgl & Jam WIB)</span>
                                <span class="text-[10px] text-pasuruan-700 bg-pasuruan-50 px-1.5 py-0.5 rounded font-mono">24 Jam</span>
                            </label>
                            <input type="text" name="start_date" x-datetimepicker24 placeholder="YYYY-MM-DD HH:mm" class="datetimepicker-24h w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                            <p class="text-[9px] text-slate-400">Sebelum tanggal dan jam ini, pendaftaran & materi belum dibuka.</p>
                        </div>
                        <div class="space-y-1">
                            <label class="font-bold text-slate-700 text-xs flex items-center justify-between">
                                <span class="flex items-center gap-1"><i class="fa-regular fa-clock text-rose-600"></i> Batas Akhir (Tgl & Jam WIB)</span>
                                <span class="text-[10px] text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded font-mono">24 Jam</span>
                            </label>
                            <input type="text" name="end_date" x-datetimepicker24 placeholder="YYYY-MM-DD HH:mm" class="datetimepicker-24h w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                            <p class="text-[9px] text-slate-400">Lewat tanggal dan jam ini, pendaftaran ditutup & peserta yang belum selesai otomatis Tidak Lulus.</p>
                        </div>
                    </div>
                </div>

                <!-- Unggah Background / Banner Pelatihan (Fitur Baru) -->
                <div class="space-y-1.5 p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
                    <label class="block font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                        <i class="fa-regular fa-image text-emerald-600"></i>
                        <span>Upload Background / Banner Pelatihan (Opsional)</span>
                    </label>
                    <input type="file" name="banner" accept="image/png,image/jpeg,image/jpg,image/webp" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                    <p class="text-[10px] text-slate-400">Format gambar: JPG, PNG, WebP (Maks. 5 MB). Gambar akan menjadi latar ruang belajar peserta dan kartu katalog.</p>
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
                                    <option value="{{ $tpl->id }}">{{ $tpl->name }} ({{ $tpl->target_badge }})</option>
                                @endforeach
                            @endif
                        </select>
                        <p class="text-[9px] text-slate-400">Pilih template khusus jika pelatihan ini menggunakan blanko/desain sertifikat tersendiri.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="font-bold text-slate-700 text-xs">Nomor Urut Awal (Start Number)</label>
                            <input type="number" min="1" name="certificate_start_number" placeholder="Contoh: 1 atau 101" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono font-bold focus:outline-none focus:border-pasuruan-500">
                            <p class="text-[9px] text-slate-400">Nomor sertifikat peserta pertama pelatihan ini akan dimulai dari angka ini.</p>
                        </div>
                        <div class="space-y-1">
                            <label class="font-bold text-slate-700 text-xs">Pola Format Nomor (Opsional)</label>
                            <input type="text" name="certificate_format" placeholder="893.3/{NOMOR}/BKPSDM-PAS/{TAHUN}" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono focus:outline-none focus:border-pasuruan-500">
                            <p class="text-[9px] text-slate-400">Kosongkan jika ingin memakai format default sistem.</p>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-slate-700 text-xs">Nomor SK / Surat Statis Seragam (Opsional)</label>
                        <input type="text" name="certificate_number" placeholder="Contoh: 893.3/045/BKPSDM-PAS/2026" class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono focus:outline-none focus:border-pasuruan-500">
                        <p class="text-[10px] text-slate-500 leading-relaxed">
                            Jika diisi, seluruh peserta yang lulus mendapat nomor surat yang sama persis. Jika dikosongkan, penomoran urut otomatis diterapkan.
                        </p>
                    </div>

                    <div class="space-y-1 pt-2 border-t border-slate-200">
                        <label class="font-bold text-slate-700 text-xs flex items-center justify-between">
                            <span class="flex items-center gap-1.5">
                                <i class="fa-solid fa-file-invoice text-indigo-600"></i>
                                <span>Upload Blanko Lampiran Khusus Pelatihan (Hal. 2)</span>
                            </span>
                            <span class="text-[10px] text-slate-400">Opsional</span>
                        </label>
                        <input type="file" name="certificate_lampiran_file" accept="image/png,image/jpeg,image/jpg,image/webp" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                        <p class="text-[10px] text-slate-400 leading-relaxed">
                            Blanko Halaman 2 untuk rincian sesi materi & jam pelajaran (JP). Biarkan kosong untuk menggunakan template lampiran default dinas.
                        </p>
                    </div>
                </div>

                <div class="p-3 bg-pasuruan-50 border border-pasuruan-200 rounded-xl flex items-center gap-3">
                    <i class="fa-solid fa-circle-info text-pasuruan-700 text-base shrink-0"></i>
                    <p class="text-[11px] text-pasuruan-900 leading-snug">
                        Setelah pelatihan dibuat, Anda akan langsung diarahkan ke form kelola materi tiap bab (video anti-skip dan modul dokumen PDF).
                    </p>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2.5 text-slate-600 hover:bg-slate-100 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk text-amber-300"></i>
                        <span>Simpan & Buat Pelatihan</span>
                    </button>
                </div>
            </form>
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

            <form :action="selectedCourse.update_url" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <div class="space-y-1.5">
                    <label class="font-bold text-slate-700">Nama / Judul Program Pelatihan <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" x-model="selectedCourse.title" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
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
                    <select name="organizer_id" x-model="selectedCourse.organizer_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 text-xs font-medium">
                        @foreach($organizers as $org)
                            <option value="{{ $org->id }}">
                                {{ $org->name }} — {{ $org->profile->unit_kerja ?? 'BKPSDM Kabupaten Pasuruan' }} ({{ $org->role === 'super_admin' ? 'Super Admin' : 'Penyelenggara Diklat' }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400">Pilih akun Penyelenggara / PIC kegiatan ini. Data unit kerja penyelenggara ini akan dicetak pada e-sertifikat kelulusan ("Diselenggarakan oleh ...").</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700">Tipe Pelatihan <span class="text-rose-500">*</span></label>
                        <select name="type" x-model="selectedCourse.type" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                            <option value="mooc">MOOC Mandiri (Asynchronous)</option>
                            <option value="teknis">Bimtek & Diklat Teknis</option>
                            <option value="kepemimpinan">Pelatihan Kepemimpinan (PKP/PKA)</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700">Kategori / Rumpun Diklat <span class="text-rose-500">*</span></label>
                        <input type="text" name="category" x-model="selectedCourse.category" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700">Bobot JP <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="number" name="jp" x-model="selectedCourse.jp" min="1" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 text-xs">
                            <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">JP</span>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700">Passing Grade (&ge;) <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="number" name="passing_grade" x-model="selectedCourse.passing_grade" min="10" max="100" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 text-xs font-mono font-bold text-emerald-700">
                            <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">Min</span>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700">Plafon Remedial <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="number" name="remedial_cap_score" x-model="selectedCourse.remedial_cap_score" min="10" max="100" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 text-xs font-mono font-bold text-amber-700">
                            <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">Maks</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="font-bold text-slate-700">Batas Maksimal Percobaan Remedial <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="max_remedial" x-model="selectedCourse.max_remedial" min="1" max="10" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 text-xs">
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">Kali Percobaan Remedial</span>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="font-bold text-slate-700">Deskripsi & Capaian Pembelajaran <span class="text-rose-500">*</span></label>
                    <textarea name="description" x-model="selectedCourse.description" rows="3" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500"></textarea>
                </div>

                <!-- Pengaturan Batas Waktu & Jangka Waktu Pelatihan -->
                <div class="space-y-3 p-4 bg-amber-50/60 rounded-xl border border-amber-200">
                    <div class="flex items-center justify-between">
                        <label class="block font-bold text-amber-950 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <i class="fa-regular fa-clock text-amber-600"></i>
                            <span>Batas Waktu Pelaksanaan & Masa Pengerjaan</span>
                        </label>
                        <span class="text-[10px] text-amber-800 bg-amber-100 px-2 py-0.5 rounded font-bold">Auto-Fail & Lock</span>
                    </div>

                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700 text-xs">Pilih Skema Jangka Waktu</label>
                        <select name="time_limit_type" x-model="selectedCourse.time_limit_type" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-semibold focus:outline-none focus:border-pasuruan-500">
                            <option value="none">Tanpa Batas Waktu (Fleksibel)</option>
                            <option value="relative_days">Durasi Hari sejak Peserta Mulai (e.g. 14 hari pengerjaan)</option>
                            <option value="fixed_date">Rentang Tanggal Tertentu (Tanggal Mulai & Batas Akhir)</option>
                            <option value="both">Kombinasi (Rentang Tanggal + Maksimal Hari sejak Mulai)</option>
                        </select>
                    </div>

                    <!-- Input Durasi Hari -->
                    <div x-show="selectedCourse.time_limit_type === 'relative_days' || selectedCourse.time_limit_type === 'both'" class="space-y-1.5 pt-1">
                        <label class="font-bold text-slate-700 text-xs">Batas Masa Pengerjaan (Hari) <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="number" name="duration_days" x-model="selectedCourse.duration_days" min="1" placeholder="Contoh: 14 atau 30" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-bold">Hari Kalender</span>
                        </div>
                        <p class="text-[10px] text-slate-500">Dihitung otomatis sejak peserta pertama kali menekan mulai belajar. Jika hari habis dan belum selesai, status menjadi Tidak Lulus.</p>
                    </div>

                    <!-- Input Rentang Tanggal & Waktu 24 Jam -->
                    <div x-show="selectedCourse.time_limit_type === 'fixed_date' || selectedCourse.time_limit_type === 'both'" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div class="space-y-1">
                            <label class="font-bold text-slate-700 text-xs flex items-center justify-between">
                                <span class="flex items-center gap-1"><i class="fa-regular fa-calendar text-pasuruan-700"></i> Mulai Dibuka (Tgl & Jam WIB)</span>
                                <span class="text-[10px] text-pasuruan-700 bg-pasuruan-50 px-1.5 py-0.5 rounded font-mono">24 Jam</span>
                            </label>
                            <input type="text" name="start_date" x-datetimepicker24 x-model="selectedCourse.start_date" placeholder="YYYY-MM-DD HH:mm" class="datetimepicker-24h w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                            <p class="text-[9px] text-slate-400">Sebelum tanggal dan jam ini, pendaftaran & materi belum dibuka.</p>
                        </div>
                        <div class="space-y-1">
                            <label class="font-bold text-slate-700 text-xs flex items-center justify-between">
                                <span class="flex items-center gap-1"><i class="fa-regular fa-clock text-rose-600"></i> Batas Akhir (Tgl & Jam WIB)</span>
                                <span class="text-[10px] text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded font-mono">24 Jam</span>
                            </label>
                            <input type="text" name="end_date" x-datetimepicker24 x-model="selectedCourse.end_date" placeholder="YYYY-MM-DD HH:mm" class="datetimepicker-24h w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono font-semibold cursor-pointer">
                            <p class="text-[9px] text-slate-400">Lewat tanggal dan jam ini, pendaftaran ditutup & peserta yang belum selesai otomatis Tidak Lulus.</p>
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
                        <select name="certificate_template_id" x-model="selectedCourse.certificate_template_id" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-semibold focus:outline-none focus:border-pasuruan-500">
                            <option value="">-- Gunakan Template Default Sistem --</option>
                            @if(isset($templates))
                                @foreach($templates as $tpl)
                                    <option value="{{ $tpl->id }}">{{ $tpl->name }} ({{ $tpl->target_badge }})</option>
                                @endforeach
                            @endif
                        </select>
                        <p class="text-[9px] text-slate-400">Pilih template khusus jika pelatihan ini menggunakan blanko/desain sertifikat tersendiri.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="font-bold text-slate-700 text-xs">Nomor Urut Awal (Start Number)</label>
                            <input type="number" min="1" name="certificate_start_number" x-model="selectedCourse.certificate_start_number" placeholder="Contoh: 1 atau 101" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono font-bold focus:outline-none focus:border-pasuruan-500">
                            <p class="text-[9px] text-slate-400">Nomor urut peserta pertama diklat ini dimulai dari angka ini.</p>
                        </div>
                        <div class="space-y-1">
                            <label class="font-bold text-slate-700 text-xs">Pola Format Nomor (Opsional)</label>
                            <input type="text" name="certificate_format" x-model="selectedCourse.certificate_format" placeholder="893.3/{NOMOR}/BKPSDM-PAS/{TAHUN}" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono focus:outline-none focus:border-pasuruan-500">
                            <p class="text-[9px] text-slate-400">Kosongkan jika ingin memakai format default sistem.</p>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-slate-700 text-xs">Nomor SK / Surat Statis Seragam (Opsional)</label>
                        <input type="text" name="certificate_number" x-model="selectedCourse.certificate_number" placeholder="Contoh: 893.3/045/BKPSDM-PAS/2026" class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-xs font-mono focus:outline-none focus:border-pasuruan-500">
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
                            <template x-if="selectedCourse.certificate_lampiran_url">
                                <a :href="selectedCourse.certificate_lampiran_url" target="_blank" class="text-[10px] text-indigo-600 hover:underline flex items-center gap-1 font-bold">
                                    <i class="fa-solid fa-eye"></i> Lihat Blanko Saat Ini
                                </a>
                            </template>
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
                    <p class="font-extrabold text-slate-900" x-text="deleteCourse.title"></p>
                    <p class="text-[10px] text-slate-400 pt-1">Seluruh data materi sesi, riwayat enrollment, progres belajar, dan nilai peserta terkait akan dihapus secara otomatis.</p>
                </div>

                <form :action="deleteCourse.delete_url" method="POST" class="pt-2 flex items-center justify-end gap-2 text-xs">
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

    <!-- Modal Bagikan Link Pelatihan MOOC -->
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
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-amber-100 text-amber-900 border border-amber-300" x-text="'Plafon Remedial ' + (shareModal.remedial_cap_score || 90)"></span>
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
