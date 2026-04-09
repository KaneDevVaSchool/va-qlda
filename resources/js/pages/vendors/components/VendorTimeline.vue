<template>
    <div class="vm-timeline">
        <div v-if="loading" class="ppms-loading-line" role="status">{{ t('common.loading') }}</div>
        <p v-else-if="err" class="ppms-error">{{ err }}</p>
        <p v-else-if="!grouped.length" class="ppms-muted">{{ t('vendors.timelineEmpty') }}</p>
        <div v-else class="vm-timeline__groups">
            <section v-for="grp in grouped" :key="grp.phase" class="vm-timeline__group">
                <h3 class="vm-timeline__group-title">{{ phaseLabel(grp.phase) }}</h3>
                <ul class="vm-timeline__list" role="list">
                    <li
                        v-for="ev in grp.items"
                        :key="ev.id"
                        class="vm-timeline__item"
                        :class="{ 'vm-timeline__item--current': ev.is_current }"
                    >
                        <div class="vm-timeline__dot" aria-hidden="true" />
                        <div class="vm-timeline__body">
                            <div class="vm-timeline__time">{{ formatVn(ev.occurred_at) }}</div>
                            <div v-if="ev.performed_by" class="vm-timeline__actor">
                                {{ ev.performed_by.name }}
                            </div>
                            <div v-if="ev.note" class="vm-timeline__note">{{ ev.note }}</div>
                            <div v-if="ev.is_current" class="vm-timeline__badge">{{ t('vendors.timelineCurrent') }}</div>
                            <div v-if="canEdit" class="vm-timeline__actions">
                                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="$emit('delete', ev)">
                                    {{ t('vendors.timelineDelete') }}
                                </button>
                            </div>
                        </div>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    events: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    err: { type: String, default: '' },
    canEdit: { type: Boolean, default: false },
});

defineEmits(['delete']);

const { t } = useI18n();

const PHASE_ORDER = [
    'potential_contact',
    'survey_consult',
    'quotation',
    'negotiation',
    'contract_signed',
    'payment_acceptance',
    'no_contract',
    'research_update',
];

function phaseLabel(phase) {
    const map = {
        potential_contact: 'vendors.phasePotentialContact',
        survey_consult: 'vendors.phaseSurveyConsult',
        quotation: 'vendors.phaseQuotation',
        negotiation: 'vendors.phaseNegotiation',
        contract_signed: 'vendors.phaseContractSigned',
        payment_acceptance: 'vendors.phasePaymentAcceptance',
        no_contract: 'vendors.phaseNoContract',
        research_update: 'vendors.phaseResearchUpdate',
    };
    const key = map[phase];
    return key ? t(key) : phase;
}

/** HH:mm — dd/MM/yyyy (Asia/Ho_Chi_Minh) */
function formatVn(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '—';
    const fmt = new Intl.DateTimeFormat('en-GB', {
        timeZone: 'Asia/Ho_Chi_Minh',
        hour: '2-digit',
        minute: '2-digit',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
    const parts = fmt.formatToParts(d);
    const g = (t) => parts.find((p) => p.type === t)?.value ?? '';
    return `${g('hour')}:${g('minute')} — ${g('day')}/${g('month')}/${g('year')}`;
}

const grouped = computed(() => {
    const by = new Map();
    for (const ev of props.events) {
        const p = ev.phase || 'unknown';
        if (!by.has(p)) {
            by.set(p, []);
        }
        by.get(p).push(ev);
    }
    const keys = [...by.keys()].sort((a, b) => {
        const ia = PHASE_ORDER.indexOf(a);
        const ib = PHASE_ORDER.indexOf(b);
        const sa = ia === -1 ? 999 : ia;
        const sb = ib === -1 ? 999 : ib;
        return sa - sb;
    });
    return keys.map((phase) => ({
        phase,
        items: (by.get(phase) || []).sort(
            (x, y) => new Date(y.occurred_at).getTime() - new Date(x.occurred_at).getTime()
        ),
    }));
});
</script>

<style scoped>
.vm-timeline__groups {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
.vm-timeline__group-title {
    margin: 0 0 0.5rem;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--ppms-muted, #5c6470);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.vm-timeline__list {
    list-style: none;
    margin: 0;
    padding: 0;
    border-left: 2px solid var(--ppms-border, #e2e6ea);
    padding-left: 1rem;
}
.vm-timeline__item {
    position: relative;
    padding-bottom: 1rem;
}
.vm-timeline__item--current .vm-timeline__body {
    background: rgba(59, 130, 246, 0.08);
    border-radius: 8px;
    padding: 0.5rem 0.75rem;
}
.vm-timeline__dot {
    position: absolute;
    left: -1.05rem;
    top: 0.35rem;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--ppms-accent, #2563eb);
    border: 2px solid #fff;
    box-shadow: 0 0 0 1px var(--ppms-border, #e2e6ea);
}
.vm-timeline__time {
    font-weight: 600;
    font-size: 0.95rem;
}
.vm-timeline__actor {
    font-size: 0.9rem;
    color: var(--ppms-muted, #5c6470);
}
.vm-timeline__note {
    margin-top: 0.35rem;
    white-space: pre-wrap;
    font-size: 0.92rem;
}
.vm-timeline__badge {
    display: inline-block;
    margin-top: 0.35rem;
    font-size: 0.75rem;
    padding: 0.15rem 0.45rem;
    border-radius: 4px;
    background: #dbeafe;
    color: #1d4ed8;
}
.vm-timeline__actions {
    margin-top: 0.35rem;
}
</style>
