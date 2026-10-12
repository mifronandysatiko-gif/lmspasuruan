<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('warning', 'Silakan masuk terlebih dahulu.');
        }

        // If user is inactive
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Akun Anda dinonaktifkan oleh Administrator. Hubungi BKPSDM Kabupaten Pasuruan.');
        }

        // Super Admin has bypass access to all admin routes if super_admin is not explicitly excluded
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // Redirect with contextual explanation based on role
        if ($user->role === 'penyelenggara') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses Dibatasi: Modul ini adalah wewenang khusus Super Admin BKPSDM (Manajemen Data Pegawai, Konfigurasi Sistem, & Integrasi API). Akun Penyelenggara difokuskan pada manajemen kegiatan Webinar & Pelatihan MOOC.');
        }

        if ($user->role === 'widyaiswara') {
            return redirect()->route('admin.courses.index')->with('error', 'Akses Dibatasi: Akun Widyaiswara difokuskan pada evaluasi materi, penilaian tugas peserta, dan review aksi perubahan.');
        }

        if ($user->role === 'mentor') {
            return redirect()->route('admin.kepemimpinan.review')->with('error', 'Akses Dibatasi: Akun Mentor difokuskan pada review logbook aksi perubahan bawahan.');
        }

        if ($user->role === 'peserta') {
            return redirect()->route('peserta.dashboard')->with('error', 'Akses Ditolak: Anda tidak memiliki wewenang untuk membuka portal pengelola.');
        }

        abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses sumber daya ini.');
    }
}
