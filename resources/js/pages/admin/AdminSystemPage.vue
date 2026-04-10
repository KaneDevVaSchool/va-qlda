<template>
    <div class="ppms-page ppms-admin-sys">
        <div v-if="loadErr" class="ppms-admin-sys-alert ppms-admin-sys-alert--err" role="alert">{{ loadErr }}</div>
        <template v-else>
            <header class="ppms-admin-sys-hero">
                <div class="ppms-admin-sys-hero-row">
                    <div class="ppms-admin-sys-kicker" aria-hidden="true">{{ t('admin.system.heroBadge') }}</div>
                    <div class="ppms-admin-sys-hero-text">
                        <h1 class="ppms-admin-sys-title">{{ t('admin.system.pageTitle') }}</h1>
                        <p class="ppms-admin-sys-desc">{{ t('admin.system.pageDescription') }}</p>
                    </div>
                </div>
            </header>

            <nav
                class="ppms-admin-sys-tabs"
                role="tablist"
                :aria-label="t('admin.system.tabsAria')"
                @keydown="onTabsKeydown"
            >
                <button
                    id="ppms-admin-tab-maint"
                    type="button"
                    role="tab"
                    class="ppms-admin-sys-tab"
                    :class="{ 'ppms-admin-sys-tab--active': activeTab === 'maintenance' }"
                    :aria-selected="activeTab === 'maintenance'"
                    :tabindex="activeTab === 'maintenance' ? 0 : -1"
                    :aria-controls="'ppms-admin-panel-maint'"
                    @click="setTab('maintenance')"
                >
                    <span class="ppms-admin-sys-tab-text">{{ t('admin.system.tabMaintenance') }}</span>
                </button>
                <button
                    id="ppms-admin-tab-access"
                    type="button"
                    role="tab"
                    class="ppms-admin-sys-tab"
                    :class="{ 'ppms-admin-sys-tab--active': activeTab === 'access' }"
                    :aria-selected="activeTab === 'access'"
                    :tabindex="activeTab === 'access' ? 0 : -1"
                    :aria-controls="'ppms-admin-panel-access'"
                    @click="setTab('access')"
                >
                    <span class="ppms-admin-sys-tab-text">{{ t('admin.system.tabAccess') }}</span>
                </button>
            </nav>

            <div
                v-show="activeTab === 'maintenance'"
                id="ppms-admin-panel-maint"
                class="ppms-admin-sys-panel"
                role="tabpanel"
                :aria-labelledby="'ppms-admin-tab-maint'"
            >
                <section class="ppms-admin-sys-card" :aria-labelledby="'admin-sys-maint-h-' + uid">
                    <header class="ppms-admin-sys-head">
                        <h2 :id="'admin-sys-maint-h-' + uid" class="ppms-admin-sys-h2">{{ t('admin.system.modulesTitle') }}</h2>
                        <p class="ppms-admin-sys-lead">{{ t('admin.system.modulesLead') }}</p>
                        <p class="ppms-admin-sys-bypass">
                            {{ t('admin.system.bypassRoles') }}:
                            <code class="ppms-admin-sys-code">{{ bypassRolesText }}</code>
                        </p>
                    </header>

                    <div v-if="loading" class="ppms-admin-sys-table-wrap" role="status" :aria-label="t('common.loading')">
                        <table class="ppms-admin-sys-table ppms-admin-sys-table--skel">
                            <thead>
                                <tr>
                                    <th scope="col">{{ t('admin.system.colModule') }}</th>
                                    <th scope="col">{{ t('admin.system.colStatus') }}</th>
                                    <th scope="col">{{ t('admin.system.colToggle') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="n in 6" :key="'skel-' + n">
                                    <td><span class="ppms-admin-sys-skel ppms-admin-sys-skel--lg" /></td>
                                    <td><span class="ppms-admin-sys-skel ppms-admin-sys-skel--sm" /></td>
                                    <td><span class="ppms-admin-sys-skel ppms-admin-sys-skel--switch" /></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else-if="!rows.length" class="ppms-admin-sys-empty">
                        <p class="ppms-admin-sys-empty-title">{{ t('admin.system.emptyModulesTitle') }}</p>
                        <p class="ppms-admin-sys-empty-desc">{{ t('admin.system.emptyModulesDesc') }}</p>
                    </div>
                    <div v-else class="ppms-admin-sys-table-wrap">
                        <table class="ppms-admin-sys-table" :aria-label="t('admin.system.modulesTitle')">
                            <thead>
                                <tr>
                                    <th scope="col">{{ t('admin.system.colModule') }}</th>
                                    <th scope="col">{{ t('admin.system.colStatus') }}</th>
                                    <th scope="col">{{ t('admin.system.colToggle') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in rows" :key="row.key">
                                    <td>
                                        <span class="ppms-admin-sys-name">{{ row.label }}</span>
                                        <span class="ppms-admin-sys-key">{{ row.key }}</span>
                                    </td>
                                    <td>
                                        <span
                                            class="ppms-admin-sys-pill"
                                            :class="
                                                row.maintenance ? 'ppms-admin-sys-pill--off' : 'ppms-admin-sys-pill--on'
                                            "
                                        >
                                            {{
                                                row.maintenance
                                                    ? t('admin.system.statusMaintenance')
                                                    : t('admin.system.statusActive')
                                            }}
                                        </span>
                                    </td>
                                    <td>
                                        <label class="ppms-admin-sys-switch">
                                            <input
                                                type="checkbox"
                                                :checked="row.maintenance"
                                                :disabled="savingKey === row.key"
                                                :aria-label="t('admin.system.toggleAria', { name: row.label })"
                                                @change="onToggle(row, $event)"
                                            />
                                            <span class="ppms-admin-sys-switch-ui" aria-hidden="true" />
                                        </label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="ppms-admin-sys-footnote">{{ t('admin.system.footnote') }}</p>
                </section>
            </div>

            <div
                v-show="activeTab === 'access'"
                id="ppms-admin-panel-access"
                class="ppms-admin-sys-panel ppms-admin-sys-panel--access"
                role="tabpanel"
                :aria-labelledby="'ppms-admin-tab-access'"
            >
                <div class="ppms-admin-sys-info-grid">
                    <details class="ppms-admin-sys-details">
                        <summary class="ppms-admin-sys-details-sum">
                            <span class="ppms-admin-sys-details-sum-text">{{ t('admin.system.securityModelTitle') }}</span>
                        </summary>
                        <p class="ppms-admin-sys-details-lead">{{ t('admin.system.securityModelLead') }}</p>
                        <ul class="ppms-admin-sys-bullets">
                            <li>{{ t('admin.system.securityBullet1') }}</li>
                            <li>{{ t('admin.system.securityBullet2') }}</li>
                            <li>{{ t('admin.system.securityBullet3') }}</li>
                            <li>{{ t('admin.system.securityBullet4') }}</li>
                            <li>{{ t('admin.system.securityBullet5') }}</li>
                        </ul>
                    </details>
                    <details class="ppms-admin-sys-details ppms-admin-sys-details--muted">
                        <summary class="ppms-admin-sys-details-sum">
                            <span class="ppms-admin-sys-details-sum-text">{{ t('admin.system.roadmapTitle') }}</span>
                        </summary>
                        <p class="ppms-admin-sys-details-lead">{{ t('admin.system.roadmapLead') }}</p>
                        <ul class="ppms-admin-sys-bullets">
                            <li>{{ t('admin.system.roadmapBullet1') }}</li>
                            <li>{{ t('admin.system.roadmapBullet2') }}</li>
                            <li>{{ t('admin.system.roadmapBullet3') }}</li>
                            <li>{{ t('admin.system.roadmapBullet4') }}</li>
                        </ul>
                    </details>
                </div>
                <div class="ppms-admin-sys-access-shell">
                    <ProfileTabAccessDelegation embedded @refresh="onAccessRefresh" />
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { formatApiUserMessage } from '../../bootstrap';
import { refreshAppBootstrap } from '../../appBootstrap';
import ProfileTabAccessDelegation from '../profile/ProfileTabAccessDelegation.vue';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const uid = `u${Math.random().toString(36).slice(2, 9)}`;
const loading = ref(true);
const loadErr = ref('');
const rows = ref([]);
const bypassRoles = ref([]);
const savingKey = ref('');
const activeTab = ref('maintenance');

const TAB_ORDER = ['maintenance', 'access'];

const bypassRolesText = computed(() => bypassRoles.value.join(', ') || '—');

function syncTabFromRoute() {
    const q = route.query.tab;
    const raw = typeof q === 'string' ? q : '';
    activeTab.value = raw === 'access' ? 'access' : 'maintenance';
}

function setTab(id) {
    activeTab.value = id;
    const query = { ...route.query };
    if (id === 'access') {
        query.tab = 'access';
    } else {
        delete query.tab;
    }
    router.replace({ path: route.path, query });
}

function onTabsKeydown(e) {
    const i = TAB_ORDER.indexOf(activeTab.value);
    if (i < 0) {
        return;
    }
    if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
        e.preventDefault();
        setTab(TAB_ORDER[(i + 1) % TAB_ORDER.length]);
    } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
        e.preventDefault();
        setTab(TAB_ORDER[(i - 1 + TAB_ORDER.length) % TAB_ORDER.length]);
    } else if (e.key === 'Home') {
        e.preventDefault();
        setTab(TAB_ORDER[0]);
    } else if (e.key === 'End') {
        e.preventDefault();
        setTab(TAB_ORDER[TAB_ORDER.length - 1]);
    }
}

watch(
    () => route.query.tab,
    () => {
        syncTabFromRoute();
    },
    { immediate: true },
);

async function load() {
    loading.value = true;
    loadErr.value = '';
    try {
        const { data } = await axios.get('/api/admin/system/modules');
        rows.value = Array.isArray(data.modules) ? data.modules : [];
        bypassRoles.value = Array.isArray(data.maintenance_bypass_roles) ? data.maintenance_bypass_roles : [];
    } catch (e) {
        loadErr.value = formatApiUserMessage(e, t('admin.system.loadError'));
    } finally {
        loading.value = false;
    }
}

async function onToggle(row, event) {
    const maintenance = event.target.checked;
    savingKey.value = row.key;
    try {
        await axios.patch(`/api/admin/system/modules/${encodeURIComponent(row.key)}`, {
            maintenance,
            message: maintenance ? t('admin.system.defaultMaintMessage') : null,
        });
        row.maintenance = maintenance;
        await refreshAppBootstrap();
    } catch (e) {
        event.target.checked = !maintenance;
        loadErr.value = formatApiUserMessage(e, t('admin.system.saveError'));
    } finally {
        savingKey.value = '';
    }
}

async function onAccessRefresh() {
    await refreshAppBootstrap();
}

onMounted(load);
</script>

<style scoped>
.ppms-admin-sys {
    width: 100%;
    max-width: min(1200px, 100%);
    margin: 0 auto;
    box-sizing: border-box;
    padding-bottom: 2rem;
}

.ppms-admin-sys-alert {
    padding: 0.85rem 1rem;
    border-radius: 10px;
    font-size: 0.95rem;
}

.ppms-admin-sys-alert--err {
    background: rgba(220, 38, 38, 0.08);
    color: #b91c1c;
    border: 1px solid rgba(220, 38, 38, 0.2);
}

.ppms-admin-sys-hero {
    margin-bottom: 1.25rem;
    padding: 1rem 1.1rem 1.15rem;
    border-radius: 14px;
    border: 1px solid var(--ppms-border-subtle, rgba(15, 23, 42, 0.08));
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.07) 0%, rgba(248, 250, 252, 0.98) 48%, rgba(255, 255, 255, 1) 100%);
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.ppms-admin-sys-hero-row {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.ppms-admin-sys-kicker {
    flex-shrink: 0;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--ppms-primary, #2563eb);
    padding: 0.35rem 0.55rem;
    border-radius: 8px;
    background: rgba(37, 99, 235, 0.12);
    border: 1px solid rgba(37, 99, 235, 0.2);
}

.ppms-admin-sys-hero-text {
    min-width: 0;
}

.ppms-admin-sys-title {
    margin: 0 0 0.35rem;
    font-size: clamp(1.25rem, 2.5vw, 1.55rem);
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--ppms-text, #0f172a);
}

.ppms-admin-sys-desc {
    margin: 0;
    font-size: 0.95rem;
    line-height: 1.55;
    color: var(--ppms-text-muted, #64748b);
    max-width: 40rem;
}

.ppms-admin-sys-tabs {
    display: flex;
    flex-wrap: nowrap;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
    padding: 0.2rem;
    border-radius: 12px;
    background: rgba(148, 163, 184, 0.1);
    border: 1px solid var(--ppms-border-subtle, rgba(15, 23, 42, 0.1));
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
}

.ppms-admin-sys-tab {
    flex: 1 1 0;
    min-width: min(100%, 10rem);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.55rem 0.85rem;
    border-radius: 10px;
    border: 1px solid transparent;
    background: transparent;
    color: var(--ppms-text-muted, #475569);
    font-size: 0.92rem;
    font-weight: 600;
    cursor: pointer;
    transition:
        background 0.18s ease,
        border-color 0.18s ease,
        color 0.18s ease,
        box-shadow 0.18s ease;
}

.ppms-admin-sys-tab-text {
    text-align: center;
    line-height: 1.3;
}

.ppms-admin-sys-tab:hover {
    color: var(--ppms-text, #0f172a);
    background: rgba(255, 255, 255, 0.55);
}

.ppms-admin-sys-tab--active {
    background: var(--ppms-surface, #fff);
    border-color: rgba(37, 99, 235, 0.35);
    color: var(--ppms-primary, #2563eb);
    box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08);
}

.ppms-admin-sys-tab:focus-visible {
    outline: 2px solid var(--ppms-focus, #2563eb);
    outline-offset: 2px;
}

.ppms-admin-sys-panel {
    min-width: 0;
}

.ppms-admin-sys-panel--access {
    padding-top: 0.15rem;
}

.ppms-admin-sys-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 17rem), 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.ppms-admin-sys-details {
    border-radius: 12px;
    border: 1px solid var(--ppms-border-subtle, rgba(15, 23, 42, 0.1));
    background: linear-gradient(165deg, rgba(37, 99, 235, 0.06), rgba(248, 250, 252, 0.95));
    padding: 0.6rem 0.95rem 0.85rem;
}

.ppms-admin-sys-details--muted {
    background: linear-gradient(165deg, rgba(100, 116, 139, 0.08), rgba(248, 250, 252, 0.96));
}

.ppms-admin-sys-details-sum {
    cursor: pointer;
    list-style: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    width: 100%;
    padding: 0.15rem 0;
    font: inherit;
    text-align: left;
}

.ppms-admin-sys-details-sum::-webkit-details-marker {
    display: none;
}

.ppms-admin-sys-details-sum-text {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--ppms-text, #0f172a);
}

.ppms-admin-sys-details-sum-text::before {
    content: '';
    width: 0.3rem;
    height: 1.05rem;
    border-radius: 2px;
    background: var(--ppms-primary, #2563eb);
    flex-shrink: 0;
}

.ppms-admin-sys-details--muted .ppms-admin-sys-details-sum-text::before {
    background: #64748b;
}

.ppms-admin-sys-details-sum::after {
    content: '';
    width: 0.45rem;
    height: 0.45rem;
    border-right: 2px solid currentColor;
    border-bottom: 2px solid currentColor;
    transform: rotate(45deg);
    transition: transform 0.2s ease;
    flex-shrink: 0;
    opacity: 0.55;
    margin-top: -0.2rem;
}

.ppms-admin-sys-details[open] > .ppms-admin-sys-details-sum::after {
    transform: rotate(225deg);
    margin-top: 0.15rem;
}

.ppms-admin-sys-details[open] > .ppms-admin-sys-details-sum {
    margin-bottom: 0.35rem;
}

.ppms-admin-sys-details-lead {
    margin: 0 0 0.5rem;
    font-size: 0.88rem;
    line-height: 1.45;
    color: var(--ppms-text-muted, #64748b);
}

.ppms-admin-sys-bullets {
    margin: 0;
    padding-left: 1.15rem;
    font-size: 0.88rem;
    line-height: 1.5;
    color: var(--ppms-text, #334155);
}

.ppms-admin-sys-bullets li {
    margin-bottom: 0.35rem;
}

.ppms-admin-sys-bullets li:last-child {
    margin-bottom: 0;
}

.ppms-admin-sys-access-shell {
    padding: 0.85rem 1rem 1.35rem;
    border-radius: 14px;
    border: 1px solid var(--ppms-border-subtle, rgba(15, 23, 42, 0.08));
    background: var(--ppms-surface, #fff);
    box-shadow:
        0 1px 2px rgba(15, 23, 42, 0.04),
        0 4px 24px rgba(15, 23, 42, 0.04);
}

.ppms-admin-sys-access-shell :deep(.ppms-profile-access--embedded) {
    padding-bottom: 0.25rem;
}

.ppms-admin-sys-card {
    padding: 1.25rem 1.35rem 1.75rem;
    border-radius: 14px;
    border: 1px solid var(--ppms-border-subtle, rgba(15, 23, 42, 0.08));
    background: var(--ppms-surface, #fff);
    box-shadow:
        0 1px 2px rgba(15, 23, 42, 0.04),
        0 4px 20px rgba(15, 23, 42, 0.03);
}

.ppms-admin-sys-head {
    margin-bottom: 1.25rem;
}

.ppms-admin-sys-h2 {
    margin: 0 0 0.35rem;
    font-size: 1.15rem;
    font-weight: 700;
}

.ppms-admin-sys-lead,
.ppms-admin-sys-bypass {
    margin: 0.25rem 0 0;
    font-size: 0.95rem;
    color: var(--ppms-text-muted, #64748b);
}

.ppms-admin-sys-code {
    font-size: 0.85em;
    padding: 0.12rem 0.4rem;
    border-radius: 6px;
    background: rgba(15, 23, 42, 0.06);
    border: 1px solid rgba(15, 23, 42, 0.06);
}

.ppms-admin-sys-table-wrap {
    overflow-x: auto;
    margin: 0 -0.25rem;
    padding: 0 0.25rem;
}

.ppms-admin-sys-table {
    width: 100%;
    min-width: 480px;
    border-collapse: collapse;
    font-size: 0.95rem;
}

.ppms-admin-sys-table--skel tbody tr:hover {
    background: transparent;
}

.ppms-admin-sys-table tbody tr {
    transition: background 0.12s ease;
}

.ppms-admin-sys-table tbody tr:hover {
    background: rgba(37, 99, 235, 0.045);
}

.ppms-admin-sys-table th,
.ppms-admin-sys-table td {
    text-align: left;
    padding: 0.72rem 0.65rem;
    border-bottom: 1px solid var(--ppms-border-subtle, rgba(0, 0, 0, 0.07));
    vertical-align: middle;
}

.ppms-admin-sys-table th {
    font-weight: 600;
    color: var(--ppms-text-muted, #64748b);
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.ppms-admin-sys-name {
    display: block;
    font-weight: 600;
}

.ppms-admin-sys-key {
    display: block;
    font-size: 0.8rem;
    color: var(--ppms-text-muted, #64748b);
    font-family: ui-monospace, monospace;
}

.ppms-admin-sys-pill {
    display: inline-block;
    padding: 0.22rem 0.58rem;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
}

.ppms-admin-sys-pill--on {
    background: rgba(34, 197, 94, 0.14);
    color: #15803d;
}

.ppms-admin-sys-pill--off {
    background: rgba(245, 158, 11, 0.18);
    color: #b45309;
}

.ppms-admin-sys-switch {
    position: relative;
    display: inline-flex;
    cursor: pointer;
    align-items: center;
}

.ppms-admin-sys-switch input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.ppms-admin-sys-switch-ui {
    width: 44px;
    height: 24px;
    border-radius: 999px;
    background: #cbd5e1;
    transition: background 0.2s;
    position: relative;
}

.ppms-admin-sys-switch-ui::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    transition: transform 0.2s;
}

.ppms-admin-sys-switch input:checked + .ppms-admin-sys-switch-ui {
    background: linear-gradient(180deg, #fbbf24, #f59e0b);
}

.ppms-admin-sys-switch input:checked + .ppms-admin-sys-switch-ui::after {
    transform: translateX(20px);
}

.ppms-admin-sys-switch input:disabled + .ppms-admin-sys-switch-ui {
    opacity: 0.55;
    cursor: wait;
}

.ppms-admin-sys-switch input:focus-visible + .ppms-admin-sys-switch-ui {
    outline: 2px solid var(--ppms-focus, #2563eb);
    outline-offset: 2px;
}

.ppms-admin-sys-skel {
    display: block;
    height: 0.85rem;
    border-radius: 6px;
    background: linear-gradient(90deg, rgba(148, 163, 184, 0.2) 0%, rgba(148, 163, 184, 0.35) 50%, rgba(148, 163, 184, 0.2) 100%);
    background-size: 200% 100%;
    animation: ppms-admin-skel-shimmer 1.1s ease-in-out infinite;
}

.ppms-admin-sys-skel--lg {
    width: min(100%, 12rem);
}

.ppms-admin-sys-skel--sm {
    width: 4.5rem;
}

.ppms-admin-sys-skel--switch {
    width: 44px;
    height: 24px;
    border-radius: 999px;
}

@keyframes ppms-admin-skel-shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

.ppms-admin-sys-empty {
    text-align: center;
    padding: 2rem 1rem;
    border-radius: 12px;
    border: 1px dashed rgba(148, 163, 184, 0.45);
    background: rgba(248, 250, 252, 0.65);
}

.ppms-admin-sys-empty-title {
    margin: 0 0 0.35rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--ppms-text, #0f172a);
}

.ppms-admin-sys-empty-desc {
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.45;
    color: var(--ppms-text-muted, #64748b);
}

.ppms-admin-sys-footnote {
    margin: 1rem 0 0;
    font-size: 0.86rem;
    line-height: 1.45;
    color: var(--ppms-text-muted, #64748b);
}
</style>
