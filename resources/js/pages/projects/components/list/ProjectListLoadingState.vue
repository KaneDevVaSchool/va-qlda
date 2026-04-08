<template>
    <div
        v-if="loading"
        class="ppms-project-list-loading ppms-mt"
        role="status"
        aria-live="polite"
        aria-busy="true"
    >
        <span class="ppms-sr-only">{{ t('common.loading') }}</span>
        <div v-if="viewMode === 'list'" class="ppms-skeleton-table" aria-hidden="true">
            <div v-for="n in 6" :key="'sk-' + n" class="ppms-skeleton-row">
                <div class="ppms-skeleton-cell ppms-skeleton-cell--lg" />
                <div class="ppms-skeleton-cell ppms-skeleton-cell--sm" />
                <div class="ppms-skeleton-cell ppms-skeleton-cell--md" />
                <div class="ppms-skeleton-cell ppms-skeleton-cell--sm" />
                <div class="ppms-skeleton-cell ppms-skeleton-cell--xs" />
            </div>
        </div>
        <div v-else class="ppms-pl-kanban-skeleton" aria-hidden="true">
            <div v-for="n in 6" :key="'ksk-' + n" class="ppms-pl-kanban-skeleton-col" />
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    loading: { type: Boolean, required: true },
    viewMode: { type: String, required: true },
});
</script>
