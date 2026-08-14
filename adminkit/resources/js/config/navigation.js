import { LayoutDashboard, LayoutGrid, ShieldCheck } from 'lucide-vue-next';

/**
 * Konfigurasi navigasi terpusat.
 *
 * Sidebar dibagi menjadi AREA (dapat ditukar dari header sidebar):
 *   - `member` — pekerjaan harian untuk semua pengguna.
 *   - `admin`  — pengelolaan sistem (khusus role admin).
 *
 * Menu ditambahkan bertahap: fase ini hanya Dashboard.
 */
export const AREAS = [
    {
        id: 'member',
        label: 'Member Area',
        description: 'Pekerjaan harian',
        icon: LayoutGrid,
        adminOnly: false,
        sections: [
            {
                label: 'Umum',
                items: [{ title: 'Dashboard', href: '/', end: true, icon: LayoutDashboard }],
            },
        ],
    },
    {
        id: 'admin',
        label: 'Administrator',
        description: 'Pengelolaan sistem',
        icon: ShieldCheck,
        adminOnly: true,
        sections: [],
    },
];

export const DEFAULT_AREA_ID = 'member';

/** Area yang terlihat untuk pengguna saat ini. */
export const getAreas = (isAdmin) => AREAS.filter((area) => !area.adminOnly || isAdmin);

/** Ambil area berdasarkan id, jatuh ke area default. */
export const getArea = (areaId, isAdmin) => {
    const areas = getAreas(isAdmin);
    return areas.find((a) => a.id === areaId) || areas[0];
};

/** Rute pertama yang bisa dituju dari sebuah area. */
export const firstRouteOf = (area) => area?.sections?.[0]?.items?.[0]?.href || null;

const ROUTE_TRAILS = [
    [/^\/$/, ['Dashboard']],
    [/^\/profile$/, ['Profil Pengguna']],
];

const ADMIN_ROUTES = [];

/** Id area yang memiliki sebuah pathname. */
export const areaIdOf = (pathname) =>
    ADMIN_ROUTES.some((re) => re.test(pathname)) ? 'admin' : 'member';

/** Jejak breadcrumb untuk sebuah pathname. */
export function getBreadcrumb(pathname) {
    const match = ROUTE_TRAILS.find(([pattern]) => pattern.test(pathname));
    return { trail: match ? match[1] : ['Dashboard'] };
}
