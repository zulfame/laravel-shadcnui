<script setup>
import BrandMark from '@/components/composite/BrandMark.vue';
import { computed, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, LogOut, UserRound } from 'lucide-vue-next';

import Avatar from '@/components/ui/Avatar.vue';
import DropdownMenu from '@/components/ui/DropdownMenu.vue';
import DropdownMenuTrigger from '@/components/ui/DropdownMenuTrigger.vue';
import DropdownMenuContent from '@/components/ui/DropdownMenuContent.vue';
import DropdownMenuItem from '@/components/ui/DropdownMenuItem.vue';
import DropdownMenuLabel from '@/components/ui/DropdownMenuLabel.vue';
import DropdownMenuSeparator from '@/components/ui/DropdownMenuSeparator.vue';
import Sidebar from '@/components/ui/sidebar/Sidebar.vue';
import SidebarContent from '@/components/ui/sidebar/SidebarContent.vue';
import SidebarFooter from '@/components/ui/sidebar/SidebarFooter.vue';
import SidebarGroup from '@/components/ui/sidebar/SidebarGroup.vue';
import SidebarGroupLabel from '@/components/ui/sidebar/SidebarGroupLabel.vue';
import SidebarHeader from '@/components/ui/sidebar/SidebarHeader.vue';
import SidebarMenu from '@/components/ui/sidebar/SidebarMenu.vue';
import SidebarMenuButton from '@/components/ui/sidebar/SidebarMenuButton.vue';
import SidebarMenuItem from '@/components/ui/sidebar/SidebarMenuItem.vue';
import SidebarRail from '@/components/ui/sidebar/SidebarRail.vue';
import MenuNode from '@/components/layout/MenuNode.vue';
import { areaIdOf, AREA_META, DEFAULT_AREA_ID } from '@/config/navigation';
import { initialsOf } from '@/lib/utils';

const AREA_KEY = 'adminkit.activeArea';

const page = usePage();
const auth = computed(() => page.props.auth?.user ?? {});
const branding = computed(() => page.props.branding ?? {});
const isAdmin = computed(() => Boolean(auth.value.is_admin));

// Menu dirender dari basis data (modul Menu Sidebar), sudah tersaring per izin.
const areas = computed(() =>
    (page.props.menu ?? [])
        .filter((area) => area.items.length)
        .map((area) => ({ ...area, ...(AREA_META[area.id] ?? {}) })),
);

const areaId = ref(DEFAULT_AREA_ID);
const activeArea = computed(
    () => areas.value.find((area) => area.id === areaId.value) ?? areas.value[0],
);

const firstHrefOf = (items = []) => {
    for (const item of items) {
        if (item.href) return item.href;
        const nested = firstHrefOf(item.children);
        if (nested) return nested;
    }

    return null;
};

const pathname = computed(() => page.url.split('?')[0]);

// Area mengikuti halaman yang sedang dibuka agar titik awal selalu konsisten
// (mis. setelah masuk, halaman dashboard selalu menampilkan Member Area).
onMounted(() => {
    areaId.value = areaIdOf(pathname.value);
});

watch(pathname, (next) => {
    const routeArea = areaIdOf(next);
    if (routeArea === 'admin' && !isAdmin.value) return;
    areaId.value = routeArea;
});

const changeArea = (nextId) => {
    areaId.value = nextId;
    try {
        window.localStorage.setItem(AREA_KEY, nextId);
    } catch (e) {
        /* penyimpanan tidak tersedia */
    }
    const target = firstHrefOf(areas.value.find((area) => area.id === nextId)?.items);
    if (target && target !== pathname.value) router.visit(target);
};

const currentUser = computed(() => ({
    name: auth.value.name || 'Pengguna',
    email: auth.value.email || '',
    avatar: auth.value.avatar || '',
    initials: initialsOf(auth.value.name, auth.value.email),
}));

</script>

