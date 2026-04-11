import { createRouter, createWebHistory } from 'vue-router';
import { getStoredToken } from '../bootstrap';
import { appBootstrapState, loadAppBootstrap, shouldBlockModuleRoute } from '../appBootstrap';
import { i18n } from '../i18n';

const bc = (items) => items;

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../pages/Login.vue'),
        meta: { guest: true, title: 'Đăng nhập' },
    },
    {
        path: '/',
        component: () => import('../layouts/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: () => import('../pages/Dashboard.vue'),
                meta: {
                    moduleKey: 'dashboard',
                    titleKey: 'dashboard.pageTitle',
                    pageTitleKey: 'dashboard.pageTitle',
                    pageDescriptionKey: 'dashboard.pageDescription',
                    breadcrumb: bc([{ labelKey: 'common.home' }]),
                },
            },
            {
                path: 'maintenance',
                name: 'module-maintenance',
                component: () => import('../pages/ModuleMaintenancePage.vue'),
                meta: {
                    skipMaintenanceGuard: true,
                    titleKey: 'maintenance.pageTitle',
                    pageTitleKey: 'maintenance.pageTitle',
                    pageDescriptionKey: 'maintenance.pageDescription',
                    hideLayoutTitle: true,
                    flushContent: true,
                    breadcrumb: bc([
                        { labelKey: 'common.home', to: '/' },
                        { labelKey: 'maintenance.breadcrumb' },
                    ]),
                },
            },
            {
                path: 'admin',
                name: 'admin-system',
                component: () => import('../pages/admin/AdminSystemPage.vue'),
                meta: {
                    skipMaintenanceGuard: true,
                    titleKey: 'admin.system.pageTitle',
                    pageTitleKey: 'admin.system.pageTitle',
                    pageDescriptionKey: 'admin.system.pageDescription',
                    flushContent: true,
                    breadcrumb: bc([
                        { labelKey: 'common.home', to: '/' },
                        { labelKey: 'admin.system.breadcrumb' },
                    ]),
                },
            },
            {
                path: 'projects',
                name: 'projects',
                component: () => import('../pages/projects/ProjectList.vue'),
                meta: {
                    moduleKey: 'projects',
                    titleKey: 'projects.pageTitle',
                    pageTitleKey: 'projects.pageTitle',
                    breadcrumb: bc([
                        { labelKey: 'common.home', to: '/' },
                        { labelKey: 'projects.breadcrumb' },
                    ]),
                },
            },
            {
                path: 'projects/:id',
                name: 'project-detail',
                component: () => import('../pages/projects/ProjectDetail.vue'),
                props: true,
                meta: {
                    moduleKey: 'projects',
                    titleKey: 'projects.detailTitle',
                    hideLayoutTitle: true,
                    breadcrumb: bc([
                        { labelKey: 'common.home', to: '/' },
                        { labelKey: 'projects.breadcrumb', to: '/projects' },
                        { labelKey: 'projects.breadcrumbDetail' },
                    ]),
                },
            },
            {
                path: 'teams',
                name: 'teams',
                component: () => import('../pages/TeamsPage.vue'),
                meta: {
                    moduleKey: 'teams',
                    titleKey: 'layout.navTeams',
                    pageTitleKey: 'teams.title',
                    pageDescriptionKey: 'teams.subtitle',
                    breadcrumb: bc([
                        { labelKey: 'common.home', to: '/' },
                        { labelKey: 'layout.navTeams' },
                    ]),
                },
            },
            {
                path: 'vendors',
                name: 'vendors',
                component: () => import('../pages/vendors/VendorList.vue'),
                meta: {
                    moduleKey: 'vendors',
                    titleKey: 'vendors.pageTitle',
                    pageTitleKey: 'vendors.pageTitle',
                    pageDescriptionKey: 'vendors.pageDescription',
                    hideLayoutTitle: true,
                    breadcrumb: bc([
                        { labelKey: 'common.home', to: '/' },
                        { labelKey: 'vendors.breadcrumb' },
                    ]),
                },
            },
            {
                path: 'vendors/:id',
                name: 'vendor-detail',
                component: () => import('../pages/vendors/VendorDetail.vue'),
                props: true,
                meta: {
                    moduleKey: 'vendors',
                    titleKey: 'vendors.detailTitle',
                    hideLayoutTitle: true,
                    breadcrumb: bc([
                        { labelKey: 'common.home', to: '/' },
                        { labelKey: 'vendors.breadcrumb', to: '/vendors' },
                        { labelKey: 'vendors.detailTitle' },
                    ]),
                },
            },
            {
                path: 'contracts',
                name: 'contracts',
                component: () => import('../pages/contracts/ContractList.vue'),
                meta: {
                    moduleKey: 'contracts',
                    titleKey: 'contracts.pageTitle',
                    pageTitleKey: 'contracts.pageTitle',
                    pageDescriptionKey: 'contracts.pageDescription',
                    breadcrumb: bc([
                        { labelKey: 'common.home', to: '/' },
                        { labelKey: 'layout.navModuleSupplier' },
                        { labelKey: 'contracts.breadcrumb' },
                    ]),
                },
            },
            {
                path: 'contracts/:id',
                name: 'contract-detail',
                component: () => import('../pages/contracts/ContractDetail.vue'),
                props: true,
                meta: {
                    moduleKey: 'contracts',
                    titleKey: 'contracts.detailTitle',
                    hideLayoutTitle: true,
                    breadcrumb: bc([
                        { labelKey: 'common.home', to: '/' },
                        { labelKey: 'layout.navModuleSupplier' },
                        { labelKey: 'contracts.breadcrumb', to: '/contracts' },
                        { labelKey: 'contracts.detailTitle' },
                    ]),
                },
            },
            {
                path: 'kaizens',
                name: 'kaizens',
                component: () => import('../pages/kaizen/KaizenList.vue'),
                meta: {
                    moduleKey: 'kaizens',
                    titleKey: 'kaizenPage.pageTitle',
                    pageTitleKey: 'kaizenPage.pageTitle',
                    pageDescriptionKey: 'kaizenPage.pageDescription',
                    breadcrumb: bc([
                        { labelKey: 'common.home', to: '/' },
                        { labelKey: 'layout.navKaizen' },
                    ]),
                },
            },
            {
                path: 'innovation',
                name: 'innovation',
                component: () => import('../pages/InnovationList.vue'),
                meta: {
                    moduleKey: 'innovation',
                    titleKey: 'innovationPage.pageTitle',
                    pageTitleKey: 'innovationPage.pageTitle',
                    pageDescriptionKey: 'innovationPage.pageDescription',
                    breadcrumb: bc([
                        { labelKey: 'common.home', to: '/' },
                        { labelKey: 'layout.navInnovation' },
                    ]),
                },
            },
            {
                path: 'evaluations',
                name: 'evaluations',
                component: () => import('../pages/evaluations/EvaluationList.vue'),
                meta: {
                    moduleKey: 'evaluations',
                    titleKey: 'evaluationsPage.pageTitle',
                    pageTitleKey: 'evaluationsPage.pageTitle',
                    pageDescriptionKey: 'evaluationsPage.pageDescription',
                    breadcrumb: bc([
                        { labelKey: 'common.home', to: '/' },
                        { labelKey: 'layout.navEvaluations' },
                    ]),
                },
            },
            {
                path: 'notifications',
                name: 'notifications',
                component: () => import('../pages/Notifications.vue'),
                meta: {
                    moduleKey: 'notifications',
                    titleKey: 'activityFeed.pageTitle',
                    pageTitleKey: 'activityFeed.pageTitle',
                    pageDescriptionKey: 'activityFeed.pageDescription',
                    breadcrumb: bc([
                        { labelKey: 'common.home', to: '/' },
                        { labelKey: 'layout.navNotifications' },
                    ]),
                },
            },
            {
                path: 'profile',
                name: 'profile',
                component: () => import('../pages/profile/ProfileDashboard.vue'),
                meta: {
                    titleKey: 'profile.pageTitle',
                    pageTitleKey: 'profile.pageTitle',
                    pageDescriptionKey: 'profile.pageDescription',
                    flushContent: true,
                    breadcrumb: bc([
                        { labelKey: 'common.home', to: '/' },
                        { labelKey: 'profile.breadcrumb' },
                    ]),
                },
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0, left: 0, behavior: 'smooth' };
    },
});

router.beforeEach(async (to, from, next) => {
    const token = getStoredToken();
    if (to.meta.requiresAuth && !token) {
        next({ name: 'login', query: { redirect: to.fullPath } });

        return;
    }
    if (to.meta.guest && token) {
        next({ name: 'dashboard' });

        return;
    }

    if (token && to.meta.requiresAuth) {
        try {
            await loadAppBootstrap();
        } catch {
            next({ name: 'login', query: { redirect: to.fullPath } });

            return;
        }

        if (to.name === 'admin-system') {
            const s = appBootstrapState();
            if (!s.rbac.can_manage) {
                next({ name: 'dashboard' });

                return;
            }
        }

        if (!to.meta.skipMaintenanceGuard && shouldBlockModuleRoute(to.meta.moduleKey)) {
            next({
                name: 'module-maintenance',
                query: { module: to.meta.moduleKey || '' },
                replace: true,
            });

            return;
        }
    }

    next();
});

router.afterEach((to) => {
    const base = 'PPMS — VA Schools';
    const title = to.meta.titleKey ? i18n.global.t(to.meta.titleKey) : to.meta.title;
    document.title = title ? `${title} | ${base}` : base;
});

export default router;
