<template>
    <div class="ppms-page ppms-activity-page">
        <div class="ppms-activity-toolbar">
            <div class="ppms-activity-filters">
                <label class="ppms-activity-field">
                    <span class="ppms-muted">{{ t('activityFeed.filterSubject') }}</span>
                    <select v-model="filters.subject_type" class="ppms-input" @change="resetAndLoad">
                        <option value="">{{ t('activityFeed.filterAll') }}</option>
                        <option value="project">{{ t('activityFeed.subject.project') }}</option>
                        <option value="contract">{{ t('activityFeed.subject.contract') }}</option>
                    </select>
                </label>
                <label class="ppms-activity-field">
                    <span class="ppms-muted">{{ t('activityFeed.filterKind') }}</span>
                    <select v-model="filters.activity_kind" class="ppms-input" @change="resetAndLoad">
                        <option value="">{{ t('activityFeed.filterAll') }}</option>
                        <option v-for="k in kindOptions" :key="k" :value="k">
                            {{ t(`activityFeed.kind.${k}`) }}
                        </option>
                    </select>
                </label>
                <label class="ppms-activity-field">
                    <span class="ppms-muted">{{ t('activityFeed.filterUser') }}</span>
                    <input
                        v-model.number="filters.user_id"
                        type="number"
                        min="1"
                        class="ppms-input"
                        :placeholder="t('activityFeed.userIdPlaceholder')"
                        @change="resetAndLoad"
                    />
                </label>
                <label class="ppms-activity-field">
                    <span class="ppms-muted">{{ t('activityFeed.filterFrom') }}</span>
                    <input v-model="filters.from" type="date" class="ppms-input" @change="resetAndLoad" />
                </label>
                <label class="ppms-activity-field">
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
                        @keyup.enter="resetAndLoad"
                    />
                </label>
            </div>
            <div class="ppms-activity-actions">
                <button type="button" class="ppms-btn-ghost" @click="applySearch">{{ t('activityFeed.apply') }}</button>
                <button type="button" class="ppms-btn-ghost" @click="clearFilters">{{ t('activityFeed.clearFilters') }}</button>
                <button type="button" class="ppms-btn-ghost" @click="markAll">{{ t('activityFeed.markAllRead') }}</button>
            </div>
        </div>

        <div v-if="loading && items.length === 0" class="ppms-loading-line" role="status">
            {{ t('common.loading') }}
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
                                        <span class="ppms-muted">{{ item.created_at_vn }}</span>
                                        <span v-if="item.metadata?.ip" class="ppms-muted">IP {{ item.metadata.ip }}</span>
                                        <span v-if="item.metadata?.source" class="ppms-muted">{{ item.metadata.source }}</span>
                                    </div>
                                </div>
                                <button
                                    v-if="!item.read"
                                    type="button"
                                    class="ppms-btn-ghost ppms-btn-sm"
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

            <div v-if="items.length === 0 && !loading" class="ppms-muted ppms-activity-empty">
                {{ t('activityFeed.empty') }}
            </div>

            <div ref="sentinel" class="ppms-activity-sentinel" />
            <div v-if="loadingMore" class="ppms-loading-line">{{ t('activityFeed.loadingMore') }}</div>
            <div v-if="!hasMore && items.length > 0" class="ppms-muted ppms-activity-end">{{ t('activityFeed.endOfFeed') }}</div>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
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
const loading = ref(true);
const loadingMore = ref(false);
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
    const d = new Date();
    const tz = 'Asia/Ho_Chi_Minh';
    return d.toLocaleDateString('en-CA', { timeZone: tz });
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

async function loadMore() {
    if (loadingMore.value || loading.value) {
        return;
    }
    if (!hasMore.value) {
        return;
    }

    const append = cursor.value !== null;
    if (append) {
        loadingMore.value = true;
    } else {
        loading.value = true;
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
    } finally {
        loadingMore.value = false;
        loading.value = false;
    }
}

