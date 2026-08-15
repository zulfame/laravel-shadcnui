<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Eye, Lock, Pencil, Plus, Save, ShieldCheck, Trash2, X } from 'lucide-vue-next';

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
            >
                <template #header-action>
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
