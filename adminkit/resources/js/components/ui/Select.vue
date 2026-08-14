<script setup>
import {
    SelectContent,
    SelectItem,
    SelectItemIndicator,
    SelectItemText,
    SelectPortal,
    SelectRoot,
    SelectTrigger,
    SelectValue,
    SelectViewport,
} from 'reka-ui';
import { Check, ChevronDown } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, default: () => [] }, // [{ value, label }]
    placeholder: { type: String, default: 'Pilih' },
    class: { type: null, default: '' },
});
defineEmits(['update:modelValue']);

// SelectRoot reka-ui tidak merender DOM node, jadi atribut (mis. data-testid)
// harus diteruskan manual ke trigger.
defineOptions({ inheritAttrs: false });
</script>

<template>
    <SelectRoot
        :model-value="String(props.modelValue)"
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <SelectTrigger
            v-bind="$attrs"
            :class="
                cn(
                    'flex h-[var(--ctl-h-sm)] w-full items-center justify-between gap-2 rounded-md border border-input bg-transparent px-2.5 text-xs shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring',
                    props.class,
                )
            "
        >
            <SelectValue :placeholder="props.placeholder" />
            <ChevronDown class="size-3.5 shrink-0 opacity-50" />
        </SelectTrigger>
        <SelectPortal>
            <SelectContent
                position="popper"
                :side-offset="4"
                class="z-50 max-h-72 min-w-[6rem] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md"
            >
                <SelectViewport class="p-1">
                    <SelectItem
                        v-for="opt in props.options"
                        :key="opt.value"
                        :value="String(opt.value)"
                        class="relative flex cursor-pointer select-none items-center rounded-sm py-1.5 pl-7 pr-2 text-xs outline-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                    >
                        <span class="absolute left-1.5 flex size-4 items-center justify-center">
                            <SelectItemIndicator><Check class="size-3.5" /></SelectItemIndicator>
                        </span>
                        <SelectItemText>{{ opt.label }}</SelectItemText>
                    </SelectItem>
                </SelectViewport>
            </SelectContent>
        </SelectPortal>
    </SelectRoot>
</template>
