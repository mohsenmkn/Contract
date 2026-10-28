<!-- src/views/Contract/ContractorPortal.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useContractStore } from '@/stores/contractStore'
import { useSignatureStore } from '@/stores/signatureStore'
import { useInvoiceStore } from '@/stores/invoiceStore'
import { useGuaranteeStore } from '@/stores/guaranteeStore'
import { useContract } from '@/composables/useContract'
import { useNumberFormatter } from '@/composables/useNumberFormatter'
import { useDateFormatter } from '@/composables/useDateFormatter'
import StatCard from '@/components/contract/StatCard.vue'
import ContractStatusBadge from '@/components/contract/ContractStatusBadge.vue'
import AmountDisplay from '@/components/contract/AmountDisplay.vue'
import DateDisplay from '@/components/contract/DateDisplay.vue'
import EmptyState from '@/components/contract/EmptyState.vue'
import ProgressBar from 'primevue/progressbar'

const router = useRouter()
const authStore = useAuthStore()
const contractStore = useContractStore()
const signatureStore = useSignatureStore()
const invoiceStore = useInvoiceStore()
const guaranteeStore = useGuaranteeStore()
const { getContractProgress, getDisplayStatus } = useContract()
const { formatSmartAmount } = useNumberFormatter()
const { daysRemaining } = useDateFormatter()

const loading = ref(true)
const stats = ref({
  activeContracts: 0,
  pendingSignatures: 0,
  pendingInvoices: 0,
  totalAmount: 0,
  expiringContracts: 0,
  paidAmount: 0,
  unpaidAmount: 0,
})

const recentContracts = ref([])
const pendingInvoices = ref([])
const expiringGuarantees = ref([])

onMounted(async () => {
  loading.value = true
  try {
    // بارگذاری موازی داده‌ها
    await Promise.all([
      contractStore.fetchContracts({ per_page: 5, sort_by: 'created_at', sort_dir: 'desc' }),
      signatureStore.fetchMyPendingSignatures(),
      invoiceStore.fetchInvoicesByContract(null, { status: 'submitted' }).catch(() => []),
    ])

    const contracts = contractStore.contracts

    // محاسبه آمار
    stats.value.activeContracts = contracts.filter(c => c.status === 'active').length
    stats.value.pendingSignatures = signatureStore.pendingCount
    stats.value.totalAmount = contracts.reduce((sum, c) => sum + parseFloat(c.current_amount || 0), 0)
    stats.value.expiringContracts = contracts.filter(c => c.is_expiring).length

    recentContracts.value = contracts.slice(0, 5)

    // صورت‌وضعیت‌های در انتظار پرداخت
    pendingInvoices.value = contracts.flatMap(c =>
        (c.invoices || []).filter(i => ['submitted', 'approved'].includes(i.status))
    ).slice(0, 5)

    // ضمانت‌نامه‌های در حال اتمام
    expiringGuarantees.value = contracts.flatMap(c =>
        (c.guarantees || []).filter(g => g.is_expiring)
    ).slice(0, 5)
  } catch (err) {
    console.error('Error loading contractor dashboard:', err)
  } finally {
    loading.value = false
  }
})

