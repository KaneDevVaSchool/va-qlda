<!-- eslint-disable vue/no-mutating-props -- parent reactive ganttFilters, csat -->
<template>
    <div class="ppms-pd-tab-panel">
        <section ref="tasksBoardRef" class="ppms-pd-tasks-shell ppms-card ppms-pd-section">
            <div class="ppms-pd-tasks-topbar">
                <div class="ppms-pd-tasks-topbar__title">
                    <h2 class="ppms-pd-tasks-board-title">{{ t('projects.pdTasksBoardTitle') }}</h2>
                    <div class="ppms-pd-tasks-topbar__icons" role="toolbar" :aria-label="t('projects.pdTasksBoardHint')">
                        <button type="button" class="ppms-pd-tasks-ico-btn" :title="t('projects.pdTasksZoomOut')" @click="zoomOut">
                            −
                        </button>
                        <button type="button" class="ppms-pd-tasks-ico-btn" :title="t('projects.pdTasksZoomIn')" @click="zoomIn">
                            +
                        </button>
                    </div>
                </div>
                <div class="ppms-pd-tasks-filters ppms-task-form">
                    <div class="ppms-pd-filter-field">
                        <label class="ppms-pd-filter-label" for="ppms-pd-filter-assignee">{{ t('projects.thAssigneeFull') }}</label>
                        <div class="ppms-pd-filter-control">
                            <select id="ppms-pd-filter-assignee" v-model="ganttFilters.assignee_id" class="ppms-pd-tasks-select">
                                <option value="">{{ t('projects.ganttFilterAssigneeAll') }}</option>
                                <option v-for="o in assigneeOptions" :key="o.id" :value="String(o.id)">
                                    {{ o.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="ppms-pd-filter-field">
                        <label class="ppms-pd-filter-label" for="ppms-pd-filter-status">{{ t('projects.thStatus') }}</label>
                        <div class="ppms-pd-filter-control">
                            <select id="ppms-pd-filter-status" v-model="ganttFilters.status" class="ppms-pd-tasks-select">
                                <option value="">{{ t('projects.ganttFilterStatusAll') }}</option>
                                <option v-for="sid in TASK_STATUS_IDS" :key="sid" :value="sid">
                                    {{ t('projects.taskStatus.' + sid) }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="ppms-pd-filter-field ppms-pd-filter-field--switch">
                        <span class="ppms-pd-filter-label ppms-pd-filter-label--spacer" aria-hidden="true">&nbsp;</span>
                        <label class="ppms-pd-filter-switch">
                            <input
                                id="ppms-pd-filter-root"
                                v-model="ganttFilters.root_only"
                                type="checkbox"
                                class="ppms-pd-filter-switch-input"
                            />
                            <span class="ppms-pd-filter-switch-ui" aria-hidden="true" />
                            <span class="ppms-pd-filter-switch-text">{{ t('projects.ganttRootOnly') }}</span>
                        </label>
                    </div>
                    <button type="button" class="ppms-pd-tasks-refresh" @click="$emit('refresh-gantt')">
                        {{ t('projects.ganttRefresh') }}
                    </button>
                </div>
            </div>

            <p v-if="gantt.window?.start" class="ppms-pd-tasks-window ppms-muted">
                {{
                    t('projects.ganttWindow', {
                        start: gantt.window.start,
                        end: gantt.window.end,
                        days: gantt.window.days,
                    })
                }}
            </p>

            <div class="ppms-pd-tasks-split">
                <div class="ppms-pd-tasks-col-left">
                    <div ref="leftScrollRef" class="ppms-pd-tasks-scroll" @scroll="onLeftScroll">
                        <table class="ppms-pd-tasks-table">
                            <thead>
                                <tr>
                                    <th class="ppms-pd-th-task">{{ t('projects.thTaskWork') }}</th>
                                    <th class="ppms-pd-th-assignee">{{ t('projects.thAssigneeFull') }}</th>
                                    <th class="ppms-pd-th-status">{{ t('projects.thStatus') }}</th>
                                    <th class="ppms-pd-th-date">{{ t('projects.thStart') }}</th>
                                    <th class="ppms-pd-th-date">{{ t('projects.thEnd') }}</th>
                                    <th class="ppms-pd-th-num">{{ t('projects.thWeightPct') }}</th>
                                    <th class="ppms-pd-th-due">{{ t('projects.thDeadlineCol') }}</th>
                                    <th class="ppms-pd-th-act" aria-label="actions" />
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!boardRows.length">
                                    <td colspan="8" class="ppms-pd-tasks-empty">{{ t('projects.ganttEmpty') }}</td>
                                </tr>
                                <template v-for="(row, idx) in boardRows" :key="row.kind === 'phase' ? 'ph-' + row.phaseKey : 'tk-' + row.task.id + '-' + idx">
                                    <tr v-if="row.kind === 'phase'" class="ppms-pd-tr-phase">
                                        <td colspan="8" class="ppms-pd-td-phase">
                                            <button
                                                type="button"
                                                class="ppms-pd-phase-toggle"
                                                :aria-expanded="!isPhaseCollapsed(row.phaseKey)"
                                                @click="togglePhase(row.phaseKey)"
                                            >
                                                <span
                                                    class="ppms-pd-phase-caret"
                                                    :class="{ 'is-collapsed': isPhaseCollapsed(row.phaseKey) }"
                                                    aria-hidden="true"
                                                    >▾</span
                                                >
                                                <span class="ppms-pd-phase-name">{{ phaseTitle(row) }}</span>
                                                <span class="ppms-pd-phase-count">({{ row.taskCount }})</span>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-else class="ppms-pd-tr-task" :class="{ 'is-overdue-row': isTaskOverdue(row.task) }">
                                        <td class="ppms-pd-td-name">
                                            <button
                                                type="button"
                                                class="ppms-pd-task-name"
                                                :class="{ 'is-overdue-text': isTaskOverdue(row.task) }"
                                                :style="{ paddingLeft: `${12 + row.depth * 16}px` }"
                                                @click="$emit('toggle-focus', row.task || { id: row.bar.task_id, name: row.bar.name })"
                                            >
                                                {{ row.bar.name }}
                                            </button>
                                        </td>
                                        <td class="ppms-pd-td-assignee">
                                            <span
                                                class="ppms-pd-tasks-avatar"
                                                :style="{ background: avatarColor(row.bar.assignee_name || row.bar.assignee_id || '?') }"
                                                :title="row.bar.assignee_name || t('projects.pdUnassigned')"
                                            >
                                                {{ initials(row.bar.assignee_name) }}
                                            </span>
                                        </td>
                                        <td class="ppms-pd-td-status">
                                            <span :class="['ppms-pd-status-pill', statusPillClass(row.bar.status)]">
                                                {{ ganttTaskStatusLabel(t, row.bar.status) }}
                                            </span>
                                        </td>
                                        <td class="ppms-pd-td-date">{{ formatYmdDisplay(row.bar.start) }}</td>
                                        <td class="ppms-pd-td-date">{{ formatYmdDisplay(row.bar.end) }}</td>
                                        <td class="ppms-pd-td-pct">{{ formatWeightPct(row.task) }}</td>
                                        <td class="ppms-pd-td-due">
                                            <span v-if="overdueParts(row.task)" class="ppms-pd-due-pill">
                                                {{ overdueLabel(row.task) }}
                                            </span>
                                            <span v-else class="ppms-pd-due-dash">—</span>
                                        </td>
                                        <td class="ppms-pd-td-actions">
                                            <button
                                                v-if="row.task"
                                                type="button"
                                                class="ppms-pd-task-del"
                                                :title="t('projects.deleteTask')"
                                                @click.stop="$emit('remove-task', row.task)"
                                            >
                                                ×
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="ppms-pd-tasks-col-right">
                    <div
                        ref="rightScrollRef"
                        class="ppms-pd-tasks-scroll ppms-pd-tasks-scroll--gantt"
                        :style="{ '--pd-gantt-day-w': dayColWidthPx + 'px' }"
                        @scroll="onRightScroll"
                    >
                        <div class="ppms-pd-gantt-hscroll-inner" :style="{ width: timelineInnerWidthPx + 'px' }">
                            <div class="ppms-pd-gantt-head">
                                <div class="ppms-pd-gantt-month">{{ ganttMonthLabel }}</div>
                                <div v-if="dayColumns.length" class="ppms-pd-gantt-daycols ppms-pd-gantt-daycols--nums" :style="dayGridStyle">
                                    <div v-for="col in dayColumns" :key="'n-' + col.key" class="ppms-pd-gantt-daycell">
                                        {{ col.dayNum }}
                                    </div>
                                </div>
                                <div v-if="dayColumns.length" class="ppms-pd-gantt-daycols ppms-pd-gantt-daycols--dow" :style="dayGridStyle">
                                    <div v-for="col in dayColumns" :key="'w-' + col.key" class="ppms-pd-gantt-daycell">
                                        {{ col.dowShort }}
                                    </div>
                                </div>
                            </div>
                            <div v-if="!boardRows.length" class="ppms-pd-gantt-track-row ppms-pd-gantt-track-row--empty">
                                <span class="ppms-muted">{{ t('projects.ganttEmpty') }}</span>
                            </div>
                            <template v-for="(row, gidx) in boardRows" :key="row.kind === 'phase' ? 'g-ph-' + row.phaseKey : 'g-tk-' + row.task.id + '-' + gidx">
                                <div v-if="row.kind === 'phase'" class="ppms-pd-gantt-track-row ppms-pd-gantt-track-row--phase" />
                                <div v-else class="ppms-pd-gantt-track-row">
                                    <div class="ppms-pd-gantt-track-bg">
                                        <div
                                            class="ppms-pd-gantt-bar-fill"
                                            :style="{
                                                marginLeft: `${row.bar.layout.left_pct}%`,
                                                width: `${row.bar.layout.width_pct}%`,
                                            }"
                                        />
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="project.type === 'delivery'" class="ppms-card ppms-pd-section ppms-pd-csat-block">
            <h2>{{ t('projects.csatTitle') }}</h2>
            <p v-if="project.csat_metrics" class="ppms-muted ppms-mt-sm">
                {{
                    t('projects.csatMetrics', {
                        count: project.csat_metrics.response_count,
                        invites: project.csat_metrics.invites_sent,
                        rate: project.csat_metrics.response_rate_pct,
                    })
                }}
            </p>
            <form class="ppms-task-form ppms-mt-sm" @submit.prevent="$emit('submit-csat')">
                <input v-model.number="csat.rating" type="number" min="1" max="5" required />
                <input v-model="csat.milestone_label" :placeholder="t('projects.csatMilestonePh')" />
                <button type="submit" class="ppms-btn-primary">{{ t('projects.csatSubmit') }}</button>
            </form>
            <p v-if="csatMsg" class="ppms-muted">{{ csatMsg }}</p>
        </section>

        <section v-if="focusTask" class="ppms-card ppms-pd-section">
            <h2>{{ t('projects.taskDetailTitle', { id: focusTask.id }) }}</h2>
            <div class="ppms-split">
                <div>
                    <h3>{{ t('projects.depTitle') }}</h3>
                    <form class="ppms-task-form" @submit.prevent="$emit('add-dep')">
                        <input v-model.number="depPredId" type="number" :placeholder="t('projects.depPredPh')" required />
                        <button type="submit" class="ppms-btn-primary">{{ t('common.add') }}</button>
                    </form>
                    <p v-if="depErr" class="ppms-error">{{ depErr }}</p>
                </div>
                <div>
                    <h3>{{ t('projects.attachTitle') }}</h3>
                    <input type="file" @change="$emit('task-file', $event)" />
                    <ul class="ppms-filelist">
                        <li v-for="a in attachments" :key="a.id">
                            <button type="button" class="ppms-linklike" @click="$emit('download-attachment', a)">
                                {{ a.original_name }}
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { barsWithTasks, buildBoardRows } from '../../utils/projectTasksBoard';
import { ganttTaskStatusLabel, initials } from '../../utils/projectDetailFormatters';

const { t, locale } = useI18n();

const depPredId = defineModel('depPredId', { type: [Number, String, null], default: null });

const props = defineProps({
    project: { type: Object, required: true },
    gantt: { type: Object, required: true },
    ganttFilters: { type: Object, required: true },
    assigneeOptions: { type: Array, required: true },
    csat: { type: Object, required: true },
    csatMsg: { type: String, default: '' },
    focusTask: { type: Object, default: null },
    depErr: { type: String, default: '' },
    attachments: { type: Array, required: true },
});

defineEmits([
    'submit-csat',
    'refresh-gantt',
    'toggle-focus',
    'remove-task',
    'add-dep',
    'task-file',
    'download-attachment',
]);

const tasksBoardRef = ref(null);
const leftScrollRef = ref(null);
const rightScrollRef = ref(null);
const syncingScroll = ref(false);
const ganttZoom = ref(1);
/** Các phaseKey đang thu gọn (ẩn danh sách task). */
const collapsedPhaseKeys = ref([]);

const BASE_DAY_PX = 26;

/** Trùng rule backend `TaskController` — lọc Gantt theo một trạng thái. */
const TASK_STATUS_IDS = ['todo', 'in_progress', 'done', 'blocked'];

const taskById = computed(() => {
    const m = new Map();
    for (const row of props.project?.tasks || []) {
        m.set(row.id, row);
    }
    return m;
});

const ganttItems = computed(() => barsWithTasks(props.gantt?.bars, taskById.value));

function isPhaseCollapsed(key) {
    return collapsedPhaseKeys.value.includes(key);
}

const boardRows = computed(() =>
    buildBoardRows(ganttItems.value, props.project?.phases || [], isPhaseCollapsed),
);

function togglePhase(key) {
    const i = collapsedPhaseKeys.value.indexOf(key);
    if (i >= 0) {
        collapsedPhaseKeys.value = collapsedPhaseKeys.value.filter((k) => k !== key);
    } else {
        collapsedPhaseKeys.value = [...collapsedPhaseKeys.value, key];
    }
}

function phaseTitle(row) {
    if (row.isOther) {
        return t('projects.pdTasksPhaseUncat');
    }

    return row.title || '—';
}

function hashHue(seed) {
    const s = String(seed ?? '');
    let h = 0;
    for (let i = 0; i < s.length; i++) {
        h = (h * 31 + s.charCodeAt(i)) >>> 0;
    }

    return h % 360;
}

function avatarColor(seed) {
    const hue = hashHue(seed);

    return `hsl(${hue} 48% 40%)`;
}

function parseYmd(s) {
    if (!s) {
        return null;
    }
    const parts = String(s).split('-');
    if (parts.length < 3) {
        return null;
    }
    const y = Number(parts[0]);
    const m = Number(parts[1]) - 1;
    const d = Number(parts[2]);

    return new Date(y, m, d);
}

function isTaskOverdue(task) {
    if (!task?.due_date || task.status === 'done') {
        return false;
    }
    const due = parseYmd(String(task.due_date).slice(0, 10));
    if (!due) {
        return false;
    }
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    due.setHours(0, 0, 0, 0);

    return due < now;
}

function overdueParts(task) {
    if (!task?.due_date || task.status === 'done') {
        return null;
    }
    const due = parseYmd(String(task.due_date).slice(0, 10));
    if (!due) {
        return null;
    }
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    due.setHours(0, 0, 0, 0);
    if (due >= now) {
        return null;
    }
    const ms = now.getTime() - due.getTime();
    const days = Math.floor(ms / 86400000);
    const hours = Math.floor((ms % 86400000) / 3600000);

    return { days, hours };
}

function overdueLabel(task) {
    const p = overdueParts(task);
    if (!p) {
        return '';
    }

    return t('projects.pdOverdueFmt', { days: p.days, hours: p.hours });
}

function formatWeightPct(task) {
    if (!task) {
        return '—';
    }
    const w = Number(task.weight);
    if (!Number.isFinite(w)) {
        return '—';
    }
    if (w >= 0 && w <= 1) {
        const pct = w * 100;

        return `${pct % 1 === 0 ? pct.toFixed(0) : pct.toFixed(2)}%`;
    }

    return `${w.toFixed(2)}%`;
}

const dayColumns = computed(() => {
    const w = props.gantt?.window;
    if (!w?.start || !w?.end) {
        return [];
    }
    const start = parseYmd(w.start);
    const end = parseYmd(w.end);
    if (!start || !end || start > end) {
        return [];
    }
    const loc = locale.value === 'vi' ? 'vi-VN' : 'en-US';
    const dowFmt = new Intl.DateTimeFormat(loc, { weekday: 'short' });
    const out = [];
    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
        const copy = new Date(d);
        const y = copy.getFullYear();
        const m = String(copy.getMonth() + 1).padStart(2, '0');
        const day = String(copy.getDate()).padStart(2, '0');
        out.push({
            key: `${y}-${m}-${day}`,
            dayNum: copy.getDate(),
            dowShort: dowFmt.format(copy),
        });
    }
    return out;
});

const ganttMonthLabel = computed(() => {
    const w = props.gantt?.window;
    if (!w?.start) {
        return '—';
    }
    const d = parseYmd(w.start);
    if (!d) {
        return '—';
    }
    const loc = locale.value === 'vi' ? 'vi-VN' : 'en-US';

    return new Intl.DateTimeFormat(loc, { month: 'long', year: 'numeric' }).format(d).toUpperCase();
});

const dayColWidthPx = computed(() => Math.round(BASE_DAY_PX * ganttZoom.value));

const timelineInnerWidthPx = computed(() => Math.max(320, dayColumns.value.length * dayColWidthPx.value));

const dayGridStyle = computed(() => ({
    gridTemplateColumns: `repeat(${dayColumns.value.length}, var(--pd-gantt-day-w, 26px))`,
}));

function zoomIn() {
    ganttZoom.value = Math.min(1.5, Math.round((ganttZoom.value + 0.1) * 10) / 10);
}

function zoomOut() {
    ganttZoom.value = Math.max(0.55, Math.round((ganttZoom.value - 0.1) * 10) / 10);
}

function formatYmdDisplay(ymd) {
    if (!ymd) {
        return '—';
    }
    const d = parseYmd(ymd);
    if (!d) {
        return String(ymd);
    }
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yyyy = d.getFullYear();

    return `${dd}/${mm}/${yyyy}`;
}

function statusPillClass(status) {
    const s = String(status || '');
    if (['todo', 'in_progress', 'done', 'blocked'].includes(s)) {
        return `ppms-pd-status-pill--${s}`;
    }

    return 'ppms-pd-status-pill--todo';
}

function onLeftScroll(e) {
    const el = e.target;
    const r = rightScrollRef.value;
    if (!r || syncingScroll.value) {
        return;
    }
    syncingScroll.value = true;
    r.scrollTop = el.scrollTop;
    requestAnimationFrame(() => {
        syncingScroll.value = false;
    });
}

function onRightScroll(e) {
    const el = e.target;
    const l = leftScrollRef.value;
    if (!l || syncingScroll.value) {
        return;
    }
    syncingScroll.value = true;
    l.scrollTop = el.scrollTop;
    requestAnimationFrame(() => {
        syncingScroll.value = false;
    });
}

function scrollBoardIntoView() {
    tasksBoardRef.value?.scrollIntoView?.({ behavior: 'smooth', block: 'start' });
}

defineExpose({ tasksBoardRef, scrollBoardIntoView });
</script>

<style scoped>
.ppms-pd-tasks-shell {
    padding: 0;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 10px;
    background: #fff;
}

.ppms-pd-tasks-topbar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem 1rem;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    background: linear-gradient(180deg, #fafaf9 0%, #fff 100%);
}

.ppms-pd-tasks-topbar__title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
}

