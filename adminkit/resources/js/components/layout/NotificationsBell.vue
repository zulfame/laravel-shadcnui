<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Bell, CheckCheck } from 'lucide-vue-next';
import { PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger } from 'reka-ui';

import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';

const page = usePage();

// Data berasal dari share Inertia dan HANYA memuat notifikasi milik
// pengguna yang sedang masuk.
const items = computed(() => page.props.notifications?.items ?? []);
const unread = computed(() => page.props.notifications?.unread ?? 0);

const reloadOnly = { preserveScroll: true, preserveState: true, only: ['notifications'] };

const markAll = () => router.post('/notifications/read-all', {}, reloadOnly);

const open = (item) => {
    if (item.unread) {
        router.post(`/notifications/${item.id}/read`, {}, item.url ? { preserveScroll: true } : reloadOnly);
    }

    if (item.url) router.get(item.url);
};

const DOT = {
    success: 'bg-foreground',
    info: 'bg-foreground',
    warning: 'bg-foreground',
    danger: 'bg-destructive',
};
</script>

<template>
    <PopoverRoot>
        <PopoverTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative size-8"
                aria-label="Notifikasi"
                data-testid="notifications-trigger"
            >
                <Bell class="size-4" />
                <span
                    v-if="unread"
                    class="absolute right-1 top-1 size-1.5 rounded-full bg-destructive"
                    data-testid="notifications-dot"
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
                        <Badge variant="secondary" class="font-normal tabular-nums" data-testid="notifications-count">
                            {{ unread }}
                        </Badge>
                    </span>
                    <Button
                        variant="ghost"
                        size="sm"
                        :disabled="!unread"
                        data-testid="notifications-mark-all"
                        @click="markAll"
                    >
                        <CheckCheck class="size-4" /> Tandai
                    </Button>
                </div>
                <ul v-if="items.length" class="thin-scroll max-h-72 divide-y overflow-y-auto">
                    <li v-for="item in items" :key="item.id">
                        <button
                            type="button"
                            class="flex w-full items-start gap-3 px-4 py-2.5 text-left transition-colors hover:bg-muted/40"
                            :data-testid="`notification-${item.id}`"
                            @click="open(item)"
                        >
                            <span
                                class="mt-1.5 size-1.5 shrink-0 rounded-full"
                                :class="item.unread ? DOT[item.level] ?? 'bg-foreground' : 'bg-transparent'"
                                :data-testid="`notification-${item.id}-unread-dot`"
                                aria-hidden="true"
                            />
                            <span class="min-w-0">
                                <span
                                    class="block truncate text-[13px]"
                                    :class="item.unread ? 'font-medium' : 'text-muted-foreground'"
                                >
                                    {{ item.title }}
                                </span>
                                <span class="block truncate text-xs text-muted-foreground">
                                    {{ [item.body, item.time].filter(Boolean).join(' · ') }}
                                </span>
                            </span>
                        </button>
                    </li>
                </ul>
                <p v-else class="px-4 py-6 text-center text-xs text-muted-foreground" data-testid="notifications-empty">
                    Belum ada notifikasi.
                </p>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>
