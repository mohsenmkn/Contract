<!-- src/views/Invoice/InvoiceCreate.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useInvoiceStore } from '@/stores/invoiceStore'
import { useContractStore } from '@/stores/contractStore'
import AmountInput from '@/components/contract/AmountInput.vue'
import AmountDisplay from '@/components/contract/AmountDisplay.vue'
import { showToast } from '@/plugins/toast'

const router = useRouter()
const route = useRoute()
const invoiceStore = useInvoiceStore()
const contractStore = useContractStore()

const loading = ref(false)
const errors = ref({})

const contractId = computed(() => route.params.contractId || route.query.contract_id)
const contract = computed(() => contractStore.currentContract)

const form = ref({
  contract_id: contractId.value,
  period: 1,
  from_date: null,
  to_date: null,
  gross_amount: null,
  deductions: 0,
  tax_amount: 0,
  description: '',
  items: [],
})

onMounted(async () => {
  if (contractId.value) {
    await contractStore.fetchContractById(contractId.value)
  }
})

const netAmount = computed(() => {
  const gross = parseFloat(form.value.gross_amount) || 0
  const deductions = parseFloat(form.value.deductions) || 0
  const tax = parseFloat(form.value.tax_amount) || 0
  return gross - deductions + tax
})

const validate = () => {
  errors.value = {}

  if (!form.value.period) errors.value.period = 'دوره الزامی است'
  if (!form.value.from_date) errors.value.from_date = 'تاریخ شروع الزامی است'
  if (!form.value.to_date) errors.value.to_date = 'تاریخ پایان الزامی است'
  if (!form.value.gross_amount) errors.value.gross_amount = 'مبلغ ناخالص الزامی است'

  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validate()) return

  loading.value = true
  try {
    await invoiceStore.createInvoice(form.value)
    showToast({ severity: 'success', summary: 'صورت‌وضعیت ایجاد شد', life: 3000 })
    router.push(`/contracts/${contractId.value}`)
  } catch (err) {
    showToast({ severity: 'error', summary: 'خطا', detail: err.message, life: 5000 })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="p-6 max-w-4xl mx-auto">
    <div class="mb-6">
      <h1 class="text-2xl font-bold">
        <i class="pi pi-receipt ml-2 text-primary" />
        ایجاد صورت‌وضعیت جدید
      </h1>
      <p v-if="contract" class="text-surface-600 dark:text-surface-400 mt-2">
        قرارداد: {{ contract.contract_number }} - {{ contract.subject }}
      </p>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- اطلاعات پایه -->
      <Card>
        <template #title>اطلاعات پایه</template>
        <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium mb-2">
              دوره <span class="text-red-500">*</span>
            </label>
            <InputNumber
                v-model="form.period"
                :min="1"
                class="w-full"
                :invalid="!!errors.period"
            />
            <small v-if="errors.period" class="text-red-500">{{ errors.period }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">
              از تاریخ <span class="text-red-500">*</span>
            </label>
            <DatePicker
                v-model="form.from_date"
                format="YYYY-MM-DD"
                display-format="jYYYY/jMM/jDD"
                class="w-full"
                :invalid="!!errors.from_date"
            />
            <small v-if="errors.from_date" class="text-red-500">{{ errors.from_date }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">
              تا تاریخ <span class="text-red-500">*</span>
            </label>
            <DatePicker
                v-model="form.to_date"
                format="YYYY-MM-DD"
                display-format="jYYYY/jMM/jDD"
                class="w-full"
                :invalid="!!errors.to_date"
            />
            <small v-if="errors.to_date" class="text-red-500">{{ errors.to_date }}</small>
          </div>
        </div>
        </template>
      </Card>

      <!-- مبالغ -->
      <Card>
        <template #title>مبالغ</template>
        <template #content>
        <div class="space-y-4">
          <AmountInput
              v-model="form.gross_amount"
              label="مبلغ ناخالص"
              :required="true"
              :invalid="!!errors.gross_amount"
          />
          <small v-if="errors.gross_amount" class="text-red-500">{{ errors.gross_amount }}</small>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <AmountInput
                v-model="form.deductions"
                label="کسورات"
            />
            <AmountInput
                v-model="form.tax_amount"
                label="مالیات"
            />
          </div>

          <div class="bg-primary-50 dark:bg-primary-900/20 p-4 rounded-lg">
            <div class="flex justify-between items-center">
              <span class="font-semibold">مبلغ خالص قابل پرداخت:</span>
              <AmountDisplay :amount="netAmount" size="lg" />
            </div>
          </div>
        </div>
        </template>
      </Card>

      <!-- توضیحات -->
      <Card>
        <template #title>توضیحات</template>
        <template #content>
        <Textarea
            v-model="form.description"
            rows="4"
            class="w-full"
            placeholder="توضیحات صورت‌وضعیت..."
        />
        </template>
      </Card>

      <!-- دکمه‌ها -->
      <div class="flex items-center justify-end gap-3">
        <Button
            label="انصراف"
            severity="secondary"
            outlined
            @click="router.back()"
        />
        <Button
            type="submit"
            label="ایجاد صورت‌وضعیت"
            icon="pi pi-check"
            :loading="loading"
        />
      </div>
    </form>
  </div>
</template>