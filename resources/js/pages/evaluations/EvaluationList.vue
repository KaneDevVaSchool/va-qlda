<template>
    <div class="ppms-page ppms-lean-eval">
        <header class="ppms-lean-hero">
            <div class="ppms-lean-hero__text">
                <span class="ppms-lean-badge">{{ t('evaluationsPage.leanBadge') }}</span>
                <h1 class="ppms-lean-hero__title">{{ t('evaluationsPage.pageTitle') }}</h1>
                <p class="ppms-muted ppms-lean-hero__sub">{{ t('evaluationsPage.pageDescription') }}</p>
                <p class="ppms-lean-hero__formula">{{ t('evaluationsPage.leanIntro') }}</p>
            </div>
            <div v-if="gradeLegend.length" class="ppms-lean-legend" role="list">
                <div v-for="row in gradeLegend" :key="row.grade" class="ppms-lean-legend__item" role="listitem">
                    <span class="ppms-lean-legend__g">{{ row.grade }}</span>
                    <span class="ppms-lean-legend__txt">{{ row.summary_key ? t(row.summary_key) : row.summary }}</span>
                </div>
            </div>
        </header>

        <section v-if="extras" class="ppms-card ppms-lean-framework-ref ppms-mt">
            <h2 class="ppms-lean-h2">{{ t('evaluationsPage.frameworkRefTitle') }}</h2>
            <p class="ppms-muted ppms-lean-hint">{{ t('evaluationsPage.frameworkRefLead', { v: extras.framework_version ?? extras.excel_version ?? '2.0' }) }}</p>
            <details class="ppms-lean-details">
                <summary>{{ t('evaluationsPage.leanGradingScaleSection') }}</summary>
                <div class="ppms-lean-table-wrap">
                    <table class="ppms-lean-table">
                        <thead>
                            <tr>
                                <th>{{ t('evaluationsPage.colGrade') }}</th>
                                <th>{{ t('evaluationsPage.colPoints') }}</th>
                                <th>{{ t('evaluationsPage.colKaizenRule') }}</th>
                                <th>{{ t('evaluationsPage.colOutcome') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in extras.grading_scale || []" :key="row.grade">
                                <td><strong>{{ row.grade }}</strong></td>
                                <td>{{ row.points }}</td>
                                <td>{{ gradingDetailField(row.grade, 'kaizen_rule') }}</td>
                                <td>{{ gradingDetailField(row.grade, 'outcome') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </details>
            <details class="ppms-lean-details">
                <summary>{{ t('evaluationsPage.leanLevelMatrixSection') }}</summary>
                <p class="ppms-muted ppms-lean-hint">{{ t('evaluationsPage.levelMatrixHint') }}</p>
                <div class="ppms-lean-table-wrap ppms-lean-table-wrap--scroll">
                    <table class="ppms-lean-table ppms-lean-table--matrix">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ t('evaluationsPage.colDimension') }}</th>
                                <th>{{ t('evaluationsPage.careerJunior') }}</th>
                                <th>{{ t('evaluationsPage.careerMiddle') }}</th>
                                <th>{{ t('evaluationsPage.careerSenior') }}</th>
                                <th>{{ t('evaluationsPage.careerLead') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in extras.level_matrix || []" :key="row.dim">
                                <td>{{ row.dim }}</td>
                                <td>{{ row.name_key ? t(row.name_key) : row.name }}</td>
                                <td>{{ levelMatrixCell(row.dim, 'junior') }}</td>
                                <td>{{ levelMatrixCell(row.dim, 'middle') }}</td>
                                <td>{{ levelMatrixCell(row.dim, 'senior') }}</td>
                                <td>{{ levelMatrixCell(row.dim, 'lead') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </details>
            <p v-if="extras?.kaizen_log_policy_key" class="ppms-lean-policy ppms-mt-sm">{{ t(extras.kaizen_log_policy_key) }}</p>
        </section>

        <section v-if="canEdit && teamDashboard" class="ppms-card ppms-lean-team-dash ppms-mt">
            <div class="ppms-lean-team-dash__head">
                <h2 class="ppms-lean-h2">{{ t('evaluationsPage.teamDashTitle') }}</h2>
                <label class="ppms-field ppms-field--inline">
                    <span>{{ t('evaluationsPage.filterPeriod') }}</span>
                    <input v-model="teamDashPeriod" class="ppms-input" placeholder="2026-Q2" @change="loadTeamDashboard" />
                </label>
            </div>
            <p class="ppms-muted ppms-lean-hint">{{ t('evaluationsPage.teamDashHint') }}</p>
            <div v-if="teamDashboard.grade_distribution" class="ppms-lean-dist">
                <span v-for="(n, g) in teamDashboard.grade_distribution" :key="g" class="ppms-lean-dist__item">
                    <strong>{{ g }}</strong>: {{ n }}
                    <span v-if="extras?.distribution_hint_keys?.[g]" class="ppms-lean-dist__hint"> — {{ t(extras.distribution_hint_keys[g]) }}</span>
                </span>
            </div>
            <div class="ppms-lean-table-wrap ppms-mt-sm">
                <table class="ppms-lean-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ t('evaluationsPage.colName') }}</th>
                            <th>{{ t('evaluationsPage.colCareer') }}</th>
                            <th>P1</th>
                            <th>P2</th>
                            <th>P3</th>
                            <th>{{ t('evaluationsPage.totalLabel') }}</th>
                            <th>{{ t('evaluationsPage.gradeLabel') }}</th>
                            <th>Kaizen ✓</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in teamDashboard.ranking || []" :key="row.evaluation_id">
                            <td>{{ row.rank }}</td>
                            <td>{{ row.name }}</td>
                            <td>{{ careerLevelLabel(row.career_level) }}</td>
                            <td>{{ row.p1 ?? '—' }}</td>
                            <td>{{ row.p2 ?? '—' }}</td>
                            <td>{{ row.p3 ?? '—' }}</td>
                            <td>{{ row.total ?? '—' }}</td>
                            <td><strong>{{ row.grade }}</strong></td>
                            <td>{{ row.kaizen_verified ? '✓' : '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <h3 class="ppms-lean-h3">{{ t('evaluationsPage.heatmapTitle') }}</h3>
            <p v-if="extras?.heatmap_guide_key" class="ppms-muted ppms-lean-hint">{{ t(extras.heatmap_guide_key) }}</p>
            <div class="ppms-lean-table-wrap ppms-lean-table-wrap--scroll">
                <table class="ppms-lean-table">
                    <thead>
                        <tr>
                            <th>{{ t('evaluationsPage.colName') }}</th>
                            <th v-for="pl in teamDashboard.period_columns || []" :key="pl">{{ pl }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="hr in teamDashboard.heatmap || []" :key="hr.person_id">
                            <td>{{ hr.name }}</td>
                            <td v-for="pl in teamDashboard.period_columns || []" :key="pl + '-' + hr.person_id">
                                <span class="ppms-lean-heat-cell" :class="heatmapClass(hr.cells[pl])">{{ hr.cells[pl] != null ? hr.cells[pl] : '—' }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div v-if="canEdit" class="ppms-lean-grid">
            <section class="ppms-card ppms-lean-panel">
                <h2 class="ppms-lean-h2">{{ t('evaluationsPage.criteriaTitle') }}</h2>
                <div class="ppms-lean-form-row">
                    <label class="ppms-field ppms-field--inline">
                        <span>{{ t('evaluationsPage.trackLabel') }}</span>
                        <select v-model="leanForm.lean_track" class="ppms-select" @change="onTrackChange">
                            <option v-for="(def, key) in framework?.tracks || {}" :key="key" :value="key">
                                {{ def?.label_key ? t(def.label_key) : key }}
                            </option>
                        </select>
                    </label>
                    <label class="ppms-field ppms-field--inline">
                        <span>{{ t('evaluationsPage.projectLabel') }}</span>
                        <select v-model="leanForm.project_id" class="ppms-select" @change="fetchTaskContext">
                            <option value="">{{ t('projects.filterTeamAll') }}</option>
                            <option v-for="p in projectOptions" :key="'p-' + p.id" :value="String(p.id)">{{ p.name }}</option>
                        </select>
                    </label>
                    <label class="ppms-field ppms-field--inline">
                        <span>{{ t('evaluationsPage.careerLevelLabel') }}</span>
                        <select v-model="leanForm.career_level" class="ppms-select">
                            <option value="">{{ t('evaluationsPage.careerUnset') }}</option>
                            <option value="junior">{{ t('evaluationsPage.careerJunior') }}</option>
                            <option value="middle">{{ t('evaluationsPage.careerMiddle') }}</option>
                            <option value="senior">{{ t('evaluationsPage.careerSenior') }}</option>
                            <option value="lead">{{ t('evaluationsPage.careerLead') }}</option>
                        </select>
                    </label>
                </div>
                <p class="ppms-muted ppms-lean-hint">{{ t('evaluationsPage.projectHint') }}</p>
                <div class="ppms-lean-form-row ppms-mt-sm">
                    <label class="ppms-field ppms-field--inline">
                        <span>period</span>
                        <select v-model="leanForm.period_type" class="ppms-select">
                            <option value="quarterly">quarterly</option>
                            <option value="annual">annual</option>
                        </select>
                    </label>
                    <label class="ppms-field ppms-field--inline">
                        <span>label</span>
                        <input v-model="leanForm.period_label" class="ppms-input" placeholder="2026-Q2" @change="onPersonOrPeriodChange" />
                    </label>
                    <label class="ppms-field ppms-field--inline">
                        <span>person_id</span>
                        <input v-model.number="leanForm.person_id" type="number" class="ppms-input" @change="onPersonOrPeriodChange" />
                    </label>
                </div>

                <div v-for="pillar in ['p1', 'p2', 'p3']" :key="pillar" class="ppms-lean-pillar">
                    <h3 class="ppms-lean-pillar__title">{{ pillarTitle(pillar) }}</h3>
                    <div
                        v-for="c in criteriaForPillar(pillar)"
                        :key="c.id"
                        class="ppms-lean-criterion"
                    >
                        <div class="ppms-lean-criterion__head">
                            <span class="ppms-lean-criterion__code">{{ c.code }}</span>
                            <span class="ppms-lean-criterion__name">{{ criterionT(c, 'title') }}</span>
                            <span class="ppms-lean-criterion__w">{{ weightPct(c.weight) }}</span>
                        </div>
                        <p v-if="criterionHas(c, 'cluster')" class="ppms-lean-criterion__cluster">{{ criterionT(c, 'cluster') }}</p>
                        <p class="ppms-lean-criterion__hint">{{ criterionT(c, 'hint') }}</p>
                        <p v-if="criterionHas(c, 'frequency') || criterionHas(c, 'dataSource')" class="ppms-lean-criterion__meta">
                            <span v-if="criterionHas(c, 'frequency')">{{ t('evaluationsPage.metaFrequency') }}: {{ criterionT(c, 'frequency') }}</span>
                            <span v-if="criterionHas(c, 'frequency') && criterionHas(c, 'dataSource')"> · </span>
                            <span v-if="criterionHas(c, 'dataSource')">{{ t('evaluationsPage.metaSource') }}: {{ criterionT(c, 'dataSource') }}</span>
                        </p>
                        <div class="ppms-lean-score-row" role="group" :aria-label="criterionT(c, 'title')">
                            <button
                                v-for="n in 5"
                                :key="n"
                                type="button"
                                class="ppms-lean-score-btn"
                                :class="{ 'ppms-lean-score-btn--on': scoreFor(c.id) === n }"
                                @click="setScore(c.id, n)"
                            >
                                {{ n }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="ppms-lean-kaizen ppms-mt">
                    <label class="ppms-lean-check">
                        <input v-model="leanForm.kaizen_verified" type="checkbox" />
                        <span>{{ t('evaluationsPage.kaizenVerifiedLabel') }}</span>
                    </label>
                    <p class="ppms-muted ppms-lean-hint">{{ t('evaluationsPage.kaizenVerifiedHint') }}</p>
                    <label class="ppms-field ppms-field--stack ppms-mt-sm">
                        <span>{{ t('evaluationsPage.kaizenActionLabel') }}</span>
                        <textarea
                            v-model="leanForm.kaizen_action"
                            rows="2"
                            class="ppms-input ppms-lean-textarea"
                            :placeholder="t('evaluationsPage.kaizenActionPlaceholder')"
                        />
                    </label>
                </div>

                <p v-if="leanErr" class="ppms-error ppms-mt-sm">{{ leanErr }}</p>
                <button type="button" class="ppms-btn-primary ppms-mt" :disabled="leanSaving" @click="saveLean">
                    {{ t('evaluationsPage.btnCreateLean') }}
                </button>
            </section>

            <aside class="ppms-lean-side">
                <section class="ppms-card ppms-lean-preview">
                    <h2 class="ppms-lean-h2">{{ t('evaluationsPage.previewTitle') }}</h2>
                    <p v-if="preview?.kaizen_cap_applied" class="ppms-lean-warn" role="status">
                        {{ t('evaluationsPage.kaizenCapWarning') }}
                    </p>
                    <div v-if="preview" class="ppms-lean-preview__nums">
                        <div class="ppms-lean-kpi">
                            <span class="ppms-lean-kpi__l">P1</span>
                            <strong>{{ preview.p1 ?? '—' }}</strong>
                        </div>
                        <div class="ppms-lean-kpi">
                            <span class="ppms-lean-kpi__l">P2</span>
                            <strong>{{ preview.p2 ?? '—' }}</strong>
                        </div>
                        <div class="ppms-lean-kpi">
                            <span class="ppms-lean-kpi__l">P3</span>
                            <strong>{{ preview.p3 ?? '—' }}</strong>
                        </div>
                        <div class="ppms-lean-kpi ppms-lean-kpi--total">
                            <span class="ppms-lean-kpi__l">{{ t('evaluationsPage.totalLabel') }}</span>
                            <strong>{{ preview.total ?? '—' }}</strong>
                        </div>
                        <div class="ppms-lean-kpi ppms-lean-kpi--grade">
                            <span class="ppms-lean-kpi__l">{{ t('evaluationsPage.gradeLabel') }}</span>
                            <strong class="ppms-lean-grade">{{ preview.grade ?? '—' }}</strong>
                        </div>
                        <div v-if="preview.kaizen_cap_applied && preview.p2_raw != null" class="ppms-lean-kpi ppms-lean-kpi--raw">
                            <span class="ppms-lean-kpi__l">{{ t('evaluationsPage.p2RawLabel') }}</span>
                            <strong>{{ preview.p2_raw }}</strong>
                        </div>
                    </div>
                    <div v-if="pillarPreviewRows.length" class="ppms-lean-pillar-bars">
                        <div class="ppms-lean-pillar-bars__title">{{ t('evaluationsPage.pillarBarsTitle') }}</div>
                        <div v-for="row in pillarPreviewRows" :key="row.key" class="ppms-lean-pillar-bar-row">
                            <span class="ppms-lean-pillar-bar-row__l">{{ row.label }}</span>
                            <div class="ppms-lean-pillar-bar-track" :aria-valuenow="row.val" role="progressbar">
                                <div class="ppms-lean-pillar-bar-fill" :style="{ width: row.pct + '%' }" />
                            </div>
                            <span class="ppms-lean-pillar-bar-row__v">{{ row.val }}</span>
                        </div>
                    </div>
                    <p class="ppms-muted ppms-mt-sm">{{ t('evaluationsPage.scoreHint') }}</p>
                    <div class="ppms-lean-radar-host">
                        <canvas ref="radarCanvasRef" height="240" />
                    </div>
                </section>

                <section v-if="taskCtx" class="ppms-card ppms-mt">
                    <h2 class="ppms-lean-h2">{{ t('evaluationsPage.taskCtxTitle') }}</h2>
                    <p class="ppms-muted ppms-lean-hint">{{ t('evaluationsPage.taskCtxHint') }}</p>
                    <ul class="ppms-lean-ctx-list">
                        <li>{{ taskCtx.tasks_total }} tasks (scope: {{ taskCtx.scope }})</li>
                        <li>{{ taskCtx.tasks_done }} done · {{ taskCtx.tasks_open }} open</li>
                        <li>{{ taskCtx.tasks_overdue_open }} overdue (open)</li>
                        <li v-if="taskCtx.on_time_done_rate_pct != null">On-time (done): {{ taskCtx.on_time_done_rate_pct }}%</li>
                    </ul>
                </section>

                <section v-if="kaizenCtx" class="ppms-card ppms-mt">
                    <h2 class="ppms-lean-h2">{{ t('evaluationsPage.kaizenCtxTitle') }}</h2>
                    <p class="ppms-muted ppms-lean-hint">{{ t('evaluationsPage.kaizenCtxHint') }}</p>
                    <ul class="ppms-lean-ctx-list">
                        <li>{{ t('evaluationsPage.kaizenVerifiedCount') }}: {{ kaizenCtx.kaizens_verified_count }}</li>
                        <li>{{ t('evaluationsPage.kaizenTotalScope') }}: {{ kaizenCtx.kaizens_total_in_scope }}</li>
                    </ul>
                </section>
            </aside>
        </div>

        <section v-if="canEdit" class="ppms-card ppms-mt ppms-lean-legacy">
            <h2>{{ t('evaluationsPage.legacySection') }}</h2>
            <form class="ppms-stack ppms-mt-sm" @submit.prevent="createEval">
                <div class="ppms-task-form">
                    <select v-model="form.period_type" required>
                        <option value="quarterly">quarterly</option>
                        <option value="annual">annual</option>
                    </select>
                    <input v-model="form.period_label" placeholder="2026-Q1" required />
                    <input v-model.number="form.person_id" type="number" placeholder="Person id" required />
                    <input v-model.number="form.p1" type="number" step="0.1" min="0" max="100" placeholder="P1" />
                    <input v-model.number="form.p2" type="number" step="0.1" min="0" max="100" placeholder="P2" />
                    <input v-model.number="form.p3" type="number" step="0.1" min="0" max="100" placeholder="P3" />
                </div>
                <p v-if="err" class="ppms-error">{{ err }}</p>
                <button type="submit" class="ppms-btn-secondary">Save legacy draft</button>
            </form>
        </section>

        <section v-if="canEdit" class="ppms-card ppms-mt">
            <h2>Peer review (BR-3P-03)</h2>
            <form class="ppms-task-form ppms-mt-sm" @submit.prevent="addPeer">
                <input v-model.number="peerForm.evaluation_id" type="number" placeholder="Evaluation id" required />
                <input v-model.number="peerForm.reviewer_id" type="number" placeholder="Reviewer user id" required />
                <input v-model.number="peerForm.attitude_score" type="number" step="0.1" min="0" max="100" />
                <button type="submit" class="ppms-btn-primary">Add peer</button>
            </form>
            <p v-if="peerErr" class="ppms-error">{{ peerErr }}</p>
        </section>

        <section class="ppms-card ppms-mt">
            <h2>Danh sách</h2>
            <div v-if="loading" class="ppms-loading-line" role="status">…</div>
            <div v-else class="ppms-table-scroll">
                <table class="ppms-table">
                    <thead>
                        <tr>
                            <th>Kỳ</th>
                            <th>{{ t('evaluationsPage.colMode') }}</th>
                            <th>Nhân sự</th>
                            <th>{{ t('evaluationsPage.colProject') }}</th>
                            <th>{{ t('evaluationsPage.colCareer') }}</th>
                            <th>P1 / P2 / P3</th>
                            <th>Tổng</th>
                            <th>Grade</th>
                            <th>Status</th>
                            <th>PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in items" :key="e.id">
                            <td>{{ e.period_type }} {{ e.period_label }}</td>
                            <td><span class="ppms-lean-mode">{{ e.scoring_mode || 'legacy' }}</span></td>
                            <td>{{ e.person?.name }}</td>
                            <td>{{ e.project?.name || '—' }}</td>
                            <td>{{ careerLevelLabel(e.career_level) }}</td>
                            <td>{{ e.p1 }} / {{ e.p2 }} / {{ e.p3 }}</td>
                            <td>{{ e.total }}</td>
                            <td>{{ e.grade }}</td>
                            <td>{{ e.status }}</td>
                            <td>
                                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="downloadPdf(e)">PDF</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<script setup>
import {
    Chart,
    Filler,
    Legend,
    LineElement,
    PointElement,
    RadialLinearScale,
    RadarController,
    Tooltip,
} from 'chart.js';
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '@/bootstrap';
import { ppmsConfirm, ppmsToastError, ppmsToastSuccess } from '@/ppmsUi';

Chart.register(RadarController, RadialLinearScale, PointElement, LineElement, Filler, Tooltip, Legend);

const { t, te } = useI18n();
const route = useRoute();

const items = ref([]);
const loading = ref(true);
const err = ref('');
const peerErr = ref('');
const leanErr = ref('');
const leanSaving = ref(false);
const me = ref(null);
const framework = ref(null);
const extras = computed(() => framework.value?.extras || null);
const preview = ref(null);
const taskCtx = ref(null);
const kaizenCtx = ref(null);
const teamDashboard = ref(null);
const teamDashPeriod = ref('');
const projectOptions = ref([]);
const radarCanvasRef = ref(null);
let radarChart;

const peerForm = reactive({
    evaluation_id: null,
    reviewer_id: null,
    attitude_score: 75,
});
const form = reactive({
    period_type: 'quarterly',
    period_label: '2026-Q2',
    person_id: null,
    p1: 80,
    p2: 75,
    p3: 70,
});

const leanForm = reactive({
    period_type: 'quarterly',
    period_label: '2026-Q2',
    person_id: null,
    lean_track: 'dev',
    career_level: '',
    project_id: '',
    criteria_scores: {},
    kaizen_verified: false,
    kaizen_action: '',
});

const scores = reactive({});

const canEdit = computed(() => ['admin', 'pm', 'tl', 'hr'].includes(me.value?.role));

const gradeLegend = computed(() => framework.value?.grade_scale_legend || []);

const pillarPreviewRows = computed(() => {
    const p = preview.value;
    const pw = framework.value?.pillar_weights;
    if (!p || p.p1 == null || p.p2 == null || p.p3 == null || !pw) {
        return [];
    }
    const w1 = Math.round((Number(pw.p1) || 0) * 100);
    const w2 = Math.round((Number(pw.p2) || 0) * 100);
    const w3 = Math.round((Number(pw.p3) || 0) * 100);
    const v = (n) => Math.max(0, Math.min(5, Number(n) || 0));
    const row = (key, labelKey, pct, val) => ({
        key,
        label: `${t(labelKey)} (${pct}%)`,
        val: Number(val).toFixed(2),
        pct: (v(val) / 5) * 100,
    });

    return [
        row('p1', 'evaluationsPage.pillarP1', w1, p.p1),
        row('p2', 'evaluationsPage.pillarP2', w2, p.p2),
        row('p3', 'evaluationsPage.pillarP3', w3, p.p3),
    ];
});

const activeCriteria = computed(() => {
    const resolved = framework.value?.tracks_resolved?.[leanForm.lean_track];
    if (!resolved?.criteria?.length) {
        return [];
    }

    return resolved.criteria;
});

function criteriaForPillar(pillar) {
    return activeCriteria.value.filter((c) => c.pillar === pillar);
}

function pillarTitle(pillar) {
    if (pillar === 'p1') {
        return t('evaluationsPage.pillarP1');
    }
    if (pillar === 'p2') {
        return t('evaluationsPage.pillarP2');
    }

    return t('evaluationsPage.pillarP3');
}

function weightPct(w) {
    if (w == null) {
        return '';
    }

    return `${Math.round(Number(w) * 1000) / 10}%`;
}

function gradingDetailField(grade, field) {
    return t(`evaluationsPage.gradingDetail.${grade}.${field}`);
}

function levelMatrixCell(dim, tier) {
    return t(`evaluationsPage.levelMatrix.dim${dim}.${tier}`);
}

function criterionT(c, field) {
    return t(`evaluationsPage.criteria.${c.id}.${field}`);
}

function criterionHas(c, field) {
    return te(`evaluationsPage.criteria.${c.id}.${field}`);
}

function careerLevelLabel(code) {
    if (!code) {
        return '—';
    }
    const list = extras.value?.career_levels || [];
    const row = list.find((r) => r.id === code);

    return row?.label_key ? t(row.label_key) : String(code);
}

function scoreFor(id) {
    return scores[id] ?? null;
}

function setScore(id, n) {
    scores[id] = n;
    leanForm.criteria_scores[id] = n;
    schedulePreview();
}

function onTrackChange() {
    for (const k of Object.keys(scores)) {
        delete scores[k];
    }
    Object.keys(leanForm.criteria_scores).forEach((k) => delete leanForm.criteria_scores[k]);
    schedulePreview();
}

let previewTimer = null;
function schedulePreview() {
    if (previewTimer) {
        clearTimeout(previewTimer);
    }
    previewTimer = setTimeout(runPreview, 280);
}

async function runPreview() {
    const payload = {
        lean_track: leanForm.lean_track,
        criteria_scores: { ...scores },
        kaizen_verified: leanForm.kaizen_verified,
    };
    const has = Object.keys(payload.criteria_scores).length;
    if (!has) {
        preview.value = null;
        renderRadar(null);
        return;
    }
    try {
        const { data } = await axios.post('/api/lean-evaluation/preview', payload);
        preview.value = data;
        renderRadar(data?.radar || null);
    } catch {
        preview.value = null;
        renderRadar(null);
    }
}

function renderRadar(radar) {
    const el = radarCanvasRef.value;
    if (!el) {
        return;
    }
    if (radarChart) {
        radarChart.destroy();
        radarChart = null;
    }
    if (!radar || typeof radar !== 'object') {
        return;
    }
    let order = (framework.value?.radar_axes || []).map((a) => a.id);
    if (!order.length) {
        order = ['position', 'craft', 'kaizen', 'collab', 'delivery'];
    }
    const keys = order.filter((k) => Object.prototype.hasOwnProperty.call(radar, k));
    if (!keys.length) {
        return;
    }
    const labels = keys.map((k) => radarLabel(k));
    const data = keys.map((k) => Number(radar[k]) || 0);
    radarChart = new Chart(el, {
        type: 'radar',
        data: {
            labels,
            datasets: [
                {
                    label: t('evaluationsPage.radarTitle'),
                    data,
                    borderColor: 'rgba(154, 0, 54, 0.9)',
                    backgroundColor: 'rgba(154, 0, 54, 0.12)',
                    borderWidth: 2,
                    pointRadius: 3,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    min: 0,
                    max: 5,
                    ticks: { stepSize: 1 },
                },
            },
            plugins: {
                legend: { display: false },
            },
        },
    });
}

function radarLabel(key) {
    const axes = framework.value?.radar_axes || [];
    const ax = axes.find((a) => a.id === key);
    if (ax?.label_key) {
        return t(ax.label_key);
    }

    return key;
}

async function fetchTaskContext() {
    taskCtx.value = null;
    if (!leanForm.person_id) {
        return;
    }
    try {
        const params = { person_id: leanForm.person_id };
        if (leanForm.project_id) {
            params.project_id = leanForm.project_id;
        }
        const { data } = await axios.get('/api/lean-evaluation/task-context', { params });
        taskCtx.value = data;
    } catch {
        taskCtx.value = null;
    }
}

async function fetchKaizenContext() {
    kaizenCtx.value = null;
    if (!leanForm.person_id) {
        return;
    }
    try {
        const params = { person_id: leanForm.person_id };
        if (leanForm.period_label?.trim()) {
            params.period_label = leanForm.period_label.trim();
        }
        const { data } = await axios.get('/api/lean-evaluation/kaizen-context', { params });
        kaizenCtx.value = data;
    } catch {
        kaizenCtx.value = null;
    }
}

function onPersonOrPeriodChange() {
    fetchTaskContext();
    fetchKaizenContext();
}

async function loadTeamDashboard() {
    if (!canEdit.value) {
        return;
    }
    try {
        const params = {};
        if (teamDashPeriod.value?.trim()) {
            params.period_label = teamDashPeriod.value.trim();
        }
        const { data } = await axios.get('/api/lean-evaluation/team-dashboard', { params });
        teamDashboard.value = data;
    } catch {
        teamDashboard.value = null;
    }
}

function heatmapClass(v) {
    if (v == null || v === '') {
        return '';
    }
    const n = Number(v);
    if (Number.isNaN(n)) {
        return '';
    }
    if (n >= 4.5) {
        return 'ppms-lean-heat--hi';
    }
    if (n >= 4) {
        return 'ppms-lean-heat--good';
    }
    if (n >= 3) {
        return 'ppms-lean-heat--ok';
    }

    return 'ppms-lean-heat--low';
}

async function loadFramework() {
    const { data } = await axios.get('/api/lean-evaluation/framework');
    framework.value = data;
}

async function loadProjects() {
    try {
        const { data } = await axios.get('/api/projects', { params: { per_page: 100 } });
        const rows = data.data ?? data;
        projectOptions.value = Array.isArray(rows) ? rows : [];
    } catch {
        projectOptions.value = [];
    }
}

async function load() {
    loading.value = true;
    try {
        const params = {};
        if (route.query.project_id) {
            params.project_id = route.query.project_id;
        }
        const [u, list] = await Promise.all([axios.get('/api/user'), axios.get('/api/evaluations', { params })]);
        me.value = u.data;
        items.value = list.data.data || list.data;
    } finally {
        loading.value = false;
    }
}

async function saveLean() {
    leanErr.value = '';
    const criteria_scores = { ...scores };
    if (Object.keys(criteria_scores).length === 0) {
        leanErr.value = 'Chọn ít nhất một tiêu chí (1–5).';

        return;
    }
    if (!(await ppmsConfirm('Lưu đánh giá Lean với dữ liệu đã nhập?'))) {
        return;
    }
    leanSaving.value = true;
    try {
        await axios.post('/api/evaluations', {
            scoring_mode: 'lean',
            lean_track: leanForm.lean_track,
            period_type: leanForm.period_type,
            period_label: leanForm.period_label,
            person_id: leanForm.person_id,
            career_level: leanForm.career_level || null,
            project_id: leanForm.project_id ? Number(leanForm.project_id) : null,
            criteria_scores,
            kaizen_verified: leanForm.kaizen_verified,
            kaizen_action: leanForm.kaizen_action?.trim() || null,
        });
        ppmsToastSuccess('Đã lưu đánh giá Lean.');
        await load();
        await loadTeamDashboard();
    } catch (e) {
        leanErr.value = formatApiUserMessage(e, 'Không lưu được.');
    } finally {
        leanSaving.value = false;
    }
}

async function createEval() {
    err.value = '';
    if (!(await ppmsConfirm('Tạo đánh giá legacy?'))) {
        return;
    }
    try {
        await axios.post('/api/evaluations', {
            scoring_mode: 'legacy',
            period_type: form.period_type,
            period_label: form.period_label,
            person_id: form.person_id,
            p1: form.p1,
            p2: form.p2,
            p3: form.p3,
        });
        ppmsToastSuccess('OK');
        await load();
    } catch (e) {
        err.value = formatApiUserMessage(e, 'Lỗi');
    }
}

async function downloadPdf(e) {
    try {
        const res = await axios.get(`/api/evaluations/${e.id}/export-pdf`, { responseType: 'blob' });
        const url = URL.createObjectURL(res.data);
        const el = document.createElement('a');
        el.href = url;
        el.download = `ppms-3p-evaluation-${e.id}.pdf`;
        el.click();
        URL.revokeObjectURL(url);
    } catch (err) {
        ppmsToastError(formatApiUserMessage(err, 'PDF'));
    }
}

async function addPeer() {
    peerErr.value = '';
    if (!(await ppmsConfirm('Thêm peer?'))) {
        return;
    }
    try {
        await axios.post(`/api/evaluations/${peerForm.evaluation_id}/peers`, {
            reviewer_id: peerForm.reviewer_id,
            attitude_score: peerForm.attitude_score,
        });
        ppmsToastSuccess('OK');
        await load();
    } catch (e) {
        peerErr.value = formatApiUserMessage(e, 'Peer');
    }
}

watch(
    () => leanForm.lean_track,
    () => {
        nextTick(schedulePreview);
    },
);

watch(
    () => leanForm.kaizen_verified,
    () => {
        schedulePreview();
    },
);

onMounted(async () => {
    if (route.query.project_id) {
        leanForm.project_id = String(route.query.project_id);
    }
    await loadFramework();
    await loadProjects();
    await load();
    await nextTick();
    await runPreview();
    await fetchTaskContext();
    await fetchKaizenContext();
    if (['admin', 'pm', 'tl', 'hr'].includes(me.value?.role)) {
        await loadTeamDashboard();
    }
});

onBeforeUnmount(() => {
    if (radarChart) {
        radarChart.destroy();
        radarChart = null;
    }
});
</script>

<style scoped>
.ppms-lean-eval {
    max-width: 1180px;
    margin: 0 auto;
}
.ppms-lean-hero {
    display: grid;
    gap: 1.25rem;
    margin-bottom: 1.5rem;
    padding: 1.25rem 1.25rem 1.5rem;
    border-radius: 14px;
    background: linear-gradient(135deg, rgba(154, 0, 54, 0.08), rgba(13, 148, 136, 0.06));
    border: 1px solid rgba(15, 23, 42, 0.08);
}
.ppms-lean-badge {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #9a0036;
    margin-bottom: 0.35rem;
}
.ppms-lean-hero__title {
    margin: 0;
    font-size: 1.5rem;
}
.ppms-lean-hero__sub {
    margin: 0.35rem 0 0;
}
.ppms-lean-hero__formula {
    margin: 0.5rem 0 0;
    font-size: 0.9rem;
    color: #475569;
}
.ppms-lean-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 1rem;
    font-size: 0.8rem;
}
.ppms-lean-legend__item {
    display: flex;
    align-items: baseline;
    gap: 0.35rem;
}
.ppms-lean-legend__g {
    font-weight: 800;
    color: #9a0036;
    min-width: 1.25rem;
}
.ppms-lean-grid {
    display: grid;
    grid-template-columns: 1fr minmax(280px, 360px);
    gap: 1.25rem;
    align-items: start;
}
@media (max-width: 960px) {
    .ppms-lean-grid {
        grid-template-columns: 1fr;
    }
}
.ppms-lean-h2 {
    margin: 0 0 0.75rem;
    font-size: 1.1rem;
}
.ppms-lean-form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem 1rem;
}
.ppms-lean-hint {
    font-size: 0.85rem;
    margin: 0.35rem 0 0;
}
.ppms-lean-pillar {
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
}
.ppms-lean-pillar__title {
    margin: 0 0 0.75rem;
    font-size: 0.95rem;
    color: #0f172a;
}
.ppms-lean-criterion {
    margin-bottom: 1rem;
    padding: 0.75rem 0.85rem;
    border-radius: 10px;
    background: #fafafa;
    border: 1px solid rgba(15, 23, 42, 0.06);
}
.ppms-lean-criterion__head {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem 0.75rem;
    align-items: baseline;
}
.ppms-lean-criterion__code {
    font-weight: 700;
    color: #9a0036;
    font-size: 0.85rem;
}
.ppms-lean-criterion__name {
    flex: 1;
    font-weight: 600;
    font-size: 0.9rem;
}
.ppms-lean-criterion__w {
    font-size: 0.8rem;
    color: #64748b;
}
.ppms-lean-criterion__hint {
    margin: 0.35rem 0 0.5rem;
    font-size: 0.8rem;
    color: #64748b;
}
.ppms-lean-score-row {
    display: flex;
    gap: 0.35rem;
}
.ppms-lean-score-btn {
    min-width: 2.25rem;
    height: 2.25rem;
    border-radius: 8px;
    border: 1px solid rgba(15, 23, 42, 0.12);
    background: #fff;
    cursor: pointer;
    font-weight: 700;
}
.ppms-lean-score-btn--on {
    background: #9a0036;
    color: #fff;
    border-color: #9a0036;
}
.ppms-lean-preview__nums {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
}
.ppms-lean-kpi {
    padding: 0.5rem 0.65rem;
    border-radius: 8px;
    background: #f8fafc;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
}
.ppms-lean-kpi__l {
    color: #64748b;
    font-size: 0.8rem;
}
.ppms-lean-kpi--total {
    grid-column: 1 / -1;
    background: rgba(154, 0, 54, 0.06);
}
.ppms-lean-kpi--grade {
    grid-column: 1 / -1;
}
.ppms-lean-kpi--raw {
    grid-column: 1 / -1;
    background: rgba(180, 83, 9, 0.08);
}
.ppms-lean-warn {
    margin: 0 0 0.5rem;
    padding: 0.5rem 0.65rem;
    border-radius: 8px;
    font-size: 0.8rem;
    color: #92400e;
    background: rgba(251, 191, 36, 0.2);
}
.ppms-lean-check {
    display: flex;
    gap: 0.5rem;
    align-items: flex-start;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
}
.ppms-lean-check input {
    margin-top: 0.2rem;
}
.ppms-field--stack {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    width: 100%;
}
.ppms-lean-textarea {
    width: 100%;
    min-height: 3rem;
    resize: vertical;
}
.ppms-lean-pillar-bars {
    margin: 0.75rem 0 0;
    padding-top: 0.75rem;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
}
.ppms-lean-pillar-bars__title {
    font-size: 0.8rem;
    font-weight: 700;
    color: #475569;
    margin-bottom: 0.5rem;
}
.ppms-lean-pillar-bar-row {
    display: grid;
    grid-template-columns: 4.5rem 1fr 2.75rem;
    gap: 0.5rem;
    align-items: center;
    margin-bottom: 0.4rem;
    font-size: 0.8rem;
}
.ppms-lean-pillar-bar-row__l {
    color: #64748b;
}
.ppms-lean-pillar-bar-row__v {
    text-align: right;
    font-weight: 700;
    color: #0f172a;
}
.ppms-lean-pillar-bar-track {
    height: 9px;
    background: #e2e8f0;
    border-radius: 5px;
    overflow: hidden;
}
.ppms-lean-pillar-bar-fill {
    height: 100%;
    border-radius: 5px;
    min-width: 2px;
    background: linear-gradient(90deg, #0d9488, #9a0036);
}
.ppms-lean-grade {
    font-size: 1.35rem;
    color: #9a0036;
}
.ppms-lean-radar-host {
    margin-top: 0.75rem;
    height: 240px;
    position: relative;
}
.ppms-lean-ctx-list {
    margin: 0.5rem 0 0;
    padding-left: 1.1rem;
    font-size: 0.9rem;
    color: #334155;
}
.ppms-lean-legacy {
    opacity: 0.95;
}
.ppms-lean-mode {
    text-transform: lowercase;
    font-size: 0.85rem;
    color: #64748b;
}
.ppms-lean-framework-ref .ppms-lean-details {
    margin-top: 0.65rem;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 10px;
    padding: 0.5rem 0.75rem;
    background: #fafafa;
}
.ppms-lean-framework-ref summary {
    cursor: pointer;
    font-weight: 700;
    color: #0f172a;
}
.ppms-lean-policy {
    font-size: 0.8rem;
    color: #92400e;
    margin: 0.5rem 0 0;
}
.ppms-lean-table-wrap {
    overflow-x: auto;
    margin-top: 0.5rem;
}
.ppms-lean-table-wrap--scroll {
    max-height: 320px;
    overflow: auto;
}
.ppms-lean-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.78rem;
}
.ppms-lean-table th,
.ppms-lean-table td {
    border: 1px solid rgba(15, 23, 42, 0.1);
    padding: 0.35rem 0.45rem;
    text-align: left;
    vertical-align: top;
}
.ppms-lean-table--matrix th,
.ppms-lean-table--matrix td {
    min-width: 7rem;
}
.ppms-lean-team-dash__head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 0.75rem;
}
.ppms-lean-h3 {
    margin: 1rem 0 0.35rem;
    font-size: 0.95rem;
}
.ppms-lean-dist {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 1rem;
    font-size: 0.8rem;
    margin-top: 0.5rem;
}
.ppms-lean-dist__hint {
    color: #64748b;
    font-weight: 400;
}
.ppms-lean-heat-cell {
    display: inline-block;
    min-width: 2.25rem;
    padding: 0.1rem 0.35rem;
    border-radius: 4px;
    text-align: center;
}
.ppms-lean-heat--hi {
    background: rgba(13, 148, 136, 0.35);
    font-weight: 800;
}
.ppms-lean-heat--good {
    background: rgba(13, 148, 136, 0.18);
}
.ppms-lean-heat--ok {
    background: rgba(251, 191, 36, 0.25);
}
.ppms-lean-heat--low {
    background: rgba(239, 68, 68, 0.2);
}
.ppms-lean-criterion__cluster {
    margin: 0.2rem 0 0;
    font-size: 0.75rem;
    font-weight: 700;
    color: #0d9488;
}
.ppms-lean-criterion__meta {
    margin: 0.25rem 0 0.5rem;
    font-size: 0.75rem;
    color: #64748b;
}
</style>
