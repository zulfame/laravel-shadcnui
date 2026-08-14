<script setup>
import { Trash2, X } from 'lucide-vue-next';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import { ACTION } from '@/constants/labels';

// Judul singkat di header, penjelasan di body, aksi di footer (kiri & kanan).
const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Hapus data?' },
    description: { type: String, default: 'Tindakan ini tidak dapat dibatalkan.' },
    processing: { type: Boolean, default: false },
});
const emit = defineEmits(['update:open', 'confirm']);
</script>

<template>
    <Dialog
        :open="props.open"
        :title="props.title"
        class="max-w-md"
        @update:open="emit('update:open', $event)"
    >
        <p class="text-sm text-muted-foreground" data-testid="confirm-delete-description">
            {{ props.description }}
        </p>

        <template #footer>
            <Button variant="outline" size="sm" data-testid="confirm-delete-cancel" @click="emit('update:open', false)">
                <X class="size-4" /> {{ ACTION.cancel }}
            </Button>
            <Button
                variant="destructive"
                size="sm"
                :disabled="props.processing"
                data-testid="confirm-delete-submit"
                @click="emit('confirm')"
            >
                <Trash2 class="size-4" /> {{ ACTION.delete }}
            </Button>
        </template>
    </Dialog>
</template>
