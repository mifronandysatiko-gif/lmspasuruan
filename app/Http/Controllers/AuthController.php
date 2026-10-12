<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\DataPegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use App\Mail\ResetPasswordVerificationMail;
use App\Jobs\SendPasswordResetTokenEmail;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'password' => 'required|string',
        ]);

        $identifier = $request->input('identifier');
        $password = $request->input('password');

        $user = User::where('nip', $identifier)
                    ->orWhere('username', $identifier)
                    ->orWhere('email', $identifier)
                    ->first();

        $passwordMatches = false;
        if ($user) {
            if (Hash::check($password, $user->password)) {
                $passwordMatches = true;
            } elseif ($user->nip && $password === $user->nip) {
                // Mendukung login peserta ASN dengan NIP sebagai kata sandi
                $passwordMatches = true;
            } elseif ($user->isPeserta() && $password === 'password123') {
                // Mendukung login peserta dengan kata sandi default sistem
                $passwordMatches = true;
            }
        }

        if ($user && $passwordMatches) {
            if (!$user->is_active) {
                return back()->withErrors([
                    'identifier' => 'Akun Anda dinonaktifkan sementara oleh administrator. Silakan hubungi BKPSDM.',
                ]);
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return $this->redirectBasedOnRole($user);
        }

        return back()->withErrors([
            'identifier' => 'Kombinasi NIP/Username/Email dan kata sandi tidak cocok.',
        ])->onlyInput('identifier');
    }

    /**
     * Mengarahkan pengguna ke halaman OAuth Google Consent
     */
    public function redirectToGoogle()
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return redirect()->route('login')->withErrors([
                'identifier' => 'Fitur Login Google belum aktif karena kredensial GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET pada file .env belum diisi oleh Administrator. Anda tetap dapat masuk menggunakan NIP, Username, atau Email.',
            ]);
        }

        try {
            return Socialite::driver('google')->redirect();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors([
                'identifier' => 'Gagal mengarahkan ke layanan Google: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Memproses respon balik (callback) dari Google setelah pengguna berhasil login
     */
    public function handleGoogleCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('login')->withErrors([
                'identifier' => 'Otentikasi Google dibatalkan atau terjadi kesalahan: ' . $request->input('error'),
            ]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors([
                'identifier' => 'Gagal mengambil data akun Google: ' . $e->getMessage() . '. Silakan coba login kembali.',
            ]);
        }

        if (!$googleUser || empty($googleUser->getEmail())) {
            return redirect()->route('login')->withErrors([
                'identifier' => 'Gagal membaca alamat email dari akun Google Anda.',
            ]);
        }

        $email = strtolower(trim($googleUser->getEmail()));
        $googleId = (string) $googleUser->getId();
        $avatar = $googleUser->getAvatar();
        $name = $googleUser->getName() ?: 'Pengguna Google';

        // 1. Cari user berdasarkan google_id
        $user = User::where('google_id', $googleId)->first();

        // 2. Jika tidak ada, cari berdasarkan email terdaftar
        if (!$user) {
            $user = User::where('email', $email)->first();
        }

        if ($user) {
            // Cek apakah akun aktif
            if (!$user->is_active) {
                return redirect()->route('login')->withErrors([
                    'identifier' => 'Akun Anda dinonaktifkan sementara oleh administrator. Silakan hubungi BKPSDM.',
                ]);
            }

            // Tautkan google_id & avatar jika belum ada
            $dirty = false;
            if (empty($user->google_id)) {
                $user->google_id = $googleId;
                $dirty = true;
            }
            if (empty($user->avatar) && !empty($avatar)) {
                $user->avatar = $avatar;
                $dirty = true;
            }
            if (empty($user->email_verified_at)) {
                $user->email_verified_at = now();
                $dirty = true;
            }
            if ($dirty) {
                $user->save();
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'login_google',
                'model_type' => 'User',
                'model_id' => $user->id,
                'description' => "Pengguna ({$user->name}) berhasil masuk menggunakan Single Sign-On Google ({$email}).",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->redirectBasedOnRole($user);
        }

        // 3. Jika akun baru (belum pernah terdaftar):
        // Periksa apakah email terdaftar di Master Data Pegawai Pemkab Pasuruan (SIMPEG)
        $pegawai = DataPegawai::where('email', $email)->first();

        $baseUsername = Str::slug(explode('@', $email)[0], '_');
        $username = $baseUsername;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . '_' . $counter++;
        }

        $user = User::create([
            'name' => $pegawai ? ($pegawai->nama ?: $name) : $name,
            'email' => $email,
            'nip' => $pegawai ? $pegawai->nip : null,
            'nik' => $pegawai ? $pegawai->nik : null,
            'jabatan' => $pegawai ? $pegawai->jabatan : null,
            'unit_kerja' => $pegawai ? ($pegawai->unit_kerja ?? 'Pemerintah Kabupaten Pasuruan') : 'Peserta Umum',
            'username' => $username,
            'password' => Hash::make(Str::random(32)),
            'google_id' => $googleId,
            'avatar' => $avatar,
            'role' => 'peserta',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'nik' => $pegawai ? $pegawai->nik : null,
            'full_name' => $user->name,
            'gelar_depan' => $pegawai ? $pegawai->gelar_depan : null,
            'gelar_belakang' => $pegawai ? $pegawai->gelar_belakang : null,
            'jabatan' => $pegawai ? $pegawai->jabatan : null,
            'pangkat_golongan' => $pegawai ? $pegawai->pangkat_golongan : null,
            'unit_kerja' => $pegawai ? ($pegawai->unit_kerja ?? 'Pemerintah Kabupaten Pasuruan') : 'Peserta Umum',
            'kategori_peserta' => $pegawai ? 'asn_pasuruan' : 'umum',
            'phone_number' => $pegawai ? $pegawai->no_hp : null,
            'sync_source' => $pegawai ? 'data_pegawai' : 'google_oauth',
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'register_google',
            'model_type' => 'User',
            'model_id' => $user->id,
            'description' => "Pengguna baru ({$user->name}) berhasil terdaftar dan masuk melalui Single Sign-On Google ({$email}).",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return $this->redirectBasedOnRole($user);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Endpoint API untuk verifikasi NIP ke Master Data Pegawai Pemkab Pasuruan dan akun terdaftar.
     */
    public function checkNip(Request $request)
    {
        $nip = trim($request->input('nip', ''));

        if (empty($nip)) {
            return response()->json([
                'valid' => false,
                'status' => 'empty',
                'message' => 'Silakan masukkan NIP untuk diverifikasi.',
            ], 422);
        }

        // 1. Cek apakah NIP sudah memiliki akun LMS
        $existingUser = User::where('nip', $nip)->first();
        if ($existingUser) {
            return response()->json([
                'valid' => false,
                'status' => 'already_registered',
                'masked_email' => self::maskEmail($existingUser->email),
                'message' => 'NIP ini sudah terdaftar dengan akun (' . self::maskEmail($existingUser->email) . '). Silakan langsung masuk melalui halaman login.',
            ]);
        }

        // 2. Cek ke tabel master data_pegawai Pemkab Pasuruan
        $pegawai = DataPegawai::where('nip', $nip)->first();
        if (!$pegawai) {
            return response()->json([
                'valid' => false,
                'status' => 'not_found',
                'message' => 'NIP tidak terdaftar dalam Master Data Pegawai Pemerintah Kabupaten Pasuruan. Pendaftaran sebagai ASN Pasuruan tidak dapat dilanjutkan.',
            ]);
        }

        if (!$pegawai->is_active) {
            return response()->json([
                'valid' => false,
                'status' => 'inactive',
                'message' => 'Status kepegawaian untuk NIP ini tercatat non-aktif di database BKPSDM. Silakan hubungi admin BKPSDM.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'status' => 'verified',
            'message' => 'NIP Terverifikasi Resmi: ' . $pegawai->nama . ' (' . ($pegawai->unit_kerja ?? 'Pemkab Pasuruan') . ')',
            'pegawai' => [
                'nip' => $pegawai->nip,
                'nik' => $pegawai->nik ?? '',
                'nama' => $pegawai->pure_name ?: $pegawai->nama,
                'gelar_depan' => $pegawai->gelar_depan ?? '',
                'gelar_belakang' => $pegawai->gelar_belakang ?? '',
                'formatted_name' => $pegawai->formatted_full_name,
                'jabatan' => $pegawai->jabatan ?? '',
                'unit_kerja' => $pegawai->unit_kerja ?? '',
                'pangkat_golongan' => $pegawai->pangkat_golongan ?? '',
                'email' => $pegawai->email ?? '',
                'no_hp' => $pegawai->no_hp ?? '',
            ],
        ]);
    }

    public function register(Request $request)
    {
        $accountType = $request->input('account_type', 'asn_pasuruan');
        if ($accountType === 'asn') {
            $accountType = 'asn_pasuruan';
        }

        $rules = [
            'account_type' => 'required|in:asn_pasuruan,asn_luar,umum,asn',
            'nik' => 'nullable|string|max:30',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'unit_kerja' => 'required|string|max:255',
        ];

        $pegawai = null;

        $customMessages = [
            'email.unique' => 'Alamat email ini sudah terdaftar.',
        ];

        if ($accountType === 'asn_pasuruan') {
            $rules['nip'] = 'required|string|max:30|unique:users,nip';
            $request->validate($rules, array_merge($customMessages, [
                'nip.required' => 'NIP wajib diisi untuk kategori ASN Kabupaten Pasuruan.',
                'nip.unique' => 'NIP ini sudah terdaftar sebagai akun LMS. Silakan langsung login.',
            ]));

            $nip = trim($request->nip);
            $pegawai = DataPegawai::where('nip', $nip)->first();
            if (!$pegawai) {
                return back()->withErrors([
                    'nip' => 'NIP tidak terdaftar dalam Master Data Pegawai Pemerintah Kabupaten Pasuruan. Pendaftaran tidak dapat dilanjutkan.',
                ])->withInput();
            }

            if (!$pegawai->is_active) {
                return back()->withErrors([
                    'nip' => 'Status kepegawaian NIP ini sedang non-aktif di database BKPSDM.',
                ])->withInput();
            }
        } elseif ($accountType === 'asn_luar') {
            $rules['nip'] = 'required|string|max:30|unique:users,nip';
            $request->validate($rules, array_merge($customMessages, [
                'nip.required' => 'NIP wajib diisi untuk kategori ASN di luar Pasuruan.',
                'nip.unique' => 'NIP ini sudah terdaftar.',
            ]));
        } else {
            // umum
            $rules['nip'] = 'nullable|string|max:30|unique:users,nip';
            $request->validate($rules, $customMessages);
        }

        $nipValue = $accountType === 'asn_pasuruan' ? trim($request->nip) : ($request->nip ? trim($request->nip) : null);
        
        // Buat username unik otomatis untuk akun peserta
        $usernameBase = $nipValue ?: ($request->email ? explode('@', $request->email)[0] : Str::slug($request->name));
        $usernameValue = strtolower(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $usernameBase));
        if (empty($usernameValue) || User::where('username', $usernameValue)->exists()) {
            $usernameValue = ($usernameValue ?: 'peserta') . rand(100, 9999);
        }

        // Kata sandi awal: gunakan NIP atau password123 (peserta dapat masuk via NIP atau Google SSO)
        $rawPassword = $nipValue ?: 'password123';
        $passwordValue = Hash::make($rawPassword);

        $nikVal = $request->nik ?: ($pegawai ? $pegawai->nik : null);
        $jabatanVal = $pegawai ? ($pegawai->jabatan ?? $request->jabatan) : $request->jabatan;
        $unitKerjaVal = $pegawai ? ($pegawai->unit_kerja ?? $request->unit_kerja) : $request->unit_kerja;

        $user = User::create([
            'name' => $pegawai ? ($pegawai->nama ?: $request->name) : $request->name,
            'email' => $request->email,
            'nip' => $nipValue,
            'nik' => $nikVal,
            'jabatan' => $jabatanVal,
            'unit_kerja' => $unitKerjaVal,
            'username' => $usernameValue,
            'password' => $passwordValue,
            'role' => 'peserta',
            'is_active' => true,
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'nik' => $nikVal,
            'full_name' => $user->name,
            'gelar_depan' => $pegawai ? ($pegawai->gelar_depan ?? $request->gelar_depan) : $request->gelar_depan,
            'gelar_belakang' => $pegawai ? ($pegawai->gelar_belakang ?? $request->gelar_belakang) : $request->gelar_belakang,
            'jabatan' => $jabatanVal,
            'pangkat_golongan' => $pegawai ? ($pegawai->pangkat_golongan ?? $request->pangkat_golongan) : $request->pangkat_golongan,
            'unit_kerja' => $unitKerjaVal,
            'kategori_peserta' => $accountType,
            'phone_number' => $request->phone_number ?? ($pegawai ? $pegawai->no_hp : null),
            'sync_source' => $accountType === 'asn_pasuruan' ? 'data_pegawai' : 'manual_registration',
        ]);

        // Audit log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'register',
            'model_type' => 'User',
            'model_id' => $user->id,
            'description' => "Peserta mendaftar akun baru ({$accountType}): {$user->name} (" . ($user->nip ?? 'Umum') . ")",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Auth::login($user);

        return redirect()->route('profile.show')->with('warning', 'Pendaftaran akun berhasil! Demi keamanan akun dan validitas data sertifikat, silakan simpan profil dan ubah kata sandi Anda terlebih dahulu.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }

    private function getRedirectRoute(User $user): string
    {
        if ($user->needsFirstLoginSetup()) {
            return route('profile.show');
        }

        if ($user->role === 'super_admin') {
            return route('admin.dashboard');
        } elseif ($user->role === 'penyelenggara') {
            return route('admin.webinars.index');
        } elseif (in_array($user->role, ['widyaiswara', 'mentor'])) {
            return route('admin.kepemimpinan.review');
        } else {
            return route('peserta.dashboard');
        }
    }

    private function redirectBasedOnRole(User $user)
    {
        if ($user->needsFirstLoginSetup()) {
            return redirect()->route('profile.show')->with('warning', 'Selamat datang di LMS BKPSDM! Akun Anda baru pertama kali masuk. Silakan simpan data profil dan perbarui kata sandi akun Anda terlebih dahulu untuk membuka seluruh menu pembelajaran.');
        }

        return redirect()->intended($this->getRedirectRoute($user));
    }

    /**
     * Helper menyamarkan alamat email (hanya tampilkan 3 huruf awal nama sebelum @).
     * Contoh: rahmadhidayat@pasuruankab.go.id -> rah***@pasuruankab.go.id
     */
    public static function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***';
        }
        $name = $parts[0];
        $domain = $parts[1];
        $prefixLen = min(3, max(1, strlen($name) - 1));
        $prefix = substr($name, 0, $prefixLen);

        return $prefix . '***@' . $domain;
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'email' => 'required|email',
        ], [
            'identifier.required' => 'NIP, Username, atau Email akun wajib diisi.',
            'email.required' => 'Alamat email terdaftar wajib diisi untuk verifikasi pengiriman tautan.',
            'email.email' => 'Format alamat email tidak valid.',
        ]);

        $identifier = trim($request->input('identifier'));
        $inputEmail = strtolower(trim($request->input('email')));

        $user = User::where('email', $identifier)
            ->orWhere('nip', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (!$user) {
            return back()->withInput()->withErrors([
                'identifier' => 'Akun dengan NIP / Username / Email tersebut tidak ditemukan dalam sistem BKPSDM.'
            ]);
        }

        // Verifikasi kecocokan email: input peserta HARUS cocok 100% dengan email terdaftar pada akun tersebut!
        $registeredEmail = strtolower(trim($user->email));
        if ($inputEmail !== $registeredEmail) {
            $masked = self::maskEmail($user->email);
            return back()->withInput()->withErrors([
                'email' => "Verifikasi Gagal: Alamat email yang Anda masukkan tidak sesuai dengan email terdaftar pada akun ini ({$masked}). Tautan verifikasi hanya dapat dikirim ke email terdaftar."
            ]);
        }

        // Buat token reset kriptografis satu kali pakai
        $token = Str::random(64);
        $hashedToken = hash('sha256', $token);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => $hashedToken, 'created_at' => now()]
        );

        $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);
        $maskedEmail = self::maskEmail($user->email);

        // Cek apakah ada antrean pengiriman yang sedang menunggu di database
        $pendingJobsCount = DB::table('jobs')->count();

        // 1. JIKA ANTREAN KOSONG: Coba kirimkan langsung seketika (Instant Direct Send)
        // Pengguna langsung menerima email dalam hitungan detik tanpa perlu menunggu worker / Super Admin
        if ($pendingJobsCount === 0) {
            try {
                Mail::to($user->email)->send(
                    new ResetPasswordVerificationMail($user, $resetUrl, 60)
                );

                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'REQUEST_RESET_PASSWORD',
                    'description' => "Pengguna ({$user->name}) mengajukan reset kata sandi. Tautan verifikasi berhasil dikirimkan langsung ke email ({$user->email}).",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                $statusMsg = "Tautan verifikasi pemulihan kata sandi telah berhasil dikirimkan ke alamat email Anda ({$maskedEmail}). Silakan periksa kotak masuk atau folder spam Anda.";

                return back()->with('status', $statusMsg)
                    ->with('email_sent', true)
                    ->with('sent_to', $maskedEmail);

            } catch (\Throwable $e) {
                // 2. JIKA TERKENA LIMIT KUOTA GMAIL (550 / 421 / 450) ATAU KENDALA JARINGAN:
                // Alihkan secara otomatis dan aman ke antrean database (Queue Fallback) dengan jadwal coba ulang
                Log::warning("[ResetPassword] Gagal kirim langsung ke {$user->email} ({$e->getMessage()}), mengalihkan ke antrean database.");

                SendPasswordResetTokenEmail::dispatch($user, $resetUrl, $token, 60);
                $queuePosition = DB::table('jobs')->count();

                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'REQUEST_RESET_PASSWORD',
                    'description' => "Pengiriman langsung ke email ({$user->email}) terhambat batas kuota Gmail/jaringan. Tugas dialihkan ke antrean sistem (Nomor antrean: {$queuePosition}).",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                $statusMsg = "Kuota pengiriman email Google saat ini sedang mencapai batas atau dibatasi sesaat. Permintaan Anda aman berada di antrean sistem (Nomor antrean #{$queuePosition}) dan akan otomatis dikirimkan begitu kuota siap kembali.";

                return back()->with('status', $statusMsg)
                    ->with('email_sent', true)
                    ->with('sent_to', $maskedEmail)
                    ->with('queue_position', $queuePosition);
            }
        }

        // 3. JIKA SEDANG ADA ANTREAN MENUMPUK: Masukkan ke urutan berikutnya (FIFO)
        SendPasswordResetTokenEmail::dispatch($user, $resetUrl, $token, 60);
        $queuePosition = DB::table('jobs')->count();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'REQUEST_RESET_PASSWORD',
            'description' => "Pengguna ({$user->name}) mengajukan reset kata sandi saat antrean aktif. Tugas dimasukkan ke antrean sistem (Nomor antrean: {$queuePosition}).",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $statusMsg = "Permintaan pemulihan kata sandi Anda telah dimasukkan ke antrean sistem pengiriman email resmi BKPSDM. Tautan verifikasi akan dikirimkan ke alamat email Anda ({$maskedEmail}) secara otomatis sesuai urutan antrean.";

        return back()->with('status', $statusMsg)
            ->with('email_sent', true)
            ->with('sent_to', $maskedEmail)
            ->with('queue_position', $queuePosition);
    }

    public function showResetPassword(Request $request, $token)
    {
        $hashedToken = hash('sha256', $token);
        $email = $request->query('email');

        $query = DB::table('password_reset_tokens')
            ->where('token', $hashedToken)
            ->where('created_at', '>=', now()->subHours(1)); // 60 menit masa berlaku

        if ($email) {
            $query->where('email', strtolower(trim($email)));
        }

        $record = $query->first();

        if (!$record) {
            return redirect()->route('password.request')->withErrors([
                'identifier' => 'Tautan verifikasi pemulihan kata sandi tidak valid atau telah kedaluwarsa (masa berlaku 60 menit). Silakan ajukan permintaan baru.'
            ]);
        }

        $user = User::where('email', $record->email)->first();
        if (!$user) {
            return redirect()->route('password.request')->withErrors([
                'identifier' => 'Akun pengguna terkait tidak ditemukan dalam sistem.'
            ]);
        }

        return view('auth.reset-password', compact('token', 'user'));
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal harus 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sesuai.',
        ]);

        $hashedToken = hash('sha256', $request->token);
        $email = strtolower(trim($request->email));

        $record = DB::table('password_reset_tokens')
            ->where('token', $hashedToken)
            ->where('email', $email)
            ->where('created_at', '>=', now()->subHours(1))
            ->first();

        if (!$record) {
            return redirect()->route('password.request')->withErrors([
                'identifier' => 'Sesi pemulihan kata sandi tidak valid atau telah kedaluwarsa. Silakan ajukan ulang permintaan pemulihan kata sandi.'
            ]);
        }

        $user = User::where('email', $record->email)->first();
        if (!$user) {
            return redirect()->route('password.request')->withErrors([
                'identifier' => 'Data akun pengguna tidak ditemukan.'
            ]);
        }

        // Ubah kata sandi
        $user->password = Hash::make($request->password);
        $user->save();

        // Hapus token yang sudah digunakan agar tidak bisa dipakai ulang
        DB::table('password_reset_tokens')->where('email', $record->email)->delete();

        // Catat ke audit log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'RESET_PASSWORD',
            'description' => "Pengguna ({$user->name}) berhasil mereset kata sandi baru setelah lolos verifikasi tautan email resmi ({$user->email}).",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('login')->with(
            'success',
            'Kata sandi Anda berhasil diperbarui! Silakan masuk ke LMS menggunakan kata sandi baru Anda.'
        );
    }
}
