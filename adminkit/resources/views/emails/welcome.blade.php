@extends('emails.layout')

@section('subject', 'Selamat Datang di '.($branding['app_name'] ?? config('app.name')))
@section('preheader', 'Akun Anda sudah aktif — berikut cara masuk ke '.($branding['app_name'] ?? config('app.name')).'.')

@section('content')
    <div style="display:inline-block;padding:3px 10px;border:1px solid #e5e5e5;border-radius:999px;font-size:11px;color:#737373;">
        Akun Baru
    </div>

    <h1 style="margin:16px 0 12px;font-size:22px;line-height:1.25;font-weight:600;color:#0a0a0a;letter-spacing:-0.02em;">
        Selamat datang, {{ $user->name }}
    </h1>

    <p style="margin:0 0 14px;font-size:14px;line-height:1.65;color:#525252;">
        Akun Anda pada {{ $branding['app_name'] ?? config('app.name') }} sudah aktif{{ $roles ? ' dengan peranan '.$roles : '' }}.
        Gunakan detail berikut untuk masuk pertama kali.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
           style="margin:0 0 20px;border:1px solid #e5e5e5;border-radius:8px;background:#fafafa;">
        @foreach ($credentials as $label => $value)
            <tr>
                <td style="padding:9px 14px;font-size:12px;color:#737373;border-bottom:1px solid #eeeeee;width:40%;">{{ $label }}</td>
                <td style="padding:9px 14px;font-size:12px;color:#0a0a0a;border-bottom:1px solid #eeeeee;font-family:ui-monospace,Menlo,Consolas,monospace;">{{ $value }}</td>
            </tr>
        @endforeach
    </table>

    <a href="{{ $loginUrl }}"
       style="display:inline-block;padding:10px 18px;background:#0a0a0a;color:#ffffff;font-size:13px;font-weight:500;text-decoration:none;border-radius:6px;">
        Masuk Ke Aplikasi
    </a>

    <p style="margin:18px 0 0;font-size:12px;line-height:1.6;color:#737373;">
        @if ($password)
            Demi keamanan, segera ganti kata sandi sementara di atas setelah berhasil masuk melalui menu Profil.
        @else
            Bila Anda lupa kata sandi, hubungi administrator untuk melakukan pengaturan ulang.
        @endif
    </p>
@endsection
