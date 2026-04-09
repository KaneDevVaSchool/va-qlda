<template>
    <div class="ppms-page">
        <div v-if="loading" class="ppms-loading-line" role="status">{{ t('common.loading') }}</div>
        <template v-else-if="contract">
            <header class="cd-header ppms-contract-header">
                <div class="cd-header__main">
                    <router-link to="/contracts" class="ppms-back cd-header__back">{{ t('common.back') }}</router-link>
                    <h1 class="ppms-contract-title cd-header__title">{{ contract.code }}</h1>
                    <div class="cd-header__badges">
                        <span class="cd-pill" :class="statusPillClass(contract.status)">{{ statusLabel(contract.status) }}</span>
                    </div>
                    <p class="cd-header__summary">
                        <span class="cd-header__summary-item"
                            ><span class="cd-header__summary-key">{{ t('contracts.fieldVendor') }}</span>
                            <router-link
                                v-if="contract.vendor_id"
                                :to="{ name: 'vendor-detail', params: { id: String(contract.vendor_id) } }"
                                class="cd-header__vendor-link"
                                >{{ contract.vendor?.name || '—' }}</router-link
                            >
                            <template v-else>{{ contract.vendor?.name || '—' }}</template></span
                        >
                        <span class="cd-header__sep" aria-hidden="true">·</span>
                        <span class="cd-header__summary-item"
                            ><span class="cd-header__summary-key">{{ t('contracts.fieldProduct') }}</span>
                            {{ contract.product?.name || '—' }}</span
                        >
                        <span class="cd-header__sep" aria-hidden="true">·</span>
                        <span class="cd-header__summary-item"
                            ><span class="cd-header__summary-key">{{ t('contracts.fieldDepartment') }}</span>
                            {{ contract.department?.name || '—' }}</span
                        >
                        <span class="cd-header__sep" aria-hidden="true">·</span>
                        <span class="cd-header__summary-item"
                            ><span class="cd-header__summary-key">{{ t('contracts.fieldBlock') }}</span>
                            {{ contract.block?.name || '—' }}</span
                        >
                    </p>
                    <p class="cd-header__dates">
                        <span class="cd-header__dates-label">{{ t('contracts.detailDurationLabel') }}</span>
                        {{ formatDateDisplay(contract.start_date) }} → {{ formatDateDisplay(contract.end_date) }}
                    </p>
                    <p v-if="contract.status === 'pending_approval' && isCurrentApprover" class="cd-header__approval-nudge">
                        <span class="cd-header__approval-nudge__txt">{{ t('contracts.approvalYourTurnNudge') }}</span>
                        <button
                            v-if="tab !== 'approvals'"
                            type="button"
                            class="ppms-btn-primary ppms-btn-sm"
                            @click="tab = 'approvals'"
                        >
                            {{ t('contracts.approvalGoToStep') }}
                        </button>
                    </p>
                </div>
                <div class="ppms-contract-actions cd-header__actions">
                    <button type="button" class="ppms-btn-ghost" @click="exportSummaryPdf">{{ t('contracts.exportSummaryPdf') }}</button>
                    <template v-if="contract.status === 'draft' && canEdit">
                        <button type="button" class="ppms-btn-ghost" @click="openEdit">{{ t('common.edit') }}</button>
                    </template>
                    <template v-if="(contract.status === 'draft' && canEdit) || isAdmin">
                        <button type="button" class="ppms-btn-ghost ppms-btn-danger" @click="removeDraft">{{ t('common.delete') }}</button>
                    </template>
                    <template v-if="contract.status === 'draft' && canSubmit">
                        <button type="button" class="ppms-btn-primary" @click="submitOpen = true">{{ t('contracts.submitApproval') }}</button>
                    </template>
                    <template v-if="contract.status === 'active' && canTerminate">
                        <button type="button" class="ppms-btn-ghost ppms-btn-danger" @click="doTerminate">{{ t('contracts.terminate') }}</button>
                    </template>
                </div>
            </header>

            <div class="cd-tabs ppms-tabs" role="tablist">
                <button
                    type="button"
                    class="ppms-tab cd-tab"
                    role="tab"
                    :aria-selected="tab === 'info'"
                    :class="{ 'ppms-tab--active': tab === 'info' }"
                    @click="tab = 'info'"
                >
                    {{ t('contracts.tabInfo') }}
                </button>
                <button
                    type="button"
                    class="ppms-tab cd-tab"
                    role="tab"
                    :aria-selected="tab === 'payments'"
                    :class="{ 'ppms-tab--active': tab === 'payments' }"
                    @click="tab = 'payments'"
                >
                    {{ t('contracts.tabPayments') }}
                    <span v-if="(contract.payments || []).length" class="cd-tab__count">{{ (contract.payments || []).length }}</span>
                </button>
                <button
                    type="button"
                    class="ppms-tab cd-tab"
                    role="tab"
                    :aria-selected="tab === 'files'"
                    :class="{ 'ppms-tab--active': tab === 'files' }"
                    @click="tab = 'files'"
                >
                    {{ t('contracts.tabFiles') }}
                    <span v-if="(contract.files || []).length" class="cd-tab__count">{{ (contract.files || []).length }}</span>
                </button>
                <button
                    type="button"
                    class="ppms-tab cd-tab"
                    role="tab"
                    :aria-selected="tab === 'approvals'"
                    :class="{ 'ppms-tab--active': tab === 'approvals' }"
                    @click="tab = 'approvals'"
                >
                    {{ t('contracts.tabApprovals') }}
                </button>
                <button
                    type="button"
                    class="ppms-tab cd-tab"
                    role="tab"
                    :aria-selected="tab === 'logs'"
                    :class="{ 'ppms-tab--active': tab === 'logs' }"
                    @click="tab = 'logs'"
                >
                    {{ t('contracts.tabLogs') }}
                </button>
            </div>

            <section v-show="tab === 'info'" class="cd-panel ppms-card ppms-mt">
                <p class="cd-panel__intro">{{ t('contracts.detailIntro') }}</p>
                <div class="cd-detail__grid">
                    <div class="cd-detail__card">
                        <h3 class="cd-detail__card-title">{{ t('contracts.sectionParties') }}</h3>
                        <dl class="cd-detail__dl">
                            <div>
                                <dt>{{ t('contracts.fieldVendor') }}</dt>
                                <dd>
                                    <router-link
                                        v-if="contract.vendor_id"
                                        :to="{ name: 'vendor-detail', params: { id: String(contract.vendor_id) } }"
                                        class="cd-header__vendor-link"
                                        >{{ contract.vendor?.name || '—' }}</router-link
                                    >
                                    <template v-else>{{ contract.vendor?.name || '—' }}</template>
                                </dd>
                            </div>
                            <div>
                                <dt>{{ t('contracts.fieldProduct') }}</dt>
                                <dd>{{ contract.product?.name || '—' }}</dd>
                            </div>
                            <div>
                                <dt>{{ t('contracts.fieldDepartment') }}</dt>
                                <dd>{{ contract.department?.name || '—' }}</dd>
                            </div>
                            <div>
                                <dt>{{ t('contracts.fieldBlock') }}</dt>
                                <dd>{{ contract.block?.name || '—' }}</dd>
                            </div>
                            <div>
                                <dt>{{ t('contracts.fieldCreator') }}</dt>
                                <dd>{{ contract.creator?.name || '—' }}</dd>
                            </div>
                            <div>
                                <dt>{{ t('contracts.fieldFollower') }}</dt>
                                <dd>{{ contract.followed_by?.name || '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="cd-detail__card">
                        <h3 class="cd-detail__card-title">{{ t('contracts.sectionTerm') }}</h3>
                        <p class="cd-detail__metric">{{ formatMoneyDisplay(contract.total_value) }}</p>
                        <dl class="cd-detail__dl cd-detail__dl--compact">
                            <div>
                                <dt>{{ t('contracts.fieldStart') }}</dt>
                                <dd>{{ formatDateDisplay(contract.start_date) }}</dd>
                            </div>
                            <div>
                                <dt>{{ t('contracts.fieldEnd') }}</dt>
                                <dd>{{ formatDateDisplay(contract.end_date) }}</dd>
                            </div>
                            <div>
                                <dt>{{ t('contracts.fieldCycle') }}</dt>
                                <dd>{{ paymentCycleLabel(contract.payment_cycle) }}</dd>
                            </div>
                        </dl>
                        <div v-if="periodProgressPercent(contract) !== null" class="cd-detail__progress">
                            <div class="cd-detail__progress-head">
                                <span>{{ t('contracts.tablePeriodProgress') }}</span>
                                <strong>{{ periodProgressPercent(contract) }}%</strong>
                            </div>
                            <div class="cd-detail__progress-track" role="presentation">
                                <div class="cd-detail__progress-fill" :style="{ width: `${periodProgressPercent(contract)}%` }" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cd-detail__card cd-detail__card--full">
                    <h3 class="cd-detail__card-title">{{ t('contracts.sectionScope') }}</h3>
                    <p class="cd-detail__scope">{{ contract.scope?.trim() || '—' }}</p>
                </div>
            </section>

            <section v-show="tab === 'payments'" class="cd-panel ppms-card ppms-mt">
                <p class="cd-panel__intro">{{ t('contracts.paymentsTabIntro') }}</p>
                <p v-if="canManage && contract && contract.status !== 'active'" class="ppms-hint cd-payments-inactive">
                    {{ t('contracts.paymentsMarkPaidInactive') }}
                </p>
                <div v-if="(contract.payments || []).length" class="cd-pay-summary">
                    <div class="cd-pay-summary__grid">
                        <div class="cd-pay-summary__card">
                            <span class="cd-pay-summary__label">{{ t('contracts.paymentsTotalScheduled') }}</span>
                            <strong class="cd-pay-summary__value">{{ formatMoneyDisplay(paymentTotals.scheduled) }}</strong>
                        </div>
                        <div class="cd-pay-summary__card cd-pay-summary__card--paid">
                            <span class="cd-pay-summary__label">{{ t('contracts.paymentsTotalPaid') }}</span>
                            <strong class="cd-pay-summary__value">{{ formatMoneyDisplay(paymentTotals.paid) }}</strong>
                        </div>
                        <div class="cd-pay-summary__card cd-pay-summary__card--out">
                            <span class="cd-pay-summary__label">{{ t('contracts.paymentsTotalOutstanding') }}</span>
                            <strong class="cd-pay-summary__value">{{ formatMoneyDisplay(paymentTotals.outstanding) }}</strong>
                        </div>
                    </div>
                    <div class="cd-pay-summary__progress">
                        <div class="cd-pay-summary__progress-head">
                            <span>{{ t('contracts.paymentsProgressLabel') }}</span>
                            <strong>{{ paymentTotals.pct }}%</strong>
                        </div>
                        <div class="cd-detail__progress-track" role="presentation">
                            <div class="cd-detail__progress-fill" :style="{ width: `${paymentTotals.pct}%` }" />
                        </div>
                    </div>
                </div>
                <div class="ppms-table-scroll">
                    <table class="ppms-table cd-table">
                        <thead>
                            <tr>
                                <th>{{ t('contracts.paymentsDue') }}</th>
                                <th class="cd-table__num">{{ t('contracts.paymentsScheduled') }}</th>
                                <th class="cd-table__num">{{ t('contracts.paymentsPaidCol') }}</th>
                                <th class="cd-table__num">{{ t('contracts.paymentsRemainingCol') }}</th>
                                <th>{{ t('contracts.paymentsStatus') }}</th>
                                <th>{{ t('contracts.paymentsProofCol') }}</th>
                                <th v-if="canMarkPaid" />
                            </tr>
                        </thead>
                        <tbody v-if="(contract.payments || []).length">
                            <tr v-for="p in contract.payments || []" :key="p.id">
                                <td>{{ formatDateDisplay(p.due_date) }}</td>
                                <td class="cd-table__num">{{ formatMoneyDisplay(p.amount) }}</td>
                                <td class="cd-table__num">{{ formatMoneyDisplay(p.amount_paid ?? 0) }}</td>
                                <td class="cd-table__num">{{ formatMoneyDisplay(paymentRemainingAmount(p)) }}</td>
                                <td>
                                    <span class="cd-pill cd-pill--sm" :class="paymentPillClass(p.status)">{{
                                        paymentStatusLabel(p.status)
                                    }}</span>
                                </td>
                                <td>
                                    <button
                                        v-if="p.proof_file?.id"
                                        type="button"
                                        class="ppms-btn-ghost ppms-btn-sm"
                                        @click="downloadFile(p.proof_file.id)"
                                    >
                                        {{ p.proof_file.file_name }}
                                    </button>
                                    <span v-else class="cd-table__muted">—</span>
                                </td>
                                <td v-if="canMarkPaid">
                                    <button
                                        v-if="canRecordPayment(p)"
                                        type="button"
                                        class="ppms-btn-ghost ppms-btn-sm"
                                        @click="openPayModal(p.id)"
                                    >
                                        {{ t('contracts.recordPayment') }}
                                    </button>
                                    <span v-else class="cd-table__muted">—</span>
                                </td>
                            </tr>
                        </tbody>
                        <tbody v-else>
                            <tr>
                                <td :colspan="canMarkPaid ? 7 : 6" class="cd-empty">{{ t('contracts.emptyPayments') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-show="tab === 'files'" class="cd-panel ppms-card ppms-mt">
                <p class="cd-panel__intro">{{ t('contracts.filesTabIntro') }}</p>
                <div v-if="sortedVersions.length >= 2" class="cd-version-compare">
                    <h4 class="cd-version-compare__title">{{ t('contracts.versionCompareTitle') }}</h4>
                    <p class="cd-version-compare__hint">{{ t('contracts.versionCompareHint') }}</p>
                    <div class="cd-version-compare__row">
                        <label class="cd-version-compare__lbl">{{ t('contracts.versionCompareA') }}</label>
                        <select v-model.number="versionCompareA" class="ppms-input cd-version-compare__sel">
                            <option v-for="v in sortedVersions" :key="'a-' + v.id" :value="v.id">{{ t('contracts.versionN', { n: v.version }) }}</option>
                        </select>
                        <label class="cd-version-compare__lbl">{{ t('contracts.versionCompareB') }}</label>
                        <select v-model.number="versionCompareB" class="ppms-input cd-version-compare__sel">
                            <option v-for="v in sortedVersions" :key="'b-' + v.id" :value="v.id">{{ t('contracts.versionN', { n: v.version }) }}</option>
                        </select>
                    </div>
                    <dl v-if="versionComparePair" class="cd-version-diff">
                        <div>
                            <dt>{{ t('contracts.versionNote') }}</dt>
                            <dd>
                                <span class="cd-version-diff__a">{{ versionComparePair.a.note || '—' }}</span>
                                <span class="cd-version-diff__sep" aria-hidden="true">|</span>
                                <span class="cd-version-diff__b">{{ versionComparePair.b.note || '—' }}</span>
                            </dd>
                        </div>
                        <div>
                            <dt>{{ t('contracts.filesUploaded') }}</dt>
                            <dd>
                                <span class="cd-version-diff__a">{{ formatDateTimeDisplay(versionComparePair.a.created_at) }}</span>
                                <span class="cd-version-diff__sep" aria-hidden="true">|</span>
                                <span class="cd-version-diff__b">{{ formatDateTimeDisplay(versionComparePair.b.created_at) }}</span>
                            </dd>
                        </div>
                    </dl>
                </div>
                <div v-if="sortedVersions.length" class="cd-version-timeline">
                    <h4 class="cd-version-timeline__title">{{ t('contracts.versionTimelineTitle') }}</h4>
                    <ol class="cd-version-timeline__list">
                        <li v-for="v in sortedVersions" :key="v.id" class="cd-version-timeline__item">
                            <span class="cd-version-timeline__badge">{{ t('contracts.versionN', { n: v.version }) }}</span>
                            <span class="cd-version-timeline__meta">{{ formatDateTimeDisplay(v.created_at) }}</span>
                            <p class="cd-version-timeline__note">{{ v.note || '—' }}</p>
                        </li>
                    </ol>
                </div>
                <div v-if="contract.status === 'draft' && canEdit" class="cd-upload-bar ppms-file-upload ppms-mb">
                    <label class="ppms-btn-ghost ppms-btn-sm cd-upload-btn" style="cursor: pointer">
                        {{ t('contracts.upload') }}
                        <input ref="fileInput" type="file" class="ppms-sr-file" @change="onFilePick" />
                    </label>
                    <label class="ppms-checkbox">
                        <input v-model="uploadAsVersion" type="checkbox" />
                        {{ t('contracts.uploadVersion') }}
                    </label>
                    <span v-if="uploading" class="ppms-hint">{{ t('common.loading') }}</span>
                </div>
                <div class="ppms-table-scroll">
                    <table class="ppms-table cd-table">
                        <thead>
                            <tr>
                                <th class="cd-table__icon-col" aria-hidden="true" />
                                <th>{{ t('contracts.filesName') }}</th>
                                <th>{{ t('contracts.filesUploaded') }}</th>
                                <th />
                            </tr>
                        </thead>
                        <tbody v-if="(contract.files || []).length">
                            <tr v-for="f in contract.files || []" :key="f.id">
                                <td class="cd-file-icon-cell">
                                    <span class="cd-file-icon" :class="'cd-file-icon--' + fileIconKind(f)" aria-hidden="true" />
                                </td>
                                <td>
                                    <span class="cd-file-name" :title="f.file_name">{{ f.file_name }}</span>
                                </td>
                                <td class="cd-table__muted">{{ formatDateTimeDisplay(f.created_at) }}</td>
                                <td class="cd-file-actions">
                                    <button
                                        v-if="canPreviewFile(f)"
                                        type="button"
                                        class="ppms-btn-ghost ppms-btn-sm"
                                        @click="openFilePreview(f)"
                                    >
                                        {{ t('contracts.preview') }}
                                    </button>
                                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="downloadFile(f.id)">
                                        {{ t('contracts.download') }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tbody v-else>
                            <tr>
                                <td colspan="4" class="cd-empty">{{ t('contracts.emptyFiles') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-show="tab === 'approvals'" class="cd-panel ppms-card ppms-mt cd-approvals-panel">
                <div class="cd-approvals-panel__head">
                    <h3 class="cd-approvals-panel__title">{{ t('contracts.approvalTimelineTitle') }}</h3>
                    <p class="cd-panel__intro cd-approvals-panel__intro">{{ t('contracts.approvalsTabIntro') }}</p>
                </div>
                <div
                    v-if="contract.status === 'pending_approval' && isCurrentApprover"
                    class="cd-approval-callout"
                    role="status"
                >
                    <span class="cd-approval-callout__icon" aria-hidden="true">●</span>
                    <div class="cd-approval-callout__text">
                        <strong>{{ t('contracts.approvalYourTurnTitle') }}</strong>
                        <span>{{ t('contracts.approvalYourTurnBody') }}</span>
                    </div>
                </div>
                <ol v-if="sortedApprovals.length" class="cd-approval-timeline">
                    <li
                        v-for="(a, idx) in sortedApprovals"
                        :key="a.id"
                        class="cd-approval-timeline__item"
                        :class="approvalItemClass(a.status)"
                    >
                        <div class="cd-approval-timeline__track" aria-hidden="true">
                            <span class="cd-approval-timeline__dot" :class="approvalDotClass(a.status)" />
                            <span v-if="idx < sortedApprovals.length - 1" class="cd-approval-timeline__line" />
                        </div>
                        <div class="cd-approval-timeline__body">
                            <div class="cd-approval-timeline__head">
                                <div class="cd-approval-timeline__head-main">
                                    <span class="cd-approval-timeline__step"
                                        >{{ t('contracts.approvalStep') }} {{ a.step }}</span
                                    >
                                    <span class="cd-pill cd-pill--sm" :class="approvalPillClass(a.status)">{{
                                        approvalStatusLabel(a.status)
                                    }}</span>
                                </div>
                                <div v-if="showApprovalStepActions(a)" class="cd-approval-timeline__actions" @click.stop>
                                    <button type="button" class="ppms-btn-primary ppms-btn-sm" @click="doApprove">
                                        {{ t('contracts.approveShort') }}
                                    </button>
                                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="doReject">
                                        {{ t('contracts.rejectShort') }}
                                    </button>
                                </div>
                            </div>
                            <p class="cd-approval-timeline__role">{{ t('contracts.approvalApproverRole') }}</p>
                            <p class="cd-approval-timeline__who">{{ a.approver?.name || '—' }}</p>
                            <p v-if="a.status === 'approved' && a.approved_at" class="cd-approval-timeline__meta">
                                {{ t('contracts.approvalDoneAt') }} {{ formatDateTimeDisplay(a.approved_at) }}
                            </p>
                            <p v-else-if="a.status === 'pending' && !isCurrentApprover" class="cd-approval-timeline__meta">
                                {{ t('contracts.approvalWaitingFor', { name: a.approver?.name || '—' }) }}
                            </p>
                            <p v-else-if="a.status === 'queued'" class="cd-approval-timeline__meta">
                                {{ t('contracts.approvalQueuedHint') }}
                            </p>
                        </div>
                    </li>
                </ol>
                <p v-else class="cd-empty cd-empty--block">{{ t('contracts.emptyApprovals') }}</p>
            </section>

            <section v-show="tab === 'logs'" class="cd-panel ppms-card ppms-mt">
                <p class="cd-panel__intro">{{ t('contracts.logsTabIntro') }}</p>
                <div class="cd-logs-toolbar">
                    <label class="cd-logs-filter">
                        <span class="cd-logs-filter__label">{{ t('contracts.logsFilterAction') }}</span>
                        <select v-model="logActionFilter" class="ppms-input" @change="loadLogs">
                            <option value="">{{ t('contracts.logsFilterAll') }}</option>
                            <option v-for="act in logActions" :key="act" :value="act">{{ act }}</option>
                        </select>
                    </label>
                </div>
                <div v-if="logsLoading" class="ppms-loading-line">{{ t('common.loading') }}</div>
                <div v-else class="ppms-table-scroll cd-logs-scroll">
                    <table class="ppms-table cd-table">
                        <thead>
                            <tr>
                                <th>{{ t('contracts.logsTime') }}</th>
                                <th>{{ t('contracts.logsAction') }}</th>
                                <th>{{ t('contracts.logsUser') }}</th>
                                <th>{{ t('contracts.logsPayload') }}</th>
                            </tr>
                        </thead>
                        <tbody v-if="logs.length">
                            <tr v-for="log in logs" :key="log.id">
                                <td class="cd-table__muted">{{ formatDateTimeDisplay(log.created_at) }}</td>
                                <td><code class="cd-log-action">{{ log.action }}</code></td>
                                <td>{{ log.user?.name || log.user_id || '—' }}</td>
                                <td class="cd-log-payload">
                                    <pre class="cd-log-payload__pre">{{ logPayloadSummary(log) }}</pre>
                                </td>
                            </tr>
                        </tbody>
                        <tbody v-else>
                            <tr>
                                <td colspan="4" class="cd-empty">{{ t('contracts.emptyLogs') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </template>

        <div v-else class="cd-not-found ppms-card">
            <p class="cd-not-found__title">{{ t('contracts.detailNotFound') }}</p>
            <router-link to="/contracts" class="ppms-btn-primary">{{ t('contracts.backToList') }}</router-link>
        </div>

        <div v-if="submitOpen" class="contract-modal__backdrop" role="presentation" @click.self="submitOpen = false">
            <div class="contract-modal cd-submit-modal ppms-card" role="dialog" aria-modal="true" aria-labelledby="cd-submit-title">
                <div class="contract-modal__head">
                    <div class="contract-modal__head-text">
                        <h2 id="cd-submit-title" class="contract-modal__title">{{ t('contracts.submitApproval') }}</h2>
                        <p class="contract-modal__subtitle">{{ t('contracts.submitModalHint') }}</p>
                    </div>
                    <button type="button" class="contract-modal__close" :aria-label="t('common.cancel')" @click="submitOpen = false">
                        ×
                    </button>
                </div>
                <div class="contract-modal__body">
                    <div class="cd-submit-approvers">
                        <span class="cd-submit-approvers__label">{{ t('contracts.approverPickerLabel') }}</span>
                        <p class="ppms-hint cd-submit-approvers__hint">{{ t('contracts.approverPickerHint') }}</p>
                        <O1UserPicker
                            v-model="approverIds"
                            :users="[]"
                            :search-placeholder="t('contracts.approverSearchPlaceholder')"
                            :search-aria="t('contracts.approverAriaSearch')"
                            :list-aria="t('contracts.approverAriaList')"
                            :empty-text="t('contracts.approverEmpty')"
                            :remove-chip-label="approverChipRemoveLabel"
                            remote-lookup
                            :lookup-min-chars="1"
                            :remote-loading-text="t('common.loading')"
                        />
                    </div>
                    <p v-if="submitErr" class="ppms-error">{{ submitErr }}</p>
                </div>
                <div class="contract-modal__footer">
                    <div class="contract-modal__actions">
                        <button type="button" class="ppms-btn-ghost" @click="submitOpen = false">{{ t('common.cancel') }}</button>
                        <button type="button" class="ppms-btn-primary contract-modal__submit" @click="submitApproval">{{ t('common.send') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="payModalOpen"
            class="contract-modal__backdrop"
            role="presentation"
            @click.self="!paySubmitting && (payModalOpen = false)"
        >
            <div class="contract-modal cd-pay-modal ppms-card" role="dialog" aria-modal="true" aria-labelledby="cd-pay-title">
                <div class="contract-modal__head">
                    <div class="contract-modal__head-text">
                        <h2 id="cd-pay-title" class="contract-modal__title">{{ t('contracts.paymentModalTitle') }}</h2>
                        <p class="contract-modal__subtitle">{{ t('contracts.paymentModalHint') }}</p>
                    </div>
                    <button
                        type="button"
                        class="contract-modal__close"
                        :disabled="paySubmitting"
                        :aria-label="t('common.cancel')"
                        @click="payModalOpen = false"
                    >
                        ×
                    </button>
                </div>
                <div class="contract-modal__body">
                    <div class="contract-modal__field">
                        <label for="cd-pay-remaining">{{ t('contracts.paymentRemainingLabel') }}</label>
                        <p id="cd-pay-remaining" class="cd-pay-modal__highlight">{{ formatMoneyDisplay(payRemaining) }}</p>
                    </div>
                    <div class="contract-modal__field">
                        <label for="cd-pay-amount">{{ t('contracts.paymentAmountLabel') }}</label>
                        <input
                            id="cd-pay-amount"
                            v-model="payAmountDisplay"
                            type="text"
                            class="ppms-input"
                            inputmode="numeric"
                            autocomplete="off"
                            :placeholder="t('contracts.paymentAmountPlaceholder')"
                        />
                        <p v-if="payAmountInWords" class="contract-modal__amount-words">{{ payAmountInWords }}</p>
                    </div>
                    <div class="contract-modal__field">
                        <label for="cd-pay-proof">{{ t('contracts.paymentProofLabel') }}</label>
                        <p class="ppms-hint">{{ t('contracts.paymentProofHint') }}</p>
                        <input
                            id="cd-pay-proof"
                            ref="payProofInputRef"
                            type="file"
                            class="ppms-input cd-pay-modal__file"
                            :accept="paymentProofAccept"
                            @change="onPayProofPick"
                        />
                        <p v-if="payProofFile" class="cd-pay-modal__file-name">
                            {{ payProofFile.name }}
                            <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="clearPayProof">
                                {{ t('contracts.paymentProofRemove') }}
                            </button>
                        </p>
                    </div>
                </div>
                <div class="contract-modal__footer">
                    <div class="contract-modal__actions">
                        <button type="button" class="ppms-btn-ghost" :disabled="paySubmitting" @click="payModalOpen = false">
                            {{ t('common.cancel') }}
                        </button>
                        <button type="button" class="ppms-btn-primary" :disabled="paySubmitting" @click="submitPayPayment">
                            <span v-if="paySubmitting">{{ t('contracts.modalSaving') }}</span>
                            <span v-else>{{ t('contracts.paymentSubmit') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="editOpen"
            class="contract-modal__backdrop"
            role="presentation"
            @click.self="!editBusy && (editOpen = false)"
        >
            <div class="contract-modal ppms-card" role="dialog" aria-modal="true" aria-labelledby="modal-edit-title">
                <div class="contract-modal__head">
                    <div class="contract-modal__head-text">
                        <h2 id="modal-edit-title" class="contract-modal__title">{{ t('contracts.modalEditTitle') }}</h2>
                    </div>
                    <button
                        type="button"
                        class="contract-modal__close"
                        :disabled="editBusy"
                        :aria-label="t('common.cancel')"
                        @click="editOpen = false"
                    >
                        ×
                    </button>
                </div>
                <form class="contract-modal__form" @submit.prevent="saveEdit">
                    <div class="contract-modal__body">
                        <section class="contract-modal__section">
                            <h3 class="contract-modal__section-title">{{ t('contracts.sectionParties') }}</h3>
                            <div class="contract-modal__grid contract-modal__grid--2">
                                <div class="contract-modal__field">
                                    <label for="em-vendor">{{ t('contracts.fieldVendor') }}</label>
                                    <input
                                        id="em-vendor"
                                        v-model.trim="editForm.vendor_name"
                                        type="text"
                                        class="ppms-input"
                                        required
                                        list="em-vendor-list"
                                        :placeholder="t('contracts.fieldVendorPlaceholder')"
                                    />
                                    <datalist id="em-vendor-list">
                                        <option v-for="v in lookups.vendors" :key="v.id" :value="v.name" />
                                    </datalist>
                                </div>
                                <div class="contract-modal__field">
                                    <label for="em-product">{{ t('contracts.fieldProduct') }}</label>
                                    <input
                                        id="em-product"
                                        v-model.trim="editForm.product_name"
                                        type="text"
                                        class="ppms-input"
                                        required
                                        :placeholder="t('contracts.fieldProductPlaceholder')"
                                    />
                                </div>
                                <div class="contract-modal__field contract-modal__field--full">
                                    <div class="contract-modal__dept-toolbar">
                                        <label for="em-dept">{{ t('contracts.fieldDepartment') }}</label>
                                        <button
                                            type="button"
                                            class="ppms-btn-ghost ppms-btn-sm contract-modal__dept-add"
                                            @click="deptCreateOpenEdit = !deptCreateOpenEdit"
                                        >
                                            {{ deptCreateOpenEdit ? t('contracts.addDepartmentClose') : t('contracts.addDepartment') }}
                                        </button>
                                    </div>
                                    <select id="em-dept" v-model.number="editForm.department_id" class="ppms-input" required>
                                        <option disabled :value="0">{{ t('contracts.fieldDepartment') }}</option>
                                        <option v-for="d in lookups.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                    </select>
                                    <label for="em-block" class="contract-modal__sub-label">{{ t('contracts.fieldBlock') }}</label>
                                    <p class="ppms-hint contract-modal__field-hint">{{ t('contracts.fieldBlockHint') }}</p>
                                    <select id="em-block" v-model.number="editForm.block_id" class="ppms-input">
                                        <option :value="0">{{ t('contracts.allBlocks') }}</option>
                                        <option v-for="b in lookups.blocks" :key="b.id" :value="b.id">{{ b.name }}</option>
                                    </select>
                                    <div v-if="deptCreateOpenEdit" class="contract-modal__dept-inline">
                                        <input
                                            v-model.trim="newDeptNameEdit"
                                            type="text"
                                            class="ppms-input"
                                            :placeholder="t('contracts.departmentNewNamePlaceholder')"
                                            maxlength="255"
                                        />
                                        <input
                                            v-model.trim="newDeptCodeEdit"
                                            type="text"
                                            class="ppms-input"
                                            :placeholder="t('contracts.departmentNewCodePlaceholder')"
                                            maxlength="64"
                                        />
                                        <button
                                            type="button"
                                            class="ppms-btn-primary ppms-btn-sm"
                                            :disabled="departmentSavingEdit"
                                            @click="submitNewDepartmentEdit"
                                        >
                                            {{ t('contracts.departmentCreate') }}
                                        </button>
                                    </div>
                                </div>
                                <div class="contract-modal__field contract-modal__field--full">
                                    <label>{{ t('contracts.fieldFollower') }}</label>
                                    <p class="ppms-hint contract-modal__field-hint">{{ t('contracts.fieldFollowerHint') }}</p>
                                    <O1UserLookupSelect
                                        v-model="editForm.followed_by_id"
                                        :base-users="[]"
                                        :search-placeholder="t('projects.createUserSearchPlaceholder')"
                                        :search-aria="t('contracts.followerSearchAria')"
                                        :min-hint="t('projects.createUserSearchMinHint')"
                                        :empty-text="t('projects.createUserSearchEmpty')"
                                        :loading-text="t('common.loading')"
                                        :clear-aria="t('contracts.followerClearAria')"
                                    />
                                </div>
                            </div>
                        </section>
                        <section class="contract-modal__section">
                            <h3 class="contract-modal__section-title">{{ t('contracts.sectionScope') }}</h3>
                            <div class="contract-modal__field">
                                <label for="em-scope">{{ t('contracts.fieldScope') }}</label>
                                <textarea id="em-scope" v-model="editForm.scope" class="ppms-input contract-modal__textarea" rows="3" />
                            </div>
                        </section>
                        <section class="contract-modal__section">
                            <h3 class="contract-modal__section-title">{{ t('contracts.sectionTerm') }}</h3>
                            <div class="contract-modal__grid contract-modal__grid--2">
                                <div class="contract-modal__field">
                                    <label for="em-start">{{ t('contracts.fieldStart') }}</label>
                                    <input id="em-start" v-model="editForm.start_date" type="date" class="ppms-input" required />
                                </div>
                                <div class="contract-modal__field">
                                    <label for="em-end">{{ t('contracts.fieldEnd') }}</label>
                                    <input id="em-end" v-model="editForm.end_date" type="date" class="ppms-input" required />
                                </div>
                                <div class="contract-modal__field">
                                    <label for="em-value">{{ t('contracts.fieldValue') }}</label>
                                    <input
                                        id="em-value"
                                        v-model="editTotalValueDisplay"
                                        type="text"
                                        class="ppms-input"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        required
                                        placeholder="0"
                                    />
                                    <p v-if="editTotalValueInWords" class="contract-modal__amount-words">{{ editTotalValueInWords }}</p>
                                </div>
                                <div class="contract-modal__field">
                                    <label for="em-cycle">{{ t('contracts.fieldCycle') }}</label>
                                    <select id="em-cycle" v-model="editForm.payment_cycle" class="ppms-input" required>
                                        <option value="monthly">{{ t('contracts.cycleMonthly') }}</option>
                                        <option value="quarterly">{{ t('contracts.cycleQuarterly') }}</option>
                                        <option value="yearly">{{ t('contracts.cycleYearly') }}</option>
                                    </select>
                                </div>
                            </div>
                        </section>
                    </div>
                    <div class="contract-modal__footer">
                        <p v-if="editErr" class="ppms-error contract-modal__error">{{ editErr }}</p>
                        <div class="contract-modal__actions">
                            <button type="button" class="ppms-btn-ghost" :disabled="editBusy" @click="editOpen = false">
                                {{ t('common.cancel') }}
                            </button>
                            <button type="submit" class="ppms-btn-primary contract-modal__submit" :disabled="editBusy">
                                <span v-if="editSaving">{{ t('contracts.modalSaving') }}</span>
                                <span v-else>{{ t('common.save') }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="previewOpen" class="contract-modal__backdrop" role="presentation" @click.self="closeFilePreview">
            <div class="contract-modal cd-preview-modal ppms-card" role="dialog" aria-modal="true" :aria-label="previewFileName">
                <div class="contract-modal__head">
                    <div class="contract-modal__head-text">
                        <h2 class="contract-modal__title">{{ previewFileName }}</h2>
                    </div>
                    <button type="button" class="contract-modal__close" :aria-label="t('common.close')" @click="closeFilePreview">×</button>
                </div>
                <div class="cd-preview-frame-wrap">
                    <iframe v-if="previewKind === 'pdf' && previewUrl" class="cd-preview-frame" :src="previewUrl" title="PDF" />
                    <img v-else-if="previewKind === 'image' && previewUrl" class="cd-preview-img" :src="previewUrl" alt="" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { formatApiUserMessage } from '@/bootstrap';
import { ppmsConfirm, ppmsToastError, ppmsToastSuccess } from '@/ppmsUi';
import { readVndAmountVietnamese } from '@/utils/vndReadWords';
import O1UserPicker from '@/pages/projects/components/detail/O1UserPicker.vue';
import O1UserLookupSelect from '@/pages/projects/components/detail/O1UserLookupSelect.vue';

const props = defineProps({
    id: { type: [String, Number], required: true },
});

const { t, locale } = useI18n();
const route = useRoute();

const loading = ref(true);
const contract = ref(null);
const me = ref(null);
const tab = ref('info');
const logs = ref([]);
const logsLoading = ref(false);
const logActionFilter = ref('');
const logActions = ref([]);

const previewOpen = ref(false);
const previewUrl = ref('');
const previewKind = ref('pdf');
const previewFileName = ref('');

const versionCompareA = ref(0);
const versionCompareB = ref(0);

const submitOpen = ref(false);
const approverIds = ref([]);
const submitErr = ref('');

const payModalOpen = ref(false);
const payPaymentId = ref(null);
const payAmountDigits = ref('');
const payProofFile = ref(null);
const payProofInputRef = ref(null);
const paySubmitting = ref(false);
const paymentProofAccept = 'image/*,.pdf,.PDF,application/pdf';

const editOpen = ref(false);
const editErr = ref('');
const editSaving = ref(false);
const editForm = reactive({
    vendor_name: '',
    product_name: '',
    department_id: 0,
    block_id: 0,
    scope: '',
    start_date: '',
    end_date: '',
    total_value: '',
    payment_cycle: 'monthly',
    followed_by_id: '',
});

const lookups = reactive({
    vendors: [],
    products: [],
    departments: [],
    blocks: [],
});

const deptCreateOpenEdit = ref(false);
const newDeptNameEdit = ref('');
const newDeptCodeEdit = ref('');
const departmentSavingEdit = ref(false);

const editBusy = computed(() => editSaving.value || departmentSavingEdit.value);

const editTotalValueDisplay = computed({
    get() {
        if (!editForm.total_value) return '';
        const n = Number(editForm.total_value);
        if (!Number.isFinite(n)) return editForm.total_value;
        return new Intl.NumberFormat('vi-VN').format(n);
    },
    set(raw) {
        editForm.total_value = String(raw ?? '').replace(/\D/g, '');
    },
});

function formatMoneyDisplay(value) {
    const n = Number(value);
    if (Number.isNaN(n)) {
        return value ?? '—';
    }
    const formatted = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(n);
    return `${formatted} VNĐ`;
}

function formatDateDisplay(iso) {
    if (!iso) return '—';
    const d = new Date(`${String(iso).slice(0, 10)}T12:00:00`);
    if (Number.isNaN(d.getTime())) return String(iso);
    return new Intl.DateTimeFormat(locale.value === 'vi' ? 'vi-VN' : 'en-GB', { dateStyle: 'medium' }).format(d);
}

function formatDateTimeDisplay(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return String(iso);
    return new Intl.DateTimeFormat(locale.value === 'vi' ? 'vi-VN' : 'en-GB', { dateStyle: 'medium', timeStyle: 'short' }).format(d);
}

function paymentCycleLabel(cycle) {
    const map = {
        monthly: 'contracts.cycleMonthly',
        quarterly: 'contracts.cycleQuarterly',
        yearly: 'contracts.cycleYearly',
    };
    return map[cycle] ? t(map[cycle]) : cycle;
}

function statusLabel(status) {
    const map = {
        draft: 'contracts.statusDraft',
        pending_approval: 'contracts.statusPending',
        active: 'contracts.statusActive',
        expired: 'contracts.statusExpired',
        terminated: 'contracts.statusTerminated',
    };
    return map[status] ? t(map[status]) : status;
}

function statusPillClass(status) {
    return {
        'cd-pill--draft': status === 'draft',
        'cd-pill--pending': status === 'pending_approval',
        'cd-pill--active': status === 'active',
        'cd-pill--expired': status === 'expired',
        'cd-pill--terminated': status === 'terminated',
    };
}

function parseDateOnly(str) {
    if (!str) return null;
    const d = new Date(`${String(str).slice(0, 10)}T12:00:00`);
    return Number.isNaN(d.getTime()) ? null : d;
}

function periodProgressPercent(row) {
    const start = parseDateOnly(row.start_date);
    const end = parseDateOnly(row.end_date);
    if (!start || !end) return null;
    const span = end.getTime() - start.getTime();
    if (span <= 0) return 100;
    if (row.status === 'terminated' || row.status === 'expired') {
        return 100;
    }
    const now = new Date();
    now.setHours(12, 0, 0, 0);
    const t0 = now.getTime();
    if (t0 <= start.getTime()) return 0;
    if (t0 >= end.getTime()) return 100;
    return Math.min(100, Math.max(0, Math.round(((t0 - start.getTime()) / span) * 100)));
}

function paymentStatusLabel(s) {
    const map = {
        pending: 'contracts.payPending',
        partial: 'contracts.payPartial',
        paid: 'contracts.payPaid',
        overdue: 'contracts.payOverdue',
    };
    return map[s] ? t(map[s]) : s;
}

function paymentPillClass(s) {
    return {
        'cd-pill--pay-pending': s === 'pending',
        'cd-pill--pay-partial': s === 'partial',
        'cd-pill--pay-paid': s === 'paid',
        'cd-pill--pay-overdue': s === 'overdue',
    };
}

function paymentRemainingAmount(p) {
    const total = Number(p.amount);
    const ap = Number(p.amount_paid ?? 0);
    if (p.status === 'paid') {
        return 0;
    }
    if (Number.isNaN(total)) {
        return 0;
    }
    return Math.max(0, total - (Number.isNaN(ap) ? 0 : ap));
}

function canRecordPayment(p) {
    if (!canMarkPaid.value) {
        return false;
    }
    if (p.status === 'paid') {
        return false;
    }
    return paymentRemainingAmount(p) > 0.0001;
}

function approvalStatusLabel(s) {
    const map = {
        pending: 'contracts.approvalStatePending',
        queued: 'contracts.approvalStateQueued',
        approved: 'contracts.approvalStateApproved',
        rejected: 'contracts.approvalStateRejected',
    };
    return map[s] ? t(map[s]) : s;
}

function approvalPillClass(s) {
    return {
        'cd-pill--appr-pending': s === 'pending',
        'cd-pill--appr-queued': s === 'queued',
        'cd-pill--appr-approved': s === 'approved',
        'cd-pill--appr-rejected': s === 'rejected',
    };
}

function approvalItemClass(status) {
    return {
        'cd-approval-timeline__item--current': status === 'pending',
        'cd-approval-timeline__item--done': status === 'approved',
        'cd-approval-timeline__item--rejected': status === 'rejected',
        'cd-approval-timeline__item--queued': status === 'queued',
    };
}

function approvalDotClass(status) {
    return {
        'cd-approval-timeline__dot--approved': status === 'approved',
        'cd-approval-timeline__dot--rejected': status === 'rejected',
        'cd-approval-timeline__dot--pending': status === 'pending',
        'cd-approval-timeline__dot--queued': status === 'queued',
    };
}

const editTotalValueInWords = computed(() => {
    if (!editForm.total_value) return '';
    const n = Number(editForm.total_value);
    if (!Number.isFinite(n) || n < 0) return '';
    if (locale.value === 'vi') {
        return `${t('contracts.amountInWordsPrefix')}: ${readVndAmountVietnamese(n)}`;
    }
    return `${t('contracts.amountInWordsPrefix')}: ${formatMoneyDisplay(n)}`;
});

const paymentTotals = computed(() => {
    const payments = contract.value?.payments || [];
    let scheduled = 0;
    let paid = 0;
    let outstanding = 0;
    for (const p of payments) {
        const total = Number(p.amount);
        if (Number.isNaN(total)) {
            continue;
        }
        scheduled += total;
        const ap = Number(p.amount_paid ?? 0);
        paid += Number.isNaN(ap) ? 0 : ap;
        const rem = paymentRemainingAmount(p);
        if (rem > 0.0001) {
            outstanding += rem;
        }
    }
    const pct = scheduled > 0 ? Math.min(100, Math.round((paid / scheduled) * 100)) : 0;
    return { scheduled, paid, outstanding, pct };
});

const payTargetPayment = computed(() => {
    const id = payPaymentId.value;
    if (!id || !contract.value?.payments) {
        return null;
    }
    return contract.value.payments.find((p) => p.id === id) ?? null;
});

const payRemaining = computed(() => {
    const p = payTargetPayment.value;
    if (!p) {
        return 0;
    }
    return paymentRemainingAmount(p);
});

const payAmountDisplay = computed({
    get() {
        if (!payAmountDigits.value) {
            return '';
        }
        const n = Number(payAmountDigits.value);
        if (!Number.isFinite(n)) {
            return payAmountDigits.value;
        }
        return new Intl.NumberFormat('vi-VN').format(n);
    },
    set(raw) {
        payAmountDigits.value = String(raw ?? '').replace(/\D/g, '');
    },
});

const payAmountInWords = computed(() => {
    if (!payAmountDigits.value) {
        return '';
    }
    const n = Number(payAmountDigits.value);
    if (!Number.isFinite(n) || n <= 0) {
        return '';
    }
    if (locale.value === 'vi') {
        return `${t('contracts.amountInWordsPrefix')}: ${readVndAmountVietnamese(n)}`;
    }
    return `${t('contracts.amountInWordsPrefix')}: ${formatMoneyDisplay(n)}`;
});

const sortedApprovals = computed(() => {
    const list = contract.value?.approvals;
    if (!Array.isArray(list)) {
        return [];
    }
    return [...list].sort((a, b) => (a.step ?? 0) - (b.step ?? 0));
});

const sortedVersions = computed(() => {
    const list = contract.value?.versions;
    if (!Array.isArray(list)) {
        return [];
    }
    return [...list].sort((a, b) => a.version - b.version);
});

const versionComparePair = computed(() => {
    const list = sortedVersions.value;
    const a = list.find((x) => x.id === versionCompareA.value);
    const b = list.find((x) => x.id === versionCompareB.value);
    if (!a || !b) {
        return null;
    }
    return { a, b };
});

watch(
    sortedVersions,
    (vers) => {
        if (vers.length >= 2) {
            versionCompareA.value = vers[0].id;
            versionCompareB.value = vers[1].id;
        } else if (vers.length === 1) {
            versionCompareA.value = vers[0].id;
            versionCompareB.value = vers[0].id;
        } else {
            versionCompareA.value = 0;
            versionCompareB.value = 0;
        }
    },
    { immediate: true },
);

const fileInput = ref(null);
const pendingFile = ref(null);
const uploadAsVersion = ref(false);
const uploading = ref(false);

const canEdit = computed(() => {
    if (!contract.value || !me.value) {
        return false;
    }
    const manage = ['admin', 'pm', 'tl'].includes(me.value.role);

    return manage || contract.value.created_by === me.value.id;
});
const canManage = computed(() => ['admin', 'pm', 'tl'].includes(me.value?.role));

const canSubmit = computed(() => {
    if (!contract.value || contract.value.status !== 'draft') {
        return false;
    }
    return (
        canManage.value ||
        me.value?.id === contract.value.created_by
    );
});

const canTerminate = computed(() => canManage.value);

const isAdmin = computed(() => me.value?.role === 'admin');

const canMarkPaid = computed(() => canManage.value && contract.value?.status === 'active');

const isCurrentApprover = computed(() => {
    if (!contract.value || contract.value.status !== 'pending_approval' || !me.value) {
        return false;
    }
    const pending = (contract.value.approvals || []).find((a) => a.status === 'pending');
    return pending && pending.approver_id === me.value.id;
});

function showApprovalStepActions(a) {
    return a.status === 'pending' && isCurrentApprover.value;
}

async function loadMe() {
    try {
        const { data } = await axios.get('/api/user');
        me.value = data;
    } catch {
        me.value = null;
    }
}

async function loadLookups() {
    try {
        const { data } = await axios.get('/api/contract-lookups');
        lookups.vendors = data.vendors || [];
        lookups.products = data.products || [];
        lookups.departments = data.departments || [];
        lookups.blocks = data.blocks || [];
    } catch {
        lookups.vendors = [];
        lookups.products = [];
        lookups.departments = [];
        lookups.blocks = [];
    }
}

async function submitNewDepartmentEdit() {
    const name = newDeptNameEdit.value.trim();
    if (!name) {
        ppmsToastError(t('contracts.departmentNameRequired'));
        return;
    }
    departmentSavingEdit.value = true;
    try {
        const { data } = await axios.post('/api/departments', {
            name,
            code: newDeptCodeEdit.value.trim() || null,
        });
        const d = data.data ?? data;
        await loadLookups();
        if (d?.id != null) {
            editForm.department_id = d.id;
        }
        deptCreateOpenEdit.value = false;
        newDeptNameEdit.value = '';
        newDeptCodeEdit.value = '';
        ppmsToastSuccess(t('contracts.departmentCreated'));
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    } finally {
        departmentSavingEdit.value = false;
    }
}

async function loadContract() {
    loading.value = true;
    try {
        const { data } = await axios.get(`/api/contracts/${props.id}`);
        contract.value = data.data || data;
        logs.value = [];
        logActionFilter.value = '';
    } catch (e) {
        contract.value = null;
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    } finally {
        loading.value = false;
    }
}

async function loadLogActions() {
    try {
        const { data } = await axios.get(`/api/contracts/${props.id}/logs/actions`);
        logActions.value = data.data || [];
    } catch {
        logActions.value = [];
    }
}

async function loadLogs() {
    logsLoading.value = true;
    try {
        const params = {};
        if (logActionFilter.value) {
            params.action = logActionFilter.value;
        }
        const { data } = await axios.get(`/api/contracts/${props.id}/logs`, { params });
        logs.value = data.data || [];
    } catch {
        logs.value = [];
    } finally {
        logsLoading.value = false;
    }
}

function logPayloadSummary(log) {
    const o = log.old_data;
    const n = log.new_data;
    if (!o && !n) {
        return '—';
    }
    try {
        if (n && typeof n === 'object' && !Array.isArray(n)) {
            const keys = Object.keys(n);
            const lines = [];
            for (const k of keys.slice(0, 12)) {
                const nv = n[k];
                const ov = o?.[k];
                if (JSON.stringify(ov) === JSON.stringify(nv)) {
                    continue;
                }
                const s = typeof nv === 'object' ? JSON.stringify(nv) : String(nv);
                lines.push(`${k}: ${s.length > 120 ? `${s.slice(0, 120)}…` : s}`);
            }
            if (lines.length) {
                return lines.join('\n');
            }
        }
        const raw = JSON.stringify(n ?? o);
        return raw.length > 500 ? `${raw.slice(0, 500)}…` : raw;
    } catch {
        return '—';
    }
}

function canPreviewFile(f) {
    const m = (f.file_type || '').toLowerCase();
    const n = (f.file_name || '').toLowerCase();
    if (m.includes('pdf')) {
        return true;
    }
    if (m.startsWith('image/')) {
        return true;
    }
    if (n.endsWith('.pdf')) {
        return true;
    }
    return /\.(png|jpe?g|gif|webp|bmp)$/i.test(n);
}

function fileIconKind(f) {
    const m = (f.file_type || '').toLowerCase();
    const n = (f.file_name || '').toLowerCase();
    if (m.includes('pdf') || n.endsWith('.pdf')) {
        return 'pdf';
    }
    if (m.startsWith('image/')) {
        return 'image';
    }
    return 'doc';
}

async function openFilePreview(f) {
    closeFilePreview();
    previewFileName.value = f.file_name || 'file';
    try {
        const res = await axios.get(`/api/contracts/${props.id}/files/${f.id}/preview`, { responseType: 'blob' });
        const blob = res.data;
        const mt = blob.type || '';
        if (mt.includes('pdf') || (f.file_name || '').toLowerCase().endsWith('.pdf')) {
            previewKind.value = 'pdf';
        } else {
            previewKind.value = 'image';
        }
        previewUrl.value = URL.createObjectURL(blob);
        previewOpen.value = true;
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

function closeFilePreview() {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = '';
    }
    previewOpen.value = false;
}

async function exportSummaryPdf() {
    try {
        const res = await axios.get(`/api/contracts/${props.id}/summary.pdf`, { responseType: 'blob' });
        const url = URL.createObjectURL(res.data);
        const a = document.createElement('a');
        const code = contract.value?.code || String(props.id);
        a.href = url;
        a.download = `contract-${code}.pdf`;
        a.click();
        URL.revokeObjectURL(url);
        ppmsToastSuccess(t('contracts.exportPdfReady'));
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

function openEdit() {
    if (!contract.value) {
        return;
    }
    editErr.value = '';
    editForm.vendor_name = contract.value.vendor?.name || '';
    editForm.product_name = contract.value.product?.name || '';
    editForm.department_id = contract.value.department_id ?? contract.value.department?.id ?? 0;
    editForm.block_id = contract.value.block_id ?? contract.value.block?.id ?? 0;
    editForm.scope = contract.value.scope || '';
    editForm.start_date = contract.value.start_date || '';
    editForm.end_date = contract.value.end_date || '';
    editForm.total_value = String(contract.value.total_value ?? '').replace(/\D/g, '');
    editForm.payment_cycle = contract.value.payment_cycle || 'monthly';
    editForm.followed_by_id =
        contract.value.followed_by_id != null && contract.value.followed_by_id !== ''
            ? String(contract.value.followed_by_id)
            : '';
    deptCreateOpenEdit.value = false;
    newDeptNameEdit.value = '';
    newDeptCodeEdit.value = '';
    editOpen.value = true;
}

async function saveEdit() {
    editErr.value = '';
    editSaving.value = true;
    try {
        await axios.patch(`/api/contracts/${props.id}`, {
            vendor_name: editForm.vendor_name,
            product_name: editForm.product_name,
            department_id: editForm.department_id,
            block_id: editForm.block_id || null,
            scope: editForm.scope || null,
            start_date: editForm.start_date,
            end_date: editForm.end_date,
            total_value: editForm.total_value === '' ? 0 : Number(editForm.total_value),
            payment_cycle: editForm.payment_cycle,
            followed_by_id: editForm.followed_by_id ? Number(editForm.followed_by_id) : null,
        });
        ppmsToastSuccess(t('contracts.updated'));
        editOpen.value = false;
        await loadContract();
    } catch (e) {
        editErr.value = formatApiUserMessage(e, t('contracts.loadError'));
    } finally {
        editSaving.value = false;
    }
}

async function removeDraft() {
    const confirmKey =
        isAdmin.value && contract.value?.status !== 'draft' ? 'contracts.deleteConfirmAdmin' : 'contracts.deleteConfirm';
    if (!(await ppmsConfirm(t(confirmKey)))) {
        return;
    }
    try {
        await axios.delete(`/api/contracts/${props.id}`);
        ppmsToastSuccess(t('contracts.deletedToTrash'));
        window.location.href = '/contracts';
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

function approverChipRemoveLabel(u) {
    return t('contracts.approverChipRemove', { name: u.name || String(u.id) });
}

async function submitApproval() {
    submitErr.value = '';
    const parts = (approverIds.value || [])
        .map((x) => Number(x))
        .filter((n) => Number.isFinite(n) && n > 0);
    if (parts.length === 0) {
        submitErr.value = t('contracts.submitApproversRequired');
        return;
    }
    if (!(await ppmsConfirm(t('contracts.submitConfirm')))) {
        return;
    }
    try {
        await axios.post(`/api/contracts/${props.id}/submit`, {
            approvers: parts.map((user_id) => ({ user_id })),
        });
        ppmsToastSuccess(t('contracts.submitted'));
        submitOpen.value = false;
        await loadContract();
    } catch (e) {
        submitErr.value = formatApiUserMessage(e, t('contracts.loadError'));
    }
}

watch(submitOpen, (open) => {
    if (open) {
        approverIds.value = [];
        submitErr.value = '';
    }
});

async function doApprove() {
    try {
        await axios.post(`/api/contracts/${props.id}/approve`);
        ppmsToastSuccess(t('contracts.approved'));
        await loadContract();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

async function doReject() {
    try {
        await axios.post(`/api/contracts/${props.id}/reject`);
        ppmsToastSuccess(t('contracts.rejected'));
        await loadContract();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

async function doTerminate() {
    if (!(await ppmsConfirm(t('contracts.terminateConfirm')))) {
        return;
    }
    try {
        await axios.post(`/api/contracts/${props.id}/terminate`);
        ppmsToastSuccess(t('contracts.terminated'));
        await loadContract();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

function openPayModal(paymentId) {
    payPaymentId.value = paymentId;
    const p = contract.value?.payments?.find((x) => x.id === paymentId);
    const rem = p ? paymentRemainingAmount(p) : 0;
    payAmountDigits.value = rem > 0 ? String(Math.round(rem)) : '';
    payProofFile.value = null;
    if (payProofInputRef.value) {
        payProofInputRef.value.value = '';
    }
    payModalOpen.value = true;
}

async function submitPayPayment() {
    const amt = Number(payAmountDigits.value);
    if (!Number.isFinite(amt) || amt <= 0) {
        ppmsToastError(t('contracts.paymentAmountInvalid'));
        return;
    }
    const rem = payRemaining.value;
    if (amt > rem + 0.0001) {
        ppmsToastError(t('contracts.paymentAmountTooHigh'));
        return;
    }
    if (!(await ppmsConfirm(t('contracts.paymentConfirm', { amount: formatMoneyDisplay(amt) })))) {
        return;
    }
    paySubmitting.value = true;
    try {
        const fd = new FormData();
        fd.append('paid_amount', String(amt));
        if (payProofFile.value) {
            fd.append('file', payProofFile.value);
        }
        await axios.post(`/api/contracts/${props.id}/payments/${payPaymentId.value}/mark-paid`, fd);
        ppmsToastSuccess(t('contracts.markedPaid'));
        payModalOpen.value = false;
        await loadContract();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    } finally {
        paySubmitting.value = false;
    }
}

function onPayProofPick(e) {
    payProofFile.value = e.target.files?.[0] || null;
}

function clearPayProof() {
    payProofFile.value = null;
    if (payProofInputRef.value) {
        payProofInputRef.value.value = '';
    }
}

function onFilePick(e) {
    const f = e.target.files?.[0];
    pendingFile.value = f || null;
    if (f) {
        doUpload();
    }
}

async function doUpload() {
    if (!pendingFile.value) {
        return;
    }
    uploading.value = true;
    try {
        const fd = new FormData();
        fd.append('file', pendingFile.value);
        fd.append('create_version', uploadAsVersion.value ? '1' : '0');
        await axios.post(`/api/contracts/${props.id}/files`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        ppmsToastSuccess(t('contracts.fileUploaded'));
        pendingFile.value = null;
        if (fileInput.value) {
            fileInput.value.value = '';
        }
        await loadContract();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    } finally {
        uploading.value = false;
    }
}

async function downloadFile(fileId) {
    try {
        const res = await axios.get(`/api/contracts/${props.id}/files/${fileId}/download`, { responseType: 'blob' });
        const dispo = res.headers['content-disposition'];
        let name = 'download';
        if (dispo && dispo.includes('filename=')) {
            name = dispo.split('filename=')[1].replace(/"/g, '').trim();
        }
        const url = URL.createObjectURL(res.data);
        const a = document.createElement('a');
        a.href = url;
        a.download = name;
        a.click();
        URL.revokeObjectURL(url);
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

watch(
    () => route.params.id,
    () => {
        loadContract();
        logs.value = [];
    },
);

watch(tab, (v) => {
    if (v === 'logs') {
        loadLogActions();
        loadLogs();
    }
});

onUnmounted(() => {
    closeFilePreview();
});

onMounted(async () => {
    await loadMe();
    await Promise.all([loadContract(), loadLookups()]);
});
</script>

<style scoped>
.ppms-contract-header {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}
.ppms-contract-title {
    margin: 8px 0 4px;
    font-size: 1.5rem;
}
.ppms-contract-meta {
    margin: 0;
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
    color: var(--ppms-muted, #64748b);
}
.ppms-contract-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.ppms-back {
    font-size: 0.9rem;
    text-decoration: none;
}
.ppms-back:hover {
    text-decoration: underline;
}
.ppms-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    border-bottom: 1px solid var(--ppms-border, #e2e8f0);
    margin-bottom: 0;
}
.ppms-tab {
    padding: 10px 16px;
    border: none;
    background: transparent;
    cursor: pointer;
    font: inherit;
    color: var(--ppms-muted, #64748b);
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
}
.ppms-tab--active {
    color: var(--ppms-accent, #2563eb);
    border-bottom-color: var(--ppms-accent, #2563eb);
    font-weight: 600;
}
.ppms-mt {
    margin-top: 16px;
}
.ppms-mb {
    margin-bottom: 12px;
}
.ppms-dl {
    display: grid;
    gap: 12px;
}
.ppms-dl dt {
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--ppms-muted, #64748b);
}
.ppms-dl dd {
    margin: 4px 0 0;
}
.ppms-file-upload {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}
.ppms-checkbox {
    display: inline-flex;
    gap: 8px;
    align-items: center;
    font-size: 0.9rem;
}
.ppms-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 80;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 24px;
    overflow: auto;
}
.ppms-modal {
    width: min(480px, 100%);
    margin-top: 48px;
}
.ppms-modal-title {
    margin: 0 0 12px;
}
.ppms-modal-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 12px;
}
.ppms-btn-danger {
    color: #b91c1c;
}
.ppms-sr-file {
    position: absolute;
    width: 0;
    height: 0;
    opacity: 0;
    pointer-events: none;
}

/* —— Contract detail: clearer hierarchy —— */
.cd-header {
    padding-bottom: 4px;
    border-bottom: 1px solid var(--ppms-border, #e2e8f0);
    margin-bottom: 0;
}
.cd-header__title {
    letter-spacing: -0.02em;
}
.cd-header__badges {
    margin-top: 6px;
}
.cd-header__summary {
    margin: 10px 0 0;
    font-size: 0.9rem;
    line-height: 1.55;
    color: var(--ppms-fg, #0f172a);
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 6px 10px;
}
.cd-header__summary-key {
    display: block;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--ppms-muted, #64748b);
    margin-bottom: 2px;
}
.cd-header__vendor-link {
    color: #1d4ed8;
    text-decoration: underline;
    text-underline-offset: 2px;
}
.cd-header__vendor-link:hover {
    color: #1e40af;
}
.cd-header__sep {
    color: #cbd5e1;
    user-select: none;
}
.cd-header__dates {
    margin: 8px 0 0;
    font-size: 0.875rem;
    color: var(--ppms-muted, #64748b);
}
.cd-header__dates-label {
    font-weight: 600;
    color: var(--ppms-fg, #334155);
    margin-right: 6px;
}
.cd-header__approval-nudge {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px 14px;
    margin: 12px 0 0;
    padding: 10px 14px;
    border-radius: 10px;
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
    border: 1px solid #c7d2fe;
    font-size: 0.875rem;
    color: #3730a3;
}
.cd-header__approval-nudge__txt {
    flex: 1;
    min-width: 0;
    line-height: 1.45;
}

.cd-pill {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.02em;
}
.cd-pill--sm {
    padding: 2px 8px;
    font-size: 0.75rem;
}
.cd-pill--draft {
    background: #f1f5f9;
    color: #475569;
}
.cd-pill--pending {
    background: #fef3c7;
    color: #b45309;
}
.cd-pill--active {
    background: #d1fae5;
    color: #047857;
}
.cd-pill--expired {
    background: #e2e8f0;
    color: #475569;
}
.cd-pill--terminated {
    background: #fee2e2;
    color: #b91c1c;
}
.cd-pill--pay-pending {
    background: #fef9c3;
    color: #854d0e;
}
.cd-pill--pay-paid {
    background: #d1fae5;
    color: #047857;
}
.cd-pill--pay-overdue {
    background: #fee2e2;
    color: #b91c1c;
}
.cd-pill--pay-partial {
    background: #fef9c3;
    color: #854d0e;
}
.cd-pay-modal__highlight {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--ppms-fg, #0f172a);
}
.cd-pay-modal__file {
    padding: 8px 0;
}
.cd-pay-modal__file-name {
    margin: 8px 0 0;
    font-size: 0.875rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}
.cd-pill--appr-pending {
    background: #dbeafe;
    color: #1d4ed8;
}
.cd-pill--appr-queued {
    background: #f1f5f9;
    color: #475569;
}
.cd-pill--appr-approved {
    background: #d1fae5;
    color: #047857;
}
.cd-pill--appr-rejected {
    background: #fee2e2;
    color: #b91c1c;
}

.cd-tabs {
    margin-top: 16px;
    padding: 0 2px;
    border-radius: 10px 10px 0 0;
    background: rgba(248, 250, 252, 0.9);
}
.cd-tab__count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    padding: 0 5px;
    margin-left: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    border-radius: 999px;
    background: rgba(79, 70, 229, 0.12);
    color: #4338ca;
}

.cd-panel {
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
}
.cd-panel__intro {
    margin: 0 0 14px;
    font-size: 0.875rem;
    line-height: 1.5;
    color: var(--ppms-muted, #64748b);
    max-width: 48rem;
}

.cd-detail__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 14px;
}
@media (max-width: 768px) {
    .cd-detail__grid {
        grid-template-columns: 1fr;
    }
}
.cd-detail__card {
    padding: 14px 16px;
    border-radius: 12px;
    border: 1px solid var(--ppms-border, #e2e8f0);
    background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
}
.cd-detail__card--full {
    margin-top: 0;
}
.cd-detail__card-title {
    margin: 0 0 12px;
    font-size: 0.6875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--ppms-muted, #64748b);
}
.cd-detail__dl {
    display: grid;
    gap: 12px;
    margin: 0;
}
.cd-detail__dl dt {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--ppms-muted, #64748b);
    margin: 0;
}
.cd-detail__dl dd {
    margin: 4px 0 0;
    font-size: 0.9375rem;
    color: var(--ppms-fg, #0f172a);
    line-height: 1.45;
}
.cd-detail__dl--compact dd {
    font-variant-numeric: tabular-nums;
}
.cd-detail__metric {
    margin: 0 0 12px;
    font-size: 1.35rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--ppms-fg, #0f172a);
    font-variant-numeric: tabular-nums;
}
.cd-detail__progress {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
}
.cd-detail__progress-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8rem;
    color: var(--ppms-muted, #64748b);
    margin-bottom: 6px;
}
.cd-detail__progress-track {
    height: 8px;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
}
.cd-detail__progress-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #818cf8, #4f46e5);
    transition: width 0.2s ease;
}
.cd-detail__scope {
    margin: 0;
    font-size: 0.9375rem;
    line-height: 1.6;
    color: #334155;
    white-space: pre-wrap;
}

.cd-table__num {
    text-align: right;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.cd-table__muted {
    font-size: 0.875rem;
    color: var(--ppms-muted, #64748b);
}
.cd-empty {
    text-align: center;
    padding: 20px 16px !important;
    color: var(--ppms-muted, #64748b);
    font-size: 0.9rem;
}
.cd-file-name {
    font-weight: 500;
    word-break: break-word;
}
.cd-step-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.75rem;
    height: 1.75rem;
    padding: 0 8px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 700;
    background: #eef2ff;
    color: #4338ca;
}
.cd-log-action {
    font-size: 0.8rem;
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 4px;
    color: #334155;
}

.cd-upload-bar {
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px dashed #cbd5e1;
    background: #f8fafc;
}

.cd-not-found {
    text-align: center;
    padding: 40px 24px;
    max-width: 28rem;
    margin: 24px auto 0;
}
.cd-not-found__title {
    margin: 0 0 16px;
    font-size: 1.05rem;
    color: var(--ppms-fg, #0f172a);
}

.cd-sr-label {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.cd-pay-summary {
    margin-bottom: 20px;
}
.cd-pay-summary__grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 14px;
}
@media (max-width: 720px) {
    .cd-pay-summary__grid {
        grid-template-columns: 1fr;
    }
}
.cd-pay-summary__card {
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #fff;
}
.cd-pay-summary__card--paid {
    border-color: rgba(16, 185, 129, 0.35);
    background: rgba(16, 185, 129, 0.06);
}
.cd-pay-summary__card--out {
    border-color: rgba(245, 158, 11, 0.4);
    background: rgba(254, 243, 199, 0.35);
}
.cd-pay-summary__label {
    display: block;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--ppms-muted, #64748b);
    margin-bottom: 6px;
}
.cd-pay-summary__value {
    font-size: 1.05rem;
    font-variant-numeric: tabular-nums;
}
.cd-pay-summary__progress-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8rem;
    color: var(--ppms-muted, #64748b);
    margin-bottom: 6px;
}

.cd-file-icon-cell {
    width: 40px;
    vertical-align: middle;
}
.cd-file-icon {
    display: inline-block;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: #e2e8f0 center / 16px no-repeat;
}
.cd-file-icon--pdf {
    background-color: #fee2e2;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23b91c1c'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'/%3E%3C/svg%3E");
}
.cd-file-icon--image {
    background-color: #dbeafe;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%231d4ed8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'/%3E%3C/svg%3E");
}
.cd-file-icon--doc {
    background-color: #f1f5f9;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23475569'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'/%3E%3C/svg%3E");
}
.cd-file-actions {
    white-space: nowrap;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    justify-content: flex-end;
}
.cd-table__icon-col {
    width: 44px;
}

.cd-version-compare {
    margin-bottom: 18px;
    padding: 14px 16px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #fafafa;
}
.cd-version-compare__title {
    margin: 0 0 6px;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--ppms-fg, #0f172a);
}
.cd-version-compare__hint {
    margin: 0 0 12px;
    font-size: 0.8rem;
    color: var(--ppms-muted, #64748b);
}
.cd-version-compare__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px 14px;
    margin-bottom: 12px;
}
.cd-version-compare__lbl {
    font-size: 0.8rem;
    font-weight: 600;
    color: #475569;
}
.cd-version-compare__sel {
    min-width: 160px;
    max-width: 220px;
}
.cd-version-diff {
    margin: 0;
    display: grid;
    gap: 10px;
}

.cd-version-diff dt {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--ppms-muted, #64748b);
    margin: 0 0 4px;
}
.cd-version-diff dd {
    margin: 0;
    font-size: 0.875rem;
    line-height: 1.45;
}
.cd-version-diff__sep {
    margin: 0 8px;
    color: #cbd5e1;
}
.cd-version-diff__a {
    color: #1d4ed8;
}
.cd-version-diff__b {
    color: #047857;
}

.cd-version-timeline {
    margin-bottom: 18px;
}
.cd-version-timeline__title {
    margin: 0 0 10px;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--ppms-fg, #0f172a);
}
.cd-version-timeline__list {
    margin: 0;
    padding: 0 0 0 8px;
    list-style: none;
    border-left: 2px solid #e2e8f0;
}
.cd-version-timeline__item {
    position: relative;
    padding: 0 0 14px 14px;
}
.cd-version-timeline__item::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 4px;
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: #6366f1;
    border: 2px solid #fff;
    box-shadow: 0 0 0 1px #e2e8f0;
}
.cd-version-timeline__badge {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 700;
    color: #4338ca;
    margin-right: 8px;
}
.cd-version-timeline__meta {
    font-size: 0.8rem;
    color: var(--ppms-muted, #64748b);
}
.cd-version-timeline__note {
    margin: 6px 0 0;
    font-size: 0.875rem;
    color: #334155;
}

.cd-approvals-panel__head {
    margin-bottom: 4px;
}
.cd-approvals-panel__title {
    margin: 0 0 6px;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--ppms-fg, #0f172a);
}
.cd-approvals-panel__intro {
    margin-top: 0;
}
.cd-approval-callout {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    margin-bottom: 16px;
    padding: 12px 14px;
    border-radius: 12px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #14532d;
    font-size: 0.875rem;
    line-height: 1.45;
}
.cd-approval-callout__icon {
    flex-shrink: 0;
    margin-top: 2px;
    color: #16a34a;
    font-size: 0.65rem;
}
.cd-approval-callout__text {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.cd-approval-callout__text strong {
    font-size: 0.9rem;
}

.cd-approval-timeline {
    margin: 0;
    padding: 0;
    list-style: none;
}
.cd-approval-timeline__item {
    display: flex;
    gap: 14px;
    align-items: stretch;
    min-height: 48px;
}
.cd-approval-timeline__item--current .cd-approval-timeline__body {
    margin-bottom: 8px;
    padding: 12px 14px 14px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.cd-approval-timeline__item--done .cd-approval-timeline__body {
    opacity: 0.98;
}
.cd-approval-timeline__track {
    position: relative;
    padding-top: 6px;
    margin-left: 4px;
    flex-shrink: 0;
}
.cd-approval-timeline__dot {
    display: block;
    width: 14px;
    height: 14px;
    border-radius: 999px;
    background: #e2e8f0;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #94a3b8;
}
.cd-approval-timeline__dot--approved {
    background: #22c55e;
    box-shadow: 0 0 0 2px #86efac;
}
.cd-approval-timeline__dot--pending {
    background: #f59e0b;
    box-shadow: 0 0 0 2px #fcd34d;
    animation: cd-approval-pulse 2s ease-in-out infinite;
}
.cd-approval-timeline__dot--queued {
    background: #e2e8f0;
    box-shadow: 0 0 0 2px #cbd5e1;
}
.cd-approval-timeline__dot--rejected {
    background: #ef4444;
    box-shadow: 0 0 0 2px #fecaca;
}
@keyframes cd-approval-pulse {
    0%,
    100% {
        box-shadow: 0 0 0 2px #fcd34d;
    }
    50% {
        box-shadow: 0 0 0 4px rgba(252, 211, 77, 0.45);
    }
}
.cd-approval-timeline__line {
    position: absolute;
    left: 6px;
    top: 22px;
    width: 2px;
    border-radius: 1px;
    bottom: -12px;
    background: linear-gradient(180deg, #cbd5e1, #e2e8f0);
}
.cd-approval-timeline__body {
    flex: 1;
    min-width: 0;
    padding-bottom: 16px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 4px;
}
.cd-approval-timeline__item:last-child .cd-approval-timeline__body {
    border-bottom: none;
}
.cd-approval-timeline__head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px 12px;
}
.cd-approval-timeline__head-main {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    min-width: 0;
}
.cd-approval-timeline__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    flex-shrink: 0;
}
.cd-approval-timeline__step {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--ppms-fg, #0f172a);
}
.cd-approval-timeline__role {
    margin: 8px 0 0;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
}
.cd-approval-timeline__who {
    margin: 4px 0 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: #1e293b;
}
.cd-approval-timeline__meta {
    margin: 8px 0 0;
    font-size: 0.8rem;
    color: #64748b;
}
.cd-empty--block {
    padding: 20px;
    text-align: center;
}

.cd-logs-toolbar {
    margin-bottom: 12px;
}
.cd-logs-filter {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px 10px;
}
.cd-logs-filter__label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #475569;
}
.cd-logs-filter .ppms-input {
    min-width: 200px;
    max-width: 100%;
}
.cd-log-payload {
    max-width: 320px;
    vertical-align: top;
}
.cd-log-payload__pre {
    margin: 0;
    font-size: 0.72rem;
    line-height: 1.4;
    white-space: pre-wrap;
    word-break: break-word;
    color: #334155;
    max-height: 120px;
    overflow: auto;
    background: #f8fafc;
    padding: 6px 8px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}
.cd-logs-scroll {
    max-width: 100%;
}
.cd-payments-inactive {
    margin-top: 8px;
}
.cd-submit-approvers {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.cd-submit-approvers__label {
    display: block;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--ppms-fg, #0f172a);
}
.cd-submit-approvers__hint {
    margin: 0;
}
.cd-submit-approvers .o1-user-picker__search {
    width: 100%;
    box-sizing: border-box;
}
</style>
<style src="./contract-modal.css"></style>
