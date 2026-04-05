<template>
    <div class="ppms-page">
        <section v-if="canEdit" class="ppms-card">
            <h2>Tạo kỳ đánh giá</h2>
            <form class="ppms-stack" @submit.prevent="createEval">
                <div class="ppms-task-form">
                    <select v-model="form.period_type" required>
                        <option value="quarterly">quarterly</option>
                        <option value="annual">annual</option>
                    </select>
                    <input v-model="form.period_label" placeholder="VD: 2026-Q1" required />
                    <input v-model.number="form.person_id" type="number" placeholder="Person user id *" required />
                    <input v-model.number="form.p1" type="number" step="0.1" min="0" max="100" placeholder="P1" />
                    <input v-model.number="form.p2" type="number" step="0.1" min="0" max="100" placeholder="P2" />
                    <input v-model.number="form.p3" type="number" step="0.1" min="0" max="100" placeholder="P3" />
                </div>
                <p v-if="err" class="ppms-error">{{ err }}</p>
                <button type="submit" class="ppms-btn-primary">Lưu nháp / tính điểm</button>
            </form>
        </section>

        <section v-if="canEdit" class="ppms-card ppms-mt">
            <h2>Peer review (BR-3P-03)</h2>
            <form class="ppms-task-form" @submit.prevent="addPeer">
                <input v-model.number="peerForm.evaluation_id" type="number" placeholder="Evaluation id" required />
                <input v-model.number="peerForm.reviewer_id" type="number" placeholder="Reviewer user id" required />
                <input v-model.number="peerForm.attitude_score" type="number" step="0.1" min="0" max="100" placeholder="Attitude 0-100" />
                <button type="submit" class="ppms-btn-primary">Thêm peer</button>
            </form>
            <p v-if="peerErr" class="ppms-error">{{ peerErr }}</p>
        </section>

        <section class="ppms-card ppms-mt">
            <h2>Danh sách</h2>
            <div v-if="loading" class="ppms-loading-line" role="status">Đang tải…</div>
            <div v-else class="ppms-table-scroll">
                <table class="ppms-table">
                    <thead>
                        <tr>
                            <th>Kỳ</th>
                            <th>Nhân sự</th>
                            <th>P1 / P2 / P3</th>
                            <th>Tổng</th>
                            <th>Grade</th>
                            <th>Trạng thái</th>
                            <th>PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in items" :key="e.id">
                            <td>{{ e.period_type }} {{ e.period_label }}</td>
                            <td>{{ e.person?.name }}</td>
                            <td>{{ e.p1 }} / {{ e.p2 }} / {{ e.p3 }}</td>
                            <td>{{ e.total }}</td>
                            <td>{{ e.grade }}</td>
                            <td>{{ e.status }}</td>
                            <td>
                                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="downloadPdf(e)">Tải PDF</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { formatApiUserMessage } from '@/bootstrap';
import { ppmsConfirm, ppmsToastError, ppmsToastSuccess } from '@/ppmsUi';

const items = ref([]);
const loading = ref(true);
const err = ref('');
const peerErr = ref('');
const me = ref(null);
const peerForm = reactive({
    evaluation_id: null,
    reviewer_id: null,
    attitude_score: 75,
});
const form = reactive({
    period_type: 'quarterly',
    period_label: '2026-Q2',
    person_id: null,
    p1: 80,
    p2: 75,
    p3: 70,
});

const canEdit = computed(() => ['admin', 'pm', 'tl', 'hr'].includes(me.value?.role));

async function load() {
    loading.value = true;
    try {
        const [u, list] = await Promise.all([axios.get('/api/user'), axios.get('/api/evaluations')]);
        me.value = u.data;
        items.value = list.data.data || list.data;
    } finally {
        loading.value = false;
    }
}

async function createEval() {
    err.value = '';
    if (!(await ppmsConfirm('Tạo / cập nhật kỳ đánh giá với dữ liệu đã nhập?'))) {
        return;
    }
    try {
        await axios.post('/api/evaluations', {
            period_type: form.period_type,
            period_label: form.period_label,
            person_id: form.person_id,
            p1: form.p1,
            p2: form.p2,
            p3: form.p3,
        });
        ppmsToastSuccess('Đã lưu kỳ đánh giá.');
        await load();
    } catch (e) {
        err.value = formatApiUserMessage(e, 'Không tạo được đánh giá.');
    }
}

async function downloadPdf(e) {
    try {
        const res = await axios.get(`/api/evaluations/${e.id}/export-pdf`, { responseType: 'blob' });
        const url = URL.createObjectURL(res.data);
        const el = document.createElement('a');
        el.href = url;
        el.download = `ppms-3p-evaluation-${e.id}.pdf`;
        el.click();
        URL.revokeObjectURL(url);
    } catch (err) {
        ppmsToastError(formatApiUserMessage(err, 'Không tải được PDF.'));
    }
}

async function addPeer() {
    peerErr.value = '';
    if (!(await ppmsConfirm('Thêm peer review với thông tin đã nhập?'))) {
        return;
    }
    try {
        await axios.post(`/api/evaluations/${peerForm.evaluation_id}/peers`, {
            reviewer_id: peerForm.reviewer_id,
            attitude_score: peerForm.attitude_score,
        });
        ppmsToastSuccess('Đã thêm peer review.');
        await load();
    } catch (e) {
        peerErr.value = formatApiUserMessage(e, 'Không thêm được peer.');
    }
}

onMounted(load);
</script>
