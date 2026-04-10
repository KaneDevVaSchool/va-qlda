<template>
    <div class="ppms-page vm-page">
        <section class="ppms-card">
            <div class="vm-toolbar">
                <div class="vm-toolbar__head">
                    <h1 class="vm-toolbar__title">{{ t('vendors.pageTitle') }}</h1>
                    <p class="vm-toolbar__desc">{{ t('vendors.pageDescription') }}</p>
                </div>
                <div class="vm-toolbar__actions">
                    <button type="button" class="vm-toolbar__btn vm-toolbar__btn--ghost" :disabled="loading" @click="load">
                        {{ t('vendors.refresh') }}
                    </button>
                    <button type="button" class="vm-toolbar__btn vm-toolbar__btn--primary" @click="openCreate">
                        {{ t('vendors.create') }}
                    </button>
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

            <div class="vm-filters-panel" role="search" :aria-label="t('vendors.filtersSectionTitle')">
                <h2 class="vm-filters-panel__title">{{ t('vendors.filtersSectionTitle') }}</h2>
                <div class="vm-filters-grid">
                    <label class="ppms-field ppms-field--compact vm-filters__field">
                        <span>{{ t('common.search') }}</span>
                        <input
                            v-model="filters.q"
                            type="search"
                            class="ppms-input"
                            autocomplete="off"
                            :placeholder="t('vendors.searchPlaceholder')"
                        />
                    </label>
                    <label class="ppms-field ppms-field--compact vm-filters__field vm-filters__field--offerings">
                        <span>{{ t('vendors.filterOfferings') }}</span>
                        <input
                            v-model="filters.q_offerings"
                            type="search"
                            class="ppms-input"
                            autocomplete="off"
                            :placeholder="t('vendors.filterOfferingsPlaceholder')"
                            :title="t('vendors.filterOfferingsHint')"
                        />
                    </label>
                    <label class="ppms-field ppms-field--compact vm-filters__field">
                        <span>{{ t('vendors.filterStatus') }}</span>
                        <select v-model="filters.status" class="ppms-input" @change="load">
                            <option value="">{{ t('vendors.selectStatusPlaceholder') }}</option>
                            <option v-for="s in statusOptions" :key="s" :value="s">{{ statusLabel(s) }}</option>
                        </select>
                    </label>
                    <label class="ppms-field ppms-field--compact vm-filters__field">
                        <span>{{ t('vendors.filterIndustry') }}</span>
                        <input
                            v-model="filters.industry"
                            class="ppms-input"
                            autocomplete="off"
                            :placeholder="t('vendors.industryPlaceholder')"
                        />
                    </label>
                    <label class="ppms-field ppms-field--compact vm-filters__field">
                        <span>{{ t('vendors.filterMinScore') }}</span>
                        <input
                            v-model.number="filters.min_score"
                            type="number"
                            min="0"
                            max="5"
                            step="0.1"
                            class="ppms-input"
                            inputmode="decimal"
                            :placeholder="t('vendors.minScorePlaceholder')"
                        />
                    </label>
                    <div class="vm-filters-actions">
                        <button type="button" class="ppms-btn-ghost" @click="resetFilters">{{ t('vendors.resetFilters') }}</button>
                    </div>
                </div>
            </div>

            <div v-if="loading" class="ppms-loading-line" role="status">{{ t('common.loading') }}</div>
            <p v-else-if="err" class="ppms-error">{{ err }}</p>
            <div v-else class="vm-list-cards ppms-mt">
                <h2 class="ppms-sr-only">{{ t('vendors.listCardsSection') }}</h2>
                <ul class="vm-crm-grid" role="list">
                    <li v-for="v in rows" :key="v.id" class="vm-crm-grid__cell">
                        <article
                            class="vm-crm-card"
                            :class="{
                                'vm-crm-card--research': v.kind === 'research',
                                'vm-crm-card--active': v.kind !== 'research',
                            }"
                            @click="goDetail(v.id)"
                        >
                            <div class="vm-crm-card__accent" aria-hidden="true" />
                            <header class="vm-crm-card__top">
                                <div class="vm-crm-card__badges">
                                    <span class="vm-crm-card__kind">{{ kindLabel(v.kind) }}</span>
                                    <span
                                        class="vm-crm-card__status"
                                        :class="{ 'vm-crm-card__status--shortlist': v.status === 'shortlist' }"
                                        >{{ statusLabel(v.status) }}</span
                                    >
                                </div>
                                <h3 class="vm-crm-card__title">{{ v.name }}</h3>
                            </header>
                            <p v-if="vendorMetaLine(v)" class="vm-crm-card__meta-line">{{ vendorMetaLine(v) }}</p>
                            <ul
                                v-if="vendorDeptChips(v).length"
                                class="vm-crm-card__depts"
                                :aria-label="t('vendors.cardDepartmentsAria')"
                            >
                                <li v-for="d in vendorDeptChips(v)" :key="d.id" class="vm-crm-card__dept-chip">
                                    {{ d.name }}
                                </li>
                            </ul>
                            <div class="vm-crm-card__stats">
                                <span
                                    v-if="hasVendorScore(v)"
                                    class="vm-crm-card__stat vm-crm-card__stat--score"
                                    :title="t('vendors.vendorScore')"
                                >
                                    <span class="vm-crm-card__stat-k">{{ t('vendors.colScore') }}</span>
                                    <span class="vm-crm-card__stat-v">{{ formatScore(v.vendor_score) }}</span>
                                </span>
                                <span
                                    v-if="hasReviewAvg(v)"
                                    class="vm-crm-card__stat vm-crm-card__stat--review"
                                    :title="t('vendors.colReviews')"
                                >
                                    <span class="vm-crm-card__stat-k">{{ t('vendors.colReviews') }}</span>
                                    <span class="vm-crm-card__stat-v">{{ formatScore(v.review_rating_avg) }}</span>
                                </span>
                                <span v-if="v.updated_at" class="vm-crm-card__stat vm-crm-card__stat--muted">
                                    <span class="vm-crm-card__stat-k">{{ t('vendors.colUpdated') }}</span>
                                    <span class="vm-crm-card__stat-v">{{ formatCardDate(v.updated_at) }}</span>
                                </span>
                            </div>
                            <p v-if="vendorFeaturesLine(v)" class="vm-crm-card__block vm-crm-card__block--features">
                                <span class="vm-crm-card__block-k">{{ t('vendors.cardFeatures') }}</span>
                                <span class="vm-crm-card__block-v">{{ vendorFeaturesLine(v) }}</span>
                            </p>
                            <p v-if="vendorServicesLine(v)" class="vm-crm-card__block vm-crm-card__block--services">
                                <span class="vm-crm-card__block-k">{{ t('vendors.cardServices') }}</span>
                                <span class="vm-crm-card__block-v">{{ vendorServicesLine(v) }}</span>
                            </p>
                            <div class="vm-crm-card__footer" @click.stop>
                                <a
                                    v-if="normalizeHttpUrl(v.website)"
                                    class="vm-crm-card__foot-link"
                                    :href="normalizeHttpUrl(v.website)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    >{{ t('vendors.linkWebsite') }}</a
                                >
                                <router-link
                                    class="vm-crm-card__foot-link vm-crm-card__foot-link--primary"
                                    :to="{ name: 'vendor-detail', params: { id: String(v.id) } }"
                                    @click.stop
                                    >{{ t('vendors.cardOpenDetail') }}</router-link
                                >
                            </div>
                        </article>
                    </li>
                </ul>
                <p v-if="!rows.length" class="ppms-muted ppms-mt">{{ t('vendors.empty') }}</p>
            </div>

            <div v-if="meta && meta.total > 0" class="vm-list-pager">
                <p class="vm-list-pager__range">{{ paginationRangeText }}</p>
                <div class="vm-list-pager__row">
                    <label class="vm-list-pager__per">
                        <span class="vm-list-pager__per-lbl">{{ t('vendors.paginationPerPage') }}</span>
                        <select
                            v-model.number="perPage"
                            class="vm-list-pager__select"
                            :disabled="loading"
                            @change="onPerPageChange"
                        >
                            <option v-for="n in perPageOptions" :key="'vpp-' + n" :value="n">{{ n }}</option>
                        </select>
                    </label>
                    <nav
                        v-if="meta.last_page > 1"
                        class="vm-list-pager__nav"
                        :aria-label="t('vendors.listPagerAria')"
                    >
                        <button
                            type="button"
                            class="vm-pg-btn"
                            :disabled="meta.current_page <= 1 || loading"
                            @click="pageTo(meta.current_page - 1)"
                        >
                            {{ t('vendors.paginationPrev') }}
                        </button>
                        <span class="vm-pg-meta">{{ meta.current_page }} / {{ meta.last_page }}</span>
                        <button
                            type="button"
                            class="vm-pg-btn"
                            :disabled="meta.current_page >= meta.last_page || loading"
                            @click="pageTo(meta.current_page + 1)"
                        >
                            {{ t('vendors.paginationNext') }}
                        </button>
                    </nav>
                </div>
            </div>
        </section>

        <div v-if="createOpen" class="vm-modal-backdrop" @click.self="createOpen = false">
            <div class="vm-modal vm-modal--wide ppms-card" role="dialog" aria-modal="true" :aria-labelledby="createModalTitleId">
                <h3 :id="createModalTitleId" class="vm-modal__heading">{{ t('vendors.modalCreateTitle') }}</h3>
                <p class="vm-modal__hint">{{ t('vendors.modalCreateHint') }}</p>
                <form class="vm-modal-form" @submit.prevent="createVendor">
                    <label class="ppms-field vm-modal-form__full">
                        <span>{{ t('vendors.fieldName') }} <abbr class="vm-req" :title="t('vendors.requiredField')">*</abbr></span>
                        <input
                            id="vm-create-name"
                            v-model="createForm.name"
                            required
                            class="ppms-input"
                            maxlength="255"
                            autocomplete="organization"
                            :placeholder="t('vendors.modalCreateNamePlaceholder')"
                        />
                    </label>
                    <div class="vm-modal-form__row">
                        <label class="ppms-field">
                            <span>{{ t('vendors.colKind') }}</span>
                            <select id="vm-create-kind" v-model="createForm.kind" class="ppms-input" required>
                                <option value="active">{{ t('vendors.kindActive') }}</option>
                                <option value="research">{{ t('vendors.kindResearch') }}</option>
                            </select>
                        </label>
                        <label class="ppms-field">
                            <span>{{ t('vendors.filterStatus') }}</span>
                            <select id="vm-create-status" v-model="createForm.status" class="ppms-input" required>
                                <option v-for="s in createStatusOptions" :key="s" :value="s">{{ statusLabel(s) }}</option>
                            </select>
                        </label>
                    </div>
                    <p v-if="createErr" class="ppms-error vm-modal__error" role="alert">{{ createErr }}</p>
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
import { computed, onMounted, reactive, ref, watch } from 'vue';
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
const perPage = ref(20);
const perPageOptions = [12, 20, 40, 60];

