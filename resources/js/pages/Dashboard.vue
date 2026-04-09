<template>
    <div class="ppms-page pbi-report" :class="{ 'pbi-report--present': presentationMode }">
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
                    <nav class="pbi-dash-tabs" role="tablist" :aria-label="t('dashboard.tabs.aria')">
                        <button
                            type="button"
                            role="tab"
                            class="pbi-dash-tab"
                            :class="{ 'pbi-dash-tab--active': activeTab === 'overview' }"
                            :aria-selected="activeTab === 'overview'"
                            @click="activeTab = 'overview'"
                        >
                            {{ t('dashboard.tabs.overview') }}
                        </button>
                        <button
                            type="button"
                            role="tab"
                            class="pbi-dash-tab"
                            :class="{ 'pbi-dash-tab--active': activeTab === 'tasks' }"
                            :aria-selected="activeTab === 'tasks'"
                            @click="activeTab = 'tasks'"
                        >
                            {{ t('dashboard.tabs.taskReport') }}
                        </button>
                    </nav>
                </div>
                <div class="pbi-report-meta">
                    <button
                        type="button"
                        class="pbi-present-toggle"
                        :aria-pressed="presentationMode"
                        :title="t('dashboard.exec.presentHint')"
                        @click="togglePresentation"
                    >
                        {{ presentationMode ? t('dashboard.exec.presentExit') : t('dashboard.exec.presentOn') }}
                    </button>
                    <span class="pbi-refresh-label">{{ t('dashboard.lastRefreshed') }}</span>
                    <time class="pbi-refresh-time" :datetime="refreshedIso">{{ refreshedDisplay }}</time>
                </div>
            </header>

            <section class="pbi-exec-summary" :aria-label="t('dashboard.exec.aria')">
                <div class="pbi-exec-summary__accent" aria-hidden="true" />
                <div class="pbi-exec-summary__body">
                    <p class="pbi-exec-summary__kicker">{{ t('dashboard.exec.kicker') }}</p>
                    <p class="pbi-exec-summary__lead">{{ executiveLead }}</p>
                    <p class="pbi-exec-summary__detail">{{ executiveDetail }}</p>
                </div>
            </section>

            <div v-show="activeTab === 'overview'" class="pbi-dash-panel">
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

            <div v-show="activeTab === 'tasks'" class="pbi-dash-panel" :aria-label="t('dashboard.tabs.taskReport')">
                <section class="pbi-kpi-row pbi-kpi-row--tasks" :aria-label="t('dashboard.taskReport.kpiAria')">
                    <article class="pbi-kpi-card pbi-kpi-card--gauge" :title="t('dashboard.taskReport.completionHint')">
                        <span class="pbi-kpi-label">{{ t('dashboard.taskReport.completionRate') }}</span>
                        <span class="pbi-kpi-value">{{ formatPctOne(taskAnalytics.completion_pct) }}</span>
                        <div class="pbi-gauge-bar" role="img" :aria-label="t('dashboard.taskReport.completionRate')">
                            <div
                                class="pbi-gauge-fill"
                                :style="{ width: Math.min(100, Math.max(0, Number(taskAnalytics.completion_pct) || 0)) + '%' }"
                            />
                        </div>
                        <span class="pbi-kpi-hint">{{ t('dashboard.taskReport.completionHint') }}</span>
                    </article>
                    <article class="pbi-kpi-card">
                        <span class="pbi-kpi-label">{{ t('dashboard.taskReport.tasksDone') }}</span>
                        <span class="pbi-kpi-value">{{ formatInt(taskAnalytics.tasks_done) }} / {{ formatInt(taskAnalytics.tasks_total) }}</span>
                        <span class="pbi-kpi-hint">{{ t('dashboard.taskReport.tasksDoneHint') }}</span>
                    </article>
                    <article class="pbi-kpi-card">
                        <span class="pbi-kpi-label">{{ t('dashboard.taskReport.hoursDoneTab') }}</span>
                        <span class="pbi-kpi-value pbi-kpi-value--sm">{{ formatHours(taskAnalytics.hours?.done?.estimate) }} / {{ formatHours(taskAnalytics.hours?.done?.actual) }}</span>
                        <span class="pbi-kpi-hint">{{ t('dashboard.taskReport.hoursDoneHint') }}</span>
                    </article>
                    <article class="pbi-kpi-card">
                        <span class="pbi-kpi-label">{{ t('dashboard.taskReport.hoursAllTab') }}</span>
                        <span class="pbi-kpi-value pbi-kpi-value--sm">{{ formatHours(taskAnalytics.hours?.all?.estimate) }} / {{ formatHours(taskAnalytics.hours?.all?.actual) }}</span>
                        <span class="pbi-kpi-hint">{{ t('dashboard.taskReport.hoursAllHint') }}</span>
                    </article>
                </section>

                <section class="pbi-visual-row pbi-visual-row--2 pbi-mt">
                    <article class="pbi-visual">
                        <header class="pbi-visual-header">
                            <div class="pbi-visual-accent" aria-hidden="true" />
                            <div class="pbi-visual-heading">
                                <h3 class="pbi-visual-title">{{ t('dashboard.taskReport.chartStatus') }}</h3>
                                <p class="pbi-visual-sub">{{ t('dashboard.taskReport.chartStatusSub') }}</p>
                            </div>
                        </header>
                        <div class="pbi-visual-body">
                            <div v-if="hasTaskStatusData" class="pbi-chart-host">
                                <canvas ref="taskStatusChartRef" height="260" />
                            </div>
                            <p v-else class="pbi-visual-empty">{{ t('dashboard.taskReport.emptyTasks') }}</p>
                        </div>
                    </article>
                    <article class="pbi-visual">
                        <header class="pbi-visual-header">
                            <div class="pbi-visual-accent" aria-hidden="true" />
                            <div class="pbi-visual-heading">
                                <h3 class="pbi-visual-title">{{ t('dashboard.taskReport.chartHours') }}</h3>
                                <p class="pbi-visual-sub">{{ t('dashboard.taskReport.chartHoursSub') }}</p>
                            </div>
                        </header>
                        <div class="pbi-visual-body">
                            <div v-if="hasHoursCompare" class="pbi-chart-host">
                                <canvas ref="hoursCompareChartRef" height="260" />
                            </div>
                            <p v-else class="pbi-visual-empty">{{ t('dashboard.taskReport.emptyHours') }}</p>
                        </div>
                    </article>
                </section>

                <section class="pbi-visual-row pbi-visual-row--2 pbi-mt">
                    <article class="pbi-visual">
                        <header class="pbi-visual-header">
                            <div class="pbi-visual-accent" aria-hidden="true" />
                            <div class="pbi-visual-heading">
                                <h3 class="pbi-visual-title">{{ t('dashboard.taskReport.chartProgressBuckets') }}</h3>
                                <p class="pbi-visual-sub">{{ t('dashboard.taskReport.chartProgressBucketsSub') }}</p>
                            </div>
                        </header>
                        <div class="pbi-visual-body">
                            <div v-if="hasProgressBuckets" class="pbi-chart-host">
                                <canvas ref="progressBucketChartRef" height="260" />
                            </div>
                            <p v-else class="pbi-visual-empty">{{ t('dashboard.taskReport.emptyProjects') }}</p>
                        </div>
                    </article>
                    <article class="pbi-visual">
                        <header class="pbi-visual-header">
                            <div class="pbi-visual-accent" aria-hidden="true" />
                            <div class="pbi-visual-heading">
                                <h3 class="pbi-visual-title">{{ t('dashboard.taskReport.chartPhase') }}</h3>
                                <p class="pbi-visual-sub">{{ t('dashboard.taskReport.chartPhaseSub') }}</p>
                            </div>
                        </header>
                        <div class="pbi-visual-body">
                            <div v-if="hasProjectPhaseData" class="pbi-chart-host">
                                <canvas ref="phaseChartRef" height="260" />
                            </div>
                            <p v-else class="pbi-visual-empty">{{ t('dashboard.taskReport.emptyPhase') }}</p>
                        </div>
                    </article>
                </section>

                <section class="pbi-visual-row pbi-visual-row--2 pbi-mt">
                    <article class="pbi-visual pbi-visual--wide">
                        <header class="pbi-visual-header">
                            <div class="pbi-visual-accent" aria-hidden="true" />
                            <div class="pbi-visual-heading">
                                <h3 class="pbi-visual-title">{{ t('dashboard.taskReport.chartCategory') }}</h3>
                                <p class="pbi-visual-sub">{{ t('dashboard.taskReport.chartCategorySub') }}</p>
                            </div>
                        </header>
                        <div class="pbi-visual-body">
                            <div v-if="hasCategoryData" class="pbi-chart-host pbi-chart-host--tall">
                                <canvas ref="categoryChartRef" height="320" />
                            </div>
                            <p v-else class="pbi-visual-empty">{{ t('dashboard.taskReport.emptyCategory') }}</p>
                        </div>
                    </article>
                    <article class="pbi-visual">
                        <header class="pbi-visual-header">
                            <div class="pbi-visual-accent" aria-hidden="true" />
                            <div class="pbi-visual-heading">
                                <h3 class="pbi-visual-title">{{ t('dashboard.taskReport.matrixTitle') }}</h3>
                                <p class="pbi-visual-sub">{{ t('dashboard.taskReport.matrixSub') }}</p>
                            </div>
                        </header>
                        <div class="pbi-visual-body pbi-task-matrix-body">
                            <ul class="pbi-task-matrix">
                                <li>
                                    <span>{{ t('dashboard.taskReport.mTasksTotal') }}</span>
                                    <strong>{{ formatInt(taskAnalytics.tasks_total) }}</strong>
                                </li>
                                <li>
                                    <span>{{ t('dashboard.taskReport.mTasksDone') }}</span>
                                    <strong>{{ formatInt(taskAnalytics.tasks_done) }}</strong>
                                </li>
                                <li>
                                    <span>{{ t('dashboard.taskReport.mCompletion') }}</span>
                                    <strong>{{ formatPctOne(taskAnalytics.completion_pct) }}</strong>
                                </li>
                                <li>
                                    <span>{{ t('dashboard.taskReport.mHoursEstAll') }}</span>
                                    <strong>{{ formatHours(taskAnalytics.hours?.all?.estimate) }}</strong>
                                </li>
                                <li>
                                    <span>{{ t('dashboard.taskReport.mHoursActAll') }}</span>
                                    <strong>{{ formatHours(taskAnalytics.hours?.all?.actual) }}</strong>
                                </li>
                                <li>
                                    <span>{{ t('dashboard.taskReport.mHoursEstDone') }}</span>
                                    <strong>{{ formatHours(taskAnalytics.hours?.done?.estimate) }}</strong>
                                </li>
                                <li>
                                    <span>{{ t('dashboard.taskReport.mHoursActDone') }}</span>
                                    <strong>{{ formatHours(taskAnalytics.hours?.done?.actual) }}</strong>
                                </li>
                            </ul>
                        </div>
                    </article>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup>
