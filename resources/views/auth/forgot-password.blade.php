@extends('layouts.app')

@section('title', 'Lupa Kata Sandi - LMS Kabupaten Pasuruan')

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
                Pemulihan Kata Sandi
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                LMS BKPSDM Pemerintah Kabupaten Pasuruan
            </p>
        </div>

        <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm space-y-6">

            @if(session('status'))
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs space-y-2.5 shadow-xs">
                    <div class="flex items-start gap-2.5 font-bold">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-base mt-0.5 shrink-0"></i>
                        <span>{{ session('status') }}</span>
                    </div>

                    @if(session('queue_position'))
                        <div class="flex items-center gap-2 text-[11px] font-semibold text-emerald-800 bg-white/80 px-3 py-1.5 rounded-lg border border-emerald-200">
                            <i class="fa-solid fa-layer-group text-emerald-600"></i>
                            <span>Posisi Antrean Pengiriman: <strong>#{{ session('queue_position') }}</strong></span>
                        </div>
                    @endif

                    <div class="text-[11px] text-emerald-800 bg-emerald-100/70 p-2.5 rounded-xl border border-emerald-200/80 leading-relaxed space-y-1">
                        <div>
                            <i class="fa-solid fa-envelope-open-text mr-1 text-emerald-700"></i>
                            <strong>Petunjuk:</strong> Buka aplikasi email Anda (Inbox/Kotak Masuk atau folder Spam), cari email dari <strong>BKPSDM Kab. Pasuruan</strong>, dan klik tombol <strong>"Atur Ulang Kata Sandi Saya"</strong> untuk melanjutkan.
                        </div>
                        <p class="text-[10px] text-emerald-700/90 italic">
                            *Antrean pengiriman bekerja secara otomatis dan terjadwal sesuai urutan permohonan untuk menjaga keandalan pengiriman email.
                        </p>
                    </div>
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1 shadow-xs">
                    <div class="font-bold flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                        <span>Permintaan Belum Dapat Diproses:</span>
                    </div>
                    <ul class="list-disc list-inside text-[11px] space-y-0.5 ml-1">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p class="text-xs text-slate-600 leading-relaxed">
                Masukkan <strong>NIP atau Username Akun</strong> dan <strong>Alamat Email Terdaftar</strong> Anda. Sistem akan memverifikasi kecocokan data dan mengirimkan tautan token verifikasi langsung ke email Anda.
            </p>

            <form action="{{ route('password.email') }}" method="POST" class="space-y-4 text-xs">
                @csrf

                <!-- Field 1: NIP / Username -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        NIP / Username Akun <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-user-shield text-sm"></i>
                        </div>
                        <input type="text" name="identifier" required placeholder="Contoh: 19920817... atau username akun Anda" value="{{ old('identifier') }}"
                               class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pasuruan-700 focus:border-transparent transition-all">
                    </div>
                </div>

                <!-- Field 2: Alamat Email Terdaftar -->
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Alamat Email Terdaftar <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-envelope text-sm"></i>
                        </div>
                        <input type="email" name="email" required placeholder="Contoh: nama.pegawai@pasuruankab.go.id atau email Anda" value="{{ old('email') }}"
                               class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pasuruan-700 focus:border-transparent transition-all">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Tautan verifikasi akan dikirimkan ke alamat email ini jika cocok dengan data akun.</p>
                </div>

                <button type="submit" class="w-full py-3 px-4 rounded-xl text-white font-bold text-xs bg-pasuruan-700 hover:bg-pasuruan-800 shadow-sm hover:shadow transition-all flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-paper-plane text-amber-300"></i>
                    <span>Kirim Tautan Verifikasi ke Email</span>
                </button>
            </form>

            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-[11px] text-slate-600 space-y-1">
                <div class="font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-question text-pasuruan-700"></i>
                    <span>Butuh bantuan admin kepegawaian?</span>
                </div>
                <p class="leading-relaxed text-[11px]">
                    Bagi aparatur ASN yang lupa email atau NIP, silakan menghubungi administrator BKPSDM Pasuruan di Subbid Pengembangan Kompetensi untuk bantuan reset kata sandi langsung melalui Super Admin.
                </p>
            </div>

            <!-- Footer Links -->
            <div class="text-center pt-3 border-t border-slate-100 text-xs text-slate-500">
                Sudah ingat kata sandi Anda?
                <a href="{{ route('login') }}" class="font-bold text-pasuruan-700 hover:underline ml-1">
                    Kembali ke Halaman Masuk
                </a>
            </div>

        </div>

    </div>
</div>
@endsection
