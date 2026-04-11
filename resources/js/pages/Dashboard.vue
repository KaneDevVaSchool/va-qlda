<template>
    <div class="ppms-page pbi-report" :class="{ 'pbi-report--present': presentationMode }">
        <div v-if="loading" class="pbi-loading" role="status">
            <span class="pbi-loading-bar" aria-hidden="true" />
            {{ t('dashboard.loading') }}
        </div>
        <div v-else-if="err" class="ppms-error">{{ err }}</div>
        <div v-else class="pbi-canvas pbi-board">
            <header class="pbi-board-header">
                <div class="pbi-board-header-main">
                    <h2 class="pbi-board-title">{{ t('dashboard.board.pageTitle') }}</h2>
                    <p class="pbi-board-dates">{{ boardDateRange }}</p>
                    <nav class="pbi-board-tabs" role="tablist" :aria-label="t('dashboard.tabs.aria')">
                        <button
                            type="button"
                            role="tab"
                            class="pbi-board-tab"
                            :class="{ 'pbi-board-tab--active': activeTab === 'overview' }"
                            :aria-selected="activeTab === 'overview'"
                            @click="activeTab = 'overview'"
                        >
                            {{ t('dashboard.board.tabCompany') }}
                        </button>
                        <button
                            type="button"
                            role="tab"
                            class="pbi-board-tab"
                            :class="{ 'pbi-board-tab--active': activeTab === 'tasks' }"
                            :aria-selected="activeTab === 'tasks'"
                            @click="activeTab = 'tasks'"
                        >
                            {{ t('dashboard.tabs.taskReport') }}
                        </button>
                    </nav>
                </div>
                <div class="pbi-board-header-actions">
                    <button
                        type="button"
                        class="pbi-board-filter"
                        disabled
                        :title="t('dashboard.board.filterSoon')"
                    >
                        <span class="pbi-board-filter-icon" aria-hidden="true" />
                    </button>
                    <button
                        type="button"
                        class="pbi-present-toggle"
                        :aria-pressed="presentationMode"
                        :title="t('dashboard.exec.presentHint')"
                        @click="togglePresentation"
                    >
                        {{ presentationMode ? t('dashboard.exec.presentExit') : t('dashboard.exec.presentOn') }}
                    </button>
                    <div class="pbi-report-meta">
                        <span class="pbi-refresh-label">{{ t('dashboard.lastRefreshed') }}</span>
                        <time class="pbi-refresh-time" :datetime="refreshedIso">{{ refreshedDisplay }}</time>
                    </div>
                </div>
            </header>

            <section class="pbi-exec-summary pbi-exec-summary--board" :aria-label="t('dashboard.exec.aria')">
                <div class="pbi-exec-summary__accent" aria-hidden="true" />
                <div class="pbi-exec-summary__body">
                    <p class="pbi-exec-summary__kicker">{{ t('dashboard.exec.kicker') }}</p>
                    <p class="pbi-exec-summary__lead">{{ executiveLead }}</p>
                    <p class="pbi-exec-summary__detail">{{ executiveDetail }}</p>
                </div>
            </section>

            <div v-show="activeTab === 'overview'" class="pbi-dash-panel">
                <section class="pbi-board-hero" :aria-label="t('dashboard.board.heroAria')">
                    <article
                        v-for="card in boardHeroCards"
                        :key="card.id"
                        class="pbi-stat-card"
                        :class="'pbi-stat-card--' + card.tone"
                    >
                        <!-- eslint-disable-next-line vue/no-v-html -- static SVG from script -->
                        <div class="pbi-stat-card__icon" aria-hidden="true" v-html="card.iconSvg" />
                        <div class="pbi-stat-card__body">
                            <span class="pbi-stat-card__label">{{ card.label }}</span>
                            <span class="pbi-stat-card__value">{{ formatInt(card.value) }}</span>
                        </div>
                    </article>
                </section>

                <section class="pbi-board-chart-row">
                    <article class="pbi-widget-card">
                        <header class="pbi-widget-card__head">
                            <h3 class="pbi-widget-card__title">{{ t('dashboard.board.chartTaskRatio') }}</h3>
                            <p class="pbi-widget-card__sub">{{ t('dashboard.board.chartTaskRatioSub') }}</p>
                        </header>
                        <div class="pbi-widget-card__body">
                            <div v-if="hasTaskStatusData" class="pbi-chart-host pbi-chart-host--board">
                                <canvas ref="overviewTaskPieRef" height="280" />
                            </div>
                            <p v-else class="pbi-visual-empty">{{ t('dashboard.board.emptyPie') }}</p>
                        </div>
                    </article>
                    <article class="pbi-widget-card">
                        <header class="pbi-widget-card__head">
                            <h3 class="pbi-widget-card__title">{{ t('dashboard.board.chartByPm') }}</h3>
                            <p class="pbi-widget-card__sub">{{ t('dashboard.board.chartByPmSub') }}</p>
                        </header>
                        <div class="pbi-widget-card__body">
                            <div v-if="hasProjectsByOwner" class="pbi-chart-host pbi-chart-host--board">
                                <canvas ref="overviewOwnerBarRef" height="280" />
                            </div>
                            <p v-else class="pbi-visual-empty">{{ t('dashboard.board.emptyBar') }}</p>
                        </div>
                    </article>
                </section>

                <section class="pbi-board-hero pbi-board-hero--secondary" :aria-label="t('dashboard.board.secondaryAria')">
                    <article
                        v-for="card in boardSecondaryCards"
                        :key="card.id"
                        class="pbi-stat-card pbi-stat-card--compact"
                        :class="'pbi-stat-card--' + card.tone"
                    >
                        <!-- eslint-disable-next-line vue/no-v-html -- static SVG from script -->
                        <div class="pbi-stat-card__icon pbi-stat-card__icon--sm" aria-hidden="true" v-html="card.iconSvg" />
                        <div class="pbi-stat-card__body">
                            <span class="pbi-stat-card__label">{{ card.label }}</span>
                            <span class="pbi-stat-card__value">{{ formatInt(card.value) }}</span>
                        </div>
                    </article>
                </section>

                <section class="pbi-board-chart-row pbi-board-chart-row--triple">
                    <article class="pbi-widget-card">
                        <header class="pbi-widget-card__head">
                            <h3 class="pbi-widget-card__title">{{ t('dashboard.charts.radarPhase') }}</h3>
                            <p class="pbi-widget-card__sub">{{ t('dashboard.charts.radarPhaseSub') }}</p>
                        </header>
                        <div class="pbi-widget-card__body pbi-widget-card__body--radar">
                            <div v-if="hasRadarPhaseData" class="pbi-chart-host pbi-chart-host--radar">
                                <canvas ref="overviewRadarPhaseRef" height="300" />
                            </div>
                            <p v-else class="pbi-visual-empty">{{ t('dashboard.charts.emptyPhase') }}</p>
                        </div>
                    </article>
                    <article class="pbi-widget-card">
                        <header class="pbi-widget-card__head">
                            <h3 class="pbi-widget-card__title">{{ t('dashboard.charts.polarStatus') }}</h3>
                            <p class="pbi-widget-card__sub">{{ t('dashboard.charts.polarStatusSub') }}</p>
                        </header>
                        <div class="pbi-widget-card__body pbi-widget-card__body--polar">
                            <div v-if="hasPolarStatusData" class="pbi-chart-host pbi-chart-host--polar">
                                <canvas ref="overviewPolarStatusRef" height="300" />
                            </div>
                            <p v-else class="pbi-visual-empty">{{ t('dashboard.charts.emptyStatus') }}</p>
                        </div>
                    </article>
                    <article class="pbi-widget-card">
                        <header class="pbi-widget-card__head">
                            <h3 class="pbi-widget-card__title">{{ t('dashboard.charts.funnelBars') }}</h3>
                            <p class="pbi-widget-card__sub">{{ t('dashboard.charts.funnelBarsSub') }}</p>
                        </header>
                        <div class="pbi-widget-card__body">
                            <div v-if="hasFunnelChartData" class="pbi-chart-host pbi-chart-host--funnel">
                                <canvas ref="overviewFunnelBarRef" height="300" />
                            </div>
                            <p v-else class="pbi-visual-empty">{{ t('dashboard.charts.emptyFunnel') }}</p>
                        </div>
                    </article>
                </section>

                <section class="pbi-board-chart-row pbi-board-chart-row--full">
                    <article class="pbi-widget-card pbi-widget-card--stretch">
                        <header class="pbi-widget-card__head">
                            <h3 class="pbi-widget-card__title">{{ t('dashboard.charts.workloadHBar') }}</h3>
                            <p class="pbi-widget-card__sub">{{ t('dashboard.charts.workloadHBarSub') }}</p>
                        </header>
                        <div class="pbi-widget-card__body">
                            <div v-if="hasWorkloadChartData" class="pbi-chart-host pbi-chart-host--hbar">
                                <canvas ref="overviewWorkloadBarRef" height="320" />
                            </div>
                            <p v-else class="pbi-visual-empty">{{ t('dashboard.charts.emptyWorkload') }}</p>
                        </div>
                    </article>
                </section>

            <section class="pbi-visual-row pbi-board-section">
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
    Legend,
    LinearScale,
    PolarAreaController,
    RadialLinearScale,
    RadarController,
    Title,
    Tooltip,
} from 'chart.js';
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { getApiErrorMessage } from '@/bootstrap';
import {
    barVerticalGradient,
    chartAnimation,
    chartFont,
    legendBottom,
    tooltipPremium,
} from '@/utils/dashboardChartTheme';

