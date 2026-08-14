import { inject, provide } from 'vue';

export const SIDEBAR_KEY = Symbol('sidebar');

export function provideSidebar(value) {
    provide(SIDEBAR_KEY, value);
}

export function useSidebar() {
    const ctx = inject(SIDEBAR_KEY, null);
    if (!ctx) throw new Error('useSidebar harus dipakai di dalam SidebarProvider.');
    return ctx;
}

export const SIDEBAR_WIDTH = '16rem';
export const SIDEBAR_WIDTH_MOBILE = '18rem';
export const SIDEBAR_WIDTH_ICON = '3rem';
export const SIDEBAR_STORAGE_KEY = 'adminkit.sidebar';
