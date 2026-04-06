<template>
    <footer class="va-site-footer" role="contentinfo">
        <div class="va-site-shell">
            <div class="va-site-footer-top">
                <div class="va-site-footer-brand">
                    <div class="va-site-footer-brand-icons">
                        <VaFooterWordmark alt="Vietnam America Schools" />
                        <span class="va-site-footer-mascot-wrap" aria-hidden="true">
                            <VaMascotImg img-class="va-site-footer-mascot" alt="" />
                        </span>
                    </div>
                    <div class="va-site-footer-brand-text">
                        <span class="va-site-footer-school">Vietnam America Schools</span>
                        <span class="va-site-footer-product">Hệ thống PPMS</span>
                        <span class="va-site-footer-tagline">Quản lý dự án &amp; hiệu suất</span>
                    </div>
                </div>
                <nav v-if="showNav" class="va-site-footer-nav" aria-label="Liên kết nhanh">
                    <router-link to="/">Dashboard</router-link>
                    <router-link to="/projects">Dự án</router-link>
                    <router-link to="/kpi">KPI</router-link>
                    <router-link to="/reports">Báo cáo</router-link>
                    <router-link to="/notifications">Thông báo</router-link>
                    <a href="https://www.w3.org/WAI/WCAG21/quickref/" rel="noopener noreferrer" target="_blank"
                        >WCAG 2.1</a
                    >
                </nav>
                <nav v-else class="va-site-footer-nav va-site-footer-nav--compact" aria-label="Tài nguyên">
                    <a href="https://www.w3.org/WAI/WCAG21/quickref/" rel="noopener noreferrer" target="_blank"
                        >Hướng tới WCAG 2.1</a
                    >
                </nav>
            </div>
            <div class="va-site-footer-rule" aria-hidden="true" />
            <div class="va-site-footer-bottom">
                <p class="va-site-footer-copy">© {{ year }} Vietnam America Schools</p>
                <p class="va-site-footer-meta">Hệ thống PPMS · Quản lý dự án &amp; hiệu suất</p>
            </div>
        </div>
    </footer>
</template>

<script setup>
import VaFooterWordmark from './VaFooterWordmark.vue';
import VaMascotImg from './VaMascotImg.vue';

defineProps({
    /** Hiển thị menu nội bộ (sau đăng nhập). Trang đăng nhập dùng false. */
    showNav: {
        type: Boolean,
        default: true,
    },
});

const year = new Date().getFullYear();
</script>
