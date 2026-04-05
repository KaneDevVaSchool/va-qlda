<template>
    <img
        :src="src"
        :alt="alt"
        :class="['ppms-mascot-img', imgClass]"
        :width="width"
        :height="height"
        decoding="async"
        loading="lazy"
        @error="onImgError"
    />
</template>

<script setup>
import { ref } from 'vue';

const PRIMARY_SRC = '/images/logo/logo-mascos-vaytay.png';
const FALLBACK_SRC = '/images/default/logo-default.png';

defineProps({
    alt: {
        type: String,
        default: 'Mascot Vietnam America Schools',
    },
    /** Thêm class kích thước: ppms-mascot--login, ppms-app-header-mascot */
    imgClass: {
        type: String,
        default: '',
    },
    width: {
        type: [String, Number],
        default: undefined,
    },
    height: {
        type: [String, Number],
        default: undefined,
    },
});

const src = ref(PRIMARY_SRC);

function onImgError() {
    if (src.value !== FALLBACK_SRC) {
        src.value = FALLBACK_SRC;
    }
}
</script>