.ppms-pd-tasks-board-title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 600;
    color: #252423;
}

.ppms-pd-tasks-topbar__icons {
    display: inline-flex;
    gap: 0.25rem;
}

.ppms-pd-tasks-ico-btn {
    width: 2rem;
    height: 2rem;
    border-radius: 6px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    background: #fff;
    cursor: pointer;
    font-size: 1.1rem;
    line-height: 1;
    color: #323130;
}

.ppms-pd-tasks-ico-btn:hover {
    background: #f3f2f1;
}

.ppms-pd-tasks-filters {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 0.75rem 1rem;
}

.ppms-pd-filter-field--switch {
    padding-top: 0.1rem;
}

.ppms-pd-filter-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    min-width: 0;
}

.ppms-pd-filter-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #605e5c;
    letter-spacing: 0.02em;
    line-height: 1.2;
    min-height: 0.9rem;
}

.ppms-pd-filter-label--spacer {
    user-select: none;
}

.ppms-pd-filter-control {
    position: relative;
    min-width: 11rem;
    max-width: 16rem;
}

.ppms-pd-tasks-select {
    width: 100%;
    min-height: 2.5rem;
    padding: 0.45rem 2.25rem 0.45rem 0.75rem;
    border: 1px solid #d2d0ce;
    border-radius: 8px;
    font: inherit;
    font-size: 0.875rem;
    color: #323130;
    background-color: #fff;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23605e5c' stroke-width='2.2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.65rem center;
    background-size: 12px;
    appearance: none;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04);
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}

