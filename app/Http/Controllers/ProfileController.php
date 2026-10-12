<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman kelola profil dan keamanan akun.
     */
    public function show()
    {
        /** @var User $user */
        $user = Auth::user();
        $user->load('profile');

        return view('pages.profile', compact('user'));
    }

    /**
     * Perbarui data profil pribadi pengguna.
     */
    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'nik' => 'nullable|string|max:30',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:20',
            'gelar_depan' => 'nullable|string|max:50',
            'gelar_belakang' => 'nullable|string|max:50',
            'unit_kerja' => 'nullable|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // 1. Perbarui data dasar pada tabel users
        $user->name = $request->name;
        $user->email = $request->email;
        if ($request->filled('nik')) {
            $user->nik = $request->nik;
        }
        if ($request->filled('jabatan')) {
            $user->jabatan = $request->jabatan;
        }
        if ($request->filled('unit_kerja')) {
            $user->unit_kerja = $request->unit_kerja;
        }
        $user->save();

        // 2. Perbarui atau buat profil
        $profile = $user->profile;
        if (!$profile) {
            $profile = new UserProfile(['user_id' => $user->id]);
        }

        $profile->full_name = $request->name;
        $profile->nik = $request->nik;
        $profile->gelar_depan = $request->gelar_depan;
        $profile->gelar_belakang = $request->gelar_belakang;
        $profile->phone_number = $request->phone_number;

        // Jika peserta umum atau pengguna lokal, perbarui unit kerja & jabatan secara bebas
        if (!$user->isAsn() || $profile->sync_source !== 'api_simpeg') {
            if ($request->filled('unit_kerja')) {
                $profile->unit_kerja = $request->unit_kerja;
            }
            if ($request->filled('jabatan')) {
                $profile->jabatan = $request->jabatan;
            }
        }

        // Upload foto avatar jika ada
        if ($request->hasFile('avatar')) {
            $avatarFile = $request->file('avatar');
            $fileName = 'avatar_' . $user->id . '_' . time() . '.' . $avatarFile->getClientOriginalExtension();
            $path = $avatarFile->storeAs('avatars', $fileName, 'public');
            $profile->photo_path = $path;
        }

        $profile->save();

        // Cek pengaktifan akun pertama kali untuk peserta
        $isFirstLoginCompletedNow = false;
        if ($user->isPeserta() && !$user->first_login_completed) {
            $user->first_login_profile_updated = true;
            if ($user->first_login_password_changed) {
                $user->first_login_completed = true;
                $isFirstLoginCompletedNow = true;
            }
            $user->save();
        }

        // Catat ke log audit
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'UPDATE_PROFILE',
            'description' => "Pengguna ({$user->name}, Role: {$user->role}) memperbarui data profil akun.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($isFirstLoginCompletedNow) {
            return redirect()->route('peserta.dashboard')->with('success', 'Selamat! Profil Anda telah disimpan dan kata sandi berhasil diperbarui. Seluruh menu pembelajaran kini telah aktif.');
        }

        if ($user->isPeserta() && !$user->first_login_completed) {
            return redirect()->route('profile.show', ['tab' => 'security'])->with('success', 'Langkah 1 selesai: Data profil Anda berhasil disimpan! Sekarang silakan lanjutkan dengan mengubah kata sandi akun Anda pada tab Keamanan.');
        }

        return back()->with('success', 'Data profil Anda berhasil diperbarui.');
    }

    /**
     * Perbarui kata sandi akun pengguna saat ini.
     */
    public function updatePassword(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal harus 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sesuai.',
        ]);

        // Verifikasi kata sandi lama (mendukung hash terenkripsi, NIP, atau password default sistem)
        $currentPassValid = false;
        if (Hash::check($request->current_password, $user->password)) {
            $currentPassValid = true;
        } elseif ($user->nip && $request->current_password === $user->nip) {
            $currentPassValid = true;
        } elseif ($user->isPeserta() && $request->current_password === 'password123') {
            $currentPassValid = true;
        }

        if (!$currentPassValid) {
            return back()->withErrors(['current_password' => 'Kata sandi saat ini yang Anda masukkan salah. (Untuk akun baru, gunakan NIP Anda atau kata sandi default).']);
        }

        // Update kata sandi baru
        $user->password = Hash::make($request->password);

        // Cek pengaktifan akun pertama kali untuk peserta
        $isFirstLoginCompletedNow = false;
        if ($user->isPeserta() && !$user->first_login_completed) {
            $user->first_login_password_changed = true;
            if ($user->first_login_profile_updated) {
                $user->first_login_completed = true;
                $isFirstLoginCompletedNow = true;
            }
        }
        $user->save();

        // Catat ke log audit
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'CHANGE_PASSWORD',
            'description' => "Pengguna ({$user->name}) berhasil mengubah kata sandi akun secara mandiri.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($isFirstLoginCompletedNow) {
            return redirect()->route('peserta.dashboard')->with('success', 'Selamat! Kata sandi baru Anda berhasil disimpan dan profil Anda telah lengkap. Seluruh menu pembelajaran kini telah terbuka!');
        }

        if ($user->isPeserta() && !$user->first_login_completed) {
            return redirect()->route('profile.show', ['tab' => 'profile'])->with('success', 'Langkah 2 selesai: Kata sandi baru Anda berhasil disimpan! Sekarang silakan periksa dan simpan data profil Anda pada tab Profil.');
        }

        return back()->with('success', 'Kata sandi akun Anda berhasil diperbarui. Silakan gunakan kata sandi baru untuk sesi berikutnya.');
    }
}
