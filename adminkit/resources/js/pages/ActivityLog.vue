<script setup>
import { Head } from '@inertiajs/vue3';
import { ScrollText } from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Badge from '@/components/ui/Badge.vue';
import Combobox from '@/components/ui/Combobox.vue';
import DataTableCard from '@/components/composite/DataTableCard.vue';
import StateChip from '@/components/composite/StateChip.vue';
import { useServerTable } from '@/composables/useServerTable';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    moduleOptions: { type: Array, default: () => [] },
});

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
        page: props.logs.meta.page ?? 1,
        per_page: props.logs.meta.per_page ?? 10,
    },
});
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
                    <Combobox
                        :model-value="query.module"
                        :options="props.moduleOptions"
                        placeholder="Semua modul"
                        class="w-[150px]"
                        data-testid="activity-filter-module"
                        @update:model-value="onFilter('module', $event)"
                    />
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
        </div>
    </AppLayout>
</template>
