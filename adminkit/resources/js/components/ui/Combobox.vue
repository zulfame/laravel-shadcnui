<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { PopoverAnchor, PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger } from 'reka-ui';
import { Check, ChevronDown, Search } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

/**
 * Combobox — pengganti Select: daftar pilihan DENGAN pencarian.
 * API sengaja dibuat sama dengan Select lama: `modelValue` + `options`
 * ([{ value, label }]) sehingga bisa ditukar langsung.
 */
const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Pilih' },
    searchPlaceholder: { type: String, default: 'Cari...' },
    emptyText: { type: String, default: 'Tidak ada pilihan.' },
    disabled: { type: Boolean, default: false },
    class: { type: null, default: '' },
});
const emit = defineEmits(['update:modelValue']);

defineOptions({ inheritAttrs: false });

const open = ref(false);
const term = ref('');
const searchInput = ref(null);

const selected = computed(() =>
    props.options.find((o) => String(o.value) === String(props.modelValue)),
);

const filtered = computed(() => {
    const q = term.value.trim().toLowerCase();
    if (!q) return props.options;
    return props.options.filter((o) => String(o.label).toLowerCase().includes(q));
});

const choose = (option) => {
    emit('update:modelValue', option.value);
    open.value = false;
};

watch(open, async (isOpen) => {
    term.value = '';
    if (isOpen) {
        await nextTick();
        searchInput.value?.focus();
    }
});
</script>

<template>
    <PopoverRoot v-model:open="open">
        <PopoverAnchor as-child>
            <PopoverTrigger as-child>
                <button
                    type="button"
                    role="combobox"
                    :aria-expanded="open"
                    :disabled="props.disabled"
                    v-bind="$attrs"
                    :class="
                        cn(
                            'flex h-[var(--ctl-h-sm)] w-full items-center justify-between gap-2 rounded-md border border-input bg-transparent px-2.5 text-xs shadow-sm transition-colors hover:bg-accent/40 focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                            props.class,
                        )
                    "
                >
                    <span :class="cn('truncate', !selected && 'text-muted-foreground')">
                        {{ selected?.label ?? props.placeholder }}
                    </span>
                    <ChevronDown class="size-3.5 shrink-0 opacity-50" aria-hidden="true" />
                </button>
            </PopoverTrigger>
        </PopoverAnchor>

        <PopoverPortal>
            <PopoverContent
                align="start"
                :side-offset="4"
                class="z-50 w-[--reka-popper-anchor-width] min-w-[10rem] overflow-hidden rounded-md border bg-popover p-0 text-popover-foreground shadow-md"
            >
                <div class="flex items-center gap-2 border-b px-2.5 py-1.5">
                    <Search class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                    <input
                        ref="searchInput"
                        v-model="term"
                        type="text"
                        :placeholder="props.searchPlaceholder"
                        class="h-6 w-full bg-transparent text-xs outline-none placeholder:text-muted-foreground"
                        data-testid="combobox-search"
                    />
                </div>
                <div class="thin-scroll max-h-56 overflow-y-auto p-1">
                    <button
                        v-for="option in filtered"
                        :key="option.value"
                        type="button"
                        class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-xs transition-colors hover:bg-accent hover:text-accent-foreground"
                        :data-testid="`combobox-option-${option.value}`"
                        @click="choose(option)"
                    >
                        <Check
                            class="size-3.5 shrink-0"
                            :class="String(option.value) === String(props.modelValue) ? 'opacity-100' : 'opacity-0'"
                            aria-hidden="true"
                        />
                        <span class="truncate">{{ option.label }}</span>
                    </button>
                    <p v-if="filtered.length === 0" class="px-2 py-3 text-center text-xs text-muted-foreground">
                        {{ props.emptyText }}
                    </p>
                </div>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>
