<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: null, default: '' } });
</script>

<!-- Header kartu: latar bg-sidebar (bingkai judul) + padding vertikal 12px. -->
<template>
    <div
        :class="
            cn(
                'flex flex-col space-y-1 rounded-t-xl border-b border-border bg-sidebar px-6 py-3',
                props.class,
            )
        "
    >
        <slot />
    </div>
</template>
