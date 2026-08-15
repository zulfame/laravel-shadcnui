<script setup>
import { ref } from 'vue';
import {
    BellRing,
    Copy,
    Download,
    Info,
    Loader2,
    Pencil,
    Save,
    Trash2,
    TriangleAlert,
} from 'lucide-vue-next';

import Alert from '@/components/ui/Alert.vue';
import AlertDescription from '@/components/ui/AlertDescription.vue';
import AlertTitle from '@/components/ui/AlertTitle.vue';
import Avatar from '@/components/ui/Avatar.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Combobox from '@/components/ui/Combobox.vue';
import DatePicker from '@/components/ui/DatePicker.vue';
import Dialog from '@/components/ui/Dialog.vue';
import DropdownMenu from '@/components/ui/DropdownMenu.vue';
import DropdownMenuContent from '@/components/ui/DropdownMenuContent.vue';
import DropdownMenuItem from '@/components/ui/DropdownMenuItem.vue';
import DropdownMenuLabel from '@/components/ui/DropdownMenuLabel.vue';
import DropdownMenuSeparator from '@/components/ui/DropdownMenuSeparator.vue';
import DropdownMenuTrigger from '@/components/ui/DropdownMenuTrigger.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import PhoneInput from '@/components/ui/PhoneInput.vue';
import Progress from '@/components/ui/Progress.vue';
import Separator from '@/components/ui/Separator.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import Switch from '@/components/ui/Switch.vue';
import Table from '@/components/ui/Table.vue';
import TableBody from '@/components/ui/TableBody.vue';
import TableCell from '@/components/ui/TableCell.vue';
import TableHead from '@/components/ui/TableHead.vue';
import TableHeader from '@/components/ui/TableHeader.vue';
import TableRow from '@/components/ui/TableRow.vue';
import Textarea from '@/components/ui/Textarea.vue';
import Tooltip from '@/components/ui/Tooltip.vue';
import TooltipContent from '@/components/ui/TooltipContent.vue';
import TooltipTrigger from '@/components/ui/TooltipTrigger.vue';
import ConfirmDeleteDialog from '@/components/composite/ConfirmDeleteDialog.vue';
import EmptyState from '@/components/composite/EmptyState.vue';
import PasswordInput from '@/components/composite/PasswordInput.vue';
import RowActions from '@/components/composite/RowActions.vue';
import StateChip from '@/components/composite/StateChip.vue';
import { notify } from '@/composables/useToast';

const CHIPS = [
    { label: 'Selesai', chip: '--st-done' },
    { label: 'Berjalan', chip: '--st-progress' },
    { label: 'Menunggu', chip: '--st-pending' },
    { label: 'Draf', chip: '--st-draft' },
    { label: 'Dibatalkan', chip: '--st-cancelled' },
    { label: 'Terlambat', chip: '--st-overdue' },
];

const EMPTY_VARIANTS = [
    { value: 'no-data', label: 'Belum Ada Data' },
    { value: 'no-results', label: 'Tidak Ada Hasil' },
    { value: 'first-time', label: 'Mulai Dari Sini' },
    { value: 'forbidden', label: 'Akses Ditolak' },
    { value: 'offline', label: 'Sedang Offline' },
    { value: 'error', label: 'Terjadi Kesalahan' },
];

const ROWS = [
    { id: 1, name: 'Rani Kusuma', role: 'Editor', chip: '--st-done', label: 'Aktif' },
    { id: 2, name: 'Budi Santoso', role: 'Auditor', chip: '--st-pending', label: 'Menunggu' },
    { id: 3, name: 'Sinta Larasati', role: 'Staf', chip: '--st-cancelled', label: 'Nonaktif' },
];

const text = ref('AdminKit');
const password = ref('rahasia123');
const phone = ref('081234567890');
const note = ref('Catatan singkat untuk contoh Textarea.');
const checked = ref(true);
const toggled = ref(true);
const pick = ref('editor');
const date = ref('');
const progress = ref(62);
const loading = ref(false);
const emptyVariant = ref('no-results');
const dialogOpen = ref(false);
const confirmOpen = ref(false);

const runLoading = () => {
    loading.value = true;
    setTimeout(() => (loading.value = false), 1600);
};
</script>

