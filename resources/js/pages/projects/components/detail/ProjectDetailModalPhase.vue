<!-- eslint-disable vue/no-mutating-props -- parent reactive phaseNew -->
<template>
    <div v-if="modelValue" class="ppms-modal-backdrop" @click.self="$emit('update:modelValue', false)">
        <div class="ppms-modal ppms-modal--wide">
            <h2>{{ t('projects.pdPhaseModalTitle') }}</h2>
            <p class="ppms-muted">{{ t('projects.pdPhaseModalHint') }}</p>
            <form class="ppms-stack ppms-mt-sm" @submit.prevent="$emit('submit')">
                <label class="ppms-field">
                    <span>{{ t('projects.pdPhaseName') }} *</span>
                    <input v-model="phaseNew.name" required />
                </label>
                <div class="ppms-split">
                    <label class="ppms-field">
                        <span>{{ t('projects.colStart') }}</span>
                        <input v-model="phaseNew.start_date" type="date" />
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('projects.pdFieldDeadline') }}</span>
                        <input v-model="phaseNew.end_date" type="date" />
                    </label>
                </div>
                <label class="ppms-field">
                    <span>{{ t('projects.pdProgressMode') }} *</span>
                    <select v-model="phaseNew.progress_mode" required>
                        <option v-for="m in progressModeOptions" :key="'pm-' + m.id" :value="m.id">
                            {{ m.label }}
                        </option>
                    </select>
                </label>
                <label class="ppms-field">
                    <span>{{ t('projects.fieldDescription') }}</span>
                    <textarea v-model="phaseNew.description" rows="2"></textarea>
                </label>
                <div class="ppms-modal-actions">
                    <button type="button" class="ppms-btn-ghost" @click="$emit('update:modelValue', false)">
                        {{ t('common.cancel') }}
                    </button>
                    <button type="submit" class="ppms-btn-primary">{{ t('projects.pdPhaseSubmit') }}</button>
                </div>
            </form>
            <div v-if="projectPhases.length" class="ppms-mt">
                <h3 class="ppms-pd-mini-h">{{ t('projects.pdPhaseList') }}</h3>
                <ul class="ppms-pd-phase-existing">
                    <li v-for="ph in projectPhases" :key="'pex-' + ph.id">
                        <strong>{{ ph.name }}</strong>
                        <span class="ppms-muted">{{ ph.start_date || '—' }} → {{ ph.end_date || '—' }}</span>
                        <button
                            v-if="canManageProject"
                            type="button"
                            class="ppms-btn-ghost ppms-btn-sm"
                            @click="$emit('delete-phase', ph)"
                        >
                            {{ t('common.delete') }}
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    modelValue: { type: Boolean, required: true },
    phaseNew: { type: Object, required: true },
    progressModeOptions: { type: Array, required: true },
    projectPhases: { type: Array, required: true },
    canManageProject: { type: Boolean, default: false },
});

defineEmits(['update:modelValue', 'submit', 'delete-phase']);
</script>
