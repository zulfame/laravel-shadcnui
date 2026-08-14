<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: null, default: '' } });
</script>

<template>
    <div :class="cn('col-start-2 text-sm leading-5', props.class)">
        <slot />
    </div>
</template>
