<template>
    <div class="ppms-page">
        <section class="ppms-card">
            <div class="ppms-contract-toolbar">
                <div class="ppms-contract-filters">
                    <label class="ppms-sr-only" for="contract-filter-status">{{ t('contracts.filterStatus') }}</label>
                    <select id="contract-filter-status" v-model="filters.status" class="ppms-input" @change="page = 1">
                        <option value="">{{ t('contracts.allStatuses') }}</option>
                        <option value="draft">draft</option>
                        <option value="pending_approval">pending_approval</option>
                        <option value="active">active</option>
                        <option value="expired">expired</option>
                        <option value="terminated">terminated</option>
                    </select>
                    <label class="ppms-sr-only" for="contract-filter-code">{{ t('contracts.filterCode') }}</label>
                    <input
                        id="contract-filter-code"
                        v-model.trim="filters.code"
                        type="search"
                        class="ppms-input"
                        :placeholder="t('contracts.filterCode')"
                        @keyup.enter="page = 1; load()"
                    />
                    <button type="button" class="ppms-btn-ghost" @click="page = 1; load()">
                        {{ t('contracts.refresh') }}
                    </button>
                </div>
                <button type="button" class="ppms-btn-primary" @click="openCreate">{{ t('contracts.create') }}</button>
            </div>

            <p v-if="lookupWarning" class="ppms-hint ppms-mt">{{ t('contracts.lookupEmpty') }}</p>

            <div v-if="loading" class="ppms-loading-line" role="status">{{ t('common.loading') }}</div>
            <div v-else class="ppms-table-scroll ppms-mt">
                <table class="ppms-table">
                    <thead>
                        <tr>
                            <th>{{ t('contracts.tableCode') }}</th>
                            <th>{{ t('contracts.tableVendor') }}</th>
                            <th>{{ t('contracts.tableStatus') }}</th>
                            <th>{{ t('contracts.tablePeriod') }}</th>
                            <th>{{ t('contracts.tableValue') }}</th>
                            <th>{{ t('contracts.tableActions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="items.length === 0">
                            <td colspan="6" class="ppms-table-empty">{{ t('contracts.empty') }}</td>
                        </tr>
                        <tr v-for="row in items" :key="row.id">
                            <td>
                                <router-link :to="`/contracts/${row.id}`" class="ppms-link">{{ row.code }}</router-link>
                            </td>
                            <td>{{ row.vendor?.name || '—' }}</td>
                            <td><span class="ppms-pill">{{ row.status }}</span></td>
                            <td>{{ row.start_date }} → {{ row.end_date }}</td>
                            <td>{{ row.total_value }}</td>
                            <td>
                                <router-link :to="`/contracts/${row.id}`" class="ppms-btn-ghost ppms-btn-sm">{{
                                    t('contracts.view')
                                }}</router-link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="meta && meta.last_page > 1" class="ppms-pagination ppms-mt">
                <button
                    type="button"
                    class="ppms-btn-ghost ppms-btn-sm"
                    :disabled="page <= 1"
                    @click="page--; load()"
                >
                    ‹
                </button>
                <span class="ppms-pagination-meta">{{ meta.current_page }} / {{ meta.last_page }}</span>
                <button
                    type="button"
                    class="ppms-btn-ghost ppms-btn-sm"
                    :disabled="page >= meta.last_page"
                    @click="page++; load()"
                >
                    ›
                </button>
            </div>
        </section>

        <div v-if="modalOpen" class="ppms-modal-backdrop" role="presentation" @click.self="modalOpen = false">
            <div class="ppms-modal ppms-card" role="dialog" aria-modal="true" :aria-label="t('contracts.modalCreateTitle')">
                <h2 class="ppms-modal-title">{{ t('contracts.modalCreateTitle') }}</h2>
                <form class="ppms-stack" @submit.prevent="submitCreate">
                    <label>{{ t('contracts.fieldVendor') }}</label>
                    <select v-model.number="form.vendor_id" class="ppms-input" required>
                        <option disabled value="0">{{ t('contracts.fieldVendor') }}</option>
                        <option v-for="v in lookups.vendors" :key="v.id" :value="v.id">{{ v.name }}</option>
                    </select>
                    <label>{{ t('contracts.fieldProduct') }}</label>
                    <select v-model.number="form.product_id" class="ppms-input" required>
                        <option disabled value="0">{{ t('contracts.fieldProduct') }}</option>
                        <option v-for="p in lookups.products" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <label>{{ t('contracts.fieldDepartment') }}</label>
                    <select v-model.number="form.department_id" class="ppms-input" required>
                        <option disabled value="0">{{ t('contracts.fieldDepartment') }}</option>
                        <option v-for="d in lookups.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                    <label>{{ t('contracts.fieldScope') }}</label>
                    <textarea v-model="form.scope" class="ppms-input" rows="3" />
                    <label>{{ t('contracts.fieldStart') }}</label>
                    <input v-model="form.start_date" type="date" class="ppms-input" required />
                    <label>{{ t('contracts.fieldEnd') }}</label>
                    <input v-model="form.end_date" type="date" class="ppms-input" required />
                    <label>{{ t('contracts.fieldValue') }}</label>
                    <input v-model="form.total_value" type="text" class="ppms-input" required placeholder="0.00" />
                    <label>{{ t('contracts.fieldCycle') }}</label>
                    <select v-model="form.payment_cycle" class="ppms-input" required>
                        <option value="monthly">{{ t('contracts.cycleMonthly') }}</option>
                        <option value="quarterly">{{ t('contracts.cycleQuarterly') }}</option>
                        <option value="yearly">{{ t('contracts.cycleYearly') }}</option>
                    </select>
                    <p v-if="formError" class="ppms-error">{{ formError }}</p>
                    <div class="ppms-modal-actions">
                        <button type="button" class="ppms-btn-ghost" @click="modalOpen = false">{{ t('common.cancel') }}</button>
                        <button type="submit" class="ppms-btn-primary" :disabled="saving">{{ t('common.save') }}</button>
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
import { formatApiUserMessage } from '@/bootstrap';
import { ppmsToastError, ppmsToastSuccess } from '@/ppmsUi';

const { t } = useI18n();

const loading = ref(true);
const saving = ref(false);
const items = ref([]);
const meta = ref(null);
const page = ref(1);
const filters = reactive({ status: '', code: '' });
const modalOpen = ref(false);
const formError = ref('');

const lookups = reactive({
    vendors: [],
    products: [],
    departments: [],
});

const form = reactive({
    vendor_id: 0,
    product_id: 0,
    department_id: 0,
    scope: '',
    start_date: '',
    end_date: '',
    total_value: '',
    payment_cycle: 'monthly',
});

const lookupWarning = computed(
    () => lookups.vendors.length === 0 || lookups.products.length === 0 || lookups.departments.length === 0,
);

async function loadLookups() {
    try {
        const { data } = await axios.get('/api/contract-lookups');
        lookups.vendors = data.vendors || [];
        lookups.products = data.products || [];
        lookups.departments = data.departments || [];
    } catch {
        lookups.vendors = [];
        lookups.products = [];
        lookups.departments = [];
    }
}

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/contracts', {
            params: {
                page: page.value,
                per_page: 25,
                status: filters.status || undefined,
                code: filters.code || undefined,
            },
        });
        items.value = data.data || [];
        meta.value = data.meta || null;
    } catch (e) {
        items.value = [];
        meta.value = null;
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    formError.value = '';
    form.vendor_id = lookups.vendors[0]?.id || 0;
    form.product_id = lookups.products[0]?.id || 0;
    form.department_id = lookups.departments[0]?.id || 0;
    form.scope = '';
    form.start_date = '';
    form.end_date = '';
    form.total_value = '';
    form.payment_cycle = 'monthly';
    modalOpen.value = true;
}

