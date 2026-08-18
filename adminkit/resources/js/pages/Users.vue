<script setup>
import { computed, ref, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Download, FileDown, Loader2, Pencil, Plus, Save, ToggleLeft, ToggleRight, Trash2, Upload, Users2, X } from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import DropdownMenuItem from '@/components/ui/DropdownMenuItem.vue';
import DropdownMenuSeparator from '@/components/ui/DropdownMenuSeparator.vue';
import FileInput from '@/components/ui/FileInput.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import PhoneInput from '@/components/ui/PhoneInput.vue';
import Combobox from '@/components/ui/Combobox.vue';
import Switch from '@/components/ui/Switch.vue';
import ConfirmDeleteDialog from '@/components/composite/ConfirmDeleteDialog.vue';
import DataTableCard from '@/components/composite/DataTableCard.vue';
import PasswordInput from '@/components/composite/PasswordInput.vue';
import RowActions from '@/components/composite/RowActions.vue';
import { ACTION } from '@/constants/labels';
import { useServerTable } from '@/composables/useServerTable';
import { all, email as emailRule, max, min, personName, phone, required, username } from '@/lib/validators';
import { useLiveValidation } from '@/composables/useLiveValidation';

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    roleOptions: { type: Array, default: () => [] },
});

const page = usePage();
const can = (perm) => (page.props.auth?.user?.permissions ?? []).includes(perm);
const canManage = computed(() => can('users.manage'));
const currentUserId = computed(() => page.props.auth?.user?.id);

const columns = [
    { key: 'name', label: 'Nama Lengkap' },
    { key: 'username', label: 'Nama Pengguna', hideBelow: 'lg' },
    { key: 'email', label: 'Alamat Email', hideBelow: 'md' },
    { key: 'phone', label: 'Nomor HP', hideBelow: 'xl' },
    { key: 'role', label: 'Peranan', hideBelow: 'sm' },
    { key: 'status_label', label: 'Status', sortKey: 'is_active' },
    { key: 'actions', label: '', align: 'right', width: '48px', sortable: false },
];

/* ── Query server-side: pencarian (debounce), urut, paginasi ─────────── */
const { query, loading, reload, onSearch, onSort, onPage, onPerPage, onFilter, sortState } = useServerTable({
    url: '/users',
    only: ['users', 'filters'],
    initial: {
        search: props.filters.search ?? '',
        sort: props.filters.sort ?? 'name',
        dir: props.filters.dir ?? 'asc',
        status: props.filters.status || 'all',
        role: props.filters.role || 'all',
        page: props.users.meta.page ?? 1,
        per_page: props.users.meta.per_page ?? 10,
    },
});

// reka-ui melarang value kosong pada item, jadi 'all' dipakai sebagai sentinel.
const statusOptions = [
    { value: 'all', label: 'Semua Status' },
    { value: 'aktif', label: 'Aktif' },
    { value: 'nonaktif', label: 'Nonaktif' },
];

// Opsi filter peran diambil dari data peranan (dinamis).
const roleFilterOptions = computed(() => [
    { value: 'all', label: 'Semua Peranan' },
    ...props.roleOptions,
]);

/* ── Dialog Tambah / Ubah ────────────────────────────────────────────── */
const dialogOpen = ref(false);
const editing = ref(null);

const form = useForm({
    name: '',
    username: '',
    email: '',
    phone: '',
    role: props.roleOptions[0]?.value ?? '',
    is_active: true,
    password: '',
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.role = props.roleOptions[0]?.value ?? '';
    dialogOpen.value = true;
};

const openEdit = (row) => {
    editing.value = row;
    form.clearErrors();
    form.defaults({
        name: row.name,
        username: row.username ?? '',
        email: row.email ?? '',
        phone: row.phone ?? '',
        role: row.role ?? props.roleOptions[0]?.value,
        is_active: row.is_active,
        password: '',
    });
    form.reset();
    dialogOpen.value = true;
};

const rules = {
    name: all(required('nama lengkap'), min(3, 'Nama Lengkap'), max(100, 'Nama Lengkap'), personName('Nama Lengkap')),
    username: all(min(3, 'Nama Pengguna'), max(50, 'Nama Pengguna'), username('Nama Pengguna')),
    email: all(emailRule('Alamat Email'), max(150, 'Alamat Email')),
    phone: phone('Nomor HP'),
    role: required('peranan'),
    password: (value) => {
        if (editing.value) return value ? min(8, 'Kata Sandi')(value) : '';

        return all(required('kata sandi'), min(8, 'Kata Sandi'))(value);
    },
};

const check = useLiveValidation(form, rules);

const submit = () => {
    if (!check.validateAll()) return;

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
            form.reset('password');
        },
    };

    if (editing.value) form.put(`/users/${editing.value.id}`, options);
    else form.post('/users', options);
};

