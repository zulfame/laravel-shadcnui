<script setup>
import { computed } from 'vue';
import Badge from '@/components/ui/Badge.vue';

// Status/level dipetakan ke varian Badge shadcn (tanpa warna kustom).
const props = defineProps({
    label: { type: String, default: '' },
    chip: { type: String, default: '--st-draft' },
    testid: { type: String, default: undefined },
});

const VARIANTS = {
    '--st-done': 'default',
    '--st-progress': 'default',
    '--st-pending': 'outline',
    '--st-draft': 'secondary',
    '--st-cancelled': 'secondary',
    '--st-archived': 'secondary',
    '--st-overdue': 'destructive',
};

const variant = computed(() => VARIANTS[props.chip] ?? 'secondary');
</script>

<template>
    <Badge :variant="variant" class="whitespace-nowrap font-medium" :data-testid="props.testid">
        {{ props.label || '\u2014' }}
    </Badge>
</template>
