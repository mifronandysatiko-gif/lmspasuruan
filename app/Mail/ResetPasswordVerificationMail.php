<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $resetUrl;
    public int $expireMinutes;

    public function __construct(User $user, string $resetUrl, int $expireMinutes = 60)
    {
        $this->user = $user;
        $this->resetUrl = $resetUrl;
        $this->expireMinutes = $expireMinutes;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifikasi Pemulihan Kata Sandi - LMS BKPSDM Kab. Pasuruan',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