const paginationRangeText = computed(() => {
    const m = meta.value;
    if (!m || m.total == null) {
        return '';
    }
    const total = Number(m.total);
    if (!total) {
        return '';
    }
    const from = m.from ?? 0;
    const to = m.to ?? 0;
    return t('vendors.paginationRangeList', { from, to, total });
});

const filters = reactive({
    kind: 'active',
    q: '',
    q_offerings: '',
    status: '',
    industry: '',
    min_score: '',
});

const createOpen = ref(false);
const createSaving = ref(false);
const createErr = ref('');
const createModalTitleId = 'vm-modal-create-title';

const createForm = reactive({
    name: '',
    kind: 'active',
    status: 'active',
});

watch(
    () => createForm.kind,
    (k) => {
        if (k === 'research') {
            if (!['researching', 'shortlist', 'rejected'].includes(createForm.status)) {
                createForm.status = 'researching';
            }
        } else if (!['active', 'inactive', 'blacklist'].includes(createForm.status)) {
            createForm.status = 'active';
        }
    },
);

/** Text/number filters: reload without pressing Enter */
let vendorFilterDebounce = null;
function scheduleVendorFilterReload() {
    clearTimeout(vendorFilterDebounce);
    vendorFilterDebounce = setTimeout(() => {
        page.value = 1;
        load();
    }, 350);
}
watch(
    () => [filters.q, filters.q_offerings, filters.industry, filters.min_score],
    () => {
        scheduleVendorFilterReload();
    },
);

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
    clearTimeout(vendorFilterDebounce);
    filters.kind = k;
    filters.status = '';
    page.value = 1;
    load();
}

