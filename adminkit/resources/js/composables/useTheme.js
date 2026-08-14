import { onMounted, ref } from 'vue';

const STORAGE_KEY = 'adminkit.theme';
const theme = ref('system');
const isDark = ref(false);

function resolve(value) {
    return value === 'dark' || (value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
}

function apply(value) {
    isDark.value = resolve(value);
    document.documentElement.classList.toggle('dark', isDark.value);
}

function setTheme(value) {
    theme.value = value;
    try {
        window.localStorage.setItem(STORAGE_KEY, value);
    } catch (e) {
        /* penyimpanan tidak tersedia */
    }
    apply(value);
}

/** Tema terang/gelap/sistem, disimpan di localStorage. */
export function useTheme() {
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

    const toggle = () => setTheme(isDark.value ? 'light' : 'dark');

    return { theme, isDark, setTheme, toggle };
}
