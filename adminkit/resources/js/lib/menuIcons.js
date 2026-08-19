import { defineAsyncComponent } from 'vue';
import { Folder } from 'lucide-vue-next';

/**
 * Ikon menu memakai nama ikon Lucide apa pun (https://lucide.dev), ditulis
 * kebab-case (`house-wifi`) maupun PascalCase (`HouseWifi`) — alias lama seperti
 * `Users2` juga dikenali. Setiap ikon dimuat sebagai chunk terpisah saat dipakai
 * sehingga seluruh koleksi tersedia tanpa membengkakkan bundel utama.
 */
const loaders = import.meta.glob('/node_modules/lucide-vue-next/dist/esm/icons/*.js');

const normalize = (value) => String(value ?? '').toLowerCase().replace(/[^a-z0-9]/g, '');

const registry = new Map();
for (const [path, loader] of Object.entries(loaders)) {
    registry.set(normalize(path.split('/').pop().replace(/\.js$/, '')), loader);
}

// Beberapa nama lama Lucide berakhiran angka (Users2 → UsersRound).
const ALIASES = { users2: 'usersround', user2: 'userround', shield2: 'shield' };

const loaderOf = (name) => {
    const key = normalize(name).replace(/^lucide/, '').replace(/icon$/, '');

    return registry.get(key) ?? registry.get(ALIASES[key] ?? '') ?? null;
};

const cache = new Map();

/** Komponen ikon untuk sebuah nama; `Folder` bila nama tidak dikenali. */
export const iconOf = (name) => {
    const loader = loaderOf(name);
    if (!loader) return Folder;

    const key = normalize(name);
    if (!cache.has(key)) {
        cache.set(key, defineAsyncComponent({ loader, loadingComponent: Folder }));
    }

    return cache.get(key);
};

/** Apakah nama ikon dikenali Lucide (kosong dianggap sah = ikon bawaan). */
export const isKnownIcon = (name) => !name || Boolean(loaderOf(name));
