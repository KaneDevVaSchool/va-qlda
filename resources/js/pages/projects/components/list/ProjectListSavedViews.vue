<template>
    <section v-if="savedViews.length" class="ppms-card ppms-mt ppms-saved-views ppms-saved-views--compact">
        <h3 class="ppms-saved-views-title">{{ t('projects.savedViewsTitle') }}</h3>
        <div class="ppms-saved-views-scroll">
            <ul class="ppms-saved-views-list">
                <li v-for="v in savedViews" :key="v.id">
                    <button type="button" class="ppms-linklike" @click="$emit('apply', v)">{{ v.name }}</button>
                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="$emit('remove', v.id)">
                        {{ t('projects.removeView') }}
                    </button>
                </li>
            </ul>
        </div>
    </section>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    savedViews: { type: Array, required: true },
});

defineEmits(['apply', 'remove']);
</script>
