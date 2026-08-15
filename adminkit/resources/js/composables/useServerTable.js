import { computed, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const SEARCH_DEBOUNCE = 350;

/**
 * useServerTable — satu tempat untuk state & permintaan tabel sisi server
 * (pencarian debounce, urut, paginasi, filter). Halaman hanya menyediakan
 * url, nilai awal, dan daftar prop yang perlu dimuat ulang.
 */
export function useServerTable({ url, initial = {}, only = [] }) {
    const query = reactive({ search: '', sort: '', dir: 'asc', page: 1, per_page: 10, ...initial });
    const loading = ref(false);
    let timer = null;

    const reload = (delay = 0) => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => {
            loading.value = true;
            router.get(url, { ...query }, {
                only,
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => {
                    loading.value = false;
                },
            });
        }, delay);
    };

    const goFirstPage = () => {
        query.page = 1;
    };

    const onSearch = (value) => {
        query.search = value;
        goFirstPage();
        reload(SEARCH_DEBOUNCE);
    };

    const onSort = ({ key, dir }) => {
        query.sort = key || initial.sort || '';
        query.dir = dir;
        reload();
    };

    const onPage = (page) => {
        query.page = page;
        reload();
    };

    const onPerPage = (value) => {
        query.per_page = value;
        goFirstPage();
        reload();
    };

    /** Filter tambahan; nilai kosong dikirim sebagai 'all'. */
    const onFilter = (key, value) => {
        query[key] = value;
        goFirstPage();
        reload();
    };

    const sortState = computed(() => ({ key: query.sort, dir: query.dir }));

    return { query, loading, reload, onSearch, onSort, onPage, onPerPage, onFilter, sortState };
}
