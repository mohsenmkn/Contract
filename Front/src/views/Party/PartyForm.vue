<!-- src/views/Party/PartyForm.vue -->
<script setup>
import { ref, computed, onMounted, watch, nextTick, defineComponent, h } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { usePartyStore } from '@/stores/partyStore.js'
import { useApiErrorHandler } from '@/composables/useApiErrorHandler'
import { showToast } from '@/plugins/toast'

const router = useRouter()
const route = useRoute()
const partyStore = usePartyStore()
const { handleApiError } = useApiErrorHandler()

const isEdit = computed(() => !!route.params.id)
const loadingData = ref(false)
const submitting = ref(false)
const errors = ref({})

const FIELDS = [
  'name', 'type', 'role', 'national_id', 'registration_number', 'economic_code',
  'phone', 'email', 'website', 'address', 'bank_name', 'bank_account', 'iban', 'is_active'
]

const emptyForm = () => ({
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
  is_active: true
})

const form = ref(emptyForm())

const typeOptions = [
  { label: 'حقوقی', value: 'legal' },
  { label: 'حقیقی', value: 'real' }
]

const roleOptions = [
  { label: 'پیمانکار', value: 'contractor' },
  { label: 'کارفرما', value: 'employer' },
  { label: 'هر دو', value: 'both' }
]

/* ---------- نمایش خطای زیر فیلد ---------- */
const FieldError = defineComponent({
  props: { id: String, message: String },
  setup(props) {
    return () =>
        props.message
            ? h('small', { id: props.id, role: 'alert', class: 'field-error' }, [
              h('i', { class: 'pi pi-exclamation-circle' }),
              props.message
            ])
            : null
  }
})

const errText = (key) => {
  const value = errors.value[key]
  return Array.isArray(value) ? value[0] : value
}

// خطاهایی که به هیچ فیلدی در فرم مربوط نیستند، تا هیچ پیامی گم نشود
const generalErrors = computed(() =>
    Object.entries(errors.value)
        .filter(([key]) => !FIELDS.includes(key))
        .map(([, value]) => (Array.isArray(value) ? value[0] : value))
        .filter(Boolean)
)

const errorCount = computed(() => Object.keys(errors.value).length)

// با تغییر هر فیلد، خطای همان فیلد پاک می‌شود
watch(
    () => ({ ...form.value }),
    (next, prev) => {
      if (!errorCount.value) return
      const remaining = { ...errors.value }
      for (const key in next) if (next[key] !== prev[key]) delete remaining[key]
      errors.value = remaining
    }
)

const focusFirstError = async () => {
  await nextTick()
  const el = document.querySelector('[aria-invalid="true"]')
  if (!el) return
  const target = el.matches('input,textarea,select,button') ? el : el.querySelector('input,textarea,[tabindex]')
  el.scrollIntoView({ behavior: 'smooth', block: 'center' })
  target?.focus({ preventScroll: true })
}

/* ---------- نرمال‌سازی ---------- */
const toLatinDigits = (value) =>
    String(value ?? '')
        .replace(/[۰-۹]/g, (d) => String(d.charCodeAt(0) - 0x06f0))
        .replace(/[٠-٩]/g, (d) => String(d.charCodeAt(0) - 0x0660))

const text = (value) => String(value ?? '').trim()

const buildPayload = () => {
  const f = form.value
  const website = text(f.website)
  return {
    name: text(f.name),
    type: f.type,
    role: f.role,
    national_id: toLatinDigits(text(f.national_id)),
    registration_number: toLatinDigits(text(f.registration_number)),
    economic_code: toLatinDigits(text(f.economic_code)),
    address: text(f.address),
    phone: toLatinDigits(text(f.phone)),
    email: text(f.email).toLowerCase(),
    website: website && !/^https?:\/\//i.test(website) ? `https://${website}` : website,
    bank_name: text(f.bank_name),
    bank_account: toLatinDigits(text(f.bank_account)),
    iban: toLatinDigits(text(f.iban)).replace(/\s+/g, '').toUpperCase(),
    is_active: !!f.is_active
  }
}

