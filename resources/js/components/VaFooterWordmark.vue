<template>
    <span class="ppms-brand-mark" :class="markClass">
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
import { computed, ref } from 'vue';

const PRIMARY_SRC = '/images/logo/logo-footer.png';
const FALLBACK_SRC = '/images/default/logo-default.png';

const props = defineProps({
    alt: {
        type: String,
        default: 'Vietnam America Schools',
    },
    /** header: nhỏ gọn cho thanh đỉnh; footer: chip chuẩn */
    variant: {
        type: String,
        default: 'footer',
        validator: (v) => ['footer', 'header'].includes(v),
    },
});

const markClass = computed(() =>
    props.variant === 'header' ? 'ppms-brand-mark--header' : 'ppms-brand-mark--footer',
);

const src = ref(PRIMARY_SRC);

function onImgError() {
    if (src.value !== FALLBACK_SRC) {
        src.value = FALLBACK_SRC;
    }
}
</script>
