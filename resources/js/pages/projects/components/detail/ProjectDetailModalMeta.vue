<!-- eslint-disable vue/no-mutating-props -- parent reactive metaForm -->
<template>
    <div v-if="modelValue" class="ppms-modal-backdrop" @click.self="$emit('update:modelValue', false)">
        <div class="ppms-modal">
            <h2>{{ t('projects.editStakeholders') }}</h2>
            <form class="ppms-stack" @submit.prevent="$emit('save')">
                <label class="ppms-field">
                    <span>{{ t('projects.fieldCustomerName') }}</span>
                    <input v-model="metaForm.customer_name" />
                </label>
                <label class="ppms-field">
                    <span>{{ t('projects.fieldCustomerEmail') }}</span>
                    <input v-model="metaForm.customer_email" type="email" />
                </label>
                <label class="ppms-field">
                    <span>{{ t('projects.fieldLabelsHint') }}</span>
                    <input v-model="metaForm.labels_text" type="text" autocomplete="off" />
                </label>
                <label class="ppms-field">
                    <span>{{ t('projects.colStart') }}</span>
                    <input v-model="metaForm.start_date" type="date" />
                </label>
                <label class="ppms-field">
                    <span>{{ t('projects.colActualStart') }}</span>
                    <input v-model="metaForm.actual_start_date" type="date" />
                </label>
                <div>
                    <span class="ppms-field" style="display: block; margin-bottom: 0.5rem">{{
                        t('projects.suppliersTitle')
                    }}</span>
                    <div v-for="(s, i) in metaForm.suppliers" :key="i" class="ppms-supplier-edit-row ppms-mt-sm">
                        <label class="ppms-field">
                            <span>{{ t('projects.fieldSupplierName') }}</span>
                            <input v-model="s.name" />
                        </label>
                        <label class="ppms-field">
                            <span>{{ t('projects.fieldSupplierContact') }}</span>
                            <input v-model="s.contact" />
                        </label>
                        <button
                            v-if="metaForm.suppliers.length > 1"
                            type="button"
                            class="ppms-btn-ghost ppms-btn-sm ppms-supplier-edit-remove"
                            @click.prevent="$emit('remove-supplier', i)"
                        >
                            {{ t('projects.removeSupplier') }}
                        </button>
                    </div>
                    <button type="button" class="ppms-btn-ghost ppms-mt-sm" @click="$emit('add-supplier')">
                        {{ t('projects.addSupplier') }}
                    </button>
                </div>
                <div class="ppms-mt">
                    <span class="ppms-muted" style="font-size: 0.85rem">{{ t('projects.timelineTitle') }}</span>
                    <div class="ppms-stack ppms-mt-sm">
                        <label v-for="ph in TIMELINE_PHASES" :key="ph" class="ppms-field">
                            <span>{{ t(`projects.phase.${ph}`) }}</span>
                            <input v-model="metaForm.timelineDates[ph]" type="date" />
                        </label>
                    </div>
                </div>
                <p v-if="metaErr" class="ppms-error">{{ metaErr }}</p>
                <div class="ppms-modal-actions">
                    <button type="button" class="ppms-btn-ghost" @click="$emit('update:modelValue', false)">
                        {{ t('common.cancel') }}
                    </button>
                    <button type="submit" class="ppms-btn-primary">{{ t('common.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { TIMELINE_PHASES } from '../../constants/projectDetail';

const { t } = useI18n();

defineProps({
    modelValue: { type: Boolean, required: true },
    metaForm: { type: Object, required: true },
    metaErr: { type: String, default: '' },
});

defineEmits(['update:modelValue', 'save', 'add-supplier', 'remove-supplier']);
</script>
