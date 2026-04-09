<template>
    <div class="ppms-app" :class="{ 'ppms-app--nav-open': mobileNavOpen }">
        <a href="#ppms-main" class="ppms-skip-link">{{ t('common.skipToContent') }}</a>

        <header class="ppms-app-header">
            <div class="ppms-app-header-inner">
                <button
                    type="button"
                    class="ppms-icon-btn-header ppms-app-nav-toggle"
                    :aria-expanded="mobileNavOpen"
                    aria-controls="ppms-sidebar-nav"
                    :aria-label="mobileNavOpen ? t('layout.closeMenu') : t('layout.toggleMenu')"
                    @click="toggleMobileNav"
                >
                    <span v-if="!mobileNavOpen" class="ppms-icon-menu" aria-hidden="true" />
                    <span v-else class="ppms-icon-close" aria-hidden="true" />
                </button>
                <router-link to="/" class="ppms-app-brand-link">
                    <span class="ppms-app-header-mascot-wrap" aria-hidden="true">
                        <VaMascotImg img-class="ppms-app-header-mascot" alt="" />
                    </span>
                    <span class="ppms-app-brand">
                        <strong>PPMS</strong>
                        <span>VA Schools</span>
                    </span>
                </router-link>
                <div class="ppms-app-header-spacer" />
                <div class="ppms-locale-switch" role="group" :aria-label="t('common.language')">
                    <button
                        type="button"
                        class="ppms-locale-btn"
                        :class="{ 'ppms-locale-btn--active': locale === 'vi' }"
                        :title="t('common.localeVi')"
                        @click="setLocale('vi')"
                    >
                        VI
                    </button>
                    <button
                        type="button"
                        class="ppms-locale-btn"
                        :class="{ 'ppms-locale-btn--active': locale === 'en' }"
                        :title="t('common.localeEn')"
                        @click="setLocale('en')"
                    >
                        EN
                    </button>
                </div>
                <NotificationDropdown :unread="unread" @refresh="refreshUnread" />
            </div>
        </header>

        <div class="ppms-app-middle ppms-shell">
            <div
                class="ppms-sidebar-backdrop"
                aria-hidden="true"
                @click="closeMobileNav"
            />
            <aside
                id="ppms-sidebar-nav"
                class="ppms-sidebar"
                :class="{ 'ppms-sidebar--collapsed': sidebarCollapsed }"
                :aria-hidden="sidebarAriaHidden"
            >
                <nav class="ppms-nav ppms-nav--sidebar" :aria-label="t('common.mainNav')">
                    <div class="ppms-nav-group">
                        <div class="ppms-nav-label">{{ t('layout.navOverview') }}</div>
                        <router-link to="/" :title="t('layout.navDashboard')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                    <polyline points="9 22 9 12 15 12 15 22" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navDashboard') }}</span>
                        </router-link>
                    </div>
                    <div class="ppms-nav-group">
                        <div class="ppms-nav-label">{{ t('layout.navModuleOps') }}</div>
                        <router-link to="/projects" :title="t('layout.navProjects')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <path
                                        d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"
                                    />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navProjects') }}</span>
                        </router-link>
                        <router-link to="/kpi" :title="t('layout.navKpi')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <line x1="18" y1="20" x2="18" y2="10" />
                                    <line x1="12" y1="20" x2="12" y2="4" />
                                    <line x1="6" y1="20" x2="6" y2="14" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navKpi') }}</span>
                        </router-link>
                        <router-link to="/teams" :title="t('layout.navTeams')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navTeams') }}</span>
                        </router-link>
                        <router-link to="/reports" :title="t('layout.navReports')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                    <line x1="16" y1="13" x2="8" y2="13" />
                                    <line x1="16" y1="17" x2="8" y2="17" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navReports') }}</span>
                        </router-link>
                    </div>
                    <div class="ppms-nav-group">
                        <div class="ppms-nav-label">{{ t('layout.navModuleSupplier') }}</div>
                        <router-link to="/contracts" :title="t('layout.navContracts')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                    <line x1="16" y1="13" x2="8" y2="13" />
                                    <line x1="16" y1="17" x2="8" y2="17" />
                                    <polyline points="10 9 9 9 8 9" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navContracts') }}</span>
                        </router-link>
                    </div>
                    <div class="ppms-nav-group">
                        <div class="ppms-nav-label">{{ t('layout.navModuleInnovation') }}</div>
                        <router-link to="/kaizens" :title="t('layout.navKaizen')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <path d="M12 2v4M6.34 6.34l2.83 2.83M2 12h4M6.34 17.66l2.83-2.83M12 22v-4M17.66 17.66l-2.83-2.83M22 12h-4M17.66 6.34l-2.83 2.83" />
                                    <circle cx="12" cy="12" r="4" fill="none" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navKaizen') }}</span>
                        </router-link>
                        <router-link to="/innovation" :title="t('layout.navInnovation')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="2" fill="none" />
                                    <path d="M12 2v4M12 18v4M2 12h4M18 12h4" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navInnovation') }}</span>
                        </router-link>
                    </div>
                    <div class="ppms-nav-group">
                        <div class="ppms-nav-label">{{ t('layout.navModulePeople') }}</div>
                        <router-link to="/evaluations" :title="t('layout.navEvaluations')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navEvaluations') }}</span>
                        </router-link>
                        <router-link to="/notifications" :title="t('layout.navNotifications')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navNotifications') }}</span>
                            <span v-if="unread > 0" class="ppms-badge">{{ unread > 9 ? '9+' : String(unread) }}</span>
                        </router-link>
                        <router-link to="/profile" :title="t('layout.navAccount')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navAccount') }}</span>
                        </router-link>
                    </div>
                </nav>

                <div class="ppms-sidebar-collapse-zone">
                    <div class="ppms-sidebar-divider" aria-hidden="true" />
                    <button
                        type="button"
                        class="ppms-sidebar-collapse-toggle"
                        :aria-expanded="!sidebarCollapsed"
                        :aria-label="sidebarCollapsed ? t('layout.expandSidebar') : t('layout.collapseSidebar')"
                        @click="toggleSidebarCollapsed"
                    >
                        <span class="ppms-sidebar-collapse-face">
                            <svg
                                class="ppms-sidebar-collapse-svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <template v-if="!sidebarCollapsed">
                                    <polyline points="11 6 6 12 11 18" />
                                    <polyline points="18 6 13 12 18 18" />
                                </template>
                                <template v-else>
                                    <polyline points="6 6 11 12 6 18" />
                                    <polyline points="13 6 18 12 13 18" />
                                </template>
                            </svg>
                        </span>
                    </button>
                </div>

                <div class="ppms-user">
                    <div class="ppms-user-card">
                        <div class="ppms-user-avatar" aria-hidden="true">{{ userInitial }}</div>
                        <div class="ppms-user-meta">
                            <span class="ppms-user-name">{{ user?.name }}</span>
                            <span class="ppms-user-role">{{ roleLabel(user?.role) }}</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="ppms-btn-ghost ppms-btn-block ppms-mt-sm ppms-sidebar-logout-btn"
                        :title="t('layout.logout')"
                        @click="logout"
                    >
                        <span class="ppms-nav-ico-wrap" aria-hidden="true">
                            <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                <polyline points="16 17 21 12 16 7" />
                                <line x1="21" y1="12" x2="9" y2="12" />
                            </svg>
                        </span>
                        <span class="ppms-nav-text">{{ t('layout.logout') }}</span>
                    </button>
                </div>
            </aside>

            <div class="ppms-stage">
                <div class="ppms-content-header">
                    <nav class="ppms-breadcrumb" :aria-label="t('common.breadcrumb')">
                        <template v-for="(c, i) in crumbs" :key="i">
                            <span v-if="i > 0" class="ppms-bc-sep" aria-hidden="true">/</span>
                            <router-link v-if="c.to" :to="c.to">{{ c.labelKey ? t(c.labelKey) : c.label }}</router-link>
                            <span v-else class="ppms-bc-current">{{ c.labelKey ? t(c.labelKey) : c.label }}</span>
                        </template>
                    </nav>
                    <template v-if="!route.meta.hideLayoutTitle">
                        <h1 v-if="pageTitle" class="ppms-content-title">{{ pageTitle }}</h1>
                        <p v-if="pageDescription" class="ppms-content-desc">{{ pageDescription }}</p>
                    </template>
                </div>

                <main
                    id="ppms-main"
                    class="ppms-main"
                    :class="{ 'ppms-main--flush': route.meta.flushContent }"
                    tabindex="-1"
                >
                    <router-view />
                </main>
            </div>
        </div>

        <nav class="ppms-mobile-tabbar" :aria-label="t('layout.mobileTabNav')">
            <router-link
                to="/"
                class="ppms-mobile-tab"
                :class="{ 'ppms-mobile-tab--active': route.name === 'dashboard' }"
            >
                <span class="ppms-mobile-tab-ico" aria-hidden="true">
                    <svg class="ppms-mobile-tab-svg" viewBox="0 0 24 24">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <polyline points="9 22 9 12 15 12 15 22" />
                    </svg>
                </span>
                <span class="ppms-mobile-tab-label">{{ t('layout.navDashboard') }}</span>
            </router-link>
            <router-link
                to="/projects"
                class="ppms-mobile-tab"
                :class="{ 'ppms-mobile-tab--active': route.path.startsWith('/projects') }"
            >
                <span class="ppms-mobile-tab-ico" aria-hidden="true">
                    <svg class="ppms-mobile-tab-svg" viewBox="0 0 24 24">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                    </svg>
                </span>
                <span class="ppms-mobile-tab-label">{{ t('layout.navProjects') }}</span>
            </router-link>
            <router-link
                to="/notifications"
                class="ppms-mobile-tab ppms-mobile-tab--notify"
                :class="{ 'ppms-mobile-tab--active': route.name === 'notifications' }"
            >
                <span class="ppms-mobile-tab-ico" aria-hidden="true">
                    <svg class="ppms-mobile-tab-svg" viewBox="0 0 24 24">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                    </svg>
                    <span v-if="unread > 0" class="ppms-mobile-tab-badge">{{ unread > 9 ? '9+' : unread }}</span>
                </span>
                <span class="ppms-mobile-tab-label">{{ t('layout.navNotifications') }}</span>
            </router-link>
            <router-link
                to="/profile"
                class="ppms-mobile-tab"
                :class="{ 'ppms-mobile-tab--active': route.name === 'profile' }"
            >
                <span class="ppms-mobile-tab-ico" aria-hidden="true">
                    <svg class="ppms-mobile-tab-svg" viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                </span>
                <span class="ppms-mobile-tab-label">{{ t('layout.navAccount') }}</span>
            </router-link>
        </nav>

        <VaSiteFooter />
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import VaMascotImg from '../components/VaMascotImg.vue';
import VaSiteFooter from '../components/VaSiteFooter.vue';
import { useI18n } from 'vue-i18n';
import { setAuthToken } from '../bootstrap';
import { persistLocale } from '../i18n';
import { ppmsConfirm } from '../ppmsUi';

