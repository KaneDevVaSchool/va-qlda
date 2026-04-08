<template>
    <div class="ppms-pd-empty-state" role="region" :aria-labelledby="headingId">
        <div class="ppms-pd-empty-state__visual" aria-hidden="true" />
        <h3 :id="headingId" class="ppms-pd-empty-state__title">{{ title }}</h3>
        <p v-if="description" class="ppms-pd-empty-state__desc">{{ description }}</p>
        <div v-if="$slots.default" class="ppms-pd-empty-state__actions">
            <slot />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    /** id HTML cho tiêu đề (a11y). */
    headingId: { type: String, default: 'ppms-pd-empty-heading' },
});
</script>
