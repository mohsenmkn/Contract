<template>
  <div class="contracts-page" dir="rtl">
    <!-- پس‌زمینه تزئینی -->
    <div class="page-background">
      <div class="bg-shape shape-one"></div>
      <div class="bg-shape shape-two"></div>
      <div class="bg-shape shape-three"></div>
      <div class="bg-grid"></div>
      <div class="floating-icons">
        <i class="pi pi-file-signature floating-icon icon-1"></i>
        <i class="pi pi-handshake floating-icon icon-2"></i>
        <i class="pi pi-shield floating-icon icon-3"></i>
        <i class="pi pi-file-pdf floating-icon icon-4"></i>
        <i class="pi pi-users floating-icon icon-5"></i>
        <i class="pi pi-wallet floating-icon icon-6"></i>
      </div>
    </div>
    <div class="contracts-shell">
    <!-- ═══════ هدر صفحه ═══════ -->
    <header class="page-header">
      <div class="header-content">
        <div class="header-text">
          <div class="header-badge">
            <i class="pi pi-file"></i>
            <span>مدیریت قراردادها</span>
          </div>
          <h1 class="header-title">لیست قراردادها</h1>
          <p class="header-subtitle">
            مشاهده، فیلتر و مدیریت تمامی قراردادهای ثبت‌شده در سامانه
          </p>
        </div>
        <div class="header-actions">
          <Button
              v-if="can('contracts.create')"
              label="قرارداد جدید"
              icon="pi pi-plus"
              class="create-btn"
              @click="router.push('/contracts/create')"
          />
          <Button
              label="خروجی Excel"
              icon="pi pi-file-excel"
              class="export-btn"
              outlined
          />
        </div>
      </div>
    </header>

    <!-- ═══════ بخش فیلترها ═══════ -->
    <section class="filters-section">
      <div class="section-header">
        <div class="section-title-group">
          <div class="section-icon">
            <i class="pi pi-filter"></i>
          </div>
          <h2 class="section-title">فیلترهای جستجو</h2>
        </div>
        <span class="result-count" v-if="contractStore.pagination.total > 0">
        <i class="pi pi-list"></i>
        {{ contractStore.pagination.total }} قرارداد یافت شد
      </span>
      </div>
      <div class="filters-card">
        <ContractFilters
            v-model="filters"
            :loading="contractStore.loading"
            @apply="loadContracts"
            @reset="loadContracts"
        />
      </div>
    </section>

    <!-- ═══════ جدول قراردادها ═══════ -->
    <section class="table-section">
      <div class="section-header">
        <div class="section-title-group">
          <div class="section-icon green">
            <i class="pi pi-table"></i>
          </div>
          <h2 class="section-title">قراردادهای ثبت‌شده</h2>
        </div>
      </div>
      <div class="table-card">
        <!-- Loading -->
        <div v-if="contractStore.loading" class="table-loading">
          <i class="pi pi-spin pi-spinner loading-icon"></i>
          <p>در حال بارگذاری قراردادها...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="!contractStore.contracts.length" class="table-empty">
          <div class="empty-icon">
            <i class="pi pi-file"></i>
          </div>
          <h3 class="empty-title">قراردادی یافت نشد</h3>
          <p class="empty-description">
            با فیلترهای فعلی هیچ قراردادی ثبت نشده است.
            <br />
            می‌توانید فیلترها را تغییر دهید یا قرارداد جدیدی ایجاد کنید.
          </p>
          <div class="empty-actions">
            <Button
                label="پاک کردن فیلترها"
                icon="pi pi-filter-slash"
                class="reset-btn"
                outlined
                @click="filters = {}; loadContracts()"
            />
            <Button
                v-if="can('contracts.create')"
                label="ایجاد قرارداد جدید"
                icon="pi pi-plus"
                class="create-btn"
                @click="router.push('/contracts/create')"
            />
          </div>
        </div>

        <!-- Data Table -->
        <DataTable
            v-else
            :value="contractStore.contracts"
            :loading="contractStore.loading"
            :paginator="true"
            :rows="15"
            :rowsPerPageOptions="[15, 30, 50, 100]"
            responsiveLayout="scroll"
            :totalRecords="contractStore.pagination.total"
            lazy
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="نمایش {first} تا {last} از {totalRecords} قرارداد"
            class="contracts-table"
            @page="contractStore.setPage($event.page + 1); loadContracts()"
            @sort="handleSort($event)"
        >
          <!-- شماره قرارداد -->
          <Column field="contract_number" header="شماره قرارداد" :sortable="true" class="col-number">
            <template #body="{ data }">
              <div class="cell-number">
                <i class="pi pi-file cell-icon"></i>
                <span class="number-text">{{ data.contract_number }}</span>
              </div>
            </template>
          </Column>

          <!-- موضوع -->
          <Column field="subject" header="موضوع قرارداد" class="col-subject">
            <template #body="{ data }">
              <div class="cell-subject" :title="data.subject">
                {{ data.subject }}
              </div>
            </template>
          </Column>

          <!-- طرف قرارداد -->
          <Column field="party.name" header="طرف قرارداد" class="col-party">
            <template #body="{ data }">
              <div class="cell-party" v-if="data.party">
                <div class="party-avatar">
                  {{ data.party.name?.charAt(0) || '?' }}
                </div>
                <span class="party-name">{{ data.party.name }}</span>
              </div>
              <span v-else class="text-muted">—</span>
            </template>
          </Column>

          <!-- نوع قرارداد -->
          <Column field="type" header="نوع" class="col-type">
            <template #body="{ data }">
              <ContractTypeBadge :type="data.type" />
            </template>
          </Column>

          <!-- مبلغ -->
          <Column field="current_amount" header="مبلغ قرارداد" :sortable="true" class="col-amount">
            <template #body="{ data }">
              <div class="cell-amount">
                <i class="pi pi-dollar amount-icon"></i>
                <AmountDisplay :amount="data.current_amount" unit="smart" />
              </div>
            </template>
          </Column>

          <!-- تاریخ پایان -->
          <Column field="end_date" header="تاریخ پایان" :sortable="true" class="col-date">
            <template #body="{ data }">
              <div class="cell-date">
                <DateDisplay :date="data.end_date" />
                <span
                    v-if="data.status === 'active'"
                    :class="getDaysBadge(data).color"
                    class="days-badge"
                >
                {{ getDaysBadge(data).label }}
              </span>
              </div>
            </template>
          </Column>

          <!-- وضعیت -->
          <Column field="status" header="وضعیت" class="col-status">
            <template #body="{ data }">
              <ContractStatusBadge :status="getDisplayStatus(data).value" />
            </template>
          </Column>

          <!-- عملیات -->
          <Column header="عملیات" class="min-w-40 text-center">
            <template #body="{ data }">
              <div class="flex items-center justify-center gap-1">
                <Button
                    icon="pi pi-eye"
                    severity="info"
                    text
                    rounded
                    size="small"
                    v-tooltip="'مشاهده'"
                    @click="viewContract(data)"
                />

                <Button
                    v-if="can('contracts.update') && ['draft', 'pending_internal_approval'].includes(data.status)"
                    icon="pi pi-pencil"
                    severity="warn"
                    text
                    rounded
                    size="small"
                    v-tooltip="'ویرایش'"
                    @click="editContract(data)"
                />
                <!-- ✅ دکمه جدید: ارسال به پیمانکار -->
                <Button
                 v-if="can('contracts.submit') &&
                data.type === 'downstream' &&
                ['draft', 'pending_internal_approval'].includes(data.status)"
                    icon="pi pi-send"
                    severity="success"
                    text
                    rounded
                    size="small"
                    v-tooltip="'ارسال به پیمانکار'"
                    @click="handleSendToContractor(data)"
                />
                <Button
                    v-if="can('contracts.delete') && data.status === 'draft'"
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    rounded
                    size="small"
                    v-tooltip="'حذف'"
                    @click="handleDelete(data)"
                />
              </div>
            </template>
          </Column>

          <!-- Empty template -->
          <template #empty>
            <div class="table-empty-inline">
              <i class="pi pi-inbox"></i>
              <p>قراردادی یافت نشد</p>
            </div>
          </template>
        </DataTable>
      </div>
    </section>
  </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import { useConfirm } from 'primevue/useconfirm'
