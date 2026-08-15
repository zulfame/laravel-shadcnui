import {
    HardDrive,
    LayoutDashboard,
    LayoutGrid,
    Palette,
    ScrollText,
    ShieldCheck,
    UserRound,
    Users2,
} from 'lucide-vue-next';

/**
 * Konfigurasi navigasi terpusat.
 * `perm` = izin yang wajib dimiliki agar item muncul. Penyembunyian menu di
 * sini hanya kosmetik — penegakan sesungguhnya ada di middleware backend.
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
                items: [
                    { title: 'Dashboard', href: '/', end: true, icon: LayoutDashboard, perm: 'dashboard.view' },
                    { title: 'Profil', href: '/profile', icon: UserRound, perm: 'profile.view' },
                ],
            },
        ],
    },
    {
        id: 'admin',
        label: 'Administrator',
        description: 'Pengelolaan sistem',
        icon: ShieldCheck,
        adminOnly: false,
        sections: [
            {
                label: 'Pengaturan',
                items: [
                    { title: 'Penampilan', href: '/appearance', icon: Palette, perm: 'appearance.view' },
                    { title: 'Penyimpanan', href: '/storage-settings', icon: HardDrive, perm: 'storage.view' },
                    { title: 'Peranan', href: '/roles', icon: ShieldCheck, perm: 'roles.view' },
                    { title: 'Pengguna', href: '/users', icon: Users2, perm: 'users.view' },
                    { title: 'Log Aktivitas', href: '/activity', icon: ScrollText, perm: 'activity.view' },
                ],
            },
        ],
    },
];

export const DEFAULT_AREA_ID = 'member';

const filterSections = (area, can) =>
    area.sections
        .map((section) => ({ ...section, items: section.items.filter((i) => !i.perm || can(i.perm)) }))
        .filter((section) => section.items.length > 0);

/** Area yang terlihat untuk pengguna saat ini (item disaring per izin). */
export const getAreas = (can = () => true) =>
    AREAS.map((area) => ({ ...area, sections: filterSections(area, can) }));

/** Ambil area berdasarkan id, jatuh ke area pertama yang punya menu. */
export const getArea = (areaId, can) => {
    const areas = getAreas(can);
    return areas.find((a) => a.id === areaId) || areas.find((a) => a.sections.length > 0) || areas[0];
};

/** Rute pertama yang bisa dituju dari sebuah area. */
export const firstRouteOf = (area) => area?.sections?.[0]?.items?.[0]?.href || null;

const ROUTE_TRAILS = [
    [/^\/$/, ['Dashboard']],
    [/^\/profile$/, ['Profil']],
    [/^\/users$/, ['Pengguna']],
    [/^\/roles$/, ['Peranan']],
    [/^\/appearance$/, ['Penampilan']],
    [/^\/storage-settings$/, ['Penyimpanan']],
    [/^\/activity$/, ['Log Aktivitas']],
];

const ADMIN_ROUTES = [/^\/users/, /^\/roles/, /^\/appearance/, /^\/storage-settings/, /^\/activity/];

/** Id area yang memiliki sebuah pathname. */
export const areaIdOf = (pathname) =>
    ADMIN_ROUTES.some((re) => re.test(pathname)) ? 'admin' : 'member';

/** Jejak breadcrumb untuk sebuah pathname. */
export function getBreadcrumb(pathname) {
    const match = ROUTE_TRAILS.find(([pattern]) => pattern.test(pathname));
    return { trail: match ? match[1] : ['Dashboard'] };
}
