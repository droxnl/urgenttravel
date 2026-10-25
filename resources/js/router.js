import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/',
        name: 'home',
        component: () => import('./views/HomePage.vue'),
    },
    {
        path: '/admin/login',
        name: 'admin-login',
        component: () => import('./views/admin/AdminLogin.vue'),
        meta: { admin: true, guest: true },
    },
    {
        path: '/admin',
        component: () => import('./views/admin/AdminLayout.vue'),
        meta: { admin: true, requiresAuth: true },
        children: [
            { path: '', name: 'admin-dashboard', component: () => import('./views/admin/AdminDashboard.vue') },
            { path: 'pages', name: 'admin-pages', component: () => import('./views/admin/AdminPages.vue') },
            { path: 'pages/:id', name: 'admin-page-edit', component: () => import('./views/admin/AdminPageEdit.vue') },
            { path: 'booking-settings', name: 'admin-booking-settings', component: () => import('./views/admin/AdminBookingSettings.vue') },
            { path: 'settings', name: 'admin-settings', component: () => import('./views/admin/AdminSettings.vue') },
            { path: 'inquiries', name: 'admin-inquiries', component: () => import('./views/admin/AdminInquiries.vue') },
        ],
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'dynamic-page',
        component: () => import('./views/DynamicPage.vue'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) return savedPosition;
        if (to.hash) return { el: to.hash, behavior: 'smooth' };
        return { top: 0 };
    },
});

export default router;
