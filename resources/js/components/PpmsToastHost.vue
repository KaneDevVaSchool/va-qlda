<template>
    <Teleport to="body">
        <div class="ppms-toast-host" aria-live="polite" aria-relevant="additions text">
            <TransitionGroup name="ppms-toast">
                <div
                    v-for="t in toastState.items"
                    :key="t.id"
                    :class="['ppms-toast', `ppms-toast--${t.type}`]"
                    role="status"
                >
                    <span class="ppms-toast-msg">{{ t.message }}</span>
                    <button type="button" class="ppms-toast-close" aria-label="Đóng" @click="ppmsToastRemove(t.id)">
                        ×
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { toastState, ppmsToastRemove } from '../ppmsUi';
</script>
