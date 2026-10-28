<template>
  <div class="p-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0">
        <i class="pi pi-user-plus ml-2 text-primary" />
        {{ isEdit ? 'ویرایش طرف قرارداد' : 'ایجاد طرف قرارداد جدید' }}
      </h1>
      <p class="text-sm text-surface-600 dark:text-surface-400 mt-2">
        {{ isEdit ? 'اطلاعات طرف قرارداد را ویرایش کنید' : 'اطلاعات طرف قرارداد جدید را وارد کنید' }}
      </p>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">

      <!-- اطلاعات پایه -->
      <Card>
        <template #title>
          <span class="flex items-center gap-2">
            <i class="pi pi-info-circle text-primary" />
            اطلاعات پایه
          </span>
        </template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium mb-2">
                نام شرکت / شخص <span class="text-red-500">*</span>
              </label>
              <InputText
                  v-model="form.name"
                  :invalid="!!errors.name"
                  class="w-full"
                  placeholder="نام کامل"
              />
              <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">نوع شخص</label>
              <Select
                  v-model="form.type"
                  :options="typeOptions"
                  option-label="label"
                  option-value="value"
                  class="w-full"
              />
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">
                نقش <span class="text-red-500">*</span>
              </label>
              <Select
                  v-model="form.role"
                  :options="roleOptions"
                  option-label="label"
                  option-value="value"
                  class="w-full"
                  :invalid="!!errors.role"
              />
              <small v-if="errors.role" class="text-red-500">{{ errors.role }}</small>
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">شناسه ملی / کدملی</label>
              <InputText
                  v-model="form.national_id"
                  class="w-full font-mono"
                  placeholder="۱۱ رقم"
                  maxlength="11"
              />
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">شماره ثبت</label>
              <InputText v-model="form.registration_number" class="w-full" />
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">کد اقتصادی</label>
              <InputText v-model="form.economic_code" class="w-full" />
            </div>
          </div>
        </template>
      </Card>

      <!-- اطلاعات تماس -->
      <Card>
        <template #title>
          <span class="flex items-center gap-2">
            <i class="pi pi-phone text-primary" />
            اطلاعات تماس
          </span>
        </template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-2">تلفن</label>
              <InputText v-model="form.phone" class="w-full" placeholder="021-12345678" />
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">ایمیل</label>
              <InputText
                  v-model="form.email"
                  type="email"
                  class="w-full"
                  :invalid="!!errors.email"
                  placeholder="example@company.com"
              />
              <small v-if="errors.email" class="text-red-500">{{ errors.email }}</small>
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">وب‌سایت</label>
              <InputText v-model="form.website" class="w-full" placeholder="https://example.com" />
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium mb-2">آدرس</label>
              <Textarea v-model="form.address" rows="3" class="w-full" />
            </div>
          </div>
        </template>
      </Card>

      <!-- اطلاعات بانکی -->
      <Card>
        <template #title>
          <span class="flex items-center gap-2">
            <i class="pi pi-building text-primary" />
            اطلاعات بانکی
          </span>
        </template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-2">نام بانک</label>
              <InputText v-model="form.bank_name" class="w-full" />
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">شماره حساب</label>
              <InputText v-model="form.bank_account" class="w-full font-mono" />
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium mb-2">شماره شبا (IBAN)</label>
              <InputText
                  v-model="form.iban"
                  class="w-full font-mono"
                  placeholder="IR123456789012345678901234"
                  maxlength="26"
              />
            </div>
          </div>
        </template>
      </Card>

      <!-- وضعیت -->
      <Card>
        <template #content>
          <div class="flex items-center gap-3">
            <Checkbox v-model="form.is_active" :binary="true" input-id="is_active" />
            <label for="is_active" class="text-sm font-medium">فعال</label>
          </div>
        </template>
      </Card>

      <!-- دکمه‌ها -->
      <div class="flex items-center justify-end gap-3">
        <Button
            label="انصراف"
            severity="secondary"
            outlined
            @click="router.push('/parties')"
        />
        <Button
            type="submit"
            :label="isEdit ? 'بروزرسانی' : 'ایجاد'"
            icon="pi pi-check"
            :loading="loading"
        />
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { usePartyStore } from '@/stores/partyStore.js'
import { showToast } from '@/plugins/toast'

const router = useRouter()
const route = useRoute()
const partyStore = usePartyStore()

const isEdit = computed(() => !!route.params.id)
const loading = ref(false)
const errors = ref({})

const form = ref({
  name: '',
  national_id: '',
  registration_number: '',
  economic_code: '',
  type: 'legal',
  role: 'contractor',
  address: '',
  phone: '',
  email: '',
  website: '',
  bank_name: '',
  bank_account: '',
  iban: '',
  is_active: true,
})

const typeOptions = [
  { label: 'حقوقی', value: 'legal' },
  { label: 'حقیقی', value: 'real' },
]

const roleOptions = [
  { label: 'پیمانکار', value: 'contractor' },
  { label: 'کارفرما', value: 'employer' },
  { label: 'هر دو', value: 'both' },
]

onMounted(async () => {
  if (isEdit.value) {
    loading.value = true
    try {
      const party = await partyStore.fetchPartyById(route.params.id)
      form.value = { ...party }
    } catch (err) {
      showToast({ severity: 'error', summary: 'خطا', detail: err.message, life: 5000 })
      router.push('/parties')
    } finally {
      loading.value = false
    }
  }
})

const validate = () => {
  errors.value = {}
  if (!form.value.name) errors.value.name = 'نام الزامی است'
  if (!form.value.role) errors.value.role = 'نوع طرف الزامی است'
  if (form.value.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
    errors.value.email = 'ایمیل نامعتبر است'
  }
  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validate()) return
  loading.value = true
  try {
    if (isEdit.value) {
      await partyStore.updateParty(route.params.id, form.value)
      showToast({ severity: 'success', summary: 'بروزرسانی شد', life: 3000 })
    } else {
      await partyStore.createParty(form.value)
      showToast({ severity: 'success', summary: 'ایجاد شد', life: 3000 })
    }
    router.push('/parties')
  } catch (err) {
    showToast({ severity: 'error', summary: 'خطا', detail: err.message, life: 5000 })
  } finally {
    loading.value = false
  }
}
</script>