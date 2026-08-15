<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { KeyRound, Lock, Pencil, Plus, Save, Trash2, X } from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Combobox from '@/components/ui/Combobox.vue';
import Dialog from '@/components/ui/Dialog.vue';
import DropdownMenuItem from '@/components/ui/DropdownMenuItem.vue';
import DropdownMenuSeparator from '@/components/ui/DropdownMenuSeparator.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import ConfirmDeleteDialog from '@/components/composite/ConfirmDeleteDialog.vue';
import DataTableCard from '@/components/composite/DataTableCard.vue';
import RowActions from '@/components/composite/RowActions.vue';
import { ACTION } from '@/constants/labels';
import { useServerTable } from '@/composables/useServerTable';
import { all, max, min, required } from '@/lib/validators';
import { useLiveValidation } from '@/composables/useLiveValidation';

const props = defineProps({
    permissions: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    entityOptions: { type: Array, default: () => [] },
});

const page = usePage();
const canManage = computed(() => (page.props.auth?.user?.permissions ?? []).includes('permissions.manage'));

const columns = [
    { key: 'name', label: 'Nama Izin' },
    { key: 'entity', label: 'Entitas', hideBelow: 'sm' },
    { key: 'ability', label: 'Aksi', hideBelow: 'md' },
    { key: 'guard_name', label: 'Guard', hideBelow: 'lg' },
    { key: 'roles_count', label: 'Peranan', align: 'right' },
    { key: 'actions', label: '', align: 'right', width: '48px', sortable: false },
];

const { query, loading, onSearch, onSort, onPage, onPerPage, onFilter, sortState } = useServerTable({
    url: '/permissions',
    only: ['permissions', 'filters', 'entityOptions'],
    initial: {
        search: props.filters.search ?? '',
        sort: props.filters.sort ?? 'name',
        dir: props.filters.dir ?? 'asc',
        entity: props.filters.entity || 'all',
        page: props.permissions.meta.page ?? 1,
        per_page: props.permissions.meta.per_page ?? 10,
    },
});

/* ── Dialog Tambah / Ubah ────────────────────────────────────────────── */
const dialogOpen = ref(false);
const editing = ref(null);
const form = useForm({ name: '' });

const check = useLiveValidation(form, {
    name: all(
        required('nama izin'),
        min(5, 'Nama Izin'),
        max(80, 'Nama Izin'),
        (value) =>
            value && !/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/.test(value)
                ? 'Nama izin harus berformat entitas.aksi memakai huruf kecil, mis. users.view.'
                : '',
    ),
});

const openCreate = () => {
    editing.value = null;
    form.defaults({ name: '' });
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
};

const openEdit = (row) => {
    editing.value = row;
    form.defaults({ name: row.name });
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
};

const submit = () =>
    check.submit(() => {
        const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };
        if (editing.value) form.put(`/permissions/${editing.value.id}`, options);
        else form.post('/permissions', options);
    });

/* ── Hapus & aksi massal ─────────────────────────────────────────────── */
const deleting = ref(null);
const deleteForm = useForm({});
const confirmDelete = () =>
    deleteForm.delete(`/permissions/${deleting.value.id}`, {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });

const selected = ref([]);
const bulkForm = useForm({ ids: [] });
const bulkConfirm = ref(false);

const runBulkDelete = () => {
    bulkForm.ids = [...selected.value];
    bulkForm.post('/permissions/bulk-destroy', {
        preserveScroll: true,
        onSuccess: () => {
            selected.value = [];
            bulkConfirm.value = false;
        },
    });
};
</script>

