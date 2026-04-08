<template>
    <div class="ppms-page ppms-page--project-detail">
        <div v-if="loading" class="pbi-loading" role="status">
            <span class="pbi-loading-bar" aria-hidden="true" />
            {{ t('common.loading') }}
        </div>
        <template v-else-if="project">
            <div class="ppms-pd-shell">
                <div class="ppms-pd-main-col">
                    <ProjectDetailHeader
                        ref="detailHeaderRef"
                        v-model:active-tab="activeTab"
                        :project="project"
                        :detail-tabs="detailTabs"
                        :can-manage-project="canManageProject"
                        @open-meta="openMetaEdit"
                        @join="joinProject"
                        @group-labels="openGroupLabels"
                        @menu-add-regular="menuAddRegular"
                        @menu-add-bulk="menuAddBulk"
                        @menu-add-category="menuAddByCategory"
                        @menu-add-process="menuAddProcess"
                        @menu-add-phase="menuAddByPhase"
                        @open-phase-modal="openPhaseModal"
                        @duplicate="duplicateProject"
                    />
                    <ProjectDetailTabInfo
                        v-show="activeTab === 'info'"
                        :project="project"
                        :task-stats="taskStats"
                        :planned-span-days="plannedSpanDays"
                        :workload-rows="workloadRows"
                        :timeline-display="timelineDisplay"
                        :project-activities="projectActivities"
                        :media-counts="mediaCounts"
                        :project-media="projectMedia"
                        :recent-media-items="recentMediaForInfo"
                        @edit-stakeholders="openMetaEdit"
                        @go-attachments="activeTab = 'attachments'"
                        @download-media-row="downloadMediaRow"
                        @open-project-doc="openProjectDocumentFromMedia"
                        @open-task-from-attachment="openTaskForAttachment"
                    />
                    <ProjectDetailTabTasks
                        v-show="activeTab === 'tasks'"
                        ref="tabTasksRef"
                        v-model:dep-pred-id="depPredId"
                        :project="project"
                        :gantt="gantt"
                        :gantt-filters="ganttFilters"
                        :assignee-options="assigneeOptions"
                        :csat="csat"
                        :csat-msg="csatMsg"
                        :focus-task="focusTask"
                        :dep-err="depErr"
                        :attachments="attachments"
                        @submit-csat="submitCsat"
                        @toggle-focus="toggleFocus"
                        @remove-task="removeTask"
                        @add-dep="addDep"
                        @task-file="onFile"
                        @download-attachment="downloadFile"
                    />
                    <ProjectDetailTabSupplies
                        v-show="activeTab === 'supplies'"
                        :project-supplies="projectSupplies"
                        :can-manage-project="canManageProject"
                        :supply-new="supplyNew"
                        @submit="addSupply"
                        @delete="deleteSupply"
                    />
                    <ProjectDetailTabReports
                        v-show="activeTab === 'reports'"
                        :project="project"
                        :can-pdf="canPdf"
                        :can-csv="canCsv"
                        @dl-weekly-pdf="dlWeeklyPdf"
                        @dl-projects-csv="dlProjectsCsv"
                    />
                    <ProjectDetailTabAttachments
                        v-show="activeTab === 'attachments'"
                        :project-media="projectMedia"
                        :media-counts="mediaCounts"
                        :project-doc-upload-state="projectDocUploadState"
                        :can-manage-documents="canManageDocuments"
                        :doc-add-mode="docAddMode"
                        :doc-form="docForm"
                        :doc-add-needs-url="docAddNeedsUrl"
                        @download="downloadMediaRow"
                        @open-task="openTaskForAttachment"
                        @open-project-doc="openProjectDocument"
                        @delete-project-doc="deleteProjectDocument"
                        @doc-add="openDocAdd"
                        @cancel-doc-add="cancelDocAdd"
                        @submit-doc="submitDocForm"
                        @project-doc-upload="onProjectDocUpload"
                    />
                </div>
            </div>
            <ProjectDetailModalMeta
                v-model="showMetaEdit"
                :meta-form="metaForm"
                :meta-err="metaErr"
                @save="saveMeta"
                @add-supplier="addMetaSupplier"
                @remove-supplier="removeMetaSupplier"
            />
            <ProjectDetailModalPhase
                v-model="showPhaseModal"
                :phase-new="phaseNew"
                :progress-mode-options="progressModeOptions"
                :project-phases="projectPhases"
                :can-manage-project="canManageProject"
                @submit="submitNewPhase"
                @delete-phase="deletePhase"
            />
            <ProjectDetailModalTaskRegular
                v-model="showRegularTaskModal"
                :project="project"
                :project-phases="projectPhases"
                @submit="submitRegularTask"
            />
            <ProjectDetailModalBulk
                v-model="showBulkModal"
                :bulk-rows="bulkRows"
                :project-phases="projectPhases"
                :project="project"
                @add-row="addBulkRow"
                @remove-row="removeBulkRow"
                @submit="submitBulkTasks"
            />
            <ProjectDetailModalTaskByList
                v-model="showCategoryTaskModal"
                variant="category"
                :project="project"
                :project-phases="projectPhases"
                @submit="submitGroupTasks"
            />
            <ProjectDetailModalTaskByList
                v-model="showPhaseTaskModal"
                variant="phase"
                :project="project"
                :project-phases="projectPhases"
                @submit="submitGroupTasks"
            />
            <ProjectDetailModalTaskProcess
                v-model="showProcessTaskModal"
                :project="project"
                :project-phases="projectPhases"
                @submit="submitProcessTask"
            />
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '@/bootstrap';
import { ppmsConfirm, ppmsToastError, ppmsToastSuccess, ppmsToastWarning } from '@/ppmsUi';
import { parseCommaLabelTokens } from './utils/projectLabels';
import { TIMELINE_PHASES } from './constants/projectDetail';
import {
    ProjectDetailHeader,
    ProjectDetailModalBulk,
    ProjectDetailModalTaskByList,
    ProjectDetailModalTaskProcess,
    ProjectDetailModalTaskRegular,
    ProjectDetailModalMeta,
    ProjectDetailModalPhase,
    ProjectDetailTabAttachments,
    ProjectDetailTabInfo,
    ProjectDetailTabReports,
    ProjectDetailTabSupplies,
    ProjectDetailTabTasks,
} from './components/detail';

