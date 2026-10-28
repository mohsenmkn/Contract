<!-- src/views/Contract/ContractShow.vue (ادامه) -->
<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useContractStore } from '@/stores/contractStore'
import { useAmendmentStore } from '@/stores/amendmentStore'
import { useInvoiceStore } from '@/stores/invoiceStore'
import { useGuaranteeStore } from '@/stores/guaranteeStore'
import { usePermission } from '@/composables/usePermission'
import { useContract } from '@/composables/useContract'
import { useAmendment } from '@/composables/useAmendment'
import { useInvoice } from '@/composables/useInvoice'
import { useGuarantee } from '@/composables/useGuarantee'
import ContractStatusBadge from '@/components/contract/ContractStatusBadge.vue'
import ContractTypeBadge from '@/components/contract/ContractTypeBadge.vue'
import AmountDisplay from '@/components/contract/AmountDisplay.vue'
import DateDisplay from '@/components/contract/DateDisplay.vue'
import ContractItemsTable from '@/components/contract/ContractItemsTable.vue'
import ContractTimeline from '@/components/contract/ContractTimeline.vue'
import SignaturePanel from '@/components/contract/SignaturePanel.vue'
import EmptyState from '@/components/contract/EmptyState.vue'
import { showToast } from '@/plugins/toast'
import {useAuthStore} from "@/stores/auth.js";
const authStore = useAuthStore()

const router = useRouter()
const route = useRoute()
const contractStore = useContractStore()
const amendmentStore = useAmendmentStore()
const invoiceStore = useInvoiceStore()
const guaranteeStore = useGuaranteeStore()
const { can } = usePermission()
const { getContractProgress, getAmountChange, getDisplayStatus } = useContract()
const { getStatusInfo: getAmendmentStatusInfo } = useAmendment()
const { getStatusInfo: getInvoiceStatusInfo } = useInvoice()
const { getStatusInfo: getGuaranteeStatusInfo } = useGuarantee()

const loading = ref(true)
const contract = ref(null)

const printContent = () => {
  window.print()
}

onMounted(async () => {
  try {
    contract.value = await contractStore.fetchContractById(route.params.id)
    await Promise.all([
      amendmentStore.fetchAmendmentsByContract(route.params.id),
      invoiceStore.fetchInvoicesByContract(route.params.id),
      guaranteeStore.fetchGuaranteesByContract(route.params.id),
    ])
  } catch (err) {
    showToast({ severity: 'error', summary: 'خطا', detail: err.message, life: 5000 })
    router.push('/contracts')
  } finally {
    loading.value = false
  }
})

const progress = computed(() => contract.value ? getContractProgress(contract.value) : 0)
const amountChange = computed(() => contract.value ? getAmountChange(contract.value) : null)
const statusInfo = computed(() => contract.value ? getDisplayStatus(contract.value) : null)

const handleSignatureUpdated = async () => {
  // بارگذاری مجدد اطلاعات قرارداد برای نمایش وضعیت جدید (مثلاً بازگشت به draft)
  try {
    contract.value = await contractStore.fetchContractById(route.params.id)
  } catch (err) {
    console.error('خطا در به‌روزرسانی قرارداد:', err)
  }
}

const pendingSignature = computed(() => {
  if (!contract.value?.signatures) return null
  return contract.value.signatures.find(s => s.status === 'pending' && s.signer_id === authStore.user?.id)
})
</script>

