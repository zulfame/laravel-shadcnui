<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Eye, FileDown, Loader2, Lock, Pencil, Plus, Save, ShieldCheck, Trash2, Upload, X } from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import DropdownMenuItem from '@/components/ui/DropdownMenuItem.vue';
import DropdownMenuSeparator from '@/components/ui/DropdownMenuSeparator.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import ConfirmDeleteDialog from '@/components/composite/ConfirmDeleteDialog.vue';
import DataTableCard from '@/components/composite/DataTableCard.vue';
import RowActions from '@/components/composite/RowActions.vue';
import { ACTION } from '@/constants/labels';
import { all, max, min, required } from '@/lib/validators';
import { useLiveValidation } from '@/composables/useLiveValidation';

const props = defineProps({
    roles: { type: Array, default: () => [] },
});

const page = usePage();
const canManage = computed(() => (page.props.auth?.user?.permissions ?? []).includes('roles.manage'));

const columns = [
    { key: 'name', label: 'Peranan' },
    { key: 'users_count', label: 'Pengguna', align: 'right' },
    { key: 'actions', label: '', align: 'right', width: '48px', sortable: false },
];

/* ── Dialog Tambah / Ubah Peranan ────────────────────────────────────── */
const dialogOpen = ref(false);
const editing = ref(null);
const form = useForm({ name: '' });

const check = useLiveValidation(form, {
    name: all(required('nama peranan'), min(3, 'Nama Peranan'), max(50, 'Nama Peranan')),
});

const openCreate = () => {
    editing.value = null;
    form.defaults({ name: '' });
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
};

const openEdit = (role) => {
    editing.value = role;
    form.defaults({ name: role.name });
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
};

const submit = () =>
    check.submit(() => {
        const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };
        if (editing.value) form.put(`/roles/${editing.value.id}`, options);
        else form.post('/roles', options);
    });

const openDetail = (role) => router.get(`/roles/${role.id}`);

/* ── Impor Peranan (Excel) ───────────────────────────────────────────── */
const importOpen = ref(false);
const importInput = ref(null);
const importForm = useForm({ file: null });

const openImport = () => {
    importForm.clearErrors();
    importForm.reset();
    importOpen.value = true;
};

const onImportFile = (event) => {
    importForm.file = event.target.files?.[0] ?? null;
};

const submitImport = () =>
    importForm.post('/roles/import', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            importOpen.value = false;
            importForm.reset();
            if (importInput.value) importInput.value.value = '';
        },
    });

/* ── Seleksi & hapus massal ────────────────────────────────────────── */
const selected = ref([]);
const bulkForm = useForm({ ids: [] });
const bulkConfirm = ref(false);

const runBulkDelete = () => {
    bulkForm.ids = [...selected.value];
    bulkForm.post('/roles/bulk-destroy', {
        preserveScroll: true,
        onSuccess: () => {
            selected.value = [];
            bulkConfirm.value = false;
        },
    });
};

/* ── Hapus ──────────────────────────────────────────────────────────── */
const deleting = ref(null);
const deleteForm = useForm({});
const confirmDelete = () =>
    deleteForm.delete(`/roles/${deleting.value.id}`, {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });
</script>

