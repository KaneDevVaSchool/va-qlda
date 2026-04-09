<template>
    <div class="ppms-profile-perm">
        <div v-if="loading" class="ppms-profile-skel" role="status" :aria-label="t('common.loading')">
            <div class="ppms-profile-skel-line ppms-profile-perm-skel-a" />
            <div class="ppms-profile-skel-line ppms-profile-perm-skel-b" />
        </div>
        <template v-else-if="err">
            <p class="ppms-profile-perm-err">{{ err }}</p>
        </template>
        <template v-else>
            <p v-if="!compact" class="ppms-profile-perm-lead">{{ t('profile.permissionsMatrix') }}</p>
            <div v-if="!canManage" class="ppms-profile-perm-readonly" role="note">
                <p class="ppms-profile-perm-hint">{{ t('profile.canManageHint') }}</p>
                <p v-if="permissionAdminRoles.length" class="ppms-profile-perm-hint ppms-profile-perm-hint--detail">
                    {{ t('profile.permissionAdminExplain', { role: roleLabelSlug(role), roles: permissionAdminRolesListed }) }}
                </p>
            </div>

            <div class="ppms-profile-perm-search">
                <input
                    v-model="search"
                    type="search"
                    class="ppms-profile-perm-input"
                    :placeholder="t('profile.searchPermission')"
                    :aria-label="t('profile.searchPermission')"
                />
            </div>

            <div class="ppms-profile-table-wrap ppms-profile-perm-table-wrap">
                <table class="ppms-profile-table ppms-profile-matrix">
                    <thead>
                        <tr>
                            <th class="ppms-profile-matrix-corner" scope="col">{{ t('profile.matrixModule') }}</th>
                            <th v-for="a in actions" :key="a" scope="col">{{ actionLabel(a) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="mod in filteredModules" :key="mod.key">
                            <th scope="row">{{ mod.label }}</th>
                            <td v-for="a in actions" :key="mod.key + a" class="ppms-profile-perm-cell">
                                <input
                                    type="checkbox"
                                    class="ppms-profile-perm-cb"
                                    :checked="desired[keyFor(mod.key, a)]"
                                    :disabled="!canManage"
                                    :aria-label="checkboxAria(mod, a)"
                                    @change="toggle(mod.key, a, $event.target.checked)"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="canManage" class="ppms-profile-perm-actions">
                <button
                    type="button"
                    class="ppms-pf-btn ppms-pf-btn--primary"
                    :disabled="saving"
                    :aria-busy="saving"
                    @click="save"
                >
                    {{ t('profile.save') }}
                </button>
            </div>
        </template>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '../../bootstrap';
import { ppmsToastError, ppmsToastSuccess } from '../../ppmsUi';

defineProps({
    compact: {
        type: Boolean,
        default: false,
    },
});

const { t } = useI18n();

const loading = ref(true);
const err = ref('');
const saving = ref(false);
const search = ref('');
const role = ref('');
const modules = ref({});
const actions = ref(['view', 'create', 'update', 'delete']);
const matrix = ref({});
const effective = ref({});
const desired = ref({});
const canManage = ref(false);
const permissionAdminRoles = ref([]);

const permissionAdminRolesListed = computed(() =>
    permissionAdminRoles.value.map((slug) => roleLabelSlug(slug)).join(', '),
);

function roleLabelSlug(slug) {
    if (!slug) {
        return '';
    }
    const key = `layout.role.${slug}`;
    const tr = t(key);
    return tr === key ? slug : tr;
}

function actionLabel(action) {
    const key = `profile.permissionActions.${action}`;
    const tr = t(key);
    return tr === key ? action : tr;
}

function checkboxAria(mod, action) {
    return `${mod.label} — ${actionLabel(action)}`;
}

const moduleRows = computed(() =>
    Object.entries(modules.value || {}).map(([key, v]) => ({
        key,
        label: (v && v.label) || key,
    })),
);

const filteredModules = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) {
        return moduleRows.value;
    }
    return moduleRows.value.filter(
        (m) => m.key.toLowerCase().includes(q) || m.label.toLowerCase().includes(q),
    );
});

