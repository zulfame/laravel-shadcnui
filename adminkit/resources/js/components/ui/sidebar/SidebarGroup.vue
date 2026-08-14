<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: null, default: '' } });
</script>

<template>
    <div :class="cn('relative flex w-full min-w-0 flex-col p-2', props.class)">
        <slot />
    </div>
</template>
