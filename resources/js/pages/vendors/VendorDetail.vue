<template>
    <div class="ppms-page vm-detail">
        <div v-if="loading" class="ppms-loading-line" role="status">{{ t('common.loading') }}</div>
        <p v-else-if="err" class="ppms-error">{{ err }}</p>
        <template v-else-if="vendor">
            <header class="vm-detail__header">
                <router-link to="/vendors" class="ppms-back">{{ t('common.back') }}</router-link>
                <h1 class="vm-detail__title">{{ vendor.name }}</h1>
                <div class="vm-detail__badges">
                    <span class="vm-pill">{{ kindLabel(vendor.kind) }}</span>
                    <span class="vm-pill vm-pill--muted">{{ statusLabel(vendor.status) }}</span>
                    <span v-if="vendor.vendor_score" class="vm-pill vm-pill--score">{{ t('vendors.vendorScore') }}: {{ vendor.vendor_score }}</span>
                </div>
                <div v-if="canEdit" class="vm-detail__actions">
                    <button v-if="!editMode" type="button" class="ppms-btn-ghost" @click="startEdit">{{ t('vendors.edit') }}</button>
                    <template v-else>
                        <button type="button" class="ppms-btn-primary" :disabled="saving" @click="save">{{ t('vendors.save') }}</button>
                        <button type="button" class="ppms-btn-ghost" @click="cancelEdit">{{ t('vendors.cancel') }}</button>
                    </template>
                    <button
                        v-if="canDelete"
                        type="button"
                        class="ppms-btn-ghost ppms-btn-danger"
                        @click="removeVendor"
                    >
                        {{ t('vendors.deleteVendor') }}
                    </button>
                </div>
            </header>

            <div class="vm-tabs ppms-tabs" role="tablist">
                <button type="button" class="ppms-tab" :class="{ 'ppms-tab--active': tab === 'overview' }" @click="tab = 'overview'">{{ t('vendors.tabOverview') }}</button>
                <button type="button" class="ppms-tab" :class="{ 'ppms-tab--active': tab === 'business' }" @click="tab = 'business'">{{ t('vendors.tabBusiness') }}</button>
                <button type="button" class="ppms-tab" :class="{ 'ppms-tab--active': tab === 'contracts' }" @click="tab = 'contracts'">{{ t('vendors.tabContracts') }}</button>
                <button type="button" class="ppms-tab" :class="{ 'ppms-tab--active': tab === 'reviews' }" @click="tab = 'reviews'">{{ t('vendors.tabReviews') }}</button>
                <button type="button" class="ppms-tab" :class="{ 'ppms-tab--active': tab === 'timeline' }" @click="openTimeline">{{ t('vendors.tabTimeline') }}</button>
            </div>

            <section v-show="tab === 'overview'" class="ppms-card ppms-mt">
                <table v-if="!editMode" class="vm-kv">
                    <tbody>
                        <tr><th>{{ t('vendors.legalName') }}</th><td>{{ vendor.legal_name || '—' }}</td></tr>
                        <tr><th>{{ t('vendors.country') }}</th><td>{{ vendor.country || '—' }}</td></tr>
                        <tr><th>{{ t('vendors.website') }}</th><td><a v-if="vendor.website" :href="vendor.website" target="_blank" rel="noopener">{{ vendor.website }}</a><template v-else>—</template></td></tr>
                        <tr><th>{{ t('vendors.taxCode') }}</th><td>{{ vendor.tax_code || '—' }}</td></tr>
                        <tr><th>{{ t('vendors.riskLevel') }}</th><td>{{ riskLabel(vendor.risk_level) }}</td></tr>
                        <tr><th>{{ t('vendors.internalNote') }}</th><td class="vm-pre">{{ vendor.internal_note || '—' }}</td></tr>
                        <tr>
                            <th>{{ t('vendors.departments') }}</th>
                            <td>
                                <span v-for="d in vendor.departments || []" :key="d.id" class="vm-dept-badge">{{ d.name }}</span>
                                <template v-if="!(vendor.departments || []).length">—</template>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="editMode" class="ppms-stack ppms-mt">
                    <label class="ppms-field"><span>{{ t('vendors.legalName') }}</span><input v-model="edit.legal_name" class="ppms-input" /></label>
                    <label class="ppms-field"><span>{{ t('vendors.country') }}</span><input v-model="edit.country" class="ppms-input" /></label>
                    <label class="ppms-field"><span>{{ t('vendors.website') }}</span><input v-model="edit.website" class="ppms-input" /></label>
                    <label class="ppms-field"><span>{{ t('vendors.taxCode') }}</span><input v-model="edit.tax_code" class="ppms-input" /></label>
                    <label class="ppms-field"><span>{{ t('vendors.riskLevel') }}</span>
                        <select v-model="edit.risk_level" class="ppms-input">
                            <option value="">{{ t('contracts.allStatuses') }}</option>
                            <option value="low">{{ t('vendors.riskLow') }}</option>
                            <option value="medium">{{ t('vendors.riskMedium') }}</option>
                            <option value="high">{{ t('vendors.riskHigh') }}</option>
                        </select>
                    </label>
                    <label class="ppms-field"><span>{{ t('vendors.internalNote') }}</span><textarea v-model="edit.internal_note" rows="3" class="ppms-input" /></label>
                </div>
            </section>

            <section v-show="tab === 'business'" class="ppms-card ppms-mt">
                <h3 class="vm-sec-title">{{ t('vendors.tabBusiness') }}</h3>
                <table v-if="!editMode" class="vm-kv">
                    <tbody>
                        <tr><th>{{ t('vendors.filterIndustry') }}</th><td>{{ vendor.industry || '—' }}</td></tr>
                        <tr><th>{{ t('vendors.mainProducts') }}</th><td class="vm-pre">{{ vendor.main_products || '—' }}</td></tr>
                        <tr><th>{{ t('vendors.contractValue') }}</th><td>{{ vendor.contract_value ?? '—' }}</td></tr>
                        <tr><th>{{ t('vendors.estimatedCost') }}</th><td>{{ vendor.estimated_cost ?? '—' }}</td></tr>
                        <tr><th>{{ t('vendors.referencePrice') }}</th><td>{{ vendor.reference_price ?? '—' }}</td></tr>
                        <tr><th>{{ t('vendors.researchSource') }}</th><td>{{ vendor.research_source || '—' }}</td></tr>
                        <tr><th>{{ t('vendors.fitScore') }}</th><td>{{ vendor.fit_score ?? '—' }}</td></tr>
                        <tr><th>{{ t('vendors.pros') }}</th><td class="vm-pre">{{ vendor.pros || '—' }}</td></tr>
                        <tr><th>{{ t('vendors.cons') }}</th><td class="vm-pre">{{ vendor.cons || '—' }}</td></tr>
                        <tr><th>{{ t('vendors.researchNote') }}</th><td class="vm-pre">{{ vendor.research_note || '—' }}</td></tr>
                    </tbody>
                </table>
                <div v-if="editMode" class="ppms-stack ppms-mt">
                    <label class="ppms-field"><span>{{ t('vendors.filterIndustry') }}</span><input v-model="edit.industry" class="ppms-input" /></label>
                    <label class="ppms-field"><span>{{ t('vendors.mainProducts') }}</span><textarea v-model="edit.main_products" rows="2" class="ppms-input" /></label>
                    <label class="ppms-field"><span>{{ t('vendors.contractValue') }}</span><input v-model="edit.contract_value" type="number" min="0" step="0.01" class="ppms-input" /></label>
                    <label class="ppms-field"><span>{{ t('vendors.estimatedCost') }}</span><input v-model="edit.estimated_cost" type="number" min="0" step="0.01" class="ppms-input" /></label>
                    <label class="ppms-field"><span>{{ t('vendors.referencePrice') }}</span><input v-model="edit.reference_price" type="number" min="0" step="0.01" class="ppms-input" /></label>
                    <label class="ppms-field"><span>{{ t('vendors.researchSource') }}</span><input v-model="edit.research_source" class="ppms-input" /></label>
                    <label class="ppms-field"><span>{{ t('vendors.fitScore') }}</span><input v-model.number="edit.fit_score" type="number" min="0" max="100" class="ppms-input" /></label>
                    <label class="ppms-field"><span>{{ t('vendors.pros') }}</span><textarea v-model="edit.pros" rows="2" class="ppms-input" /></label>
                    <label class="ppms-field"><span>{{ t('vendors.cons') }}</span><textarea v-model="edit.cons" rows="2" class="ppms-input" /></label>
                    <label class="ppms-field"><span>{{ t('vendors.researchNote') }}</span><textarea v-model="edit.research_note" rows="2" class="ppms-input" /></label>
                    <label class="ppms-field"><span>{{ t('vendors.criteriaScores') }}</span></label>
                    <div class="vm-grid-4">
                        <label class="ppms-field"><span>{{ t('vendors.scorePrice') }}</span><input v-model="edit.score_price" type="number" min="0" max="5" step="0.1" class="ppms-input" /></label>
                        <label class="ppms-field"><span>{{ t('vendors.scoreQuality') }}</span><input v-model="edit.score_quality" type="number" min="0" max="5" step="0.1" class="ppms-input" /></label>
                        <label class="ppms-field"><span>{{ t('vendors.scoreSla') }}</span><input v-model="edit.score_sla" type="number" min="0" max="5" step="0.1" class="ppms-input" /></label>
                        <label class="ppms-field"><span>{{ t('vendors.scoreSupport') }}</span><input v-model="edit.score_support" type="number" min="0" max="5" step="0.1" class="ppms-input" /></label>
                    </div>
                    <label class="ppms-field"><span>{{ t('vendors.departments') }}</span>
                        <select v-model="editDepartmentIds" class="ppms-input" multiple size="6">
                            <option v-for="d in lookups.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                    </label>
                    <p class="ppms-muted">{{ t('contracts.addDepartment') }}</p>
                </div>
                <div v-if="vendor.products?.length" class="ppms-mt">
                    <h4>{{ t('vendors.productLines') }}</h4>
                    <ul class="vm-product-list">
                        <li v-for="p in vendor.products" :key="p.id"><strong>{{ p.name }}</strong> — {{ p.description || '' }}</li>
                    </ul>
                </div>
            </section>

            <section v-show="tab === 'contracts'" class="ppms-card ppms-mt">
                <table v-if="vendor.contracts?.length" class="ppms-table">
                    <thead>
                        <tr>
                            <th>{{ t('vendors.contractCode') }}</th>
                            <th>{{ t('contracts.tableStatus') }}</th>
                            <th>{{ t('vendors.contractDept') }}</th>
                            <th>{{ t('contracts.tableValue') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in vendor.contracts" :key="c.id">
                            <td>{{ c.code }}</td>
                            <td>{{ c.status }}</td>
                            <td>{{ c.department?.name || '—' }}</td>
                            <td>{{ c.total_value }}</td>
                            <td><router-link class="ppms-btn-ghost ppms-btn-sm" :to="`/contracts/${c.id}`">{{ t('vendors.goContract') }}</router-link></td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="ppms-muted">{{ t('vendors.contractsEmpty') }}</p>
            </section>

            <section v-show="tab === 'reviews'" class="ppms-card ppms-mt">
                <VendorReview
                    :vendor-id="Number(vendor.id)"
                    :items="vendor.reviews || []"
                    :can-review="canEdit"
                    :me="me"
                    @refresh="reload"
                    @delete="onDeleteReview"
                />
            </section>

            <section v-show="tab === 'timeline'" class="ppms-card ppms-mt">
                <div v-if="canEdit" class="vm-timeline-form ppms-stack">
                    <h2 class="vm-sec-title">{{ t('vendors.timelineAdd') }}</h2>
                    <div class="vm-grid-2">
                        <label class="ppms-field">
                            <span>{{ t('vendors.timelinePhase') }}</span>
                            <select v-model="tlForm.phase" class="ppms-input">
                                <option v-for="ph in phases" :key="ph" :value="ph">{{ timelinePhaseLabel(ph) }}</option>
                            </select>
                        </label>
                        <label class="ppms-field">
                            <span>{{ t('vendors.timelineWhen') }}</span>
                            <input v-model="tlForm.occurred_at_local" type="datetime-local" class="ppms-input" :step="60" />
                        </label>
                    </div>
                    <label class="ppms-field">
                        <span>{{ t('vendors.timelineActor') }}</span>
                        <input v-model.number="tlForm.performed_by_user_id" type="number" min="1" class="ppms-input" />
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('vendors.timelineNote') }}</span>
                        <textarea v-model="tlForm.note" rows="2" class="ppms-input" />
                    </label>
                    <label class="ppms-field ppms-field-inline">
                        <input v-model="tlForm.is_current" type="checkbox" />
                        <span>{{ t('vendors.timelineCurrent') }}</span>
                    </label>
                    <button type="button" class="ppms-btn-primary" :disabled="tlSaving" @click="addTimeline">{{ t('vendors.timelineAdd') }}</button>
                </div>
                <VendorTimeline
                    :events="timelineEvents"
                    :loading="timelineLoading"
                    :err="timelineErr"
                    :can-edit="canEdit"
                    @delete="onDeleteTimeline"
                />
            </section>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { getApiErrorMessage } from '@/bootstrap';