.ppms-pd-tasks-select:hover {
    border-color: #b3b0ad;
}

.ppms-pd-tasks-select:focus {
    outline: none;
    border-color: #0078d4;
    box-shadow:
        inset 0 1px 2px rgba(0, 0, 0, 0.04),
        0 0 0 2px rgba(0, 120, 212, 0.22);
}

.ppms-pd-filter-switch {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    user-select: none;
    padding: 0.35rem 0;
    font-size: 0.875rem;
    color: #323130;
}

.ppms-pd-filter-switch-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.ppms-pd-filter-switch-ui {
    position: relative;
    width: 2.25rem;
    height: 1.25rem;
    flex-shrink: 0;
    border-radius: 999px;
    background: #c8c6c4;
    transition: background 0.2s ease;
}

.ppms-pd-filter-switch-ui::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: calc(1.25rem - 4px);
    height: calc(1.25rem - 4px);
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
    transition: transform 0.2s ease;
}

.ppms-pd-filter-switch-input:checked + .ppms-pd-filter-switch-ui {
    background: #0078d4;
}

.ppms-pd-filter-switch-input:checked + .ppms-pd-filter-switch-ui::after {
    transform: translateX(1rem);
}

.ppms-pd-filter-switch-input:focus-visible + .ppms-pd-filter-switch-ui {
    box-shadow: 0 0 0 2px rgba(0, 120, 212, 0.35);
}

