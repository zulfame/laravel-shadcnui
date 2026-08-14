<script setup>
import { CheckboxIndicator, CheckboxRoot } from 'reka-ui';
import { Check } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    class: { type: null, default: '' },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <CheckboxRoot
        :model-value="props.modelValue"
        :class="
            cn(
                'peer size-4 shrink-0 rounded-sm border border-input shadow focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:border-primary data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground',
                props.class,
            )
        "
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <CheckboxIndicator class="flex items-center justify-center text-current">
            <Check class="size-3.5" />
        </CheckboxIndicator>
    </CheckboxRoot>
</template>
