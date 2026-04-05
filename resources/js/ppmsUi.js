import { reactive } from 'vue';

export const confirmDialog = reactive({
    open: false,
    title: 'Xác nhận',
    message: '',
    confirmLabel: 'Đồng ý',
    cancelLabel: 'Hủy',
    destructive: false,
    _resolve: null,
});

export function ppmsConfirm(message, options = {}) {
    return new Promise((resolve) => {
        confirmDialog.open = true;
        confirmDialog.title = options.title ?? 'Xác nhận';
        confirmDialog.message = message;
        confirmDialog.confirmLabel = options.confirmLabel ?? 'Đồng ý';
        confirmDialog.cancelLabel = options.cancelLabel ?? 'Hủy';
        confirmDialog.destructive = !!options.destructive;
        confirmDialog._resolve = resolve;
    });
}

export function ppmsConfirmClose(confirmed) {
    const r = confirmDialog._resolve;
    confirmDialog.open = false;
    confirmDialog._resolve = null;
    if (r) {
        r(!!confirmed);
    }
}

export const toastState = reactive({
    items: [],
});

let toastSeq = 0;

export function ppmsToastRemove(id) {
    const i = toastState.items.findIndex((t) => t.id === id);
    if (i >= 0) {
        toastState.items.splice(i, 1);
    }
}

export function ppmsToast(message, type = 'info', duration = 4500) {
    const id = ++toastSeq;
    toastState.items.push({ id, message, type });
    if (duration > 0) {
        setTimeout(() => ppmsToastRemove(id), duration);
    }
    return id;
}

export function ppmsToastSuccess(message) {
    return ppmsToast(message, 'success');
}

export function ppmsToastError(message) {
    return ppmsToast(message, 'error', 6500);
}

export function ppmsToastWarning(message) {
    return ppmsToast(message, 'warning', 5500);
}
