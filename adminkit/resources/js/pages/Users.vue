<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ArchiveRestore, Download, FileDown, Loader2, MailCheck, Pencil, Plus, Trash2, Upload, Users2, X } from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import { menuLabelOf } from '@/composables/useMenuLabel';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import DropdownMenuItem from '@/components/ui/DropdownMenuItem.vue';
import DropdownMenuSeparator from '@/components/ui/DropdownMenuSeparator.vue';
import FileInput from '@/components/ui/FileInput.vue';
import Label from '@/components/ui/Label.vue';
import Combobox from '@/components/ui/Combobox.vue';
import ConfirmDeleteDialog from '@/components/composite/ConfirmDeleteDialog.vue';
import UploadProgress from '@/components/composite/UploadProgress.vue';
import DataTableCard from '@/components/composite/DataTableCard.vue';
import RowActions from '@/components/composite/RowActions.vue';
import { ACTION } from '@/constants/labels';
import { useServerTable } from '@/composables/useServerTable';

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
    { key: 'office', label: 'Kantor', hideBelow: 'xl' },
    { key: 'status_label', label: 'Status', sortable: false },
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
        status: props.filters.status || 'aktif',
        role: props.filters.role || 'all',
        page: props.users.meta.page ?? 1,
        per_page: props.users.meta.per_page ?? 10,
    },
});

// Pengguna terarsip = soft deleted; hanya muncul bila filter status dipilih.
const statusOptions = [
    { value: 'aktif', label: 'Aktif' },
    { value: 'terarsip', label: 'Terarsip' },
    { value: 'semua', label: 'Semua Status' },
];

// Opsi filter peran diambil dari data peranan (dinamis).
const roleFilterOptions = computed(() => [
    { value: 'all', label: 'Semua Peranan' },
    ...props.roleOptions,
]);

const sendWelcomeEmail = (row) =>
    router.post(`/users/${row.id}/welcome-email`, {}, { preserveScroll: true, preserveState: true });

const restore = (row) =>
    router.post(`/users/${row.id}/restore`, {}, { preserveScroll: true, preserveState: true });

/* ── Ekspor Excel mengikuti filter aktif ───────────────────────────────── */
const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (query.search) params.set('search', query.search);
    if (query.status) params.set('status', query.status);
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
const bulkConfirm = ref(null);

const runBulk = (action) => {
    bulkForm.action = action;
    bulkForm.ids = [...selected.value];
    bulkForm.post('/users/bulk', {
        preserveScroll: true,
        onSuccess: () => {
            selected.value = [];
            bulkConfirm.value = null;
        },
    });
};

/* ── Arsip & hapus permanen per baris ───────────────────────────────── */
const archiving = ref(null);
const purging = ref(null);
const rowForm = useForm({});

