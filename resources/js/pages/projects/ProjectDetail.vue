<template>
    <div class="ppms-page">
        <p v-if="loading" class="ppms-loading-line" role="status">Đang tải…</p>
        <template v-else-if="project">
            <header class="ppms-page-head ppms-row">
                <div>
                    <router-link to="/projects" class="ppms-back">Danh sách</router-link>
                    <h1>{{ project.name }}</h1>
                    <p>{{ project.description }}</p>
                    <div class="ppms-tags">
                        <span>{{ project.type }}</span>
                        <span>{{ project.phase }}</span>
                        <span>{{ project.status }}</span>
                        <span>Tiến độ: {{ Number(project.progress).toFixed(2) }}%</span>
                    </div>
                </div>
                <div class="ppms-actions">
                    <button type="button" class="ppms-btn-ghost" @click="duplicateProject">Nhân bản dự án</button>
                </div>
            </header>

            <section class="ppms-card ppms-mt">
                <h2>Gantt (API — lọc assignee / status / root)</h2>
                <div class="ppms-gantt-filters ppms-task-form">
                    <select v-model="ganttFilters.assignee_id">
                        <option value="">— Assignee —</option>
                        <option v-for="o in assigneeOptions" :key="o.id" :value="String(o.id)">{{ o.name }}</option>
                    </select>
                    <input v-model="ganttFilters.status" type="text" placeholder="status: todo,in_progress,done" />
                    <label class="ppms-inline-check">
                        <input v-model="ganttFilters.root_only" type="checkbox" />
                        Chỉ task gốc
                    </label>
                    <button type="button" class="ppms-btn-ghost" @click="loadGantt">Làm mới</button>
                </div>
                <p v-if="gantt.window?.start" class="ppms-muted ppms-mt-sm">
                    Cửa sổ: {{ gantt.window.start }} → {{ gantt.window.end }} ({{ gantt.window.days }} ngày)
                </p>
                <p v-if="!gantt.bars?.length" class="ppms-muted ppms-mt-sm">Không có task khớp bộ lọc.</p>
                <div class="ppms-gantt">
                    <div v-for="b in gantt.bars" :key="b.task_id" class="ppms-gantt-row">
                        <span class="ppms-gantt-name">{{ b.name }} · {{ b.status }}</span>
                        <div class="ppms-gantt-bar-wrap">
                            <div
                                class="ppms-gantt-bar"
                                :style="{
                                    marginLeft: `${b.layout.left_pct}%`,
                                    width: `${b.layout.width_pct}%`,
                                }"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="project.type === 'delivery'" class="ppms-card ppms-mt">
                <h2>CSAT (Type 2) — đánh giá 1–5</h2>
                <p v-if="project.csat_metrics" class="ppms-muted ppms-mt-sm">
                    Phản hồi: {{ project.csat_metrics.response_count }} / mời
                    {{ project.csat_metrics.invites_sent }} ({{ project.csat_metrics.response_rate_pct }}%)
                </p>
                <form class="ppms-task-form" @submit.prevent="submitCsat">
                    <input v-model.number="csat.rating" type="number" min="1" max="5" required />
                    <input v-model="csat.milestone_label" placeholder="Milestone" />
                    <button type="submit" class="ppms-btn-primary">Gửi CSAT</button>
                </form>
                <p v-if="csatMsg" class="ppms-muted">{{ csatMsg }}</p>
            </section>

            <section class="ppms-card ppms-mt">
                <h2>Task — weight = complexity×0.4 + impact×0.6</h2>
                <form class="ppms-stack" @submit.prevent="addTask">
                    <div class="ppms-task-form">
                        <input v-model="taskForm.name" placeholder="Tên task" required />
                        <input
                            v-model.number="taskForm.parent_id"
                            type="number"
                            placeholder="Parent task id (tối đa 1 cấp)"
                        />
                        <input v-model.number="taskForm.estimate_hours" type="number" min="0" step="0.5" required />
                        <input v-model.number="taskForm.complexity" type="number" min="1" max="5" required />
                        <input v-model.number="taskForm.impact" type="number" min="1" max="5" required />
                        <input v-model="taskForm.due_date" type="date" />
                        <input v-model.number="taskForm.assignee_id" type="number" placeholder="Assignee id" />
                    </div>
                    <button type="submit" class="ppms-btn-primary">Thêm task</button>
                </form>
                <p v-if="taskErr" class="ppms-error">{{ taskErr }}</p>

                <div class="ppms-bulk ppms-mt">
                    <span>Bulk (≤50):</span>
                    <input v-model.number="bulk.assignee_id" type="number" placeholder="Assignee id" />
                    <select v-model="bulk.status">
                        <option value="">— status —</option>
                        <option value="todo">todo</option>
                        <option value="in_progress">in_progress</option>
                        <option value="done">done</option>
                        <option value="blocked">blocked</option>
                    </select>
                    <button type="button" class="ppms-btn-primary" :disabled="!selectedIds.length" @click="runBulk">
                        Áp dụng
                    </button>
                </div>

                <div class="ppms-table-scroll ppms-mt">
                    <table class="ppms-table">
                        <thead>
                            <tr>
                                <th class="ppms-th-check"></th>
                                <th>Task</th>
                                <th>Pred</th>
                                <th>Weight</th>
                                <th>Est / Act</th>
                                <th>Trạng thái</th>
                                <th>Gán</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in project.tasks" :key="t.id">
                                <td><input v-model="selectedIds" type="checkbox" :value="t.id" /></td>
                                <td>
                                    <button type="button" class="ppms-linklike" @click="toggleFocus(t)">
                                        {{ t.name }}
                                    </button>
                                </td>
                                <td class="ppms-muted">
                                    <span v-if="t.predecessors?.length">{{
                                        t.predecessors.map((p) => p.id).join(',')
                                    }}</span>
                                    <span v-else>—</span>
                                </td>
                                <td>{{ t.weight }}</td>
                                <td>{{ t.estimate_hours }} / {{ t.actual_hours }}</td>
                                <td>
                                    <select :value="t.status" @change="updateStatus(t, $event.target.value)">
                                        <option value="todo">todo</option>
                                        <option value="in_progress">in_progress</option>
                                        <option value="done">done</option>
                                        <option value="blocked">blocked</option>
                                    </select>
                                </td>
                                <td>{{ t.assignee?.name || '—' }}</td>
                                <td>
                                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="removeTask(t)">
                                        Xóa
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-if="focusTask" class="ppms-card ppms-mt">
                <h2>Chi tiết task #{{ focusTask.id }}</h2>
                <div class="ppms-split">
                    <div>
                        <h3>Comments</h3>
                        <ul class="ppms-comments">
                            <li v-for="c in comments" :key="c.id">
                                <strong>{{ c.user?.name }}</strong
                                >: {{ c.body }}
                            </li>
                        </ul>
                        <form @submit.prevent="addComment">
                            <textarea v-model="commentBody" rows="2" placeholder="Bình luận" required />
                            <button type="submit" class="ppms-btn-primary">Gửi</button>
                        </form>
                    </div>
                    <div>
                        <h3>Dependency (Finish-to-Start)</h3>
                        <form class="ppms-task-form" @submit.prevent="addDep">
                            <input
                                v-model.number="depPredId"
                                type="number"
                                placeholder="Predecessor task id"
                                required
                            />
                            <button type="submit" class="ppms-btn-primary">Thêm</button>
                        </form>
                        <p v-if="depErr" class="ppms-error">{{ depErr }}</p>
                        <h3 class="ppms-mt">File (≤10MB)</h3>
                        <input type="file" @change="onFile" />
                        <ul class="ppms-filelist">
                            <li v-for="a in attachments" :key="a.id">
                                <button type="button" class="ppms-linklike" @click="downloadFile(a)">
                                    {{ a.original_name }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import { formatApiUserMessage } from '@/bootstrap';
import { ppmsConfirm, ppmsToastError, ppmsToastSuccess, ppmsToastWarning } from '@/ppmsUi';

const props = defineProps({
    id: { type: [String, Number], required: true },
});

const route = useRoute();
const router = useRouter();
const project = ref(null);
const loading = ref(true);
const taskErr = ref('');
const selectedIds = ref([]);
const focusTask = ref(null);
const comments = ref([]);
const attachments = ref([]);
const commentBody = ref('');
const depPredId = ref(null);
const depErr = ref('');
const csat = reactive({ rating: 5, milestone_label: '' });
const csatMsg = ref('');
const gantt = ref({ window: {}, bars: [] });
const ganttFilters = reactive({
    assignee_id: '',
    status: '',
    root_only: false,
});

const taskForm = reactive({
    name: '',
    parent_id: null,
    estimate_hours: 8,
    complexity: 3,
    impact: 3,
    due_date: '',
    assignee_id: null,
});

const bulk = reactive({
    assignee_id: null,
    status: '',
});

const assigneeOptions = computed(() => {
    const m = new Map();
    for (const t of project.value?.tasks || []) {
        if (t.assignee_id && t.assignee?.name) {
            m.set(t.assignee_id, t.assignee.name);
        }
    }
    return [...m.entries()].map(([id, name]) => ({ id, name }));
});

async function loadGantt() {
    if (!props.id) {
        return;
    }
    const p = new URLSearchParams();
    if (ganttFilters.assignee_id) {
        p.set('assignee_id', ganttFilters.assignee_id);
    }
    if (ganttFilters.status.trim()) {
        p.set('status', ganttFilters.status.trim());
    }
    if (ganttFilters.root_only) {
        p.set('root_only', '1');
    }
    const q = p.toString();
    const { data } = await axios.get(`/api/projects/${props.id}/gantt${q ? `?${q}` : ''}`);
    gantt.value = data;
}

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get(`/api/projects/${props.id}`);
        project.value = data;
        await loadGantt();
    } finally {
        loading.value = false;
    }
}