function resetAndLoad() {
    items.value = [];
    cursor.value = null;
    hasMore.value = true;
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
    await axios.patch(`/api/activity-feed/${item.id}/read`);
    item.read = true;
    ppmsToastSuccess(t('activityFeed.markedRead'));
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
    await axios.post('/api/activity-feed/read-all', body);
    ppmsToastSuccess(t('activityFeed.markedAll'));
    for (const it of items.value) {
        it.read = true;
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
    resetAndLoad();
    await nextTick();
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting) {
                loadMore();
            }
        },
        { rootMargin: '120px' },
    );
    if (sentinel.value) {
        observer.observe(sentinel.value);
    }
    scrollToFocus();
});

watch(sentinel, (el) => {
    if (observer && el) {
        observer.observe(el);
    }
});

watch(
    () => route.query.focus,
    () => scrollToFocus(),
);

onUnmounted(() => {
    observer?.disconnect();
});
</script>

<style scoped>
.ppms-activity-page {
    max-width: 920px;
}

.ppms-activity-toolbar {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}

.ppms-activity-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: flex-end;
}

.ppms-activity-field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.8rem;
}

.ppms-activity-field--grow {
    flex: 1 1 200px;
    min-width: 180px;
}

.ppms-activity-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.ppms-input {
    border: 1px solid var(--ppms-border, #e5e7eb);
    border-radius: 8px;
    padding: 0.4rem 0.55rem;
    font: inherit;
    min-width: 0;
    background: var(--ppms-surface, #fff);
}

.ppms-activity-day {
    font-weight: 600;
    font-size: 0.95rem;
    margin: 1.25rem 0 0.5rem;
    color: var(--ppms-heading, #111827);
}

.ppms-activity-timeline {
    list-style: none;
    margin: 0;
    padding: 0;
}

.ppms-activity-item {
    position: relative;
    padding-left: 1.25rem;
    padding-bottom: 1.25rem;
}

.ppms-activity-item.unread .ppms-activity-card {
    border-color: #93c5fd;
    background: #f8fafc;
}

.ppms-activity-line {
    position: absolute;
    left: 0.35rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--ppms-border, #e5e7eb);
}

.ppms-activity-item:last-child .ppms-activity-line {
    bottom: auto;
    height: 100%;
}

.ppms-activity-card {
    border: 1px solid var(--ppms-border, #e5e7eb);
    border-radius: 12px;
    padding: 0.85rem 1rem;
    background: var(--ppms-surface, #fff);
}

.ppms-activity-card-head {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
}

.ppms-activity-avatar {
    width: 40px;
    height: 40px;
    border-radius: 999px;
    background: #e0e7ff;
    color: #3730a3;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
}

.ppms-activity-head-text {
    flex: 1;
    min-width: 0;
}

.ppms-activity-actor-line {
    margin: 0 0 0.35rem;
    font-size: 0.95rem;
    line-height: 1.45;
}

.ppms-activity-verb {
    font-weight: 600;
    margin: 0 0.2rem;
}

.ppms-activity-subject-link {
    font-weight: 600;
    margin-left: 0.25rem;
}

.ppms-activity-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    font-size: 0.8rem;
}

.ppms-activity-badge {
    display: inline-block;
    padding: 0.1rem 0.45rem;
    border-radius: 6px;
    font-size: 0.72rem;
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
    margin: 0.75rem 0 0;
    padding: 0.5rem 0 0;
    list-style: none;
    border-top: 1px dashed var(--ppms-border, #e5e7eb);
}

.ppms-activity-change {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    margin-bottom: 0.45rem;
    font-size: 0.88rem;
}

.ppms-activity-change-field {
    font-weight: 600;
    color: var(--ppms-heading, #111827);
}

.ppms-activity-diff {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    align-items: baseline;
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
}

.ppms-activity-empty,
.ppms-activity-end {
    text-align: center;
    padding: 1rem;
    font-size: 0.9rem;
}
</style>