<template>
    <Head title="Perizinan" />
    <AppLayout>
        <div class="space-y-6" data-testid="permissions-page-view">
            <DataTableCard
                server
                title="Perizinan"
                testid="permissions"
                :columns="columns"
                :rows="props.permissions.data"
                :meta="props.permissions.meta"
                :search="query.search"
                :sort="sortState"
                :loading="loading"
                :empty-icon="KeyRound"
                empty-title="Belum ada izin"
                empty-description="Tambahkan izin dengan format entitas.aksi, mis. projects.view."
                :show-refresh="false"
                :selectable="canManage"
                :selected="selected"
                @update:selected="selected = $event"
                @update:search="onSearch"
                @update:sort="onSort"
                @update:page="onPage"
                @update:per-page="onPerPage"
            >
                <template #filters>
                    <Combobox
                        :model-value="query.entity"
                        :options="props.entityOptions"
                        placeholder="Semua Entitas"
                        class="w-[160px]"
                        data-testid="permissions-filter-entity"
                        @update:model-value="onFilter('entity', $event)"
                    />
                </template>

                <template #bulk-actions>
                    <Button
                        variant="destructive"
                        size="sm"
                        :disabled="bulkForm.processing"
                        data-testid="permissions-bulk-delete"
                        @click="bulkConfirm = true"
                    >
                        <Trash2 class="size-4" /> {{ ACTION.delete }}
                    </Button>
                </template>

                <template #header-action>
                    <Button v-if="canManage" size="sm" data-testid="permissions-add" @click="openCreate">
                        <Plus class="size-4" /> {{ ACTION.add }}
                    </Button>
                </template>

                <template #cell-name="{ row }">
                    <span class="flex items-center gap-2 font-mono text-xs font-medium">
                        {{ row.name }}
                        <Lock v-if="row.locked" class="size-3.5 text-muted-foreground" aria-label="Izin inti" />
                    </span>
                </template>

                <template #cell-ability="{ row }">
                    <Badge variant="secondary" class="font-normal">{{ row.ability }}</Badge>
                </template>

                <template #cell-guard_name="{ row }">
                    <span class="font-mono text-xs text-muted-foreground">{{ row.guard_name }}</span>
                </template>

                <template #cell-roles_count="{ row }">
                    <span class="tabular-nums">{{ row.roles_count }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <RowActions v-if="canManage && !row.locked" :testid="`permissions-actions-${row.id}`">
                        <DropdownMenuItem :data-testid="`permissions-edit-${row.id}`" @click="openEdit(row)">
                            <Pencil />{{ ACTION.edit }}
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            class="text-destructive data-[highlighted]:text-destructive"
                            :data-testid="`permissions-delete-${row.id}`"
                            @click="deleting = row"
                        >
                            <Trash2 />{{ ACTION.delete }}
                        </DropdownMenuItem>
                    </RowActions>
                </template>
            </DataTableCard>

            <!-- Dialog Tambah / Ubah Izin -->
            <Dialog v-model:open="dialogOpen" :title="editing ? 'Ubah Izin' : 'Tambah Izin'" class="max-w-md">
                <form
                    id="permission-form"
                    class="form-dense space-y-[var(--field-gap)]"
                    novalidate
                    @submit.prevent="submit"
                >
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="p-name">Nama Izin</Label>
                        <Input
                            id="p-name"
                            v-model="form.name"
                            class="font-mono"
                            maxlength="80"
                            data-testid="permission-form-name"
                            @blur="check.validate('name')"
                        />
                        <p
                            v-if="form.errors.name"
                            class="text-xs font-medium text-destructive"
                            data-testid="permission-form-name-error"
                        >
                            {{ form.errors.name }}
                        </p>
                        <p v-else class="text-xs text-muted-foreground">
                            Format <span class="font-mono">entitas.aksi</span>, mis.
                            <span class="font-mono">projects.view</span> atau
                            <span class="font-mono">projects.delete_any</span>.
                        </p>
                    </div>
                </form>

                <template #footer>
                    <Button variant="outline" size="sm" data-testid="permission-form-cancel" @click="dialogOpen = false">
                        <X class="size-4" /> {{ ACTION.cancel }}
                    </Button>
                    <Button
                        size="sm"
                        type="submit"
                        form="permission-form"
                        :disabled="form.processing"
                        data-testid="permission-form-save"
                    >
                        <Save class="size-4" /> {{ form.processing ? ACTION.saving : ACTION.save }}
                    </Button>
                </template>
            </Dialog>

            <ConfirmDeleteDialog
                :open="bulkConfirm"
                title="Hapus Izin Terpilih?"
                :description="`${selected.length} izin akan dihapus. Izin inti bawaan modul dilewati.`"
                :processing="bulkForm.processing"
                @update:open="bulkConfirm = false"
                @confirm="runBulkDelete"
            />

            <ConfirmDeleteDialog
                :open="Boolean(deleting)"
                title="Hapus Izin?"
                :processing="deleteForm.processing"
                @update:open="deleting = null"
                @confirm="confirmDelete"
            />
        </div>
    </AppLayout>
</template>
