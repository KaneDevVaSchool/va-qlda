<template>
    <div class="ppms-pf-activity">
        <div v-if="loading" class="ppms-profile-skel" role="status">
            <div class="ppms-profile-skel-line" style="width: 40%" />
            <div class="ppms-profile-skel-line" style="width: 85%" />
        </div>
        <template v-else-if="err">{{ err }}</template>
        <template v-else>
            <section class="ppms-pf-activity-filters" :aria-labelledby="'act-filt-' + uid">
                <h3 :id="'act-filt-' + uid" class="ppms-pf-activity-filters__title">{{ t('profile.activityFiltersTitle') }}</h3>
                <div class="ppms-pf-activity-filters__grid">
                    <label class="ppms-pf-field">
                        <span class="ppms-pf-field__lbl">{{ t('profile.filterFrom') }}</span>
                        <input v-model="filters.from" type="date" class="ppms-pf-field__input" />
                    </label>
                    <label class="ppms-pf-field">
                        <span class="ppms-pf-field__lbl">{{ t('profile.filterTo') }}</span>
                        <input v-model="filters.to" type="date" class="ppms-pf-field__input" />
                    </label>
                    <label class="ppms-pf-field ppms-pf-field--grow">
                        <span class="ppms-pf-field__lbl">{{ t('profile.filterEvent') }}</span>
                        <input
                            v-model="filters.event"
                            type="text"
                            class="ppms-pf-field__input"
                            :placeholder="t('profile.filterEvent')"
                        />
                    </label>
                    <label class="ppms-pf-field ppms-pf-field--grow">
                        <span class="ppms-pf-field__lbl">{{ t('common.search') }}</span>
                        <input
                            v-model="filters.q"
                            type="search"
                            class="ppms-pf-field__input"
                            :placeholder="t('common.search')"
                        />
                    </label>
                    <div class="ppms-pf-activity-filters__actions">
                        <button type="button" class="ppms-pf-btn" @click="exportCsv">{{ t('profile.exportCsv') }}</button>
                    </div>
                </div>
            </section>

            <div v-if="!rows.length" class="ppms-profile-empty">{{ t('profile.empty') }}</div>
            <div v-else class="ppms-profile-table-wrap ppms-pf-activity-table-wrap">
                <table class="ppms-profile-table ppms-pf-activity-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ t('profile.activityCols.action') }}</th>
                            <th scope="col">{{ t('profile.activityCols.module') }}</th>
                            <th scope="col">{{ t('profile.activityCols.ip') }}</th>
                            <th scope="col">{{ t('profile.activityCols.device') }}</th>
                            <th scope="col">{{ t('profile.activityCols.time') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.id">
                            <td>
                                <span class="ppms-pf-activity-action" :title="row.action">{{ actionLabel(row.action) }}</span>
                            </td>
                            <td>{{ moduleLabel(row.module) }}</td>
                            <td>{{ row.ip }}</td>
                            <td class="ppms-pf-activity-device">{{ row.device }}</td>
                            <td>{{ formatTime(row.time) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="meta.total" class="ppms-pf-activity-meta">
                {{ t('profile.activityPagination', { current: meta.current_page, last: meta.last_page, total: meta.total }) }}
            </p>
            <div v-if="meta.last_page > 1" class="ppms-pf-activity-pager">
                <button
                    type="button"
                    class="ppms-pf-btn"
                    :disabled="meta.current_page <= 1"
                    @click="load(meta.current_page - 1)"
                >
                    {{ t('profile.activityPrev') }}
                </button>
                <button
                    type="button"
                    class="ppms-pf-btn"
                    :disabled="meta.current_page >= meta.last_page"
                    @click="load(meta.current_page + 1)"
                >
                    {{ t('profile.activityNext') }}
                </button>
            </div>
        </template>
    </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, reactive, ref, unref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '../../bootstrap';

const { t, te, locale } = useI18n();

const uid = `a${Math.random().toString(36).slice(2, 9)}`;

const loading = ref(true);
const err = ref('');
const rows = ref([]);
const meta = reactive({
    current_page: 1,
    last_page: 1,
    total: 0,
});
const filters = reactive({
    from: '',
    to: '',
    event: '',
    q: '',
});

let activityFilterDebounce = null;

function actionLabel(code) {
    if (!code || code === '—') {
        return '—';
    }
    const k = `profile.activityAction.${code}`;
    if (te(k)) {
        return t(k);
    }
    return String(code)
        .replace(/\./g, ' · ')
        .replace(/_/g, ' ');
}

function moduleLabel(mod) {
    if (!mod || mod === '—') {
        return '—';
    }
    const k = `profile.activityModule.${mod}`;
    if (te(k)) {
        return t(k);
    }
    return mod;
}

function formatTime(v) {
    if (!v) {
        return '—';
    }
    try {
        const d = new Date(v);
        const loc = unref(locale) || 'vi';
        return d.toLocaleString(loc === 'en' ? 'en-GB' : 'vi-VN', {
            day: '2-digit',
            month: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    } catch {
        return v;
    }
}

async function load(page = 1) {
    clearTimeout(activityFilterDebounce);
    loading.value = true;
    err.value = '';
    try {
        const { data } = await axios.get('/api/me/activity', {
            params: {
                page,
                per_page: 15,
                from: filters.from || undefined,
                to: filters.to || undefined,
                event: filters.event || undefined,
                q: filters.q || undefined,
            },
        });
        rows.value = data.data || [];
        meta.current_page = data.current_page || 1;
        meta.last_page = data.last_page || 1;
        meta.total = data.total || 0;
    } catch (e) {
        err.value = formatApiUserMessage(e, t('common.loading'));
    } finally {
        loading.value = false;
    }
}

async function exportCsv() {
    try {
        const { data } = await axios.get('/api/me/activity/export.csv', {
            responseType: 'blob',
            params: {
                from: filters.from || undefined,
                to: filters.to || undefined,
                event: filters.event || undefined,
                q: filters.q || undefined,
            },
        });
        const url = URL.createObjectURL(data);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'activity.csv';
        a.click();
        URL.revokeObjectURL(url);
    } catch (e) {
        window.alert(formatApiUserMessage(e, 'Export failed'));
    }
}

function scheduleActivityLoad() {
    clearTimeout(activityFilterDebounce);
    activityFilterDebounce = setTimeout(() => load(1), 400);
}
watch(
    () => [filters.q, filters.event],
    () => {
        scheduleActivityLoad();
    },
);
watch(
    () => [filters.from, filters.to],
    () => {
        clearTimeout(activityFilterDebounce);
        load(1);
    },
);

onMounted(() => load(1));
</script>

<style scoped>
.ppms-pf-activity-filters {
    margin-bottom: 1.25rem;
    padding: 1rem 1.1rem;
    border-radius: 12px;
    border: 1px solid var(--ppms-border-subtle, rgba(15, 23, 42, 0.1));
    background: var(--ppms-surface-2, rgba(248, 250, 252, 0.9));
}
.ppms-pf-activity-filters__title {
    margin: 0 0 0.75rem;
    font-size: 0.8125rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--ppms-pf-muted, #64748b);
}
.ppms-pf-activity-filters__grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem 1rem;
    align-items: flex-end;
}
.ppms-pf-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    min-width: 10rem;
}
.ppms-pf-field--grow {
    flex: 1 1 12rem;
    min-width: 12rem;
}
.ppms-pf-field__lbl {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--ppms-text, #0f172a);
}
.ppms-pf-field__input {
    padding: 0.45rem 0.6rem;
    border-radius: 8px;
    border: 1px solid var(--ppms-border, #e2e8f0);
    font-size: 0.875rem;
    background: #fff;
    width: 100%;
    box-sizing: border-box;
}
.ppms-pf-field__input:focus {
    outline: 2px solid var(--ppms-focus, #2563eb);
    outline-offset: 1px;
}
.ppms-pf-activity-filters__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-left: auto;
}
.ppms-pf-activity-table-wrap {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid var(--ppms-border-subtle, rgba(15, 23, 42, 0.08));
}
.ppms-pf-activity-table th,
.ppms-pf-activity-table td {
    vertical-align: top;
}
.ppms-pf-activity-action {
    font-weight: 600;
    color: var(--ppms-text, #0f172a);
}
.ppms-pf-activity-device {
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.ppms-pf-activity-meta {
    margin: 0.75rem 0 0;
    font-size: 0.85rem;
    color: var(--ppms-pf-muted, #64748b);
}
.ppms-pf-activity-pager {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.75rem;
}
</style>
