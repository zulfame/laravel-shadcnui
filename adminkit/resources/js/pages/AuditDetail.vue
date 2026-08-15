<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Check, Copy } from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Separator from '@/components/ui/Separator.vue';
import StateChip from '@/components/composite/StateChip.vue';
import { ACTION } from '@/constants/labels';

const props = defineProps({
    log: { type: Object, required: true },
});

const asText = (value) => {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value === 'boolean') return value ? 'true' : 'false';

    return String(value);
};

const summary = computed(() => [
    { label: 'ID Log', value: `#${props.log.id}` },
    { label: 'Waktu', value: props.log.created_at_full },
    { label: 'Relatif', value: props.log.created_at_diff },
    { label: 'ISO 8601', value: props.log.created_at_iso, mono: true },
    { label: 'Modul', value: props.log.module },
    { label: 'Level', value: `${props.log.level_label} (${props.log.level})`, mono: true },
    { label: 'Pelaku', value: props.log.actor },
    { label: 'Email Pelaku', value: asText(props.log.actor_email) },
    { label: 'ID Pengguna', value: asText(props.log.user_id), mono: true },
    { label: 'Objek', value: asText(props.log.subject) },
    { label: 'Kelas Objek', value: asText(props.log.subject_type), mono: true },
]);

const technical = computed(() => [
    { label: 'Alamat IP', value: asText(props.log.ip) },
    { label: 'Metode', value: asText(props.log.method) },
    { label: 'Kode Status', value: asText(props.log.status_code) },
    { label: 'URL', value: asText(props.log.url) },
    { label: 'User Agent', value: asText(props.log.user_agent) },
]);

const changeRows = computed(() =>
    Object.entries(props.log.changes ?? {}).map(([field, pair]) => ({
        field,
        old: asText(pair?.old),
        new: asText(pair?.new),
        changed: asText(pair?.old) !== asText(pair?.new),
    })),
);

const contextRows = computed(() =>
    Object.entries(props.log.context ?? {}).map(([key, value]) => ({ key, value: asText(value) })),
);

const rawJson = computed(() =>
    JSON.stringify(
        {
            id: props.log.id,
            created_at: props.log.created_at_iso,
            actor: { id: props.log.user_id, name: props.log.actor, email: props.log.actor_email },
            action: props.log.action,
            module: props.log.module,
            level: props.log.level,
            subject: { type: props.log.subject_type, id: props.log.subject_id },
            request: {
                ip: props.log.ip,
                method: props.log.method,
                url: props.log.url,
                status_code: props.log.status_code,
                user_agent: props.log.user_agent,
            },
            changes: props.log.changes,
            context: props.log.context,
        },
        null,
        2,
    ),
);

const copied = ref(false);
const copyJson = async () => {
    await navigator.clipboard.writeText(rawJson.value);
    copied.value = true;
    setTimeout(() => (copied.value = false), 1500);
};
</script>

<template>
    <Head :title="`Audit #${props.log.id}`" />
    <AppLayout>
        <div class="space-y-6" data-testid="audit-detail-page">
            <!-- Ringkasan -->
            <Card>
                <CardHeader class="flex-row items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <StateChip :label="props.log.level_label" :chip="props.log.level_chip" />
                        <CardTitle class="truncate" data-testid="audit-detail-action">
                            {{ props.log.action }}
                        </CardTitle>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        data-testid="audit-detail-back"
                        @click="router.get('/audit-trail')"
                    >
                        <ArrowLeft class="size-4" /> {{ ACTION.back }}
                    </Button>
                </CardHeader>
                <CardContent>
                    <dl class="grid gap-x-6 gap-y-2 sm:grid-cols-2 xl:grid-cols-3" data-testid="audit-detail-summary">
                        <div
                            v-for="item in summary"
                            :key="item.label"
                            class="flex items-baseline gap-3 border-b py-1.5 last:border-b-0"
                        >
                            <dt class="w-28 shrink-0 text-xs text-muted-foreground">{{ item.label }}</dt>
                            <dd class="min-w-0 break-all text-[13px]" :class="item.mono && 'font-mono text-xs'">
                                {{ item.value }}
                            </dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <div class="grid gap-6 xl:grid-cols-3">
                <!-- Perubahan data -->
                <Card class="xl:col-span-2">
                    <CardHeader class="flex-row items-center justify-between gap-4">
                        <CardTitle>Perubahan Data</CardTitle>
                        <span class="text-xs tabular-nums text-muted-foreground" data-testid="audit-detail-change-count">
                            {{ changeRows.length }} kolom
                        </span>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div v-if="changeRows.length" class="thin-scroll max-h-[420px] overflow-auto" data-testid="audit-detail-changes">
                            <table class="w-full text-[13px]">
                                <thead class="sticky top-0 z-10 bg-muted text-xs text-muted-foreground">
                                    <tr>
                                        <th class="w-1/4 px-3 py-2 text-left font-medium">Kolom</th>
                                        <th class="px-3 py-2 text-left font-medium">Sebelum</th>
                                        <th class="px-3 py-2 text-left font-medium">Sesudah</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr v-for="row in changeRows" :key="row.field" class="align-top">
                                        <td class="px-3 py-2 font-mono text-xs font-medium">{{ row.field }}</td>
                                        <td class="whitespace-pre-wrap break-all px-3 py-2 font-mono text-xs text-muted-foreground">
                                            {{ row.old }}
                                        </td>
                                        <td class="whitespace-pre-wrap break-all px-3 py-2 font-mono text-xs">
                                            {{ row.new }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="px-4 py-6 text-center text-xs text-muted-foreground">
                            Tidak ada perubahan kolom yang tercatat.
                        </p>
                    </CardContent>
                </Card>

                <div class="space-y-6">
                    <!-- Konteks / kesalahan -->
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ props.log.level === 'danger' ? 'Kesalahan' : 'Konteks' }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl v-if="contextRows.length" class="space-y-2" data-testid="audit-detail-context">
                                <div v-for="row in contextRows" :key="row.key" class="space-y-0.5">
                                    <dt class="font-mono text-[11px] uppercase tracking-wide text-muted-foreground">
                                        {{ row.key }}
                                    </dt>
                                    <dd class="whitespace-pre-wrap break-all font-mono text-xs">{{ row.value }}</dd>
                                </div>
                            </dl>
                            <p v-else class="text-xs text-muted-foreground">Tidak ada konteks tambahan.</p>
                        </CardContent>
                    </Card>

                    <!-- Teknis -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Teknis</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl class="space-y-2" data-testid="audit-detail-technical">
                                <div v-for="item in technical" :key="item.label" class="space-y-0.5">
                                    <dt class="text-[11px] uppercase tracking-wide text-muted-foreground">
                                        {{ item.label }}
                                    </dt>
                                    <dd class="break-all font-mono text-xs">{{ item.value }}</dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Payload mentah -->
            <Card>
                <CardHeader class="flex-row items-center justify-between gap-4">
                    <CardTitle>Payload Mentah (JSON)</CardTitle>
                    <Button variant="outline" size="sm" data-testid="audit-detail-copy" @click="copyJson">
                        <Check v-if="copied" class="size-4" />
                        <Copy v-else class="size-4" />
                        {{ copied ? 'Tersalin' : 'Salin' }}
                    </Button>
                </CardHeader>
                <Separator />
                <CardContent class="p-0">
                    <pre
                        class="thin-scroll max-h-[420px] overflow-auto px-4 py-3 font-mono text-xs leading-relaxed"
                        data-testid="audit-detail-json"
                        >{{ rawJson }}</pre
                    >
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