.ppms-pd-filter-switch-text {
    line-height: 1.3;
}

.ppms-pd-tasks-refresh {
    min-height: 2.5rem;
    padding: 0 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 8px;
    border: 1px solid #0078d4;
    background: #fff;
    color: #0078d4;
    cursor: pointer;
    transition:
        background 0.15s ease,
        color 0.15s ease;
}

.ppms-pd-tasks-refresh:hover {
    background: #f3f9fd;
}

.ppms-pd-tasks-refresh:focus-visible {
    outline: none;
    box-shadow: 0 0 0 2px rgba(0, 120, 212, 0.35);
}

.ppms-pd-tasks-window {
    margin: 0;
    padding: 0.35rem 1rem 0;
    font-size: 0.8rem;
}

.ppms-pd-tasks-split {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(280px, 46%);
    gap: 0;
    min-height: 320px;
}

@media (max-width: 1100px) {
    .ppms-pd-tasks-split {
        grid-template-columns: 1fr;
    }
    .ppms-pd-tasks-col-right {
        border-top: 1px solid rgba(0, 0, 0, 0.08);
        max-height: 50vh;
    }
}

.ppms-pd-tasks-col-left {
    border-right: 1px solid rgba(0, 0, 0, 0.08);
    min-width: 0;
}

.ppms-pd-tasks-scroll {
    max-height: min(70vh, 720px);
    overflow: auto;
}

