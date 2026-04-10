import { computed } from 'vue';
import { appBootstrapState } from '../appBootstrap';

/**
 * Effective permission keys from /api/bootstrap (role matrix + overrides).
 *
 * @param {string} key e.g. "projects.update"
 * @returns {boolean}
 */
export function canPermission(key) {
    const state = appBootstrapState();
    return !!(state.rbac?.effective && state.rbac.effective[key]);
}

/**
 * @returns {{ can: (key: string) => boolean, effective: import('vue').ComputedRef<Record<string, boolean>> }}
 */
export function usePermissions() {
    const state = appBootstrapState();

    const effective = computed(() => state.rbac?.effective ?? {});

    function can(key) {
        return !!(effective.value && effective.value[key]);
    }

    return { can, effective };
}
