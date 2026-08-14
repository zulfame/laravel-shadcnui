<script setup>
import { DialogClose, DialogContent, DialogOverlay, DialogPortal, DialogRoot, DialogTitle, DialogDescription } from 'reka-ui';
import { X } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    class: { type: null, default: '' },
});
defineEmits(['update:open']);
</script>

<!-- Dialog: judul di header (bg-sidebar), penjelasan di body, aksi di footer. -->
<template>
    <DialogRoot :open="props.open" @update:open="$emit('update:open', $event)">
        <DialogPortal>
            <DialogOverlay
                class="fixed inset-0 z-50 bg-black/60 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
            />
            <DialogContent
                :class="
                    cn(
                        'fixed left-1/2 top-1/2 z-50 w-full max-w-lg -translate-x-1/2 -translate-y-1/2 overflow-hidden rounded-lg border bg-card shadow-lg data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
                        props.class,
                    )
                "
            >
                <div class="flex items-start justify-between gap-4 border-b border-border bg-sidebar px-6 py-4">
                    <div class="space-y-1">
                        <DialogTitle class="text-base font-semibold leading-none tracking-tight">
                            {{ props.title }}
                        </DialogTitle>
                        <DialogDescription v-if="props.description" class="text-xs text-muted-foreground">
                            {{ props.description }}
                        </DialogDescription>
                    </div>
                    <DialogClose
                        class="rounded-md p-1 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                        aria-label="Tutup"
                    >
                        <X class="size-4" />
                    </DialogClose>
                </div>

                <div class="px-6 py-4">
                    <slot />
                </div>

                <div
                    v-if="$slots.footer"
                    class="flex items-center justify-between gap-2 border-t border-border bg-sidebar px-6 py-4"
                >
                    <slot name="footer" />
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
