<!-- eslint-disable vue/no-mutating-props -- parent reactive ganttFilters, csat -->
<template>
    <div class="ppms-pd-tab-panel">
        <section v-if="project.type === 'delivery'" class="ppms-card ppms-pd-section">
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

        <section ref="tasksBoardRef" class="ppms-card ppms-pd-section ppms-pd-tasks-board">
            <div class="ppms-pd-tasks-toolbar">
                <h2 class="ppms-pd-tasks-board-title">{{ t('projects.ganttTitle') }}</h2>
                <div class="ppms-gantt-filters ppms-task-form">
                    <select v-model="ganttFilters.assignee_id">
                        <option value="">— {{ t('projects.ganttAssignee') }} —</option>
                        <option v-for="o in assigneeOptions" :key="o.id" :value="String(o.id)">
                            {{ o.name }}
                        </option>
                    </select>
                    <input v-model="ganttFilters.status" type="text" :placeholder="t('projects.ganttStatusPh')" />
                    <label class="ppms-inline-check">
                        <input v-model="ganttFilters.root_only" type="checkbox" />
                        {{ t('projects.ganttRootOnly') }}
                    </label>
                    <button type="button" class="ppms-btn-ghost" @click="$emit('refresh-gantt')">
                        {{ t('projects.ganttRefresh') }}
                    </button>
                </div>
                <div class="ppms-pd-tasks-toolbar-tools" role="group" :aria-label="t('projects.pdTasksBoardHint')">
                    <button type="button" :title="t('projects.pdTasksZoomOut')" @click="zoomOut">−</button>
                    <button type="button" :title="t('projects.pdTasksZoomIn')" @click="zoomIn">+</button>
                </div>
            </div>
            <p v-if="gantt.window?.start" class="ppms-muted ppms-mt-sm">
                {{
                    t('projects.ganttWindow', {
                        start: gantt.window.start,
                        end: gantt.window.end,
                        days: gantt.window.days,
                    })
                }}
            </p>
            <p class="ppms-muted ppms-mt-sm">{{ t('projects.pdTasksBoardHint') }}</p>

            <div class="ppms-pd-tasks-split">
                <div class="ppms-pd-tasks-col-left">
                    <div ref="leftScrollRef" class="ppms-pd-tasks-scroll" @scroll="onLeftScroll">
                        <table class="ppms-pd-tasks-table">
                            <thead>
                                <tr>
                                    <th>{{ t('projects.thTask') }}</th>
                                    <th>{{ t('projects.thAssignee') }}</th>
                                    <th>{{ t('projects.thStatus') }}</th>
                                    <th>{{ t('projects.thStart') }}</th>
                                    <th>{{ t('projects.thEnd') }}</th>
                                    <th>{{ t('projects.thEstAct') }}</th>
                                    <th>{{ t('projects.thActions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!ganttRows.length">
                                    <td colspan="7" class="ppms-muted">{{ t('projects.ganttEmpty') }}</td>
                                </tr>
                                <tr v-for="row in ganttRows" :key="row.bar.task_id">
                                    <td class="ppms-td-task-name">
                                        <button
                                            type="button"
                                            class="ppms-linklike ppms-task-name-btn"
                                            :style="{ paddingLeft: `${0.65 + row.depth * 0.75}rem` }"
                                            @click="$emit('toggle-focus', row.task || { id: row.bar.task_id, name: row.bar.name })"
                                        >
                                            {{ row.bar.name }}
                                        </button>
                                    </td>
                                    <td>
                                        <span
                                            class="ppms-pd-tasks-avatar"
                                            :title="row.bar.assignee_name || t('projects.pdUnassigned')"
                                        >
                                            {{ initials(row.bar.assignee_name) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span :class="['ppms-task-status-pill', statusPillClass(row.bar.status)]">
                                            {{ ganttTaskStatusLabel(t, row.bar.status) }}
                                        </span>
                                    </td>
                                    <td>{{ formatYmdDisplay(row.bar.start) }}</td>
                                    <td>{{ formatYmdDisplay(row.bar.end) }}</td>
                                    <td>
                                        <span v-if="row.task">
                                            {{ row.task.estimate_hours }} / {{ row.task.actual_hours }}
                                        </span>
                                        <span v-else>—</span>
                                    </td>
                                    <td>
                                        <button
                                            v-if="row.task"
                                            type="button"
                                            class="ppms-btn-ghost ppms-btn-sm"
                                            @click="$emit('remove-task', row.task)"
                                        >
                                            {{ t('common.delete') }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="ppms-pd-tasks-col-right">
                    <div
                        ref="rightScrollRef"
                        class="ppms-pd-tasks-scroll"
                        :style="{ '--pd-gantt-day-w': dayColWidthPx + 'px' }"
                        @scroll="onRightScroll"
                    >
                        <div class="ppms-pd-gantt-hscroll-inner" :style="{ width: timelineInnerWidthPx + 'px' }">
                            <div class="ppms-pd-gantt-head">
                                <div class="ppms-pd-gantt-month">{{ ganttMonthLabel }}</div>
                                <div
                                    v-if="dayColumns.length"
                                    class="ppms-pd-gantt-daycols ppms-pd-gantt-daycols--nums"
                                    :style="dayGridStyle"
                                >
                                    <div v-for="col in dayColumns" :key="'n-' + col.key" class="ppms-pd-gantt-daycell">
                                        {{ col.dayNum }}
                                    </div>
                                </div>
                                <div
                                    v-if="dayColumns.length"
                                    class="ppms-pd-gantt-daycols ppms-pd-gantt-daycols--dow"
                                    :style="dayGridStyle"
                                >
                                    <div v-for="col in dayColumns" :key="'w-' + col.key" class="ppms-pd-gantt-daycell">
                                        {{ col.dowShort }}
                                    </div>
                                </div>
                            </div>
                            <div v-if="!ganttRows.length" class="ppms-pd-gantt-track-row">
                                <span class="ppms-muted">{{ t('projects.ganttEmpty') }}</span>
                            </div>
                            <div v-for="row in ganttRows" :key="'g-' + row.bar.task_id" class="ppms-pd-gantt-track-row">
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
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="focusTask" class="ppms-card ppms-pd-section">
            <h2>{{ t('projects.taskDetailTitle', { id: focusTask.id }) }}</h2>
            <div class="ppms-split">
                <div>
                    <h3>{{ t('projects.depTitle') }}</h3>
                    <form class="ppms-task-form" @submit.prevent="$emit('add-dep')">
                        <input
                            v-model.number="depPredId"
                            type="number"
                            :placeholder="t('projects.depPredPh')"
                            required
                        />
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

const BASE_DAY_PX = 26;

const taskById = computed(() => {
    const m = new Map();
    for (const row of props.project?.tasks || []) {
        m.set(row.id, row);
    }
    return m;
});

function taskDepth(taskId) {
    let d = 0;
    let cur = taskById.value.get(taskId);
    const seen = new Set();
    while (cur?.parent_id != null && d < 48) {
        if (seen.has(cur.id)) {
            break;
        }
        seen.add(cur.id);
        d += 1;
        cur = taskById.value.get(cur.parent_id);
    }
    return d;
}

const ganttRows = computed(() => {
    const bars = props.gantt?.bars || [];
    return bars.map((bar) => ({
        bar,
        task: taskById.value.get(bar.task_id) || null,
        depth: taskDepth(bar.task_id),
    }));
});

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
        return `ppms-task-status-pill--${s}`;
    }

    return 'ppms-task-status-pill--todo';
}

function onLeftScroll(e) {
    const t = e.target;
    const r = rightScrollRef.value;
    if (!r || syncingScroll.value) {
        return;
    }
    syncingScroll.value = true;
    r.scrollTop = t.scrollTop;
    requestAnimationFrame(() => {
        syncingScroll.value = false;
    });
}

function onRightScroll(e) {
    const t = e.target;
    const l = leftScrollRef.value;
    if (!l || syncingScroll.value) {
        return;
    }
    syncingScroll.value = true;
    l.scrollTop = t.scrollTop;
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
.ppms-pd-tasks-board-title {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: #252423;
    width: 100%;
    flex-basis: 100%;
}

@media (min-width: 640px) {
    .ppms-pd-tasks-board-title {
        width: auto;
        flex-basis: auto;
    }
}

.ppms-pd-gantt-head {
    position: sticky;
    top: 0;
    z-index: 3;
    background: #fff;
}
</style>
