<template>
    <router-link v-if="!isDisabled" :to="to" :title="title">
        <slot />
    </router-link>
    <span
        v-else
        class="ppms-nav-item--disabled"
        role="link"
        aria-disabled="true"
        tabindex="-1"
        :title="t('layout.navDisabledMaint')"
    >
        <slot />
    </span>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { canBypassModuleMaintenance, isModuleUnderMaintenance } from '../appBootstrap';

const props = defineProps({
    /** Null = không áp dụng bảo trì (luôn click được). */
    moduleKey: {
        type: String,
        default: null,
    },
    to: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
});

const { t } = useI18n();

const isDisabled = computed(() => {
    if (!props.moduleKey) {
        return false;
    }
    if (!isModuleUnderMaintenance(props.moduleKey)) {
        return false;
    }
    return !canBypassModuleMaintenance();
});
</script>
