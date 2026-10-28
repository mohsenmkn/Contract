<!-- src/views/Dashboard/ContractorDashboard.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { dashboardService } from '@/services/dashboardService'
import { useContract } from '@/composables/useContract'
import { useNumberFormatter } from '@/composables/useNumberFormatter'
import { useDateFormatter } from '@/composables/useDateFormatter'
import StatCard from '@/components/contract/StatCard.vue'
import AmountDisplay from '@/components/contract/AmountDisplay.vue'
import DateDisplay from '@/components/contract/DateDisplay.vue'
import ContractStatusBadge from '@/components/contract/ContractStatusBadge.vue'
import ProgressBar from 'primevue/progressbar'
import EmptyState from '@/components/contract/EmptyState.vue'

const router = useRouter()
const authStore = useAuthStore()
const { getContractProgress, getDisplayStatus } = useContract()
const { formatSmartAmount } = useNumberFormatter()
const { daysRemaining } = useDateFormatter()

const loading = ref(true)
const dashboard = ref({
  activeContracts: 0,
  pendingSignatures: 0,
  totalAmount: 0,
  expiringContracts: 0,
  paidAmount: 0,
  unpaidAmount: 0,
  contracts: [],
  pendingSignaturesList: [],
  pendingInvoices: [],
  expiringGuarantees: [],
})

onMounted(async () => {
  loading.value = true
  try {
    const response = await dashboardService.getContractorDashboard()
    dashboard.value = response.data || dashboard.value
  } catch (err) {
    console.error('Contractor dashboard error:', err)
  } finally {
    loading.value = false
  }
})

const contractorName = computed(() =>
    authStore.user?.party?.name || authStore.user?.name || 'پیمانکار'
)