/* ── Ekspor Excel mengikuti filter aktif ───────────────────────────────── */
const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (query.search) params.set('search', query.search);
    if (query.status && query.status !== 'all') params.set('status', query.status);
    if (query.role && query.role !== 'all') params.set('role', query.role);

    return `/users/export?${params.toString()}`;
});

/* ── Impor pengguna (Excel) ────────────────────────────────────────────── */
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
    importForm.post('/users/import', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            importOpen.value = false;
            importForm.reset();
            importInput.value?.clear();
        },
    });

/* ── Seleksi & aksi massal ─────────────────────────────────────────── */
const selected = ref([]);
const bulkForm = useForm({ action: '', ids: [] });
const bulkConfirm = ref(false);

const runBulk = (action) => {
    bulkForm.action = action;
    bulkForm.ids = [...selected.value];
    bulkForm.post('/users/bulk', {
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

const confirmDelete = () => {
    deleteForm.delete(`/users/${deleting.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = null;
        },
    });
};

watch(dialogOpen, (open) => {
    if (!open) editing.value = null;
});
</script>

<template>
    <Head title="Pengguna" />
    <AppLayout>
        <div class="space-y-6" data-testid="users-page-view">
            <DataTableCard
                server
                title="Pengguna"
                testid="users"
                :columns="columns"
                :rows="props.users.data"
                :meta="props.users.meta"
                :search="query.search"
                :sort="sortState"
                :loading="loading"
                :empty-icon="Users2"
                empty-title="Belum ada pengguna"
                empty-description="Tambahkan pengguna pertama untuk mulai mengelola akses."
                :show-refresh="false"
                selectable
                :selected="selected"
                @update:selected="selected = $event"
                @update:search="onSearch"
                @update:sort="onSort"
                @update:page="onPage"
                @update:per-page="onPerPage"
                @refresh="reload()"
            >
                <template #filters>
                    <Combobox
                        :model-value="query.role"
                        :options="roleFilterOptions"
                        placeholder="Semua Peranan"
                        class="w-[150px]"
                        data-testid="users-filter-role"
                        @update:model-value="onFilter('role', $event)"
                    />
                    <Combobox
                        :model-value="query.status"
                        :options="statusOptions"
                        placeholder="Semua Status"
                        class="w-[140px]"
                        data-testid="users-filter-status"
                        @update:model-value="onFilter('status', $event)"
                    />
                </template>

                <template #bulk-actions>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="bulkForm.processing"
                        data-testid="users-bulk-activate"
                        @click="runBulk('activate')"
                    >
                        <ToggleRight class="size-4" /> Aktifkan
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="bulkForm.processing"
                        data-testid="users-bulk-deactivate"
                        @click="runBulk('deactivate')"
                    >
                        <ToggleLeft class="size-4" /> Nonaktifkan
                    </Button>
                    <Button
                        variant="destructive"
                        size="sm"
                        :disabled="bulkForm.processing"
                        data-testid="users-bulk-delete"
                        @click="bulkConfirm = true"
                    >
                        <Trash2 class="size-4" /> {{ ACTION.delete }}
                    </Button>
                </template>

                <template #header-action>
                    <Button variant="outline" size="sm" as="a" :href="exportUrl" data-testid="users-export">
                        <Download class="size-4" /> {{ ACTION.export }}
                    </Button>
                    <Button
                        v-if="canManage"
                        variant="outline"
                        size="sm"
                        data-testid="users-import"
                        @click="openImport"
                    >
                        <Upload class="size-4" /> {{ ACTION.import }}
                    </Button>
                    <Button v-if="canManage" size="sm" data-testid="users-add" @click="openCreate">
                        <Plus class="size-4" /> {{ ACTION.add }}
                    </Button>
                </template>

                <template #cell-name="{ row }">
                    <span class="block max-w-[45vw] truncate font-medium sm:max-w-none">{{ row.name }}</span>
                </template>

                <template #cell-username="{ row }">
                    <span class="font-mono text-xs text-muted-foreground">{{ row.username ?? '—' }}</span>
                </template>

                <template #cell-role="{ row }">
                    <span>{{ row.role ?? '—' }}</span>
                </template>

                <template #cell-status_label="{ row }">
                    <Badge
                        :variant="row.is_active ? 'secondary' : 'destructive'"
                        class="whitespace-nowrap font-medium"
                        :data-testid="`users-status-${row.id}`"
                    >
                        {{ row.status_label }}
                    </Badge>
                </template>

                <template #cell-actions="{ row }">
                    <RowActions v-if="canManage" :testid="`users-actions-${row.id}`">
                        <DropdownMenuItem :data-testid="`users-edit-${row.id}`" @click="openEdit(row)">
                            <Pencil />{{ ACTION.edit }}
                        </DropdownMenuItem>
                        <template v-if="row.id !== currentUserId">
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                class="text-destructive data-[highlighted]:text-destructive"
                                :data-testid="`users-delete-${row.id}`"
                                @click="deleting = row"
                            >
                                <Trash2 />{{ ACTION.delete }}
                            </DropdownMenuItem>
                        </template>
                    </RowActions>
                </template>
            </DataTableCard>

            <!-- Dialog Tambah / Ubah -->
            <Dialog v-model:open="dialogOpen" :title="editing ? 'Ubah Pengguna' : 'Tambah Pengguna'">
                <form id="user-form" class="form-dense grid gap-[var(--field-gap)] sm:grid-cols-2" novalidate @submit.prevent="submit">
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="f-name">Nama Lengkap</Label>
                        <Input
                            id="f-name"
                            v-model="form.name"
                            maxlength="100"
                            data-testid="user-form-name"
                            @blur="check.validate('name')"
                        />
                        <p v-if="form.errors.name" class="text-xs font-medium text-destructive" data-testid="user-form-name-error">
                            {{ form.errors.name }}
                        </p>
                    </div>
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="f-username">Nama Pengguna</Label>
                        <Input
                            id="f-username"
                            v-model="form.username"
                            maxlength="50"
                            data-testid="user-form-username"
                            @blur="check.validate('username')"
                        />
                        <p v-if="form.errors.username" class="text-xs font-medium text-destructive" data-testid="user-form-username-error">
                            {{ form.errors.username }}
                        </p>
                    </div>
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="f-email">Alamat Email</Label>
                        <Input
                            id="f-email"
                            v-model="form.email"
                            type="email"
                            maxlength="150"
                            data-testid="user-form-email"
                            @blur="check.validate('email')"
                        />
                        <p v-if="form.errors.email" class="text-xs font-medium text-destructive" data-testid="user-form-email-error">
                            {{ form.errors.email }}
                        </p>
                    </div>
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="f-phone">Nomor HP</Label>
                        <PhoneInput
                            id="f-phone"
                            v-model="form.phone"
                            testid="user-form-phone"
                            @blur="check.validate('phone')"
                        />
                        <p v-if="form.errors.phone" class="text-xs font-medium text-destructive" data-testid="user-form-phone-error">
                            {{ form.errors.phone }}
                        </p>
                    </div>
                    <div class="space-y-[var(--item-gap)]">
                        <Label>Peranan</Label>
                        <Combobox
                            v-model="form.role"
                            :options="props.roleOptions"
                            placeholder="Pilih Peranan"
                            class="h-[var(--ctl-h)] text-[13px]"
                            data-testid="user-form-role"
                        />
                        <p v-if="form.errors.role" class="text-xs font-medium text-destructive" data-testid="user-form-role-error">
                            {{ form.errors.role }}
                        </p>
                    </div>
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="f-last-login">Terakhir Login</Label>
                        <Input
                            id="f-last-login"
                            :model-value="editing?.last_login_at ?? '—'"
                            readonly
                            disabled
                            data-testid="user-form-last-login"
                        />
                    </div>
                    <div class="space-y-[var(--item-gap)] sm:col-span-2">
                        <Label for="f-password">Kata Sandi</Label>
                        <PasswordInput
                            id="f-password"
                            v-model="form.password"
                            :placeholder="editing ? 'Biarkan kosong bila tidak diubah' : 'Minimal 8 karakter'"
                            testid="user-form-password"
                            @blur="check.validate('password')"
                        />
                        <p v-if="form.errors.password" class="text-xs font-medium text-destructive" data-testid="user-form-password-error">
                            {{ form.errors.password }}
                        </p>
                    </div>
                    <div class="flex items-center justify-between gap-4 rounded-lg border bg-muted/30 px-3 py-2 sm:col-span-2">
                        <div>
                            <p class="text-[13px] font-medium">Akun Aktif</p>
                            <p class="text-xs text-muted-foreground">Akun nonaktif tidak dapat masuk ke aplikasi.</p>
                        </div>
                        <Switch v-model="form.is_active" data-testid="user-form-active" />
                    </div>
                </form>

                <template #footer>
                    <Button variant="outline" size="sm" data-testid="user-form-cancel" @click="dialogOpen = false">
                        <X class="size-4" /> {{ ACTION.cancel }}
                    </Button>
                    <Button
                        size="sm"
                        type="submit"
                        form="user-form"
                        :disabled="form.processing"
                        data-testid="user-form-save"
                    >
                        <Save class="size-4" /> {{ form.processing ? ACTION.saving : ACTION.save }}
                    </Button>
                </template>
            </Dialog>

            <!-- Dialog Impor Pengguna -->
            <Dialog v-model:open="importOpen" title="Impor Pengguna" class="max-w-md">
                <div class="form-dense space-y-[var(--field-gap)]">
                    <p class="text-sm text-muted-foreground">
                        Unduh berkas contoh melalui tombol <span class="font-medium text-foreground">Template</span>,
                        isi datanya, lalu unggah kembali. Baris judul diabaikan, baris tidak valid dilewati, dan kata
                        sandi yang dibiarkan kosong diisi acak.
                    </p>
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="user-import-file">Berkas Excel</Label>
                        <FileInput
                            id="user-import-file"
                            ref="importInput"
                            accept=".xlsx,.xls"
                            data-testid="user-import-file"
                            @change="onImportFile"
                        />
                        <p v-if="importForm.errors.file" class="text-xs font-medium text-destructive" data-testid="user-import-error">
                            {{ importForm.errors.file }}
                        </p>
                    </div>
                </div>

                <template #footer>
                    <Button
                        variant="outline"
                        size="sm"
                        as="a"
                        href="/users/import/template"
                        class="mr-auto"
                        data-testid="user-import-template"
                    >
                        <FileDown class="size-4" /> Template
                    </Button>
                    <Button variant="outline" size="sm" data-testid="user-import-cancel" @click="importOpen = false">
                        <X class="size-4" /> {{ ACTION.cancel }}
                    </Button>
                    <Button
                        size="sm"
                        :disabled="!importForm.file || importForm.processing"
                        data-testid="user-import-submit"
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
                title="Hapus Pengguna Terpilih?"
                :description="`${selected.length} pengguna akan dihapus permanen. Akun Anda sendiri dilewati.`"
                :processing="bulkForm.processing"
                @update:open="bulkConfirm = false"
                @confirm="runBulk('delete')"
            />

            <ConfirmDeleteDialog
                :open="Boolean(deleting)"
                title="Hapus Pengguna?"
                :processing="deleteForm.processing"
                @update:open="deleting = null"
                @confirm="confirmDelete"
            />
        </div>
    </AppLayout>
</template>
