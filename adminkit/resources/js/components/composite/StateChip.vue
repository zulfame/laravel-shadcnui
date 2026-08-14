<script setup>
import Badge from '@/components/ui/Badge.vue';

// Hue dikirim sebagai nama CSS variable agar warna tetap token-based,
// mis. chip="--st-done".
const props = defineProps({
    label: { type: String, default: '' },
    chip: { type: String, default: '--st-draft' },
    testid: { type: String, default: undefined },
});
</script>

<template>
    <Badge
        variant="outline"
        class="state-chip whitespace-nowrap font-medium"
        :style="{ '--chip': `var(${props.chip})` }"
        :data-testid="props.testid"
    >
        {{ props.label || '\u2014' }}
    </Badge>
</template>
