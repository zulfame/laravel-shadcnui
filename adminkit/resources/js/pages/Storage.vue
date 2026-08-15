<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Info, PlugZap, Save } from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Alert from '@/components/ui/Alert.vue';
import AlertDescription from '@/components/ui/AlertDescription.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardFooter from '@/components/ui/CardFooter.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Combobox from '@/components/ui/Combobox.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Switch from '@/components/ui/Switch.vue';
import PasswordInput from '@/components/composite/PasswordInput.vue';
import { ACTION } from '@/constants/labels';

const props = defineProps({
    settings: { type: Object, required: true },
    driverOptions: { type: Array, default: () => [] },
});

const form = useForm({
    storage_driver: props.settings.storage_driver,
    s3_endpoint: props.settings.s3_endpoint,
    s3_key: props.settings.s3_key,
    s3_secret: '',
    s3_region: props.settings.s3_region,
    s3_bucket: props.settings.s3_bucket,
    s3_path_style: props.settings.s3_path_style,
    s3_public_url: props.settings.s3_public_url,
});

const testForm = useForm({});

const save = () => form.put('/storage-settings', { preserveScroll: true, onSuccess: () => form.reset('s3_secret') });
const test = () => testForm.post('/storage-settings/test', { preserveScroll: true });
</script>

<template>
    <Head title="Penyimpanan" />
    <AppLayout>
        <div class="space-y-6" data-testid="storage-page-view">
            <Card>
                <CardHeader>
                    <CardTitle>Object Storage (S3)</CardTitle>
                </CardHeader>
                <form class="form-dense" @submit.prevent="save">
                    <CardContent class="space-y-[var(--field-gap)]">
                        <div class="grid gap-[var(--field-gap)] sm:grid-cols-2">
                            <div class="space-y-[var(--item-gap)]">
                                <Label>Driver aktif</Label>
                                <Combobox
                                    v-model="form.storage_driver"
                                    :options="props.driverOptions"
                                    class="h-[var(--ctl-h)] text-[13px]"
                                    data-testid="storage-driver-select"
                                />
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="s3_bucket">Bucket</Label>
                                <Input id="s3_bucket" v-model="form.s3_bucket" data-testid="s3-bucket-input" />
                                <p v-if="form.errors.s3_bucket" class="text-xs font-medium text-destructive">
                                    {{ form.errors.s3_bucket }}
                                </p>
                            </div>
                            <div class="space-y-[var(--item-gap)] sm:col-span-2">
                                <Label for="s3_endpoint">Endpoint</Label>
                                <Input id="s3_endpoint" v-model="form.s3_endpoint" placeholder="https://" data-testid="s3-endpoint-input" />
                                <p v-if="form.errors.s3_endpoint" class="text-xs font-medium text-destructive">
                                    {{ form.errors.s3_endpoint }}
                                </p>
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="s3_key">Access Key ID</Label>
                                <Input id="s3_key" v-model="form.s3_key" class="font-mono" data-testid="s3-key-input" />
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="s3_secret">Secret Access Key</Label>
                                <PasswordInput
                                    id="s3_secret"
                                    v-model="form.s3_secret"
                                    :placeholder="props.settings.s3_secret_set ? 'Tersimpan — isi untuk mengganti' : 'Secret key'"
                                    testid="s3-secret-input"
                                />
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="s3_region">Region</Label>
                                <Input id="s3_region" v-model="form.s3_region" data-testid="s3-region-input" />
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="s3_public_url">URL Publik (opsional)</Label>
                                <Input id="s3_public_url" v-model="form.s3_public_url" placeholder="https://" data-testid="s3-public-url-input" />
                                <p v-if="form.errors.s3_public_url" class="text-xs font-medium text-destructive">
                                    {{ form.errors.s3_public_url }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4 rounded-lg border bg-muted/30 px-3 py-2">
                            <div>
                                <p class="text-[13px] font-medium">Path-style endpoint</p>
                                <p class="text-xs text-muted-foreground">
                                    Aktifkan untuk penyedia non-AWS (MinIO, Neo) yang memakai
                                    <span class="font-mono text-xs">endpoint/bucket</span>.
                                </p>
                            </div>
                            <Switch v-model="form.s3_path_style" data-testid="s3-path-style-toggle" />
                        </div>

                        <Alert>
                            <Info aria-hidden="true" />
                            <AlertDescription>
                                Secret key tidak pernah dikirim balik ke antarmuka. Biarkan kosong bila tidak ingin
                                menggantinya. Gunakan <span class="font-mono text-xs">Uji Koneksi</span> untuk
                                memastikan kredensial benar sebelum memindahkan driver ke S3.
                            </AlertDescription>
                        </Alert>
                    </CardContent>
                    <CardFooter class="justify-between">
                        <Button
                            variant="outline"
                            size="sm"
                            type="button"
                            :disabled="testForm.processing"
                            data-testid="storage-test"
                            @click="test"
                        >
                            <PlugZap class="size-4" /> Uji Koneksi
                        </Button>
                        <Button size="sm" type="submit" :disabled="form.processing" data-testid="storage-save">
                            <Save class="size-4" /> {{ form.processing ? ACTION.saving : ACTION.save }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
