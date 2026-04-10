<template>
    <div class="ppms-profile-access" :class="{ 'ppms-profile-access--embedded': embedded }">
        <header v-if="!embedded" class="ppms-profile-access-head">
            <h2 class="ppms-profile-access-title">{{ t('profile.accessTabTitle') }}</h2>
            <p class="ppms-profile-access-lead">{{ t('profile.accessTabLead') }}</p>
        </header>

        <div
            v-if="!rbacLoading && !canManageRbac"
            class="ppms-profile-access-banner ppms-profile-access-banner--readonly"
            role="region"
            :aria-labelledby="'acc-ro-' + uid"
        >
            <h3 :id="'acc-ro-' + uid" class="ppms-profile-access-banner-title">{{ t('profile.accessReadOnlyBannerTitle') }}</h3>
            <p class="ppms-profile-access-banner-summary">{{ t('profile.accessReadOnlyBannerSummary') }}</p>
            <details class="ppms-profile-access-details">
                <summary class="ppms-profile-access-details-summary">{{ t('profile.accessReadOnlyDetailsToggle') }}</summary>
                <p class="ppms-profile-access-banner-body">{{ t('profile.permissionAdminExplain', { role: roleLabel(myRbacRole), roles: permissionAdminRolesListed }) }}</p>
            </details>
        </div>

        <div v-if="!rbacLoading" class="ppms-profile-access-subtabs" role="tablist" :aria-label="t('profile.accessTabTitle')">
            <button
                v-if="!hidePermissionsSubtab"
                type="button"
                role="tab"
                class="ppms-profile-access-subtab"
                :class="{ 'ppms-profile-access-subtab--active': activeSubTab === 'permissions' }"
                :aria-selected="activeSubTab === 'permissions'"
                @click="activeSubTab = 'permissions'"
            >
                {{ t('profile.accessSubTabPermissions') }}
            </button>
            <button
                type="button"
                role="tab"
                class="ppms-profile-access-subtab"
                :class="{ 'ppms-profile-access-subtab--active': activeSubTab === 'delegation' }"
                :aria-selected="activeSubTab === 'delegation'"
                @click="activeSubTab = 'delegation'"
            >
                {{ t('profile.accessSubTabDelegation') }}
            </button>
            <button
                v-if="canManageRbac"
                type="button"
                role="tab"
                class="ppms-profile-access-subtab"
                :class="{ 'ppms-profile-access-subtab--active': activeSubTab === 'admin' }"
                :aria-selected="activeSubTab === 'admin'"
                @click="activeSubTab = 'admin'"
            >
                {{ t('profile.accessSubTabAdmin') }}
            </button>
        </div>

        <div v-show="!hidePermissionsSubtab && activeSubTab === 'permissions'" class="ppms-profile-access-panel" role="tabpanel">
            <section class="ppms-profile-access-section" :aria-labelledby="'acc-role-' + uid">
                <h3 :id="'acc-role-' + uid" class="ppms-profile-access-h">{{ t('profile.accessRoleTitle') }}</h3>
                <p class="ppms-profile-access-desc">{{ t('profile.accessRoleLead') }}</p>
                <div v-if="rbacLoading" class="ppms-profile-skel" role="status" :aria-label="t('common.loading')">
                    <div class="ppms-profile-skel-line ppms-profile-access-skel-a" />
                    <div class="ppms-profile-skel-line ppms-profile-access-skel-b" />
                </div>
                <div v-else class="ppms-profile-access-card ppms-profile-access-card--tight">
                    <div class="ppms-profile-access-role-line">
                        <span class="ppms-profile-role-badge" :class="roleBadgeClass(myRbacRole)">{{ roleLabel(myRbacRole) }}</span>
                    </div>
                    <p class="ppms-profile-access-note">{{ t('profile.accessRoleNoteDefault') }}</p>
                    <p v-if="canManageRbac" class="ppms-profile-access-note ppms-profile-access-note--admin">
                        {{ t('profile.accessRoleAdminHint') }}
                    </p>
                    <div v-if="showSelfRoleChange" class="ppms-profile-access-field">
                        <label class="ppms-profile-access-lbl" for="self-role">{{ t('profile.accessRoleChange') }}</label>
                        <select
                            id="self-role"
                            v-model="selfRoleDraft"
                            class="ppms-profile-access-input"
                            :disabled="roleSaving"
                            @change="saveSelfRole"
                        >
                            <option v-for="r in assignableRoleOptionsSelf" :key="r" :value="r">{{ roleLabel(r) }}</option>
                        </select>
                    </div>
                    <p v-else-if="canManageRbac && isPermissionAdminRole(myRbacRole)" class="ppms-profile-access-note ppms-profile-access-note--lock">
                        {{ t('profile.accessRolePermissionAdminLocked') }}
                    </p>
                    <p v-else-if="!canManageRbac" class="ppms-profile-access-note">{{ t('profile.accessRoleUserHint') }}</p>
                    <p v-else-if="!roleOptions.length" class="ppms-profile-access-note">{{ t('profile.accessRoleNoRoleOptions') }}</p>
                </div>
            </section>

            <section class="ppms-profile-access-section" :aria-labelledby="'acc-perm-' + uid">
                <h3 :id="'acc-perm-' + uid" class="ppms-profile-access-h">{{ t('profile.accessSectionPermissions') }}</h3>
                <p class="ppms-profile-access-desc">{{ t('profile.accessSectionPermHint') }}</p>
                <ProfileTabPermissions :key="permKey" compact />
            </section>
        </div>

        <div v-show="activeSubTab === 'delegation'" class="ppms-profile-access-panel" role="tabpanel">
            <section class="ppms-profile-access-section" :aria-labelledby="'acc-del-' + uid">
                <h3 :id="'acc-del-' + uid" class="ppms-profile-access-h">{{ t('profile.accessSectionDelegation') }}</h3>
                <p class="ppms-profile-access-desc" :class="{ 'ppms-profile-access-desc--admin': canManageOthers }">
                    {{ canManageOthers ? t('profile.delegationIntroAdmin') : t('profile.delegationIntro') }}
                </p>

                <div v-if="canManageOthers" class="ppms-profile-access-card ppms-profile-access-card--tight">
                    <div class="ppms-profile-access-field">
                        <label class="ppms-profile-access-lbl" for="deleg-search">{{ t('profile.delegationDelegatorLabel') }}</label>
                        <div ref="delegLookupEl" class="ppms-profile-access-lookup">
                            <input
                                id="deleg-search"
                                v-model="delegSearchQ"
                                type="search"
                                class="ppms-profile-access-input"
                                autocomplete="off"
                                :placeholder="t('profile.delegationDelegatorPlaceholder')"
                                :aria-expanded="delegPickCandidates.length > 0"
                                aria-controls="deleg-pick-list"
                                @input="onDelegatorSearchInput"
                                @keydown.escape.prevent="closeDelegPick"
                            />
                            <ul
                                v-if="delegPickCandidates.length"
                                id="deleg-pick-list"
                                class="ppms-profile-access-pick"
                                role="listbox"
                            >
                                <li v-for="u in delegPickCandidates" :key="u.id" role="option">
                                    <button type="button" class="ppms-profile-access-pick-btn" @click="selectDelegatorUser(u)">
                                        <span class="ppms-profile-access-user">{{ u.name }}</span>
                                        <span class="ppms-profile-access-email">{{ u.email }}</span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <div class="ppms-profile-access-inline-actions">
                            <p v-if="delegatorContext" class="ppms-profile-access-picked ppms-profile-access-picked--inline">
                                <strong>{{ delegatorContext.name }}</strong>
                                <span class="ppms-profile-access-email">· {{ delegatorContext.email }}</span>
                            </p>
                            <button
                                v-if="delegatorContext"
                                type="button"
                                class="ppms-pf-btn ppms-profile-access-btn-secondary"
                                @click="clearDelegatorContext"
                            >
                                {{ t('profile.delegationDelegatorSelf') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="canManageOthers && delegatorContext"
                    class="ppms-profile-access-banner ppms-profile-access-banner--context"
                    role="status"
                >
                    {{ t('profile.delegationContextBanner', { name: delegatorContext.name }) }}
                </div>

                <div
                    v-if="showDelegatorRoleBlock"
                    class="ppms-profile-access-card ppms-profile-access-card--tight ppms-profile-access-card--accent"
                >
                    <p class="ppms-profile-access-sub">{{ t('profile.accessDelegatorRoleTitle', { name: delegatorContext.name }) }}</p>
                    <p class="ppms-profile-access-note">{{ t('profile.accessDelegatorRoleLead') }}</p>
                    <div class="ppms-profile-access-field">
                        <label class="ppms-profile-access-lbl" for="delegator-role">{{ t('profile.accessRoleChange') }}</label>
                        <select
                            id="delegator-role"
                            v-model="delegatorRoleDraft"
                            class="ppms-profile-access-input"
                            :disabled="roleSaving"
                            @change="saveDelegatorRole"
                        >
                            <option v-for="r in assignableRoleOptionsDelegator" :key="'d-' + r" :value="r">{{ roleLabel(r) }}</option>
                        </select>
                    </div>
                </div>

                <p v-else-if="canManageOthers && delegatorContext && delegatorRoleReady && isPermissionAdminRole(delegatorRoleDraft)" class="ppms-profile-access-note ppms-profile-access-note--lock">
                    {{ t('profile.rbacAdminTargetIsPermissionAdmin') }}
                </p>

                <div v-if="delErr" class="ppms-profile-access-banner ppms-profile-access-banner--err" role="alert">
                    {{ delErr }}
                </div>

                <div class="ppms-profile-access-card ppms-profile-access-card--tight">
                    <h4 class="ppms-profile-access-h4">{{ t('profile.delegationListTitle') }}</h4>
                    <div v-if="listLoading" class="ppms-profile-skel" role="status" :aria-label="t('common.loading')">
                        <div class="ppms-profile-skel-line ppms-profile-access-skel-a" />
                        <div class="ppms-profile-skel-line ppms-profile-access-skel-b" />
                    </div>
                    <div v-else-if="items.length === 0" class="ppms-profile-access-empty">{{ t('profile.delegationEmpty') }}</div>
                    <div v-else class="ppms-profile-table-wrap ppms-profile-access-table-wrap">
                        <table class="ppms-profile-table ppms-profile-access-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ t('profile.delegationColUser') }}</th>
                                    <th scope="col">{{ t('profile.delegationColScope') }}</th>
                                    <th scope="col">{{ t('profile.delegationColExpires') }}</th>
                                    <th scope="col">{{ t('profile.delegationColStatus') }}</th>
                                    <th scope="col">{{ t('profile.delegationColAction') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in items"
                                    :key="row.id"
                                    :class="{ 'ppms-profile-access-tr--muted': row.is_expired }"
                                >
                                    <td>
                                        <div class="ppms-profile-access-user">{{ row.delegatee?.name || '—' }}</div>
                                        <div class="ppms-profile-access-email">{{ row.delegatee?.email }}</div>
                                        <span class="ppms-profile-access-role">{{ roleLabel(row.delegatee?.role) }}</span>
                                    </td>
                                    <td>{{ scopeLabel(row.scope) }}</td>
                                    <td>{{ formatExpires(row.expires_at) }}</td>
                                    <td>
                                        <span v-if="row.is_expired" class="ppms-profile-access-badge ppms-profile-access-badge--exp">{{
                                            t('profile.delegationExpired')
                                        }}</span>
                                        <span v-else class="ppms-profile-access-badge ppms-profile-access-badge--ok">{{
                                            t('profile.delegationActive')
                                        }}</span>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="ppms-pf-btn ppms-profile-access-revoke"
                                            :disabled="revokingId === row.id"
                                            @click="revoke(row)"
                                        >
                                            {{ t('profile.delegationRevoke') }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>

        <div v-show="activeSubTab === 'admin' && canManageRbac" class="ppms-profile-access-panel" role="tabpanel">
            <ProfileRbacAdmin />
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '../../bootstrap';
import { ppmsConfirm, ppmsToastError, ppmsToastSuccess } from '../../ppmsUi';
import ProfileRbacAdmin from './ProfileRbacAdmin.vue';
import ProfileTabPermissions from './ProfileTabPermissions.vue';

const emit = defineEmits(['refresh']);

const props = defineProps({
    /** Hide page heading when embedded in System admin */
    embedded: { type: Boolean, default: false },
    /** Hide «Permissions» sub-tab (e.g. admin page focuses on delegation & RBAC) */
    hidePermissionsSubtab: { type: Boolean, default: false },
});

const { t, locale } = useI18n();

const uid = `u${Math.random().toString(36).slice(2, 9)}`;

const activeSubTab = ref(props.hidePermissionsSubtab ? 'delegation' : 'permissions');

const rbacLoading = ref(true);
const myRbacRole = ref('');
const roleOptions = ref([]);
const canManageRbac = ref(false);
const permissionAdminRoles = ref([]);
const permissionAdminRolesListed = computed(() => permissionAdminRoles.value.map((r) => roleLabel(r)).join(', '));
const selfRoleDraft = ref('');
const delegatorRoleDraft = ref('');
const delegatorRoleReady = ref(false);
const roleSaving = ref(false);
const permKey = ref(0);

function isPermissionAdminRole(role) {
    if (!role) {
        return false;
    }
    return permissionAdminRoles.value.includes(String(role));
}

/** Vai trò có thể gán qua UI (không gồm quản trị quyền) */
const assignableRoleOptionsDelegator = computed(() =>
    roleOptions.value.filter((r) => !isPermissionAdminRole(r)),
);

const assignableRoleOptionsSelf = computed(() => assignableRoleOptionsDelegator.value);

const showSelfRoleChange = computed(
    () => canManageRbac.value && assignableRoleOptionsSelf.value.length > 0 && !isPermissionAdminRole(myRbacRole.value),
);

const showDelegatorRoleBlock = computed(
    () =>
        canManageOthers.value &&
        !!delegatorContext.value &&
        delegatorRoleReady.value &&
        assignableRoleOptionsDelegator.value.length > 0 &&
        !isPermissionAdminRole(delegatorRoleDraft.value),
);

watch([canManageRbac, rbacLoading], () => {
    if (activeSubTab.value === 'admin' && (!canManageRbac.value || rbacLoading.value)) {
        activeSubTab.value = props.hidePermissionsSubtab ? 'delegation' : 'permissions';
    }
});

const listLoading = ref(true);
const delErr = ref('');
const items = ref([]);
const revokingId = ref(null);
const currentUserId = ref(null);
const canManageOthers = ref(false);
const delegatorContext = ref(null);

const delegSearchQ = ref('');
const delegPickCandidates = ref([]);

const delegLookupEl = ref(null);
let delegSearchTimer;

async function loadRbac() {
    rbacLoading.value = true;
    try {
        const { data } = await axios.get('/api/me/rbac');
        myRbacRole.value = data.role || '';
        roleOptions.value = Array.isArray(data.role_options) ? data.role_options : [];
        canManageRbac.value = !!data.can_manage;
        const raw = Array.isArray(data.permission_admin_roles) ? data.permission_admin_roles : ['admin'];
        permissionAdminRoles.value = raw.length ? raw : ['admin'];
        selfRoleDraft.value = myRbacRole.value || roleOptions.value[0] || '';
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('common.loading')));
    } finally {
        rbacLoading.value = false;
    }
}

async function saveSelfRole() {
    if (!currentUserId.value || !showSelfRoleChange.value) {
        return;
    }
    const ok = await ppmsConfirm(t('profile.confirmRoleChangeDelegator'), {
        title: t('profile.accessRoleChange'),
        confirmLabel: t('common.save'),
        cancelLabel: t('common.cancel'),
    });
    if (!ok) {
        selfRoleDraft.value = myRbacRole.value || selfRoleDraft.value;
        return;
    }
    roleSaving.value = true;
    try {
        await axios.patch(`/api/users/${currentUserId.value}/role`, { role: selfRoleDraft.value });
        myRbacRole.value = selfRoleDraft.value;
        ppmsToastSuccess(t('profile.accessRoleSaved'));
        emit('refresh');
        permKey.value += 1;
        await loadRbac();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('profile.saveError')));
        selfRoleDraft.value = myRbacRole.value || selfRoleDraft.value;
    } finally {
        roleSaving.value = false;
    }
}

async function loadDelegatorRoleDetail() {
    delegatorRoleReady.value = false;
    if (!delegatorContext.value?.id) {
        return;
    }
    try {
        const { data } = await axios.get('/api/users/lookup', { params: { ids: delegatorContext.value.id } });
        const row = Array.isArray(data) && data[0];
        delegatorRoleDraft.value = (row && row.role) || roleOptions.value[0] || '';
        delegatorRoleReady.value = true;
    } catch {
        delegatorRoleReady.value = false;
    }
}

async function saveDelegatorRole() {
    if (!delegatorContext.value?.id || !showDelegatorRoleBlock.value) {
        return;
    }
    const ok = await ppmsConfirm(t('profile.confirmRoleChangeDelegator'), {
        title: t('profile.accessRoleChange'),
        confirmLabel: t('common.save'),
        cancelLabel: t('common.cancel'),
    });
    if (!ok) {
        await loadDelegatorRoleDetail();
        return;
    }
    roleSaving.value = true;
    try {
        await axios.patch(`/api/users/${delegatorContext.value.id}/role`, { role: delegatorRoleDraft.value });
        ppmsToastSuccess(t('profile.accessRoleSaved'));
        emit('refresh');
        permKey.value += 1;
        await loadDelegatorRoleDetail();
        await loadDelegations();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('profile.saveError')));
        await loadDelegatorRoleDetail();
    } finally {
        roleSaving.value = false;
    }
}