import { useContractStore } from '@/stores/contractStore'
import { usePermission } from '@/composables/usePermission'
import { useContract } from '@/composables/useContract'
import { useDateFormatter } from '@/composables/useDateFormatter'
import ContractStatusBadge from '@/components/contract/ContractStatusBadge.vue'
import ContractTypeBadge from '@/components/contract/ContractTypeBadge.vue'
import ContractFilters from '@/components/contract/ContractFilters.vue'
import AmountDisplay from '@/components/contract/AmountDisplay.vue'
import DateDisplay from '@/components/contract/DateDisplay.vue'
import { showToast } from '@/plugins/toast'
import {contractService} from "@/services/contractService.js";

const router = useRouter()
const contractStore = useContractStore()
const { can } = usePermission()
const { getDisplayStatus } = useContract()
const { daysRemaining } = useDateFormatter()
const confirm = useConfirm()

const filters = ref({})
const sortField = ref('')
const sortOrder = ref(1)




const viewContract = (contract) => {
  if (!contract?.id) {
    showToast({
      severity: 'warn',
      summary: 'خطا',
      detail: 'شناسه قرارداد معتبر نیست',
      life: 3000
    })
    return
  }
  router.push(`/contracts/${contract.id}`)
}

// ✅ تابع امن برای ویرایش
const editContract = (contract) => {
  if (!contract?.id) {
    showToast({
      severity: 'warn',
      summary: 'خطا',
      detail: 'شناسه قرارداد معتبر نیست',
      life: 3000
    })
    return
  }
  router.push(`/contracts/${contract.id}/edit`)
}

