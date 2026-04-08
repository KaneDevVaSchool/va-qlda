<template>
    <div>
        <div v-if="loading" class="ppms-profile-skel" role="status">
            <div class="ppms-profile-skel-line" style="width: 50%" />
            <div class="ppms-profile-skel-line" style="width: 80%" />
        </div>
        <template v-else-if="err">{{ err }}</template>
        <template v-else>
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem">
                <button type="button" class="ppms-pf-btn ppms-pf-btn--danger" :disabled="revoking" @click="revokeOthers">
                    {{ t('profile.logoutOthers') }}
                </button>
            </div>
            <div v-if="!devices.length" class="ppms-profile-empty">{{ t('profile.empty') }}</div>
            <div v-else class="ppms-profile-table-wrap">
                <table class="ppms-profile-table">
                    <thead>
                        <tr>
                            <th>{{ t('profile.devicesCols.device') }}</th>
                            <th>{{ t('profile.devicesCols.browser') }}</th>
                            <th>{{ t('profile.devicesCols.status') }}</th>
                            <th>{{ t('profile.devicesCols.ip') }}</th>
                            <th>{{ t('profile.devicesCols.login') }}</th>
                            <th>{{ t('profile.devicesCols.activity') }}</th>
                            <th>{{ t('profile.devicesCols.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="d in devices"
                            :key="d.id"
                            :class="{ 'ppms-profile-device-current': d.is_current_device }"
                        >
                            <td>
                                {{ iconFor(d) }} {{ d.device_name }}
                                <span v-if="d.is_current_device" class="ppms-profile-badge-this">{{
                                    t('profile.thisDevice')
                                }}</span>
                            </td>
                            <td>{{ d.browser }} / {{ d.os }}</td>
                            <td>{{ statusLabel(d.status) }}</td>
                            <td>{{ d.ip_address }}</td>
                            <td>{{ formatTime(d.login_at) }}</td>
                            <td>{{ formatTime(d.last_activity) }}</td>
                            <td>
                                <button type="button" class="ppms-pf-btn ppms-pf-btn--danger" @click="revokeOne(d.id)">
                                    {{ t('profile.logoutDevice') }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '../../bootstrap';
import { ppmsConfirm } from '../../ppmsUi';
import { ppmsToastSuccess } from '../../ppmsUi';

const { t } = useI18n();

const loading = ref(true);
const err = ref('');
const devices = ref([]);
const revoking = ref(false);

function statusLabel(s) {
    const k = s === 'suspicious' ? 'suspicious' : s === 'online' ? 'online' : 'offline';
    return t('profile.statusDevice.' + k);
}

function iconFor(d) {
    const os = (d.os || '').toLowerCase();
    if (os.includes('ios')) {
        return '📱';
    }
    if (os.includes('android')) {
        return '🤖';
    }
    return '💻';
}

function formatTime(iso) {
    if (!iso) {
        return '—';
    }
    try {
        return new Date(iso).toLocaleString();
    } catch {
        return iso;
    }
}

async function load() {
    loading.value = true;
    err.value = '';
    try {
        const { data } = await axios.get('/api/me/sessions');
        devices.value = data.devices || [];
    } catch (e) {
        err.value = formatApiUserMessage(e, t('common.loading'));
    } finally {
        loading.value = false;
    }
}

onMounted(load);

async function revokeOne(id) {
    if (!(await ppmsConfirm('Revoke session?', { destructive: true }))) {
        return;
    }
    try {
        await axios.delete(`/api/me/sessions/${id}`);
        ppmsToastSuccess('OK');
        await load();
    } catch (e) {
        window.alert(formatApiUserMessage(e, 'Error'));
    }
}

async function revokeOthers() {
    if (!(await ppmsConfirm('Sign out all other devices?', { destructive: true }))) {
        return;
    }
    revoking.value = true;
    try {
        await axios.post('/api/me/sessions/revoke-others');
        ppmsToastSuccess('OK');
        await load();
    } catch (e) {
        window.alert(formatApiUserMessage(e, 'Error'));
    } finally {
        revoking.value = false;
    }
}
</script>
