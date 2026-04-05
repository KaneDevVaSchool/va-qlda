<template>
    <span class="ppms-brand-mark ppms-brand-mark--footer">
        <img
            :src="src"
            :alt="alt"
            class="ppms-brand-mark-img"
            width="56"
            height="56"
            decoding="async"
            loading="lazy"
            @error="onImgError"
        />
    </span>
</template>

<script setup>
import { ref } from 'vue';

const PRIMARY_SRC = '/images/logo/logo-footer.png';
const FALLBACK_SRC = '/images/default/logo-default.png';

defineProps({
    alt: {
        type: String,
        default: 'Vietnam America Schools',
    },
});

const src = ref(PRIMARY_SRC);

function onImgError() {
    if (src.value !== FALLBACK_SRC) {
        src.value = FALLBACK_SRC;
    }
}
</script>