let ganttDebounce = null;
watch(
    ganttFilters,
    () => {
        if (!project.value) {
            return;
        }
        clearTimeout(ganttDebounce);
        ganttDebounce = setTimeout(() => loadGantt(), 350);
    },
    { deep: true },
);

watch(
    () => route.params.id,
    () => load(),
    { immediate: true },
);

watch(focusTask, async (t) => {
    if (!t) {
        comments.value = [];
        attachments.value = [];

        return;
    }
    const [c, a] = await Promise.all([
        axios.get(`/api/tasks/${t.id}/comments`),
        axios.get(`/api/tasks/${t.id}/attachments`),
    ]);
    comments.value = c.data;
    attachments.value = a.data;
});

function toggleFocus(t) {
    focusTask.value = focusTask.value?.id === t.id ? null : t;
}

async function addTask() {
    taskErr.value = '';
    if (!(await ppmsConfirm('Tạo task mới với thông tin đã nhập?'))) {
        return;
    }
    try {
        await axios.post(`/api/projects/${props.id}/tasks`, {
            name: taskForm.name,
            parent_id: taskForm.parent_id || null,
            estimate_hours: taskForm.estimate_hours,
            complexity: taskForm.complexity,
            impact: taskForm.impact,
            due_date: taskForm.due_date || null,
            assignee_id: taskForm.assignee_id || null,
        });
        taskForm.name = '';
        taskForm.parent_id = null;
        ppmsToastSuccess('Đã tạo task.');
        await load();
    } catch (e) {
        taskErr.value = formatApiUserMessage(e, 'Không tạo được task.');
    }
}

