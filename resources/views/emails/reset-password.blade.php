<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pemulihan Kata Sandi</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #1e293b;">
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 30px 15px;">
        <tr>
            <td align="center">
                <!-- Main Container Card -->
                <table role="presentation" width="100%" max-width="600" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                    
                    <!-- Header Kop Resmi -->
                    <tr>
                        <td style="background-color: #0c4a6e; padding: 24px 30px; text-align: left; border-bottom: 3px solid #f59e0b;">
                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td>
                                        <div style="font-size: 11px; font-weight: 800; color: #fbbf24; text-transform: uppercase; letter-spacing: 1.5px;">Pemerintah Kabupaten Pasuruan</div>
                                        <div style="font-size: 16px; font-weight: 800; color: #ffffff; margin-top: 4px; line-height: 1.3;">Badan Kepegawaian dan Pengembangan Sumber Daya Manusia</div>
                                        <div style="font-size: 12px; color: #bae6fd; margin-top: 2px;">LMS Pengembangan Kompetensi Aparatur & Pelatihan Terbuka</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px 30px 24px 30px;">
                            <h2 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 16px 0;">
                                Permintaan Pemulihan Kata Sandi
                            </h2>

                            <p style="font-size: 14px; line-height: 1.6; color: #334155; margin: 0 0 16px 0;">
                                Yth. Bapak/Ibu <strong>{{ $user->name }}</strong>,
                            </p>

                            <p style="font-size: 13px; line-height: 1.6; color: #475569; margin: 0 0 20px 0;">
                                Kami menerima permintaan untuk mengatur ulang kata sandi pada akun LMS BKPSDM Kabupaten Pasuruan Anda. Untuk melanjutkan pembaruan kata sandi, silakan klik tombol verifikasi di bawah ini:
                            </p>

                            <!-- Identity Box -->
                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px;">Rincian Akun Terdaftar:</div>
                                        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="4" style="font-size: 12px; color: #334155;">
                                            <tr>
                                                <td width="30%" style="color: #64748b;">Nama Lengkap</td>
                                                <td width="70%"><strong>{{ $user->name }}</strong></td>
                                            </tr>
                                            @if($user->nip)
                                            <tr>
                                                <td style="color: #64748b;">NIP</td>
                                                <td><strong style="font-family: monospace;">{{ $user->nip }}</strong></td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td style="color: #64748b;">Email Akun</td>
                                                <td><strong style="color: #0369a1;">{{ $user->email }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b;">Unit Kerja</td>
                                                <td>{{ $user->profile->unit_kerja ?? $user->unit_kerja ?? 'Pemerintah Kabupaten Pasuruan' }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{ $resetUrl }}" target="_blank" style="display: inline-block; background-color: #0284c7; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(2, 132, 199, 0.3); letter-spacing: 0.3px;">
                                    Atur Ulang Kata Sandi Saya &rarr;
                                </a>
                            </div>

                            <!-- Expiry Notice -->
                            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 6px; margin-bottom: 24px;">
                                <p style="font-size: 12px; color: #92400e; margin: 0; line-height: 1.5;">
                                    <strong>Perhatian:</strong> Tautan verifikasi ini hanya berlaku selama <strong>{{ $expireMinutes }} menit</strong> sejak email ini dikirimkan dan hanya dapat digunakan <strong>1 (satu) kali</strong> demi keamanan akun Anda.
                                </p>
                            </div>

                            <!-- Fallback Link -->
                            <p style="font-size: 11px; line-height: 1.5; color: #64748b; margin: 0 0 8px 0;">
                                Jika tombol di atas tidak dapat diklik, salin dan tempel tautan berikut ke browser web Anda:
                            </p>
                            <p style="font-size: 11px; word-break: break-all; background-color: #f1f5f9; padding: 10px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: monospace; color: #0f172a; margin: 0 0 24px 0;">
                                {{ $resetUrl }}
                            </p>

                            <!-- Security Warning -->
                            <div style="border-top: 1px solid #e2e8f0; padding-top: 16px;">
                                <p style="font-size: 11px; color: #94a3b8; line-height: 1.5; margin: 0;">
                                    <strong>Tidak merasa meminta pemulihan kata sandi?</strong> Jika Anda tidak mengajukan permintaan ini, abaikan pesan email ini. Kata sandi Anda tidak akan berubah dan akun Anda tetap terlindungi dengan aman.
                                </p>
                            </div>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #0f172a; padding: 20px 30px; text-align: center; color: #94a3b8; font-size: 11px; line-height: 1.5;">
                            <div style="font-weight: 700; color: #e2e8f0; margin-bottom: 4px;">Badan Kepegawaian dan Pengembangan Sumber Daya Manusia (BKPSDM)</div>
                            <div>Pemerintah Kabupaten Pasuruan, Jawa Timur</div>
                            <div style="margin-top: 8px; font-size: 10px; color: #64748b;">
                                Email ini dibuat secara otomatis oleh sistem LMS. Mohon tidak membalas langsung ke alamat email ini.
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
