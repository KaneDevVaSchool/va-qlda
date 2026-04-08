<template>
    <div v-if="modelValue" class="ppms-modal-backdrop" @click.self="close">
        <div class="ppms-modal ppms-modal--wide" role="dialog" aria-modal="true" @click.stop>
            <h2 class="ppms-modal-title">{{ t('projects.o1ProcessTitle') }}</h2>
            <div class="o1tf-modal-body">
                <div class="o1tf-tabs">
                    <div class="o1tf-tab-head">
                        <button type="button" class="o1tf-tab-label is-active">{{ t('projects.o1TabGeneral') }}</button>
                    </div>
                </div>
                <div class="o1tf-section">
                    <div class="o1tf-section-title">
                        <span class="o1tf-section-caret" aria-hidden="true">◀</span>
                        {{ t('projects.o1SecGeneral') }}
                    </div>
                    <div class="o1tf-section-body">
                        <div class="o1tf-row">
                            <div class="o1tf-col-4 o1tf-field">
                                <label class="o1tf-label">{{ t('projects.o1FieldCode') }}</label>
                                <input v-model="form.code_hint" type="text" />
                                <span class="o1tf-hint">{{ t('projects.o1ProcessCodeHint') }}</span>
                            </div>
                            <div class="o1tf-col-8 o1tf-field">
                                <label class="o1tf-label">{{ t('projects.o1FieldTitle') }} <span class="req">*</span></label>
                                <input v-model="form.name" type="text" autocomplete="off" />
                            </div>
                        </div>
                        <div class="o1tf-row">
                            <div class="o1tf-field" style="flex: 1 1 260px">
                                <label class="o1tf-label">{{ t('projects.taskPhasePh') }}</label>
                                <select v-model="form.project_phase_id">
                                    <option value="">{{ t('projects.o1SelectPhase') }}</option>
                                    <option v-for="ph in projectPhases" :key="'pr-ph-' + ph.id" :value="String(ph.id)">
                                        {{ ph.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="o1tf-field" style="flex: 1 1 320px">
                                <label class="o1tf-label">{{ t('projects.o1ApplyProcess') }} <span class="req">*</span></label>
                                <input v-model="form.process_label" type="text" :placeholder="t('projects.o1ProcessPh')" />
                            </div>
                        </div>
                        <div class="o1tf-row">
                            <div class="o1tf-field" style="flex: 1 1 200px">
                                <span class="o1tf-label">{{ t('projects.o1Start') }}</span>
                                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap">
                                    <input type="time" disabled class="o1tf-time" />
                                    <input v-model="form.start_date" type="date" />
                                </div>
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
                            <div class="o1tf-field" style="flex: 1 1 240px">
                                <label class="o1tf-label">{{ t('projects.o1Manager') }} <span class="req">*</span></label>
                                <O1UserLookupSelect
                                    v-model="form.assignee_id"
                                    :base-users="userOptions"
                                    :search-placeholder="t('projects.createUserSearchPlaceholder')"
                                    :search-aria="t('projects.o1UserAriaSearchAssignees')"
                                    :min-hint="t('projects.createUserSearchMinHint')"
                                    :empty-text="t('projects.createUserSearchEmpty')"
                                    :loading-text="t('common.loading')"
                                    :clear-aria="t('common.close')"
                                />
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
                        {{ t('projects.o1SecProcess') }}
                    </div>
                    <div class="o1tf-section-body">
                        <div class="o1tf-process-preview">{{ t('projects.o1ProcessEmpty') }}</div>
                    </div>
                </div>
                <div class="o1tf-section">
                    <div class="o1tf-section-title">
                        <span class="o1tf-section-caret" aria-hidden="true">◀</span>
                        {{ t('projects.o1SecAdvShort') }}
                    </div>
                    <div class="o1tf-section-body">
                        <div class="o1tf-row">
                            <div class="o1tf-field" style="flex: 1 1 280px">
                                <label class="o1tf-label">{{ t('projects.o1ParentTask') }}</label>
                                <select v-model="form.parent_id">
                                    <option value="">{{ t('projects.o1ParentEmpty') }}</option>
                                    <option v-for="tk in parentTaskOptions" :key="'ppt-' + tk.id" :value="String(tk.id)">
                                        {{ tk.name }}
                                    </option>
                                </select>
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
import O1UserLookupSelect from './O1UserLookupSelect.vue';

const { t } = useI18n();

const props = defineProps({
    modelValue: { type: Boolean, required: true },
    project: { type: Object, required: true },
    projectPhases: { type: Array, required: true },
});

const emit = defineEmits(['update:modelValue', 'submit']);

const err = ref('');
const form = ref({
    code_hint: '',
    name: '',
    process_label: '',
    project_phase_id: '',
    assignee_id: '',
    parent_id: '',
    due_date: '',
    start_date: '',
    description: '',
    estimate_hours: 8,
    complexity: 3,
    impact: 3,
});

const userOptions = computed(() => {
    const m = new Map();
    const p = props.project;
    for (const u of p.executor_users || []) {
        m.set(u.id, u);
    }
    for (const u of p.follower_users || []) {
        m.set(u.id, u);
    }
    const owner = p.owner;
    if (owner?.id) {
        m.set(owner.id, { id: owner.id, name: owner.name, email: owner.email });
    }

    return [...m.values()].sort((a, b) => String(a.name).localeCompare(String(b.name)));
});

const parentTaskOptions = computed(() => (props.project?.tasks || []).filter((tk) => !tk.parent_id));

function reset() {
    err.value = '';
    form.value = {
        code_hint: '',
        name: '',
        process_label: '',
        project_phase_id: '',
        assignee_id: '',
        parent_id: '',
        due_date: '',
        start_date: '',
        description: '',
        estimate_hours: 8,
        complexity: 3,
        impact: 3,
    };
}

watch(
    () => props.modelValue,
    (open) => {
        if (open) {
            reset();
        }
    },
);

function close() {
    emit('update:modelValue', false);
}

function submit() {
    err.value = '';
    const f = form.value;
    const name = String(f.name || '').trim();
    const proc = String(f.process_label || '').trim();
    if (!name) {
        err.value = t('projects.o1ErrTitle');

        return;
    }
    if (!proc) {
        err.value = t('projects.o1ErrProcess');

        return;
    }
    if (!f.assignee_id) {
        err.value = t('projects.o1ErrManager');

        return;
    }
    let description = f.description?.trim() || '';
    const bits = [`[${t('projects.o1ApplyProcess')}: ${proc}]`];
    if (f.code_hint?.trim()) {
        bits.push(`${t('projects.o1FieldCode')}: ${f.code_hint.trim()}`);
    }
    if (f.start_date) {
        bits.push(`${t('projects.o1Start')}: ${f.start_date}`);
    }
    const head = bits.join(' ');
    description = description ? `${head}\n${description}` : head;

    const cat = `workflow:${proc}`.slice(0, 128);
    const payload = {
        name,
        description,
        estimate_hours: Number(f.estimate_hours) || 0,
        complexity: Math.min(5, Math.max(1, Number(f.complexity) || 3)),
        impact: Math.min(5, Math.max(1, Number(f.impact) || 3)),
        assignee_id: Number(f.assignee_id),
        category: cat,
    };
    if (f.project_phase_id) {
        payload.project_phase_id = Number(f.project_phase_id);
    }
    if (f.parent_id) {
        payload.parent_id = Number(f.parent_id);
    }
    if (f.due_date) {
        payload.due_date = f.due_date;
    }
    emit('submit', payload);
}
</script>

<style scoped>
.o1tf-time {
    max-width: 7rem;
    opacity: 0.45;
}
</style>