const { t } = useI18n();

const props = defineProps({
    id: { type: [String, Number], required: true },
});

const route = useRoute();
const router = useRouter();
const project = ref(null);
const loading = ref(true);
const focusTask = ref(null);
const attachments = ref([]);
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

const activeTab = ref('info');
const projectMedia = ref([]);
const projectDocuments = ref([]);
const docAddMode = ref(null);
const docForm = reactive({
    name: '',
    url: '',
    parent_id: '',
});
/** { status: 'idle'|'uploading'|'success'|'error', fileName?: string } */
const projectDocUploadState = ref({ status: 'idle' });
const me = ref(null);
const detailHeaderRef = ref(null);
const tabTasksRef = ref(null);
const projectActivities = ref([]);
const showPhaseModal = ref(false);
const showBulkModal = ref(false);
const showRegularTaskModal = ref(false);
const showCategoryTaskModal = ref(false);
const showPhaseTaskModal = ref(false);
const showProcessTaskModal = ref(false);
const bulkRows = ref([]);
const supplyNew = reactive({ name: '', quantity: 0, unit: '', notes: '' });
const phaseNew = reactive({
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    progress_mode: 'status_default',
});

const showMetaEdit = ref(false);
const metaErr = ref('');
const metaForm = reactive({
    customer_name: '',
    customer_email: '',
    labels_text: '',
    start_date: '',
    actual_start_date: '',
    suppliers: [{ name: '', contact: '' }],
    timelineDates: {
        planning: '',
        development: '',
        uat: '',
        done: '',
        maintenance: '',
    },
});

const canManageProject = computed(() => ['admin', 'pm', 'tl'].includes(me.value?.role));

