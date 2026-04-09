<template>
    <section class="ppms-rbac-admin" :aria-labelledby="'rbac-admin-main-' + uid">
        <header class="ppms-rbac-admin-header">
            <p class="ppms-rbac-admin-kicker">{{ t('profile.rbacAdminSectionKicker') }}</p>
            <h2 :id="'rbac-admin-main-' + uid" class="ppms-rbac-admin-title">{{ t('profile.rbacAdminSectionTitle') }}</h2>
            <p class="ppms-rbac-admin-intro">{{ t('profile.rbacAdminSectionIntro') }}</p>
        </header>

        <div class="ppms-rbac-admin-layout">
            <!-- Cột trái / trên: gán vai trò nhanh -->
            <div class="ppms-rbac-admin-panel ppms-rbac-admin-panel--assign">
                <div class="ppms-rbac-admin-panel-head">
                    <span class="ppms-rbac-admin-panel-badge" aria-hidden="true">1</span>
                    <div>
                        <h3 class="ppms-rbac-admin-h3">{{ t('profile.rbacAdminAssignUserTitle') }}</h3>
                        <p class="ppms-rbac-admin-panel-lead">{{ t('profile.rbacAdminAssignUserLead') }}</p>
                    </div>
                </div>

                <div class="ppms-rbac-admin-field">
                    <label class="ppms-rbac-admin-label" for="rbac-user-search">{{ t('profile.rbacAdminAssignUserSearch') }}</label>
                    <div ref="lookupEl" class="ppms-profile-access-lookup">
                        <input
                            id="rbac-user-search"
                            v-model="userSearchQ"
                            type="search"
                            class="ppms-profile-access-input ppms-rbac-admin-input"
                            autocomplete="off"
                            :placeholder="t('profile.rbacAdminAssignPlaceholder')"
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
                </div>

                <div v-if="assignUser" class="ppms-rbac-admin-selected">
                    <div class="ppms-rbac-admin-selected-main">
                        <span class="ppms-rbac-admin-selected-name">{{ assignUser.name }}</span>
                        <span class="ppms-rbac-admin-selected-email">{{ assignUser.email }}</span>
                    </div>
                    <button type="button" class="ppms-rbac-admin-chip-clear" @click="clearAssignUser">{{ t('common.cancel') }}</button>
                </div>

                <div v-if="assignUser" class="ppms-rbac-admin-field">
                    <label class="ppms-rbac-admin-label" for="rbac-assign-role">{{ t('profile.rbacAdminNewRoleLabel') }}</label>
                    <select
                        id="rbac-assign-role"
                        v-model="assignRoleDraft"
                        class="ppms-profile-access-input ppms-rbac-admin-input"
                        :disabled="assignSaving"
                    >
                        <option v-for="r in roleList" :key="'a-' + r" :value="r">{{ roleLabel(r) }}</option>
                    </select>
                </div>

                <div v-if="assignUser" class="ppms-rbac-admin-panel-actions">
                    <button
                        type="button"
                        class="ppms-pf-btn ppms-pf-btn--primary ppms-rbac-admin-btn-full"
                        :disabled="assignSaving"
                        :aria-busy="assignSaving"
                        @click="saveUserRole"
                    >
                        {{ t('profile.rbacAdminSaveUserRole') }}
                    </button>
                </div>

                <p v-if="!assignUser" class="ppms-rbac-admin-hint">{{ t('profile.rbacAdminAssignHint') }}</p>
            </div>

            <!-- Cột phải / dưới: ma trận theo vai trò -->
            <div class="ppms-rbac-admin-panel ppms-rbac-admin-panel--matrix">
                <div class="ppms-rbac-admin-panel-head">
                    <span class="ppms-rbac-admin-panel-badge ppms-rbac-admin-panel-badge--muted" aria-hidden="true">2</span>
                    <div>
                        <h3 class="ppms-rbac-admin-h3">{{ t('profile.rbacAdminRoleMatrixTitle') }}</h3>
                        <p class="ppms-rbac-admin-panel-lead">{{ t('profile.rbacAdminRoleMatrixLead') }}</p>
                    </div>
                </div>

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
                    <div class="ppms-rbac-admin-toolbar">
                        <div class="ppms-rbac-admin-field ppms-rbac-admin-field--grow">
                            <label class="ppms-rbac-admin-label" for="rbac-edit-role">{{ t('profile.rbacAdminPickRole') }}</label>
                            <select
                                id="rbac-edit-role"
                                v-model="selectedRole"
                                class="ppms-profile-access-input ppms-rbac-admin-input"
                                @change="onRoleChange"
                            >
                                <option v-for="r in roleList" :key="r" :value="r">{{ roleLabel(r) }}</option>
                            </select>
                        </div>
                        <div class="ppms-rbac-admin-field ppms-rbac-admin-field--grow">
                            <label class="ppms-rbac-admin-label" for="rbac-matrix-search">{{ t('profile.searchPermission') }}</label>
                            <input
                                id="rbac-matrix-search"
                                v-model="search"
                                type="search"
                                class="ppms-profile-perm-input ppms-rbac-admin-input"
                                :placeholder="t('profile.searchPermission')"
                                :aria-label="t('profile.searchPermission')"
                            />
                        </div>
                    </div>

                    <div class="ppms-profile-table-wrap ppms-profile-perm-table-wrap ppms-rbac-admin-table-wrap">
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

                    <div class="ppms-rbac-admin-matrix-actions">
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
            </div>
        </div>
    </section>
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
    margin: 0 0 1.5rem;
    padding: 1rem 1.1rem 1.15rem;
    border-radius: 12px;
    border: 1px solid rgba(59, 130, 246, 0.22);
    background: linear-gradient(165deg, rgba(239, 246, 255, 0.65) 0%, rgba(248, 250, 252, 0.9) 100%);
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
}
.ppms-rbac-admin-header {
    margin-bottom: 1.1rem;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.35);
}
.ppms-rbac-admin-kicker {
    margin: 0 0 0.35rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #2563eb;
}
.ppms-rbac-admin-title {
    margin: 0 0 0.4rem;
    font-size: 1.15rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--ppms-pf-fg, inherit);
}
.ppms-rbac-admin-intro {
    margin: 0;
    font-size: 0.88rem;
    line-height: 1.5;
    color: var(--ppms-pf-muted);
    max-width: 52rem;
}