const { locale, t } = useI18n();

const route = useRoute();
const router = useRouter();
const user = ref(null);
const unread = ref(0);
const PPMS_SIDEBAR_COLLAPSED = 'ppms-sidebar-collapsed';
const sidebarCollapsed = ref(false);
const mobileNavOpen = ref(false);
const isMobileLayout = ref(
    typeof window !== 'undefined' && window.matchMedia('(max-width: 1023px)').matches,
);

const sidebarAriaHidden = computed(() =>
    isMobileLayout.value && !mobileNavOpen.value ? true : undefined,
);

const crumbs = computed(() => route.meta.breadcrumb ?? [{ labelKey: 'common.home' }]);
const pageTitle = computed(() =>
    route.meta.pageTitleKey ? t(route.meta.pageTitleKey) : (route.meta.pageTitle ?? ''),
);
const pageDescription = computed(() =>
    route.meta.pageDescriptionKey ? t(route.meta.pageDescriptionKey) : (route.meta.pageDescription ?? ''),
);

const userInitial = computed(() => {
    const n = user.value?.name?.trim();
    if (!n) {
        return '?';
    }
    return n.charAt(0).toUpperCase();
});

function roleLabel(role) {
    if (!role) {
        return '—';
    }
    const key = `layout.role.${role}`;
    const translated = t(key);
    return translated === key ? role : translated;
}

