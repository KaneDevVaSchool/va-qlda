<template>
    <div class="ppms-rbac-admin">
        <section class="ppms-rbac-admin-block" :aria-labelledby="'rbac-matrix-' + uid">
            <h2 :id="'rbac-matrix-' + uid" class="ppms-rbac-admin-h2">{{ t('profile.rbacAdminRoleMatrixTitle') }}</h2>
            <p class="ppms-rbac-admin-lead">{{ t('profile.rbacAdminRoleMatrixLead') }}</p>
            <p v-if="rolesWithCustom.length" class="ppms-rbac-admin-note">
                {{ t('profile.rbacAdminCustomRolesHint', { roles: rolesWithCustomLabels }) }}
            </p>

            <div v-if="loading" class="ppms-profile-skel" role="status" :aria-label="t('common.loading')">
                <div class="ppms-profile-skel-line ppms-profile-access-skel-a" />
                <div class="ppms-profile-skel-line ppms-profile-access-skel-b" />
            </div>
            <template v-else-if="err">
                <p class="ppms-rbac-admin-err">{{ err }}</p>
            </template>
            <template v-else>
                <div class="ppms-rbac-admin-row">
                    <label class="ppms-rbac-admin-label" for="rbac-edit-role">{{ t('profile.rbacAdminPickRole') }}</label>
                    <select id="rbac-edit-role" v-model="selectedRole" class="ppms-profile-access-input" @change="onRoleChange">
                        <option v-for="r in roleList" :key="r" :value="r">{{ roleLabel(r) }}</option>
                    </select>
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
                                        :aria-label="checkboxAria(mod, a)"
                                        @change="toggle(mod.key, a, $event.target.checked)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="ppms-rbac-admin-actions">
                    <button
                        type="button"
                        class="ppms-pf-btn ppms-pf-btn--primary"
                        :disabled="saving"
                        :aria-busy="saving"
                        @click="saveMatrix"
                    >
                        {{ t('profile.rbacAdminSaveRoleMatrix') }}
                    </button>
                    <button
                        type="button"
                        class="ppms-pf-btn ppms-profile-access-btn-secondary"
                        :disabled="saving || !rolesWithCustom.includes(selectedRole)"
                        @click="resetMatrix"
                    >
                        {{ t('profile.rbacAdminResetRoleMatrix') }}
                    </button>
                </div>
            </template>
        </section>

        <section class="ppms-rbac-admin-block" :aria-labelledby="'rbac-user-' + uid">
            <h2 :id="'rbac-user-' + uid" class="ppms-rbac-admin-h2">{{ t('profile.rbacAdminAssignUserTitle') }}</h2>
            <p class="ppms-rbac-admin-lead">{{ t('profile.rbacAdminAssignUserLead') }}</p>

            <div class="ppms-profile-access-row">
                <label class="ppms-rbac-admin-label" for="rbac-user-search">{{ t('profile.rbacAdminAssignUserSearch') }}</label>
                <div ref="lookupEl" class="ppms-profile-access-lookup">
                    <input
                        id="rbac-user-search"
                        v-model="userSearchQ"
                        type="search"
                        class="ppms-profile-access-input"
                        autocomplete="off"
                        :placeholder="t('profile.delegationSearchPlaceholder')"
                        :aria-expanded="userPickCandidates.length > 0"
                        aria-controls="rbac-user-pick-list"
                        @input="onUserSearchInput"
                        @keydown.escape.prevent="userPickCandidates = []"
                    />
                    <ul
                        v-if="userPickCandidates.length"
                        id="rbac-user-pick-list"
                        class="ppms-profile-access-pick"
                        role="listbox"
                    >
                        <li v-for="u in userPickCandidates" :key="u.id" role="option">
                            <button type="button" class="ppms-profile-access-pick-btn" @click="selectUserForRole(u)">
                                <span class="ppms-profile-access-user">{{ u.name }}</span>
                                <span class="ppms-profile-access-email">{{ u.email }}</span>
                                <span class="ppms-rbac-admin-pick-role">{{ roleLabel(u.role) }}</span>
                            </button>
                        </li>
                    </ul>
                </div>
                <p v-if="assignUser" class="ppms-profile-access-picked">
                    <span>{{ assignUser.name }} · {{ assignUser.email }}</span>
                    <button type="button" class="ppms-profile-access-clear" @click="clearAssignUser">{{ t('common.cancel') }}</button>
                </p>
            </div>

            <div v-if="assignUser" class="ppms-profile-access-row">
                <label class="ppms-rbac-admin-label" for="rbac-assign-role">{{ t('profile.accessRoleChange') }}</label>
                <select id="rbac-assign-role" v-model="assignRoleDraft" class="ppms-profile-access-input" :disabled="assignSaving">
                    <option v-for="r in roleList" :key="'a-' + r" :value="r">{{ roleLabel(r) }}</option>
                </select>
            </div>

            <div v-if="assignUser" class="ppms-rbac-admin-actions">
                <button
                    type="button"
                    class="ppms-pf-btn ppms-pf-btn--primary"
                    :disabled="assignSaving"
                    :aria-busy="assignSaving"
                    @click="saveUserRole"
                >
                    {{ t('profile.rbacAdminSaveUserRole') }}
                </button>
            </div>
        </section>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '../../bootstrap';
