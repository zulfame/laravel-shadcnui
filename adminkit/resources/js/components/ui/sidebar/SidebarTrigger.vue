<script setup>
import { PanelLeft } from 'lucide-vue-next';
import Button from '@/components/ui/Button.vue';
import { cn } from '@/lib/utils';
import { useSidebar } from './context';

const props = defineProps({ class: { type: null, default: '' } });
const { toggleSidebar } = useSidebar();
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        :class="cn('size-7', props.class)"
        aria-label="Buka/tutup sidebar"
        data-testid="sidebar-trigger"
        @click="toggleSidebar"
    >
        <PanelLeft />
    </Button>
</template>
