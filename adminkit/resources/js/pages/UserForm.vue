<script setup>
import { computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, X } from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardFooter from '@/components/ui/CardFooter.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Combobox from '@/components/ui/Combobox.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import PhoneInput from '@/components/ui/PhoneInput.vue';
import PasswordInput from '@/components/composite/PasswordInput.vue';
import { ACTION } from '@/constants/labels';
import { all, code, email as emailRule, max, min, personName, phone, required, username } from '@/lib/validators';
import { useLiveValidation } from '@/composables/useLiveValidation';

const props = defineProps({
    user: { type: Object, default: null },
    roleOptions: { type: Array, default: () => [] },
});

const editing = computed(() => Boolean(props.user));
const pageTitle = computed(() => `${editing.value ? ACTION.edit : ACTION.add} Pengguna`);

const form = useForm({
    name: props.user?.name ?? '',
    username: props.user?.username ?? '',
    email: props.user?.email ?? '',
    phone: props.user?.phone ?? '',
    role: props.user?.role ?? props.roleOptions[0]?.value ?? '',
    office: props.user?.office ?? '',
    alias: props.user?.alias ?? '',
    mso_code: props.user?.mso_code ?? '',
    collector_code: props.user?.collector_code ?? '',
    password: '',
});

const rules = {
    name: all(required('nama lengkap'), min(3, 'Nama Lengkap'), max(100, 'Nama Lengkap'), personName('Nama Lengkap')),
    username: all(min(3, 'Nama Pengguna'), max(50, 'Nama Pengguna'), username('Nama Pengguna')),
    email: all(emailRule('Alamat Email'), max(150, 'Alamat Email')),
    phone: phone('Nomor HP'),
    role: required('peranan'),
    office: max(100, 'Kantor'),
    alias: code(3, 'Alias'),
    mso_code: code(4, 'Kode MSO'),
    collector_code: code(3, 'Kode Kolektor'),
    password: (value) => {
        if (editing.value) return value ? min(8, 'Kata Sandi')(value) : '';

        return all(required('kata sandi'), min(8, 'Kata Sandi'))(value);
    },
};

const check = useLiveValidation(form, rules);

const submit = () => {
    if (!check.validateAll()) return;

    if (editing.value) form.put(`/users/${props.user.id}`);
    else form.post('/users');
};

const back = () => router.visit('/users');
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <form
            id="user-form"
            class="form-dense space-y-6"
            novalidate
            data-testid="user-form-page"
            @submit.prevent="submit"
        >
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0">
                    <CardTitle class="flex items-center gap-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            type="button"
                            data-testid="user-form-back"
                            @click="back"
                        >
                            <ArrowLeft class="size-4" />
                        </Button>
                        {{ pageTitle }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="grid gap-[var(--field-gap)] sm:grid-cols-2">
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="f-name">Nama Lengkap</Label>
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
                        <Label for="f-username">Nama Pengguna</Label>
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
                        <Label for="f-email">Alamat Email</Label>
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
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Penempatan &amp; Kode</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-[var(--field-gap)] sm:grid-cols-2">
                    <div class="space-y-[var(--item-gap)]">
                        <Label>Peranan</Label>
                        <Combobox
                            v-model="form.role"
                            :options="props.roleOptions"
                            placeholder="Pilih Peranan"
                            data-testid="user-form-role"
                        />
                        <p v-if="form.errors.role" class="text-xs font-medium text-destructive" data-testid="user-form-role-error">
                            {{ form.errors.role }}
                        </p>
                    </div>
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="f-office">Kantor</Label>
                        <Input
                            id="f-office"
                            v-model="form.office"
                            maxlength="100"
                            data-testid="user-form-office"
                            @blur="check.validate('office')"
                        />
                        <p v-if="form.errors.office" class="text-xs font-medium text-destructive" data-testid="user-form-office-error">
                            {{ form.errors.office }}
                        </p>
                    </div>
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="f-alias">Alias</Label>
                        <Input
                            id="f-alias"
                            v-model="form.alias"
                            maxlength="3"
                            class="uppercase"
                            placeholder="3 karakter"
                            data-testid="user-form-alias"
                            @blur="check.validate('alias')"
                        />
                        <p v-if="form.errors.alias" class="text-xs font-medium text-destructive" data-testid="user-form-alias-error">
                            {{ form.errors.alias }}
                        </p>
                    </div>
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="f-mso">Kode MSO</Label>
                        <Input
                            id="f-mso"
                            v-model="form.mso_code"
                            maxlength="4"
                            class="uppercase"
                            placeholder="4 karakter"
                            data-testid="user-form-mso-code"
                            @blur="check.validate('mso_code')"
                        />
                        <p v-if="form.errors.mso_code" class="text-xs font-medium text-destructive" data-testid="user-form-mso-code-error">
                            {{ form.errors.mso_code }}
                        </p>
                    </div>
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="f-collector">Kode Kolektor</Label>
                        <Input
                            id="f-collector"
                            v-model="form.collector_code"
                            maxlength="3"
                            class="uppercase"
                            placeholder="3 karakter"
                            data-testid="user-form-collector-code"
                            @blur="check.validate('collector_code')"
                        />
                        <p v-if="form.errors.collector_code" class="text-xs font-medium text-destructive" data-testid="user-form-collector-code-error">
                            {{ form.errors.collector_code }}
                        </p>
                    </div>
                    <div class="space-y-[var(--item-gap)]">
                        <Label for="f-last-login">Terakhir Login</Label>
                        <Input
                            id="f-last-login"
                            :model-value="props.user?.last_login_at ?? '—'"
                            readonly
                            disabled
                            data-testid="user-form-last-login"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Keamanan</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-[var(--item-gap)] sm:max-w-md">
                        <Label for="f-password">Kata Sandi</Label>
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
                </CardContent>
                <CardFooter class="justify-between">
                    <Button variant="outline" size="sm" type="button" data-testid="user-form-cancel" @click="back">
                        <X class="size-4" /> {{ ACTION.cancel }}
                    </Button>
                    <Button size="sm" type="submit" :disabled="form.processing" data-testid="user-form-save">
                        <Save class="size-4" /> {{ form.processing ? ACTION.saving : ACTION.save }}
                    </Button>
                </CardFooter>
            </Card>
        </form>
    </AppLayout>
</template>