import { ppmsConfirm, ppmsToastError, ppmsToastSuccess } from '../../ppmsUi';

const { t } = useI18n();

const uid = `r${Math.random().toString(36).slice(2, 9)}`;

const loading = ref(true);
const err = ref('');
const saving = ref(false);
const search = ref('');
const roleList = ref([]);
const actions = ref(['view', 'create', 'update', 'delete']);
const modules = ref({});
const matrix = ref({});
const selectedRole = ref('pm');
const desired = ref({});

const rolesWithCustom = ref([]);

const userSearchQ = ref('');
const userPickCandidates = ref([]);
const assignUser = ref(null);
const assignRoleDraft = ref('developer');
const assignSaving = ref(false);
const lookupEl = ref(null);
let userSearchTimer;

const rolesWithCustomLabels = computed(() => rolesWithCustom.value.map((r) => roleLabel(r)).join(', '));

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

function roleLabel(role) {
    if (!role) {
        return t('layout.role.unassigned');
    }
    const key = `layout.role.${role}`;
    const tr = t(key);
    return tr === key ? role : tr;
}

function actionLabel(action) {
    const key = `profile.permissionActions.${action}`;
    const tr = t(key);
    return tr === key ? action : tr;
}

function keyFor(mod, act) {
    return `${mod}.${act}`;
}

function checkboxAria(mod, action) {
    return `${mod.label} — ${actionLabel(action)}`;
}

function syncDesiredFromMatrix() {
    const r = selectedRole.value;
    const row = matrix.value[r] || {};
    desired.value = { ...row };
}

function onRoleChange() {
    syncDesiredFromMatrix();
}

function toggle(mod, act, checked) {
    const k = keyFor(mod, act);
    desired.value = { ...desired.value, [k]: checked };
}

function buildEntries() {
    const out = {};
    for (const mod of Object.keys(modules.value || {})) {
        for (const a of actions.value) {
            const pk = keyFor(mod, a);
            out[pk] = !!desired.value[pk];
        }
    }
    return out;
}

async function loadMatrix() {
    loading.value = true;
    err.value = '';
    try {
        const { data } = await axios.get('/api/admin/rbac/role-matrix');
        modules.value = data.modules || {};
        actions.value = Array.isArray(data.actions) && data.actions.length ? data.actions : actions.value;
        matrix.value = data.matrix || {};
        roleList.value = Array.isArray(data.roles) ? data.roles : [];
        rolesWithCustom.value = Array.isArray(data.roles_with_custom_matrix) ? data.roles_with_custom_matrix : [];
        if (!roleList.value.includes(selectedRole.value) && roleList.value.length) {
            selectedRole.value = roleList.value[0];
        }
        syncDesiredFromMatrix();
    } catch (e) {
        err.value = formatApiUserMessage(e, t('common.loading'));
    } finally {
        loading.value = false;
    }
}

