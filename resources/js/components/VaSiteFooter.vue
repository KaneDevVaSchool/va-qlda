<template>
    <footer class="va-site-footer" role="contentinfo">
        <div class="va-site-shell">
            <div class="va-site-footer-top">
                <div class="va-site-footer-brand">
                    <div class="va-site-footer-brand-icons">
                        <VaFooterWordmark :alt="t('layout.footerBrandSchool')" />
                        <span class="va-site-footer-mascot-wrap" aria-hidden="true">
                            <VaMascotImg img-class="va-site-footer-mascot" alt="" />
                        </span>
                    </div>
                    <div class="va-site-footer-brand-text">
                        <span class="va-site-footer-school">{{ t('layout.footerBrandSchool') }}</span>
                        <span class="va-site-footer-product">{{ t('layout.footerProduct') }}</span>
                        <span class="va-site-footer-tagline">{{ t('layout.footerTagline') }}</span>
                    </div>
                </div>

                <template v-if="showNav">
                    <nav class="va-site-footer-modules" :aria-label="t('layout.footerModuleNav')">
                        <div class="va-site-footer-module">
                            <div class="va-site-footer-module-title">{{ t('layout.footerColOverview') }}</div>
                            <div class="va-site-footer-module-links">
                                <router-link to="/">{{ t('layout.navDashboard') }}</router-link>
                            </div>
                        </div>
                        <div class="va-site-footer-module">
                            <div class="va-site-footer-module-title">{{ t('layout.footerColOps') }}</div>
                            <div class="va-site-footer-module-links">
                                <router-link to="/projects">{{ t('layout.navProjects') }}</router-link>
                                <router-link to="/kpi">{{ t('layout.navKpi') }}</router-link>
                                <router-link to="/teams">{{ t('layout.navTeams') }}</router-link>
                                <router-link to="/reports">{{ t('layout.navReports') }}</router-link>
                            </div>
                        </div>
                        <div class="va-site-footer-module">
                            <div class="va-site-footer-module-title">{{ t('layout.footerColSupplier') }}</div>
                            <div class="va-site-footer-module-links">
                                <router-link to="/vendors">{{ t('layout.navVendors') }}</router-link>
                                <router-link to="/contracts">{{ t('layout.navContracts') }}</router-link>
                            </div>
                        </div>
                        <div class="va-site-footer-module">
                            <div class="va-site-footer-module-title">{{ t('layout.footerColInnovation') }}</div>
                            <div class="va-site-footer-module-links">
                                <router-link to="/kaizens">{{ t('layout.navKaizen') }}</router-link>
                                <router-link to="/innovation">{{ t('layout.navInnovation') }}</router-link>
                            </div>
                        </div>
                        <div class="va-site-footer-module">
                            <div class="va-site-footer-module-title">{{ t('layout.footerColPersonal') }}</div>
                            <div class="va-site-footer-module-links">
                                <router-link to="/evaluations">{{ t('layout.navEvaluations') }}</router-link>
                                <router-link to="/notifications">{{ t('layout.navNotifications') }}</router-link>
                                <router-link to="/profile">{{ t('layout.navAccount') }}</router-link>
                            </div>
                        </div>
                    </nav>
                </template>
                <nav v-else class="va-site-footer-nav va-site-footer-nav--compact" :aria-label="t('layout.footerResources')">
                    <a href="https://www.w3.org/WAI/WCAG21/quickref/" rel="noopener noreferrer" target="_blank">{{
                        t('layout.footerWcag')
                    }}</a>
                </nav>
            </div>

            <div v-if="showNav" class="va-site-footer-external">
                <a href="https://www.w3.org/WAI/WCAG21/quickref/" rel="noopener noreferrer" target="_blank">{{
                    t('layout.footerWcag')
                }}</a>
            </div>

            <div class="va-site-footer-rule" aria-hidden="true" />
            <div class="va-site-footer-bottom">
                <p class="va-site-footer-copy">{{ t('layout.footerCopyright', { year }) }}</p>
                <p class="va-site-footer-meta">{{ t('layout.footerMeta') }}</p>
            </div>
        </div>
    </footer>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import VaFooterWordmark from './VaFooterWordmark.vue';
import VaMascotImg from './VaMascotImg.vue';

defineProps({
    showNav: {
        type: Boolean,
        default: true,
    },
});

const { t } = useI18n();
const year = computed(() => new Date().getFullYear());
</script>
