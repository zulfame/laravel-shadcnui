<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

import SidebarMenuButton from '@/components/ui/sidebar/SidebarMenuButton.vue';
import SidebarMenuItem from '@/components/ui/sidebar/SidebarMenuItem.vue';
import { iconOf } from '@/lib/menuIcons';

/** Item menu sidebar rekursif (maks. 3 tingkat). Item tanpa `href` = grup. */
const props = defineProps({
    item: { type: Object, required: true },
    pathname: { type: String, default: '' },
    depth: { type: Number, default: 0 },
});

const hasChildren = computed(() => (props.item.children ?? []).length > 0);

const matches = (item) =>
    item.href && (item.href === '/' ? props.pathname === '/' : props.pathname.startsWith(item.href));

const containsActive = (item) =>
    matches(item) || (item.children ?? []).some((child) => containsActive(child));

const open = ref(containsActive(props.item));
const testid = computed(() =>
    `nav-${(props.item.href || props.item.label).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'root'}`,
);
</script>

<template>
    <SidebarMenuItem>
        <!-- Grup: tombol buka/tutup + daftar anak menjorok. -->
        <template v-if="hasChildren">
            <SidebarMenuButton
                :is-active="!open && containsActive(item)"
                :tooltip="item.label"
                :data-testid="`${testid}-toggle`"
                @click="open = !open"
            >
                <component :is="iconOf(item.icon)" aria-hidden="true" />
                <span class="flex-1 text-left">{{ item.label }}</span>
                <ChevronRight
                    class="ml-auto size-3.5 shrink-0 transition-transform duration-200 group-data-[collapsible=icon]:hidden"
                    :class="open ? 'rotate-90' : ''"
                    aria-hidden="true"
                />
            </SidebarMenuButton>

            <ul
                v-show="open"
                class="ml-3.5 mt-1 space-y-1 border-l border-sidebar-border pl-2 group-data-[collapsible=icon]:hidden"
                :data-testid="`${testid}-children`"
            >
                <MenuNode
                    v-for="child in item.children"
                    :key="child.id"
                    :item="child"
                    :pathname="pathname"
                    :depth="depth + 1"
                />
            </ul>
        </template>

        <!-- Tautan biasa. -->
        <SidebarMenuButton
            v-else
            :as="Link"
            :href="item.href || '#'"
            :is-active="matches(item)"
            :tooltip="item.label"
            :data-testid="testid"
        >
            <component :is="iconOf(item.icon)" aria-hidden="true" />
            <span>{{ item.label }}</span>
        </SidebarMenuButton>
    </SidebarMenuItem>
</template>
