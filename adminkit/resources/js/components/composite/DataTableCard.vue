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
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Input from '@/components/ui/Input.vue';
import Combobox from '@/components/ui/Combobox.vue';
import Table from '@/components/ui/Table.vue';
import TableBody from '@/components/ui/TableBody.vue';
import TableCell from '@/components/ui/TableCell.vue';
import TableHead from '@/components/ui/TableHead.vue';
import TableHeader from '@/components/ui/TableHeader.vue';
import TableRow from '@/components/ui/TableRow.vue';
import EmptyState from '@/components/composite/EmptyState.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import { ACTION } from '@/constants/labels';

/**
 * DataTableCard — tabel standar berbingkai kartu:
 * Card → toolbar dalam kotak muted → tabel padat 13px → footer paginasi.
 *
 * Dua mode:
 *   CLIENT (default) — cari/urut/paginasi dihitung di browser dari `rows`.
 *   SERVER (`server`) — komponen hanya mengirim `update:*`; induk yang query.
 *
 * `columns`: [{ key, label, align?, width?, sortable?, sortKey?, hideBelow? }]
 * `hideBelow`: 'sm' | 'md' | 'lg' | 'xl' — kolom disembunyikan di bawah breakpoint itu.
 * Sel kustom lewat slot bernama `cell-<key>` dengan payload { row, value }.
 */
const props = defineProps({
    title: { type: String, required: true },
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    rowKey: { type: String, default: 'id' },
    testid: { type: String, required: true },
    emptyIcon: { type: [Object, Function], default: null },
    emptyTitle: { type: String, default: '' },
    emptyDescription: { type: String, default: '' },
    showRefresh: { type: Boolean, default: true },
    rowClickable: { type: Boolean, default: false },
    // ── mode server ──
    server: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    search: { type: String, default: '' },
    sort: { type: Object, default: () => ({ key: '', dir: 'asc' }) },
    meta: { type: Object, default: () => ({ page: 1, per_page: 10, total: 0, last_page: 1 }) },
});

// Kelas literal (bukan dinamis) agar tidak dibuang purge Tailwind.
const HIDE_BELOW = {
    sm: 'hidden sm:table-cell',
    md: 'hidden md:table-cell',
    lg: 'hidden lg:table-cell',
    xl: 'hidden xl:table-cell',
};

const cellClass = (col) => [col.align === 'right' ? 'text-right' : '', HIDE_BELOW[col.hideBelow] ?? ''];

const emit = defineEmits([
    'refresh',
    'row-click',
    'update:search',
    'update:sort',
    'update:page',
    'update:perPage',
]);

const localSearch = ref(props.search);
const localSort = ref({ ...props.sort });
const localPage = ref(0);
const localPerPage = ref(props.meta.per_page ?? 10);

watch(
    () => props.search,
    (v) => {
        localSearch.value = v;
    },
);
watch(
    () => props.sort,
    (v) => {
        localSort.value = { ...v };
    },
);

const setSearch = (value) => {
    localSearch.value = value;
    if (props.server) emit('update:search', value);
    else localPage.value = 0;
};

const toggleSort = (key) => {
    const current = localSort.value;
    let next;
    if (current.key !== key) next = { key, dir: 'asc' };
    else if (current.dir === 'asc') next = { key, dir: 'desc' };
    else next = props.server ? { key, dir: 'asc' } : { key: '', dir: 'asc' };

    localSort.value = next;
    if (props.server) emit('update:sort', next);
};

const setPerPage = (value) => {
    localPerPage.value = Number(value);
    if (props.server) emit('update:perPage', Number(value));
    else localPage.value = 0;
};

const goPage = (index) => {
    if (props.server) emit('update:page', index + 1);
    else localPage.value = index;
};

// ── perhitungan mode client ──
const filtered = computed(() => {
    const q = localSearch.value.trim().toLowerCase();
    if (props.server || !q) return props.rows;
    return props.rows.filter((row) =>
        props.columns.some((col) => String(row[col.key] ?? '').toLowerCase().includes(q)),
    );
});

const sorted = computed(() => {
    if (props.server || !localSort.value.key) return filtered.value;
    const dir = localSort.value.dir === 'asc' ? 1 : -1;
    const key = localSort.value.key;
    return [...filtered.value].sort((a, b) => {
        const x = a[key];
        const y = b[key];
        if (typeof x === 'number' && typeof y === 'number') return (x - y) * dir;
        return String(x ?? '').localeCompare(String(y ?? ''), 'id') * dir;
    });
});

