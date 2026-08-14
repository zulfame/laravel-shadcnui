<script setup>
import { Head } from '@inertiajs/vue3';
import {
    ArrowRight,
    Clock,
    HardDrive,
    ListChecks,
    RefreshCw,
    ShieldCheck,
    UserPlus,
    Users2,
} from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Avatar from '@/components/ui/Avatar.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardFooter from '@/components/ui/CardFooter.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Progress from '@/components/ui/Progress.vue';
import StateChip from '@/components/composite/StateChip.vue';
import MiniBarChart from '@/components/composite/MiniBarChart.vue';
import HBarChart from '@/components/composite/HBarChart.vue';
import { initialsOf } from '@/lib/utils';
import { ACTION } from '@/constants/labels';

const props = defineProps({
    kpis: { type: Array, default: () => [] },
    recentUsers: { type: Array, default: () => [] },
    activities: { type: Array, default: () => [] },
    trend: { type: Array, default: () => [] },
    byModule: { type: Array, default: () => [] },
    storage: { type: Array, default: () => [] },
});

const ICONS = {
    users: Users2,
    roles: ShieldCheck,
    sessions: Clock,
    storage: HardDrive,
    queue: ListChecks,
};

const trendSeries = [
    { key: 'created', label: 'Pengguna baru', token: '--ev-meeting' },
    { key: 'active', label: 'Aktif harian', token: '--success' },
];

const totalModule = props.byModule.reduce((a, b) => a + b.count, 0);
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <div class="space-y-6" data-testid="dashboard-page">
            <!-- KPI -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <Card v-for="kpi in props.kpis" :key="kpi.key" :data-testid="`stat-${kpi.key}`">
                    <CardContent class="flex items-start justify-between gap-3">
                        <div class="min-w-0 space-y-1">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ kpi.label }}</p>
                            <p class="text-base font-semibold tabular-nums">{{ kpi.value }}</p>
                            <p class="truncate text-xs text-muted-foreground">{{ kpi.hint }}</p>
                        </div>
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-md border bg-muted/40">
                            <component
                                :is="ICONS[kpi.key] ?? Users2"
                                class="size-4 text-muted-foreground"
                                aria-hidden="true"
                            />
                        </span>
                    </CardContent>
                </Card>
            </div>

            <!-- Kartu daftar: tinggi terkunci + isi bergulir -->
            <div class="grid gap-6 lg:h-[31.5rem] lg:grid-cols-3">
                <Card class="flex flex-col lg:col-span-2">
                    <CardHeader class="flex flex-col gap-3 space-y-0 sm:flex-row sm:items-center sm:justify-between">
                        <CardTitle class="flex items-center gap-2">
                            Pengguna Terbaru
                            <Badge variant="secondary" class="font-normal tabular-nums">
                                {{ props.recentUsers.length }}
                            </Badge>
                        </CardTitle>
                        <div class="flex flex-wrap items-center gap-2">
                            <Button variant="outline" size="sm" data-testid="dashboard-refresh">
                                <RefreshCw class="size-4" /> {{ ACTION.refresh }}
                            </Button>
                            <Button size="sm" data-testid="dashboard-new-user">
                                <UserPlus class="size-4" /> Pengguna Baru
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent class="min-h-0 flex-1 p-0">
                        <div class="thin-scroll h-full divide-y overflow-y-auto" data-testid="recent-users">
                            <div
                                v-for="user in props.recentUsers"
                                :key="user.id"
                                class="flex items-center gap-3 px-6 py-2 transition-colors hover:bg-muted/40"
                                :data-testid="`recent-user-${user.id}`"
                            >
                                <Avatar :fallback="initialsOf(user.name, user.email)" class="size-7" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[13px] font-medium">{{ user.name }}</span>
                                    <span class="block truncate text-xs text-muted-foreground">{{ user.email }}</span>
                                </span>
                                <span class="hidden w-28 shrink-0 text-xs text-muted-foreground sm:block">
                                    {{ user.role }}
                                </span>
                                <span class="hidden w-24 shrink-0 items-center gap-2 md:flex">
                                    <Progress :value="user.completeness" class="h-1.5 flex-1" />
                                    <span class="w-8 text-right text-xs tabular-nums text-muted-foreground">
                                        {{ user.completeness }}%
                                    </span>
                                </span>
                                <span class="flex w-[5.5rem] shrink-0 justify-end">
                                    <StateChip :label="user.status_label" :chip="user.status_chip" />
                                </span>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter class="justify-end">
                        <Button variant="outline" size="sm" data-testid="link-all-users">
                            Lihat semua pengguna <ArrowRight class="size-4" />
                        </Button>
                    </CardFooter>
                </Card>

                <div class="flex flex-col gap-6 lg:min-h-0">
                    <Card class="flex min-h-0 flex-[2] flex-col">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                Aktivitas Terakhir
                                <Badge variant="secondary" class="font-normal tabular-nums">
                                    {{ props.activities.length }}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="min-h-0 flex-1 p-0">
                            <div class="thin-scroll h-full divide-y overflow-y-auto" data-testid="recent-activities">
                                <div
                                    v-for="log in props.activities"
                                    :key="log.id"
                                    class="flex items-start gap-3 px-6 py-2 transition-colors hover:bg-muted/40"
                                >
                                    <span class="w-11 shrink-0 text-xs font-medium tabular-nums text-muted-foreground">
                                        {{ log.time }}
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-[13px] font-medium">{{ log.action }}</span>
                                        <span class="block truncate text-xs text-muted-foreground">
                                            {{ log.actor }} · {{ log.module }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter class="justify-end">
                            <Button variant="outline" size="sm">
                                Semua aktivitas <ArrowRight class="size-4" />
                            </Button>
                        </CardFooter>
                    </Card>

                    <Card class="flex min-h-0 flex-1 flex-col">
                        <CardHeader>
                            <CardTitle>Penyimpanan</CardTitle>
                        </CardHeader>
                        <CardContent class="thin-scroll min-h-0 flex-1 space-y-3 overflow-y-auto">
                            <div v-for="item in props.storage" :key="item.label" class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-muted-foreground">{{ item.label }}</span>
                                    <span class="tabular-nums">{{ item.used }} / {{ item.total }}</span>
                                </div>
                                <Progress :value="item.percent" class="h-1.5" />
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Tren Mingguan</CardTitle>
                </CardHeader>
                <CardContent>
                    <MiniBarChart :data="props.trend" :series="trendSeries" :height="220" />
                </CardContent>
            </Card>

            <div class="grid items-start gap-6 lg:grid-cols-2">
                <Card data-testid="card-activity-module">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            Aktivitas per Modul
                            <Badge variant="secondary" class="font-normal tabular-nums">{{ totalModule }}</Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <HBarChart :data="props.byModule" :height="220" />
                    </CardContent>
                </Card>

                <Card data-testid="card-role-distribution">
                    <CardHeader>
                        <CardTitle>Sebaran Peranan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <HBarChart
                            :data="[
                                { label: 'Staf', count: 96 },
                                { label: 'Editor', count: 18 },
                                { label: 'Auditor', count: 5 },
                                { label: 'Administrator', count: 3 },
                            ]"
                            :height="220"
                        />
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
