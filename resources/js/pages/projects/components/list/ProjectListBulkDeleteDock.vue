<template>
    <aside
        v-if="canBulkDelete && selectedCount > 0 && !loading"
        class="ppms-card ppms-bulk-dock ppms-bulk-dock--full ppms-mt ppms-bulk-dock-panel ppms-bulk-dock-panel--danger"
        role="region"
        :aria-label="t('projects.bulkBarTitle', { n: selectedCount })"
    >
        <div class="ppms-bulk-dock-head ppms-bulk-dock-head--danger">
            <h3 class="ppms-bulk-dock-title">{{ t('projects.bulkBarTitle', { n: selectedCount }) }}</h3>
            <button type="button" class="ppms-linklike ppms-bulk-dock-clear" @click="$emit('clear')">
                {{ t('projects.bulkClearSelection') }}
            </button>
        </div>
        <div class="ppms-bulk-danger-row">
            <div class="ppms-bulk-danger-icon" aria-hidden="true">
                <svg class="ppms-bulk-danger-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6" />
                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" />
                    <line x1="10" y1="11" x2="10" y2="17" />
                    <line x1="14" y1="11" x2="14" y2="17" />
                </svg>
            </div>
            <div class="ppms-bulk-danger-copy">
                <h4 class="ppms-bulk-danger-title">{{ t('projects.bulkDeleteTitle') }}</h4>
                <p class="ppms-bulk-danger-text">{{ t('projects.bulkDeleteHint', { n: selectedCount }) }}</p>
            </div>
            <div class="ppms-bulk-danger-actions">
                <button type="button" class="ppms-btn-danger-outline ppms-bulk-danger-btn" @click="$emit('bulk-delete')">
                    {{ t('projects.bulkDeleteBtn') }}
                </button>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    canBulkDelete: { type: Boolean, default: false },
    selectedCount: { type: Number, required: true },
    loading: { type: Boolean, default: false },
});

defineEmits(['clear', 'bulk-delete']);
</script>
