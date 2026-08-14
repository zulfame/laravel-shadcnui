<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { AlertCircle, Eye, EyeOff, Loader2, LogIn } from 'lucide-vue-next';

import AuthLayout from '@/components/layout/AuthLayout.vue';
import Alert from '@/components/ui/Alert.vue';
import AlertTitle from '@/components/ui/AlertTitle.vue';
import AlertDescription from '@/components/ui/AlertDescription.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardDescription from '@/components/ui/CardDescription.vue';
import CardFooter from '@/components/ui/CardFooter.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { ACTION } from '@/constants/labels';

const props = defineProps({ error: { type: String, default: '' } });

const form = useForm({ email: 'admin@adminkit.test', password: 'password', remember: true });
const showPassword = ref(false);

const submit = () => form.post('/login');
</script>

<template>
    <Head title="Masuk" />
    <AuthLayout>
        <Card>
            <CardHeader>
                <CardTitle class="text-xl">Masuk</CardTitle>
                <CardDescription>Gunakan kredensial akun Anda untuk melanjutkan.</CardDescription>
            </CardHeader>

            <form class="form-dense" novalidate @submit.prevent="submit">
                <CardContent class="space-y-[var(--field-gap)]">
                    <Alert v-if="props.error" variant="destructive" data-testid="login-error-alert">
                        <AlertCircle aria-hidden="true" />
                        <AlertTitle>Gagal masuk</AlertTitle>
                        <AlertDescription>{{ props.error }}</AlertDescription>
                    </Alert>

                    <div class="space-y-[var(--item-gap)]">
                        <Label for="email" class="text-sm">Kredensial</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="text"
                            autocomplete="username"
                            placeholder="Email atau nama pengguna"
                            data-testid="login-email-input"
                        />
                    </div>

                    <div class="space-y-[var(--item-gap)]">
                        <Label for="password" class="text-sm">Kata Sandi</Label>
                        <div class="relative">
                            <Input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                placeholder="Kata sandi"
                                class="pr-9"
                                data-testid="login-password-input"
                            />
                            <button
                                type="button"
                                class="absolute right-1 top-1/2 -translate-y-1/2 rounded-md p-1.5 text-muted-foreground transition-colors hover:text-foreground"
                                :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                data-testid="login-password-toggle"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="size-4" />
                                <Eye v-else class="size-4" />
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox id="remember" v-model="form.remember" data-testid="login-remember-checkbox" />
                        <Label for="remember" class="text-sm font-normal text-muted-foreground">
                            Ingat email saya
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
</template>
