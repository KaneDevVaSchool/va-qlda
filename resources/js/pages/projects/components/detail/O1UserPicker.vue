<template>
    <div class="o1-user-picker">
        <input
            v-model="search"
            type="search"
            class="o1tf-input-el o1-user-picker__search"
            :placeholder="searchPlaceholder"
            autocomplete="off"
            :aria-label="searchAria"
            aria-autocomplete="list"
            :aria-controls="listboxId"
            :aria-activedescendant="activeOptionId"
            @keydown.escape="search = ''"
            @keydown.down.prevent="moveActive(1)"
            @keydown.up.prevent="moveActive(-1)"
            @keydown.enter.prevent="toggleActive"
            @keydown.space.prevent="toggleActive"
        />
        <p
            v-if="remoteLookup && lookupPending && search.trim().length >= lookupMinChars"
            class="o1-user-picker__pending"
            role="status"
        >
            {{ remoteLoadingText }}
        </p>
        <div
            :id="listboxId"
            class="o1-user-picker__list"
            role="listbox"
            :aria-label="listAria"
            :aria-multiselectable="multiple"
        >
            <button
                v-for="(u, idx) in filteredUsers"
                :id="optionId(idx)"
                :key="'up-' + u.id"
                type="button"
                class="o1-user-picker__opt"
                :class="{ 'is-on': isOn(u.id), 'is-active-kb': idx === activeIndex }"
                role="option"
                :aria-selected="isOn(u.id)"
                @click="toggle(u.id)"
                @mouseenter="activeIndex = idx"
            >
                <span class="o1-user-picker__name">{{ u.name || u.id }}</span>
                <span v-if="u.email" class="o1-user-picker__email">{{ u.email }}</span>
            </button>
            <p v-if="filteredUsers.length === 0" class="o1-user-picker__empty">{{ emptyText }}</p>
        </div>
        <div v-if="selectedChips.length" class="o1-user-picker__chips">
            <span v-for="su in selectedChips" :key="'ch-' + su.id" class="o1-user-chip">
                <span class="o1-user-chip__txt">{{ su.name || su.id }}</span>
                <button type="button" class="o1-user-chip__x" :aria-label="removeLabelFor(su)" @click="remove(su.id)">×</button>
            </span>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    /** { id, name?, email? } */
    users: { type: Array, required: true },
    multiple: { type: Boolean, default: true },
    searchPlaceholder: { type: String, required: true },
    searchAria: { type: String, required: true },
    listAria: { type: String, required: true },
    emptyText: { type: String, required: true },
    removeChipLabel: { type: Function, required: true },
    /** Gộp kết quả /api/users/lookup khi gõ tìm để thêm người ngoài nhóm dự án */
    remoteLookup: { type: Boolean, default: false },
    lookupMinChars: { type: Number, default: 1 },
    remoteLoadingText: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const search = ref('');
const activeIndex = ref(-1);
const listboxId = `o1-up-lb-${Math.random().toString(36).slice(2, 9)}`;
const remoteLookupHits = ref([]);
const lookupPending = ref(false);
const lookupCache = ref({});
let lookupTimer = null;

const idSet = computed(() => {
    const raw = props.modelValue || [];
    const s = new Set();

    for (const x of raw) {
        const n = Number(x);
        if (Number.isFinite(n) && n > 0) {
            s.add(n);
        }
    }

    return s;
});

function norm(s) {
    return String(s || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '');
}

const filteredUsers = computed(() => {
    const q = norm(search.value.trim());
    const baseSorted = [...(props.users || [])].sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'vi'));

    if (!q) {
        return baseSorted;
    }

    const m = new Map();
    for (const u of props.users || []) {
        m.set(Number(u.id), u);
    }
    if (props.remoteLookup && q.length >= props.lookupMinChars) {
        for (const u of remoteLookupHits.value) {
            m.set(Number(u.id), u);
        }
    }

    const list = [...m.values()].sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'vi'));

    return list.filter((u) => {
        const hay = `${u.name || ''} ${u.email || ''}`;

        return norm(hay).includes(q);
    });
});

