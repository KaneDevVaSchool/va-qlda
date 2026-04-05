<template>
    <Teleport to="body">
        <div v-if="confirmDialog.open" class="ppms-modal-backdrop" role="presentation" @click.self="onCancel">
            <div
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
                :aria-describedby="descId"
                class="ppms-modal"
                tabindex="-1"
                @keydown.escape.prevent="onCancel"
            >
                <h2 :id="titleId" class="ppms-modal-title">{{ confirmDialog.title }}</h2>
                <p :id="descId" class="ppms-modal-msg">{{ confirmDialog.message }}</p>
                <div class="ppms-modal-actions">
                    <button ref="cancelBtnRef" type="button" class="ppms-btn-ghost ppms-modal-btn" @click="onCancel">
                        {{ confirmDialog.cancelLabel }}
                    </button>
                    <button
                        ref="confirmBtnRef"
                        type="button"
                        class="ppms-btn-primary ppms-modal-btn"
                        :class="{ 'ppms-btn-danger': confirmDialog.destructive }"
                        @click="onConfirm"
                    >
                        {{ confirmDialog.confirmLabel }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { nextTick, ref, watch } from 'vue';
import { confirmDialog, ppmsConfirmClose } from '../ppmsUi';

const titleId = 'ppms-confirm-title';
const descId = 'ppms-confirm-desc';
const cancelBtnRef = ref(null);
const confirmBtnRef = ref(null);

function onCancel() {
    ppmsConfirmClose(false);
}

function onConfirm() {
    ppmsConfirmClose(true);
}

watch(
    () => confirmDialog.open,
    async (open) => {
        if (typeof document === 'undefined') {
            return;
        }
        if (open) {
            document.body.classList.add('ppms-modal-open');
            await nextTick();
            const focusEl = confirmDialog.destructive ? cancelBtnRef.value : confirmBtnRef.value;
            focusEl?.focus?.();
        } else {
            document.body.classList.remove('ppms-modal-open');
        }
    },
);
</script>
