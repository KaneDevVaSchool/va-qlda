<template>
    <div v-if="open" class="ppms-modal-backdrop" role="presentation" @click.self="open = false">
        <div class="ppms-modal" role="dialog" aria-modal="true" aria-labelledby="ppms-modal-save-view-title">
            <h2 id="ppms-modal-save-view-title">{{ t('projects.saveViewBtn') }}</h2>
            <label class="ppms-field">
                <span>{{ t('projects.saveViewPrompt') }}</span>
                <input v-model="name" type="text" maxlength="80" @keyup.enter="$emit('confirm')" />
            </label>
            <div class="ppms-modal-actions">
                <button type="button" class="ppms-btn-ghost" @click="open = false">{{ t('common.cancel') }}</button>
                <button type="button" class="ppms-btn-primary" @click="$emit('confirm')">{{ t('common.save') }}</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const open = defineModel('open', { type: Boolean, default: false });
const name = defineModel('name', { type: String, default: '' });

defineEmits(['confirm']);
</script>
