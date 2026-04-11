<template>
    <div class="ppms-app" :class="{ 'ppms-app--nav-open': mobileNavOpen }">
        <a href="#ppms-main" class="ppms-skip-link">{{ t('common.skipToContent') }}</a>

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
                        <AppSidebarNavEntry module-key="dashboard" to="/" :title="t('layout.navDashboard')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                    <polyline points="9 22 9 12 15 12 15 22" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navDashboard') }}</span>
                            <span v-if="navMaint('dashboard')" class="ppms-nav-maint-pill">{{ t('layout.moduleMaintBadge') }}</span>
                        </AppSidebarNavEntry>
                    </div>
                    <div class="ppms-nav-group">
                        <div class="ppms-nav-label">{{ t('layout.navModuleOps') }}</div>
                        <AppSidebarNavEntry module-key="projects" to="/projects" :title="t('layout.navProjects')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <path
                                        d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"
                                    />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navProjects') }}</span>
                            <span v-if="navMaint('projects')" class="ppms-nav-maint-pill">{{ t('layout.moduleMaintBadge') }}</span>
                        </AppSidebarNavEntry>
                        <AppSidebarNavEntry module-key="teams" to="/teams" :title="t('layout.navTeams')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navTeams') }}</span>
                            <span v-if="navMaint('teams')" class="ppms-nav-maint-pill">{{ t('layout.moduleMaintBadge') }}</span>
                        </AppSidebarNavEntry>
                    </div>
                    <div class="ppms-nav-group">
                        <div class="ppms-nav-label">{{ t('layout.navModuleSupplier') }}</div>
                        <AppSidebarNavEntry module-key="vendors" to="/vendors" :title="t('layout.navVendors')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navVendors') }}</span>
                            <span v-if="navMaint('vendors')" class="ppms-nav-maint-pill">{{ t('layout.moduleMaintBadge') }}</span>
                        </AppSidebarNavEntry>
                        <AppSidebarNavEntry module-key="contracts" to="/contracts" :title="t('layout.navContracts')">
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
                            <span v-if="navMaint('contracts')" class="ppms-nav-maint-pill">{{ t('layout.moduleMaintBadge') }}</span>
                        </AppSidebarNavEntry>
                    </div>
                    <div class="ppms-nav-group">
                        <div class="ppms-nav-label">{{ t('layout.navModuleInnovation') }}</div>
                        <AppSidebarNavEntry module-key="kaizens" to="/kaizens" :title="t('layout.navKaizen')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <path d="M12 2v4M6.34 6.34l2.83 2.83M2 12h4M6.34 17.66l2.83-2.83M12 22v-4M17.66 17.66l-2.83-2.83M22 12h-4M17.66 6.34l-2.83 2.83" />
                                    <circle cx="12" cy="12" r="4" fill="none" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navKaizen') }}</span>
                            <span v-if="navMaint('kaizens')" class="ppms-nav-maint-pill">{{ t('layout.moduleMaintBadge') }}</span>
                        </AppSidebarNavEntry>
                        <AppSidebarNavEntry module-key="innovation" to="/innovation" :title="t('layout.navInnovation')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="2" fill="none" />
                                    <path d="M12 2v4M12 18v4M2 12h4M18 12h4" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navInnovation') }}</span>
                            <span v-if="navMaint('innovation')" class="ppms-nav-maint-pill">{{ t('layout.moduleMaintBadge') }}</span>
                        </AppSidebarNavEntry>
                    </div>
                    <div class="ppms-nav-group">
                        <div class="ppms-nav-label">{{ t('layout.navModuleReviewsComms') }}</div>
                        <AppSidebarNavEntry module-key="evaluations" to="/evaluations" :title="t('layout.navEvaluations')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M5 11h4v10H5V11zm7 0h4V4h-4v17zm7 0h4v-6h-4v6z" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navEvaluations') }}</span>
                            <span v-if="navMaint('evaluations')" class="ppms-nav-maint-pill">{{ t('layout.moduleMaintBadge') }}</span>
                        </AppSidebarNavEntry>
                        <AppSidebarNavEntry module-key="notifications" to="/notifications" :title="t('layout.navNotifications')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navNotifications') }}</span>
                            <span v-if="navMaint('notifications')" class="ppms-nav-maint-pill">{{ t('layout.moduleMaintBadge') }}</span>
                            <span v-if="unread > 0" class="ppms-badge">{{ unread > 9 ? '9+' : String(unread) }}</span>
                        </AppSidebarNavEntry>
                    </div>
                    <div class="ppms-nav-group">
                        <div class="ppms-nav-label">{{ t('layout.navModuleAccountAdmin') }}</div>
                        <router-link to="/profile" :title="t('layout.navAccount')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navAccount') }}</span>
                        </router-link>
                        <router-link v-if="canManageAdmin" to="/admin" :title="t('layout.navAdmin')">
                            <span class="ppms-nav-ico-wrap" aria-hidden="true">
                                <svg class="ppms-nav-ico-svg" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="3" />
                                    <path
                                        d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"
                                    />
                                </svg>
                            </span>
                            <span class="ppms-nav-text">{{ t('layout.navAdmin') }}</span>
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
                <div v-if="isMobileLayout" class="ppms-app-mobile-strip">
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
                v-if="!navMobileDisabled('dashboard')"
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
            <span
                v-else
                class="ppms-mobile-tab ppms-mobile-tab--disabled"
                role="presentation"
                :title="t('layout.navDisabledMaint')"
            >
                <span class="ppms-mobile-tab-ico" aria-hidden="true">
                    <svg class="ppms-mobile-tab-svg" viewBox="0 0 24 24">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <polyline points="9 22 9 12 15 12 15 22" />
                    </svg>
                </span>
                <span class="ppms-mobile-tab-label">{{ t('layout.navDashboard') }}</span>
            </span>
            <router-link
                v-if="!navMobileDisabled('projects')"
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
            <span
                v-else
                class="ppms-mobile-tab ppms-mobile-tab--disabled"
                role="presentation"
                :title="t('layout.navDisabledMaint')"
            >
                <span class="ppms-mobile-tab-ico" aria-hidden="true">
                    <svg class="ppms-mobile-tab-svg" viewBox="0 0 24 24">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                    </svg>
                </span>
                <span class="ppms-mobile-tab-label">{{ t('layout.navProjects') }}</span>
            </span>
            <router-link
                v-if="!navMobileDisabled('evaluations')"
                to="/evaluations"
                class="ppms-mobile-tab"
                :class="{ 'ppms-mobile-tab--active': route.name === 'evaluations' }"
            >
                <span class="ppms-mobile-tab-ico" aria-hidden="true">
                    <svg class="ppms-mobile-tab-svg" viewBox="0 0 24 24">
                        <path d="M5 11h4v10H5V11zm7 0h4V4h-4v17zm7 0h4v-6h-4v6z" />
                    </svg>
                </span>
                <span class="ppms-mobile-tab-label">{{ t('layout.navEvaluations') }}</span>
            </router-link>
            <span
                v-else
                class="ppms-mobile-tab ppms-mobile-tab--disabled"
                role="presentation"
                :title="t('layout.navDisabledMaint')"
            >
                <span class="ppms-mobile-tab-ico" aria-hidden="true">
                    <svg class="ppms-mobile-tab-svg" viewBox="0 0 24 24">
                        <path d="M5 11h4v10H5V11zm7 0h4V4h-4v17zm7 0h4v-6h-4v6z" />
                    </svg>
                </span>
                <span class="ppms-mobile-tab-label">{{ t('layout.navEvaluations') }}</span>
            </span>
            <router-link
                v-if="!navMobileDisabled('notifications')"
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
            <span
                v-else
                class="ppms-mobile-tab ppms-mobile-tab--notify ppms-mobile-tab--disabled"
                role="presentation"
                :title="t('layout.navDisabledMaint')"
            >
                <span class="ppms-mobile-tab-ico" aria-hidden="true">
                    <svg class="ppms-mobile-tab-svg" viewBox="0 0 24 24">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                    </svg>
                    <span v-if="unread > 0" class="ppms-mobile-tab-badge">{{ unread > 9 ? '9+' : unread }}</span>
                </span>
                <span class="ppms-mobile-tab-label">{{ t('layout.navNotifications') }}</span>
            </span>
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
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import AppSidebarNavEntry from '../components/AppSidebarNavEntry.vue';
import { useI18n } from 'vue-i18n';
import {
    appBootstrapState,
    canBypassModuleMaintenance,
    isModuleUnderMaintenance,
    resetAppBootstrap,
} from '../appBootstrap';
import { setAuthToken } from '../bootstrap';
import { ppmsConfirm } from '../ppmsUi';

