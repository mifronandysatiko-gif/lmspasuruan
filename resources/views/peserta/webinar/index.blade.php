@extends('layouts.dashboard')

@section('title', 'Agenda Webinar & Bimtek - LMS Kabupaten Pasuruan')
@section('header_title', 'Agenda Webinar & Sesi Live Interaktif')

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
                <div class="flex items-center gap-2 text-xs font-bold text-rose-600 uppercase tracking-wider mb-1">
                    <i class="fa-solid fa-tower-broadcast"></i> Portal Sesi Terbuka & Bimtek Daring
                </div>
                <h2 class="text-xl font-extrabold text-slate-900">Agenda Webinar & Bimbingan Teknis Terbuka</h2>
                <p class="text-xs text-slate-500">Ikuti siaran live interaktif, presensi, evaluasi sequential gating, dan klaim e-sertifikat kedinasan</p>
            </div>

            <!-- Search Bar Form -->
            <form action="{{ route('peserta.webinar.index') }}" method="GET" class="flex items-center gap-2 max-w-sm w-full">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="relative w-full">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                    <input type="text" name="q" value="{{ $search }}" placeholder="Cari topik webinar..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl transition-colors shrink-0">
                    Cari
                </button>
            </form>
        </div>

        <!-- Filter Status Sesi -->
        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 text-xs">
            <a href="{{ route('peserta.webinar.index', ['status' => 'all', 'q' => $search]) }}" 
               class="px-3.5 py-1.5 rounded-lg font-bold transition-colors {{ $status === 'all' ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Semua Agenda ({{ $webinars->count() }})
            </a>
            <a href="{{ route('peserta.webinar.index', ['status' => 'live', 'q' => $search]) }}" 
               class="px-3.5 py-1.5 rounded-lg font-bold transition-colors flex items-center gap-1.5 {{ $status === 'live' ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                <span>Live Sekarang</span>
            </a>
            <a href="{{ route('peserta.webinar.index', ['status' => 'upcoming', 'q' => $search]) }}" 
               class="px-3.5 py-1.5 rounded-lg font-bold transition-colors {{ $status === 'upcoming' ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                <i class="fa-regular fa-calendar-check mr-1"></i> Mendatang / Terjadwal
            </a>
            <a href="{{ route('peserta.webinar.index', ['status' => 'completed', 'q' => $search]) }}" 
               class="px-3.5 py-1.5 rounded-lg font-bold transition-colors {{ $status === 'completed' ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                <i class="fa-solid fa-clock-rotate-left mr-1"></i> Telah Terlaksana
            </a>
        </div>
    </div>

    <!-- DAFTAR WEBINAR -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-600 animate-ping"></span>
                <h3 class="font-extrabold text-base text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-tower-broadcast text-rose-600"></i>
                    <span>Daftar Sesi Webinar Terbuka</span>
                </h3>
            </div>
            <span class="text-xs text-slate-500 font-medium">Terbuka untuk ASN Pemkab Pasuruan & Peserta Umum</span>
        </div>

        @if($webinars->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($webinars as $webinar)
                @php
                    $webinarStatus = $webinar->getParticipantStatus($user);
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col justify-between shadow-xs hover:border-rose-400 hover:shadow-md transition-all group">
                    <div>
                        <!-- Header Banner Acara -->
                        <div class="relative h-44 w-full overflow-hidden bg-slate-900">
                            @if($webinar->banner_url)
                                <img src="{{ $webinar->banner_url }}" alt="{{ $webinar->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full bg-gradient-to-r from-pasuruan-950 to-slate-900 flex items-center justify-center text-white/40">
                                    <i class="fa-solid fa-tower-broadcast text-4xl"></i>
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/30 to-transparent"></div>

                            <!-- Badges di atas banner -->
                            <div class="absolute top-3 left-3 right-3 flex items-start justify-between gap-2">
                                <div class="flex flex-wrap gap-1.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wider {{ $webinar->is_live ? 'bg-rose-600 text-white animate-pulse' : 'bg-slate-900/80 text-slate-200 backdrop-blur-xs' }}">
                                        {{ $webinar->is_live ? 'LIVE SEKARANG' : 'TERJADWAL' }}
                                    </span>
                                    @if($webinar->youtube_link)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-rose-600/90 text-white flex items-center gap-1 backdrop-blur-xs">
                                            <i class="fa-brands fa-youtube"></i> YouTube
                                        </span>
                                    @endif
                                </div>

                                <!-- Status Partisipasi Peserta -->
                                <span class="px-2.5 py-1 rounded-lg text-[10px] {{ $webinarStatus['badge_class'] }} flex items-center gap-1.5 shadow-sm">
                                    <i class="fa-solid {{ $webinarStatus['icon'] }}"></i>
                                    <span>{{ $webinarStatus['label'] }}</span>
                                </span>
                            </div>

                            <!-- JP & Waktu di bawah banner -->
                            <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between text-white text-xs font-bold drop-shadow">
                                <span class="flex items-center gap-1">
                                    <i class="fa-regular fa-calendar text-amber-400"></i>
                                    <span>{{ \Carbon\Carbon::parse($webinar->event_date)->isoFormat('D MMM Y') }}</span>
                                </span>
                                <span class="bg-amber-500/90 text-pasuruan-950 px-2 py-0.5 rounded font-extrabold text-[11px]">
                                    {{ $webinar->jp }} JP
                                </span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-5 space-y-3">
                            <a href="{{ route('peserta.webinar.room', $webinar->slug) }}" class="block">
                                <h4 class="font-bold text-base text-slate-900 group-hover:text-pasuruan-800 transition-colors line-clamp-2 leading-snug">
                                    {{ $webinar->title }}
                                </h4>
                            </a>
                            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                {{ $webinar->description }}
                            </p>

                            <div class="pt-2 flex items-center justify-between text-[11px] text-slate-500 font-medium border-t border-slate-100">
                                <span><i class="fa-regular fa-clock mr-1 text-slate-400"></i> {{ substr($webinar->start_time, 0, 5) }} - {{ substr($webinar->end_time, 0, 5) }} WIB</span>
                                <span class="line-clamp-1">Oleh: {{ $webinar->organizer->name }}</span>
                            </div>
                            <div class="flex items-center justify-between text-[11px] font-semibold text-emerald-800 bg-emerald-50/80 px-2.5 py-1.5 rounded-lg border border-emerald-200">
                                <span><i class="fa-solid fa-graduation-cap text-emerald-600 mr-1"></i> Passing Grade: &ge; {{ $webinar->passing_grade }}</span>
                                <span class="text-amber-800 font-bold text-[10px]">Plafon Remedial: {{ $webinar->remedial_cap_score ?? 90 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="p-5 pt-0 flex items-center gap-2">
                        <button type="button" @click="openShareWebinar({{ json_encode($webinar) }})" class="p-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold rounded-xl text-xs transition-colors border border-slate-200 flex items-center justify-center shrink-0 cursor-pointer" title="Bagikan Undangan Webinar ke WhatsApp">
                            <i class="fa-solid fa-share-nodes"></i>
                        </button>
                        <div class="flex-1">
                            @if($webinarStatus['status'] === 'unregistered')
                                <form action="{{ route('peserta.webinar.register', $webinar->id) }}" method="POST" class="w-full">
                                    @csrf
                                    <button type="submit" class="block w-full py-2.5 text-center font-bold text-xs bg-pasuruan-700 hover:bg-pasuruan-800 text-white rounded-xl shadow-xs transition-colors flex items-center justify-center gap-1.5 cursor-pointer">
                                        <i class="fa-solid fa-user-plus"></i>
                                        <span>Daftar</span>
                                    </button>
                                </form>
                            @elseif($webinarStatus['status'] === 'certified')
                                <div class="grid grid-cols-2 gap-2">
                                    <a href="{{ route('peserta.webinar.room', $webinar->slug) }}" class="py-2.5 text-center font-bold text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-colors flex items-center justify-center gap-1">
                                        <span>Masuk Sesi</span>
                                    </a>
                                    <a href="{{ route('validation.show', $webinarStatus['certificate']->hash_token) }}" target="_blank" class="py-2.5 text-center font-bold text-xs bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-xs transition-colors flex items-center justify-center gap-1">
                                        <i class="fa-solid fa-certificate"></i>
                                        <span>E-Sertifikat</span>
                                    </a>
                                </div>
                            @else
                                <a href="{{ route('peserta.webinar.room', $webinar->slug) }}" class="block w-full py-2.5 text-center font-bold text-xs {{ $webinar->is_live ? 'bg-rose-600 hover:bg-rose-700 animate-pulse' : 'bg-pasuruan-700 hover:bg-pasuruan-800' }} text-white rounded-xl shadow-xs transition-colors flex items-center justify-center gap-1.5">
                                    <i class="fa-solid {{ $webinar->is_live ? 'fa-tower-broadcast' : 'fa-door-open' }}"></i>
                                    <span>Masuk Sesi</span>
                                    <span>&rarr;</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @else
        <div class="bg-white p-12 rounded-2xl border border-dashed border-slate-300 text-center space-y-3">
            <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center mx-auto text-2xl">
                <i class="fa-solid fa-tower-broadcast"></i>
            </div>
            <h4 class="font-bold text-base text-slate-900">Tidak ada sesi webinar yang ditemukan</h4>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">
                @if($search)
                    Tidak ada agenda webinar dengan kata kunci "{{ $search }}". Silakan coba kata kunci lain.
                @else
                    Belum ada agenda webinar dengan status ini. Silakan pantau secara berkala untuk agenda bimbingan teknis berikutnya.
                @endif
            </p>
            @if($search || $status !== 'all')
                <a href="{{ route('peserta.webinar.index') }}" class="inline-block px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors">
                    Lihat Semua Webinar
                </a>
            @endif
        </div>
        @endif
    </div>

    <!-- MODAL BAGIKAN WEBINAR (SHARE MODAL) -->
    <div x-show="shareModal.open" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto" @click.away="shareModal.open = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5 text-rose-800 font-bold text-sm">
                        <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center">
                            <i class="fa-solid fa-share-nodes"></i>
                        </div>
                        <span>Bagikan Undangan Webinar</span>
                    </div>
                    <button type="button" @click="shareModal.open = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Info Acara -->
                <div class="p-3.5 bg-rose-50/70 border border-rose-200 rounded-xl space-y-1.5">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-rose-200 text-rose-900">WEBINAR TERBUKA</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-amber-200 text-amber-900" x-text="shareModal.jpBadge"></span>
                    </div>
                    <h4 class="font-extrabold text-xs text-slate-900" x-text="shareModal.title"></h4>
                    <p class="text-[11px] text-slate-600" x-text="shareModal.subtitle"></p>
                </div>

                <!-- Direct URL Input with Copy Button -->
                <div class="space-y-1.5">
                    <label class="font-bold text-xs text-slate-700 block">Tautan Langsung Ruang Acara:</label>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly :value="shareModal.url" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono text-slate-700 focus:outline-none select-all">
                        <button type="button" @click="copyShareUrl()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5 shrink-0 cursor-pointer">
                            <i class="fa-solid" :class="shareModal.copied ? 'fa-check text-emerald-300' : 'fa-copy'"></i>
                            <span x-text="shareModal.copied ? 'Tersalin!' : 'Salin'"></span>
                        </button>
                    </div>
                </div>

                <!-- WhatsApp Message Preview -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="font-bold text-xs text-slate-700">Format Undangan WhatsApp:</label>
                        <button type="button" @click="copyShareMessage()" class="text-[11px] font-bold text-rose-700 hover:text-rose-900 flex items-center gap-1">
                            <i class="fa-solid fa-copy"></i>
                            <span>Salin Teks Lengkap</span>
                        </button>
                    </div>
                    <textarea readonly rows="5" :value="shareModal.rawMessage" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-600 leading-relaxed resize-none focus:outline-none select-all"></textarea>
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