const projectPhases = computed(() => project.value?.phases || []);

const projectSupplies = computed(() => project.value?.supplies || []);

const progressModeOptions = computed(() =>
    [
        'status_default',
        'manual_pct',
        'volume_ratio',
        'checklist_ratio',
        'child_weight',
        'time_auto',
    ].map((id) => ({
        id,
        label: t(`projects.pdPm_${id}`),
    })),
);

const detailTabs = computed(() => [
    { id: 'info', label: t('projects.pdTabInfo') },
    { id: 'tasks', label: t('projects.pdTabTasks'), badge: project.value?.tasks?.length },
    {
        id: 'supplies',
        label: t('projects.pdTabSupplies'),
        badge: project.value?.supplies?.length || undefined,
    },
    { id: 'reports', label: t('projects.pdTabReports') },
    {
        id: 'attachments',
        label: t('projects.pdTabAttachments'),
        badge: projectMedia.value.length || undefined,
    },
]);

const canPdf = computed(() => ['admin', 'pm', 'tl'].includes(me.value?.role));
const canCsv = computed(() => ['admin', 'pm', 'hr'].includes(me.value?.role));

const canManageDocuments = computed(() => ['admin', 'pm', 'tl'].includes(me.value?.role));

const docAddNeedsUrl = computed(() => docAddMode.value === 'link');

const taskStats = computed(() => {
    const tasks = project.value?.tasks || [];
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    let overdue = 0;
    let inProgress = 0;
    let done = 0;
    let pending = 0;
    for (const row of tasks) {
        if (row.status === 'done') {
            done++;
        } else if (row.status === 'in_progress') {
            inProgress++;
        } else {
            pending++;
        }
        if (row.status !== 'done' && row.due_date) {
            const d = new Date(row.due_date);
            d.setHours(0, 0, 0, 0);
            if (d < today) {
                overdue++;
            }
        }
    }
    return {
        total: tasks.length,
        overdue,
        inProgress,
        done,
        pending,
    };
});

function parseYmd(s) {
    if (!s) {
        return null;
    }
    const d = new Date(s);
    return Number.isNaN(d.getTime()) ? null : d;
}

const plannedSpanDays = computed(() => {
    const p = project.value;
    if (!p) {
        return null;
    }
    const a = parseYmd(p.start_date);
    const b = parseYmd(p.deadline);
    if (!a || !b) {
        return null;
    }
    const ms = b.getTime() - a.getTime();
    return Math.max(0, Math.round(ms / 86400000));
});

const workloadRows = computed(() => {
    const tasks = project.value?.tasks || [];
    const m = new Map();
    const keyFor = (assigneeId) => (assigneeId != null ? String(assigneeId) : '__none');
    for (const row of tasks) {
        const k = keyFor(row.assignee_id);
        if (!m.has(k)) {
            m.set(k, {
                key: k,
                name: row.assignee?.name || t('projects.pdUnassigned'),
                total: 0,
                done: 0,
                inProgress: 0,
                other: 0,
                est: 0,
                act: 0,
            });
        }
        const agg = m.get(k);
        agg.total++;
        agg.est += Number(row.estimate_hours) || 0;
        agg.act += Number(row.actual_hours) || 0;
        if (row.status === 'done') {
            agg.done++;
        } else if (row.status === 'in_progress') {
            agg.inProgress++;
        } else {
            agg.other++;
        }
    }
    return [...m.values()].sort((a, b) => b.total - a.total);
});

const mediaCounts = computed(() => {
    const m = projectMedia.value;
    return {
        project: m.filter((x) => x.scope === 'project').length,
        task: m.filter((x) => x.scope === 'task').length,
    };
});

/** Tab Thông tin: tối đa 8 mục mới nhất (thư viện dự án + tệp công việc), đã sắp xếp từ API. */
const recentMediaForInfo = computed(() => (projectMedia.value || []).slice(0, 8));