Chart.register(
    DoughnutController,
    PolarAreaController,
    RadarController,
    BarController,
    BarElement,
    ArcElement,
    CategoryScale,
    Legend,
    LinearScale,
    RadialLinearScale,
    Title,
    Tooltip,
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
const overviewTaskPieRef = ref(null);
const overviewOwnerBarRef = ref(null);
const overviewRadarPhaseRef = ref(null);
const overviewPolarStatusRef = ref(null);
const overviewFunnelBarRef = ref(null);
const overviewWorkloadBarRef = ref(null);
const taskStatusChartRef = ref(null);
const hoursCompareChartRef = ref(null);
const progressBucketChartRef = ref(null);
const phaseChartRef = ref(null);
const categoryChartRef = ref(null);

let typeChart;
let overviewTaskPieChart;
let overviewOwnerBarChart;
let overviewRadarPhaseChart;
let overviewPolarStatusChart;
let overviewFunnelBarChart;
let overviewWorkloadBarChart;
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
    board_stats: {},
    projects_by_owner: [],
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

const boardStats = computed(() => summary.board_stats || {});

const boardDateRange = computed(() => {
    const loc = numberLocale.value;
    const fmt = new Intl.DateTimeFormat(loc, { day: '2-digit', month: '2-digit', year: 'numeric' });
    const end = new Date();
    const start = new Date(end.getFullYear(), end.getMonth(), 1);
    return `${fmt.format(start)} — ${fmt.format(end)}`;
});

/** SVG tĩnh cho widget — chỉ dùng chuỗi cố định trong code (an toàn cho v-html). */
const BOARD_ICON = {
    list: '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>',
    activity:
        '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
    folder:
        '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>',
    flag: '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>',
    plus: '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5v14M5 12h14"/></svg>',
    check:
        '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>',
    play: '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>',
    alert:
        '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    bulb: '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 18h6M10 22h4M12 2a7 7 0 017 7c0 2.38-1.19 4.47-3 5.74V17h-8v-2.26C6.19 13.47 5 11.38 5 9a7 7 0 017-7z"/></svg>',
    users:
        '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>',
};

const boardHeroCards = computed(() => {
    const b = boardStats.value;
    return [
        {
            id: 'h1',
            tone: 'blue',
            value: Number(b.tasks_in_progress ?? 0),
            label: t('dashboard.board.hero.inProgress'),
            iconSvg: BOARD_ICON.list,
        },
        {
            id: 'h2',
            tone: 'purple',
            value: Number(b.tasks_this_month ?? 0),
            label: t('dashboard.board.hero.tasksMonth'),
            iconSvg: BOARD_ICON.activity,
        },
        {
            id: 'h3',
            tone: 'green',
            value: Number(b.projects_unfinished ?? 0),
            label: t('dashboard.board.hero.projectsOpen'),
            iconSvg: BOARD_ICON.folder,
        },
        {
            id: 'h4',
            tone: 'amber',
            value: Number(b.tasks_due_today ?? 0),
            label: t('dashboard.board.hero.dueToday'),
            iconSvg: BOARD_ICON.flag,
        },
    ];
});

const boardSecondaryCards = computed(() => {
    const b = boardStats.value;
    return [
        {
            id: 's1',
            tone: 'amber',
            value: Number(b.projects_new_month ?? 0),
            label: t('dashboard.board.secondary.newMonth'),
            iconSvg: BOARD_ICON.plus,
        },
        {
            id: 's2',
            tone: 'green',
            value: Number(b.projects_phase_done ?? 0),
            label: t('dashboard.board.secondary.done'),
            iconSvg: BOARD_ICON.check,
        },
        {
            id: 's3',
            tone: 'blue',
            value: Number(b.projects_active ?? 0),
            label: t('dashboard.board.secondary.active'),
            iconSvg: BOARD_ICON.play,
        },
        {
            id: 's4',
            tone: 'amber',
            value: Number(b.projects_blocked ?? 0),
            label: t('dashboard.board.secondary.blocked'),
            iconSvg: BOARD_ICON.alert,
        },
        {
            id: 's5',
            tone: 'purple',
            value: innovationPipeline.value,
            label: t('dashboard.board.secondary.innovation'),
            iconSvg: BOARD_ICON.bulb,
        },
        {
            id: 's6',
            tone: 'blue',
            value: openTasksTotal.value,
            label: t('dashboard.board.secondary.openTasks'),
            iconSvg: BOARD_ICON.users,
        },
    ];
});

const hasProjectsByOwner = computed(() => (summary.projects_by_owner || []).length > 0);

const hasRadarPhaseData = computed(() => Object.keys(summary.projects?.by_phase || {}).length > 0);

const hasPolarStatusData = computed(() => Object.keys(summary.projects?.by_status || {}).length > 0);

const hasFunnelChartData = computed(() => Object.keys(summary.innovation_funnel || {}).length > 0);

const hasWorkloadChartData = computed(() => (summary.workload_open_tasks || []).length > 0);

const hasProjectTypeData = computed(() => Object.keys(summary.projects?.by_type || {}).length > 0);

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
    teal: '#475569',
    amber: '#57534e',
    indigo: '#4b5563',
    rose: '#64748b',
};

