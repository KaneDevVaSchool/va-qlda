<template>
    <div v-if="modelValue" class="ppms-modal-backdrop" @click.self="close">
        <div class="ppms-modal ppms-modal--wide" role="dialog" aria-modal="true" @click.stop>
            <h2 class="ppms-modal-title">{{ title }}</h2>
            <div class="o1tf-modal-body">
                <div v-for="(list, li) in lists" :key="'lst-' + li" class="o1tf-plus" style="margin-bottom: 1rem">
                    <div class="o1tf-plus-row" style="border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 6px; padding: 0.65rem">
                        <button
                            v-if="lists.length > 1"
                            type="button"
                            class="o1tf-plus-rm"
                            :title="t('projects.o1RemoveRow')"
                            @click="removeList(li)"
                        >
                            ×
                        </button>
                        <div style="flex: 1; min-width: 0">
                            <div class="o1tf-field" style="max-width: 320px; margin-bottom: 0.65rem">
                                <label class="o1tf-label">{{ phaseLabel }} <span class="req">*</span></label>
                                <select v-model="list.phase_id">
                                    <option value="">{{ t('projects.o1SelectPhase') }}</option>
                                    <option v-for="ph in projectPhases" :key="'lph-' + ph.id" :value="String(ph.id)">
                                        {{ ph.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="o1tf-nested">
                                <div v-for="(task, ti) in list.tasks" :key="'t-' + li + '-' + ti" class="o1tf-plus-row">
                                    <button
                                        v-if="list.tasks.length > 1"
                                        type="button"
                                        class="o1tf-plus-rm"
                                        :title="t('projects.o1RemoveRow')"
                                        @click="removeTask(li, ti)"
                                    >
                                        ×
                                    </button>
                                    <div class="o1tf-plus-cols">
                                        <div class="o1tf-field w-title">
                                            <label class="o1tf-label">{{ t('projects.o1FieldTaskName') }} <span class="req">*</span></label>
                                            <input v-model="task.name" type="text" autocomplete="off" />
                                        </div>
                                        <div class="o1tf-field w-code">
                                            <label class="o1tf-label">{{ t('projects.o1FieldCode') }}</label>
                                            <input type="text" :placeholder="t('projects.o1CodeAuto')" disabled />
                                        </div>
                                        <div class="o1tf-field w-user">
                                            <label class="o1tf-label">{{ t('projects.o1Owner') }}</label>
                                            <O1UserLookupSelect
                                                v-model="task.owner_id"
                                                :base-users="userOptions"
                                                :search-placeholder="t('projects.createUserSearchPlaceholder')"
                                                :search-aria="t('projects.o1UserAriaSearchOwners')"
                                                :min-hint="t('projects.createUserSearchMinHint')"
                                                :empty-text="t('projects.createUserSearchEmpty')"
                                                :loading-text="t('common.loading')"
                                                :clear-aria="t('common.close')"
                                            />
                                        </div>
                                        <div class="o1tf-field w-user">
                                            <label class="o1tf-label">{{ t('projects.thAssigneeFull') }}</label>
                                            <O1UserLookupSelect
                                                v-model="task.assignee_id"
                                                :base-users="userOptions"
                                                :search-placeholder="t('projects.createUserSearchPlaceholder')"
                                                :search-aria="t('projects.o1UserAriaSearchAssignees')"
                                                :min-hint="t('projects.createUserSearchMinHint')"
                                                :empty-text="t('projects.createUserSearchEmpty')"
                                                :loading-text="t('common.loading')"
                                                :clear-aria="t('common.close')"
                                            />
                                        </div>
                                        <div class="o1tf-field w-dates">
                                            <label class="o1tf-label">{{ t('projects.o1PlanRange') }}</label>
                                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap">
                                                <input v-model="task.start_date" type="date" />
                                                <input v-model="task.due_date" type="date" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="o1tf-plus-footer">
                                    <button type="button" class="o1tf-plus-add" :title="t('projects.o1AddTaskRow')" @click="addTask(li)">
                                        +
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="o1tf-plus-footer">
                    <button type="button" class="o1tf-plus-add" :title="t('projects.o1AddListRow')" @click="addList">
                        +
                    </button>
                </div>
                <p v-if="err" class="ppms-error">{{ err }}</p>
            </div>
            <div class="ppms-modal-actions">
                <button type="button" class="ppms-btn-ghost" @click="close">{{ t('common.cancel') }}</button>
                <button type="button" class="ppms-btn-primary" @click="submit">{{ t('projects.pdBulkSubmit') }}</button>
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
    variant: { type: String, required: true }, // 'category' | 'phase'
    project: { type: Object, required: true },
    projectPhases: { type: Array, required: true },
});

const emit = defineEmits(['update:modelValue', 'submit']);

const lists = ref([]);
const err = ref('');

const title = computed(() =>
    props.variant === 'category' ? t('projects.o1ByCategoryTitle') : t('projects.o1ByPhaseTitle'),
);

const phaseLabel = computed(() =>
    props.variant === 'category' ? t('projects.o1CategoryProject') : t('projects.o1PickPhase'),
);

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

function emptyTask() {
    return {
        name: '',
        owner_id: '',
        assignee_id: '',
        due_date: '',
        start_date: '',
        estimate_hours: 8,
        complexity: 3,
        impact: 3,
    };
}

function reset() {
    err.value = '';
    lists.value = [
        {
            phase_id: '',
            tasks: [emptyTask()],
        },
    ];
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

function addList() {
    lists.value.push({
        phase_id: '',
        tasks: [emptyTask()],
    });
}

function removeList(li) {
    if (lists.value.length <= 1) {
        return;
    }
    lists.value.splice(li, 1);
}

function addTask(li) {
    lists.value[li].tasks.push(emptyTask());
}

function removeTask(li, ti) {
    const tks = lists.value[li].tasks;
    if (tks.length <= 1) {
        return;
    }
    tks.splice(ti, 1);
}

function submit() {
    err.value = '';
    const out = [];
    for (const list of lists.value) {
        if (!list.phase_id) {
            err.value = t('projects.o1ErrPhaseEach');

            return;
        }
        const pid = Number(list.phase_id);
        for (const task of list.tasks) {
            const name = String(task.name || '').trim();
            if (!name) {
                continue;
            }
            const row = {
                name,
                estimate_hours: Number(task.estimate_hours) || 0,
                complexity: Math.min(5, Math.max(1, Number(task.complexity) || 3)),
                impact: Math.min(5, Math.max(1, Number(task.impact) || 3)),
                project_phase_id: pid,
            };
            if (task.assignee_id) {
                row.assignee_id = Number(task.assignee_id);
            }
            if (task.due_date) {
                row.due_date = task.due_date;
            }
            let desc = '';
            if (task.owner_id) {
                desc = `(owner:${task.owner_id})`;
            }
            if (task.start_date) {
                const line = `(${t('projects.o1Start')}: ${task.start_date})`;

                desc = desc ? `${desc} ${line}` : line;
            }
            if (desc) {
                row.description = desc;
            }
            out.push(row);
        }
    }
    if (!out.length) {
        err.value = t('projects.pdBulkEmpty');

        return;
    }
    emit('submit', { tasks: out });
}
</script>
