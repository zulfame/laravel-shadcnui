<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ScrollText, Trash2, X } from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Combobox from '@/components/ui/Combobox.vue';
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
    moduleOptions: { type: Array, default: () => [] },
});

const page = usePage();
const canManage = computed(() => (page.props.auth?.user?.permissions ?? []).includes('activity.manage'));

const columns = [
    { key: 'created_at', label: 'Waktu', sortable: false },
    { key: 'actor', label: 'Pelaku', sortable: false },
    { key: 'action', label: 'Aksi', sortable: false },
    { key: 'module', label: 'Modul', sortable: false },
    { key: 'ip', label: 'Alamat IP', sortable: false },
    { key: 'level_label', label: 'Level', sortable: false },
];

const { query, loading, reload, onSearch, onPage, onPerPage, onFilter } = useServerTable({
    url: '/activity',
    only: ['logs', 'filters', 'moduleOptions'],
    initial: {
        search: props.filters.search ?? '',
        module: props.filters.module || 'all',
        date_from: props.filters.date_from ?? '',
        date_to: props.filters.date_to ?? '',
        page: props.logs.meta.page ?? 1,
        per_page: props.logs.meta.per_page ?? 10,
    },
});

const hasRange = computed(() => Boolean(query.date_from && query.date_to));

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
    purgeForm.delete('/activity', {
        preserveScroll: true,
        onSuccess: () => {
            purgeOpen.value = false;
            reload();
        },
    });

const fmt = (iso) =>
    iso ? new Date(`${iso}T00:00:00`).toLocaleDateString('id-ID', { dateStyle: 'medium' }) : '—';
</script>

<template>
    <Head title="Log Aktivitas" />
    <AppLayout>
        <div class="space-y-6" data-testid="activity-page-view">
            <DataTableCard
                server
                title="Log Aktivitas"
                testid="activity"
                :columns="columns"
                :rows="props.logs.data"
                :meta="props.logs.meta"
                :search="query.search"
                :loading="loading"
                :empty-icon="ScrollText"
                empty-title="Belum ada aktivitas"
                empty-description="Aktivitas akan tercatat otomatis saat ada perubahan data."
                @update:search="onSearch"
                @update:page="onPage"
                @update:per-page="onPerPage"
                @refresh="reload()"
            >
                <template #filters>
                    <DatePicker
                        :model-value="query.date_from"
                        placeholder="Dari Tanggal"
                        class="h-[var(--ctl-h-sm)] w-[150px] text-xs"
                        data-testid="activity-date-from"
                        @update:model-value="onFilter('date_from', $event)"
                    />
                    <DatePicker
                        :model-value="query.date_to"
                        placeholder="Sampai Tanggal"
                        class="h-[var(--ctl-h-sm)] w-[150px] text-xs"
                        data-testid="activity-date-to"
                        @update:model-value="onFilter('date_to', $event)"
                    />
                    <Combobox
                        :model-value="query.module"
                        :options="props.moduleOptions"
                        placeholder="Semua Modul"
                        class="w-[150px]"
                        data-testid="activity-filter-module"
                        @update:model-value="onFilter('module', $event)"
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

                <template #cell-ip="{ row }">
                    <span class="font-mono text-xs text-muted-foreground">{{ row.ip }}</span>
                </template>

                <template #cell-level_label="{ row }">
                    <StateChip :label="row.level_label" :chip="row.level_chip" />
                </template>
            </DataTableCard>

            <!-- Dialog hapus log berdasarkan rentang tanggal -->
            <Dialog v-model:open="purgeOpen" title="Hapus log aktivitas" class="max-w-md">
                <div class="form-dense space-y-[var(--field-gap)]">
                    <p class="text-sm text-muted-foreground">
                        Seluruh log pada rentang tanggal berikut akan dihapus permanen (termasuk tanggal awal
                        dan akhir).
                    </p>
                    <div class="grid gap-[var(--field-gap)] sm:grid-cols-2">
                        <div class="space-y-[var(--item-gap)]">
                            <Label>Tanggal awal</Label>
                            <DatePicker v-model="purgeForm.date_from" data-testid="purge-date-from" />
                            <p v-if="purgeForm.errors.date_from" class="text-xs font-medium text-destructive">
                                {{ purgeForm.errors.date_from }}
                            </p>
                        </div>
                        <div class="space-y-[var(--item-gap)]">
                            <Label>Tanggal akhir</Label>
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
