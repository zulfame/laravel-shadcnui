<script setup>
import { ref } from 'vue';
import { Eye, EyeOff } from 'lucide-vue-next';
import Input from '@/components/ui/Input.vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    id: { type: String, default: undefined },
    testid: { type: String, default: undefined },
    class: { type: null, default: '' },
});
defineEmits(['update:modelValue']);

const visible = ref(false);
</script>

<template>
    <div class="relative">
        <Input
            :id="props.id"
            :model-value="props.modelValue"
            :type="visible ? 'text' : 'password'"
            :placeholder="props.placeholder"
            autocomplete="off"
            :class="cn('pr-9', props.class)"
            :data-testid="props.testid"
            @update:model-value="$emit('update:modelValue', $event)"
        />
        <button
            type="button"
            class="absolute right-1 top-1/2 -translate-y-1/2 rounded-md p-1.5 text-muted-foreground transition-colors hover:text-foreground"
            :aria-label="visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
            :data-testid="props.testid ? `${props.testid}-toggle` : undefined"
            @click="visible = !visible"
        >
            <EyeOff v-if="visible" class="size-4" />
            <Eye v-else class="size-4" />
        </button>
    </div>
</template>
