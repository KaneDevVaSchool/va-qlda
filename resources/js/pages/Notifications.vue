<template>
    <div class="ppms-page ppms-activity-page">
        <div class="ppms-activity-toolbar">
            <div class="ppms-activity-toolbar-card">
                <div class="ppms-activity-filters">
                    <label class="ppms-activity-field ppms-activity-field--sm">
                        <span class="ppms-muted">{{ t('activityFeed.filterSubject') }}</span>
                        <select v-model="filters.subject_type" class="ppms-input" @change="resetAndLoad">
                            <option value="">{{ t('activityFeed.filterAll') }}</option>
                            <option value="project">{{ t('activityFeed.subject.project') }}</option>
                            <option value="contract">{{ t('activityFeed.subject.contract') }}</option>
                        </select>
                    </label>
                    <label class="ppms-activity-field ppms-activity-field--sm">
                        <span class="ppms-muted">{{ t('activityFeed.filterKind') }}</span>
                        <select v-model="filters.activity_kind" class="ppms-input" @change="resetAndLoad">
                            <option value="">{{ t('activityFeed.filterAll') }}</option>
                            <option v-for="k in kindOptions" :key="k" :value="k">
                                {{ t(`activityFeed.kind.${k}`) }}
                            </option>
                        </select>
                    </label>
                    <label class="ppms-activity-field ppms-activity-field--xs">
                        <span class="ppms-muted">{{ t('activityFeed.filterUser') }}</span>
                        <input
                            v-model.number="filters.user_id"
                            type="number"
                            min="1"
                            inputmode="numeric"
                            class="ppms-input"
                            :placeholder="t('activityFeed.userIdPlaceholder')"
                            @change="resetAndLoad"
                        />
                    </label>
                    <label class="ppms-activity-field ppms-activity-field--date">
                        <span class="ppms-muted">{{ t('activityFeed.filterFrom') }}</span>
                        <input v-model="filters.from" type="date" class="ppms-input" @change="resetAndLoad" />
                    </label>
                    <label class="ppms-activity-field ppms-activity-field--date">
                        <span class="ppms-muted">{{ t('activityFeed.filterTo') }}</span>
                        <input v-model="filters.to" type="date" class="ppms-input" @change="resetAndLoad" />
                    </label>
                    <label class="ppms-activity-field ppms-activity-field--grow">
                        <span class="ppms-muted">{{ t('common.search') }}</span>
                        <input
                            v-model="filters.q"
                            type="search"
                            class="ppms-input"
                            :placeholder="t('activityFeed.searchPlaceholder')"
                            enterkeyhint="search"
                        />
                    </label>
                </div>
                <div class="ppms-activity-actions">
                    <button type="button" class="ppms-btn-ghost" @click="clearFilters">{{ t('activityFeed.clearFilters') }}</button>
                    <button type="button" class="ppms-btn-ghost" :disabled="initialLoading" @click="markAll">
                        {{ t('activityFeed.markAllRead') }}
                    </button>
                </div>
            </div>
        </div>

        <p v-if="loadError" class="ppms-activity-error" role="alert">{{ loadError }}</p>

        <!-- Initial load: skeleton (toolbar stays usable) -->
        <div v-if="initialLoading && items.length === 0" class="ppms-activity-skeleton" aria-busy="true" :aria-label="t('common.loading')">
            <div v-for="n in 4" :key="n" class="ppms-activity-skel-card">
                <div class="ppms-activity-skel-row">
                    <div class="ppms-activity-skel-avatar" />
                    <div class="ppms-activity-skel-lines">
                        <div class="ppms-activity-skel-line ppms-activity-skel-line--lg" />
                        <div class="ppms-activity-skel-line ppms-activity-skel-line--sm" />
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="ppms-activity-timeline-wrap">
            <template v-for="group in grouped" :key="group.day">
                <div class="ppms-activity-day">{{ dayHeading(group.day) }}</div>
                <ul class="ppms-activity-timeline">
                    <li
                        v-for="item in group.items"
                        :id="'activity-' + item.id"
                        :key="item.id"
                        class="ppms-activity-item"
                        :class="{ unread: !item.read }"
                    >
                        <div class="ppms-activity-line" aria-hidden="true" />
                        <div
                            class="ppms-activity-card ppms-activity-card--interactive"
                            role="button"
                            tabindex="0"
                            :aria-label="listCardAria(item)"
                            @click="openDetail(item)"
                            @keydown.enter.prevent="openDetail(item)"
                            @keydown.space.prevent="openDetail(item)"
                        >
                            <div class="ppms-activity-card-head">
                                <div class="ppms-activity-avatar" aria-hidden="true">
                                    {{ initials(item.actor?.name) }}
                                </div>
                                <div class="ppms-activity-head-text">
                                    <p class="ppms-activity-list-summary">
                                        {{ listSummaryLine(item) }}
                                    </p>
                                    <div class="ppms-activity-meta">
                                        <span class="ppms-activity-badge" :class="'ppms-activity-badge--' + (item.kind_color || 'yellow')">
                                            {{ t(`activityFeed.kind.${item.activity_kind || 'updated'}`) }}
                                        </span>
                                        <span class="ppms-muted ppms-activity-time">{{ item.created_at_vn }}</span>
                                        <span v-if="item.metadata?.ip" class="ppms-muted ppms-activity-meta-ip">
                                            {{ t('activityFeed.ipPrefix') }} {{ item.metadata.ip }}
                                        </span>
                                        <span
                                            v-if="item.metadata?.source"
                                            class="ppms-activity-source"
                                            :title="t('activityFeed.sourceHint')"
                                        >
                                            {{ sourceLabel(item.metadata.source) }}
                                        </span>
                                    </div>
                                    <p class="ppms-activity-peek">
                                        <span class="ppms-activity-peek-count">{{ changePeekLine(item) }}</span>
                                        <span class="ppms-activity-peek-hint">{{ t('activityFeed.listHint') }}</span>
                                        <span class="ppms-activity-peek-chev" aria-hidden="true">›</span>
                                    </p>
                                </div>
                                <button
                                    v-if="!item.read"
                                    type="button"
                                    class="ppms-btn-ghost ppms-btn-sm ppms-activity-mark-read"
                                    @click.stop="markOne(item)"
                                >
                                    {{ t('activityFeed.markRead') }}
                                </button>
                            </div>
                        </div>
                    </li>
                </ul>
            </template>

            <div v-if="items.length === 0 && !initialLoading" class="ppms-activity-empty">
                <p class="ppms-activity-empty-title">{{ t('activityFeed.empty') }}</p>
            </div>

            <div v-if="!initialLoading && total > 0" class="ppms-activity-pager">
                <label class="ppms-activity-per-page">
                    <span class="ppms-muted">{{ t('activityFeed.perPage') }}</span>
                    <select v-model.number="pageSize" class="ppms-input ppms-activity-pager-select" @change="onPageSizeChange">
                        <option v-for="n in pageSizeOptions" :key="n" :value="n">
                            {{ n === 0 ? t('activityFeed.perPageAll') : n }}
                        </option>
                    </select>
                </label>
                <div v-if="pageSize > 0" class="ppms-activity-pager-controls">
                    <button
                        type="button"
                        class="ppms-btn-ghost ppms-btn-sm ppms-activity-pager-btn"
                        :disabled="currentPage <= 1 || listLoading"
                        :aria-label="t('activityFeed.paginationPrev')"
                        @click="goPrevPage"
                    >
                        ‹
                    </button>
                    <span class="ppms-activity-pager-info">{{ pageRangeText }}</span>
                    <span class="ppms-activity-pager-page">{{ pageOfText }}</span>
                    <button
                        type="button"
                        class="ppms-btn-ghost ppms-btn-sm ppms-activity-pager-btn"
                        :disabled="currentPage >= lastPage || listLoading"
                        :aria-label="t('activityFeed.paginationNext')"
                        @click="goNextPage"
                    >
                        ›
                    </button>
                </div>
                <div v-else class="ppms-activity-pager-controls ppms-activity-pager-controls--all">
                    <span class="ppms-activity-pager-info">{{ pageRangeText }}</span>
                </div>
            </div>
            <div v-if="listLoading && items.length > 0" class="ppms-activity-load-more">
                <span class="ppms-activity-spinner" aria-hidden="true" />
                <span>{{ t('activityFeed.loadingMore') }}</span>
            </div>
        </div>

        <Teleport to="body">
            <div
                v-if="detailModalItem"
                class="ppms-modal-backdrop ppms-activity-modal-backdrop"
                role="presentation"
                @click.self="closeDetail"
            >
                <div
                    class="ppms-modal ppms-modal--wide ppms-activity-detail-modal"
                    role="dialog"
                    aria-modal="true"
                    :aria-labelledby="detailModalTitleId"
                >
                    <div class="ppms-activity-detail-head">
                        <div class="ppms-activity-detail-head-top">
                            <p :id="detailModalTitleId" class="ppms-activity-detail-title">
                                {{ t('activityFeed.detailModalTitle') }}
                            </p>
                            <button
                                ref="detailModalCloseRef"
                                type="button"
                                class="ppms-activity-detail-close"
                                :aria-label="t('activityFeed.detailModalCloseLabel')"
                                @click="closeDetail"
                            >
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="ppms-activity-detail-badges">
                            <span
                                class="ppms-activity-badge"
                                :class="'ppms-activity-badge--' + (detailModalItem.kind_color || 'yellow')"
                            >
                                {{ t(`activityFeed.kind.${detailModalItem.activity_kind || 'updated'}`) }}
                            </span>
                            <span class="ppms-muted ppms-activity-detail-time">{{ detailModalItem.created_at_vn }}</span>
                        </div>
                    </div>

                    <div class="ppms-activity-detail-body">
                        <div class="ppms-activity-detail-hero">
                            <div class="ppms-activity-detail-avatar" aria-hidden="true">
                                {{ initials(detailModalItem.actor?.name) }}
                            </div>
                            <div class="ppms-activity-detail-hero-text">
                                <p class="ppms-activity-actor-line ppms-activity-actor-line--modal">
                                    <span class="ppms-activity-actor-seg">
                                        <strong>{{ detailModalItem.actor?.name || t('activityFeed.systemUser') }}</strong>
                                    </span>
                                    <span class="ppms-activity-actor-seg ppms-activity-verb">{{
                                        kindVerb(detailModalItem.activity_kind)
                                    }}</span>
                                    <span class="ppms-activity-actor-seg">{{ t(`activityFeed.subject.${detailModalItem.subject_type}`) }}</span>
                                    <span class="ppms-activity-actor-seg">
                                        <router-link
                                            v-if="detailModalItem.link"
                                            :to="detailModalItem.link"
                                            class="ppms-activity-subject-link"
                                            @click="closeDetail"
                                        >
                                            {{ detailModalItem.subject_label || t('activityFeed.valueEmpty') }}
                                        </router-link>
                                        <span v-else class="ppms-activity-subject">{{
                                            detailModalItem.subject_label || t('activityFeed.valueEmpty')
                                        }}</span>
                                    </span>
                                </p>
                                <div class="ppms-activity-meta ppms-activity-meta--modal">
                                    <span v-if="detailModalItem.metadata?.ip" class="ppms-muted ppms-activity-meta-ip">
                                        {{ t('activityFeed.ipPrefix') }} {{ detailModalItem.metadata.ip }}
                                    </span>
                                    <span
                                        v-if="detailModalItem.metadata?.source"
                                        class="ppms-activity-source"
                                        :title="t('activityFeed.sourceHint')"
                                    >
                                        {{ sourceLabel(detailModalItem.metadata.source) }}
                                    </span>
                                </div>
                                <router-link
                                    v-if="detailModalItem.link"
                                    :to="detailModalItem.link"
                                    class="ppms-activity-open-record"
                                    @click="closeDetail"
                                >
                                    {{ t('activityFeed.openSubject') }} →
                                </router-link>
                            </div>
                        </div>

                        <div class="ppms-activity-detail-section">
                            <h3 class="ppms-activity-detail-section-title">{{ t('activityFeed.diffSectionTitle') }}</h3>
                            <ul v-if="detailModalItem.changes?.length" class="ppms-activity-changes ppms-activity-changes--modal">
                                <li v-for="(ch, ci) in detailModalItem.changes" :key="ci" class="ppms-activity-change">
                                    <span class="ppms-activity-change-field">{{ fieldLabel(ch.label_key) }}</span>
                                    <span class="ppms-activity-diff">
                                        <span class="ppms-activity-old">{{ fmt(ch.old) }}</span>
                                        <span class="ppms-activity-arrow" aria-hidden="true" :title="t('activityFeed.diffArrow')">→</span>
                                        <span class="ppms-activity-new">{{ fmt(ch.new) }}</span>
                                    </span>
                                </li>
                            </ul>
                            <p v-else class="ppms-muted ppms-activity-no-diff ppms-activity-no-diff--modal">
                                {{ t('activityFeed.noFieldDiff') }}
                            </p>
                        </div>
                    </div>

                    <div class="ppms-activity-detail-foot">
                        <button type="button" class="ppms-btn-primary ppms-activity-detail-done" @click="closeDetail">
                            {{ t('activityFeed.closeDetail') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '@/bootstrap';
import { ppmsToastSuccess } from '@/ppmsUi';

const { t, locale } = useI18n();
const route = useRoute();

const kindOptions = ['created', 'updated', 'deleted', 'file_upload', 'status_change', 'assign_po', 'assign_project'];

const filters = ref({
    subject_type: '',
    activity_kind: '',
    user_id: '',
    from: '',
    to: '',
    q: '',
});

/** Debounced text search — cleared when other filters call resetAndLoad */
let activityFeedQDebounce = null;

const items = ref([]);
/** First load with empty list — skeleton */
const initialLoading = ref(true);
const listLoading = ref(false);
const loadError = ref('');

const pageSizeOptions = [5, 10, 15, 20, 0];
const pageSize = ref(10);
const currentPage = ref(1);
const total = ref(0);
const lastPage = ref(1);

const detailModalTitleId = 'ppms-activity-detail-modal-title';
const detailModalItem = ref(null);
const detailModalCloseRef = ref(null);

function listSummaryLine(item) {
    const actor = item.actor?.name || t('activityFeed.systemUser');
    const verb = kindVerb(item.activity_kind);
    const st = t(`activityFeed.subject.${item.subject_type}`);
    const label = item.subject_label || t('activityFeed.valueEmpty');
    return `${actor} ${verb} ${st} ${label}`;
}

function changePeekLine(item) {
    const n = item.changes?.length || 0;
    if (n > 0) {
        return t('activityFeed.changeCount', { n });
    }
    return t('activityFeed.noChangesShort');
}

function listCardAria(item) {
    return `${listSummaryLine(item)}. ${changePeekLine(item)}. ${t('activityFeed.listHint')}`;
}

function openDetail(item) {
    detailModalItem.value = item;
    nextTick(() => {
        detailModalCloseRef.value?.focus();
    });
}

function closeDetail() {
    detailModalItem.value = null;
}

function onDetailEscape(e) {
    if (e.key === 'Escape' && detailModalItem.value) {
        e.preventDefault();
        closeDetail();
    }
}

function initials(name) {
    const n = (name || '?').trim();
    return n.charAt(0).toUpperCase();
}

function kindVerb(kind) {
    const k = `activityFeed.kindVerb.${kind || 'updated'}`;
    const tr = t(k);
    return tr === k ? t(`activityFeed.kind.${kind || 'updated'}`) : tr;
}

function fieldLabel(labelKey) {
    if (!labelKey) {
        return t('activityFeed.valueEmpty');
    }
    const path = `activityFeed.fields.${labelKey}`;
    const tr = t(path);
    if (tr !== path) {
        return tr;
    }
    const tail = labelKey.includes('.') ? labelKey.slice(labelKey.lastIndexOf('.') + 1) : labelKey;
    return tail.replace(/_/g, ' ');
}

function sourceLabel(src) {
    const key = `activityFeed.source.${src}`;
    const tr = t(key);
    return tr === key ? String(src) : tr;
}

function fmt(v) {
    if (v === null || v === undefined) {
        return t('activityFeed.valueEmpty');
    }
    if (typeof v === 'object') {
        return JSON.stringify(v);
    }
    const s = String(v);
    if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(s) || /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/.test(s)) {
        const d = new Date(s);
        if (!Number.isNaN(d.getTime())) {
            return d.toLocaleString(locale.value === 'en' ? 'en-GB' : 'vi-VN', {
                dateStyle: 'short',
                timeStyle: 'short',
                timeZone: 'Asia/Ho_Chi_Minh',
            });
        }
    }
    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
        const [y, mo, day] = s.split('-').map((x) => parseInt(x, 10));
        const d = new Date(y, mo - 1, day);
        if (!Number.isNaN(d.getTime())) {
            return d.toLocaleDateString(locale.value === 'en' ? 'en-GB' : 'vi-VN', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            });
        }
    }
    return s;
}

