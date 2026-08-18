<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: null, default: '' }, for: { type: String, default: undefined } });
</script>

<!-- Aturan baku label form: 12px, uppercase, letter-spacing lebar, warna foreground. -->
<template>
    <label
        :for="props.for"
        :class="
            cn(
                'block uppercase leading-none text-foreground',
                'text-[length:var(--label-size)] font-[number:var(--label-weight)] tracking-[var(--label-tracking)]',
                props.class,
            )
        "
    >
        <slot />
    </label>
</template>
