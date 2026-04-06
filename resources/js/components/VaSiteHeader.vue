<template>
    <header class="va-site-header" role="banner">
        <div class="va-site-shell va-site-header-inner">
            <router-link to="/" class="va-site-header-brand">
                <VaFooterWordmark variant="header" alt="Vietnam America Schools" />
                <div class="va-site-header-brand-text">
                    <span class="va-site-header-school">Vietnam America Schools</span>
                    <span class="va-site-header-product">Hệ thống PPMS</span>
                    <span class="va-site-header-tagline">Quản lý dự án &amp; hiệu suất</span>
                </div>
            </router-link>
        </div>
    </header>
</template>

<script setup>
import VaFooterWordmark from './VaFooterWordmark.vue';
</script>
