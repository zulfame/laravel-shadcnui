<script setup>
import { Bell, CheckCheck } from 'lucide-vue-next';
import { PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger } from 'reka-ui';

import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';

const items = [
    { id: 1, title: 'Pengguna baru terdaftar', meta: 'Rani Kusuma · 5 menit lalu', unread: true },
    { id: 2, title: 'Peranan "Editor" diperbarui', meta: 'Budi Santoso · 1 jam lalu', unread: true },
    { id: 3, title: 'Cadangan basis data selesai', meta: 'Sistem · 3 jam lalu', unread: false },
];
const unread = items.filter((i) => i.unread).length;
</script>

<template>
    <PopoverRoot>
        <PopoverTrigger as-child>
            <Button variant="ghost" size="icon" class="relative size-8" aria-label="Notifikasi" data-testid="notifications-trigger">
                <Bell class="size-4" />
                <span
                    v-if="unread"
                    class="absolute right-1 top-1 size-1.5 rounded-full bg-destructive"
                    aria-hidden="true"
                />
            </Button>
        </PopoverTrigger>
        <PopoverPortal>
            <PopoverContent
                align="end"
                :side-offset="6"
                class="z-50 w-80 overflow-hidden rounded-xl border bg-card p-0 text-card-foreground shadow-md"
            >
                <div class="flex items-center justify-between border-b border-border bg-sidebar px-4 py-3">
                    <span class="flex items-center gap-2 text-sm font-semibold">
                        Notifikasi
                        <Badge variant="secondary" class="font-normal tabular-nums">{{ unread }}</Badge>
                    </span>
                    <Button variant="ghost" size="sm" data-testid="notifications-mark-all">
                        <CheckCheck class="size-4" /> Tandai
                    </Button>
                </div>
                <ul class="thin-scroll max-h-72 divide-y overflow-y-auto">
                    <li
                        v-for="item in items"
                        :key="item.id"
                        class="flex items-start gap-3 px-4 py-2.5 transition-colors hover:bg-muted/40"
                        :data-testid="`notification-${item.id}`"
                    >
                        <span
                            class="mt-1.5 size-1.5 shrink-0 rounded-full"
                            :class="item.unread ? 'bg-foreground' : 'bg-transparent'"
                            aria-hidden="true"
                        />
                        <span class="min-w-0">
                            <span class="block truncate text-[13px] font-medium">{{ item.title }}</span>
                            <span class="block truncate text-xs text-muted-foreground">{{ item.meta }}</span>
                        </span>
                    </li>
                </ul>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>