async function submitCreate() {
    formError.value = '';
    saving.value = true;
    try {
        await axios.post('/api/contracts', {
            vendor_id: form.vendor_id,
            product_id: form.product_id,
            department_id: form.department_id,
            scope: form.scope || null,
            start_date: form.start_date,
            end_date: form.end_date,
            total_value: form.total_value,
            payment_cycle: form.payment_cycle,
        });
        ppmsToastSuccess(t('contracts.created'));
        modalOpen.value = false;
        await load();
    } catch (e) {
        formError.value = formatApiUserMessage(e, t('contracts.loadError'));
    } finally {
        saving.value = false;
    }
}

watch(
    () => filters.status,
    () => {
        page.value = 1;
        load();
    },
);

onMounted(async () => {
    await loadLookups();
    await load();
});
</script>

<style scoped>
.ppms-contract-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
    justify-content: space-between;
}
.ppms-contract-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
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
    width: min(520px, 100%);
    margin-top: 48px;
}
.ppms-modal-title {
    margin: 0 0 16px;
    font-size: 1.15rem;
}
.ppms-modal-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 8px;
}
.ppms-sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}
.ppms-pagination {
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: center;
}
.ppms-table-empty {
    text-align: center;
    color: var(--ppms-muted, #64748b);
    padding: 24px !important;
}
.ppms-link {
    font-weight: 600;
}
</style>