<template>
  <div class="p-6">
    <div v-if="loading" class="text-center py-12">
      <ProgressSpinner />
    </div>

    <div v-else-if="contract" class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <div class="flex items-center gap-3 mb-2 flex-wrap">
            <h1 class="text-2xl font-bold">{{ contract.contract_number }}</h1>
            <ContractStatusBadge :status="statusInfo.value" />
            <ContractTypeBadge :type="contract.type" />
          </div>
          <p class="text-surface-600 dark:text-surface-400">{{ contract.subject }}</p>
        </div>

        <div class="flex gap-2">
          <Button
              v-if="can('contracts.update') && ['draft', 'pending_internal_approval'].includes(contract.status)"
              label="ویرایش"
              icon="pi pi-pencil"
              severity="warn"
              outlined
              @click="router.push(`/contracts/${contract.id}/edit`)"
          />
          <Button
              label="بازگشت"
              icon="pi pi-arrow-right"
              severity="secondary"
              outlined
              @click="router.push('/contracts')"
          />
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <template #content>
          <div class="text-sm text-surface-500 mb-2">مبلغ قرارداد</div>
          <AmountDisplay :amount="contract.current_amount" unit="smart" size="lg" />
          <div v-if="amountChange && amountChange.amount !== 0" class="mt-2 text-xs">
            <span :class="amountChange.direction === 'increase' ? 'text-green-600' : 'text-red-600'">
              {{ amountChange.direction === 'increase' ? '+' : '' }}{{ amountChange.formattedPercent }}
            </span>
            نسبت به مبلغ اولیه
          </div>
          </template>
        </Card>

        <Card>
          <div class="text-sm text-surface-500 mb-2">طرف قرارداد</div>
          <div class="font-semibold">{{ contract.party?.name }}</div>
          <div class="text-xs text-surface-400 mt-1 font-mono">{{ contract.party?.national_id }}</div>
        </Card>

        <Card>
          <template #content>
          <div class="text-sm text-surface-500 mb-2">مدت قرارداد</div>
          <div class="flex items-center gap-2 text-sm">
            <DateDisplay :date="contract.start_date" />
            <span class="text-surface-400">تا</span>
            <DateDisplay :date="contract.end_date" />
          </div>
          <div class="mt-3">
            <ProgressBar :value="progress" :show-value="false" class="h-2" />
            <div class="text-xs text-surface-500 mt-1">{{ progress }}٪ از زمان سپری شده</div>
          </div>
          </template>
        </Card>

        <Card>
          <div class="text-sm text-surface-500 mb-2">آمار</div>
          <div class="space-y-1.5 text-sm">
            <div class="flex justify-between">
              <span>الحاقیه‌ها:</span>
              <span class="font-medium">{{ contract.amendments?.length || 0 }}</span>
            </div>
            <div class="flex justify-between">
              <span>صورت‌وضعیت‌ها:</span>
              <span class="font-medium">{{ contract.invoices?.length || 0 }}</span>
            </div>
            <div class="flex justify-between">
              <span>ضمانت‌نامه‌ها:</span>
              <span class="font-medium">{{ contract.guarantees?.length || 0 }}</span>
            </div>
          </div>
        </Card>
      </div>


      <!-- TabView -->
      <TabView>
        <!-- اطلاعات قرارداد -->
        <TabPanel header="اطلاعات قرارداد" left-icon="pi pi-info-circle">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Card>
              <template #title>اطلاعات کلی</template>
              <template #content>
              <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                  <span class="text-surface-500">شماره قرارداد:</span>
                  <span class="font-mono font-medium">{{ contract.contract_number }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-surface-500">موضوع:</span>
                  <span class="font-medium">{{ contract.subject }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-surface-500">نوع:</span>
                  <ContractTypeBadge :type="contract.type" />
                </div>
                <div class="flex justify-between">
                  <span class="text-surface-500">وضعیت:</span>
                  <ContractStatusBadge :status="contract.status" />
                </div>
                <div class="flex justify-between">
                  <span class="text-surface-500">ارز:</span>
                  <span class="font-medium">{{ contract.currency }}</span>
                </div>
              </div>
              </template>
            </Card>

            <Card>
              <template #title>مبالغ</template>
              <template #content>
              <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                  <span class="text-surface-500">مبلغ اولیه:</span>
                  <AmountDisplay :amount="contract.initial_amount" />
                </div>
                <div class="flex justify-between">
                  <span class="text-surface-500">مبلغ فعلی:</span>
                  <AmountDisplay :amount="contract.current_amount" />
                </div>
                <div v-if="amountChange && amountChange.amount !== 0" class="flex justify-between">
                  <span class="text-surface-500">تغییر مبلغ:</span>
                  <AmountDisplay :amount="amountChange.amount" :colorize="true" />
                </div>
              </div>
              </template>
            </Card>

            <Card>
              <template #title>تاریخ‌ها</template>
              <template #content>
              <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                  <span class="text-surface-500">تاریخ شروع:</span>
                  <DateDisplay :date="contract.start_date" />
                </div>
                <div class="flex justify-between">
                  <span class="text-surface-500">تاریخ پایان:</span>
                  <DateDisplay :date="contract.end_date" show-status />
                </div>
                <div class="flex justify-between">
                  <span class="text-surface-500">تاریخ ایجاد:</span>
                  <DateDisplay :date="contract.created_at" show-time />
                </div>
                <div v-if="contract.approved_at" class="flex justify-between">
                  <span class="text-surface-500">تاریخ تایید:</span>
                  <DateDisplay :date="contract.approved_at" show-time />
                </div>
              </div>
              </template>
            </Card>

            <Card>
              <template #title>افراد</template>
              <template #content>
              <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                  <span class="text-surface-500">ایجاد کننده:</span>
                  <span class="font-medium">{{ contract.creator?.name }}</span>
                </div>
                <div v-if="contract.approver" class="flex justify-between">
                  <span class="text-surface-500">تایید کننده:</span>
                  <span class="font-medium">{{ contract.approver.name }}</span>
                </div>
              </div>
              </template>
            </Card>

            <Card v-if="contract.description" class="md:col-span-2">
              <template #title>توضیحات</template>
              <template #content>
              <p class="text-sm leading-7 text-surface-700 dark:text-surface-300">
                {{ contract.description }}
              </p>
              </template>
            </Card>
          </div>
        </TabPanel>


        <!-- ✅ تب جدید: متن قرارداد -->
        <TabPanel v-if="contract?.has_content || contract?.content" header="متن قرارداد">
          <div class="space-y-4">
            <!-- هدر متن -->
            <div class="flex items-center justify-between bg-surface-50 dark:bg-surface-800/50 p-4 rounded-lg">
              <div class="flex items-center gap-3">
                <i class="pi pi-file-edit text-2xl text-primary" />
                <div>
                  <h3 class="font-semibold text-surface-900 dark:text-surface-0">
                    متن کامل قرارداد
                  </h3>
                  <p class="text-xs text-surface-500 mt-1">
                    نسخه {{ contract.content_version || '1.0' }}
                    <span v-if="contract.pages_count">
                • {{ contract.pages_count }} صفحه
              </span>
                  </p>
                </div>
              </div>

              <div class="flex gap-2">
                <Button
                    label="چاپ"
                    icon="pi pi-print"
                    severity="secondary"
                    outlined
                    size="small"
                    @click="printContent"
                />
                <Button
                    label="دانلود PDF"
                    icon="pi pi-file-pdf"
                    severity="secondary"
                    outlined
                    size="small"
                />
              </div>
            </div>

            <!-- نمایش متن -->
            <div
                v-if="contract.content"
                class="contract-content bg-white dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-lg p-8 shadow-sm"
                dir="rtl"
            >
              <div v-html="contract.content" class="ql-editor prose prose-sm max-w-none" />
            </div>

            <EmptyState
                v-else
                title="متن قرارداد ثبت نشده"
                description="برای این قرارداد متنی ثبت نشده است."
                icon="pi pi-file-edit"
            />
          </div>
        </TabPanel>


        <!-- آیتم‌های قرارداد -->
        <TabPanel header="آیتم‌های قرارداد" left-icon="pi pi-list">
          <ContractItemsTable
              :model-value="contract.items || []"
              :readonly="true"
              title="فهرست بها / آیتم‌های قرارداد"
          />
        </TabPanel>

        <!-- الحاقیه‌ها -->
        <TabPanel header="الحاقیه‌ها" left-icon="pi pi-file-edit">
          <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold">لیست الحاقیه‌ها</h3>
            <Button
                v-if="can('amendments.create') && contract.status === 'active'"
                label="الحاقیه جدید"
                icon="pi pi-plus"
                size="small"
                @click="router.push(`/contracts/${contract.id}/amendments/create`)"
            />
          </div>

          <EmptyState
              v-if="!amendmentStore.amendments.length"
              title="الحاقیه‌ای ثبت نشده"
              description="برای این قرارداد هنوز الحاقیه‌ای ایجاد نشده است."
              icon="pi pi-file-edit"
          />

          <DataTable
              v-else
              :value="amendmentStore.amendments"
              :loading="amendmentStore.loading"
              striped-rows
              responsive-layout="scroll"
          >
            <Column field="amendment_number" header="شماره الحاقیه" class="min-w-36">
              <template #body="{ data }">
                <span class="font-mono text-sm">{{ data.amendment_number }}</span>
              </template>
            </Column>

            <Column field="type" header="نوع" class="min-w-32">
              <template #body="{ data }">
                <Tag :value="data.type_label" severity="info" class="!text-xs" />
              </template>
            </Column>

            <Column field="description" header="توضیحات" class="min-w-64">
              <template #body="{ data }">
                <div class="truncate max-w-xs">{{ data.description }}</div>
              </template>
            </Column>

            <Column field="status" header="وضعیت" class="min-w-32">
              <template #body="{ data }">
                <Tag
                    :value="data.status_label"
                    :severity="getAmendmentStatusInfo(data.status).color"
                    class="!text-xs"
                />
              </template>
            </Column>

            <Column field="created_at" header="تاریخ ایجاد" class="min-w-36">
              <template #body="{ data }">
                <DateDisplay :date="data.created_at" />
              </template>
            </Column>

            <Column header="عملیات" class="min-w-24 text-center">
              <template #body="{ data }">
                <Button
                    icon="pi pi-eye"
                    severity="info"
                    text
                    rounded
                    size="small"
                    @click="router.push(`/amendments/${data.id}`)"
                />
              </template>
            </Column>
          </DataTable>
        </TabPanel>

        <!-- صورت‌وضعیت‌ها -->
        <TabPanel header="صورت‌وضعیت‌ها" left-icon="pi pi-receipt">
          <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold">لیست صورت‌وضعیت‌ها</h3>
            <Button
                v-if="can('invoices.create')"
                label="صورت‌وضعیت جدید"
                icon="pi pi-plus"
                size="small"
                @click="router.push(`/contracts/${contract.id}/invoices/create`)"
            />
          </div>

          <EmptyState
              v-if="!invoiceStore.invoices.length"
              title="صورت‌وضعیتی ثبت نشده"
              description="برای این قرارداد هنوز صورت‌وضعیتی ایجاد نشده است."
              icon="pi pi-receipt"
          />

          <DataTable
              v-else
              :value="invoiceStore.invoices"
              :loading="invoiceStore.loading"
              striped-rows
              responsive-layout="scroll"
          >
            <Column field="invoice_number" header="شماره" class="min-w-32">
              <template #body="{ data }">
                <span class="font-mono text-sm">{{ data.invoice_number }}</span>
              </template>
            </Column>

            <Column field="invoice_period" header="دوره" class="min-w-20">
              <template #body="{ data }">
                <span class="font-medium">{{ data.invoice_period }}</span>
              </template>
            </Column>

            <Column field="from_date" header="از تاریخ" class="min-w-32">
              <template #body="{ data }">
                <DateDisplay :date="data.from_date" />
              </template>
            </Column>

            <Column field="to_date" header="تا تاریخ" class="min-w-32">
              <template #body="{ data }">
                <DateDisplay :date="data.to_date" />
              </template>
            </Column>

            <Column field="net_amount" header="مبلغ خالص" class="min-w-40 text-left">
              <template #body="{ data }">
                <AmountDisplay :amount="data.net_amount" />
              </template>
            </Column>

            <Column field="status" header="وضعیت" class="min-w-32">
              <template #body="{ data }">
                <Tag
                    :value="data.status_label"
                    :severity="getInvoiceStatusInfo(data.status).color"
                    class="!text-xs"
                />
              </template>
            </Column>

            <Column header="عملیات" class="min-w-24 text-center">
              <template #body="{ data }">
                <Button
                    icon="pi pi-eye"
                    severity="info"
                    text
                    rounded
                    size="small"
                    @click="router.push(`/invoices/${data.id}`)"
                />
              </template>
            </Column>
          </DataTable>
        </TabPanel>

        <!-- ضمانت‌نامه‌ها -->
        <TabPanel header="ضمانت‌نامه‌ها" left-icon="pi pi-shield">
          <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold">لیست ضمانت‌نامه‌ها</h3>
            <Button
                v-if="can('guarantees.create')"
                label="ضمانت‌نامه جدید"
                icon="pi pi-plus"
                size="small"
                @click="router.push(`/contracts/${contract.id}/guarantees/create`)"
            />
          </div>

          <EmptyState
              v-if="!guaranteeStore.guarantees.length"
              title="ضمانت‌نامه‌ای ثبت نشده"
              description="برای این قرارداد هنوز ضمانت‌نامه‌ای ثبت نشده است."
              icon="pi pi-shield"
          />

          <DataTable
              v-else
              :value="guaranteeStore.guarantees"
              :loading="guaranteeStore.loading"
              striped-rows
              responsive-layout="scroll"
          >
            <Column field="guarantee_number" header="شماره" class="min-w-32">
              <template #body="{ data }">
                <span class="font-mono text-sm">{{ data.guarantee_number }}</span>
              </template>
            </Column>

            <Column field="type" header="نوع" class="min-w-36">
              <template #body="{ data }">
                <Tag :value="data.type_label" severity="info" class="!text-xs" />
              </template>
            </Column>

            <Column field="amount" header="مبلغ" class="min-w-40 text-left">
              <template #body="{ data }">
                <AmountDisplay :amount="data.amount" />
              </template>
            </Column>

            <Column field="bank_name" header="بانک" class="min-w-32">
              <template #body="{ data }">
                <span>{{ data.bank_name }}</span>
              </template>
            </Column>

            <Column field="expiry_date" header="تاریخ انقضا" class="min-w-36">
              <template #body="{ data }">
                <DateDisplay :date="data.expiry_date" show-status />
              </template>
            </Column>

            <Column field="status" header="وضعیت" class="min-w-32">
              <template #body="{ data }">
                <Tag
                    :value="data.status_label"
                    :severity="getGuaranteeStatusInfo(data.status).color"
                    class="!text-xs"
                />
              </template>
            </Column>

            <Column header="عملیات" class="min-w-24 text-center">
              <template #body="{ data }">
                <Button
                    icon="pi pi-eye"
                    severity="info"
                    text
                    rounded
                    size="small"
                    @click="router.push(`/guarantees/${data.id}`)"
                />
              </template>
            </Column>
          </DataTable>
        </TabPanel>

        <!-- امضاها -->
        <TabPanel header="امضاها" left-icon="pi pi-signature">
          <EmptyState
              v-if="!contract.signatures?.length"
              title="امضایی ثبت نشده"
              description="هنوز هیچ امضایی برای این قرارداد ثبت نشده است."
              icon="pi pi-signature"
          />
          <!-- Signature Panel (اگر امضای در انتظار وجود دارد) -->
          <!-- در ContractShow.vue -->
          <SignaturePanel
              v-if="pendingSignature"
              :signature="pendingSignature"
              :contract-content="contract?.content || ''"
              :contract-pages-count="contract?.pages_count || 0"
              document-type="contract"
              @signed="handleSignatureUpdated"
              @rejected="handleSignatureUpdated"
          />


          <div v-else class="space-y-4">
            <div
                v-for="sig in contract.signatures"
                :key="sig.id"
                class="border border-surface-200 dark:border-surface-700 rounded-lg p-4"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-2">
                    <i class="pi pi-user text-primary" />
                    <span class="font-medium">{{ sig.signer?.name }}</span>
                    <Tag
                        :value="sig.signer_type === 'contractor' ? 'پیمانکار' : 'داخلی'"
                        :severity="sig.signer_type === 'contractor' ? 'info' : 'success'"
                        class="!text-xs"
                    />
                  </div>

                  <div class="grid grid-cols-2 gap-4 text-sm mt-3">
                    <div>
                      <span class="text-surface-500">وضعیت:</span>
                      <Tag
                          :value="sig.status_label"
                          :severity="sig.status === 'signed' ? 'success' : sig.status === 'rejected' ? 'danger' : 'warn'"
                          class="!text-xs mr-2"
                      />
                    </div>
                    <div v-if="sig.signed_at">
                      <span class="text-surface-500">تاریخ امضا:</span>
                      <DateDisplay :date="sig.signed_at" show-time />
                    </div>
                  </div>

                  <div v-if="sig.comments" class="mt-3 text-sm bg-surface-50 dark:bg-surface-800 p-3 rounded">
                    <i class="pi pi-comment ml-1 text-surface-400" />
                    {{ sig.comments }}
                  </div>

                  <div v-if="sig.ip_address" class="mt-2 text-xs text-surface-400">
                    <i class="pi pi-globe ml-1" />
                    IP: {{ sig.ip_address }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </TabPanel>

        <!-- تاریخچه -->
        <TabPanel header="تاریخچه" left-icon="pi pi-history">
          <ContractTimeline :contract="contract" />
        </TabPanel>
      </TabView>
    </div>
  </div>
</template>

<style scoped>
.contract-content {
  min-height: 400px;
  line-height: 2;
  font-size: 14px;
}

.contract-content :deep(h1),
.contract-content :deep(h2),
.contract-content :deep(h3) {
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
  color: #0f172a;
}

.contract-content :deep(p) {
  margin-bottom: 1rem;
  text-align: justify;
}

.contract-content :deep(ul),
.contract-content :deep(ol) {
  margin-bottom: 1rem;
  padding-right: 2rem;
}

.contract-content :deep(li) {
  margin-bottom: 0.5rem;
}

.contract-content :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin: 1rem 0;
}

.contract-content :deep(th),
.contract-content :deep(td) {
  border: 1px solid #e2e8f0;
  padding: 0.5rem;
}

.contract-content :deep(th) {
  background: #f8fafc;
  font-weight: 600;
}

@media print {
  .contract-content {
    border: none;
    box-shadow: none;
    padding: 0;
  }
}
</style>