<template>
    <div class="ppms-page vm-detail vm-detail--flat">
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
                                    <a v-if="!isEmptyText(vendor.website)" :href="vendor.website" target="_blank" rel="noopener">{{ vendor.website }}</a>
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
                                    <textarea
                                        id="vm-b-mp"
                                        v-model="edit.main_products"
                                        rows="3"
                                        class="ppms-input vm-kv__control vm-kv__control--textarea"
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
        'industry', 'main_products', 'contract_value', 'estimated_cost', 'reference_price',
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
    saving.value = true;
    try {
        const body = {};
        for (const k of OVERVIEW_FIELD_KEYS) {
            body[k] = edit[k];
        }
        if (vendor.value?.kind !== 'research') {
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

/* Borderless / flat layout */
.vm-detail--flat .vm-detail__header {
    border: none;
    box-shadow: none;
    background: transparent;
    padding-left: 0;
    padding-right: 0;
}

.vm-detail--flat .vm-detail__badges {
    border-top: none;
    padding-top: 0.5rem;
    margin-top: 0.35rem;
}

.vm-detail--flat .ppms-card {
    border: none !important;
    box-shadow: none !important;
    background: transparent;
}

.vm-detail--flat .vm-kv th,
.vm-detail--flat .vm-kv td {
    border-bottom: none;
}

.vm-detail--flat .vm-kv__group-title {
    border-bottom: none;
    background: transparent;
}

.vm-detail--flat .vm-kv__section-hint {
    border-bottom: none;
    background: transparent;
}

.vm-detail--flat .ppms-input,
.vm-detail--flat .vm-kv__select,
.vm-detail--flat textarea.ppms-input {
    border: none;
    background: #f1f5f9;
}

.vm-detail--flat .ppms-input:focus,
.vm-detail--flat .vm-kv__select:focus,
.vm-detail--flat textarea.ppms-input:focus {
    outline: 2px solid var(--ppms-focus, #2563eb);
    outline-offset: 1px;
}

.vm-detail--flat .vm-dept-multi__trigger {
    border: none;
    background: #f1f5f9;
}

.vm-detail--flat .vm-detail__inline-actions {
    border-top: none;
    padding-top: 0.75rem;
}
</style>
