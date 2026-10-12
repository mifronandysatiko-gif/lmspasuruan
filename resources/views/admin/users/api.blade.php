@extends('layouts.dashboard')

@section('title', 'Integrasi API Sumber Data Pegawai - BKPSDM Pasuruan')
@section('header_title', 'Integrasi API Sumber Data Pegawai & Pengguna')

@section('content')
<div class="space-y-6" x-data="{ 
    selectedMode: '{{ $apiEnabled ? 'api' : 'local' }}',
    apiEnabled: {{ $apiEnabled ? 'true' : 'false' }},
    syncing: false,
    testing: false,
    testResult: null,
    apiUrl: '{{ $apiUrl }}',
    apiAuthType: '{{ $apiAuthType }}',
    apiToken: '{{ $apiToken }}',
    batchLimit: 500,
    startPage: 1,
    pagesToFetch: 'all',

    // Live Interactive Full Sync State
    liveSyncModal: false,
    liveSyncRunning: false,
    liveSyncPaused: false,
    liveSyncFinished: false,
    liveCurrentPage: 1,
    liveTotalPages: 33,
    liveTotalRecords: 16383,
    liveProcessedRecords: 0,
    liveProgressPercent: 0,
    liveStatusText: '',
    liveLogs: [],

    async startLiveFullSync(startFromPage = 1) {
        this.liveSyncModal = true;
        this.liveSyncRunning = true;
        this.liveSyncPaused = false;
        this.liveSyncFinished = false;
        this.liveCurrentPage = startFromPage;
        if (startFromPage === 1) {
            this.liveProcessedRecords = 0;
            this.liveProgressPercent = 0;
            this.liveLogs = [];
        }
        this.addLog(`🚀 Memulai sinkronisasi seluruh data dari halaman ${startFromPage}...`);

        while (this.liveSyncRunning && !this.liveSyncPaused) {
            this.liveStatusText = `Sedang mengambil & memproses data halaman ${this.liveCurrentPage}...`;
            try {
                const res = await fetch('{{ route('admin.api_sync.batch') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        page: this.liveCurrentPage,
                        limit: this.batchLimit
                    })
                });

                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    throw new Error(errData.message || `HTTP ${res.status}`);
                }

                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.message || 'Gagal memproses batch data.');
                }

                if (data.total_records) {
                    this.liveTotalRecords = data.total_records;
                }
                if (data.total_pages) {
                    this.liveTotalPages = data.total_pages;
                }

                this.liveProcessedRecords += data.batch_count;
                if (this.liveTotalRecords > 0) {
                    this.liveProgressPercent = Math.min(100, Math.round((this.liveProcessedRecords / this.liveTotalRecords) * 100));
                }

                this.addLog(`✅ Halaman ${this.liveCurrentPage}: ${data.pegawai_count} pegawai disinkronkan (${this.liveProcessedRecords.toLocaleString('id-ID')} total).`);

                if (!data.has_more || !data.next_page) {
                    this.liveProgressPercent = 100;
                    this.liveSyncRunning = false;
                    this.liveSyncFinished = true;
                    this.liveStatusText = `🎉 Selesai! Sebanyak ${this.liveProcessedRecords.toLocaleString('id-ID')} data pegawai telah berhasil disinkronkan.`;
                    this.addLog(`🏁 Sinkronisasi penuh selesai sempurna.`);
                    break;
                }

                this.liveCurrentPage = data.next_page;
            } catch (err) {
                this.addLog(`❌ Kendala pada halaman ${this.liveCurrentPage}: ${err.message}`);
                this.liveStatusText = `Terjadi kendala pada halaman ${this.liveCurrentPage}: ${err.message}`;
                this.liveSyncPaused = true;
                this.liveSyncRunning = false;
                break;
            }
        }
    },

    pauseLiveSync() {
        this.liveSyncPaused = true;
        this.liveSyncRunning = false;
        this.addLog(`⏸️ Sinkronisasi dijeda pada halaman ${this.liveCurrentPage}.`);
    },

    resumeLiveSync() {
        this.liveSyncRunning = true;
        this.liveSyncPaused = false;
        this.startLiveFullSync(this.liveCurrentPage);
    },

    addLog(msg) {
        const time = new Date().toLocaleTimeString('id-ID');
        this.liveLogs.unshift(`[${time}] ${msg}`);
        if (this.liveLogs.length > 50) this.liveLogs.pop();
    },

    async testApi() {
        this.testing = true;
        this.testResult = null;
        try {
            const res = await fetch('{{ route('admin.api_sync.test') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    simpeg_api_url: this.apiUrl,
                    simpeg_api_auth_type: this.apiAuthType,
                    simpeg_api_token: this.apiToken
                })
            });
            const data = await res.json();
            this.testResult = data;
        } catch (err) {
            this.testResult = {
                success: false,
                message: 'Gagal menghubungi server lokal untuk pengujian koneksi: ' + err.message
            };
        } finally {
            this.testing = false;
        }
    }
}">

    <!-- Top Breadcrumbs & Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.users.index') }}" class="hover:text-pasuruan-700 font-semibold">&larr; Basis Data Pegawai</a>
                <span>/</span>
                <span class="text-slate-600 font-medium">Integrasi API Eksternal</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900">Pilihan Sumber Basis Data Pegawai</h2>
            <p class="text-xs text-slate-500">Tentukan apakah LMS menggunakan basis data mandiri lokal atau menempelkan API dari aplikasi kepegawaian lain (SIMPEG / SIASN / Satu Data).</p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 transition-colors flex items-center gap-2">
                <i class="fa-solid fa-users text-pasuruan-700"></i>
                <span>Lihat Daftar Pegawai ({{ $totalAsn }})</span>
            </a>
        </div>
    </div>

    <!-- Pilihan Mode Sumber Data Pegawai (Dual-Source Toggle Banner) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <!-- Opsi 1: Basis Data Mandiri Lokal -->
        <div @click="selectedMode = 'local'; apiEnabled = false" 
             class="p-5 rounded-2xl border-2 transition-all cursor-pointer relative bg-white"
             :class="selectedMode === 'local' ? 'border-pasuruan-600 shadow-md ring-2 ring-pasuruan-600/10' : 'border-slate-200 hover:border-slate-300'">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl bg-pasuruan-50 text-pasuruan-700 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-database"></i>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold" :class="selectedMode === 'local' ? 'bg-pasuruan-700 text-white' : 'bg-slate-100 text-slate-500'">
                    <span x-text="selectedMode === 'local' ? 'Mode Terpilih' : 'Pilihan 1'"></span>
                </span>
            </div>
            <h3 class="text-sm font-bold text-slate-900 mt-3">Opsi 1: Buat & Kelola Basis Data Sendiri di LMS</h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                Super Admin menginput data pegawai secara manual satu per satu atau import internal langsung ke basis data MySQL LMS Kabupaten Pasuruan. Cocok jika instansi belum memiliki integrasi API terpusat.
            </p>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px]">
                <span class="text-slate-400">Total Akun Input Mandiri:</span>
                <span class="font-extrabold text-pasuruan-700">{{ $totalLocal }} Pegawai</span>
            </div>
        </div>

        <!-- Opsi 2: Sinkronisasi API Eksternal (SIMPEG / SIASN) -->
        <div @click="selectedMode = 'api'; apiEnabled = true" 
             class="p-5 rounded-2xl border-2 transition-all cursor-pointer relative bg-white"
             :class="selectedMode === 'api' ? 'border-emerald-600 shadow-md ring-2 ring-emerald-600/10' : 'border-slate-200 hover:border-slate-300'">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-network-wired"></i>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold" :class="selectedMode === 'api' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500'">
                    <span x-text="selectedMode === 'api' ? 'Mode Terpilih' : 'Pilihan 2 (Rekomendasi)'"></span>
                </span>
            </div>
            <h3 class="text-sm font-bold text-slate-900 mt-3">Opsi 2: Tempelkan API dari Aplikasi Lain (SIMPEG / SIASN)</h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                LMS secara otomatis menarik dan menyinkronkan data profil seluruh pegawai ASN melalui tautan API JSON terenkripsi dari aplikasi SIMPEG BKPSDM atau BKN SIASN secara berkala.
            </p>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px]">
                <span class="text-slate-400">Total Master Pegawai SIMPEG Tersinkron:</span>
                <span class="font-extrabold text-emerald-700">{{ $totalMasterPegawai }} Pegawai ({{ $totalSynced }} Akun LMS)</span>
            </div>
        </div>

    </div>

    <!-- Main Workspace: Form Konfigurasi API & Eksekusi Sinkronisasi -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Kolom Kiri: Form Konfigurasi API Eksternal -->
        <div class="lg:col-span-7 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2 text-pasuruan-800 font-bold text-sm">
                        <i class="fa-solid fa-gears text-amber-500"></i>
                        <span>Konfigurasi Sambungan API Aplikasi Eksternal</span>
                    </div>
                    <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg {{ $apiEnabled ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                        {{ $apiEnabled ? 'Integrasi Aktif' : 'Integrasi Nonaktif' }}
                    </span>
                </div>

                <form action="{{ route('admin.api_sync.settings') }}" method="POST" class="space-y-4 text-xs">
                    @csrf

                    <!-- Sakelar Mode API -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-800 block">Aktifkan Penggunaan Sumber Data API</span>
                            <span class="text-[11px] text-slate-500">Gunakan API eksternal sebagai sumber rujukan akun pegawai ASN</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="simpeg_api_enabled" value="1" x-model="apiEnabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-pasuruan-700"></div>
                        </label>
                    </div>

                    <!-- URL Endpoint API -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="font-bold text-slate-700">URL Endpoint API Eksternal <span class="text-rose-500">*</span></label>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="apiUrl = 'https://satudatakormi.my.id/api/external/data/api'; apiAuthType = 'apikey'; apiToken = 'pntr_live_4a3ae74640a3f581a3b07c571f3dad20'" class="text-[10px] text-emerald-700 font-bold hover:underline">
                                    Setel API Satu Data Pasuruan
                                </button>
                                <span class="text-slate-300">|</span>
                                <button type="button" @click="apiUrl = 'http://127.0.0.1:8000/api/external-simpeg/mock-pegawai'; apiAuthType = 'bearer'; apiToken = 'PASURUAN-SIMPEG-SECURE-KEY-2026'" class="text-[10px] text-pasuruan-700 font-bold hover:underline">
                                    Demo Lokal
                                </button>
                            </div>
                        </div>
                        <div class="relative">
                            <input type="url" name="simpeg_api_url" x-model="apiUrl" required placeholder="https://satudatakormi.my.id/api/external/data/api" class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 font-mono text-xs">
                            <i class="fa-solid fa-link absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        </div>
                        <p class="text-[10px] text-slate-400">
                            Mendukung JSON format Satu Data Kormi (<code class="bg-slate-100 px-1 py-0.5 rounded text-slate-700">nama_lengkap</code>, <code class="bg-slate-100 px-1 py-0.5 rounded text-slate-700">unit_kerja_nama</code>) maupun SIMPEG standard (<code class="bg-slate-100 px-1 py-0.5 rounded text-slate-700">nama</code>, <code class="bg-slate-100 px-1 py-0.5 rounded text-slate-700">opd</code>).
                        </p>
                    </div>

                    <!-- Tipe Otentikasi -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="font-bold text-slate-700">Metode Keamanan Otentikasi <span class="text-rose-500">*</span></label>
                            <select name="simpeg_api_auth_type" x-model="apiAuthType" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 font-semibold text-slate-700">
                                <option value="apikey">API Key (Header: X-API-KEY)</option>
                                <option value="bearer">Bearer Token (Header: Authorization)</option>
                                <option value="basic">Basic Auth (Username & Password)</option>
                                <option value="none">Tanpa Otentikasi (Public Endpoint)</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="font-bold text-slate-700">Token Rahasia / API Secret Key</label>
                            <div class="relative">
                                <input type="password" name="simpeg_api_token" x-model="apiToken" placeholder="Masukkan kunci API rahasia..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 font-mono text-xs">
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi Form -->
                    <div class="pt-2 flex flex-wrap items-center justify-between gap-3">
                        <button type="button" 
                                @click="testApi()" 
                                :disabled="testing"
                                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors flex items-center gap-2 border border-slate-300 text-xs disabled:opacity-50">
                            <i class="fa-solid fa-circle-notch fa-spin text-emerald-600" x-show="testing" x-cloak></i>
                            <i class="fa-solid fa-plug text-emerald-600" x-show="!testing"></i>
                            <span x-text="testing ? 'Menguji Sambungan...' : 'Uji Koneksi Endpoint'"></span>
                        </button>

                        <button type="submit" data-loading-text="Menyimpan Konfigurasi..." class="px-5 py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2">
                            <i class="fa-solid fa-floppy-disk text-amber-300"></i>
                            <span>Simpan Konfigurasi Sambungan API</span>
                        </button>
                    </div>

                    <!-- Kotak Hasil Pengujian API -->
                    <template x-if="testResult">
                        <div class="mt-4 p-4 rounded-xl border transition-all text-xs"
                             :class="testResult.success ? 'bg-emerald-50/80 border-emerald-300 text-emerald-900' : 'bg-rose-50 border-rose-300 text-rose-900'">
                            <div class="flex items-start gap-2.5">
                                <i class="fa-solid text-base mt-0.5" :class="testResult.success ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-rose-600'"></i>
                                <div class="space-y-1">
                                    <h4 class="font-bold" x-text="testResult.success ? 'Sambungan API Berhasil Terhubung!' : 'Koneksi API Gagal'"></h4>
                                    <p class="text-[11px]" x-text="testResult.message"></p>
                                    <template x-if="testResult.total_records !== undefined">
                                        <div class="mt-2 text-[11px] font-semibold flex items-center gap-2">
                                            <span class="bg-white/80 px-2.5 py-1 rounded-lg border border-emerald-300 text-emerald-800">
                                                <i class="fa-solid fa-users mr-1"></i>
                                                Terdeteksi <strong x-text="Number(testResult.total_records).toLocaleString('id-ID')"></strong> Data Pegawai Tersedia
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                </form>
            </div>
        </div>

        <!-- Kolom Kanan: Eksekusi Sinkronisasi & Info Endpoint -->
        <div class="lg:col-span-5 space-y-6">
            
            <!-- Kartu Eksekusi Sinkronisasi Instan -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-bold text-sm text-slate-900">Eksekusi Sinkronisasi Data Pegawai</h3>
                    <span class="flex items-center gap-1.5 text-[11px] font-bold text-emerald-600">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                        <span>Siap Sinkron</span>
                    </span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Terakhir Disinkronkan:</span>
                        <span class="font-bold text-slate-800 font-mono">{{ $lastSynced ?? 'Belum Pernah' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Master Data Pegawai (SIMPEG):</span>
                        <span class="font-extrabold text-emerald-700 font-mono">{{ number_format($totalMasterPegawai, 0, ',', '.') }} Pegawai</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Akun ASN Terdaftar LMS:</span>
                        <span class="font-extrabold text-pasuruan-700 font-mono">{{ number_format($totalAsn, 0, ',', '.') }} Akun</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Akun Hasil Sinkronisasi API:</span>
                        <span class="font-bold text-sky-700 font-mono">{{ number_format($totalSynced, 0, ',', '.') }} Akun</span>
                    </div>
                </div>

                <!-- Rekomendasi Sinkronisasi Penuh Semua Data (Live Modal) -->
                <div class="p-4 rounded-xl bg-emerald-50/60 bg-gradient-to-br from-emerald-500/10 via-emerald-50 to-teal-50 border border-emerald-200/80 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-600 text-white uppercase tracking-wider shadow-xs">
                                <i class="fa-solid fa-bolt text-amber-300"></i> Rekomendasi Utama
                            </span>
                            <h4 class="font-bold text-xs text-slate-800 mt-1.5">Tarik Seluruh Data Pegawai Sekaligus</h4>
                            <p class="text-[11px] text-slate-600 leading-snug mt-0.5">
                                Menarik dan menyinkronkan seluruh <strong>~16.383 data pegawai</strong> dari API secara otomatis halaman per halaman dilengkapi <em>live visual progress bar</em>, log aktivitas realtime, dan tombol jeda/lanjut.
                            </p>
                        </div>
                    </div>

                    <button type="button" 
                            @click="startLiveFullSync(1)" 
                            :disabled="liveSyncRunning"
                            class="w-full py-3 px-4 bg-emerald-600 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center justify-center gap-2 text-xs cursor-pointer disabled:opacity-50">
                        <i class="fa-solid fa-cloud-arrow-down text-amber-300 text-sm"></i>
                        <span>⚡ Tarik Seluruh Data Sekaligus (Semua 16.383 Pegawai)</span>
                    </button>
                </div>

                <div class="relative flex py-1 items-center">
                    <div class="flex-grow border-t border-slate-200"></div>
                    <span class="shrink mx-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Atau Tarik Manual / Terjadwal</span>
                    <div class="flex-grow border-t border-slate-200"></div>
                </div>

                <!-- Form Sinkronisasi Cepat -->
                <form action="{{ route('admin.api_sync.now') }}" method="POST" @submit="syncing = true" class="space-y-3">
                    @csrf

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="space-y-1">
                            <label class="font-semibold text-slate-600 text-[11px]">Pilihan Jumlah Tarikan:</label>
                            <select name="pages_to_fetch" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-700 text-xs">
                                <option value="all" selected>⚡ Semua Data (Full Auto-Loop)</option>
                                <option value="1">500 Pegawai (1 Halaman)</option>
                                <option value="2">1.000 Pegawai (2 Halaman)</option>
                                <option value="4">2.000 Pegawai (4 Halaman)</option>
                                <option value="10">5.000 Pegawai (10 Halaman)</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="font-semibold text-slate-600 text-[11px]">Mulai Dari Halaman:</label>
                            <input type="number" name="page" min="1" value="1" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-700 text-xs">
                        </div>
                    </div>

                    <input type="hidden" name="limit" value="500">

                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        Data pegawai dari API otomatis di-<em>upsert</em> ke <strong>Master Data Pegawai BKPSDM</strong> dan dibuatkan akun LMS dengan aman tanpa menduplikasi data atau mereset kata sandi akun yang telah aktif.
                    </p>

                    <button type="submit" 
                            :disabled="syncing"
                            class="w-full py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center justify-center gap-2 text-xs disabled:opacity-50">
                        <i class="fa-solid fa-rotate text-amber-300" :class="syncing ? 'animate-spin' : ''"></i>
                        <span x-text="syncing ? 'Sedang Memproses Sinkronisasi Server...' : 'Jalankan Sinkronisasi Form Standard'"></span>
                    </button>
                </form>

                <!-- Info CLI Background Sync -->
                <div class="p-3 bg-slate-900 rounded-xl text-slate-300 text-[11px] space-y-1">
                    <div class="flex items-center gap-1.5 text-amber-400 font-bold">
                        <i class="fa-solid fa-terminal text-[10px]"></i>
                        <span>Opsi Sinkronisasi Penuh di Terminal Server:</span>
                    </div>
                    <p class="text-slate-400 text-[10px]">Untuk menarik seluruh 16.383 data tanpa batas waktu peramban web:</p>
                    <code class="block font-mono text-emerald-400 bg-slate-950 p-2 rounded text-[10.5px]">php artisan simpeg:sync --all</code>
                </div>

            </div>

            <!-- Kartu Spesifikasi Format JSON API -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-3">
                <div class="flex items-center gap-2 text-xs font-bold text-slate-800">
                    <i class="fa-solid fa-code text-pasuruan-700"></i>
                    <span>Format Payload JSON yang Diterima & Didukung:</span>
                </div>
                
                <div class="bg-slate-900 rounded-xl p-3.5 text-[10.5px] font-mono text-emerald-400 overflow-x-auto leading-relaxed">
<pre>{
  "data": [
    {
      "nip": "196810092025211009",
      "nama_lengkap": "PAITO KUNAIFI",
      "gelar_depan": "",
      "gelar_belakang": "",
      "email": "paitoaliando@gmail.com",
      "unit_kerja_nama": "SDN PANDAAN II KECAMATAN PANDAAN",
      "jabatan_nama": "PENGELOLA LAYANAN OPERASIONAL",
      "golongan": "GOLONGAN I",
      "no_hp": "81330543266"
    }
  ]
}</pre>
                </div>

                <div class="text-[10px] text-slate-400 flex items-center justify-between">
                    <span>Mendukung varian kunci: <code>nama_lengkap</code>, <code>nama</code>, <code>unit_kerja_nama</code>, <code>opd</code></span>
                </div>
            </div>

        </div>

    <!-- Modal Interaktif Live Sinkronisasi Penuh Seluruh Data Pegawai -->
    <div x-show="liveSyncModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/75 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="relative bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-2xl p-6 sm:p-8 space-y-6 overflow-hidden"
             @click.outside="if(!liveSyncRunning) liveSyncModal = false">
             
            <!-- Header Modal -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-600 bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center text-xl font-bold shadow-md shadow-emerald-500/20">
                        <i class="fa-solid fa-cloud-arrow-down" :class="liveSyncRunning ? 'animate-bounce' : ''"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-extrabold text-slate-900">Sinkronisasi Penuh Seluruh Data Pegawai</h3>
                            <span x-show="liveSyncRunning" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping mr-1"></span> Berjalan
                            </span>
                            <span x-show="liveSyncPaused && !liveSyncFinished" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                <i class="fa-solid fa-pause text-[9px] mr-1"></i> Dijeda
                            </span>
                            <span x-show="liveSyncFinished" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 text-teal-800">
                                <i class="fa-solid fa-check text-[9px] mr-1"></i> Selesai
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Menarik dan memperbarui seluruh data ASN dari API ke Master Pegawai LMS</p>
                    </div>
                </div>

                <template x-if="!liveSyncRunning">
                    <button type="button" 
                            @click="liveSyncModal = false" 
                            class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </template>
            </div>

            <!-- Progress Bar Section -->
            <div class="space-y-3.5 bg-slate-50 p-5 rounded-2xl border border-slate-200/80">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-700 flex items-center gap-2">
                        <i class="fa-solid fa-spinner fa-spin text-emerald-600 text-sm" x-show="liveSyncRunning"></i>
                        <span x-text="liveStatusText || 'Mempersiapkan penarikan data pegawai...'"></span>
                    </span>
                    <span class="font-mono font-black text-emerald-700 text-base" x-text="liveProgressPercent + '%'"></span>
                </div>

                <!-- Progress Bar Track -->
                <div class="w-full h-4 bg-slate-200/90 rounded-full overflow-hidden p-0.5 shadow-inner">
                    <div class="h-full bg-emerald-500 bg-gradient-to-r from-emerald-500 via-teal-500 to-emerald-600 rounded-full transition-all duration-300 relative shadow-sm"
                         :style="'width: ' + liveProgressPercent + '%; min-width: ' + (liveProgressPercent > 0 ? '8px' : '0px') + '; background: linear-gradient(90deg, #10b981 0%, #0d9488 100%);'">
                        <div class="absolute inset-0 bg-white/25 rounded-full animate-pulse"></div>
                    </div>
                </div>

                <!-- Statistik Angka -->
                <div class="grid grid-cols-3 gap-3 pt-1 text-center">
                    <div class="bg-white p-3 rounded-xl border border-slate-200/90 shadow-2xs">
                        <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider">Pegawai Terproses</span>
                        <span class="text-sm sm:text-base font-extrabold text-slate-900 font-mono" x-text="liveProcessedRecords.toLocaleString('id-ID')"></span>
                    </div>
                    <div class="bg-white p-3 rounded-xl border border-slate-200/90 shadow-2xs">
                        <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider">Total Target API</span>
                        <span class="text-sm sm:text-base font-extrabold text-emerald-700 font-mono" x-text="liveTotalRecords.toLocaleString('id-ID') + ' Data'"></span>
                    </div>
                    <div class="bg-white p-3 rounded-xl border border-slate-200/90 shadow-2xs">
                        <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider">Halaman Berjalan</span>
                        <span class="text-sm sm:text-base font-extrabold text-sky-700 font-mono" x-text="liveCurrentPage + ' / ' + liveTotalPages"></span>
                    </div>
                </div>
            </div>

            <!-- Terminal Log Window -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-xs font-bold text-slate-700">
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-terminal text-slate-400"></i>
                        <span>Aktivitas & Log Batch Realtime:</span>
                    </span>
                    <span class="text-[10px] font-normal text-slate-400">Pembaruan otomatis tiap batch halaman</span>
                </div>
                <div class="bg-slate-950 text-emerald-400 font-mono text-[11px] p-4 rounded-xl h-44 overflow-y-auto space-y-1.5 border border-slate-800 scrollbar-thin">
                    <template x-if="liveLogs.length === 0">
                        <p class="text-slate-500 italic">Menunggu proses dimulai...</p>
                    </template>
                    <template x-for="(log, idx) in liveLogs" :key="idx">
                        <div class="leading-relaxed border-b border-slate-900/60 pb-1" x-text="log"></div>
                    </template>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <div>
                    <template x-if="liveSyncRunning">
                        <button type="button" 
                                @click="pauseLiveSync()" 
                                class="px-4 py-2.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 font-bold text-xs rounded-xl transition-colors flex items-center gap-2 cursor-pointer">
                            <i class="fa-solid fa-pause"></i>
                            <span>Jeda Sinkronisasi</span>
                        </button>
                    </template>

                    <template x-if="liveSyncPaused && !liveSyncFinished">
                        <button type="button" 
                                @click="resumeLiveSync()" 
                                class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-2 cursor-pointer">
                            <i class="fa-solid fa-play"></i>
                            <span>Lanjutkan Sinkronisasi</span>
                        </button>
                    </template>
                </div>

                <div class="flex items-center gap-2">
                    <template x-if="liveSyncFinished">
                        <button type="button" 
                                @click="window.location.reload()" 
                                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center gap-2 cursor-pointer">
                            <i class="fa-solid fa-arrows-rotate"></i>
                            <span>Selesai & Muat Ulang Halaman</span>
                        </button>
                    </template>

                    <template x-if="!liveSyncRunning && !liveSyncFinished">
                        <button type="button" 
                                @click="liveSyncModal = false" 
                                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors cursor-pointer">
                            <span>Tutup</span>
                        </button>
                    </template>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