const timelineDisplay = computed(() => {
    const p = project.value;
    if (!p) {
        return [];
    }
    const byPhase = {};
    for (const r of p.process_timeline || []) {
        if (r.phase) {
            byPhase[r.phase] = r.completed_at || null;
        }
    }
    return TIMELINE_PHASES.map((phase) => ({
        phase,
        completed_at: byPhase[phase] || null,
        isCurrent: p.phase === phase,
    }));
});

function openMetaEdit() {
    const p = project.value;
    if (!p) {
        return;
    }
    metaErr.value = '';
    metaForm.customer_name = p.customer_name || '';
    metaForm.customer_email = p.customer_email || '';
    metaForm.labels_text = Array.isArray(p.labels) && p.labels.length ? p.labels.join(', ') : '';
    metaForm.start_date = p.start_date ? String(p.start_date).slice(0, 10) : '';
    metaForm.actual_start_date = p.actual_start_date ? String(p.actual_start_date).slice(0, 10) : '';
    const sup = p.suppliers || [];
    metaForm.suppliers = sup.length
        ? sup.map((s) => ({ name: s.name || '', contact: s.contact || '' }))
        : [{ name: '', contact: '' }];
    const byPhase = {};
    for (const row of p.process_timeline || []) {
        if (row.phase) {
            byPhase[row.phase] = row.completed_at ? String(row.completed_at).slice(0, 10) : '';
        }
    }
    for (const ph of TIMELINE_PHASES) {
        metaForm.timelineDates[ph] = byPhase[ph] || '';
    }
    showMetaEdit.value = true;
}

function addMetaSupplier() {
    metaForm.suppliers.push({ name: '', contact: '' });
}

function removeMetaSupplier(i) {
    if (metaForm.suppliers.length > 1) {
        metaForm.suppliers.splice(i, 1);
    }
}

async function saveMeta() {
    if (!(await ppmsConfirm(t('projects.confirmSaveMeta')))) {
        return;
    }
    metaErr.value = '';
    const suppliers = metaForm.suppliers
        .filter((s) => s.name.trim())
        .map((s) => {
            const row = { name: s.name.trim() };
            if (s.contact?.trim()) {
                row.contact = s.contact.trim();
            }

            return row;
        });
    const process_timeline = TIMELINE_PHASES.filter((ph) => metaForm.timelineDates[ph]).map((phase) => ({
        phase,
        completed_at: metaForm.timelineDates[phase],
    }));
    try {
        await axios.patch(`/api/projects/${props.id}`, {
            customer_name: metaForm.customer_name.trim() || null,
            customer_email: metaForm.customer_email.trim() || null,
            labels: parseCommaLabelTokens(metaForm.labels_text),
            start_date: metaForm.start_date || null,
            actual_start_date: metaForm.actual_start_date || null,
            suppliers: suppliers.length ? suppliers : null,
            process_timeline: process_timeline.length ? process_timeline : null,
        });
        showMetaEdit.value = false;
        ppmsToastSuccess(t('projects.saveMetaOk'));
        await load();
    } catch (e) {
        metaErr.value = formatApiUserMessage(e, t('projects.saveMetaErr'));
    }
}

const projectUserNameById = computed(() => {
    const m = new Map();
    const p = project.value;
    if (!p) {
        return m;
    }
    for (const u of p.executor_users || []) {
        m.set(Number(u.id), u.name || String(u.id));
    }
    for (const u of p.follower_users || []) {
        m.set(Number(u.id), u.name || String(u.id));
    }
    if (p.owner?.id) {
        m.set(Number(p.owner.id), p.owner.name || String(p.owner.id));
    }

    return m;
});