// ✅ تابع امن برای حذف
const handleDelete = (contract) => {
  if (!contract?.id) {
    showToast({
      severity: 'warn',
      summary: 'خطا',
      detail: 'شناسه قرارداد معتبر نیست',
      life: 3000
    })
    return
  }

  confirm.require({
    message: `آیا از حذف قرارداد ${contract.contract_number} اطمینان دارید؟`,
    header: 'تایید حذف',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await contractStore.deleteContract(contract.id)
        showToast({ severity: 'success', summary: 'حذف شد', life: 3000 })
      } catch (err) {
        showToast({ severity: 'error', summary: 'خطا', detail: err.message, life: 5000 })
      }
    },
  })
}


// ✅ متد جدید برای ارسال به پیمانکار
const handleSendToContractor = (contract) => {
  if (!contract?.id) {
    showToast({
      severity: 'warn',
      summary: 'خطا',
      detail: 'شناسه قرارداد معتبر نیست',
      life: 3000
    })
    return
  }

  confirm.require({
    message: `آیا از ارسال قرارداد ${contract.contract_number} برای امضای پیمانکار اطمینان دارید؟`,
    header: 'ارسال به پیمانکار',
    icon: 'pi pi-send',
    acceptLabel: 'بله، ارسال کن',
    rejectLabel: 'انصراف',
    accept: async () => {
      try {
        await contractService.sendToContractor(contract.id)
        showToast({
          severity: 'success',
          summary: 'ارسال شد',
          detail: 'قرارداد با موفقیت برای پیمانکار ارسال شد',
          life: 3000
        })
        // بارگذاری مجدد لیست
        await contractStore.fetchContracts(filters.value)
      } catch (err) {
        showToast({
          severity: 'error',
          summary: 'خطا',
          detail: err.response?.data?.message || err.message || 'خطا در ارسال قرارداد',
          life: 5000
        })
      }
    },
  })
}


const loadContracts = () => {
  contractStore.fetchContracts({
    ...filters.value,
    sort_by: sortField.value,
    sort_order: sortOrder.value,
  })
}

const handleSort = (event) => {
  sortField.value = event.sortField || ''
  sortOrder.value = event.sortOrder || 1
  loadContracts()
}

onMounted(loadContracts)


const handleSubmit = (contract) => {
  confirm.require({
    message: `قرارداد ${contract.contract_number} برای تایید ارسال شود؟`,
    header: 'ارسال برای تایید',
    icon: 'pi pi-send',
    accept: async () => {
      try {
        await contractStore.submitContract(contract.id)
        showToast({ severity: 'success', summary: 'ارسال شد', life: 3000 })
        loadContracts()
      } catch (err) {
        showToast({ severity: 'error', summary: 'خطا', detail: err.message, life: 5000 })
      }
    },
  })
}

const getDaysBadge = (contract) => {
  const days = daysRemaining(contract.end_date)
  if (days < 0) return { label: 'منقضی', color: 'expired' }
if (days => 30) return { label: `${days} روز`, color: 'critical' }
if (days => 90) return { label: `${days} روز`, color: 'warning' }
return { label: `${days} روز`, color: 'safe' }
}
</script>

