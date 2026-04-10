<template>
    <div class="ppms-page ppms-admin-sys">
        <div v-if="loadErr" class="ppms-error">{{ loadErr }}</div>
        <template v-else>
            <section class="ppms-admin-sys-panel" :aria-labelledby="'admin-sys-maint-' + uid">
                <header class="ppms-admin-sys-head">
                    <h2 :id="'admin-sys-maint-' + uid" class="ppms-admin-sys-h2">{{ t('admin.system.modulesTitle') }}</h2>
                    <p class="ppms-admin-sys-lead">{{ t('admin.system.modulesLead') }}</p>
                    <p class="ppms-admin-sys-bypass">
                        {{ t('admin.system.bypassRoles') }}:
                        <code class="ppms-admin-sys-code">{{ bypassRolesText }}</code>
                    </p>
                </header>

                <div v-if="loading" class="ppms-admin-sys-loading">{{ t('common.loading') }}</div>
                <table v-else class="ppms-admin-sys-table" :aria-label="t('admin.system.modulesTitle')">
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

                <p class="ppms-admin-sys-footnote">{{ t('admin.system.footnote') }}</p>
            </section>

            <section class="ppms-admin-sys-links" :aria-label="t('admin.system.moreTitle')">
                <h3 class="ppms-admin-sys-h3">{{ t('admin.system.moreTitle') }}</h3>
                <router-link to="/profile" class="ppms-admin-sys-link">{{ t('admin.system.linkProfileRbac') }}</router-link>
            </section>
        </template>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '../../bootstrap';
import { refreshAppBootstrap } from '../../appBootstrap';

const { t } = useI18n();

const uid = `u${Math.random().toString(36).slice(2, 9)}`;
const loading = ref(true);
const loadErr = ref('');
const rows = ref([]);
const bypassRoles = ref([]);
const savingKey = ref('');

const bypassRolesText = computed(() => bypassRoles.value.join(', ') || '—');

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

onMounted(load);
</script>

<style scoped>
.ppms-admin-sys {
    max-width: 960px;
    margin: 0 auto;
}

.ppms-admin-sys-panel {
    padding: 1.25rem 0 2rem;
}

.ppms-admin-sys-head {
    margin-bottom: 1.25rem;
}

.ppms-admin-sys-h2 {
    margin: 0 0 0.35rem;
    font-size: 1.35rem;
    font-weight: 700;
}

.ppms-admin-sys-h3 {
    margin: 0 0 0.75rem;
    font-size: 1.05rem;
    font-weight: 600;
}

.ppms-admin-sys-lead,
.ppms-admin-sys-bypass {
    margin: 0.25rem 0 0;
    font-size: 0.95rem;
    color: var(--ppms-text-muted, #64748b);
}

.ppms-admin-sys-code {
    font-size: 0.85em;
    padding: 0.1rem 0.35rem;
    border-radius: 4px;
    background: rgba(0, 0, 0, 0.05);
}

.ppms-admin-sys-loading {
    padding: 1rem 0;
    color: var(--ppms-text-muted, #64748b);
}

.ppms-admin-sys-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.95rem;
}

.ppms-admin-sys-table th,
.ppms-admin-sys-table td {
    text-align: left;
    padding: 0.65rem 0.5rem;
    border-bottom: 1px solid var(--ppms-border-subtle, rgba(0, 0, 0, 0.08));
    vertical-align: middle;
}

.ppms-admin-sys-table th {
    font-weight: 600;
    color: var(--ppms-text-muted, #64748b);
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
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
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
}

.ppms-admin-sys-pill--on {
    background: rgba(34, 197, 94, 0.15);
    color: #15803d;
}

.ppms-admin-sys-pill--off {
    background: rgba(245, 158, 11, 0.2);
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
    background: #f59e0b;
}

.ppms-admin-sys-switch input:checked + .ppms-admin-sys-switch-ui::after {
    transform: translateX(20px);
}

.ppms-admin-sys-switch input:focus-visible + .ppms-admin-sys-switch-ui {
    outline: 2px solid var(--ppms-focus, #2563eb);
    outline-offset: 2px;
}

.ppms-admin-sys-footnote {
    margin: 1rem 0 0;
    font-size: 0.88rem;
    color: var(--ppms-text-muted, #64748b);
}

.ppms-admin-sys-links {
    padding: 1rem 0 2rem;
    border-top: 1px solid var(--ppms-border-subtle, rgba(0, 0, 0, 0.08));
}

.ppms-admin-sys-link {
    display: inline-block;
    color: var(--ppms-link, #2563eb);
    text-decoration: underline;
    font-size: 0.95rem;
}
</style>
