<script setup>
import { computed, ref, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Lock, Pencil, Plus, Save, ShieldCheck, Trash2, X } from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardFooter from '@/components/ui/CardFooter.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Dialog from '@/components/ui/Dialog.vue';
import DropdownMenuItem from '@/components/ui/DropdownMenuItem.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Table from '@/components/ui/Table.vue';
import TableBody from '@/components/ui/TableBody.vue';
import TableCell from '@/components/ui/TableCell.vue';
import TableHead from '@/components/ui/TableHead.vue';
import TableHeader from '@/components/ui/TableHeader.vue';
import TableRow from '@/components/ui/TableRow.vue';
import ConfirmDeleteDialog from '@/components/composite/ConfirmDeleteDialog.vue';
import DataTableCard from '@/components/composite/DataTableCard.vue';
import RowActions from '@/components/composite/RowActions.vue';
import { ACTION } from '@/constants/labels';

const props = defineProps({
    roles: { type: Array, default: () => [] },
    modules: { type: Array, default: () => [] },
});

const page = usePage();
const canManage = computed(() => (page.props.auth?.user?.permissions ?? []).includes('roles.manage'));

const columns = [
    { key: 'name', label: 'Peranan' },
    { key: 'users_count', label: 'Pengguna', align: 'right' },
    { key: 'permissions_count', label: 'Jumlah Izin', align: 'right' },
    { key: 'actions', label: '', align: 'right', width: '48px', sortable: false },
];

/* ── Matriks izin yang dapat diubah ──────────────────────────────────── */
const matrix = ref({});

const buildMatrix = () => {
    matrix.value = Object.fromEntries(props.roles.map((r) => [r.id, [...r.permissions]]));
};
buildMatrix();
watch(() => props.roles, buildMatrix, { deep: true });

const isChecked = (roleId, permission) => matrix.value[roleId]?.includes(permission) ?? false;

const toggle = (role, permission, checked) => {
    if (role.locked || !canManage.value) return;
    const list = matrix.value[role.id] ?? [];
    matrix.value[role.id] = checked ? [...new Set([...list, permission])] : list.filter((p) => p !== permission);
};

const dirty = computed(() =>
    props.roles.some((r) => {
        const before = [...r.permissions].sort().join(',');
        const after = [...(matrix.value[r.id] ?? [])].sort().join(',');
        return before !== after;
    }),
);

const matrixForm = useForm({ matrix: {} });

const saveMatrix = () => {
    matrixForm.matrix = matrix.value;
    matrixForm.put('/roles/matrix', { preserveScroll: true });
};

/* ── Dialog tambah / ubah peranan ────────────────────────────────────── */
const dialogOpen = ref(false);
const editing = ref(null);
const form = useForm({ name: '', permissions: [] });

const openCreate = () => {
    editing.value = null;
    form.defaults({ name: '', permissions: [] });
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
};

const openEdit = (role) => {
    editing.value = role;
    form.defaults({ name: role.name, permissions: [...role.permissions] });
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
};

const toggleFormPermission = (permission, checked) => {
    form.permissions = checked
        ? [...new Set([...form.permissions, permission])]
        : form.permissions.filter((p) => p !== permission);
};

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };
    if (editing.value) form.put(`/roles/${editing.value.id}`, options);
    else form.post('/roles', options);
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

                <template #cell-permissions_count="{ row }">
                    <Badge variant="secondary" class="font-normal tabular-nums">{{ row.permissions_count }}</Badge>
                </template>

                <template #cell-actions="{ row }">
                    <RowActions v-if="canManage && !row.locked" :testid="`roles-actions-${row.id}`">
                        <DropdownMenuItem :data-testid="`roles-edit-${row.id}`" @click="openEdit(row)">
                            <Pencil />{{ ACTION.edit }}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            class="text-destructive data-[highlighted]:text-destructive"
                            :data-testid="`roles-delete-${row.id}`"
                            @click="deleting = row"
                        >
                            <Trash2 />{{ ACTION.delete }}
                        </DropdownMenuItem>
                    </RowActions>
                </template>
            </DataTableCard>

            <!-- Matriks hak akses -->
            <Card>
                <CardHeader>
                    <CardTitle>Matriks Hak Akses</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <Table class="tbl-density" data-testid="permission-matrix">
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="pl-6">Modul</TableHead>
                                <TableHead>Izin</TableHead>
                                <TableHead v-for="role in props.roles" :key="role.id" class="text-center">
                                    {{ role.name }}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-for="mod in props.modules" :key="mod.key">
                                <TableRow v-for="(ability, index) in mod.abilities" :key="ability.name">
                                    <TableCell class="pl-6 font-medium">
                                        {{ index === 0 ? mod.label : '' }}
                                    </TableCell>
                                    <TableCell class="text-muted-foreground">{{ ability.label }}</TableCell>
                                    <TableCell v-for="role in props.roles" :key="role.id" class="text-center">
                                        <span class="flex justify-center">
                                            <Checkbox
                                                :model-value="isChecked(role.id, ability.name)"
                                                :disabled="role.locked || !canManage"
                                                :class="(role.locked || !canManage) && 'opacity-50'"
                                                :data-testid="`perm-${role.id}-${ability.name}`"
                                                @update:model-value="toggle(role, ability.name, $event)"
                                            />
                                        </span>
                                    </TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>
                </CardContent>
                <CardFooter v-if="canManage" class="justify-between">
                    <span class="text-xs text-muted-foreground" data-testid="matrix-dirty-state">
                        {{ dirty ? 'Ada perubahan yang belum disimpan.' : 'Tidak ada perubahan.' }}
                    </span>
                    <Button
                        size="sm"
                        :disabled="!dirty || matrixForm.processing"
                        data-testid="matrix-save"
                        @click="saveMatrix"
                    >
                        <Save class="size-4" /> {{ matrixForm.processing ? ACTION.saving : ACTION.save }}
                    </Button>
                </CardFooter>
            </Card>

            <!-- Dialog tambah / ubah peranan -->
            <Dialog
                v-model:open="dialogOpen"
                :title="editing ? 'Ubah peranan' : 'Tambah peranan'"
            >
                <form id="role-form" class="form-dense space-y-[var(--field-gap)]" @submit.prevent="submit">
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="r-name">Nama peranan</Label>
                        <Input id="r-name" v-model="form.name" placeholder="mis. Editor" data-testid="role-form-name" />
                        <p v-if="form.errors.name" class="text-xs font-medium text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-[var(--item-gap)]">
                        <Label>Izin</Label>
                        <div class="thin-scroll max-h-64 space-y-3 overflow-y-auto rounded-lg border bg-muted/30 p-3">
                            <div v-for="mod in props.modules" :key="mod.key" class="space-y-1.5">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    {{ mod.label }}
                                </p>
                                <div class="flex flex-wrap gap-x-6 gap-y-2">
                                    <label
                                        v-for="ability in mod.abilities"
                                        :key="ability.name"
                                        class="flex cursor-pointer items-center gap-2 text-[13px]"
                                    >
                                        <Checkbox
                                            :model-value="form.permissions.includes(ability.name)"
                                            :data-testid="`role-form-perm-${ability.name}`"
                                            @update:model-value="toggleFormPermission(ability.name, $event)"
                                        />
                                        {{ ability.label }}
                                    </label>
                                </div>
                            </div>
                        </div>
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
                title="Hapus peranan?"
                :processing="deleteForm.processing"
                @update:open="deleting = null"
                @confirm="confirmDelete"
            />
        </div>
    </AppLayout>
</template>
