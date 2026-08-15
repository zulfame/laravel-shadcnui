<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Ban, Clock, Hourglass, LogIn, RotateCcw, SearchX, ServerCrash, Wrench } from 'lucide-vue-next';

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
        title: 'Sesi Berakhir',
        description: 'Sesi Anda sudah tidak berlaku. Silakan masuk kembali untuk melanjutkan.',
    },
    403: {
        icon: Ban,
        title: 'Akses Ditolak',
        description: 'Akun Anda tidak memiliki izin untuk membuka halaman ini. Hubungi administrator bila Anda merasa seharusnya punya akses.',
    },
    404: {
        icon: SearchX,
        title: 'Halaman Tidak Ditemukan',
        description: 'Alamat yang Anda tuju tidak tersedia, mungkin sudah dipindahkan atau dihapus.',
    },
    419: {
        icon: Clock,
        title: 'Halaman Kedaluwarsa',
        description: 'Halaman terlalu lama dibiarkan terbuka sehingga token keamanannya kedaluwarsa. Muat ulang lalu coba lagi.',
    },
    429: {
        icon: Hourglass,
        title: 'Terlalu Banyak Permintaan',
        description: 'Permintaan Anda dibatasi sementara demi keamanan. Tunggu beberapa saat sebelum mencoba kembali.',
    },
    500: {
        icon: ServerCrash,
        title: 'Terjadi Kesalahan Sistem',
        description: 'Ada kendala di sisi server saat memproses permintaan Anda. Kejadian ini sudah tercatat pada audit trail.',
    },
    503: {
        icon: Wrench,
        title: 'Sedang Pemeliharaan',
        description: 'Aplikasi sedang dalam pemeliharaan singkat. Silakan coba beberapa menit lagi.',
    },
};

const detail = computed(
    () =>
        CATALOG[props.status] ?? {
            icon: ServerCrash,
            title: 'Terjadi Kesalahan',
            description: 'Permintaan Anda tidak dapat diselesaikan.',
        },
);

const reload = () => window.location.reload();
</script>

<template>
    <Head :title="`${status} · ${detail.title}`" />

    <div class="relative flex min-h-screen flex-col overflow-hidden bg-background">
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,hsl(var(--foreground)/0.05)_1px,transparent_1px),linear-gradient(to_bottom,hsl(var(--foreground)/0.05)_1px,transparent_1px)] bg-[size:44px_44px] [mask-image:radial-gradient(ellipse_at_top_right,black,transparent_70%)]"
        />

        <header class="relative z-10 flex h-[65px] items-center justify-between px-5 sm:px-8">
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

        <main class="relative z-10 flex flex-1 items-center px-5 pb-16 sm:px-8" data-testid="error-page">
            <div class="mx-auto w-full max-w-3xl space-y-7">
                <div class="flex items-center gap-3">
                    <span
                        class="flex size-9 shrink-0 items-center justify-center rounded-md border border-border bg-card text-foreground"
                    >
                        <component :is="detail.icon" class="size-4" aria-hidden="true" />
                    </span>
                    <span
                        class="text-2xl font-semibold leading-none tracking-tight text-muted-foreground"
                        data-testid="error-status"
                    >
                        {{ status }}
                    </span>
                </div>

                <div class="space-y-3">
                    <h1
                        class="text-3xl font-semibold tracking-tight sm:text-4xl lg:text-5xl"
                        data-testid="error-title"
                    >
                        {{ detail.title }}
                    </h1>
                    <p class="max-w-xl text-sm leading-relaxed text-muted-foreground" data-testid="error-description">
                        {{ detail.description }}
                    </p>
                </div>

                <Separator />

                <div class="flex flex-wrap items-center gap-2">
                    <Button v-if="isAuthenticated" as="a" href="/" size="sm" data-testid="error-home">
                        <ArrowLeft class="size-4" /> Kembali Ke Dasbor
                    </Button>
                    <Button v-else as="a" href="/login" size="sm" data-testid="error-login">
                        <LogIn class="size-4" /> Masuk
                    </Button>

                    <Button variant="outline" size="sm" data-testid="error-reload" @click="reload">
                        <RotateCcw class="size-4" /> Muat Ulang
                    </Button>
                </div>

                <dl class="grid gap-x-8 gap-y-2 pt-2 text-xs sm:grid-cols-2" data-testid="error-meta">
                    <div v-if="path" class="flex flex-col gap-0.5">
                        <dt class="text-muted-foreground">Alamat</dt>
                        <dd class="truncate font-mono text-[11px] text-foreground">{{ path }}</dd>
                    </div>
                    <div v-if="reference" class="flex flex-col gap-0.5">
                        <dt class="text-muted-foreground">Kode Referensi</dt>
                        <dd class="font-mono text-[11px] text-foreground">{{ reference }}</dd>
                    </div>
                </dl>

                <p v-if="branding.support_email" class="text-xs text-muted-foreground">
                    Butuh bantuan?
                    <a
                        :href="`mailto:${branding.support_email}`"
                        class="font-medium text-foreground underline-offset-4 hover:underline"
                        data-testid="error-support-email"
                    >
                        {{ branding.support_email }}
                    </a>
                </p>
            </div>
        </main>
    </div>
</template>
