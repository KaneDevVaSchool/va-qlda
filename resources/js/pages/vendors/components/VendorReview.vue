<template>
    <div class="vm-reviews">
        <form v-if="canReview" class="vm-reviews__form" @submit.prevent="submit">
            <p class="vm-reviews__intro">{{ t('vendors.reviewFormHint') }}</p>

            <div class="vm-reviews__fields">
                <div class="vm-reviews__field">
                    <label class="vm-field__label" for="vm-rev-summary">{{ t('vendors.reviewSummary') }}</label>
                    <p id="vm-rev-sum-hint" class="vm-field__hint">{{ t('vendors.reviewSummaryHint') }}</p>
                    <input
                        id="vm-rev-summary"
                        v-model="form.summary"
                        type="text"
                        class="ppms-input vm-reviews__input"
                        maxlength="255"
                        :placeholder="t('vendors.reviewSummaryPlaceholder')"
                        :title="t('vendors.reviewSummaryHint')"
                        aria-describedby="vm-rev-sum-hint"
                    />
                </div>

                <div class="vm-reviews__field">
                    <label class="vm-field__label" for="vm-rev-ctx">{{ t('vendors.reviewContext') }}</label>
                    <p id="vm-rev-ctx-hint" class="vm-field__hint">{{ t('vendors.reviewContextHint') }}</p>
                    <select
                        id="vm-rev-ctx"
                        v-model="form.context"
                        class="ppms-input vm-reviews__input"
                        :title="t('vendors.reviewContextHint')"
                        aria-describedby="vm-rev-ctx-hint"
                    >
                        <option value="">{{ t('vendors.reviewContextUnset') }}</option>
                        <option value="procurement">{{ t('vendors.reviewContextProcurement') }}</option>
                        <option value="implementation">{{ t('vendors.reviewContextImplementation') }}</option>
                        <option value="support">{{ t('vendors.reviewContextSupport') }}</option>
                        <option value="renewal">{{ t('vendors.reviewContextRenewal') }}</option>
                        <option value="other">{{ t('vendors.reviewContextOther') }}</option>
                    </select>
                </div>

                <div class="vm-reviews__field">
                    <label class="vm-field__label" for="vm-rev-rating-label">
                        {{ t('vendors.reviewRatingOverall') }} (1–5)
                        <abbr class="vm-reviews__req" :title="t('vendors.requiredField')">*</abbr>
                    </label>
                    <p id="vm-rev-r-hint" class="vm-field__hint">{{ t('vendors.reviewRatingHint') }}</p>
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

                <div class="vm-reviews__dims">
                    <p class="vm-reviews__dims-title">{{ t('vendors.reviewDimensionsTitle') }}</p>
                    <p class="vm-reviews__dims-intro">{{ t('vendors.reviewDimensionsIntro') }}</p>
                    <div class="vm-reviews__dims-grid">
                        <div v-for="dim in dimensionFields" :key="dim.key" class="vm-reviews__dim">
                            <span class="vm-reviews__dim-label" :id="'vm-rev-d-' + dim.key">{{ dim.label }}</span>
                            <div class="vm-reviews__mini-stars" role="group" :aria-labelledby="'vm-rev-d-' + dim.key">
                                <button
                                    v-for="n in 5"
                                    :key="dim.key + '-' + n"
                                    type="button"
                                    class="vm-reviews__mini-btn"
                                    :class="{ 'vm-reviews__mini-btn--on': form[dim.key] >= n }"
                                    :aria-label="dim.label + ' ' + n"
                                    @click="form[dim.key] = n"
                                >
                                    <span aria-hidden="true">★</span>
                                </button>
                            </div>
                            <button type="button" class="vm-reviews__dim-clear" @click="form[dim.key] = 0">
                                {{ t('vendors.reviewDimClear') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="vm-reviews__field">
                    <label class="vm-field__label" for="vm-rev-rec">{{ t('vendors.reviewRecommend') }}</label>
                    <p id="vm-rev-rec-hint" class="vm-field__hint">{{ t('vendors.reviewRecommendHint') }}</p>
                    <select
                        id="vm-rev-rec"
                        v-model="form.would_recommend"
                        class="ppms-input vm-reviews__input"
                        aria-describedby="vm-rev-rec-hint"
                    >
                        <option value="">{{ t('vendors.reviewRecommendUnset') }}</option>
                        <option value="yes">{{ t('vendors.reviewRecommendYes') }}</option>
                        <option value="no">{{ t('vendors.reviewRecommendNo') }}</option>
                    </select>
                </div>

                <div class="vm-reviews__field vm-reviews__field--wide">
                    <label class="vm-field__label" for="vm-rev-body">
                        {{ t('vendors.reviewBody') }}
                        <abbr class="vm-reviews__req" :title="t('vendors.requiredField')">*</abbr>
                    </label>
                    <p id="vm-rev-b-hint" class="vm-field__hint">{{ t('vendors.reviewBodyHint') }}</p>
                    <textarea
                        id="vm-rev-body"
                        v-model="form.body"
                        rows="5"
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

        <p v-if="!items.length" class="vm-reviews__empty ppms-muted">{{ t('vendors.reviewsEmpty') }}</p>
        <ul v-else class="vm-reviews__cards" role="list">
            <li v-for="r in items" :key="r.id" class="vm-reviews__card">
                <div class="vm-reviews__card-head">
                    <div class="vm-reviews__card-main">
                        <p v-if="r.summary" class="vm-reviews__card-title">{{ r.summary }}</p>
                        <div class="vm-reviews__card-rating" :aria-label="t('vendors.reviewRating')">
                            <span
                                v-for="n in 5"
                                :key="n"
                                class="vm-reviews__star-static"
                                :class="{ 'vm-reviews__star-static--on': n <= starDisplay(r.rating) }"
                                aria-hidden="true"
                            >★</span>
                            <span class="vm-reviews__rating-num vm-reviews__rating-num--inline">{{ formatRating(r.rating) }}</span>
                            <span v-if="r.context" class="vm-reviews__ctx-badge">{{ contextLabel(r.context) }}</span>
                        </div>
                    </div>
                    <div class="vm-reviews__card-meta">
                        <span class="vm-reviews__author">{{ r.author?.name || '—' }}</span>
                        <span class="vm-reviews__date">{{ formatDate(r.created_at) }}</span>
                    </div>
                </div>
                <div v-if="hasSubScores(r)" class="vm-reviews__subscores">
                    <span v-if="r.quality_score" class="vm-reviews__subpill">{{ t('vendors.reviewDimQualityShort') }}: {{ r.quality_score }}</span>
                    <span v-if="r.delivery_score" class="vm-reviews__subpill">{{ t('vendors.reviewDimDeliveryShort') }}: {{ r.delivery_score }}</span>
                    <span v-if="r.communication_score" class="vm-reviews__subpill">{{ t('vendors.reviewDimCommShort') }}: {{ r.communication_score }}</span>
                    <span v-if="r.would_recommend === true" class="vm-reviews__subpill vm-reviews__subpill--pos">{{ t('vendors.reviewRecommendYes') }}</span>
                    <span v-else-if="r.would_recommend === false" class="vm-reviews__subpill vm-reviews__subpill--neg">{{ t('vendors.reviewRecommendNo') }}</span>
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
import { computed, reactive, ref, watch } from 'vue';
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
    summary: '',
    context: '',
    rating: 4,
    quality_score: 0,
    delivery_score: 0,
    communication_score: 0,
    would_recommend: '',
    body: '',
});