async function saveMatrix() {
    saving.value = true;
    try {
        await axios.patch(`/api/admin/rbac/roles/${encodeURIComponent(selectedRole.value)}`, {
            entries: buildEntries(),
        });
        ppmsToastSuccess(t('profile.rbacAdminMatrixSaved'));
        await loadMatrix();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('profile.saveError')));
    } finally {
        saving.value = false;
    }
}

async function resetMatrix() {
    const ok = await ppmsConfirm(t('profile.rbacAdminResetConfirm'), {
        title: t('profile.rbacAdminResetRoleMatrix'),
        destructive: true,
        confirmLabel: t('profile.rbacAdminResetRoleMatrix'),
        cancelLabel: t('common.cancel'),
    });
    if (!ok) {
        return;
    }
    saving.value = true;
    try {
        await axios.delete(`/api/admin/rbac/roles/${encodeURIComponent(selectedRole.value)}`);
        ppmsToastSuccess(t('profile.rbacAdminMatrixReset'));
        await loadMatrix();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('profile.saveError')));
    } finally {
        saving.value = false;
    }
}

function onUserSearchInput() {
    clearTimeout(userSearchTimer);
    userSearchTimer = setTimeout(async () => {
        const q = userSearchQ.value.trim();
        if (q.length < 2) {
            userPickCandidates.value = [];
            return;
        }
        try {
            const { data } = await axios.get('/api/users/lookup', { params: { q } });
            userPickCandidates.value = Array.isArray(data) ? data : [];
        } catch {
            userPickCandidates.value = [];
        }
    }, 280);
}

function selectUserForRole(u) {
    assignUser.value = u;
    assignRoleDraft.value = u.role || roleList.value[0] || 'developer';
    userSearchQ.value = '';
    userPickCandidates.value = [];
}

function clearAssignUser() {
    assignUser.value = null;
}

async function saveUserRole() {
    if (!assignUser.value?.id) {
        return;
    }
    assignSaving.value = true;
    try {
        await axios.patch(`/api/users/${assignUser.value.id}/role`, { role: assignRoleDraft.value });
        ppmsToastSuccess(t('profile.accessRoleSaved'));
        assignUser.value = { ...assignUser.value, role: assignRoleDraft.value };
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('profile.saveError')));
    } finally {
        assignSaving.value = false;
    }
}

function onDocClick(e) {
    if (lookupEl.value?.contains(e.target)) {
        return;
    }
    userPickCandidates.value = [];
}

onMounted(async () => {
    await loadMatrix();
    document.addEventListener('click', onDocClick, true);
});

onUnmounted(() => {
    clearTimeout(userSearchTimer);
    document.removeEventListener('click', onDocClick, true);
});
</script>

<style scoped>
.ppms-rbac-admin {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}
.ppms-rbac-admin-block {
    min-width: 0;
}
.ppms-rbac-admin-h2 {
    font-size: 1.05rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
    color: var(--ppms-pf-fg, inherit);
}
.ppms-rbac-admin-lead {
    font-size: 0.9rem;
    color: var(--ppms-pf-muted);
    margin: 0 0 0.75rem;
    line-height: 1.45;
}
.ppms-rbac-admin-note {
    font-size: 0.82rem;
    color: var(--ppms-pf-muted);
    margin: 0 0 0.75rem;
    line-height: 1.45;
}
.ppms-rbac-admin-err {
    color: #b91c1c;
    margin: 0;
}
.ppms-rbac-admin-row {
    margin-bottom: 0.75rem;
    max-width: 22rem;
}
.ppms-rbac-admin-label {
    display: block;
    font-size: 0.85rem;
    margin-bottom: 0.35rem;
    color: var(--ppms-pf-muted);
}
.ppms-rbac-admin-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 0.75rem;
    margin-top: 1rem;
}
.ppms-rbac-admin-pick-role {
    display: block;
    font-size: 0.75rem;
    color: var(--ppms-pf-muted);
    margin-top: 0.15rem;
}
</style>