function roleBadgeClass(role) {
    const r = (role || '').toLowerCase();
    if (!r) {
        return 'ppms-profile-role-badge--unassigned';
    }
    return `ppms-profile-role-badge--${r}`;
}

function roleLabel(role) {
    if (!role) {
        return t('layout.role.unassigned');
    }
    const key = `layout.role.${role}`;
    const tr = t(key);
    return tr === key ? role : tr;
}

function scopeLabel(scope) {
    const key = `profile.delegationScopes.${scope}`;
    const tr = t(key);
    return tr === key ? scope : tr;
}

function formatExpires(iso) {
    if (!iso) {
        return t('profile.delegationNoExpiry');
    }
    try {
        const d = new Date(iso);
        return d.toLocaleDateString(locale.value === 'vi' ? 'vi-VN' : 'en-GB', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    } catch {
        return '—';
    }
}

async function loadDelegations() {
    listLoading.value = true;
    delErr.value = '';
    try {
        const params = {};
        if (canManageOthers.value && delegatorContext.value) {
            params.delegator_id = delegatorContext.value.id;
        }
        const { data } = await axios.get('/api/me/delegations', { params });
        canManageOthers.value = !!data.can_manage_others;
        if (Array.isArray(data.role_options) && data.role_options.length) {
            roleOptions.value = data.role_options;
        }
        items.value = Array.isArray(data.items) ? data.items : [];
    } catch (e) {
        delErr.value = formatApiUserMessage(e, t('profile.delegationLoadError'));
    } finally {
        listLoading.value = false;
    }
}

function onDelegatorSearchInput() {
    clearTimeout(delegSearchTimer);
    delegSearchTimer = setTimeout(async () => {
        const q = delegSearchQ.value.trim();
        if (q.length < 2) {
            delegPickCandidates.value = [];

            return;
        }
        try {
            const { data } = await axios.get('/api/users/lookup', { params: { q } });
            delegPickCandidates.value = Array.isArray(data) ? data : [];
        } catch {
            delegPickCandidates.value = [];
        }
    }, 280);
}

function closeDelegPick() {
    delegPickCandidates.value = [];
}

function onDocClick(e) {
    const elTarget = e.target;
    if (delegLookupEl.value?.contains(elTarget)) {
        return;
    }
    closeDelegPick();
}

async function selectDelegatorUser(u) {
    if (currentUserId.value != null && u.id === currentUserId.value) {
        delegatorContext.value = null;
    } else {
        delegatorContext.value = u;
    }
    delegSearchQ.value = '';
    delegPickCandidates.value = [];
    await loadDelegations();
    if (delegatorContext.value) {
        await loadDelegatorRoleDetail();
    }
}

async function clearDelegatorContext() {
    delegatorContext.value = null;
    delegSearchQ.value = '';
    delegPickCandidates.value = [];
    delegatorRoleReady.value = false;
    await loadDelegations();
}

async function revoke(row) {
    const ok = await ppmsConfirm(t('profile.delegationRevokeConfirm'), {
        title: t('profile.delegationRevokeTitle'),
        destructive: true,
        confirmLabel: t('profile.delegationRevoke'),
        cancelLabel: t('common.cancel'),
    });
    if (!ok) {
        return;
    }
    revokingId.value = row.id;
    try {
        await axios.delete(`/api/me/delegations/${row.id}`);
        ppmsToastSuccess(t('profile.delegationRevoked'));
        await loadDelegations();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('profile.saveError')));
    } finally {
        revokingId.value = null;
    }
}

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/user');
        currentUserId.value = data?.id ?? null;
    } catch {
        currentUserId.value = null;
    }
    document.addEventListener('click', onDocClick, true);
    await Promise.all([loadRbac(), loadDelegations()]);
});

