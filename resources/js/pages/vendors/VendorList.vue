<template>
    <div class="ppms-page vm-page">
        <section class="ppms-card">
            <div class="vm-toolbar">
                <div class="vm-toolbar__head">
                    <h1 class="vm-toolbar__title">{{ t('vendors.pageTitle') }}</h1>
                    <p class="vm-toolbar__desc">{{ t('vendors.pageDescription') }}</p>
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
                            @keyup.enter="load"
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
                            @change="load"
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
                            @change="load"
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
                        <article class="vm-crm-card" @click="goDetail(v.id)">
                            <header class="vm-crm-card__head">
                                <h3 class="vm-crm-card__title">{{ v.name }}</h3>
                                <span
                                    class="vm-crm-card__status"
                                    :class="{ 'vm-crm-card__status--shortlist': v.status === 'shortlist' }"
                                    >{{ statusLabel(v.status) }}</span
                                >
                            </header>
                            <p v-if="vendorMetaLine(v)" class="vm-crm-card__meta">{{ vendorMetaLine(v) }}</p>
                            <p v-if="vendorPriceLine(v)" class="vm-crm-card__price">{{ vendorPriceLine(v) }}</p>
                            <p v-if="vendorFeaturesLine(v)" class="vm-crm-card__features">
                                <span class="vm-crm-card__features-k">{{ t('vendors.cardFeatures') }}:</span>
                                {{ vendorFeaturesLine(v) }}
                            </p>
                            <div v-if="v.website" class="vm-crm-card__links" @click.stop>
                                <a
                                    class="vm-crm-card__link"
                                    :href="normalizeHttpUrl(v.website)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    >{{ t('vendors.linkWebsite') }}</a
                                >
                                <a
                                    class="vm-crm-card__link"
                                    :href="vendorPathUrl(v.website, '/pricing')"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    >{{ t('vendors.linkPricing') }}</a
                                >
                                <a
                                    class="vm-crm-card__link"
                                    :href="vendorPathUrl(v.website, '/demo')"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    >{{ t('vendors.linkDemo') }}</a
                                >
                                <a
                                    class="vm-crm-card__link"
                                    :href="vendorPathUrl(v.website, '/docs')"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    >{{ t('vendors.linkDocs') }}</a
                                >
                                <router-link
                                    class="vm-crm-card__link vm-crm-card__link--int"
                                    :to="{ name: 'vendor-detail', params: { id: String(v.id) } }"
                                    @click.stop
                                    >{{ t('vendors.linkReview') }}</router-link
                                >
                            </div>
                            <p v-if="vendorNoteLine(v)" class="vm-crm-card__note">
                                <span class="vm-crm-card__note-k">{{ t('vendors.cardNote') }}:</span>
                                {{ vendorNoteLine(v) }}
                            </p>
                            <footer class="vm-crm-card__foot">
                                <span v-if="v.vendor_score != null && v.vendor_score !== ''" class="vm-crm-card__foot-item">{{
                                    t('vendors.cardFooterScore', { score: v.vendor_score })
                                }}</span>
                                <span v-if="v.review_rating_avg != null && v.review_rating_avg !== ''" class="vm-crm-card__foot-item">{{
                                    t('vendors.cardFooterReviews', { n: v.review_rating_avg })
                                }}</span>
                                <span class="vm-crm-card__foot-item vm-crm-card__foot-item--muted">{{
                                    t('vendors.cardFooterUpdated', { date: formatDate(v.updated_at) })
                                }}</span>
                            </footer>
                        </article>
                    </li>
                </ul>
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

const { t, locale } = useI18n();
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

function vendorOriginBase(url) {
    try {
        return new URL(normalizeHttpUrl(url)).origin;
    } catch {
        return '';
    }
}

function vendorPathUrl(url, path) {
    const o = vendorOriginBase(url);
    if (!o) {
        return '#';
    }
    const p = path.startsWith('/') ? path : `/${path}`;
    return `${o}${p}`;
}

function vendorMetaLine(v) {
    const parts = [];
    if (v.country && String(v.country).trim()) {
        parts.push(String(v.country).trim());
    }
    if (v.industry && String(v.industry).trim()) {
        String(v.industry)
            .split(/[,|]/)
            .map((s) => s.trim())
            .filter(Boolean)
            .forEach((seg) => parts.push(seg));
    }
    const seen = new Set();
    const uniq = [];
    for (const p of parts) {
        if (!seen.has(p)) {
            seen.add(p);
            uniq.push(p);
        }
    }
    return uniq.length ? uniq.join(' | ') : '';
}