function todayYmd() {
    const tz = 'Asia/Ho_Chi_Minh';
    return new Date().toLocaleDateString('en-CA', { timeZone: tz });
}

function yesterdayYmd() {
    const d = new Date();
    d.setDate(d.getDate() - 1);
    return d.toLocaleDateString('en-CA', { timeZone: 'Asia/Ho_Chi_Minh' });
}

function dayHeading(ymd) {
    if (ymd === todayYmd()) {
        return t('activityFeed.today');
    }
    if (ymd === yesterdayYmd()) {
        return t('activityFeed.yesterday');
    }
    const [y, m, day] = ymd.split('-').map((x) => parseInt(x, 10));
    if (y && m && day) {
        const d = new Date(y, m - 1, day);
        return d.toLocaleDateString(locale.value === 'en' ? 'en-GB' : 'vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    }
    return ymd;
}

const grouped = computed(() => {
    const map = new Map();
    for (const item of items.value) {
        const day = item.created_at_vn_date || (item.created_at || '').slice(0, 10);
        if (!map.has(day)) {
            map.set(day, []);
        }
        map.get(day).push(item);
    }
    return [...map.entries()].map(([day, groupItems]) => ({ day, items: groupItems }));
});

const pageRangeText = computed(() => {
    if (total.value === 0) {
        return '';
    }
    if (pageSize.value === 0) {
        return t('activityFeed.paginationRange', {
            from: 1,
            to: items.value.length,
            total: total.value,
        });
    }
    const from = (currentPage.value - 1) * pageSize.value + 1;
    const to = Math.min(currentPage.value * pageSize.value, total.value);
    return t('activityFeed.paginationRange', { from, to, total: total.value });
});

const pageOfText = computed(() => {
    if (pageSize.value === 0 || total.value === 0) {
        return '';
    }
    return t('activityFeed.paginationPage', { page: currentPage.value, total: lastPage.value });
});

function buildListParams() {
    const p = {
        page: currentPage.value,
        per_page: pageSize.value,
    };
    if (filters.value.subject_type) {
        p.subject_type = filters.value.subject_type;
    }
    if (filters.value.activity_kind) {
        p.activity_kind = filters.value.activity_kind;
    }
    if (filters.value.user_id) {
        p.user_id = filters.value.user_id;
    }
    if (filters.value.from) {
        p.from = filters.value.from;
    }
    if (filters.value.to) {
        p.to = filters.value.to;
    }
    if (filters.value.q) {
        p.q = filters.value.q;
    }
    return p;
}

async function fetchFeed() {
    if (listLoading.value) {
        return;
    }
    const emptyBefore = items.value.length === 0;
    if (emptyBefore) {
        initialLoading.value = true;
    } else {
        listLoading.value = true;
    }
    loadError.value = '';

    try {
        const { data } = await axios.get('/api/activity-feed', {
            params: buildListParams(),
        });
        items.value = data.data || [];
        const m = data.meta;
        if (m) {
            total.value = m.total ?? 0;
            lastPage.value = m.last_page ?? 1;
            if (m.current_page != null) {
                currentPage.value = m.current_page;
            }
        } else {
            total.value = items.value.length;
            lastPage.value = 1;
        }
    } catch (e) {
        loadError.value = formatApiUserMessage(e, t('activityFeed.loadError'));
        items.value = [];
        total.value = 0;
        lastPage.value = 1;
    } finally {
        initialLoading.value = false;
        listLoading.value = false;
        await nextTick();
        scrollToFocus();
        if (!route.query.focus) {
            const mainEl = document.getElementById('ppms-main');
            if (mainEl) {
                mainEl.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
    }
}

function resetAndLoad() {
    clearTimeout(activityFeedQDebounce);
    currentPage.value = 1;
    loadError.value = '';
    fetchFeed();
}

function onPageSizeChange() {
    currentPage.value = 1;
    fetchFeed();
}

function goPrevPage() {
    if (currentPage.value <= 1) {
        return;
    }
    currentPage.value -= 1;
    fetchFeed();
}

function goNextPage() {
    if (currentPage.value >= lastPage.value) {
        return;
    }
    currentPage.value += 1;
    fetchFeed();
}

function clearFilters() {
    filters.value = {
        subject_type: '',
        activity_kind: '',
        user_id: '',
        from: '',
        to: '',
        q: '',
    };
    clearTimeout(activityFeedQDebounce);
    resetAndLoad();
}

watch(
    () => filters.value.q,
    () => {
        clearTimeout(activityFeedQDebounce);
        activityFeedQDebounce = setTimeout(() => resetAndLoad(), 320);
    },
);

async function markOne(item) {
    try {
        await axios.patch(`/api/activity-feed/${item.id}/read`);
        item.read = true;
        ppmsToastSuccess(t('activityFeed.markedRead'));
    } catch {
        /* ignore */
    }
}

async function markAll() {
    const body = {};
    if (filters.value.subject_type) {
        body.subject_type = filters.value.subject_type;
    }
    if (filters.value.activity_kind) {
        body.activity_kind = filters.value.activity_kind;
    }
    if (filters.value.user_id) {
        body.user_id = filters.value.user_id;
    }
    if (filters.value.from) {
        body.from = filters.value.from;
    }
    if (filters.value.to) {
        body.to = filters.value.to;
    }
    if (filters.value.q) {
        body.q = filters.value.q;
    }
    try {
        await axios.post('/api/activity-feed/read-all', body);
        ppmsToastSuccess(t('activityFeed.markedAll'));
        for (const it of items.value) {
            it.read = true;
        }
    } catch {
        /* ignore */
    }
}

async function scrollToFocus() {
    const id = route.query.focus;
    if (!id) {
        return;
    }
    await nextTick();
    const el = document.getElementById(`activity-${id}`);
    el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

onMounted(async () => {
    await fetchFeed();
});

watch(
    () => route.query.focus,
    () => scrollToFocus(),
);

watch(detailModalItem, (v) => {
    if (typeof document === 'undefined') {
        return;
    }
    if (v) {
        document.body.classList.add('ppms-modal-open');
        window.addEventListener('keydown', onDetailEscape);
    } else {
        document.body.classList.remove('ppms-modal-open');
        window.removeEventListener('keydown', onDetailEscape);
    }
});

onUnmounted(() => {
    window.removeEventListener('keydown', onDetailEscape);
    if (typeof document !== 'undefined') {
        document.body.classList.remove('ppms-modal-open');
    }
});
</script>

<style scoped>
.ppms-activity-page {
    width: 100%;
    max-width: none;
    margin-inline: 0;
    box-sizing: border-box;
}

.ppms-activity-toolbar {
    margin-bottom: 0.85rem;
    position: sticky;
    top: 0;
    z-index: 2;
    padding-bottom: 0.35rem;
    background: linear-gradient(to bottom, var(--ppms-bg, #f8fafc) 88%, transparent);
}

.ppms-activity-toolbar-card {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    padding: 0.65rem 0.85rem;
    border: 1px solid var(--ppms-border, #e5e7eb);
    border-radius: 10px;
    background: var(--ppms-surface, #fff);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

@media (min-width: 1100px) {
    .ppms-activity-toolbar-card {
        flex-direction: row;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 0.65rem 1rem;
    }

    .ppms-activity-toolbar-card .ppms-activity-filters {
        flex: 1;
        min-width: min(100%, 42rem);
    }

    .ppms-activity-toolbar-card .ppms-activity-actions {
        margin-left: auto;
    }
}

.ppms-activity-filters {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 112px), 1fr));
    gap: 0.45rem 0.55rem;
    align-items: end;
}

@media (min-width: 640px) {
    .ppms-activity-filters {
        grid-template-columns: repeat(auto-fill, minmax(128px, 1fr));
    }
}

.ppms-activity-field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.8rem;
    min-width: 0;
}

.ppms-activity-field--grow {
    grid-column: 1 / -1;
}

@media (min-width: 768px) {
    .ppms-activity-field--grow {
        grid-column: span 2;
    }
}

@media (min-width: 1100px) {
    .ppms-activity-field--grow {
        grid-column: span 3;
        min-width: 12rem;
    }
}

.ppms-activity-field--xs :deep(.ppms-input) {
    max-width: 100%;
}

.ppms-activity-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    align-items: center;
}

.ppms-activity-actions .ppms-btn-ghost {
    flex: 1 1 auto;
    min-width: min(100%, 7.5rem);
    justify-content: center;
    padding: 0.4rem 0.65rem;
    font-size: 0.85rem;
}

@media (min-width: 480px) {
    .ppms-activity-actions .ppms-btn-ghost {
        flex: 0 1 auto;
        min-width: unset;
    }
}

.ppms-input {
    border: 1px solid var(--ppms-border, #e5e7eb);
    border-radius: 8px;
    padding: 0.45rem 0.6rem;
    font: inherit;
    min-width: 0;
    width: 100%;
    max-width: 100%;
    background: var(--ppms-surface, #fff);
    box-sizing: border-box;
}

.ppms-activity-error {
    margin: 0 0 0.75rem;
    padding: 0.65rem 0.85rem;
    border-radius: 8px;
    background: #fef2f2;
    color: #991b1b;
    font-size: 0.9rem;
}

/* Skeleton */
.ppms-activity-skeleton {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 0.25rem 0 1rem;
}

.ppms-activity-skel-card {
    border: 1px solid var(--ppms-border, #e5e7eb);
    border-radius: 12px;
    padding: 0.85rem 1rem;
    background: var(--ppms-surface, #fff);
}

.ppms-activity-skel-row {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
}

.ppms-activity-skel-avatar {
    width: 40px;
    height: 40px;
    border-radius: 999px;
    background: linear-gradient(90deg, #e5e7eb 0%, #f3f4f6 50%, #e5e7eb 100%);
    background-size: 200% 100%;
    animation: ppms-activity-shimmer 1.2s ease-in-out infinite;
    flex-shrink: 0;
}

.ppms-activity-skel-lines {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.ppms-activity-skel-line {
    height: 0.75rem;
    border-radius: 4px;
    background: linear-gradient(90deg, #e5e7eb 0%, #f3f4f6 50%, #e5e7eb 100%);
    background-size: 200% 100%;
    animation: ppms-activity-shimmer 1.2s ease-in-out infinite;
}

.ppms-activity-skel-line--lg {
    width: 85%;
    max-width: 100%;
}

.ppms-activity-skel-line--sm {
    width: 55%;
    height: 0.6rem;
}

@keyframes ppms-activity-shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

.ppms-activity-day {
    font-weight: 600;
    font-size: clamp(0.88rem, 2.2vw, 0.92rem);
    margin: 0.85rem 0 0.4rem;
    color: var(--ppms-heading, #111827);
}

.ppms-activity-day:first-of-type {
    margin-top: 0.15rem;
}

.ppms-activity-timeline {
    list-style: none;
    margin: 0;
    padding: 0;
}

.ppms-activity-item {
    position: relative;
    padding-left: 0.85rem;
    padding-bottom: 0.75rem;
}

@media (min-width: 480px) {
    .ppms-activity-item {
        padding-left: 1.25rem;
    }
}

.ppms-activity-item.unread .ppms-activity-card {
    border-color: #93c5fd;
    background: #f8fafc;
}

.ppms-activity-line {
    position: absolute;
    left: 0.2rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--ppms-border, #e5e7eb);
}

@media (min-width: 480px) {
    .ppms-activity-line {
        left: 0.35rem;
    }
}

.ppms-activity-item:last-child .ppms-activity-line {
    bottom: auto;
    height: 100%;
}

.ppms-activity-card {
    border: 1px solid var(--ppms-border, #e5e7eb);
    border-radius: 10px;
    padding: 0.65rem 0.75rem;
    background: var(--ppms-surface, #fff);
}

.ppms-activity-card--interactive {
    cursor: pointer;
    text-align: left;
    width: 100%;
    box-sizing: border-box;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}

.ppms-activity-card--interactive:hover {
    border-color: rgba(79, 70, 229, 0.35);
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
}

.ppms-activity-card--interactive:focus {
    outline: none;
}

.ppms-activity-card--interactive:focus-visible {
    outline: 2px solid var(--ppms-primary, #4f46e5);
    outline-offset: 2px;
}

@media (min-width: 480px) {
    .ppms-activity-card {
        padding: 0.7rem 0.9rem;
    }
}

.ppms-activity-list-summary {
    margin: 0 0 0.35rem;
    font-size: clamp(0.88rem, 2.6vw, 0.94rem);
    line-height: 1.45;
    color: var(--ppms-text, #111827);
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow: hidden;
    word-break: break-word;
}

.ppms-activity-peek {
    margin: 0.4rem 0 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem 0.5rem;
    font-size: 0.78rem;
    color: var(--ppms-muted, #64748b);
}

.ppms-activity-peek-count {
    font-weight: 600;
    color: var(--ppms-heading, #334155);
}

.ppms-activity-peek-hint {
    flex: 1 1 auto;
    min-width: 0;
}

.ppms-activity-peek-chev {
    font-size: 1.1rem;
    line-height: 1;
    color: var(--ppms-primary, #4f46e5);
    font-weight: 300;
}

.ppms-activity-card-head {
    display: flex;
    gap: 0.65rem;
    align-items: flex-start;
    flex-wrap: wrap;
}

@media (min-width: 560px) {
    .ppms-activity-card-head {
        flex-wrap: nowrap;
    }
}

.ppms-activity-avatar {
    width: 38px;
    height: 38px;
    border-radius: 999px;
    background: #e0e7ff;
    color: #3730a3;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
    font-size: 0.95rem;
}

@media (min-width: 480px) {
    .ppms-activity-avatar {
        width: 40px;
        height: 40px;
    }
}

.ppms-activity-head-text {
    flex: 1;
    min-width: min(100%, 12rem);
}

.ppms-activity-actor-line {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 0.35rem 0.5rem;
    margin: 0 0 0.35rem;
    font-size: clamp(0.88rem, 2.8vw, 0.95rem);
    line-height: 1.45;
    word-break: break-word;
}

.ppms-activity-actor-seg {
    display: inline-flex;
    align-items: baseline;
    max-width: 100%;
    min-width: 0;
}

.ppms-activity-verb {
    font-weight: 600;
}

.ppms-activity-subject-link {
    font-weight: 600;
    word-break: break-word;
}

.ppms-activity-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem 0.5rem;
    align-items: center;
    font-size: clamp(0.72rem, 2.2vw, 0.8rem);
}

.ppms-activity-meta-ip {
    display: none;
}

@media (min-width: 640px) {
    .ppms-activity-meta-ip {
        display: inline;
    }
}

.ppms-activity-source {
    display: inline-flex;
    align-items: center;
    padding: 0.1rem 0.4rem;
    border-radius: 4px;
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    font-family: ui-monospace, 'Cascadia Code', 'Segoe UI', system-ui, sans-serif;
    color: var(--ppms-muted, #64748b);
    background: rgba(100, 116, 139, 0.1);
    border: 1px solid rgba(100, 116, 139, 0.2);
}

.ppms-activity-mark-read {
    margin-left: auto;
    flex-shrink: 0;
}

@media (max-width: 559px) {
    .ppms-activity-mark-read {
        width: 100%;
        margin-left: 0;
        margin-top: 0.35rem;
    }
}

.ppms-activity-badge {
    display: inline-block;
    padding: 0.1rem 0.45rem;
    border-radius: 6px;
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.ppms-activity-badge--green {
    background: #dcfce7;
    color: #166534;
}

.ppms-activity-badge--yellow {
    background: #fef9c3;
    color: #854d0e;
}

.ppms-activity-badge--red {
    background: #fee2e2;
    color: #991b1b;
}

.ppms-activity-changes--modal {
    margin: 0;
    padding: 0;
    border-top: none;
}

.ppms-activity-changes {
    margin: 0.55rem 0 0;
    padding: 0.45rem 0 0;
    list-style: none;
    border-top: 1px dashed var(--ppms-border, #e5e7eb);
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.35rem 0.75rem;
}

@media (min-width: 720px) {
    .ppms-activity-changes {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (min-width: 1200px) {
    .ppms-activity-changes {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

.ppms-activity-change {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.12rem;
    margin: 0;
    padding: 0.35rem 0.45rem;
    font-size: clamp(0.78rem, 2.1vw, 0.84rem);
    border-radius: 6px;
    background: rgba(248, 250, 252, 0.85);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-left: 2px solid rgba(79, 70, 229, 0.35);
    min-width: 0;
}

@media (min-width: 560px) {
    .ppms-activity-change {
        grid-template-columns: minmax(0, 10rem) minmax(0, 1fr);
        gap: 0.35rem 0.5rem;
        align-items: baseline;
    }
}

.ppms-activity-change-field {
    font-weight: 600;
    color: var(--ppms-heading, #111827);
    word-break: break-word;
}

.ppms-activity-diff {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem 0.35rem;
    align-items: baseline;
    min-width: 0;
    font-family: ui-monospace, 'Cascadia Code', 'Segoe UI', system-ui, sans-serif;
    font-size: 0.92em;
    font-variant-numeric: tabular-nums;
}

.ppms-activity-arrow {
    color: var(--ppms-muted, #64748b);
    flex-shrink: 0;
}

.ppms-activity-old {
    color: #b91c1c;
    text-decoration: line-through;
    word-break: break-word;
}

.ppms-activity-new {
    color: #15803d;
    font-weight: 500;
    word-break: break-word;
}

.ppms-activity-no-diff {
    margin: 0.5rem 0 0;
    font-size: 0.85rem;
}

.ppms-activity-no-diff--modal {
    margin: 0;
    padding: 0.75rem;
    border-radius: 8px;
    background: rgba(248, 250, 252, 0.9);
    border: 1px dashed var(--ppms-border, #e5e7eb);
}

.ppms-activity-modal-backdrop {
    z-index: 10000;
}

.ppms-activity-detail-modal {
    max-width: min(96vw, 52rem);
    padding: 0;
    display: flex;
    flex-direction: column;
    max-height: min(92vh, 44rem);
    overflow: hidden;
    border-radius: 14px;
    box-shadow:
        0 24px 48px rgba(15, 23, 42, 0.14),
        0 0 0 1px rgba(15, 23, 42, 0.06);
}

.ppms-activity-detail-head {
    flex-shrink: 0;
    padding: 1rem 1.15rem 0.85rem;
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.08) 0%, rgba(255, 140, 0, 0.06) 100%);
    border-bottom: 1px solid var(--ppms-border, #e5e7eb);
}

.ppms-activity-detail-head-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}

.ppms-activity-detail-title {
    margin: 0;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--ppms-muted, #64748b);
}

.ppms-activity-detail-close {
    flex-shrink: 0;
    width: 2.25rem;
    height: 2.25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: -0.35rem -0.25rem 0 0;
    border: none;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.65);
    color: var(--ppms-text, #334155);
    font-size: 1.5rem;
    line-height: 1;
    cursor: pointer;
    transition: background 0.15s ease;
}

.ppms-activity-detail-close:hover {
    background: rgba(255, 255, 255, 0.95);
}

.ppms-activity-detail-badges {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem 0.6rem;
    margin-top: 0.5rem;
}

.ppms-activity-detail-time {
    font-size: 0.82rem;
}

.ppms-activity-detail-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow: auto;
    padding: 1rem 1.15rem 1.1rem;
    -webkit-overflow-scrolling: touch;
}

.ppms-activity-detail-hero {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--ppms-border, #e5e7eb);
}

.ppms-activity-detail-avatar {
    width: 44px;
    height: 44px;
    border-radius: 999px;
    background: linear-gradient(145deg, #e0e7ff, #c7d2fe);
    color: #3730a3;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
    font-size: 1rem;
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.15);
}

.ppms-activity-detail-hero-text {
    flex: 1;
    min-width: 0;
}

.ppms-activity-actor-line--modal {
    margin-bottom: 0.4rem;
}

.ppms-activity-meta--modal {
    margin-top: 0.25rem;
}

.ppms-activity-open-record {
    display: inline-flex;
    margin-top: 0.65rem;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--ppms-primary, #4f46e5);
    text-decoration: none;
}

.ppms-activity-open-record:hover {
    text-decoration: underline;
}

.ppms-activity-detail-section {
    margin-top: 0.25rem;
}

.ppms-activity-detail-section-title {
    margin: 0 0 0.65rem;
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--ppms-muted, #64748b);
}

.ppms-activity-detail-foot {
    flex-shrink: 0;
    padding: 0.85rem 1.15rem 1rem;
    border-top: 1px solid var(--ppms-border, #e5e7eb);
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.9) 0%, var(--ppms-surface, #fff) 100%);
    display: flex;
    justify-content: flex-end;
}

.ppms-activity-detail-done {
    min-width: 7.5rem;
}

.ppms-activity-sentinel {
    height: 1px;
    width: 100%;
}

.ppms-activity-load-more {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem;
    font-size: 0.88rem;
    color: var(--ppms-muted, #64748b);
}

.ppms-activity-spinner {
    width: 1rem;
    height: 1rem;
    border: 2px solid #e5e7eb;
    border-top-color: var(--ppms-accent, #4f46e5);
    border-radius: 50%;
    animation: ppms-activity-spin 0.7s linear infinite;
}

@keyframes ppms-activity-spin {
    to {
        transform: rotate(360deg);
    }
}

.ppms-activity-empty {
    text-align: center;
    padding: 2rem 1rem;
}

.ppms-activity-empty-title {
    margin: 0;
    font-size: 0.95rem;
    color: var(--ppms-muted, #64748b);
}

.ppms-activity-end {
    text-align: center;
    padding: 0.75rem 1rem 1.25rem;
    font-size: 0.85rem;
    color: var(--ppms-muted, #94a3b8);
}

.ppms-activity-pager {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem 1rem;
    margin-top: 1rem;
    padding: 0.65rem 0.85rem;
    border: 1px solid var(--ppms-border, #e5e7eb);
    border-radius: 10px;
    background: var(--ppms-surface, #fff);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.ppms-activity-per-page {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.8rem;
    min-width: 0;
}

.ppms-activity-pager-select {
    max-width: 10rem;
}

.ppms-activity-pager-controls {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem 0.75rem;
    margin-left: auto;
}

.ppms-activity-pager-controls--all {
    margin-left: auto;
}

.ppms-activity-pager-btn {
    min-width: 2.25rem;
    font-size: 1.1rem;
    line-height: 1;
    padding: 0.35rem 0.5rem;
}

.ppms-activity-pager-info {
    font-size: 0.85rem;
    color: var(--ppms-text, #334155);
}

.ppms-activity-pager-page {
    font-size: 0.8rem;
    color: var(--ppms-muted, #64748b);
    font-weight: 600;
}
</style>
