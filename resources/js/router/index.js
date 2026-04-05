import { createRouter, createWebHistory } from 'vue-router';
import { getStoredToken } from '../bootstrap';

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
                    title: 'Dashboard',
                    pageTitle: 'Dashboard',
                    pageDescription: 'Tổng quan dự án, KPI, Innovation funnel và dự án cần chú ý.',
                    breadcrumb: bc([{ label: 'Trang chủ' }]),
                },
            },
            {
                path: 'projects',
                name: 'projects',
                component: () => import('../pages/projects/ProjectList.vue'),
                meta: {
                    title: 'Dự án',
                    pageTitle: 'Dự án',
                    pageDescription: 'Danh sách theo loại, phase, owner và tiến độ (BR-PM).',
                    breadcrumb: bc([
                        { label: 'Trang chủ', to: '/' },
                        { label: 'Dự án' },
                    ]),
                },
            },
            {
                path: 'projects/:id',
                name: 'project-detail',
                component: () => import('../pages/projects/ProjectDetail.vue'),
                props: true,
                meta: {
                    title: 'Chi tiết dự án',
                    hideLayoutTitle: true,
                    breadcrumb: bc([
                        { label: 'Trang chủ', to: '/' },
                        { label: 'Dự án', to: '/projects' },
                        { label: 'Chi tiết' },
                    ]),
                },
            },
            {
                path: 'kpi',
                name: 'kpi',
                component: () => import('../pages/KpiPage.vue'),
                meta: {
                    title: 'KPI',
                    pageTitle: 'KPI Engine',
                    pageDescription: 'Performance, efficiency, SLA, benchmark và snapshot tuần.',
                    breadcrumb: bc([
                        { label: 'Trang chủ', to: '/' },
                        { label: 'KPI' },
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
                    breadcrumb: bc([
                        { label: 'Trang chủ', to: '/' },
                        { label: 'Kaizen' },
                    ]),
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
                    breadcrumb: bc([
                        { label: 'Trang chủ', to: '/' },
                        { label: 'Innovation' },
                    ]),
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
                    breadcrumb: bc([
                        { label: 'Trang chủ', to: '/' },
                        { label: 'Đánh giá 3P' },
                    ]),
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
                    breadcrumb: bc([
                        { label: 'Trang chủ', to: '/' },
                        { label: 'Báo cáo' },
                    ]),
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
                    breadcrumb: bc([
                        { label: 'Trang chủ', to: '/' },
                        { label: 'Thông báo' },
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
    document.title = to.meta.title ? `${to.meta.title} | ${base}` : base;
});

export default router;
