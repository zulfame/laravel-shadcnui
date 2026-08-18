<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Copy,
    Database,
    Download,
    FileArchive,
    FileText,
    Film,
    Image as ImageIcon,
    Loader2,
    Music,
    Pencil,
    Save,
    Trash2,
    Upload,
    X,
} from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Combobox from '@/components/ui/Combobox.vue';
import Dialog from '@/components/ui/Dialog.vue';
import DropdownMenuItem from '@/components/ui/DropdownMenuItem.vue';
import FileInput from '@/components/ui/FileInput.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import ConfirmDeleteDialog from '@/components/composite/ConfirmDeleteDialog.vue';
import DataTableCard from '@/components/composite/DataTableCard.vue';
import RowActions from '@/components/composite/RowActions.vue';
import UploadProgress from '@/components/composite/UploadProgress.vue';
import { menuLabelOf } from '@/composables/useMenuLabel';
import { notify } from '@/composables/useToast';

const props = defineProps({
    folders: { type: Array, default: () => [] },
    files: { type: Array, default: () => [] },
    query: { type: Object, default: () => ({ folder: '', search: '' }) },
});

const page = usePage();
const pageTitle = computed(() => menuLabelOf('/object-storage', 'Object Storage'));

const KIND_ICONS = {
    gambar: ImageIcon,
    video: Film,
    audio: Music,
    dokumen: FileText,
    arsip: FileArchive,
};

const iconOfKind = (kind) => KIND_ICONS[kind] ?? FileText;

const bytes = (value) => {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = value;
    let unit = 0;

    while (size >= 1024 && unit < units.length - 1) {
        size /= 1024;
        unit += 1;
    }

    return `${size.toFixed(unit > 1 ? 1 : 0).replace('.', ',')} ${units[unit]}`;
};

const columns = [
    { key: 'name', label: 'Berkas', sortable: true },
    { key: 'kind', label: 'Jenis', sortable: true, hideBelow: 'sm' },
    { key: 'folder', label: 'Folder', sortable: true, hideBelow: 'md' },
    { key: 'size', label: 'Ukuran', sortable: true, align: 'right', hideBelow: 'sm' },
    { key: 'modified_at', label: 'Diubah', hideBelow: 'lg' },
    { key: 'actions', label: '', align: 'right', width: 'w-12' },
];

const selected = ref([]);
const uploadOpen = ref(false);
const renameOpen = ref(false);
const confirmOpen = ref(false);
const uploadInput = ref(null);
const target = ref(null);

const uploadForm = useForm({ folder: 'uploads', files: [] });
const renameForm = useForm({ path: '', target: '' });

const onFolder = (folder) =>
    router.get('/object-storage', { folder, search: props.query.search }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });

const onSearch = (value) =>
    router.get('/object-storage', { folder: props.query.folder, search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });

const onPickFiles = (event) => {
    uploadForm.files = Array.from(event.target.files ?? []);
};

const submitUpload = () => {
    uploadForm.clearErrors();
    uploadForm.post('/object-storage', {
        forceFormData: true,
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadOpen.value = false;
            uploadForm.files = [];
            uploadInput.value?.clear();
        },
    });
};

const openRename = (file) => {
    target.value = file;
    renameForm.defaults({ path: file.path, target: file.path });
    renameForm.reset();
    renameForm.clearErrors();
    renameOpen.value = true;
};

const submitRename = () => {
    renameForm.clearErrors();
    renameForm.put('/object-storage/rename', {
        // preserveState wajib: tanpa ini komponen dipasang ulang setelah redirect
        // sehingga state dialog & pesan validasi hilang.
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => (renameOpen.value = false),
    });
};

/** Pesan galat dari form maupun props halaman (redirect back membawa error bag). */
const renameErrors = computed(() => {
    const fromForm = renameForm.errors ?? {};

    return Object.keys(fromForm).length ? fromForm : (page.props.errors ?? {});
});

const askDelete = (paths) => {
    selected.value = paths;
    confirmOpen.value = true;
};

const destroy = () =>
    router.delete('/object-storage', {
        data: { paths: selected.value },
        preserveScroll: true,
        onSuccess: () => {
            confirmOpen.value = false;
            selected.value = [];
        },
    });

const copyUrl = async (url) => {
    try {
        await navigator.clipboard.writeText(url);
        notify.success('Tautan berkas disalin.');
    } catch (e) {
        notify.error('Peramban menolak akses papan klip.');
    }
};

