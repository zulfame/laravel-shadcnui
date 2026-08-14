<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: null, default: '' }, for: { type: String, default: undefined } });
</script>

<!-- `block` + `leading-none` supaya jarak label→kontrol persis nilai token. -->
<template>
    <label
        :for="props.for"
        :class="cn('block text-sm font-medium leading-none', props.class)"
    >
        <slot />
    </label>
</template>
