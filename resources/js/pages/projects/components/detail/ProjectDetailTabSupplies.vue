<!-- eslint-disable vue/no-mutating-props -- parent reactive supplyNew -->
<template>
    <div class="ppms-pd-tab-panel">
        <section class="ppms-card ppms-pd-section">
            <h2>{{ t('projects.pdTabSupplies') }}</h2>
            <p class="ppms-muted ppms-mt-sm">{{ t('projects.pdSuppliesHint') }}</p>
            <form v-if="canManageProject" class="ppms-task-form ppms-mt-sm" @submit.prevent="$emit('submit')">
                <input v-model="supplyNew.name" :placeholder="t('projects.pdSupplyName')" required />
                <input
                    v-model.number="supplyNew.quantity"
                    type="number"
                    min="0"
                    step="0.0001"
                    :placeholder="t('projects.pdSupplyQty')"
                />
                <input v-model="supplyNew.unit" :placeholder="t('projects.pdSupplyUnit')" />
                <input v-model="supplyNew.notes" :placeholder="t('projects.pdSupplyNotes')" />
                <button type="submit" class="ppms-btn-primary ppms-btn-sm">{{ t('projects.pdSupplyAdd') }}</button>
            </form>
            <div v-if="!projectSupplies.length" class="ppms-muted ppms-mt-sm">{{ t('projects.pdSuppliesEmpty') }}</div>
            <div v-else class="ppms-table-scroll ppms-mt-sm">
                <table class="ppms-table">
                    <thead>
                        <tr>
                            <th>{{ t('projects.pdSupplyName') }}</th>
                            <th>{{ t('projects.pdSupplyQty') }}</th>
                            <th>{{ t('projects.pdSupplyUnit') }}</th>
                            <th>{{ t('projects.pdSupplyNotes') }}</th>
                            <th v-if="canManageProject"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in projectSupplies" :key="'sup-' + s.id">
                            <td>{{ s.name }}</td>
                            <td>{{ s.quantity }}</td>
                            <td>{{ s.unit || '—' }}</td>
                            <td class="ppms-muted">{{ s.notes || '—' }}</td>
                            <td v-if="canManageProject">
                                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="$emit('delete', s)">
                                    {{ t('common.delete') }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    projectSupplies: { type: Array, required: true },
    canManageProject: { type: Boolean, default: false },
    supplyNew: { type: Object, required: true },
});

defineEmits(['submit', 'delete']);
</script>
