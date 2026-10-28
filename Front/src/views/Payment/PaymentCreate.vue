<!-- src/views/Payment/PaymentCreate.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { usePaymentStore } from '@/stores/contract/paymentStore'
import { useInvoiceStore } from '@/stores/contract/invoiceStore'
import { usePayment } from '@/composables/usePayment'
import AmountInput from '@/components/contract/AmountInput.vue'
import AmountDisplay from '@/components/contract/AmountDisplay.vue'
import { showToast } from '@/plugins/toast'

const router = useRouter()
const route = useRoute()
const paymentStore = usePaymentStore()
const invoiceStore = useInvoiceStore()
const { methodOptions } = usePayment()

const loading = ref(false)
const errors = ref({})

const invoiceId = computed(() => route.params.invoiceId || route.query.invoice_id)
const invoice = computed(() => invoiceStore.currentInvoice)

const form = ref({
  invoice_id: invoiceId.value,
  amount: null,
  payment_date: null,
  method: 'bank_transfer',
  reference_number: '',
  check_number: '',
  check_due_date: null,
  description: '',
})

onMounted(async () => {
  if (invoiceId.value) {
    await invoiceStore.fetchInvoiceById(invoiceId.value)
  }
})

const validate = () => {
  errors.value = {}

  if (!form.value.amount) errors.value.amount = 'مبلغ الزامی است'
  if (!form.value.payment_date) errors.value.payment_date = 'تاریخ پرداخت الزامی است'
  if (!form.value.method) errors.value.method = 'روش پرداخت الزامی است'

  if (form.value.method === 'check' && !form.value.check_number) {
    errors.value.check_number = 'شماره چک الزامی است'
  }

  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validate()) return

  loading.value = true
  try {
    await paymentStore.createPayment(form.value)
    showToast({ severity: 'success', summary: 'پرداخت ثبت شد', life: 3000 })
    router.push(`/invoices/${invoiceId.value}`)
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
        <i class="pi pi-wallet ml-2 text-primary" />
        ثبت پرداخت جدید
      </h1>
      <p v-if="invoice" class="text-surface-600 dark:text-surface-400 mt-2">
        صورت‌وضعیت: {{ invoice.invoice_number }} - مبلغ خالص:
        <AmountDisplay :amount="invoice.net_amount" :show-unit="false" />
      </p>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- مبلغ و تاریخ -->
      <Card>
        <template #title>اطلاعات پرداخت</template>
        <template #content>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <AmountInput
              v-model="form.amount"
              label="مبلغ پرداخت"
              :required="true"
              :invalid="!!errors.amount"
          />
          <small v-if="errors.amount" class="text-red-500">{{ errors.amount }}</small>

          <div>
            <label class="block text-sm font-medium mb-2">
              تاریخ پرداخت <span class="text-red-500">*</span>
            </label>
            <DatePicker
                v-model="form.payment_date"
                format="YYYY-MM-DD"
                display-format="jYYYY/jMM/jDD"
                class="w-full"
                :invalid="!!errors.payment_date"
            />
            <small v-if="errors.payment_date" class="text-red-500">{{ errors.payment_date }}</small>
          </div>
        </div>
        </template>
      </Card>

      <!-- روش پرداخت -->
      <Card>
        <template #title>روش پرداخت</template>
        <template #content>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-2">
              روش پرداخت <span class="text-red-500">*</span>
            </label>
            <Select
                v-model="form.method"
                :options="methodOptions"
                option-label="label"
                option-value="value"
                class="w-full"
                :invalid="!!errors.method"
            />
            <small v-if="errors.method" class="text-red-500">{{ errors.method }}</small>
          </div>

          <div v-if="form.method === 'bank_transfer'">
            <label class="block text-sm font-medium mb-2">شماره پیگیری</label>
            <InputText v-model="form.reference_number" class="w-full" />
          </div>

          <div v-if="form.method === 'check'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-2">
                شماره چک <span class="text-red-500">*</span>
              </label>
              <InputText
                  v-model="form.check_number"
                  class="w-full"
                  :invalid="!!errors.check_number"
              />
              <small v-if="errors.check_number" class="text-red-500">{{ errors.check_number }}</small>
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">تاریخ سررسید چک</label>
              <DatePicker
                  v-model="form.check_due_date"
                  format="YYYY-MM-DD"
                  display-format="jYYYY/jMM/jDD"
                  class="w-full"
              />
            </div>
          </div>
        </div>
        </template>
      </Card>

      <!-- توضیحات -->
      <Card>
        <template #title>توضیحات</template>
        <template #content>
        <Textarea v-model="form.description" rows="3" class="w-full" />
        </template>
      </Card>

      <!-- دکمه‌ها -->
      <div class="flex items-center justify-end gap-3">
        <Button label="انصراف" severity="secondary" outlined @click="router.back()" />
        <Button type="submit" label="ثبت پرداخت" icon="pi pi-check" :loading="loading" />
      </div>
    </form>
  </div>
</template>