import { ppmsConfirm, ppmsToastSuccess, ppmsToastError } from '@/ppmsUi';
import VendorTimeline from './components/VendorTimeline.vue';
import VendorReview from './components/VendorReview.vue';

defineProps({
    id: { type: String, required: true },
});

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const loading = ref(true);
const err = ref('');
const vendor = ref(null);
const me = ref(null);
const editMode = ref(false);
const saving = ref(false);
const lookups = ref({ departments: [] });

const edit = reactive({});
const editDepartmentIds = ref([]);

const tab = ref('overview');
const timelineEvents = ref([]);
const timelineLoading = ref(false);
const timelineErr = ref('');
const tlSaving = ref(false);
const tlForm = reactive({
    phase: 'potential_contact',
    occurred_at_local: '',
    performed_by_user_id: null,
    note: '',
    is_current: false,
});

const phases = [
    'potential_contact',
    'survey_consult',
    'quotation',
    'negotiation',
    'contract_signed',
    'payment_acceptance',
    'no_contract',
    'research_update',
];

const canEdit = computed(() => ['admin', 'pm', 'tl', 'hr', 'developer'].includes(me.value?.role));
const canDelete = computed(() => ['admin', 'pm', 'tl'].includes(me.value?.role));

function kindLabel(k) {
    return k === 'research' ? t('vendors.kindResearch') : t('vendors.kindActive');
}

