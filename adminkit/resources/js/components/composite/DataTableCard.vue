<script setup>
import { computed, ref, watch } from 'vue';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronLeft,
    ChevronRight,
    FilterX,
    RefreshCw,
    Search,
} from 'lucide-vue-next';

import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardDescription from '@/components/ui/CardDescription.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import Table from '@/components/ui/Table.vue';
import TableBody from '@/components/ui/TableBody.vue';
import TableCell from '@/components/ui/TableCell.vue';
import TableHead from '@/components/ui/TableHead.vue';
import TableHeader from '@/components/ui/TableHeader.vue';
import TableRow from '@/components/ui/TableRow.vue';
import EmptyState from '@/components/composite/EmptyState.vue';
import { ACTION } from '@/constants/labels';

/**
 * DataTableCard — tabel standar berbingkai kartu:
 * Card → toolbar dalam kotak muted → tabel padat 13px → footer paginasi.
 *
 * `columns`: [{ key, label, align?, width?, sortable? }]
 * Sel kustom lewat slot bernama `cell-<key>` dengan payload { row, value }.
 * Placeholder pencarian DIKUNCI ke ACTION.search agar seragam di seluruh app.
 */
const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    rowKey: { type: String, default: 'id' },
    testid: { type: String, required: true },
    emptyIcon: { type: [Object, Function], default: null },
    emptyTitle: { type: String, default: '' },
    emptyDescription: { type: String, default: '' },
    showRefresh: { type: Boolean, default: true },
});

const emit = defineEmits(['refresh']);

const search = ref('');
const sortKey = ref('');
const sortDir = ref('asc');
const pageIndex = ref(0);
const pageSize = ref(10);

const toggleSort = (key) => {
    if (sortKey.value !== key) {
        sortKey.value = key;
        sortDir.value = 'asc';
    } else if (sortDir.value === 'asc') {
        sortDir.value = 'desc';
    } else {
        sortKey.value = '';
    }
};

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.rows;
    return props.rows.filter((row) =>
        props.columns.some((col) => String(row[col.key] ?? '').toLowerCase().includes(q)),
    );
});

const sorted = computed(() => {
    if (!sortKey.value) return filtered.value;
    const dir = sortDir.value === 'asc' ? 1 : -1;
    return [...filtered.value].sort((a, b) => {
        const x = a[sortKey.value];
        const y = b[sortKey.value];
        if (typeof x === 'number' && typeof y === 'number') return (x - y) * dir;
        return String(x ?? '').localeCompare(String(y ?? ''), 'id') * dir;
    });
});

const pageCount = computed(() => Math.max(1, Math.ceil(sorted.value.length / pageSize.value)));
const paged = computed(() =>
    sorted.value.slice(pageIndex.value * pageSize.value, (pageIndex.value + 1) * pageSize.value),
);

watch([search, pageSize], () => {
    pageIndex.value = 0;
});

const hasSearch = computed(() => search.value.trim().length > 0);
const pageSizeOptions = [
    { value: 10, label: '10' },
    { value: 20, label: '20' },
    { value: 50, label: '50' },
];
</script>

<template>
    <Card>
        <CardHeader class="flex flex-col gap-3 space-y-0 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <CardTitle>{{ props.title }}</CardTitle>
                <CardDescription v-if="props.description">{{ props.description }}</CardDescription>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <slot name="header-action" />
                <Button
                    v-if="props.showRefresh"
                    variant="outline"
                    size="sm"
                    :data-testid="`${props.testid}-refresh`"
                    @click="emit('refresh')"
                >
                    <RefreshCw class="size-4" /> {{ ACTION.refresh }}
                </Button>
            </div>
        </CardHeader>

        <CardContent class="space-y-4">
            <div class="flex flex-col gap-2 rounded-lg border bg-muted/40 p-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full max-w-[15rem]">
                    <Search class="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                    <Input
                        v-model="search"
                        :placeholder="ACTION.search"
                        class="h-[var(--ctl-h-sm)] pl-8 text-xs"
                        :data-testid="`${props.testid}-search`"
                    />
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <slot name="filters" />
                    <Button
                        v-if="hasSearch"
                        variant="outline"
                        size="sm"
                        :data-testid="`${props.testid}-reset`"
                        @click="search = ''"
                    >
                        <FilterX class="size-4" /> {{ ACTION.reset }}
                    </Button>
                </div>
            </div>

            <div class="rounded-md border">
                <EmptyState
                    v-if="props.rows.length === 0"
                    variant="first-time"
                    :icon="props.emptyIcon"
                    :title="props.emptyTitle"
                    :description="props.emptyDescription"
                />
                <Table
                    v-else
                    class="tbl-density [&_td]:whitespace-nowrap [&_th]:whitespace-nowrap"
                    :data-testid="`${props.testid}-table`"
                >
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead
                                v-for="col in props.columns"
                                :key="col.key"
                                :style="col.width ? { width: col.width } : undefined"
                                :class="col.align === 'right' ? 'text-right' : ''"
                            >
                                <button
                                    v-if="col.sortable !== false"
                                    type="button"
                                    class="flex h-full w-full items-center gap-1 font-medium"
                                    :class="col.align === 'right' ? 'justify-end text-right' : 'text-left'"
                                    :data-testid="`sort-${col.key}`"
                                    @click="toggleSort(col.key)"
                                >
                                    {{ col.label }}
                                    <ArrowUp v-if="sortKey === col.key && sortDir === 'asc'" class="size-3.5" />
                                    <ArrowDown v-else-if="sortKey === col.key" class="size-3.5" />
                                    <ArrowUpDown v-else class="size-3.5 opacity-50" />
                                </button>
                                <span v-else>{{ col.label }}</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="row in paged" :key="row[props.rowKey]">
                            <TableCell
                                v-for="col in props.columns"
                                :key="col.key"
                                :class="col.align === 'right' ? 'text-right' : ''"
                            >
                                <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                                    {{ row[col.key] ?? '\u2014' }}
                                </slot>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="paged.length === 0">
                            <TableCell :colspan="props.columns.length" class="h-24 text-center text-muted-foreground">
                                <div class="flex flex-col items-center gap-2" :data-testid="`${props.testid}-empty-filtered`">
                                    <span>Tidak ada baris yang cocok dengan pencarian.</span>
                                    <Button variant="outline" size="sm" @click="search = ''">
                                        <FilterX class="size-4" /> {{ ACTION.reset }}
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div
                v-if="props.rows.length > 0"
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <Select
                        v-model="pageSize"
                        :options="pageSizeOptions"
                        class="w-[70px]"
                        :data-testid="`${props.testid}-page-size`"
                        @update:model-value="pageSize = Number($event)"
                    />
                    <span :data-testid="`${props.testid}-total`">
                        dari {{ sorted.length.toLocaleString('id-ID') }} baris
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <span class="text-xs text-muted-foreground" :data-testid="`${props.testid}-page`">
                        Halaman {{ pageIndex + 1 }} dari {{ pageCount }}
                    </span>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            size="icon"
                            class="size-[var(--ctl-h-sm)]"
                            aria-label="Halaman sebelumnya"
                            :disabled="pageIndex === 0"
                            :data-testid="`${props.testid}-prev`"
                            @click="pageIndex -= 1"
                        >
                            <ChevronLeft class="size-4" />
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            class="size-[var(--ctl-h-sm)]"
                            aria-label="Halaman berikutnya"
                            :disabled="pageIndex + 1 >= pageCount"
                            :data-testid="`${props.testid}-next`"
                            @click="pageIndex += 1"
                        >
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