const dimensionFields = computed(() => [
    { key: 'quality_score', label: t('vendors.reviewDimQuality') },
    { key: 'delivery_score', label: t('vendors.reviewDimDelivery') },
    { key: 'communication_score', label: t('vendors.reviewDimCommunication') },
]);

const sending = ref(false);

watch(
    () => props.vendorId,
    () => {
        resetForm();
    }
);

function resetForm() {
    form.summary = '';
    form.context = '';
    form.rating = 4;
    form.quality_score = 0;
    form.delivery_score = 0;
    form.communication_score = 0;
    form.would_recommend = '';
    form.body = '';
}

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

function contextLabel(ctx) {
    const map = {
        procurement: 'vendors.reviewContextProcurement',
        implementation: 'vendors.reviewContextImplementation',
        support: 'vendors.reviewContextSupport',
        renewal: 'vendors.reviewContextRenewal',
        other: 'vendors.reviewContextOther',
    };
    const key = map[ctx];
    return key ? t(key) : ctx;
}

function hasSubScores(r) {
    return (
        (r.quality_score != null && r.quality_score !== '')
        || (r.delivery_score != null && r.delivery_score !== '')
        || (r.communication_score != null && r.communication_score !== '')
        || r.would_recommend === true
        || r.would_recommend === false
    );
}

function canDelete(r) {
    if (!props.me) return false;
    if (['admin', 'pm', 'tl'].includes(props.me.role)) return true;
    return r.author?.id === props.me.id;
}

function optionalDim(v) {
    if (v === 0 || v === null || v === undefined || v === '') return undefined;
    return v;
}

function payloadFromForm() {
    let rec = undefined;
    if (form.would_recommend === 'yes') rec = true;
    if (form.would_recommend === 'no') rec = false;

    return {
        rating: form.rating,
        summary: form.summary.trim() || undefined,
        context: form.context || undefined,
        quality_score: optionalDim(form.quality_score),
        delivery_score: optionalDim(form.delivery_score),
        communication_score: optionalDim(form.communication_score),
        would_recommend: rec,
        body: form.body,
    };
}

