<!-- eslint-disable vue/no-mutating-props -- labelsModal is parent reactive -->
<template>
    <div v-if="modelValue" class="ppms-modal-backdrop" role="presentation" @click.self="$emit('close')">
        <div
            class="ppms-modal ppms-modal--labels"
            role="dialog"
            aria-modal="true"
            aria-labelledby="ppms-modal-labels-title"
            @click.stop
        >
            <h2 id="ppms-modal-labels-title">{{ t('projects.labelsModalTitle') }}</h2>
            <section class="ppms-labels-modal-section">
                <h3 class="ppms-labels-modal-sub">{{ t('projects.labelsFilterSection') }}</h3>
                <p class="ppms-muted ppms-labels-modal-hint">{{ t('projects.labelsFilterHint') }}</p>
                <label class="ppms-field">
                    <span>{{ t('projects.colLabels') }}</span>
                    <input v-model="labelsModal.filterInput" type="text" list="ppms-pl-label-datalist" autocomplete="off" />
                </label>
                <div class="ppms-labels-modal-actions-row ppms-mt-sm">
                    <button type="button" class="ppms-btn-primary ppms-btn-sm" @click="$emit('apply-filter')">
                        {{ t('projects.labelsFilterApply') }}
                    </button>
                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="$emit('clear-filter')">
                        {{ t('projects.labelsFilterClear') }}
                    </button>
                </div>
            </section>
            <section v-if="canBulk" class="ppms-labels-modal-section ppms-mt">
                <h3 class="ppms-labels-modal-sub">{{ t('projects.labelsBulkSection') }}</h3>
                <label class="ppms-field">
                    <span>{{ t('projects.labelsBulkAddHint') }}</span>
                    <input
                        :value="labelsBulkAddText"
                        type="text"
                        autocomplete="off"
                        @input="$emit('update:labelsBulkAddText', $event.target.value)"
                    />
                </label>
                <button type="button" class="ppms-btn-primary ppms-btn-sm ppms-mt-sm" @click="$emit('bulk-add')">
                    {{ t('projects.labelsBulkApplyAdd') }}
                </button>
                <label class="ppms-field ppms-mt">
                    <span>{{ t('projects.labelsBulkRemoveHint') }}</span>
                    <input
                        :value="labelsBulkRemoveText"
                        type="text"
                        autocomplete="off"
                        @input="$emit('update:labelsBulkRemoveText', $event.target.value)"
                    />
                </label>
                <button type="button" class="ppms-btn-ghost ppms-btn-sm ppms-mt-sm" @click="$emit('bulk-remove')">
                    {{ t('projects.labelsBulkApplyRemove') }}
                </button>
            </section>
            <div class="ppms-modal-actions">
                <button type="button" class="ppms-btn-ghost" @click="$emit('close')">{{ t('common.close') }}</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    modelValue: { type: Boolean, required: true },
    labelsModal: { type: Object, required: true },
    labelsBulkAddText: { type: String, required: true },
    labelsBulkRemoveText: { type: String, required: true },
    canBulk: { type: Boolean, default: false },
});

defineEmits(['close', 'apply-filter', 'clear-filter', 'bulk-add', 'bulk-remove', 'update:labelsBulkAddText', 'update:labelsBulkRemoveText']);
</script>
