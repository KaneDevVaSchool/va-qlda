<template>
    <div class="ppms-pd-media-tree-children" :class="{ 'ppms-pd-media-tree-children--nested': depth > 0 }">
        <p v-if="!sortedChildren.length" class="ppms-pd-media-tree-empty ppms-muted">{{ t('projects.pdMediaTreeEmptyFolder') }}</p>
        <div
            v-for="c in sortedChildren"
            :key="'tc-' + c.source_id"
            class="ppms-pd-media-tree-child-block"
        >
            <div
                class="ppms-pd-media-tree-child-row"
                :style="{ paddingLeft: depth * 0.65 + 'rem' }"
            >
                <button
                    v-if="c.doc_type === 'folder'"
                    type="button"
                    class="ppms-pd-media-tree-child-folder"
                    :aria-expanded="isExpanded(c.source_id)"
                    @click="toggleFolder(c.source_id)"
                >
                    <span class="ppms-pd-media-tree-chev" aria-hidden="true">{{ isExpanded(c.source_id) ? '▼' : '▶' }}</span>
                    <span class="ppms-pd-attach-folder-ico ppms-pd-attach-folder-ico--sm" aria-hidden="true" />
                    <span class="ppms-pd-media-tree-child-name">{{ c.name }}</span>
                </button>
                <button
                    v-else-if="c.doc_type === 'link' && c.url"
                    type="button"
                    class="ppms-pd-media-tree-child-file ppms-linklike"
                    @click="$emit('open-project-doc', c)"
                >
                    {{ c.name }}
                </button>
                <button
                    v-else-if="c.doc_type === 'upload'"
                    type="button"
                    class="ppms-pd-media-tree-child-file ppms-linklike"
                    @click="$emit('download-media-row', c)"
                >
                    {{ c.original_name || c.name }}
                </button>
                <span v-else class="ppms-pd-media-tree-child-file ppms-muted">{{ c.name }}</span>
            </div>
            <ProjectMediaTreeChildren
                v-if="c.doc_type === 'folder' && isExpanded(c.source_id)"
                :folder-id="Number(c.source_id)"
                :depth="depth + 1"
                @download-media-row="$emit('download-media-row', $event)"
                @open-project-doc="$emit('open-project-doc', $event)"
            />
        </div>
    </div>
</template>

<script setup>
import { computed, inject } from 'vue';
import { useI18n } from 'vue-i18n';
import ProjectMediaTreeChildren from './ProjectMediaTreeChildren.vue';

const { t } = useI18n();

const props = defineProps({
    folderId: { type: Number, required: true },
    depth: { type: Number, default: 0 },
});

defineEmits(['download-media-row', 'open-project-doc']);

const projectMedia = inject('projectMedia');
const treeExpandedFolderIds = inject('treeExpandedFolderIds');

const sortedChildren = computed(() => {
    const all = projectMedia.value || [];
    const list = Array.isArray(all) ? all : [];
    return list
        .filter((m) => m.source === 'project_document' && Number(m.parent_id) === Number(props.folderId))
        .slice()
        .sort((a, b) => {
            const ord = (t) => (t === 'folder' ? 0 : 1);
            const c = ord(a.doc_type) - ord(b.doc_type);
            if (c !== 0) {
                return c;
            }
            return String(a.name || '').localeCompare(String(b.name || ''), undefined, { sensitivity: 'base' });
        });
});

function isExpanded(id) {
    return !!treeExpandedFolderIds[Number(id)];
}

function toggleFolder(id) {
    const k = Number(id);
    treeExpandedFolderIds[k] = !treeExpandedFolderIds[k];
}
</script>
