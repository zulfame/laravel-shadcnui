<script setup>
import { Moon, Sun, Monitor } from 'lucide-vue-next';

import Button from '@/components/ui/Button.vue';
import DropdownMenu from '@/components/ui/DropdownMenu.vue';
import DropdownMenuTrigger from '@/components/ui/DropdownMenuTrigger.vue';
import DropdownMenuContent from '@/components/ui/DropdownMenuContent.vue';
import DropdownMenuItem from '@/components/ui/DropdownMenuItem.vue';
import { useTheme } from '@/composables/useTheme';
import { cn } from '@/lib/utils';

const OPTIONS = [
    { value: 'light', label: 'Terang', icon: Sun },
    { value: 'dark', label: 'Gelap', icon: Moon },
    { value: 'system', label: 'Sistem', icon: Monitor },
];

const { theme, setTheme } = useTheme();
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
