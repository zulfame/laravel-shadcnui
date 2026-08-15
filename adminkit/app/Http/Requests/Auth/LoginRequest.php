<?php

namespace App\Http\Requests\Auth;

use App\Models\ActivityLog;
use App\Support\Notify;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'credential' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'credential' => 'kredensial',
            'password' => 'kata sandi',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
        ];
    }

    /**
     * Coba autentikasi: kredensial dapat berupa email, username, atau telepon.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credential = trim((string) $this->input('credential'));
        $field = match (true) {
            filter_var($credential, FILTER_VALIDATE_EMAIL) !== false => 'email',
            preg_match('/^[0-9+()\-\s]{6,}$/', $credential) === 1 => 'phone',
            default => 'username',
        };

        $attempted = Auth::attempt(
            [$field => $credential, 'password' => $this->input('password'), 'is_active' => true],
            $this->boolean('remember')
        );

        if (! $attempted) {
            RateLimiter::hit($this->throttleKey());

            Notify::toPermission(
                permission: 'activity.view',
                title: 'Percobaan masuk gagal',
                module: 'Keamanan',
                body: "Kredensial: {$credential}",
                url: '/audit-trail',
                level: 'danger',
            );

            ActivityLog::record(
                'Percobaan masuk gagal',
                'Keamanan',
                'danger',
                context: ['kredensial' => $credential, 'jenis_kolom' => $field],
                statusCode: 422,
            );

            throw ValidationException::withMessages([
                'credential' => 'Kredensial atau kata sandi tidak cocok.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        $this->user()->forceFill(['last_login_at' => now()])->saveQuietly();
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        ActivityLog::record(
            'Akun terkunci sementara (terlalu banyak percobaan masuk)',
            'Keamanan',
            'warning',
            context: ['kredensial' => (string) $this->input('credential'), 'buka_dalam_detik' => $seconds],
            statusCode: 429,
        );

        throw ValidationException::withMessages([
            'credential' => "Terlalu banyak percobaan masuk. Silakan coba lagi dalam {$seconds} detik.",
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('credential')).'|'.$this->ip());
    }
}