async function updateStatus(task, status) {
    if (!(await ppmsConfirm(`Cập nhật trạng thái task "${task.name}" thành "${status}"?`))) {
        return;
    }
    const body = { status };
    if (status === 'blocked') {
        body.blocked_reason = 'Blocked từ UI';
    }
    try {
        const res = await axios.put(`/api/tasks/${task.id}`, body);
        if (res.headers['x-ppms-warn-estimate']) {
            ppmsToastWarning('Cảnh báo: actual > 1.5× estimate (BR-TK-03).');
        }
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, 'Không cập nhật được task.'));
    }
    await load();
}

async function removeTask(task) {
    if (
        !(await ppmsConfirm(`Xóa task "${task.name}"? Thao tác không thể hoàn tác.`, {
            destructive: true,
            confirmLabel: 'Xóa',
        }))
    ) {
        return;
    }
    await axios.delete(`/api/tasks/${task.id}`);
    ppmsToastSuccess('Đã xóa task.');
    await load();
}

async function runBulk() {
    if (!selectedIds.value.length) {
        return;
    }
    if (!(await ppmsConfirm(`Áp dụng thao tác hàng loạt cho ${selectedIds.value.length} task đã chọn?`))) {
        return;
    }
    const body = { task_ids: selectedIds.value };
    if (bulk.assignee_id !== null && bulk.assignee_id !== '') {
        body.assignee_id = bulk.assignee_id;
    }
    if (bulk.status) {
        body.status = bulk.status;
    }
    try {
        await axios.post('/api/tasks/bulk', body);
        selectedIds.value = [];
        ppmsToastSuccess('Đã cập nhật hàng loạt.');
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, 'Không cập nhật hàng loạt được.'));
    }
}

