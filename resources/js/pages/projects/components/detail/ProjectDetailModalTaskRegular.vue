<template>
    <div v-if="modelValue" class="ppms-modal-backdrop" @click.self="close">
        <div class="ppms-modal ppms-modal--wide" role="dialog" aria-modal="true" @click.stop>
            <h2 class="ppms-modal-title">{{ t('projects.o1TaskRegularTitle') }}</h2>
            <div class="o1tf-modal-body">
                <div class="o1tf-tabs">
                    <div class="o1tf-tab-head">
                        <button
                            type="button"
                            class="o1tf-tab-label"
                            :class="{ 'is-active': tab === 'general' }"
                            @click="tab = 'general'"
                        >
                            {{ t('projects.o1TabGeneral') }}
                        </button>
                        <button
                            type="button"
                            class="o1tf-tab-label"
                            :class="{ 'is-active': tab === 'advanced' }"
                            @click="tab = 'advanced'"
                        >
                            {{ t('projects.o1TabAdvanced') }}
                        </button>
                    </div>
                    <div class="o1tf-tab-pane" :class="{ 'is-active': tab === 'general' }">
                        <div class="o1tf-section">
                            <div class="o1tf-section-title">
                                <span class="o1tf-section-caret" aria-hidden="true">◀</span>
                                {{ t('projects.o1SecGeneral') }}
                            </div>
                            <div class="o1tf-section-body">
                                <div class="o1tf-row">
                                    <div class="o1tf-col-4 o1tf-field">
                                        <span class="o1tf-label">{{ t('projects.o1FieldCode') }}</span>
                                        <input type="text" :value="codePlaceholder" disabled readonly />
                                        <span class="o1tf-hint">{{ t('projects.o1CodeHint') }}</span>
                                    </div>
                                    <div class="o1tf-col-8 o1tf-field">
                                        <label class="o1tf-label">{{ t('projects.o1FieldTitle') }} <span class="req">*</span></label>
                                        <input v-model="form.name" type="text" autocomplete="off" />
                                    </div>
                                </div>
                                <div class="o1tf-row">
                                    <div class="o1tf-field" style="flex: 1 1 280px">
                                        <label class="o1tf-label">{{ t('projects.taskPhasePh') }}</label>
                                        <select v-model="form.project_phase_id">
                                            <option value="">{{ t('projects.o1SelectPhase') }}</option>
                                            <option v-for="ph in projectPhases" :key="'rg-ph-' + ph.id" :value="String(ph.id)">
                                                {{ ph.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="o1tf-row">
                                    <div class="o1tf-field" style="flex: 1 1 200px">
                                        <span class="o1tf-label">{{ t('projects.o1Start') }}</span>
                                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap">
                                            <input type="time" disabled class="o1tf-time" />
                                            <input v-model="form.start_date" type="date" />
                                        </div>
                                        <span class="o1tf-hint">{{ t('projects.o1StartHint') }}</span>
                                    </div>
                                    <div class="o1tf-field" style="flex: 1 1 200px">
                                        <span class="o1tf-label">{{ t('projects.o1End') }}</span>
                                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap">
                                            <input type="time" disabled class="o1tf-time" />
                                            <input v-model="form.due_date" type="date" />
                                        </div>
                                    </div>
                                </div>
                                <div class="o1tf-row">
                                    <label class="o1tf-check">
                                        <input v-model="form.is_assign_hour" type="checkbox" value="1" disabled />
                                        <span>{{ t('projects.o1AssignHour') }}</span>
                                    </label>
                                </div>
                                <div class="o1tf-row">
                                    <div class="o1tf-field" style="flex: 1 1 240px">
                                        <label class="o1tf-label">{{ t('projects.thAssigneeFull') }}</label>
                                        <select v-model="form.assignee_id">
                                            <option value="">{{ t('projects.o1SelectAssignee') }}</option>
                                            <option v-for="u in userOptions" :key="'u-' + u.id" :value="String(u.id)">
                                                {{ userLabel(u) }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="o1tf-row">
                                    <div class="o1tf-field" style="flex: 1 1 100%">
                                        <label class="o1tf-label">{{ t('projects.o1FieldDesc') }}</label>
                                        <textarea v-model="form.description" rows="3" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="o1tf-section">
                            <div class="o1tf-section-title">
                                <span class="o1tf-section-caret" aria-hidden="true">◀</span>
                                {{ t('projects.o1SecOther') }}
                            </div>
                            <div class="o1tf-section-body">
                                <div class="o1tf-row">
                                    <div class="o1tf-field" style="flex: 1 1 200px">
                                        <label class="o1tf-label">{{ t('projects.o1FieldCategory') }}</label>
                                        <input v-model="form.category" type="text" />
                                    </div>
                                    <div class="o1tf-field" style="flex: 1 1 200px">
                                        <label class="o1tf-label">{{ t('projects.o1FieldProgressMode') }} <span class="req">*</span></label>
                                        <select v-model="form.progress_mode">
                                            <option v-for="m in progressModes" :key="m.id" :value="m.id">{{ m.label }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="o1tf-row">
                                    <div class="o1tf-field" style="flex: 1 1 120px">
                                        <label class="o1tf-label">{{ t('projects.thWeight') }}</label>
                                        <input v-model.number="form.estimate_hours" type="number" min="0" step="0.5" />
                                    </div>
                                    <div class="o1tf-field" style="flex: 1 1 100px">
                                        <label class="o1tf-label">{{ t('projects.o1Complexity') }}</label>
                                        <input v-model.number="form.complexity" type="number" min="1" max="5" />
                                    </div>
                                    <div class="o1tf-field" style="flex: 1 1 100px">
                                        <label class="o1tf-label">{{ t('projects.o1Impact') }}</label>
                                        <input v-model.number="form.impact" type="number" min="1" max="5" />
                                    </div>
                                </div>
                                <div v-if="form.progress_mode === 'manual_pct'" class="o1tf-row">
                                    <div class="o1tf-field" style="flex: 1 1 160px">
                                        <label class="o1tf-label">{{ t('projects.o1ManualPct') }}</label>
                                        <input v-model.number="form.manual_progress_pct" type="number" min="0" max="100" step="0.1" />
                                    </div>
                                </div>
                                <div v-if="form.progress_mode === 'volume_ratio'" class="o1tf-row">
                                    <div class="o1tf-field" style="flex: 1 1 140px">
                                        <label class="o1tf-label">{{ t('projects.o1VolTotal') }}</label>
                                        <input v-model.number="form.volume_total" type="number" min="0" />
                                    </div>
                                    <div class="o1tf-field" style="flex: 1 1 140px">
                                        <label class="o1tf-label">{{ t('projects.o1VolDone') }}</label>
                                        <input v-model.number="form.volume_done" type="number" min="0" />
                                    </div>
                                </div>
                                <div v-if="form.progress_mode === 'checklist_ratio'" class="o1tf-row">
                                    <div class="o1tf-field" style="flex: 1 1 140px">
                                        <label class="o1tf-label">{{ t('projects.o1ClTotal') }}</label>
                                        <input v-model.number="form.checklist_total" type="number" min="0" />
                                    </div>
                                    <div class="o1tf-field" style="flex: 1 1 140px">
                                        <label class="o1tf-label">{{ t('projects.o1ClDone') }}</label>
                                        <input v-model.number="form.checklist_done" type="number" min="0" />
                                    </div>
                                </div>
                                <div class="o1tf-row">
                                    <div class="o1tf-field" style="flex: 1 1 280px">
                                        <label class="o1tf-label">{{ t('projects.o1ParentTask') }}</label>
                                        <select v-model="form.parent_id">
                                            <option value="">{{ t('projects.o1ParentEmpty') }}</option>
                                            <option v-for="tk in parentTaskOptions" :key="'pt-' + tk.id" :value="String(tk.id)">
                                                {{ tk.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <p class="o1tf-muted">{{ t('projects.o1ExtraNotSupported') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="o1tf-tab-pane" :class="{ 'is-active': tab === 'advanced' }">
                        <div class="o1tf-section">
                            <div class="o1tf-section-title">
                                <span class="o1tf-section-caret" aria-hidden="true">◀</span>
                                {{ t('projects.o1SecPermissions') }}
                            </div>
                            <div class="o1tf-section-body">
                                <div class="o1tf-row">
                                    <label class="o1tf-check">
                                        <input type="checkbox" disabled />
                                        <span>{{ t('projects.o1PermCross') }}</span>
                                    </label>
                                </div>
                                <div class="o1tf-row">
                                    <label class="o1tf-check">
                                        <input type="checkbox" disabled />
                                        <span>{{ t('projects.o1PermParent') }}</span>
                                    </label>
                                </div>
                                <div class="o1tf-row">
                                    <div class="o1tf-field" style="flex: 1 1 100%">
                                        <span class="o1tf-label">{{ t('projects.o1ReportDesc') }}</span>
                                        <div class="o1tf-radio-group">
                                            <label><input v-model="form._advDesc" type="radio" value="no" disabled /> {{ t('projects.o1No') }}</label>
                                            <label><input type="radio" disabled /> {{ t('projects.o1ReportDescAlway') }}</label>
                                        </div>
                                    </div>
                                </div>
                                <p class="o1tf-hint">{{ t('projects.o1AdvancedHint') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-if="err" class="ppms-error">{{ err }}</p>
            </div>
            <div class="ppms-modal-actions">
                <button type="button" class="ppms-btn-ghost" @click="close">{{ t('common.cancel') }}</button>
                <button type="button" class="ppms-btn-primary" @click="submit">{{ t('common.save') }}</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    modelValue: { type: Boolean, required: true },
    project: { type: Object, required: true },
    projectPhases: { type: Array, required: true },
});

const emit = defineEmits(['update:modelValue', 'submit']);

const tab = ref('general');
const err = ref('');

const form = ref({
    name: '',
    description: '',
    project_phase_id: '',
    assignee_id: '',
    parent_id: '',
    estimate_hours: 8,
    complexity: 3,
    impact: 3,
    due_date: '',
    start_date: '',
    category: '',
    progress_mode: 'status_default',
    manual_progress_pct: null,
    volume_total: null,
    volume_done: null,
    checklist_total: null,
    checklist_done: null,
    is_assign_hour: false,
    _advDesc: 'no',
});

const codePlaceholder = computed(() => {
    const y = new Date().getFullYear();

    return `DA_${y}_{count}`;
});

const progressModes = computed(() =>
    ['status_default', 'manual_pct', 'volume_ratio', 'checklist_ratio', 'child_weight', 'time_auto'].map((id) => ({
        id,
        label: t(`projects.pdPm_${id}`),
    })),
);

const userOptions = computed(() => {
    const m = new Map();
    for (const u of props.project?.executor_users || []) {
        m.set(u.id, u);
    }
    for (const u of props.project?.follower_users || []) {
        m.set(u.id, u);
    }
    const owner = props.project?.owner;
    if (owner?.id) {
        m.set(owner.id, { id: owner.id, name: owner.name, email: owner.email });
    }

    return [...m.values()].sort((a, b) => String(a.name).localeCompare(String(b.name)));
});

function userLabel(u) {
    const mail = u.email ? ` — ${u.email}` : '';

    return `${u.name || u.id}${mail}`;
}

const parentTaskOptions = computed(() => {
    const tasks = props.project?.tasks || [];

    return tasks.filter((tk) => !tk.parent_id);
});

function resetForm() {
    err.value = '';
    tab.value = 'general';
    form.value = {
        name: '',
        description: '',
        project_phase_id: '',
        assignee_id: '',
        parent_id: '',
        estimate_hours: 8,
        complexity: 3,
        impact: 3,
        due_date: '',
        start_date: '',
        category: '',
        progress_mode: 'status_default',
        manual_progress_pct: null,
        volume_total: null,
        volume_done: null,
        checklist_total: null,
        checklist_done: null,
        is_assign_hour: false,
        _advDesc: 'no',
    };
}

watch(
    () => props.modelValue,
    (open) => {
        if (open) {
            resetForm();
        }
    },
);

function close() {
    emit('update:modelValue', false);
}

function buildPayload() {
    const f = form.value;
    const name = String(f.name || '').trim();
    if (!name) {
        return null;
    }
    const payload = {
        name,
        description: f.description?.trim() || null,
        estimate_hours: Number(f.estimate_hours) || 0,
        complexity: Math.min(5, Math.max(1, Number(f.complexity) || 3)),
        impact: Math.min(5, Math.max(1, Number(f.impact) || 3)),
        progress_mode: f.progress_mode || 'status_default',
    };
    if (f.assignee_id) {
        payload.assignee_id = Number(f.assignee_id);
    }
    if (f.project_phase_id) {
        payload.project_phase_id = Number(f.project_phase_id);
    }
    if (f.parent_id) {
        payload.parent_id = Number(f.parent_id);
    }
    if (f.due_date) {
        payload.due_date = f.due_date;
    }
    if (f.category?.trim()) {
        payload.category = f.category.trim();
    }
    if (f.progress_mode === 'manual_pct' && f.manual_progress_pct != null && f.manual_progress_pct !== '') {
        payload.manual_progress_pct = Number(f.manual_progress_pct);
    }
    if (f.progress_mode === 'volume_ratio') {
        if (f.volume_total != null && f.volume_total !== '') {
            payload.volume_total = Number(f.volume_total);
        }
        if (f.volume_done != null && f.volume_done !== '') {
            payload.volume_done = Number(f.volume_done);
        }
    }
    if (f.progress_mode === 'checklist_ratio') {
        if (f.checklist_total != null && f.checklist_total !== '') {
            payload.checklist_total = Number(f.checklist_total);
        }
        if (f.checklist_done != null && f.checklist_done !== '') {
            payload.checklist_done = Number(f.checklist_done);
        }
    }
    if (f.start_date) {
        const line = `(${t('projects.o1Start')}: ${f.start_date})`;
        const base = payload.description != null && payload.description !== '' ? String(payload.description) : '';

        payload.description = base ? `${base}\n${line}` : line;
    }

    return payload;
}

function submit() {
    const payload = buildPayload();
    if (!payload) {
        err.value = t('projects.o1ErrTitle');

        return;
    }
    err.value = '';
    emit('submit', payload);
}
</script>

<style scoped>
.o1tf-time {
    max-width: 7rem;
    opacity: 0.45;
}
</style>
