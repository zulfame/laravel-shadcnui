<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Download, ScrollText, Trash2, X } from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import { menuLabelOf } from '@/composables/useMenuLabel';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import DatePicker from '@/components/ui/DatePicker.vue';
import DataTableCard from '@/components/composite/DataTableCard.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Label from '@/components/ui/Label.vue';
import StateChip from '@/components/composite/StateChip.vue';
import { useServerTable } from '@/composables/useServerTable';
import { ACTION } from '@/constants/labels';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const canManage = computed(() => (page.props.auth?.user?.permissions ?? []).includes('activity.manage'));

const columns = [
    { key: 'created_at', label: 'Waktu' },
    { key: 'actor', label: 'Pelaku', sortKey: 'actor_name', hideBelow: 'md' },
    { key: 'action', label: 'Aksi' },
    { key: 'module', label: 'Modul', hideBelow: 'sm' },
    { key: 'level_label', label: 'Level', sortKey: 'level' },
];

const { query, loading, reload, onSearch, onSort, onPage, onPerPage, onFilter, sortState } = useServerTable({
    url: '/audit-trail',
    only: ['logs', 'filters'],
    initial: {
        search: props.filters.search ?? '',
        sort: props.filters.sort ?? 'created_at',
        dir: props.filters.dir ?? 'desc',
        date_from: props.filters.date_from ?? '',
        date_to: props.filters.date_to ?? '',
        page: props.logs.meta.page ?? 1,
        per_page: props.logs.meta.per_page ?? 10,
    },
});

const hasRange = computed(() => Boolean(query.date_from && query.date_to));

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (query.search) params.set('search', query.search);
    if (query.date_from) params.set('date_from', query.date_from);
    if (query.date_to) params.set('date_to', query.date_to);
    params.set('sort', query.sort);
    params.set('dir', query.dir);

    return `/audit-trail/export?${params.toString()}`;
});

const openDetail = (row) => router.get(`/audit-trail/${row.id}`);

/* ── Hapus log pada rentang tanggal ─────────────────────────────────── */
const purgeOpen = ref(false);
const purgeForm = useForm({ date_from: '', date_to: '' });

const openPurge = () => {
    purgeForm.clearErrors();
    purgeForm.date_from = query.date_from || '';
    purgeForm.date_to = query.date_to || '';
    purgeOpen.value = true;
};

const purge = () =>
    purgeForm.delete('/audit-trail', {
        preserveScroll: true,
        onSuccess: () => {
            purgeOpen.value = false;
            reload();
        },
    });

const fmt = (iso) =>
    iso ? new Date(`${iso}T00:00:00`).toLocaleDateString('id-ID', { dateStyle: 'medium' }) : '—';

const pageTitle = computed(() => menuLabelOf('/audit-trail', 'Audit Trail'));
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <div class="space-y-6" data-testid="audit-page-view">
            <DataTableCard
                server
                :title="pageTitle"
                testid="activity"
                :columns="columns"
                :rows="props.logs.data"
                :meta="props.logs.meta"
                :search="query.search"
                :sort="sortState"
                :loading="loading"
                row-clickable
                :show-refresh="false"
                :empty-icon="ScrollText"
                empty-title="Belum ada jejak audit"
                empty-description="Perubahan data, akses ditolak, dan kegagalan sistem tercatat otomatis di sini."
                @update:search="onSearch"
                @update:sort="onSort"
                @update:page="onPage"
                @update:per-page="onPerPage"
                @row-click="openDetail"
            >
                <template #filters>
                    <DatePicker
                        :model-value="query.date_from"
                        placeholder="Dari Tanggal"
                        class="w-[150px]"
                        data-testid="activity-date-from"
                        @update:model-value="onFilter('date_from', $event)"
                    />
                    <DatePicker
                        :model-value="query.date_to"
                        placeholder="Sampai Tanggal"
                        class="w-[150px]"
                        data-testid="activity-date-to"
                        @update:model-value="onFilter('date_to', $event)"
                    />
                    <Button
                        v-if="hasRange"
                        variant="outline"
                        size="sm"
                        data-testid="activity-clear-range"
                        @click="onFilter('date_from', ''), onFilter('date_to', '')"
                    >
                        Semua Tanggal
                    </Button>
                </template>

                <template #header-action>
                    <Button variant="outline" size="sm" as="a" :href="exportUrl" data-testid="audit-export">
                        <Download class="size-4" /> {{ ACTION.export }}
                    </Button>
                    <Button
                        v-if="canManage"
                        variant="outline"
                        size="sm"
                        class="text-destructive hover:text-destructive"
                        data-testid="activity-purge"
                        @click="openPurge"
                    >
                        <Trash2 class="size-4" /> Hapus Log
                    </Button>
                </template>

                <template #cell-created_at="{ row }">
                    <span class="font-mono text-xs tabular-nums text-muted-foreground">{{ row.created_at }}</span>
                </template>

                <template #cell-actor="{ row }">
                    <span class="font-medium">{{ row.actor }}</span>
                </template>

                <template #cell-module="{ row }">
                    <Badge variant="secondary" class="font-normal">{{ row.module }}</Badge>
                </template>

                <template #cell-level_label="{ row }">
                    <StateChip :label="row.level_label" :chip="row.level_chip" />
                </template>
            </DataTableCard>

            <!-- Dialog hapus log berdasarkan rentang tanggal -->
            <Dialog v-model:open="purgeOpen" title="Hapus Log Audit?" class="max-w-md">
                <div class="form-dense space-y-[var(--field-gap)]">
                    <p class="text-sm text-muted-foreground">
                        Seluruh log pada rentang tanggal berikut akan dihapus permanen (termasuk tanggal awal
                        dan akhir).
                    </p>
                    <div class="grid gap-[var(--field-gap)] sm:grid-cols-2">
                        <div class="space-y-[var(--item-gap)]">
                            <Label>Tanggal Awal</Label>
                            <DatePicker v-model="purgeForm.date_from" data-testid="purge-date-from" />
                            <p v-if="purgeForm.errors.date_from" class="text-xs font-medium text-destructive">
                                {{ purgeForm.errors.date_from }}
                            </p>
                        </div>
                        <div class="space-y-[var(--item-gap)]">
                            <Label>Tanggal Akhir</Label>
                            <DatePicker v-model="purgeForm.date_to" data-testid="purge-date-to" />
                            <p v-if="purgeForm.errors.date_to" class="text-xs font-medium text-destructive">
                                {{ purgeForm.errors.date_to }}
                            </p>
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground" data-testid="purge-range-summary">
                        Rentang terpilih: {{ fmt(purgeForm.date_from) }} s.d. {{ fmt(purgeForm.date_to) }}
                    </p>
                </div>

                <template #footer>
                    <Button variant="outline" size="sm" data-testid="purge-cancel" @click="purgeOpen = false">
                        <X class="size-4" /> {{ ACTION.cancel }}
                    </Button>
                    <Button
                        variant="destructive"
                        size="sm"
                        :disabled="purgeForm.processing || !purgeForm.date_from || !purgeForm.date_to"
                        data-testid="purge-submit"
                        @click="purge"
                    >
                        <Trash2 class="size-4" /> {{ purgeForm.processing ? ACTION.saving : ACTION.delete }}
                    </Button>
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
