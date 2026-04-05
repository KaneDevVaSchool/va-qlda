<template>
    <div class="ppms-page pbi-report">
        <div v-if="loading" class="pbi-loading" role="status">
            <span class="pbi-loading-bar" aria-hidden="true" />
            {{ t('dashboard.loading') }}
        </div>
        <div v-else-if="err" class="ppms-error">{{ err }}</div>
        <div v-else class="pbi-canvas">
            <header class="pbi-report-header">
                <div class="pbi-report-title-block">
                    <h2 class="pbi-report-title">{{ t('dashboard.reportTitle') }}</h2>
                    <p class="pbi-report-sub">{{ t('dashboard.reportSubtitle') }}</p>
                </div>
                <div class="pbi-report-meta">
                    <span class="pbi-refresh-label">{{ t('dashboard.lastRefreshed') }}</span>
                    <time class="pbi-refresh-time" :datetime="refreshedIso">{{ refreshedDisplay }}</time>
                </div>
            </header>

            <section class="pbi-kpi-row" :aria-label="t('dashboard.reportTitle')">
                <article v-for="card in kpiCards" :key="card.id" class="pbi-kpi-card" :title="card.hint">
                    <span class="pbi-kpi-label">{{ card.label }}</span>
                    <span class="pbi-kpi-value">{{ formatInt(card.value) }}</span>
                    <span class="pbi-kpi-hint">{{ card.hint }}</span>
                </article>
            </section>

            <section class="pbi-visual-row pbi-visual-row--2">
                <article class="pbi-visual">
                    <header class="pbi-visual-header">
                        <div class="pbi-visual-accent" aria-hidden="true" />
                        <div class="pbi-visual-heading">
                            <h3 class="pbi-visual-title">{{ t('dashboard.visual.projectTypes') }}</h3>
                            <p class="pbi-visual-sub">{{ t('dashboard.visual.projectTypesSub') }}</p>
                        </div>
                    </header>
                    <div class="pbi-visual-body">
                        <div v-if="hasProjectTypeData" class="pbi-chart-host">
                            <canvas ref="typeChartRef" height="260" />
                        </div>
                        <p v-else class="pbi-visual-empty">{{ t('dashboard.empty.projectTypes') }}</p>
                    </div>
                </article>

                <article class="pbi-visual">
                    <header class="pbi-visual-header">
                        <div class="pbi-visual-accent" aria-hidden="true" />
                        <div class="pbi-visual-heading">
                            <h3 class="pbi-visual-title">{{ t('dashboard.visual.kpiTrend') }}</h3>
                            <p class="pbi-visual-sub">{{ t('dashboard.visual.kpiTrendSub') }}</p>
                        </div>
                    </header>
                    <div class="pbi-visual-body">
                        <div v-if="hasKpiTrend" class="pbi-chart-host">
                            <canvas ref="kpiChartRef" height="260" />
                        </div>
                        <p v-else class="pbi-visual-empty">{{ t('dashboard.visual.kpiTrendEmpty') }}</p>
                    </div>
                </article>
            </section>

            <article class="pbi-visual pbi-visual--wide pbi-mt">
                <header class="pbi-visual-header">
                    <div class="pbi-visual-accent" aria-hidden="true" />
                    <div class="pbi-visual-heading">
                        <h3 class="pbi-visual-title">{{ t('dashboard.funnel.title') }}</h3>
                    </div>
                </header>
                <div class="pbi-funnel-metrics">
                    <div class="pbi-funnel-metric">
                        <span class="pbi-funnel-metric-label">{{ t('dashboard.funnel.submitted') }}</span>
                        <span class="pbi-funnel-metric-value">{{
                            formatInt(summary.innovation_funnel?.submitted ?? 0)
                        }}</span>
                    </div>
                    <div class="pbi-funnel-metric">
                        <span class="pbi-funnel-metric-label">{{ t('dashboard.funnel.poc') }}</span>
                        <span class="pbi-funnel-metric-value">{{
                            formatInt(summary.innovation_funnel?.poc ?? 0)
                        }}</span>
                    </div>
                    <div class="pbi-funnel-metric">
                        <span class="pbi-funnel-metric-label">{{ t('dashboard.funnel.applied') }}</span>
                        <span class="pbi-funnel-metric-value">{{
                            formatInt(summary.innovation_funnel?.applied ?? 0)
                        }}</span>
                    </div>
                </div>
            </article>

            <section class="pbi-matrix-row pbi-mt">
                <article v-for="block in matrixBlocks" :key="block.titleKey" class="pbi-visual pbi-visual--matrix">
                    <header class="pbi-visual-header pbi-visual-header--compact">
                        <h3 class="pbi-visual-title pbi-visual-title--sm">{{ t(block.titleKey) }}</h3>
                    </header>
                    <ul class="pbi-matrix-list">
                        <li v-for="row in block.rows" :key="row.key">
                            <span>{{ row.label }}</span>
                            <strong>{{ formatInt(row.value) }}</strong>
                        </li>
                    </ul>
                </article>
            </section>

            <article class="pbi-visual pbi-visual--wide pbi-mt">
                <header class="pbi-visual-header">
                    <div class="pbi-visual-accent" aria-hidden="true" />
                    <div class="pbi-visual-heading">
                        <h3 class="pbi-visual-title">{{ t('dashboard.table.attentionTitle') }}</h3>
                    </div>
                </header>
                <div class="pbi-visual-body pbi-table-body">
                    <div v-if="summary.at_risk_projects.length" class="ppms-table-scroll">
                        <table class="ppms-table pbi-table">
                            <thead>
                                <tr>
                                    <th>{{ t('dashboard.table.name') }}</th>
                                    <th>{{ t('dashboard.table.type') }}</th>
                                    <th>{{ t('dashboard.table.status') }}</th>
                                    <th>{{ t('dashboard.table.progress') }}</th>
                                    <th>{{ t('dashboard.table.pm') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in summary.at_risk_projects" :key="p.id">
                                    <td>
                                        <router-link :to="'/projects/' + p.id">{{ p.name }}</router-link>
                                    </td>
                                    <td>{{ typeLabel(p.type) }}</td>
                                    <td>{{ statusLabel(p.status) }}</td>
                                    <td>{{ Number(p.progress).toFixed(1) }}</td>
                                    <td>{{ p.owner?.name }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="pbi-visual-empty pbi-visual-empty--pad">
                        {{ t('dashboard.table.attentionEmpty') }}
                    </p>
                </div>
            </article>

            <article class="pbi-visual pbi-visual--wide pbi-mt">
                <header class="pbi-visual-header">
                    <div class="pbi-visual-accent" aria-hidden="true" />
                    <div class="pbi-visual-heading">
                        <h3 class="pbi-visual-title">{{ t('dashboard.table.workloadTitle') }}</h3>
                    </div>
                </header>
                <div class="pbi-visual-body pbi-table-body">
                    <div v-if="summary.workload_open_tasks.length" class="ppms-table-scroll">
                        <table class="ppms-table pbi-table">
                            <thead>
                                <tr>
                                    <th>{{ t('dashboard.table.member') }}</th>
                                    <th>{{ t('dashboard.table.openTasks') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in summary.workload_open_tasks" :key="row.assignee_id">
                                    <td>{{ row.assignee_name }}</td>
                                    <td>{{ formatInt(row.open_tasks) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="pbi-visual-empty pbi-visual-empty--pad">
                        {{ t('dashboard.table.workloadEmpty') }}
                    </p>
                </div>
            </article>
        </div>
    </div>
</template>

<script setup>
import {
    ArcElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js';
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { getApiErrorMessage } from '@/bootstrap';

Chart.register(
    DoughnutController,
    LineController,
    ArcElement,
    CategoryScale,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Title,
    Tooltip,
    Filler,
);

const { t, locale } = useI18n();

const loading = ref(true);
const err = ref('');
const lastRefreshedAt = ref(null);
const typeChartRef = ref(null);
const kpiChartRef = ref(null);
let typeChart;
let kpiChart;

const summary = reactive({
    projects: { by_type: {}, by_status: {}, by_phase: {} },
    kaizen_by_status: {},
    workload_open_tasks: [],
    at_risk_projects: [],
    kpi_performance_trend: [],
    innovation_funnel: {},
});

const numberLocale = computed(() => (locale.value === 'en' ? 'en-GB' : 'vi-VN'));

const refreshedIso = computed(() => (lastRefreshedAt.value ? lastRefreshedAt.value.toISOString() : ''));

const refreshedDisplay = computed(() => {
    if (!lastRefreshedAt.value) {
        return '—';
    }
    return new Intl.DateTimeFormat(numberLocale.value, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(lastRefreshedAt.value);
});

const totalProjects = computed(() => {
    const o = summary.projects?.by_type || {};
    return Object.values(o).reduce((a, b) => a + Number(b), 0);
});

const attentionProjects = computed(() => {
    const s = summary.projects?.by_status || {};
    return (Number(s.at_risk) || 0) + (Number(s.delayed) || 0) + (Number(s.blocked) || 0);
});

const openTasksTotal = computed(() =>
    (summary.workload_open_tasks || []).reduce((a, r) => a + Number(r.open_tasks), 0),
);

const innovationPipeline = computed(() => {
    const f = summary.innovation_funnel || {};
    return (Number(f.submitted) || 0) + (Number(f.poc) || 0) + (Number(f.applied) || 0);
});

const kpiCards = computed(() => [
    {
        id: 'tp',
        value: totalProjects.value,
        label: t('dashboard.kpi.totalProjects'),
        hint: t('dashboard.kpiHint.totalProjects'),
    },
    {
        id: 'att',
        value: attentionProjects.value,
        label: t('dashboard.kpi.attentionProjects'),
        hint: t('dashboard.kpiHint.attentionProjects'),
    },
    {
        id: 'tasks',
        value: openTasksTotal.value,
        label: t('dashboard.kpi.openTasks'),
        hint: t('dashboard.kpiHint.openTasks'),
    },
    {
        id: 'inn',
        value: innovationPipeline.value,
        label: t('dashboard.kpi.innovationPipeline'),
        hint: t('dashboard.kpiHint.innovationPipeline'),
    },
]);

const hasProjectTypeData = computed(() => Object.keys(summary.projects?.by_type || {}).length > 0);

const hasKpiTrend = computed(() => (summary.kpi_performance_trend || []).length > 0);

const matrixBlocks = computed(() => [
    {
        titleKey: 'dashboard.matrix.byType',
        rows: Object.entries(summary.projects?.by_type || {}).map(([k, v]) => ({
            key: k,
            label: typeLabel(k),
            value: v,
        })),
    },
    {
        titleKey: 'dashboard.matrix.byStatus',
        rows: Object.entries(summary.projects?.by_status || {}).map(([k, v]) => ({
            key: k,
            label: statusLabel(k),
            value: v,
        })),
    },
    {
        titleKey: 'dashboard.matrix.kaizenByStatus',
        rows: Object.entries(summary.kaizen_by_status || {}).map(([k, v]) => ({
            key: k,
            label: kaizenLabel(k),
            value: v,
        })),
    },
]);

function formatInt(n) {
    return new Intl.NumberFormat(numberLocale.value).format(Number(n) || 0);
}

function typeLabel(key) {
    const path = `dashboard.projectType.${key}`;
    const out = t(path);
    return out === path ? key : out;
}

function statusLabel(key) {
    const path = `dashboard.projectStatus.${key}`;
    const out = t(path);
    return out === path ? key : out;
}

function kaizenLabel(key) {
    const path = `dashboard.kaizenStatus.${key}`;
    const out = t(path);
    return out === path ? key : out;
}

const chartColors = {
    text: '#5c6370',
    textDark: '#1a1d26',
    grid: '#e8eaef',
    primary: '#9a0036',
    teal: '#0d9488',
    amber: '#d97706',
};

function renderCharts() {
    if (typeChart) {
        typeChart.destroy();
        typeChart = null;
    }
    if (kpiChart) {
        kpiChart.destroy();
        kpiChart = null;
    }

    const typeEl = typeChartRef.value;
    const kpiEl = kpiChartRef.value;

    if (hasProjectTypeData.value && typeEl) {
        const byType = summary.projects?.by_type || {};
        const keys = Object.keys(byType);
        const data = keys.map((k) => byType[k]);

        typeChart = new Chart(typeEl, {
            type: 'doughnut',
            data: {
                labels: keys.map(typeLabel),
                datasets: [
                    {
                        data,
                        backgroundColor: [chartColors.primary, chartColors.teal, chartColors.amber],
                        borderWidth: 2,
                        borderColor: '#fff',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: chartColors.text, padding: 14, usePointStyle: true },
                    },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                const v = ctx.raw ?? 0;
                                return `${ctx.label}: ${formatInt(v)}`;
                            },
                        },
                    },
                },
            },
        });
    }

    if (hasKpiTrend.value && kpiEl) {
        const trend = summary.kpi_performance_trend || [];
        kpiChart = new Chart(kpiEl, {
            type: 'line',
            data: {
                labels: trend.map((r) => r.week_ending),
                datasets: [
                    {
                        label: t('dashboard.visual.performancePct'),
                        data: trend.map((r) => Number(r.value)),
                        borderColor: chartColors.primary,
                        backgroundColor: 'rgba(154, 0, 54, 0.08)',
                        fill: true,
                        tension: 0.25,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: { color: chartColors.text, maxRotation: 45, minRotation: 0 },
                        grid: { color: chartColors.grid },
                    },
                    y: {
                        ticks: { color: chartColors.text },
                        grid: { color: chartColors.grid },
                        beginAtZero: true,
                    },
                },
                plugins: {
                    legend: { labels: { color: chartColors.textDark } },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                const v = ctx.raw ?? 0;
                                return `${ctx.dataset.label}: ${formatInt(v)}`;
                            },
                        },
                    },
                },
            },
        });
    }
}

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/dashboard/summary');
        Object.assign(summary, data);
        lastRefreshedAt.value = new Date();
    } catch (e) {
        err.value = getApiErrorMessage(e, t('dashboard.loadError'));
    } finally {
        loading.value = false;
    }
});

watch(
    () => loading.value,
    async (v) => {
        if (!v && !err.value) {
            await nextTick();
            renderCharts();
        }
    },
);

watch(locale, async () => {
    if (!loading.value && !err.value) {
        await nextTick();
        renderCharts();
    }
});

onBeforeUnmount(() => {
    if (typeChart) {
        typeChart.destroy();
    }
    if (kpiChart) {
        kpiChart.destroy();
    }
});
</script>
