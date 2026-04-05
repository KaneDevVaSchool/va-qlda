<template>
    <footer class="ppms-site-footer" role="contentinfo">
        <div class="ppms-site-footer-inner">
            <div class="ppms-site-footer-top">
                <div class="ppms-site-footer-brand">
                    <div class="ppms-site-footer-brand-icons">
                        <VaFooterWordmark alt="Vietnam America Schools" />
                        <span class="ppms-footer-mascot-wrap" aria-hidden="true">
                            <VaMascotImg img-class="ppms-footer-mascot" alt="" />
                        </span>
                    </div>
                    <div class="ppms-site-footer-brand-text">
                        <span class="ppms-site-footer-school">Vietnam America Schools</span>
                        <span class="ppms-site-footer-product">Hệ thống PPMS</span>
                        <span class="ppms-site-footer-tagline">Quản lý dự án &amp; hiệu suất</span>
                    </div>
                </div>
                <nav v-if="showNav" class="ppms-site-footer-nav" aria-label="Liên kết nhanh">
                    <router-link to="/">Dashboard</router-link>
                    <router-link to="/projects">Dự án</router-link>
                    <router-link to="/kpi">KPI</router-link>
                    <router-link to="/reports">Báo cáo</router-link>
                    <router-link to="/notifications">Thông báo</router-link>
                    <a href="https://www.w3.org/WAI/WCAG21/quickref/" rel="noopener noreferrer" target="_blank"
                        >WCAG 2.1</a
                    >
                </nav>
                <nav v-else class="ppms-site-footer-nav ppms-site-footer-nav--compact" aria-label="Tài nguyên">
                    <a href="https://www.w3.org/WAI/WCAG21/quickref/" rel="noopener noreferrer" target="_blank"
                        >Hướng tới WCAG 2.1</a
                    >
                </nav>
            </div>
            <div class="ppms-site-footer-rule" aria-hidden="true" />
            <div class="ppms-site-footer-bottom">
                <p class="ppms-site-footer-copy">© {{ year }} Vietnam America Schools. Bảo lưu mọi quyền.</p>
                <p class="ppms-site-footer-note">Phiên bản vận hành &amp; kiểm thử nội bộ.</p>
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