async function submit() {
    sending.value = true;
    try {
        await axios.post(`/api/vendors/${props.vendorId}/reviews`, payloadFromForm());
        resetForm();
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
    border: 1px solid var(--ppms-border, #e2e8f0);
    background: linear-gradient(180deg, #fafbfc 0%, #f8fafc 100%);
    margin-bottom: 1rem;
}
.vm-reviews__intro {
    margin: 0 0 1rem;
    font-size: 0.875rem;
    line-height: 1.5;
    color: var(--ppms-muted, #64748b);
}
.vm-reviews__fields {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.vm-reviews__field {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.vm-reviews__field--wide {
    width: 100%;
}
.vm-field__label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--ppms-text, #0f172a);
}
.vm-field__hint {
    margin: 0;
    font-size: 0.75rem;
    line-height: 1.45;
    color: var(--ppms-muted, #64748b);
}
.vm-reviews__req {
    text-decoration: none;
    font-weight: 700;
    color: var(--ppms-danger, #b91c1c);
    cursor: help;
    margin-left: 0.15rem;
}
.vm-reviews__input {
    width: 100%;
    min-height: 2.5rem;
    box-sizing: border-box;
    font-size: 0.875rem;
    color: var(--ppms-text, #0f172a);
}
.vm-reviews__textarea {
    min-height: 7rem;
    resize: vertical;
    width: 100%;
    box-sizing: border-box;
    font-size: 0.875rem;
    line-height: 1.5;
    color: var(--ppms-text, #0f172a);
}
.vm-reviews__dims {
    padding: 0.85rem 0.9rem;
    border-radius: 8px;
    border: 1px dashed var(--ppms-border, #cbd5e1);
    background: rgba(248, 250, 252, 0.8);
}
.vm-reviews__dims-title {
    margin: 0;
    font-size: 0.8125rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    color: var(--ppms-muted, #64748b);
}
.vm-reviews__dims-intro {
    margin: 0.35rem 0 0.75rem;
    font-size: 0.75rem;
    line-height: 1.45;
    color: var(--ppms-muted, #64748b);
}
.vm-reviews__dims-grid {
    display: grid;
    gap: 0.85rem;
    grid-template-columns: 1fr;
}
@media (min-width: 720px) {
    .vm-reviews__dims-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
.vm-reviews__dim {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    min-width: 0;
}
.vm-reviews__dim-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--ppms-text, #0f172a);
}
.vm-reviews__mini-stars {
    display: flex;
    flex-wrap: wrap;
    gap: 0.1rem;
}
.vm-reviews__mini-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.85rem;
    height: 1.85rem;
    padding: 0;
    border: none;
    border-radius: 6px;
    background: rgba(148, 163, 184, 0.12);
    cursor: pointer;
    color: rgba(148, 163, 184, 0.85);
    font-size: 0.95rem;
    transition:
        color 0.12s ease,
        background 0.12s ease;
}
.vm-reviews__mini-btn:hover {
    background: rgba(251, 191, 36, 0.2);
    color: #d97706;
}
.vm-reviews__mini-btn--on {
    background: rgba(251, 191, 36, 0.28);
    color: #ea580c;
}
.vm-reviews__dim-clear {
    align-self: flex-start;
    padding: 0;
    border: none;
    background: none;
    font-size: 0.75rem;
    color: var(--ppms-muted, #64748b);
    text-decoration: underline;
    cursor: pointer;
}
.vm-reviews__dim-clear:hover {
    color: var(--ppms-text, #0f172a);
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
.vm-reviews__actions {
    margin-top: 1rem;
    padding-top: 0.85rem;
    border-top: 1px solid var(--ppms-border, #e2e8f0);
}
.vm-reviews__empty {
    margin: 0.5rem 0 0;
    font-size: 0.875rem;
}
.vm-reviews__cards {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}
.vm-reviews__card {
    border: 1px solid var(--ppms-border, #e2e8f0);
    border-radius: 10px;
    padding: 0.85rem 1rem;
    background: #fff;
}
.vm-reviews__card-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.65rem 1rem;
    margin-bottom: 0.5rem;
}
.vm-reviews__card-main {
    min-width: 0;
    flex: 1 1 12rem;
}
.vm-reviews__card-title {
    margin: 0 0 0.35rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--ppms-text, #0f172a);
    line-height: 1.35;
}
.vm-reviews__card-rating {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.15rem 0.5rem;
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
.vm-reviews__ctx-badge {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 0.15rem 0.45rem;
    border-radius: 999px;
    background: #eef2ff;
    color: #4338ca;
}
.vm-reviews__card-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.15rem;
    text-align: right;
}
.vm-reviews__author {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--ppms-text, #0f172a);
}
.vm-reviews__date {
    font-size: 0.8125rem;
    color: var(--ppms-muted, #64748b);
}
.vm-reviews__subscores {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-bottom: 0.5rem;
}
.vm-reviews__subpill {
    font-size: 0.75rem;
    padding: 0.15rem 0.45rem;
    border-radius: 6px;
    background: #f1f5f9;
    color: #475569;
}
.vm-reviews__subpill--pos {
    background: #ecfdf5;
    color: #047857;
}
.vm-reviews__subpill--neg {
    background: #fef2f2;
    color: #b91c1c;
}
.vm-reviews__body {
    margin: 0;
    white-space: pre-wrap;
    font-size: 0.875rem;
    line-height: 1.55;
    color: var(--ppms-text, #0f172a);
}
</style>
