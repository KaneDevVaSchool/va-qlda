<template>
    <div class="ppms-page" :class="{ 'ppms-page--teams-tree': viewMode === 'tree' }">
        <section class="ppms-card">
            <div class="ppms-teams-head">
                <div>
                    <h2>{{ t('teams.title') }}</h2>
                    <p class="ppms-muted ppms-mt-sm">{{ t('teams.subtitle') }}</p>
                </div>
                <div class="ppms-teams-head-actions">
                    <router-link to="/kpi" class="ppms-btn-secondary ppms-btn-linkish">{{ t('teams.linkKpi') }}</router-link>
                    <button v-if="canCreate" type="button" class="ppms-btn-primary" @click="openCreateModal">
                        {{ t('teams.createBtn') }}
                    </button>
                </div>
            </div>

            <div class="ppms-teams-toolbar ppms-mt">
                <span class="ppms-teams-toolbar-label">{{ t('teams.viewMode') }}</span>
                <div class="ppms-teams-seg" role="group" :aria-label="t('teams.viewMode')">
                    <button
                        type="button"
                        class="ppms-teams-seg__btn"
                        :class="{ 'ppms-teams-seg__btn--on': viewMode === 'cards' }"
                        @click="viewMode = 'cards'"
                    >
                        {{ t('teams.viewCards') }}
                    </button>
                    <button
                        type="button"
                        class="ppms-teams-seg__btn"
                        :class="{ 'ppms-teams-seg__btn--on': viewMode === 'tree' }"
                        @click="viewMode = 'tree'"
                    >
                        {{ t('teams.viewTree') }}
                    </button>
                </div>
            </div>
        </section>

        <section class="ppms-card ppms-mt">
            <h3 class="ppms-h3">{{ t('teams.listSection') }}</h3>
            <div v-if="loading" class="ppms-loading-line ppms-mt-sm" role="status">{{ t('common.loading') }}</div>
            <p v-else-if="!teams.length" class="ppms-muted ppms-mt-sm">{{ t('teams.empty') }}</p>

            <div v-else-if="viewMode === 'cards'" class="ppms-teams-card-grid ppms-mt">
                <article
                    v-for="row in teams"
                    :key="row.id"
                    class="ppms-team-card"
                    :class="{ 'ppms-team-card--active': detail?.id === row.id }"
                >
                    <button type="button" class="ppms-team-card__main" @click="openTeam(row.id)">
                        <h4 class="ppms-team-card__title">{{ row.name }}</h4>
                        <p v-if="row.description" class="ppms-team-card__desc ppms-muted">{{ row.description }}</p>
                        <div class="ppms-team-card__meta">
                            <span>{{ t('teams.membersCount') }}: {{ row.members_count ?? 0 }}</span>
                            <span v-if="row.creator?.name"> · {{ row.creator.name }}</span>
                        </div>
                    </button>
                    <div v-if="row.can_manage" class="ppms-team-card__actions">
                        <button type="button" class="ppms-btn-ghost" @click.stop="confirmDelete(row)">
                            {{ t('common.delete') }}
                        </button>
                    </div>
                </article>
            </div>

            <div v-else class="ppms-team-forest ppms-mt" role="region" :aria-label="t('teams.viewTree')">
                <p class="ppms-team-tree-legend">{{ t('teams.treeLegend') }}</p>
                <div
                    v-for="team in teams"
                    :key="team.id"
                    class="ppms-team-tree-block"
                    :class="{ 'ppms-team-tree-block--active': detail?.id === team.id }"
                >
                    <div class="ppms-team-tree-block__head">
                        <div class="ppms-team-tree-block__title-row">
                            <span class="ppms-team-tree-block__folder" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z" />
                                </svg>
                            </span>
                            <div class="ppms-team-tree-block__title-wrap">
                                <button type="button" class="ppms-team-tree-block__title" @click="openTeam(team.id)">
                                    {{ team.name }}
                                </button>
                                <p v-if="team.description" class="ppms-team-tree-block__snippet">{{ team.description }}</p>
                            </div>
                        </div>
                        <span class="ppms-team-tree-block__pill">{{ team.members_count ?? 0 }} {{ t('teams.membersShort') }}</span>
                    </div>

                    <div v-if="!team.members?.length" class="ppms-team-tree-empty">
                        <span class="ppms-team-tree-empty__icon" aria-hidden="true">◇</span>
                        <p>{{ t('teams.treeNoMembers') }}</p>
                    </div>

                    <div v-else class="ppms-team-tree" :aria-label="team.name">
                        <div class="ppms-team-tree__structure">
                            <div class="ppms-team-tree__leaders">
                                <div
                                    v-for="node in treeLeaders(team)"
                                    :key="'l-' + node.id"
                                    class="ppms-team-tree-node ppms-team-tree-node--leader"
                                >
                                    <div class="ppms-team-tree-node__avatar ppms-team-tree-node__avatar--leader" :title="node.name">
                                        {{ initials(node.name) }}
                                    </div>
                                    <div class="ppms-team-tree-node__body">
                                        <div class="ppms-team-tree-node__row">
                                            <span class="ppms-team-tree-node__badge">{{ t('teams.roleLeader') }}</span>
                                            <span class="ppms-team-tree-node__name">{{ node.name }}</span>
                                        </div>
                                        <p v-if="node.pivot?.position" class="ppms-team-tree-node__meta">{{ node.pivot.position }}</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="treeMembers(team).length" class="ppms-team-tree__branch-wrap">
                                <div class="ppms-team-tree__branch-label">
                                    <span class="ppms-team-tree__branch-line" aria-hidden="true" />
                                    <span>{{ t('teams.treeMembersLabel') }}</span>
                                </div>
                                <ul class="ppms-team-tree__members" role="list">
                                    <li v-for="node in treeMembers(team)" :key="'m-' + node.id" class="ppms-team-tree__li" role="listitem">
                                        <div class="ppms-team-tree-node ppms-team-tree-node--member">
                                            <div class="ppms-team-tree-node__avatar" :title="node.name">
                                                {{ initials(node.name) }}
                                            </div>
                                            <div class="ppms-team-tree-node__body">
                                                <div class="ppms-team-tree-node__row">
                                                    <span class="ppms-team-tree-node__badge ppms-team-tree-node__badge--member">{{ t('teams.roleMember') }}</span>
                                                    <span class="ppms-team-tree-node__name">{{ node.name }}</span>
                                                </div>
                                                <p v-if="node.pivot?.position" class="ppms-team-tree-node__meta">{{ node.pivot.position }}</p>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="detail" class="ppms-card ppms-mt">
            <div class="ppms-teams-detail-head">
                <h3 class="ppms-h3">{{ detail.name }}</h3>
                <p v-if="detail.description" class="ppms-muted ppms-mt-sm">{{ detail.description }}</p>
            </div>

            <h4 class="ppms-h4 ppms-mt">{{ t('teams.membersTitle') }}</h4>
            <p v-if="detail.can_manage" class="ppms-muted ppms-mt-sm ppms-team-hint">{{ t('teams.manageHint') }}</p>
            <div class="ppms-table-scroll ppms-mt-sm">
                <table class="ppms-table">
                    <thead>
                        <tr>
                            <th>{{ t('teams.colName') }}</th>
                            <th>{{ t('teams.colEmail') }}</th>
                            <th>{{ t('teams.colPosition') }}</th>
                            <th>{{ t('teams.colRole') }}</th>
                            <th>{{ t('teams.colPermissions') }}</th>
                            <th v-if="detail.can_manage" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in detail.members || []" :key="m.id">
                            <td>{{ m.name }}</td>
                            <td>{{ m.email }}</td>
                            <td>{{ m.pivot?.position || '—' }}</td>
                            <td>{{ formatMemberRole(m) }}</td>
                            <td class="ppms-team-perm-cell">{{ formatPermissionsShort(m) }}</td>
                            <td v-if="detail.can_manage" class="ppms-td-actions">
                                <button type="button" class="ppms-btn-ghost" @click="openMemberModal(m)">
                                    {{ t('teams.editMember') }}
                                </button>
                                <button
                                    v-if="Number(m.id) !== Number(detail.created_by) || me?.role === 'admin'"
                                    type="button"
                                    class="ppms-btn-ghost"
                                    @click="removeMember(m.id)"
                                >
                                    {{ t('teams.removeMember') }}
                                </button>
                                <span v-else class="ppms-muted">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="detail.can_manage" class="ppms-mt">
                <h4 class="ppms-h4">{{ t('teams.addMembers') }}</h4>
                <label class="ppms-field ppms-mt-sm">
                    <span>{{ t('teams.searchUser') }}</span>
                    <input
                        v-model="lookupQ"
                        type="search"
                        autocomplete="off"
                        :placeholder="t('teams.searchPlaceholder')"
                        @input="onLookupInput"
                    />
                </label>
                <ul v-if="lookupHits.length" class="ppms-lookup-list ppms-mt-sm">
                    <li v-for="u in lookupHits" :key="u.id">
                        <button type="button" class="ppms-link-btn" @click="addMember(u)">
                            {{ u.name }} — {{ u.email }}
                        </button>
                    </li>
                </ul>
            </div>
        </section>

        <!-- Modal: tạo nhóm -->
        <div
            v-if="showCreateModal"
            class="ppms-modal-backdrop"
            role="presentation"
            @click.self="closeCreateModal"
        >
            <div
                class="ppms-modal ppms-modal--wide ppms-team-create-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="teams-create-title"
                aria-describedby="teams-create-desc"
                @click.stop
            >
                <button type="button" class="ppms-team-create-close" :aria-label="t('common.close')" @click="closeCreateModal">
                    <span aria-hidden="true">×</span>
                </button>
                <div class="ppms-team-create-hero">
                    <div class="ppms-team-create-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </div>
                    <div>
                        <h2 id="teams-create-title" class="ppms-modal-title ppms-team-create-title">{{ t('teams.createSection') }}</h2>
                        <p id="teams-create-desc" class="ppms-team-create-sub">{{ t('teams.createModalSubtitle') }}</p>
                    </div>
                </div>

                <form class="ppms-team-create-form" @submit.prevent="createTeam">
                    <div class="ppms-team-create-fields">
                        <label class="ppms-field ppms-team-create-field">
                            <span class="ppms-team-create-label">{{ t('teams.name') }} <abbr class="ppms-team-create-req" :title="t('teams.fieldRequired')">*</abbr></span>
                            <input
                                v-model="createForm.name"
                                type="text"
                                required
                                maxlength="255"
                                autocomplete="organization"
                                :placeholder="t('teams.namePlaceholder')"
                                :disabled="creating"
                                :aria-invalid="!!createErrDetail"
                                aria-describedby="teams-create-err"
                            />
                        </label>
                        <label class="ppms-field ppms-team-create-field">
                            <span class="ppms-team-create-label">{{ t('teams.description') }}</span>
                            <textarea
                                v-model="createForm.description"
                                rows="4"
                                maxlength="5000"
                                :placeholder="t('teams.descriptionPlaceholder')"
                                :disabled="creating"
                            />
                        </label>
                    </div>

                    <div
                        v-if="createErrDetail"
                        id="teams-create-err"
                        class="ppms-team-create-alert ppms-team-create-alert--err"
                        role="alert"
                        aria-live="assertive"
                    >
                        <span class="ppms-team-create-alert-icon" aria-hidden="true">!</span>
                        <div class="ppms-team-create-alert-body">
                            <p v-if="createErrSummary" class="ppms-team-create-alert-lead">
                                <strong>{{ createErrSummary }}</strong>
                            </p>
                            <p class="ppms-team-create-alert-msg">{{ createErrDetail }}</p>
                        </div>
                    </div>

                    <div class="ppms-modal-actions ppms-team-create-actions">
                        <button type="button" class="ppms-btn-ghost ppms-modal-btn" :disabled="creating" @click="closeCreateModal">
                            {{ t('common.cancel') }}
                        </button>
                        <button type="submit" class="ppms-btn-primary ppms-modal-btn" :disabled="creating || !createForm.name.trim()">
                            <span v-if="creating" class="ppms-team-create-spinner" aria-hidden="true" />
                            {{ creating ? t('teams.creating') : t('teams.createBtn') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: chỉnh thành viên -->
        <div
            v-if="showMemberModal && memberEdit"
            class="ppms-modal-backdrop"
            role="presentation"
            @click.self="closeMemberModal"
        >
            <div class="ppms-modal ppms-modal--wide" role="dialog" aria-modal="true" aria-labelledby="teams-member-title" @click.stop>
                <h2 id="teams-member-title" class="ppms-modal-title">{{ t('teams.editMemberTitle') }}</h2>
                <p class="ppms-muted ppms-mt-sm">{{ memberEdit.name }} · {{ memberEdit.email }}</p>
                <form class="ppms-stack ppms-mt" @submit.prevent="saveMemberEdit">
                    <label class="ppms-field">
                        <span>{{ t('teams.colRole') }}</span>
                        <select v-model="memberEdit.role">
                            <option value="leader">{{ t('teams.roleLeader') }}</option>
                            <option value="member">{{ t('teams.roleMember') }}</option>
                        </select>
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('teams.colPosition') }}</span>
                        <input v-model="memberEdit.position" type="text" maxlength="255" :placeholder="t('teams.positionPlaceholder')" />
                    </label>
                    <fieldset class="ppms-team-perm-fieldset">
                        <legend class="ppms-field"><span>{{ t('teams.colPermissions') }}</span></legend>
                        <label v-for="key in TEAM_PERMISSION_KEYS" :key="key" class="ppms-team-perm-row">
                            <input v-model="memberEdit.permissions" type="checkbox" :value="key" />
                            <span>{{ permLabel(key) }}</span>
                        </label>
                    </fieldset>
                    <p v-if="memberEditErr" class="ppms-error">{{ memberEditErr }}</p>
                    <div class="ppms-modal-actions">
                        <button type="button" class="ppms-btn-ghost ppms-modal-btn" @click="closeMemberModal">
                            {{ t('common.cancel') }}
                        </button>
                        <button type="submit" class="ppms-btn-primary ppms-modal-btn" :disabled="memberSaving">
                            {{ t('common.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { ppmsConfirm, ppmsToastError, ppmsToastSuccess } from '@/ppmsUi';

const { t } = useI18n();

const TEAM_PERMISSION_KEYS = ['manage_members', 'edit_team_meta', 'view_team_kpi', 'assign_projects'];

const loading = ref(true);
const teams = ref([]);
const me = ref(null);
const detail = ref(null);
const viewMode = ref('cards');
const creating = ref(false);
const createErrSummary = ref('');
const createErrDetail = ref('');
const createForm = reactive({ name: '', description: '' });
const showCreateModal = ref(false);
const showMemberModal = ref(false);
const memberEdit = ref(null);
const memberEditErr = ref('');
const memberSaving = ref(false);

const lookupQ = ref('');
const lookupHits = ref([]);
let lookupTimer = null;

const canCreate = computed(() => ['admin', 'pm', 'tl'].includes(me.value?.role));

function treeLeaders(team) {
    return (team.members || []).filter((m) => m.pivot?.role === 'leader');
}

function treeMembers(team) {
    return (team.members || []).filter((m) => m.pivot?.role !== 'leader');
}

function initials(name) {
    if (!name || typeof name !== 'string') {
        return '?';
    }
    const parts = name.trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) {
        return '?';
    }
    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function permLabel(key) {
    return t('teams.perm_' + key);
}

function formatMemberRole(m) {
    const r = m.pivot?.role;
    if (r === 'leader') {
        return t('teams.roleLeader');
    }
    return t('teams.roleMember');
}

function formatPermissionsShort(m) {
    const perms = m.pivot?.permissions;
    if (!perms || !Array.isArray(perms) || !perms.length) {
        return '—';
    }
    return perms.map((k) => permLabel(k)).join(', ');
}

function clearCreateErrors() {
    createErrSummary.value = '';
    createErrDetail.value = '';
}

function parseCreateTeamError(e) {
    const data = e.response?.data;
    if (!e.response) {
        return { summary: '', detail: t('teams.createErrorNetwork') };
    }
    if (data?.errors && typeof data.errors === 'object') {
        const lines = [];
        for (const msgs of Object.values(data.errors)) {
            if (Array.isArray(msgs)) {
                lines.push(...msgs);
            } else if (typeof msgs === 'string') {
                lines.push(msgs);
            }
        }
        if (lines.length) {
            return { summary: t('teams.createErrorValidation'), detail: lines.join(' ') };
        }
    }
    if (typeof data?.message === 'string' && data.message.trim()) {
        return { summary: '', detail: data.message.trim() };
    }
    return { summary: '', detail: t('teams.createError') };
}

function openCreateModal() {
    clearCreateErrors();
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
    clearCreateErrors();
}

function openMemberModal(m) {
    memberEditErr.value = '';
    const perms = m.pivot?.permissions;
    memberEdit.value = {
        id: m.id,
        name: m.name,
        email: m.email,
        role: m.pivot?.role === 'leader' ? 'leader' : 'member',
        position: m.pivot?.position || '',
        permissions: Array.isArray(perms) ? [...perms] : [],
    };
    showMemberModal.value = true;
}

function closeMemberModal() {
    showMemberModal.value = false;
    memberEdit.value = null;
}

async function saveMemberEdit() {
    if (!detail.value || !memberEdit.value) {
        return;
    }
    memberEditErr.value = '';
    memberSaving.value = true;
    try {
        const { data } = await axios.patch(`/api/teams/${detail.value.id}/members/${memberEdit.value.id}`, {
            role: memberEdit.value.role,
            position: memberEdit.value.position?.trim() || null,
            permissions: memberEdit.value.permissions,
        });
        detail.value = data;
        ppmsToastSuccess(t('teams.memberUpdated'));
        closeMemberModal();
        await load();
    } catch (e) {
        memberEditErr.value = e.response?.data?.message || t('teams.memberUpdateError');
    } finally {
        memberSaving.value = false;
    }
}

async function load() {
    loading.value = true;
    try {
        const [u, list] = await Promise.all([
            axios.get('/api/user'),
            axios.get('/api/teams', { params: { include: 'members' } }),
        ]);
        me.value = u.data;
        teams.value = Array.isArray(list.data) ? list.data : [];
    } catch (e) {
        ppmsToastError(t('teams.loadError'));
    } finally {
        loading.value = false;
    }
}

async function openTeam(id) {
    try {
        const { data } = await axios.get(`/api/teams/${id}`);
        detail.value = data;
    } catch (e) {
        ppmsToastError(t('teams.loadDetailError'));
    }
}

async function createTeam() {
    clearCreateErrors();
    const nameTrim = createForm.name.trim();
    if (!nameTrim) {
        createErrSummary.value = t('teams.createErrorValidation');
        createErrDetail.value = t('teams.nameRequired');
        return;
    }
    creating.value = true;
    try {
        const { data: created } = await axios.post('/api/teams', {
            name: nameTrim,
            description: createForm.description?.trim() || null,
        });
        const displayName = created?.name || nameTrim;
        ppmsToastSuccess(t('teams.createdWithName', { name: displayName }));
        createForm.name = '';
        createForm.description = '';
        closeCreateModal();
        await load();
        if (created?.id) {
            await openTeam(created.id);
        }
    } catch (e) {
        const parsed = parseCreateTeamError(e);
        createErrSummary.value = parsed.summary;
        createErrDetail.value = parsed.detail;
    } finally {
        creating.value = false;
    }
}

async function confirmDelete(teamRow) {
    if (!(await ppmsConfirm(t('teams.deleteConfirm', { name: teamRow.name })))) {
        return;
    }
    try {
        await axios.delete(`/api/teams/${teamRow.id}`);
        ppmsToastSuccess(t('teams.deleted'));
        if (detail.value?.id === teamRow.id) {
            detail.value = null;
        }
        await load();
    } catch (e) {
        ppmsToastError(t('teams.deleteError'));
    }
}

function onLookupInput() {
    clearTimeout(lookupTimer);
    const q = lookupQ.value.trim();
    if (q.length < 2) {
        lookupHits.value = [];

        return;
    }
    lookupTimer = setTimeout(async () => {
        try {
            const { data } = await axios.get('/api/users/lookup', { params: { q } });
            const existing = new Set((detail.value?.members || []).map((m) => m.id));
            lookupHits.value = (Array.isArray(data) ? data : []).filter((u) => !existing.has(u.id)).slice(0, 20);
        } catch {
            lookupHits.value = [];
        }
    }, 300);
}

async function addMember(u) {
    if (!detail.value) {
        return;
    }
    try {
        await axios.post(`/api/teams/${detail.value.id}/members`, { user_ids: [u.id] });
        lookupQ.value = '';
        lookupHits.value = [];
        ppmsToastSuccess(t('teams.memberAdded'));
        await openTeam(detail.value.id);
        await load();
    } catch (e) {
        ppmsToastError(t('teams.memberAddError'));
    }
}

async function removeMember(userId) {
    if (!detail.value) {
        return;
    }
    if (me.value?.role !== 'admin' && !(await ppmsConfirm(t('teams.removeConfirm')))) {
        return;
    }
    try {
        await axios.delete(`/api/teams/${detail.value.id}/members/${userId}`);
        ppmsToastSuccess(t('teams.memberRemoved'));
        await openTeam(detail.value.id);
        await load();
    } catch (e) {
        ppmsToastError(t('teams.memberRemoveError'));
    }
}

function syncModalBodyClass() {
    if (showCreateModal.value || showMemberModal.value) {
        document.body.classList.add('ppms-modal-open');
    } else {
        document.body.classList.remove('ppms-modal-open');
    }
}

watch([showCreateModal, showMemberModal], syncModalBodyClass);

onMounted(() => {
    load();
    syncModalBodyClass();
});

onUnmounted(() => {
    document.body.classList.remove('ppms-modal-open');
});
</script>

<style scoped>
.ppms-teams-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}
.ppms-teams-head-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
}
.ppms-btn-linkish {
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.ppms-h3 {
    font-size: 1.1rem;
    margin: 0 0 0.5rem;
}
.ppms-h4 {
    font-size: 1rem;
    margin: 0;
}
.ppms-teams-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
}
.ppms-teams-toolbar-label {
    font-size: 0.9rem;
    color: var(--ppms-muted, #666);
}
.ppms-teams-seg {
    display: inline-flex;
    border: 1px solid var(--ppms-border, #ccc);
    border-radius: 8px;
    overflow: hidden;
}
.ppms-teams-seg__btn {
    border: none;
    background: transparent;
    padding: 0.4rem 0.9rem;
    font: inherit;
    cursor: pointer;
    color: inherit;
}
.ppms-teams-seg__btn--on {
    background: rgba(0, 0, 0, 0.06);
    font-weight: 600;
}
.ppms-teams-card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1rem;
}
.ppms-team-card {
    border: 1px solid var(--ppms-border, #ddd);
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-height: 120px;
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
}
.ppms-team-card--active {
    border-color: var(--ppms-accent, #2563eb);
    box-shadow: 0 0 0 1px var(--ppms-accent, #2563eb);
}
.ppms-team-card__main {
    flex: 1;
    text-align: left;
    border: none;
    background: transparent;
    padding: 1rem;
    cursor: pointer;
    font: inherit;
    color: inherit;
}
.ppms-team-card__main:hover {
    background: rgba(0, 0, 0, 0.03);
}
.ppms-team-card__title {
    margin: 0 0 0.35rem;
    font-size: 1.05rem;
}
.ppms-team-card__desc {
    margin: 0 0 0.5rem;
    font-size: 0.875rem;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.ppms-team-card__meta {
    font-size: 0.8rem;
    color: var(--ppms-muted, #666);
}
.ppms-team-card__actions {
    padding: 0 1rem 1rem;
}
.ppms-page--teams-tree {
    width: 100%;
    max-width: min(100%, 1920px);
    margin-left: auto;
    margin-right: auto;
}
.ppms-team-forest {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    align-content: start;
    width: 100%;
    grid-auto-rows: min-content;
}
@media (min-width: 900px) {
    .ppms-team-forest {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem 1.25rem;
        min-height: min(62vh, 820px);
    }
}
@media (min-width: 1440px) {
    .ppms-team-forest {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
.ppms-team-tree-legend {
    grid-column: 1 / -1;
    margin: 0 0 0.15rem;
    font-size: 0.82rem;
    line-height: 1.4;
    color: var(--ppms-muted, #64748b);
    padding: 0.45rem 0.65rem;
    border-radius: 8px;
    background: rgba(37, 99, 235, 0.06);
    border: 1px solid rgba(37, 99, 235, 0.12);
}
.ppms-team-tree-block {
    --ppms-tree-line: rgba(37, 99, 235, 0.28);
    --ppms-tree-line-soft: rgba(37, 99, 235, 0.12);
    border: 1px solid var(--ppms-border, #e2e8f0);
    border-radius: 16px;
    padding: 0;
    background: var(--ppms-surface, #fff);
    box-shadow:
        0 1px 2px rgba(15, 23, 42, 0.04),
        0 4px 16px -4px rgba(15, 23, 42, 0.08);
    overflow: hidden;
    transition:
        box-shadow 0.2s ease,
        border-color 0.2s ease;
}
.ppms-team-tree-block:hover {
    border-color: rgba(37, 99, 235, 0.22);
    box-shadow:
        0 2px 4px rgba(15, 23, 42, 0.06),
        0 8px 24px -6px rgba(37, 99, 235, 0.12);
}
.ppms-team-tree-block--active {
    border-color: var(--ppms-accent, #2563eb);
    box-shadow:
        0 0 0 2px rgba(37, 99, 235, 0.2),
        0 8px 28px -8px rgba(37, 99, 235, 0.25);
}
.ppms-team-tree-block__head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem 1rem;
    padding: 0.85rem 1rem;
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.07) 0%, rgba(255, 255, 255, 0) 55%);
    border-bottom: 1px solid var(--ppms-border, #f1f5f9);
}
.ppms-team-tree-block__title-row {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    min-width: 0;
    flex: 1;
}
.ppms-team-tree-block__folder {
    flex-shrink: 0;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ppms-accent, #2563eb);
    background: rgba(37, 99, 235, 0.12);
}
.ppms-team-tree-block__title-wrap {
    min-width: 0;
}
.ppms-team-tree-block__title {
    border: none;
    background: none;
    padding: 0;
    margin: 0;
    font: inherit;
    font-weight: 700;
    font-size: 1.08rem;
    line-height: 1.3;
    cursor: pointer;
    color: var(--ppms-text, #0f172a);
    text-align: left;
    text-decoration: none;
    border-radius: 6px;
    display: inline;
    box-decoration-break: clone;
    transition: color 0.15s ease;
}
.ppms-team-tree-block__title:hover {
    color: var(--ppms-accent, #2563eb);
    text-decoration: underline;
    text-underline-offset: 3px;
}
.ppms-team-tree-block__title:focus-visible {
    outline: 2px solid var(--ppms-accent, #2563eb);
    outline-offset: 3px;
}
.ppms-team-tree-block__snippet {
    margin: 0.35rem 0 0;
    font-size: 0.82rem;
    line-height: 1.4;
    color: var(--ppms-muted, #64748b);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.ppms-team-tree-block__pill {
    flex-shrink: 0;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.06);
    color: var(--ppms-muted, #475569);
    white-space: nowrap;
}
.ppms-team-tree-empty {
    padding: 1.75rem 1.15rem;
    text-align: center;
    color: var(--ppms-muted, #94a3b8);
    font-size: 0.9rem;
}
.ppms-team-tree-empty__icon {
    display: block;
    font-size: 1.5rem;
    margin-bottom: 0.35rem;
    opacity: 0.5;
}
.ppms-team-tree-empty p {
    margin: 0;
}
.ppms-team-tree {
    padding: 0 0.75rem 0.85rem 0.85rem;
}
.ppms-team-tree__structure {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.65rem;
    align-items: start;
    padding-top: 0.2rem;
}
@media (min-width: 640px) {
    .ppms-team-tree__structure {
        grid-template-columns: minmax(200px, 300px) minmax(0, 1fr);
        gap: 0.75rem 1rem;
    }
}
.ppms-team-tree__leaders {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.ppms-team-tree-node {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    min-width: 0;
}
.ppms-team-tree-node--leader {
    padding: 0.75rem 0.85rem;
    border-radius: 12px;
    background: linear-gradient(180deg, rgba(37, 99, 235, 0.08) 0%, rgba(37, 99, 235, 0.02) 100%);
    border: 1px solid var(--ppms-tree-line-soft);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
}
.ppms-team-tree-node--member {
    padding: 0.55rem 0.65rem;
    border-radius: 10px;
    background: rgba(248, 250, 252, 0.9);
    border: 1px solid var(--ppms-border, #e2e8f0);
    flex: 1;
    min-width: 0;
    height: 100%;
}
.ppms-team-tree-node__avatar {
    flex-shrink: 0;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    color: #475569;
    background: linear-gradient(145deg, #f1f5f9, #e2e8f0);
    border: 1px solid rgba(148, 163, 184, 0.35);
}
.ppms-team-tree-node__avatar--leader {
    color: #fff;
    background: linear-gradient(145deg, #3b82f6, #2563eb);
    border-color: rgba(37, 99, 235, 0.5);
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.35);
}
.ppms-team-tree-node__body {
    min-width: 0;
    flex: 1;
}
.ppms-team-tree-node__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem 0.6rem;
}
.ppms-team-tree-node__badge {
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--ppms-accent, #2563eb);
    background: rgba(37, 99, 235, 0.12);
    padding: 0.2rem 0.45rem;
    border-radius: 6px;
    line-height: 1.2;
}
.ppms-team-tree-node__badge--member {
    color: #64748b;
    background: rgba(100, 116, 139, 0.12);
}
.ppms-team-tree-node__name {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--ppms-text, #0f172a);
    word-break: break-word;
}
.ppms-team-tree-node__meta {
    margin: 0.35rem 0 0;
    font-size: 0.8rem;
    line-height: 1.35;
    color: var(--ppms-muted, #64748b);
}
.ppms-team-tree__branch-wrap {
    margin-top: 0;
    padding-left: 0;
    min-width: 0;
}
.ppms-team-tree__branch-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #94a3b8;
}
.ppms-team-tree__branch-line {
    flex: 1;
    max-width: 2rem;
    height: 2px;
    border-radius: 2px;
    background: linear-gradient(90deg, var(--ppms-tree-line), transparent);
}
.ppms-team-tree__members {
    list-style: none;
    margin: 0.35rem 0 0;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.55rem;
    border-left: none;
}
.ppms-team-tree__li {
    position: relative;
    padding: 0;
}
.ppms-team-tree__li::before {
    display: none;
}
@media (max-width: 520px) {
    .ppms-team-tree-block__head {
        flex-direction: column;
        align-items: stretch;
    }
    .ppms-team-tree-block__pill {
        align-self: flex-start;
    }
}
@media (prefers-reduced-motion: reduce) {
    .ppms-team-tree-block {
        transition: none;
    }
}
.ppms-team-perm-cell {
    font-size: 0.85rem;
    max-width: 220px;
}
.ppms-team-hint {
    font-size: 0.875rem;
}
.ppms-team-perm-fieldset {
    border: none;
    padding: 0;
    margin: 0;
}
.ppms-team-perm-row {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
    cursor: pointer;
}
.ppms-team-perm-row input {
    margin-top: 0.2rem;
}
.ppms-link-btn {
    background: none;
    border: none;
    padding: 0;
    color: inherit;
    text-decoration: underline;
    cursor: pointer;
    font: inherit;
    text-align: left;
}
.ppms-td-actions {
    white-space: nowrap;
}
.ppms-btn-ghost {
    background: transparent;
    border: 1px solid var(--ppms-border, #ccc);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    cursor: pointer;
    font: inherit;
    margin-right: 0.35rem;
}
.ppms-lookup-list {
    list-style: none;
    margin: 0;
    padding: 0;
    border: 1px solid var(--ppms-border, #ddd);
    border-radius: 6px;
    max-height: 200px;
    overflow: auto;
}
.ppms-lookup-list li {
    border-bottom: 1px solid var(--ppms-border, #eee);
}
.ppms-lookup-list li:last-child {
    border-bottom: none;
}
.ppms-lookup-list .ppms-link-btn {
    display: block;
    width: 100%;
    padding: 0.5rem 0.75rem;
    text-decoration: none;
}
.ppms-lookup-list .ppms-link-btn:hover {
    background: rgba(0, 0, 0, 0.04);
}
.ppms-teams-detail-head .ppms-h3 {
    margin-bottom: 0;
}

/* Modal tạo nhóm */
.ppms-team-create-modal {
    position: relative;
    max-width: 480px;
    width: 100%;
    border-radius: 14px;
    box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.08),
        0 12px 28px -8px rgba(0, 0, 0, 0.18);
    padding: 1.5rem 1.5rem 1.25rem;
    background: linear-gradient(180deg, rgba(37, 99, 235, 0.06) 0%, transparent 42%), var(--ppms-surface, #fff);
    border: 1px solid var(--ppms-border, #e5e7eb);
}
.ppms-team-create-close {
    position: absolute;
    top: 0.65rem;
    right: 0.65rem;
    width: 2.25rem;
    height: 2.25rem;
    border: none;
    border-radius: 8px;
    background: transparent;
    font-size: 1.5rem;
    line-height: 1;
    cursor: pointer;
    color: var(--ppms-muted, #6b7280);
    display: flex;
    align-items: center;
    justify-content: center;
}
.ppms-team-create-close:hover {
    background: rgba(0, 0, 0, 0.06);
    color: inherit;
}
.ppms-team-create-hero {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    padding-right: 2rem;
    margin-bottom: 1.25rem;
}
.ppms-team-create-icon {
    flex-shrink: 0;
    width: 3rem;
    height: 3rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ppms-accent, #2563eb);
    background: rgba(37, 99, 235, 0.12);
}
.ppms-team-create-title {
    margin: 0 0 0.35rem;
    font-size: 1.25rem;
    line-height: 1.25;
}
.ppms-team-create-sub {
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.45;
    color: var(--ppms-muted, #6b7280);
}
.ppms-team-create-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.ppms-team-create-fields {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.ppms-team-create-label {
    font-weight: 600;
    font-size: 0.9rem;
}
.ppms-team-create-req {
    color: var(--ppms-danger, #b91c1c);
    text-decoration: none;
    font-weight: 700;
    cursor: help;
}
.ppms-team-create-field input,
.ppms-team-create-field textarea {
    border-radius: 8px;
    border: 1px solid var(--ppms-border, #d1d5db);
    padding: 0.55rem 0.75rem;
    font: inherit;
    width: 100%;
    box-sizing: border-box;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}
.ppms-team-create-field textarea {
    resize: vertical;
    min-height: 5.5rem;
    line-height: 1.45;
}
.ppms-team-create-field input:focus,
.ppms-team-create-field textarea:focus {
    outline: none;
    border-color: var(--ppms-accent, #2563eb);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
}
.ppms-team-create-field input:disabled,
.ppms-team-create-field textarea:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}
.ppms-team-create-alert {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
    padding: 0.75rem 0.85rem;
    border-radius: 10px;
    border: 1px solid rgba(185, 28, 28, 0.35);
    background: rgba(254, 242, 242, 0.95);
    color: #7f1d1d;
}
.ppms-team-create-alert-icon {
    flex-shrink: 0;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 50%;
    background: rgba(185, 28, 28, 0.15);
    color: #b91c1c;
    font-weight: 800;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}
.ppms-team-create-alert-body {
    min-width: 0;
    flex: 1;
}
.ppms-team-create-alert-lead {
    margin: 0 0 0.25rem;
    font-size: 0.9rem;
}
.ppms-team-create-alert-msg {
    margin: 0;
    font-size: 0.875rem;
    line-height: 1.45;
}
.ppms-team-create-actions {
    margin-top: 0.25rem;
    padding-top: 0.25rem;
    border-top: 1px solid var(--ppms-border, #eee);
}
.ppms-team-create-spinner {
    display: inline-block;
    width: 0.95em;
    height: 0.95em;
    margin-right: 0.4rem;
    vertical-align: -0.12em;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: ppms-team-create-spin 0.7s linear infinite;
}
@keyframes ppms-team-create-spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
