<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { LayoutDashboard, ShieldCheck, SlidersHorizontal, Waves } from 'lucide-vue-next';

import Separator from '@/components/ui/Separator.vue';

/**
 * AuthLayout — shell split-screen untuk halaman autentikasi:
 * panel brand kiri (desktop, overlay grid dekoratif bertoken) + panel konten
 * kanan (`max-w-md`).
 */
const page = usePage();
const branding = computed(() => page.props.branding ?? {});

const highlights = [
    {
        icon: LayoutDashboard,
        title: 'Shell admin siap pakai',
        description: 'Sidebar berbasis area, breadcrumb, dan header aksi sudah tertata.',
    },
    {
        icon: ShieldCheck,
        title: 'Peranan & hak akses',
        description: 'Matriks izin per modul dengan pola kartu yang konsisten.',
    },
    {
        icon: SlidersHorizontal,
        title: 'Token desain dua lapis',
        description: 'Monokrom, compact, dan mendukung tema terang maupun gelap.',
    },
];
</script>

<template>
    <div class="grid min-h-screen w-full lg:grid-cols-2">
        <!-- Panel brand — desktop saja -->
        <aside
            class="relative hidden flex-col justify-between overflow-hidden bg-primary p-10 text-primary-foreground lg:flex xl:p-14"
        >
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,hsl(var(--primary-foreground)/0.06)_1px,transparent_1px),linear-gradient(to_bottom,hsl(var(--primary-foreground)/0.06)_1px,transparent_1px)] bg-[size:44px_44px] [mask-image:radial-gradient(ellipse_at_top_left,black,transparent_75%)]"
            />

            <div class="relative z-10 flex items-center gap-2.5" data-testid="auth-brand-desktop">
                <span
                    class="flex size-9 items-center justify-center rounded-md bg-primary-foreground/10 ring-1 ring-inset ring-primary-foreground/20"
                >
                    <Waves class="size-5" aria-hidden="true" />
                </span>
                <span class="flex flex-col">
                    <span class="text-sm font-semibold tracking-tight" data-testid="auth-brand-name">
                        {{ branding.app_name || 'AdminKit' }}
                    </span>
                    <span class="text-xs text-primary-foreground/60">{{ branding.company || 'Admin Panel Starter Kit' }}</span>
                </span>
            </div>

            <div class="relative z-10 max-w-md space-y-8">
                <div class="space-y-3">
                    <h1 class="text-3xl font-semibold leading-tight tracking-tight xl:text-4xl">
                        Fondasi panel admin yang rapi sejak menit pertama.
                    </h1>
                    <p class="text-sm leading-relaxed text-primary-foreground/70">
                        {{ branding.meta_description || 'Laravel 12 · Inertia · Vue 3 · TailwindCSS. Compact, monokrom, token-first.' }}
                    </p>
                </div>

                <Separator class="bg-primary-foreground/15" />

                <ul class="space-y-6">
                    <li v-for="item in highlights" :key="item.title" class="flex items-start gap-3">
                        <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-md bg-primary-foreground/10">
                            <component :is="item.icon" class="size-4" aria-hidden="true" />
                        </span>
                        <div class="space-y-0.5">
                            <p class="text-sm font-medium">{{ item.title }}</p>
                            <p class="text-sm leading-relaxed text-primary-foreground/60">{{ item.description }}</p>
                        </div>
                    </li>
                </ul>
            </div>

            <p class="relative z-10 text-xs text-primary-foreground/50">
                {{ branding.footer_text || `© ${new Date().getFullYear()} AdminKit` }}
            </p>
        </aside>

        <!-- Panel konten -->
        <main class="flex flex-col items-center justify-center bg-background px-4 py-10 sm:px-6 lg:px-8">
            <div class="w-full max-w-md">
                <div class="mb-8 flex items-center gap-2.5 lg:hidden" data-testid="auth-brand-mobile">
                    <span class="flex size-9 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <Waves class="size-5" aria-hidden="true" />
                    </span>
                    <span class="flex flex-col">
                        <span class="text-sm font-semibold tracking-tight">{{ branding.app_name || 'AdminKit' }}</span>
                        <span class="text-xs text-muted-foreground">{{ branding.company || 'Admin Panel Starter Kit' }}</span>
                    </span>
                </div>

                <slot />

                <div class="mt-8 space-y-1 text-center text-xs text-muted-foreground" data-testid="auth-footer">
                    <p v-if="branding.support_email">
                        Butuh bantuan?
                        <a
                            :href="`mailto:${branding.support_email}`"
                            class="font-medium text-foreground underline-offset-4 hover:underline"
                        >
                            {{ branding.support_email }}
                        </a>
                    </p>
                    <p>{{ branding.footer_text || `© ${new Date().getFullYear()} AdminKit` }}</p>
                </div>
            </div>
        </main>
    </div>
</template>
