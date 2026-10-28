<!-- src/views/Invoice/InvoicesList.vue -->
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useInvoiceStore } from '@/stores/invoiceStore'
import { useInvoice } from '@/composables/useInvoice'
import { usePermission } from '@/composables/usePermission'
import AmountDisplay from '@/components/contract/AmountDisplay.vue'
import DateDisplay from '@/components/contract/DateDisplay.vue'
import EmptyState from '@/components/contract/EmptyState.vue'
import ProgressBar from 'primevue/progressbar'

const router = useRouter()
const invoiceStore = useInvoiceStore()
const { getStatusInfo, getPaymentProgress } = useInvoice()
const { can } = usePermission()

onMounted(() => {
  // لیست صورت‌وضعیت‌ها
})
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">
        <i class="pi pi-receipt ml-2 text-primary" />
        مدیریت صورت‌وضعیت‌ها
      </h1>
    </div>

    <!-- Table -->
    <Card>
      <template #content>
      <EmptyState
          v-if="!invoiceStore.invoices.length && !invoiceStore.loading"
          title="صورت‌وضعیتی ثبت نشده"
          description="هنوز صورت‌وضعیتی در سیستم ثبت نشده است."
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
            <span class="font-mono text-sm font-medium">{{ data.invoice_number }}</span>
          </template>
        </Column>

        <Column field="contract.contract_number" header="قرارداد" class="min-w-32">
          <template #body="{ data }">
            <span class="text-sm">{{ data.contract?.contract_number }}</span>
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

        <Column field="paid_amount" header="پرداخت شده" class="min-w-40 text-left">
          <template #body="{ data }">
            <AmountDisplay :amount="data.paid_amount || 0" :show-unit="false" />
            <ProgressBar
                :value="getPaymentProgress(data)"
                :show-value="false"
                class="h-1.5 mt-1"
            />
          </template>
        </Column>

        <Column field="status" header="وضعیت" class="min-w-32">
          <template #body="{ data }">
            <Tag
                :value="data.status_label"
                :severity="getStatusInfo(data.status).color"
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
                v-tooltip="'مشاهده'"
                @click="router.push(`/invoices/${data.id}`)"
            />
          </template>
        </Column>
      </DataTable>
      </template>
    </Card>
  </div>
</template>