<template>
    <header class="va-site-header" role="banner">
        <div class="va-site-header-inner">
            <router-link to="/" class="va-site-header-brand">
                <span class="va-site-header-title">PPMS</span>
                <span class="va-site-header-sub">VA Schools</span>
            </router-link>
        </div>
    </header>
</template>

<style scoped>
.va-site-header {
    position: sticky;
    top: 0;
    z-index: 200;
    border-bottom: 1px solid rgba(154, 0, 54, 0.1);
    background: rgba(255, 255, 255, 0.78);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.85) inset,
        0 4px 24px rgba(26, 29, 38, 0.06);
}

.va-site-header-inner {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-start;
    gap: 0.75rem 1.25rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0.65rem max(1rem, env(safe-area-inset-left)) 0.65rem max(1rem, env(safe-area-inset-right));
    padding-top: max(0.65rem, env(safe-area-inset-top));
}

.va-site-header-brand {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    text-decoration: none;
    color: inherit;
    min-width: 0;
}

.va-site-header-brand:focus-visible {
    outline: none;
    border-radius: 10px;
    box-shadow: 0 0 0 2px #fff, 0 0 0 4px var(--ppms-primary, #9a0036);
}

.va-site-header-title {
    font-size: 1.2rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    color: var(--ppms-primary, #9a0036);
    line-height: 1;
}

.va-site-header-sub {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--ppms-muted, #5c6370);
    align-self: flex-end;
    margin-bottom: 0.1rem;
    margin-left: -0.15rem;
}

</style>
