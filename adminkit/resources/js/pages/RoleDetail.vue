<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, ShieldCheck } from 'lucide-vue-next';

import AppLayout from '@/components/layout/AppLayout.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import EmptyState from '@/components/composite/EmptyState.vue';
import { ACTION } from '@/constants/labels';

const props = defineProps({
    role: { type: Object, required: true },
});
</script>

<template>
    <Head :title="`Peranan · ${props.role.name}`" />
    <AppLayout>
        <div class="space-y-6" data-testid="role-detail-page">
            <Card>
                <CardHeader class="flex-row items-center justify-between gap-4">
                    <CardTitle data-testid="role-detail-name">{{ props.role.name }}</CardTitle>
                    <Button variant="outline" size="sm" data-testid="role-detail-back" @click="router.get('/roles')">
                        <ArrowLeft class="size-4" /> {{ ACTION.back }}
                    </Button>
                </CardHeader>
                <CardContent>
                    <EmptyState
                        :icon="ShieldCheck"
                        title="Pengaturan Hak Akses"
                        description="Pengaturan hak akses untuk peranan ini akan tersedia di sini."
                        data-testid="role-detail-placeholder"
                    />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
