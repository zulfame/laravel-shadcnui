import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

const appName = document.querySelector('meta[name="app-name"]')?.content || 'AdminKit';

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    // Halaman dimuat lazy (code-splitting) agar bundle awal tetap kecil.
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.vue');
        return pages[`./pages/${name}.vue`]();
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: { color: 'hsl(var(--foreground))' },
});
