@php
    $brand = $branding ?? [];
    $appName = $brand['app_name'] ?? config('app.name');
    $initials = $brand['brand_initials'] ?? mb_strtoupper(mb_substr($appName, 0, 2));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('subject', $appName)</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:ui-sans-serif,system-ui,-apple-system,'Segoe UI',Arial,sans-serif;-webkit-font-smoothing:antialiased;">
    {{-- Preheader: teks pratinjau di kotak masuk. --}}
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">@yield('preheader', $appName)</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                       style="max-width:560px;background:#ffffff;border:1px solid #e5e5e5;border-radius:12px;overflow:hidden;">
                    <!-- Kop merek -->
                    <tr>
                        <td style="padding:20px 24px;border-bottom:1px solid #e5e5e5;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding-right:10px;">
                                        <div style="width:32px;height:32px;border-radius:6px;background:#0a0a0a;color:#ffffff;font-size:11px;font-weight:600;line-height:32px;text-align:center;">
                                            {{ $initials }}
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size:14px;font-weight:600;color:#0a0a0a;letter-spacing:-0.01em;">{{ $appName }}</div>
                                        @if (! empty($brand['tagline']))
                                            <div style="font-size:12px;color:#737373;">{{ $brand['tagline'] }}</div>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Isi -->
                    <tr>
                        <td style="padding:28px 24px 24px;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Kaki -->
                    <tr>
                        <td style="padding:16px 24px;border-top:1px solid #e5e5e5;background:#fafafa;">
                            <div style="font-size:11px;line-height:1.6;color:#737373;">
                                {{ $brand['footer_text'] ?? $appName }}
                                @if (! empty($brand['support_email']))
                                    <br>Butuh bantuan? Balas surel ini atau hubungi
                                    <a href="mailto:{{ $brand['support_email'] }}" style="color:#0a0a0a;">{{ $brand['support_email'] }}</a>.
                                @endif
                                <br>Surel ini dikirim otomatis oleh sistem — mohon jangan bagikan isinya kepada siapa pun.
                            </div>
                        </td>
                    </tr>
                </table>

                <div style="max-width:560px;padding:12px 4px 0;font-size:11px;color:#a1a1a1;">
                    {{ now()->translatedFormat('d F Y, H.i') }} · {{ config('app.url') }}
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