<style scoped>
/* ═══════ توکن‌های رنگ (هماهنگ با داشبورد) ═══════ */
:root,
.contracts-page {
  --accent: oklch(45% 0.15 250);
  --accent-dark: oklch(38% 0.17 250);
  --accent-soft: oklch(45% 0.15 250 / 0.10);
  --accent-ring: oklch(45% 0.15 250 / 0.14);
  --gold: oklch(55% 0.15 155);
  --gold-dark: oklch(48% 0.16 155);
  --gold-soft: oklch(55% 0.15 155 / 0.10);
  --surface: oklch(99% 0.004 250);
  --bg: oklch(97.5% 0.008 250);
  --border: oklch(88% 0.008 250);
  --text-main: oklch(15% 0.01 250);
  --text-muted: oklch(45% 0.012 250);
  --text-subtle: oklch(65% 0.008 250);
  --danger: oklch(50% 0.20 25);
  --danger-bg: oklch(97% 0.04 25);
  --danger-border: oklch(85% 0.08 25);
  --warn: oklch(65% 0.15 75);
  --warn-bg: oklch(97% 0.04 75);
  --warn-border: oklch(85% 0.08 75);
  --success: oklch(42% 0.14 145);
  --success-bg: oklch(97% 0.04 145);
  --success-border:oklch(82% 0.10 145);
  --info: oklch(55% 0.12 220);
  --info-bg: oklch(97% 0.04 220);
  --info-border: oklch(85% 0.08 220);
}

/* ═══════ صفحه ═══════ */
.contracts-page {
  min-height: 100dvh;
  direction: rtl;
  background-color: var(--bg);
  position: relative;
  padding: 24px;
  overflow-x: hidden;
}

/* ═══════ پس‌زمینه تزئینی ═══════ */
.page-background {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  overflow: hidden;
}

.bg-shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
}

.shape-one {
  width: 500px;
  height: 500px;
  top: -150px;
  right: -150px;
  background: oklch(45% 0.15 250 / 0.08);
}

.shape-two {
  width: 450px;
  height: 450px;
  bottom: -150px;
  left: -150px;
  background: oklch(55% 0.15 155 / 0.07);
}

.shape-three {
  width: 300px;
  height: 300px;
  top: 40%;
  left: 40%;
  background: oklch(50% 0.12 200 / 0.05);
}

.bg-grid {
  position: absolute;
  inset: 0;
  background-image:
      linear-gradient(oklch(58% 0.008 250 / 0.05) 1px, transparent 1px),
      linear-gradient(90deg, oklch(58% 0.008 250 / 0.05) 1px, transparent 1px);
  background-size: 50px 50px;
  mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 30%, transparent 80%);
}

.floating-icons {
  position: absolute;
  inset: 0;
}

.floating-icon {
  position: absolute;
  font-size: 32px;
  opacity: 0.06;
  color: var(--accent);
  animation: float 10s ease-in-out infinite;
}

.icon-1 { top: 10%; left: 8%; animation-delay: 0s; }
.icon-2 { top: 65%; left: 12%; animation-delay: 2s; color: var(--gold); }
.icon-3 { top: 15%; right: 10%; animation-delay: 4s; }
.icon-4 { bottom: 20%; right: 8%; animation-delay: 1s; color: var(--gold); }
.icon-5 { top: 45%; left: 3%; animation-delay: 3s; }
.icon-6 { top: 30%; right: 15%; animation-delay: 2.5s; color: var(--gold); }

@keyframes float {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-25px) rotate(5deg); }
}

/* ═══════ پوسته ═══════ */
.contracts-shell {
  position: relative;
  z-index: 1;
  max-width: 1600px;
  margin: 0 auto;
}

/* ═══════ هدر ═══════ */
.page-header {
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  border-radius: 20px;
  padding: 28px 32px;
  margin-bottom: 24px;
  box-shadow:
      0 12px 30px oklch(45% 0.15 250 / 0.25),
      0 0 0 1px oklch(95% 0.004 250 / 0.1);
  position: relative;
  overflow: hidden;
}

.page-header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, var(--gold) 0%, transparent 70%);
  opacity: 0.15;
  filter: blur(40px);
}

