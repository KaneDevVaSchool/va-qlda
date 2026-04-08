<template>
    <Teleport to="body">
        <div
            v-if="open"
            ref="rootEl"
            class="ppms-pl-user-popover"
            role="dialog"
            :aria-label="ariaLabel"
            :style="{ top: top + 'px', left: left + 'px' }"
            @click.stop
        >
            <template v-if="menuSlots.length && !user">
                <p class="ppms-pl-user-popover-extra-title">{{ t('projects.userPopoverPickParticipant') }}</p>
                <ul class="ppms-pl-user-popover-list ppms-pl-user-popover-pick-list">
                    <li v-for="(slot, pi) in menuSlots" :key="'ms-' + pi">
                        <button type="button" class="ppms-pl-user-popover-pick" @click="$emit('pick', slot)">
                            {{ participantSlotLabel(slot) }}
                        </button>
                    </li>
                </ul>
            </template>
            <template v-else>
                <div v-if="user" class="ppms-pl-user-popover-head">
                    <span
                        class="ppms-pl-avatar ppms-pl-avatar--sm"
                        :style="{ background: avatarColor(user?.name || user?.email || '?') }"
                        aria-hidden="true"
                    >
                        {{ userInitials(user?.name) }}
                    </span>
                    <div class="ppms-pl-user-popover-text">
                        <strong class="ppms-pl-user-popover-name">{{ user?.name || '—' }}</strong>
                        <p v-if="user?.email" class="ppms-pl-user-popover-email">{{ user.email }}</p>
                    </div>
                </div>
                <div v-if="stakeholders.length" class="ppms-pl-user-popover-extra">
                    <p class="ppms-pl-user-popover-extra-title">{{ t('projects.userPopoverOtherParticipants') }}</p>
                    <ul class="ppms-pl-user-popover-list">
                        <li v-for="(line, pi) in stakeholders" :key="'st-' + pi">{{ line }}</li>
                    </ul>
                </div>
            </template>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    open: { type: Boolean, required: true },
    top: { type: Number, default: 0 },
    left: { type: Number, default: 0 },
    mode: { type: String, default: 'admin' },
    user: { type: Object, default: null },
    menuSlots: { type: Array, default: () => [] },
    stakeholders: { type: Array, default: () => [] },
    participantSlotLabel: { type: Function, required: true },
    avatarColor: { type: Function, required: true },
    userInitials: { type: Function, required: true },
});

defineEmits(['pick']);

const rootEl = ref(null);

const ariaLabel = computed(() => {
    if (props.mode === 'admin') {
        return t('projects.avatarInfoAdmin');
    }
    if (props.menuSlots.length && !props.user) {
        return t('projects.userPopoverPickParticipant');
    }

    return t('projects.avatarInfoParticipants');
});

defineExpose({ rootEl });
</script>