function resetFilters() {
    clearTimeout(vendorFilterDebounce);
    filters.q = '';
    filters.q_offerings = '';
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

function kindLabel(k) {
    return k === 'research' ? t('vendors.kindResearch') : t('vendors.kindActive');
}

function vendorMetaLine(v) {
    const c = (v.country || '').trim();
    const i = (v.industry || '').trim();
    const parts = [];
    if (c) {
        parts.push(c);
    }
    if (i) {
        parts.push(i);
    }
    return parts.join(' · ');
}

function vendorDeptChips(v) {
    const d = v.departments;
    if (!Array.isArray(d) || !d.length) {
        return [];
    }
    return d.slice(0, 4);
}

function hasVendorScore(v) {
    return v.vendor_score != null && String(v.vendor_score).trim() !== '';
}

function hasReviewAvg(v) {
    return v.review_rating_avg != null && String(v.review_rating_avg).trim() !== '';
}

function formatScore(x) {
    if (x == null || x === '') {
        return '';
    }
    const n = Number(x);
    if (Number.isFinite(n)) {
        return String(n);
    }
    return String(x);
}

function formatCardDate(iso) {
    if (!iso) {
        return '';
    }
    try {
        const d = new Date(iso);
        if (Number.isNaN(d.getTime())) {
            return '';
        }
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    } catch {
        return '';
    }
}

function vendorServicesLine(v) {
    const raw = (v.services_offered || '').trim();
    if (!raw) {
        return '';
    }
    const firstLine = raw.split(/\n/)[0];
    const bits = firstLine
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean)
        .slice(0, 6);
    if (bits.length) {
        return bits.join(', ');
    }
    return firstLine.length > 140 ? `${firstLine.slice(0, 137)}…` : firstLine;
}

