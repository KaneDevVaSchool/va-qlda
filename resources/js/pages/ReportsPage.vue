<template>
    <div class="ppms-page">
        <section class="ppms-card">
            <ul class="ppms-report-actions">
                <li v-if="canPdf">
                    <button type="button" class="ppms-btn-primary" @click="dlPdf">Tải Weekly Status PDF</button>
                </li>
                <li v-if="canCsv">
                    <button type="button" class="ppms-btn-primary" @click="dlCsv">Export projects.csv</button>
                </li>
                <li v-if="canImpact">
                    <button type="button" class="ppms-btn-ghost" @click="loadImpact">Xem Kaizen impact (JSON)</button>
                </li>
            </ul>
            <pre v-if="impactJson" class="ppms-pre">{{ impactJson }}</pre>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

const me = ref(null);
const impactJson = ref('');

const canPdf = computed(() => ['admin', 'pm', 'tl'].includes(me.value?.role));
const canCsv = computed(() => ['admin', 'pm', 'hr'].includes(me.value?.role));
const canImpact = computed(() => ['admin', 'pm', 'tl', 'hr'].includes(me.value?.role));

onMounted(async () => {
    const { data } = await axios.get('/api/user');
    me.value = data;
});

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