function setLocale(code) {
    locale.value = code;
    persistLocale(code);
}

function toggleSidebarCollapsed() {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    if (typeof localStorage !== 'undefined') {
        localStorage.setItem(PPMS_SIDEBAR_COLLAPSED, sidebarCollapsed.value ? '1' : '0');
    }
}

function updateMobileLayoutFlag() {
    if (typeof window === 'undefined') {
        return;
    }
    isMobileLayout.value = window.matchMedia('(max-width: 1023px)').matches;
}

function toggleMobileNav() {
    mobileNavOpen.value = !mobileNavOpen.value;
}

function closeMobileNav() {
    mobileNavOpen.value = false;
}

function onEscapeKey(e) {
    if (e.key === 'Escape' && mobileNavOpen.value) {
        e.preventDefault();
        closeMobileNav();
    }
}

function onDesktopBreakpoint(e) {
    if (e.matches) {
        closeMobileNav();
    }
}

watch(mobileNavOpen, (open) => {
    if (typeof document === 'undefined') {
        return;
    }
    document.body.classList.toggle('ppms-drawer-open', open);
});

watch(
    () => route.fullPath,
    () => {
        closeMobileNav();
    },
);

async function refreshUnread() {
    try {
        const { data } = await axios.get('/api/activity-feed/unread-count');
        unread.value = typeof data.unread === 'number' ? data.unread : 0;
    } catch {
        unread.value = 0;
    }
}

