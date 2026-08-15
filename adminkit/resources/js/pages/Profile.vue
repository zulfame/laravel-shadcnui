<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Loader2, Save, Trash2, Upload } from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Avatar from '@/components/ui/Avatar.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardFooter from '@/components/ui/CardFooter.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import PasswordInput from '@/components/composite/PasswordInput.vue';
import Separator from '@/components/ui/Separator.vue';
import { ACTION } from '@/constants/labels';
import { initialsOf } from '@/lib/utils';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? {});

const profile = useForm({
    name: user.value.name ?? '',
    username: user.value.username ?? '',
    email: user.value.email ?? '',
    phone: user.value.phone ?? '',
    office: user.value.office ?? '',
});

const password = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const saveProfile = () => profile.put('/profile', { preserveScroll: true });

const savePassword = () =>
    password.put('/profile/password', {
        preserveScroll: true,
        onSuccess: () => password.reset(),
    });

/* ── Foto profil ─────────────────────────────────────────────────────── */
const fileInput = ref(null);
const avatarForm = useForm({ avatar: null });

const pickAvatar = () => fileInput.value?.click();

const onAvatarChange = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    avatarForm.avatar = file;
    avatarForm.post('/profile/avatar', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            avatarForm.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};

const removeAvatar = () =>
    avatarForm.delete('/profile/avatar', { preserveScroll: true });
</script>

<template>
    <Head title="Profil" />
    <AppLayout>
        <div class="space-y-6" data-testid="profile-page">
            <Card>
                <CardHeader>
                    <CardTitle>Informasi Diri</CardTitle>
                </CardHeader>
                <form class="form-dense" @submit.prevent="saveProfile">
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <Avatar
                                    :src="user.avatar"
                                    :fallback="initialsOf(profile.name, profile.email)"
                                    class="size-11 rounded-lg text-sm"
                                    data-testid="profile-avatar"
                                />
                                <div class="flex flex-wrap items-center gap-2">
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        class="hidden"
                                        data-testid="profile-avatar-input"
                                        @change="onAvatarChange"
                                    />
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        type="button"
                                        :disabled="avatarForm.processing"
                                        data-testid="profile-upload-avatar"
                                        @click="pickAvatar"
                                    >
                                        <Loader2 v-if="avatarForm.processing" class="size-4 animate-spin" />
                                        <Upload v-else class="size-4" />
                                        Unggah Foto
                                    </Button>
                                    <Button
                                        v-if="user.has_avatar"
                                        variant="ghost"
                                        size="sm"
                                        type="button"
                                        class="text-destructive hover:text-destructive"
                                        data-testid="profile-remove-avatar"
                                        @click="removeAvatar"
                                    >
                                        <Trash2 class="size-4" /> {{ ACTION.delete }}
                                    </Button>
                                    <p class="w-full text-xs text-muted-foreground">
                                        JPG, PNG, atau WEBP. Maksimal 1 MB.
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1 text-right">
                                <span class="text-[13px] font-semibold" data-testid="profile-display-name">
                                    {{ user.name }}
                                </span>
                                <Badge variant="secondary" class="font-normal" data-testid="profile-role">
                                    {{ user.role || 'Pengguna' }}
                                </Badge>
                            </div>
                        </div>

                        <Separator />

                        <p v-if="avatarForm.errors.avatar" class="text-xs font-medium text-destructive" data-testid="avatar-error">
                            {{ avatarForm.errors.avatar }}
                        </p>

                        <div class="grid gap-[var(--field-gap)] sm:grid-cols-2">
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="p-name">Nama</Label>
                                <Input id="p-name" v-model="profile.name" data-testid="profile-name" />
                                <p v-if="profile.errors.name" class="text-xs font-medium text-destructive">
                                    {{ profile.errors.name }}
                                </p>
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="p-username">Nama Pengguna</Label>
                                <Input id="p-username" v-model="profile.username" data-testid="profile-username" />
                                <p v-if="profile.errors.username" class="text-xs font-medium text-destructive">
                                    {{ profile.errors.username }}
                                </p>
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="p-email">Email</Label>
                                <Input id="p-email" v-model="profile.email" data-testid="profile-email" />
                                <p v-if="profile.errors.email" class="text-xs font-medium text-destructive">
                                    {{ profile.errors.email }}
                                </p>
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="p-phone">Telepon</Label>
                                <Input id="p-phone" v-model="profile.phone" data-testid="profile-phone" />
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="p-office">Kantor</Label>
                                <Input id="p-office" v-model="profile.office" data-testid="profile-office" />
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter class="justify-end">
                        <Button size="sm" type="submit" :disabled="profile.processing" data-testid="profile-save">
                            <Save class="size-4" /> {{ profile.processing ? ACTION.saving : ACTION.save }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Ubah Kata Sandi</CardTitle>
                </CardHeader>
                <form class="form-dense" @submit.prevent="savePassword">
                    <CardContent class="grid gap-[var(--field-gap)] sm:grid-cols-2">
                        <div class="space-y-[var(--item-gap)]">
                            <Label for="pw-current">Kata Sandi Saat Ini</Label>
                            <PasswordInput
                                id="pw-current"
                                v-model="password.current_password"
                                testid="profile-password-current"
                            />
                            <p v-if="password.errors.current_password" class="text-xs font-medium text-destructive">
                                {{ password.errors.current_password }}
                            </p>
                        </div>
                        <div class="hidden sm:block" aria-hidden="true" />
                        <div class="space-y-[var(--item-gap)]">
                            <Label for="pw-new">Kata Sandi Baru</Label>
                            <PasswordInput
                                id="pw-new"
                                v-model="password.password"
                                placeholder="Minimal 8 karakter"
                                testid="profile-password-new"
                            />
                            <p v-if="password.errors.password" class="text-xs font-medium text-destructive">
                                {{ password.errors.password }}
                            </p>
                        </div>
                        <div class="space-y-[var(--item-gap)]">
                            <Label for="pw-confirm">Konfirmasi Kata Sandi</Label>
                            <PasswordInput
                                id="pw-confirm"
                                v-model="password.password_confirmation"
                                testid="profile-password-confirm"
                            />
                        </div>
                    </CardContent>
                    <CardFooter class="justify-end">
                        <Button
                            size="sm"
                            type="submit"
                            :disabled="password.processing"
                            data-testid="profile-password-save"
                        >
                            <Save class="size-4" /> {{ password.processing ? ACTION.saving : ACTION.save }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