onUnmounted(() => {
    clearTimeout(delegSearchTimer);
    document.removeEventListener('click', onDocClick, true);
});
</script>

<style scoped>
.ppms-profile-access {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    max-width: 56rem;
}

.ppms-profile-access--embedded {
    max-width: none;
    gap: 1rem;
}

.ppms-profile-access-head {
    margin: 0;
}

.ppms-profile-access-title {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 0 0.35rem;
    letter-spacing: -0.02em;
    color: var(--ppms-pf-fg, inherit);
}

.ppms-profile-access-lead {
    font-size: 0.88rem;
    color: var(--ppms-pf-muted);
    margin: 0;
    line-height: 1.45;
}

.ppms-profile-access-banner {
    padding: 0.75rem 0.9rem;
    border-radius: 8px;
    font-size: 0.88rem;
}

.ppms-profile-access-banner--readonly {
    border: 1px solid rgba(148, 163, 184, 0.45);
    background: rgba(254, 243, 199, 0.35);
    border-left: 4px solid #d97706;
}

.ppms-profile-access-banner-title {
    font-size: 0.92rem;
    font-weight: 600;
    margin: 0 0 0.35rem;
    color: var(--ppms-pf-fg, inherit);
}

.ppms-profile-access-banner-summary {
    font-size: 0.86rem;
    line-height: 1.45;
    margin: 0 0 0.4rem;
    color: var(--ppms-pf-fg, inherit);
}