.ppms-rbac-admin-layout {
    display: grid;
    gap: 1.1rem;
    align-items: start;
}
@media (min-width: 960px) {
    .ppms-rbac-admin-layout {
        grid-template-columns: minmax(17rem, 22rem) minmax(0, 1fr);
        gap: 1.25rem 1.35rem;
    }
}

.ppms-rbac-admin-panel {
    min-width: 0;
    padding: 0.9rem 1rem 1rem;
    border-radius: 10px;
    border: 1px solid rgba(148, 163, 184, 0.4);
    background: var(--ppms-pf-card-bg, rgba(255, 255, 255, 0.85));
}
.ppms-rbac-admin-panel--assign {
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.ppms-rbac-admin-panel--matrix {
    border-color: rgba(59, 130, 246, 0.2);
}

.ppms-rbac-admin-panel-head {
    display: flex;
    gap: 0.65rem;
    align-items: flex-start;
    margin-bottom: 0.85rem;
}
.ppms-rbac-admin-panel-badge {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 700;
    color: #1d4ed8;
    background: rgba(59, 130, 246, 0.14);
    border: 1px solid rgba(59, 130, 246, 0.25);
}
.ppms-rbac-admin-panel-badge--muted {
    color: #64748b;
    background: rgba(148, 163, 184, 0.18);
    border-color: rgba(148, 163, 184, 0.35);
}
.ppms-rbac-admin-h3 {
    margin: 0 0 0.25rem;
    font-size: 0.98rem;
    font-weight: 600;
    color: var(--ppms-pf-fg, inherit);
}
.ppms-rbac-admin-panel-lead {
    margin: 0;
    font-size: 0.82rem;
    line-height: 1.45;
    color: var(--ppms-pf-muted);
}

.ppms-rbac-admin-field {
    margin-bottom: 0.75rem;
}
.ppms-rbac-admin-field--grow {
    flex: 1;
    min-width: 0;
    margin-bottom: 0;
}
.ppms-rbac-admin-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.3rem;
    color: var(--ppms-pf-fg, inherit);
}
.ppms-rbac-admin-input {
    width: 100%;
    max-width: 100%;
}
.ppms-rbac-admin-hint {
    margin: 0.5rem 0 0;
    font-size: 0.8rem;
    line-height: 1.4;
    color: var(--ppms-pf-muted);
}

.ppms-rbac-admin-selected {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
    margin: 0 0 0.75rem;
    padding: 0.55rem 0.65rem;
    border-radius: 8px;
    background: rgba(59, 130, 246, 0.08);
    border: 1px solid rgba(59, 130, 246, 0.2);
}
.ppms-rbac-admin-selected-main {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}
.ppms-rbac-admin-selected-name {
    font-weight: 600;
    font-size: 0.9rem;
}
.ppms-rbac-admin-selected-email {
    font-size: 0.8rem;
    color: var(--ppms-pf-muted);
    word-break: break-word;
}
.ppms-rbac-admin-chip-clear {
    flex-shrink: 0;
    font-size: 0.8rem;
    border: none;
    background: transparent;
    color: #2563eb;
    cursor: pointer;
    text-decoration: underline;
}
.ppms-rbac-admin-panel-actions {
    margin-top: 0.25rem;
}
.ppms-rbac-admin-btn-full {
    width: 100%;
}

.ppms-rbac-admin-note {
    font-size: 0.8rem;
    color: var(--ppms-pf-muted);
    margin: 0 0 0.75rem;
    line-height: 1.45;
    padding: 0.45rem 0.55rem;
    border-radius: 6px;
    background: rgba(254, 243, 199, 0.45);
    border: 1px solid rgba(217, 119, 6, 0.22);
}
.ppms-rbac-admin-err {
    color: #b91c1c;
    margin: 0;
    font-size: 0.9rem;
}

.ppms-rbac-admin-toolbar {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    margin-bottom: 0.75rem;
}
@media (min-width: 640px) {
    .ppms-rbac-admin-toolbar {
        flex-direction: row;
        align-items: flex-end;
    }
}

.ppms-rbac-admin-table-wrap {
    margin-top: 0.25rem;
    border-radius: 8px;
    border: 1px solid rgba(148, 163, 184, 0.35);
    background: var(--ppms-pf-input-bg, #fff);
}

.ppms-rbac-admin-matrix-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 0.75rem;
    margin-top: 1rem;
    padding-top: 0.85rem;
    border-top: 1px solid rgba(148, 163, 184, 0.3);
}

.ppms-rbac-admin-pick-role {
    display: block;
    font-size: 0.72rem;
    color: var(--ppms-pf-muted);
    margin-top: 0.15rem;
}

:deep(.ppms-profile-perm-input:focus-visible),
.ppms-rbac-admin-input:focus-visible {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
}
</style>