<template>
    <Sidebar collapsible="icon">
        <SidebarHeader class="sticky top-0 z-10 border-b border-sidebar-border bg-sidebar">
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger>
                            <SidebarMenuButton
                                size="lg"
                                class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                                data-testid="area-switcher-trigger"
                            >
                                <BrandMark
                                    :logo="branding.logo_light"
                                    :initials="branding.brand_initials"
                                    class="aspect-square size-8 shrink-0 rounded-lg bg-primary text-[11px] font-semibold text-primary-foreground"
                                />
                                <div
                                    class="grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden"
                                >
                                    <span class="truncate font-semibold">{{ branding.app_name || 'AdminKit' }}</span>
                                    <span class="truncate text-xs text-sidebar-foreground/70">
                                        {{ activeArea?.label }}
                                    </span>
                                </div>
                                <ChevronsUpDown
                                    class="ml-auto size-4 group-data-[collapsible=icon]:hidden"
                                    aria-hidden="true"
                                />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent side="right" align="start" class="min-w-56 rounded-lg">
                            <DropdownMenuLabel class="text-xs font-normal text-muted-foreground">
                                Area
                            </DropdownMenuLabel>
                            <DropdownMenuItem
                                v-for="area in areas"
                                :key="area.id"
                                class="gap-2"
                                :data-testid="`area-option-${area.id}`"
                                @click="changeArea(area.id)"
                            >
                                <div class="flex size-6 items-center justify-center rounded-md border">
                                    <component :is="area.icon" class="size-3.5 shrink-0" aria-hidden="true" />
                                </div>
                                <div class="grid flex-1 leading-tight">
                                    <span class="truncate font-medium">{{ area.label }}</span>
                                    <span class="truncate text-xs text-muted-foreground">{{ area.description }}</span>
                                </div>
                                <Check v-if="area.id === activeArea?.id" class="size-4" aria-hidden="true" />
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <template v-if="activeArea?.items?.length">
                <SidebarGroup>
                    <SidebarGroupLabel class="uppercase tracking-wide">{{ activeArea.label }}</SidebarGroupLabel>
                    <SidebarMenu>
                        <MenuNode
                            v-for="item in activeArea.items"
                            :key="item.id"
                            :item="item"
                            :pathname="pathname"
                        />
                    </SidebarMenu>
                </SidebarGroup>
            </template>
            <SidebarGroup v-else>
                <SidebarGroupLabel class="uppercase tracking-wide">Menu</SidebarGroupLabel>
                <p
                    class="px-2 text-xs leading-relaxed text-sidebar-foreground/60 group-data-[collapsible=icon]:hidden"
                    data-testid="area-empty-note"
                >
                    Belum ada menu untuk area ini. Tambahkan lewat modul Menu Sidebar.
                </p>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger>
                            <SidebarMenuButton
                                size="lg"
                                class="border border-sidebar-border bg-sidebar-accent text-sidebar-accent-foreground shadow-sm hover:bg-sidebar-accent data-[state=open]:bg-sidebar-accent"
                                data-testid="user-menu-trigger"
                            >
                                <Avatar
                                    :src="currentUser.avatar"
                                    :fallback="currentUser.initials"
                                    class="shrink-0"
                                />
                                <div
                                    class="grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden"
                                >
                                    <span class="truncate font-semibold">{{ currentUser.name }}</span>
                                    <span class="truncate text-xs text-sidebar-foreground/70">{{ currentUser.email }}</span>
                                </div>
                                <ChevronsUpDown
                                    class="ml-auto size-4 group-data-[collapsible=icon]:hidden"
                                    aria-hidden="true"
                                />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent side="right" align="end" class="min-w-56 rounded-lg">
                            <DropdownMenuLabel class="p-0 font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                    <Avatar :src="currentUser.avatar" :fallback="currentUser.initials" />
                                    <div class="grid flex-1 leading-tight">
                                        <span class="truncate font-semibold">{{ currentUser.name }}</span>
                                        <span class="truncate text-xs text-muted-foreground">{{ currentUser.email }}</span>
                                    </div>
                                </div>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem as-child data-testid="user-menu-profile">
                                <Link href="/profile" class="flex w-full items-center gap-2">
                                    <UserRound aria-hidden="true" />
                                    Profil
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem data-testid="logout-button" @click="router.post('/logout')">
                                <LogOut aria-hidden="true" />
                                Keluar
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>

        <SidebarRail />
    </Sidebar>
</template>
