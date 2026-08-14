<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: null, default: '' } });
</script>

<!-- Footer kartu: tempat WAJIB tombol aksi. Dua tombol → kiri & kanan. -->
<template>
    <div
        :class="
            cn(
                'flex items-center rounded-b-xl border-t border-border bg-sidebar px-6 py-3',
                props.class,
            )
        "
    >
        <slot />
    </div>
</template>