const slugOf = (path) => path.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <div class="space-y-6" data-testid="storage-page">
            <DataTableCard
                :title="pageTitle"
                testid="storage"
                :columns="columns"
                :rows="props.files"
                row-key="path"
                :empty-icon="Database"
                empty-title="Belum ada berkas"
                empty-description="Unggah berkas pertama Anda ke object storage."
                :show-refresh="false"
                selectable
                :selected="selected"
                :search="props.query.search"
                @update:selected="selected = $event"
                @update:search="onSearch"
            >
                <template #filters>
                    <Combobox
                        :model-value="props.query.folder"
                        :options="props.folders"
                        placeholder="Semua Folder"
                        class="w-[180px]"
                        data-testid="storage-filter-folder"
                        @update:model-value="onFolder"
                    />
                </template>

                <template #header-action>
                    <Button
                        v-if="selected.length"
                        variant="outline"
                        size="sm"
                        class="text-destructive"
                        data-testid="storage-bulk-delete"
                        @click="askDelete([...selected])"
                    >
                        <Trash2 class="size-4" /> Hapus ({{ selected.length }})
                    </Button>
                    <Button size="sm" data-testid="storage-upload-open" @click="uploadOpen = true">
                        <Upload class="size-4" /> Unggah
                    </Button>
                </template>

                <template #cell-name="{ row }">
                    <span class="flex min-w-0 items-center gap-2.5">
                        <span
                            class="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-md border bg-muted/40"
                        >
                            <img
                                v-if="row.is_image"
                                :src="row.url"
                                :alt="row.name"
                                class="size-full object-cover"
                                loading="lazy"
                            />
                            <component
                                :is="iconOfKind(row.kind)"
                                v-else
                                class="size-3.5 text-muted-foreground"
                                aria-hidden="true"
                            />
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate font-medium">{{ row.name }}</span>
                            <span class="block truncate font-mono text-[11px] text-muted-foreground">
                                {{ row.path }}
                            </span>
                        </span>
                    </span>
                </template>

                <template #cell-kind="{ row }">
                    <Badge variant="outline" class="font-normal capitalize">{{ row.kind }}</Badge>
                </template>

                <template #cell-folder="{ row }">
                    <span class="font-mono text-[11px] text-muted-foreground">{{ row.folder || '—' }}</span>
                </template>

                <template #cell-size="{ row }">
                    <span class="tabular-nums">{{ bytes(row.size) }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <RowActions :testid="`storage-actions-${slugOf(row.path)}`">
                        <DropdownMenuItem
                            :data-testid="`storage-copy-${slugOf(row.path)}`"
                            @select="copyUrl(row.url)"
                        >
                            <Copy />Salin Tautan
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <a
                                :href="row.url"
                                target="_blank"
                                rel="noopener"
                                class="relative flex cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none transition-colors focus:bg-accent data-[highlighted]:bg-accent [&>svg]:size-4"
                                :data-testid="`storage-open-${slugOf(row.path)}`"
                            >
                                <Download />Buka Berkas
                            </a>
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            :data-testid="`storage-rename-${slugOf(row.path)}`"
                            @select="openRename(row)"
                        >
                            <Pencil />Ganti Nama
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            class="text-destructive"
                            :data-testid="`storage-delete-${slugOf(row.path)}`"
                            @select="askDelete([row.path])"
                        >
                            <Trash2 />Hapus
                        </DropdownMenuItem>
                    </RowActions>
                </template>
            </DataTableCard>

            <Dialog v-model:open="uploadOpen" title="Unggah Berkas" class="max-w-lg">
                <div class="space-y-3">
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="storage-folder">Folder Tujuan</Label>
                        <Input
                            id="storage-folder"
                            v-model="uploadForm.folder"
                            placeholder="uploads"
                            data-testid="storage-upload-folder"
                        />
                        <p v-if="uploadForm.errors.folder" class="text-xs font-medium text-destructive">
                            {{ uploadForm.errors.folder }}
                        </p>
                    </div>
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="storage-files">Berkas (Maks. 10 Berkas, 50 MB Per Berkas)</Label>
                        <FileInput
                            id="storage-files"
                            ref="uploadInput"
                            multiple
                            data-testid="storage-upload-files"
                            @change="onPickFiles"
                        />
                        <p v-if="uploadForm.errors.files" class="text-xs font-medium text-destructive">
                            {{ uploadForm.errors.files }}
                        </p>
                        <UploadProgress
                            :progress="uploadForm.progress"
                            :label="`Mengunggah ${uploadForm.files.length} berkas`"
                            testid="storage-upload-progress"
                        />
                    </div>
                </div>
                <template #footer>
                    <Button variant="outline" size="sm" data-testid="storage-upload-cancel" @click="uploadOpen = false">
                        <X class="size-4" /> Batal
                    </Button>
                    <Button
                        size="sm"
                        :disabled="uploadForm.processing || !uploadForm.files.length"
                        data-testid="storage-upload-submit"
                        @click="submitUpload"
                    >
                        <Loader2 v-if="uploadForm.processing" class="size-4 animate-spin" />
                        <Upload v-else class="size-4" />
                        Unggah
                    </Button>
                </template>
            </Dialog>

            <Dialog v-model:open="renameOpen" title="Ganti Nama Berkas" class="max-w-lg">
                <div class="space-y-[var(--item-gap)]">
                    <Label for="storage-target">Nama Baru (Termasuk Folder)</Label>
                    <Input id="storage-target" v-model="renameForm.target" data-testid="storage-rename-input" />
                    <p
                        v-for="(message, field) in renameErrors"
                        :key="field"
                        class="text-xs font-medium text-destructive"
                        data-testid="storage-rename-error"
                    >
                        {{ message }}
                    </p>
                </div>
                <template #footer>
                    <Button variant="outline" size="sm" data-testid="storage-rename-cancel" @click="renameOpen = false">
                        <X class="size-4" /> Batal
                    </Button>
                    <Button
                        size="sm"
                        :disabled="renameForm.processing"
                        data-testid="storage-rename-submit"
                        @click="submitRename"
                    >
                        <Loader2 v-if="renameForm.processing" class="size-4 animate-spin" />
                        <Save v-else class="size-4" />
                        Simpan
                    </Button>
                </template>
            </Dialog>

            <ConfirmDeleteDialog
                v-model:open="confirmOpen"
                title="Hapus Berkas?"
                :description="`${selected.length} berkas akan dihapus permanen dari penyimpanan.`"
                @confirm="destroy"
            />
        </div>
    </AppLayout>
</template>
