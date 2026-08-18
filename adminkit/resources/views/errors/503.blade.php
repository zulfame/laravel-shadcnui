<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta http-equiv="refresh" content="{{ $retryAfter ?? 60 }}">
    <title>Sedang Pemeliharaan · {{ $appName ?? config('app.name') }}</title>
    <style>
        /* Sengaja mandiri (tanpa Vite/DB) agar tetap tampil saat aplikasi dimatikan. */
        :root {
            --bg: #ffffff;
            --fg: #0a0a0a;
            --muted: #737373;
            --border: #e7e7e7;
            --card: #fafafa;
            --grid: rgba(10, 10, 10, .045);
            --ghost: rgba(10, 10, 10, .12);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0a0a0a;
                --fg: #fafafa;
                --muted: #a1a1a1;
                --border: #232323;
                --card: #121212;
                --grid: rgba(250, 250, 250, .05);
                --ghost: rgba(250, 250, 250, .14);
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100svh;
            display: flex;
            flex-direction: column;
            background: var(--bg);
            color: var(--fg);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .grid-bg, .glow { position: fixed; pointer-events: none; }

        .grid-bg {
            inset: 0;
            background-image:
                linear-gradient(to right, var(--grid) 1px, transparent 1px),
                linear-gradient(to bottom, var(--grid) 1px, transparent 1px);
            background-size: 38px 38px;
            -webkit-mask-image: radial-gradient(120% 90% at 85% 0%, black, transparent 70%);
            mask-image: radial-gradient(120% 90% at 85% 0%, black, transparent 70%);
        }

        .glow {
            top: -10rem;
            right: -10rem;
            width: 34rem;
            height: 34rem;
            border-radius: 999px;
            background: var(--grid);
            filter: blur(64px);
        }

        header, footer {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 20px;
            border-bottom: 1px solid var(--border);
            height: 65px;
            flex-shrink: 0;
        }

        footer {
            border-bottom: 0;
            border-top: 1px solid var(--border);
            height: auto;
            padding: 12px 20px;
            justify-content: space-between;
            font-size: 12px;
            color: var(--muted);
        }

        .mark {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: var(--fg);
            color: var(--bg);
            font-size: 11px;
            font-weight: 600;
        }

        .brand { display: flex; flex-direction: column; line-height: 1.25; }
        .brand b { font-size: 14px; font-weight: 600; letter-spacing: -.01em; }
        .brand span { font-size: 12px; color: var(--muted); }

        main {
            position: relative;
            flex: 1;
            display: flex;
            align-items: center;
            padding: 40px 20px;
        }

        .wrap {
            width: 100%;
            max-width: 72rem;
            margin: 0 auto;
            display: grid;
            gap: 40px;
            align-items: center;
        }

        @media (min-width: 1024px) {
            .wrap { grid-template-columns: 1.05fr .95fr; gap: 64px; }
            .visual { order: 2; }
            .content { order: 1; }
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 12px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--card);
            font-size: 12px;
            color: var(--muted);
        }

        .chip code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .chip .sep { width: 1px; height: 12px; background: var(--border); }

        .dot { position: relative; display: flex; width: 6px; height: 6px; }
        .dot i, .dot b {
            position: absolute;
            inset: 0;
            border-radius: 999px;
            background: var(--fg);
        }
        .dot i { animation: ping 1.6s cubic-bezier(0, 0, .2, 1) infinite; }

        @keyframes ping { 75%, 100% { transform: scale(2.4); opacity: 0; } }

        h1 {
            margin: 22px 0 12px;
            font-size: clamp(1.875rem, 4.4vw, 3rem);
            font-weight: 600;
            letter-spacing: -.02em;
            line-height: 1.1;
        }

        p { margin: 0; max-width: 34rem; font-size: 14px; line-height: 1.65; color: var(--muted); }

        hr { margin: 24px 0; border: 0; border-top: 1px solid var(--border); }

        .actions { display: flex; flex-wrap: wrap; gap: 8px; }

        a.button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border: 1px solid var(--fg);
            border-radius: 6px;
            background: var(--fg);
            color: var(--bg);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: transform .15s ease, opacity .15s ease;
        }

        a.button.ghost { background: var(--card); color: var(--fg); border-color: var(--border); }
        a.button:hover { transform: translateY(-1px); opacity: .92; }

        dl.meta { display: grid; gap: 12px 40px; margin: 0; font-size: 12px; }
        @media (min-width: 640px) { dl.meta { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        dl.meta div { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
        dl.meta dt { color: var(--muted); }
        dl.meta dd { margin: 0; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; }

        .panel {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--card);
            padding: 24px;
            text-align: center;
        }

        .panel::before {
            content: "";
            position: absolute;
            inset: 0;
            opacity: .07;
            background-image: repeating-linear-gradient(45deg, var(--fg) 0 1px, transparent 1px 9px);
        }

        .digits {
            position: relative;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 6px;
        }

        .digits span {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: clamp(3.5rem, 9vw, 6rem);
            font-weight: 600;
            line-height: 1;
            letter-spacing: -.04em;
            color: var(--ghost);
            animation: rise .7s cubic-bezier(.2, .7, .2, 1) both;
        }

        .digits span:nth-child(2) { animation-delay: .09s; }
        .digits span:nth-child(3) { animation-delay: .18s; }

        @keyframes rise { from { opacity: 0; transform: translateY(10px) scale(.94); } }

        .panel .label {
            position: relative;
            margin-top: 18px;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .bar {
            position: relative;
            margin-top: 20px;
            height: 4px;
            border-radius: 999px;
            background: var(--border);
            overflow: hidden;
        }

        .bar i {
            position: absolute;
            inset: 0 auto 0 0;
            width: 38%;
            border-radius: 999px;
            background: var(--fg);
            opacity: .55;
            animation: slide 2.4s ease-in-out infinite;
        }

        @keyframes slide {
            0% { left: -40%; }
            100% { left: 102%; }
        }

        .steps {
            margin-top: 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--card);
            overflow: hidden;
        }

        .steps p {
            margin: 0;
            padding: 8px 16px;
            border-bottom: 1px solid var(--border);
            background: var(--grid);
            font-size: 12px;
            font-weight: 600;
            color: var(--fg);
        }

        .steps ul { margin: 0; padding: 0; list-style: none; }
        .steps li {
            display: flex;
            gap: 12px;
            padding: 10px 16px;
            font-size: 13px;
            border-bottom: 1px solid var(--border);
        }
        .steps li:last-child { border-bottom: 0; }
        .steps li b { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: var(--muted); font-weight: 500; }
        .steps li span { color: var(--muted); }
    </style>
</head>
<body>
    <div class="grid-bg"></div>
    <div class="glow"></div>

    <header>
        <span class="mark">{{ $brandInitials ?: mb_strtoupper(mb_substr($appName ?? config('app.name'), 0, 2)) }}</span>
        <span class="brand">
            <b>{{ $appName ?? config('app.name') }}</b>
            <span>{{ $tagline ?? 'Mode Pemeliharaan' }}</span>
        </span>
    </header>

    <main>
        <div class="wrap">
            <div class="content">
                <span class="chip">
                    <span class="dot"><i></i><b></b></span>
                    <code>503</code>
                    <span class="sep"></span>
                    Layanan Dijeda
                </span>

                <h1>Aplikasi Sedang Dalam Pemeliharaan</h1>

                <p>
                    Kami sedang memasang pembaruan agar aplikasi kembali lebih cepat dan stabil.
                    Prosesnya singkat — halaman ini akan menyegarkan dirinya sendiri, jadi Anda tidak perlu menunggu
                    sambil menekan tombol.
                </p>

                <hr>

                <div class="actions">
                    <a class="button" href="{{ url()->current() }}">Muat Ulang Sekarang</a>
                    @isset($supportEmail)
                        <a class="button ghost" href="mailto:{{ $supportEmail }}">Hubungi Dukungan</a>
                    @endisset
                </div>

                <hr>

                <dl class="meta">
                    <div>
                        <dt>Coba Lagi Otomatis</dt>
                        <dd>{{ $retryAfter ?? 60 }} detik</dd>
                    </div>
                    <div>
                        <dt>Waktu Server</dt>
                        <dd>{{ now()->translatedFormat('d M Y, H.i') }}</dd>
                    </div>
                    @isset($supportEmail)
                        <div>
                            <dt>Bantuan</dt>
                            <dd>{{ $supportEmail }}</dd>
                        </div>
                    @endisset
                </dl>
            </div>

            <div class="visual">
                <div class="panel">
                    <div class="digits"><span>5</span><span>0</span><span>3</span></div>
                    <div class="label">Service Unavailable</div>
                    <div class="bar"><i></i></div>
                </div>

                <div class="steps">
                    <p>Yang Sedang Kami Lakukan</p>
                    <ul>
                        <li><b>01</b> Memasang pembaruan aplikasi</li>
                        <li><b>02</b> Menyelaraskan struktur basis data</li>
                        <li><b>03</b> Membersihkan berkas cache</li>
                        <li><b>04</b> <span>Menyalakan kembali layanan</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <span>{{ $footerText ?? $appName ?? config('app.name') }}</span>
        <span style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">HTTP 503</span>
    </footer>
</body>
</html>
