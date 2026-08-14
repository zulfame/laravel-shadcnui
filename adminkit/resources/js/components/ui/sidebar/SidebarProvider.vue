<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { cn } from '@/lib/utils';
import TooltipProvider from '@/components/ui/TooltipProvider.vue';
import {
    provideSidebar,
    SIDEBAR_STORAGE_KEY,
    SIDEBAR_WIDTH,
    SIDEBAR_WIDTH_ICON,
} from './context';

const props = defineProps({ class: { type: null, default: '' } });

const open = ref(true);
const openMobile = ref(false);
const isMobile = ref(false);

const state = computed(() => (open.value ? 'expanded' : 'collapsed'));

const setOpen = (value) => {
    open.value = value;
    try {
        window.localStorage.setItem(SIDEBAR_STORAGE_KEY, String(value));
    } catch (e) {
        /* penyimpanan tidak tersedia */
    }
};

const toggleSidebar = () => {
    if (isMobile.value) openMobile.value = !openMobile.value;
    else setOpen(!open.value);
};

const mq = ref(null);
const syncMobile = () => {
    isMobile.value = window.innerWidth < 768;
};
const onKeydown = (event) => {
    if (event.key === 'b' && (event.metaKey || event.ctrlKey)) {
        event.preventDefault();
        toggleSidebar();
    }
};

onMounted(() => {
    syncMobile();
    window.addEventListener('resize', syncMobile);
    window.addEventListener('keydown', onKeydown);
    try {
        const saved = window.localStorage.getItem(SIDEBAR_STORAGE_KEY);
        if (saved !== null) open.value = saved === 'true';
    } catch (e) {
        /* penyimpanan tidak tersedia */
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', syncMobile);
    window.removeEventListener('keydown', onKeydown);
});

provideSidebar({ state, open, setOpen, isMobile, openMobile, toggleSidebar });
</script>

<template>
    <TooltipProvider>
        <div
            :style="{ '--sidebar-width': SIDEBAR_WIDTH, '--sidebar-width-icon': SIDEBAR_WIDTH_ICON }"
            :class="cn('group/sidebar-wrapper flex min-h-svh w-full', props.class)"
        >
            <slot />
        </div>
    </TooltipProvider>
</template>
