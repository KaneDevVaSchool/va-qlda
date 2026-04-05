<template>
    <div class="ppms-page">
        <div class="ppms-page-toolbar">
            <button type="button" class="ppms-btn-primary" @click="showForm = true">+ Dự án mới</button>
        </div>

        <div v-if="loading" class="ppms-loading-line" role="status">Đang tải…</div>
        <div v-else class="ppms-table-scroll">
            <table class="ppms-table">
                <thead>
                    <tr>
                        <th>Tên</th>
                        <th>Loại</th>
                        <th>Phase</th>
                        <th>Trạng thái</th>
                        <th>Tiến độ</th>
                        <th>Owner</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in projects" :key="p.id">
                        <td>
                            <router-link :to="'/projects/' + p.id">{{ p.name }}</router-link>
                        </td>
                        <td>{{ p.type }}</td>
                        <td>{{ p.phase }}</td>
                        <td>{{ p.status }}</td>
                        <td>{{ Number(p.progress).toFixed(1) }}%</td>
                        <td>{{ p.owner?.name }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showForm" class="ppms-modal-backdrop" @click.self="showForm = false">
            <div class="ppms-modal">
                <h2>Dự án mới</h2>
                <form @submit.prevent="createProject">
                    <label class="ppms-field">
                        <span>Tên *</span>
                        <input v-model="form.name" required />
                    </label>
                    <label class="ppms-field">
                        <span>Loại *</span>
                        <select v-model="form.type" required>
                            <option value="maintenance">maintenance</option>
                            <option value="delivery">delivery</option>
                            <option value="rnd">rnd</option>
                        </select>
                    </label>
                    <label class="ppms-field">
                        <span>Owner (user id) *</span>
                        <input v-model.number="form.owner_id" type="number" required />
                    </label>
                    <label class="ppms-field">
                        <span>Deadline</span>
                        <input v-model="form.deadline" type="date" />
                    </label>
                    <label class="ppms-field">
                        <span>Mô tả</span>
                        <textarea v-model="form.description" rows="3" />
                    </label>
                    <p v-if="formError" class="ppms-error">{{ formError }}</p>
                    <div class="ppms-modal-actions">
                        <button type="button" class="ppms-btn-ghost" @click="showForm = false">Hủy</button>
                        <button type="submit" class="ppms-btn-primary">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { formatApiUserMessage } from '@/bootstrap';
import { ppmsConfirm, ppmsToastSuccess } from '@/ppmsUi';

const projects = ref([]);
const loading = ref(true);
const showForm = ref(false);
const formError = ref('');
const form = reactive({
    name: '',
    type: 'delivery',
    owner_id: null,
    deadline: '',
    description: '',
});

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/projects');
        projects.value = data.data || data;
    } finally {
        loading.value = false;
    }
}

async function createProject() {
    formError.value = '';
    if (!(await ppmsConfirm('Tạo dự án mới với thông tin đã nhập?'))) {
        return;
    }
    try {
        await axios.post('/api/projects', {
            ...form,
            deadline: form.deadline || null,
        });
        showForm.value = false;
        ppmsToastSuccess('Đã tạo dự án.');
        await load();
    } catch (e) {
        formError.value = formatApiUserMessage(e, 'Lỗi tạo dự án.');
    }
}

onMounted(load);
</script>
