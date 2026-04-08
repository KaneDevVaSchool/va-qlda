<template>
    <div class="ppms-pd-tab-panel ppms-pd-finance-tab">
        <PpmsPdEmptyState
            v-if="!hasFinanceData"
            :title="t('projects.pdEmptyFinanceTitle')"
            :description="t('projects.pdEmptyFinanceDesc')"
            heading-id="pd-finance-empty-h"
        >
            <button type="button" class="ppms-btn-primary ppms-btn-sm" @click="$emit('open-meta')">
                {{ t('projects.pdEmptyFinanceCta') }}
            </button>
        </PpmsPdEmptyState>
        <section v-else class="ppms-card ppms-pd-section" aria-labelledby="pd-finance-title">
            <h2 id="pd-finance-title" class="ppms-pd-report-section-title">{{ t('projects.pdReportFinanceTitle') }}</h2>
            <div class="ppms-pd-report-finance-grid ppms-mt-sm">
                <div class="ppms-pd-report-finance-card">
                    <span class="ppms-muted">{{ t('projects.createFieldEstimatedValue') }}</span>
                    <strong class="ppms-pd-report-finance-value">{{ financeEstimated }}</strong>
                </div>
                <div class="ppms-pd-report-finance-card">
                    <span class="ppms-muted">{{ t('projects.pdReportFinanceTotalEstHours') }}</span>
                    <strong class="ppms-pd-report-finance-value">{{ financeEstHours }}</strong>
                </div>
                <div class="ppms-pd-report-finance-card">
                    <span class="ppms-muted">{{ t('projects.pdReportFinanceTotalActHours') }}</span>
                    <strong class="ppms-pd-report-finance-value">{{ financeActHours }}</strong>
                </div>
            </div>
            <p class="ppms-muted ppms-mt-sm">{{ t('projects.pdReportFinanceFootnote') }}</p>
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import PpmsPdEmptyState from './PpmsPdEmptyState.vue';

const { t } = useI18n();

defineEmits(['open-meta']);

const props = defineProps({
    project: { type: Object, required: true },
});

const hasFinanceData = computed(() => {
    const p = props.project;
    if (p.estimated_value != null && String(p.estimated_value).trim() !== '') {
        return true;
    }
    for (const tk of p.tasks || []) {
        if ((Number(tk.estimate_hours) || 0) > 0 || (Number(tk.actual_hours) || 0) > 0) {
            return true;
        }
    }

    return false;
});

const financeEstimated = computed(() => {
    const v = props.project.estimated_value;
    if (v == null || v === '') {
        return '—';
    }
    const n = Number(v);
    if (Number.isNaN(n)) {
        return String(v);
    }
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(n);
});

const financeEstHours = computed(() => {
    let s = 0;
    for (const tk of props.project.tasks || []) {
        s += Number(tk.estimate_hours) || 0;
    }
    return s.toFixed(1);
});

const financeActHours = computed(() => {
    let s = 0;
    for (const tk of props.project.tasks || []) {
        s += Number(tk.actual_hours) || 0;
    }
    return s.toFixed(1);
});
</script>
