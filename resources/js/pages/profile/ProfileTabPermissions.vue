<template>
    <div>
        <div v-if="loading" class="ppms-profile-skel" role="status">
            <div class="ppms-profile-skel-line" style="width: 55%" />
            <div class="ppms-profile-skel-line" style="width: 70%" />
        </div>
        <template v-else-if="err">{{ err }}</template>
        <template v-else>
            <p style="font-size: 0.9rem; color: var(--ppms-pf-muted); margin-top: 0">{{ t('profile.permissionsMatrix') }}</p>
            <p v-if="!canManage" style="font-size: 0.85rem">{{ t('profile.canManageHint') }}</p>

            <p style="margin: 0.75rem 0">
                <input
                    v-model="search"
                    type="search"
                    :placeholder="t('profile.searchPermission')"
                    style="width: 100%; max-width: 320px; padding: 0.45rem 0.5rem"
                />
            </p>

            <div class="ppms-profile-table-wrap">
                <table class="ppms-profile-table ppms-profile-matrix">
                    <thead>
                        <tr>
                            <th class="ppms-profile-matrix-corner">{{ t('common.search') }}</th>
                            <th v-for="a in actions" :key="a">{{ a }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="mod in filteredModules" :key="mod.key">
                            <th scope="row">{{ mod.label }}</th>
                            <td v-for="a in actions" :key="mod.key + a" style="text-align: center">
                                <input
                                    type="checkbox"
                                    :checked="desired[keyFor(mod.key, a)]"
                                    :disabled="!canManage"
                                    @change="toggle(mod.key, a, $event.target.checked)"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="canManage" style="margin-top: 1rem">
                <button type="button" class="ppms-pf-btn ppms-pf-btn--primary" :disabled="saving" @click="save">
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
import { ppmsToastSuccess } from '../../ppmsUi';

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
        ppmsToastSuccess('OK');
        const { data } = await axios.get('/api/me/rbac');
        effective.value = data.effective || {};
        desired.value = { ...data.effective };
    } catch (e) {
        window.alert(formatApiUserMessage(e, 'Error'));
    } finally {
        saving.value = false;
    }
}
</script>
