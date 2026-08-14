<script setup>
import { DialogContent, DialogOverlay, DialogPortal, DialogRoot } from 'reka-ui';
import { cn } from '@/lib/utils';
import { useSidebar, SIDEBAR_WIDTH_MOBILE } from './context';

const props = defineProps({
    collapsible: { type: String, default: 'icon' },
    class: { type: null, default: '' },
});

const { state, isMobile, openMobile } = useSidebar();
const sidebar = useSidebar();
</script>

<template>
    <!-- Mobile: sidebar tampil sebagai sheet dari kiri. -->
    <DialogRoot v-if="isMobile" :open="openMobile" @update:open="sidebar.openMobile.value = $event">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-50 bg-black/60" />
            <DialogContent
                :style="{ width: SIDEBAR_WIDTH_MOBILE }"
                class="fixed inset-y-0 left-0 z-50 flex flex-col bg-sidebar p-0 text-sidebar-foreground shadow-lg data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:slide-out-to-left data-[state=open]:slide-in-from-left"
            >
                <slot />
            </DialogContent>
        </DialogPortal>
    </DialogRoot>

    <!-- Desktop: kolom tetap, dapat dikuncupkan menjadi ikon. -->
    <div
        v-else
        class="group peer hidden text-sidebar-foreground md:block"
        :data-state="state"
        :data-collapsible="state === 'collapsed' ? props.collapsible : ''"
        data-side="left"
    >
        <div
            class="relative w-[--sidebar-width] bg-transparent transition-[width] duration-200 ease-linear group-data-[collapsible=icon]:w-[--sidebar-width-icon]"
        />
        <div
            :class="
                cn(
                    'fixed inset-y-0 left-0 z-10 hidden h-svh w-[--sidebar-width] border-r border-sidebar-border transition-[left,right,width] duration-200 ease-linear md:flex',
                    'group-data-[collapsible=icon]:w-[--sidebar-width-icon]',
                    props.class,
                )
            "
        >
            <div class="flex h-full w-full flex-col bg-sidebar">
                <slot />
            </div>
        </div>
    </div>
</template>