const total = computed(() => (props.server ? (props.meta.total ?? 0) : sorted.value.length));
const perPage = computed(() => (props.server ? (props.meta.per_page ?? 10) : localPerPage.value));
const pageIndex = computed(() => (props.server ? (props.meta.page ?? 1) - 1 : localPage.value));
const pageCount = computed(() =>
    props.server ? Math.max(1, props.meta.last_page ?? 1) : Math.max(1, Math.ceil(total.value / perPage.value)),
);
const paged = computed(() =>
    props.server
        ? props.rows
        : sorted.value.slice(localPage.value * perPage.value, (localPage.value + 1) * perPage.value),
);

const hasFilter = computed(() => localSearch.value.trim().length > 0);
const isEmptySource = computed(() => (props.server ? total.value === 0 && !hasFilter.value : props.rows.length === 0));

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
                    <RefreshCw class="size-4" :class="props.loading && 'animate-spin'" /> {{ ACTION.refresh }}
                </Button>
            </div>
        </CardHeader>

        <CardContent class="space-y-4">
            <div
                class="flex flex-col gap-2 rounded-lg border bg-muted/40 p-2 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="relative w-full max-w-[15rem]">
                    <Search
                        class="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        :model-value="localSearch"
                        :placeholder="ACTION.search"
                        class="h-[var(--ctl-h-sm)] pl-8 text-xs"
                        :data-testid="`${props.testid}-search`"
                        @update:model-value="setSearch"
                    />
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <slot name="filters" />
                    <Button
                        v-if="hasFilter"
                        variant="outline"
                        size="sm"
                        :data-testid="`${props.testid}-reset`"
                        @click="setSearch('')"
                    >
                        <FilterX class="size-4" /> {{ ACTION.reset }}
                    </Button>
                </div>
            </div>

            <div class="rounded-md border">
                <EmptyState
                    v-if="isEmptySource"
                    variant="first-time"
                    :icon="props.emptyIcon"
                    :title="props.emptyTitle"
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
                                :class="cellClass(col)"
                            >
                                <button
                                    v-if="col.sortable !== false"
                                    type="button"
                                    class="flex h-full w-full items-center gap-1 font-medium"
                                    :class="col.align === 'right' ? 'justify-end text-right' : 'text-left'"
                                    :data-testid="`sort-${col.key}`"
                                    @click="toggleSort(col.sortKey ?? col.key)"
                                >
                                    {{ col.label }}
                                    <ArrowUp
                                        v-if="localSort.key === (col.sortKey ?? col.key) && localSort.dir === 'asc'"
                                        class="size-3.5"
                                    />
                                    <ArrowDown v-else-if="localSort.key === (col.sortKey ?? col.key)" class="size-3.5" />
                                    <ArrowUpDown v-else class="size-3.5 opacity-50" />
                                </button>
                                <span v-else>{{ col.label }}</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="props.loading" class="hover:bg-transparent">
                            <TableCell :colspan="props.columns.length" class="py-3">
                                <div class="space-y-2" :data-testid="`${props.testid}-loading`">
                                    <Skeleton v-for="n in 5" :key="n" class="h-5 w-full" />
                                </div>
                            </TableCell>
                        </TableRow>
                        <template v-else>
                            <TableRow
                                v-for="row in paged"
                                :key="row[props.rowKey]"
                                :class="props.rowClickable ? 'cursor-pointer' : ''"
                                :data-testid="props.rowClickable ? `${props.testid}-row-${row[props.rowKey]}` : undefined"
                                @click="props.rowClickable && emit('row-click', row)"
                            >
                                <TableCell v-for="col in props.columns" :key="col.key" :class="cellClass(col)">
                                    <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                                        {{ row[col.key] ?? '\u2014' }}
                                    </slot>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="paged.length === 0">
                                <TableCell
                                    :colspan="props.columns.length"
                                    class="h-24 text-center text-muted-foreground"
                                >
                                    <div
                                        class="flex flex-col items-center gap-2"
                                        :data-testid="`${props.testid}-empty-filtered`"
                                    >
                                        <span>Tidak ada baris yang cocok dengan pencarian.</span>
                                        <Button variant="outline" size="sm" @click="setSearch('')">
                                            <FilterX class="size-4" /> {{ ACTION.reset }}
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </Table>
            </div>

            <div v-if="!isEmptySource" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <Combobox
                        :model-value="perPage"
                        :options="pageSizeOptions"
                        class="w-[70px]"
                        :data-testid="`${props.testid}-page-size`"
                        @update:model-value="setPerPage"
                    />
                    <span :data-testid="`${props.testid}-total`">
                        dari {{ total.toLocaleString('id-ID') }} baris
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
                            @click="goPage(pageIndex - 1)"
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
                            @click="goPage(pageIndex + 1)"
                        >
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
