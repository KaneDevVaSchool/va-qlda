<template>
    <div class="vm-reviews">
        <form v-if="canReview" class="vm-reviews__form" @submit.prevent="submit">
            <p class="vm-reviews__intro">{{ t('vendors.reviewFormHint') }}</p>
            <div class="vm-reviews__grid">
                <div class="vm-reviews__field">
                    <label class="vm-reviews__label" for="vm-rev-rating-label">
                        {{ t('vendors.reviewRating') }} (1–5)
                        <abbr class="vm-reviews__req" :title="t('vendors.requiredField')">*</abbr>
                    </label>
                    <p id="vm-rev-r-hint" class="vm-reviews__hint">{{ t('vendors.reviewRatingHint') }}</p>
                    <div
                        id="vm-rev-rating-label"
                        class="vm-reviews__stars"
                        role="group"
                        :aria-label="t('vendors.reviewRatingStarsGroup')"
                        aria-describedby="vm-rev-r-hint"
                    >
                        <button
                            v-for="n in 5"
                            :key="n"
                            type="button"
                            class="vm-reviews__star-btn"
                            :class="{ 'vm-reviews__star-btn--active': form.rating >= n }"
                            :aria-label="t('vendors.reviewRatingPickStar', { n })"
                            @click="form.rating = n"
                        >
                            <span class="vm-reviews__star-icon" aria-hidden="true">★</span>
                        </button>
                    </div>
                    <p class="vm-reviews__rating-value" aria-live="polite">
                        <span class="vm-reviews__rating-num">{{ form.rating }}</span>
                        <span class="vm-reviews__rating-max">/ 5</span>
                    </p>
                </div>
                <div class="vm-reviews__field vm-reviews__field--wide">
                    <label class="vm-reviews__label" for="vm-rev-body">
                        {{ t('vendors.reviewBody') }}
                        <abbr class="vm-reviews__req" :title="t('vendors.requiredField')">*</abbr>
                    </label>
                    <p id="vm-rev-b-hint" class="vm-reviews__hint">{{ t('vendors.reviewBodyHint') }}</p>
                    <textarea
                        id="vm-rev-body"
                        v-model="form.body"
                        rows="4"
                        required
                        class="ppms-input vm-reviews__textarea"
                        :placeholder="t('vendors.reviewBodyPlaceholder')"
                        aria-describedby="vm-rev-b-hint"
                    />
                </div>
            </div>
            <div class="vm-reviews__actions">
                <button type="submit" class="ppms-btn-primary" :disabled="sending">{{ t('vendors.reviewSubmit') }}</button>
            </div>
        </form>

        <p v-if="!items.length" class="ppms-muted ppms-mt">{{ t('vendors.reviewsEmpty') }}</p>
        <ul v-else class="vm-reviews__cards" role="list">
            <li v-for="r in items" :key="r.id" class="vm-reviews__card">
                <div class="vm-reviews__card-head">
                    <div class="vm-reviews__card-rating" :aria-label="t('vendors.reviewRating')">
                        <span
                            v-for="n in 5"
                            :key="n"
                            class="vm-reviews__star-static"
                            :class="{ 'vm-reviews__star-static--on': n <= starDisplay(r.rating) }"
                            aria-hidden="true"
                        >★</span>
                        <span class="vm-reviews__rating-num vm-reviews__rating-num--inline">{{ formatRating(r.rating) }}</span>
                    </div>
                    <span class="vm-reviews__author">{{ r.author?.name || '—' }}</span>
                    <span class="vm-reviews__date">{{ formatDate(r.created_at) }}</span>
                </div>
                <p class="vm-reviews__body">{{ r.body }}</p>
                <button
                    v-if="canDelete(r)"
                    type="button"
                    class="ppms-btn-ghost ppms-btn-sm"
                    @click="$emit('delete', r)"
                >
                    {{ t('vendors.reviewDelete') }}
                </button>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { getApiErrorMessage } from '@/bootstrap';
import { ppmsToastSuccess, ppmsToastError } from '@/ppmsUi';

const props = defineProps({
    vendorId: { type: [Number, String], required: true },
    items: { type: Array, default: () => [] },
    canReview: { type: Boolean, default: false },
    me: { type: Object, default: null },
});

const emit = defineEmits(['delete', 'refresh']);

const { t } = useI18n();

const form = reactive({
    rating: 4,
    body: '',
});
const sending = ref(false);

watch(
    () => props.vendorId,
    () => {
        form.body = '';
    }
);

function formatDate(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(d);
}

function starDisplay(raw) {
    const x = Number(raw);
    if (Number.isNaN(x)) {
        return 0;
    }
    return Math.min(5, Math.max(0, Math.round(x)));
}

function formatRating(raw) {
    const x = Number(raw);
    if (Number.isNaN(x)) {
        return '—';
    }
    return Number.isInteger(x) ? String(x) : x.toFixed(1);
}

