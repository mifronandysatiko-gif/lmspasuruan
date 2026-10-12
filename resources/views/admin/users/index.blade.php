@extends('layouts.dashboard')

@section('title', 'Data Pegawai & Manajemen Pengguna - BKPSDM Pasuruan')
@section('header_title', 'Data Pegawai ASN & Manajemen Pengguna Sistem')

@section('content')
<div class="space-y-6" x-data="{ 
    createModalOpen: {{ $errors->any() ? 'true' : 'false' }},
    importModalOpen: false,
    roleModalOpen: false,
    editModalOpen: false,
    selectedUser: {
        id: null,
        name: '',
        nip: '',
        nik: '',
        email: '',
        role: '',
        unit_kerja: '',
        jabatan: '',
        pangkat_golongan: '',
        phone_number: '',
        gelar_depan: '',
        gelar_belakang: ''
    },
    openRoleModal(usr) {
        this.selectedUser = usr;
        this.roleModalOpen = true;
    },
    openEditModal(usr) {
        this.selectedUser = {
            id: usr.id,
            name: usr.name,
            nip: usr.nip || '',
            nik: usr.profile ? (usr.profile.nik || '') : '',
            email: usr.email,
            role: usr.role,
            unit_kerja: usr.profile ? (usr.profile.unit_kerja || '') : '',
            jabatan: usr.profile ? (usr.profile.jabatan || '') : '',
            pangkat_golongan: usr.profile ? (usr.profile.pangkat_golongan || '') : '',
            phone_number: usr.profile ? (usr.profile.phone_number || '') : '',
            gelar_depan: usr.profile ? (usr.profile.gelar_depan || '') : '',
            gelar_belakang: usr.profile ? (usr.profile.gelar_belakang || '') : ''
        };
        this.editModalOpen = true;
    }
}">

    <!-- Top Action & Breadcrumb -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <span>Tata Kelola Sistem</span>
                <span>/</span>
                <span class="text-slate-600 font-medium">Data Pegawai</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900">Basis Data Pegawai & Pengguna</h2>
            <p class="text-xs text-slate-500">Kelola akun aparatur ASN Pemkab Pasuruan, tetapkan hak akses role, atau sinkronkan dengan API SIMPEG.</p>
        </div>

        <div class="flex items-center gap-3 shrink-0 flex-wrap sm:flex-nowrap">
            <a href="{{ route('admin.api_sync.index') }}" class="px-4 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold rounded-xl border border-emerald-300 transition-colors flex items-center gap-2 shadow-2xs">
                <i class="fa-solid fa-network-wired text-emerald-600"></i>
                <span>Tarik / Sinkronkan API SIMPEG</span>
            </a>

            <button @click="importModalOpen = true" class="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2">
                <i class="fa-solid fa-file-excel text-emerald-300 text-sm"></i>
                <span>+ Import Akun Excel</span>
            </button>

            <button @click="createModalOpen = true" class="px-4 py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-amber-400"></i>
                <span>+ Tambah Pegawai Baru</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="p-3.5 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-400 block">Total Pengguna</span>
            <div class="text-xl font-black text-slate-900 mt-1">{{ $stats['total_users'] }}</div>
            <span class="text-[10px] text-slate-500">Semua akun</span>
        </div>

        <div class="p-3.5 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-400 block">Aparatur ASN</span>
            <div class="text-xl font-black text-sky-700 mt-1">{{ $stats['total_asn'] }}</div>
            <span class="text-[10px] text-sky-600">Terdaftar NIP</span>
        </div>

        <div class="p-3.5 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-400 block">Penyelenggara</span>
            <div class="text-xl font-black text-purple-700 mt-1">{{ $stats['total_penyelenggara'] }}</div>
            <span class="text-[10px] text-purple-600">Panitia Diklat</span>
        </div>

        <div class="p-3.5 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-400 block">Widyaiswara / Coach</span>
            <div class="text-xl font-black text-emerald-700 mt-1">{{ $stats['total_widyaiswara'] }}</div>
            <span class="text-[10px] text-emerald-600">Pengampu Modul</span>
        </div>

        <div class="p-3.5 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-400 block">Mentor (Atasan)</span>
            <div class="text-xl font-black text-amber-700 mt-1">{{ $stats['total_mentor'] }}</div>
            <span class="text-[10px] text-amber-600">Penilai Logbook</span>
        </div>

        <div class="p-3.5 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-400 block">Peserta Umum</span>
            <div class="text-xl font-black text-slate-600 mt-1">{{ $stats['total_umum'] }}</div>
            <span class="text-[10px] text-slate-400">Non-ASN (Webinar)</span>
        </div>
    </div>

    <!-- Filters & Search Form -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
            <!-- Search -->
            <div class="sm:col-span-5 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari NIP, nama pegawai, email, atau jabatan..." class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
            </div>

            <!-- Role Filter -->
            <div class="sm:col-span-3">
                <select name="role" onchange="this.form.submit()" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 font-semibold text-slate-700">
                    <option value="all" {{ $roleFilter === 'all' ? 'selected' : '' }}>-- Semua Peran (Role) --</option>
                    <option value="super_admin" {{ $roleFilter === 'super_admin' ? 'selected' : '' }}>Super Admin BKPSDM</option>
                    <option value="penyelenggara" {{ $roleFilter === 'penyelenggara' ? 'selected' : '' }}>Penyelenggara Diklat</option>
                    <option value="widyaiswara" {{ $roleFilter === 'widyaiswara' ? 'selected' : '' }}>Widyaiswara / Coach</option>
                    <option value="mentor" {{ $roleFilter === 'mentor' ? 'selected' : '' }}>Mentor (Atasan Langsung)</option>
                    <option value="peserta_asn" {{ $roleFilter === 'peserta_asn' ? 'selected' : '' }}>Peserta ASN Pasuruan</option>
                    <option value="peserta_umum" {{ $roleFilter === 'peserta_umum' ? 'selected' : '' }}>Peserta Umum (Non-ASN)</option>
                </select>
            </div>

            <!-- OPD Filter -->
            <div class="sm:col-span-3">
                <select name="opd" onchange="this.form.submit()" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 font-semibold text-slate-700 truncate">
                    <option value="all" {{ $opdFilter === 'all' ? 'selected' : '' }}>-- Semua Unit Kerja / OPD --</option>
                    @foreach($opdList as $opdName)
                        <option value="{{ $opdName }}" {{ $opdFilter === $opdName ? 'selected' : '' }}>{{ $opdName }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Submit Filter Button -->
            <div class="sm:col-span-1 flex items-center">
                <button type="submit" class="w-full py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold rounded-xl text-center shadow-2xs transition-colors">
                    <i class="fa-solid fa-filter"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Table of Users / Pegawai -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-bold uppercase text-[10px] tracking-wider">
                        <th class="py-3.5 px-4">Pegawai ASN / User</th>
                        <th class="py-3.5 px-4">Unit Kerja & Jabatan</th>
                        <th class="py-3.5 px-4">Kontak</th>
                        <th class="py-3.5 px-4">Hak Akses (Role)</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-center">Kelola Akses & Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $usr)
                        @php
                            $prof = $usr->profile;
                            $roleBadges = [
                                'super_admin' => ['label' => 'Super Admin', 'class' => 'bg-rose-50 text-rose-700 border-rose-200'],
                                'penyelenggara' => ['label' => 'Penyelenggara', 'class' => 'bg-purple-50 text-purple-700 border-purple-200'],
                                'widyaiswara' => ['label' => 'Widyaiswara', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                                'mentor' => ['label' => 'Mentor', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
                                'peserta' => match($prof?->kategori_peserta) {
                                    'asn_pasuruan' => ['label' => 'ASN Pasuruan', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                                    'asn_luar' => ['label' => 'ASN Luar', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
                                    default => $usr->nip ? ['label' => 'Peserta ASN', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'] : ['label' => 'Peserta Umum', 'class' => 'bg-slate-100 text-slate-600 border-slate-300'],
                                },
                            ];
                            $badge = $roleBadges[$usr->role] ?? ['label' => $usr->role, 'class' => 'bg-slate-100 text-slate-700'];
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <!-- Pegawai / User -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-pasuruan-700 text-white font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ substr($usr->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-900">{{ $prof?->formatted_full_name ?? $usr->name }}</h4>
                                        <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                            <span class="text-[11px] text-slate-600 font-mono font-semibold">{{ $usr->nip ? 'NIP: ' . $usr->nip : 'Non-ASN' }}</span>
                                            @if($prof?->nik)
                                                <span class="text-[10px] text-slate-500 font-mono bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200" title="Nomor Induk Kependudukan">
                                                    NIK: {{ $prof->nik }}
                                                </span>
                                            @endif
                                            @if($prof?->sync_source === 'data_pegawai')
                                                <span class="px-1.5 py-0.2 bg-emerald-100 text-emerald-800 text-[9px] font-bold rounded">
                                                    Master Pegawai
                                                </span>
                                            @elseif($prof?->sync_source === 'api_simpeg')
                                                <span class="px-1.5 py-0.2 bg-sky-100 text-sky-800 text-[9px] font-bold rounded">
                                                    SIMPEG API
                                                </span>
                                            @else
                                                <span class="px-1.5 py-0.2 bg-slate-100 text-slate-600 text-[9px] font-medium rounded">
                                                    Lokal
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Unit Kerja & Jabatan -->
                            <td class="py-3.5 px-4 max-w-xs">
                                <p class="font-semibold text-slate-800 truncate">{{ $prof?->unit_kerja ?? 'Pemerintah Kabupaten Pasuruan' }}</p>
                                <p class="text-[11px] text-slate-500 truncate">{{ $prof?->jabatan ?? '-' }}</p>
                                @if($prof?->pangkat_golongan)
                                    <span class="inline-block text-[10px] text-pasuruan-700 font-medium bg-pasuruan-50 px-1.5 py-0.5 rounded border border-pasuruan-200 mt-0.5">
                                        {{ $prof->pangkat_golongan }}
                                    </span>
                                @endif
                            </td>

                            <!-- Kontak -->
                            <td class="py-3.5 px-4">
                                <div class="space-y-0.5">
                                    <p class="text-[11px] text-slate-700 font-medium flex items-center gap-1.5">
                                        <i class="fa-solid fa-envelope text-slate-400 text-[10px]"></i>
                                        <span class="truncate max-w-[170px]">{{ $usr->email }}</span>
                                    </p>
                                    @if($prof?->phone_number)
                                        <p class="text-[11px] text-slate-500 flex items-center gap-1.5">
                                            <i class="fa-solid fa-phone text-slate-400 text-[10px]"></i>
                                            <span>{{ $prof->phone_number }}</span>
                                        </p>
                                    @endif
                                </div>
                            </td>

                            <!-- Hak Akses Role -->
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $badge['class'] }}">
                                    <i class="fa-solid fa-shield-halved text-[10px]"></i>
                                    {{ $badge['label'] }}
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4">
                                @if($usr->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Nonaktif
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Tombol Atur Role -->
                                    <button type="button" 
                                            @click="openRoleModal({
                                                id: {{ $usr->id }},
                                                name: '{{ addslashes($usr->name) }}',
                                                nip: '{{ $usr->nip ?? '-' }}',
                                                role: '{{ $usr->role }}'
                                            })"
                                            title="Atur Hak Akses / Role Pengguna"
                                            class="px-2.5 py-1.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-[11px] rounded-lg shadow-2xs transition-colors flex items-center gap-1">
                                        <i class="fa-solid fa-user-shield text-amber-300"></i>
                                        <span>Atur Hak Akses</span>
                                    </button>

                                    <!-- Tombol Edit Data -->
                                    <button type="button"
                                            @click="openEditModal({
                                                id: {{ $usr->id }},
                                                name: '{{ addslashes($usr->name) }}',
                                                nip: '{{ $usr->nip ?? '' }}',
                                                email: '{{ $usr->email }}',
                                                unit_kerja: '{{ addslashes($prof?->unit_kerja ?? '') }}',
                                                jabatan: '{{ addslashes($prof?->jabatan ?? '') }}',
                                                pangkat_golongan: '{{ addslashes($prof?->pangkat_golongan ?? '') }}',
                                                phone_number: '{{ addslashes($prof?->phone_number ?? '') }}',
                                                gelar_depan: '{{ addslashes($prof?->gelar_depan ?? '') }}',
                                                gelar_belakang: '{{ addslashes($prof?->gelar_belakang ?? '') }}'
                                            })"
                                            title="Edit Profil"
                                            class="p-1.5 text-slate-500 hover:text-pasuruan-700 hover:bg-slate-100 rounded-lg transition-colors">
                                        <i class="fa-solid fa-pencil"></i>
                                    </button>

                                    <!-- Toggle Status Aktif/Nonaktif -->
                                    <form action="{{ route('admin.users.toggle_status', $usr->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                onclick="return confirm('Apakah Anda yakin ingin mengubah status keaktifan akun {{ addslashes($usr->name) }}?')"
                                                title="{{ $usr->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}"
                                                class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-slate-100 rounded-lg transition-colors">
                                            <i class="fa-solid {{ $usr->is_active ? 'fa-user-slash text-slate-400 hover:text-rose-600' : 'fa-user-check text-emerald-600' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-500">
                                <i class="fa-solid fa-users-slash text-3xl text-slate-300 mb-2"></i>
                                <p class="font-bold text-xs text-slate-700">Tidak ada data pegawai yang sesuai dengan filter pencarian.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Links -->
        <div class="p-4 border-t border-slate-100">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Modal 1: Tambah Pegawai Baru (Input Manual ke Basis Data Lokal) -->
    <div x-show="createModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5 text-pasuruan-800 font-bold text-base">
                    <i class="fa-solid fa-user-plus text-amber-500"></i>
                    <span>Tambah Pegawai Baru (Input Manual Basis Data LMS)</span>
                </div>
                <button @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            @if ($errors->any())
                <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 space-y-1">
                    <span class="font-bold flex items-center gap-1.5"><i class="fa-solid fa-triangle-exclamation text-rose-600"></i> Terjadi kesalahan pengisian data:</span>
                    <ul class="list-disc list-inside space-y-0.5 text-[11px]">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4 text-xs" x-data="{ createRole: 'peserta' }">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">Nomor Induk Pegawai (NIP)</label>
                        <input type="text" name="nip" placeholder="Contoh: 199001012015031001" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 font-mono">
                        <p class="text-[10px] text-slate-400">Kosongkan jika mendaftarkan Peserta Umum Non-ASN.</p>
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">NIK (16 Digit KTP)</label>
                        <input type="text" name="nik" maxlength="20" placeholder="Contoh: 3514xxxxxxxxxxxx" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 font-mono">
                        <p class="text-[10px] text-slate-400">Nomor Induk Kependudukan peserta.</p>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Nama Lengkap (Tanpa Gelar) <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: Budi Santoso" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">Gelar Depan</label>
                        <input type="text" name="gelar_depan" placeholder="Contoh: Dr. / Ir." class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                    </div>
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">Gelar Belakang</label>
                        <input type="text" name="gelar_belakang" placeholder="Contoh: S.STP, M.Si" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">Email Resmi / Aktif <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" required placeholder="budi@pasuruankab.go.id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">Nomor Telepon / WhatsApp</label>
                        <input type="text" name="phone_number" placeholder="081234567890" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Perangkat Daerah / OPD / Unit Kerja <span class="text-rose-500">*</span></label>
                    <input type="text" name="unit_kerja" required placeholder="Contoh: Badan Kepegawaian dan Pengembangan SDM" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">Jabatan Pegawai <span class="text-rose-500">*</span></label>
                        <input type="text" name="jabatan" required placeholder="Contoh: Analis SDM Aparatur Ahli Pertama" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">Pangkat / Golongan</label>
                        <input type="text" name="pangkat_golongan" placeholder="Contoh: Penata Muda / III-a" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Penetapan Peran Awal (Hak Akses) <span class="text-rose-500">*</span></label>
                    <select name="role" x-model="createRole" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 font-bold text-pasuruan-700">
                        <option value="peserta">Peserta Pembelajaran (ASN / Umum)</option>
                        <option value="penyelenggara">Penyelenggara Diklat (Operator Event & Pelatihan)</option>
                        <option value="widyaiswara">Widyaiswara / Coach (Pengampu Pembelajaran & Penilai)</option>
                        <option value="mentor">Mentor (Atasan Langsung)</option>
                        <option value="super_admin">Super Admin BKPSDM (Tata Kelola Penuh)</option>
                    </select>
                </div>

                <!-- Box Username & Password Khusus Akun Selain Peserta (Penyelenggara, Coach, Mentor, Super Admin) -->
                <div x-show="createRole !== 'peserta'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 bg-amber-50/70 border border-amber-200 rounded-xl animate-in fade-in duration-200">
                    <div class="space-y-1">
                        <label class="font-bold text-slate-800 flex items-center justify-between">
                            <span class="text-amber-950 font-bold">Username Login <span class="text-rose-500">*</span></span>
                            <span class="text-[10px] text-amber-700 font-semibold">Wajib Non-Peserta</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 font-mono text-xs">@</span>
                            <input type="text" name="username" :required="createRole !== 'peserta'" maxlength="50" placeholder="Contoh: coach.andi / mentor_budi" class="w-full pl-8 pr-3 py-2 bg-white border border-amber-300 rounded-lg focus:outline-none focus:border-amber-500 font-mono text-xs font-semibold">
                        </div>
                        <p class="text-[10px] text-slate-500">Username unik untuk login selain NIP.</p>
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-slate-800 flex items-center justify-between">
                            <span class="text-amber-950 font-bold">Kata Sandi (Password) <span class="text-rose-500">*</span></span>
                            <span class="text-[10px] text-amber-700 font-semibold">Min. 6 Karakter</span>
                        </label>
                        <input type="text" name="password" :required="createRole !== 'peserta'" minlength="6" placeholder="Masukkan password akun" class="w-full px-3 py-2 bg-white border border-amber-300 rounded-lg focus:outline-none focus:border-amber-500 text-xs">
                        <p class="text-[10px] text-slate-500">Password akses untuk pengguna peran ini.</p>
                    </div>
                </div>

                <!-- Keterangan Khusus Akun Peserta -->
                <div x-show="createRole === 'peserta'" class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-[11px] text-slate-600 flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-info text-sky-600 text-xs mt-0.5 shrink-0"></i>
                    <div class="space-y-0.5 leading-relaxed">
                        <span class="font-bold text-slate-800">Akses Masuk (Login) Akun Peserta:</span>
                        <p>Untuk akun dengan peran <strong>Peserta</strong>, pegawai/peserta dapat langsung login menggunakan <strong>NIP</strong> atau <strong>Akun Google</strong> tanpa perlu pengaturan username & password manual.</p>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" data-loading-text="Menyimpan Pegawai..." class="px-5 py-2 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-floppy-disk text-amber-300"></i>
                        <span>Simpan Data Pegawai</span>
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- Modal 2: Atur Hak Akses / Role Pengguna (Role Assignment) -->
    <div x-show="roleModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2 text-pasuruan-800 font-bold text-sm">
                    <i class="fa-solid fa-user-shield text-amber-500"></i>
                    <span>Atur Hak Akses (Role Assignment)</span>
                </div>
                <button @click="roleModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Target User Info -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1">
                <div class="flex items-center justify-between">
                    <span class="font-extrabold text-slate-900" x-text="selectedUser.name"></span>
                    <span class="text-[11px] font-mono text-slate-500" x-text="'NIP: ' + selectedUser.nip"></span>
                </div>
                <p class="text-[11px] text-slate-500">Pilih hak akses baru untuk akun pengguna ini. Perubahan akan segera berlaku pada sesi berikutnya.</p>
            </div>

            <form :action="'{{ url('/admin/users') }}/' + selectedUser.id + '/role'" method="POST" class="space-y-4 text-xs">
                @csrf

                <div class="space-y-2">
                    <label class="font-bold text-slate-700">Pilih Peran / Tingkat Otoritas:</label>

                    <label class="p-3 rounded-xl border border-slate-200 flex items-start gap-3 cursor-pointer hover:bg-slate-50 transition-colors" :class="selectedUser.role === 'peserta' ? 'border-pasuruan-600 bg-pasuruan-50/50' : ''">
                        <input type="radio" name="role" value="peserta" x-model="selectedUser.role" class="mt-0.5 text-pasuruan-700">
                        <div>
                            <span class="font-bold text-slate-900 block">Peserta Pembelajaran</span>
                            <span class="text-[11px] text-slate-500">Akses modul pembelajaran mandiri, ruang kelas webinar, pengerjaan kuis, dan pengunduhan sertifikat.</span>
                        </div>
                    </label>

                    <label class="p-3 rounded-xl border border-slate-200 flex items-start gap-3 cursor-pointer hover:bg-slate-50 transition-colors" :class="selectedUser.role === 'penyelenggara' ? 'border-purple-600 bg-purple-50/50' : ''">
                        <input type="radio" name="role" value="penyelenggara" x-model="selectedUser.role" class="mt-0.5 text-purple-700">
                        <div>
                            <span class="font-bold text-purple-900 block">Penyelenggara Diklat (Operator Event)</span>
                            <span class="text-[11px] text-slate-500">Mengelola kegiatan webinar (live switch), mengunggah materi modul/video sesi, dan memantau rekap hasil belajar.</span>
                        </div>
                    </label>

                    <label class="p-3 rounded-xl border border-slate-200 flex items-start gap-3 cursor-pointer hover:bg-slate-50 transition-colors" :class="selectedUser.role === 'widyaiswara' ? 'border-emerald-600 bg-emerald-50/50' : ''">
                        <input type="radio" name="role" value="widyaiswara" x-model="selectedUser.role" class="mt-0.5 text-emerald-700">
                        <div>
                            <span class="font-bold text-emerald-900 block">Widyaiswara / Coach (Pengampu)</span>
                            <span class="text-[11px] text-slate-500">Menelaah berkas tugas peserta, memberi nilai kelulusan modul, dan membimbing aksi perubahan kepemimpinan.</span>
                        </div>
                    </label>

                    <label class="p-3 rounded-xl border border-slate-200 flex items-start gap-3 cursor-pointer hover:bg-slate-50 transition-colors" :class="selectedUser.role === 'mentor' ? 'border-amber-600 bg-amber-50/50' : ''">
                        <input type="radio" name="role" value="mentor" x-model="selectedUser.role" class="mt-0.5 text-amber-700">
                        <div>
                            <span class="font-bold text-amber-900 block">Mentor (Atasan Langsung)</span>
                            <span class="text-[11px] text-slate-500">Memberikan persetujuan (*approval*) terhadap logbook mingguan dan milestone aksi perubahan staf bawahannya.</span>
                        </div>
                    </label>

                    <label class="p-3 rounded-xl border border-slate-200 flex items-start gap-3 cursor-pointer hover:bg-slate-50 transition-colors" :class="selectedUser.role === 'super_admin' ? 'border-rose-600 bg-rose-50/50' : ''">
                        <input type="radio" name="role" value="super_admin" x-model="selectedUser.role" class="mt-0.5 text-rose-700">
                        <div>
                            <span class="font-bold text-rose-900 block">Super Admin BKPSDM</span>
                            <span class="text-[11px] text-slate-500">Otoritas tertinggi sistem: manajemen pegawai, integrasi API PINTAR, konfigurasi no-code, dan audit trail.</span>
                        </div>
                    </label>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" @click="roleModalOpen = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" data-loading-text="Menyimpan Role..." class="px-5 py-2 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-check text-amber-300"></i>
                        <span>Simpan Penetapan Role</span>
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- Modal 3: Edit Data Pegawai -->
    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2 text-pasuruan-800 font-bold text-sm">
                    <i class="fa-solid fa-user-pen text-amber-500"></i>
                    <span>Sunting Data Profil Pegawai</span>
                </div>
                <button @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form :action="'{{ url('/admin/users') }}/' + selectedUser.id" method="POST" class="space-y-4 text-xs">
                @csrf

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">NIP Pegawai</label>
                        <input type="text" name="nip" x-model="selectedUser.nip" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 font-mono">
                    </div>
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">NIK (16 Digit KTP)</label>
                        <input type="text" name="nik" x-model="selectedUser.nik" maxlength="20" placeholder="Contoh: 3514xxxxxxxxxxxx" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500 font-mono">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Nama Lengkap (Tanpa Gelar) <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="selectedUser.name" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">Gelar Depan</label>
                        <input type="text" name="gelar_depan" x-model="selectedUser.gelar_depan" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                    </div>
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">Gelar Belakang</label>
                        <input type="text" name="gelar_belakang" x-model="selectedUser.gelar_belakang" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">Email Pegawai</label>
                        <input type="email" name="email" x-model="selectedUser.email" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                    </div>
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">Nomor Telepon</label>
                        <input type="text" name="phone_number" x-model="selectedUser.phone_number" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700">Perangkat Daerah / Unit Kerja</label>
                    <input type="text" name="unit_kerja" x-model="selectedUser.unit_kerja" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">Jabatan Pegawai</label>
                        <input type="text" name="jabatan" x-model="selectedUser.jabatan" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                    </div>
                    <div class="space-y-1">
                        <label class="font-bold text-slate-700">Pangkat / Golongan</label>
                        <input type="text" name="pangkat_golongan" x-model="selectedUser.pangkat_golongan" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:border-pasuruan-500">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" data-loading-text="Memperbarui Data..." class="px-5 py-2 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-floppy-disk text-amber-300"></i>
                        <span>Perbarui Data</span>
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- Modal 4: Import Akun dari Excel / CSV -->
    <div x-show="importModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="importModalOpen = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-xl bg-white rounded-3xl p-6 shadow-2xl space-y-5" @click.away="importModalOpen = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg">
                            <i class="fa-solid fa-file-excel"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-base">Import Akun dari Excel</h3>
                            <p class="text-xs text-slate-500">Sinkronisasi data master pegawai dan buat akun pengguna LMS.</p>
                        </div>
                    </div>
                    <button @click="importModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Template Download Card -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="space-y-0.5">
                        <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <i class="fa-solid fa-download text-pasuruan-700"></i>
                            <span>Unduh Template Excel Resmi</span>
                        </div>
                        <p class="text-[11px] text-slate-500">Gunakan format kolom baku untuk menghindari kegagalan proses import.</p>
                    </div>
                    <a href="{{ route('admin.users.template') }}" class="px-3.5 py-2 bg-pasuruan-700 hover:bg-pasuruan-800 text-white text-xs font-bold rounded-xl shadow-2xs transition-colors shrink-0 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-file-arrow-down text-amber-300"></i>
                        <span>Download (.xlsx)</span>
                    </a>
                </div>

                <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-200/80 text-[11px] text-amber-900 space-y-1.5">
                    <div class="font-bold flex items-center gap-1.5 text-amber-950">
                        <i class="fa-solid fa-circle-info text-amber-600"></i>
                        <span>Petunjuk & Ketentuan Import:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-slate-700 ml-1">
                        <li>Kolom didukung: <strong>NIP, NIK, NAMA_LENGKAP, GELAR_DEPAN, GELAR_BELAKANG, EMAIL, NO_HP, UNIT_KERJA, JABATAN, PANGKAT_GOLONGAN, ROLE, USERNAME, PASSWORD</strong>.</li>
                        <li>Baris dengan NIP otomatis disimpan/diperbarui ke tabel <em>Data Pegawai ASN</em>.</li>
                        <li>Untuk peserta ASN baru, kata sandi awal default adalah NIP mereka (atau password123 jika non-NIP).</li>
                        <li>Peserta yang baru diimpor <strong>wajib melengkapi profil dan memperbarui kata sandi</strong> saat pertama kali login sebelum dapat memilih menu sidebar lainnya.</li>
                    </ul>
                </div>

                <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                    @csrf
                    <div class="space-y-1.5">
                        <label class="font-bold text-slate-700 block">Pilih File Excel / CSV <span class="text-rose-500">*</span></label>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                               class="block w-full text-xs text-slate-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer border border-slate-200 rounded-xl p-1 bg-slate-50">
                        <span class="text-[10px] text-slate-400">Maksimum ukuran file: 10 MB (.xlsx, .xls, .csv).</span>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button type="button" @click="importModalOpen = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 font-bold rounded-xl transition-colors">
                            Batal
                        </button>
                        <button type="submit" data-loading-text="Mengimpor Data..." class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                            <i class="fa-solid fa-cloud-arrow-up text-amber-300"></i>
                            <span>Mulai Import Data</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
