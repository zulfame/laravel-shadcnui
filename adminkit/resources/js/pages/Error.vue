<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowUpRight,
    Ban,
    Clock,
    Hourglass,
    KeyRound,
    LayoutDashboard,
    LogIn,
    RotateCcw,
    ScrollText,
    SearchX,
    ServerCrash,
    ShieldCheck,
    Users2,
    Wrench,
} from 'lucide-vue-next';

import BrandMark from '@/components/composite/BrandMark.vue';
import Button from '@/components/ui/Button.vue';
import ModeToggle from '@/components/ModeToggle.vue';
import Separator from '@/components/ui/Separator.vue';

const props = defineProps({
    status: { type: Number, required: true },
    path: { type: String, default: '' },
    reference: { type: String, default: '' },
});

const page = usePage();
const branding = computed(() => page.props.branding ?? {});
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));

const CATALOG = {
    401: {
        icon: LogIn,
        tag: 'Sesi Berakhir',
        title: 'Sesi Anda Sudah Berakhir',
        description:
            'Demi keamanan, sesi otomatis berakhir setelah tidak digunakan. Masuk kembali untuk melanjutkan pekerjaan Anda.',
        hint: 'Data yang belum tersimpan mungkin perlu diisi ulang.',
    },
    403: {
        icon: Ban,
        tag: 'Akses Ditolak',
        title: 'Anda Tidak Punya Izin Ke Halaman Ini',
        description:
            'Peranan akun Anda belum memiliki hak akses untuk membuka halaman ini. Hubungi administrator bila seharusnya Anda punya akses.',
        hint: 'Percobaan akses ini tercatat pada audit trail.',
    },
    404: {
        icon: SearchX,
        tag: 'Tidak Ditemukan',
        title: 'Halaman Yang Anda Cari Tidak Ada',
        description:
            'Alamat mungkin salah tulis, sudah dipindahkan, atau datanya telah dihapus. Coba mulai lagi dari salah satu pintasan berikut.',
        hint: 'Periksa kembali ejaan alamat pada bilah peramban.',
    },
    419: {
        icon: Clock,
        tag: 'Kedaluwarsa',
        title: 'Halaman Terlalu Lama Terbuka',
        description:
            'Token keamanan halaman ini sudah kedaluwarsa sehingga permintaan tidak dapat diproses. Muat ulang lalu coba sekali lagi.',
        hint: 'Kejadian normal bila halaman dibiarkan terbuka sangat lama.',
    },
    429: {
        icon: Hourglass,
        tag: 'Dibatasi Sementara',
        title: 'Terlalu Banyak Permintaan',
        description:
            'Permintaan dari perangkat Anda dibatasi sementara demi keamanan. Tunggu sejenak sebelum mencoba kembali.',
        hint: 'Batas otomatis terbuka setelah beberapa menit.',
    },
    500: {
        icon: ServerCrash,
        tag: 'Kesalahan Server',
        title: 'Terjadi Kesalahan Di Sisi Sistem',
        description:
            'Permintaan Anda tidak dapat diselesaikan karena kendala internal. Kejadian ini sudah tercatat lengkap pada audit trail.',
        hint: 'Sertakan kode referensi di bawah saat melaporkan kendala ini.',
    },
    503: {
        icon: Wrench,
        tag: 'Layanan Dijeda',
        title: 'Aplikasi Sedang Dalam Pemeliharaan',
        description: 'Kami sedang melakukan pemeliharaan singkat. Silakan coba beberapa menit lagi.',
        hint: 'Halaman akan tersedia otomatis setelah pemeliharaan selesai.',
    },
};

const FALLBACK = {
    icon: ServerCrash,
    tag: 'Kesalahan',
    title: 'Permintaan Tidak Dapat Diselesaikan',
    description: 'Terjadi kendala yang tidak dikenali saat memproses permintaan Anda.',
    hint: '',
};

const LINKS = [
    { href: '/', label: 'Dashboard', icon: LayoutDashboard },
    { href: '/users', label: 'Pengguna', icon: Users2 },
    { href: '/roles', label: 'Peranan', icon: ShieldCheck },
    { href: '/permissions', label: 'Perizinan', icon: KeyRound },
    { href: '/audit-trail', label: 'Audit Trail', icon: ScrollText },
];