import {
    ArcElement,
    BarController,
    BarElement,
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
    BarController,
    BarElement,
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
const activeTab = ref('overview');
const presentationMode = ref(
    typeof sessionStorage !== 'undefined' && sessionStorage.getItem('ppms_dashboard_present') === '1',
);

function togglePresentation() {
    presentationMode.value = !presentationMode.value;
    if (typeof sessionStorage !== 'undefined') {
        sessionStorage.setItem('ppms_dashboard_present', presentationMode.value ? '1' : '0');
    }
}

const typeChartRef = ref(null);
const kpiChartRef = ref(null);
const taskStatusChartRef = ref(null);
const hoursCompareChartRef = ref(null);
const progressBucketChartRef = ref(null);
const phaseChartRef = ref(null);
const categoryChartRef = ref(null);

let typeChart;
let kpiChart;
let statusChart;
let hoursCompareChart;
let progressBucketChart;
let phaseChart;
let categoryChart;

const summary = reactive({
    projects: { by_type: {}, by_status: {}, by_phase: {} },
    kaizen_by_status: {},
    workload_open_tasks: [],
    at_risk_projects: [],
    kpi_performance_trend: [],
    innovation_funnel: {},
    task_analytics: {
        tasks_by_status: {},
        tasks_total: 0,
        tasks_done: 0,
        completion_pct: 0,
        hours: {
            all: { estimate: 0, actual: 0 },
            done: { estimate: 0, actual: 0 },
        },
        project_progress_buckets: {},
        tasks_by_category: {},
    },
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

const taskAnalytics = computed(() => summary.task_analytics || {});

const hasTaskStatusData = computed(() => Object.keys(taskAnalytics.value.tasks_by_status || {}).length > 0);

const hasHoursCompare = computed(() => {
    const h = taskAnalytics.value.hours;
    if (!h) {
        return false;
    }
    const sum =
        Number(h.all?.estimate || 0) +
        Number(h.all?.actual || 0) +
        Number(h.done?.estimate || 0) +
        Number(h.done?.actual || 0);
    return sum > 0;
});

const hasProgressBuckets = computed(() => {
    const b = taskAnalytics.value.project_progress_buckets || {};
    return Object.values(b).reduce((a, n) => a + Number(n), 0) > 0;
});

const hasProjectPhaseData = computed(() => Object.keys(summary.projects?.by_phase || {}).length > 0);

const hasCategoryData = computed(() => Object.keys(taskAnalytics.value.tasks_by_category || {}).length > 0);

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

function formatPctOne(n) {
    const v = Number(n);
    if (Number.isNaN(v)) {
        return '0%';
    }
    return `${v.toFixed(1)}%`;
}

const executiveLead = computed(() =>
    t('dashboard.exec.lead', {
        total: formatInt(totalProjects.value),
        attention: formatInt(attentionProjects.value),
        completion: formatPctOne(taskAnalytics.value.completion_pct ?? 0),
    }),
);

const executiveDetail = computed(() =>
    t('dashboard.exec.detail', {
        openTasks: formatInt(openTasksTotal.value),
        innovation: formatInt(innovationPipeline.value),
    }),
);

function formatHours(n) {
    const v = Number(n);
    if (Number.isNaN(v)) {
        return '0';
    }
    return new Intl.NumberFormat(numberLocale.value, { maximumFractionDigits: 1 }).format(v);
}

function taskStatusLabel(key) {
    const path = `projects.taskStatus.${key}`;
    const out = t(path);
    return out === path ? key : out;
}

function phaseLabel(key) {
    const path = `projects.phase.${key}`;
    const out = t(path);
    return out === path ? key : out;
}

function progressBucketLabel(key) {
    const path = `dashboard.taskReport.buckets.${key}`;
    const out = t(path);
    return out === path ? key : out;
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
    indigo: '#4f46e5',
    rose: '#e11d48',
};

const barPalette = [chartColors.primary, chartColors.teal, chartColors.amber, chartColors.indigo, chartColors.rose];

function destroyOverviewCharts() {
    if (typeChart) {
        typeChart.destroy();
        typeChart = null;
    }
    if (kpiChart) {
        kpiChart.destroy();
        kpiChart = null;
    }
}

function destroyTaskCharts() {
    if (statusChart) {
        statusChart.destroy();
        statusChart = null;
    }
    if (hoursCompareChart) {
        hoursCompareChart.destroy();
        hoursCompareChart = null;
    }
    if (progressBucketChart) {
        progressBucketChart.destroy();
        progressBucketChart = null;
    }
    if (phaseChart) {
        phaseChart.destroy();
        phaseChart = null;
    }
    if (categoryChart) {
        categoryChart.destroy();
        categoryChart = null;
    }
}

function destroyAllCharts() {
    destroyOverviewCharts();
    destroyTaskCharts();
}

function renderOverviewCharts() {
    destroyOverviewCharts();

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

function renderTaskCharts() {
    destroyTaskCharts();

    const ta = taskAnalytics.value;
    const byStatus = ta.tasks_by_status || {};

    if (hasTaskStatusData.value && taskStatusChartRef.value) {
        const keys = Object.keys(byStatus).sort();
        const data = keys.map((k) => Number(byStatus[k]));
        statusChart = new Chart(taskStatusChartRef.value, {
            type: 'bar',
            data: {
                labels: keys.map(taskStatusLabel),
                datasets: [
                    {
                        label: t('dashboard.taskReport.chartStatusDataset'),
                        data,
                        backgroundColor: keys.map((_, i) => barPalette[i % barPalette.length]),
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                return `${ctx.label}: ${formatInt(ctx.raw)}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: chartColors.text, maxRotation: 45, minRotation: 0 },
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: chartColors.text, precision: 0 },
                        grid: { color: chartColors.grid },
                    },
                },
            },
        });
    }

    if (hasHoursCompare.value && hoursCompareChartRef.value) {
        const h = ta.hours || {};
        const all = h.all || { estimate: 0, actual: 0 };
        const done = h.done || { estimate: 0, actual: 0 };
        hoursCompareChart = new Chart(hoursCompareChartRef.value, {
            type: 'bar',
            data: {
                labels: [t('dashboard.taskReport.hoursScopeAll'), t('dashboard.taskReport.hoursScopeDone')],
                datasets: [
                    {
                        label: t('dashboard.taskReport.hoursEstimate'),
                        data: [Number(all.estimate), Number(done.estimate)],
                        backgroundColor: chartColors.teal,
                        borderRadius: 4,
                    },
                    {
                        label: t('dashboard.taskReport.hoursActual'),
                        data: [Number(all.actual), Number(done.actual)],
                        backgroundColor: chartColors.primary,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: chartColors.textDark } },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                return `${ctx.dataset.label}: ${formatHours(ctx.raw)}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: chartColors.text },
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: chartColors.text,
                            callback(v) {
                                return formatHours(v);
                            },
                        },
                        grid: { color: chartColors.grid },
                    },
                },
            },
        });
    }

    if (hasProgressBuckets.value && progressBucketChartRef.value) {
        const b = ta.project_progress_buckets || {};
        const order = ['0-25', '25-50', '50-75', '75-100'];
        const keys = order.filter((k) => k in b);
        const data = keys.map((k) => Number(b[k]));
        progressBucketChart = new Chart(progressBucketChartRef.value, {
            type: 'doughnut',
            data: {
                labels: keys.map(progressBucketLabel),
                datasets: [
                    {
                        data,
                        backgroundColor: [chartColors.teal, chartColors.amber, chartColors.indigo, chartColors.primary],
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
                        labels: { color: chartColors.text, padding: 12, usePointStyle: true },
                    },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                return `${ctx.label}: ${formatInt(ctx.raw)}`;
                            },
                        },
                    },
                },
            },
        });
    }

    if (hasProjectPhaseData.value && phaseChartRef.value) {
        const byPh = summary.projects?.by_phase || {};
        const keys = Object.keys(byPh).sort();
        const data = keys.map((k) => Number(byPh[k]));
        phaseChart = new Chart(phaseChartRef.value, {
            type: 'doughnut',
            data: {
                labels: keys.map(phaseLabel),
                datasets: [
                    {
                        data,
                        backgroundColor: keys.map((_, i) => barPalette[i % barPalette.length]),
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
                        labels: { color: chartColors.text, padding: 12, usePointStyle: true },
                    },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                return `${ctx.label}: ${formatInt(ctx.raw)}`;
                            },
                        },
                    },
                },
            },
        });
    }

    if (hasCategoryData.value && categoryChartRef.value) {
        const cat = ta.tasks_by_category || {};
        const keys = Object.keys(cat);
        const data = keys.map((k) => Number(cat[k]));
        categoryChart = new Chart(categoryChartRef.value, {
            type: 'bar',
            data: {
                labels: keys,
                datasets: [
                    {
                        label: t('dashboard.taskReport.chartCategoryDataset'),
                        data,
                        backgroundColor: chartColors.indigo,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                return `${ctx.label}: ${formatInt(ctx.raw)}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { color: chartColors.text, precision: 0 },
                        grid: { color: chartColors.grid },
                    },
                    y: {
                        ticks: { color: chartColors.text },
                        grid: { display: false },
                    },
                },
            },
        });
    }
}

async function renderActiveTabCharts() {
    if (loading.value || err.value) {
        return;
    }
    destroyAllCharts();
    await nextTick();
    if (activeTab.value === 'overview') {
        renderOverviewCharts();
    } else {
        renderTaskCharts();
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
            await renderActiveTabCharts();
        }
    },
);

watch(activeTab, async () => {
    if (!loading.value && !err.value) {
        await renderActiveTabCharts();
    }
});

watch(locale, async () => {
    if (!loading.value && !err.value) {
        await renderActiveTabCharts();
    }
});

watch(presentationMode, async () => {
    if (loading.value || err.value) {
        return;
    }
    await nextTick();
    await new Promise((resolve) => {
        requestAnimationFrame(() => resolve());
    });
    await renderActiveTabCharts();
});

onBeforeUnmount(() => {
    destroyAllCharts();
});
</script>
