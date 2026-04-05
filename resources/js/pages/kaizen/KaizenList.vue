<template>
    <div class="ppms-page">
        <section v-if="badges.length" class="ppms-card">
            <h2>Huy hiệu của bạn</h2>
            <ul class="ppms-badge-list">
                <li v-for="b in badges" :key="b.badge">{{ b.label }} — {{ b.level }}</li>
            </ul>
        </section>

        <section v-if="compliance && canReview" class="ppms-card ppms-mt">
            <h2>Nhắc Kaizen (tuân thủ)</h2>
            <ul class="ppms-kv">
                <li><span>Đã gửi nhắc</span><strong>{{ compliance.reminders_sent }}</strong></li>
                <li><span>Đã hoàn thành (có Kaizen)</span><strong>{{ compliance.fulfilled }}</strong></li>
                <li><span>Tỷ lệ bỏ qua %</span><strong>{{ compliance.skip_rate_pct }}</strong></li>
            </ul>
        </section>

        <section class="ppms-card ppms-mt">
            <div class="ppms-leaderboard-head">
                <div>
                    <h2>Bảng xếp hạng (BR-KZ-05)</h2>
                    <p class="ppms-muted ppms-mt-sm">Tổng điểm Kaizen đã <strong>verified</strong> trong tháng (theo ngày tạo bản ghi).</p>
                </div>
                <label class="ppms-field ppms-field-inline">
                    <span>Tháng</span>
                    <input v-model="boardMonth" type="month" @change="loadLeaderboard" />
                </label>
            </div>
            <p v-if="leaderboardLoading" class="ppms-muted ppms-mt-sm">Đang tải bảng xếp hạng…</p>
            <template v-else>
                <p v-if="!board.length" class="ppms-muted ppms-mt-sm">Chưa có ai trong tháng {{ boardMonthResolved }}.</p>
                <div v-else class="ppms-table-scroll ppms-mt-sm">
                    <table class="ppms-table">
                        <thead>
                            <tr>
                                <th class="ppms-th-rank">#</th>
                                <th>Người</th>
                                <th>Tổng điểm</th>
                                <th>Số Kaizen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, i) in board"
                                :key="row.submitter_id"
                                :class="{ 'ppms-row-me': isMeRow(row) }"
                            >
                                <td class="ppms-th-rank">{{ i + 1 }}</td>
                                <td>{{ row.submitter?.name || '—' }}</td>
                                <td>{{ formatScore(row.total_score) }}</td>
                                <td>{{ row.kaizen_count }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </section>

        <section class="ppms-card ppms-mt">
            <h2>Gửi Kaizen tuần này</h2>
            <form class="ppms-stack" @submit.prevent="submit">
                <label class="ppms-field">
                    <span>Tuần bắt đầu *</span>
                    <input v-model="form.week_start" type="date" required />
                </label>
                <label class="ppms-field">
                    <span>Vấn đề *</span>
                    <textarea v-model="form.problem" required rows="2" />
                </label>
                <label class="ppms-field">
                    <span>Giải pháp *</span>
                    <textarea v-model="form.solution" required rows="2" />
                </label>
                <label class="ppms-field">
                    <span>Kết quả đo được *</span>
                    <input v-model="form.outcome_measurable" required placeholder="VD: Giảm 15% thời gian build" />
                </label>
                <label class="ppms-field">
                    <span>Giá trị ước tính (BR-KZ-06)</span>
                    <input v-model.number="form.estimated_value" type="number" min="0" step="0.01" placeholder="VND / giờ tiết kiệm…" />
                </label>
                <p v-if="err" class="ppms-error">{{ err }}</p>
                <button type="submit" class="ppms-btn-primary">Gửi</button>
            </form>
        </section>

        <section class="ppms-card ppms-mt">
            <h2>Danh sách</h2>
            <div v-if="loading" class="ppms-loading-line" role="status">Đang tải…</div>
            <div v-else class="ppms-table-scroll">
                <table class="ppms-table">
                    <thead>
                        <tr>
                            <th>Tuần</th>
                            <th>Người gửi</th>
                            <th>Vấn đề</th>
                            <th>Trạng thái</th>
                            <th v-if="canReview">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="k in items" :key="k.id">
                            <td>{{ k.week_start }}</td>
                            <td>{{ k.submitter?.name }}</td>
                            <td>{{ k.problem?.slice(0, 80) }}…</td>
                            <td>{{ k.status }}</td>
                            <td v-if="canReview">
                                <select v-if="k.status !== 'verified'" @change="onStatus(k, $event)">
                                    <option value="">Đổi trạng thái…</option>
                                    <option value="approved">approved</option>
                                    <option value="implemented">implemented</option>
                                    <option value="verified">verified</option>
                                </select>
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
import { ppmsConfirm, ppmsToastSuccess } from '@/ppmsUi';

const items = ref([]);
const loading = ref(true);
const err = ref('');
const me = ref(null);
const form = reactive({
    week_start: new Date().toISOString().slice(0, 10),
    problem: '',
    solution: '',
    outcome_measurable: '',
    estimated_value: null,
});

const board = ref([]);
const boardMonth = ref(new Date().toISOString().slice(0, 7));
const boardMonthResolved = ref(boardMonth.value);
const leaderboardLoading = ref(false);
const badges = ref([]);
const compliance = ref(null);

const canReview = computed(() => ['admin', 'pm', 'tl', 'hr'].includes(me.value?.role));

function formatScore(v) {
    const n = Number(v);
    return Number.isFinite(n) ? n.toFixed(2) : '—';
}

function isMeRow(row) {
    return me.value && Number(row.submitter_id) === Number(me.value.id);
}

async function loadLeaderboard() {
    leaderboardLoading.value = true;
    try {
        const { data } = await axios.get('/api/kaizens/leaderboard', {
            params: { month: boardMonth.value },
        });
        board.value = data.leaderboard || [];
        boardMonthResolved.value = data.month || boardMonth.value;
    } catch {
        board.value = [];
    } finally {
        leaderboardLoading.value = false;
    }
}

async function loadMe() {
    const { data } = await axios.get('/api/user');
    me.value = data;
}

async function loadList() {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/kaizens');
        items.value = data.data || data;
    } finally {
        loading.value = false;
    }
}