.page-header::after {
  content: '';
  position: absolute;
  bottom: -50%;
  left: -10%;
  width: 250px;
  height: 250px;
  background: radial-gradient(circle, var(--gold) 0%, transparent 70%);
  opacity: 0.10;
  filter: blur(40px);
}

.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
  position: relative;
  z-index: 1;
}

.header-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: oklch(100% 0 0 / 0.15);
  backdrop-filter: blur(10px);
  color: #fff;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 11.5px;
  font-weight: 600;
  margin-bottom: 10px;
  border: 1px solid oklch(100% 0 0 / 0.2);
}

.header-badge i {
  font-size: 12px;
}

.header-title {
  font-size: 24px;
  font-weight: 800;
  color: #fff;
  margin: 0 0 6px;
  letter-spacing: -0.01em;
}

.header-subtitle {
  font-size: 13px;
  color: oklch(100% 0 0 / 0.85);
  margin: 0;
  line-height: 1.7;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.create-btn {
  background: #fff !important;
  color: var(--accent) !important;
  border: none !important;
  font-weight: 700 !important;
  border-radius: 10px !important;
  box-shadow: 0 4px 12px oklch(0% 0 0 / 0.15) !important;
  transition: transform 0.2s, box-shadow 0.2s !important;
}

.create-btn:hover {
  transform: translateY(-1px) !important;
  box-shadow: 0 6px 16px oklch(0% 0 0 / 0.2) !important;
}

.export-btn {
  background: oklch(100% 0 0 / 0.15) !important;
  backdrop-filter: blur(10px);
  border: 1px solid oklch(100% 0 0 / 0.2) !important;
  color: #fff !important;
  font-weight: 600 !important;
  border-radius: 10px !important;
}

.export-btn:hover {
  background: oklch(100% 0 0 / 0.25) !important;
}

.export-btn :deep(.p-button-icon) {
  color: #fff !important;
}

/* ═══════ بخش‌ها ═══════ */
.filters-section,
.table-section {
  margin-bottom: 24px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
  padding: 0 4px;
  flex-wrap: wrap;
  gap: 12px;
}

.section-title-group {
  display: flex;
  align-items: center;
  gap: 10px;
}

.section-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  box-shadow: 0 4px 12px oklch(45% 0.15 250 / 0.25);
}

.section-icon.green {
  background: linear-gradient(135deg, var(--gold), var(--gold-dark));
  box-shadow: 0 4px 12px oklch(55% 0.15 155 / 0.25);
}

.section-title {
  font-size: 16px;
  font-weight: 800;
  color: var(--text-main);
  margin: 0;
  letter-spacing: -0.01em;
}

.result-count {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--text-muted);
  background: var(--surface);
  padding: 6px 12px;
  border-radius: 14px;
  border: 1px solid var(--border);
  font-weight: 600;
}

.result-count i {
  font-size: 12px;
  color: var(--accent);
}

/* ═══════ کارت فیلتر ═══════ */
.filters-card {
  background: oklch(99.5% 0.003 250 / 0.97);
  border: 1px solid oklch(90% 0.006 250 / 0.9);
  border-radius: 16px;
  padding: 20px;
  box-shadow:
      0 8px 24px oklch(15% 0.01 250 / 0.06),
      0 0 0 1px oklch(95% 0.004 250 / 0.5);
  backdrop-filter: blur(10px);
}

/* ═══════ کارت جدول ═══════ */
.table-card {
  background: oklch(99.5% 0.003 250 / 0.97);
  border: 1px solid oklch(90% 0.006 250 / 0.9);
  border-radius: 16px;
  padding: 0;
  box-shadow:
      0 8px 24px oklch(15% 0.01 250 / 0.06),
      0 0 0 1px oklch(95% 0.004 250 / 0.5);
  backdrop-filter: blur(10px);
  overflow: hidden;
}

/* ═══════ Loading ═══════ */
.table-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  gap: 12px;
}

.loading-icon {
  font-size: 36px;
  color: var(--accent);
}

.table-loading p {
  color: var(--text-muted);
  font-size: 13px;
  margin: 0;
  font-weight: 500;
}

/* ═══════ Empty State ═══════ */
.table-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

.empty-icon {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--accent-soft), var(--gold-soft));
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  border: 2px dashed var(--border);
}

.empty-icon i {
  font-size: 28px;
  color: var(--accent);
}

