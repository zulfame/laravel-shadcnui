<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { ImageIcon, Loader2, Upload, X } from 'lucide-vue-next';

import Button from '@/components/ui/Button.vue';
import Label from '@/components/ui/Label.vue';
import { ACTION } from '@/constants/labels';

/**
 * AssetUploader — pratinjau + unggah/hapus satu aset merek.
 * Mengirim langsung ke endpoint agar tiap aset dapat diganti mandiri.
 */
const props = defineProps({
    label: { type: String, required: true },
    hint: { type: String, default: '' },
    assetKey: { type: String, required: true },
    url: { type: String, default: null },
    accept: { type: String, default: 'image/png,image/jpeg,image/webp,image/svg+xml' },
    dark: { type: Boolean, default: false },
});

const input = ref(null);
const form = useForm({ file: null });

const onChange = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    form.file = file;
    form.post(`/appearance/asset/${props.assetKey}`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            if (input.value) input.value.value = '';
        },
    });
};

const remove = () => form.delete(`/appearance/asset/${props.assetKey}`, { preserveScroll: true });
</script>

<template>
    <div class="space-y-[var(--item-gap)]">
        <Label>{{ props.label }}</Label>
        <p v-if="props.hint" class="text-xs text-muted-foreground">{{ props.hint }}</p>
        <div class="flex flex-wrap items-center gap-2">
            <span
                class="flex size-9 items-center justify-center overflow-hidden rounded-md border"
                :class="props.dark ? 'bg-zinc-900' : 'bg-muted/40'"
                :data-testid="`asset-preview-${props.assetKey}`"
            >
                <img v-if="props.url" :src="props.url" alt="" class="size-full object-contain" />
                <ImageIcon v-else class="size-4 text-muted-foreground" aria-hidden="true" />
            </span>
            <input
                ref="input"
                type="file"
                :accept="props.accept"
                class="hidden"
                :data-testid="`asset-input-${props.assetKey}`"
                @change="onChange"
            />
            <Button
                variant="outline"
                size="sm"
                type="button"
                :disabled="form.processing"
                :data-testid="`asset-upload-${props.assetKey}`"
                @click="input?.click()"
            >
                <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                <Upload v-else class="size-4" />
                {{ ACTION.upload }}
            </Button>
            <Button
                v-if="props.url"
                variant="ghost"
                size="sm"
                type="button"
                class="text-destructive hover:text-destructive"
                :data-testid="`asset-remove-${props.assetKey}`"
                @click="remove"
            >
                <X class="size-4" /> {{ ACTION.delete }}
            </Button>
        </div>
        <p v-if="form.errors.file" class="text-xs font-medium text-destructive">{{ form.errors.file }}</p>
    </div>
</template>
