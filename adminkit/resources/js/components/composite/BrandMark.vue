<script setup>
import { computed, ref, watch } from 'vue';
import { Boxes } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

/**
 * BrandMark — urutan tampil: logo (bila ada) → inisial brand (bila ada) → ikon.
 */
const props = defineProps({
    logo: { type: String, default: null },
    initials: { type: String, default: '' },
    class: { type: null, default: '' },
    iconClass: { type: null, default: 'size-4' },
});

const text = computed(() => (props.initials || '').slice(0, 4).toUpperCase());

// Berkas logo bisa hilang (mis. driver penyimpanan berganti) — jatuh ke inisial/ikon.
const broken = ref(false);
watch(
    () => props.logo,
    () => {
        broken.value = false;
    },
);
const logo = computed(() => (broken.value ? null : props.logo));
</script>

<template>
    <span
        :class="cn('flex items-center justify-center overflow-hidden rounded-md', props.class)"
        data-testid="brand-mark"
    >
        <img v-if="logo" :src="logo" alt="" class="size-full object-contain" @error="broken = true" />
        <template v-else-if="text">{{ text }}</template>
        <Boxes v-else :class="props.iconClass" aria-hidden="true" />
    </span>
</template>
