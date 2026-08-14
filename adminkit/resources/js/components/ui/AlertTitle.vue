<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: null, default: '' } });
</script>

<template>
    <h5 :class="cn('col-start-2 font-medium leading-5 tracking-tight', props.class)">
        <slot />
    </h5>
</template>
