<template>
    <div class="ppms-page">
        <section class="ppms-card ppms-kpi-scope">
            <div class="ppms-kpi-scope-row">
                <label class="ppms-field ppms-field-inline ppms-kpi-scope-field">
                    <span>{{ t('kpiPage.scopeLabel') }}</span>
                    <select v-model="teamId" class="ppms-kpi-team-select" @change="onTeamChange">
                        <option :value="''">{{ t('kpiPage.scopeAll') }}</option>
                        <option v-for="tm in teamOptions" :key="tm.id" :value="String(tm.id)">{{ tm.name }}</option>
                    </select>
                </label>
                <router-link to="/teams" class="ppms-btn-secondary ppms-btn-linkish">{{ t('kpiPage.manageTeams') }}</router-link>
            </div>
            <p class="ppms-muted ppms-mt-sm">{{ t('kpiPage.scopeHint') }}</p>
            <p v-if="scopeBanner" class="ppms-kpi-scope-banner ppms-mt-sm" role="status">{{ scopeBanner }}</p>
        </section>

        <div v-if="canRun" class="ppms-page-toolbar">
            <button type="button" class="ppms-btn-primary" @click="runSnap">{{ t('kpiPage.runSnapshot') }}</button>
        </div>
        <div v-if="loading" class="ppms-loading-line" role="status">{{ t('common.loading') }}</div>
        <template v-else>
            <section class="ppms-card">
                <h2>{{ t('kpiPage.sectionRealtime') }}</h2>
                <ul class="ppms-kv">
                    <li>
                        <span>{{ t('kpiPage.perfPct') }}</span>
                        <strong>{{ cur.person?.performance_pct?.toFixed?.(2) ?? cur.person?.performance_pct }}</strong>
                    </li>
                    <li>
                        <span>{{ t('kpiPage.percentile') }}</span>
                        <strong>{{ cur.person?.performance_percentile ?? '—' }}</strong>
                    </li>
                    <li>
                        <span>{{ t('kpiPage.efficiency') }}</span>
                        <strong>{{ cur.person?.efficiency_ratio ?? '—' }}</strong>
                    </li>
                    <li>
                        <span>{{ t('kpiPage.teamAvgPerf') }}</span>
                        <strong>{{ cur.person?.benchmark_team_avg_performance?.toFixed?.(2) }}</strong>
                    </li>
                    <li>
                        <span>{{ t('kpiPage.slaMaint') }}</span>
                        <strong>{{ cur.person?.sla_rate_pct ?? '—' }}</strong>
                    </li>
                </ul>
                <p v-if="cur.person?.efficiency_warn" class="ppms-error">{{ t('kpiPage.warnEfficiency') }}</p>
                <p v-if="cur.person?.sla_critical" class="ppms-error">{{ t('kpiPage.warnSla') }}</p>
            </section>
            <section v-if="bench" class="ppms-card ppms-mt">
                <h2>{{ t('kpiPage.sectionBenchmark') }}</h2>
                <ul class="ppms-kv ppms-mt-sm">
                    <li>
                        <span>{{ t('kpiPage.yourPerf') }}</span>
                        <strong>{{ bench.your_performance_pct?.toFixed?.(2) ?? bench.your_performance_pct }}</strong>
                    </li>
                    <li>
                        <span>{{ t('kpiPage.yourPercentile') }}</span>
                        <strong>{{ bench.your_percentile ?? '—' }}</strong>
                    </li>
                    <li>
                        <span>{{ t('kpiPage.teamAvg') }}</span>
                        <strong>{{ bench.team_avg?.toFixed?.(2) ?? bench.team_avg }}</strong>
                    </li>
                </ul>
                <h3 class="ppms-mt-sm">{{ t('kpiPage.histTitle') }}</h3>
                <div class="ppms-hist">
                    <div v-for="row in histRows" :key="row.label" class="ppms-hist-row">
                        <span class="ppms-hist-label">{{ row.label }}</span>
                        <div class="ppms-hist-track">
                            <div class="ppms-hist-bar" :style="{ width: `${(row.count / histMax) * 100}%` }" />
                        </div>
                        <span class="ppms-hist-count">{{ row.count }}</span>
                    </div>
                </div>
            </section>
            <section class="ppms-card ppms-mt">
                <h2>{{ t('kpiPage.sectionSnapshots') }}</h2>
                <div class="ppms-table-scroll">
                    <table class="ppms-table">
                        <thead>
                            <tr>
                                <th>{{ t('kpiPage.snapWeek') }}</th>
                                <th>{{ t('kpiPage.snapEntity') }}</th>
                                <th>{{ t('kpiPage.snapMetric') }}</th>
                                <th>{{ t('kpiPage.snapValue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in snaps" :key="s.id">
                                <td>{{ s.week_ending }}</td>
                                <td>{{ s.entity_type }} #{{ s.entity_id }}</td>
                                <td>{{ s.metric_name }}</td>
                                <td>{{ s.value }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { ppmsConfirm, ppmsToastSuccess } from '@/ppmsUi';

const { t } = useI18n();

const loading = ref(true);
const cur = reactive({ person: {}, innovation_funnel: {}, scope: {} });
const snaps = ref([]);
const me = ref(null);
const bench = ref(null);
const teamOptions = ref([]);
const teamId = ref('');

const histRows = computed(() => {
    const h = bench.value?.histogram;
    if (!h || typeof h !== 'object') {
        return [];
    }
    return Object.entries(h).map(([label, count]) => ({ label, count: Number(count) || 0 }));
});

const histMax = computed(() => Math.max(...histRows.value.map((r) => r.count), 1));

const canRun = computed(() => ['admin', 'pm'].includes(me.value?.role));

const scopeBanner = computed(() => {
    const mode = cur.scope?.mode || bench.value?.scope?.mode;
    const name = cur.scope?.team_name || bench.value?.scope?.team_name;
    if (mode === 'team' && name) {
        return t('kpiPage.scopedBanner', { name });
    }
    return '';
});

function kpiParams() {
    const p = {};
    if (teamId.value) {
        p.team_id = teamId.value;
    }
    return { params: p };
}

async function load() {
    loading.value = true;
    try {
        const [u, teams, k, s, b] = await Promise.all([
            axios.get('/api/user'),
            axios.get('/api/teams').catch(() => ({ data: [] })),
            axios.get('/api/kpi/current', kpiParams()),
            axios.get('/api/kpi/snapshots'),
            axios.get('/api/kpi/benchmark', kpiParams()),
        ]);
        me.value = u.data;
        teamOptions.value = Array.isArray(teams.data) ? teams.data : [];
        Object.assign(cur, k.data);
        bench.value = b.data;
        const arr = s.data.data ?? s.data;
        snaps.value = Array.isArray(arr) ? arr.slice(0, 40) : [];
    } finally {
        loading.value = false;
    }
}

function onTeamChange() {
    load();
}

async function runSnap() {
    if (!(await ppmsConfirm(t('kpiPage.snapshotConfirm')))) {
        return;
    }
    await axios.post('/api/kpi/snapshot-run');
    ppmsToastSuccess(t('kpiPage.snapshotDone'));
    await load();
}

onMounted(load);
</script>

<style scoped>
.ppms-kpi-scope-row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 1rem;
    justify-content: space-between;
}
.ppms-kpi-scope-field {
    flex: 1 1 14rem;
}
.ppms-kpi-team-select {
    min-width: 12rem;
}
.ppms-btn-linkish {
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.ppms-kpi-scope-banner {
    font-weight: 600;
}
</style>
