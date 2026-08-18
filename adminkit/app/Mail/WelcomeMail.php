<?php

namespace App\Mail;

use App\Models\User;
use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $password = null,
    ) {}

    public function envelope(): Envelope
    {
        $appName = Branding::values()['app_name'] ?? config('app.name');

        return new Envelope(subject: "Selamat Datang di {$appName}");
    }

    public function content(): Content
    {
        $branding = Branding::values();
        $roles = $this->user->roles->pluck('name')->join(', ');

        return new Content(
            view: 'emails.welcome',
            with: [
                'branding' => $branding,
                'roles' => $roles,
                'password' => $this->password,
                'loginUrl' => rtrim((string) ($branding['app_url'] ?: config('app.url')), '/').'/login',
                'credentials' => array_filter([
                    'Nama' => $this->user->name,
                    'Nama Pengguna' => $this->user->username,
                    'Alamat Email' => $this->user->email,
                    'Kata Sandi Sementara' => $this->password,
                ]),
            ],
        );
    }
}