/* ---------- اعتبارسنجی سمت کلاینت ---------- */
const collectErrors = (p) => {
  const e = {}

  if (!p.name) e.name = 'نام شرکت / شخص الزامی است'
  if (!p.type) e.type = 'نوع شخص الزامی است'
  if (!p.role) e.role = 'نقش الزامی است'

  if (p.national_id && !/^\d{10,11}$/.test(p.national_id)) {
    e.national_id = 'شناسه ملی / کد ملی باید ۱۰ یا ۱۱ رقم عددی باشد'
  }
  if (p.bank_account && !/^[\d./-]+$/.test(p.bank_account)) {
    e.bank_account = 'شماره حساب فقط می‌تواند شامل عدد باشد'
  }
  if (p.iban && !/^IR\d{24}$/.test(p.iban)) {
    e.iban = 'شماره شبا باید با IR شروع شود و ۲۶ کاراکتر باشد'
  }
  if (p.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(p.email)) {
    e.email = 'ایمیل نامعتبر است'
  }
  if (p.website) {
    try {
      if (!new URL(p.website).hostname.includes('.')) throw new Error()
    } catch {
      e.website = 'آدرس وب‌سایت معتبر نیست'
    }
  }

  return e
}

/* ---------- خطاهای سرور ---------- */
const applyServerErrors = (validationErrors = {}) => {
  errors.value = validationErrors
  focusFirstError()
}

const handleRequestError = (err) => {
  // حالت استاندارد: خطای axios (422 زیر فیلدها، بقیه کدها با toast استاندارد)
  if (err?.response) {
    handleApiError(err, { setErrors: applyServerErrors })
    return
  }

  // خطاهایی که response ندارند (مثلاً store خطا را دوباره Error ساخته یا شبکه قطع است)
  const details = err?.errors ?? err?.data?.errors
  if (details && typeof details === 'object') applyServerErrors(details)

  showToast({
    severity: 'error',
    summary: 'خطا',
    detail: err?.message || 'ارتباط با سرور برقرار نشد. اتصال اینترنت را بررسی کنید.',
    life: 5000
  })
}

/* ---------- بارگذاری برای ویرایش ---------- */
const pickFormFields = (party) => {
  const base = emptyForm()
  const picked = {}
  for (const key of FIELDS) picked[key] = party?.[key] ?? base[key]
  picked.is_active = !!picked.is_active
  return picked
}

onMounted(async () => {
  if (!isEdit.value) return
  loadingData.value = true
  try {
    const party = await partyStore.fetchPartyById(route.params.id)
    form.value = pickFormFields(party)
  } catch (err) {
    handleRequestError(err)
    router.push('/parties')
  } finally {
    loadingData.value = false
  }
})