function statusLabel(s) {
    const map = {
        active: 'vendors.statusActive',
        inactive: 'vendors.statusInactive',
        blacklist: 'vendors.statusBlacklist',
        researching: 'vendors.statusResearching',
        shortlist: 'vendors.statusShortlist',
        rejected: 'vendors.statusRejected',
    };
    const key = map[s];
    return key ? t(key) : s;
}

function riskLabel(r) {
    const map = { low: 'vendors.riskLow', medium: 'vendors.riskMedium', high: 'vendors.riskHigh' };
    const key = map[r];
    return key ? t(key) : r || '—';
}

function timelinePhaseLabel(ph) {
    const map = {
        potential_contact: 'vendors.phasePotentialContact',
        survey_consult: 'vendors.phaseSurveyConsult',
        quotation: 'vendors.phaseQuotation',
        negotiation: 'vendors.phaseNegotiation',
        contract_signed: 'vendors.phaseContractSigned',
        payment_acceptance: 'vendors.phasePaymentAcceptance',
        no_contract: 'vendors.phaseNoContract',
        research_update: 'vendors.phaseResearchUpdate',
    };
    const key = map[ph];
    return key ? t(key) : ph;
}

function snapshotFromVendor(v) {
    const keys = [
        'legal_name', 'country', 'website', 'tax_code', 'contact_info', 'internal_note', 'risk_level',
        'industry', 'main_products', 'contract_value', 'estimated_cost', 'reference_price',
        'research_source', 'research_note', 'pros', 'cons', 'fit_score',
        'score_price', 'score_quality', 'score_sla', 'score_support',
    ];
    for (const k of keys) {
        edit[k] = v[k] ?? '';
    }
    editDepartmentIds.value = (v.departments || []).map((d) => d.id);
}

