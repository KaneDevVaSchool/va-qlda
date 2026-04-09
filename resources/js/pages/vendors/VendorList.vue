<template>
    <div class="ppms-page vm-page">
        <section class="ppms-card">
            <div class="vm-toolbar">
                <div>
                    <h2 class="vm-toolbar__title">{{ t('vendors.pageTitle') }}</h2>
                    <p class="ppms-muted ppms-mt-sm">{{ t('vendors.pageDescription') }}</p>
                </div>
                <div class="vm-toolbar__actions">
                    <button type="button" class="ppms-btn-ghost" :disabled="loading" @click="load">
                        {{ t('vendors.refresh') }}
                    </button>
                    <button type="button" class="ppms-btn-primary" @click="openCreate">{{ t('vendors.create') }}</button>
                </div>
            </div>

            <div class="vm-kind-tabs" role="tablist">
                <button
                    type="button"
                    role="tab"
                    class="vm-kind-tab"
                    :class="{ 'vm-kind-tab--active': filters.kind === 'active' }"
                    :aria-selected="filters.kind === 'active'"
                    @click="setKind('active')"
                >
                    {{ t('vendors.kindActive') }}
                </button>
                <button
                    type="button"
                    role="tab"
                    class="vm-kind-tab"
                    :class="{ 'vm-kind-tab--active': filters.kind === 'research' }"
                    :aria-selected="filters.kind === 'research'"
                    @click="setKind('research')"
                >
                    {{ t('vendors.kindResearch') }}
                </button>
            </div>

            <div class="vm-filters" role="search">
                <label class="ppms-field vm-filters__field">
                    <span>{{ t('common.search') }}</span>
                    <input v-model="filters.q" class="ppms-input" :placeholder="t('vendors.searchPlaceholder')" @keyup.enter="load" />
                </label>
                <label class="ppms-field vm-filters__field">
                    <span>{{ t('vendors.filterStatus') }}</span>
                    <select v-model="filters.status" class="ppms-input" @change="load">
                        <option value="">{{ t('contracts.allStatuses') }}</option>
                        <option v-for="s in statusOptions" :key="s" :value="s">{{ statusLabel(s) }}</option>
                    </select>
                </label>
                <label class="ppms-field vm-filters__field">
                    <span>{{ t('vendors.filterIndustry') }}</span>
                    <input v-model="filters.industry" class="ppms-input" @change="load" />
                </label>
                <label class="ppms-field vm-filters__field">
                    <span>{{ t('vendors.filterMinScore') }}</span>
                    <input v-model.number="filters.min_score" type="number" min="0" max="5" step="0.1" class="ppms-input" @change="load" />
                </label>
                <button type="button" class="ppms-btn-ghost" @click="resetFilters">{{ t('vendors.resetFilters') }}</button>
            </div>

            <div v-if="loading" class="ppms-loading-line" role="status">{{ t('common.loading') }}</div>
            <p v-else-if="err" class="ppms-error">{{ err }}</p>
            <div v-else class="ppms-table-scroll ppms-mt">
                <table class="ppms-table">
                    <thead>
                        <tr>
                            <th>{{ t('vendors.colName') }}</th>
                            <th>{{ t('vendors.colStatus') }}</th>
                            <th>{{ t('vendors.colIndustry') }}</th>
                            <th>{{ t('vendors.colScore') }}</th>
                            <th>{{ t('vendors.colReviews') }}</th>
                            <th>{{ t('vendors.colUpdated') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="v in rows" :key="v.id" class="vm-row" @click="goDetail(v.id)">
                            <td>
                                <button type="button" class="vm-link" @click.stop="goDetail(v.id)">{{ v.name }}</button>
                            </td>
                            <td>{{ statusLabel(v.status) }}</td>
                            <td>{{ v.industry || '—' }}</td>
                            <td>{{ v.vendor_score ?? '—' }}</td>
                            <td>{{ v.review_rating_avg ?? '—' }}</td>
                            <td>{{ formatDate(v.updated_at) }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!rows.length" class="ppms-muted ppms-mt">{{ t('vendors.empty') }}</p>
            </div>

            <div v-if="meta && meta.last_page > 1" class="vm-pagination">
                <button
                    type="button"
                    class="ppms-btn-ghost"
                    :disabled="meta.current_page <= 1 || loading"
                    @click="pageTo(meta.current_page - 1)"
                >
                    {{ t('vendors.paginationPrev') }}
                </button>
                <span class="ppms-muted">{{ meta.current_page }} / {{ meta.last_page }}</span>
                <button
                    type="button"
                    class="ppms-btn-ghost"
                    :disabled="meta.current_page >= meta.last_page || loading"
                    @click="pageTo(meta.current_page + 1)"
                >
                    {{ t('vendors.paginationNext') }}
                </button>
            </div>
        </section>

        <div v-if="createOpen" class="vm-modal-backdrop" @click.self="createOpen = false">
            <div class="vm-modal ppms-card" role="dialog" aria-modal="true" :aria-label="t('vendors.modalCreateTitle')">
                <h3>{{ t('vendors.modalCreateTitle') }}</h3>
                <form class="ppms-stack ppms-mt" @submit.prevent="createVendor">
                    <label class="ppms-field">
                        <span>{{ t('vendors.fieldName') }} *</span>
                        <input v-model="createForm.name" required class="ppms-input" />
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('vendors.colKind') }}</span>
                        <select v-model="createForm.kind" class="ppms-input" required>
                            <option value="active">{{ t('vendors.kindActive') }}</option>
                            <option value="research">{{ t('vendors.kindResearch') }}</option>
                        </select>
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('vendors.filterStatus') }}</span>
                        <select v-model="createForm.status" class="ppms-input" required>
                            <option v-for="s in createStatusOptions" :key="s" :value="s">{{ statusLabel(s) }}</option>
                        </select>
                    </label>
                    <p v-if="createErr" class="ppms-error">{{ createErr }}</p>
                    <div class="vm-modal__actions">
                        <button type="button" class="ppms-btn-ghost" @click="createOpen = false">{{ t('vendors.cancel') }}</button>
                        <button type="submit" class="ppms-btn-primary" :disabled="createSaving">{{ t('vendors.modalCreateSave') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { getApiErrorMessage } from '@/bootstrap';
import { ppmsToastSuccess } from '@/ppmsUi';

const { t } = useI18n();
const router = useRouter();

const loading = ref(true);
const err = ref('');
const rows = ref([]);
const meta = ref(null);
const page = ref(1);

const filters = reactive({
    kind: 'active',
    q: '',
    status: '',
    industry: '',
    min_score: '',
});

const createOpen = ref(false);
const createSaving = ref(false);
const createErr = ref('');
const createForm = reactive({
    name: '',
    kind: 'active',
    status: 'active',
});

const statusOptions = computed(() => {
    if (filters.kind === 'research') {
        return ['researching', 'shortlist', 'rejected'];
    }
    return ['active', 'inactive', 'blacklist'];
});

const createStatusOptions = computed(() => {
    if (createForm.kind === 'research') {
        return ['researching', 'shortlist', 'rejected'];
    }
    return ['active', 'inactive', 'blacklist'];
});

function setKind(k) {
    filters.kind = k;
    filters.status = '';
    page.value = 1;
    load();
}

function resetFilters() {
    filters.q = '';
    filters.status = '';
    filters.industry = '';
    filters.min_score = '';
    page.value = 1;
    load();
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

function formatDate(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(d);
}

function goDetail(id) {
    router.push({ name: 'vendor-detail', params: { id: String(id) } });
}

function openCreate() {
    createErr.value = '';
    createForm.name = '';
    createForm.kind = filters.kind === 'research' ? 'research' : 'active';
    createForm.status = createForm.kind === 'research' ? 'researching' : 'active';
    createOpen.value = true;
}

async function createVendor() {
    createSaving.value = true;
    createErr.value = '';
    try {
        const { data } = await axios.post('/api/vendors', {
            name: createForm.name,
            kind: createForm.kind,
            status: createForm.status,
        });
        createOpen.value = false;
        ppmsToastSuccess(t('vendors.saved'));
        const id = data?.data?.id ?? data?.id;
        if (id) {
            router.push({ name: 'vendor-detail', params: { id: String(id) } });
        } else {
            load();
        }
    } catch (e) {
        createErr.value = getApiErrorMessage(e);
    } finally {
        createSaving.value = false;
    }
}

function buildParams() {
    const p = {
        page: page.value,
        kind: filters.kind,
    };
    if (filters.q) p.q = filters.q;
    if (filters.status) p.status = filters.status;
    if (filters.industry) p.industry = filters.industry;
    if (filters.min_score !== '' && filters.min_score !== null) p.min_score = filters.min_score;
    return p;
}

async function load() {
    loading.value = true;
    err.value = '';
    try {
        const { data } = await axios.get('/api/vendors', { params: buildParams() });
        rows.value = data.data ?? [];
        meta.value = data.meta ?? null;
    } catch (e) {
        err.value = getApiErrorMessage(e, t('vendors.loadError'));
        rows.value = [];
        meta.value = null;
    } finally {
        loading.value = false;
    }
}

function pageTo(n) {
    page.value = n;
    load();
}

onMounted(load);
</script>

<style scoped>
.vm-toolbar {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
}
.vm-toolbar__title {
    margin: 0;
    font-size: 1.25rem;
}
.vm-toolbar__actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.vm-kind-tabs {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}
.vm-kind-tab {
    border: 1px solid var(--ppms-border, #e2e6ea);
    background: #fff;
    padding: 0.4rem 0.9rem;
    border-radius: 999px;
    cursor: pointer;
    font-size: 0.9rem;
}
.vm-kind-tab--active {
    background: #1e3a5f;
    color: #fff;
    border-color: #1e3a5f;
}
.vm-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: flex-end;
    margin-top: 1rem;
}
.vm-filters__field {
    min-width: 160px;
}
.vm-row {
    cursor: pointer;
}
.vm-row:hover {
    background: rgba(0, 0, 0, 0.03);
}
.vm-link {
    background: none;
    border: none;
    padding: 0;
    color: #1d4ed8;
    text-decoration: underline;
    cursor: pointer;
    font: inherit;
}
.vm-pagination {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 1rem;
}
.vm-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 80;
    padding: 1rem;
}
.vm-modal {
    max-width: 420px;
    width: 100%;
    padding: 1.25rem;
}
.vm-modal__actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 0.5rem;
}
</style>
