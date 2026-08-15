<script setup>
import { computed, ref, watch } from 'vue';
import { PopoverAnchor, PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger } from 'reka-ui';
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

/**
 * DatePicker — pemilih tanggal compact (tanpa dependensi tambahan).
 * `modelValue` memakai format ISO `YYYY-MM-DD`; tampilan mengikuti locale id-ID.
 */
const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Pilih tanggal' },
    disabled: { type: Boolean, default: false },
    class: { type: null, default: '' },
});
const emit = defineEmits(['update:modelValue']);

defineOptions({ inheritAttrs: false });

const DAY_LABELS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
const open = ref(false);

const parsed = computed(() => {
    const d = props.modelValue ? new Date(`${props.modelValue}T00:00:00`) : null;
    return d && !Number.isNaN(d.getTime()) ? d : null;
});

const cursor = ref(parsed.value ?? new Date());
watch(open, (isOpen) => {
    if (isOpen) cursor.value = parsed.value ?? new Date();
});

const label = computed(() =>
    parsed.value
        ? parsed.value.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
        : props.placeholder,
);

const monthLabel = computed(() =>
    cursor.value.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }),
);

const days = computed(() => {
    const year = cursor.value.getFullYear();
    const month = cursor.value.getMonth();
    const first = new Date(year, month, 1);
    const offset = (first.getDay() + 6) % 7; // Senin sebagai awal pekan
    const total = new Date(year, month + 1, 0).getDate();

    const cells = Array.from({ length: offset }, () => null);
    for (let day = 1; day <= total; day += 1) cells.push(new Date(year, month, day));
    return cells;
});

const iso = (date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

const isToday = (date) => iso(date) === iso(new Date());
const isSelected = (date) => iso(date) === props.modelValue;

const shift = (amount) => {
    cursor.value = new Date(cursor.value.getFullYear(), cursor.value.getMonth() + amount, 1);
};

const pick = (date) => {
    emit('update:modelValue', iso(date));
    open.value = false;
};

const clear = () => {
    emit('update:modelValue', '');
    open.value = false;
};
</script>

<template>
    <PopoverRoot v-model:open="open">
        <PopoverAnchor as-child>
            <PopoverTrigger as-child>
                <button
                    type="button"
                    :disabled="props.disabled"
                    v-bind="$attrs"
                    :class="
                        cn(
                            'flex h-[var(--ctl-h)] w-full items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 text-sm shadow-sm transition-colors hover:bg-accent/40 focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                            props.class,
                        )
                    "
                >
                    <span :class="cn('truncate', !parsed && 'text-muted-foreground')">{{ label }}</span>
                    <CalendarDays class="size-4 shrink-0 opacity-60" aria-hidden="true" />
                </button>
            </PopoverTrigger>
        </PopoverAnchor>

        <PopoverPortal>
            <PopoverContent
                align="start"
                :side-offset="4"
                class="z-50 w-[17rem] rounded-md border bg-popover p-3 text-popover-foreground shadow-md"
                data-testid="datepicker-panel"
            >
                <div class="mb-2 flex items-center justify-between">
                    <button
                        type="button"
                        class="rounded-md p-1 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                        aria-label="Bulan sebelumnya"
                        data-testid="datepicker-prev"
                        @click="shift(-1)"
                    >
                        <ChevronLeft class="size-4" />
                    </button>
                    <span class="text-[13px] font-medium capitalize">{{ monthLabel }}</span>
                    <button
                        type="button"
                        class="rounded-md p-1 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                        aria-label="Bulan berikutnya"
                        data-testid="datepicker-next"
                        @click="shift(1)"
                    >
                        <ChevronRight class="size-4" />
                    </button>
                </div>

                <div class="grid grid-cols-7 gap-1 text-center text-[11px] text-muted-foreground">
                    <span v-for="day in DAY_LABELS" :key="day">{{ day }}</span>
                </div>
                <div class="mt-1 grid grid-cols-7 gap-1">
                    <template v-for="(date, index) in days" :key="index">
                        <span v-if="!date" />
                        <button
                            v-else
                            type="button"
                            class="flex size-8 items-center justify-center rounded-md text-xs tabular-nums transition-colors hover:bg-accent"
                            :class="[
                                isSelected(date) && 'bg-primary text-primary-foreground hover:bg-primary',
                                !isSelected(date) && isToday(date) && 'border border-border font-medium',
                            ]"
                            :data-testid="`datepicker-day-${date.getDate()}`"
                            @click="pick(date)"
                        >
                            {{ date.getDate() }}
                        </button>
                    </template>
                </div>

                <div class="mt-2 flex justify-between border-t pt-2">
                    <button
                        type="button"
                        class="text-xs text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                        data-testid="datepicker-clear"
                        @click="clear"
                    >
                        Bersihkan
                    </button>
                    <button
                        type="button"
                        class="text-xs font-medium underline-offset-4 hover:underline"
                        data-testid="datepicker-today"
                        @click="pick(new Date())"
                    >
                        Hari ini
                    </button>
                </div>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>
