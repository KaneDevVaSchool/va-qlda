<template>
    <div class="ppms-profile">
        <div v-if="loadErr" class="ppms-error">{{ loadErr }}</div>
        <template v-else>
            <header class="ppms-profile-header">
                <div class="ppms-profile-header-avatar" aria-hidden="true">
                    <img
                        v-if="header.avatar_url"
                        :src="header.avatar_url"
                        alt=""
                        width="56"
                        height="56"
                        style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%"
                    />
                    <span v-else>{{ initial }}</span>
                </div>
                <div class="ppms-profile-header-main">
                    <h2 class="ppms-profile-header-name">{{ header.name || '—' }}</h2>
                    <div class="ppms-profile-header-meta">
                        <span
                            class="ppms-profile-role-badge"
                            :class="roleBadgeClass(header.role)"
                            >{{ roleLabel(header.role) }}</span
                        >
                        <span class="ppms-profile-status-pill">{{ t('profile.statusOnline') }}</span>
                    </div>
                </div>
            </header>

            <div class="ppms-profile-tabs-wrap">
                <div class="ppms-profile-tabs" role="tablist" :aria-label="t('profile.pageTitle')">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        role="tab"
                        class="ppms-profile-tab"
                        :class="{ 'ppms-profile-tab--active': activeTab === tab.id }"
                        :aria-selected="activeTab === tab.id"
                        @click="setTab(tab.id)"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <section class="ppms-profile-panel" :aria-label="activeTab">
                <component :is="tabComponent" :key="activeTab" @refresh="loadHeader" />
            </section>
        </template>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, defineAsyncComponent, onMounted, ref, shallowRef, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { formatApiUserMessage } from '../../bootstrap';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const loadErr = ref('');
const header = ref({
    name: '',
    email: '',
    role: '',
    avatar_url: null,
});
const TAB_ALIAS = { permissions: 'access' };

const tabs = computed(() => [
    { id: 'profile', label: t('profile.navProfile') },
    { id: 'security', label: t('profile.navSecurity') },
    { id: 'devices', label: t('profile.navDevices') },
    { id: 'access', label: t('profile.navAccessDelegation') },
    { id: 'activity', label: t('profile.navActivity') },
]);

const activeTab = ref('profile');

const tabLoaders = {
    profile: () => import('./ProfileTabProfile.vue'),
    security: () => import('./ProfileTabSecurity.vue'),
    devices: () => import('./ProfileTabDevices.vue'),
    access: () => import('./ProfileTabAccessDelegation.vue'),
    activity: () => import('./ProfileTabActivity.vue'),
};

const tabComponent = shallowRef(
    defineAsyncComponent({
        loader: tabLoaders.profile,
        delay: 0,
    }),
);

const initial = computed(() => {
    const n = header.value?.name?.trim();
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
    const tr = t(key);
    return tr === key ? role : tr;
}

function roleBadgeClass(role) {
    const r = (role || '').toLowerCase();
    if (!r) {
        return 'ppms-profile-role-badge--unassigned';
    }
    return `ppms-profile-role-badge--${r}`;
}

function setTab(id) {
    activeTab.value = id;
    tabComponent.value = defineAsyncComponent({
        loader: tabLoaders[id] || tabLoaders.profile,
        delay: 0,
    });
    router.replace({ query: { ...route.query, tab: id } });
}

async function loadHeader() {
    loadErr.value = '';
    try {
        const { data } = await axios.get('/api/me/profile');
        header.value = data;
    } catch (e) {
        loadErr.value = formatApiUserMessage(e, t('common.loading'));
    }
}

watch(
    () => route.query.tab,
    (tab) => {
        const raw = typeof tab === 'string' ? tab : '';
        const id = TAB_ALIAS[raw] || raw;
        if (id && tabLoaders[id]) {
            activeTab.value = id;
            tabComponent.value = defineAsyncComponent({
                loader: tabLoaders[id],
                delay: 0,
            });
        }
    },
    { immediate: true },
);

loadHeader();

onMounted(() => {
    try {
        document.documentElement.classList.remove('ppms-profile-dark');
        localStorage.removeItem('ppms-profile-dark');
    } catch {
        /* ignore */
    }
});
</script>

<style scoped>
.ppms-error {
    padding: 1rem;
    color: #b91c1c;
}
</style>
