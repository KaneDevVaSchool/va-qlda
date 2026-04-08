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

        <section class="ppms-card ppms-pd-section ppms-pd-report-section" aria-labelledby="pd-report-tasks-title">
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
                <div v-else class="ppms-pd-report-empty-wrap">
                    <PpmsPdEmptyState
                        :title="t('projects.pdReportEmptyTasks')"
                        :description="t('projects.pdEmptyReportsDesc')"
                        heading-id="pd-report-empty-h"
                    >
                        <button type="button" class="ppms-btn-primary ppms-btn-sm" @click="$emit('go-tasks')">
                            {{ t('projects.pdEmptyReportsCta') }}
                        </button>
                    </PpmsPdEmptyState>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import PpmsPdEmptyState from './PpmsPdEmptyState.vue';
import { buildPersonnelTaskReportRows, pct } from '../../utils/projectReportTaskTable';

const { t } = useI18n();

const props = defineProps({
    project: { type: Object, required: true },
    canPdf: { type: Boolean, default: false },
    canCsv: { type: Boolean, default: false },
});

defineEmits(['dl-weekly-pdf', 'dl-projects-csv', 'go-tasks']);

const taskReportRows = computed(() =>
    buildPersonnelTaskReportRows(props.project.tasks || [], {
        unassignedLabel: t('projects.pdReportUnassigned'),
    }),
);

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
</script>
