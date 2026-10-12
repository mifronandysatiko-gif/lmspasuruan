<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePesertaSetupCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->needsFirstLoginSetup()) {
            // Rute-rute yang diizinkan untuk dikunjungi peserta saat pengaktifan awal
            $allowedRoutes = [
                'profile.show',
                'profile.update',
                'profile.password',
                'logout',
            ];

            $currentRoute = $request->route()?->getName();

            if (!in_array($currentRoute, $allowedRoutes)) {
                return redirect()->route('profile.show')->with('warning', 'Pemberitahuan: Akun Anda baru pertama kali aktif. Silakan simpan data profil dan perbarui kata sandi Anda terlebih dahulu untuk membuka seluruh menu pembelajaran.');
            }
        }

        return $next($request);
    }
}
