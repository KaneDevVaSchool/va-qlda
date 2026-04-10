<template>
    <div class="ppms-page vm-detail">
        <div v-if="loading" class="ppms-loading-line" role="status">{{ t('common.loading') }}</div>
        <p v-else-if="err" class="ppms-error">{{ err }}</p>
        <template v-else-if="vendor">
            <header class="vm-detail__header">
                <div class="vm-detail__toprow">
                    <router-link to="/vendors" class="ppms-back vm-detail__back">{{ t('vendors.backToVendors') }}</router-link>
                    <div class="vm-detail__head-main">
                        <h1 class="vm-detail__title">{{ vendor.name }}</h1>
                        <p v-if="vendor.updated_at" class="vm-detail__meta">
                            <span class="vm-detail__meta-label">{{ t('vendors.metaLastUpdated') }}</span>
                            <time class="vm-detail__meta-time" :datetime="vendor.updated_at">{{ formatVendorDateTime(vendor.updated_at) }}</time>
                            <template v-if="vendor.updated_by?.name">
                                <span class="vm-detail__meta-sep" aria-hidden="true">·</span>
                                <span class="vm-detail__meta-by">{{ t('vendors.metaBy') }} {{ vendor.updated_by.name }}</span>
                            </template>
                        </p>
                    </div>
                    <div v-if="canEdit || canDelete" class="vm-detail__toolbar-actions">
                        <template v-if="canEdit && canEditCurrentTab">
                            <button
                                v-if="!editingTab"
                                type="button"
                                class="ppms-btn-ghost"
                                @click="startEditTab"
                            >
                                {{ t('vendors.edit') }}
                            </button>
                            <template v-else-if="editingTab === tab">
                                <button type="button" class="ppms-btn-primary" :disabled="saving" @click="saveTab">
                                    {{ t('vendors.save') }}
                                </button>
                                <button type="button" class="ppms-btn-ghost" :disabled="saving" @click="cancelEditTab">
                                    {{ t('vendors.cancel') }}
                                </button>
                            </template>
                        </template>
                        <button
                            v-if="canDelete"
                            type="button"
                            class="ppms-btn-ghost ppms-btn-danger"
                            @click="removeVendor"
                        >
                            {{ t('vendors.deleteVendor') }}
                        </button>
                    </div>
                </div>
                <div class="vm-detail__badges">
                    <span class="vm-pill">{{ kindLabel(vendor.kind) }}</span>
                    <span class="vm-pill vm-pill--muted">{{ statusLabel(vendor.status) }}</span>
                    <span v-if="vendor.vendor_score" class="vm-pill vm-pill--score">{{ t('vendors.vendorScore') }}: {{ vendor.vendor_score }}</span>
                </div>
            </header>

            <nav class="vm-tabs" role="tablist" :aria-label="t('vendors.detailTitle')">
                <button
                    id="vm-tab-overview"
                    type="button"
                    role="tab"
                    class="vm-tab"
                    :class="{ 'vm-tab--active': tab === 'overview' }"
                    :aria-selected="tab === 'overview'"
                    :tabindex="tab === 'overview' ? 0 : -1"
                    @click="tab = 'overview'"
                >
                    <span class="vm-tab__label">{{ t('vendors.tabOverview') }}</span>
                    <span class="vm-tab__hint">{{ t('vendors.tabOverviewHint') }}</span>
                </button>
                <button
                    id="vm-tab-business"
                    type="button"
                    role="tab"
                    class="vm-tab"
                    :class="{ 'vm-tab--active': tab === 'business' }"
                    :aria-selected="tab === 'business'"
                    :tabindex="tab === 'business' ? 0 : -1"
                    @click="tab = 'business'"
                >
                    <span class="vm-tab__label">{{ t('vendors.tabBusiness') }}</span>
                    <span class="vm-tab__hint">{{ t('vendors.tabBusinessHint') }}</span>
                </button>
                <button
                    id="vm-tab-contracts"
                    type="button"
                    role="tab"
                    class="vm-tab"
                    :class="{ 'vm-tab--active': tab === 'contracts' }"
                    :aria-selected="tab === 'contracts'"
                    :tabindex="tab === 'contracts' ? 0 : -1"
                    @click="tab = 'contracts'"
                >
                    <span class="vm-tab__label">{{ t('vendors.tabContracts') }}</span>
                    <span class="vm-tab__hint">{{ t('vendors.tabContractsHint') }}</span>
                </button>
                <button
                    id="vm-tab-reviews"
                    type="button"
                    role="tab"
                    class="vm-tab"
                    :class="{ 'vm-tab--active': tab === 'reviews' }"
                    :aria-selected="tab === 'reviews'"
                    :tabindex="tab === 'reviews' ? 0 : -1"
                    @click="tab = 'reviews'"
                >
                    <span class="vm-tab__label">{{ t('vendors.tabReviews') }}</span>
                    <span class="vm-tab__hint">{{ t('vendors.tabReviewsHint') }}</span>
                </button>
                <button
                    id="vm-tab-timeline"
                    type="button"
                    role="tab"
                    class="vm-tab"
                    :class="{ 'vm-tab--active': tab === 'timeline' }"
                    :aria-selected="tab === 'timeline'"
                    :tabindex="tab === 'timeline' ? 0 : -1"
                    @click="openTimeline"
                >
                    <span class="vm-tab__label">{{ t('vendors.tabTimeline') }}</span>
                    <span class="vm-tab__hint">{{ t('vendors.tabTimelineHint') }}</span>
                </button>
            </nav>

            <section
                v-show="tab === 'overview'"
                id="vm-panel-overview"
                class="ppms-card ppms-mt vm-panel"
                role="tabpanel"
                aria-labelledby="vm-tab-overview"
            >
                <h3 v-if="editingTab !== 'overview'" class="vm-sec-title">{{ t('vendors.tabOverview') }}</h3>
                <table v-if="editingTab !== 'overview'" class="vm-kv">
                    <tbody>
                        <tr>
                            <th>{{ t('vendors.legalName') }}</th>
                            <td>
                                <template v-if="!isEmptyText(vendor.legal_name)">{{ vendor.legal_name }}</template>
                                <span v-else class="vm-empty" :title="t('vendors.detailLegalNameHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ t('vendors.country') }}</th>
                            <td>
                                <template v-if="!isEmptyText(vendor.country)">{{ vendor.country }}</template>
                                <span v-else class="vm-empty" :title="t('vendors.detailCountryHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ t('vendors.website') }}</th>
                            <td>
                                <a v-if="!isEmptyText(vendor.website)" :href="vendor.website" target="_blank" rel="noopener">{{ vendor.website }}</a>
                                <span v-else class="vm-empty" :title="t('vendors.detailWebsiteHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ t('vendors.taxCode') }}</th>
                            <td>
                                <template v-if="!isEmptyText(vendor.tax_code)">{{ vendor.tax_code }}</template>
                                <span v-else class="vm-empty" :title="t('vendors.detailTaxHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ t('vendors.contactInfo') }}</th>
                            <td class="vm-pre">
                                <template v-if="!isEmptyText(vendor.contact_info)">{{ vendor.contact_info }}</template>
                                <span v-else class="vm-empty" :title="t('vendors.detailContactHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ t('vendors.riskLevel') }}</th>
                            <td>
                                <template v-if="vendor.risk_level">{{ riskLabel(vendor.risk_level) }}</template>
                                <span v-else class="vm-empty" :title="t('vendors.detailRiskHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ t('vendors.internalNote') }}</th>
                            <td class="vm-pre">
                                <template v-if="!isEmptyText(vendor.internal_note)">{{ vendor.internal_note }}</template>
                                <span v-else class="vm-empty" :title="t('vendors.detailInternalNoteHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ t('vendors.departments') }}</th>
                            <td>
                                <span v-for="d in vendor.departments || []" :key="d.id" class="vm-dept-badge">{{ d.name }}</span>
                                <span v-if="!(vendor.departments || []).length" class="vm-empty" :title="t('vendors.emptyDepartmentsHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-else class="vm-edit-form">
                    <h3 class="vm-sec-title vm-sec-title--panel">{{ t('vendors.tabOverview') }}</h3>
                    <div class="vm-edit-banner" role="status">
                        <strong class="vm-edit-banner__title">{{ t('vendors.editModeBanner') }}</strong>
                        <p class="vm-edit-banner__hint">{{ t('vendors.editModeBannerOverview') }}</p>
                    </div>
                    <section class="vm-form-section">
                        <h3 class="vm-form-section__title">{{ t('vendors.formSectionOverview') }}</h3>
                        <div class="vm-form-grid vm-form-grid--2">
                            <div class="vm-field">
                                <label class="vm-field__label" for="vm-o-legal">{{ t('vendors.legalName') }}</label>
                                <p id="vm-o-legal-hint" class="vm-field__hint">{{ t('vendors.detailLegalNameHint') }}</p>
                                <input
                                    id="vm-o-legal"
                                    v-model="edit.legal_name"
                                    class="ppms-input vm-field__control"
                                    :placeholder="t('vendors.detailLegalNamePlaceholder')"
                                    aria-describedby="vm-o-legal-hint"
                                />
                            </div>
                            <div class="vm-field">
                                <label class="vm-field__label" for="vm-o-country">{{ t('vendors.country') }}</label>
                                <p id="vm-o-country-hint" class="vm-field__hint">{{ t('vendors.detailCountryHint') }}</p>
                                <input
                                    id="vm-o-country"
                                    v-model="edit.country"
                                    class="ppms-input vm-field__control"
                                    :placeholder="t('vendors.detailCountryPlaceholder')"
                                    autocomplete="country-name"
                                    aria-describedby="vm-o-country-hint"
                                />
                            </div>
                            <div class="vm-field">
                                <label class="vm-field__label" for="vm-o-web">{{ t('vendors.website') }}</label>
                                <p id="vm-o-web-hint" class="vm-field__hint">{{ t('vendors.detailWebsiteHint') }}</p>
                                <input
                                    id="vm-o-web"
                                    v-model="edit.website"
                                    type="url"
                                    class="ppms-input vm-field__control"
                                    :placeholder="t('vendors.detailWebsitePlaceholder')"
                                    aria-describedby="vm-o-web-hint"
                                />
                            </div>
                            <div class="vm-field">
                                <label class="vm-field__label" for="vm-o-tax">{{ t('vendors.taxCode') }}</label>
                                <p id="vm-o-tax-hint" class="vm-field__hint">{{ t('vendors.detailTaxHint') }}</p>
                                <input
                                    id="vm-o-tax"
                                    v-model="edit.tax_code"
                                    class="ppms-input vm-field__control"
                                    :placeholder="t('vendors.detailTaxPlaceholder')"
                                    aria-describedby="vm-o-tax-hint"
                                />
                            </div>
                            <div class="vm-field vm-field--full">
                                <label class="vm-field__label" for="vm-o-contact">{{ t('vendors.contactInfo') }}</label>
                                <p id="vm-o-contact-hint" class="vm-field__hint">{{ t('vendors.detailContactHint') }}</p>
                                <textarea
                                    id="vm-o-contact"
                                    v-model="edit.contact_info"
                                    rows="3"
                                    class="ppms-input vm-field__control"
                                    :placeholder="t('vendors.detailContactPlaceholder')"
                                    aria-describedby="vm-o-contact-hint"
                                />
                            </div>
                            <div class="vm-field vm-field--span-2 vm-field--max-md">
                                <label class="vm-field__label" for="vm-o-risk">{{ t('vendors.riskLevel') }}</label>
                                <p id="vm-o-risk-hint" class="vm-field__hint">{{ t('vendors.detailRiskHint') }}</p>
                                <select id="vm-o-risk" v-model="edit.risk_level" class="ppms-input vm-field__control" aria-describedby="vm-o-risk-hint">
                                    <option value="">{{ t('vendors.riskNotSet') }}</option>
                                    <option value="low">{{ t('vendors.riskLow') }}</option>
                                    <option value="medium">{{ t('vendors.riskMedium') }}</option>
                                    <option value="high">{{ t('vendors.riskHigh') }}</option>
                                </select>
                            </div>
                            <div class="vm-field vm-field--full">
                                <label class="vm-field__label" for="vm-o-note">{{ t('vendors.internalNote') }}</label>
                                <p id="vm-o-note-hint" class="vm-field__hint">{{ t('vendors.detailInternalNoteHint') }}</p>
                                <textarea
                                    id="vm-o-note"
                                    v-model="edit.internal_note"
                                    rows="4"
                                    class="ppms-input vm-field__control"
                                    :placeholder="t('vendors.detailInternalNotePlaceholder')"
                                    aria-describedby="vm-o-note-hint"
                                />
                            </div>
                        </div>
                    </section>
                </div>
            </section>

            <section
                v-show="tab === 'business'"
                id="vm-panel-business"
                class="ppms-card ppms-mt vm-panel"
                role="tabpanel"
                aria-labelledby="vm-tab-business"
            >
                <h3 v-if="editingTab !== 'business'" class="vm-sec-title">{{ t('vendors.tabBusiness') }}</h3>
                <table v-if="editingTab !== 'business'" class="vm-kv">
                    <tbody>
                        <tr>
                            <th>{{ t('vendors.filterIndustry') }}</th>
                            <td>
                                <template v-if="!isEmptyText(vendor.industry)">{{ vendor.industry }}</template>
                                <span v-else class="vm-empty" :title="t('vendors.detailIndustryHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ t('vendors.mainProducts') }}</th>
                            <td class="vm-pre">
                                <template v-if="!isEmptyText(vendor.main_products)">{{ vendor.main_products }}</template>
                                <span v-else class="vm-empty" :title="t('vendors.detailMainProductsHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ t('vendors.contractValue') }}</th>
                            <td>
                                <template v-if="!isEmptyNumber(vendor.contract_value)">{{ vendor.contract_value }}</template>
                                <span v-else class="vm-empty" :title="t('vendors.detailContractValueHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ t('vendors.estimatedCost') }}</th>
                            <td>
                                <template v-if="!isEmptyNumber(vendor.estimated_cost)">{{ vendor.estimated_cost }}</template>
                                <span v-else class="vm-empty" :title="t('vendors.detailEstimatedCostHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ t('vendors.referencePrice') }}</th>
                            <td>
                                <template v-if="!isEmptyNumber(vendor.reference_price)">{{ vendor.reference_price }}</template>
                                <span v-else class="vm-empty" :title="t('vendors.detailReferencePriceHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ t('vendors.researchSource') }}</th>
                            <td>
                                <template v-if="!isEmptyText(vendor.research_source)">{{ vendor.research_source }}</template>
                                <span v-else class="vm-empty" :title="t('vendors.detailResearchSourceHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ t('vendors.fitScore') }}</th>
                            <td>
                                <template v-if="!isEmptyNumber(vendor.fit_score)">{{ vendor.fit_score }}</template>
                                <span v-else class="vm-empty" :title="t('vendors.detailFitScoreHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ t('vendors.pros') }}</th>
                            <td class="vm-pre">
                                <template v-if="!isEmptyText(vendor.pros)">{{ vendor.pros }}</template>
                                <span v-else class="vm-empty" :title="t('vendors.detailProsPlaceholder')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ t('vendors.cons') }}</th>
                            <td class="vm-pre">
                                <template v-if="!isEmptyText(vendor.cons)">{{ vendor.cons }}</template>
                                <span v-else class="vm-empty" :title="t('vendors.detailConsPlaceholder')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ t('vendors.researchNote') }}</th>
                            <td class="vm-pre">
                                <template v-if="!isEmptyText(vendor.research_note)">{{ vendor.research_note }}</template>
                                <span v-else class="vm-empty" :title="t('vendors.detailResearchNoteHint')">{{ t('vendors.emptyFieldPlaceholder') }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-else class="vm-edit-form">
                    <h3 class="vm-sec-title vm-sec-title--panel">{{ t('vendors.tabBusiness') }}</h3>
                    <div class="vm-edit-banner" role="status">
                        <strong class="vm-edit-banner__title">{{ t('vendors.editModeBanner') }}</strong>
                        <p class="vm-edit-banner__hint">{{ t('vendors.editModeBannerBusiness') }}</p>
                    </div>
                    <section class="vm-form-section">
                        <h4 class="vm-form-section__title">{{ t('vendors.formSectionBiz') }}</h4>
                        <div class="vm-form-grid vm-form-grid--3">
                            <div class="vm-field">
                                <label class="vm-field__label" for="vm-b-ind">{{ t('vendors.filterIndustry') }}</label>
                                <p id="vm-b-ind-hint" class="vm-field__hint">{{ t('vendors.detailIndustryHint') }}</p>
                                <input
                                    id="vm-b-ind"
                                    v-model="edit.industry"
                                    class="ppms-input vm-field__control"
                                    :placeholder="t('vendors.detailIndustryPlaceholder')"
                                    aria-describedby="vm-b-ind-hint"
                                />
                            </div>
                            <div class="vm-field">
                                <label class="vm-field__label" for="vm-b-src">{{ t('vendors.researchSource') }}</label>
                                <p id="vm-b-src-hint" class="vm-field__hint">{{ t('vendors.detailResearchSourceHint') }}</p>
                                <input
                                    id="vm-b-src"
                                    v-model="edit.research_source"
                                    class="ppms-input vm-field__control"
                                    :placeholder="t('vendors.detailResearchSourcePlaceholder')"
                                    aria-describedby="vm-b-src-hint"
                                />
                            </div>
                            <div class="vm-field">
                                <label class="vm-field__label" for="vm-b-fit">{{ t('vendors.fitScore') }}</label>
                                <p id="vm-b-fit-hint" class="vm-field__hint">{{ t('vendors.detailFitScoreHint') }}</p>
                                <input
                                    id="vm-b-fit"
                                    v-model.number="edit.fit_score"
                                    type="number"
                                    min="0"
                                    max="100"
                                    class="ppms-input vm-field__control"
                                    :placeholder="t('vendors.detailFitScorePlaceholder')"
                                    aria-describedby="vm-b-fit-hint"
                                />
                            </div>
                        </div>
                    </section>
                    <section class="vm-form-section">
                        <h4 class="vm-form-section__title">{{ t('vendors.formSectionBizValue') }}</h4>
                        <div class="vm-form-grid vm-form-grid--3">
                            <div class="vm-field">
                                <label class="vm-field__label" for="vm-b-cv">{{ t('vendors.contractValue') }}</label>
                                <p id="vm-b-cv-hint" class="vm-field__hint">{{ t('vendors.detailContractValueHint') }}</p>
                                <input
                                    id="vm-b-cv"
                                    v-model="edit.contract_value"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="ppms-input vm-field__control"
                                    :placeholder="t('vendors.detailContractValuePlaceholder')"
                                    inputmode="decimal"
                                    aria-describedby="vm-b-cv-hint"
                                />
                            </div>
                            <div class="vm-field">
                                <label class="vm-field__label" for="vm-b-ec">{{ t('vendors.estimatedCost') }}</label>
                                <p id="vm-b-ec-hint" class="vm-field__hint">{{ t('vendors.detailEstimatedCostHint') }}</p>
                                <input
                                    id="vm-b-ec"
                                    v-model="edit.estimated_cost"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="ppms-input vm-field__control"
                                    :placeholder="t('vendors.detailEstimatedCostPlaceholder')"
                                    inputmode="decimal"
                                    aria-describedby="vm-b-ec-hint"
                                />
                            </div>
                            <div class="vm-field">
                                <label class="vm-field__label" for="vm-b-rp">{{ t('vendors.referencePrice') }}</label>
                                <p id="vm-b-rp-hint" class="vm-field__hint">{{ t('vendors.detailReferencePriceHint') }}</p>
                                <input
                                    id="vm-b-rp"
                                    v-model="edit.reference_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="ppms-input vm-field__control"
                                    :placeholder="t('vendors.detailReferencePricePlaceholder')"
                                    inputmode="decimal"
                                    aria-describedby="vm-b-rp-hint"
                                />
                            </div>
                        </div>
                    </section>
                    <section class="vm-form-section">
                        <h4 class="vm-form-section__title">{{ t('vendors.formSectionBizNarrative') }}</h4>
                        <div class="vm-field vm-field--full">
                            <label class="vm-field__label" for="vm-b-mp">{{ t('vendors.mainProducts') }}</label>
                            <p id="vm-b-mp-hint" class="vm-field__hint">{{ t('vendors.detailMainProductsHint') }}</p>
                            <textarea
                                id="vm-b-mp"
                                v-model="edit.main_products"
                                rows="3"
                                class="ppms-input vm-field__control"
                                :placeholder="t('vendors.detailMainProductsPlaceholder')"
                                aria-describedby="vm-b-mp-hint"
                            />
                        </div>
                        <div class="vm-form-grid vm-form-grid--2">
                            <div class="vm-field">
                                <label class="vm-field__label" for="vm-b-pros">{{ t('vendors.pros') }}</label>
                                <textarea
                                    id="vm-b-pros"
                                    v-model="edit.pros"
                                    rows="4"
                                    class="ppms-input vm-field__control"
                                    :placeholder="t('vendors.detailProsPlaceholder')"
                                />
                            </div>
                            <div class="vm-field">
                                <label class="vm-field__label" for="vm-b-cons">{{ t('vendors.cons') }}</label>
                                <textarea
                                    id="vm-b-cons"
                                    v-model="edit.cons"
                                    rows="4"
                                    class="ppms-input vm-field__control"
                                    :placeholder="t('vendors.detailConsPlaceholder')"
                                />
                            </div>
                        </div>
                        <div class="vm-field vm-field--full">
                            <label class="vm-field__label" for="vm-b-rn">{{ t('vendors.researchNote') }}</label>
                            <p id="vm-b-rn-hint" class="vm-field__hint">{{ t('vendors.detailResearchNoteHint') }}</p>
                            <textarea
                                id="vm-b-rn"
                                v-model="edit.research_note"
                                rows="3"
                                class="ppms-input vm-field__control"
                                :placeholder="t('vendors.detailResearchNotePlaceholder')"
                                aria-describedby="vm-b-rn-hint"
                            />
                        </div>
                    </section>
                    <section class="vm-form-section">
                        <h4 class="vm-form-section__title">{{ t('vendors.formSectionBizCriteria') }}</h4>
                        <p class="vm-form-section__intro">{{ t('vendors.formSectionBizCriteriaHint') }}</p>
                        <div class="vm-form-grid vm-form-grid--4">
                            <div class="vm-field">
                                <label class="vm-field__label" for="vm-b-sp">{{ t('vendors.scorePrice') }}</label>
                                <p class="vm-field__hint">{{ t('vendors.detailScorePriceHint') }}</p>
                                <input
                                    id="vm-b-sp"
                                    v-model="edit.score_price"
                                    type="number"
                                    min="0"
                                    max="5"
                                    step="0.1"
                                    class="ppms-input vm-field__control"
                                    placeholder="0–5"
                                />
                            </div>
                            <div class="vm-field">
                                <label class="vm-field__label" for="vm-b-sq">{{ t('vendors.scoreQuality') }}</label>
                                <p class="vm-field__hint">{{ t('vendors.detailScoreQualityHint') }}</p>
                                <input
                                    id="vm-b-sq"
                                    v-model="edit.score_quality"
                                    type="number"
                                    min="0"
                                    max="5"
                                    step="0.1"
                                    class="ppms-input vm-field__control"
                                    placeholder="0–5"
                                />
                            </div>
                            <div class="vm-field">
                                <label class="vm-field__label" for="vm-b-ss">{{ t('vendors.scoreSla') }}</label>
                                <p class="vm-field__hint">{{ t('vendors.detailScoreSlaHint') }}</p>
                                <input
                                    id="vm-b-ss"
                                    v-model="edit.score_sla"
                                    type="number"
                                    min="0"
                                    max="5"
                                    step="0.1"
                                    class="ppms-input vm-field__control"
                                    placeholder="0–5"
                                />
                            </div>
                            <div class="vm-field">
                                <label class="vm-field__label" for="vm-b-su">{{ t('vendors.scoreSupport') }}</label>
                                <p class="vm-field__hint">{{ t('vendors.detailScoreSupportHint') }}</p>
                                <input
                                    id="vm-b-su"
                                    v-model="edit.score_support"
                                    type="number"
                                    min="0"
                                    max="5"
                                    step="0.1"
                                    class="ppms-input vm-field__control"
                                    placeholder="0–5"
                                />
                            </div>
                        </div>
                    </section>
                    <section class="vm-form-section">
                        <h4 class="vm-form-section__title">{{ t('vendors.formSectionDepartments') }}</h4>
                        <p id="vm-b-dept-hint" class="vm-field__hint vm-field__hint--block">{{ t('vendors.detailDepartmentsHint') }}</p>
                        <div class="vm-field vm-field--full">
                            <label class="vm-field__label" for="vm-b-dept">{{ t('vendors.departments') }}</label>
                            <select
                                id="vm-b-dept"
                                v-model="editDepartmentIds"
                                class="ppms-input vm-field__control vm-field__control--multiselect"
                                multiple
                                size="6"
                                aria-describedby="vm-b-dept-hint"
                            >
                                <option v-for="d in lookups.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>
                        <p class="vm-form-footnote">{{ t('contracts.addDepartment') }}</p>
                    </section>
                </div>
                <div v-if="vendor.products?.length" class="ppms-mt">
                    <h4>{{ t('vendors.productLines') }}</h4>
                    <ul class="vm-product-list">
                        <li v-for="p in vendor.products" :key="p.id"><strong>{{ p.name }}</strong> — {{ p.description || '' }}</li>
                    </ul>
                </div>
            </section>

            <section
                v-show="tab === 'contracts'"
                id="vm-panel-contracts"
                class="ppms-card ppms-mt vm-panel"
                role="tabpanel"
                aria-labelledby="vm-tab-contracts"
            >
                <table v-if="vendor.contracts?.length" class="ppms-table">
                    <thead>
                        <tr>
                            <th>{{ t('vendors.contractCode') }}</th>
                            <th>{{ t('contracts.tableStatus') }}</th>
                            <th>{{ t('vendors.contractDept') }}</th>
                            <th>{{ t('contracts.tableValue') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in vendor.contracts" :key="c.id">
                            <td>{{ c.code }}</td>
                            <td>{{ c.status }}</td>
                            <td>{{ c.department?.name || '—' }}</td>
                            <td>{{ c.total_value }}</td>
                            <td><router-link class="ppms-btn-ghost ppms-btn-sm" :to="`/contracts/${c.id}`">{{ t('vendors.goContract') }}</router-link></td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="ppms-muted">{{ t('vendors.contractsEmpty') }}</p>
            </section>

            <section
                v-show="tab === 'reviews'"
                id="vm-panel-reviews"
                class="ppms-card ppms-mt vm-panel"
                role="tabpanel"
                aria-labelledby="vm-tab-reviews"
            >
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
                v-show="tab === 'timeline'"
                id="vm-panel-timeline"
                class="ppms-card ppms-mt vm-panel"
                role="tabpanel"
                aria-labelledby="vm-tab-timeline"
            >
                <div v-if="canEdit" class="vm-timeline-form vm-edit-form vm-edit-form--compact">
                    <h2 class="vm-sec-title">{{ t('vendors.timelineAdd') }}</h2>
                    <div class="vm-form-grid vm-form-grid--2">
                        <div class="vm-field">
                            <label class="vm-field__label" for="vm-tl-ph">{{ t('vendors.timelinePhase') }}</label>
                            <p id="vm-tl-ph-hint" class="vm-field__hint">{{ t('vendors.timelinePhaseHint') }}</p>
                            <select id="vm-tl-ph" v-model="tlForm.phase" class="ppms-input vm-field__control" aria-describedby="vm-tl-ph-hint">
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
                                class="ppms-input vm-field__control"
                                :step="60"
                                aria-describedby="vm-tl-when-hint"
                            />
                        </div>
                    </div>
                    <div class="vm-form-grid vm-form-grid--2">
                        <div class="vm-field">
                            <label class="vm-field__label" for="vm-tl-act">{{ t('vendors.timelineActor') }}</label>
                            <p id="vm-tl-act-hint" class="vm-field__hint">{{ t('vendors.timelineActorHint') }}</p>
                            <input
                                id="vm-tl-act"
                                v-model.number="tlForm.performed_by_user_id"
                                type="number"
                                min="1"
                                class="ppms-input vm-field__control"
                                :placeholder="t('vendors.timelineActorPlaceholder')"
                                aria-describedby="vm-tl-act-hint"
                            />
                        </div>
                        <div class="vm-field vm-field--checkbox">
                            <label class="vm-field__checkbox">
                                <input v-model="tlForm.is_current" type="checkbox" />
                                <span>{{ t('vendors.timelineCurrent') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="vm-field vm-field--full">
                        <label class="vm-field__label" for="vm-tl-note">{{ t('vendors.timelineNote') }}</label>
                        <textarea
                            id="vm-tl-note"
                            v-model="tlForm.note"
                            rows="3"
                            class="ppms-input vm-field__control"
                            :placeholder="t('vendors.timelineNotePlaceholder')"
                        />
                    </div>
                    <div class="vm-timeline-form__actions">
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
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { getApiErrorMessage } from '@/bootstrap';
import { ppmsConfirm, ppmsToastSuccess, ppmsToastError } from '@/ppmsUi';
import VendorTimeline from './components/VendorTimeline.vue';
import VendorReview from './components/VendorReview.vue';

defineProps({
    id: { type: String, required: true },
});

const { t, locale } = useI18n();
const route = useRoute();
const router = useRouter();

const loading = ref(true);
const err = ref('');
const vendor = ref(null);
const me = ref(null);
const editingTab = ref(null);
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

const tab = ref('overview');
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
const canDelete = computed(() => ['admin', 'pm', 'tl'].includes(me.value?.role));
const canEditCurrentTab = computed(() => tab.value === 'overview' || tab.value === 'business');

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

function formatVendorDateTime(iso) {
    if (!iso) return '';
    try {
        const d = new Date(iso);
        const loc = locale.value === 'vi' ? 'vi-VN' : undefined;
        return new Intl.DateTimeFormat(loc, { dateStyle: 'medium', timeStyle: 'short' }).format(d);
    } catch {
        return iso;
    }
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
        'industry', 'main_products', 'contract_value', 'estimated_cost', 'reference_price',
        'research_source', 'research_note', 'pros', 'cons', 'fit_score',
        'score_price', 'score_quality', 'score_sla', 'score_support',
    ];
    for (const k of keys) {
        edit[k] = v[k] ?? '';
    }
    editDepartmentIds.value = (v.departments || []).map((d) => d.id);
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

function openTimeline() {
    tab.value = 'timeline';
    loadTimeline();
}

function startEditTab() {
    if (!canEditCurrentTab.value) return;
    editingTab.value = tab.value;
    if (vendor.value) snapshotFromVendor(vendor.value);
}

function cancelEditTab() {
    editingTab.value = null;
    if (vendor.value) snapshotFromVendor(vendor.value);
}

async function saveTab() {
    const section = editingTab.value;
    if (!section) return;
    saving.value = true;
    try {
        const body = {};
        if (section === 'overview') {
            for (const k of OVERVIEW_FIELD_KEYS) {
                body[k] = edit[k];
            }
        } else if (section === 'business') {
            for (const k of BUSINESS_FIELD_KEYS) {
                body[k] = edit[k];
            }
            body.department_ids = editDepartmentIds.value.map((x) => Number(x));
        }
        await axios.patch(`/api/vendors/${route.params.id}`, body);
        ppmsToastSuccess(t('vendors.saved'));
        editingTab.value = null;
        await loadVendor();
    } catch (e) {
        ppmsToastError(getApiErrorMessage(e));
    } finally {
        saving.value = false;
    }
}

async function removeVendor() {
    const ok = await ppmsConfirm(t('vendors.deleteVendorConfirm'), { title: t('vendors.deleteVendor') });
    if (!ok) return;
    try {
        await axios.delete(`/api/vendors/${route.params.id}`);
        ppmsToastSuccess(t('vendors.deleted'));
        router.push({ name: 'vendors' });
    } catch (e) {
        ppmsToastError(getApiErrorMessage(e));
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
        editingTab.value = null;
        loadVendor();
    }
);

watch(tab, () => {
    if (editingTab.value) {
        cancelEditTab();
    }
});

onMounted(async () => {
    try {
        const u = await axios.get('/api/user');
        me.value = u.data;
    } catch {
        me.value = null;
    }
    await loadLookups();
    await loadVendor();
});
</script>

<style scoped>
.vm-detail__header {
    margin-bottom: 1rem;
}
.vm-detail__toprow {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: 0.75rem 1rem;
}
.vm-detail__back {
    flex-shrink: 0;
    align-self: center;
}
.vm-detail__head-main {
    flex: 1 1 14rem;
    min-width: 0;
}
.vm-detail__toolbar-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    margin-left: auto;
}
.vm-detail__title {
    margin: 0.15rem 0 0.25rem;
    font-size: 1.5rem;
    line-height: 1.25;
}
.vm-detail__meta {
    margin: 0;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: var(--ppms-muted, #64748b);
}
.vm-detail__meta-time {
    font-weight: 600;
    color: var(--ppms-text-muted, #475569);
}
.vm-detail__meta-sep {
    margin: 0 0.35rem;
    opacity: 0.65;
}
.vm-detail__meta-by {
    color: var(--ppms-text, #0f172a);
}
.vm-detail__badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 0.5rem 0;
}
.vm-pill {
    display: inline-block;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    background: #e2e8f0;
    font-size: 0.85rem;
}
.vm-pill--muted {
    background: #f1f5f9;
}
.vm-pill--score {
    background: #dbeafe;
    color: #1d4ed8;
}
.vm-empty {
    cursor: help;
    color: var(--ppms-muted, #94a3b8);
    font-style: italic;
    border-bottom: 1px dashed var(--ppms-border, #cbd5e1);
}

/* —— Tabs —— */
.vm-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 0.5rem 0 0;
    padding: 0.35rem;
    border-radius: 12px;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid var(--ppms-border, #e2e8f0);
}
.vm-tab {
    flex: 1 1 9.5rem;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.15rem;
    padding: 0.55rem 0.75rem 0.6rem;
    border-radius: 9px;
    border: 1px solid transparent;
    background: transparent;
    color: var(--ppms-text, #0f172a);
    font: inherit;
    text-align: left;
    cursor: pointer;
    transition:
        background 0.15s ease,
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}
.vm-tab:hover {
    background: rgba(255, 255, 255, 0.75);
    border-color: var(--ppms-border, #e2e8f0);
}
.vm-tab:focus-visible {
    outline: 2px solid var(--ppms-accent, #4f46e5);
    outline-offset: 2px;
}
.vm-tab--active {
    background: #fff;
    border-color: #c7d2fe;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
}
.vm-tab__label {
    font-weight: 700;
    font-size: 0.9rem;
    line-height: 1.25;
}
.vm-tab__hint {
    font-size: 0.7rem;
    line-height: 1.35;
    color: var(--ppms-muted, #64748b);
    font-weight: 500;
}
.vm-tab--active .vm-tab__hint {
    color: #475569;
}

.vm-panel {
    scroll-margin-top: 0.5rem;
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
.vm-pre {
    white-space: pre-wrap;
}
.vm-sec-title {
    margin: 0 0 0.75rem;
    font-size: 1.05rem;
}
.vm-sec-title--panel {
    margin-top: 0;
    margin-bottom: 0.65rem;
}
.vm-product-list {
    margin: 0.5rem 0 0;
    padding-left: 1.2rem;
}
.vm-timeline-form {
    margin-bottom: 1.25rem;
    padding: 1rem 1.1rem;
    border-radius: 10px;
    background: var(--ppms-bg-subtle, rgba(248, 250, 252, 0.95));
    border: 1px solid var(--ppms-border, #e2e6ea);
    border-bottom: 1px solid var(--ppms-border, #e2e6ea);
}
.vm-timeline-form__actions {
    margin-top: 0.75rem;
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
</style>
