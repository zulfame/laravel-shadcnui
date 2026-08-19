<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ChevronsRight,
    ChevronsLeft,
    GripVertical,
    Link2,
    Loader2,
    Pencil,
    Plus,
    Save,
    Trash2,
    X,
} from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Combobox from '@/components/ui/Combobox.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Switch from '@/components/ui/Switch.vue';
import ConfirmDeleteDialog from '@/components/composite/ConfirmDeleteDialog.vue';
import EmptyState from '@/components/composite/EmptyState.vue';
import { MENU_ICON_OPTIONS, iconOf } from '@/lib/menuIcons';

const props = defineProps({
    areas: { type: Array, default: () => [] },
    permissionOptions: { type: Array, default: () => [] },
    maxDepth: { type: Number, default: 3 },
});

/* ── Susunan datar untuk geser & jorok ────────────────────────────────── */
const flatten = (items, depth = 0, out = []) => {
    items.forEach((item) => {
        out.push({ ...item, depth });
        flatten(item.children ?? [], depth + 1, out);
    });

    return out;
};

const rows = ref(
    props.areas.reduce((acc, area) => ({ ...acc, [area.id]: flatten(area.items) }), {}),
);

// Daftar disinkronkan ulang setiap props dari server berubah (setelah CRUD).
watch(
    () => props.areas,
    (areas) => {
        rows.value = areas.reduce((acc, area) => ({ ...acc, [area.id]: flatten(area.items) }), {});
    },
    { deep: true },
);

const dragKey = ref(null);
const dragIndex = ref(null);

const persist = (areaId) => {
    router.put(
        '/menus/reorder',
        {
            nodes: rows.value[areaId].map((row) => ({
                id: row.id,
                parent_id: row.parent_id,
                area: areaId,
            })),
        },
        { preserveScroll: true, preserveState: false },
    );
};

const onDragStart = (areaId, index, event) => {
    dragKey.value = areaId;
    dragIndex.value = index;
    event.dataTransfer.effectAllowed = 'move';
};

const onDragEnter = (areaId, index) => {
    if (dragKey.value !== areaId || dragIndex.value === null || dragIndex.value === index) return;

    const list = [...rows.value[areaId]];
    list.splice(index, 0, list.splice(dragIndex.value, 1)[0]);
    rows.value[areaId] = list;
    dragIndex.value = index;
};

const onDragEnd = (areaId) => {
    dragIndex.value = null;
    dragKey.value = null;
    reparent(areaId);
};

/** Induk item = item terdekat di atasnya yang tingkatnya satu lebih kecil. */
const reparent = (areaId) => {
    const list = rows.value[areaId];

    list.forEach((row, index) => {
        if (row.depth === 0) {
            row.parent_id = null;

            return;
        }

        const parent = [...list.slice(0, index)].reverse().find((prev) => prev.depth === row.depth - 1);
        row.parent_id = parent ? parent.id : (row.depth = 0, null);
    });

    persist(areaId);
};

const indent = (areaId, index, delta) => {
    const list = rows.value[areaId];
    const prev = list[index - 1];
    const max = Math.min(props.maxDepth - 1, prev ? prev.depth + 1 : 0);
    const next = Math.max(0, Math.min(max, list[index].depth + delta));

    if (next === list[index].depth) return;

    list[index].depth = next;
    reparent(areaId);
};

/* ── Formulir ─────────────────────────────────────────────────────────── */
const editing = ref(null);
const dialogOpen = ref(false);
const confirmOpen = ref(false);
const target = ref(null);
const activeArea = ref(props.areas[0]?.id ?? 'member');

const form = useForm({
    label: '',
    area: 'member',
    parent_id: '',
    href: '',
    icon: '',
    permission: '',
    is_active: true,
});

