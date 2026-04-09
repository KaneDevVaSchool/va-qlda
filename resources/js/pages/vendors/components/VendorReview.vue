<template>
    <div class="vm-reviews">
        <form v-if="canReview" class="vm-reviews__form ppms-stack" @submit.prevent="submit">
            <label class="ppms-field">
                <span>{{ t('vendors.reviewRating') }} (1–5)</span>
                <input v-model.number="form.rating" type="number" min="1" max="5" step="0.1" required class="ppms-input" />
            </label>
            <label class="ppms-field">
                <span>{{ t('vendors.reviewBody') }}</span>
                <textarea v-model="form.body" rows="3" required class="ppms-input" />
            </label>
            <button type="submit" class="ppms-btn-primary" :disabled="sending">{{ t('vendors.reviewSubmit') }}</button>
        </form>

        <p v-if="!items.length" class="ppms-muted ppms-mt">{{ t('vendors.reviewsEmpty') }}</p>
        <ul v-else class="vm-reviews__cards" role="list">
            <li v-for="r in items" :key="r.id" class="vm-reviews__card">
                <div class="vm-reviews__card-head">
                    <strong class="vm-reviews__rating">{{ r.rating }}</strong>
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
    align-items: baseline;
    gap: 0.5rem 1rem;
    margin-bottom: 0.35rem;
}
.vm-reviews__rating {
    font-size: 1.1rem;
}
.vm-reviews__author {
    font-weight: 600;
}
.vm-reviews__date {
    font-size: 0.85rem;
    color: var(--ppms-muted, #5c6470);
}
.vm-reviews__body {
    margin: 0;
    white-space: pre-wrap;
    font-size: 0.95rem;
}
</style>