<template>
    <Head title="Peranan" />
    <AppLayout>
        <div class="space-y-6" data-testid="roles-page-view">
            <DataTableCard
                title="Peranan"
                testid="roles"
                :columns="columns"
                :rows="props.roles"
                :empty-icon="ShieldCheck"
                :show-refresh="false"
                :selectable="canManage"
                :selected="selected"
                @update:selected="selected = $event"
            >
                <template #bulk-actions>
                    <Button
                        variant="destructive"
                        size="sm"
                        :disabled="bulkForm.processing"
                        data-testid="roles-bulk-delete"
                        @click="bulkConfirm = true"
                    >
                        <Trash2 class="size-4" /> {{ ACTION.delete }}
                    </Button>
                </template>

                <template #header-action>
                    <Button
                        v-if="canManage"
                        variant="outline"
                        size="sm"
                        data-testid="roles-import"
                        @click="openImport"
                    >
                        <Upload class="size-4" /> {{ ACTION.import }}
                    </Button>
                    <Button v-if="canManage" size="sm" data-testid="roles-add" @click="openCreate">
                        <Plus class="size-4" /> {{ ACTION.add }}
                    </Button>
                </template>

                <template #cell-name="{ row }">
                    <span class="flex items-center gap-2 font-medium">
                        {{ row.name }}
                        <Lock v-if="row.locked" class="size-3.5 text-muted-foreground" aria-label="Terkunci" />
                    </span>
                </template>

                <template #cell-users_count="{ row }">
                    <span class="tabular-nums">{{ row.users_count }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <RowActions :testid="`roles-actions-${row.id}`">
                        <DropdownMenuItem :data-testid="`roles-detail-${row.id}`" @click="openDetail(row)">
                            <Eye />{{ ACTION.detail }}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            v-if="canManage && !row.locked"
                            :data-testid="`roles-edit-${row.id}`"
                            @click="openEdit(row)"
                        >
                            <Pencil />{{ ACTION.edit }}
                        </DropdownMenuItem>
                        <template v-if="canManage && !row.locked">
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                class="text-destructive data-[highlighted]:text-destructive"
                                :data-testid="`roles-delete-${row.id}`"
                                @click="deleting = row"
                            >
                                <Trash2 />{{ ACTION.delete }}
                            </DropdownMenuItem>
                        </template>
                    </RowActions>
                </template>
            </DataTableCard>

            <!-- Dialog Tambah / Ubah Peranan -->
            <Dialog v-model:open="dialogOpen" :title="editing ? 'Ubah Peranan' : 'Tambah Peranan'">
                <form id="role-form" class="form-dense space-y-[var(--field-gap)]" novalidate @submit.prevent="submit">
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="r-name">Nama Peranan</Label>
                        <Input
                            id="r-name"
                            v-model="form.name"
                            maxlength="50"
                            data-testid="role-form-name"
                            @blur="check.validate('name')"
                        />
                        <p v-if="form.errors.name" class="text-xs font-medium text-destructive" data-testid="role-form-name-error">
                            {{ form.errors.name }}
                        </p>
                    </div>
                </form>

                <template #footer>
                    <Button variant="outline" size="sm" data-testid="role-form-cancel" @click="dialogOpen = false">
                        <X class="size-4" /> {{ ACTION.cancel }}
                    </Button>
                    <Button
                        size="sm"
                        type="submit"
                        form="role-form"
                        :disabled="form.processing"
                        data-testid="role-form-save"
                    >
                        <Save class="size-4" /> {{ form.processing ? ACTION.saving : ACTION.save }}
                    </Button>
                </template>
            </Dialog>

            <!-- Dialog Impor Peranan -->
            <Dialog v-model:open="importOpen" title="Impor Peranan" class="max-w-md">
                <div class="form-dense space-y-[var(--field-gap)]">
                    <p class="text-sm text-muted-foreground">
                        Unduh berkas contoh melalui tombol <span class="font-medium text-foreground">Template</span>,
                        isi datanya, lalu unggah kembali. Baris judul diabaikan dan nama peranan yang sudah ada dilewati.
                    </p>
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="role-import-file">Berkas Excel</Label>
                        <input
                            id="role-import-file"
                            ref="importInput"
                            type="file"
                            accept=".xlsx,.xls"
                            class="block w-full cursor-pointer rounded-md border border-input bg-transparent px-3 py-1.5 text-[13px] file:mr-3 file:rounded file:border-0 file:bg-secondary file:px-2 file:py-1 file:text-xs"
                            data-testid="role-import-file"
                            @change="onImportFile"
                        />
                        <p v-if="importForm.errors.file" class="text-xs font-medium text-destructive" data-testid="role-import-error">
                            {{ importForm.errors.file }}
                        </p>
                    </div>
                </div>

                <template #footer>
                    <Button
                        variant="outline"
                        size="sm"
                        as="a"
                        href="/roles/import/template"
                        class="mr-auto"
                        data-testid="role-import-template"
                    >
                        <FileDown class="size-4" /> Template
                    </Button>
                    <Button variant="outline" size="sm" data-testid="role-import-cancel" @click="importOpen = false">
                        <X class="size-4" /> {{ ACTION.cancel }}
                    </Button>
                    <Button
                        size="sm"
                        :disabled="!importForm.file || importForm.processing"
                        data-testid="role-import-submit"
                        @click="submitImport"
                    >
                        <Loader2 v-if="importForm.processing" class="size-4 animate-spin" />
                        <Upload v-else class="size-4" />
                        {{ ACTION.import }}
                    </Button>
                </template>
            </Dialog>

            <ConfirmDeleteDialog
                :open="bulkConfirm"
                title="Hapus Peranan Terpilih?"
                :description="`${selected.length} peranan akan dihapus. Super Admin dan peranan yang masih dipakai dilewati.`"
                :processing="bulkForm.processing"
                @update:open="bulkConfirm = false"
                @confirm="runBulkDelete"
            />

            <ConfirmDeleteDialog
                :open="Boolean(deleting)"
                title="Hapus Peranan?"
                :processing="deleteForm.processing"
                @update:open="deleting = null"
                @confirm="confirmDelete"
            />
        </div>
    </AppLayout>
</template>
