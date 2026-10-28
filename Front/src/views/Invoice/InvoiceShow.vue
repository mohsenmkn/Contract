<!-- src/views/Invoice/InvoiceShow.vue -->
<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useInvoiceStore } from '@/stores/contract/invoiceStore'
import { usePaymentStore } from '@/stores/contract/paymentStore'
import { useInvoice } from '@/composables/useInvoice'
import { usePermission } from '@/composables/usePermission'
import AmountDisplay from '@/components/contract/AmountDisplay.vue'
import DateDisplay from '@/components/contract/DateDisplay.vue'
import ProgressBar from 'primevue/progressbar'
import { showToast } from '@/plugins/toast'

const router = useRouter()
const route = useRoute()
const invoiceStore = useInvoiceStore()
const paymentStore = usePaymentStore()
const { getStatusInfo, getPaymentProgress, getRemainingAmount } = useInvoice()
const { can } = usePermission()
const confirm = useConfirm()

const loading = ref(true)
const invoice = ref(null)

onMounted(async () => {
  try {
    invoice.value = await invoiceStore.fetchInvoiceById(route.params.id)
    await paymentStore.fetchPaymentsByInvoice(route.params.id)
  } catch (err) {
    showToast({ severity: 'error', summary: 'خطا', detail: err.message, life: 5000 })
    router.push('/invoices')
  } finally {
    loading.value = false
  }
})

const handleSubmit = () => {
  confirm.require({
    message: 'صورت‌وضعیت برای تایید ارسال شود؟',
    header: 'ارسال برای تایید',
    icon: 'pi pi-send',
    accept: async () => {
      try {
        await invoiceStore.submitInvoice(invoice.value.id)
        invoice.value = invoiceStore.currentInvoice
        showToast({ severity: 'success', summary: 'ارسال شد', life: 3000 })
      } catch (err) {
        showToast({ severity: 'error', summary: 'خطا', detail: err.message, life: 5000 })
      }
    },
  })
}

const handleApprove = () => {
  confirm.require({
    message: 'صورت‌وضعیت تایید شود؟',
    header: 'تایید صورت‌وضعیت',
    icon: 'pi pi-check-circle',
    accept: async () => {
      try {
        await invoiceStore.approveInvoice(invoice.value.id)
        invoice.value = invoiceStore.currentInvoice
        showToast({ severity: 'success', summary: 'تایید شد', life: 3000 })
      } catch (err) {
        showToast({ severity: 'error', summary: 'خطا', detail: err.message, life: 5000 })
      }
    },
  })
}
</script>