function normalizeHttpUrl(url) {
    if (!url || typeof url !== 'string') {
        return '';
    }
    const u = url.trim();
    if (!u) {
        return '';
    }
    return /^https?:\/\//i.test(u) ? u : `https://${u}`;
}

function vendorFeaturesLine(v) {
    const raw = (v.main_products || '').trim();
    if (!raw) {
        return '';
    }
    const firstLine = raw.split(/\n/)[0];
    const bits = firstLine
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean)
        .slice(0, 8);
    if (bits.length) {
        return bits.join(', ');
    }
    return firstLine.length > 180 ? `${firstLine.slice(0, 177)}…` : firstLine;
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
        per_page: perPage.value,
    };
    if (filters.q) p.q = filters.q;
    if (filters.q_offerings && String(filters.q_offerings).trim()) p.q_offerings = String(filters.q_offerings).trim();
    if (filters.status) p.status = filters.status;
    if (filters.industry) p.industry = filters.industry;
    if (filters.min_score !== '' && filters.min_score !== null) p.min_score = filters.min_score;
    return p;
}

function onPerPageChange() {
    clearTimeout(vendorFilterDebounce);
    page.value = 1;
    load();
}

async function load() {
    clearTimeout(vendorFilterDebounce);
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
    clearTimeout(vendorFilterDebounce);
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
    align-items: flex-start;
    gap: 1rem 1.25rem;
    padding: 1.15rem 1.2rem 1.2rem;
    border-radius: 14px;
    border: 1px solid rgba(15, 23, 42, 0.07);
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 42%, #f1f5f9 100%);
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.85) inset,
        0 10px 28px rgba(15, 23, 42, 0.05);
    position: relative;
    overflow: hidden;
}
.vm-toolbar::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    border-radius: 14px 0 0 14px;
    background: linear-gradient(180deg, #2563eb, #4f46e5, #7c3aed);
    pointer-events: none;
}
.vm-toolbar__head {
    min-width: 0;
    flex: 1 1 14rem;
    padding-left: 0.35rem;
}
.vm-toolbar__title {
    margin: 0;
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--ppms-text, #0f172a);
    line-height: 1.25;
}
.vm-toolbar__desc {
    margin: 0.35rem 0 0;
    font-size: 0.875rem;
    color: var(--ppms-muted, #64748b);
    line-height: 1.5;
    max-width: 44rem;
}
.vm-toolbar__actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    flex-shrink: 0;
    padding: 0.15rem 0 0;
}
.vm-toolbar__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.5rem;
    padding: 0.45rem 1rem;
    font-size: 0.875rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    border-radius: 10px;
    cursor: pointer;
    border: 1px solid transparent;
    transition:
        background 0.15s ease,
        border-color 0.15s ease,
        box-shadow 0.15s ease,
        color 0.15s ease,
        transform 0.12s ease;
}
.vm-toolbar__btn:focus-visible {
    outline: 2px solid #6366f1;
    outline-offset: 2px;
}
.vm-toolbar__btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}
.vm-toolbar__btn--ghost {
    background: rgba(255, 255, 255, 0.85);
    border-color: rgba(15, 23, 42, 0.12);
    color: #334155;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.vm-toolbar__btn--ghost:hover:not(:disabled) {
    background: #fff;
    border-color: rgba(37, 99, 235, 0.35);
    color: #1d4ed8;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);
}
.vm-toolbar__btn--primary {
    background: linear-gradient(135deg, #2563eb 0%, #4f46e5 55%, #7c3aed 100%);
    color: #fff;
    border-color: rgba(37, 99, 235, 0.35);
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.2) inset,
        0 8px 20px rgba(79, 70, 229, 0.28);
}
.vm-toolbar__btn--primary:hover:not(:disabled) {
    filter: brightness(1.05);
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.22) inset,
        0 10px 26px rgba(79, 70, 229, 0.35);
    transform: translateY(-1px);
}
@media (max-width: 560px) {
    .vm-toolbar {
        padding: 1rem 1rem 1.1rem;
    }
    .vm-toolbar__actions {
        width: 100%;
        justify-content: stretch;
    }
    .vm-toolbar__btn {
        flex: 1 1 auto;
        min-width: 0;
    }
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
.vm-filters-panel {
    margin-top: 1rem;
    padding: 1rem 1.1rem;
    border: 1px solid var(--ppms-border, #e2e6ea);
    border-radius: 10px;
    background: var(--ppms-bg-subtle, rgba(248, 250, 252, 0.9));
}
.vm-filters-panel__title {
    margin: 0 0 0.65rem;
    font-size: 0.8125rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--ppms-muted, #64748b);
}
.vm-filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(11.5rem, 1fr));
    gap: 0.65rem 1rem;
    align-items: end;
}
.vm-filters__field {
    margin-bottom: 0;
    min-width: 0;
}
.vm-filters__field .ppms-input {
    width: 100%;
    min-height: 2.5rem;
    box-sizing: border-box;
}
.vm-filters__field--offerings {
    grid-column: 1 / -1;
}
.vm-filters-actions {
    display: flex;
    align-items: flex-end;
    padding-bottom: 0.1rem;
}
@media (min-width: 900px) {
    .vm-filters-grid {
        grid-template-columns: minmax(11rem, 1.5fr) minmax(9rem, 1fr) minmax(9rem, 1fr) minmax(7rem, 0.85fr) auto;
        align-items: end;
    }
    .vm-filters-actions {
        justify-self: end;
        white-space: nowrap;
    }
}
.vm-list-cards {
    width: 100%;
}
.vm-crm-grid {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}
@media (min-width: 640px) {
    .vm-crm-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (min-width: 1100px) {
    .vm-crm-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
.vm-crm-card {
    position: relative;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 16px;
    padding: 0;
    background: linear-gradient(165deg, #ffffff 0%, #f8fafc 100%);
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.9) inset,
        0 10px 28px rgba(15, 23, 42, 0.06),
        0 2px 6px rgba(15, 23, 42, 0.04);
    cursor: pointer;
    transition:
        box-shadow 0.18s ease,
        border-color 0.18s ease,
        transform 0.18s ease;
    min-height: 100%;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 0;
    overflow: hidden;
}
.vm-crm-card:hover {
    border-color: rgba(99, 102, 241, 0.35);
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.95) inset,
        0 16px 40px rgba(15, 23, 42, 0.1);
    transform: translateY(-2px);
}
.vm-crm-card:focus-visible {
    outline: 2px solid #6366f1;
    outline-offset: 2px;
}
.vm-crm-card__accent {
    height: 3px;
    width: 100%;
    flex-shrink: 0;
}
.vm-crm-card--active .vm-crm-card__accent {
    background: linear-gradient(90deg, #2563eb, #38bdf8, #0ea5e9);
}
.vm-crm-card--research .vm-crm-card__accent {
    background: linear-gradient(90deg, #7c3aed, #a78bfa, #c084fc);
}
.vm-crm-card__top {
    padding: 1rem 1.15rem 0.65rem;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}
.vm-crm-card__badges {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem 0.45rem;
}
.vm-crm-card__kind {
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 0.2rem 0.5rem;
    border-radius: 999px;
    background: rgba(241, 245, 249, 0.95);
    border: 1px solid #e2e8f0;
    color: #475569;
}
.vm-crm-card--research .vm-crm-card__kind {
    background: rgba(245, 243, 255, 0.95);
    border-color: #ddd6fe;
    color: #5b21b6;
}
.vm-crm-card__title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    line-height: 1.28;
    letter-spacing: -0.02em;
    color: var(--ppms-text, #0f172a);
    min-width: 0;
}
.vm-crm-card__status {
    flex-shrink: 0;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 0.2rem 0.5rem;
    border-radius: 999px;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}
.vm-crm-card__status--shortlist {
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
    color: #3730a3;
    border-color: #c7d2fe;
}
.vm-crm-card__meta-line {
    margin: 0;
    padding: 0 1.15rem;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: #64748b;
}
.vm-crm-card__depts {
    list-style: none;
    margin: 0;
    padding: 0 1.15rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}
.vm-crm-card__dept-chip {
    font-size: 0.72rem;
    font-weight: 600;
    padding: 0.2rem 0.45rem;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(15, 23, 42, 0.08);
    color: #475569;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.vm-crm-card__stats {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem 0.65rem;
    padding: 0.65rem 1.15rem 0.5rem;
    border-top: 1px solid rgba(15, 23, 42, 0.06);
    margin-top: 0.35rem;
    background: rgba(255, 255, 255, 0.55);
}
.vm-crm-card__stat {
    display: inline-flex;
    align-items: baseline;
    gap: 0.3rem;
    font-size: 0.78rem;
    line-height: 1.3;
}
.vm-crm-card__stat-k {
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-size: 0.65rem;
}
.vm-crm-card__stat-v {
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    color: #0f172a;
}
.vm-crm-card__stat--score .vm-crm-card__stat-v {
    color: #1d4ed8;
}
.vm-crm-card__stat--review .vm-crm-card__stat-v {
    color: #b45309;
}
.vm-crm-card__stat--muted .vm-crm-card__stat-v {
    font-weight: 600;
    color: #64748b;
}
.vm-crm-card__block {
    margin: 0;
    padding: 0.55rem 1.15rem 0;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}
.vm-crm-card__block-k {
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #64748b;
}
.vm-crm-card__block-v {
    font-size: 0.8125rem;
    line-height: 1.5;
    color: #334155;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.vm-crm-card__block--services .vm-crm-card__block-k {
    color: #6d28d9;
}
.vm-crm-card__footer {
    margin-top: auto;
    padding: 0.75rem 1.15rem 1rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem 0.75rem;
    border-top: 1px dashed rgba(15, 23, 42, 0.1);
    background: rgba(248, 250, 252, 0.85);
}
.vm-crm-card__foot-link {
    font-size: 0.8125rem;
    font-weight: 700;
    text-decoration: none;
    color: #2563eb;
    padding: 0.35rem 0.6rem;
    border-radius: 8px;
    border: 1px solid transparent;
    transition:
        background 0.15s ease,
        border-color 0.15s ease;
}
.vm-crm-card__foot-link:hover {
    background: rgba(37, 99, 235, 0.08);
    border-color: rgba(37, 99, 235, 0.2);
}
.vm-crm-card__foot-link--primary {
    color: #4f46e5;
    margin-left: auto;
}
.vm-crm-card__foot-link--primary:hover {
    background: rgba(79, 70, 229, 0.08);
    border-color: rgba(79, 70, 229, 0.22);
}
.vm-list-pager {
    margin-top: 1.25rem;
    padding: 0.85rem 1rem;
    border-radius: 12px;
    border: 1px solid var(--ppms-border, #e2e8f0);
    background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}
.vm-list-pager__range {
    margin: 0;
    font-size: 0.8125rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: #475569;
}
.vm-list-pager__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem 1rem;
}
.vm-list-pager__per {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    margin: 0;
    font-size: 0.78rem;
    font-weight: 600;
    color: #475569;
}
.vm-list-pager__per-lbl {
    white-space: nowrap;
}
.vm-list-pager__select {
    appearance: none;
    cursor: pointer;
    padding: 0.35rem 1.75rem 0.35rem 0.55rem;
    font: inherit;
    font-size: 0.8125rem;
    font-weight: 700;
    color: #0f172a;
    border-radius: 9px;
    border: 1px solid rgba(15, 23, 42, 0.12);
    background:
        #fff
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E")
        no-repeat right 0.45rem center;
    background-size: 12px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
}
.vm-list-pager__select:hover:not(:disabled) {
    border-color: rgba(37, 99, 235, 0.35);
}
.vm-list-pager__select:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
.vm-list-pager__nav {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
}
.vm-pg-btn {
    cursor: pointer;
    padding: 0.4rem 0.85rem;
    font: inherit;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    border-radius: 999px;
    border: 1px solid rgba(15, 23, 42, 0.12);
    background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
    color: #334155;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease,
        color 0.15s ease;
}
.vm-pg-btn:hover:not(:disabled) {
    border-color: rgba(37, 99, 235, 0.4);
    color: #1d4ed8;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.12);
}
.vm-pg-btn:disabled {
    cursor: not-allowed;
    opacity: 0.42;
}
.vm-pg-meta {
    font-size: 0.78rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: #64748b;
    padding: 0 0.15rem;
}
@media (max-width: 520px) {
    .vm-list-pager__row {
        flex-direction: column;
        align-items: stretch;
    }
    .vm-list-pager__nav {
        justify-content: flex-end;
    }
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
    padding: 1.35rem 1.5rem;
    max-height: min(90vh, 720px);
    overflow-y: auto;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
}
.vm-modal--wide {
    max-width: min(36rem, calc(100vw - 2rem));
}
.vm-modal__heading {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--ppms-text, #0f172a);
}
.vm-modal__hint {
    margin: 0.45rem 0 0;
    font-size: 0.875rem;
    color: var(--ppms-muted, #64748b);
    line-height: 1.45;
}
.vm-modal-form {
    margin-top: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0;
}
.vm-modal-form .ppms-field {
    margin-bottom: 1rem;
}
.vm-modal-form__full .ppms-input {
    width: 100%;
    min-height: 2.65rem;
    box-sizing: border-box;
}
.vm-modal-form__row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0 1rem;
}
@media (min-width: 480px) {
    .vm-modal-form__row {
        grid-template-columns: 1fr 1fr;
    }
}
.vm-req {
    text-decoration: none;
    font-weight: 700;
    color: var(--ppms-danger, #b91c1c);
    cursor: help;
}
.vm-modal__error {
    margin: 0 0 0.75rem;
}
.vm-modal__actions {
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.25rem;
    padding-top: 0.5rem;
    border-top: 1px solid var(--ppms-border, #e2e6ea);
}
</style>