/* ---------- ارسال ---------- */
const handleSubmit = async () => {
  if (submitting.value) return

  const payload = buildPayload()
  const clientErrors = collectErrors(payload)
  if (Object.keys(clientErrors).length) {
    errors.value = clientErrors
    focusFirstError()
    return
  }

  errors.value = {}
  submitting.value = true
  try {
    if (isEdit.value) {
      await partyStore.updateParty(route.params.id, payload)
      showToast({ severity: 'success', summary: 'بروزرسانی شد', life: 3000 })
    } else {
      await partyStore.createParty(payload)
      showToast({ severity: 'success', summary: 'ایجاد شد', life: 3000 })
    }
    router.push('/parties')
  } catch (err) {
    handleRequestError(err)
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-4xl p-6" dir="rtl">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0">
        <i class="pi pi-user-plus ml-2 text-primary" />
        {{ isEdit ? 'ویرایش طرف قرارداد' : 'ایجاد طرف قرارداد جدید' }}
      </h1>
      <p class="mt-2 text-sm text-surface-600 dark:text-surface-400">
        {{ isEdit ? 'اطلاعات طرف قرارداد را ویرایش کنید' : 'اطلاعات طرف قرارداد جدید را وارد کنید' }}
      </p>
    </div>

    <div v-if="loadingData" class="py-16 text-center">
      <ProgressSpinner style="width: 40px; height: 40px" strokeWidth="4" />
    </div>

    <form v-else class="space-y-6" novalidate @submit.prevent="handleSubmit">
      <!-- خلاصه خطاها -->
      <Message v-if="errorCount" severity="error" :closable="false" role="alert">
        <div class="space-y-1 text-sm">
          <div class="font-semibold">فرم دارای خطا است؛ موارد مشخص‌شده را اصلاح کنید.</div>
          <ul v-if="generalErrors.length" class="list-inside list-disc">
            <li v-for="msg in generalErrors" :key="msg">{{ msg }}</li>
          </ul>
        </div>
      </Message>

      <!-- اطلاعات پایه -->
      <Card>
        <template #title>
          <span class="flex items-center gap-2"><i class="pi pi-info-circle text-primary" />اطلاعات پایه</span>
        </template>
        <template #content>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
              <label for="party-name" class="field-label">نام شرکت / شخص <span class="text-red-500" aria-hidden="true">*</span></label>
              <InputText
                  id="party-name"
                  v-model="form.name"
                  :invalid="!!errText('name')"
                  :aria-invalid="!!errText('name')"
                  aria-describedby="err-name"
                  class="w-full"
                  placeholder="نام کامل"
              />
              <FieldError id="err-name" :message="errText('name')" />
            </div>

            <div>
              <label for="party-type" class="field-label">نوع شخص <span class="text-red-500" aria-hidden="true">*</span></label>
              <Select
                  v-model="form.type"
                  inputId="party-type"
                  :options="typeOptions"
                  option-label="label"
                  option-value="value"
                  :invalid="!!errText('type')"
                  :aria-invalid="!!errText('type')"
                  class="w-full"
              />
              <FieldError id="err-type" :message="errText('type')" />
            </div>

            <div>
              <label for="party-role" class="field-label">نقش <span class="text-red-500" aria-hidden="true">*</span></label>
              <Select
                  v-model="form.role"
                  inputId="party-role"
                  :options="roleOptions"
                  option-label="label"
                  option-value="value"
                  :invalid="!!errText('role')"
                  :aria-invalid="!!errText('role')"
                  class="w-full"
              />
              <FieldError id="err-role" :message="errText('role')" />
            </div>

            <div>
              <label for="party-national-id" class="field-label">شناسه ملی / کد ملی</label>
              <InputText
                  id="party-national-id"
                  v-model="form.national_id"
                  :invalid="!!errText('national_id')"
                  :aria-invalid="!!errText('national_id')"
                  aria-describedby="err-national-id"
                  class="w-full font-mono"
                  dir="ltr"
                  inputmode="numeric"
                  placeholder="۱۰ یا ۱۱ رقم"
                  maxlength="11"
              />
              <FieldError id="err-national-id" :message="errText('national_id')" />
            </div>

            <div>
              <label for="party-registration" class="field-label">شماره ثبت</label>
              <InputText
                  id="party-registration"
                  v-model="form.registration_number"
                  :invalid="!!errText('registration_number')"
                  :aria-invalid="!!errText('registration_number')"
                  class="w-full"
              />
              <FieldError id="err-registration" :message="errText('registration_number')" />
            </div>

            <div>
              <label for="party-economic" class="field-label">کد اقتصادی</label>
              <InputText
                  id="party-economic"
                  v-model="form.economic_code"
                  :invalid="!!errText('economic_code')"
                  :aria-invalid="!!errText('economic_code')"
                  class="w-full"
                  dir="ltr"
                  inputmode="numeric"
              />
              <FieldError id="err-economic" :message="errText('economic_code')" />
            </div>
          </div>
        </template>
      </Card>

      <!-- اطلاعات تماس -->
      <Card>
        <template #title>
          <span class="flex items-center gap-2"><i class="pi pi-phone text-primary" />اطلاعات تماس</span>
        </template>
        <template #content>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label for="party-phone" class="field-label">تلفن</label>
              <InputText
                  id="party-phone"
                  v-model="form.phone"
                  :invalid="!!errText('phone')"
                  :aria-invalid="!!errText('phone')"
                  class="w-full"
                  dir="ltr"
                  inputmode="tel"
                  placeholder="021-12345678"
              />
              <FieldError id="err-phone" :message="errText('phone')" />
            </div>

            <div>
              <label for="party-email" class="field-label">ایمیل</label>
              <InputText
                  id="party-email"
                  v-model="form.email"
                  type="email"
                  :invalid="!!errText('email')"
                  :aria-invalid="!!errText('email')"
                  class="w-full"
                  dir="ltr"
                  placeholder="example@company.com"
              />
              <FieldError id="err-email" :message="errText('email')" />
            </div>

            <div>
              <label for="party-website" class="field-label">وب‌سایت</label>
              <InputText
                  id="party-website"
                  v-model="form.website"
                  :invalid="!!errText('website')"
                  :aria-invalid="!!errText('website')"
                  class="w-full"
                  dir="ltr"
                  placeholder="https://example.com"
              />
              <FieldError id="err-website" :message="errText('website')" />
            </div>

            <div class="md:col-span-2">
              <label for="party-address" class="field-label">آدرس</label>
              <Textarea
                  id="party-address"
                  v-model="form.address"
                  rows="3"
                  :invalid="!!errText('address')"
                  :aria-invalid="!!errText('address')"
                  class="w-full"
              />
              <FieldError id="err-address" :message="errText('address')" />
            </div>
          </div>
        </template>
      </Card>

      <!-- اطلاعات بانکی -->
      <Card>
        <template #title>
          <span class="flex items-center gap-2"><i class="pi pi-building text-primary" />اطلاعات بانکی</span>
        </template>
        <template #content>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label for="party-bank" class="field-label">نام بانک</label>
              <InputText
                  id="party-bank"
                  v-model="form.bank_name"
                  :invalid="!!errText('bank_name')"
                  :aria-invalid="!!errText('bank_name')"
                  class="w-full"
              />
              <FieldError id="err-bank" :message="errText('bank_name')" />
            </div>

            <div>
              <label for="party-account" class="field-label">شماره حساب</label>
              <InputText
                  id="party-account"
                  v-model="form.bank_account"
                  :invalid="!!errText('bank_account')"
                  :aria-invalid="!!errText('bank_account')"
                  class="w-full font-mono"
                  dir="ltr"
                  inputmode="numeric"
              />
              <FieldError id="err-account" :message="errText('bank_account')" />
            </div>

            <div class="md:col-span-2">
              <label for="party-iban" class="field-label">شماره شبا (IBAN)</label>
              <InputText
                  id="party-iban"
                  v-model="form.iban"
                  :invalid="!!errText('iban')"
                  :aria-invalid="!!errText('iban')"
                  class="w-full font-mono"
                  dir="ltr"
                  placeholder="IR123456789012345678901234"
                  maxlength="30"
              />
              <FieldError id="err-iban" :message="errText('iban')" />
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
          <FieldError id="err-active" :message="errText('is_active')" />
        </template>
      </Card>

      <!-- دکمه‌ها -->
      <div class="flex items-center justify-end gap-3">
        <Button type="button" label="انصراف" severity="secondary" outlined :disabled="submitting" @click="router.push('/parties')" />
        <Button type="submit" :label="isEdit ? 'بروزرسانی' : 'ایجاد'" icon="pi pi-check" :loading="submitting" />
      </div>
    </form>
  </div>
</template>

<style scoped>
.field-label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
}
:deep(.field-error) {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  margin-top: 0.35rem;
  font-size: 0.78rem;
  color: #dc2626;
}
</style>