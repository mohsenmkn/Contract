<!-- src/views/Payment/PaymentsList.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { usePaymentStore } from '@/stores/paymentStore'
import { usePayment } from '@/composables/usePayment'
import { usePermission } from '@/composables/usePermission'
import { useNumberFormatter } from '@/composables/useNumberFormatter'
import AmountDisplay from '@/components/contract/AmountDisplay.vue'
import DateDisplay from '@/components/contract/DateDisplay.vue'
import EmptyState from '@/components/contract/EmptyState.vue'
import { showToast } from '@/plugins/toast'

const router = useRouter()
const paymentStore = usePaymentStore()
const { methodOptions, statusOptions, getMethodInfo, getStatusInfo } = usePayment()
const { can } = usePermission()
const { formatSmartAmount } = useNumberFormatter()

// فیلترها
const search = ref('')
const statusFilter = ref(null)
const methodFilter = ref(null)
const fromDate = ref(null)
const toDate = ref(null)
const expanded = ref(false)

// محاسبه آمار
const stats = computed(() => {
  const payments = paymentStore.allPayments
  const total = payments.reduce((sum, p) => sum + parseFloat(p.amount || 0), 0)
  const completed = payments.filter(p => p.status === 'completed')
      .reduce((sum, p) => sum + parseFloat(p.amount || 0), 0)
  const pending = payments.filter(p => p.status === 'pending')
      .reduce((sum, p) => sum + parseFloat(p.amount || 0), 0)

  return {
    total,
    completed,
    pending,
    count: payments.length,
  }
})

// بارگذاری اولیه
onMounted(() => {
  paymentStore.fetchAllPayments()
})

// اعمال فیلتر
const applyFilters = () => {
  paymentStore.setFilters({
    search: search.value,
    status: statusFilter.value,
    method: methodFilter.value,
    from_date: fromDate.value,
    to_date: toDate.value,
  })
  paymentStore.setPage(1)
  paymentStore.fetchAllPayments()
}

// ریست فیلترها
const resetFilters = () => {
  search.value = ''
  statusFilter.value = null
  methodFilter.value = null
  fromDate.value = null
  toDate.value = null
  paymentStore.resetFilters()
  paymentStore.setPage(1)
  paymentStore.fetchAllPayments()
}

// تغییر صفحه
const onPageChange = (event) => {
  paymentStore.setPage(event.page + 1)
  paymentStore.fetchAllPayments()
}

// مشاهده جزئیات پرداخت
const viewPayment = (payment) => {
  // می‌توانیم به صفحه جزئیات پرداخت برویم یا دیالوگ نمایش دهیم
  router.push(`/payments/${payment.id}`)
}

// مشاهده صورت‌وضعیت مرتبط
const viewInvoice = (payment) => {
  if (payment.invoice?.id) {
    router.push(`/invoices/${payment.invoice.id}`)
  }
}

// مشاهده قرارداد مرتبط
const viewContract = (payment) => {
  if (payment.invoice?.contract?.id) {
    router.push(`/contracts/${payment.invoice.contract.id}`)
  }
}

