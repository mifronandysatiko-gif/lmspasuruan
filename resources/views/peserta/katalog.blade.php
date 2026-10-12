@extends('layouts.dashboard')

@section('title', 'Katalog Pelatihan - LMS Kabupaten Pasuruan')
@section('header_title', 'Katalog Pengembangan Kompetensi')

@section('content')
<div class="space-y-8" x-data="{
    shareModal: {
        open: false,
        title: '',
        subtitle: '',
        typeBadge: '',
        jpBadge: '',
        url: '',
        rawMessage: '',
        whatsappUrl: '',
        copied: false
    },
    openShareWebinar(wb) {
        let url = '{{ url('/peserta/webinar') }}/' + wb.slug;
        let schedule = wb.event_date + ' (' + (wb.start_time ? wb.start_time.substring(0, 5) : '08:00') + ' - ' + (wb.end_time ? wb.end_time.substring(0, 5) : '12:00') + ' WIB)';
        let desc = (wb.description || '').trim();
        let msg = `📢 *WEBINAR & BIMTEK BKPSDM KABUPATEN PASURUAN*\n\n` +
                  `📌 *Topik:* ${wb.title}\n` +
                  `🗓 *Jadwal:* ${schedule}\n` +
                  `⏱ *Bobot:* ${wb.jp} JP\n` +
                  `🎯 *Passing Grade Kelulusan:* ≥ ${wb.passing_grade} (Remedial: Plafon ${wb.remedial_cap_score || 90}, Maks. ${wb.max_remedial || 5}x)\n` +
                  `🏢 *Penyelenggara:* BKPSDM Kabupaten Pasuruan\n`;
        if (desc) {
            msg += `\n📝 *Deskripsi & Ringkasan Materi:*\n${desc}\n`;
        }
        msg += `\n🔗 *Tautan Mengikuti Kegiatan:* ${url}\n\n` +
               `_Catatan: Silakan login/masuk ke akun LMS Pasuruan untuk presensi dan klaim e-sertifikat._`;
        this.shareModal = {
            open: true,
            title: wb.title,
            subtitle: 'Jadwal: ' + schedule,
            typeBadge: 'WEBINAR',
            jpBadge: wb.jp + ' JP',
            passingGradeBadge: 'Min. ' + wb.passing_grade + ' (Plafon ' + (wb.remedial_cap_score || 90) + ')',
            url: url,
            rawMessage: msg,
            whatsappUrl: 'https://api.whatsapp.com/send?text=' + encodeURIComponent(msg),
            copied: false
        };
    },
    openShareCourse(crs) {
        let url = '{{ url('/peserta/mooc') }}/' + crs.slug;
        let typeStr = (crs.type || 'mooc').toUpperCase().replace('_', ' ');
        let desc = (crs.description || '').trim();
        let msg = `📢 *PELATIHAN MANDIRI (${typeStr}) BKPSDM KABUPATEN PASURUAN*\n\n` +
                  `📌 *Program:* ${crs.title}\n` +
                  `📚 *Rumpun / Kategori:* ${crs.category}\n` +
                  `🏷 *Tipe Program:* ${typeStr}\n` +
                  `⏱ *Bobot:* ${crs.jp} JP\n` +
                  `🎯 *Passing Grade Kelulusan:* ≥ ${crs.passing_grade} (Remedial: Plafon ${crs.remedial_cap_score || 90}, Maks. ${crs.max_remedial || 5}x)\n`;
        if (desc) {
            msg += `\n📝 *Deskripsi & Ringkasan Materi:*\n${desc}\n`;
        }
        msg += `\n🔗 *Tautan Akses Ruang Belajar:* ${url}\n\n` +
               `_Catatan: Khusus ASN Pemerintah Kabupaten Pasuruan. Silakan login untuk memulai materi pembelajaran._`;
        this.shareModal = {
            open: true,
            title: crs.title,
            subtitle: crs.category,
            typeBadge: typeStr,
            jpBadge: crs.jp + ' JP',
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
    }
}">
    
    <!-- Top Filter & Search Bar -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900">Katalog Pelatihan Mandiri & Kedinasan</h2>
                <p class="text-xs text-slate-500">Pilih program pelatihan sesuai standar kompetensi jabatan ASN Anda</p>
            </div>

            <!-- Search Bar Form -->
            <form action="{{ route('peserta.katalog') }}" method="GET" class="flex items-center gap-2 max-w-sm w-full">
                <input type="hidden" name="category" value="{{ $category }}">
                <div class="relative w-full">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                    <input type="text" name="q" value="{{ $search }}" placeholder="Cari judul pelatihan..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pasuruan-700">
                </div>
                <button type="submit" class="px-4 py-2 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-xl transition-colors">
                    Cari
                </button>
            </form>
        </div>

        <!-- Category Pills (MOOC, Teknis & Fungsional, Kepemimpinan) -->
        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 text-xs">
            <a href="{{ route('peserta.katalog', ['category' => 'all', 'q' => $search]) }}" class="px-3.5 py-1.5 rounded-lg font-bold transition-colors {{ $category === 'all' ? 'bg-pasuruan-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Semua Pelatihan ({{ $courses->count() }})
            </a>
            <a href="{{ route('peserta.katalog', ['category' => 'mooc', 'q' => $search]) }}" class="px-3.5 py-1.5 rounded-lg font-bold transition-colors {{ $category === 'mooc' ? 'bg-pasuruan-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                <i class="fa-solid fa-laptop-code mr-1"></i> MOOC Mandiri (Asynchronous)
            </a>
            <a href="{{ route('peserta.katalog', ['category' => 'teknis_fungsional', 'q' => $search]) }}" class="px-3.5 py-1.5 rounded-lg font-bold transition-colors {{ $category === 'teknis_fungsional' ? 'bg-pasuruan-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                <i class="fa-solid fa-briefcase mr-1"></i> Pelatihan Teknis & Fungsional
            </a>
            <a href="{{ route('peserta.katalog', ['category' => 'kepemimpinan', 'q' => $search]) }}" class="px-3.5 py-1.5 rounded-lg font-bold transition-colors {{ $category === 'kepemimpinan' ? 'bg-pasuruan-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                <i class="fa-solid fa-award mr-1"></i> Pelatihan Kepemimpinan (PKP/PKA)
            </a>
        </div>
    </div>

    <!-- DAFTAR KURSUS MOOC MANDIRI & KEDINASAN ASN -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-extrabold text-base text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-graduation-cap text-pasuruan-700"></i>
                <span>Program MOOC & Pelatihan Kedinasan ASN</span>
            </h3>
            <span class="text-xs text-slate-500 font-medium">Asynchronous Video Anti-Skip & Tugas</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @forelse($courses as $course)
                @php
                    $courseStatus = $course->getParticipantStatus($user);
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col justify-between shadow-xs hover:border-pasuruan-500 hover:shadow-md transition-all group">
                    <div>
                        @if($course->banner_url)
                            <div class="h-36 w-full overflow-hidden relative bg-pasuruan-950">
                                <img src="{{ $course->banner_url }}" alt="{{ $course->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/95 via-slate-900/40 to-transparent"></div>
                                <div class="absolute top-3 left-3 right-3 flex items-center justify-between">
                                    <span class="text-[10px] font-extrabold px-2.5 py-1 rounded-md bg-white/20 text-white uppercase tracking-wider backdrop-blur-xs border border-white/20">
                                        {{ $course->category }}
                                    </span>
                                    <span class="text-xs font-extrabold text-amber-300 bg-slate-900/80 px-2.5 py-1 rounded-md border border-amber-400/30">
                                        <i class="fa-solid fa-hourglass-half mr-1"></i> {{ $course->jp }} JP
                                    </span>
                                </div>
                            </div>
                        @endif

                        <div class="p-6 space-y-3">
                            @if(!$course->banner_url)
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[10px] font-bold px-2.5 py-1 rounded bg-sky-50 text-sky-700 border border-sky-200 uppercase tracking-wider">
                                        {{ $course->category }}
                                    </span>
                                    <span class="text-xs font-bold text-amber-600">
                                        <i class="fa-solid fa-hourglass-half mr-1"></i> {{ $course->jp }} JP
                                    </span>
                                </div>
                            @endif

                            <!-- Status Partisipasi Peserta pada Pelatihan Lainnya & Info Batas Waktu -->
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] {{ $courseStatus['badge_class'] }}">
                                    <i class="fa-solid {{ $courseStatus['icon'] }}"></i>
                                    <span>{{ $courseStatus['label'] }}</span>
                                </span>

                                @if($course->hasTimeLimit())
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-900 border border-amber-200" title="{{ $course->getTimeLimitDescription() }}">
                                        <i class="fa-regular fa-clock text-amber-600"></i>
                                        <span>{{ $course->getTimeLimitDescription() }}</span>
                                    </span>
                                @endif
                            </div>

                            <h3 class="font-bold text-base text-slate-900 leading-snug">
                                {{ $course->title }}
                            </h3>

                            <p class="text-xs text-slate-500 leading-relaxed line-clamp-3">
                                {{ $course->description }}
                            </p>

                            <div class="pt-2 flex items-center justify-between text-[11px] font-semibold text-emerald-800 bg-emerald-50/80 px-2.5 py-1.5 rounded-lg border border-emerald-200">
                                <span><i class="fa-solid fa-graduation-cap text-emerald-600 mr-1"></i> Passing Grade: &ge; {{ $course->passing_grade }}</span>
                                <span class="text-amber-800 font-bold text-[10px]">Plafon Remedial: {{ $course->remedial_cap_score ?? 90 }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 p-6 pt-0 flex items-center gap-2">
                        <button type="button" @click="openShareCourse({{ json_encode($course) }})" class="p-2.5 bg-sky-50 hover:bg-sky-100 text-sky-700 font-bold rounded-xl text-xs transition-colors border border-sky-200 flex items-center justify-center shrink-0 cursor-pointer" title="Bagikan Tautan Pelatihan ke Rekan ASN">
                            <i class="fa-solid fa-share-nodes"></i>
                        </button>
                        <div class="flex-1">
                            @if($user->isPesertaUmum())
                                <div class="p-2 bg-slate-50 border border-slate-200 rounded-xl text-center space-y-1">
                                    <span class="text-[11px] font-bold text-slate-400 flex items-center justify-center gap-1">
                                        <i class="fa-solid fa-lock text-rose-500"></i> Khusus ASN Pemkab Pasuruan
                                    </span>
                                    <a href="{{ route('peserta.webinar.index') }}" class="block text-[11px] font-bold text-pasuruan-700 hover:underline">
                                        Lihat Webinar Terbuka &rarr;
                                    </a>
                                </div>
                            @elseif($courseStatus['status'] === 'failed')
                                <button type="button" disabled class="block w-full py-2.5 text-center font-bold text-xs bg-rose-50 text-rose-700 border border-rose-200 rounded-xl cursor-not-allowed">
                                    <i class="fa-solid fa-ban mr-1 text-rose-500"></i> Tidak Lulus (Waktu Habis)
                                </button>
                            @elseif($courseStatus['status'] === 'closed')
                                <button type="button" disabled class="block w-full py-2.5 text-center font-bold text-xs bg-slate-100 text-slate-500 border border-slate-200 rounded-xl cursor-not-allowed">
                                    <i class="fa-solid fa-calendar-xmark mr-1"></i> Pendaftaran Ditutup (Kadaluarsa)
                                </button>
                            @elseif($courseStatus['status'] === 'upcoming')
                                <button type="button" disabled class="block w-full py-2.5 text-center font-bold text-xs bg-amber-50 text-amber-800 border border-amber-200 rounded-xl cursor-not-allowed">
                                    <i class="fa-solid fa-hourglass-start mr-1 text-amber-600"></i> Belum Dibuka
                                </button>
                            @elseif($course->type === 'kepemimpinan')
                                <a href="{{ route('peserta.kepemimpinan') }}" class="block w-full py-2.5 text-center font-bold text-xs bg-amber-500 hover:bg-amber-600 text-white rounded-xl shadow-xs transition-colors">
                                    Masuk Bimbingan Aksi Perubahan &rarr;
                                </a>
                            @else
                                <a href="{{ route('peserta.mooc.show', $course->slug) }}" class="block w-full py-2.5 text-center font-bold text-xs bg-pasuruan-700 hover:bg-pasuruan-800 text-white rounded-xl shadow-xs transition-colors">
                                    {{ $courseStatus['status'] === 'completed' ? 'Pelajari Ulang Modul &rarr;' : ($courseStatus['status'] === 'in_progress' ? 'Lanjutkan Belajar (' . $courseStatus['progress'] . '%) &rarr;' : 'Mulai Belajar Sekarang &rarr;') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-16 bg-white rounded-2xl border border-slate-200 text-slate-400 space-y-3">
                    <i class="fa-solid fa-magnifying-glass text-4xl text-slate-300"></i>
                    <p class="text-sm">Tidak ada program pelatihan yang sesuai dengan kriteria pencarian Anda.</p>
                    <a href="{{ route('peserta.katalog') }}" class="inline-block text-xs font-bold text-pasuruan-700 hover:underline">
                        Reset Filter Pencarian
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    <!-- MODAL BAGIKAN TAUTAN (WEBINAR / PELATIHAN) -->
    <div x-show="shareModal.open" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5 text-sky-800 font-bold text-sm">
                        <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center">
                            <i class="fa-solid fa-share-nodes"></i>
                        </div>
                        <span>Bagikan Informasi & Tautan Kegiatan</span>
                    </div>
                    <button type="button" @click="shareModal.open = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Quick Summary Box -->
                <div class="p-3.5 bg-sky-50/70 border border-sky-200 rounded-xl space-y-1">
                    <div class="flex items-center gap-1.5">
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-sky-200 text-sky-900" x-text="shareModal.typeBadge"></span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-amber-200 text-amber-900" x-text="shareModal.jpBadge"></span>
                    </div>
                    <h4 class="font-extrabold text-xs text-slate-900" x-text="shareModal.title"></h4>
                    <p class="text-[11px] text-slate-600" x-text="shareModal.subtitle"></p>
                </div>

                <!-- Direct URL Input with Copy Button -->
                <div class="space-y-1.5">
                    <label class="font-bold text-xs text-slate-700 block">Tautan Langsung (Direct URL):</label>
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
                        <label class="font-bold text-xs text-slate-700">Format Pesan WhatsApp / Grup OPD:</label>
                        <button type="button" @click="copyShareMessage()" class="text-[11px] font-bold text-sky-700 hover:text-sky-900 flex items-center gap-1">
                            <i class="fa-solid fa-copy"></i>
                            <span>Salin Teks Lengkap</span>
                        </button>
                    </div>
                    <textarea readonly rows="5" :value="shareModal.rawMessage" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-600 leading-relaxed resize-none focus:outline-none select-all"></textarea>
                </div>

                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-[11px] flex items-start gap-2">
                    <i class="fa-solid fa-circle-info text-amber-600 text-xs mt-0.5"></i>
                    <span>Tautan ini dapat dibagikan kepada seluruh rekan ASN. Penerima tautan yang belum masuk akan diminta login terlebih dahulu dan otomatis diarahkan langsung ke halaman kegiatan ini.</span>
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
