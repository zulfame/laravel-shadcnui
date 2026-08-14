<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

import Separator from '@/components/ui/Separator.vue';
import SidebarProvider from '@/components/ui/sidebar/SidebarProvider.vue';
import SidebarInset from '@/components/ui/sidebar/SidebarInset.vue';
import SidebarTrigger from '@/components/ui/sidebar/SidebarTrigger.vue';
import AppSidebar from '@/components/layout/AppSidebar.vue';
import NotificationsBell from '@/components/layout/NotificationsBell.vue';
import ModeToggle from '@/components/ModeToggle.vue';
import Toaster from '@/components/ui/Toaster.vue';
import { getBreadcrumb } from '@/config/navigation';
import { useFlashToast } from '@/composables/useFlashToast';

/**
 * AppLayout — shell aplikasi.
 * Sidebar (kuncup ke ikon) + header tetap (trigger + breadcrumb + aksi).
 * Hanya area konten yang bergulir; shell dikunci ke `h-svh`.
 * Kerapatan permanen Compact — tidak ada pemilih kerapatan di header.
 */
const page = usePage();
const trail = computed(() => getBreadcrumb(page.url.split('?')[0]).trail);

useFlashToast();
</script>

<template>
    <SidebarProvider class="h-svh">
        <AppSidebar />
        <SidebarInset class="overflow-hidden">
            <!-- h-[65px] menyamai header sidebar (64px + 1px border) agar kedua
                 garis bawah menyambung menjadi satu baris. -->
            <header
                class="flex h-[65px] shrink-0 items-center gap-2 border-b border-border bg-background px-4"
            >
                <SidebarTrigger class="-ml-1" />
                <Separator orientation="vertical" class="mr-1 h-4" />
                <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-sm" data-testid="breadcrumb">
                    <template v-for="(label, index) in trail" :key="`${label}-${index}`">
                        <span
                            v-if="index < trail.length - 1"
                            class="hidden text-muted-foreground md:block"
                        >
                            {{ label }}
                        </span>
                        <ChevronRight
                            v-if="index < trail.length - 1"
                            class="hidden size-3.5 text-muted-foreground md:block"
                            aria-hidden="true"
                        />
                        <span v-if="index === trail.length - 1" class="font-medium text-foreground">
                            {{ label }}
                        </span>
                    </template>
                </nav>
                <div class="ml-auto flex items-center gap-1">
                    <NotificationsBell />
                    <ModeToggle />
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-4 lg:p-6">
                <slot />
            </div>
        </SidebarInset>
        <Toaster />
    </SidebarProvider>
</template>