const confirmArchive = () =>
    rowForm.delete(`/users/${archiving.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            archiving.value = null;
        },
    });

const confirmPurge = () =>
    rowForm.delete(`/users/${purging.value.id}/force`, {
        preserveScroll: true,
        onFinish: () => {
            purging.value = null;
        },
    });

const pageTitle = computed(() => menuLabelOf('/users', 'Pengguna'));
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <div class="space-y-6" data-testid="users-page-view">
            <DataTableCard
                server
                :title="pageTitle"
                testid="users"
                :columns="columns"
                :rows="props.users.data"
                :meta="props.users.meta"
                :search="query.search"
                :sort="sortState"
                :loading="loading"
                :empty-icon="Users2"
                :empty-title="query.status === 'terarsip' ? 'Tidak ada pengguna terarsip' : 'Belum ada pengguna'"
                :empty-description="
                    query.status === 'terarsip'
                        ? 'Pengguna yang diarsipkan akan muncul di sini dan dapat dipulihkan kembali.'
                        : 'Tambahkan pengguna pertama untuk mulai mengelola akses.'
                "
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
                        placeholder="Status"
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
                        data-testid="users-bulk-restore"
                        @click="runBulk('restore')"
                    >
                        <ArchiveRestore class="size-4" /> {{ ACTION.restore }}
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="bulkForm.processing"
                        data-testid="users-bulk-archive"
                        @click="bulkConfirm = 'archive'"
                    >
                        <Trash2 class="size-4" /> {{ ACTION.archive }}
                    </Button>
                    <Button
                        variant="destructive"
                        size="sm"
                        :disabled="bulkForm.processing"
                        data-testid="users-bulk-force-delete"
                        @click="bulkConfirm = 'force-delete'"
                    >
                        <Trash2 class="size-4" /> Hapus Permanen
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
                    <Button v-if="canManage" size="sm" as="a" href="/users/create" data-testid="users-add">
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

                <template #cell-office="{ row }">
                    <span>{{ row.office ?? '—' }}</span>
                </template>

                <template #cell-status_label="{ row }">
                    <Badge
                        :variant="row.archived ? 'destructive' : 'secondary'"
                        class="whitespace-nowrap font-medium"
                        :data-testid="`users-status-${row.id}`"
                    >
                        {{ row.status_label }}
                    </Badge>
                </template>

                <template #cell-actions="{ row }">
                    <RowActions v-if="canManage" :testid="`users-actions-${row.id}`">
                        <DropdownMenuItem
                            v-if="!row.archived"
                            :data-testid="`users-edit-${row.id}`"
                            @select="router.visit(`/users/${row.id}/edit`)"
                        >
                            <Pencil />{{ ACTION.edit }}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            v-if="!row.archived"
                            :disabled="!row.email"
                            :data-testid="`users-welcome-email-${row.id}`"
                            @select="sendWelcomeEmail(row)"
                        >
                            <MailCheck />Kirim Email Sambutan
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            v-if="row.archived"
                            :data-testid="`users-restore-${row.id}`"
                            @select="restore(row)"
                        >
                            <ArchiveRestore />{{ ACTION.restore }}
                        </DropdownMenuItem>
                        <template v-if="row.id !== currentUserId">
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                v-if="!row.archived"
                                class="text-destructive data-[highlighted]:text-destructive"
                                :data-testid="`users-archive-${row.id}`"
                                @click="archiving = row"
                            >
                                <Trash2 />{{ ACTION.archive }}
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-else
                                class="text-destructive data-[highlighted]:text-destructive"
                                :data-testid="`users-force-delete-${row.id}`"
                                @click="purging = row"
                            >
                                <Trash2 />Hapus Permanen
                            </DropdownMenuItem>
                        </template>
                    </RowActions>
                </template>
            </DataTableCard>

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
                        <UploadProgress
                            :progress="importForm.progress"
                            label="Mengunggah berkas Excel"
                            testid="user-import-progress"
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
                :open="bulkConfirm === 'archive'"
                title="Arsipkan Pengguna Terpilih?"
                :description="`${selected.length} pengguna akan diarsipkan dan tidak dapat masuk lagi. Akun Anda sendiri dilewati.`"
                :processing="bulkForm.processing"
                @update:open="bulkConfirm = null"
                @confirm="runBulk('archive')"
            />

            <ConfirmDeleteDialog
                :open="bulkConfirm === 'force-delete'"
                title="Hapus Permanen Pengguna Terpilih?"
                :description="`${selected.length} pengguna akan dihapus permanen dan tidak dapat dipulihkan. Akun Anda sendiri dilewati.`"
                :processing="bulkForm.processing"
                @update:open="bulkConfirm = null"
                @confirm="runBulk('force-delete')"
            />

            <ConfirmDeleteDialog
                :open="Boolean(archiving)"
                title="Arsipkan Pengguna?"
                description="Pengguna tidak dapat masuk lagi, namun datanya tetap tersimpan dan bisa dipulihkan."
                :processing="rowForm.processing"
                @update:open="archiving = null"
                @confirm="confirmArchive"
            />

            <ConfirmDeleteDialog
                :open="Boolean(purging)"
                title="Hapus Permanen Pengguna?"
                description="Data pengguna dihapus selamanya dan tidak dapat dipulihkan."
                :processing="rowForm.processing"
                @update:open="purging = null"
                @confirm="confirmPurge"
            />
        </div>
    </AppLayout>
</template>
