<template>
    <div class="ppms-pl-inline-actions-bar">
        <button
            type="button"
            class="ppms-pl-inline-icon-btn ppms-pl-inline-icon-btn--ok"
            :disabled="disabled"
            :title="t('projects.inlineSave')"
            :aria-label="t('projects.inlineSave')"
            @click.stop="$emit('save')"
        >
            <svg
                class="ppms-pl-inline-icon-svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.25"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="M20 6 9 17l-5-5" />
            </svg>
        </button>
        <button
            type="button"
            class="ppms-pl-inline-icon-btn ppms-pl-inline-icon-btn--cancel"
            :disabled="disabled"
            :title="t('projects.inlineCancel')"
            :aria-label="t('projects.inlineCancel')"
            @click.stop="$emit('cancel')"
        >
            <svg
                class="ppms-pl-inline-icon-svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.25"
                stroke-linecap="round"
                aria-hidden="true"
            >
                <path d="M18 6 6 18M6 6l12 12" />
            </svg>
        </button>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

defineProps({
    disabled: { type: Boolean, default: false },
});

defineEmits(['save', 'cancel']);

const { t } = useI18n();
</script>

<style scoped>
.ppms-pl-inline-actions-bar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    padding: 0.15rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(148, 163, 184, 0.45);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
}
.ppms-pl-inline-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.85rem;
    height: 1.85rem;
    padding: 0;
    margin: 0;
    border: none;
    border-radius: 999px;
    cursor: pointer;
    background: transparent;
    color: var(--ppms-muted, #64748b);
    transition:
        background 0.15s ease,
        color 0.15s ease,
        transform 0.1s ease;
}
.ppms-pl-inline-icon-btn:hover:not(:disabled) {
    transform: scale(1.05);
}
.ppms-pl-inline-icon-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
.ppms-pl-inline-icon-btn--ok {
    color: #047857;
    background: rgba(16, 185, 129, 0.12);
}
.ppms-pl-inline-icon-btn--ok:hover:not(:disabled) {
    background: rgba(16, 185, 129, 0.22);
    color: #065f46;
}
.ppms-pl-inline-icon-btn--cancel {
    color: #b91c1c;
    background: rgba(248, 113, 113, 0.12);
}
.ppms-pl-inline-icon-btn--cancel:hover:not(:disabled) {
    background: rgba(248, 113, 113, 0.22);
    color: #991b1b;
}
.ppms-pl-inline-icon-svg {
    width: 0.95rem;
    height: 0.95rem;
    flex-shrink: 0;
}
</style>
