<template>
    <header class="ppms-pd-head">
        <div class="ppms-pd-head-row ppms-pd-head-row--main">
            <div class="ppms-pd-head-left">
                <router-link to="/projects" class="ppms-back">{{ t('projects.listBack') }}</router-link>
                <div class="ppms-pd-head-title">
                    <h1>{{ project.name }}</h1>
                    <div v-if="projectLabelList(project).length" class="ppms-pl-detail-name-labels">
                        <span
                            v-for="(plb, pli) in projectLabelList(project)"
                            :key="'phl-' + pli"
                            class="ppms-pl-label-chip ppms-pl-label-chip--header"
                            >{{ plb }}</span
                        >
                    </div>
                </div>
            </div>
            <div class="ppms-pd-head-actions">
                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="$emit('open-meta')">
                    {{ t('projects.pdActionStatus') }}
                </button>
                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="$emit('join')">
                    {{ t('projects.pdActionJoin') }}
                </button>
                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="$emit('go-tasks')">
                    {{ t('projects.pdActionTaskList') }}
                </button>
                <details ref="addTaskDetailsRef" class="ppms-pd-add-task-dd">
                    <summary class="ppms-btn-primary ppms-btn-sm">{{ t('projects.pdActionAddTask') }}</summary>
                    <div class="ppms-pd-add-task-menu" role="menu">
                        <button type="button" role="menuitem" @click="$emit('menu-add-regular')">
                            {{ t('projects.pdAddMenuRegular') }}
                        </button>
                        <button type="button" role="menuitem" @click="$emit('menu-add-bulk')">
                            {{ t('projects.pdAddMenuBulk') }}
                        </button>
                        <button type="button" role="menuitem" @click="$emit('menu-add-category')">
                            {{ t('projects.pdAddMenuCategory') }}
                        </button>
                        <button type="button" role="menuitem" @click="$emit('menu-add-process')">
                            {{ t('projects.pdAddMenuProcess') }}
                        </button>
                        <button type="button" role="menuitem" @click="$emit('menu-add-phase')">
                            {{ t('projects.pdAddMenuPhase') }}
                        </button>
                    </div>
                </details>
                <button
                    v-if="canManageProject"
                    type="button"
                    class="ppms-btn-ghost ppms-btn-sm"
                    @click="$emit('open-phase-modal')"
                >
                    {{ t('projects.pdAddPhase') }}
                </button>
                <details class="ppms-pd-more-dd">
                    <summary class="ppms-btn-ghost ppms-btn-sm ppms-pd-more-dd__summary" :title="t('projects.pdActionMore')">
                        <span class="ppms-pd-more-dd__caret" aria-hidden="true">▾</span>
                    </summary>
                    <div class="ppms-pd-more-dd-menu" role="menu">
                        <button type="button" role="menuitem" @click="$emit('duplicate')">
                            {{ t('projects.duplicate') }}
                        </button>
                    </div>
                </details>
            </div>
        </div>
        <nav class="ppms-pd-tabs" role="tablist" :aria-label="t('projects.detailTitle')">
            <button
                v-for="tab in detailTabs"
                :key="tab.id"
                type="button"
                role="tab"
                class="ppms-pd-tab"
                :class="{ 'is-active': activeTab === tab.id }"
                :aria-selected="activeTab === tab.id"
                :tabindex="activeTab === tab.id ? 0 : -1"
                @click="activeTab = tab.id"
            >
                {{ tab.label }}
                <span v-if="tab.badge != null" class="ppms-pd-tab-badge">{{ tab.badge }}</span>
            </button>
        </nav>
    </header>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { projectLabelList } from '../../utils/projectLabels';

const { t } = useI18n();

defineProps({
    project: { type: Object, required: true },
    detailTabs: { type: Array, required: true },
    canManageProject: { type: Boolean, default: false },
});

const activeTab = defineModel('activeTab', { type: String, required: true });

defineEmits([
    'open-meta',
    'join',
    'go-tasks',
    'menu-add-regular',
    'menu-add-bulk',
    'menu-add-category',
    'menu-add-process',
    'menu-add-phase',
    'open-phase-modal',
    'duplicate',
]);

const addTaskDetailsRef = ref(null);

function closeAddTaskMenu() {
    const el = addTaskDetailsRef.value;
    if (el) {
        el.open = false;
    }
}

defineExpose({ closeAddTaskMenu });
</script>