async function submit() {
    err.value = '';
    if (!(await ppmsConfirm('Gửi bản ghi Kaizen với nội dung đã nhập?'))) {
        return;
    }
    try {
        await axios.post('/api/kaizens', {
            ...form,
            estimated_value: form.estimated_value ?? null,
        });
        Object.assign(form, { problem: '', solution: '', outcome_measurable: '', estimated_value: null });
        ppmsToastSuccess('Đã gửi Kaizen.');
        await loadList();
        await loadLeaderboard();
    } catch (e) {
        err.value = formatApiUserMessage(e, 'Lỗi gửi Kaizen.');
    }
}

async function onStatus(k, ev) {
    const status = ev.target.value;
    if (!status) {
        return;
    }
    if (!(await ppmsConfirm(`Cập nhật Kaizen (tuần ${k.week_start}) sang trạng thái "${status}"?`))) {
        ev.target.value = '';
        return;
    }
    await axios.patch(`/api/kaizens/${k.id}/status`, { status, tl_rating: 4 });
    ev.target.value = '';
    await loadList();
    await loadLeaderboard();
}

onMounted(async () => {
    await loadMe();
    await loadList();
    await loadLeaderboard();
    try {
        const { data } = await axios.get('/api/kaizens/badges');
        badges.value = data.badges || [];
    } catch {
        badges.value = [];
    }
    if (canReview.value) {
        try {
            const { data } = await axios.get('/api/kaizens/reminder-compliance');
            compliance.value = data;
        } catch {
            compliance.value = null;
        }
    }
});
</script>