const contractorName = computed(() => {
  return authStore.user?.party?.name || authStore.user?.name || 'پیمانکار'
})
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-l from-primary-500 to-primary-700 dark:from-primary-700 dark:to-primary-900 rounded-2xl p-6 text-white shadow-lg">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <span class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
              <i class="pi pi-building text-2xl" />
            </span>
            <div>
              <h1 class="text-2xl font-bold">پورتال پیمانکار</h1>
              <p class="text-white/80 text-sm">{{ contractorName }}</p>
            </div>
          </div>
        </div>

        <div class="flex gap-3">
          <Button
              v-if="stats.pendingSignatures > 0"
              label="امضاهای در انتظار"
              icon="pi pi-signature"
              severity="warn"
              :badge="String(stats.pendingSignatures)"
              @click="router.push('/signatures')"
          />
          <Button
              label="قراردادهای من"
              icon="pi pi-file"
              outlined
              class="!bg-white/10 !border-white/30 !text-white hover:!bg-white/20"
              @click="router.push('/contracts')"
          />
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <ProgressSpinner />
    </div>

    <template v-else>
      <!-- آمار -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
            title="قراردادهای فعال"
            :value="stats.activeContracts"
            icon="pi pi-file"
            color="success"
            unit="count"
            clickable
            @click="router.push('/contracts?status=active')"
        />

        <StatCard
            title="امضاهای در انتظار"
            :value="stats.pendingSignatures"
            icon="pi pi-signature"
            color="warn"
            unit="count"
            clickable
            @click="router.push('/signatures')"
        />

        <StatCard
            title="قراردادهای در حال اتمام"
            :value="stats.expiringContracts"
            icon="pi pi-exclamation-triangle"
            color="danger"
            unit="count"
            clickable
            @click="router.push('/contracts?expiring_soon=30')"
        />

        <StatCard
            title="مجموع مبالغ قراردادها"
            :value="stats.totalAmount"
            icon="pi pi-dollar"
            color="primary"
            unit="smart"
        />
      </div>

      <!-- قراردادها + امضاها -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- قراردادهای اخیر -->
        <Card class="lg:col-span-2">
          <template #title>
            <span class="flex items-center justify-between">
              <span class="flex items-center gap-2">
                <i class="pi pi-file text-primary" />
                قراردادهای اخیر
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
              v-if="!recentContracts.length"
              title="قراردادی ثبت نشده"
              description="هنوز قراردادی به شما ارجاع نشده است."
              icon="pi pi-file"
              compact
          />

          <div v-else class="space-y-3">
            <div
                v-for="contract in recentContracts"
                :key="contract.id"
                class="border border-surface-200 dark:border-surface-700 rounded-lg p-4 hover:border-primary/40 transition cursor-pointer"
                @click="router.push(`/contracts/${contract.id}`)"
            >
              <div class="flex items-start justify-between gap-3 mb-3">
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="font-mono text-sm font-medium">{{ contract.contract_number }}</span>
                    <ContractStatusBadge :status="getDisplayStatus(contract).value" />
                  </div>
                  <p class="text-sm text-surface-700 dark:text-surface-300 truncate">
                    {{ contract.subject }}
                  </p>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3 text-xs">
                <div>
                  <span class="text-surface-500 block">مبلغ:</span>
                  <AmountDisplay :amount="contract.current_amount" unit="smart" size="sm" />
                </div>
                <div>
                  <span class="text-surface-500 block">پایان:</span>
                  <DateDisplay :date="contract.end_date" />
                </div>
              </div>

              <div class="mt-3">
                <ProgressBar
                    :value="getContractProgress(contract)"
                    :show-value="false"
                    class="h-1.5"
                />
                <div class="text-xs text-surface-500 mt-1 text-left">
                  {{ getContractProgress(contract) }}٪ از زمان سپری شده
                </div>
              </div>
            </div>
          </div>
          </template>
        </Card>

        <!-- امضاهای در انتظار -->
        <Card>
          <template #title>
            <span class="flex items-center gap-2">
              <i class="pi pi-signature text-warn-500" />
              امضاهای در انتظار
              <Tag v-if="stats.pendingSignatures" :value="String(stats.pendingSignatures)" severity="warn" class="!text-xs" />
            </span>
          </template>
          <template #content>
          <EmptyState
              v-if="!signatureStore.pendingSignatures.length"
              title="موردی نیست"
              description="همه اسناد امضا شده‌اند."
              icon="pi pi-check-circle"
              compact
          />

          <div v-else class="space-y-2">
            <div
                v-for="sig in signatureStore.pendingSignatures.slice(0, 5)"
                :key="sig.id"
                class="flex items-center gap-3 p-3 rounded-lg bg-surface-50 dark:bg-surface-800/50 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition cursor-pointer"
                @click="router.push(`/signatures/${sig.id}`)"
            >
              <span class="w-10 h-10 rounded-lg bg-warn-100 dark:bg-warn-900/40 text-warn-700 dark:text-warn-300 flex items-center justify-center shrink-0">
                <i :class="sig.signable_type?.includes('Contract') ? 'pi pi-file' : 'pi pi-file-edit'" />
              </span>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium truncate">
                  {{ sig.signable_type?.includes('Contract') ? 'قرارداد' : 'الحاقیه' }}
                  #{{ sig.signable_id }}
                </p>
                <p class="text-xs text-surface-500">
                  <DateDisplay :date="sig.created_at" />
                </p>
              </div>
              <i class="pi pi-arrow-left text-surface-400" />
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
              صورت‌وضعیت‌های در انتظار
            </span>
          </template>
          <template #content>
          <EmptyState
              v-if="!pendingInvoices.length"
              title="صورت‌وضعیتی در انتظار نیست"
              icon="pi pi-check-circle"
              compact
          />

          <div v-else class="space-y-2">
            <div
                v-for="invoice in pendingInvoices"
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
              <i class="pi pi-shield text-danger-500" />
              ضمانت‌نامه‌های در حال اتمام
            </span>
          </template>
          <template #content>
          <EmptyState
              v-if="!expiringGuarantees.length"
              title="ضمانت‌نامه‌ای در حال اتمام نیست"
              icon="pi pi-check-circle"
              compact
          />

          <div v-else class="space-y-2">
            <div
                v-for="g in expiringGuarantees"
                :key="g.id"
                class="flex items-center justify-between p-3 rounded-lg border border-orange-200 dark:border-orange-900/40 bg-orange-50 dark:bg-orange-900/10"
            >
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium font-mono">{{ g.guarantee_number }}</p>
                <p class="text-xs text-surface-500">{{ g.type_label }} - {{ g.bank_name }}</p>
              </div>
              <div class="text-left">
                <AmountDisplay :amount="g.amount" size="sm" />
                <p class="text-xs text-orange-600 mt-1">
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