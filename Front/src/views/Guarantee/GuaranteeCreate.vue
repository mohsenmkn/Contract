<!-- src/views/Guarantee/GuaranteeCreate.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useGuaranteeStore } from '@/stores/guaranteeStore'
import api from '@/api/axios'
import { useGuarantee } from '@/composables/useGuarantee'
import AmountInput from '@/components/contract/AmountInput.vue'
import { showToast } from '@/plugins/toast'

const router = useRouter()
const route = useRoute()
const guaranteeStore = useGuaranteeStore()
const { typeOptions } = useGuarantee()

const loading = ref(false)
const errors = ref({})
const contract = ref(null)

const contractId = computed(() => route.params.contractId || route.query.contract_id)

const form = ref({
  contract_id: contractId.value,
  type: '',
  instrument_type: '',
  instrument_number: '',
  amount: null,
  bank_name: '',
  bank_branch: '',
  issue_date: null,
  expiry_date: null,
  description: '',
})

onMounted(async () => {
  if (contractId.value) {
    try {
      const { data } = await api.get(`/guarantee-desk/${contractId.value}`)
      contract.value = data.data
      form.value.type = contract.value.guarantee_type || ''
      form.value.instrument_type = contract.value.guarantee_instrument_type || ''
    } catch {
      showToast({ severity: 'error', summary: 'دسترسی', detail: 'این قرارداد در میز دریافت ضمانت در دسترس نیست.', life: 4000 })
    }
  }
})

const validate = () => {
  errors.value = {}

  if (!form.value.type) errors.value.type = 'نوع الزامی است'
  if (!form.value.instrument_type) errors.value.instrument_type = 'نوع سند ضمانت را انتخاب کنید'
  if (!form.value.instrument_number?.trim()) errors.value.instrument_number = 'شماره چک یا سفته الزامی است'
  if (!form.value.amount) errors.value.amount = 'مبلغ الزامی است'
  if (!form.value.bank_name) errors.value.bank_name = 'نام بانک الزامی است'
  if (!form.value.issue_date) errors.value.issue_date = 'تاریخ صدور الزامی است'
  if (!form.value.expiry_date) errors.value.expiry_date = 'تاریخ انقضا الزامی است'

  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validate()) return

  loading.value = true
  try {
    await guaranteeStore.createGuarantee(form.value)
    showToast({ severity: 'success', summary: 'ضمانت‌نامه ثبت شد', life: 3000 })
    router.push('/guarantee-desk')
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
        <i class="pi pi-shield ml-2 text-primary" />
        ثبت ضمانت‌نامه جدید
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-2">
              نوع ضمانت‌نامه <span class="text-red-500">*</span>
            </label>
            <Select
                v-model="form.type"
                :options="typeOptions"
                option-label="label"
                option-value="value"
                class="w-full"
                disabled
                :invalid="!!errors.type"
            />
            <small v-if="errors.type" class="text-red-500">{{ errors.type }}</small>
          </div>

          <AmountInput
              v-model="form.amount"
              label="مبلغ ضمانت‌نامه"
              :required="true"
              :invalid="!!errors.amount"
          />
          <small v-if="errors.amount" class="text-red-500">{{ errors.amount }}</small>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <div>
            <label class="block text-sm font-medium mb-2">نوع سند ضمانت</label>
            <Select v-model="form.instrument_type" :options="[
              { label: 'چک ضمانت', value: 'check' },
              { label: 'سفته', value: 'promissory_note' },
              { label: 'ضمانت‌نامه بانکی', value: 'bank_guarantee' },
            ]" option-label="label" option-value="value" class="w-full" disabled />
            <small class="text-surface-600">نوع ضمانت و سند را واحد حقوقی و قراردادها تعیین می‌کنند.</small>
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">شماره چک یا سفته</label>
            <InputText v-model="form.instrument_number" class="w-full" :invalid="!!errors.instrument_number" />
            <small v-if="errors.instrument_number" class="text-red-500">{{ errors.instrument_number }}</small>
          </div>
        </div>
        </template>
      </Card>

      <!-- اطلاعات بانکی -->
      <Card>
        <template #title>اطلاعات بانکی</template>
        <template #content>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-2">
              نام بانک <span class="text-red-500">*</span>
            </label>
            <InputText
                v-model="form.bank_name"
                class="w-full"
                :invalid="!!errors.bank_name"
            />
            <small v-if="errors.bank_name" class="text-red-500">{{ errors.bank_name }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">شعبه</label>
            <InputText v-model="form.bank_branch" class="w-full" />
          </div>
        </div>
        </template>
      </Card>

      <!-- تاریخ‌ها -->
      <Card>
        <template #title>تاریخ‌ها</template>
        <template #content>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-2">
              تاریخ صدور <span class="text-red-500">*</span>
            </label>
            <DatePicker
                v-model="form.issue_date"
                format="YYYY-MM-DD"
                display-format="jYYYY/jMM/jDD"
                class="w-full"
                :invalid="!!errors.issue_date"
            />
            <small v-if="errors.issue_date" class="text-red-500">{{ errors.issue_date }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">
              تاریخ انقضا <span class="text-red-500">*</span>
            </label>
            <DatePicker
                v-model="form.expiry_date"
                format="YYYY-MM-DD"
                display-format="jYYYY/jMM/jDD"
                class="w-full"
                :invalid="!!errors.expiry_date"
            />
            <small v-if="errors.expiry_date" class="text-red-500">{{ errors.expiry_date }}</small>
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
        <Button type="submit" label="ثبت ضمانت‌نامه" icon="pi pi-check" :loading="loading" />
      </div>
    </form>
  </div>
</template>
