@extends('layouts.dashboard')

@section('title', 'Konfigurasi Dinamis Sistem - Super Admin')
@section('header_title', 'Super Admin Engine (No-Code Configuration)')

@section('content')
<div class="space-y-8" x-data="{ activeGroup: 'branding' }">
    
    <!-- Top Header -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-pasuruan-700 uppercase tracking-wider mb-1">
                <i class="fa-solid fa-sliders"></i> Zero-Code Administration Engine
            </div>
            <h2 class="text-xl font-extrabold text-slate-900">Pengaturan Dinamis Sistem Terpusat</h2>
            <p class="text-xs text-slate-500">Konfigurasi seluruh aspek non-struktural aplikasi tanpa perlu memodifikasi kode sumber.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-xl border border-emerald-200">
                <i class="fa-solid fa-database mr-1"></i> Tersimpan di MySQL
            </span>
        </div>
    </div>

    <!-- Navigation Tabs Group -->
    <div class="bg-slate-200/80 p-1 rounded-xl flex flex-wrap text-xs font-bold max-w-4xl gap-1">
        <button type="button" @click="activeGroup = 'branding'" :class="activeGroup === 'branding' ? 'bg-white text-pasuruan-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="flex-1 py-2 px-3 rounded-lg transition-all flex items-center justify-center gap-2">
            <i class="fa-solid fa-palette text-amber-500"></i>
            <span>3.1 Branding & Visual</span>
        </button>

        <button type="button" @click="activeGroup = 'general'" :class="activeGroup === 'general' ? 'bg-white text-pasuruan-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="flex-1 py-2 px-3 rounded-lg transition-all flex items-center justify-center gap-2">
            <i class="fa-solid fa-gear text-sky-500"></i>
            <span>3.2 Parameter Operasional</span>
        </button>

        <button type="button" @click="activeGroup = 'evaluation'" :class="activeGroup === 'evaluation' ? 'bg-white text-pasuruan-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="flex-1 py-2 px-3 rounded-lg transition-all flex items-center justify-center gap-2">
            <i class="fa-solid fa-graduation-cap text-emerald-500"></i>
            <span>3.3 Remedial & JP</span>
        </button>

        <button type="button" @click="activeGroup = 'contact'" :class="activeGroup === 'contact' ? 'bg-white text-pasuruan-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="flex-1 py-2 px-3 rounded-lg transition-all flex items-center justify-center gap-2">
            <i class="fa-solid fa-headset text-purple-500"></i>
            <span>3.4 Kontak & Helpdesk</span>
        </button>

        <button type="button" @click="activeGroup = 'queue'" :class="activeGroup === 'queue' ? 'bg-white text-pasuruan-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="flex-1 py-2 px-3 rounded-lg transition-all flex items-center justify-center gap-2">
            <i class="fa-solid fa-envelope-circle-check text-rose-500"></i>
            <span>3.5 Antrean & Kuota Email</span>
            @if($pendingJobsCount > 0)
                <span class="px-1.5 py-0.5 rounded-full text-[9px] bg-rose-500 text-white font-extrabold">{{ $pendingJobsCount }}</span>
            @endif
        </button>
    </div>

    <!-- Settings Form Container -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-xs">
        <form x-show="activeGroup !== 'queue'" action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
            @csrf

            <!-- 1. GROUP: BRANDING & IDENTITAS VISUAL (PRD 3.1) -->
            <div x-show="activeGroup === 'branding'" class="space-y-5">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-sm text-slate-900">Identitas Visual & Branding Daerah</h3>
                    <p class="text-xs text-slate-500">Kustomisasi nama instansi, moto, logo resmi, dan teks pengumuman berjalan</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Aplikasi</label>
                        <input type="text" name="app_name" value="{{ \App\Models\SystemSetting::get('app_name') }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Motto / Tagline Instansi</label>
                        <input type="text" name="app_tagline" value="{{ \App\Models\SystemSetting::get('app_tagline') }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                        Pengumuman Teks Berjalan (Running Marquee Announcement)
                    </label>
                    <textarea name="marquee_announcement" rows="2" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none">{{ \App\Models\SystemSetting::get('marquee_announcement') }}</textarea>
                    <p class="text-[11px] text-slate-400 mt-1">Ditampilkan di bilah atas portal publik untuk informasi mendesak atau pembukaan pendaftaran.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Teks Footer & Hak Cipta</label>
                    <textarea name="footer_copyright" rows="2" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none">{{ \App\Models\SystemSetting::get('footer_copyright') }}</textarea>
                </div>
            </div>

            <!-- 2. GROUP: PARAMETER OPERASIONAL & SAKELAR SISTEM (PRD 3.2) -->
            <div x-show="activeGroup === 'general'" x-cloak class="space-y-5">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-sm text-slate-900">Parameter Operasional & Sakelar Sistem</h3>
                    <p class="text-xs text-slate-500">Kontrol operasional global dan status pemeliharaan</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Standar 1 Jam Pelajaran (JP) dalam Menit</label>
                        <input type="number" name="jp_duration_minutes" value="{{ \App\Models\SystemSetting::get('jp_duration_minutes', 45) }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none font-mono">
                        <p class="text-[11px] text-slate-400 mt-1">Default standar kedinasan: 45 menit.</p>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800">Mode Pemeliharaan (Maintenance)</span>
                            <input type="checkbox" name="maintenance_mode" value="1" {{ \App\Models\SystemSetting::get('maintenance_mode') ? 'checked' : '' }} class="rounded text-pasuruan-700 h-4 w-4">
                        </div>
                        <p class="text-[11px] text-slate-500">Jika aktif, portal publik akan menampilkan halaman pemeliharaan berkala.</p>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800">Buka Registrasi Peserta Umum</span>
                            <input type="checkbox" name="allow_public_registration" value="1" {{ \App\Models\SystemSetting::get('allow_public_registration', true) ? 'checked' : '' }} class="rounded text-pasuruan-700 h-4 w-4">
                        </div>
                        <p class="text-[11px] text-slate-500">Mengizinkan peserta non-ASN (masyarakat umum) mendaftar ke webinar terbuka.</p>
                    </div>
                </div>
            </div>

            <!-- 3. GROUP: REGULASI REMEDIAL & EVALUASI (PRD 4.3) -->
            <div x-show="activeGroup === 'evaluation'" x-cloak class="space-y-5">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-sm text-slate-900">Parameter Evaluasi & Plafon Nilai Remedial</h3>
                    <p class="text-xs text-slate-500">Konfigurasi passing grade dan score capping sesuai regulasi</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Default Passing Grade</label>
                        <input type="number" name="default_passing_grade" value="{{ \App\Models\SystemSetting::get('default_passing_grade', 75) }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none font-mono">
                        <p class="text-[11px] text-slate-400 mt-1">Batas nilai kelulusan evaluasi (Default: 75).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Maksimal Kesempatan Remedial</label>
                        <input type="number" name="default_max_remedial" value="{{ \App\Models\SystemSetting::get('default_max_remedial', 5) }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none font-mono">
                        <p class="text-[11px] text-slate-400 mt-1">Maksimal pengerjaan ulang (Default: 5x).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Plafon Skor Remedial (Score Capping)</label>
                        <input type="number" name="default_remedial_cap_score" value="{{ \App\Models\SystemSetting::get('default_remedial_cap_score', 90) }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none font-mono font-bold text-amber-700">
                        <p class="text-[11px] text-slate-400 mt-1">Nilai remedial tidak boleh melebihi plafon ini (Default: 90).</p>
                    </div>
                </div>

                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 leading-relaxed">
                    <i class="fa-solid fa-info-circle text-amber-600 mr-1"></i>
                    <code>Skor Akhir Remedial = min(Skor, Remedial Cap)</code>. Jika peserta remedial memperoleh skor 96, nilai yang dicatat sistem otomatis adalah 90.
                </div>
            </div>

            <!-- 4. GROUP: KONTAK & HELPDESK (PRD 3.2) -->
            <div x-show="activeGroup === 'contact'" x-cloak class="space-y-5">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-sm text-slate-900">Kontak Layanan Bantuan (Helpdesk)</h3>
                    <p class="text-xs text-slate-500">Pusat kontak resmi BKPSDM yang ditampilkan pada seluruh antarmuka portal</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nomor WhatsApp Helpdesk</label>
                        <input type="text" name="helpdesk_whatsapp" value="{{ \App\Models\SystemSetting::get('helpdesk_whatsapp') }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Email Resmi Layanan</label>
                        <input type="email" name="helpdesk_email" value="{{ \App\Models\SystemSetting::get('helpdesk_email') }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Alamat Kantor BKPSDM Kabupaten Pasuruan</label>
                    <textarea name="office_address" rows="2" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none">{{ \App\Models\SystemSetting::get('office_address') }}</textarea>
                </div>
            </div>

            <!-- Action Save Button -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-400">Perubahan akan langsung tercermin di seluruh portal tanpa reload server.</span>
                <button type="submit" class="px-6 py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Konfigurasi Dinamis</span>
                </button>
            </div>

        </form>

        <!-- 5. GROUP: ANTREAN & KUOTA EMAIL (GMAIL SMTP GUARD) -->
        <div x-show="activeGroup === 'queue'" x-cloak class="space-y-6">
            <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-envelope-circle-check text-rose-600"></i>
                        <span>Sistem Antrean Email & Penjaga Kuota Gmail (Queue Worker)</span>
                    </h3>
                    <p class="text-xs text-slate-500">Mencegah email gagal akibat limitasi harian (500 email/hari) dan pembatasan kecepatan akun Google gratis.</p>
                </div>
                <span class="px-3 py-1 bg-sky-50 text-sky-800 text-[11px] font-bold rounded-xl border border-sky-200 shrink-0">
                    Driver: {{ config('queue.default') }} (Tabel MySQL: jobs)
                </span>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200">
                    <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider block">Antrean Menunggu</span>
                    <div class="text-2xl font-black text-amber-900 mt-1">{{ $pendingJobsCount }}</div>
                    <span class="text-[10px] text-amber-700">Pesan belum terkirim / tertunda</span>
                </div>

                <div class="p-4 rounded-2xl bg-rose-50/70 border border-rose-200">
                    <span class="text-[11px] font-bold text-rose-800 uppercase tracking-wider block">Pekerjaan Gagal (Failed)</span>
                    <div class="text-2xl font-black text-rose-900 mt-1">{{ $failedJobsCount }}</div>
                    <span class="text-[10px] text-rose-700">Melebihi batas maksimal coba ulang</span>
                </div>

                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider block">Akun Pengirim Gmail</span>
                    <div class="text-xs font-bold text-slate-900 mt-1 truncate" title="{{ config('mail.mailers.smtp.username') }}">
                        {{ config('mail.mailers.smtp.username') ?: 'Belum Dikonfigurasi' }}
                    </div>
                    <span class="text-[10px] text-emerald-600 font-semibold">Port {{ config('mail.mailers.smtp.port', 465) }} (SSL Aktif)</span>
                </div>
            </div>

            <!-- Action Buttons: Manual Process & Retry Failed -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <div class="space-y-0.5">
                    <h4 class="text-xs font-bold text-slate-800">Proses Cepat Antrean (Web Trigger)</h4>
                    <p class="text-[11px] text-slate-500">Kirimkan seluruh email yang sedang mengantre sekarang juga tanpa menunggu jadwal otomatis.</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    @if($failedJobsCount > 0)
                        <form action="{{ route('admin.queue.retry_failed') }}" method="POST">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-rotate-right"></i>
                                <span>Coba Ulang Gagal ({{ $failedJobsCount }})</span>
                            </button>
                        </form>
                    @endif

                    <form action="{{ route('admin.queue.process') }}" method="POST">
                        @csrf
                        <button type="submit" data-loading-text="Memproses Antrean..." class="px-4 py-2 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                            <i class="fa-solid fa-paper-plane text-amber-300"></i>
                            <span>Kirim Antrean Sekarang</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- How It Works & Google Free Quota Protection Explanation -->
            <div class="p-5 rounded-2xl bg-indigo-50/60 border border-indigo-200/80 text-xs space-y-3">
                <div class="font-bold text-indigo-950 flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-indigo-600 text-base"></i>
                    <span>Cara Kerja Perlindungan Kuota Akun Google Gratis di LMS Pasuruan:</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-[11px] text-slate-700">
                    <div class="space-y-1 bg-white/70 p-3.5 rounded-xl border border-indigo-100">
                        <p class="font-bold text-slate-900">1. Urutan Antrean Terjamin (FIFO):</p>
                        <p class="leading-relaxed">Setiap permintaan verifikasi dan token kata sandi disimpan ke tabel antrean MySQL berdasarkan waktu request. Pengiriman diproses satu per satu secara berurutan.</p>
                    </div>
                    <div class="space-y-1 bg-white/70 p-3.5 rounded-xl border border-indigo-100">
                        <p class="font-bold text-slate-900">2. Otomatis Menunda Saat Kuota Habis:</p>
                        <p class="leading-relaxed">Jika server Gmail mengembalikan pesan batasan kuota (550 daily quota exceeded atau rate limit), sistem tidak membatalkan email, melainkan menundanya dan mencobanya kembali secara mandiri.</p>
                    </div>
                    <div class="space-y-1 bg-white/70 p-3.5 rounded-xl border border-indigo-100">
                        <p class="font-bold text-slate-900">3. Masa Berlaku Token Tidak Hangus:</p>
                        <p class="leading-relaxed">Waktu pembuatan token di-refresh tepat saat email siap dikirim, sehingga peserta tetap memperoleh waktu penuh 60 menit untuk membuka link verifikasi saat email diterima.</p>
                    </div>
                    <div class="space-y-1 bg-white/70 p-3.5 rounded-xl border border-indigo-100">
                        <p class="font-bold text-slate-900">4. Menjalankan Worker di Background (Windows):</p>
                        <p class="leading-relaxed">Untuk memproses email secara otomatis setiap saat, Anda dapat menjalankan file <code>jalankan-antrean-email.bat</code> di folder aplikasi atau mengaktifkan cron Task Scheduler.</p>
                    </div>
                </div>
            </div>

            <!-- Table: Pending Queue Items Preview -->
            <div class="space-y-2">
                <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fa-solid fa-list-ol text-slate-500"></i>
                    <span>10 Antrean Teratas Saat Ini:</span>
                </h4>
                @if($pendingJobs->isEmpty())
                    <div class="p-6 text-center text-xs text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <i class="fa-solid fa-check-circle text-2xl text-emerald-500 mb-1 block"></i>
                        Antrean saat ini bersih (tidak ada email yang tertunda).
                    </div>
                @else
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                <tr>
                                    <th class="py-2.5 px-3">No. Job ID</th>
                                    <th class="py-2.5 px-3">Nama Antrean</th>
                                    <th class="py-2.5 px-3">Percobaan</th>
                                    <th class="py-2.5 px-3">Waktu Masuk</th>
                                    <th class="py-2.5 px-3">Jadwal Kirim</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-[11px]">
                                @foreach($pendingJobs as $j)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-2 px-3 font-mono font-bold text-pasuruan-800">#{{ $j->id }}</td>
                                    <td class="py-2 px-3">{{ $j->queue }}</td>
                                    <td class="py-2 px-3">
                                        <span class="px-2 py-0.5 rounded-full {{ $j->attempts > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }} font-bold">
                                            {{ $j->attempts }} kali
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 text-slate-500">{{ date('d M Y H:i:s', $j->created_at) }}</td>
                                    <td class="py-2 px-3 text-slate-600">{{ date('d M Y H:i:s', $j->available_at) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
