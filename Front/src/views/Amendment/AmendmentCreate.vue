<!-- src/views/Amendment/AmendmentCreate.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAmendmentStore } from '@/stores/amendmentStore'
import { useContractStore } from '@/stores/contractStore'
import { useAmendment } from '@/composables/useAmendment'
import AmountInput from '@/components/contract/AmountInput.vue'
import AmountDisplay from '@/components/contract/AmountDisplay.vue'
import DateDisplay from '@/components/contract/DateDisplay.vue'
import { showToast } from '@/plugins/toast'

const router = useRouter()
const route = useRoute()
const amendmentStore = useAmendmentStore()
const contractStore = useContractStore()
const { typeOptions } = useAmendment()

const loading = ref(false)
const errors = ref({})

const contractId = computed(() => route.params.contractId || route.query.contract_id)
const contract = computed(() => contractStore.currentContract)

const form = ref({
  contract_id: contractId.value,
  type: 'amount',
  new_amount: null,
  new_end_date: null,
  description: '',
})

onMounted(async () => {
  if (contractId.value) {
    await contractStore.fetchContractById(contractId.value)
  }
})

const validate = () => {
  errors.value = {}

  if (!form.value.type) errors.value.type = 'نوع الحاقیه الزامی است'
  if (!form.value.description) errors.value.description = 'توضیحات الزامی است'

  if (form.value.type === 'amount' && !form.value.new_amount) {
    errors.value.new_amount = 'مبلغ جدید الزامی است'
  }

  if (form.value.type === 'extension' && !form.value.new_end_date) {
    errors.value.new_end_date = 'تاریخ جدید الزامی است'
  }

  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validate()) return

  loading.value = true
  try {
    await amendmentStore.createAmendment(form.value)
    showToast({ severity: 'success', summary: 'الحاقیه ایجاد شد', life: 3000 })
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
        <i class="pi pi-file-edit ml-2 text-primary" />
        ایجاد الحاقیه جدید
      </h1>
      <p v-if="contract" class="text-surface-600 dark:text-surface-400 mt-2">
        قرارداد: {{ contract.contract_number }} - {{ contract.subject }}
      </p>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- اطلاعات قرارداد -->
      <Card v-if="contract">
        <template #title>اطلاعات فعلی قرارداد</template>
        <template #content>
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span class="text-surface-500">مبلغ فعلی:</span>
            <AmountDisplay :amount="contract.current_amount" class="mr-2" />
          </div>
          <div>
            <span class="text-surface-500">تاریخ پایان فعلی:</span>
            <DateDisplay :date="contract.end_date" class="mr-2" />
          </div>
        </div>
        </template>
      </Card>

      <!-- نوع الحاقیه -->
      <Card>
        <template #title>نوع الحاقیه</template>
        <template #content>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-2">
              نوع الحاقیه <span class="text-red-500">*</span>
            </label>
            <Select
                v-model="form.type"
                :options="typeOptions"
                option-label="label"
                option-value="value"
                class="w-full"
                :invalid="!!errors.type"
            />
            <small v-if="errors.type" class="text-red-500">{{ errors.type }}</small>
          </div>
        </div>
        </template>
      </Card>

      <!-- تغییر مبلغ -->
      <Card v-if="form.type === 'amount'">
        <template #title>تغییر مبلغ</template>
        <template #content>
        <div class="space-y-4">
          <AmountInput
              v-model="form.new_amount"
              label="مبلغ جدید قرارداد"
              :required="true"
              :invalid="!!errors.new_amount"
          />
          <small v-if="errors.new_amount" class="text-red-500">{{ errors.new_amount }}</small>

          <div v-if="form.new_amount && contract" class="bg-surface-50 dark:bg-surface-800/50 p-4 rounded-lg">
            <div class="flex justify-between text-sm">
              <span>تغییر مبلغ:</span>
              <AmountDisplay
                  :amount="form.new_amount - contract.current_amount"
                  :colorize="true"
              />
            </div>
          </div>
        </div>
        </template>
      </Card>

      <!-- تمدید مدت -->
      <Card v-if="form.type === 'extension'">
        <template #title>تمدید مدت قرارداد</template>
        <template #content>
        <div class="space-y-4">
          <DatePicker
              v-model="form.new_end_date"
              label="تاریخ پایان جدید"
              format="YYYY-MM-DD"
              display-format="jYYYY/jMM/jDD"
              :required="true"
              :invalid="!!errors.new_end_date"
          />
          <small v-if="errors.new_end_date" class="text-red-500">{{ errors.new_end_date }}</small>
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
            placeholder="دلیل ایجاد الحاقیه را شرح دهید..."
            :invalid="!!errors.description"
        />
        <small v-if="errors.description" class="text-red-500">{{ errors.description }}</small>
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
            label="ایجاد الحاقیه"
            icon="pi pi-check"
            :loading="loading"
        />
      </div>
    </form>
  </div>
</template>