.ppms-profile-access-details {
    margin: 0;
}

.ppms-profile-access-details-summary {
    cursor: pointer;
    font-size: 0.82rem;
    font-weight: 600;
    color: #b45309;
}

.ppms-profile-access-banner-body {
    font-size: 0.84rem;
    line-height: 1.5;
    margin: 0.4rem 0 0;
    color: var(--ppms-pf-muted);
}

.ppms-profile-access-subtabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    padding: 0.2rem;
    border-radius: 10px;
    background: rgba(148, 163, 184, 0.12);
    border: 1px solid rgba(148, 163, 184, 0.28);
}

.ppms-profile-access-subtab {
    flex: 1 1 auto;
    min-width: 7rem;
    padding: 0.45rem 0.65rem;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: var(--ppms-pf-muted);
    font-size: 0.86rem;
    font-weight: 600;
    cursor: pointer;
    transition:
        background 0.12s ease,
        color 0.12s ease;
}

.ppms-profile-access-subtab:hover {
    color: var(--ppms-pf-fg, inherit);
}

.ppms-profile-access-subtab--active {
    background: var(--ppms-pf-card-bg, #fff);
    color: var(--ppms-pf-fg, inherit);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
}

.ppms-profile-access-panel {
    min-width: 0;
}

.ppms-profile-access-section {
    margin-bottom: 1.25rem;
}

.ppms-profile-access-section:last-child {
    margin-bottom: 0;
}

.ppms-profile-access-h {
    font-size: 0.95rem;
    font-weight: 600;
    margin: 0 0 0.35rem;
    color: var(--ppms-pf-fg, inherit);
}

.ppms-profile-access-h4 {
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0 0 0.65rem;
    color: var(--ppms-pf-fg, inherit);
}

.ppms-profile-access-desc {
    font-size: 0.86rem;
    color: var(--ppms-pf-muted);
    margin: 0 0 0.75rem;
    line-height: 1.45;
}

.ppms-profile-access-desc--admin {
    padding: 0.45rem 0.55rem;
    border-radius: 6px;
    background: rgba(59, 130, 246, 0.06);
    border: 1px solid rgba(59, 130, 246, 0.18);
}

.ppms-profile-access-card {
    border: 1px solid rgba(148, 163, 184, 0.38);
    border-radius: 10px;
    padding: 0.85rem 1rem;
    background: var(--ppms-pf-card-bg, rgba(255, 255, 255, 0.5));
}

.ppms-profile-access-card--tight {
    padding: 0.75rem 0.9rem;
}

.ppms-profile-access-card--accent {
    border-color: rgba(59, 130, 246, 0.28);
    background: rgba(59, 130, 246, 0.04);
}

.ppms-profile-access-card--form {
    margin-top: 0.75rem;
}

.ppms-profile-access-role-line {
    margin-bottom: 0.35rem;
}

.ppms-profile-access-note {
    font-size: 0.82rem;
    color: var(--ppms-pf-muted);
    line-height: 1.45;
    margin: 0 0 0.5rem;
}

.ppms-profile-access-note:last-child {
    margin-bottom: 0;
}

.ppms-profile-access-note--admin {
    padding: 0.4rem 0.5rem;
    border-radius: 6px;
    background: rgba(59, 130, 246, 0.06);
    border: 1px solid rgba(59, 130, 246, 0.18);
    color: #1e40af;
}

.ppms-profile-access-note--lock {
    padding: 0.4rem 0.5rem;
    border-radius: 6px;
    background: rgba(100, 116, 139, 0.08);
    border: 1px solid rgba(100, 116, 139, 0.22);
    color: var(--ppms-pf-fg, inherit);
}

.ppms-profile-access-field {
    margin-bottom: 0.65rem;
}

.ppms-profile-access-field:last-child {
    margin-bottom: 0;
}

.ppms-profile-access-field-row {
    display: grid;
    gap: 0.65rem;
    margin-bottom: 0.65rem;
}

@media (min-width: 560px) {
    .ppms-profile-access-field-row {
        grid-template-columns: 1fr 1fr;
        align-items: end;
    }
}

.ppms-profile-access-lbl {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.3rem;
    color: var(--ppms-pf-fg, inherit);
}

.ppms-profile-access-sub {
    font-weight: 600;
    margin: 0 0 0.35rem;
    font-size: 0.88rem;
    color: var(--ppms-pf-fg, inherit);
}

.ppms-profile-access-input {
    width: 100%;
    max-width: 100%;
    padding: 0.45rem 0.55rem;
    border-radius: 6px;
    border: 1px solid rgba(148, 163, 184, 0.5);
    background: var(--ppms-pf-input-bg, #fff);
    color: inherit;
    font-size: 0.88rem;
}

.ppms-profile-access-input:focus-visible {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
}

.ppms-profile-access-inline-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem 0.75rem;
    margin-top: 0.45rem;
}

.ppms-profile-access-banner--context {
    padding: 0.55rem 0.65rem;
    border-radius: 6px;
    font-size: 0.85rem;
    background: rgba(59, 130, 246, 0.08);
    color: #1e40af;
    border: 1px solid rgba(59, 130, 246, 0.22);
}

.ppms-profile-access-banner--err {
    background: rgba(185, 28, 28, 0.08);
    color: #991b1b;
    border: 1px solid rgba(185, 28, 28, 0.22);
}

.ppms-profile-access-empty {
    font-size: 0.86rem;
    color: var(--ppms-pf-muted);
    padding: 0.5rem 0;
}

.ppms-profile-access-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.ppms-profile-access-table {
    font-size: 0.86rem;
}

.ppms-profile-access-table th,
.ppms-profile-access-table td {
    vertical-align: top;
    padding: 0.5rem 0.6rem;
}

.ppms-profile-access-tr--muted {
    opacity: 0.72;
}

.ppms-profile-access-user {
    font-weight: 500;
}

.ppms-profile-access-email {
    font-size: 0.8rem;
    color: var(--ppms-pf-muted);
}

.ppms-profile-access-role {
    display: inline-block;
    margin-top: 0.2rem;
    font-size: 0.7rem;
    padding: 0.08rem 0.35rem;
    border-radius: 4px;
    background: rgba(100, 116, 139, 0.12);
    color: var(--ppms-pf-muted);
}

.ppms-profile-access-badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.12rem 0.4rem;
    border-radius: 999px;
}

