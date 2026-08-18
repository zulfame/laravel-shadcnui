<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, GripVertical, Lock, Save } from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardFooter from '@/components/ui/CardFooter.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Switch from '@/components/ui/Switch.vue';
import { ACTION } from '@/constants/labels';

const props = defineProps({
    role: { type: Object, required: true },
    matrix: { type: Array, default: () => [] },
});

const form = useForm({ permissions: [...props.role.permissions] });
const search = ref('');

/* ── Urutan kartu entitas (dapat digeser) ─────────────────────────────── */
const entityOrder = ref(props.matrix.map((g) => g.entity));
const dragIndex = ref(null);
const dragOverIndex = ref(null);

const ordered = computed(() =>
    entityOrder.value
        .map((entity) => props.matrix.find((g) => g.entity === entity))
        .filter(Boolean),
);

const groups = computed(() => {
    const term = search.value.trim().toLowerCase();

    return ordered.value
        .map((group) => ({
            ...group,
            abilities: term
                ? group.abilities.filter(
                      (a) => a.name.includes(term) || group.entity.includes(term),
                  )
                : group.abilities,
        }))
        .filter((group) => group.abilities.length);
});

const draggable = computed(() => search.value.trim() === '');

const onDragStart = (index, event) => {
    dragIndex.value = index;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(index));
};

const onDragEnter = (index) => {
    if (dragIndex.value === null || dragIndex.value === index) return;

    const next = [...entityOrder.value];
    next.splice(index, 0, next.splice(dragIndex.value, 1)[0]);
    entityOrder.value = next;
    dragIndex.value = index;
    dragOverIndex.value = index;
};

const onDragEnd = () => {
    dragIndex.value = null;
    dragOverIndex.value = null;

    router.put(
        '/roles/entity-order',
        { order: entityOrder.value },
        { preserveScroll: true, preserveState: true },
    );
};

const has = (name) => form.permissions.includes(name);

const toggle = (name, checked) => {
    form.permissions = checked
        ? [...form.permissions, name]
        : form.permissions.filter((p) => p !== name);
};

const groupChecked = (group) => group.abilities.every((a) => has(a.name));

const toggleGroup = (group, checked) => {
    const names = group.abilities.map((a) => a.name);
    form.permissions = checked
        ? [...new Set([...form.permissions, ...names])]
        : form.permissions.filter((p) => !names.includes(p));
};

const allNames = computed(() => props.matrix.flatMap((g) => g.abilities.map((a) => a.name)));
const allChecked = computed(() => allNames.value.length > 0 && allNames.value.every(has));

const toggleAll = (checked) => {
    form.permissions = checked ? [...allNames.value] : [];
};

const save = () => form.put(`/roles/${props.role.id}/permissions`, { preserveScroll: true });
</script>

<template>
    <Head :title="`Peranan · ${props.role.name}`" />
    <AppLayout>
        <div class="space-y-6" data-testid="role-detail-page">
            <Card>
                <CardHeader class="flex flex-col gap-3 space-y-0 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <CardTitle class="truncate" data-testid="role-detail-name">{{ props.role.name }}</CardTitle>
                        <Badge variant="secondary" class="font-normal" data-testid="role-detail-users">
                            {{ props.role.users_count }} pengguna
                        </Badge>
                        <Lock v-if="props.role.locked" class="size-3.5 text-muted-foreground" aria-label="Terkunci" />
                    </div>
                    <Button variant="outline" size="sm" data-testid="role-detail-back" @click="router.get('/roles')">
                        <ArrowLeft class="size-4" /> {{ ACTION.back }}
                    </Button>
                </CardHeader>
            </Card>

            <Card>
                <CardHeader class="flex flex-col gap-3 space-y-0 sm:flex-row sm:items-center sm:justify-between">
                    <CardTitle>Hak Akses</CardTitle>
                    <div class="flex flex-wrap items-center gap-3">
                        <Input
                            v-model="search"
                            placeholder="Cari izin…"
                            class="h-[var(--ctl-h)] w-full text-[13px] sm:w-56"
                            data-testid="role-permissions-search"
                        />
                        <div class="flex items-center gap-2">
                            <Label>Pilih Semua</Label>
                            <Switch
                                :model-value="allChecked"
                                :disabled="props.role.locked"
                                data-testid="role-permissions-toggle-all"
                                @update:model-value="toggleAll"
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <p v-if="props.role.locked" class="mb-3 text-xs text-muted-foreground" data-testid="role-locked-note">
                        Peranan Super Admin selalu memiliki seluruh hak akses dan tidak dapat diubah.
                    </p>
                    <p v-if="draggable" class="mb-3 text-xs text-muted-foreground" data-testid="role-matrix-drag-hint">
                        Geser kartu entitas untuk menyusun urutannya — urutan tersimpan otomatis.
                    </p>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3" data-testid="role-permission-matrix">
                        <section
                            v-for="(group, index) in groups"
                            :key="group.entity"
                            class="overflow-hidden rounded-lg border transition-shadow"
                            :class="dragOverIndex === index ? 'ring-1 ring-ring' : ''"
                            :draggable="draggable"
                            :data-testid="`permission-group-${group.entity}`"
                            @dragstart="onDragStart(index, $event)"
                            @dragenter.prevent="onDragEnter(index)"
                            @dragover.prevent
                            @dragend="onDragEnd"
                        >
                            <header class="flex items-center justify-between gap-3 border-b bg-muted/40 px-3 py-2">
                                <span class="flex min-w-0 items-center gap-2">
                                    <GripVertical
                                        v-if="draggable"
                                        class="size-3.5 shrink-0 cursor-grab text-muted-foreground active:cursor-grabbing"
                                        :data-testid="`permission-group-${group.entity}-handle`"
                                        aria-hidden="true"
                                    />
                                    <span class="truncate font-mono text-xs font-semibold">{{ group.entity }}</span>
                                </span>
                                <Switch
                                    :model-value="groupChecked(group)"
                                    :disabled="props.role.locked"
                                    :data-testid="`permission-group-${group.entity}-toggle`"
                                    @update:model-value="toggleGroup(group, $event)"
                                />
                            </header>
                            <ul class="divide-y">
                                <li
                                    v-for="ability in group.abilities"
                                    :key="ability.name"
                                    class="flex items-center gap-2.5 px-3 py-1.5"
                                >
                                    <Checkbox
                                        :model-value="has(ability.name)"
                                        :disabled="props.role.locked"
                                        :aria-label="ability.name"
                                        :data-testid="`permission-${ability.name}`"
                                        @update:model-value="toggle(ability.name, $event)"
                                    />
                                    <span class="text-[13px]">{{ ability.label }}</span>
                                    <span class="ml-auto truncate font-mono text-[11px] text-muted-foreground">
                                        {{ ability.name }}
                                    </span>
                                </li>
                            </ul>
                        </section>
                    </div>

                    <p v-if="!groups.length" class="py-6 text-center text-xs text-muted-foreground">
                        Tidak ada izin yang cocok dengan pencarian.
                    </p>
                </CardContent>
                <CardFooter class="justify-between">
                    <span class="text-xs text-muted-foreground" data-testid="role-permissions-count">
                        {{ form.permissions.length }} izin dipilih
                    </span>
                    <Button
                        size="sm"
                        :disabled="props.role.locked || form.processing"
                        data-testid="role-permissions-save"
                        @click="save"
                    >
                        <Save class="size-4" /> {{ form.processing ? ACTION.saving : ACTION.save }}
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
