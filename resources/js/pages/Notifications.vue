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
                            v-model="searchQ"
                            type="search"
                            class="ppms-input"
                            :placeholder="t('activityFeed.searchPlaceholder')"
                            enterkeyhint="search"
                            @keyup.enter="resetAndLoad"
                        />
                    </label>
                </div>
                <div class="ppms-activity-actions">
                    <button type="button" class="ppms-btn-ghost" @click="applySearch">{{ t('activityFeed.apply') }}</button>
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
                        <div class="ppms-activity-card">
                            <div class="ppms-activity-card-head">
                                <div class="ppms-activity-avatar" aria-hidden="true">
                                    {{ initials(item.actor?.name) }}
                                </div>
                                <div class="ppms-activity-head-text">
                                    <p class="ppms-activity-actor-line">
                                        <strong>{{ item.actor?.name || t('activityFeed.systemUser') }}</strong>
                                        <span class="ppms-activity-verb">{{ kindVerb(item.activity_kind) }}</span>
                                        <span>{{ t(`activityFeed.subject.${item.subject_type}`) }}</span>
                                        <router-link v-if="item.link" :to="item.link" class="ppms-activity-subject-link">
                                            {{ item.subject_label || '—' }}
                                        </router-link>
                                        <span v-else class="ppms-activity-subject">{{ item.subject_label || '—' }}</span>
                                    </p>
                                    <div class="ppms-activity-meta">
                                        <span class="ppms-activity-badge" :class="'ppms-activity-badge--' + (item.kind_color || 'yellow')">
                                            {{ t(`activityFeed.kind.${item.activity_kind || 'updated'}`) }}
                                        </span>
                                        <span class="ppms-muted ppms-activity-time">{{ item.created_at_vn }}</span>
                                        <span v-if="item.metadata?.ip" class="ppms-muted ppms-activity-meta-ip">IP {{ item.metadata.ip }}</span>
                                        <span v-if="item.metadata?.source" class="ppms-muted">{{ item.metadata.source }}</span>
                                    </div>
                                </div>
                                <button
                                    v-if="!item.read"
                                    type="button"
                                    class="ppms-btn-ghost ppms-btn-sm ppms-activity-mark-read"
                                    @click="markOne(item)"
                                >
                                    {{ t('activityFeed.markRead') }}
                                </button>
                            </div>
                            <ul v-if="item.changes?.length" class="ppms-activity-changes">
                                <li v-for="(ch, ci) in item.changes" :key="ci" class="ppms-activity-change">
                                    <span class="ppms-activity-change-field">{{ fieldLabel(ch.label_key) }}</span>
                                    <span class="ppms-activity-diff">
                                        <span class="ppms-activity-old">{{ fmt(ch.old) }}</span>
                                        <span class="ppms-activity-arrow" aria-hidden="true">→</span>
                                        <span class="ppms-activity-new">{{ fmt(ch.new) }}</span>
                                    </span>
                                </li>
                            </ul>
                            <p v-else class="ppms-muted ppms-activity-no-diff">{{ t('activityFeed.noFieldDiff') }}</p>
                        </div>
                    </li>
                </ul>
            </template>

            <div v-if="items.length === 0 && !initialLoading" class="ppms-activity-empty">
                <p class="ppms-activity-empty-title">{{ t('activityFeed.empty') }}</p>
            </div>

            <div ref="sentinel" class="ppms-activity-sentinel" />
            <div v-if="loadingMore" class="ppms-activity-load-more">
                <span class="ppms-activity-spinner" aria-hidden="true" />
                <span>{{ t('activityFeed.loadingMore') }}</span>
            </div>
            <div v-if="!hasMore && items.length > 0 && !loadingMore" class="ppms-activity-end">{{ t('activityFeed.endOfFeed') }}</div>
        </div>
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
const searchQ = ref('');

const items = ref([]);
/** First page in flight (empty list) — show skeleton, not full-page blocking text */
const initialLoading = ref(true);
const loadingMore = ref(false);
const loadError = ref('');
const hasMore = ref(true);
const cursor = ref(null);

const sentinel = ref(null);
let observer;

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
    const path = `activityFeed.fields.${labelKey}`;
    const tr = t(path);
    return tr === path ? labelKey : tr;
}

function fmt(v) {
    if (v === null || v === undefined) {
        return '—';
    }
    if (typeof v === 'object') {
        return JSON.stringify(v);
    }
    return String(v);
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

function buildParams(forCursor) {
    const p = { per_page: 25 };
    if (forCursor) {
        p.before_id = forCursor;
    }
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

function detachSentinel() {
    if (observer && sentinel.value) {
        try {
            observer.unobserve(sentinel.value);
        } catch {
            /* ignore */
        }
    }
}

function attachSentinel() {
    if (!observer || !sentinel.value) {
        return;
    }
    try {
        observer.observe(sentinel.value);
    } catch {
        /* ignore */
    }
}

async function loadMore() {
    if (loadingMore.value) {
        return;
    }

    const append = cursor.value !== null;
    if (append && (!hasMore.value || cursor.value === null)) {
        return;
    }
    if (!append && !hasMore.value && items.value.length > 0) {
        return;
    }
    if (append) {
        loadingMore.value = true;
        detachSentinel();
    } else {
        initialLoading.value = true;
        loadError.value = '';
    }

    try {
        const { data } = await axios.get('/api/activity-feed', {
            params: buildParams(cursor.value),
        });
        const rows = data.data || [];
        if (append) {
            items.value = items.value.concat(rows);
        } else {
            items.value = rows;
        }
        hasMore.value = !!data.has_more;
        cursor.value = data.next_cursor ?? null;
    } catch (e) {
        loadError.value = formatApiUserMessage(e, t('activityFeed.loadError'));
        if (!append) {
            items.value = [];
            hasMore.value = false;
        }
    } finally {
        initialLoading.value = false;
        loadingMore.value = false;
        await nextTick();
        if (hasMore.value) {
            attachSentinel();
        }
    }
}

function resetAndLoad() {
    items.value = [];
    cursor.value = null;
    hasMore.value = true;
    loadError.value = '';
    loadMore();
}

function applySearch() {
    filters.value.q = searchQ.value.trim();
    resetAndLoad();
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
    searchQ.value = '';
    resetAndLoad();
}

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
    observer = new IntersectionObserver(
        (entries) => {
            const e = entries[0];
            if (!e?.isIntersecting || initialLoading.value || loadingMore.value) {
                return;
            }
            if (!hasMore.value || cursor.value === null) {
                return;
            }
            loadMore();
        },
        {
            root: null,
            rootMargin: '0px 0px 320px 0px',
            threshold: 0,
        },
    );

    await loadMore();
    await nextTick();
    attachSentinel();
    scrollToFocus();
});

watch(sentinel, (el) => {
    if (observer && el && hasMore.value && !initialLoading.value) {
        attachSentinel();
    }
});

watch(
    () => route.query.focus,
    () => scrollToFocus(),
);

onUnmounted(() => {
    observer?.disconnect();
    observer = undefined;
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

@media (min-width: 480px) {
    .ppms-activity-card {
        padding: 0.7rem 0.9rem;
    }
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
    margin: 0 0 0.35rem;
    font-size: clamp(0.88rem, 2.8vw, 0.95rem);
    line-height: 1.45;
    word-break: break-word;
}

.ppms-activity-verb {
    font-weight: 600;
    margin: 0 0.2rem;
}

.ppms-activity-subject-link {
    font-weight: 600;
    margin-left: 0.15rem;
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
</style>
