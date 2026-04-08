<template>
    <div v-if="modelValue" class="ppms-modal-backdrop" @click.self="$emit('update:modelValue', false)">
        <div class="ppms-modal ppms-modal--wide">
            <h2>{{ t('projects.pdBulkModalTitle') }}</h2>
            <p class="ppms-muted">{{ t('projects.pdBulkModalHint') }}</p>
            <div class="ppms-table-scroll ppms-mt-sm">
                <table class="ppms-table">
                    <thead>
                        <tr>
                            <th>{{ t('projects.thTask') }}</th>
                            <th>{{ t('projects.thWeight') }}</th>
                            <th>C×I</th>
                            <th>{{ t('projects.colStart') }}</th>
                            <th>{{ t('projects.taskPhasePh') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, ri) in bulkRows" :key="'br-' + ri">
                            <td>
                                <input v-model="row.name" type="text" />
                            </td>
                            <td>
                                <input v-model.number="row.estimate_hours" type="number" min="0" step="0.5" />
                            </td>
                            <td>
                                <input v-model.number="row.complexity" type="number" min="1" max="5" />
                                <input v-model.number="row.impact" type="number" min="1" max="5" />
                            </td>
                            <td>
                                <input v-model="row.due_date" type="date" />
                            </td>
                            <td>
                                <select v-model="row.project_phase_id">
                                    <option value="">{{ t('projects.taskPhasePh') }}</option>
                                    <option v-for="ph in projectPhases" :key="'bph-' + ph.id" :value="String(ph.id)">
                                        {{ ph.name }}
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="ppms-row ppms-mt-sm">
                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="$emit('add-row')">
                    {{ t('projects.pdBulkAddRow') }}
                </button>
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
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    modelValue: { type: Boolean, required: true },
    bulkRows: { type: Array, required: true },
    projectPhases: { type: Array, required: true },
});

defineEmits(['update:modelValue', 'add-row', 'submit']);
</script>