async function loadLookups() {
    const { data } = await axios.get('/api/contract-lookups');
    lookups.value = { departments: data.departments || [] };
}

async function loadVendor() {
    loading.value = true;
    err.value = '';
    try {
        const { data } = await axios.get(`/api/vendors/${route.params.id}`);
        const payload = data.data ?? data;
        vendor.value = payload;
        snapshotFromVendor(payload);
    } catch (e) {
        err.value = getApiErrorMessage(e);
        vendor.value = null;
    } finally {
        loading.value = false;
    }
}

async function reload() {
    await loadVendor();
}

async function loadTimeline() {
    timelineLoading.value = true;
    timelineErr.value = '';
    try {
        const { data } = await axios.get(`/api/vendors/${route.params.id}/timeline`, { params: { per_page: 100 } });
        timelineEvents.value = data.data ?? [];
    } catch (e) {
        timelineErr.value = getApiErrorMessage(e, t('vendors.timelineLoadError'));
        timelineEvents.value = [];
    } finally {
        timelineLoading.value = false;
    }
}

function openTimeline() {
    tab.value = 'timeline';
    loadTimeline();
}

function startEdit() {
    editMode.value = true;
    if (vendor.value) snapshotFromVendor(vendor.value);
}

function cancelEdit() {
    editMode.value = false;
    if (vendor.value) snapshotFromVendor(vendor.value);
}