<template>
    <div class="space-y-4" data-testid="component-gallery">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="text-base font-semibold tracking-tight md:text-lg">Galeri Komponen</h2>
            <p class="text-xs text-muted-foreground">
                Semua komponen yang tersedia di starterkit ini — dapat dicoba langsung.
            </p>
        </div>

        <div class="grid items-start gap-4 lg:grid-cols-2 xl:grid-cols-3">
            <!-- Tombol -->
            <Card data-testid="gallery-buttons">
                <CardHeader><CardTitle>Tombol</CardTitle></CardHeader>
                <CardContent class="space-y-3">
                    <div class="flex flex-wrap gap-2">
                        <Button size="sm"><Save class="size-4" /> Simpan</Button>
                        <Button size="sm" variant="secondary">Sekunder</Button>
                        <Button size="sm" variant="outline"><Download class="size-4" /> Ekspor</Button>
                        <Button size="sm" variant="ghost">Ghost</Button>
                        <Button size="sm" variant="destructive"><Trash2 class="size-4" /> Hapus</Button>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Button size="icon" variant="outline" aria-label="Salin"><Copy class="size-4" /></Button>
                        <Button size="sm" :disabled="loading" data-testid="gallery-loading-btn" @click="runLoading">
                            <Loader2 v-if="loading" class="size-4 animate-spin" />
                            <BellRing v-else class="size-4" />
                            {{ loading ? 'Memproses…' : 'Coba Status Muat' }}
                        </Button>
                        <Button size="sm" variant="outline" disabled>Nonaktif</Button>
                    </div>
                    <Separator />
                    <div class="flex flex-wrap gap-2">
                        <Button size="sm" variant="outline" @click="notify.success('Data berhasil disimpan.')">
                            Toast Sukses
                        </Button>
                        <Button size="sm" variant="outline" @click="notify.error('Data gagal disimpan.')">
                            Toast Gagal
                        </Button>
                        <Button size="sm" variant="outline" @click="notify.warning('Periksa kembali isian Anda.')">
                            Toast Peringatan
                        </Button>
                        <Button size="sm" variant="outline" @click="notify.info('Sinkronisasi berjalan.')">
                            Toast Info
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Lencana & Status -->
            <Card data-testid="gallery-badges">
                <CardHeader><CardTitle>Lencana & Status</CardTitle></CardHeader>
                <CardContent class="space-y-3">
                    <div class="flex flex-wrap gap-2">
                        <Badge>Utama</Badge>
                        <Badge variant="secondary">Sekunder</Badge>
                        <Badge variant="outline">Outline</Badge>
                        <Badge variant="destructive">Destruktif</Badge>
                    </div>
                    <Separator />
                    <div class="flex flex-wrap gap-2">
                        <StateChip v-for="chip in CHIPS" :key="chip.chip" :label="chip.label" :chip="chip.chip" />
                    </div>
                    <Separator />
                    <div class="flex items-center gap-2">
                        <Avatar fallback="ZR" class="size-8" />
                        <Avatar fallback="JK" class="size-8" />
                        <Avatar fallback="AD" class="size-8" />
                        <span class="text-xs text-muted-foreground">Avatar dengan inisial</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button size="sm" variant="outline">Arahkan Kursor</Button>
                            </TooltipTrigger>
                            <TooltipContent side="top">Ini contoh Tooltip</TooltipContent>
                        </Tooltip>
                        <span class="text-xs text-muted-foreground">Tooltip</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Formulir -->
            <Card data-testid="gallery-forms">
                <CardHeader><CardTitle>Isian Formulir</CardTitle></CardHeader>
                <CardContent class="space-y-3">
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="gallery-text">Input Teks</Label>
                        <Input id="gallery-text" v-model="text" placeholder="Nama lengkap" />
                    </div>
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="gallery-invalid">Input Bermasalah</Label>
                        <Input id="gallery-invalid" model-value="" placeholder="Wajib diisi" aria-invalid="true" />
                        <p class="text-xs font-medium text-destructive">Kolom ini wajib diisi.</p>
                    </div>
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="gallery-password">Kata Sandi</Label>
                        <PasswordInput id="gallery-password" v-model="password" />
                    </div>
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="gallery-phone">Nomor HP</Label>
                        <PhoneInput id="gallery-phone" v-model="phone" />
                    </div>
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="gallery-note">Catatan</Label>
                        <Textarea id="gallery-note" v-model="note" rows="2" />
                    </div>
                </CardContent>
            </Card>

            <!-- Pilihan -->
            <Card data-testid="gallery-pickers">
                <CardHeader><CardTitle>Pemilih & Sakelar</CardTitle></CardHeader>
                <CardContent class="space-y-3">
                    <div class="space-y-[var(--item-gap)]">
                        <Label>Combobox (dengan pencarian)</Label>
                        <Combobox
                            v-model="pick"
                            :options="[
                                { value: 'editor', label: 'Editor' },
                                { value: 'auditor', label: 'Auditor' },
                                { value: 'staf', label: 'Staf' },
                                { value: 'admin', label: 'Administrator' },
                            ]"
                        />
                    </div>
                    <div class="space-y-[var(--item-gap)]">
                        <Label>Pemilih Tanggal</Label>
                        <DatePicker v-model="date" />
                    </div>
                    <Separator />
                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                        <Checkbox v-model="checked" /> Kirim ringkasan mingguan
                    </label>
                    <div class="flex items-center justify-between text-sm">
                        <span>Mode ringkas tabel</span>
                        <Switch v-model="toggled" />
                    </div>
                </CardContent>
            </Card>

            <!-- Umpan balik -->
            <Card data-testid="gallery-feedback">
                <CardHeader><CardTitle>Umpan Balik & Muat</CardTitle></CardHeader>
                <CardContent class="space-y-3">
                    <Alert>
                        <Info />
                        <AlertTitle>Informasi</AlertTitle>
                        <AlertDescription>Pengaturan disimpan otomatis setiap perubahan.</AlertDescription>
                    </Alert>
                    <Alert variant="destructive">
                        <TriangleAlert />
                        <AlertTitle>Perlu Perhatian</AlertTitle>
                        <AlertDescription>Kredensial object storage belum lengkap.</AlertDescription>
                    </Alert>
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Progress</span>
                            <span class="tabular-nums">{{ progress }}%</span>
                        </div>
                        <Progress :value="progress" class="h-1.5" />
                        <div class="flex gap-2 pt-1">
                            <Button size="sm" variant="outline" @click="progress = Math.max(0, progress - 10)">−10</Button>
                            <Button size="sm" variant="outline" @click="progress = Math.min(100, progress + 10)">+10</Button>
                        </div>
                    </div>
                    <Separator />
                    <div class="space-y-2">
                        <p class="text-xs text-muted-foreground">Skeleton (keadaan memuat)</p>
                        <div class="flex items-center gap-3">
                            <Skeleton class="size-8 rounded-full" />
                            <div class="flex-1 space-y-1.5">
                                <Skeleton class="h-3 w-2/3" />
                                <Skeleton class="h-3 w-1/3" />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Overlay -->
            <Card data-testid="gallery-overlays">
                <CardHeader><CardTitle>Dialog & Menu</CardTitle></CardHeader>
                <CardContent class="space-y-3">
                    <div class="flex flex-wrap gap-2">
                        <Button size="sm" data-testid="gallery-open-dialog" @click="dialogOpen = true">
                            Buka Dialog
                        </Button>
                        <Button
                            size="sm"
                            variant="destructive"
                            data-testid="gallery-open-confirm"
                            @click="confirmOpen = true"
                        >
                            Dialog Konfirmasi
                        </Button>
                    </div>
                    <Separator />
                    <div class="flex flex-wrap items-center gap-3">
                        <DropdownMenu>
                            <DropdownMenuTrigger>
                                <Button size="sm" variant="outline" data-testid="gallery-dropdown">Menu Dropdown</Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start" class="w-44">
                                <DropdownMenuLabel>Tindakan</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem><Pencil class="size-4" /> Ubah</DropdownMenuItem>
                                <DropdownMenuItem><Copy class="size-4" /> Duplikat</DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem class="text-destructive">
                                    <Trash2 class="size-4" /> Hapus
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        <span class="text-xs text-muted-foreground">Dropdown menu</span>
                    </div>

                    <Dialog v-model:open="dialogOpen" title="Contoh Dialog" class="max-w-md">
                        <p class="text-sm text-muted-foreground">
                            Judul berada di header, penjelasan di body, dan aksi di footer — persis pola yang dipakai
                            seluruh modul.
                        </p>
                        <template #footer>
                            <Button variant="outline" size="sm" @click="dialogOpen = false">Batal</Button>
                            <Button
                                size="sm"
                                @click="
                                    dialogOpen = false;
                                    notify.success('Contoh dialog dikonfirmasi.');
                                "
                            >
                                <Save class="size-4" /> Simpan
                            </Button>
                        </template>
                    </Dialog>

                    <ConfirmDeleteDialog
                        v-model:open="confirmOpen"
                        title="Hapus Contoh Data?"
                        description="Tindakan ini hanya contoh — tidak ada data yang benar-benar dihapus."
                        @confirm="
                            confirmOpen = false;
                            notify.success('Contoh data dihapus.');
                        "
                    />
                </CardContent>
            </Card>

            <!-- Tabel -->
            <Card class="lg:col-span-2" data-testid="gallery-table">
                <CardHeader><CardTitle>Tabel & Aksi Baris</CardTitle></CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-10"><Checkbox :model-value="false" /></TableHead>
                                <TableHead>Pengguna</TableHead>
                                <TableHead>Peranan</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="w-12 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in ROWS" :key="row.id">
                                <TableCell><Checkbox :model-value="row.id === 1" /></TableCell>
                                <TableCell>
                                    <span class="flex items-center gap-2">
                                        <Avatar :fallback="row.name.slice(0, 2).toUpperCase()" class="size-7" />
                                        <span class="text-[13px] font-medium">{{ row.name }}</span>
                                    </span>
                                </TableCell>
                                <TableCell class="text-muted-foreground">{{ row.role }}</TableCell>
                                <TableCell><StateChip :label="row.label" :chip="row.chip" /></TableCell>
                                <TableCell class="text-right">
                                    <RowActions :testid="`gallery-row-actions-${row.id}`">
                                        <DropdownMenuItem><Pencil class="size-4" /> Ubah</DropdownMenuItem>
                                        <DropdownMenuItem class="text-destructive">
                                            <Trash2 class="size-4" /> Hapus
                                        </DropdownMenuItem>
                                    </RowActions>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Kondisi kosong -->
            <Card data-testid="gallery-empty">
                <CardHeader class="flex flex-col gap-3 space-y-0 sm:flex-row sm:items-center sm:justify-between">
                    <CardTitle>Kondisi Kosong</CardTitle>
                    <Combobox v-model="emptyVariant" :options="EMPTY_VARIANTS" class="sm:w-48" />
                </CardHeader>
                <CardContent class="p-0">
                    <EmptyState :variant="emptyVariant" />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