<template>
  <div class="p-6">
    <div v-if="loading" class="text-center py-12">
      <ProgressSpinner />
    </div>

    <div v-else-if="invoice" class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <h1 class="text-2xl font-bold">{{ invoice.invoice_number }}</h1>
            <Tag
                :value="invoice.status_label"
                :severity="getStatusInfo(invoice.status).color"
            />
          </div>
          <p class="text-surface-600 dark:text-surface-400">
            قرارداد: {{ invoice.contract?.contract_number }}
          </p>
        </div>

        <div class="flex gap-2">
          <Button
              v-if="can('invoices.submit') && invoice.status === 'draft'"
              label="ارسال برای تایید"
              icon="pi pi-send"
              severity="success"
              outlined
              @click="handleSubmit"
          />
          <Button
              v-if="can('invoices.approve') && invoice.status === 'submitted'"
              label="تایید"
              icon="pi pi-check"
              @click="handleApprove"
          />
          <Button
              v-if="can('payments.create') && invoice.status === 'approved'"
              label="ثبت پرداخت"
              icon="pi pi-wallet"
              @click="router.push(`/invoices/${invoice.id}/payments/create`)"
          />
          <Button
              label="بازگشت"
              icon="pi pi-arrow-right"
              severity="secondary"
              outlined
              @click="router.back()"
          />
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <div class="text-sm text-surface-500 mb-2">مبلغ ناخالص</div>
          <template #content>
          <AmountDisplay :amount="invoice.gross_amount" size="lg" />
          </template>
        </Card>

        <Card>
          <div class="text-sm text-surface-500 mb-2">کسورات</div>
          <template #content>
          <AmountDisplay :amount="invoice.deductions" size="lg" colorize />
          </template>
        </Card>

        <Card>
          <div class="text-sm text-surface-500 mb-2">مالیات</div>
          <template #content>
          <AmountDisplay :amount="invoice.tax_amount" size="lg" />
          </template>
        </Card>

        <Card>
          <div class="text-sm text-surface-500 mb-2">مبلغ خالص</div>
          <template #content>
          <AmountDisplay :amount="invoice.net_amount" size="lg" />
          </template>
        </Card>
      </div>

      <!-- اطلاعات -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card>
          <template #title>اطلاعات صورت‌وضعیت</template>
          <template #content>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-surface-500">دوره:</span>
              <span class="font-medium">{{ invoice.invoice_period }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-surface-500">از تاریخ:</span>
              <DateDisplay :date="invoice.from_date" />
            </div>
            <div class="flex justify-between">
              <span class="text-surface-500">تا تاریخ:</span>
              <DateDisplay :date="invoice.to_date" />
            </div>
            <div class="flex justify-between">
              <span class="text-surface-500">وضعیت:</span>
              <Tag :value="invoice.status_label" :severity="getStatusInfo(invoice.status).color" />
            </div>
          </div>
          </template>
        </Card>

        <Card>
          <template #title>وضعیت پرداخت</template>
          <template #content>
          <div class="space-y-3">
            <div class="flex justify-between text-sm">
              <span class="text-surface-500">پرداخت شده:</span>
              <AmountDisplay :amount="invoice.paid_amount || 0" />
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-surface-500">مانده:</span>
              <AmountDisplay :amount="getRemainingAmount(invoice)" />
            </div>
            <ProgressBar :value="getPaymentProgress(invoice)" class="h-3" />
            <div class="text-xs text-surface-500 text-center">
              {{ getPaymentProgress(invoice) }}٪ پرداخت شده
            </div>
          </div>
          </template>
        </Card>
      </div>

      <!-- آیتم‌ها -->
      <Card v-if="invoice.items?.length">
        <template #title>آیتم‌های صورت‌وضعیت</template>
        <template #content>
        <DataTable :value="invoice.items" striped-rows>
          <Column field="description" header="شرح" />
          <Column field="unit" header="واحد" class="w-24" />
          <Column field="quantity" header="مقدار" class="w-24 text-left">
            <template #body="{ data }">
              {{ data.quantity?.toLocaleString('fa-IR') }}
            </template>
          </Column>
          <Column field="unit_price" header="بهای واحد" class="w-40 text-left">
            <template #body="{ data }">
              <AmountDisplay :amount="data.unit_price" :show-unit="false" />
            </template>
          </Column>
          <Column field="total_price" header="مبلغ کل" class="w-40 text-left">
            <template #body="{ data }">
              <AmountDisplay :amount="data.total_price" :show-unit="false" />
            </template>
          </Column>
        </DataTable>
        </template>
      </Card>

      <!-- پرداخت‌ها -->
      <Card>
        <template #title>پرداخت‌ها</template>
        <template #content>
        <EmptyState
            v-if="!paymentStore.payments.length"
            title="پرداختی ثبت نشده"
            icon="pi pi-wallet"
            compact
        />
        <DataTable v-else :value="paymentStore.payments" striped-rows>
          <Column field="payment_number" header="شماره پرداخت" class="min-w-32">
            <template #body="{ data }">
              <span class="font-mono text-sm">{{ data.payment_number }}</span>
            </template>
          </Column>
          <Column field="amount" header="مبلغ" class="min-w-40 text-left">
            <template #body="{ data }">
              <AmountDisplay :amount="data.amount" />
            </template>
          </Column>
          <Column field="payment_date" header="تاریخ پرداخت" class="min-w-32">
            <template #body="{ data }">
              <DateDisplay :date="data.payment_date" />
            </template>
          </Column>
          <Column field="method" header="روش" class="min-w-32">
            <template #body="{ data }">
              <Tag :value="data.method_label" severity="info" class="!text-xs" />
            </template>
          </Column>
          <Column field="status" header="وضعیت" class="min-w-32">
            <template #body="{ data }">
              <Tag :value="data.status_label" :severity="data.status === 'completed' ? 'success' : 'warn'" class="!text-xs" />
            </template>
          </Column>
        </DataTable>
        </template>
      </Card>

      <!-- توضیحات -->
      <Card v-if="invoice.description">
        <template #title>توضیحات</template>
        <template #content>
        <p class="text-sm leading-7">{{ invoice.description }}</p>
        </template>
      </Card>
    </div>
  </div>
</template>