function vendorPriceLine(v) {
    const ref = v.reference_price != null && v.reference_price !== '' ? Number(v.reference_price) : null;
    const est = v.estimated_cost != null && v.estimated_cost !== '' ? Number(v.estimated_cost) : null;
    if ((ref == null || Number.isNaN(ref)) && (est == null || Number.isNaN(est))) {
        return '';
    }
    const a = !Number.isNaN(ref) && ref != null ? ref : est;
    const b = !Number.isNaN(est) && est != null ? est : ref;
    const loc = locale.value === 'vi' ? 'vi-VN' : undefined;
    const fmt = (n) =>
        new Intl.NumberFormat(loc, { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(n);
    if (a != null && b != null && a !== b) {
        const lo = Math.min(a, b);
        const hi = Math.max(a, b);
        return `💰 ${fmt(lo)} – ${fmt(hi)}`;
    }
    const one = a ?? b;
    return `💰 ${fmt(one)}`;
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

function vendorNoteLine(v) {
    const raw = (v.pros || v.research_note || '').trim();
    if (!raw) {
        return '';
    }
    const line = raw.split(/\n/)[0];
    return line.length > 220 ? `${line.slice(0, 217)}…` : line;
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
.vm-toolbar__head {
    min-width: 0;
    flex: 1 1 12rem;
}
.vm-toolbar__title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--ppms-text, #0f172a);
    line-height: 1.3;
}
.vm-toolbar__desc {
    margin: 0.35rem 0 0;
    font-size: 0.875rem;
    color: var(--ppms-muted, #64748b);
    line-height: 1.45;
    max-width: 42rem;
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
    border: 1px solid var(--ppms-border, #e2e8f0);
    border-radius: 12px;
    padding: 1rem 1.1rem;
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    cursor: pointer;
    transition:
        box-shadow 0.15s ease,
        border-color 0.15s ease;
    min-height: 100%;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}
.vm-crm-card:hover {
    border-color: #c7d2fe;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
}
.vm-crm-card__head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
}
.vm-crm-card__title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    line-height: 1.25;
    color: var(--ppms-text, #0f172a);
    flex: 1 1 auto;
    min-width: 0;
}
.vm-crm-card__status {
    flex-shrink: 0;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 0.2rem 0.45rem;
    border-radius: 6px;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}
.vm-crm-card__status--shortlist {
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
    color: #3730a3;
    border-color: #c7d2fe;
}
.vm-crm-card__meta,
.vm-crm-card__price,
.vm-crm-card__features {
    margin: 0;
    font-size: 0.8125rem;
    line-height: 1.45;
}
.vm-crm-card__meta {
    color: var(--ppms-muted, #64748b);
}
.vm-crm-card__price {
    font-weight: 600;
    color: var(--ppms-text, #0f172a);
}
.vm-crm-card__features {
    color: var(--ppms-text, #334155);
}
.vm-crm-card__features-k {
    font-weight: 700;
    color: var(--ppms-text-muted, #475569);
    margin-right: 0.25rem;
}
.vm-crm-card__links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem 0.6rem;
    margin-top: 0.15rem;
}
.vm-crm-card__link {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--ppms-primary, #2563eb);
    text-decoration: none;
    border-bottom: 1px solid transparent;
}
.vm-crm-card__link:hover {
    border-bottom-color: rgba(37, 99, 235, 0.45);
}
.vm-crm-card__link--int {
    color: #4f46e5;
}
.vm-crm-card__note {
    margin: 0;
    font-size: 0.78rem;
    line-height: 1.45;
    color: #475569;
    padding: 0.45rem 0.5rem;
    border-radius: 8px;
    background: rgba(248, 250, 252, 0.95);
    border: 1px dashed #e2e8f0;
}
.vm-crm-card__note-k {
    font-weight: 700;
    margin-right: 0.25rem;
}
.vm-crm-card__foot {
    margin-top: auto;
    padding-top: 0.65rem;
    border-top: 1px solid var(--ppms-border, #e8ecf0);
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem 0.75rem;
    font-size: 0.72rem;
    color: var(--ppms-muted, #64748b);
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