// فرمت روش پرداخت برای نمایش
const formatMethod = (method) => {
  const info = getMethodInfo(method)
  return info?.label || method
}
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-2xl font-bold flex items-center gap-2">
          <i class="pi pi-wallet text-primary" />
          مدیریت پرداخت‌ها
        </h1>
        <p class="text-surface-500 text-sm mt-1">
          لیست تمام پرداخت‌های انجام شده در سامانه
        </p>
      </div>

      <Button
          v-if="can('payments.create')"
          label="ثبت پرداخت جدید"
          icon="pi pi-plus"
          @click="router.push('/payments/create')"
      />
    </div>

    <!-- آمار سریع -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card>
        <template #content>
        <div class="flex items-center gap-3">
          <span class="w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 flex items-center justify-center">
            <i class="pi pi-list text-xl" />
          </span>
          <div>
            <p class="text-xs text-surface-500">تعداد کل</p>
            <p class="text-xl font-bold">{{ stats.count.toLocaleString('fa-IR') }}</p>
          </div>
        </div>
        </template>
      </Card>

      <Card>
        <template #content>
        <div class="flex items-center gap-3">
          <span class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 flex items-center justify-center">
            <i class="pi pi-check-circle text-xl" />
          </span>
          <div>
            <p class="text-xs text-surface-500">پرداخت شده</p>
            <AmountDisplay :amount="stats.completed" size="lg" />
          </div>
        </div>
        </template>
      </Card>

      <Card>
        <template #content>
        <div class="flex items-center gap-3">
          <span class="w-12 h-12 rounded-xl bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 flex items-center justify-center">
            <i class="pi pi-clock text-xl" />
          </span>
          <div>
            <p class="text-xs text-surface-500">در انتظار</p>
            <AmountDisplay :amount="stats.pending" size="lg" />
          </div>
        </div>
        </template>
      </Card>

      <Card>
        <template #content>
        <div class="flex items-center gap-3">
          <span class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 flex items-center justify-center">
            <i class="pi pi-dollar text-xl" />
          </span>
          <div>
            <p class="text-xs text-surface-500">مجموع کل</p>
            <AmountDisplay :amount="stats.total" unit="smart" size="lg" />
          </div>
        </div>
        </template>
      </Card>
    </div>

    <!-- فیلترها -->
    <Card>
      <template #content>
      <div class="space-y-4">
        <!-- ردیف اصلی -->
        <div class="flex flex-wrap items-center gap-3">
          <div class="relative flex-1 min-w-64">
            <i class="pi pi-search absolute right-3 top-1/2 -translate-y-1/2 text-surface-400" />
            <InputText
                v-model="search"
                placeholder="جستجو در شماره پرداخت، پیگیری یا چک..."
                class="w-full !pr-10"
                @keyup.enter="applyFilters"
            />
          </div>

          <Select
              v-model="statusFilter"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              placeholder="وضعیت"
              class="w-44"
              show-clear
              @change="applyFilters"
          />

          <Select
              v-model="methodFilter"
              :options="methodOptions"
              option-label="label"
              option-value="value"
              placeholder="روش پرداخت"
              class="w-44"
              show-clear
              @change="applyFilters"
          />

          <Button
              :label="expanded ? 'بستن فیلترها' : 'فیلتر پیشرفته'"
              :icon="expanded ? 'pi pi-chevron-up' : 'pi pi-filter'"
              severity="secondary"
              outlined
              @click="expanded = !expanded"
          />

          <Button label="اعمال" icon="pi pi-check" @click="applyFilters" />
          <Button label="پاک‌کردن" icon="pi pi-refresh" severity="secondary" text @click="resetFilters" />
        </div>

        <!-- فیلترهای پیشرفته -->
        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0 -translate-y-2"
            leave-active-class="transition duration-150"
            leave-to-class="opacity-0"
        >
          <div
              v-if="expanded"
              class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 rounded-lg bg-surface-50 dark:bg-surface-800/50"
          >
            <div>
              <label class="block text-sm font-medium mb-2">از تاریخ</label>
              <DatePicker
                  v-model="fromDate"
                  format="YYYY-MM-DD"
                  display-format="jYYYY/jMM/jDD"
                  placeholder="انتخاب تاریخ شروع"
                  class="w-full"
              />
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">تا تاریخ</label>
              <DatePicker
                  v-model="toDate"
                  format="YYYY-MM-DD"
                  display-format="jYYYY/jMM/jDD"
                  placeholder="انتخاب تاریخ پایان"
                  class="w-full"
              />
            </div>
          </div>
        </Transition>
      </div>
      </template>
    </Card>

    <!-- جدول -->
    <Card>
      <template #content>
      <EmptyState
          v-if="!paymentStore.allPayments.length && !paymentStore.loading"
          title="پرداختی ثبت نشده"
          description="هنوز پرداختی در سیستم ثبت نشده است."
          icon="pi pi-wallet"
      />

      <DataTable
          v-else
          :value="paymentStore.allPayments"
          :loading="paymentStore.loading"
          :paginator="true"
          :rows="15"
          :rows-per-page-options="[15, 30, 50]"
          striped-rows
          responsive-layout="scroll"
          :total-records="paymentStore.pagination.total"
          lazy
          @page="onPageChange"
      >
        <Column field="payment_number" header="شماره پرداخت" :sortable="true" class="min-w-36">
          <template #body="{ data }">
            <span class="font-mono text-sm font-medium">{{ data.payment_number }}</span>
          </template>
        </Column>

        <Column field="invoice.invoice_number" header="صورت‌وضعیت" class="min-w-32">
          <template #body="{ data }">
            <button
                v-if="data.invoice"
                class="font-mono text-sm text-primary hover:underline cursor-pointer"
                @click="viewInvoice(data)"
            >
              {{ data.invoice.invoice_number }}
            </button>
            <span v-else class="text-surface-400">—</span>
          </template>
        </Column>

        <Column field="invoice.contract.contract_number" header="قرارداد" class="min-w-32">
          <template #body="{ data }">
            <button
                v-if="data.invoice?.contract"
                class="font-mono text-sm text-primary hover:underline cursor-pointer"
                @click="viewContract(data)"
            >
              {{ data.invoice.contract.contract_number }}
            </button>
            <span v-else class="text-surface-400">—</span>
          </template>
        </Column>

        <Column field="amount" header="مبلغ" :sortable="true" class="min-w-40 text-left">
          <template #body="{ data }">
            <AmountDisplay :amount="data.amount" />
          </template>
        </Column>

        <Column field="payment_date" header="تاریخ پرداخت" :sortable="true" class="min-w-32">
          <template #body="{ data }">
            <DateDisplay :date="data.payment_date" />
          </template>
        </Column>

        <Column field="method" header="روش پرداخت" class="min-w-32">
          <template #body="{ data }">
            <Tag
                :value="formatMethod(data.method)"
                :icon="getMethodInfo(data.method)?.icon"
                severity="info"
                class="!text-xs"
            />
          </template>
        </Column>

        <Column field="reference_number" header="شماره پیگیری/چک" class="min-w-36">
          <template #body="{ data }">
            <div class="font-mono text-xs">
              <div v-if="data.reference_number">
                <i class="pi pi-hashtag ml-1 text-surface-400" />
                {{ data.reference_number }}
              </div>
              <div v-if="data.check_number" class="mt-1">
                <i class="pi pi-money-bill ml-1 text-surface-400" />
                چک: {{ data.check_number }}
              </div>
              <span v-if="!data.reference_number && !data.check_number" class="text-surface-400">—</span>
            </div>
          </template>
        </Column>

        <Column field="status" header="وضعیت" class="min-w-32">
          <template #body="{ data }">
            <Tag
                :value="data.status_label"
                :severity="getStatusInfo(data.status).color"
                :icon="getStatusInfo(data.status).icon"
                class="!text-xs"
            />
          </template>
        </Column>

        <Column field="creator.name" header="ثبت کننده" class="min-w-32">
          <template #body="{ data }">
            <span class="text-sm">{{ data.creator?.name || '—' }}</span>
          </template>
        </Column>

        <Column header="عملیات" class="min-w-24 text-center">
          <template #body="{ data }">
            <div class="flex items-center justify-center gap-1">
              <Button
                  icon="pi pi-eye"
                  severity="info"
                  text
                  rounded
                  size="small"
                  v-tooltip="'مشاهده جزئیات'"
                  @click="viewPayment(data)"
              />
              <Button
                  v-if="data.invoice"
                  icon="pi pi-receipt"
                  severity="secondary"
                  text
                  rounded
                  size="small"
                  v-tooltip="'مشاهده صورت‌وضعیت'"
                  @click="viewInvoice(data)"
              />
            </div>
          </template>
        </Column>

        <template #empty>
          <div class="text-center py-8 text-surface-500">
            <i class="pi pi-wallet text-4xl mb-3 block" />
            <p>پرداختی یافت نشد</p>
          </div>
        </template>
      </DataTable>
      </template>
    </Card>
  </div>
</template>