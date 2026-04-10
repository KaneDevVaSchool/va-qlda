import axios from 'axios';
import { reactive, readonly } from 'vue';

const state = reactive({
    loaded: false,
    loadError: null,
    moduleMaintenance: {},
    moduleCatalog: {},
    maintenanceBypassRoles: [],
    rbac: {
        role: null,
        effective: {},
        can_manage: false,
    },
});

let loadPromise = null;

/**
 * Load /api/bootstrap once (idempotent). Used by router and admin UI refresh.
 */
export async function loadAppBootstrap() {
    if (state.loaded) {
        return;
    }
    if (loadPromise) {
        await loadPromise;
        return;
    }
    loadPromise = (async () => {
        try {
            const { data } = await axios.get('/api/bootstrap');
            state.moduleMaintenance = data.module_maintenance || {};
            state.moduleCatalog = data.module_catalog || {};
            state.maintenanceBypassRoles = data.maintenance_bypass_roles || ['admin'];
            state.rbac = {
                role: data.rbac?.role ?? null,
                effective: data.rbac?.effective ?? {},
                can_manage: !!data.rbac?.can_manage,
            };
            state.loadError = null;
            state.loaded = true;
        } catch (e) {
            state.loadError = e;
            throw e;
        } finally {
            loadPromise = null;
        }
    })();
    await loadPromise;
}

export function resetAppBootstrap() {
    state.loaded = false;
    state.loadError = null;
    state.moduleMaintenance = {};
    state.moduleCatalog = {};
    state.maintenanceBypassRoles = [];
    state.rbac = { role: null, effective: {}, can_manage: false };
}

export function appBootstrapState() {
    return readonly(state);
}

export function isModuleUnderMaintenance(moduleKey) {
    if (!moduleKey) {
        return false;
    }
    return !!(state.moduleMaintenance[moduleKey]?.maintenance);
}

export function canBypassModuleMaintenance() {
    const role = state.rbac?.role;
    if (!role) {
        return false;
    }
    return state.maintenanceBypassRoles.includes(role);
}

/**
 * Non-bypass users should not enter SPA routes for this module.
 */
export function shouldBlockModuleRoute(moduleKey) {
    if (!moduleKey) {
        return false;
    }
    if (!isModuleUnderMaintenance(moduleKey)) {
        return false;
    }
    return !canBypassModuleMaintenance();
}

export async function refreshAppBootstrap() {
    state.loaded = false;
    await loadAppBootstrap();
}