function canDelete(r) {
    if (!props.me) return false;
    if (['admin', 'pm', 'tl'].includes(props.me.role)) return true;
    return r.author?.id === props.me.id;
}

async function submit() {
    sending.value = true;
    try {
        await axios.post(`/api/vendors/${props.vendorId}/reviews`, {
            rating: form.rating,
            body: form.body,
        });
        form.body = '';
        ppmsToastSuccess(t('vendors.reviewPosted'));
        emit('refresh');
    } catch (e) {
        ppmsToastError(getApiErrorMessage(e));
    } finally {
        sending.value = false;
    }
}
</script>

<style scoped>
.vm-reviews__form {
    padding: 1rem 1.1rem;
    border-radius: 10px;
    border: 1px solid var(--ppms-border, #e2e6ea);
    background: linear-gradient(180deg, #fafbfc 0%, #f8fafc 100%);
    margin-bottom: 0.5rem;
}
.vm-reviews__intro {
    margin: 0 0 0.85rem;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: var(--ppms-muted, #64748b);
}
.vm-reviews__grid {
    display: grid;
    gap: 1rem 1.1rem;
    grid-template-columns: 1fr;
}
@media (min-width: 720px) {
    .vm-reviews__grid {
        grid-template-columns: minmax(11rem, 13rem) minmax(0, 1fr);
        align-items: start;
    }
    .vm-reviews__field--wide {
        grid-column: auto;
    }
}
.vm-reviews__field {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-width: 0;
}
.vm-reviews__label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--ppms-text, #0f172a);
}
.vm-reviews__req {
    text-decoration: none;
    font-weight: 700;
    color: var(--ppms-danger, #b91c1c);
    cursor: help;
    margin-left: 0.15rem;
}
.vm-reviews__hint {
    margin: 0;
    font-size: 0.75rem;
    line-height: 1.4;
    color: var(--ppms-muted, #64748b);
}
.vm-reviews__stars {
    display: flex;
    flex-wrap: wrap;
    gap: 0.15rem;
    align-items: center;
    margin-top: 0.15rem;
}
.vm-reviews__star-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.35rem;
    height: 2.35rem;
    padding: 0;
    border: none;
    border-radius: 8px;
    background: rgba(148, 163, 184, 0.12);
    cursor: pointer;
    color: rgba(148, 163, 184, 0.85);
    transition:
        color 0.12s ease,
        background 0.12s ease,
        transform 0.08s ease;
}
.vm-reviews__star-btn:hover {
    background: rgba(251, 191, 36, 0.2);
    color: #d97706;
}
.vm-reviews__star-btn:focus-visible {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
}
.vm-reviews__star-btn:active {
    transform: scale(0.96);
}
.vm-reviews__star-btn--active .vm-reviews__star-icon {
    color: #ea580c;
    text-shadow: 0 0 0 rgba(234, 88, 12, 0.35);
}
.vm-reviews__star-btn--active {
    background: rgba(251, 191, 36, 0.28);
}
.vm-reviews__star-icon {
    font-size: 1.35rem;
    line-height: 1;
}
.vm-reviews__rating-value {
    margin: 0.35rem 0 0;
    font-size: 0.8125rem;
    color: var(--ppms-muted, #64748b);
}
.vm-reviews__rating-num {
    font-weight: 700;
    font-size: 1rem;
    color: var(--ppms-text, #0f172a);
}
.vm-reviews__rating-num--inline {
    font-size: 0.9rem;
    margin-left: 0.35rem;
    color: var(--ppms-text, #0f172a);
}
.vm-reviews__rating-max {
    font-weight: 500;
    opacity: 0.75;
}
.vm-reviews__textarea {
    min-height: 6.5rem;
    resize: vertical;
    width: 100%;
    box-sizing: border-box;
}
.vm-reviews__actions {
    margin-top: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--ppms-border, #e2e6ea);
}
.vm-reviews__cards {
    list-style: none;
    margin: 1rem 0 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.vm-reviews__card {
    border: 1px solid var(--ppms-border, #e2e6ea);
    border-radius: 10px;
    padding: 0.75rem 1rem;
    background: #fff;
}
.vm-reviews__card-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem 1rem;
    margin-bottom: 0.35rem;
}
.vm-reviews__card-rating {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.05rem;
    min-width: 0;
}
.vm-reviews__star-static {
    font-size: 1rem;
    line-height: 1;
    color: rgba(203, 213, 225, 0.95);
}
.vm-reviews__star-static--on {
    color: #ea580c;
}
.vm-reviews__author {
    font-weight: 600;
}
.vm-reviews__date {
    font-size: 0.85rem;
    color: var(--ppms-muted, #5c6470);
    margin-left: auto;
}
.vm-reviews__body {
    margin: 0;
    white-space: pre-wrap;
    font-size: 0.95rem;
}
</style>
