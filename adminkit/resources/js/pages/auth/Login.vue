<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { AlertCircle, Loader2, LogIn } from 'lucide-vue-next';

import AuthLayout from '@/components/layout/AuthLayout.vue';
import Alert from '@/components/ui/Alert.vue';
import AlertDescription from '@/components/ui/AlertDescription.vue';
import AlertTitle from '@/components/ui/AlertTitle.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardFooter from '@/components/ui/CardFooter.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Toaster from '@/components/ui/Toaster.vue';
import PasswordInput from '@/components/composite/PasswordInput.vue';
import { useFlashToast } from '@/composables/useFlashToast';
import { required } from '@/lib/validators';
import { useLiveValidation } from '@/composables/useLiveValidation';
import { ACTION } from '@/constants/labels';

/**
 * Login — mengikuti design system: AuthLayout split-screen + Card
 * (header judul, isi form `.form-dense`, footer aksi).
 * Kredensial dapat berupa email, nama pengguna, atau nomor telepon.
 */
useFlashToast();

const form = useForm({ credential: '', password: '', remember: false });

const check = useLiveValidation(form, {
    credential: required('kredensial'),
    password: required('kata sandi'),
});

const submit = () =>
    check.submit(() =>
        form.post('/login', {
            onFinish: () => form.reset('password'),
        }),
    );
</script>

<template>
    <Head title="Masuk" />

    <AuthLayout>
        <Card>
            <CardHeader>
                <CardTitle>Autentikasi</CardTitle>
            </CardHeader>

            <form class="form-dense" novalidate @submit.prevent="submit">
                <CardContent class="space-y-[var(--field-gap)]">
                    <Alert v-if="form.errors.credential" variant="destructive" data-testid="login-form-error">
                        <AlertCircle aria-hidden="true" />
                        <AlertTitle>Gagal masuk</AlertTitle>
                        <AlertDescription>{{ form.errors.credential }}</AlertDescription>
                    </Alert>

                    <div class="space-y-[var(--item-gap)]">
                        <Label for="credential">Kredensial</Label>
                        <Input
                            id="credential"
                            v-model="form.credential"
                            type="text"
                            autocomplete="username"
                            placeholder="Email, Nama Pengguna atau Nomor HP"
                            data-testid="login-credential-input"
                            @blur="check.validate('credential')"
                        />
                        <p v-if="form.errors.credential && !form.errors.password" class="text-xs font-medium text-destructive" data-testid="login-credential-error">
                            {{ form.errors.credential }}
                        </p>
                    </div>

                    <div class="space-y-[var(--item-gap)]">
                        <Label for="password">Kata Sandi</Label>
                        <PasswordInput
                            id="password"
                            v-model="form.password"
                            placeholder="*****************************"
                            testid="login-password-input"
                        />
                        <p v-if="form.errors.password" class="text-xs font-medium text-destructive">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox id="remember" v-model="form.remember" data-testid="login-remember-checkbox" />
                        <Label for="remember" class="normal-case tracking-normal font-normal text-muted-foreground">
                            Ingat saya
                        </Label>
                    </div>
                </CardContent>

                <CardFooter>
                    <Button type="submit" class="w-full" :disabled="form.processing" data-testid="login-submit-button">
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                        <LogIn v-else class="size-4" aria-hidden="true" />
                        {{ form.processing ? 'Memproses...' : ACTION.login }}
                    </Button>
                </CardFooter>
            </form>
        </Card>
    </AuthLayout>

    <Toaster />
</template>
