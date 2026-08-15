<script setup>
import Input from '@/components/ui/Input.vue';

/**
 * PhoneInput — hanya menerima angka, boleh satu tanda "+" di awal.
 * Karakter lain ditolak saat ketik maupun tempel (paste).
 */
const props = defineProps({
    modelValue: { type: String, default: '' },
    id: { type: String, default: undefined },
    placeholder: { type: String, default: '' },
    testid: { type: String, default: undefined },
    class: { type: null, default: '' },
});
const emit = defineEmits(['update:modelValue', 'blur']);

const sanitize = (value) => {
    const plus = value.trimStart().startsWith('+') ? '+' : '';

    return plus + value.replace(/[^0-9]/g, '').slice(0, 15);
};

const onInput = (value) => emit('update:modelValue', sanitize(String(value)));
</script>

<template>
    <Input
        :id="props.id"
        :model-value="props.modelValue"
        :placeholder="props.placeholder"
        type="tel"
        inputmode="tel"
        maxlength="16"
        :class="props.class"
        :data-testid="props.testid"
        @update:model-value="onInput"
        @blur="emit('blur')"
    />
</template>
