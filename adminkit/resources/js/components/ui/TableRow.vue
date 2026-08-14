<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: null, default: '' } });
</script>

<template>
    <tr :class="cn('border-b transition-colors hover:bg-muted/40', props.class)">
        <slot />
    </tr>
</template>
