<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Info, Save } from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Alert from '@/components/ui/Alert.vue';
import AlertDescription from '@/components/ui/AlertDescription.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardFooter from '@/components/ui/CardFooter.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Combobox from '@/components/ui/Combobox.vue';
import Switch from '@/components/ui/Switch.vue';
import Textarea from '@/components/ui/Textarea.vue';
import AssetUploader from '@/components/composite/AssetUploader.vue';
import { ACTION } from '@/constants/labels';

const props = defineProps({
    settings: { type: Object, required: true },
    timezones: { type: Array, default: () => [] },
    languages: { type: Array, default: () => [] },
    dateFormats: { type: Array, default: () => [] },
});

const s = props.settings;

const identity = useForm({
    app_name: s.app_name ?? '',
    tagline: s.tagline ?? '',
    brand_initials: s.brand_initials ?? '',
    company: s.company ?? '',
    timezone: s.timezone ?? 'Asia/Jakarta',
    language: s.language ?? 'id',
    date_format: s.date_format ?? 'DD/MM/YYYY',
    app_url: s.app_url ?? '',
});

const brand = useForm({ brand_color: s.brand_color ?? '#0F0F0F' });

const seo = useForm({
    meta_description: s.meta_description ?? '',
    meta_keywords: s.meta_keywords ?? '',
    canonical_url: s.canonical_url ?? '',
    search_indexable: Boolean(s.search_indexable),
});

const og = useForm({ og_title: s.og_title ?? '', og_description: s.og_description ?? '' });

const contact = useForm({ support_email: s.support_email ?? '', footer_text: s.footer_text ?? '' });

const save = (form, section) => form.put(`/appearance/${section}`, { preserveScroll: true });

const previewInitials = computed(
    () => (identity.brand_initials || identity.app_name || 'AK').slice(0, 3).toUpperCase(),
);
const previewHost = computed(() => {
    try {
        return new URL(seo.canonical_url || identity.app_url).host.toUpperCase();
    } catch (e) {
        return (identity.app_name || 'ADMINKIT').toUpperCase();
    }
});
</script>

