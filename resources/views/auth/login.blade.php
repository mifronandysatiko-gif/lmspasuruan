@extends('layouts.app')

@section('title', 'Masuk Akun - LMS Kabupaten Pasuruan')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        
        <!-- Header Form -->
        <div class="text-center">
            <picture>
                <source srcset="{{ asset('images/logo-pasuruan.webp') }}" type="image/webp">
                <img src="{{ asset('images/logo-pasuruan.png') }}" alt="Logo Kabupaten Pasuruan" class="h-16 w-auto mx-auto object-contain mb-3 drop-shadow-md">
            </picture>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                Masuk ke Portal LMS
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                BKPSDM Pemerintah Kabupaten Pasuruan
            </p>
        </div>

        <!-- Form Login Standar -->
        <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm space-y-5">
            
            @if (session('success'))
                <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-sm mt-0.5 shrink-0"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-sm mt-0.5 shrink-0"></i>
                    <div>
                        <div class="font-bold">Gagal Masuk:</div>
                        <ul class="list-disc list-inside mt-0.5 space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Tombol Masuk dengan Google Single Sign-On -->
            <div>
                <a href="{{ route('auth.google') }}" class="w-full py-3 px-4 bg-white hover:bg-slate-50 border border-slate-300 hover:border-slate-400 rounded-xl text-xs font-bold text-slate-700 shadow-2xs hover:shadow transition-all flex items-center justify-center gap-3 group">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span class="group-hover:text-slate-900 font-semibold">Masuk dengan Akun Google</span>
                </a>

                <div class="relative flex py-3.5 items-center">
                    <div class="flex-grow border-t border-slate-200"></div>
                    <span class="flex-shrink mx-3 text-[11px] text-slate-400 font-semibold uppercase tracking-wider">atau dengan akun terdaftar</span>
                    <div class="flex-grow border-t border-slate-200"></div>
                </div>
            </div>

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        NIP / Username / Email <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-user text-sm"></i>
                        </div>
                        <input type="text" name="identifier" required placeholder="Masukkan NIP, Username, atau Email Anda" value="{{ old('identifier') }}"
                               class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pasuruan-700 focus:border-transparent transition-all">
                    </div>
                </div>

                <div x-data="{ showPassword: false }">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Kata Sandi <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </div>
                        <input :type="showPassword ? 'text' : 'password'" 
                               type="password" 
                               name="password" 
                               required 
                               placeholder="Masukkan kata sandi akun"
                               class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pasuruan-700 focus:border-transparent transition-all">
                        <button type="button" 
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition-colors"
                                tabindex="-1"
                                title="Lihat / Sembunyikan Kata Sandi">
                            <i class="fa-solid text-sm" :class="showPassword ? 'fa-eye-slash text-pasuruan-700' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-pasuruan-700 focus:ring-pasuruan-700">
                        <span>Ingat saya di perangkat ini</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-[11px] font-semibold text-pasuruan-700 hover:text-pasuruan-900 hover:underline">
                        Lupa Kata Sandi?
                    </a>
                </div>

                <button type="submit" data-loading-text="Sedang Memverifikasi..." class="w-full py-3 px-4 rounded-xl text-white font-bold text-xs bg-pasuruan-700 hover:bg-pasuruan-800 shadow-sm hover:shadow transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-to-bracket text-amber-300"></i>
                    <span>Masuk ke Akun</span>
                </button>
            </form>

            <!-- Footer Register Link -->
            <div class="text-center pt-3 border-t border-slate-100 text-xs text-slate-500">
                Belum memiliki akun?
                <a href="{{ route('register') }}" class="font-bold text-pasuruan-700 hover:underline ml-1">
                    Daftar Akun Baru
                </a>
            </div>

        </div>

    </div>
</div>
@endsection