.ppms-pd-tasks-scroll--gantt {
    background: #fafaf9;
}

.ppms-pd-tasks-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
}

.ppms-pd-tasks-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    padding: 0.55rem 0.5rem;
    text-align: left;
    font-weight: 600;
    color: #605e5c;
    background: #f3f2f1;
    border-bottom: 1px solid #e1dfdd;
    white-space: nowrap;
}

.ppms-pd-th-task {
    min-width: 220px;
}

.ppms-pd-th-assignee {
    width: 88px;
}

.ppms-pd-th-status {
    width: 120px;
}

.ppms-pd-th-date {
    width: 96px;
}

.ppms-pd-th-num {
    width: 72px;
}

.ppms-pd-th-due {
    width: 120px;
}

.ppms-pd-th-act {
    width: 36px;
}

.ppms-pd-tasks-empty {
    padding: 1.5rem 1rem;
    text-align: center;
}

.ppms-pd-tr-phase td {
    padding: 0;
    background: #edebe9;
    border-bottom: 1px solid #e1dfdd;
}

.ppms-pd-phase-toggle {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    width: 100%;
    text-align: left;
    padding: 0.45rem 0.65rem;
    border: none;
    background: transparent;
    cursor: pointer;
    font: inherit;
    font-weight: 600;
    color: #201f1e;
}