const { t } = useI18n();

const route = useRoute();
const router = useRouter();
const bootstrap = appBootstrapState();
const canManageAdmin = computed(() => !!bootstrap.rbac.can_manage);

function navMaint(moduleKey) {
    return isModuleUnderMaintenance(moduleKey);
}

/** Tabbar mobile: mục bảo trì (không bypass) — hiện nhưng không bấm được. */
function navMobileDisabled(moduleKey) {
    if (!moduleKey) {
        return false;
    }
    if (!isModuleUnderMaintenance(moduleKey)) {
        return false;
    }
    return !canBypassModuleMaintenance();
}

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

const userInitial = computed(() => {
    const n = user.value?.name?.trim();
    if (!n) {
        return '?';
    }
    return n.charAt(0).toUpperCase();
});

function roleLabel(role) {
    if (!role) {
        return t('layout.role.unassigned');
    }
    const key = `layout.role.${role}`;
    const translated = t(key);
    return translated === key ? role : translated;
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
    resetAppBootstrap();
    setAuthToken(null);
    router.push({ name: 'login' });
}
</script>

<style scoped>
.ppms-app-mobile-strip {
    display: flex;
    align-items: center;
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid var(--ppms-border, #e2e8f0);
    background: var(--ppms-surface, #fff);
}
</style>
