import { createRouter, createWebHistory } from 'vue-router';
import { getStoredToken } from '../bootstrap';
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
                    titleKey: 'dashboard.pageTitle',
                    pageTitleKey: 'dashboard.pageTitle',
                    pageDescriptionKey: 'dashboard.pageDescription',
                    breadcrumb: bc([{ labelKey: 'common.home' }]),
                },
            },
            {
                path: 'projects',
                name: 'projects',
                component: () => import('../pages/projects/ProjectList.vue'),
                meta: {
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
                path: 'kpi',
                name: 'kpi',
                component: () => import('../pages/KpiPage.vue'),
                meta: {
                    titleKey: 'layout.navKpi',
                    pageTitleKey: 'layout.navKpi',
                    pageDescriptionKey: 'kpiPage.scopeHint',
                    breadcrumb: bc([
                        { labelKey: 'common.home', to: '/' },
                        { labelKey: 'layout.navKpi' },
                    ]),
                },
            },
            {
                path: 'teams',
                name: 'teams',
                component: () => import('../pages/TeamsPage.vue'),
                meta: {
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
                path: 'kaizens',
                name: 'kaizens',
                component: () => import('../pages/kaizen/KaizenList.vue'),
                meta: {
                    title: 'Kaizen',
                    pageTitle: 'Kaizen',
                    pageDescription: 'Cải tiến liên tục, leaderboard và nhắc tuần.',
                    breadcrumb: bc([{ label: 'Trang chủ', to: '/' }, { label: 'Kaizen' }]),
                },
            },
            {
                path: 'innovation',
                name: 'innovation',
                component: () => import('../pages/InnovationList.vue'),
                meta: {
                    title: 'Innovation',
                    pageTitle: 'Innovation (R&D)',
                    pageDescription: 'Ý tưởng Type 3 — funnel Submitted → POC → Applied.',
                    breadcrumb: bc([{ label: 'Trang chủ', to: '/' }, { label: 'Innovation' }]),
                },
            },
            {
                path: 'evaluations',
                name: 'evaluations',
                component: () => import('../pages/evaluations/EvaluationList.vue'),
                meta: {
                    title: 'Đánh giá 3P',
                    pageTitle: 'Đánh giá 3P',
                    pageDescription: 'P1 / P2 / P3, peer review và xuất PDF.',
                    breadcrumb: bc([{ label: 'Trang chủ', to: '/' }, { label: 'Đánh giá 3P' }]),
                },
            },
            {
                path: 'reports',
                name: 'reports',
                component: () => import('../pages/ReportsPage.vue'),
                meta: {
                    title: 'Báo cáo',
                    pageTitle: 'Báo cáo & export',
                    pageDescription: 'Weekly PDF, CSV dự án và tổng hợp Kaizen impact.',
                    breadcrumb: bc([{ label: 'Trang chủ', to: '/' }, { label: 'Báo cáo' }]),
                },
            },
            {
                path: 'notifications',
                name: 'notifications',
                component: () => import('../pages/Notifications.vue'),
                meta: {
                    title: 'Thông báo',
                    pageTitle: 'Thông báo',
                    pageDescription: 'Thông báo trong ứng dụng theo BR-NT.',
                    breadcrumb: bc([{ label: 'Trang chủ', to: '/' }, { label: 'Thông báo' }]),
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

router.beforeEach((to, from, next) => {
    const token = getStoredToken();
    if (to.meta.requiresAuth && !token) {
        next({ name: 'login', query: { redirect: to.fullPath } });

        return;
    }
    if (to.meta.guest && token) {
        next({ name: 'dashboard' });

        return;
    }
    next();
});

router.afterEach((to) => {
    const base = 'PPMS — VA Schools';
    const title = to.meta.titleKey ? i18n.global.t(to.meta.titleKey) : to.meta.title;
    document.title = title ? `${title} | ${base}` : base;
});

export default router;