const parentOptions = computed(() => [
    { value: '', label: 'Tanpa Induk (Tingkat 1)' },
    ...(rows.value[form.area] ?? [])
        .filter((row) => row.depth < props.maxDepth - 1 && row.id !== editing.value?.id)
        .map((row) => ({ value: row.id, label: `${'— '.repeat(row.depth)}${row.label}` })),
]);

const openCreate = (areaId) => {
    editing.value = null;
    activeArea.value = areaId;
    form.defaults({ label: '', area: areaId, parent_id: '', href: '', icon: '', permission: '', is_active: true });
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
};

const openEdit = (row) => {
    editing.value = row;
    form.defaults({
        label: row.label,
        area: row.area,
        parent_id: row.parent_id ?? '',
        href: row.href ?? '',
        icon: row.icon ?? '',
        permission: row.permission ?? '',
        is_active: row.is_active,
    });
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
};

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };

    editing.value
        ? form.put(`/menus/${editing.value.id}`, options)
        : form.post('/menus', options);
};

const confirmDelete = (row) => {
    target.value = row;
    confirmOpen.value = true;
};

const destroy = () => {
    router.delete(`/menus/${target.value.id}`, {
        preserveScroll: true,
        onSuccess: () => (confirmOpen.value = false),
    });
};
</script>

