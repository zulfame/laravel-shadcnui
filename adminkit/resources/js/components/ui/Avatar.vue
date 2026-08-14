<script setup>
import { AvatarFallback, AvatarImage, AvatarRoot } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps({
    src: { type: String, default: '' },
    alt: { type: String, default: '' },
    fallback: { type: String, default: 'U' },
    class: { type: null, default: '' },
});
</script>

<template>
    <AvatarRoot
        :class="cn('relative flex size-8 shrink-0 overflow-hidden rounded-lg', props.class)"
    >
        <AvatarImage v-if="props.src" :src="props.src" :alt="props.alt" class="size-full object-cover" />
        <AvatarFallback
            class="flex size-full items-center justify-center rounded-lg bg-muted text-xs font-medium"
        >
            {{ props.fallback }}
        </AvatarFallback>
    </AvatarRoot>
</template>