.empty-title {
  font-size: 18px;
  font-weight: 800;
  color: var(--text-main);
  margin: 0 0 8px;
}

.empty-description {
  font-size: 13px;
  color: var(--text-muted);
  margin: 0 0 20px;
  line-height: 1.8;
  max-width: 400px;
}

.empty-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: center;
}

.reset-btn {
  background: var(--surface) !important;
  color: var(--text-main) !important;
  border: 1px solid var(--border) !important;
  border-radius: 10px !important;
  font-weight: 600 !important;
}

.reset-btn:hover {
  background: var(--bg) !important;
  border-color: var(--accent) !important;
  color: var(--accent) !important;
}

.empty-actions .create-btn {
  background: linear-gradient(135deg, var(--accent), var(--accent-dark)) !important;
  color: #fff !important;
  border: none !important;
  box-shadow: 0 4px 12px oklch(45% 0.15 250 / 0.25) !important;
}

.empty-actions .create-btn:hover {
  background: linear-gradient(135deg, var(--accent-dark), var(--accent)) !important;
}

.table-empty-inline {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px 20px;
  color: var(--text-subtle);
}

.table-empty-inline i {
  font-size: 32px;
  margin-bottom: 8px;
}

.table-empty-inline p {
  margin: 0;
  font-size: 13px;
}

/* ═══════ DataTable ═══════ */
:deep(.contracts-table) {
  font-family: inherit;
}

:deep(.p-datatable .p-datatable-header) {
  background: transparent !important;
  border: none !important;
  padding: 16px 20px !important;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: linear-gradient(135deg, oklch(98% 0.005 250), oklch(97% 0.008 250)) !important;
  color: var(--text-main) !important;
  font-weight: 700 !important;
  font-size: 12.5px !important;
  padding: 14px 16px !important;
  border-bottom: 2px solid var(--border) !important;
  text-align: right !important;
  white-space: nowrap;
}

:deep(.p-datatable .p-datatable-thead > tr > th .p-sortable-column-icon) {
  color: var(--accent) !important;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  background: var(--surface) !important;
  color: var(--text-main) !important;
  transition: background 0.15s !important;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 14px 16px !important;
  border-bottom: 1px solid oklch(92% 0.006 250) !important;
  font-size: 13px !important;
  vertical-align: middle !important;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background: oklch(98.5% 0.005 250) !important;
}

:deep(.p-datatable .p-datatable-tbody > tr.p-row-even) {
  background: oklch(99% 0.004 250) !important;
}

:deep(.p-datatable .p-datatable-tbody > tr.p-row-even:hover) {
  background: oklch(98.5% 0.005 250) !important;
}

/* Paginator */
:deep(.p-datatable .p-paginator) {
  background: linear-gradient(135deg, oklch(98% 0.005 250), oklch(97% 0.008 250)) !important;
  border-top: 1px solid var(--border) !important;
  padding: 12px 16px !important;
  justify-content: center !important;
}

:deep(.p-datatable .p-paginator .p-paginator-pages .p-paginator-page) {
  border-radius: 8px !important;
  margin: 0 2px !important;
  min-width: 32px !important;
  height: 32px !important;
  font-size: 12px !important;
  font-weight: 600 !important;
  color: var(--text-muted) !important;
  transition: all 0.15s !important;
}

:deep(.p-datatable .p-paginator .p-paginator-pages .p-paginator-page:hover) {
  background: var(--accent-soft) !important;
  color: var(--accent) !important;
}

:deep(.p-datatable .p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
  background: linear-gradient(135deg, var(--accent), var(--accent-dark)) !important;
  color: #fff !important;
  box-shadow: 0 2px 8px oklch(45% 0.15 250 / 0.25) !important;
}

:deep(.p-datatable .p-paginator .p-paginator-first),
:deep(.p-datatable .p-paginator .p-paginator-prev),
:deep(.p-datatable .p-paginator .p-paginator-next),
:deep(.p-datatable .p-paginator .p-paginator-last) {
  border-radius: 8px !important;
  margin: 0 2px !important;
  color: var(--accent) !important;
}

:deep(.p-paginator-current) {
  color: var(--text-subtle) !important;
  font-size: 11.5px !important;
  font-weight: 500 !important;
}