<template>
    <Head title="Penampilan" />
    <AppLayout>
        <div class="space-y-6" data-testid="appearance-page-view">
            <!-- Identitas Aplikasi -->
            <Card>
                <CardHeader>
                    <CardTitle>Identitas Aplikasi</CardTitle>
                </CardHeader>
                <form class="form-dense" @submit.prevent="save(identity, 'identity')">
                    <CardContent class="space-y-[var(--field-gap)]">
                        <div class="grid gap-[var(--field-gap)] lg:grid-cols-4">
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="app_name">Nama Aplikasi</Label>
                                <Input id="app_name" v-model="identity.app_name" data-testid="app-name-input" />
                                <p v-if="identity.errors.app_name" class="text-xs font-medium text-destructive">
                                    {{ identity.errors.app_name }}
                                </p>
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="tagline">Tagline / Sub Judul</Label>
                                <Input id="tagline" v-model="identity.tagline" data-testid="tagline-input" />
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="initials">Inisial Brand</Label>
                                <Input id="initials" v-model="identity.brand_initials" maxlength="4" data-testid="initials-input" />
                            </div>
                            <!-- Pratinjau identitas -->
                            <div class="flex items-center gap-2.5 rounded-lg border bg-muted/30 px-3 py-2" data-testid="brand-preview">
                                <span
                                    class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-md text-[11px] font-semibold text-primary-foreground"
                                    :style="{ backgroundColor: brand.brand_color }"
                                >
                                    <img v-if="props.settings.logo_light_url" :src="props.settings.logo_light_url" alt="" class="size-full object-contain" />
                                    <template v-else>{{ previewInitials }}</template>
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-[13px] font-semibold">{{ identity.app_name }}</span>
                                    <span class="block truncate text-xs text-muted-foreground">{{ identity.tagline }}</span>
                                </span>
                            </div>
                        </div>

                        <div class="grid gap-[var(--field-gap)] lg:grid-cols-4">
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="company">Perusahaan</Label>
                                <Input id="company" v-model="identity.company" data-testid="company-input" />
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label>Zona Waktu</Label>
                                <Combobox
                                    v-model="identity.timezone"
                                    :options="props.timezones"
                                    class="h-[var(--ctl-h)] text-[13px]"
                                    data-testid="timezone-select"
                                />
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label>Bahasa</Label>
                                <Combobox
                                    v-model="identity.language"
                                    :options="props.languages"
                                    class="h-[var(--ctl-h)] text-[13px]"
                                    data-testid="language-select"
                                />
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label>Format Tanggal</Label>
                                <Combobox
                                    v-model="identity.date_format"
                                    :options="props.dateFormats"
                                    class="h-[var(--ctl-h)] text-[13px]"
                                    data-testid="date-format-select"
                                />
                            </div>
                        </div>

                        <div class="grid gap-[var(--field-gap)] lg:grid-cols-4">
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="app_url">URL Aplikasi</Label>
                                <Input id="app_url" v-model="identity.app_url" placeholder="https://" data-testid="app-url-input" />
                                <p v-if="identity.errors.app_url" class="text-xs font-medium text-destructive">
                                    {{ identity.errors.app_url }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter class="justify-end">
                        <Button size="sm" type="submit" :disabled="identity.processing" data-testid="identity-save">
                            <Save class="size-4" /> {{ identity.processing ? ACTION.saving : ACTION.save }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>

            <!-- Aset Merek -->
            <Card>
                <CardHeader>
                    <CardTitle>Aset Merek</CardTitle>
                </CardHeader>
                <form class="form-dense" @submit.prevent="save(brand, 'brand')">
                    <CardContent class="space-y-[var(--field-gap)]">
                        <div class="grid gap-[var(--field-gap)] lg:grid-cols-3">
                            <AssetUploader
                                label="Logo (latar terang)"
                                hint="Dipakai pada latar terang. Maks 600 KB."
                                asset-key="logo_light"
                                :url="props.settings.logo_light_url"
                            />
                            <AssetUploader
                                label="Logo (latar gelap)"
                                hint="Dipakai pada latar gelap, mis. panel masuk."
                                asset-key="logo_dark"
                                :url="props.settings.logo_dark_url"
                                dark
                            />
                            <AssetUploader
                                label="Favicon"
                                hint="Ikon persegi (PNG/ICO/SVG), 32–512 px. Maks 256 KB."
                                asset-key="favicon"
                                accept="image/png,image/x-icon,image/svg+xml"
                                :url="props.settings.favicon_url"
                            />
                            <AssetUploader
                                label="Thumbnail"
                                hint="Cadangan gambar pratinjau bila OG Image kosong."
                                asset-key="thumbnail"
                                :url="props.settings.thumbnail_url"
                            />
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="brand_color">Warna Merek</Label>
                                <div class="flex items-center gap-2">
                                    <input
                                        id="brand_color_picker"
                                        v-model="brand.brand_color"
                                        type="color"
                                        class="h-[var(--ctl-h)] w-10 cursor-pointer rounded-md border border-input bg-transparent p-1"
                                        data-testid="brand-color-picker"
                                    />
                                    <Input
                                        id="brand_color"
                                        v-model="brand.brand_color"
                                        class="max-w-[9rem] font-mono"
                                        data-testid="brand-color-input"
                                    />
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Antarmuka tetap monokrom; warna ini hanya identitas merek.
                                </p>
                                <p v-if="brand.errors.brand_color" class="text-xs font-medium text-destructive">
                                    {{ brand.errors.brand_color }}
                                </p>
                            </div>
                        </div>

                        <Alert>
                            <Info aria-hidden="true" />
                            <AlertDescription>
                                Nilai teks disimpan di tabel <span class="font-mono text-xs">settings</span>, berkas
                                aset di disk <span class="font-mono text-xs">public</span> — keduanya ikut terbawa
                                saat pencadangan.
                            </AlertDescription>
                        </Alert>
                    </CardContent>
                    <CardFooter class="justify-end">
                        <Button size="sm" type="submit" :disabled="brand.processing" data-testid="brand-save">
                            <Save class="size-4" /> {{ brand.processing ? ACTION.saving : ACTION.save }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>

            <!-- SEO & Metadata -->
            <Card>
                <CardHeader>
                    <CardTitle>SEO &amp; Metadata</CardTitle>
                </CardHeader>
                <form class="form-dense" @submit.prevent="save(seo, 'seo')">
                    <CardContent class="space-y-[var(--field-gap)]">
                        <div class="space-y-[var(--item-gap)]">
                            <Label for="meta_description">Meta Description</Label>
                            <Textarea id="meta_description" v-model="seo.meta_description" data-testid="meta-description-input" />
                        </div>
                        <div class="grid gap-[var(--field-gap)] sm:grid-cols-2">
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="meta_keywords">Meta Keywords</Label>
                                <Input id="meta_keywords" v-model="seo.meta_keywords" data-testid="meta-keywords-input" />
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="canonical_url">Canonical URL</Label>
                                <Input id="canonical_url" v-model="seo.canonical_url" placeholder="https://" data-testid="canonical-url-input" />
                                <p v-if="seo.errors.canonical_url" class="text-xs font-medium text-destructive">
                                    {{ seo.errors.canonical_url }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-4 rounded-lg border bg-muted/30 px-3 py-2">
                            <div>
                                <p class="text-[13px] font-medium">Terlihat di mesin pencari</p>
                                <p class="text-xs text-muted-foreground">
                                    Bila nonaktif, halaman meminta mesin pencari untuk tidak mengindeks
                                    (noindex, nofollow). Disarankan tetap nonaktif untuk konsol internal.
                                </p>
                            </div>
                            <Switch v-model="seo.search_indexable" data-testid="search-indexable-toggle" />
                        </div>
                    </CardContent>
                    <CardFooter class="justify-end">
                        <Button size="sm" type="submit" :disabled="seo.processing" data-testid="seo-save">
                            <Save class="size-4" /> {{ seo.processing ? ACTION.saving : ACTION.save }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>

            <!-- Pratinjau Tautan (Open Graph) -->
            <Card>
                <CardHeader>
                    <CardTitle>Pratinjau Tautan (Open Graph)</CardTitle>
                </CardHeader>
                <form class="form-dense" @submit.prevent="save(og, 'og')">
                    <CardContent class="space-y-[var(--field-gap)]">
                        <div class="grid gap-[var(--field-gap)] sm:grid-cols-2">
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="og_title">OG Title</Label>
                                <Input id="og_title" v-model="og.og_title" data-testid="og-title-input" />
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="og_description">OG Description</Label>
                                <Input id="og_description" v-model="og.og_description" data-testid="og-description-input" />
                            </div>
                        </div>

                        <AssetUploader
                            label="OG Image"
                            hint="Gambar pratinjau tautan (disarankan 1200×630)."
                            asset-key="og_image"
                            :url="props.settings.og_image_url"
                        />

                        <!-- Kartu pratinjau -->
                        <div class="w-full max-w-sm overflow-hidden rounded-lg border" data-testid="og-preview">
                            <div class="flex h-36 items-center justify-center bg-muted/50">
                                <img
                                    v-if="props.settings.og_image_url || props.settings.thumbnail_url"
                                    :src="props.settings.og_image_url || props.settings.thumbnail_url"
                                    alt=""
                                    class="size-full object-cover"
                                />
                                <span v-else class="text-xs text-muted-foreground">Belum ada gambar pratinjau</span>
                            </div>
                            <div class="space-y-1 px-3 py-2">
                                <p class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ previewHost }}</p>
                                <p class="text-[13px] font-semibold">
                                    {{ og.og_title || `${identity.app_name}: ${identity.tagline}` }}
                                </p>
                                <p class="line-clamp-2 text-xs text-muted-foreground">
                                    {{ og.og_description || seo.meta_description }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter class="justify-end">
                        <Button size="sm" type="submit" :disabled="og.processing" data-testid="og-save">
                            <Save class="size-4" /> {{ og.processing ? ACTION.saving : ACTION.save }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>

            <!-- Kontak & Footer -->
            <Card>
                <CardHeader>
                    <CardTitle>Kontak &amp; Footer</CardTitle>
                </CardHeader>
                <form class="form-dense" @submit.prevent="save(contact, 'contact')">
                    <CardContent class="grid gap-[var(--field-gap)] sm:grid-cols-2">
                        <div class="space-y-[var(--item-gap)]">
                            <Label for="support_email">Email Dukungan</Label>
                            <Input id="support_email" v-model="contact.support_email" data-testid="support-email-input" />
                            <p v-if="contact.errors.support_email" class="text-xs font-medium text-destructive">
                                {{ contact.errors.support_email }}
                            </p>
                        </div>
                        <div class="space-y-[var(--item-gap)]">
                            <Label for="footer_text">Teks Hak Cipta / Footer</Label>
                            <Input id="footer_text" v-model="contact.footer_text" data-testid="footer-text-input" />
                        </div>
                    </CardContent>
                    <CardFooter class="justify-end">
                        <Button size="sm" type="submit" :disabled="contact.processing" data-testid="contact-save">
                            <Save class="size-4" /> {{ contact.processing ? ACTION.saving : ACTION.save }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
