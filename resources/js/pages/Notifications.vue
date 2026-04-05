<template>
    <div class="ppms-page">
        <div class="ppms-page-toolbar ppms-page-toolbar--end">
            <button type="button" class="ppms-btn-ghost" @click="markAll">Đánh dấu đã đọc hết</button>
        </div>
        <div v-if="loading" class="ppms-loading-line" role="status">Đang tải…</div>
        <ul v-else class="ppms-notify-list">
            <li v-for="n in items" :key="n.id" :class="{ unread: !n.read_at }">
                <div>
                    <strong>{{ n.payload?.title || n.type }}</strong>
                    <p>{{ n.payload?.body }}</p>
                    <span class="ppms-muted">{{ n.created_at }}</span>
                </div>
                <button v-if="!n.read_at" type="button" class="ppms-btn-ghost ppms-btn-sm" @click="markOne(n)">Đọc</button>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';
import { ppmsConfirm, ppmsToastSuccess } from '@/ppmsUi';

const items = ref([]);
const loading = ref(true);

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/notifications');
        items.value = data.data || data;
    } finally {
        loading.value = false;
    }
}

async function markOne(n) {
    if (!(await ppmsConfirm('Đánh dấu thông báo này là đã đọc?'))) {
        return;
    }
    await axios.patch(`/api/notifications/${n.id}/read`);
    ppmsToastSuccess('Đã đánh dấu đã đọc.');
    await load();
}

async function markAll() {
    if (!(await ppmsConfirm('Đánh dấu tất cả thông báo là đã đọc?'))) {
        return;
    }
    await axios.post('/api/notifications/read-all');
    ppmsToastSuccess('Đã đánh dấu tất cả đã đọc.');
    await load();
}

onMounted(load);
</script>
