<template>
    <div class="ppms-page vm-detail vm-detail--surface">
        <div v-if="loading" class="ppms-loading-line" role="status">{{ t('common.loading') }}</div>
        <p v-else-if="err" class="ppms-error">{{ err }}</p>
        <template v-else-if="vendor">
            <header class="vm-detail__header">
                <h1 class="vm-detail__title">{{ vendor.name }}</h1>
                <div class="vm-detail__badges">
                    <template v-if="vendor.kind === 'research' && vendor.status === 'researching'">
                        <span class="vm-pill vm-pill--muted">{{ statusLabel(vendor.status) }}</span>
                    </template>
                    <template v-else>
                        <span class="vm-pill">{{ kindLabel(vendor.kind) }}</span>
                        <span class="vm-pill vm-pill--muted">{{ statusLabel(vendor.status) }}</span>
                    </template>
                    <span v-if="vendor.vendor_score" class="vm-pill vm-pill--score">{{ t('vendors.vendorScore') }}: {{ vendor.vendor_score }}</span>
                </div>
            </header>

            <div class="vm-detail__full">
            <section
                id="vm-panel-overview"
                class="ppms-card ppms-mt vm-panel vm-panel--full"
            >
                <h2 class="vm-sec-title vm-sec-title--main">{{ t('vendors.tabOverview') }}</h2>
                <div class="vm-overview-surface">
                    <div
                        v-if="vendor.pros && String(vendor.pros).trim()"
                        class="vm-overview-note vm-overview-note--inline"
                    >
                        <span class="vm-overview-note__inline-k">{{ t('vendors.overviewNoteLabel') }}:</span>
                        <span class="vm-overview-note__inline-t">{{ vendor.pros }}</span>
                    </div>
                    <div v-if="overviewFeaturesList.length || overviewServicesList.length" class="vm-overview-grid">
                        <article
                            v-if="overviewFeaturesList.length"
                            class="vm-overview-card vm-overview-card--features"
                            :aria-label="t('vendors.overviewFeaturesTitle')"
                        >
                            <div class="vm-overview-card__head">
                                <div class="vm-overview-card__badge" aria-hidden="true">
                                    <svg class="vm-overview-card__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2L2 7l10 5 10-5-10-5z" stroke-linejoin="round" />
                                        <path d="M2 17l10 5 10-5M2 12l10 5 10-5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div class="vm-overview-card__head-text">
                                    <h3 class="vm-overview-card__title">{{ t('vendors.overviewFeaturesTitle') }}</h3>
                                    <p class="vm-overview-card__hint">{{ t('vendors.overviewFeaturesHint') }}</p>
                                </div>
                            </div>
                            <ul class="vm-overview-card__list">
                                <li v-for="(item, idx) in pagedOverviewFeatures" :key="'ov-f-' + overviewFeaturesPage + '-' + idx" class="vm-overview-card__li">
                                    <span class="vm-overview-card__check" aria-hidden="true" />
                                    <span class="vm-overview-card__txt">{{ item }}</span>
                                </li>
                            </ul>
                            <nav v-if="overviewFeaturesList.length" class="vm-overview-pager" :aria-label="t('vendors.overviewPagerFeaturesAria')">
                                <div class="vm-overview-pager__row">
                                    <label class="vm-overview-pager__pp">
                                        <span class="vm-overview-pager__pp-lbl">{{ t('vendors.overviewPerPage') }}</span>
                                        <select v-model.number="overviewFeaturesPerPage" class="vm-overview-pager__select" @change="overviewFeaturesPage = 1">
                                            <option v-for="n in overviewPerPageOptions" :key="'fpp-' + n" :value="n">{{ n }}</option>
                                        </select>
                                    </label>
                                    <span class="vm-overview-pager__range">{{ overviewFeaturesRangeText }}</span>
                                    <div class="vm-overview-pager__nav">
                                        <button
                                            type="button"
                                            class="vm-overview-pager__btn"
                                            :disabled="overviewFeaturesPage <= 1"
                                            @click="overviewFeaturesPage = Math.max(1, overviewFeaturesPage - 1)"
                                        >
                                            {{ t('vendors.overviewPrev') }}
                                        </button>
                                        <button
                                            type="button"
                                            class="vm-overview-pager__btn"
                                            :disabled="overviewFeaturesPage >= overviewFeaturesTotalPages"
                                            @click="overviewFeaturesPage = Math.min(overviewFeaturesTotalPages, overviewFeaturesPage + 1)"
                                        >
                                            {{ t('vendors.overviewNext') }}
                                        </button>
                                    </div>
                                </div>
                            </nav>
                        </article>
                        <article
                            v-if="overviewServicesList.length"
                            class="vm-overview-card vm-overview-card--services"
                            :aria-label="t('vendors.overviewServicesTitle')"
                        >
                            <div class="vm-overview-card__head">
                                <div class="vm-overview-card__badge vm-overview-card__badge--svc" aria-hidden="true">
                                    <svg class="vm-overview-card__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke-linejoin="round" />
                                        <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" />
                                    </svg>
                                </div>
                                <div class="vm-overview-card__head-text">
                                    <h3 class="vm-overview-card__title">{{ t('vendors.overviewServicesTitle') }}</h3>
                                    <p class="vm-overview-card__hint">{{ t('vendors.overviewServicesHint') }}</p>
                                </div>
                            </div>
                            <ul class="vm-overview-card__list vm-overview-card__list--services">
                                <li v-for="(item, idx) in pagedOverviewServices" :key="'ov-s-' + overviewServicesPage + '-' + idx" class="vm-overview-card__li">
                                    <span class="vm-overview-card__dot" aria-hidden="true" />
                                    <span class="vm-overview-card__txt">{{ item }}</span>
                                </li>
                            </ul>
                            <nav v-if="overviewServicesList.length" class="vm-overview-pager" :aria-label="t('vendors.overviewPagerServicesAria')">
                                <div class="vm-overview-pager__row">
                                    <label class="vm-overview-pager__pp">
                                        <span class="vm-overview-pager__pp-lbl">{{ t('vendors.overviewPerPage') }}</span>
                                        <select v-model.number="overviewServicesPerPage" class="vm-overview-pager__select" @change="overviewServicesPage = 1">
                                            <option v-for="n in overviewPerPageOptions" :key="'spp-' + n" :value="n">{{ n }}</option>
                                        </select>
                                    </label>
                                    <span class="vm-overview-pager__range">{{ overviewServicesRangeText }}</span>
                                    <div class="vm-overview-pager__nav">
                                        <button
                                            type="button"
                                            class="vm-overview-pager__btn"
                                            :disabled="overviewServicesPage <= 1"
                                            @click="overviewServicesPage = Math.max(1, overviewServicesPage - 1)"
                                        >
                                            {{ t('vendors.overviewPrev') }}
                                        </button>
                                        <button
                                            type="button"
                                            class="vm-overview-pager__btn"
                                            :disabled="overviewServicesPage >= overviewServicesTotalPages"
                                            @click="overviewServicesPage = Math.min(overviewServicesTotalPages, overviewServicesPage + 1)"
                                        >
                                            {{ t('vendors.overviewNext') }}
                                        </button>
                                    </div>
                                </div>
                            </nav>
                        </article>
                    </div>
                </div>
                <table class="vm-kv vm-kv--comfort" :class="{ 'vm-kv--editing': isEditing }">
                    <tbody>
                        <tr>
                            <th scope="row">{{ t('vendors.legalName') }}</th>
                            <td>
                                <template v-if="isEditing">
                                    <input
                                        id="vm-o-legal"
                                        v-model="edit.legal_name"
                                        class="ppms-input vm-kv__control"
                                        :placeholder="t('vendors.detailLegalNamePlaceholder')"
                                        :title="t('vendors.detailLegalNameHint')"
                                        autocomplete="organization"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyText(vendor.legal_name)">{{ vendor.legal_name }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailLegalNameHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.country') }}</th>
                            <td>
                                <template v-if="isEditing">
                                    <input
                                        id="vm-o-country"
                                        v-model="edit.country"
                                        class="ppms-input vm-kv__control"
                                        :placeholder="t('vendors.detailCountryPlaceholder')"
                                        :title="t('vendors.detailCountryHint')"
                                        autocomplete="country-name"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyText(vendor.country)">{{ vendor.country }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailCountryHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.website') }}</th>
                            <td>
                                <template v-if="isEditing">
                                    <input
                                        id="vm-o-web"
                                        v-model="edit.website"
                                        type="url"
                                        class="ppms-input vm-kv__control"
                                        :placeholder="t('vendors.detailWebsitePlaceholder')"
                                        :title="t('vendors.detailWebsiteHint')"
                                    />
                                </template>
                                <template v-else>
                                    <a v-if="!isEmptyText(vendor.website)" :href="normalizeHttpUrl(vendor.website)" target="_blank" rel="noopener">{{ vendor.website }}</a>
                                    <span v-else class="vm-empty" :title="t('vendors.detailWebsiteHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.taxCode') }}</th>
                            <td>
                                <template v-if="isEditing">
                                    <input
                                        id="vm-o-tax"
                                        v-model="edit.tax_code"
                                        class="ppms-input vm-kv__control"
                                        :placeholder="t('vendors.detailTaxPlaceholder')"
                                        :title="t('vendors.detailTaxHint')"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyText(vendor.tax_code)">{{ vendor.tax_code }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailTaxHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.contactInfo') }}</th>
                            <td :class="{ 'vm-pre': !isEditing }">
                                <template v-if="isEditing">
                                    <textarea
                                        id="vm-o-contact"
                                        v-model="edit.contact_info"
                                        rows="3"
                                        class="ppms-input vm-kv__control vm-kv__control--textarea"
                                        :placeholder="t('vendors.detailContactPlaceholder')"
                                        :title="t('vendors.detailContactHint')"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyText(vendor.contact_info)">{{ vendor.contact_info }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailContactHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.riskLevel') }}</th>
                            <td>
                                <template v-if="isEditing">
                                    <select
                                        id="vm-o-risk"
                                        v-model="edit.risk_level"
                                        class="ppms-input vm-kv__control vm-kv__select"
                                        :title="t('vendors.detailRiskHint')"
                                    >
                                        <option value="">{{ t('vendors.riskNotSet') }}</option>
                                        <option value="low">{{ t('vendors.riskLow') }}</option>
                                        <option value="medium">{{ t('vendors.riskMedium') }}</option>
                                        <option value="high">{{ t('vendors.riskHigh') }}</option>
                                    </select>
                                </template>
                                <template v-else>
                                    <template v-if="vendor.risk_level">{{ riskLabel(vendor.risk_level) }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailRiskHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.internalNote') }}</th>
                            <td :class="{ 'vm-pre': !isEditing }">
                                <template v-if="isEditing">
                                    <textarea
                                        id="vm-o-note"
                                        v-model="edit.internal_note"
                                        rows="4"
                                        class="ppms-input vm-kv__control vm-kv__control--textarea"
                                        :placeholder="t('vendors.detailInternalNotePlaceholder')"
                                        :title="t('vendors.detailInternalNoteHint')"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyText(vendor.internal_note)">{{ vendor.internal_note }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailInternalNoteHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <template v-if="vendor.kind === 'research'">
                            <tr>
                                <th scope="row">{{ t('vendors.mainProducts') }}</th>
                                <td :class="{ 'vm-pre': !isEditing }">
                                    <template v-if="isEditing">
                                        <VendorTagInput
                                            ref="tagMainProductsRef"
                                            v-model="edit.main_products"
                                            input-id="vm-r-mp"
                                            :placeholder="t('vendors.detailMainProductsPlaceholder')"
                                            :title="t('vendors.detailMainProductsHint')"
                                        />
                                    </template>
                                    <template v-else>
                                        <template v-if="!isEmptyText(vendor.main_products)">{{ vendor.main_products }}</template>
                                        <span v-else class="vm-empty" :title="t('vendors.detailMainProductsHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                    </template>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">{{ t('vendors.servicesOffered') }}</th>
                                <td :class="{ 'vm-pre': !isEditing }">
                                    <template v-if="isEditing">
                                        <VendorTagInput
                                            ref="tagServicesRef"
                                            v-model="edit.services_offered"
                                            input-id="vm-r-sv"
                                            :placeholder="t('vendors.detailServicesOfferedPlaceholder')"
                                            :title="t('vendors.detailServicesOfferedHint')"
                                        />
                                    </template>
                                    <template v-else>
                                        <template v-if="!isEmptyText(vendor.services_offered)">{{ vendor.services_offered }}</template>
                                        <span v-else class="vm-empty" :title="t('vendors.detailServicesOfferedHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                    </template>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">{{ t('vendors.pros') }}</th>
                                <td :class="{ 'vm-pre': !isEditing }">
                                    <template v-if="isEditing">
                                        <textarea
                                            id="vm-r-pros"
                                            v-model="edit.pros"
                                            rows="3"
                                            class="ppms-input vm-kv__control vm-kv__control--textarea"
                                            :placeholder="t('vendors.detailProsPlaceholder')"
                                            :title="t('vendors.detailProsPlaceholder')"
                                        />
                                    </template>
                                    <template v-else>
                                        <template v-if="!isEmptyText(vendor.pros)">{{ vendor.pros }}</template>
                                        <span v-else class="vm-empty" :title="t('vendors.detailProsPlaceholder')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        <template v-if="vendor.kind !== 'research'">
                        <tr class="vm-kv__group">
                            <th colspan="2" class="vm-kv__group-title">{{ t('vendors.tabBusiness') }}</th>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.filterIndustry') }}</th>
                            <td>
                                <template v-if="isEditing">
                                    <input
                                        id="vm-b-ind"
                                        v-model="edit.industry"
                                        class="ppms-input vm-kv__control"
                                        :placeholder="t('vendors.detailIndustryPlaceholder')"
                                        :title="t('vendors.detailIndustryHint')"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyText(vendor.industry)">{{ vendor.industry }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailIndustryHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.mainProducts') }}</th>
                            <td :class="{ 'vm-pre': !isEditing }">
                                <template v-if="isEditing">
                                    <VendorTagInput
                                        ref="tagMainProductsRef"
                                        v-model="edit.main_products"
                                        input-id="vm-b-mp"
                                        :placeholder="t('vendors.detailMainProductsPlaceholder')"
                                        :title="t('vendors.detailMainProductsHint')"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyText(vendor.main_products)">{{ vendor.main_products }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailMainProductsHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.servicesOffered') }}</th>
                            <td :class="{ 'vm-pre': !isEditing }">
                                <template v-if="isEditing">
                                    <VendorTagInput
                                        ref="tagServicesRef"
                                        v-model="edit.services_offered"
                                        input-id="vm-b-sv"
                                        :placeholder="t('vendors.detailServicesOfferedPlaceholder')"
                                        :title="t('vendors.detailServicesOfferedHint')"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyText(vendor.services_offered)">{{ vendor.services_offered }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailServicesOfferedHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.contractValue') }}</th>
                            <td>
                                <template v-if="isEditing">
                                    <input
                                        id="vm-b-cv"
                                        v-model="edit.contract_value"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="ppms-input vm-kv__control"
                                        :placeholder="t('vendors.detailContractValuePlaceholder')"
                                        :title="t('vendors.detailContractValueHint')"
                                        inputmode="decimal"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyNumber(vendor.contract_value)">{{ vendor.contract_value }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailContractValueHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.estimatedCost') }}</th>
                            <td>
                                <template v-if="isEditing">
                                    <input
                                        id="vm-b-ec"
                                        v-model="edit.estimated_cost"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="ppms-input vm-kv__control"
                                        :placeholder="t('vendors.detailEstimatedCostPlaceholder')"
                                        :title="t('vendors.detailEstimatedCostHint')"
                                        inputmode="decimal"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyNumber(vendor.estimated_cost)">{{ vendor.estimated_cost }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailEstimatedCostHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.referencePrice') }}</th>
                            <td>
                                <template v-if="isEditing">
                                    <input
                                        id="vm-b-rp"
                                        v-model="edit.reference_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="ppms-input vm-kv__control"
                                        :placeholder="t('vendors.detailReferencePricePlaceholder')"
                                        :title="t('vendors.detailReferencePriceHint')"
                                        inputmode="decimal"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyNumber(vendor.reference_price)">{{ vendor.reference_price }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailReferencePriceHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.researchSource') }}</th>
                            <td>
                                <template v-if="isEditing">
                                    <input
                                        id="vm-b-src"
                                        v-model="edit.research_source"
                                        class="ppms-input vm-kv__control"
                                        :placeholder="t('vendors.detailResearchSourcePlaceholder')"
                                        :title="t('vendors.detailResearchSourceHint')"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyText(vendor.research_source)">{{ vendor.research_source }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailResearchSourceHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.fitScore') }}</th>
                            <td>
                                <template v-if="isEditing">
                                    <input
                                        id="vm-b-fit"
                                        v-model.number="edit.fit_score"
                                        type="number"
                                        min="0"
                                        max="100"
                                        class="ppms-input vm-kv__control"
                                        :placeholder="t('vendors.detailFitScorePlaceholder')"
                                        :title="t('vendors.detailFitScoreHint')"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyNumber(vendor.fit_score)">{{ vendor.fit_score }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailFitScoreHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.pros') }}</th>
                            <td :class="{ 'vm-pre': !isEditing }">
                                <template v-if="isEditing">
                                    <textarea
                                        id="vm-b-pros"
                                        v-model="edit.pros"
                                        rows="3"
                                        class="ppms-input vm-kv__control vm-kv__control--textarea"
                                        :placeholder="t('vendors.detailProsPlaceholder')"
                                        :title="t('vendors.detailProsPlaceholder')"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyText(vendor.pros)">{{ vendor.pros }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailProsPlaceholder')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.cons') }}</th>
                            <td :class="{ 'vm-pre': !isEditing }">
                                <template v-if="isEditing">
                                    <textarea
                                        id="vm-b-cons"
                                        v-model="edit.cons"
                                        rows="3"
                                        class="ppms-input vm-kv__control vm-kv__control--textarea"
                                        :placeholder="t('vendors.detailConsPlaceholder')"
                                        :title="t('vendors.detailConsPlaceholder')"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyText(vendor.cons)">{{ vendor.cons }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailConsPlaceholder')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.researchNote') }}</th>
                            <td :class="{ 'vm-pre': !isEditing }">
                                <template v-if="isEditing">
                                    <textarea
                                        id="vm-b-rn"
                                        v-model="edit.research_note"
                                        rows="3"
                                        class="ppms-input vm-kv__control vm-kv__control--textarea"
                                        :placeholder="t('vendors.detailResearchNotePlaceholder')"
                                        :title="t('vendors.detailResearchNoteHint')"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyText(vendor.research_note)">{{ vendor.research_note }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailResearchNoteHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr class="vm-kv__group">
                            <th colspan="2" class="vm-kv__group-title">{{ t('vendors.formSectionBizCriteria') }}</th>
                        </tr>
                        <tr v-if="isEditing">
                            <td colspan="2" class="vm-kv__section-hint">{{ t('vendors.formSectionBizCriteriaHint') }}</td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.scorePrice') }}</th>
                            <td>
                                <template v-if="isEditing">
                                    <input
                                        id="vm-b-sp"
                                        v-model="edit.score_price"
                                        type="number"
                                        min="0"
                                        max="5"
                                        step="0.1"
                                        class="ppms-input vm-kv__control"
                                        placeholder="0–5"
                                        :title="t('vendors.detailScorePriceHint')"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyNumber(vendor.score_price)">{{ vendor.score_price }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailScorePriceHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.scoreQuality') }}</th>
                            <td>
                                <template v-if="isEditing">
                                    <input
                                        id="vm-b-sq"
                                        v-model="edit.score_quality"
                                        type="number"
                                        min="0"
                                        max="5"
                                        step="0.1"
                                        class="ppms-input vm-kv__control"
                                        placeholder="0–5"
                                        :title="t('vendors.detailScoreQualityHint')"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyNumber(vendor.score_quality)">{{ vendor.score_quality }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailScoreQualityHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.scoreSla') }}</th>
                            <td>
                                <template v-if="isEditing">
                                    <input
                                        id="vm-b-ss"
                                        v-model="edit.score_sla"
                                        type="number"
                                        min="0"
                                        max="5"
                                        step="0.1"
                                        class="ppms-input vm-kv__control"
                                        placeholder="0–5"
                                        :title="t('vendors.detailScoreSlaHint')"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyNumber(vendor.score_sla)">{{ vendor.score_sla }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailScoreSlaHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.scoreSupport') }}</th>
                            <td>
                                <template v-if="isEditing">
                                    <input
                                        id="vm-b-su"
                                        v-model="edit.score_support"
                                        type="number"
                                        min="0"
                                        max="5"
                                        step="0.1"
                                        class="ppms-input vm-kv__control"
                                        placeholder="0–5"
                                        :title="t('vendors.detailScoreSupportHint')"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="!isEmptyNumber(vendor.score_support)">{{ vendor.score_support }}</template>
                                    <span v-else class="vm-empty" :title="t('vendors.detailScoreSupportHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr class="vm-kv__group">
                            <th colspan="2" class="vm-kv__group-title">{{ t('vendors.formSectionDepartments') }}</th>
                        </tr>
                        <tr v-if="isEditing">
                            <td colspan="2" class="vm-kv__section-hint">{{ t('vendors.detailDepartmentsHint') }}</td>
                        </tr>
                        <tr>
                            <th scope="row">{{ t('vendors.departments') }}</th>
                            <td>
                                <template v-if="isEditing">
                                    <div ref="deptMultiRef" class="vm-dept-multi">
                                        <button
                                            id="vm-b-dept"
                                            type="button"
                                            class="vm-dept-multi__trigger"
                                            :class="{ 'vm-dept-multi__trigger--open': deptMenuOpen }"
                                            :aria-expanded="deptMenuOpen"
                                            aria-haspopup="listbox"
                                            @click.stop="deptMenuOpen = !deptMenuOpen"
                                        >
                                            <span class="vm-dept-multi__trigger-text">{{ deptSelectionSummary }}</span>
                                            <span class="vm-dept-multi__chev" aria-hidden="true">▾</span>
                                        </button>
                                        <div
                                            v-show="deptMenuOpen"
                                            class="vm-dept-multi__panel"
                                            role="listbox"
                                            :aria-label="t('vendors.departments')"
                                        >
                                            <label
                                                v-for="d in lookups.departments"
                                                :key="d.id"
                                                class="vm-dept-multi__option"
                                            >
                                                <input v-model="editDepartmentIds" type="checkbox" :value="d.id" />
                                                <span>{{ d.name }}</span>
                                            </label>
                                            <p v-if="!(lookups.departments || []).length" class="vm-dept-multi__empty">
                                                {{ t('vendors.deptLookupEmpty') }}
                                            </p>
                                        </div>
                                    </div>
                                    <p class="vm-kv__foot">{{ t('contracts.addDepartment') }}</p>
                                </template>
                                <template v-else>
                                    <span v-for="d in vendor.departments || []" :key="d.id" class="vm-dept-badge">{{ d.name }}</span>
                                    <span v-if="!(vendor.departments || []).length" class="vm-empty" :title="t('vendors.emptyDepartmentsHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                                </template>
                            </td>
                        </tr>
                        </template>
                    </tbody>
                </table>
                <div v-if="isEditing" class="vm-detail__inline-actions">
                    <button type="button" class="ppms-btn-primary" :disabled="saving" @click="saveVendor">
                        {{ t('vendors.save') }}
                    </button>
                    <button type="button" class="ppms-btn-ghost" :disabled="saving" @click="resetEditFromVendor">
                        {{ t('vendors.cancel') }}
                    </button>
                </div>
                <div v-if="vendor.kind !== 'research' && vendor.products?.length" class="ppms-mt">
                    <h4>{{ t('vendors.productLines') }}</h4>
                    <ul class="vm-product-list">
                        <li v-for="p in vendor.products" :key="p.id"><strong>{{ p.name }}</strong> — {{ p.description || '' }}</li>
                    </ul>
                </div>
            </section>

            <section
                v-if="vendor.kind !== 'research'"
                id="vm-panel-contracts"
                class="ppms-card ppms-mt vm-panel"
            >
                <h3 class="vm-sec-title">{{ t('vendors.tabContracts') }}</h3>
                <p class="vm-contracts-intro">{{ t('vendors.contractsTabIntro') }}</p>
                <div v-if="vendor.contracts?.length" class="vm-contracts-table-wrap">
                    <table class="ppms-table vm-contracts-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ t('vendors.contractCode') }}</th>
                                <th scope="col">{{ t('contracts.tableStatus') }}</th>
                                <th scope="col">{{ t('vendors.contractPeriodCol') }}</th>
                                <th scope="col">{{ t('vendors.contractDept') }}</th>
                                <th scope="col" class="vm-contracts-table__num">{{ t('vendors.contractValueCol') }}</th>
                                <th scope="col" class="vm-contracts-table__act">{{ t('vendors.contractActionCol') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in vendor.contracts" :key="c.id">
                                <td>
                                    <span class="vm-contracts-code">{{ c.code }}</span>
                                </td>
                                <td>
                                    <span class="vm-contracts-status">{{ contractStatusLabel(c.status) }}</span>
                                </td>
                                <td class="vm-contracts-period">{{ formatContractPeriod(c.start_date, c.end_date) }}</td>
                                <td>{{ c.department?.name || '—' }}</td>
                                <td class="vm-contracts-table__num">{{ formatContractValue(c.total_value) }}</td>
                                <td class="vm-contracts-table__act">
                                    <router-link class="ppms-btn-ghost ppms-btn-sm" :to="`/contracts/${c.id}`">{{ t('vendors.goContract') }}</router-link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="vm-contracts-empty ppms-muted">{{ t('vendors.contractsEmpty') }}</p>
            </section>

            <section
                v-if="vendor.kind !== 'research'"
                id="vm-panel-reviews"
                class="ppms-card ppms-mt vm-panel"
            >
                <h3 class="vm-sec-title">{{ t('vendors.tabReviews') }}</h3>
                <VendorReview
                    :vendor-id="Number(vendor.id)"
                    :items="vendor.reviews || []"
                    :can-review="canEdit"
                    :me="me"
                    @refresh="reload"
                    @delete="onDeleteReview"
                />
            </section>

            <section
                v-if="vendor.kind !== 'research'"
                id="vm-panel-timeline"
                class="ppms-card ppms-mt vm-panel"
            >
                <div v-if="canEdit" class="vm-timeline-add">
                    <h3 class="vm-sec-title">{{ t('vendors.timelineAdd') }}</h3>
                    <p class="vm-timeline-add__intro">{{ t('vendors.timelineAddIntro') }}</p>
                    <div class="vm-timeline-add__grid">
                        <div class="vm-field">
                            <label class="vm-field__label" for="vm-tl-ph">{{ t('vendors.timelinePhase') }}</label>
                            <p id="vm-tl-ph-hint" class="vm-field__hint">{{ t('vendors.timelinePhaseHint') }}</p>
                            <select id="vm-tl-ph" v-model="tlForm.phase" class="ppms-input vm-kv__control vm-kv__select" aria-describedby="vm-tl-ph-hint">
                                <option v-for="ph in phases" :key="ph" :value="ph">{{ timelinePhaseLabel(ph) }}</option>
                            </select>
                        </div>
                        <div class="vm-field">
                            <label class="vm-field__label" for="vm-tl-when">{{ t('vendors.timelineWhen') }}</label>
                            <p id="vm-tl-when-hint" class="vm-field__hint">{{ t('vendors.timelineWhenHint') }}</p>
                            <input
                                id="vm-tl-when"
                                v-model="tlForm.occurred_at_local"
                                type="datetime-local"
                                class="ppms-input vm-kv__control"
                                :step="60"
                                aria-describedby="vm-tl-when-hint"
                            />
                        </div>
                        <UserPickerInput
                            v-model="tlForm.performed_by_user_id"
                            input-id="vm-tl-actor"
                            :label="t('vendors.timelineActor')"
                            :hint="t('vendors.timelineActorHint')"
                            :placeholder="t('vendors.timelineActorPlaceholder')"
                        />
                        <div class="vm-field vm-field--checkbox vm-field--timeline-cb">
                            <label class="vm-field__checkbox">
                                <input v-model="tlForm.is_current" type="checkbox" />
                                <span>{{ t('vendors.timelineCurrent') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="vm-field vm-field--full vm-field--timeline-note">
                        <label class="vm-field__label" for="vm-tl-note">{{ t('vendors.timelineNote') }}</label>
                        <p id="vm-tl-note-hint" class="vm-field__hint">{{ t('vendors.timelineNoteHint') }}</p>
                        <textarea
                            id="vm-tl-note"
                            v-model="tlForm.note"
                            rows="3"
                            class="ppms-input vm-kv__control vm-kv__control--textarea"
                            :placeholder="t('vendors.timelineNotePlaceholder')"
                            :title="t('vendors.timelineNoteHint')"
                            aria-describedby="vm-tl-note-hint"
                        />
                    </div>
                    <div class="vm-timeline-add__actions">
                        <button type="button" class="ppms-btn-primary" :disabled="tlSaving" @click="addTimeline">{{ t('vendors.timelineAdd') }}</button>
                    </div>
                </div>
                <VendorTimeline
                    :events="timelineEvents"
                    :loading="timelineLoading"
                    :err="timelineErr"
                    :can-edit="canEdit"
                    @delete="onDeleteTimeline"
                />
            </section>
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { getApiErrorMessage } from '@/bootstrap';
import { ppmsConfirm, ppmsToastSuccess, ppmsToastError } from '@/ppmsUi';
import UserPickerInput from '@/components/UserPickerInput.vue';
import VendorTagInput from '@/components/VendorTagInput.vue';
import VendorTimeline from './components/VendorTimeline.vue';
import VendorReview from './components/VendorReview.vue';

defineProps({
    id: { type: String, required: true },
});

const { t, locale } = useI18n();
const route = useRoute();

const loading = ref(true);
const err = ref('');
const vendor = ref(null);
const tagMainProductsRef = ref(null);
const tagServicesRef = ref(null);
const me = ref(null);
const saving = ref(false);

const OVERVIEW_FIELD_KEYS = [
    'legal_name',
    'country',
    'website',
    'tax_code',
    'contact_info',
    'internal_note',
    'risk_level',
];
const BUSINESS_FIELD_KEYS = [
    'industry',
    'main_products',
    'services_offered',
    'contract_value',
    'estimated_cost',
    'reference_price',
    'research_source',
    'research_note',
    'pros',
    'cons',
    'fit_score',
    'score_price',
    'score_quality',
    'score_sla',
    'score_support',
];
const lookups = ref({ departments: [] });

const edit = reactive({});
const editDepartmentIds = ref([]);
const deptMenuOpen = ref(false);
const deptMultiRef = ref(null);

const timelineEvents = ref([]);
const timelineLoading = ref(false);
const timelineErr = ref('');
const tlSaving = ref(false);
const tlForm = reactive({
    phase: 'potential_contact',
    occurred_at_local: '',
    performed_by_user_id: null,
    note: '',
    is_current: false,
});

const phases = [
    'potential_contact',
    'survey_consult',
    'quotation',
    'negotiation',
    'contract_signed',
    'payment_acceptance',
    'no_contract',
    'research_update',
];

const canEdit = computed(() => ['admin', 'pm', 'tl', 'hr', 'developer'].includes(me.value?.role));
const isEditing = computed(() => canEdit.value);

const deptSelectionSummary = computed(() => {
    const ids = editDepartmentIds.value.map(Number);
    const list = lookups.value.departments || [];
    const names = list.filter((d) => ids.includes(Number(d.id))).map((d) => d.name);
    if (!names.length) return t('vendors.deptMultiPlaceholder');
    if (names.length <= 3) return names.join(', ');
    return t('vendors.deptMultiNSelected', { n: names.length });
});

const RESEARCH_PATCH_KEYS = ['main_products', 'services_offered', 'pros'];

function splitVendorList(text) {
    if (text === null || text === undefined) return [];
    const s = String(text).trim();
    if (!s) return [];
    return s
        .split(/[\n,;|]+/)
        .map((x) => x.trim())
        .filter(Boolean);
}

const overviewFeaturesList = computed(() => splitVendorList(vendor.value?.main_products));
const overviewServicesList = computed(() => splitVendorList(vendor.value?.services_offered));

const overviewPerPageOptions = [5, 8, 12, 20];
const overviewFeaturesPage = ref(1);
const overviewServicesPage = ref(1);
const overviewFeaturesPerPage = ref(5);
const overviewServicesPerPage = ref(5);

const overviewFeaturesTotalPages = computed(() => {
    const n = overviewFeaturesList.value.length;
    if (n === 0) return 1;
    return Math.max(1, Math.ceil(n / overviewFeaturesPerPage.value));
});

const overviewServicesTotalPages = computed(() => {
    const n = overviewServicesList.value.length;
    if (n === 0) return 1;
    return Math.max(1, Math.ceil(n / overviewServicesPerPage.value));
});

const pagedOverviewFeatures = computed(() => {
    const list = overviewFeaturesList.value;
    const page = Math.min(Math.max(1, overviewFeaturesPage.value), overviewFeaturesTotalPages.value);
    const start = (page - 1) * overviewFeaturesPerPage.value;
    return list.slice(start, start + overviewFeaturesPerPage.value);
});

const pagedOverviewServices = computed(() => {
    const list = overviewServicesList.value;
    const page = Math.min(Math.max(1, overviewServicesPage.value), overviewServicesTotalPages.value);
    const start = (page - 1) * overviewServicesPerPage.value;
    return list.slice(start, start + overviewServicesPerPage.value);
});

const overviewFeaturesRangeText = computed(() => {
    const total = overviewFeaturesList.value.length;
    if (total === 0) return '';
    const page = Math.min(Math.max(1, overviewFeaturesPage.value), overviewFeaturesTotalPages.value);
    const start = (page - 1) * overviewFeaturesPerPage.value + 1;
    const end = Math.min(page * overviewFeaturesPerPage.value, total);
    return t('vendors.overviewRangeOf', { start, end, total });
});

const overviewServicesRangeText = computed(() => {
    const total = overviewServicesList.value.length;
    if (total === 0) return '';
    const page = Math.min(Math.max(1, overviewServicesPage.value), overviewServicesTotalPages.value);
    const start = (page - 1) * overviewServicesPerPage.value + 1;
    const end = Math.min(page * overviewServicesPerPage.value, total);
    return t('vendors.overviewRangeOf', { start, end, total });
});

watch(
    () => vendor.value?.id,
    () => {
        overviewFeaturesPage.value = 1;
        overviewServicesPage.value = 1;
    },
);

watch(overviewFeaturesTotalPages, (tp) => {
    if (overviewFeaturesPage.value > tp) overviewFeaturesPage.value = tp;
});

watch(overviewServicesTotalPages, (tp) => {
    if (overviewServicesPage.value > tp) overviewServicesPage.value = tp;
});

function normalizeHttpUrl(url) {
    if (!url || typeof url !== 'string') {
        return '';
    }
    const u = url.trim();
    if (!u) {
        return '';
    }
    return /^https?:\/\//i.test(u) ? u : `https://${u}`;
}

function kindLabel(k) {
    return k === 'research' ? t('vendors.kindResearch') : t('vendors.kindActive');
}

function statusLabel(s) {
    const map = {
        active: 'vendors.statusActive',
        inactive: 'vendors.statusInactive',
        blacklist: 'vendors.statusBlacklist',
        researching: 'vendors.statusResearching',
        shortlist: 'vendors.statusShortlist',
        rejected: 'vendors.statusRejected',
    };
    const key = map[s];
    return key ? t(key) : s;
}

function riskLabel(r) {
    const map = { low: 'vendors.riskLow', medium: 'vendors.riskMedium', high: 'vendors.riskHigh' };
    const key = map[r];
    return key ? t(key) : r || '—';
}

function isEmptyText(v) {
    return v === null || v === undefined || String(v).trim() === '';
}

function isEmptyNumber(v) {
    return v === null || v === undefined || v === '';
}

function contractStatusLabel(status) {
    const map = {
        draft: 'vendors.contractStatusDraft',
        pending_approval: 'vendors.contractStatusPendingApproval',
        active: 'vendors.contractStatusActive',
        expired: 'vendors.contractStatusExpired',
        terminated: 'vendors.contractStatusTerminated',
    };
    const key = map[status];
    return key ? t(key) : status || '—';
}

function formatContractPeriod(start, end) {
    if (!start && !end) {
        return t('vendors.contractPeriodOpen');
    }
    const fmt = (d) => {
        if (!d) return '';
        try {
            const loc = locale.value === 'vi' ? 'vi-VN' : undefined;
            return new Intl.DateTimeFormat(loc, { dateStyle: 'medium' }).format(new Date(d));
        } catch {
            return d;
        }
    };
    const a = fmt(start);
    const b = fmt(end);
    if (a && b) return `${a} – ${b}`;
    if (a) return `${a} …`;
    if (b) return `… ${b}`;
    return '—';
}

function formatContractValue(v) {
    if (v === null || v === undefined || v === '') return '—';
    const n = Number(v);
    if (Number.isNaN(n)) return String(v);
    const loc = locale.value === 'vi' ? 'vi-VN' : undefined;
    return new Intl.NumberFormat(loc, { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(n);
}

function timelinePhaseLabel(ph) {
    const map = {
        potential_contact: 'vendors.phasePotentialContact',
        survey_consult: 'vendors.phaseSurveyConsult',
        quotation: 'vendors.phaseQuotation',
        negotiation: 'vendors.phaseNegotiation',
        contract_signed: 'vendors.phaseContractSigned',
        payment_acceptance: 'vendors.phasePaymentAcceptance',
        no_contract: 'vendors.phaseNoContract',
        research_update: 'vendors.phaseResearchUpdate',
    };
    const key = map[ph];
    return key ? t(key) : ph;
}

function snapshotFromVendor(v) {
    const keys = [
        'legal_name', 'country', 'website', 'tax_code', 'contact_info', 'internal_note', 'risk_level',
        'industry', 'main_products', 'services_offered', 'contract_value', 'estimated_cost', 'reference_price',
        'research_source', 'research_note', 'pros', 'cons', 'fit_score',
        'score_price', 'score_quality', 'score_sla', 'score_support',
    ];
    for (const k of keys) {
        edit[k] = v[k] ?? '';
    }
    editDepartmentIds.value = (v.departments || []).map((d) => Number(d.id));
}

async function loadLookups() {
    const { data } = await axios.get('/api/contract-lookups');
    lookups.value = { departments: data.departments || [] };
}

async function loadVendor() {
    loading.value = true;
    err.value = '';
    try {
        const { data } = await axios.get(`/api/vendors/${route.params.id}`);
        const payload = data.data ?? data;
        vendor.value = payload;
        snapshotFromVendor(payload);
        if (payload?.kind === 'research') {
            timelineEvents.value = [];
            timelineErr.value = '';
            timelineLoading.value = false;
        } else {
            await loadTimeline();
        }
    } catch (e) {
        err.value = getApiErrorMessage(e);
        vendor.value = null;
    } finally {
        loading.value = false;
    }
}

async function reload() {
    await loadVendor();
}

async function loadTimeline() {
    timelineLoading.value = true;
    timelineErr.value = '';
    try {
        const { data } = await axios.get(`/api/vendors/${route.params.id}/timeline`, { params: { per_page: 100 } });
        timelineEvents.value = data.data ?? [];
    } catch (e) {
        timelineErr.value = getApiErrorMessage(e, t('vendors.timelineLoadError'));
        timelineEvents.value = [];
    } finally {
        timelineLoading.value = false;
    }
}

function onDocClickDept(e) {
    if (!deptMenuOpen.value) return;
    const el = deptMultiRef.value;
    if (el && !el.contains(e.target)) deptMenuOpen.value = false;
}

function resetEditFromVendor() {
    if (vendor.value) snapshotFromVendor(vendor.value);
}

async function saveVendor() {
    if (!canEdit.value) return;
    tagMainProductsRef.value?.flushPending?.();
    tagServicesRef.value?.flushPending?.();
    saving.value = true;
    try {
        const body = {};
        for (const k of OVERVIEW_FIELD_KEYS) {
            body[k] = edit[k];
        }
        if (vendor.value?.kind === 'research') {
            for (const k of RESEARCH_PATCH_KEYS) {
                body[k] = edit[k];
            }
        } else {
            for (const k of BUSINESS_FIELD_KEYS) {
                body[k] = edit[k];
            }
            body.department_ids = editDepartmentIds.value.map((x) => Number(x));
        }
        await axios.patch(`/api/vendors/${route.params.id}`, body);
        ppmsToastSuccess(t('vendors.saved'));
        deptMenuOpen.value = false;
        await loadVendor();
    } catch (e) {
        ppmsToastError(getApiErrorMessage(e));
    } finally {
        saving.value = false;
    }
}

async function addTimeline() {
    if (!tlForm.occurred_at_local) {
        ppmsToastError(t('vendors.timelinePickTime'));
        return;
    }
    const iso = new Date(tlForm.occurred_at_local).toISOString();
    tlSaving.value = true;
    try {
        await axios.post(`/api/vendors/${route.params.id}/timeline`, {
            phase: tlForm.phase,
            occurred_at: iso,
            performed_by_user_id: tlForm.performed_by_user_id || null,
            note: tlForm.note || null,
            is_current: tlForm.is_current,
        });
        ppmsToastSuccess(t('vendors.timelineSaved'));
        tlForm.note = '';
        tlForm.is_current = false;
        await loadTimeline();
        await loadVendor();
    } catch (e) {
        ppmsToastError(getApiErrorMessage(e));
    } finally {
        tlSaving.value = false;
    }
}

async function onDeleteTimeline(ev) {
    const ok = await ppmsConfirm(t('vendors.timelineDelete'), { title: t('vendors.timelineDelete') });
    if (!ok) return;
    try {
        await axios.delete(`/api/vendor-timelines/${ev.id}`);
        await loadTimeline();
        await loadVendor();
    } catch (e) {
        ppmsToastError(getApiErrorMessage(e));
    }
}

async function onDeleteReview(r) {
    const ok = await ppmsConfirm(t('vendors.reviewDelete'), { title: t('vendors.reviewDelete') });
    if (!ok) return;
    try {
        await axios.delete(`/api/vendor-reviews/${r.id}`);
        await loadVendor();
    } catch (e) {
        ppmsToastError(getApiErrorMessage(e));
    }
}

watch(
    () => route.params.id,
    () => {
        deptMenuOpen.value = false;
        loadVendor();
    }
);

onMounted(async () => {
    document.addEventListener('click', onDocClickDept);
    try {
        const u = await axios.get('/api/user');
        me.value = u.data;
    } catch {
        me.value = null;
    }
    await loadLookups();
    await loadVendor();
});

onUnmounted(() => {
    document.removeEventListener('click', onDocClickDept);
});
</script>

<style scoped>
/* —— Vendor detail: page header (back · title · meta · actions · badges) —— */
.vm-detail__header {
    margin: 0 0 1.25rem;
    padding: 1rem 1.15rem 1rem;
    border-radius: 14px;
    border: 1px solid var(--ppms-border, #e2e8f0);
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    box-sizing: border-box;
}

.vm-detail__toprow {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: 0.75rem 1.25rem;
}

.vm-detail__back {
    flex-shrink: 0;
    align-self: center;
    margin-top: 0.2rem;
    margin-bottom: 0;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    color: var(--ppms-primary, #2563eb);
    border-bottom: 1px solid transparent;
    transition:
        color 0.15s ease,
        border-color 0.15s ease;
}

.vm-detail__back:hover {
    color: #1d4ed8;
    border-bottom-color: rgba(37, 99, 235, 0.45);
}

.vm-detail__back:focus-visible {
    outline: 2px solid var(--ppms-focus, #2563eb);
    outline-offset: 3px;
    border-radius: 4px;
}

.vm-detail__head-main {
    flex: 1 1 16rem;
    min-width: 0;
}

.vm-detail__toolbar-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    justify-content: flex-end;
    margin-left: auto;
    padding: 0.2rem 0;
    padding-left: 0.5rem;
    border-left: 1px solid var(--ppms-border-subtle, #e8ecf0);
}

@media (max-width: 640px) {
    .vm-detail__toolbar-actions {
        flex: 1 1 100%;
        margin-left: 0;
        padding-left: 0;
        border-left: none;
        border-top: 1px solid var(--ppms-border-subtle, #e8ecf0);
        padding-top: 0.65rem;
        justify-content: flex-start;
    }
}

.vm-detail__inline-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--ppms-border-subtle, #e8ecf0);
}

.vm-detail__title {
    margin: 0 0 0.35rem;
    font-size: clamp(1.35rem, 2.5vw, 1.65rem);
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: -0.02em;
    color: var(--ppms-text, #0f172a);
}

.vm-detail__meta {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 0.25rem 0.5rem;
    margin: 0;
    font-size: 0.8125rem;
    line-height: 1.5;
    color: var(--ppms-muted, #64748b);
}

.vm-detail__meta-label {
    flex-shrink: 0;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--ppms-text-muted, #64748b);
}

.vm-detail__meta-time {
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--ppms-text, #1e293b);
}

.vm-detail__meta-sep {
    margin: 0 0.15rem;
    color: var(--ppms-muted, #94a3b8);
    font-weight: 400;
    user-select: none;
}

.vm-detail__meta-by {
    font-size: 0.8125rem;
    color: var(--ppms-text-muted, #475569);
}

.vm-detail__badges {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    margin: 0.85rem 0 0;
    padding-top: 0.85rem;
    border-top: 1px solid var(--ppms-border-subtle, #e8ecf0);
}

.vm-pill {
    display: inline-flex;
    align-items: center;
    max-width: 100%;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    border: 1px solid #cbd5e1;
    background: #f1f5f9;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1.3;
    color: var(--ppms-text, #0f172a);
}

.vm-pill--muted {
    border-color: #e2e8f0;
    background: #fff;
    color: var(--ppms-text-muted, #475569);
    font-weight: 500;
}

.vm-pill--score {
    border-color: #93c5fd;
    background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
    color: #1e40af;
    font-weight: 700;
}
.vm-empty {
    cursor: help;
    color: var(--ppms-muted, #94a3b8);
    font-style: italic;
    border-bottom: 1px dashed var(--ppms-border, #cbd5e1);
}

.vm-panel {
    scroll-margin-top: 0.5rem;
}

.vm-detail__full {
    width: 100%;
    max-width: none;
}

.vm-sec-title--main {
    margin: 0 0 1rem;
    font-size: clamp(1.2rem, 2vw, 1.45rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--ppms-text, #0f172a);
}

.vm-panel--full {
    width: 100%;
}

.vm-kv--comfort th,
.vm-kv--comfort td {
    padding: 0.7rem 0.9rem;
}

.vm-kv--comfort th {
    width: min(14rem, 34%);
    font-size: 0.9375rem;
}

.vm-kv--comfort .ppms-input:not(textarea) {
    min-height: 2.85rem;
}

.vm-kv--comfort .ppms-input,
.vm-kv--comfort .vm-kv__select {
    font-size: 1rem;
    border-radius: 10px;
}

.vm-kv--comfort .vm-kv__control--textarea {
    min-height: 5.5rem;
}

.vm-kv__select {
    appearance: none;
    cursor: pointer;
    padding-right: 2.35rem;
    background-color: #fff;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 0.65rem;
}

.vm-dept-multi {
    position: relative;
    width: 100%;
    max-width: 38rem;
}

.vm-dept-multi__trigger {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    min-height: 2.85rem;
    padding: 0.55rem 0.85rem;
    border-radius: 10px;
    border: 1px solid var(--ppms-border, #e2e8f0);
    background: #fff;
    font: inherit;
    font-size: 1rem;
    line-height: 1.35;
    text-align: left;
    color: var(--ppms-text, #0f172a);
    cursor: pointer;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}

.vm-dept-multi__trigger:hover {
    border-color: #cbd5e1;
}

.vm-dept-multi__trigger:focus-visible {
    outline: 2px solid var(--ppms-focus, #2563eb);
    outline-offset: 2px;
}

.vm-dept-multi__trigger--open {
    border-color: var(--ppms-primary, #2563eb);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18);
}

.vm-dept-multi__trigger-text {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vm-dept-multi__chev {
    flex-shrink: 0;
    font-size: 0.7rem;
    opacity: 0.75;
}

.vm-dept-multi__panel {
    position: absolute;
    z-index: 30;
    left: 0;
    right: 0;
    margin-top: 0.35rem;
    max-height: 16rem;
    overflow-y: auto;
    padding: 0.35rem;
    border-radius: 10px;
    border: 1px solid var(--ppms-border, #e2e8f0);
    background: #fff;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.12);
}

.vm-dept-multi__option {
    display: flex;
    align-items: flex-start;
    gap: 0.55rem;
    padding: 0.5rem 0.55rem;
    border-radius: 8px;
    font-size: 0.9375rem;
    line-height: 1.35;
    cursor: pointer;
    color: var(--ppms-text, #0f172a);
}

.vm-dept-multi__option:hover {
    background: #f8fafc;
}

.vm-dept-multi__option input {
    margin-top: 0.15rem;
    width: 1.1rem;
    height: 1.1rem;
    flex-shrink: 0;
    cursor: pointer;
}

.vm-dept-multi__empty {
    margin: 0.4rem 0.5rem 0.35rem;
    font-size: 0.875rem;
    color: var(--ppms-muted, #64748b);
}

.vm-kv {
    width: 100%;
    border-collapse: collapse;
}
.vm-kv th,
.vm-kv td {
    text-align: left;
    padding: 0.5rem 0.65rem;
    vertical-align: top;
    border-bottom: 1px solid var(--ppms-border, #e8ecf0);
}
.vm-kv th {
    width: 200px;
    color: var(--ppms-muted, #5c6470);
    font-weight: 600;
    font-size: 0.875rem;
}
.vm-kv tr:last-child th,
.vm-kv tr:last-child td {
    border-bottom: none;
}
.vm-kv--editing td {
    vertical-align: middle;
}
.vm-kv__control {
    width: 100%;
    min-width: 0;
    max-width: 100%;
    box-sizing: border-box;
}
.vm-kv__control--textarea {
    min-height: 4.25rem;
    resize: vertical;
    display: block;
}
.vm-kv__control--multiselect {
    min-height: 8rem;
    padding: 0.55rem 0.5rem;
    overflow-y: auto;
}
.vm-kv__group-title {
    padding-top: 0.85rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--ppms-muted, #64748b);
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.9) 0%, rgba(241, 245, 249, 0.5) 100%);
    border-bottom: 1px solid var(--ppms-border, #e8ecf0);
}
.vm-kv__section-hint {
    margin: 0;
    padding: 0.35rem 0.65rem 0.65rem;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: var(--ppms-muted, #64748b);
    background: rgba(248, 250, 252, 0.65);
    border-bottom: 1px solid var(--ppms-border, #e8ecf0);
}
.vm-kv__foot {
    margin: 0.45rem 0 0;
    font-size: 0.75rem;
    color: var(--ppms-muted, #64748b);
}
.vm-pre {
    white-space: pre-wrap;
}
.vm-sec-title {
    margin: 0 0 0.75rem;
    font-size: 1.05rem;
}
.vm-product-list {
    margin: 0.5rem 0 0;
    padding-left: 1.2rem;
}
.vm-contracts-intro {
    margin: -0.25rem 0 1rem;
    font-size: 0.875rem;
    line-height: 1.5;
    color: var(--ppms-muted, #64748b);
}
.vm-contracts-empty {
    margin: 0.5rem 0 0;
    font-size: 0.875rem;
}
.vm-contracts-table-wrap {
    overflow-x: auto;
}
.vm-contracts-table {
    font-size: 0.875rem;
}
.vm-contracts-table__num {
    text-align: right;
    white-space: nowrap;
}
.vm-contracts-table__act {
    width: 1%;
    white-space: nowrap;
}
.vm-contracts-code {
    font-weight: 600;
    color: var(--ppms-text, #0f172a);
}
.vm-contracts-status {
    display: inline-block;
    padding: 0.12rem 0.45rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 600;
    background: #f1f5f9;
    color: #334155;
}
.vm-contracts-period {
    color: var(--ppms-text, #0f172a);
    white-space: nowrap;
}
.vm-timeline-add {
    margin-bottom: 1.35rem;
    padding: 1rem 1.1rem;
    border-radius: 10px;
    background: linear-gradient(180deg, #fafbfc 0%, #f8fafc 100%);
    border: 1px solid var(--ppms-border, #e2e8f0);
}
.vm-timeline-add__intro {
    margin: -0.15rem 0 1rem;
    font-size: 0.875rem;
    line-height: 1.5;
    color: var(--ppms-muted, #64748b);
}
.vm-timeline-add__grid {
    display: grid;
    gap: 1rem 1.1rem;
    grid-template-columns: 1fr;
}
@media (min-width: 720px) {
    .vm-timeline-add__grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .vm-field--timeline-cb {
        align-self: end;
        padding-bottom: 0.35rem;
    }
}
.vm-field--timeline-note {
    margin-top: 0.25rem;
}
.vm-timeline-add__actions {
    margin-top: 1rem;
    padding-top: 0.85rem;
    border-top: 1px solid var(--ppms-border, #e2e8f0);
}
.vm-dept-badge {
    display: inline-block;
    margin: 0 0.35rem 0.35rem 0;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    background: #e0e7ff;
    color: #3730a3;
    font-size: 0.85rem;
}

/* —— Edit form —— */
.vm-edit-form {
    padding-top: 0.25rem;
}
.vm-edit-form--compact {
    padding-top: 0;
}
.vm-edit-banner {
    margin: 0 0 1.1rem;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
    border: 1px solid #c7d2fe;
}
.vm-edit-banner__title {
    display: block;
    font-size: 0.95rem;
    color: #312e81;
}
.vm-edit-banner__hint {
    margin: 0.35rem 0 0;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: #4338ca;
    opacity: 0.95;
}
.vm-form-section {
    margin-bottom: 1.35rem;
}

.vm-form-section:last-child {
    margin-bottom: 0;
}
.vm-form-section__title {
    margin: 0 0 0.65rem;
    font-size: 0.8125rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--ppms-muted, #64748b);
}
.vm-form-section__intro {
    margin: 0 0 0.65rem;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: var(--ppms-muted, #64748b);
}
.vm-form-footnote {
    margin: 0.5rem 0 0;
    font-size: 0.8125rem;
    color: var(--ppms-muted, #64748b);
}

.vm-form-grid {
    display: grid;
    gap: 1rem 1.1rem;
    align-items: start;
}
.vm-form-grid--2 {
    grid-template-columns: 1fr;
}
.vm-form-grid--3 {
    grid-template-columns: 1fr;
}
.vm-form-grid--4 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
@media (min-width: 640px) {
    .vm-form-grid--2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .vm-form-grid--3 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (min-width: 900px) {
    .vm-form-grid--3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .vm-form-grid--4 {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

.vm-field {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}
.vm-field--full {
    grid-column: 1 / -1;
}
.vm-field--span-2 {
    grid-column: span 2;
}
@media (max-width: 639px) {
    .vm-field--span-2 {
        grid-column: span 1;
    }
}
.vm-field--max-md .vm-field__control {
    max-width: 22rem;
}
.vm-field__label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--ppms-text, #0f172a);
}
.vm-field__hint {
    margin: 0;
    font-size: 0.75rem;
    line-height: 1.4;
    color: var(--ppms-muted, #64748b);
}
.vm-field__hint--block {
    margin-bottom: 0.35rem;
}
.vm-field__control {
    width: 100%;
    min-height: 2.5rem;
    box-sizing: border-box;
}
.vm-field__control--multiselect {
    min-height: 8rem;
    padding: 0.55rem 0.5rem;
    overflow-y: auto;
}
textarea.vm-field__control {
    min-height: 5rem;
    resize: vertical;
}
.vm-field--checkbox {
    justify-content: flex-end;
    padding-bottom: 0.25rem;
}
.vm-field__checkbox {
    display: inline-flex;
    align-items: flex-start;
    gap: 0.5rem;
    flex-wrap: wrap;
    font-size: 0.875rem;
    color: var(--ppms-text, #0f172a);
    cursor: pointer;
}
.vm-field__checkbox input {
    margin-top: 0.2rem;
    flex-shrink: 0;
}

/* —— Polished surface (vendor detail) —— */
.vm-detail--surface {
    --vm-radius-lg: 16px;
    --vm-radius-md: 12px;
    --vm-line: rgba(15, 23, 42, 0.07);
    --vm-line-soft: rgba(15, 23, 42, 0.045);
    --vm-shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.05);
    --vm-shadow-md: 0 4px 14px rgba(15, 23, 42, 0.07);
    --vm-input-bg: #f8fafc;
    --vm-input-border: #e2e8f0;
    --vm-input-focus: rgba(37, 99, 235, 0.22);
    max-width: 72rem;
    margin-left: auto;
    margin-right: auto;
    padding-bottom: 2rem;
}

.vm-detail--surface .vm-detail__header {
    margin: 0 0 1.5rem;
    padding: 1.35rem 1.5rem 1.25rem;
    border-radius: var(--vm-radius-lg);
    border: 1px solid var(--vm-line);
    background:
        radial-gradient(120% 140% at 0% 0%, rgba(59, 130, 246, 0.07) 0%, transparent 55%),
        radial-gradient(90% 100% at 100% 0%, rgba(99, 102, 241, 0.06) 0%, transparent 50%),
        linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: var(--vm-shadow-sm);
    position: relative;
    overflow: hidden;
}

.vm-detail--surface .vm-detail__header::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 3px;
    background: linear-gradient(90deg, #3b82f6 0%, #6366f1 42%, #8b5cf6 100%);
    opacity: 0.85;
    border-radius: 0 0 var(--vm-radius-lg) var(--vm-radius-lg);
}

.vm-detail--surface .vm-detail__title {
    margin-bottom: 0.5rem;
    font-size: clamp(1.45rem, 2.8vw, 1.85rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.18;
    color: #0f172a;
}

.vm-detail--surface .vm-detail__badges {
    margin-top: 0.75rem;
    padding-top: 0.85rem;
    border-top: 1px solid var(--vm-line-soft);
    gap: 0.45rem;
}

.vm-detail--surface .vm-pill {
    border: 1px solid #e2e8f0;
    background: #fff;
    box-shadow: 0 1px 0 rgba(15, 23, 42, 0.04);
    padding: 0.4rem 0.85rem;
    font-size: 0.8125rem;
    letter-spacing: 0.01em;
}

.vm-detail--surface .vm-pill--muted {
    background: #f1f5f9;
    border-color: #e2e8f0;
    color: #475569;
}

.vm-detail--surface .vm-pill--score {
    border-color: #bfdbfe;
    background: linear-gradient(180deg, #f0f9ff 0%, #e0f2fe 100%);
    color: #1d4ed8;
    box-shadow: 0 1px 2px rgba(37, 99, 235, 0.12);
}

.vm-detail--surface .ppms-card {
    border: 1px solid var(--vm-line) !important;
    border-radius: var(--vm-radius-lg) !important;
    background: #fff !important;
    box-shadow: var(--vm-shadow-md) !important;
    padding: 1.35rem 1.4rem 1.5rem !important;
}

.vm-detail--surface .vm-sec-title--main {
    margin: 0 0 1.15rem;
    padding-bottom: 0.65rem;
    border-bottom: 1px solid var(--vm-line-soft);
    font-size: clamp(1.15rem, 2vw, 1.35rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    color: #0f172a;
}

.vm-detail--surface .vm-kv th,
.vm-detail--surface .vm-kv td {
    border-bottom: 1px solid var(--vm-line-soft);
}

.vm-detail--surface .vm-kv th {
    color: #64748b;
    font-weight: 600;
    font-size: 0.875rem;
    letter-spacing: 0.01em;
}

.vm-detail--surface .vm-kv td {
    color: #1e293b;
    font-size: 0.9375rem;
    line-height: 1.55;
}

.vm-detail--surface .vm-kv tr:last-child th,
.vm-detail--surface .vm-kv tr:last-child td {
    border-bottom: none;
}

.vm-detail--surface .vm-kv__group-title {
    padding-top: 1.1rem;
    padding-bottom: 0.35rem;
    border-bottom: 1px solid var(--vm-line);
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.95) 0%, rgba(241, 245, 249, 0.5) 100%);
    color: #64748b;
    letter-spacing: 0.06em;
}

.vm-detail--surface .vm-kv__section-hint {
    background: rgba(248, 250, 252, 0.9);
    border-bottom: 1px solid var(--vm-line-soft);
    border-radius: 0 0 var(--vm-radius-md) var(--vm-radius-md);
}

.vm-detail--surface .vm-kv td a {
    color: #2563eb;
    font-weight: 500;
    text-decoration: none;
    border-bottom: 1px solid rgba(37, 99, 235, 0.35);
    transition:
        color 0.15s ease,
        border-color 0.15s ease;
}

.vm-detail--surface .vm-kv td a:hover {
    color: #1d4ed8;
    border-bottom-color: rgba(29, 78, 216, 0.55);
}

.vm-detail--surface .ppms-input,
.vm-detail--surface .vm-kv__select,
.vm-detail--surface textarea.ppms-input {
    border: 1px solid var(--vm-input-border);
    background: var(--vm-input-bg);
    border-radius: var(--vm-radius-md);
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease,
        background 0.15s ease;
}

.vm-detail--surface .ppms-input:hover,
.vm-detail--surface .vm-kv__select:hover,
.vm-detail--surface textarea.ppms-input:hover {
    border-color: #cbd5e1;
    background: #fff;
}

.vm-detail--surface .ppms-input:focus,
.vm-detail--surface .vm-kv__select:focus,
.vm-detail--surface textarea.ppms-input:focus {
    outline: none;
    border-color: #3b82f6;
    background: #fff;
    box-shadow: 0 0 0 3px var(--vm-input-focus);
}

.vm-detail--surface .vm-dept-multi__trigger {
    border: 1px solid var(--vm-input-border);
    background: var(--vm-input-bg);
    border-radius: var(--vm-radius-md);
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}

.vm-detail--surface .vm-dept-multi__trigger:hover {
    border-color: #cbd5e1;
    background: #fff;
}

.vm-detail--surface .vm-dept-multi__trigger--open {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px var(--vm-input-focus);
}

.vm-detail--surface .vm-detail__inline-actions {
    margin-top: 1.25rem;
    padding-top: 1.1rem;
    border-top: 1px solid var(--vm-line-soft);
}

.vm-detail--surface .vm-empty {
    border-bottom-color: #cbd5e1;
    color: #94a3b8;
}

.vm-detail--surface .vm-timeline-add {
    border-radius: var(--vm-radius-md);
    border-color: var(--vm-line);
    background: linear-gradient(180deg, #fafbfc 0%, #f4f6f9 100%);
}

.vm-detail--surface .vm-dept-badge {
    background: #eef2ff;
    border: 1px solid #c7d2fe;
    padding: 0.2rem 0.55rem;
    font-weight: 500;
}

/* —— Overview: features / services / quick links / note —— */
.vm-overview-surface {
    margin: 0 0 1.35rem;
    display: flex;
    flex-direction: column;
    gap: 1.15rem;
}

.vm-overview-note--inline {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 0.35rem 0.6rem;
    padding: 0.95rem 1.1rem 0.95rem 1rem;
    border-radius: 14px;
    border: 1px solid rgba(251, 191, 36, 0.45);
    background:
        linear-gradient(135deg, rgba(255, 251, 235, 0.98) 0%, rgba(254, 243, 199, 0.55) 42%, rgba(255, 255, 255, 0.92) 100%);
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.85) inset,
        0 8px 24px rgba(146, 64, 14, 0.07);
    border-left: 4px solid #f59e0b;
}

.vm-overview-note__inline-k {
    flex-shrink: 0;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #b45309;
}

.vm-overview-note__inline-t {
    flex: 1 1 12rem;
    min-width: 0;
    font-size: 0.9rem;
    line-height: 1.55;
    color: #78350f;
    white-space: pre-wrap;
}

.vm-overview-grid {
    display: grid;
    gap: 1.15rem;
    grid-template-columns: 1fr;
}

@media (min-width: 720px) {
    .vm-overview-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-items: stretch;
    }
}

.vm-overview-card {
    position: relative;
    display: flex;
    flex-direction: column;
    min-height: 0;
    border-radius: 18px;
    padding: 1.15rem 1.2rem 1rem;
    border: 1px solid rgba(15, 23, 42, 0.07);
    background:
        linear-gradient(155deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.96) 55%, rgba(241, 245, 249, 0.88) 100%);
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.9) inset,
        0 12px 32px rgba(15, 23, 42, 0.06),
        0 2px 6px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

.vm-overview-card::before {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    height: 3px;
    border-radius: 18px 18px 0 0;
    pointer-events: none;
}

.vm-overview-card--features::before {
    background: linear-gradient(90deg, #2563eb, #38bdf8, #0ea5e9);
}

.vm-overview-card--services::before {
    background: linear-gradient(90deg, #7c3aed, #a78bfa, #c084fc);
}

.vm-overview-card--features {
    border-color: rgba(37, 99, 235, 0.14);
}

.vm-overview-card--services {
    border-color: rgba(124, 58, 237, 0.14);
}

.vm-overview-card__head {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    margin-bottom: 0.85rem;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid rgba(15, 23, 42, 0.06);
}

.vm-overview-card__badge {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.65rem;
    height: 2.65rem;
    border-radius: 14px;
    background: linear-gradient(145deg, #eff6ff 0%, #dbeafe 100%);
    border: 1px solid rgba(59, 130, 246, 0.25);
    color: #1d4ed8;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);
}

.vm-overview-card__badge--svc {
    background: linear-gradient(145deg, #f5f3ff 0%, #ede9fe 100%);
    border-color: rgba(124, 58, 237, 0.28);
    color: #6d28d9;
    box-shadow: 0 4px 12px rgba(109, 40, 217, 0.12);
}

.vm-overview-card__ico {
    width: 1.35rem;
    height: 1.35rem;
}

.vm-overview-card__head-text {
    flex: 1;
    min-width: 0;
}

.vm-overview-card__title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.25;
    color: #0f172a;
}

.vm-overview-card__hint {
    margin: 0.35rem 0 0;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: #64748b;
}

.vm-overview-card__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    flex: 1 1 auto;
    min-height: 0;
}

.vm-overview-card__li {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    padding: 0.55rem 0.65rem;
    border-radius: 11px;
    font-size: 0.9rem;
    line-height: 1.5;
    color: #1e293b;
    background: rgba(255, 255, 255, 0.55);
    border: 1px solid rgba(15, 23, 42, 0.05);
    transition:
        background 0.15s ease,
        border-color 0.15s ease;
}

.vm-overview-card__li:hover {
    background: rgba(255, 255, 255, 0.95);
    border-color: rgba(15, 23, 42, 0.08);
}

.vm-overview-card--features .vm-overview-card__li:nth-child(even) {
    background: rgba(239, 246, 255, 0.45);
}

.vm-overview-card--services .vm-overview-card__li:nth-child(even) {
    background: rgba(245, 243, 255, 0.5);
}

.vm-overview-card__txt {
    flex: 1;
    min-width: 0;
}

.vm-overview-card__check {
    flex-shrink: 0;
    margin-top: 0.2rem;
    width: 1.1rem;
    height: 1.1rem;
    border-radius: 999px;
    background: linear-gradient(145deg, #3b82f6, #2563eb);
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35);
    position: relative;
}

.vm-overview-card__check::after {
    content: '';
    position: absolute;
    left: 50%;
    top: 50%;
    width: 0.35rem;
    height: 0.2rem;
    border: 2px solid #fff;
    border-top: none;
    border-right: none;
    transform: translate(-50%, -60%) rotate(-45deg);
}

.vm-overview-card__dot {
    flex-shrink: 0;
    margin-top: 0.45rem;
    width: 0.45rem;
    height: 0.45rem;
    border-radius: 999px;
    background: linear-gradient(145deg, #8b5cf6, #6d28d9);
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
}

.vm-overview-pager {
    margin-top: 0.85rem;
    padding-top: 0.75rem;
    border-top: 1px dashed rgba(15, 23, 42, 0.1);
}

.vm-overview-pager__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem 0.85rem;
}

.vm-overview-pager__pp {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    margin: 0;
    font-size: 0.78rem;
    font-weight: 600;
    color: #475569;
}

.vm-overview-pager__pp-lbl {
    white-space: nowrap;
}

.vm-overview-pager__select {
    appearance: none;
    cursor: pointer;
    padding: 0.35rem 1.75rem 0.35rem 0.6rem;
    font: inherit;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #0f172a;
    border-radius: 9px;
    border: 1px solid rgba(15, 23, 42, 0.12);
    background:
        #fff
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E")
        no-repeat right 0.45rem center;
    background-size: 12px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
}

.vm-overview-pager__select:hover {
    border-color: rgba(37, 99, 235, 0.35);
}

.vm-overview-pager__select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.vm-overview-pager__range {
    font-size: 0.78rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: #64748b;
    letter-spacing: 0.02em;
}

.vm-overview-pager__nav {
    display: inline-flex;
    gap: 0.35rem;
}

.vm-overview-pager__btn {
    cursor: pointer;
    padding: 0.4rem 0.85rem;
    font: inherit;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    border-radius: 999px;
    border: 1px solid rgba(15, 23, 42, 0.12);
    background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
    color: #334155;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease,
        color 0.15s ease;
}

.vm-overview-pager__btn:hover:not(:disabled) {
    border-color: rgba(37, 99, 235, 0.4);
    color: #1d4ed8;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.12);
}

.vm-overview-pager__btn:disabled {
    cursor: not-allowed;
    opacity: 0.42;
    box-shadow: none;
}

@media (max-width: 520px) {
    .vm-overview-pager__row {
        flex-direction: column;
        align-items: stretch;
    }

    .vm-overview-pager__nav {
        justify-content: flex-end;
    }
}

.vm-overview-note:not(.vm-overview-note--inline) {
    padding: 0.85rem 1rem;
    border-radius: 12px;
    border: 1px solid #fde68a;
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 45%, #fff 100%);
}

.vm-overview-note__k {
    display: block;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #92400e;
    margin-bottom: 0.35rem;
}

.vm-overview-note__txt {
    margin: 0;
    font-size: 0.875rem;
    line-height: 1.5;
    color: #78350f;
    white-space: pre-wrap;
}

@media (max-width: 640px) {
    .vm-detail--surface .vm-detail__header {
        padding: 1.1rem 1.15rem;
    }

    .vm-detail--surface .ppms-card {
        padding: 1.1rem 1rem 1.2rem !important;
    }
}
</style>
