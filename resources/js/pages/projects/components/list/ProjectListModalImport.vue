<template>
    <div v-if="show" class="ppms-modal-backdrop" role="presentation" @click.self="$emit('close')">
        <div class="ppms-modal ppms-modal--import" role="dialog" aria-modal="true" aria-labelledby="ppms-modal-import-title">
            <h2 id="ppms-modal-import-title">{{ t('projects.importModalTitle') }}</h2>
            <p class="ppms-muted ppms-import-hint">{{ t('projects.importPreviewHint') }}</p>
            <div class="ppms-import-actions ppms-mt-sm">
                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="$emit('download-template', 'csv')">
                    {{ t('projects.importDownloadTemplateCsv') }}
                </button>
                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="$emit('download-template', 'json')">
                    {{ t('projects.importDownloadTemplateJson') }}
                </button>
                <label class="ppms-btn-ghost ppms-btn-sm ppms-import-file-label">
                    <input
                        ref="fileInputRef"
                        type="file"
                        accept=".csv,.txt,.json,text/csv,text/plain,application/json"
                        class="ppms-sr-only"
                        @change="$emit('file-change', $event)"
                    />
                    <span>{{ t('projects.importPickFile') }}</span>
                </label>
                <button
                    type="button"
                    class="ppms-btn-primary ppms-btn-sm"
                    :disabled="previewLoading || !fileChosen"
                    @click="$emit('preview')"
                >
                    {{ t('projects.importPreview') }}
                </button>
            </div>
            <p v-if="summary.total > 0" class="ppms-import-summary ppms-mt-sm">
                {{
                    t('projects.importSummary', {
                        valid: summary.valid,
                        invalid: summary.invalid,
                        total: summary.total,
                    })
                }}
            </p>
            <div v-if="previewRows.length" class="ppms-table-scroll ppms-import-preview-wrap ppms-mt-sm">
                <table class="ppms-table ppms-table--compact">
                    <thead>
                        <tr>
                            <th>{{ t('projects.importLine') }}</th>
                            <th>{{ t('projects.importStatus') }}</th>
                            <th>{{ t('projects.importAction') }}</th>
                            <th>{{ t('projects.colName') }}</th>
                            <th>{{ t('projects.importErrors') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(r, i) in previewRows" :key="'ir-' + i">
                            <td>{{ r.line }}</td>
                            <td>
                                <span
                                    :class="
                                        r.status === 'valid' ? 'ppms-import-badge ppms-import-badge--ok' : 'ppms-import-badge ppms-import-badge--err'
                                    "
                                >
                                    {{ r.status === 'valid' ? t('projects.importValid') : t('projects.importInvalid') }}
                                </span>
                            </td>
                            <td>{{ r.action || '—' }}</td>
                            <td>{{ r.name || '—' }}</td>
                            <td class="ppms-import-err-cell">{{ (r.errors || []).join('; ') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="ppms-modal-actions">
                <button type="button" class="ppms-btn-ghost" @click="$emit('close')">{{ t('common.cancel') }}</button>
                <button
                    type="button"
                    class="ppms-btn-primary"
                    :disabled="commitLoading || !previewId || summary.valid < 1"
                    @click="$emit('commit')"
                >
                    {{ t('projects.importCommit') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const show = defineModel({ type: Boolean, default: false });

defineProps({
    fileChosen: { type: Boolean, default: false },
    previewLoading: { type: Boolean, default: false },
    commitLoading: { type: Boolean, default: false },
    previewId: { type: [String, Number, null], default: null },
    summary: { type: Object, required: true },
    previewRows: { type: Array, required: true },
});

defineEmits(['close', 'download-template', 'file-change', 'preview', 'commit']);

const fileInputRef = ref(null);

defineExpose({ fileInputRef });
</script>