async function addComment() {
    if (!focusTask.value) {
        return;
    }
    if (!commentBody.value.trim()) {
        return;
    }
    if (!(await ppmsConfirm('Gửi bình luận này?'))) {
        return;
    }
    await axios.post(`/api/tasks/${focusTask.value.id}/comments`, { body: commentBody.value });
    commentBody.value = '';
    const { data } = await axios.get(`/api/tasks/${focusTask.value.id}/comments`);
    comments.value = data;
    ppmsToastSuccess('Đã gửi bình luận.');
}

async function addDep() {
    depErr.value = '';
    if (!focusTask.value) {
        return;
    }
    if (!(await ppmsConfirm('Thêm dependency (tiền nhiệm) cho task này?'))) {
        return;
    }
    try {
        await axios.post(`/api/tasks/${focusTask.value.id}/dependencies`, {
            predecessor_task_id: depPredId.value,
        });
        depPredId.value = null;
        ppmsToastSuccess('Đã thêm dependency.');
        await load();
    } catch (e) {
        depErr.value = formatApiUserMessage(e, 'Không thêm được dependency.');
    }
}

async function onFile(ev) {
    const f = ev.target.files?.[0];
    if (!f || !focusTask.value) {
        return;
    }
    if (!(await ppmsConfirm(`Đính kèm tệp "${f.name}" vào task?`))) {
        ev.target.value = '';
        return;
    }
    const fd = new FormData();
    fd.append('file', f);
    await axios.post(`/api/tasks/${focusTask.value.id}/attachments`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });
    const { data } = await axios.get(`/api/tasks/${focusTask.value.id}/attachments`);
    attachments.value = data;
    ev.target.value = '';
    ppmsToastSuccess('Đã tải lên đính kèm.');
}

async function submitCsat() {
    csatMsg.value = '';
    if (!(await ppmsConfirm('Ghi nhận điểm CSAT này?'))) {
        return;
    }
    try {
        await axios.post(`/api/projects/${props.id}/csat`, {
            rating: csat.rating,
            milestone_label: csat.milestone_label || null,
        });
        csatMsg.value = 'Đã ghi nhận CSAT.';
        ppmsToastSuccess('Đã ghi nhận CSAT.');
    } catch (e) {
        csatMsg.value = formatApiUserMessage(e, 'Lỗi ghi nhận CSAT.');
    }
}

async function downloadFile(a) {
    const res = await axios.get(`/api/attachments/${a.id}/download`, { responseType: 'blob' });
    const url = URL.createObjectURL(res.data);
    const el = document.createElement('a');
    el.href = url;
    el.download = a.original_name;
    el.click();
    URL.revokeObjectURL(url);
}

async function duplicateProject() {
    if (!(await ppmsConfirm('Nhân bản dự án này? Một dự án mới sẽ được tạo.'))) {
        return;
    }
    const { data } = await axios.post(`/api/projects/${props.id}/duplicate`, { reset_dates: true });
    ppmsToastSuccess('Đã nhân bản dự án.');
    router.push({ name: 'project-detail', params: { id: String(data.id) } });
}
</script>
