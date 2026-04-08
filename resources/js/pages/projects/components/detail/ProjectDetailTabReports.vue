<template>
    <div class="ppms-pd-tab-panel ppms-pd-report">
        <div class="ppms-pd-report-toolbar">
            <div class="ppms-pd-report-exports">
                <button v-if="canPdf" type="button" class="ppms-btn-primary ppms-btn-sm" @click="$emit('dl-weekly-pdf')">
                    {{ t('projects.pdDlWeeklyPdf') }}
                </button>
                <button v-if="canCsv" type="button" class="ppms-btn-primary ppms-btn-sm" @click="$emit('dl-projects-csv')">
                    {{ t('projects.pdDlProjectsCsv') }}
                </button>
                <router-link to="/reports" class="ppms-btn-ghost ppms-btn-sm">{{ t('projects.pdOpenReportsPage') }}</router-link>
            </div>
        </div>

        <div class="ppms-pd-report-subtabs" role="tablist">
            <button
                type="button"
                role="tab"
                class="ppms-pd-report-subtab"
                :class="{ 'is-active': reportSub === 'tasks' }"
                :aria-selected="reportSub === 'tasks'"
                @click="reportSub = 'tasks'"
            >
                {{ t('projects.pdReportSubTabTasks') }}
            </button>
            <button
                type="button"
                role="tab"
                class="ppms-pd-report-subtab"
                :class="{ 'is-active': reportSub === 'finance' }"
                :aria-selected="reportSub === 'finance'"
                @click="reportSub = 'finance'"
            >
                {{ t('projects.pdReportSubTabFinance') }}
            </button>
            <button
                type="button"
                role="tab"
                class="ppms-pd-report-subtab"
                :class="{ 'is-active': reportSub === 'supplies' }"
                :aria-selected="reportSub === 'supplies'"
                @click="reportSub = 'supplies'"
            >
                {{ t('projects.pdReportSubTabSupplies') }}
            </button>
        </div>

        <section v-show="reportSub === 'tasks'" class="ppms-card ppms-pd-section ppms-pd-report-section" aria-labelledby="pd-report-tasks-title">
            <h2 id="pd-report-tasks-title" class="ppms-pd-report-section-title">{{ t('projects.pdReportTasksSectionTitle') }}</h2>
            <p class="ppms-muted ppms-mt-sm">{{ t('projects.pdReportTableHint') }}</p>
            <div class="ppms-table-scroll ppms-mt-sm ppms-pd-report-table-wrap">
                <table v-if="taskReportRows.length" class="ppms-table ppms-pd-report-matrix">
                    <thead>
                        <tr>
                            <th rowspan="2" scope="col">{{ t('projects.pdReportColNo') }}</th>
                            <th rowspan="2" scope="col">{{ t('projects.pdReportColEmployeeCode') }}</th>
                            <th rowspan="2" scope="col">{{ t('projects.pdReportColPersonnel') }}</th>
                            <th rowspan="2" scope="col">{{ t('projects.pdReportColDept') }}</th>
                            <th rowspan="2" scope="col">{{ t('projects.pdReportColTotalTasks') }}</th>
                            <th colspan="3" scope="colgroup">{{ t('projects.pdReportGroupInProgress') }}</th>
                            <th colspan="3" scope="colgroup">{{ t('projects.pdReportGroupDone') }}</th>
                        </tr>
                        <tr>
                            <th scope="col">{{ t('projects.pdReportColQty') }}</th>
                            <th scope="col">{{ t('projects.pdReportColOnTime') }}</th>
                            <th scope="col">{{ t('projects.pdReportColOverdue') }}</th>
                            <th scope="col">{{ t('projects.pdReportColQty') }}</th>
                            <th scope="col">{{ t('projects.pdReportColOnTime') }}</th>
                            <th scope="col">{{ t('projects.pdReportColOverdue') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in taskReportRows" :key="'rpt-' + row.key">
                            <td class="ppms-td-num">{{ row.stt }}</td>
                            <td class="ppms-muted">{{ row.employeeCode }}</td>
                            <td>
                                <span class="ppms-pd-report-name">{{ row.name }}</span>
                                <span v-if="row.email" class="ppms-muted ppms-pd-report-email">{{ row.email }}</span>
                            </td>
                            <td class="ppms-muted">{{ deptLabel(row.dept) }}</td>
                            <td class="ppms-td-num">{{ row.total }}</td>
                            <td class="ppms-td-num">{{ openTotal(row) }}</td>
                            <td class="ppms-td-num">{{ fmtOpenOnTime(row) }}</td>
                            <td class="ppms-td-num">{{ fmtOpenOverdue(row) }}</td>
                            <td class="ppms-td-num">{{ doneTotal(row) }}</td>
                            <td class="ppms-td-num">{{ fmtDoneOnTime(row) }}</td>
                            <td class="ppms-td-num">{{ fmtDoneLate(row) }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="ppms-muted ppms-mt-sm">{{ t('projects.pdReportEmptyTasks') }}</p>
            </div>
        </section>

        <section v-show="reportSub === 'finance'" class="ppms-card ppms-pd-section ppms-pd-report-section">
            <h2 class="ppms-pd-report-section-title">{{ t('projects.pdReportFinanceTitle') }}</h2>
            <div class="ppms-pd-report-finance-grid ppms-mt-sm">
                <div class="ppms-pd-report-finance-card">
                    <span class="ppms-muted">{{ t('projects.createFieldEstimatedValue') }}</span>
                    <strong class="ppms-pd-report-finance-value">{{ financeEstimated }}</strong>
                </div>
                <div class="ppms-pd-report-finance-card">
                    <span class="ppms-muted">{{ t('projects.pdReportFinanceTotalEstHours') }}</span>
                    <strong class="ppms-pd-report-finance-value">{{ financeEstHours }}</strong>
                </div>
                <div class="ppms-pd-report-finance-card">
                    <span class="ppms-muted">{{ t('projects.pdReportFinanceTotalActHours') }}</span>
                    <strong class="ppms-pd-report-finance-value">{{ financeActHours }}</strong>
                </div>
            </div>
            <p class="ppms-muted ppms-mt-sm">{{ t('projects.pdReportFinanceFootnote') }}</p>
        </section>

        <section v-show="reportSub === 'supplies'" class="ppms-card ppms-pd-section ppms-pd-report-section">
            <h2 class="ppms-pd-report-section-title">{{ t('projects.pdReportSuppliesTitle') }}</h2>
            <p class="ppms-muted ppms-mt-sm">{{ t('projects.pdReportSuppliesHint') }}</p>
            <div v-if="suppliesList.length" class="ppms-table-scroll ppms-mt-sm">
                <table class="ppms-table">
                    <thead>
                        <tr>
                            <th>{{ t('projects.pdSupplyName') }}</th>
                            <th>{{ t('projects.pdSupplyQty') }}</th>
                            <th>{{ t('projects.pdSupplyUnit') }}</th>
                            <th>{{ t('projects.pdSupplyNotes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in suppliesList" :key="'rps-' + s.id">
                            <td>{{ s.name }}</td>
                            <td class="ppms-td-num">{{ s.quantity }}</td>
                            <td>{{ s.unit || '—' }}</td>
                            <td class="ppms-muted">{{ s.notes || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="ppms-muted ppms-mt-sm">{{ t('projects.pdSuppliesEmpty') }}</p>
        </section>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { buildPersonnelTaskReportRows, pct } from '../../utils/projectReportTaskTable';

const { t } = useI18n();

const props = defineProps({
    project: { type: Object, required: true },
    canPdf: { type: Boolean, default: false },
    canCsv: { type: Boolean, default: false },
});

defineEmits(['dl-weekly-pdf', 'dl-projects-csv']);

const reportSub = ref('tasks');

const taskReportRows = computed(() =>
    buildPersonnelTaskReportRows(props.project.tasks || [], {
        unassignedLabel: t('projects.pdReportUnassigned'),
    }),
);

const suppliesList = computed(() => props.project.supplies || []);

function deptLabel(role) {
    if (!role) {
        return '—';
    }
    const key = `layout.role.${role}`;
    const x = t(key);
    return x === key ? role : x;
}

function openTotal(row) {
    const n = row.openOnTime + row.openOverdue;
    return n || '—';
}

function doneTotal(row) {
    const n = row.doneOnTime + row.doneLate;
    return n || '—';
}

function fmtOpenOnTime(row) {
    return row.openOnTime || '—';
}

function fmtOpenOverdue(row) {
    const n = row.openOverdue;
    if (!n) {
        return '—';
    }
    const tot = row.openOnTime + row.openOverdue;
    const p = pct(n, tot);
    return p != null ? `${n} (${p}%)` : String(n);
}

function fmtDoneOnTime(row) {
    return row.doneOnTime || '—';
}

function fmtDoneLate(row) {
    const n = row.doneLate;
    if (!n) {
        return '—';
    }
    const tot = row.doneOnTime + row.doneLate;
    const p = pct(n, tot);
    return p != null ? `${n} (${p}%)` : String(n);
}

const financeEstimated = computed(() => {
    const v = props.project.estimated_value;
    if (v == null || v === '') {
        return '—';
    }
    const n = Number(v);
    if (Number.isNaN(n)) {
        return String(v);
    }
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(n);
});

const financeEstHours = computed(() => {
    let s = 0;
    for (const tk of props.project.tasks || []) {
        s += Number(tk.estimate_hours) || 0;
    }
    return s.toFixed(1);
});

const financeActHours = computed(() => {
    let s = 0;
    for (const tk of props.project.tasks || []) {
        s += Number(tk.actual_hours) || 0;
    }
    return s.toFixed(1);
});
</script>
