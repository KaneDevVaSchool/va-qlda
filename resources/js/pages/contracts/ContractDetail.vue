<template>
    <div class="ppms-page">
        <div v-if="loading" class="ppms-loading-line" role="status">{{ t('common.loading') }}</div>
        <template v-else-if="contract">
            <header class="ppms-contract-header">
                <div>
                    <router-link to="/contracts" class="ppms-back">{{ t('common.back') }}</router-link>
                    <h1 class="ppms-contract-title">{{ contract.code }}</h1>
                    <p class="ppms-contract-meta">
                        <span class="ppms-pill">{{ contract.status }}</span>
                        <span v-if="contract.vendor">{{ contract.vendor.name }}</span>
                    </p>
                </div>
                <div class="ppms-contract-actions">
                    <template v-if="contract.status === 'draft' && canEdit">
                        <button type="button" class="ppms-btn-ghost" @click="openEdit">{{ t('common.edit') }}</button>
                        <button type="button" class="ppms-btn-ghost ppms-btn-danger" @click="removeDraft">{{ t('common.delete') }}</button>
                    </template>
                    <template v-if="contract.status === 'draft' && canSubmit">
                        <button type="button" class="ppms-btn-primary" @click="submitOpen = true">{{ t('contracts.submitApproval') }}</button>
                    </template>
                    <template v-if="contract.status === 'pending_approval' && isCurrentApprover">
                        <button type="button" class="ppms-btn-primary" @click="doApprove">{{ t('contracts.approve') }}</button>
                        <button type="button" class="ppms-btn-ghost" @click="doReject">{{ t('contracts.reject') }}</button>
                    </template>
                    <template v-if="contract.status === 'active' && canTerminate">
                        <button type="button" class="ppms-btn-ghost ppms-btn-danger" @click="doTerminate">{{ t('contracts.terminate') }}</button>
                    </template>
                </div>
            </header>

            <div class="ppms-tabs" role="tablist">
                <button
                    type="button"
                    class="ppms-tab"
                    :class="{ 'ppms-tab--active': tab === 'info' }"
                    @click="tab = 'info'"
                >
                    {{ t('contracts.tabInfo') }}
                </button>
                <button
                    type="button"
                    class="ppms-tab"
                    :class="{ 'ppms-tab--active': tab === 'payments' }"
                    @click="tab = 'payments'"
                >
                    {{ t('contracts.tabPayments') }}
                </button>
                <button
                    type="button"
                    class="ppms-tab"
                    :class="{ 'ppms-tab--active': tab === 'files' }"
                    @click="tab = 'files'"
                >
                    {{ t('contracts.tabFiles') }}
                </button>
                <button
                    type="button"
                    class="ppms-tab"
                    :class="{ 'ppms-tab--active': tab === 'approvals' }"
                    @click="tab = 'approvals'"
                >
                    {{ t('contracts.tabApprovals') }}
                </button>
                <button
                    type="button"
                    class="ppms-tab"
                    :class="{ 'ppms-tab--active': tab === 'logs' }"
                    @click="tab = 'logs'"
                >
                    {{ t('contracts.tabLogs') }}
                </button>
            </div>

            <section v-show="tab === 'info'" class="ppms-card ppms-mt">
                <dl class="ppms-dl">
                    <div><dt>{{ t('contracts.fieldScope') }}</dt><dd>{{ contract.scope || '—' }}</dd></div>
                    <div><dt>{{ t('contracts.fieldStart') }}</dt><dd>{{ contract.start_date }}</dd></div>
                    <div><dt>{{ t('contracts.fieldEnd') }}</dt><dd>{{ contract.end_date }}</dd></div>
                    <div><dt>{{ t('contracts.fieldValue') }}</dt><dd>{{ contract.total_value }}</dd></div>
                    <div><dt>{{ t('contracts.fieldCycle') }}</dt><dd>{{ contract.payment_cycle }}</dd></div>
                    <div><dt>{{ t('contracts.fieldProduct') }}</dt><dd>{{ contract.product?.name || '—' }}</dd></div>
                    <div><dt>{{ t('contracts.fieldDepartment') }}</dt><dd>{{ contract.department?.name || '—' }}</dd></div>
                </dl>
            </section>

            <section v-show="tab === 'payments'" class="ppms-card ppms-mt">
                <div class="ppms-table-scroll">
                    <table class="ppms-table">
                        <thead>
                            <tr>
                                <th>{{ t('contracts.paymentsDue') }}</th>
                                <th>{{ t('contracts.paymentsAmount') }}</th>
                                <th>{{ t('contracts.paymentsStatus') }}</th>
                                <th v-if="canMarkPaid" />
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in contract.payments || []" :key="p.id">
                                <td>{{ p.due_date }}</td>
                                <td>{{ p.amount }}</td>
                                <td>{{ p.status }}</td>
                                <td v-if="canMarkPaid">
                                    <button
                                        v-if="p.status === 'pending' || p.status === 'overdue'"
                                        type="button"
                                        class="ppms-btn-ghost ppms-btn-sm"
                                        @click="markPaid(p.id)"
                                    >
                                        {{ t('contracts.markPaid') }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-show="tab === 'files'" class="ppms-card ppms-mt">
                <div v-if="contract.status === 'draft' && canEdit" class="ppms-file-upload ppms-mb">
                    <label class="ppms-btn-ghost ppms-btn-sm" style="cursor: pointer">
                        {{ t('contracts.upload') }}
                        <input ref="fileInput" type="file" class="ppms-sr-file" @change="onFilePick" />
                    </label>
                    <label class="ppms-checkbox">
                        <input v-model="uploadAsVersion" type="checkbox" />
                        {{ t('contracts.uploadVersion') }}
                    </label>
                    <span v-if="uploading" class="ppms-hint">{{ t('common.loading') }}</span>
                </div>
                <div class="ppms-table-scroll">
                    <table class="ppms-table">
                        <thead>
                            <tr>
                                <th>{{ t('contracts.filesName') }}</th>
                                <th>{{ t('contracts.filesUploaded') }}</th>
                                <th />
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="f in contract.files || []" :key="f.id">
                                <td>{{ f.file_name }}</td>
                                <td>{{ f.created_at }}</td>
                                <td>
                                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="downloadFile(f.id)">
                                        {{ t('contracts.download') }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-show="tab === 'approvals'" class="ppms-card ppms-mt">
                <div class="ppms-table-scroll">
                    <table class="ppms-table">
                        <thead>
                            <tr>
                                <th>{{ t('contracts.approvalStep') }}</th>
                                <th>{{ t('contracts.approvalApprover') }}</th>
                                <th>{{ t('contracts.approvalState') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="a in contract.approvals || []" :key="a.id">
                                <td>{{ a.step }}</td>
                                <td>{{ a.approver?.name || a.approver_id }}</td>
                                <td>{{ a.status }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-show="tab === 'logs'" class="ppms-card ppms-mt">
                <div v-if="logsLoading" class="ppms-loading-line">{{ t('common.loading') }}</div>
                <div v-else class="ppms-table-scroll">
                    <table class="ppms-table">
                        <thead>
                            <tr>
                                <th>{{ t('contracts.logsTime') }}</th>
                                <th>{{ t('contracts.logsAction') }}</th>
                                <th>user</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in logs" :key="log.id">
                                <td>{{ log.created_at }}</td>
                                <td>{{ log.action }}</td>
                                <td>{{ log.user?.name || log.user_id || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </template>

        <div v-if="submitOpen" class="ppms-modal-backdrop" @click.self="submitOpen = false">
            <div class="ppms-modal ppms-card">
                <h2 class="ppms-modal-title">{{ t('contracts.submitApproval') }}</h2>
                <p class="ppms-hint">{{ t('contracts.submitHint') }}</p>
                <textarea v-model="approverIdsRaw" class="ppms-input" rows="3" placeholder="2, 5, 8" />
                <p v-if="submitErr" class="ppms-error">{{ submitErr }}</p>
                <div class="ppms-modal-actions">
                    <button type="button" class="ppms-btn-ghost" @click="submitOpen = false">{{ t('common.cancel') }}</button>
                    <button type="button" class="ppms-btn-primary" @click="submitApproval">{{ t('common.send') }}</button>
                </div>
            </div>
        </div>

        <div v-if="editOpen" class="ppms-modal-backdrop" @click.self="editOpen = false">
            <div class="ppms-modal ppms-card">
                <h2 class="ppms-modal-title">{{ t('contracts.modalEditTitle') }}</h2>
                <form class="ppms-stack" @submit.prevent="saveEdit">
                    <label>{{ t('contracts.fieldVendor') }}</label>
                    <input v-model.trim="editForm.vendor_name" type="text" class="ppms-input" :placeholder="t('contracts.fieldVendorPlaceholder')" />
                    <label>{{ t('contracts.fieldProduct') }}</label>
                    <input v-model.trim="editForm.product_name" type="text" class="ppms-input" :placeholder="t('contracts.fieldProductPlaceholder')" />
                    <label>{{ t('contracts.fieldScope') }}</label>
                    <textarea v-model="editForm.scope" class="ppms-input" rows="3" />
                    <label>{{ t('contracts.fieldStart') }}</label>
                    <input v-model="editForm.start_date" type="date" class="ppms-input" required />
                    <label>{{ t('contracts.fieldEnd') }}</label>
                    <input v-model="editForm.end_date" type="date" class="ppms-input" required />
                    <label>{{ t('contracts.fieldValue') }}</label>
                    <input v-model="editForm.total_value" class="ppms-input" required />
                    <label>{{ t('contracts.fieldCycle') }}</label>
                    <select v-model="editForm.payment_cycle" class="ppms-input" required>
                        <option value="monthly">{{ t('contracts.cycleMonthly') }}</option>
                        <option value="quarterly">{{ t('contracts.cycleQuarterly') }}</option>
                        <option value="yearly">{{ t('contracts.cycleYearly') }}</option>
                    </select>
                    <p v-if="editErr" class="ppms-error">{{ editErr }}</p>
                    <div class="ppms-modal-actions">
                        <button type="button" class="ppms-btn-ghost" @click="editOpen = false">{{ t('common.cancel') }}</button>
                        <button type="submit" class="ppms-btn-primary">{{ t('common.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { formatApiUserMessage } from '@/bootstrap';
import { ppmsConfirm, ppmsToastError, ppmsToastSuccess } from '@/ppmsUi';

const props = defineProps({
    id: { type: [String, Number], required: true },
});

const { t } = useI18n();
const route = useRoute();

const loading = ref(true);
const contract = ref(null);
const me = ref(null);
const tab = ref('info');
const logs = ref([]);
const logsLoading = ref(false);

const submitOpen = ref(false);
const approverIdsRaw = ref('');
const submitErr = ref('');

const editOpen = ref(false);
const editErr = ref('');
const editForm = reactive({
    vendor_name: '',
    product_name: '',
    scope: '',
    start_date: '',
    end_date: '',
    total_value: '',
    payment_cycle: 'monthly',
});

const fileInput = ref(null);
const pendingFile = ref(null);
const uploadAsVersion = ref(false);
const uploading = ref(false);

const canEdit = computed(() => {
    if (!contract.value || !me.value) {
        return false;
    }
    const manage = ['admin', 'pm', 'tl'].includes(me.value.role);

    return manage || contract.value.created_by === me.value.id;
});
const canManage = computed(() => ['admin', 'pm', 'tl'].includes(me.value?.role));

const canSubmit = computed(() => {
    if (!contract.value || contract.value.status !== 'draft') {
        return false;
    }
    return (
        canManage.value ||
        me.value?.id === contract.value.created_by
    );
});

const canTerminate = computed(() => canManage.value);

const canMarkPaid = computed(() => canManage.value);

const isCurrentApprover = computed(() => {
    if (!contract.value || contract.value.status !== 'pending_approval' || !me.value) {
        return false;
    }
    const pending = (contract.value.approvals || []).find((a) => a.status === 'pending');
    return pending && pending.approver_id === me.value.id;
});

async function loadMe() {
    try {
        const { data } = await axios.get('/api/user');
        me.value = data;
    } catch {
        me.value = null;
    }
}

async function loadContract() {
    loading.value = true;
    try {
        const { data } = await axios.get(`/api/contracts/${props.id}`);
        contract.value = data.data || data;
        logs.value = [];
    } catch (e) {
        contract.value = null;
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    } finally {
        loading.value = false;
    }
}

async function loadLogs() {
    logsLoading.value = true;
    try {
        const { data } = await axios.get(`/api/contracts/${props.id}/logs`);
        logs.value = data.data || [];
    } catch {
        logs.value = [];
    } finally {
        logsLoading.value = false;
    }
}

function openEdit() {
    if (!contract.value) {
        return;
    }
    editErr.value = '';
    editForm.vendor_name = contract.value.vendor?.name || '';
    editForm.product_name = contract.value.product?.name || '';
    editForm.scope = contract.value.scope || '';
    editForm.start_date = contract.value.start_date || '';
    editForm.end_date = contract.value.end_date || '';
    editForm.total_value = String(contract.value.total_value ?? '');
    editForm.payment_cycle = contract.value.payment_cycle || 'monthly';
    editOpen.value = true;
}

async function saveEdit() {
    editErr.value = '';
    try {
        await axios.patch(`/api/contracts/${props.id}`, {
            vendor_name: editForm.vendor_name,
            product_name: editForm.product_name,
            scope: editForm.scope || null,
            start_date: editForm.start_date,
            end_date: editForm.end_date,
            total_value: editForm.total_value,
            payment_cycle: editForm.payment_cycle,
        });
        ppmsToastSuccess(t('contracts.updated'));
        editOpen.value = false;
        await loadContract();
    } catch (e) {
        editErr.value = formatApiUserMessage(e, t('contracts.loadError'));
    }
}

async function removeDraft() {
    if (!(await ppmsConfirm(t('contracts.deleteConfirm')))) {
        return;
    }
    try {
        await axios.delete(`/api/contracts/${props.id}`);
        ppmsToastSuccess(t('contracts.deleted'));
        window.location.href = '/contracts';
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

async function submitApproval() {
    submitErr.value = '';
    const parts = approverIdsRaw.value
        .split(/[,\s]+/)
        .map((x) => parseInt(x.trim(), 10))
        .filter((n) => !Number.isNaN(n) && n > 0);
    if (parts.length === 0) {
        submitErr.value = t('contracts.submitHint');
        return;
    }
    if (!(await ppmsConfirm(t('contracts.submitConfirm')))) {
        return;
    }
    try {
        await axios.post(`/api/contracts/${props.id}/submit`, {
            approvers: parts.map((user_id) => ({ user_id })),
        });
        ppmsToastSuccess(t('contracts.submitted'));
        submitOpen.value = false;
        await loadContract();
    } catch (e) {
        submitErr.value = formatApiUserMessage(e, t('contracts.loadError'));
    }
}

async function doApprove() {
    try {
        await axios.post(`/api/contracts/${props.id}/approve`);
        ppmsToastSuccess(t('contracts.approved'));
        await loadContract();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

async function doReject() {
    try {
        await axios.post(`/api/contracts/${props.id}/reject`);
        ppmsToastSuccess(t('contracts.rejected'));
        await loadContract();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

async function doTerminate() {
    if (!(await ppmsConfirm(t('contracts.terminateConfirm')))) {
        return;
    }
    try {
        await axios.post(`/api/contracts/${props.id}/terminate`);
        ppmsToastSuccess(t('contracts.terminated'));
        await loadContract();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

async function markPaid(paymentId) {
    try {
        await axios.post(`/api/contracts/${props.id}/payments/${paymentId}/mark-paid`);
        ppmsToastSuccess(t('contracts.markedPaid'));
        await loadContract();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

function onFilePick(e) {
    const f = e.target.files?.[0];
    pendingFile.value = f || null;
    if (f) {
        doUpload();
    }
}

async function doUpload() {
    if (!pendingFile.value) {
        return;
    }
    uploading.value = true;
    try {
        const fd = new FormData();
        fd.append('file', pendingFile.value);
        fd.append('create_version', uploadAsVersion.value ? '1' : '0');
        await axios.post(`/api/contracts/${props.id}/files`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        ppmsToastSuccess(t('contracts.fileUploaded'));
        pendingFile.value = null;
        if (fileInput.value) {
            fileInput.value.value = '';
        }
        await loadContract();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    } finally {
        uploading.value = false;
    }
}

async function downloadFile(fileId) {
    try {
        const res = await axios.get(`/api/contracts/${props.id}/files/${fileId}/download`, { responseType: 'blob' });
        const dispo = res.headers['content-disposition'];
        let name = 'download';
        if (dispo && dispo.includes('filename=')) {
            name = dispo.split('filename=')[1].replace(/"/g, '').trim();
        }
        const url = URL.createObjectURL(res.data);
        const a = document.createElement('a');
        a.href = url;
        a.download = name;
        a.click();
        URL.revokeObjectURL(url);
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

watch(
    () => route.params.id,
    () => {
        loadContract();
        logs.value = [];
    },
);

watch(tab, (v) => {
    if (v === 'logs') {
        loadLogs();
    }
});

onMounted(async () => {
    await loadMe();
    await loadContract();
});
</script>

<style scoped>
.ppms-contract-header {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}
.ppms-contract-title {
    margin: 8px 0 4px;
    font-size: 1.5rem;
}
.ppms-contract-meta {
    margin: 0;
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
    color: var(--ppms-muted, #64748b);
}
.ppms-contract-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.ppms-back {
    font-size: 0.9rem;
    text-decoration: none;
}
.ppms-back:hover {
    text-decoration: underline;
}
.ppms-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    border-bottom: 1px solid var(--ppms-border, #e2e8f0);
    margin-bottom: 0;
}
.ppms-tab {
    padding: 10px 16px;
    border: none;
    background: transparent;
    cursor: pointer;
    font: inherit;
    color: var(--ppms-muted, #64748b);
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
}
.ppms-tab--active {
    color: var(--ppms-accent, #2563eb);
    border-bottom-color: var(--ppms-accent, #2563eb);
    font-weight: 600;
}
.ppms-mt {
    margin-top: 16px;
}
.ppms-mb {
    margin-bottom: 12px;
}
.ppms-dl {
    display: grid;
    gap: 12px;
}
.ppms-dl dt {
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--ppms-muted, #64748b);
}
.ppms-dl dd {
    margin: 4px 0 0;
}
.ppms-file-upload {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}
.ppms-checkbox {
    display: inline-flex;
    gap: 8px;
    align-items: center;
    font-size: 0.9rem;
}
.ppms-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 80;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 24px;
    overflow: auto;
}
.ppms-modal {
    width: min(480px, 100%);
    margin-top: 48px;
}
.ppms-modal-title {
    margin: 0 0 12px;
}
.ppms-modal-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 12px;
}
.ppms-btn-danger {
    color: #b91c1c;
}
.ppms-sr-file {
    position: absolute;
    width: 0;
    height: 0;
    opacity: 0;
    pointer-events: none;
}
</style>
