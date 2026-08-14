<script setup>
import { useSidebar } from './context';
const { toggleSidebar } = useSidebar();
</script>

<template>
    <button
        type="button"
        tabindex="-1"
        aria-label="Buka/tutup sidebar"
        title="Buka/tutup sidebar"
        class="absolute inset-y-0 -right-4 z-20 hidden w-4 -translate-x-1/2 cursor-w-resize transition-all ease-linear after:absolute after:inset-y-0 after:left-1/2 after:w-[2px] hover:after:bg-sidebar-border sm:flex"
        @click="toggleSidebar"
    />
</template>
