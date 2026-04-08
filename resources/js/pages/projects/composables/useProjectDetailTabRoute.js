import { nextTick, watch } from 'vue';
import { isValidProjectDetailTab } from '../constants/projectDetail';

/**
 * Đồng bộ `activeTab` với `route.query.tab` (chia sẻ link, F5 giữ tab).
 */
export function useProjectDetailTabRoute(activeTab, router, route) {
    function tabFromQuery(q) {
        const raw = q?.tab;
        if (typeof raw === 'string' && isValidProjectDetailTab(raw)) {
            return raw;
        }

        return 'info';
    }

    let syncingFromRoute = false;

    watch(
        () => route.query,
        () => {
            if (route.name !== 'project-detail') {
                return;
            }
            if (syncingFromRoute) {
                return;
            }
            const want = tabFromQuery(route.query);
            if (activeTab.value !== want) {
                activeTab.value = want;
            }
        },
        { immediate: true, deep: true },
    );

    watch(activeTab, (tab) => {
        if (route.name !== 'project-detail') {
            return;
        }
        const want = tabFromQuery(route.query);
        if (tab === want) {
            return;
        }
        const nextQuery = { ...route.query };
        if (tab === 'info') {
            delete nextQuery.tab;
        } else {
            nextQuery.tab = tab;
        }
        syncingFromRoute = true;
        router
            .replace({ name: 'project-detail', params: route.params, query: nextQuery })
            .catch(() => {})
            .finally(() => {
                nextTick(() => {
                    syncingFromRoute = false;
                });
            });
    });
}
