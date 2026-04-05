<template>
    <div class="ppms-page">
        <div v-if="canRun" class="ppms-page-toolbar">
            <button type="button" class="ppms-btn-primary" @click="runSnap">Chạy snapshot tuần</button>
        </div>
        <div v-if="loading" class="ppms-loading-line" role="status">Đang tải…</div>
        <template v-else>
            <section class="ppms-card">
                <h2>Hiện tại (real-time)</h2>
                <ul class="ppms-kv">
                    <li><span>Performance %</span><strong>{{ cur.person?.performance_pct?.toFixed?.(2) ?? cur.person?.performance_pct }}</strong></li>
                    <li><span>Percentile (team)</span><strong>{{ cur.person?.performance_percentile ?? '—' }}</strong></li>
                    <li><span>Efficiency</span><strong>{{ cur.person?.efficiency_ratio ?? '—' }}</strong></li>
                    <li><span>Team avg performance</span><strong>{{ cur.person?.benchmark_team_avg_performance?.toFixed?.(2) }}</strong></li>
                    <li><span>SLA maintenance %</span><strong>{{ cur.person?.sla_rate_pct ?? '—' }}</strong></li>
                </ul>
                <p v-if="cur.person?.efficiency_warn" class="ppms-error">Efficiency ngoài vùng 0.7–1.5</p>
                <p v-if="cur.person?.sla_critical" class="ppms-error">SLA Type 1 &lt; 80%</p>
            </section>
            <section v-if="bench" class="ppms-card ppms-mt">
                <h2>Benchmark team (BR-KPI-07)</h2>
                <ul class="ppms-kv ppms-mt-sm">
                    <li><span>Bạn (performance %)</span><strong>{{ bench.your_performance_pct?.toFixed?.(2) ?? bench.your_performance_pct }}</strong></li>
                    <li><span>Percentile</span><strong>{{ bench.your_percentile ?? '—' }}</strong></li>
                    <li><span>Trung bình team</span><strong>{{ bench.team_avg?.toFixed?.(2) ?? bench.team_avg }}</strong></li>
                </ul>
                <h3 class="ppms-mt-sm">Histogram performance</h3>
                <div class="ppms-hist">
                    <div v-for="row in histRows" :key="row.label" class="ppms-hist-row">
                        <span class="ppms-hist-label">{{ row.label }}</span>
                        <div class="ppms-hist-track">
                            <div class="ppms-hist-bar" :style="{ width: `${(row.count / histMax) * 100}%` }" />
                        </div>
                        <span class="ppms-hist-count">{{ row.count }}</span>
                    </div>
                </div>
            </section>
            <section class="ppms-card ppms-mt">
                <h2>Snapshot gần đây</h2>
                <div class="ppms-table-scroll">
                    <table class="ppms-table">
                        <thead>
                            <tr>
                                <th>Tuần</th>
                                <th>Entity</th>
                                <th>Metric</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in snaps" :key="s.id">
                                <td>{{ s.week_ending }}</td>
                                <td>{{ s.entity_type }} #{{ s.entity_id }}</td>
                                <td>{{ s.metric_name }}</td>
                                <td>{{ s.value }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { ppmsConfirm, ppmsToastSuccess } from '@/ppmsUi';

const loading = ref(true);
const cur = reactive({ person: {}, innovation_funnel: {} });
const snaps = ref([]);
const me = ref(null);
const bench = ref(null);

const histRows = computed(() => {
    const h = bench.value?.histogram;
    if (!h || typeof h !== 'object') {
        return [];
    }
    return Object.entries(h).map(([label, count]) => ({ label, count: Number(count) || 0 }));
});

const histMax = computed(() => Math.max(...histRows.value.map((r) => r.count), 1));

const canRun = computed(() => ['admin', 'pm'].includes(me.value?.role));

async function load() {
    loading.value = true;
    try {
        const [u, k, s, b] = await Promise.all([
            axios.get('/api/user'),
            axios.get('/api/kpi/current'),
            axios.get('/api/kpi/snapshots'),
            axios.get('/api/kpi/benchmark'),
        ]);
        me.value = u.data;
        Object.assign(cur, k.data);
        bench.value = b.data;
        const arr = s.data.data ?? s.data;
        snaps.value = Array.isArray(arr) ? arr.slice(0, 40) : [];
    } finally {
        loading.value = false;
    }
}

async function runSnap() {
    if (!(await ppmsConfirm('Chạy snapshot KPI tuần hiện tại? Thao tác ghi dữ liệu vào hệ thống.'))) {
        return;
    }
    await axios.post('/api/kpi/snapshot-run');
    ppmsToastSuccess('Đã chạy snapshot KPI.');
    await load();
}

onMounted(load);
</script>