.ppms-pd-phase-toggle:hover {
    background: rgba(0, 0, 0, 0.04);
}

.ppms-pd-phase-caret {
    display: inline-block;
    transition: transform 0.15s ease;
    color: #605e5c;
}

.ppms-pd-phase-caret.is-collapsed {
    transform: rotate(-90deg);
}

.ppms-pd-phase-count {
    color: #605e5c;
    font-weight: 500;
}

.ppms-pd-tr-task td {
    padding: 0.4rem 0.5rem;
    border-bottom: 1px solid #f3f2f1;
    vertical-align: middle;
}

.ppms-pd-tr-task.is-overdue-row {
    background: #fff8f8;
}

.ppms-pd-task-name {
    display: block;
    width: 100%;
    text-align: left;
    border: none;
    background: none;
    cursor: pointer;
    font: inherit;
    color: #201f1e;
    line-height: 1.35;
}

.ppms-pd-task-name:hover {
    text-decoration: underline;
}

.ppms-pd-task-name.is-overdue-text {
    color: #c50f1f;
    font-weight: 600;
}

.ppms-pd-tasks-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    font-size: 0.65rem;
    font-weight: 600;
    color: #fff;
    flex-shrink: 0;
}

.ppms-pd-status-pill {
    display: inline-block;
    padding: 0.15rem 0.45rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}

