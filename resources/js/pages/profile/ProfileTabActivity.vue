<template>
    <div>
        <div v-if="loading" class="ppms-profile-skel" role="status">
            <div class="ppms-profile-skel-line" style="width: 40%" />
            <div class="ppms-profile-skel-line" style="width: 85%" />
        </div>
        <template v-else-if="err">{{ err }}</template>
        <template v-else>
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem; align-items: flex-end">
                <label>
                    {{ t('profile.filterFrom') }}<br />
                    <input v-model="filters.from" type="date" style="padding: 0.35rem" />
                </label>
                <label>
                    {{ t('profile.filterTo') }}<br />
                    <input v-model="filters.to" type="date" style="padding: 0.35rem" />
                </label>
                <label>
                    {{ t('profile.filterEvent') }}<br />
                    <input v-model="filters.event" type="text" style="padding: 0.35rem" />
                </label>
                <label>
                    {{ t('common.search') }}<br />
                    <input v-model="filters.q" type="search" style="padding: 0.35rem" />
                </label>
                <button type="button" class="ppms-pf-btn" @click="load(1)">{{ t('common.search') }}</button>
                <button type="button" class="ppms-pf-btn ppms-pf-btn--primary" @click="exportCsv">
                    {{ t('profile.exportCsv') }}
                </button>
            </div>

            <div v-if="!rows.length" class="ppms-profile-empty">{{ t('profile.empty') }}</div>
            <div v-else class="ppms-profile-table-wrap">
                <table class="ppms-profile-table">
                    <thead>
                        <tr>
                            <th>{{ t('profile.activityCols.action') }}</th>
                            <th>{{ t('profile.activityCols.module') }}</th>
                            <th>{{ t('profile.activityCols.ip') }}</th>
                            <th>{{ t('profile.activityCols.device') }}</th>
                            <th>{{ t('profile.activityCols.time') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.id">
                            <td>{{ row.action }}</td>
                            <td>{{ row.module }}</td>
                            <td>{{ row.ip }}</td>
                            <td style="max-width: 220px; overflow: hidden; text-overflow: ellipsis">{{ row.device }}</td>
                            <td>{{ formatTime(row.time) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="meta.total" style="font-size: 0.85rem; color: var(--ppms-pf-muted)">
                Page {{ meta.current_page }} / {{ meta.last_page }} ({{ meta.total }})
            </p>
            <div v-if="meta.last_page > 1" style="display: flex; gap: 0.35rem">
                <button
                    type="button"
                    class="ppms-pf-btn"
                    :disabled="meta.current_page <= 1"
                    @click="load(meta.current_page - 1)"
                >
                    Prev
                </button>
                <button
                    type="button"
                    class="ppms-pf-btn"
                    :disabled="meta.current_page >= meta.last_page"
                    @click="load(meta.current_page + 1)"
                >
                    Next
                </button>
            </div>
        </template>
    </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '../../bootstrap';

const { t } = useI18n();

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

function formatTime(v) {
    if (!v) {
        return '—';
    }
    try {
        return new Date(v).toLocaleString();
    } catch {
        return v;
    }
}

async function load(page = 1) {
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

onMounted(() => load(1));
</script>
