<template>
    <div class="ppms-page">
        <section class="ppms-card">
            <form class="ppms-stack" @submit.prevent="create">
                <input v-model="form.title" placeholder="Tiêu đề" required />
                <textarea v-model="form.description" rows="2" placeholder="Mô tả" />
                <input v-model.number="form.project_id" type="number" placeholder="Project id (tuỳ chọn)" />
                <button type="submit" class="ppms-btn-primary">Gửi ý tưởng</button>
            </form>
        </section>
        <section class="ppms-card ppms-mt">
            <h2>Danh sách</h2>
            <div v-if="loading" class="ppms-loading-line" role="status">Đang tải…</div>
            <div v-else class="ppms-table-scroll">
                <table class="ppms-table">
                    <thead>
                        <tr>
                            <th>Tiêu đề</th>
                            <th>Trạng thái</th>
                            <th>Người gửi</th>
                            <th v-if="canReview"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="i in items" :key="i.id">
                            <td>{{ i.title }}</td>
                            <td>{{ i.status }}</td>
                            <td>{{ i.submitter?.name }}</td>
                            <td v-if="canReview">
                                <select @change="setStatus(i, $event)">
                                    <option value="">Đổi…</option>
                                    <option value="submitted">submitted</option>
                                    <option value="poc">poc</option>
                                    <option value="applied">applied</option>
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
import { ppmsConfirm, ppmsToastSuccess } from '@/ppmsUi';

const items = ref([]);
const loading = ref(true);
const me = ref(null);
const form = reactive({ title: '', description: '', project_id: null });

const canReview = computed(() => ['admin', 'pm', 'tl'].includes(me.value?.role));

async function load() {
    loading.value = true;
    try {
        const [u, list] = await Promise.all([axios.get('/api/user'), axios.get('/api/innovation-ideas')]);
        me.value = u.data;
        items.value = list.data.data || list.data;
    } finally {
        loading.value = false;
    }
}

async function create() {
    if (!(await ppmsConfirm('Gửi ý tưởng đổi mới với nội dung đã nhập?'))) {
        return;
    }
    await axios.post('/api/innovation-ideas', { ...form, project_id: form.project_id || null });
    form.title = '';
    form.description = '';
    form.project_id = null;
    ppmsToastSuccess('Đã gửi ý tưởng.');
    await load();
}

async function setStatus(i, ev) {
    const st = ev.target.value;
    if (!st) {
        return;
    }
    if (!(await ppmsConfirm(`Đổi trạng thái ý tưởng "${i.title}" sang "${st}"?`))) {
        ev.target.value = '';
        return;
    }
    await axios.patch(`/api/innovation-ideas/${i.id}/status`, { status: st });
    ev.target.value = '';
    await load();
}

onMounted(load);
</script>