<template>
    <Head title="Menu Sidebar" />
    <AppLayout>
        <div class="space-y-6" data-testid="menus-page">
            <Card v-for="area in props.areas" :key="area.id">
                <CardHeader class="flex flex-col gap-3 space-y-0 sm:flex-row sm:items-center sm:justify-between">
                    <CardTitle class="flex items-center gap-2">
                        {{ area.label }}
                        <Badge variant="secondary" class="font-normal tabular-nums">
                            {{ (rows[area.id] ?? []).length }}
                        </Badge>
                    </CardTitle>
                    <Button size="sm" :data-testid="`menu-add-${area.id}`" @click="openCreate(area.id)">
                        <Plus class="size-4" /> Tambah
                    </Button>
                </CardHeader>
                <CardContent class="p-0">
                    <EmptyState v-if="!(rows[area.id] ?? []).length" variant="first-time" />
                    <ul v-else class="divide-y" :data-testid="`menu-list-${area.id}`">
                        <li
                            v-for="(row, index) in rows[area.id]"
                            :key="row.id"
                            class="flex items-center gap-2 px-4 py-2 transition-colors hover:bg-muted/40"
                            draggable="true"
                            :data-testid="`menu-row-${row.id}`"
                            @dragstart="onDragStart(area.id, index, $event)"
                            @dragenter.prevent="onDragEnter(area.id, index)"
                            @dragover.prevent
                            @dragend="onDragEnd(area.id)"
                        >
                            <GripVertical class="size-3.5 shrink-0 cursor-grab text-muted-foreground" aria-hidden="true" />
                            <span :style="{ width: `${row.depth * 22}px` }" class="shrink-0" aria-hidden="true" />
                            <component
                                :is="iconOf(row.icon)"
                                class="size-4 shrink-0 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2">
                                    <span class="truncate text-[13px] font-medium">{{ row.label }}</span>
                                    <Badge variant="outline" class="font-normal">Tingkat {{ row.depth + 1 }}</Badge>
                                    <Badge v-if="!row.href" variant="secondary" class="font-normal">Grup</Badge>
                                    <Badge v-if="!row.is_active" variant="secondary" class="font-normal">Nonaktif</Badge>
                                </span>
                                <span class="flex items-center gap-2 text-xs text-muted-foreground">
                                    <span v-if="row.href" class="flex items-center gap-1 font-mono text-[11px]">
                                        <Link2 class="size-3" aria-hidden="true" /> {{ row.href }}
                                    </span>
                                    <span v-if="row.permission" class="font-mono text-[11px]">{{ row.permission }}</span>
                                </span>
                            </span>
                            <span class="flex shrink-0 items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-7"
                                    aria-label="Kurangi Tingkat"
                                    :data-testid="`menu-outdent-${row.id}`"
                                    @click="indent(area.id, index, -1)"
                                >
                                    <ChevronsLeft class="size-3.5" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-7"
                                    aria-label="Tambah Tingkat"
                                    :data-testid="`menu-indent-${row.id}`"
                                    @click="indent(area.id, index, 1)"
                                >
                                    <ChevronsRight class="size-3.5" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-7"
                                    aria-label="Ubah"
                                    :data-testid="`menu-edit-${row.id}`"
                                    @click="openEdit(row)"
                                >
                                    <Pencil class="size-3.5" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-7 text-destructive"
                                    aria-label="Hapus"
                                    :data-testid="`menu-delete-${row.id}`"
                                    @click="confirmDelete(row)"
                                >
                                    <Trash2 class="size-3.5" />
                                </Button>
                            </span>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <p class="text-xs text-muted-foreground" data-testid="menus-hint">
                Geser baris untuk mengubah urutan, lalu pakai tombol panah untuk menaikkan atau menurunkan tingkat
                (maksimal {{ props.maxDepth }} tingkat). Item tanpa alamat otomatis menjadi grup yang dapat dibuka-tutup.
            </p>

            <Dialog
                v-model:open="dialogOpen"
                :title="editing ? 'Ubah Menu' : 'Tambah Menu'"
                class="max-w-lg"
            >
                <form class="space-y-3" @submit.prevent="submit">
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="menu-label">Label Menu</Label>
                        <Input id="menu-label" v-model="form.label" data-testid="menu-form-label" />
                        <p v-if="form.errors.label" class="text-xs font-medium text-destructive">
                            {{ form.errors.label }}
                        </p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="space-y-[var(--item-gap)]">
                            <Label>Area</Label>
                            <Combobox
                                v-model="form.area"
                                :options="props.areas.map((a) => ({ value: a.id, label: a.label }))"
                                data-testid="menu-form-area"
                            />
                        </div>
                        <div class="space-y-[var(--item-gap)]">
                            <Label>Menu Induk</Label>
                            <Combobox v-model="form.parent_id" :options="parentOptions" data-testid="menu-form-parent" />
                        </div>
                    </div>
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="menu-href">Alamat (Kosongkan Untuk Grup)</Label>
                        <Input id="menu-href" v-model="form.href" placeholder="/users" data-testid="menu-form-href" />
                        <p v-if="form.errors.href" class="text-xs font-medium text-destructive">
                            {{ form.errors.href }}
                        </p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="space-y-[var(--item-gap)]">
                            <Label>Ikon</Label>
                            <Combobox
                                v-model="form.icon"
                                :options="[{ value: '', label: 'Bawaan' }, ...MENU_ICON_OPTIONS]"
                                data-testid="menu-form-icon"
                            />
                        </div>
                        <div class="space-y-[var(--item-gap)]">
                            <Label>Izin</Label>
                            <Combobox
                                v-model="form.permission"
                                :options="props.permissionOptions"
                                data-testid="menu-form-permission"
                            />
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-1 text-sm">
                        <span>Menu Aktif</span>
                        <Switch v-model="form.is_active" data-testid="menu-form-active" />
                    </div>
                </form>

                <template #footer>
                    <Button variant="outline" size="sm" data-testid="menu-form-cancel" @click="dialogOpen = false">
                        <X class="size-4" /> Batal
                    </Button>
                    <Button size="sm" :disabled="form.processing" data-testid="menu-form-save" @click="submit">
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                        <Save v-else class="size-4" />
                        Simpan
                    </Button>
                </template>
            </Dialog>

            <ConfirmDeleteDialog
                v-model:open="confirmOpen"
                title="Hapus Menu?"
                :description="`Menu ${target?.label ?? ''} beserta seluruh submenunya akan dihapus.`"
                @confirm="destroy"
            />
        </div>
    </AppLayout>
</template>
