<template>
    <router-view />
    <PpmsConfirmHost />
    <PpmsToastHost />
</template>

<script setup>
import PpmsConfirmHost from './components/PpmsConfirmHost.vue';
import PpmsToastHost from './components/PpmsToastHost.vue';
</script>
