<?php

namespace App\Support;

use App\Mail\WelcomeMail;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * Satu pintu pengiriman surel aplikasi.
 * Kegagalan SMTP tidak boleh menggagalkan alur utama (mis. pembuatan pengguna),
 * jadi setiap pengiriman dibungkus try/catch dan dicatat ke audit trail.
 *
 * Menambah surel baru: buat Mailable di app/Mail + view di resources/views/emails
 * (extends `emails.layout`), lalu tambahkan method pengirimnya di sini.
 */
class Mailer
{
    public static function welcome(User $user, ?string $password = null): bool
    {
        if (! $user->email) {
            return false;
        }

        return self::send($user->email, new WelcomeMail($user, $password), "Email sambutan ke {$user->email}");
    }

    private static function send(string $to, Mailable $mailable, string $description): bool
    {
        try {
            Mail::to($to)->send($mailable);

            ActivityLog::record($description.' terkirim', 'Surel', 'success');

            return true;
        } catch (\Throwable $e) {
            ActivityLog::record(
                $description.' gagal',
                'Surel',
                'danger',
                context: ['pengecualian' => mb_substr($e->getMessage(), 0, 300)],
            );

            return false;
        }
    }
}
