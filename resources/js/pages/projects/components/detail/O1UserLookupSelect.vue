<!-- Chọn một người: danh sách nhóm dự án + tìm /api/users/lookup để thêm người khác -->
<template>
    <div class="o1-user-lookup-select">
        <div v-if="selectedId && displayUser" class="o1-user-lookup-select__picked">
            <span class="o1-user-lookup-select__picked-txt">{{ labelFor(displayUser) }}</span>
            <button type="button" class="o1-user-lookup-select__clear" :aria-label="clearAria" @click="clear">×</button>
        </div>
        <input
            v-model="query"
            type="search"
            class="o1tf-input-el o1-user-lookup-select__search"
            :placeholder="searchPlaceholder"
            autocomplete="off"
            :aria-label="searchAria"
            @input="scheduleLookup"
        />
        <p v-if="showMinHint" class="o1tf-field-hint">{{ minHint }}</p>
        <p v-else-if="showLoading" class="o1tf-field-hint o1-user-lookup-select__muted">{{ loadingText }}</p>
        <p v-else-if="showEmpty" class="o1-user-picker__empty">{{ emptyText }}</p>
        <ul v-else-if="dropdownList.length" class="o1-user-lookup-select__hits" role="listbox">
            <li v-for="u in dropdownList" :key="'ulk-' + u.id" role="none">
                <button type="button" class="o1-user-lookup-select__hit" @click="pick(u)">
                    <span class="o1-user-lookup-select__name">{{ u.name || u.id }}</span>
                    <span v-if="u.email" class="o1-user-lookup-select__email">{{ u.email }}</span>
                </button>
            </li>
        </ul>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    baseUsers: { type: Array, default: () => [] },
    searchPlaceholder: { type: String, required: true },
    searchAria: { type: String, required: true },
    minHint: { type: String, required: true },
    emptyText: { type: String, required: true },
    loadingText: { type: String, required: true },
    clearAria: { type: String, required: true },
    minChars: { type: Number, default: 1 },
});

const emit = defineEmits(['update:modelValue']);

const query = ref('');
const hits = ref([]);
const pending = ref(false);
const cache = ref({});
const DEBOUNCE_MS = 300;
let timer = null;

function norm(s) {
    return String(s || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '');
}

function labelFor(u) {
    if (!u) {
        return '';
    }
    const mail = u.email ? ` — ${u.email}` : '';

    return `${u.name || u.id}${mail}`;
}

watch(
    () => props.baseUsers,
    (list) => {
        for (const u of list || []) {
            if (u && u.id != null) {
                cache.value[String(u.id)] = { id: u.id, name: u.name, email: u.email };
            }
        }
    },
    { immediate: true, deep: true },
);

const selectedId = computed(() => String(props.modelValue || '').trim());

const displayUser = computed(() => {
    if (!selectedId.value) {
        return null;
    }

    return cache.value[selectedId.value] || null;
});

watch(
    () => props.modelValue,
    async (id) => {
        const sid = String(id || '').trim();
        if (!sid) {
            return;
        }
        if (cache.value[sid]) {
            return;
        }
        const nid = Number(sid);
        if (!Number.isFinite(nid) || nid <= 0) {
            return;
        }
        try {
            const { data } = await axios.get('/api/users/lookup', { params: { ids: [nid] } });
            const arr = Array.isArray(data) ? data : [];
            if (arr[0]) {
                const u = arr[0];
                cache.value[String(u.id)] = { id: u.id, name: u.name, email: u.email };
            }
        } catch {
            /* ignore */
        }
    },
    { immediate: true },
);

const dropdownList = computed(() => {
    const q = norm(query.value.trim());
    const base = [...(props.baseUsers || [])];

    if (q.length < props.minChars) {
        return base.sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'vi'));
    }

    const m = new Map();
    for (const u of base) {
        const hay = norm(`${u.name || ''} ${u.email || ''}`);
        if (hay.includes(q)) {
            m.set(u.id, u);
        }
    }
    for (const u of hits.value) {
        m.set(u.id, u);
    }

    return [...m.values()].sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'vi'));
});

const showMinHint = computed(() => {
    const q = query.value.trim();

    return q.length > 0 && q.length < props.minChars;
});

const showLoading = computed(() => pending.value && query.value.trim().length >= props.minChars);

const showEmpty = computed(() => {
    const q = query.value.trim();

    return q.length >= props.minChars && !pending.value && dropdownList.value.length === 0;
});

function scheduleLookup() {
    clearTimeout(timer);
    const q = query.value.trim();
    if (q.length < props.minChars) {
        hits.value = [];
        pending.value = false;

        return;
    }
    timer = setTimeout(async () => {
        pending.value = true;
        try {
            const { data } = await axios.get('/api/users/lookup', { params: { q } });
            hits.value = Array.isArray(data) ? data : [];
            for (const u of hits.value) {
                cache.value[String(u.id)] = { id: u.id, name: u.name, email: u.email };
            }
        } catch {
            hits.value = [];
        } finally {
            pending.value = false;
        }
    }, DEBOUNCE_MS);
}

function pick(u) {
    if (!u || u.id == null) {
        return;
    }
    cache.value[String(u.id)] = { id: u.id, name: u.name, email: u.email };
    emit('update:modelValue', String(u.id));
    query.value = '';
    hits.value = [];
}

function clear() {
    emit('update:modelValue', '');
    query.value = '';
    hits.value = [];
}
</script>