:deep(.p-paginator-rpp-dropdown) {
  border-radius: 8px !important;
  border: 1px solid var(--border) !important;
  font-size: 12px !important;
  color: var(--text-main) !important;
}

/* ═══════ سلول‌های جدول ═══════ */
.cell-number {
  display: flex;
  align-items: center;
  gap: 8px;
}

.cell-icon {
  font-size: 13px;
  color: var(--accent);
  opacity: 0.7;
}

.number-text {
  font-family: 'Vazirmatn', monospace;
  font-weight: 700;
  color: var(--text-main);
  font-size: 12.5px;
  direction: ltr;
  unicode-bidi: embed;
}

.cell-subject {
  max-width: 280px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--text-main);
  font-weight: 500;
}

.cell-party {
  display: flex;
  align-items: center;
  gap: 10px;
}

.party-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
  box-shadow: 0 2px 6px oklch(45% 0.15 250 / 0.2);
}

.party-name {
  font-weight: 600;
  color: var(--text-main);
  font-size: 12.5px;
}

.text-muted {
  color: var(--text-subtle);
}

.cell-amount {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 700;
  color: var(--text-main);
  font-variant-numeric: tabular-nums;
  direction: ltr;
  unicode-bidi: embed;
  justify-content: flex-start;
}

.amount-icon {
  font-size: 12px;
  color: var(--gold);
}

.cell-date {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.days-badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 10.5px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.days-badge.safe {
  background: var(--success-bg);
  color: var(--success);
  border: 1px solid var(--success-border);
}

.days-badge.warning {
  background: oklch(97% 0.06 85);
  color: oklch(55% 0.15 85);
  border: 1px solid oklch(85% 0.10 85);
}

.days-badge.critical {
  background: var(--warn-bg);
  color: var(--warn);
  border: 1px solid var(--warn-border);
}

.days-badge.expired {
  background: var(--danger-bg);
  color: var(--danger);
  border: 1px solid var(--danger-border);
}

/* ═══════ دکمه‌های عملیات ═══════ */
.cell-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

.action-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid transparent;
  background: transparent;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  transition: all 0.15s;
}

.action-btn:hover {
  transform: translateY(-1px);
}

.view-btn {
  color: var(--info);
}

.view-btn:hover {
  background: var(--info-bg);
  border-color: var(--info-border);
}

.edit-btn {
  color: var(--warn);
}

.edit-btn:hover {
  background: var(--warn-bg);
  border-color: var(--warn-border);
}

.submit-btn {
  color: var(--success);
}

.submit-btn:hover {
  background: var(--success-bg);
  border-color: var(--success-border);
}

.delete-btn {
  color: var(--danger);
}

.delete-btn:hover {
  background: var(--danger-bg);
  border-color: var(--danger-border);
}

/* ═══════ ریسپانسیو ═══════ */
@media (max-width: 1280px) {
  .col-subject {
    min-width: 200px;
  }
}

@media (max-width: 1024px) {
  .contracts-page {
    padding: 16px;
  }

  .page-header {
    padding: 22px 20px;
    border-radius: 16px;
  }

  .header-title {
    font-size: 20px;
  }

  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .header-actions {
    width: 100%;
  }

  .create-btn,
  .export-btn {
    flex: 1;
  }

  :deep(.p-datatable .p-datatable-thead > tr > th),
  :deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 10px 12px !important;
    font-size: 12px !important;
  }

  .cell-subject {
    max-width: 180px;
  }
}

@media (max-width: 768px) {
  .floating-icon {
    font-size: 24px;
  }

  .filters-card {
    padding: 14px;
  }

  .section-header {
    flex-direction: column;
    align-items: flex-start;
  }

  :deep(.p-datatable) {
    font-size: 12px;
  }

  .party-avatar {
    width: 28px;
    height: 28px;
    font-size: 11px;
  }

  .action-btn {
    width: 28px;
    height: 28px;
    font-size: 12px;
  }
}

@media (max-width: 560px) {
  .page-header {
    padding: 18px 16px;
  }

  .header-title {
    font-size: 18px;
  }

  .header-subtitle {
    font-size: 12px;
  }

  .header-actions {
    flex-direction: column;
  }

  .empty-actions {
    flex-direction: column;
    width: 100%;
  }

  .empty-actions button {
    width: 100%;
  }
}

@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    transition-duration: 0.01ms !important;
    animation-duration: 0.01ms !important;
  }
}
</style>