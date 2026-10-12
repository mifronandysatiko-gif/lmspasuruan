<?php

namespace App\Jobs;

use App\Mail\ResetPasswordVerificationMail;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetTokenEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan maksimal jika terjadi kendala pengiriman.
     */
    public int $tries = 25;

    /**
     * Waktu timeout eksekusi pekerjaan per email (detik).
     */
    public int $timeout = 60;

    /**
     * Interval backoff berulang (detik) jika terjadi kegagalan umum.
     */
    public array $backoff = [60, 180, 300, 600, 1800, 3600];

    public User $user;
    public string $resetUrl;
    public string $token;
    public int $expireMinutes;

    /**
     * Buat instance pekerjaan antrean baru.
     */
    public function __construct(User $user, string $resetUrl, string $token, int $expireMinutes = 60)
    {
        $this->user = $user;
        $this->resetUrl = $resetUrl;
        $this->token = $token;
        $this->expireMinutes = $expireMinutes;
    }

    /**
     * Jalankan pengiriman email dari antrean.
     */
    public function handle(): void
    {
        // 1. Jeda anti-burst (0.4 detik) agar tidak memicu deteksi spam Google saat memproses antrean beruntun
        usleep(400000);

        $hashedToken = hash('sha256', $this->token);

        try {
            // 2. Perbarui waktu pembuatan token (created_at = now()) tepat saat email siap dikirim.
            // Hal ini menjamin jika antrean sempat tertunda karena kuota harian Gmail habis,
            // token tetap memiliki masa berlaku utuh 60 menit saat email mendarat di inbox peserta!
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $this->user->email],
                [
                    'token' => $hashedToken,
                    'created_at' => now(),
                ]
            );

            // 3. Kirim email melalui driver SMTP yang terkonfigurasi (Gmail)
            Mail::to($this->user->email)->send(
                new ResetPasswordVerificationMail($this->user, $this->resetUrl, $this->expireMinutes)
            );

            // 4. Catat status sukses ke Log Sistem & Audit Trail
            Log::info("[EmailQueue] Tautan token reset kata sandi berhasil dikirim ke: {$this->user->email} sesuai nomor antrean.");

            AuditLog::create([
                'user_id' => $this->user->id,
                'action' => 'EMAIL_RESET_TOKEN_DELIVERED',
                'description' => "Tautan verifikasi reset kata sandi berhasil dikirimkan ke email ({$this->user->email}) melalui antrean sistem.",
                'ip_address' => '127.0.0.1',
                'user_agent' => 'LMS Queue Worker (SMTP Gmail)',
                'created_at' => now(),
            ]);

        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            $lowerMsg = strtolower($errorMsg);

            // Analisis respon server SMTP Google (Gmail)
            $isDailyQuotaExceeded = str_contains($lowerMsg, 'quota') 
                || str_contains($lowerMsg, '550') 
                || str_contains($lowerMsg, 'daily user sending quota exceeded')
                || str_contains($lowerMsg, 'limit exceeded');

            $isRateLimitExceeded = str_contains($lowerMsg, 'too many') 
                || str_contains($lowerMsg, '450') 
                || str_contains($lowerMsg, '421') 
                || str_contains($lowerMsg, '454') 
                || str_contains($lowerMsg, 'rate limit')
                || str_contains($lowerMsg, 'temporarily deferred')
                || str_contains($lowerMsg, 'connection timed out');

            if ($isDailyQuotaExceeded) {
                // Kuota harian Gmail (maks 500 email/24 jam) telah tercapai:
                // Tunda pekerjaan selama 1 jam (3600 detik) lalu coba kembali secara otomatis tanpa kehilangan urutan
                Log::warning("[EmailQueue] Kuota harian pengiriman email Gmail tercapai saat mengirim ke {$this->user->email}. Pekerjaan ditunda 1 jam dan akan dicoba kembali secara otomatis sesuai urutan.");
                $this->release(3600);
                return;
            }

            if ($isRateLimitExceeded) {
                // Batas kecepatan burst sementara tercapai:
                // Tunda pekerjaan selama 3 menit (180 detik)
                Log::warning("[EmailQueue] Batas kecepatan pengiriman Gmail sementara tercapai saat mengirim ke {$this->user->email}. Pekerjaan ditunda 3 menit dan akan dicoba kembali.");
                $this->release(180);
                return;
            }

            // Jika error lain (misal koneksi terputus sesaat), catat dan lempar agar worker melakukan retry sesuai backoff
            Log::error("[EmailQueue] Gagal memproses pengiriman email reset ke {$this->user->email}: " . $errorMsg);
            throw $e;
        }
    }

    /**
     * Tangani kegagalan permanen setelah semua percobaan (tries) habis.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::critical("[EmailQueue] Pekerjaan pengiriman email reset ke {$this->user->email} gagal permanen setelah {$this->tries} kali percobaan: " . ($exception ? $exception->getMessage() : 'Unknown error'));
    }
}
