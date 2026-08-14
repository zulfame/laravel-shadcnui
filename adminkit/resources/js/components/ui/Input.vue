<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    class: { type: null, default: '' },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <input
        :value="props.modelValue"
        autocomplete="off"
        :class="
            cn(
                'flex h-[var(--ctl-h)] w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                props.class,
            )
        "
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>