function keyFor(mod, act) {
    return `${mod}.${act}`;
}

function roleAllows(roleName, permKey) {
    const row = matrix.value[roleName] || {};
    if (row['*']) {
        return true;
    }
    return !!row[permKey];
}

function toggle(mod, act, checked) {
    const k = keyFor(mod, act);
    desired.value = { ...desired.value, [k]: checked };
}

function buildOverrides() {
    const out = [];
    const r = role.value;
    for (const mod of Object.keys(modules.value || {})) {
        for (const a of actions.value) {
            const pk = keyFor(mod, a);
            const base = roleAllows(r, pk);
            if (desired.value[pk] !== base) {
                out.push({ permission_key: pk, granted: !!desired.value[pk] });
            }
        }
    }
    return out;
}

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/me/rbac');
        role.value = data.role || 'developer';
        modules.value = data.modules || {};
        actions.value = data.actions || actions.value;
        matrix.value = data.matrix || {};
        effective.value = data.effective || {};
        desired.value = { ...data.effective };
        canManage.value = !!data.can_manage;
        const raw = Array.isArray(data.permission_admin_roles) ? data.permission_admin_roles : ['admin'];
        permissionAdminRoles.value = raw.length ? raw : ['admin'];
    } catch (e) {
        err.value = formatApiUserMessage(e, t('common.loading'));
    } finally {
        loading.value = false;
    }
});

async function save() {
    saving.value = true;
    try {
        await axios.patch('/api/me/rbac', { overrides: buildOverrides() });
        ppmsToastSuccess(t('profile.permissionsSaved'));
        const { data } = await axios.get('/api/me/rbac');
        effective.value = data.effective || {};
        desired.value = { ...data.effective };
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('profile.saveError')));
    } finally {
        saving.value = false;
    }
}
</script>

<style scoped>
.ppms-profile-perm-err {
    color: #b91c1c;
    margin: 0;
}
.ppms-profile-perm-lead {
    font-size: 0.9rem;
    color: var(--ppms-pf-muted);
    margin: 0 0 0.5rem;
}
.ppms-profile-perm-readonly {
    padding: 0.65rem 0.75rem;
    margin: 0 0 0.75rem;
    border-radius: 8px;
    border: 1px solid rgba(148, 163, 184, 0.45);
    background: rgba(241, 245, 249, 0.55);
}
.ppms-profile-perm-hint {
    font-size: 0.85rem;
    color: var(--ppms-pf-muted);
    margin: 0 0 0.75rem;
}
.ppms-profile-perm-hint:last-child {
    margin-bottom: 0;
}
.ppms-profile-perm-hint--detail {
    margin-top: 0.35rem;
    line-height: 1.45;
}
.ppms-profile-perm-search {
    margin: 0.75rem 0;
}
.ppms-profile-perm-input {
    width: 100%;
    max-width: 100%;
    padding: 0.45rem 0.5rem;
    border-radius: 6px;
    border: 1px solid rgba(148, 163, 184, 0.5);
    background: var(--ppms-pf-input-bg, #fff);
    color: inherit;
}
.ppms-profile-perm-input:focus-visible {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
}
.ppms-profile-perm-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.ppms-profile-perm-cell {
    text-align: center;
    vertical-align: middle;
}
.ppms-profile-perm-cb {
    width: 1.1rem;
    height: 1.1rem;
    cursor: pointer;
    accent-color: #2563eb;
}
.ppms-profile-perm-cb:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}
.ppms-profile-perm-actions {
    margin-top: 1rem;
}
.ppms-profile-perm-skel-a {
    width: 55%;
}
.ppms-profile-perm-skel-b {
    width: 70%;
}
</style>