.ppms-pd-status-pill--done {
    background: #dff6dd;
    color: #107c10;
}

.ppms-pd-status-pill--in_progress {
    background: #deecf9;
    color: #0078d4;
}

.ppms-pd-status-pill--todo {
    background: #edebe9;
    color: #605e5c;
}

.ppms-pd-status-pill--blocked {
    background: #fed9cc;
    color: #d83b01;
}

.ppms-pd-td-date {
    color: #323130;
    white-space: nowrap;
}

.ppms-pd-td-pct {
    font-variant-numeric: tabular-nums;
    color: #323130;
}

.ppms-pd-due-pill {
    display: inline-block;
    padding: 0.12rem 0.4rem;
    border-radius: 999px;
    background: #fde7e9;
    color: #a4262c;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
}

.ppms-pd-due-dash {
    color: #a19f9d;
}

.ppms-pd-td-actions {
    text-align: center;
    width: 36px;
}

.ppms-pd-task-del {
    width: 1.75rem;
    height: 1.75rem;
    border: none;
    border-radius: 4px;
    background: transparent;
    color: #a19f9d;
    cursor: pointer;
    font-size: 1.1rem;
    line-height: 1;
}

.ppms-pd-task-del:hover {
    background: rgba(196, 15, 31, 0.08);
    color: #c50f1f;
}

.ppms-pd-gantt-head {
    position: sticky;
    top: 0;
    z-index: 3;
    background: #fafaf9;
    border-bottom: 1px solid #e1dfdd;
}

.ppms-pd-gantt-month {
    padding: 0.35rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #605e5c;
    text-align: center;
}

.ppms-pd-gantt-daycols {
    display: grid;
    border-bottom: 1px solid #edebe9;
}

.ppms-pd-gantt-daycell {
    text-align: center;
    font-size: 0.65rem;
    color: #605e5c;
    padding: 0.15rem 0;
    border-left: 1px solid rgba(0, 0, 0, 0.04);
}

.ppms-pd-gantt-track-row {
    height: 38px;
    box-sizing: border-box;
    border-bottom: 1px solid #f3f2f1;
    display: flex;
    align-items: center;
    padding: 0 0.25rem;
    background: #fff;
}

.ppms-pd-gantt-track-row--phase {
    height: 38px;
    background: #edebe9;
    border-bottom: 1px solid #e1dfdd;
}

.ppms-pd-gantt-track-row--empty {
    padding: 1rem;
    justify-content: center;
}

.ppms-pd-gantt-track-bg {
    position: relative;
    width: 100%;
    height: 18px;
    border-radius: 4px;
    background: rgba(0, 120, 212, 0.08);
}

.ppms-pd-gantt-bar-fill {
    height: 100%;
    border-radius: 4px;
    background: linear-gradient(180deg, #62abf5 0%, #0078d4 100%);
    min-width: 4px;
}

.ppms-pd-csat-block {
    margin-top: 1rem;
}
</style>