const barPalette = [chartColors.primary, chartColors.teal, chartColors.amber, chartColors.indigo, chartColors.rose];

/** Bảng màu dịu — dễ đọc, ít gắt (thay vì neon nhiều sắc) */
const piePaletteBoard = [
    '#475569',
    '#64748b',
    '#57534e',
    '#78716c',
    '#9a0036',
    '#6b5b63',
    '#4b5563',
    '#334155',
    '#5c4d56',
    '#526077',
];

function truncateAxisLabel(s, max = 20) {
    if (!s) {
        return '';
    }
    return s.length > max ? `${s.slice(0, max)}…` : s;
}

function destroyOverviewCharts() {
    if (overviewTaskPieChart) {
        overviewTaskPieChart.destroy();
        overviewTaskPieChart = null;
    }
    if (overviewOwnerBarChart) {
        overviewOwnerBarChart.destroy();
        overviewOwnerBarChart = null;
    }
    if (overviewRadarPhaseChart) {
        overviewRadarPhaseChart.destroy();
        overviewRadarPhaseChart = null;
    }
    if (overviewPolarStatusChart) {
        overviewPolarStatusChart.destroy();
        overviewPolarStatusChart = null;
    }
    if (overviewFunnelBarChart) {
        overviewFunnelBarChart.destroy();
        overviewFunnelBarChart = null;
    }
    if (overviewWorkloadBarChart) {
        overviewWorkloadBarChart.destroy();
        overviewWorkloadBarChart = null;
    }
    if (typeChart) {
        typeChart.destroy();
        typeChart = null;
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

    const anim = chartAnimation();
    const tt = tooltipPremium();

    const pieOverviewEl = overviewTaskPieRef.value;
    const ownerBarEl = overviewOwnerBarRef.value;
    const radarEl = overviewRadarPhaseRef.value;
    const polarEl = overviewPolarStatusRef.value;
    const funnelEl = overviewFunnelBarRef.value;
    const workloadEl = overviewWorkloadBarRef.value;

    const rgbCycle = ['154, 0, 54', '13, 148, 136', '217, 119, 6', '79, 70, 229', '225, 29, 72'];

    if (hasTaskStatusData.value && pieOverviewEl) {
        const byStatus = taskAnalytics.value.tasks_by_status || {};
        const keys = Object.keys(byStatus).sort();
        const data = keys.map((k) => Number(byStatus[k]));
        overviewTaskPieChart = new Chart(pieOverviewEl, {
            type: 'doughnut',
            data: {
                labels: keys.map(taskStatusLabel),
                datasets: [
                    {
                        data,
                        backgroundColor: keys.map((_, i) => piePaletteBoard[i % piePaletteBoard.length]),
                        borderWidth: 2,
                        borderColor: '#fff',
                        borderRadius: 8,
                        hoverOffset: 10,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '64%',
                animation: anim,
                plugins: {
                    legend: legendBottom(chartColors.text),
                    tooltip: {
                        ...tt,
                        callbacks: {
                            label(ctx) {
                                const v = ctx.raw ?? 0;
                                return ` ${ctx.label}: ${formatInt(v)}`;
                            },
                        },
                    },
                },
            },
        });
    }

    if (hasProjectsByOwner.value && ownerBarEl) {
        const owners = summary.projects_by_owner || [];
        overviewOwnerBarChart = new Chart(ownerBarEl, {
            type: 'bar',
            data: {
                labels: owners.map((o) => truncateAxisLabel(o.name)),
                datasets: [
                    {
                        label: t('dashboard.board.chartByPmDataset'),
                        data: owners.map((o) => o.count),
                        backgroundColor(context) {
                            const { chart, dataIndex } = context;
                            const { ctx: c, chartArea } = chart;
                            const rgb = rgbCycle[dataIndex % rgbCycle.length];
                            return barVerticalGradient(c, chartArea, rgb);
                        },
                        borderRadius: 8,
                        borderSkipped: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: anim,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        ...tt,
                        callbacks: {
                            title(items) {
                                const i = items[0]?.dataIndex ?? 0;
                                return owners[i]?.name ?? '';
                            },
                            label(ctx) {
                                return `${formatInt(ctx.raw)}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: {
                            color: chartColors.text,
                            maxRotation: 50,
                            minRotation: 45,
                            font: chartFont(10),
                        },
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

    if (hasRadarPhaseData.value && radarEl) {
        const byPh = summary.projects?.by_phase || {};
        const keys = Object.keys(byPh).sort();
        const data = keys.map((k) => Number(byPh[k]));
        overviewRadarPhaseChart = new Chart(radarEl, {
            type: 'radar',
            data: {
                labels: keys.map(phaseLabel),
                datasets: [
                    {
                        label: t('dashboard.charts.radarDataset'),
                        data,
                        borderColor: chartColors.primary,
                        backgroundColor: 'rgba(154, 0, 54, 0.2)',
                        borderWidth: 2,
                        pointBackgroundColor: chartColors.primary,
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: chartColors.primary,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: anim,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        ...tt,
                        callbacks: {
                            label(ctx) {
                                return `${ctx.label}: ${formatInt(ctx.raw)}`;
                            },
                        },
                    },
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        ticks: { color: chartColors.text, backdropColor: 'transparent' },
                        grid: { color: 'rgba(15, 23, 42, 0.08)' },
                        angleLines: { color: 'rgba(15, 23, 42, 0.1)' },
                        pointLabels: { font: chartFont(10), color: chartColors.text },
                    },
                },
            },
        });
    }

    if (hasPolarStatusData.value && polarEl) {
        const bySt = summary.projects?.by_status || {};
        const keys = Object.keys(bySt).sort();
        const data = keys.map((k) => Number(bySt[k]));
        const polarColors = keys.map((_, i) => {
            const a = [0.78, 0.72, 0.68, 0.64][i % 4];
            const rgb = ['154, 0, 54', '13, 148, 136', '217, 119, 6', '79, 70, 229'][i % 4];
            return `rgba(${rgb}, ${a})`;
        });
        overviewPolarStatusChart = new Chart(polarEl, {
            type: 'polarArea',
            data: {
                labels: keys.map(statusLabel),
                datasets: [
                    {
                        data,
                        backgroundColor: polarColors,
                        borderWidth: 2,
                        borderColor: '#fff',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: anim,
                plugins: {
                    legend: legendBottom(chartColors.text),
                    tooltip: {
                        ...tt,
                        callbacks: {
                            label(ctx) {
                                return `${ctx.label}: ${formatInt(ctx.raw)}`;
                            },
                        },
                    },
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        ticks: { color: chartColors.text, backdropColor: 'transparent', showLabelBackdrop: false },
                        grid: { color: 'rgba(15, 23, 42, 0.07)' },
                        angleLines: { color: 'rgba(15, 23, 42, 0.09)' },
                        pointLabels: { font: chartFont(10), color: chartColors.text },
                    },
                },
            },
        });
    }

    if (hasFunnelChartData.value && funnelEl) {
        const f = summary.innovation_funnel || {};
        const funnelRgb = ['154, 0, 54', '13, 148, 136', '217, 119, 6'];
        overviewFunnelBarChart = new Chart(funnelEl, {
            type: 'bar',
            data: {
                labels: [
                    t('dashboard.funnel.submitted'),
                    t('dashboard.funnel.poc'),
                    t('dashboard.funnel.applied'),
                ],
                datasets: [
                    {
                        data: [
                            Number(f.submitted ?? 0),
                            Number(f.poc ?? 0),
                            Number(f.applied ?? 0),
                        ],
                        backgroundColor(context) {
                            const { chart, dataIndex } = context;
                            const { ctx: c, chartArea } = chart;
                            return barVerticalGradient(c, chartArea, funnelRgb[dataIndex]);
                        },
                        borderRadius: 10,
                        borderSkipped: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: anim,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        ...tt,
                        callbacks: {
                            label(ctx) {
                                return `${formatInt(ctx.raw)}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: chartColors.text, font: chartFont(11) },
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

    if (hasWorkloadChartData.value && workloadEl) {
        const rows = (summary.workload_open_tasks || []).slice(0, 16);
        overviewWorkloadBarChart = new Chart(workloadEl, {
            type: 'bar',
            data: {
                labels: rows.map((r) => truncateAxisLabel(r.assignee_name, 24)),
                datasets: [
                    {
                        label: t('dashboard.table.openTasks'),
                        data: rows.map((r) => Number(r.open_tasks)),
                        backgroundColor(context) {
                            const { chart, dataIndex } = context;
                            const { ctx: c, chartArea } = chart;
                            const rgb = rgbCycle[dataIndex % rgbCycle.length];
                            return barVerticalGradient(c, chartArea, rgb);
                        },
                        borderRadius: 6,
                        borderSkipped: false,
                    },
                ],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: anim,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        ...tt,
                        callbacks: {
                            title(items) {
                                const i = items[0]?.dataIndex ?? 0;
                                return rows[i]?.assignee_name ?? '';
                            },
                            label(ctx) {
                                return `${formatInt(ctx.raw)}`;
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
                        ticks: { color: chartColors.text, font: chartFont(10) },
                        grid: { display: false },
                    },
                },
            },
        });
    }

    const typeEl = typeChartRef.value;

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
                        borderRadius: 8,
                        hoverOffset: 8,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '58%',
                animation: anim,
                plugins: {
                    legend: legendBottom(chartColors.text),
                    tooltip: {
                        ...tt,
                        callbacks: {
                            label(ctx) {
                                const v = ctx.raw ?? 0;
                                return ` ${ctx.label}: ${formatInt(v)}`;
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

    const anim = chartAnimation();
    const tt = tooltipPremium();
    const rgbCycle = ['154, 0, 54', '13, 148, 136', '217, 119, 6', '79, 70, 229', '225, 29, 72'];

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
                        backgroundColor(context) {
                            const { chart, dataIndex } = context;
                            const { ctx: c, chartArea } = chart;
                            const rgb = rgbCycle[dataIndex % rgbCycle.length];
                            return barVerticalGradient(c, chartArea, rgb);
                        },
                        borderRadius: 8,
                        borderSkipped: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: anim,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        ...tt,
                        callbacks: {
                            label(ctx) {
                                return `${ctx.label}: ${formatInt(ctx.raw)}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: chartColors.text, maxRotation: 45, minRotation: 0, font: chartFont(10) },
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
                        backgroundColor(context) {
                            const { chart } = context;
                            const { ctx: c, chartArea } = chart;
                            return barVerticalGradient(c, chartArea, '13, 148, 136');
                        },
                        borderRadius: 8,
                        borderSkipped: false,
                    },
                    {
                        label: t('dashboard.taskReport.hoursActual'),
                        data: [Number(all.actual), Number(done.actual)],
                        backgroundColor(context) {
                            const { chart } = context;
                            const { ctx: c, chartArea } = chart;
                            return barVerticalGradient(c, chartArea, '154, 0, 54');
                        },
                        borderRadius: 8,
                        borderSkipped: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: anim,
                plugins: {
                    legend: { labels: { color: chartColors.textDark, font: chartFont(11) } },
                    tooltip: {
                        ...tt,
                        callbacks: {
                            label(ctx) {
                                return `${ctx.dataset.label}: ${formatHours(ctx.raw)}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: chartColors.text, font: chartFont(10) },
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
                        borderRadius: 8,
                        hoverOffset: 8,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '58%',
                animation: anim,
                plugins: {
                    legend: legendBottom(chartColors.text),
                    tooltip: {
                        ...tt,
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
                        borderRadius: 8,
                        hoverOffset: 8,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '58%',
                animation: anim,
                plugins: {
                    legend: legendBottom(chartColors.text),
                    tooltip: {
                        ...tt,
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
                        backgroundColor(context) {
                            const { chart, dataIndex } = context;
                            const { ctx: c, chartArea } = chart;
                            const rgb = rgbCycle[dataIndex % rgbCycle.length];
                            return barVerticalGradient(c, chartArea, rgb);
                        },
                        borderRadius: 6,
                        borderSkipped: false,
                    },
                ],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: anim,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        ...tt,
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
                        ticks: { color: chartColors.text, precision: 0, font: chartFont(10) },
                        grid: { color: chartColors.grid },
                    },
                    y: {
                        ticks: { color: chartColors.text, font: chartFont(10) },
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
