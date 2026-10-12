@extends('layouts.dashboard')

@section('title', 'Kelola Menu Sidebar - LMS BKPSDM Pasuruan')
@section('header_title', 'Manajemen Visibilitas Menu Sidebar per Role')

@section('content')
<div class="space-y-6" x-data="{ resetModalOpen: false }">

    <!-- Top Action & Banner Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-pasuruan-900 via-pasuruan-800 to-sky-900 p-6 rounded-2xl text-white shadow-md relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 opacity-10 text-9xl text-white pointer-events-none">
            <i class="fa-solid fa-list-check"></i>
        </div>
        
        <div class="space-y-1 relative z-10">
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-amber-400/20 text-amber-300 text-xs font-bold border border-amber-400/30 mb-1">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Fitur Khusus Super Admin</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black tracking-tight">Pengaturan Visibilitas Menu Sidebar</h2>
            <p class="text-xs sm:text-sm text-sky-200 max-w-2xl leading-relaxed">
                Atur dan kendalikan menu navigasi apa saja yang dapat dilihat (*tampil*) atau disembunyikan (*hide*) untuk setiap peran pengguna di LMS BKPSDM Kabupaten Pasuruan.
            </p>
        </div>

        <div class="flex items-center gap-3 relative z-10 shrink-0">
            <button type="button" @click="resetModalOpen = true" class="inline-flex items-center gap-2 px-4 py-2.5 bg-rose-600/90 hover:bg-rose-600 text-white rounded-xl text-xs font-bold shadow-sm transition-all hover:scale-105 border border-rose-400/30">
                <i class="fa-solid fa-rotate-left"></i>
                <span>Reset ke Default</span>
            </button>
        </div>
    </div>

    <!-- Role Selection Tabs -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-xs p-2">
        <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 scrollbar-none">
            @foreach($roles as $roleKey => $roleLabel)
                @php
                    $roleMenuCount = isset($menusByRole[$roleKey]) ? $menusByRole[$roleKey]->count() : 0;
                    $roleVisibleCount = isset($menusByRole[$roleKey]) ? $menusByRole[$roleKey]->where('is_visible', true)->count() : 0;
                    $isActive = ($activeRole === $roleKey);
                @endphp
                <a href="{{ route('admin.sidebar_menu.index', ['role' => $roleKey]) }}"
                   class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ $isActive ? 'bg-pasuruan-700 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    @if($roleKey === 'peserta')
                        <i class="fa-solid fa-user-graduate {{ $isActive ? 'text-amber-300' : 'text-slate-400' }}"></i>
                    @elseif($roleKey === 'super_admin')
                        <i class="fa-solid fa-crown {{ $isActive ? 'text-amber-300' : 'text-slate-400' }}"></i>
                    @elseif($roleKey === 'penyelenggara')
                        <i class="fa-solid fa-headset {{ $isActive ? 'text-amber-300' : 'text-slate-400' }}"></i>
                    @elseif($roleKey === 'widyaiswara')
                        <i class="fa-solid fa-chalkboard-user {{ $isActive ? 'text-amber-300' : 'text-slate-400' }}"></i>
                    @elseif($roleKey === 'mentor')
                        <i class="fa-solid fa-user-tie {{ $isActive ? 'text-amber-300' : 'text-slate-400' }}"></i>
                    @else
                        <i class="fa-solid fa-globe {{ $isActive ? 'text-amber-300' : 'text-slate-400' }}"></i>
                    @endif
                    <span>{{ $roleLabel }}</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-mono {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-200/80 text-slate-700' }}">
                        {{ $roleVisibleCount }}/{{ $roleMenuCount }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Active Role Content & Menu List Form -->
    @php
        $currentMenus = $menusByRole[$activeRole] ?? collect();
        $totalCurrent = $currentMenus->count();
        $totalVisible = $currentMenus->where('is_visible', true)->count();
        $totalHidden = $totalCurrent - $totalVisible;
    @endphp

    <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
        
        <!-- Header Info Card -->
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/60">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h3 class="text-base font-extrabold text-slate-900">
                        Menu Sidebar untuk Role: <span class="text-pasuruan-700 font-black underline decoration-sky-400 decoration-2 underline-offset-4">{{ $roles[$activeRole] }}</span>
                    </h3>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Centang atau aktifkan sakelar untuk menampilkan menu pada sidebar. Matikan sakelar untuk menyembunyikan menu dari pandangan pengguna role ini.
                </p>
            </div>

            <!-- Stats Mini Badges -->
            <div class="flex items-center gap-2 shrink-0">
                <div class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>Tampil: <strong>{{ $totalVisible }}</strong></span>
                </div>
                <div class="px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-600 text-xs font-bold flex items-center gap-1.5">
                    <i class="fa-solid fa-eye-slash text-slate-400"></i>
                    <span>Sembunyi: <strong>{{ $totalHidden }}</strong></span>
                </div>
            </div>
        </div>

        <!-- Form Update Visibility -->
        <form action="{{ route('admin.sidebar_menu.update') }}" method="POST">
            @csrf
            <input type="hidden" name="role" value="{{ $activeRole }}">

            <div class="divide-y divide-slate-100">
                @forelse($currentMenus as $index => $menu)
                    <div class="p-5 flex items-center justify-between gap-4 hover:bg-slate-50/80 transition-colors {{ !$menu->is_visible ? 'bg-slate-50/40 opacity-75' : '' }}">
                        
                        <div class="flex items-center gap-4">
                            <!-- Icon Box -->
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-base shrink-0 shadow-xs {{ $menu->is_visible ? 'bg-pasuruan-50 text-pasuruan-700 border border-pasuruan-200' : 'bg-slate-100 text-slate-400 border border-slate-200' }}">
                                <i class="{{ $menu->icon ?: 'fa-solid fa-cube' }}"></i>
                            </div>

                            <!-- Menu Label & Details -->
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="text-sm font-extrabold text-slate-800 {{ !$menu->is_visible ? 'text-slate-500 line-through' : '' }}">
                                        {{ $menu->menu_label }}
                                    </h4>
                                    @if($menu->menu_group)
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-100 text-slate-600 border border-slate-200 uppercase tracking-wider">
                                            {{ $menu->menu_group }}
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3 text-[11px] text-slate-400">
                                    <span class="font-mono text-slate-500">Key: <code class="text-pasuruan-700 bg-pasuruan-50 px-1.5 py-0.5 rounded">{{ $menu->menu_key }}</code></span>
                                    @if($menu->route_name)
                                        <span>•</span>
                                        <span class="font-mono">Route: <code>{{ $menu->route_name }}</code></span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Toggle Switch -->
                        <div class="flex items-center gap-3 shrink-0" x-data="{ isVisible: {{ $menu->is_visible ? 'true' : 'false' }} }">
                            <span class="text-xs font-bold hidden sm:inline-block transition-colors"
                                  :class="isVisible ? 'text-emerald-700' : 'text-slate-400'"
                                  x-text="isVisible ? 'Tampil (Aktif)' : 'Disembunyikan'">
                            </span>

                            <!-- Modern Switch -->
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" 
                                       name="menu_visibility[{{ $menu->menu_key }}]" 
                                       value="1" 
                                       class="sr-only peer"
                                       x-model="isVisible"
                                       {{ $menu->is_visible ? 'checked' : '' }}>
                                <div class="w-12 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600 shadow-inner"></div>
                            </label>
                        </div>

                    </div>
                @empty
                    <div class="p-12 text-center text-slate-400">
                        <i class="fa-solid fa-folder-open text-4xl mb-2 text-slate-300"></i>
                        <p class="text-sm font-semibold">Belum ada data menu untuk role ini.</p>
                    </div>
                @endforelse
            </div>

            <!-- Bottom Sticky Save Bar -->
            <div class="p-5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <p class="text-xs text-slate-500 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-info text-sky-600"></i>
                    <span>Perubahan akan langsung berlaku pada bilah samping navigasi pengguna saat halaman disegarkan.</span>
                </p>

                <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-xl shadow-md transition-all hover:scale-105 shrink-0">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Pengaturan Menu {{ $roles[$activeRole] }}</span>
                </button>
            </div>
        </form>

    </div>

    <!-- Modal Konfirmasi Reset ke Default -->
    <div x-show="resetModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="resetModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="resetModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="resetModalOpen" x-transition.scale.origin.bottom.duration.200ms class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100">
                <form action="{{ route('admin.sidebar_menu.reset') }}" method="POST">
                    @csrf
                    <div class="p-6">
                        <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4 text-xl">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <h3 class="text-lg font-extrabold text-slate-900 text-center mb-2">Kembalikan ke Pengaturan Awal?</h3>
                        <p class="text-xs text-slate-600 text-center leading-relaxed">
                            Tindakan ini akan mengembalikan seluruh visibilitas menu sidebar untuk <strong>SEMUA ROLE</strong> ke konfigurasi standar sistem (semua menu diaktifkan/tampil). Apakah Anda yakin ingin melanjutkan?
                        </p>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-slate-100">
                        <button type="button" @click="resetModalOpen = false" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 text-xs font-bold rounded-xl hover:bg-slate-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors">
                            Ya, Reset Semua Menu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
