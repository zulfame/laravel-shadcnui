<script setup>
import { Moon, Sun, Monitor } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

import Button from '@/components/ui/Button.vue';
import DropdownMenu from '@/components/ui/DropdownMenu.vue';
import DropdownMenuTrigger from '@/components/ui/DropdownMenuTrigger.vue';
import DropdownMenuContent from '@/components/ui/DropdownMenuContent.vue';
import DropdownMenuItem from '@/components/ui/DropdownMenuItem.vue';
import { cn } from '@/lib/utils';

const STORAGE_KEY = 'adminkit.theme';
const OPTIONS = [
    { value: 'light', label: 'Terang', icon: Sun },
    { value: 'dark', label: 'Gelap', icon: Moon },
    { value: 'system', label: 'Sistem', icon: Monitor },
];

const theme = ref('system');

const apply = (value) => {
    const dark =
        value === 'dark' ||
        (value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', dark);
};

const setTheme = (value) => {
    theme.value = value;
    try {
        window.localStorage.setItem(STORAGE_KEY, value);
    } catch (e) {
        /* penyimpanan tidak tersedia */
    }
    apply(value);
};

onMounted(() => {
    try {
        theme.value = window.localStorage.getItem(STORAGE_KEY) || 'system';
    } catch (e) {
        theme.value = 'system';
    }
    apply(theme.value);
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (theme.value === 'system') apply('system');
    });
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger>
            <Button variant="ghost" size="icon" class="size-8" aria-label="Ubah tema" data-testid="theme-toggle-trigger">
                <Sun class="size-4 rotate-0 scale-100 transition-transform dark:-rotate-90 dark:scale-0" />
                <Moon class="absolute size-4 rotate-90 scale-0 transition-transform dark:rotate-0 dark:scale-100" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-36">
            <DropdownMenuItem
                v-for="opt in OPTIONS"
                :key="opt.value"
                :class="cn(theme === opt.value && 'bg-accent text-accent-foreground')"
                :data-testid="`theme-option-${opt.value}`"
                @click="setTheme(opt.value)"
            >
                <component :is="opt.icon" class="size-4" aria-hidden="true" />
                {{ opt.label }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
