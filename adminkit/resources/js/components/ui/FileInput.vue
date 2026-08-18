<script setup>
import { ref } from 'vue';

import { cn } from '@/lib/utils';

/** Input berkas dengan tinggi & tipografi identik Input/Combobox/DatePicker. */
defineProps({
    class: { type: String, default: '' },
});

const el = ref(null);

/** Kosongkan pilihan berkas dari komponen induk. */
const clear = () => {
    if (el.value) el.value.value = '';
};

defineExpose({ el, clear });
</script>

<template>
    <input
        ref="el"
        type="file"
        :class="
            cn(
                'flex h-[var(--ctl-h)] w-full cursor-pointer items-center rounded-md border border-input bg-transparent py-0 pl-0 pr-3 text-sm shadow-sm transition-colors file:mr-3 file:h-[calc(var(--ctl-h)-2px)] file:cursor-pointer file:border-0 file:border-r file:border-input file:bg-secondary file:px-3 file:text-xs file:font-medium file:text-secondary-foreground hover:file:bg-accent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                $props.class,
            )
        "
    />
</template>
