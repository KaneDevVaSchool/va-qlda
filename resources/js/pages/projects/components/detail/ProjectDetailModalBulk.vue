<template>
    <div v-if="modelValue" class="ppms-modal-backdrop" @click.self="$emit('update:modelValue', false)">
        <div class="ppms-modal ppms-modal--wide" role="dialog" aria-modal="true" @click.stop>
            <h2 class="ppms-modal-title">{{ t('projects.pdBulkModalTitle') }}</h2>
            <p class="ppms-muted">{{ t('projects.o1BulkIntro') }}</p>
            <div class="o1tf-modal-body">
                <div class="o1tf-plus">
                    <div v-for="(row, ri) in bulkRows" :key="'br-' + ri" class="o1tf-plus-row">
                        <button type="button" class="o1tf-plus-rm" :title="t('projects.o1RemoveRow')" @click="$emit('remove-row', ri)">
                            ×
                        </button>
                        <div class="o1tf-plus-cols">
                            <div class="o1tf-field w-title">
                                <label class="o1tf-label">{{ t('projects.o1FieldTaskName') }} <span class="req">*</span></label>
                                <input v-model="row.name" type="text" autocomplete="off" />
                            </div>
                            <div class="o1tf-field w-code">
                                <label class="o1tf-label">{{ t('projects.o1FieldCode') }}</label>
                                <input type="text" :placeholder="t('projects.o1CodeAuto')" disabled />
                            </div>
                            <div class="o1tf-field w-user">
                                <label class="o1tf-label">{{ t('projects.o1Owner') }}</label>
                                <select v-model="row.owner_id">
                                    <option value="">{{ t('projects.o1SelectOwner') }}</option>
                                    <option v-for="u in userOptions" :key="'ow-' + u.id" :value="String(u.id)">
                                        {{ userLabel(u) }}
                                    </option>
                                </select>
                            </div>
                            <div class="o1tf-field w-user">
                                <label class="o1tf-label">{{ t('projects.thAssigneeFull') }}</label>
                                <select v-model="row.assignee_id">
                                    <option value="">{{ t('projects.o1SelectAssignee') }}</option>
                                    <option v-for="u in userOptions" :key="'as-' + u.id" :value="String(u.id)">
                                        {{ userLabel(u) }}
                                    </option>
                                </select>
                            </div>
                            <div class="o1tf-field w-dates">
                                <label class="o1tf-label">{{ t('projects.o1PlanRange') }}</label>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap">
                                    <input v-model="row.start_date" type="date" />
                                    <input v-model="row.due_date" type="date" />
                                </div>
                            </div>
                            <div class="o1tf-field" style="min-width: 100px">
                                <label class="o1tf-label">{{ t('projects.thWeight') }}</label>
                                <input v-model.number="row.estimate_hours" type="number" min="0" step="0.5" />
                            </div>
                            <div class="o1tf-field" style="min-width: 72px">
                                <label class="o1tf-label">C</label>
                                <input v-model.number="row.complexity" type="number" min="1" max="5" />
                            </div>
                            <div class="o1tf-field" style="min-width: 72px">
                                <label class="o1tf-label">I</label>
                                <input v-model.number="row.impact" type="number" min="1" max="5" />
                            </div>
                            <div class="o1tf-field" style="min-width: 160px">
                                <label class="o1tf-label">{{ t('projects.taskPhasePh') }}</label>
                                <select v-model="row.project_phase_id">
                                    <option value="">{{ t('projects.o1SelectPhase') }}</option>
                                    <option v-for="ph in projectPhases" :key="'bph-' + ph.id" :value="String(ph.id)">
                                        {{ ph.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="o1tf-plus-footer">
                    <button type="button" class="o1tf-plus-add" :title="t('projects.pdBulkAddRow')" @click="$emit('add-row')">
                        +
                    </button>
                </div>
            </div>
            <div class="ppms-modal-actions">
                <button type="button" class="ppms-btn-ghost" @click="$emit('update:modelValue', false)">
                    {{ t('common.cancel') }}
                </button>
                <button type="button" class="ppms-btn-primary" @click="$emit('submit')">
                    {{ t('projects.pdBulkSubmit') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    modelValue: { type: Boolean, required: true },
    bulkRows: { type: Array, required: true },
    projectPhases: { type: Array, required: true },
    project: { type: Object, default: null },
});

defineEmits(['update:modelValue', 'add-row', 'remove-row', 'submit']);

const userOptions = computed(() => {
    const m = new Map();
    const p = props.project;
    if (!p) {
        return [];
    }
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

function userLabel(u) {
    const mail = u.email ? ` — ${u.email}` : '';

    return `${u.name || u.id}${mail}`;
}

</script>
