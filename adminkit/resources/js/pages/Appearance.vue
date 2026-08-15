<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardFooter from '@/components/ui/CardFooter.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Switch from '@/components/ui/Switch.vue';
import Textarea from '@/components/ui/Textarea.vue';
import AssetUploader from '@/components/composite/AssetUploader.vue';
import BrandMark from '@/components/composite/BrandMark.vue';
import { ACTION } from '@/constants/labels';
import { all, email as emailRule, max, required, url as urlRule } from '@/lib/validators';
import { useLiveValidation } from '@/composables/useLiveValidation';

const props = defineProps({
    settings: { type: Object, required: true },
});

const s = props.settings;

const identity = useForm({
    app_name: s.app_name ?? '',
    tagline: s.tagline ?? '',
    brand_initials: s.brand_initials ?? '',
});

const identityCheck = useLiveValidation(identity, {
    app_name: all(required('nama aplikasi'), max(60, 'Nama Aplikasi')),
    tagline: max(100, 'Tagline'),
    brand_initials: max(4, 'Inisial Brand'),
});

const seo = useForm({
    meta_title: s.meta_title ?? '',
    meta_description: s.meta_description ?? '',
    meta_keywords: s.meta_keywords ?? '',
    canonical_url: s.canonical_url ?? '',
    search_indexable: Boolean(s.search_indexable),
});

const seoCheck = useLiveValidation(seo, {
    meta_title: max(120, 'Meta Title'),
    meta_description: max(300, 'Meta Description'),
    meta_keywords: max(200, 'Meta Keywords'),
    canonical_url: all(urlRule('Canonical URL'), max(200, 'Canonical URL')),
});

const contact = useForm({ support_email: s.support_email ?? '', footer_text: s.footer_text ?? '' });

const contactCheck = useLiveValidation(contact, {
    support_email: all(emailRule('Email Dukungan'), max(150, 'Email Dukungan')),
    footer_text: max(200, 'Teks Footer'),
});

const save = (form, check, section) =>
    check.submit(() => form.put(`/appearance/${section}`, { preserveScroll: true }));

