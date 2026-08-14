<script setup>
import { computed, ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { Save, Upload } from 'lucide-vue-next';

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

const profile = ref({
    name: user.value.name ?? '',
    email: user.value.email ?? '',
    phone: user.value.phone ?? '',
    office: user.value.office ?? '',
});

const password = ref({ current: '', next: '', confirm: '' });
</script>

<template>
    <Head title="Profil Pengguna" />
    <AppLayout>
        <div class="space-y-6" data-testid="profile-page">
            <Card>
                <CardHeader>
                    <CardTitle>Informasi Diri</CardTitle>
                </CardHeader>
                <form class="form-dense" @submit.prevent>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <Avatar
                                    :fallback="initialsOf(profile.name, profile.email)"
                                    class="size-11 rounded-lg text-sm"
                                />
                                <Button variant="outline" size="sm" type="button" data-testid="profile-upload-avatar">
                                    <Upload class="size-4" /> Unggah Foto
                                </Button>
                            </div>
                            <div class="flex flex-col items-end gap-1 text-right">
                                <span class="text-[13px] font-semibold" data-testid="profile-display-name">
                                    {{ profile.name }}
                                </span>
                                <Badge variant="secondary" class="font-normal" data-testid="profile-role">
                                    {{ user.role || 'Pengguna' }}
                                </Badge>
                            </div>
                        </div>

                        <Separator />

                        <div class="grid gap-[var(--field-gap)] sm:grid-cols-2">
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="p-name">Nama</Label>
                                <Input id="p-name" v-model="profile.name" data-testid="profile-name" />
                            </div>
                            <div class="space-y-[var(--item-gap)]">
                                <Label for="p-email">Email</Label>
                                <Input id="p-email" v-model="profile.email" data-testid="profile-email" />
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
                        <Button size="sm" type="submit" data-testid="profile-save">
                            <Save class="size-4" /> {{ ACTION.save }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Ubah Kata Sandi</CardTitle>
                </CardHeader>
                <form class="form-dense" @submit.prevent>
                    <CardContent class="grid gap-[var(--field-gap)] sm:grid-cols-2">
                        <div class="space-y-[var(--item-gap)]">
                            <Label for="pw-current">Kata Sandi Saat Ini</Label>
                            <PasswordInput id="pw-current" v-model="password.current" testid="profile-password-current" />
                        </div>
                        <div class="hidden sm:block" aria-hidden="true" />
                        <div class="space-y-[var(--item-gap)]">
                            <Label for="pw-new">Kata Sandi Baru</Label>
                            <PasswordInput
                                id="pw-new"
                                v-model="password.next"
                                placeholder="Minimal 6 karakter"
                                testid="profile-password-new"
                            />
                        </div>
                        <div class="space-y-[var(--item-gap)]">
                            <Label for="pw-confirm">Konfirmasi Kata Sandi</Label>
                            <PasswordInput id="pw-confirm" v-model="password.confirm" testid="profile-password-confirm" />
                        </div>
                    </CardContent>
                    <CardFooter class="justify-end">
                        <Button size="sm" type="submit" data-testid="profile-password-save">
                            <Save class="size-4" /> {{ ACTION.save }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