const detail = computed(() => CATALOG[props.status] ?? FALLBACK);
const digits = computed(() => String(props.status).split(''));
const now = new Date().toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });

const reload = () => window.location.reload();
</script>

<template>
    <Head :title="`${status} · ${detail.tag}`" />

    <div class="relative flex min-h-svh flex-col overflow-hidden bg-background">
        <!-- Latar: kisi halus + sorot lembut di sudut. -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,hsl(var(--foreground)/0.045)_1px,transparent_1px),linear-gradient(to_bottom,hsl(var(--foreground)/0.045)_1px,transparent_1px)] bg-[size:38px_38px] [mask-image:radial-gradient(120%_90%_at_85%_0%,black,transparent_70%)]"
        />
        <div
            aria-hidden="true"
            class="pointer-events-none absolute -right-40 -top-40 size-[34rem] rounded-full bg-foreground/[0.05] blur-3xl"
        />

        <header class="relative z-10 flex h-[65px] shrink-0 items-center justify-between border-b border-border/70 px-5 sm:px-8">
            <div class="flex items-center gap-2.5">
                <BrandMark
                    :logo="branding.logo_light || branding.logo_dark"
                    :initials="branding.brand_initials"
                    class="size-8 shrink-0 bg-primary text-[11px] font-semibold text-primary-foreground"
                />
                <span class="flex flex-col leading-tight">
                    <span class="text-sm font-semibold tracking-tight">{{ branding.app_name || 'AdminKit' }}</span>
                    <span class="text-xs text-muted-foreground">{{ branding.tagline }}</span>
                </span>
            </div>
            <ModeToggle />
        </header>

        <main class="relative z-10 flex flex-1 items-center px-5 py-10 sm:px-8" data-testid="error-page">
            <div class="mx-auto grid w-full max-w-6xl items-center gap-10 lg:grid-cols-[1.05fr_.95fr] lg:gap-16">
                <!-- Kolom konten -->
                <div class="order-2 space-y-6 lg:order-1">
                    <span
                        class="animate-in fade-in slide-in-from-bottom-2 inline-flex items-center gap-2 rounded-full border border-border bg-card px-3 py-1 text-xs text-muted-foreground duration-500"
                    >
                        <span class="relative flex size-1.5">
                            <span
                                class="absolute inline-flex size-full animate-ping rounded-full"
                                :class="status >= 500 ? 'bg-destructive' : 'bg-foreground'"
                            />
                            <span
                                class="relative inline-flex size-1.5 rounded-full"
                                :class="status >= 500 ? 'bg-destructive' : 'bg-foreground'"
                            />
                        </span>
                        <span class="font-mono tabular-nums">{{ status }}</span>
                        <Separator orientation="vertical" class="h-3" />
                        {{ detail.tag }}
                    </span>

                    <h1
                        class="animate-in fade-in slide-in-from-bottom-3 text-3xl font-semibold leading-[1.1] tracking-tight duration-700 sm:text-4xl lg:text-5xl"
                        data-testid="error-title"
                    >
                        {{ detail.title }}
                    </h1>

                    <p
                        class="animate-in fade-in slide-in-from-bottom-3 max-w-xl text-sm leading-relaxed text-muted-foreground delay-100 duration-700"
                        data-testid="error-description"
                    >
                        {{ detail.description }}
                    </p>

                    <div class="flex flex-wrap items-center gap-2">
                        <Button v-if="isAuthenticated" as="a" href="/" size="sm" data-testid="error-home">
                            <ArrowLeft class="size-4" /> Kembali Ke Dasbor
                        </Button>
                        <Button v-else as="a" href="/login" size="sm" data-testid="error-login">
                            <LogIn class="size-4" /> Masuk Kembali
                        </Button>
                        <Button variant="outline" size="sm" data-testid="error-reload" @click="reload">
                            <RotateCcw class="size-4" /> Muat Ulang
                        </Button>
                    </div>

                    <Separator />

                    <dl class="grid gap-x-10 gap-y-3 text-xs sm:grid-cols-3" data-testid="error-meta">
                        <div v-if="path" class="flex min-w-0 flex-col gap-1">
                            <dt class="text-muted-foreground">Alamat</dt>
                            <dd class="truncate font-mono text-[11px]">{{ path }}</dd>
                        </div>
                        <div v-if="reference" class="flex flex-col gap-1">
                            <dt class="text-muted-foreground">Kode Referensi</dt>
                            <dd class="font-mono text-[11px]">{{ reference }}</dd>
                        </div>
                        <div class="flex flex-col gap-1">
                            <dt class="text-muted-foreground">Waktu</dt>
                            <dd class="font-mono text-[11px]">{{ now }}</dd>
                        </div>
                    </dl>

                    <p v-if="detail.hint" class="text-xs text-muted-foreground">{{ detail.hint }}</p>

                    <p v-if="branding.support_email" class="text-xs text-muted-foreground">
                        Butuh bantuan?
                        <a
                            :href="`mailto:${branding.support_email}`"
                            class="font-medium text-foreground underline-offset-4 transition-colors hover:underline"
                            data-testid="error-support-email"
                        >
                            {{ branding.support_email }}
                        </a>
                    </p>
                </div>

                <!-- Kolom visual: kode status + pintasan -->
                <div class="order-1 space-y-4 lg:order-2">
                    <div
                        class="relative overflow-hidden rounded-xl border border-border bg-card/60 p-6 backdrop-blur-sm"
                        data-testid="error-status"
                    >
                        <div
                            aria-hidden="true"
                            class="pointer-events-none absolute inset-0 opacity-[0.07] [background-image:repeating-linear-gradient(45deg,hsl(var(--foreground))_0_1px,transparent_1px_9px)]"
                        />
                        <div class="relative flex items-end justify-center gap-1 sm:gap-2">
                            <span
                                v-for="(digit, index) in digits"
                                :key="`${digit}-${index}`"
                                class="animate-in fade-in zoom-in-50 font-mono text-6xl font-semibold leading-none tracking-tighter text-foreground/15 duration-700 sm:text-7xl lg:text-8xl"
                                :style="{ animationDelay: `${index * 90}ms` }"
                            >
                                {{ digit }}
                            </span>
                        </div>
                        <div class="relative mt-5 flex items-center justify-center gap-2">
                            <span
                                class="flex size-8 items-center justify-center rounded-md border border-border bg-background"
                            >
                                <component :is="detail.icon" class="size-4" aria-hidden="true" />
                            </span>
                            <span class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">
                                {{ detail.tag }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="isAuthenticated"
                        class="overflow-hidden rounded-xl border border-border bg-card/60 backdrop-blur-sm"
                        data-testid="error-links"
                    >
                        <p class="border-b border-border bg-muted/40 px-4 py-2 text-xs font-semibold">
                            Pintasan Cepat
                        </p>
                        <ul class="divide-y divide-border">
                            <li v-for="link in LINKS" :key="link.href">
                                <a
                                    :href="link.href"
                                    class="group flex items-center gap-3 px-4 py-2.5 text-[13px] transition-colors hover:bg-muted/50"
                                    :data-testid="`error-link-${link.label.toLowerCase().replace(' ', '-')}`"
                                >
                                    <component
                                        :is="link.icon"
                                        class="size-4 text-muted-foreground transition-colors group-hover:text-foreground"
                                        aria-hidden="true"
                                    />
                                    <span class="flex-1 font-medium">{{ link.label }}</span>
                                    <ArrowUpRight
                                        class="size-3.5 text-muted-foreground transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5"
                                        aria-hidden="true"
                                    />
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>

        <footer
            class="relative z-10 flex shrink-0 flex-wrap items-center justify-between gap-2 border-t border-border/70 px-5 py-3 text-xs text-muted-foreground sm:px-8"
        >
            <span>{{ branding.footer_text || branding.app_name }}</span>
            <span class="font-mono">HTTP {{ status }}</span>
        </footer>
    </div>
</template>
