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
import { all, max, path as pathRule, required, slug, url as urlRule } from '@/lib/validators';
import { useLiveValidation } from '@/composables/useLiveValidation';

const props = defineProps({
    settings: { type: Object, required: true },
    driverOptions: { type: Array, default: () => [] },
});

const form = useForm({
    storage_driver: props.settings.storage_driver,
    s3_endpoint: props.settings.s3_endpoint,
    s3_bucket: props.settings.s3_bucket,
    s3_path: props.settings.s3_path,
    s3_key: props.settings.s3_key,
    s3_secret: '',
    s3_region: props.settings.s3_region,
    s3_public_url: props.settings.s3_public_url,
    s3_path_style: props.settings.s3_path_style,
});

// Kolom S3 wajib hanya ketika driver aktif = s3.
const whenS3 = (validator) => (value, f) => (f.storage_driver === 's3' ? validator(value, f) : '');

const check = useLiveValidation(form, {
    storage_driver: required('driver aktif'),
    s3_endpoint: all(whenS3(required('endpoint')), urlRule('Endpoint'), max(200, 'Endpoint')),
    s3_bucket: all(whenS3(required('bucket')), slug('Bucket'), max(100, 'Bucket')),
    s3_path: all(pathRule('Path'), max(120, 'Path')),
    s3_key: all(whenS3(required('access key ID')), max(200, 'Access key ID')),
    s3_region: all(whenS3(required('region')), slug('Region'), max(50, 'Region')),
    s3_public_url: all(urlRule('URL publik'), max(200, 'URL publik')),
});

const save = () =>
    check.submit(() =>
        form.put('/storage-settings', { preserveScroll: true, onSuccess: () => form.reset('s3_secret') }),
    );

const testForm = useForm({});
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
                <form class="form-dense" novalidate @submit.prevent="save">
                    <CardContent class="space-y-[var(--field-gap)]">
                        <div class="grid gap-[var(--field-gap)] sm:grid-cols-2">
                            <div class="space-y-[var(--item-gap)]">
                                <Label>Driver Aktif</Label>
                                <Combobox
                                    v-model="form.storage_driver"
                                    :options="props.driverOptions"
                                    class="h-[var(--ctl-h)] text-[13px]"
                                    data-testid="storage-driver-select"
                                />
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="s3_endpoint">Endpoint</Label>
                                <Input
                                    id="s3_endpoint"
                                    v-model="form.s3_endpoint"
                                    placeholder="https://"
                                    maxlength="200"
                                    data-testid="s3-endpoint-input"
                                    @blur="check.validate('s3_endpoint')"
                                />
                                <p v-if="form.errors.s3_endpoint" class="text-xs font-medium text-destructive" data-testid="s3-endpoint-error">
                                    {{ form.errors.s3_endpoint }}
                                </p>
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="s3_bucket">Bucket</Label>
                                <Input
                                    id="s3_bucket"
                                    v-model="form.s3_bucket"
                                    maxlength="100"
                                    data-testid="s3-bucket-input"
                                    @blur="check.validate('s3_bucket')"
                                />
                                <p v-if="form.errors.s3_bucket" class="text-xs font-medium text-destructive" data-testid="s3-bucket-error">
                                    {{ form.errors.s3_bucket }}
                                </p>
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="s3_path">Path</Label>
                                <Input
                                    id="s3_path"
                                    v-model="form.s3_path"
                                    placeholder="(Opsional)"
                                    maxlength="120"
                                    data-testid="s3-path-input"
                                    @blur="check.validate('s3_path')"
                                />
                                <p v-if="form.errors.s3_path" class="text-xs font-medium text-destructive" data-testid="s3-path-error">
                                    {{ form.errors.s3_path }}
                                </p>
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="s3_key">Access Key ID</Label>
                                <Input
                                    id="s3_key"
                                    v-model="form.s3_key"
                                    class="font-mono"
                                    maxlength="200"
                                    data-testid="s3-key-input"
                                    @blur="check.validate('s3_key')"
                                />
                                <p v-if="form.errors.s3_key" class="text-xs font-medium text-destructive" data-testid="s3-key-error">
                                    {{ form.errors.s3_key }}
                                </p>
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="s3_secret">Secret Access Key</Label>
                                <PasswordInput
                                    id="s3_secret"
                                    v-model="form.s3_secret"
                                    :placeholder="props.settings.s3_secret_set ? 'Tersimpan — isi untuk mengganti' : 'Secret key'"
                                    testid="s3-secret-input"
                                />
                                <p v-if="form.errors.s3_secret" class="text-xs font-medium text-destructive">
                                    {{ form.errors.s3_secret }}
                                </p>
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="s3_region">Region</Label>
                                <Input
                                    id="s3_region"
                                    v-model="form.s3_region"
                                    maxlength="50"
                                    data-testid="s3-region-input"
                                    @blur="check.validate('s3_region')"
                                />
                                <p v-if="form.errors.s3_region" class="text-xs font-medium text-destructive" data-testid="s3-region-error">
                                    {{ form.errors.s3_region }}
                                </p>
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="s3_public_url">URL Publik</Label>
                                <Input
                                    id="s3_public_url"
                                    v-model="form.s3_public_url"
                                    placeholder="(Opsional)"
                                    maxlength="200"
                                    data-testid="s3-public-url-input"
                                    @blur="check.validate('s3_public_url')"
                                />
                                <p v-if="form.errors.s3_public_url" class="text-xs font-medium text-destructive" data-testid="s3-public-url-error">
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