.ppms-profile-access-badge--ok {
    background: rgba(22, 163, 74, 0.12);
    color: #15803d;
}

.ppms-profile-access-badge--exp {
    background: rgba(100, 116, 139, 0.18);
    color: #475569;
}

.ppms-profile-access-revoke:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.ppms-profile-access-lookup {
    position: relative;
    max-width: 100%;
}

.ppms-profile-access-pick {
    list-style: none;
    margin: 0.25rem 0 0;
    padding: 0;
    border: 1px solid rgba(148, 163, 184, 0.45);
    border-radius: 6px;
    background: var(--ppms-pf-input-bg, #fff);
    max-height: 200px;
    overflow: auto;
    z-index: 4;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
}

.ppms-profile-access-pick-btn {
    display: block;
    width: 100%;
    text-align: left;
    padding: 0.45rem 0.6rem;
    border: none;
    background: transparent;
    cursor: pointer;
    color: inherit;
    font: inherit;
}

.ppms-profile-access-pick-btn:hover,
.ppms-profile-access-pick-btn:focus-visible {
    background: rgba(59, 130, 246, 0.1);
    outline: none;
}

.ppms-profile-access-picked {
    font-size: 0.86rem;
    margin: 0.45rem 0 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
}

.ppms-profile-access-picked--inline {
    margin: 0;
}

.ppms-profile-access-clear {
    font-size: 0.82rem;
    border: none;
    background: none;
    color: #2563eb;
    cursor: pointer;
    text-decoration: underline;
}

.ppms-profile-access-actions {
    margin-top: 0.5rem;
}

.ppms-profile-access-btn-secondary {
    font-size: 0.82rem;
    padding: 0.32rem 0.6rem;
}

.ppms-profile-access-skel-a {
    width: 50%;
}

.ppms-profile-access-skel-b {
    width: 75%;
}
</style>
