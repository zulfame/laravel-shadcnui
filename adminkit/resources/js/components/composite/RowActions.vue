<script setup>
import { MoreHorizontal } from 'lucide-vue-next';
import Button from '@/components/ui/Button.vue';
import DropdownMenu from '@/components/ui/DropdownMenu.vue';
import DropdownMenuTrigger from '@/components/ui/DropdownMenuTrigger.vue';
import DropdownMenuContent from '@/components/ui/DropdownMenuContent.vue';

// Aksi baris datatable: dropdown ⋯ berisi item beriko.
const props = defineProps({ testid: { type: String, default: undefined } });
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger>
            <Button variant="ghost" size="icon" class="size-7" aria-label="Aksi baris" :data-testid="props.testid">
                <MoreHorizontal class="size-3.5" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-40">
            <slot />
        </DropdownMenuContent>
    </DropdownMenu>
</template>
