<script setup>
import { computed, ref, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Save, Trash2, Users2, X } from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Avatar from '@/components/ui/Avatar.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import DropdownMenuItem from '@/components/ui/DropdownMenuItem.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import PhoneInput from '@/components/ui/PhoneInput.vue';
import Combobox from '@/components/ui/Combobox.vue';
import Switch from '@/components/ui/Switch.vue';
import ConfirmDeleteDialog from '@/components/composite/ConfirmDeleteDialog.vue';
import DataTableCard from '@/components/composite/DataTableCard.vue';
import PasswordInput from '@/components/composite/PasswordInput.vue';
import RowActions from '@/components/composite/RowActions.vue';
import StateChip from '@/components/composite/StateChip.vue';
import { ACTION } from '@/constants/labels';
import { initialsOf } from '@/lib/utils';
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
    { key: 'name', label: 'Nama' },
    { key: 'username', label: 'Nama Pengguna' },
    { key: 'email', label: 'Email' },
    { key: 'phone', label: 'Nomor HP' },
    { key: 'role', label: 'Peranan', sortable: false },
    { key: 'status_label', label: 'Status', sortable: false },
    { key: 'last_login_at', label: 'Login Terakhir' },
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
        page: props.users.meta.page ?? 1,
        per_page: props.users.meta.per_page ?? 10,
    },
});

// reka-ui melarang value kosong pada item, jadi 'all' dipakai sebagai sentinel.
const statusOptions = [
    { value: 'all', label: 'Semua status' },
    { value: 'aktif', label: 'Aktif' },
    { value: 'nonaktif', label: 'Nonaktif' },
];

/* ── Dialog tambah / ubah ────────────────────────────────────────────── */
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
        username: row.username,
        email: row.email,
        phone: row.phone ?? '',
        role: row.role ?? props.roleOptions[0]?.value,
        is_active: row.is_active,
        password: '',
    });
    form.reset();
    dialogOpen.value = true;
};

const rules = {
    name: all(required('nama'), min(3, 'Nama'), max(100, 'Nama'), personName('Nama')),
    username: all(required('nama pengguna'), min(3, 'Nama pengguna'), max(50, 'Nama pengguna'), username('Nama pengguna')),
    email: all(required('alamat email'), emailRule('Alamat email'), max(150, 'Alamat email')),
    phone: phone('Nomor HP'),
    role: required('peranan'),
    password: (value) => {
        if (editing.value) return value ? min(8, 'Kata sandi')(value) : '';

        return all(required('kata sandi'), min(8, 'Kata sandi'))(value);
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
                @update:search="onSearch"
                @update:sort="onSort"
                @update:page="onPage"
                @update:per-page="onPerPage"
                @refresh="reload()"
            >
                <template #filters>
                    <Combobox
                        :model-value="query.status"
                        :options="statusOptions"
                        placeholder="Semua status"
                        class="w-[140px]"
                        data-testid="users-filter-status"
                        @update:model-value="onFilter('status', $event)"
                    />
                </template>

                <template #header-action>
                    <Button v-if="canManage" size="sm" data-testid="users-add" @click="openCreate">
                        <Plus class="size-4" /> {{ ACTION.add }}
                    </Button>
                </template>

                <template #cell-name="{ row }">
                    <span class="flex items-center gap-2">
                        <Avatar :fallback="initialsOf(row.name, row.email)" class="size-6" />
                        <span class="font-medium">{{ row.name }}</span>
                    </span>
                </template>

                <template #cell-username="{ row }">
                    <span class="font-mono text-xs text-muted-foreground">{{ row.username }}</span>
                </template>

                <template #cell-role="{ row }">
                    <Badge variant="secondary" class="font-normal">{{ row.role ?? '—' }}</Badge>
                </template>

                <template #cell-status_label="{ row }">
                    <StateChip :label="row.status_label" :chip="row.status_chip" />
                </template>

                <template #cell-actions="{ row }">
                    <RowActions v-if="canManage" :testid="`users-actions-${row.id}`">
                        <DropdownMenuItem :data-testid="`users-edit-${row.id}`" @click="openEdit(row)">
                            <Pencil />{{ ACTION.edit }}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            v-if="row.id !== currentUserId"
                            class="text-destructive data-[highlighted]:text-destructive"
                            :data-testid="`users-delete-${row.id}`"
                            @click="deleting = row"
                        >
                            <Trash2 />{{ ACTION.delete }}
                        </DropdownMenuItem>
                    </RowActions>
                </template>
            </DataTableCard>

            <!-- Dialog tambah / ubah -->
            <Dialog
                v-model:open="dialogOpen"
                :title="editing ? 'Ubah pengguna' : 'Tambah pengguna'"
            >
                <form id="user-form" class="form-dense grid gap-[var(--field-gap)] sm:grid-cols-2" novalidate @submit.prevent="submit">
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="f-name">Nama</Label>
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
                        <Label for="f-username">Nama pengguna</Label>
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
                        <Label for="f-email">Email</Label>
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
                            placeholder="Pilih peranan"
                            class="h-[var(--ctl-h)] text-[13px]"
                            data-testid="user-form-role"
                        />
                    </div>
                    <div class="space-y-[var(--item-gap)] sm:col-span-2">
                        <Label for="f-password">Kata sandi</Label>
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
                            <p class="text-[13px] font-medium">Akun aktif</p>
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

            <ConfirmDeleteDialog
                :open="Boolean(deleting)"
                title="Hapus pengguna?"
                :processing="deleteForm.processing"
                @update:open="deleting = null"
                @confirm="confirmDelete"
            />
        </div>
    </AppLayout>
</template>
