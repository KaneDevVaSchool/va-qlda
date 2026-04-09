<template>
    <div ref="root" class="ppms-notify-dd">
        <button
            type="button"
            class="ppms-header-notify ppms-notify-dd-trigger"
            :aria-expanded="open"
            aria-haspopup="true"
            :aria-label="t('common.notifications')"
            @click="toggle"
        >
            <span class="ppms-icon-bell" aria-hidden="true" />
            <span v-if="unread > 0" class="ppms-header-badge">{{ unread > 9 ? '9+' : unread }}</span>
        </button>
        <div v-if="open" class="ppms-notify-dd-panel" role="menu">
            <div class="ppms-notify-dd-head">
                <strong>{{ t('activityFeed.dropdownTitle') }}</strong>
                <router-link to="/notifications" class="ppms-notify-dd-all" @click="close">
                    {{ t('activityFeed.viewAll') }}
                </router-link>
            </div>
            <div v-if="loading" class="ppms-notify-dd-loading">{{ t('common.loading') }}</div>
            <ul v-else class="ppms-notify-dd-list">
                <li v-for="item in items" :key="item.id">
                    <button type="button" class="ppms-notify-dd-item" @click="onPick(item)">
                        <span
                            class="ppms-notify-dd-dot"
                            :class="'ppms-notify-dd-dot--' + (item.kind_color || 'yellow')"
                            aria-hidden="true"
                        />
                        <span class="ppms-notify-dd-body">
                            <span class="ppms-notify-dd-title">{{ summaryLine(item) }}</span>
                            <span class="ppms-notify-dd-time">{{ rel(item.created_at) }}</span>
                        </span>
                    </button>
                </li>
                <li v-if="!loading && items.length === 0" class="ppms-notify-dd-empty">
                    {{ t('activityFeed.empty') }}
                </li>
            </ul>
            <div class="ppms-notify-dd-foot">
                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="markAll">
                    {{ t('activityFeed.markAllRead') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { formatRelativeTime } from '@/utils/relativeTime';
import { ppmsToastSuccess } from '@/ppmsUi';

defineProps({
    unread: { type: Number, default: 0 },
});

const emit = defineEmits(['refresh']);

const { t, locale } = useI18n();
const router = useRouter();

const open = ref(false);
const loading = ref(false);
const items = ref([]);
const root = ref(null);

function rel(iso) {
    return formatRelativeTime(iso, locale.value === 'en' ? 'en' : 'vi');
}

function kindVerb(kind) {
    const k = `activityFeed.kindVerb.${kind || 'updated'}`;
    const tr = t(k);
    return tr === k ? t(`activityFeed.kind.${kind || 'updated'}`) : tr;
}

function summaryLine(item) {
    const actor = item.actor?.name || t('activityFeed.systemUser');
    const verb = kindVerb(item.activity_kind);
    const st = t(`activityFeed.subject.${item.subject_type}`);
    const label = item.subject_label || '—';
    return `${actor} ${verb} ${st} ${label}`;
}

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/activity-feed', {
            params: { per_page: 12 },
        });
        items.value = data.data || [];
    } catch {
        items.value = [];
    } finally {
        loading.value = false;
    }
}

function toggle() {
    open.value = !open.value;
    if (open.value) {
        load();
    }
}

function close() {
    open.value = false;
}

function onDocClick(e) {
    if (!open.value || !root.value) {
        return;
    }
    if (!root.value.contains(e.target)) {
        open.value = false;
    }
}

async function onPick(item) {
    close();
    if (!item.read) {
        try {
            await axios.patch(`/api/activity-feed/${item.id}/read`);
            emit('refresh');
        } catch {
            /* ignore */
        }
    }
    if (item.link) {
        router.push(item.link);
    } else {
        router.push({ name: 'notifications', query: { focus: String(item.id) } });
    }
}

async function markAll() {
    try {
        await axios.post('/api/activity-feed/read-all', {});
        ppmsToastSuccess(t('activityFeed.markedAll'));
        emit('refresh');
        await load();
    } catch {
        /* ignore */
    }
    close();
}

watch(open, (v) => {
    if (typeof document === 'undefined') {
        return;
    }
    document.body.classList.toggle('ppms-notify-dd-open', v);
});

onMounted(() => {
    document.addEventListener('click', onDocClick, true);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocClick, true);
    if (typeof document !== 'undefined') {
        document.body.classList.remove('ppms-notify-dd-open');
    }
});
</script>

<style scoped>
.ppms-notify-dd {
    position: relative;
}

.ppms-notify-dd-trigger {
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.5rem;
    color: inherit;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.ppms-notify-dd-trigger:focus-visible {
    outline: 2px solid var(--ppms-focus, #2563eb);
    outline-offset: 2px;
}

.ppms-notify-dd-panel {
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    width: min(380px, calc(100vw - 24px));
    max-height: min(70vh, 480px);
    display: flex;
    flex-direction: column;
    background: var(--ppms-surface, #fff);
    border: 1px solid var(--ppms-border, #e5e7eb);
    border-radius: 12px;
    box-shadow: 0 12px 40px rgba(15, 23, 42, 0.12);
    z-index: 60;
}

.ppms-notify-dd-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--ppms-border, #e5e7eb);
    font-size: 0.95rem;
}

.ppms-notify-dd-all {
    font-size: 0.85rem;
    color: var(--ppms-link, #2563eb);
    text-decoration: none;
}

.ppms-notify-dd-all:hover {
    text-decoration: underline;
}

.ppms-notify-dd-loading {
    padding: 1rem;
    color: var(--ppms-muted, #6b7280);
    font-size: 0.9rem;
}

.ppms-notify-dd-list {
    list-style: none;
    margin: 0;
    padding: 0;
    overflow-y: auto;
    flex: 1;
}

.ppms-notify-dd-item {
    display: flex;
    gap: 0.5rem;
    width: 100%;
    text-align: left;
    padding: 0.65rem 1rem;
    border: none;
    background: transparent;
    cursor: pointer;
    border-bottom: 1px solid var(--ppms-border-subtle, #f3f4f6);
    color: inherit;
    font: inherit;
}

.ppms-notify-dd-item:hover {
    background: var(--ppms-hover, #f9fafb);
}

.ppms-notify-dd-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    margin-top: 0.35rem;
    flex-shrink: 0;
}

.ppms-notify-dd-dot--green {
    background: #16a34a;
}

.ppms-notify-dd-dot--yellow {
    background: #ca8a04;
}

.ppms-notify-dd-dot--red {
    background: #dc2626;
}

.ppms-notify-dd-body {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}

.ppms-notify-dd-title {
    font-size: 0.88rem;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.ppms-notify-dd-time {
    font-size: 0.75rem;
    color: var(--ppms-muted, #6b7280);
}

.ppms-notify-dd-empty {
    padding: 1rem;
    font-size: 0.9rem;
    color: var(--ppms-muted, #6b7280);
}

.ppms-notify-dd-foot {
    padding: 0.5rem 0.75rem;
    border-top: 1px solid var(--ppms-border, #e5e7eb);
    display: flex;
    justify-content: flex-end;
}
</style>