let pollId;
let mqlDesktop;

onMounted(async () => {
    updateMobileLayoutFlag();
    window.addEventListener('resize', updateMobileLayoutFlag);
    window.addEventListener('keydown', onEscapeKey);
    mqlDesktop = window.matchMedia('(min-width: 1024px)');
    mqlDesktop.addEventListener('change', onDesktopBreakpoint);

    if (typeof localStorage !== 'undefined' && localStorage.getItem(PPMS_SIDEBAR_COLLAPSED) === '1') {
        sidebarCollapsed.value = true;
    }
    try {
        const { data } = await axios.get('/api/user');
        user.value = data;
        await refreshUnread();
        pollId = setInterval(refreshUnread, 60000);
    } catch {
        setAuthToken(null);
        router.push({ name: 'login' });
    }
});

onUnmounted(() => {
    if (pollId) {
        clearInterval(pollId);
    }
    window.removeEventListener('resize', updateMobileLayoutFlag);
    window.removeEventListener('keydown', onEscapeKey);
    if (mqlDesktop) {
        mqlDesktop.removeEventListener('change', onDesktopBreakpoint);
    }
    if (typeof document !== 'undefined') {
        document.body.classList.remove('ppms-drawer-open');
    }
});

async function logout() {
    if (!(await ppmsConfirm(t('layout.logoutConfirm'), { title: t('layout.logoutTitle') }))) {
        return;
    }
    try {
        await axios.post('/api/logout');
    } catch {
        /* ignore */
    }
    setAuthToken(null);
    router.push({ name: 'login' });
}
</script>