const previewHost = computed(() => {
    try {
        return new URL(seo.canonical_url).host.toUpperCase();
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
                <form class="form-dense" novalidate @submit.prevent="save(identity, identityCheck, 'identity')">
                    <CardContent class="space-y-[var(--field-gap)]">
                        <div class="grid gap-[var(--field-gap)] lg:grid-cols-4">
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="app_name">Nama Aplikasi</Label>
                                <Input
                                    id="app_name"
                                    v-model="identity.app_name"
                                    maxlength="60"
                                    data-testid="app-name-input"
                                    @blur="identityCheck.validate('app_name')"
                                />
                                <p v-if="identity.errors.app_name" class="text-xs font-medium text-destructive" data-testid="app-name-error">
                                    {{ identity.errors.app_name }}
                                </p>
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="tagline">Tagline / Sub Judul</Label>
                                <Input
                                    id="tagline"
                                    v-model="identity.tagline"
                                    maxlength="100"
                                    data-testid="tagline-input"
                                    @blur="identityCheck.validate('tagline')"
                                />
                                <p v-if="identity.errors.tagline" class="text-xs font-medium text-destructive">
                                    {{ identity.errors.tagline }}
                                </p>
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="initials">Inisial Brand</Label>
                                <Input
                                    id="initials"
                                    v-model="identity.brand_initials"
                                    maxlength="4"
                                    data-testid="initials-input"
                                    @blur="identityCheck.validate('brand_initials')"
                                />
                                <p v-if="identity.errors.brand_initials" class="text-xs font-medium text-destructive">
                                    {{ identity.errors.brand_initials }}
                                </p>
                            </div>
                            <!-- Pratinjau identitas -->
                            <div class="flex items-center gap-2.5 rounded-lg border bg-muted/30 px-3 py-2" data-testid="brand-preview">
                                <BrandMark
                                    :logo="props.settings.logo_light_url"
                                    :initials="identity.brand_initials"
                                    class="size-9 shrink-0 bg-primary text-[11px] font-semibold text-primary-foreground"
                                />
                                <span class="min-w-0">
                                    <span class="block truncate text-[13px] font-semibold">{{ identity.app_name }}</span>
                                    <span class="block truncate text-xs text-muted-foreground">{{ identity.tagline }}</span>
                                </span>
                            </div>
                        </div>

                        <div class="grid gap-[var(--field-gap)] lg:grid-cols-3">
                            <AssetUploader
                                label="Logo (Latar Terang)"
                                hint="Dipakai pada latar terang. Maksimal 600 KB."
                                asset-key="logo_light"
                                :url="props.settings.logo_light_url"
                            />
                            <AssetUploader
                                label="Logo (Latar Gelap)"
                                hint="Dipakai pada latar gelap, mis. panel masuk."
                                asset-key="logo_dark"
                                :url="props.settings.logo_dark_url"
                                dark
                            />
                            <AssetUploader
                                label="Favicon"
                                hint="Ikon persegi (PNG/ICO/SVG), 32–512 px. Maksimal 256 KB."
                                asset-key="favicon"
                                accept="image/png,image/x-icon,image/svg+xml"
                                :url="props.settings.favicon_url"
                            />
                        </div>
                    </CardContent>
                    <CardFooter class="justify-end">
                        <Button size="sm" type="submit" :disabled="identity.processing" data-testid="identity-save">
                            <Save class="size-4" /> {{ identity.processing ? ACTION.saving : ACTION.save }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>

            <!-- SEO & Metadata (termasuk Open Graph) -->
            <Card>
                <CardHeader>
                    <CardTitle>SEO &amp; Metadata</CardTitle>
                </CardHeader>
                <form class="form-dense" novalidate @submit.prevent="save(seo, seoCheck, 'seo')">
                    <CardContent class="space-y-[var(--field-gap)]">
                        <div class="space-y-[var(--item-gap)]">
                            <Label for="meta_title">Meta Title</Label>
                            <Input
                                id="meta_title"
                                v-model="seo.meta_title"
                                maxlength="120"
                                data-testid="meta-title-input"
                                @blur="seoCheck.validate('meta_title')"
                            />
                            <p v-if="seo.errors.meta_title" class="text-xs font-medium text-destructive">
                                {{ seo.errors.meta_title }}
                            </p>
                        </div>
                        <div class="space-y-[var(--item-gap)]">
                            <Label for="meta_description">Meta Description</Label>
                            <Textarea
                                id="meta_description"
                                v-model="seo.meta_description"
                                maxlength="300"
                                data-testid="meta-description-input"
                                @blur="seoCheck.validate('meta_description')"
                            />
                            <p v-if="seo.errors.meta_description" class="text-xs font-medium text-destructive">
                                {{ seo.errors.meta_description }}
                            </p>
                        </div>
                        <div class="grid gap-[var(--field-gap)] sm:grid-cols-2">
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="meta_keywords">Meta Keywords</Label>
                                <Input
                                    id="meta_keywords"
                                    v-model="seo.meta_keywords"
                                    maxlength="200"
                                    data-testid="meta-keywords-input"
                                    @blur="seoCheck.validate('meta_keywords')"
                                />
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="canonical_url">Canonical URL</Label>
                                <Input
                                    id="canonical_url"
                                    v-model="seo.canonical_url"
                                    placeholder="https://"
                                    maxlength="200"
                                    data-testid="canonical-url-input"
                                    @blur="seoCheck.validate('canonical_url')"
                                />
                                <p v-if="seo.errors.canonical_url" class="text-xs font-medium text-destructive" data-testid="canonical-url-error">
                                    {{ seo.errors.canonical_url }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-[var(--field-gap)] sm:grid-cols-2">
                            <AssetUploader
                                label="OG Image"
                                hint="Gambar pratinjau tautan (disarankan 1200×630)."
                                asset-key="og_image"
                                :url="props.settings.og_image_url"
                            />
                            <div class="flex items-start justify-between gap-4 rounded-lg border bg-muted/30 px-3 py-2">
                                <div>
                                    <p class="text-[13px] font-medium">Visibilitas</p>
                                    <p class="text-xs text-muted-foreground">
                                        Bila nonaktif, halaman meminta mesin pencari untuk tidak mengindeks
                                        (noindex, nofollow).
                                    </p>
                                </div>
                                <Switch v-model="seo.search_indexable" data-testid="search-indexable-toggle" />
                            </div>
                        </div>

                        <!-- Kartu pratinjau tautan -->
                        <div class="w-full max-w-sm overflow-hidden rounded-lg border" data-testid="og-preview">
                            <div class="flex h-36 items-center justify-center bg-muted/50">
                                <img
                                    v-if="props.settings.og_image_url"
                                    :src="props.settings.og_image_url"
                                    alt=""
                                    class="size-full object-cover"
                                />
                                <span v-else class="text-xs text-muted-foreground">Belum ada gambar pratinjau</span>
                            </div>
                            <div class="space-y-1 px-3 py-2">
                                <p class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ previewHost }}</p>
                                <p class="text-[13px] font-semibold">
                                    {{ seo.meta_title || `${identity.app_name}: ${identity.tagline}` }}
                                </p>
                                <p class="line-clamp-2 text-xs text-muted-foreground">{{ seo.meta_description }}</p>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter class="justify-end">
                        <Button size="sm" type="submit" :disabled="seo.processing" data-testid="seo-save">
                            <Save class="size-4" /> {{ seo.processing ? ACTION.saving : ACTION.save }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>

            <!-- Kontak & Footer -->
            <Card>
                <CardHeader>
                    <CardTitle>Kontak &amp; Footer</CardTitle>
                </CardHeader>
                <form class="form-dense" novalidate @submit.prevent="save(contact, contactCheck, 'contact')">
                    <CardContent class="grid gap-[var(--field-gap)] sm:grid-cols-2">
                        <div class="space-y-[var(--item-gap)]">
                            <Label for="support_email">Email Dukungan</Label>
                            <Input
                                id="support_email"
                                v-model="contact.support_email"
                                type="email"
                                maxlength="150"
                                data-testid="support-email-input"
                                @blur="contactCheck.validate('support_email')"
                            />
                            <p v-if="contact.errors.support_email" class="text-xs font-medium text-destructive" data-testid="support-email-error">
                                {{ contact.errors.support_email }}
                            </p>
                        </div>
                        <div class="space-y-[var(--item-gap)]">
                            <Label for="footer_text">Teks Hak Cipta / Footer</Label>
                            <Input
                                id="footer_text"
                                v-model="contact.footer_text"
                                maxlength="200"
                                data-testid="footer-text-input"
                                @blur="contactCheck.validate('footer_text')"
                            />
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
