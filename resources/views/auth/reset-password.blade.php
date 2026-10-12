@extends('layouts.app')

@section('title', 'Atur Ulang Kata Sandi - LMS Kabupaten Pasuruan')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-lg w-full space-y-8">
        
        <!-- Header Form -->
        <div class="text-center">
            <picture>
                <source srcset="{{ asset('images/logo-pasuruan.webp') }}" type="image/webp">
                <img src="{{ asset('images/logo-pasuruan.png') }}" alt="Logo Kabupaten Pasuruan" class="h-16 w-auto mx-auto object-contain mb-3 drop-shadow-md">
            </picture>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                Atur Ulang Kata Sandi
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                LMS BKPSDM Pemerintah Kabupaten Pasuruan
            </p>
        </div>

        <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm space-y-6">

            <!-- Card Info Akun Terverifikasi via Email -->
            <div class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-200/80 space-y-2 text-xs">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-700 uppercase tracking-wider">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i>
                        Tautan Email Terverifikasi
                    </span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $user->isAsn() ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800' }}">
                        {{ $user->isAsn() ? 'ASN Pemkab Pasuruan' : 'Peserta Umum' }}
                    </span>
                </div>
                <div class="font-bold text-slate-900 text-sm">
                    {{ $user->name }}
                </div>
                @if($user->nip)
                    <div class="font-mono text-[11px] text-slate-500">
                        NIP. {{ $user->nip }}
                    </div>
                @endif
                @if($user->unit_kerja)
                    <div class="text-[11px] text-slate-600">
                        <i class="fa-solid fa-building-columns text-slate-400 mr-1"></i> {{ $user->unit_kerja }}
                    </div>
                @endif
                <div class="pt-2 border-t border-emerald-200/60 flex items-center justify-between flex-wrap gap-2">
                    <span class="text-slate-500 text-[11px]">Email Terdaftar:</span>
                    <span class="font-mono font-bold text-emerald-900 bg-emerald-100 px-2.5 py-0.5 rounded-lg border border-emerald-300 text-xs inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-envelope-circle-check text-emerald-600"></i>
                        {{ $user->email }}
                    </span>
                </div>
            </div>

            <!-- Petunjuk Pengisian Kata Sandi -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-xs space-y-1">
                <div class="font-semibold text-slate-800 flex items-center gap-1.5">
                    <i class="fa-solid fa-shield-halved text-pasuruan-700"></i>
                    <span>Tentukan Kata Sandi Baru:</span>
                </div>
                <p class="text-[11px] leading-relaxed text-slate-600">
                    Silakan masukkan kata sandi baru Anda dengan minimal 6 karakter. Pastikan kata sandi baru ini mudah Anda ingat namun sulit ditebak orang lain.
                </p>
            </div>

            @if(isset($errors) && $errors->any())
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1 shadow-xs">
                    <div class="font-bold flex items-center gap-2">
                        <i class="fa-solid fa-circle-xmark text-rose-600 text-base"></i>
                        <span>Gagal Menyimpan Kata Sandi:</span>
                    </div>
                    <ul class="list-disc list-inside text-[11px] space-y-0.5 ml-1">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('password.update') }}" method="POST" class="space-y-4 text-xs" x-data="{ showPass: false, showPassConf: false }">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $user->email }}">

                <!-- Kata Sandi Baru -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Kata Sandi Baru <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </div>
                        <input :type="showPass ? 'text' : 'password'" 
                               type="password" 
                               name="password" 
                               required 
                               minlength="6" 
                               placeholder="Minimal 6 karakter"
                               class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pasuruan-700 focus:border-transparent transition-all">
                        <button type="button" 
                                @click="showPass = !showPass"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition-colors"
                                tabindex="-1"
                                title="Lihat / Sembunyikan Kata Sandi">
                            <i class="fa-solid text-sm" :class="showPass ? 'fa-eye-slash text-pasuruan-700' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Konfirmasi Kata Sandi Baru -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Ulangi Kata Sandi Baru <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-shield-check text-sm"></i>
                        </div>
                        <input :type="showPassConf ? 'text' : 'password'" 
                               type="password" 
                               name="password_confirmation" 
                               required 
                               minlength="6" 
                               placeholder="Ketik ulang kata sandi baru"
                               class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pasuruan-700 focus:border-transparent transition-all">
                        <button type="button" 
                                @click="showPassConf = !showPassConf"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition-colors"
                                tabindex="-1"
                                title="Lihat / Sembunyikan Konfirmasi Kata Sandi">
                            <i class="fa-solid text-sm" :class="showPassConf ? 'fa-eye-slash text-pasuruan-700' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full py-3 px-4 rounded-xl text-white font-bold text-xs bg-pasuruan-800 hover:bg-pasuruan-900 shadow-sm hover:shadow transition-all flex items-center justify-center gap-2 mt-2">
                    <i class="fa-solid fa-key text-amber-300"></i>
                    <span>Simpan & Terapkan Kata Sandi Baru</span>
                </button>
            </form>

            <div class="text-center pt-3 border-t border-slate-100 text-xs text-slate-500">
                <a href="{{ route('login') }}" class="font-bold text-pasuruan-700 hover:underline inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-left text-[11px]"></i>
                    <span>Batal & Kembali ke Halaman Masuk</span>
                </a>
            </div>

        </div>

    </div>
</div>
@endsection
