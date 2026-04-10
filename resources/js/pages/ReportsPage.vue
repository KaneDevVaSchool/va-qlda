<template>
    <div class="ppms-page ppms-reports-page">
        <section
            v-if="canPdf"
            class="ppms-card ppms-reports-section"
            :aria-labelledby="'reports-pdf-' + uid"
        >
            <h2 :id="'reports-pdf-' + uid" class="ppms-reports-section-title">{{ t('reportsPage.sectionPdfTitle') }}</h2>
            <p class="ppms-reports-section-lead">{{ t('reportsPage.sectionPdfLead') }}</p>
            <button type="button" class="ppms-btn-primary" @click="dlPdf">{{ t('reportsPage.btnPdf') }}</button>
        </section>

        <section
            v-if="canCsv"
            class="ppms-card ppms-reports-section"
            :aria-labelledby="'reports-csv-' + uid"
        >
            <h2 :id="'reports-csv-' + uid" class="ppms-reports-section-title">{{ t('reportsPage.sectionCsvTitle') }}</h2>
            <p class="ppms-reports-section-lead">{{ t('reportsPage.sectionCsvLead') }}</p>
            <button type="button" class="ppms-btn-primary" @click="dlCsv">{{ t('reportsPage.btnCsv') }}</button>
        </section>

        <section
            v-if="canImpact"
            class="ppms-card ppms-reports-section"
            :aria-labelledby="'reports-impact-' + uid"
        >
            <h2 :id="'reports-impact-' + uid" class="ppms-reports-section-title">{{ t('reportsPage.sectionImpactTitle') }}</h2>
            <p class="ppms-reports-section-lead">{{ t('reportsPage.sectionImpactLead') }}</p>
            <button type="button" class="ppms-btn-ghost" @click="loadImpact">{{ t('reportsPage.btnImpact') }}</button>
            <pre v-if="impactJson" class="ppms-pre ppms-reports-impact-pre">{{ impactJson }}</pre>
        </section>

        <p v-if="!canPdf && !canCsv && !canImpact" class="ppms-reports-empty">{{ t('reportsPage.noActions') }}</p>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '../composables/usePermissions';

const { t } = useI18n();
const { can } = usePermissions();
const uid = `r${Math.random().toString(36).slice(2, 9)}`;
const impactJson = ref('');

const canPdf = computed(() => can('reports.create'));
const canCsv = computed(() => can('reports.update'));
const canImpact = computed(() => can('reports.create') || can('reports.update'));

async function dlPdf() {
    const res = await axios.get('/api/reports/weekly-status.pdf', { responseType: 'blob' });
    const url = URL.createObjectURL(res.data);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'ppms-weekly.pdf';
    a.click();
    URL.revokeObjectURL(url);
}

async function dlCsv() {
    const res = await axios.get('/api/reports/export/projects.csv', { responseType: 'blob' });
    const url = URL.createObjectURL(res.data);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'projects.csv';
    a.click();
    URL.revokeObjectURL(url);
}

async function loadImpact() {
    const { data } = await axios.get('/api/reports/kaizen-impact');
    impactJson.value = JSON.stringify(data, null, 2);
}
</script>

<style scoped>
.ppms-reports-page {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.ppms-reports-section {
    padding: 1.25rem 1.35rem;
}

.ppms-reports-section-title {
    margin: 0 0 0.35rem;
    font-size: 1.1rem;
    font-weight: 700;
}

.ppms-reports-section-lead {
    margin: 0 0 1rem;
    font-size: 0.95rem;
    color: var(--ppms-text-muted, #64748b);
    line-height: 1.45;
}

.ppms-reports-impact-pre {
    margin-top: 1rem;
    max-height: 50vh;
    overflow: auto;
}

.ppms-reports-empty {
    margin: 0;
    padding: 1rem;
    color: var(--ppms-text-muted, #64748b);
    font-size: 0.95rem;
}
</style>
