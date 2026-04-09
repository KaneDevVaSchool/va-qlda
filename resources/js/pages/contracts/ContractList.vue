<template>
    <div class="ppms-page contract-list">
        <header class="contract-list__intro">
            <h1 class="contract-list__title">{{ t('contracts.pageTitle') }}</h1>
            <p class="contract-list__desc">{{ t('contracts.pageDescription') }}</p>
        </header>

        <section class="ppms-card contract-list__card">
            <div class="contract-list__toolbar">
                <div class="contract-list__toolbar-grid">
                    <div class="contract-list__field">
                        <label class="contract-list__label" for="cl-filter-status">{{ t('contracts.filterStatus') }}</label>
                        <select
                            id="cl-filter-status"
                            v-model="filters.status"
                            class="ppms-input contract-list__input"
                            @change="onFilterCommit"
                        >
                            <option value="">{{ t('contracts.allStatuses') }}</option>
                            <option value="draft">{{ t('contracts.statusDraft') }}</option>
                            <option value="pending_approval">{{ t('contracts.statusPending') }}</option>
                            <option value="active">{{ t('contracts.statusActive') }}</option>
                            <option value="expired">{{ t('contracts.statusExpired') }}</option>
                            <option value="terminated">{{ t('contracts.statusTerminated') }}</option>
                        </select>
                    </div>
                    <div class="contract-list__field">
                        <label class="contract-list__label" for="cl-filter-vendor">{{ t('contracts.filterVendor') }}</label>
                        <select
                            id="cl-filter-vendor"
                            v-model.number="filters.vendor_id"
                            class="ppms-input contract-list__input"
                            @change="onFilterCommit"
                        >
                            <option :value="0">{{ t('contracts.allVendors') }}</option>
                            <option v-for="v in lookups.vendors" :key="v.id" :value="v.id">{{ v.name }}</option>
                        </select>
                    </div>
                    <div class="contract-list__field">
                        <label class="contract-list__label" for="cl-filter-dept">{{ t('contracts.filterDepartment') }}</label>
                        <select
                            id="cl-filter-dept"
                            v-model.number="filters.department_id"
                            class="ppms-input contract-list__input"
                            @change="onFilterCommit"
                        >
                            <option :value="0">{{ t('contracts.allDepartments') }}</option>
                            <option v-for="d in lookups.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                    </div>
                    <div class="contract-list__field contract-list__field--grow">
                        <label class="contract-list__label" for="cl-filter-code">{{ t('contracts.filterCode') }}</label>
                        <div class="contract-list__code-row">
                            <input
                                id="cl-filter-code"
                                v-model.trim="codeDraft"
                                type="search"
                                class="ppms-input contract-list__input"
                                :placeholder="t('contracts.filterCode')"
                                autocomplete="off"
                                @input="scheduleCodeDebounce"
                                @keydown.enter.prevent="flushCodeAndCommit"
                            />
                            <button type="button" class="ppms-btn-ghost contract-list__btn-compact" @click="flushCodeAndCommit">
                                {{ t('contracts.applySearch') }}
                            </button>
                        </div>
                    </div>
                    <div class="contract-list__field">
                        <label class="contract-list__label" for="cl-filter-end-from">{{ t('contracts.filterEndFrom') }}</label>
                        <input
                            id="cl-filter-end-from"
                            v-model="filters.end_from"
                            type="date"
                            class="ppms-input contract-list__input"
                            @change="onFilterCommit"
                        />
                    </div>
                    <div class="contract-list__field">
                        <label class="contract-list__label" for="cl-filter-end-to">{{ t('contracts.filterEndTo') }}</label>
                        <input
                            id="cl-filter-end-to"
                            v-model="filters.end_to"
                            type="date"
                            class="ppms-input contract-list__input"
                            @change="onFilterCommit"
                        />
                    </div>
                    <div class="contract-list__field">
                        <label class="contract-list__label" for="cl-per-page">{{ t('contracts.perPage') }}</label>
                        <select
                            id="cl-per-page"
                            v-model.number="perPage"
                            class="ppms-input contract-list__input"
                            @change="onPerPageChange"
                        >
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                </div>
                <div class="contract-list__toolbar-actions">
                    <button type="button" class="ppms-btn-ghost" :disabled="loading" @click="resetFilters">
                        {{ t('contracts.resetFilters') }}
                    </button>
                    <button type="button" class="ppms-btn-ghost" :disabled="loading" @click="refresh">
                        {{ t('contracts.refresh') }}
                    </button>
                    <button
                        type="button"
                        class="ppms-btn-ghost"
                        :disabled="loading"
                        :title="t('contracts.exportCsvHint')"
                        @click="downloadCsv"
                    >
                        {{ t('contracts.exportCsv') }}
                    </button>
                    <button type="button" class="ppms-btn-primary" @click="openCreate">{{ t('contracts.create') }}</button>
                </div>
            </div>

            <p v-if="lookupWarning" class="contract-list__hint">{{ t('contracts.lookupEmpty') }}</p>

            <div v-if="loading" class="ppms-loading-line contract-list__loading" role="status">{{ t('common.loading') }}</div>

            <template v-else>
                <div v-if="items.length === 0" class="contract-list__empty" role="status">
                    <div class="contract-list__empty-visual" aria-hidden="true" />
                    <p class="contract-list__empty-title">{{ hasActiveFilters ? t('contracts.emptyFiltered') : t('contracts.empty') }}</p>
                    <p class="contract-list__empty-desc">{{ t('contracts.emptyHint') }}</p>
                    <div class="contract-list__empty-actions">
                        <button v-if="hasActiveFilters" type="button" class="ppms-btn-ghost" @click="resetFilters">
                            {{ t('contracts.resetFilters') }}
                        </button>
                        <button type="button" class="ppms-btn-primary" @click="openCreate">{{ t('contracts.create') }}</button>
                    </div>
                </div>

                <div v-else class="contract-list__table-wrap ppms-table-scroll">
                    <table class="ppms-table contract-list__table">
                        <thead>
                            <tr>
                                <th>
                                    <button type="button" class="contract-list__th-btn" @click="toggleSort('code')">
                                        {{ t('contracts.tableCode') }}
                                        <span v-if="sortKey === 'code'" class="contract-list__sort-ind" aria-hidden="true">{{
                                            sortOrder === 'asc' ? '↑' : '↓'
                                        }}</span>
                                    </button>
                                </th>
                                <th>{{ t('contracts.tableVendor') }}</th>
                                <th>{{ t('contracts.tableProduct') }}</th>
                                <th>{{ t('contracts.tableDepartment') }}</th>
                                <th>{{ t('contracts.tableStatus') }}</th>
                                <th>
                                    <button type="button" class="contract-list__th-btn" @click="toggleSort('end_date')">
                                        {{ t('contracts.tablePeriod') }}
                                        <span v-if="sortKey === 'end_date'" class="contract-list__sort-ind" aria-hidden="true">{{
                                            sortOrder === 'asc' ? '↑' : '↓'
                                        }}</span>
                                    </button>
                                </th>
                                <th class="contract-list__cell-num">
                                    <button type="button" class="contract-list__th-btn" @click="toggleSort('total_value')">
                                        {{ t('contracts.tableValue') }}
                                        <span v-if="sortKey === 'total_value'" class="contract-list__sort-ind" aria-hidden="true">{{
                                            sortOrder === 'asc' ? '↑' : '↓'
                                        }}</span>
                                    </button>
                                </th>
                                <th>{{ t('contracts.tableExpiresIn') }}</th>
                                <th>{{ t('contracts.tableActions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in items"
                                :key="row.id"
                                class="contract-list__row"
                                tabindex="0"
                                @click="goDetail(row.id)"
                                @keydown.enter.prevent="goDetail(row.id)"
                            >
                                <td>
                                    <router-link :to="`/contracts/${row.id}`" class="contract-list__link" @click.stop>{{ row.code }}</router-link>
                                </td>
                                <td>{{ row.vendor?.name || '—' }}</td>
                                <td class="contract-list__cell-muted">{{ row.product?.name || '—' }}</td>
                                <td>{{ row.department?.name || '—' }}</td>
                                <td>
                                    <span class="contract-list__pill" :class="statusPillClass(row.status)">{{ statusLabel(row.status) }}</span>
                                </td>
                                <td>
                                    <span class="contract-list__period">{{ row.start_date }} → {{ row.end_date }}</span>
                                </td>
                                <td class="contract-list__cell-num">{{ formatMoney(row.total_value) }}</td>
                                <td>
                                    <span v-if="expiresBadge(row)" class="contract-list__badge-soon">{{ expiresBadge(row) }}</span>
                                    <span v-else class="contract-list__cell-muted">—</span>
                                </td>
                                <td @click.stop>
                                    <router-link :to="`/contracts/${row.id}`" class="ppms-btn-ghost ppms-btn-sm">{{ t('contracts.view') }}</router-link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <div v-if="!loading && items.length > 0 && meta" class="contract-list__footer">
                <p class="contract-list__meta">
                    {{ t('contracts.rangeShown', { from: meta.from ?? 0, to: meta.to ?? 0 }) }}
                    <span class="contract-list__meta-sep">·</span>
                    {{ t('contracts.totalCount', { n: meta.total ?? 0 }) }}
                </p>
                <div v-if="meta.last_page > 1" class="contract-list__pagination">
                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" :disabled="page <= 1" @click="goPrevPage">‹</button>
                    <span class="contract-list__page-label">{{ meta.current_page }} / {{ meta.last_page }}</span>
                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" :disabled="page >= meta.last_page" @click="goNextPage">
                        ›
                    </button>
                </div>
            </div>
        </section>

        <div v-if="modalOpen" class="contract-modal__backdrop" role="presentation" @click.self="modalOpen = false">
            <div class="contract-modal ppms-card" role="dialog" aria-modal="true" :aria-label="t('contracts.modalCreateTitle')">
                <div class="contract-modal__head">
                    <h2 class="contract-modal__title">{{ t('contracts.modalCreateTitle') }}</h2>
                    <button type="button" class="contract-modal__close" :aria-label="t('common.cancel')" @click="modalOpen = false">×</button>
                </div>
                <form class="contract-modal__form" @submit.prevent="submitCreate">
                    <div class="contract-modal__body">
                        <section class="contract-modal__section">
                            <h3 class="contract-modal__section-title">{{ t('contracts.sectionParties') }}</h3>
                            <div class="contract-modal__grid contract-modal__grid--2">
                                <div class="contract-modal__field">
                                    <label for="cm-vendor">{{ t('contracts.fieldVendor') }}</label>
                                    <select id="cm-vendor" v-model.number="form.vendor_id" class="ppms-input" required>
                                        <option disabled :value="0">{{ t('contracts.fieldVendor') }}</option>
                                        <option v-for="v in lookups.vendors" :key="v.id" :value="v.id">{{ v.name }}</option>
                                    </select>
                                </div>
                                <div class="contract-modal__field">
                                    <label for="cm-product">{{ t('contracts.fieldProduct') }}</label>
                                    <select id="cm-product" v-model.number="form.product_id" class="ppms-input" required>
                                        <option disabled :value="0">{{ t('contracts.fieldProduct') }}</option>
                                        <option v-for="p in lookups.products" :key="p.id" :value="p.id">{{ p.name }}</option>
                                    </select>
                                </div>
                                <div class="contract-modal__field contract-modal__field--full">
                                    <label for="cm-dept">{{ t('contracts.fieldDepartment') }}</label>
                                    <select id="cm-dept" v-model.number="form.department_id" class="ppms-input" required>
                                        <option disabled :value="0">{{ t('contracts.fieldDepartment') }}</option>
                                        <option v-for="d in lookups.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <section class="contract-modal__section">
                            <h3 class="contract-modal__section-title">{{ t('contracts.sectionScope') }}</h3>
                            <div class="contract-modal__field">
                                <label for="cm-scope">{{ t('contracts.fieldScope') }}</label>
                                <textarea id="cm-scope" v-model="form.scope" class="ppms-input contract-modal__textarea" rows="3" />
                            </div>
                        </section>

                        <section class="contract-modal__section">
                            <h3 class="contract-modal__section-title">{{ t('contracts.sectionTerm') }}</h3>
                            <div class="contract-modal__grid contract-modal__grid--2">
                                <div class="contract-modal__field">
                                    <label for="cm-start">{{ t('contracts.fieldStart') }}</label>
                                    <input id="cm-start" v-model="form.start_date" type="date" class="ppms-input" required />
                                </div>
                                <div class="contract-modal__field">
                                    <label for="cm-end">{{ t('contracts.fieldEnd') }}</label>
                                    <input id="cm-end" v-model="form.end_date" type="date" class="ppms-input" required />
                                </div>
                                <div class="contract-modal__field">
                                    <label for="cm-value">{{ t('contracts.fieldValue') }}</label>
                                    <input id="cm-value" v-model="form.total_value" type="text" class="ppms-input" required placeholder="0" />
                                </div>
                                <div class="contract-modal__field">
                                    <label for="cm-cycle">{{ t('contracts.fieldCycle') }}</label>
                                    <select id="cm-cycle" v-model="form.payment_cycle" class="ppms-input" required>
                                        <option value="monthly">{{ t('contracts.cycleMonthly') }}</option>
                                        <option value="quarterly">{{ t('contracts.cycleQuarterly') }}</option>
                                        <option value="yearly">{{ t('contracts.cycleYearly') }}</option>
                                    </select>
                                </div>
                            </div>
                        </section>
                    </div>
                    <div class="contract-modal__footer">
                        <p v-if="formError" class="ppms-error contract-modal__error">{{ formError }}</p>
                        <div class="contract-modal__actions">
                            <button type="button" class="ppms-btn-ghost" @click="modalOpen = false">{{ t('common.cancel') }}</button>
                            <button type="submit" class="ppms-btn-primary" :disabled="saving">{{ t('common.save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '@/bootstrap';
import { ppmsToastError, ppmsToastSuccess } from '@/ppmsUi';

const { t, locale } = useI18n();
const route = useRoute();
const router = useRouter();

const loading = ref(true);
const saving = ref(false);
const items = ref([]);
const meta = ref(null);
const page = ref(1);
const perPage = ref(25);
const sortKey = ref('id');
const sortOrder = ref('desc');

const filters = reactive({
    status: '',
    code: '',
    vendor_id: 0,
    department_id: 0,
    end_from: '',
    end_to: '',
});

const codeDraft = ref('');

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

const hasActiveFilters = computed(() => {
    return !!(
        filters.status ||
        filters.code ||
        filters.vendor_id ||
        filters.department_id ||
        filters.end_from ||
        filters.end_to
    );
});

const SORT_KEYS = ['id', 'code', 'end_date', 'start_date', 'total_value', 'status'];

function clampPerPage(n) {
    return Math.min(100, Math.max(10, n));
}

function buildApiParams() {
    return {
        page: page.value,
        per_page: perPage.value,
        status: filters.status || undefined,
        code: filters.code || undefined,
        vendor_id: filters.vendor_id || undefined,
        department_id: filters.department_id || undefined,
        end_from: filters.end_from || undefined,
        end_to: filters.end_to || undefined,
        sort: sortKey.value,
        order: sortOrder.value,
    };
}

function buildRouteQuery() {
    const p = buildApiParams();
    const q = {};
    if (p.page > 1) q.page = String(p.page);
    if (p.per_page !== 25) q.per_page = String(p.per_page);
    if (p.status) q.status = p.status;
    if (p.code) q.code = p.code;
    if (p.vendor_id) q.vendor_id = String(p.vendor_id);
    if (p.department_id) q.department_id = String(p.department_id);
    if (p.end_from) q.end_from = p.end_from;
    if (p.end_to) q.end_to = p.end_to;
    if (p.sort && p.sort !== 'id') q.sort = p.sort;
    if (p.order && p.order !== 'desc') q.order = p.order;
    return q;
}

function readQueryFromRoute(q = route.query) {
    page.value = q.page ? Math.max(1, parseInt(String(q.page), 10)) : 1;
    perPage.value = q.per_page ? clampPerPage(parseInt(String(q.per_page), 10)) : 25;
    filters.status = q.status ? String(q.status) : '';
    filters.code = q.code ? String(q.code) : '';
    codeDraft.value = filters.code;
    filters.vendor_id = q.vendor_id ? Number(q.vendor_id) : 0;
    filters.department_id = q.department_id ? Number(q.department_id) : 0;
    filters.end_from = q.end_from ? String(q.end_from) : '';
    filters.end_to = q.end_to ? String(q.end_to) : '';
    const s = q.sort ? String(q.sort) : 'id';
    sortKey.value = SORT_KEYS.includes(s) ? s : 'id';
    sortOrder.value = q.order === 'asc' ? 'asc' : 'desc';
}

async function replaceRouteQuery() {
    const before = route.fullPath;
    await router.replace({ path: route.path, query: buildRouteQuery() });
    if (route.fullPath === before) {
        readQueryFromRoute();
        await load();
    }
}

function statusLabel(status) {
    const map = {
        draft: 'contracts.statusDraft',
        pending_approval: 'contracts.statusPending',
        active: 'contracts.statusActive',
        expired: 'contracts.statusExpired',
        terminated: 'contracts.statusTerminated',
    };
    return map[status] ? t(map[status]) : status;
}

function statusPillClass(status) {
    return {
        'contract-list__pill--draft': status === 'draft',
        'contract-list__pill--pending': status === 'pending_approval',
        'contract-list__pill--active': status === 'active',
        'contract-list__pill--expired': status === 'expired',
        'contract-list__pill--terminated': status === 'terminated',
    };
}

function formatMoney(value) {
    const n = Number(value);
    if (Number.isNaN(n)) {
        return value ?? '—';
    }
    const loc = locale.value === 'vi' ? 'vi-VN' : 'en-US';
    return new Intl.NumberFormat(loc, { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(n);
}

function daysUntilEnd(endDateStr) {
    if (!endDateStr) return null;
    const end = new Date(`${endDateStr}T12:00:00`);
    const now = new Date();
    now.setHours(12, 0, 0, 0);
    return Math.ceil((end.getTime() - now.getTime()) / 86400000);
}

function expiresBadge(row) {
    if (row.status !== 'active') return '';
    const d = daysUntilEnd(row.end_date);
    if (d === null || Number.isNaN(d)) return '';
    if (d < 0) return '';
    if (d <= 30) {
        return d === 0 ? t('contracts.expiresSoon') : t('contracts.expiresInDays', { n: d });
    }
    return '';
}

async function toggleSort(key) {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortOrder.value = key === 'code' ? 'asc' : 'desc';
    }
    page.value = 1;
    await replaceRouteQuery();
}

async function onFilterCommit() {
    page.value = 1;
    await replaceRouteQuery();
}

async function onPerPageChange() {
    perPage.value = clampPerPage(perPage.value);
    page.value = 1;
    await replaceRouteQuery();
}

async function flushCodeAndCommit() {
    clearTimeout(codeDebounceTimer);
    filters.code = codeDraft.value;
    page.value = 1;
    await replaceRouteQuery();
}

let codeDebounceTimer = null;
function scheduleCodeDebounce() {
    clearTimeout(codeDebounceTimer);
    codeDebounceTimer = setTimeout(async () => {
        filters.code = codeDraft.value;
        page.value = 1;
        await replaceRouteQuery();
    }, 420);
}

async function resetFilters() {
    filters.status = '';
    filters.code = '';
    codeDraft.value = '';
    filters.vendor_id = 0;
    filters.department_id = 0;
    filters.end_from = '';
    filters.end_to = '';
    page.value = 1;
    perPage.value = 25;
    sortKey.value = 'id';
    sortOrder.value = 'desc';
    await replaceRouteQuery();
}

function refresh() {
    load();
}

function goDetail(id) {
    router.push(`/contracts/${id}`);
}

async function goPrevPage() {
    if (page.value <= 1) return;
    page.value -= 1;
    await replaceRouteQuery();
}

async function goNextPage() {
    if (!meta.value || page.value >= meta.value.last_page) return;
    page.value += 1;
    await replaceRouteQuery();
}

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/contracts', { params: buildApiParams() });
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

async function downloadCsv() {
    try {
        const { data, headers } = await axios.get('/api/contracts/export.csv', {
            params: {
                status: filters.status || undefined,
                code: filters.code || undefined,
                vendor_id: filters.vendor_id || undefined,
                department_id: filters.department_id || undefined,
                end_from: filters.end_from || undefined,
                end_to: filters.end_to || undefined,
                sort: sortKey.value,
                order: sortOrder.value,
            },
            responseType: 'blob',
        });
        const blob = new Blob([data], { type: 'text/csv;charset=utf-8' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        const cd = headers['content-disposition'] || headers['Content-Disposition'];
        let filename = 'contracts.csv';
        if (cd && typeof cd === 'string') {
            const m = /filename="?([^";]+)"?/i.exec(cd);
            if (m?.[1]) filename = m[1];
        }
        a.download = filename;
        a.click();
        window.URL.revokeObjectURL(url);
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

watch(
    () => route.fullPath,
    () => {
        if (route.name !== 'contracts') return;
        readQueryFromRoute();
        load();
    },
);

onMounted(async () => {
    await loadLookups();
    readQueryFromRoute();
    await load();
});

onUnmounted(() => {
    clearTimeout(codeDebounceTimer);
});
</script>

<style scoped>
/* —— Page —— */
.contract-list__intro {
    margin-bottom: 20px;
}
.contract-list__title {
    margin: 0 0 8px;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--ppms-fg, #0f172a);
}
.contract-list__desc {
    margin: 0;
    max-width: 52rem;
    line-height: 1.55;
    color: var(--ppms-muted, #64748b);
    font-size: 0.95rem;
}

.contract-list__card {
    padding: 0;
    overflow: hidden;
}

/* —— Toolbar & filters —— */
.contract-list__toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: flex-end;
    justify-content: space-between;
    padding: 20px 20px 16px;
    border-bottom: 1px solid var(--ppms-border, #e2e8f0);
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.9) 0%, transparent 100%);
}
.contract-list__toolbar-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px 14px;
    flex: 1;
    min-width: 0;
}
.contract-list__field--grow {
    grid-column: span 2;
    min-width: 220px;
}
@media (max-width: 720px) {
    .contract-list__field--grow {
        grid-column: span 1;
    }
}
.contract-list__label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--ppms-muted, #64748b);
    margin-bottom: 6px;
}
.contract-list__input {
    width: 100%;
    min-height: 40px;
}
.contract-list__code-row {
    display: flex;
    gap: 8px;
    align-items: stretch;
}
.contract-list__code-row .ppms-input {
    flex: 1;
    min-width: 0;
}
.contract-list__btn-compact {
    flex-shrink: 0;
    white-space: nowrap;
}
.contract-list__toolbar-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    justify-content: flex-end;
}

.contract-list__hint {
    margin: 0;
    padding: 12px 20px 0;
    font-size: 0.9rem;
    color: var(--ppms-muted, #64748b);
}
.contract-list__loading {
    margin: 16px 20px;
}

/* —— Empty —— */
.contract-list__empty {
    text-align: center;
    padding: 40px 24px 48px;
}
.contract-list__empty-visual {
    width: 64px;
    height: 64px;
    margin: 0 auto 16px;
    border-radius: 16px;
    background: linear-gradient(135deg, #e0e7ff 0%, #f1f5f9 100%);
    border: 1px solid var(--ppms-border, #e2e8f0);
}
.contract-list__empty-title {
    margin: 0 0 8px;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--ppms-fg, #0f172a);
}
.contract-list__empty-desc {
    margin: 0 0 20px;
    font-size: 0.95rem;
    color: var(--ppms-muted, #64748b);
}
.contract-list__empty-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}

/* —— Table —— */
.contract-list__table-wrap {
    margin: 0;
}
.contract-list__table {
    margin: 0;
}
.contract-list__th-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin: 0;
    padding: 0;
    border: none;
    background: none;
    font: inherit;
    font-weight: 600;
    color: inherit;
    cursor: pointer;
    text-align: left;
}
.contract-list__th-btn:hover {
    color: var(--ppms-accent, #4f46e5);
}
.contract-list__sort-ind {
    font-size: 0.85em;
    opacity: 0.85;
}
.contract-list__row {
    cursor: pointer;
    transition: background 0.12s ease;
}
.contract-list__row:hover {
    background: rgba(79, 70, 229, 0.04);
}
.contract-list__link {
    font-weight: 600;
    color: var(--ppms-accent, #4f46e5);
    text-decoration: none;
}
.contract-list__link:hover {
    text-decoration: underline;
}
.contract-list__cell-muted {
    color: var(--ppms-muted, #64748b);
    font-size: 0.92em;
}
.contract-list__cell-num {
    text-align: right;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.contract-list__period {
    font-size: 0.92rem;
    white-space: nowrap;
}
.contract-list__pill {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.02em;
}
.contract-list__pill--draft {
    background: #f1f5f9;
    color: #475569;
}
.contract-list__pill--pending {
    background: #fef3c7;
    color: #b45309;
}
.contract-list__pill--active {
    background: #d1fae5;
    color: #047857;
}
.contract-list__pill--expired {
    background: #e2e8f0;
    color: #475569;
}
.contract-list__pill--terminated {
    background: #fee2e2;
    color: #b91c1c;
}
.contract-list__badge-soon {
    display: inline-block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #c2410c;
    background: #ffedd5;
    padding: 2px 8px;
    border-radius: 6px;
}

/* —— Footer / pagination —— */
.contract-list__footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 20px 18px;
    border-top: 1px solid var(--ppms-border, #e2e8f0);
    background: rgba(248, 250, 252, 0.6);
}
.contract-list__meta {
    margin: 0;
    font-size: 0.9rem;
    color: var(--ppms-muted, #64748b);
}
.contract-list__meta-sep {
    margin: 0 6px;
    opacity: 0.5;
}
.contract-list__pagination {
    display: flex;
    align-items: center;
    gap: 12px;
}
.contract-list__page-label {
    font-size: 0.9rem;
    font-variant-numeric: tabular-nums;
    color: var(--ppms-fg, #0f172a);
}

/* —— Modal —— */
.contract-modal__backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    z-index: 80;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: min(24px, 4vw);
    overflow: auto;
}
.contract-modal {
    width: min(920px, calc(100vw - 32px));
    margin-top: min(40px, 5vh);
    margin-bottom: 40px;
    padding: 0;
    display: flex;
    flex-direction: column;
    max-height: min(92vh, 900px);
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
}
.contract-modal__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 20px 22px 0;
    flex-shrink: 0;
}
.contract-modal__title {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
}
.contract-modal__close {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    margin: -6px -6px 0 0;
    border: none;
    border-radius: 8px;
    background: transparent;
    font-size: 1.5rem;
    line-height: 1;
    color: var(--ppms-muted, #64748b);
    cursor: pointer;
}
.contract-modal__close:hover {
    background: rgba(148, 163, 184, 0.2);
    color: var(--ppms-fg, #0f172a);
}
.contract-modal__form {
    display: flex;
    flex-direction: column;
    min-height: 0;
    flex: 1;
}
.contract-modal__body {
    padding: 12px 22px 8px;
    overflow-y: auto;
    overflow-x: hidden;
    flex: 1;
    min-height: 0;
}
.contract-modal__section {
    margin-bottom: 18px;
    padding: 16px 18px;
    border-radius: 12px;
    border: 1px solid var(--ppms-border, #e2e8f0);
    background: rgba(248, 250, 252, 0.65);
}
.contract-modal__section:last-child {
    margin-bottom: 8px;
}
.contract-modal__section-title {
    margin: 0 0 14px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--ppms-muted, #64748b);
}
.contract-modal__grid {
    display: grid;
    gap: 12px 16px;
}
.contract-modal__grid--2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
@media (max-width: 640px) {
    .contract-modal__grid--2 {
        grid-template-columns: 1fr;
    }
}
.contract-modal__field label {
    display: block;
    font-size: 0.82rem;
    font-weight: 600;
    margin-bottom: 6px;
    color: var(--ppms-fg, #0f172a);
}
.contract-modal__field--full {
    grid-column: 1 / -1;
}
.contract-modal__textarea {
    resize: vertical;
    min-height: 72px;
    max-height: 160px;
}
.contract-modal__footer {
    flex-shrink: 0;
    padding: 12px 22px 20px;
    border-top: 1px solid var(--ppms-border, #e2e8f0);
    background: rgba(255, 255, 255, 0.96);
}
.contract-modal__error {
    margin: 0 0 10px;
}
.contract-modal__actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    flex-wrap: wrap;
}
</style>