async function save() {
    saving.value = true;
    try {
        const body = {
            ...edit,
            department_ids: editDepartmentIds.value,
        };
        await axios.patch(`/api/vendors/${route.params.id}`, {
            ...body,
            department_ids: editDepartmentIds.value.map((x) => Number(x)),
        });
        ppmsToastSuccess(t('vendors.saved'));
        editMode.value = false;
        await loadVendor();
    } catch (e) {
        ppmsToastError(getApiErrorMessage(e));
    } finally {
        saving.value = false;
    }
}

async function removeVendor() {
    const ok = await ppmsConfirm(t('vendors.deleteVendorConfirm'), { title: t('vendors.deleteVendor') });
    if (!ok) return;
    try {
        await axios.delete(`/api/vendors/${route.params.id}`);
        ppmsToastSuccess(t('vendors.deleted'));
        router.push({ name: 'vendors' });
    } catch (e) {
        ppmsToastError(getApiErrorMessage(e));
    }
}

async function addTimeline() {
    if (!tlForm.occurred_at_local) {
        ppmsToastError(t('vendors.timelinePickTime'));
        return;
    }
    const iso = new Date(tlForm.occurred_at_local).toISOString();
    tlSaving.value = true;
    try {
        await axios.post(`/api/vendors/${route.params.id}/timeline`, {
            phase: tlForm.phase,
            occurred_at: iso,
            performed_by_user_id: tlForm.performed_by_user_id || null,
            note: tlForm.note || null,
            is_current: tlForm.is_current,
        });
        ppmsToastSuccess(t('vendors.timelineSaved'));
        tlForm.note = '';
        tlForm.is_current = false;
        await loadTimeline();
        await loadVendor();
    } catch (e) {
        ppmsToastError(getApiErrorMessage(e));
    } finally {
        tlSaving.value = false;
    }
}

async function onDeleteTimeline(ev) {
    const ok = await ppmsConfirm(t('vendors.timelineDelete'), { title: t('vendors.timelineDelete') });
    if (!ok) return;
    try {
        await axios.delete(`/api/vendor-timelines/${ev.id}`);
        await loadTimeline();
        await loadVendor();
    } catch (e) {
        ppmsToastError(getApiErrorMessage(e));
    }
}

async function onDeleteReview(r) {
    const ok = await ppmsConfirm(t('vendors.reviewDelete'), { title: t('vendors.reviewDelete') });
    if (!ok) return;
    try {
        await axios.delete(`/api/vendor-reviews/${r.id}`);
        await loadVendor();
    } catch (e) {
        ppmsToastError(getApiErrorMessage(e));
    }
}

watch(
    () => route.params.id,
    () => {
        loadVendor();
    }
);

onMounted(async () => {
    try {
        const u = await axios.get('/api/user');
        me.value = u.data;
    } catch {
        me.value = null;
    }
    await loadLookups();
    await loadVendor();
});
</script>

<style scoped>
.vm-detail__header {
    margin-bottom: 1rem;
}
.vm-detail__title {
    margin: 0.35rem 0;
    font-size: 1.5rem;
}
.vm-detail__badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 0.5rem 0;
}
.vm-pill {
    display: inline-block;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    background: #e2e8f0;
    font-size: 0.85rem;
}
.vm-pill--muted {
    background: #f1f5f9;
}
.vm-pill--score {
    background: #dbeafe;
    color: #1d4ed8;
}
.vm-detail__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}
.vm-kv {
    width: 100%;
    border-collapse: collapse;
}
.vm-kv th,
.vm-kv td {
    text-align: left;
    padding: 0.35rem 0.5rem;
    vertical-align: top;
}
.vm-kv th {
    width: 200px;
    color: var(--ppms-muted, #5c6470);
    font-weight: 500;
}
.vm-pre {
    white-space: pre-wrap;
}
.vm-sec-title {
    margin: 0 0 0.75rem;
    font-size: 1.05rem;
}
.vm-grid-4 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 0.75rem;
}
.vm-grid-2 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
}
.vm-product-list {
    margin: 0.5rem 0 0;
    padding-left: 1.2rem;
}
.vm-timeline-form {
    margin-bottom: 1.25rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--ppms-border, #e2e6ea);
}
.vm-dept-badge {
    display: inline-block;
    margin: 0 0.35rem 0.35rem 0;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    background: #e0e7ff;
    color: #3730a3;
    font-size: 0.85rem;
}
</style>
