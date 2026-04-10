<template>
    <div class="ppms-page ppms-maint-page">
        <div class="ppms-maint-card">
            <div class="ppms-maint-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    <path d="M12 8v4M12 16h.01" stroke-linecap="round" />
                </svg>
            </div>
            <h2 class="ppms-maint-title">{{ t('maintenance.pageTitle') }}</h2>
            <p class="ppms-maint-module">
                <strong>{{ moduleLabel }}</strong>
            </p>
            <p v-if="detailMessage" class="ppms-maint-msg">{{ detailMessage }}</p>
            <p v-else class="ppms-maint-hint">{{ t('maintenance.hintDefault') }}</p>
            <div class="ppms-maint-actions">
                <router-link to="/" class="ppms-pf-btn ppms-pf-btn--primary">{{ t('maintenance.backHome') }}</router-link>
                <router-link v-if="canManage" to="/admin" class="ppms-pf-btn ppms-pf-btn--ghost">{{
                    t('maintenance.openAdmin')
                }}</router-link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { appBootstrapState, isModuleUnderMaintenance } from '../appBootstrap';

const { t } = useI18n();
const route = useRoute();

const state = appBootstrapState();

const moduleKey = computed(() => (typeof route.query.module === 'string' ? route.query.module : '') || '');

const moduleLabel = computed(() => {
    const k = moduleKey.value;
    if (!k) {
        return t('maintenance.unknownModule');
    }
    const cat = state.moduleCatalog[k];
    if (cat && typeof cat.label === 'string') {
        return cat.label;
    }
    return k;
});

const detailMessage = computed(() => {
    const k = moduleKey.value;
    if (!k || !isModuleUnderMaintenance(k)) {
        return '';
    }
    return state.moduleMaintenance[k]?.message || '';
});

const canManage = computed(() => state.rbac.can_manage);
</script>

<style scoped>
.ppms-maint-page {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 42vh;
    padding: 1.5rem;
}

.ppms-maint-card {
    max-width: 28rem;
    text-align: center;
    padding: 2rem 1.5rem;
    border-radius: 12px;
    border: 1px solid var(--ppms-border-subtle, rgba(0, 0, 0, 0.08));
    background: var(--ppms-surface-elevated, #fff);
    box-shadow: 0 8px 28px rgba(15, 23, 42, 0.06);
}

.ppms-maint-icon {
    color: var(--ppms-amber-600, #d97706);
    margin-bottom: 0.75rem;
}

.ppms-maint-title {
    margin: 0 0 0.5rem;
    font-size: 1.25rem;
    font-weight: 700;
}

.ppms-maint-module {
    margin: 0 0 1rem;
    font-size: 1rem;
    color: var(--ppms-text-muted, #64748b);
}

.ppms-maint-msg,
.ppms-maint-hint {
    margin: 0 0 1.25rem;
    line-height: 1.5;
    font-size: 0.95rem;
}

.ppms-maint-msg {
    color: var(--ppms-text, #0f172a);
}

.ppms-maint-hint {
    color: var(--ppms-text-muted, #64748b);
}

.ppms-maint-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    justify-content: center;
}
</style>