const paymentProgress = computed(() => {
  const total = dashboard.value.paidAmount + dashboard.value.unpaidAmount
  if (!total) return 0
  return Math.round((dashboard.value.paidAmount / total) * 100)
})
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Hero Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-l from-primary-600 via-primary-500 to-primary-700 dark:from-primary-800 dark:via-primary-700 dark:to-primary-900 shadow-xl">
      <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
          <path d="M0,50 Q25,30 50,50 T100,50 L100,100 L0,100 Z" fill="white" />
        </svg>
      </div>

      <div class="relative p-8 flex items-center justify-between flex-wrap gap-6">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center">
            <i class="pi pi-building text-3xl text-white" />
          </div>
          <div>
            <h1 class="text-3xl font-bold text-white">پورتال پیمانکار</h1>
            <p class="text-white/80 mt-1">{{ contractorName }}</p>
          </div>
        </div>

        <div class="flex gap-3 flex-wrap">
          <Button
              v-if="dashboard.pendingSignatures > 0"
              label="امضاهای در انتظار"
              icon="pi pi-signature"
              severity="warn"
              :badge="String(dashboard.pendingSignatures)"
              @click="router.push('/signatures')"
          />
          <Button
              label="قراردادهای من"
              icon="pi pi-file"
              class="!bg-white/10 !border-white/30 !text-white hover:!bg-white/20"
              outlined
              @click="router.push('/contracts')"
          />
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-16">
      <ProgressSpinner />
    </div>

    <template v-else>
      <!-- آمار اصلی -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
            title="قراردادهای فعال"
            :value="dashboard.activeContracts"
            icon="pi pi-file"
            color="success"
            unit="count"
            clickable
            @click="router.push('/contracts?status=active')"
        />

        <StatCard
            title="امضاهای در انتظار"
            :value="dashboard.pendingSignatures"
            icon="pi pi-signature"
            color="warn"
            unit="count"
            clickable
            @click="router.push('/signatures')"
        />

        <StatCard
            title="قراردادهای در حال اتمام"
            :value="dashboard.expiringContracts"
            icon="pi pi-exclamation-triangle"
            color="danger"
            unit="count"
        />

        <StatCard
            title="مجموع مبالغ قراردادها"
            :value="dashboard.totalAmount"
            icon="pi pi-dollar"
            color="primary"
            unit="smart"
        />
      </div>

      <!-- وضعیت مالی -->
      <Card>
        <template #title>
          <span class="flex items-center gap-2">
            <i class="pi pi-wallet text-primary" />
            وضعیت مالی
          </span>
        </template>
        <template #content>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="text-center p-4 rounded-xl bg-green-50 dark:bg-green-900/20">
            <p class="text-sm text-surface-600 dark:text-surface-400 mb-2">پرداخت شده</p>
            <AmountDisplay :amount="dashboard.paidAmount" size="xl" />
          </div>

          <div class="text-center p-4 rounded-xl bg-orange-50 dark:bg-orange-900/20">
            <p class="text-sm text-surface-600 dark:text-surface-400 mb-2">در انتظار پرداخت</p>
            <AmountDisplay :amount="dashboard.unpaidAmount" size="xl" />
          </div>

          <div class="text-center p-4 rounded-xl bg-surface-50 dark:bg-surface-800/50">
            <p class="text-sm text-surface-600 dark:text-surface-400 mb-2">درصد پرداخت</p>
            <div class="text-3xl font-bold text-primary mb-3">{{ paymentProgress }}٪</div>
            <ProgressBar :value="paymentProgress" class="h-2" />
          </div>
        </div>
        </template>
      </Card>

      <!-- قراردادها + امضاها -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- قراردادهای اخیر -->
        <Card class="lg:col-span-2">
          <template #title>
            <span class="flex items-center justify-between w-full">
              <span class="flex items-center gap-2">
                <i class="pi pi-file text-primary" />
                قراردادهای من
              </span>
              <Button
                  label="مشاهده همه"
                  icon="pi pi-arrow-left"
                  icon-pos="left"
                  size="small"
                  text
                  @click="router.push('/contracts')"
              />
            </span>
          </template>
          <template #content>

          <EmptyState
              v-if="!dashboard.contracts?.length"
              title="قراردادی ثبت نشده"
              description="هنوز قراردادی به شما ارجاع نشده است."
              icon="pi pi-file"
              compact
          />

          <div v-else class="space-y-3">
            <div
                v-for="contract in dashboard.contracts"
                :key="contract.id"
                class="border border-surface-200 dark:border-surface-700 rounded-xl p-4 hover:border-primary/40 hover:shadow-md transition-all cursor-pointer"
                @click="router.push(`/contracts/${contract.id}`)"
            >
              <div class="flex items-start justify-between gap-3 mb-3 flex-wrap">
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="font-mono text-sm font-medium">{{ contract.contract_number }}</span>
                    <ContractStatusBadge :status="getDisplayStatus(contract).value" />
                  </div>
                  <p class="text-sm text-surface-700 dark:text-surface-300 truncate">
                    {{ contract.subject }}
                  </p>
                </div>
                <AmountDisplay :amount="contract.current_amount" unit="smart" size="sm" />
              </div>

              <div class="grid grid-cols-2 gap-3 text-xs mb-3">
                <div>
                  <span class="text-surface-500 block mb-1">از تاریخ:</span>
                  <DateDisplay :date="contract.start_date" />
                </div>
                <div>
                  <span class="text-surface-500 block mb-1">تا تاریخ:</span>
                  <DateDisplay :date="contract.end_date" />
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between text-xs mb-1">
                  <span class="text-surface-500">پیشرفت زمانی</span>
                  <span class="font-medium">{{ getContractProgress(contract) }}٪</span>
                </div>
                <ProgressBar
                    :value="getContractProgress(contract)"
                    :show-value="false"
                    class="h-2"
                />
              </div>
            </div>
          </div>
          </template>
        </Card>

        <!-- امضاهای در انتظار -->
        <Card>
          <template #title>
            <span class="flex items-center gap-2">
              <i class="pi pi-signature text-orange-500" />
              امضاهای در انتظار
              <span
                  v-if="dashboard.pendingSignatures"
                  class="px-2 py-0.5 rounded-full bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 text-xs font-bold"
              >
                {{ dashboard.pendingSignatures }}
              </span>
            </span>
          </template>
          <template #content>
          <EmptyState
              v-if="!dashboard.pendingSignaturesList?.length"
              title="همه امضا شده"
              description="سند در انتظاری وجود ندارد."
              icon="pi pi-check-circle"
              compact
          />

          <div v-else class="space-y-2">
            <div
                v-for="sig in dashboard.pendingSignaturesList"
                :key="sig.id"
                class="flex items-center gap-3 p-3 rounded-lg bg-orange-50 dark:bg-orange-900/10 border border-orange-200 dark:border-orange-900/40 hover:border-orange-400 transition cursor-pointer"
                @click="router.push(`/signatures/${sig.id}`)"
            >
              <span class="w-10 h-10 rounded-lg bg-orange-500 text-white flex items-center justify-center shrink-0">
                <i :class="sig.signable_type?.includes('Contract') ? 'pi pi-file' : 'pi pi-file-edit'" />
              </span>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium">
                  {{ sig.signable_type?.includes('Contract') ? 'قرارداد' : 'الحاقیه' }}
                </p>
                <p class="text-xs text-surface-500">
                  <DateDisplay :date="sig.created_at" />
                </p>
              </div>
              <i class="pi pi-arrow-left text-orange-500" />
            </div>
          </div>
          </template>
        </Card>
      </div>

      <!-- صورت‌وضعیت‌ها و ضمانت‌نامه‌ها -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- صورت‌وضعیت‌ها -->
        <Card>
          <template #title>
            <span class="flex items-center gap-2">
              <i class="pi pi-receipt text-primary" />
              صورت‌وضعیت‌های من
            </span>
          </template>
          <template #content>
          <EmptyState
              v-if="!dashboard.pendingInvoices?.length"
              title="صورت‌وضعیتی نیست"
              icon="pi pi-check-circle"
              compact
          />

          <div v-else class="space-y-2">
            <div
                v-for="invoice in dashboard.pendingInvoices"
                :key="invoice.id"
                class="flex items-center justify-between p-3 rounded-lg border border-surface-200 dark:border-surface-700 hover:border-primary/40 transition cursor-pointer"
                @click="router.push(`/invoices/${invoice.id}`)"
            >
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium font-mono">{{ invoice.invoice_number }}</p>
                <p class="text-xs text-surface-500">دوره {{ invoice.invoice_period }}</p>
              </div>
              <div class="text-left">
                <AmountDisplay :amount="invoice.net_amount" size="sm" />
                <Tag
                    :value="invoice.status_label"
                    :severity="invoice.status === 'approved' ? 'success' : 'info'"
                    class="!text-xs mt-1"
                />
              </div>
            </div>
          </div>
          </template>
        </Card>

        <!-- ضمانت‌نامه‌های در حال اتمام -->
        <Card>
          <template #title>
            <span class="flex items-center gap-2">
              <i class="pi pi-shield text-red-500" />
              ضمانت‌نامه‌های در حال اتمام
            </span>
          </template>
          <template #content>
          <EmptyState
              v-if="!dashboard.expiringGuarantees?.length"
              title="موردی نیست"
              icon="pi pi-check-circle"
              compact
          />

          <div v-else class="space-y-2">
            <div
                v-for="g in dashboard.expiringGuarantees"
                :key="g.id"
                class="flex items-center justify-between p-3 rounded-lg border border-red-200 dark:border-red-900/40 bg-red-50/50 dark:bg-red-900/10"
            >
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium font-mono">{{ g.guarantee_number }}</p>
                <p class="text-xs text-surface-500">{{ g.type_label }} - {{ g.bank_name }}</p>
              </div>
              <div class="text-left">
                <AmountDisplay :amount="g.amount" size="sm" />
                <p class="text-xs text-red-600 mt-1">
                  <i class="pi pi-clock ml-1" />
                  {{ daysRemaining(g.expiry_date) }} روز مانده
                </p>
              </div>
            </div>
          </div>
          </template>
        </Card>
      </div>
    </template>
  </div>
</template>