const assigneeOptions = computed(() => {
    const m = new Map();
    const nameFor = (id) => projectUserNameById.value.get(Number(id)) || `#${id}`;

    for (const row of project.value?.tasks || []) {
        if (row.assignee_id) {
            m.set(Number(row.assignee_id), row.assignee?.name || nameFor(row.assignee_id));
        }
        const ids = Array.isArray(row.assignee_ids) ? row.assignee_ids : [];
        for (const id of ids) {
            const n = Number(id);
            if (!n) {
                continue;
            }
            if (!m.has(n)) {
                m.set(n, nameFor(n));
            }
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

async function refreshProjectMedia() {
    const { data } = await axios.get(`/api/projects/${props.id}/media`);
    projectMedia.value = data;
}

async function refreshDocuments() {
    const { data } = await axios.get(`/api/projects/${props.id}/documents`);
    projectDocuments.value = data;
}

function openDocAdd(kind) {
    docAddMode.value = kind;
    docForm.name = '';
    docForm.url = '';
}

function cancelDocAdd() {
    docAddMode.value = null;
}

async function submitDocForm() {
    if (!docAddMode.value) {
        return;
    }
    const name = docForm.name.trim();
    if (!name) {
        return;
    }
    const url = docForm.url.trim();
    if (docAddNeedsUrl.value && !url) {
        return;
    }
    const body = {
        doc_type: docAddMode.value,
        name,
        parent_id: docForm.parent_id ? Number(docForm.parent_id) : null,
    };
    if (url) {
        body.url = url;
    }
    try {
        await axios.post(`/api/projects/${props.id}/documents`, body);
        ppmsToastSuccess(t('projects.pdDocsCreated'));
        cancelDocAdd();
        await refreshDocuments();
        await refreshProjectMedia();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.pdDocsErr')));
    }
}

async function onProjectDocUpload(ev) {
    const files = ev.files ?? ev.target?.files;
    const f = files?.[0];
    if (!f) {
        return;
    }
    const skipConfirm = ev.skipConfirm === true;
    if (!skipConfirm && !(await ppmsConfirm(t('projects.pdDocsUploadConfirm')))) {
        if (ev.target) {
            ev.target.value = '';
        }

        return;
    }
    projectDocUploadState.value = { status: 'uploading', fileName: f.name };
    const fd = new FormData();
    fd.append('file', f);
    if (docForm.parent_id) {
        fd.append('parent_id', String(docForm.parent_id));
    }
    try {
        await axios.post(`/api/projects/${props.id}/documents/upload`, fd);
        ppmsToastSuccess(t('projects.pdDocsUploadOk'));
        projectDocUploadState.value = { status: 'success', fileName: f.name };
        await refreshDocuments();
        await refreshProjectMedia();
        setTimeout(() => {
            projectDocUploadState.value = { status: 'idle' };
        }, 2200);
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.pdDocsErr')));
        projectDocUploadState.value = {
            status: 'error',
            fileName: f.name,
        };
        setTimeout(() => {
            projectDocUploadState.value = { status: 'idle' };
        }, 4500);
    }
    if (ev.target) {
        ev.target.value = '';
    }
}

function openProjectDocument(drow) {
    if (drow.doc_type === 'upload') {
        projectDocDownload(drow);

        return;
    }
    if (drow.url) {
        window.open(drow.url, '_blank', 'noopener,noreferrer');
    }
}

/** Hàng từ media API (source_id) → openProjectDocument */
function openProjectDocumentFromMedia(row) {
    if (row?.source !== 'project_document') {
        return;
    }
    openProjectDocument({
        id: row.source_id,
        doc_type: row.doc_type,
        url: row.url,
        original_name: row.original_name,
        name: row.name,
    });
}

async function projectDocDownload(drow) {
    const res = await axios.get(`/api/project-documents/${drow.id}/download`, { responseType: 'blob' });
    const url = URL.createObjectURL(res.data);
    const el = document.createElement('a');
    el.href = url;
    el.download = drow.original_name || drow.name;
    el.click();
    URL.revokeObjectURL(url);
}

async function deleteProjectDocument(drow) {
    if (!(await ppmsConfirm(t('projects.pdDocsConfirmDelete'), { destructive: true }))) {
        return;
    }
    try {
        await axios.delete(`/api/project-documents/${drow.id}`);
        ppmsToastSuccess(t('projects.pdDocsDeleted'));
        await refreshDocuments();
        await refreshProjectMedia();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.pdDocsErr')));
    }
}

async function load() {
    loading.value = true;
    try {
        const [{ data }, mediaRes, docsRes, actRes] = await Promise.all([
            axios.get(`/api/projects/${props.id}`),
            axios.get(`/api/projects/${props.id}/media`),
            axios.get(`/api/projects/${props.id}/documents`),
            axios.get(`/api/projects/${props.id}/activities`),
        ]);
        project.value = data;
        projectMedia.value = mediaRes.data;
        projectDocuments.value = docsRes.data;
        projectActivities.value = actRes.data.activities || [];
        await loadGantt();
    } catch (e) {
        if (e?.response?.status === 404) {
            ppmsToastError(t('projects.detailNotFound'));
            router.replace({ name: 'projects' });
        } else {
            ppmsToastError(formatApiUserMessage(e, t('projects.detailLoadErr')));
        }
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

watch(focusTask, async (row) => {
    if (!row) {
        attachments.value = [];

        return;
    }
    const { data } = await axios.get(`/api/tasks/${row.id}/attachments`);
    attachments.value = data;
});

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/user');
        me.value = data;
    } catch {
        me.value = null;
    }
});

function openTaskForAttachment(a) {
    const taskId = a.task_id ?? a.task?.id;
    const tk = project.value?.tasks?.find((x) => x.id === taskId);
    if (tk) {
        activeTab.value = 'tasks';
        focusTask.value = tk;
        return;
    }
    ppmsToastWarning(t('projects.taskUpdateErr'));
}

function closeAddTaskMenu() {
    detailHeaderRef.value?.closeAddTaskMenu?.();
}

function menuAddRegular() {
    closeAddTaskMenu();
    showRegularTaskModal.value = true;
}

function menuAddBulk() {
    closeAddTaskMenu();
    initBulkRows();
    showBulkModal.value = true;
}

function menuAddByCategory() {
    closeAddTaskMenu();
    showCategoryTaskModal.value = true;
}

function menuAddProcess() {
    closeAddTaskMenu();
    showProcessTaskModal.value = true;
}

function menuAddByPhase() {
    closeAddTaskMenu();
    if (!projectPhases.value.length) {
        ppmsToastWarning(t('projects.pdPhaseTaskNoPhases'));

        return;
    }
    showPhaseTaskModal.value = true;
}

async function joinProject() {
    try {
        await axios.post(`/api/projects/${props.id}/join`);
        ppmsToastSuccess(t('projects.pdJoinOk'));
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.pdJoinErr')));
    }
}

function openGroupLabels() {
    openMetaEdit();
}

function openPhaseModal() {
    phaseNew.name = '';
    phaseNew.description = '';
    phaseNew.start_date = '';
    phaseNew.end_date = '';
    phaseNew.progress_mode = 'status_default';
    showPhaseModal.value = true;
}

async function submitNewPhase() {
    try {
        await axios.post(`/api/projects/${props.id}/phases`, {
            name: phaseNew.name.trim(),
            description: phaseNew.description?.trim() || null,
            start_date: phaseNew.start_date || null,
            end_date: phaseNew.end_date || null,
            progress_mode: phaseNew.progress_mode,
        });
        ppmsToastSuccess(t('projects.pdPhaseOk'));
        showPhaseModal.value = false;
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.pdPhaseErr')));
    }
}

async function deletePhase(ph) {
    if (!(await ppmsConfirm(t('projects.pdPhaseDeleteConfirm', { name: ph.name }), { destructive: true }))) {
        return;
    }
    try {
        await axios.delete(`/api/project-phases/${ph.id}`);
        ppmsToastSuccess(t('projects.pdPhaseDeleted'));
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.pdPhaseErr')));
    }
}

function initBulkRows() {
    bulkRows.value = [];
    for (let i = 0; i < 2; i++) {
        bulkRows.value.push({
            name: '',
            owner_id: '',
            assignee_id: '',
            due_date: '',
            start_date: '',
            estimate_hours: 8,
            complexity: 3,
            impact: 3,
            project_phase_id: '',
        });
    }
}

function addBulkRow() {
    bulkRows.value.push({
        name: '',
        owner_id: '',
        assignee_id: '',
        due_date: '',
        start_date: '',
        estimate_hours: 8,
        complexity: 3,
        impact: 3,
        project_phase_id: '',
    });
}

function removeBulkRow(ri) {
    if (bulkRows.value.length <= 1) {
        return;
    }
    bulkRows.value.splice(ri, 1);
}

async function submitBulkTasks() {
    const tasks = bulkRows.value
        .filter((r) => String(r.name || '').trim())
        .map((r) => {
            const row = {
                name: String(r.name).trim(),
                estimate_hours: Number(r.estimate_hours) || 0,
                complexity: Math.min(5, Math.max(1, Number(r.complexity) || 3)),
                impact: Math.min(5, Math.max(1, Number(r.impact) || 3)),
                due_date: r.due_date || null,
                project_phase_id: r.project_phase_id ? Number(r.project_phase_id) : null,
            };
            if (r.assignee_id) {
                row.assignee_id = Number(r.assignee_id);
            }
            const bits = [];
            if (r.owner_id) {
                bits.push(`owner:${r.owner_id}`);
            }
            if (r.start_date) {
                bits.push(`start:${r.start_date}`);
            }
            if (bits.length) {
                row.description = bits.join(' ');
            }

            return row;
        });
    if (!tasks.length) {
        ppmsToastWarning(t('projects.pdBulkEmpty'));

        return;
    }
    try {
        await axios.post(`/api/projects/${props.id}/tasks/bulk-create`, { tasks });
        ppmsToastSuccess(t('projects.pdBulkOk'));
        showBulkModal.value = false;
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.taskCreateErr')));
    }
}

async function submitRegularTask(data) {
    const payload = data && typeof data === 'object' && 'payload' in data ? data.payload : data;
    const files = data && typeof data === 'object' && Array.isArray(data.files) ? data.files : [];

    try {
        const res = await axios.post(`/api/projects/${props.id}/tasks`, payload);
        const taskId = res.data?.id;
        const list = Array.isArray(files) ? files : [];

        if (taskId && list.length) {
            for (const file of list) {
                const fd = new FormData();
                fd.append('file', file);
                await axios.post(`/api/tasks/${taskId}/attachments`, fd, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
            }
        }

        ppmsToastSuccess(t('projects.taskCreateOk'));
        showRegularTaskModal.value = false;
        activeTab.value = 'tasks';
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.taskCreateErr')));
    }
}

async function submitProcessTask(payload) {
    try {
        await axios.post(`/api/projects/${props.id}/tasks`, payload);
        ppmsToastSuccess(t('projects.taskCreateOk'));
        showProcessTaskModal.value = false;
        activeTab.value = 'tasks';
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.taskCreateErr')));
    }
}

async function submitGroupTasks({ tasks }) {
    if (!tasks?.length) {
        ppmsToastWarning(t('projects.pdBulkEmpty'));

        return;
    }
    try {
        await axios.post(`/api/projects/${props.id}/tasks/bulk-create`, { tasks });
        ppmsToastSuccess(t('projects.pdBulkOk'));
        showCategoryTaskModal.value = false;
        showPhaseTaskModal.value = false;
        activeTab.value = 'tasks';
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.taskCreateErr')));
    }
}

async function addSupply() {
    const name = supplyNew.name.trim();
    if (!name) {
        return;
    }
    try {
        await axios.post(`/api/projects/${props.id}/supplies`, {
            name,
            quantity: supplyNew.quantity ?? 0,
            unit: supplyNew.unit?.trim() || '',
            notes: supplyNew.notes?.trim() || null,
        });
        supplyNew.name = '';
        supplyNew.quantity = 0;
        supplyNew.unit = '';
        supplyNew.notes = '';
        ppmsToastSuccess(t('projects.pdSupplyOk'));
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.pdSupplyErr')));
    }
}

async function deleteSupply(s) {
    if (!(await ppmsConfirm(t('projects.pdSupplyDeleteConfirm', { name: s.name }), { destructive: true }))) {
        return;
    }
    try {
        await axios.delete(`/api/project-supplies/${s.id}`);
        ppmsToastSuccess(t('projects.pdSupplyDeleted'));
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.pdSupplyErr')));
    }
}

async function dlWeeklyPdf() {
    const res = await axios.get('/api/reports/weekly-status.pdf', { responseType: 'blob' });
    const url = URL.createObjectURL(res.data);
    const el = document.createElement('a');
    el.href = url;
    el.download = 'ppms-weekly.pdf';
    el.click();
    URL.revokeObjectURL(url);
}

async function dlProjectsCsv() {
    const res = await axios.get('/api/reports/export/projects.csv', { responseType: 'blob' });
    const url = URL.createObjectURL(res.data);
    const el = document.createElement('a');
    el.href = url;
    el.download = 'projects.csv';
    el.click();
    URL.revokeObjectURL(url);
}

function toggleFocus(task) {
    focusTask.value = focusTask.value?.id === task.id ? null : task;
}

async function removeTask(task) {
    if (
        !(await ppmsConfirm(t('projects.confirmDeleteTask', { name: task.name }), {
            destructive: true,
            confirmLabel: t('common.delete'),
        }))
    ) {
        return;
    }
    await axios.delete(`/api/tasks/${task.id}`);
    ppmsToastSuccess(t('projects.deleteTaskOk'));
    await load();
}

async function addDep() {
    depErr.value = '';
    if (!focusTask.value) {
        return;
    }
    if (!(await ppmsConfirm(t('projects.depConfirm')))) {
        return;
    }
    try {
        await axios.post(`/api/tasks/${focusTask.value.id}/dependencies`, {
            predecessor_task_id: depPredId.value,
        });
        depPredId.value = null;
        ppmsToastSuccess(t('projects.depOk'));
        await load();
    } catch (e) {
        depErr.value = formatApiUserMessage(e, t('projects.depErr'));
    }
}

async function onFile(ev) {
    const f = ev.target.files?.[0];
    if (!f || !focusTask.value) {
        return;
    }
    if (!(await ppmsConfirm(t('projects.attachConfirm', { fileName: f.name })))) {
        ev.target.value = '';
        return;
    }
    const fd = new FormData();
    fd.append('file', f);
    await axios.post(`/api/tasks/${focusTask.value.id}/attachments`, fd);
    const { data } = await axios.get(`/api/tasks/${focusTask.value.id}/attachments`);
    attachments.value = data;
    await refreshProjectMedia();
    ev.target.value = '';
    ppmsToastSuccess(t('projects.attachOk'));
}

async function submitCsat() {
    csatMsg.value = '';
    if (!(await ppmsConfirm(t('projects.confirmCsat')))) {
        return;
    }
    try {
        await axios.post(`/api/projects/${props.id}/csat`, {
            rating: csat.rating,
            milestone_label: csat.milestone_label || null,
        });
        csatMsg.value = t('projects.csatOkMsg');
        ppmsToastSuccess(t('projects.csatOkMsg'));
    } catch (e) {
        csatMsg.value = formatApiUserMessage(e, t('projects.csatErr'));
    }
}

async function downloadMediaRow(row) {
    if (row.source === 'task_attachment') {
        await downloadFile({ id: row.source_id, original_name: row.original_name || row.name });

        return;
    }
    if (row.source === 'project_document' && row.doc_type === 'upload') {
        await projectDocDownload({
            id: row.source_id,
            original_name: row.original_name || row.name,
            name: row.name,
        });
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
    if (!(await ppmsConfirm(t('projects.confirmDuplicate')))) {
        return;
    }
    const { data } = await axios.post(`/api/projects/${props.id}/duplicate`, { reset_dates: true });
    ppmsToastSuccess(t('projects.duplicateOk'));
    router.push({ name: 'project-detail', params: { id: String(data.id) } });
}
</script>