watch(search, () => {
    if (!props.remoteLookup) {
        return;
    }
    clearTimeout(lookupTimer);
    const raw = search.value.trim();
    if (raw.length < props.lookupMinChars) {
        remoteLookupHits.value = [];
        lookupPending.value = false;

        return;
    }
    lookupTimer = setTimeout(async () => {
        lookupPending.value = true;
        try {
            const { data } = await axios.get('/api/users/lookup', { params: { q: raw } });
            remoteLookupHits.value = Array.isArray(data) ? data : [];
            for (const u of remoteLookupHits.value) {
                lookupCache.value[String(u.id)] = { id: u.id, name: u.name, email: u.email };
            }
        } catch {
            remoteLookupHits.value = [];
        } finally {
            lookupPending.value = false;
        }
    }, 300);
});

watch(filteredUsers, () => {
    activeIndex.value = filteredUsers.value.length ? 0 : -1;
});

watch(
    () => props.modelValue,
    async (ids) => {
        const need = [];
        const fromUsers = new Set((props.users || []).map((u) => Number(u?.id)));
        for (const x of ids || []) {
            const n = Number(x);
            if (!Number.isFinite(n) || n <= 0) {
                continue;
            }
            if (!fromUsers.has(n) && !lookupCache.value[String(n)]) {
                need.push(n);
            }
        }
        if (!need.length) {
            return;
        }
        try {
            const { data } = await axios.get('/api/users/lookup', { params: { ids: need } });
            const arr = Array.isArray(data) ? data : [];
            for (const u of arr) {
                lookupCache.value[String(u.id)] = { id: u.id, name: u.name, email: u.email };
            }
        } catch {
            /* ignore */
        }
    },
    { immediate: true, deep: true },
);

const userById = computed(() => {
    const m = new Map();
    for (const u of props.users || []) {
        m.set(Number(u.id), u);
    }
    for (const u of Object.values(lookupCache.value)) {
        const id = Number(u.id);
        if (!m.has(id)) {
            m.set(id, u);
        }
    }

    return m;
});

const selectedChips = computed(() => {
    const ids = [...idSet.value].sort((a, b) => a - b);

    return ids.map((id) => userById.value.get(id) || { id, name: String(id) });
});

function optionId(idx) {
    return `${listboxId}-opt-${idx}`;
}

const activeOptionId = computed(() => {
    if (activeIndex.value < 0 || activeIndex.value >= filteredUsers.value.length) {
        return undefined;
    }

    return optionId(activeIndex.value);
});

function isOn(id) {
    return idSet.value.has(Number(id));
}

function moveActive(delta) {
    const n = filteredUsers.value.length;
    if (!n) {
        return;
    }
    if (activeIndex.value < 0) {
        activeIndex.value = delta > 0 ? 0 : n - 1;

        return;
    }
    activeIndex.value = (activeIndex.value + delta + n) % n;
}

function toggleActive() {
    const n = filteredUsers.value.length;
    if (!n || activeIndex.value < 0 || activeIndex.value >= n) {
        return;
    }
    toggle(filteredUsers.value[activeIndex.value].id);
}

function toggle(id) {
    const n = Number(id);
    if (!Number.isFinite(n) || n <= 0) {
        return;
    }

    const u = filteredUsers.value.find((x) => Number(x.id) === n);
    if (u) {
        lookupCache.value[String(u.id)] = { id: u.id, name: u.name, email: u.email };
    }

    const next = new Set(idSet.value);
    if (props.multiple) {
        if (next.has(n)) {
            next.delete(n);
        } else {
            next.add(n);
        }
    } else {
        next.clear();
        if (!idSet.value.has(n)) {
            next.add(n);
        }
    }

    emit('update:modelValue', [...next]);
}

function remove(id) {
    const n = Number(id);
    const next = new Set(idSet.value);
    next.delete(n);
    emit('update:modelValue', [...next]);
}

function removeLabelFor(u) {
    return props.removeChipLabel(u);
}
</script>
