<!-- src/views/Contract/ContractRequestForm.vue -->
<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRouter, useRoute, onBeforeRouteLeave } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useApiErrorHandler } from '@/composables/useApiErrorHandler'
import api from '@/api/axios.js'
import {
  FileSignature, Info, FileText, Handshake, Building2, Calendar,
  FileEdit, AlignLeft, List, CheckCircle, Search
} from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const { handleApiError } = useApiErrorHandler()

/* ---------- ثابت‌ها ---------- */
const steps = [
  { title: 'اطلاعات پایه', icon: Info },
  { title: 'متن قرارداد', icon: FileEdit },
  { title: 'آیتم‌ها', icon: List },
  { title: 'تایید و ارسال', icon: CheckCircle }
]

// لیست «نوع قرارداد»
const TYPE_OPTIONS = [
  { label: 'پیمانکار', value: 'downstream' },
  { label: 'کارفرما', value: 'upstream' }
]

// نقش طرف قرارداد بر اساس نوع قرارداد (برای فیلتر جستجو)
// اگر در بک‌اند نام نقش «کارفرما» چیز دیگری است، فقط همین‌جا عوض کنید.
const PARTY_ROLE_BY_TYPE = { downstream: 'contractor', upstream: 'employer' }
const PARTY_LABEL_BY_TYPE = { downstream: 'پیمانکار', upstream: 'کارفرما' }

const editorModules = {
  toolbar: [
    [{ header: [1, 2, 3, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    [{ indent: '-1' }, { indent: '+1' }],
    [{ color: [] }, { background: [] }],
    [{ align: [] }],
    ['link'],
    ['clean']
  ]
}

/* ---------- وضعیت ---------- */
const pageTop = ref(null)
const currentStep = ref(0)
const maxStep = ref(0)
const submitting = ref(false)
const submitted = ref(false)
const isEdit = ref(false)
const loadingContract = ref(false)
const myUnit = ref(null)
const errors = ref({})
const snapshot = ref('')

let itemUid = 0
const newItem = (data = {}) => ({
  _key: ++itemUid,
  item_code: '',
  description: '',
  unit: '',
  quantity: 0,
  unit_price: 0,
  ...data
})

const form = ref({
  id: null,
  subject: '',
  type: null,
  party_id: null,
  start_date: null,
  end_date: null,
  description: '',
  content: '',
  items: []
})

/* ---------- جستجوی طرف قرارداد ---------- */
const partyBox = ref(null)
const partyInput = ref(null)
const partySearch = ref('')
const partySuggestions = ref([])
const partyOpen = ref(false)
const partyLoading = ref(false)
const partySearched = ref(false)
const partyActive = ref(-1)
const selectedParty = ref(null)
let partyTimer = null
let partyRequestId = 0

/* ---------- محاسبات ---------- */
const stripHtml = (html) => {
  if (!html) return ''
  const doc = new DOMParser().parseFromString(html, 'text/html')
  return doc.body.textContent || ''
}

const plainContent = computed(() => stripHtml(form.value.content))
const pagesCount = computed(() => (plainContent.value.trim() ? Math.max(1, Math.ceil(plainContent.value.length / 1500)) : 0))

const calculateItemTotal = (item) => (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)
const totalAmount = computed(() => form.value.items.reduce((sum, item) => sum + calculateItemTotal(item), 0))

const formatNumber = (num) => Number(num || 0).toLocaleString('fa-IR')

const toIsoDate = (value) => {
  if (!value) return null
  if (value instanceof Date) {
    const m = String(value.getMonth() + 1).padStart(2, '0')
    const d = String(value.getDate()).padStart(2, '0')
    return `${value.getFullYear()}-${m}-${d}`
  }
  return String(value).slice(0, 10)
}

const formatDate = (value) => {
  const iso = toIsoDate(value)
  if (!iso) return '—'
  const date = new Date(`${iso}T00:00:00`)
  return Number.isNaN(date.getTime()) ? iso : date.toLocaleDateString('fa-IR')
}

const durationDays = computed(() => {
  const s = toIsoDate(form.value.start_date)
  const e = toIsoDate(form.value.end_date)
  if (!s || !e) return null
  const diff = Math.round((new Date(`${e}T00:00:00`) - new Date(`${s}T00:00:00`)) / 86400000)
  return diff >= 0 ? diff : null
})

const typeLabel = computed(() => TYPE_OPTIONS.find((t) => t.value === form.value.type)?.label || '—')
const partyLabel = computed(() => {
  const role = PARTY_LABEL_BY_TYPE[form.value.type]
  return role ? `طرف قرارداد (${role})` : 'طرف قرارداد'
})

const progressText = computed(() => `مرحله ${(currentStep.value + 1).toLocaleString('fa-IR')} از ${steps.length.toLocaleString('fa-IR')}`)

const warnings = computed(() => {
  const list = []
  if (!plainContent.value.trim()) list.push('متن قرارداد ثبت نشده است؛ ثبت آن بررسی حقوقی را سریع‌تر می‌کند.')
  if (!form.value.items.length) list.push('هیچ آیتمی ثبت نشده است و مبلغ قرارداد صفر خواهد بود.')
  return list
})

/* ---------- تغییرات ذخیره‌نشده ---------- */
const serialize = () =>
    JSON.stringify({
      ...form.value,
      start_date: toIsoDate(form.value.start_date),
      end_date: toIsoDate(form.value.end_date),
      content: plainContent.value.trim(),
      items: form.value.items.map(({ _key, ...item }) => item)
    })

const isDirty = computed(() => snapshot.value !== '' && serialize() !== snapshot.value)

onBeforeRouteLeave(() => {
  if (submitted.value || !isDirty.value) return true
  return window.confirm('تغییرات ذخیره‌نشده از بین می‌رود. از این صفحه خارج می‌شوید؟')
})

const onBeforeUnload = (e) => {
  if (isDirty.value && !submitted.value) {
    e.preventDefault()
    e.returnValue = ''
  }
}

/* ---------- بارگذاری ---------- */
const loadMyUnit = async () => {
  try {
    const response = await api.get('/organizational-units/my-unit')
    myUnit.value = response.data.data || response.data
  } catch (error) {
    console.error('خطا در دریافت واحد:', error)
  }
}

const loadContractForEdit = async () => {
  const id = route.params.id
  if (!id) return

  isEdit.value = true
  loadingContract.value = true
  try {
    const response = await api.get(`/contracts/${id}`)
    const contract = response.data?.data || response.data

    if (contract.status !== 'draft') {
      toast.add({ severity: 'warn', summary: 'ویرایش امکان‌پذیر نیست', detail: 'فقط پیش‌نویس قرارداد قابل ویرایش است.', life: 4000 })
      submitted.value = true
      await router.replace('/contract-requests')
      return
    }

    form.value = {
      ...form.value,
      id: contract.id,
      subject: contract.subject || '',
      type: contract.type || null,
      party_id: contract.party_id || contract.party?.id || null,
      start_date: toIsoDate(contract.start_date),
      end_date: toIsoDate(contract.end_date),
      description: contract.description || '',
      content: contract.content || '',
      items: (contract.items || []).map((item, index) =>
          newItem({
            item_code: item.item_code || `ITEM-${index + 1}`,
            description: item.description || '',
            unit: item.unit || '',
            quantity: Number(item.quantity) || 0,
            unit_price: Number(item.unit_price) || 0
          })
      )
    }
    selectedParty.value = contract.party || null
    maxStep.value = steps.length - 1
  } catch (error) {
    submitted.value = true
    handleApiError(error)
    await router.replace('/contract-requests')
  } finally {
    loadingContract.value = false
  }
}

/* ---------- نوع قرارداد و طرف قرارداد ---------- */
const onTypeChange = () => {
  errors.value = { ...errors.value, type: undefined }
  if (selectedParty.value) {
    clearParty(false)
    toast.add({
      severity: 'info',
      summary: 'طرف قرارداد پاک شد',
      detail: 'با تغییر نوع قرارداد، طرف قرارداد را دوباره انتخاب کنید.',
      life: 3500
    })
  }
  partySuggestions.value = []
  partySearch.value = ''
}

const searchParties = () => {
  clearTimeout(partyTimer)
  const term = partySearch.value.trim()

  if (term.length < 2) {
    partySuggestions.value = []
    partySearched.value = false
    partyOpen.value = false
    partyLoading.value = false
    return
  }

  partyLoading.value = true
  partyTimer = setTimeout(async () => {
    const requestId = ++partyRequestId
    try {
      const response = await api.get('/parties', {
        params: { search: term, role: PARTY_ROLE_BY_TYPE[form.value.type], per_page: 10 }
      })
      if (requestId !== partyRequestId) return
      partySuggestions.value = response.data.data || []
      partySearched.value = true
      partyActive.value = partySuggestions.value.length ? 0 : -1
      partyOpen.value = true
    } catch (error) {
      if (requestId !== partyRequestId) return
      partySuggestions.value = []
      partySearched.value = true
      partyOpen.value = true
    } finally {
      if (requestId === partyRequestId) partyLoading.value = false
    }
  }, 300)
}

const selectParty = (party) => {
  form.value.party_id = party.id
  selectedParty.value = party
  errors.value = { ...errors.value, party_id: undefined }
  partySearch.value = ''
  partySuggestions.value = []
  partyOpen.value = false
  partySearched.value = false
}

const clearParty = async (focus = true) => {
  form.value.party_id = null
  selectedParty.value = null
  if (focus) {
    await nextTick()
    partyInput.value?.$el?.focus?.()
  }
}

const onPartyKeydown = (e) => {
  if (e.key === 'ArrowDown' && partySuggestions.value.length) {
    e.preventDefault()
    partyOpen.value = true
    partyActive.value = (partyActive.value + 1) % partySuggestions.value.length
  } else if (e.key === 'ArrowUp' && partySuggestions.value.length) {
    e.preventDefault()
    partyActive.value = (partyActive.value - 1 + partySuggestions.value.length) % partySuggestions.value.length
  } else if (e.key === 'Enter' && partyOpen.value && partySuggestions.value[partyActive.value]) {
    e.preventDefault()
    selectParty(partySuggestions.value[partyActive.value])
  } else if (e.key === 'Escape') {
    partyOpen.value = false
  }
}

const onDocumentPointerDown = (e) => {
  if (partyBox.value && !partyBox.value.contains(e.target)) partyOpen.value = false
}

/* ---------- آیتم‌ها ---------- */
const addItem = async () => {
  form.value.items.push(newItem())
  await nextTick()
  const cards = document.querySelectorAll('[data-item-card]')
  cards[cards.length - 1]?.querySelector('input')?.focus()
}

const removeItem = (index) => {
  form.value.items.splice(index, 1)
  errors.value = {}
}

/* ---------- اعتبارسنجی ---------- */
const stepOfKey = (key) => (key === 'content' ? 1 : String(key).startsWith('items') ? 2 : 0)
const errText = (key) => {
  const value = errors.value[key]
  return Array.isArray(value) ? value[0] : value
}

const collectErrors = () => {
  const e = {}
  const f = form.value

  if (!f.subject?.trim()) e.subject = 'موضوع قرارداد الزامی است'
  if (!f.type) e.type = 'نوع قرارداد را انتخاب کنید'
  if (!f.party_id) e.party_id = 'انتخاب طرف قرارداد الزامی است'
  if (!f.start_date) e.start_date = 'تاریخ شروع الزامی است'
  if (!f.end_date) e.end_date = 'تاریخ پایان الزامی است'
  if (f.start_date && f.end_date && toIsoDate(f.start_date) > toIsoDate(f.end_date)) {
    e.end_date = 'تاریخ پایان باید بعد از تاریخ شروع باشد'
  }

  f.items.forEach((item, i) => {
    if (!item.description?.trim()) e[`items.${i}.description`] = 'شرح فعالیت الزامی است'
    if (!(Number(item.quantity) > 0)) e[`items.${i}.quantity`] = 'مقدار باید بیشتر از صفر باشد'
    if (item.unit_price == null || Number(item.unit_price) < 0) e[`items.${i}.unit_price`] = 'بهای واحد نامعتبر است'
  })

  return e
}

const scrollToError = async () => {
  await nextTick()
  document.querySelector('.field-error')?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

const validateStep = (step) => {
  const mine = Object.fromEntries(Object.entries(collectErrors()).filter(([key]) => stepOfKey(key) === step))
  errors.value = mine
  const valid = Object.keys(mine).length === 0
  if (!valid) scrollToError()
  return valid
}

/* ---------- ناوبری مراحل ---------- */
const scrollToTop = () => pageTop.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })

const goToStep = (index) => {
  if (index > maxStep.value) return
  currentStep.value = index
  errors.value = {}
  scrollToTop()
}

const nextStep = () => {
  if (!validateStep(currentStep.value)) return
  currentStep.value++
  maxStep.value = Math.max(maxStep.value, currentStep.value)
  scrollToTop()
}

const prevStep = () => {
  if (currentStep.value > 0) goToStep(currentStep.value - 1)
}

/* ---------- ارسال ---------- */
const buildPayload = () => ({
  ...form.value,
  start_date: toIsoDate(form.value.start_date),
  end_date: toIsoDate(form.value.end_date),
  items: form.value.items.map(({ _key, ...item }) => ({
    ...item,
    quantity: Number(item.quantity) || 0,
    unit_price: Number(item.unit_price) || 0
  })),
  unit_id: myUnit.value?.id,
  amount: totalAmount.value
})

const jumpToFirstErrorStep = (keys) => {
  if (!keys.length) return
  currentStep.value = Math.min(...keys.map(stepOfKey))
  maxStep.value = Math.max(maxStep.value, currentStep.value)
  scrollToError()
}

const submitToLegal = async () => {
  const all = collectErrors()
  if (Object.keys(all).length) {
    errors.value = all
    jumpToFirstErrorStep(Object.keys(all))
    return
  }

  submitting.value = true
  errors.value = {}

  try {
    if (isEdit.value && form.value.id) {
      await api.put(`/contracts/${form.value.id}`, buildPayload())
      toast.add({ severity: 'success', summary: 'موفق', detail: 'درخواست با موفقیت ویرایش شد', life: 3000 })
    } else {
      await api.post('/contracts', buildPayload())
      toast.add({ severity: 'success', summary: 'موفق', detail: 'درخواست قرارداد ثبت و به واحد حقوقی ارسال شد', life: 4000 })
    }
    submitted.value = true
    router.push('/contract-requests')
  } catch (error) {
    handleApiError(error, {
      setErrors: (validationErrors) => {
        errors.value = validationErrors
        jumpToFirstErrorStep(Object.keys(validationErrors))
      }
    })
  } finally {
    submitting.value = false
  }
}

const cancel = () => router.push('/contract-requests')

onMounted(async () => {
  document.addEventListener('pointerdown', onDocumentPointerDown)
  window.addEventListener('beforeunload', onBeforeUnload)
  await Promise.all([loadMyUnit(), loadContractForEdit()])
  await nextTick()
  snapshot.value = serialize()
})

onBeforeUnmount(() => {
  clearTimeout(partyTimer)
  document.removeEventListener('pointerdown', onDocumentPointerDown)
  window.removeEventListener('beforeunload', onBeforeUnload)
})
</script>

<template>
  <div ref="pageTop" class="contract-form mx-auto max-w-5xl p-4 pb-6 md:p-6" dir="rtl">
    <!-- سربرگ -->
    <header class="surface mb-5 flex flex-wrap items-center gap-4 p-5">
      <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-blue-100">
        <FileSignature class="h-7 w-7 text-blue-600" />
      </div>
      <div class="min-w-0 flex-1">
        <h1 class="text-xl font-bold text-slate-800 md:text-2xl">
          {{ isEdit ? 'ویرایش درخواست قرارداد' : 'درخواست قرارداد جدید' }}
        </h1>
        <p class="mt-1 text-sm text-slate-500">
          {{ isEdit ? 'ویرایش پیش‌نویس درخواست' : 'ثبت درخواست قرارداد جدید برای واحد شما' }}
        </p>
        <p v-if="myUnit" class="unit-chip mt-3">
          <Building2 class="h-4 w-4" />
          <span>واحد درخواست‌کننده: <strong>{{ myUnit.name }}</strong></span>
          <span v-if="myUnit.code" class="text-slate-400">|</span>
          <span v-if="myUnit.code">کد: <strong class="tabular-nums">{{ myUnit.code }}</strong></span>
        </p>
      </div>
      <Button label="بازگشت به لیست" icon="pi pi-arrow-right" severity="secondary" outlined @click="cancel" />
    </header>

    <div v-if="loadingContract" class="surface flex flex-col items-center gap-3 py-16 text-slate-500">
      <ProgressSpinner style="width: 38px; height: 38px" strokeWidth="4" />
      <span class="text-sm">در حال دریافت اطلاعات درخواست...</span>
    </div>

    <template v-else>
      <!-- مراحل -->
      <nav class="surface mb-5 px-3 py-5 md:px-6" aria-label="مراحل ثبت درخواست">
        <ol class="stepper">
          <li
              v-for="(s, idx) in steps"
              :key="s.title"
              class="stepper-item"
              :class="{ 'is-active': currentStep === idx, 'is-done': currentStep > idx }"
          >
            <button
                type="button"
                class="stepper-btn"
                :disabled="idx > maxStep"
                :aria-current="currentStep === idx ? 'step' : undefined"
                @click="goToStep(idx)"
            >
              <span class="stepper-circle">
                <i v-if="currentStep > idx" class="pi pi-check" />
                <component :is="s.icon" v-else class="h-4 w-4" />
              </span>
              <span class="stepper-title">{{ s.title }}</span>
            </button>
          </li>
        </ol>
      </nav>

      <!-- مرحله ۱: اطلاعات پایه -->
      <section v-if="currentStep === 0" class="surface mb-5 p-5 md:p-6" aria-labelledby="step-basic">
        <h2 id="step-basic" class="section-title"><Info class="h-5 w-5 text-blue-600" />اطلاعات پایه قرارداد</h2>

        <div class="form-grid">
          <div class="field col-span-2">
            <label for="subject" class="field-label"><FileText class="label-icon" />موضوع قرارداد<span class="required" aria-hidden="true">*</span></label>
            <InputText
                id="subject"
                v-model="form.subject"
                placeholder="مثلاً: قرارداد خرید خدمات نگهداری تأسیسات"
                :class="{ 'p-invalid': errText('subject') }"
                :aria-invalid="!!errText('subject')"
                maxlength="255"
            />
            <small v-if="errText('subject')" class="field-error" role="alert"><i class="pi pi-exclamation-circle" />{{ errText('subject') }}</small>
          </div>

          <div class="field">
            <label for="contract-type" class="field-label"><Handshake class="label-icon" />نوع قرارداد<span class="required" aria-hidden="true">*</span></label>
            <Select
                v-model="form.type"
                inputId="contract-type"
                :options="TYPE_OPTIONS"
                optionLabel="label"
                optionValue="value"
                placeholder="پیمانکار یا کارفرما را انتخاب کنید"
                :class="{ 'p-invalid': errText('type') }"
                class="w-full"
                @change="onTypeChange"
            />
            <small v-if="errText('type')" class="field-error" role="alert"><i class="pi pi-exclamation-circle" />{{ errText('type') }}</small>
            <small v-else class="field-hint">با انتخاب نوع، فهرست طرف‌های قرارداد فیلتر می‌شود.</small>
          </div>

          <div ref="partyBox" class="field">
            <label for="party-search" class="field-label"><Building2 class="label-icon" />{{ partyLabel }}<span class="required" aria-hidden="true">*</span></label>

            <div v-if="selectedParty" class="selected-party">
              <div class="flex min-w-0 items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-100"><Building2 class="h-5 w-5 text-blue-600" /></span>
                <div class="min-w-0">
                  <div class="truncate font-semibold text-slate-800">{{ selectedParty.name }}</div>
                  <div class="text-xs text-slate-500" dir="ltr">{{ selectedParty.national_id }}</div>
                </div>
              </div>
              <Button label="تغییر" icon="pi pi-pencil" severity="secondary" text size="small" @click="clearParty()" />
            </div>

            <div v-else class="relative">
              <Search class="pointer-events-none absolute right-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <InputText
                  id="party-search"
                  ref="partyInput"
                  v-model="partySearch"
                  class="w-full !pr-10"
                  :disabled="!form.type"
                  :placeholder="form.type ? 'جستجو در نام یا شناسه ملی...' : 'ابتدا نوع قرارداد را انتخاب کنید'"
                  :class="{ 'p-invalid': errText('party_id') }"
                  role="combobox"
                  aria-autocomplete="list"
                  aria-controls="party-listbox"
                  :aria-expanded="partyOpen"
                  autocomplete="off"
                  @input="searchParties"
                  @keydown="onPartyKeydown"
                  @focus="partySuggestions.length && (partyOpen = true)"
              />
              <i v-if="partyLoading" class="pi pi-spin pi-spinner absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />

              <ul v-if="partyOpen" id="party-listbox" class="party-list" role="listbox">
                <li
                    v-for="(party, i) in partySuggestions"
                    :key="party.id"
                    class="party-option"
                    :class="{ 'is-active': i === partyActive }"
                    role="option"
                    :aria-selected="i === partyActive"
                    @mousedown.prevent="selectParty(party)"
                    @mouseenter="partyActive = i"
                >
                  <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-slate-800">{{ party.name }}</span>
                    <span class="block text-xs text-slate-500" dir="ltr">{{ party.national_id }}</span>
                  </span>
                </li>
                <li v-if="partySearched && !partySuggestions.length" class="px-3 py-4 text-center text-sm text-slate-500">
                  نتیجه‌ای پیدا نشد
                </li>
              </ul>
            </div>

            <small v-if="errText('party_id')" class="field-error" role="alert"><i class="pi pi-exclamation-circle" />{{ errText('party_id') }}</small>
          </div>

          <div class="field">
            <label for="start-date" class="field-label"><Calendar class="label-icon" />تاریخ شروع<span class="required" aria-hidden="true">*</span></label>
            <DatePicker
                id="start-date"
                v-model="form.start_date"
                format="YYYY-MM-DD"
                display-format="jYYYY/jMM/jDD"
                :class="{ 'p-invalid': errText('start_date') }"
                class="w-full"
                placeholder="انتخاب تاریخ"
            />
            <small v-if="errText('start_date')" class="field-error" role="alert"><i class="pi pi-exclamation-circle" />{{ errText('start_date') }}</small>
          </div>

          <div class="field">
            <label for="end-date" class="field-label"><Calendar class="label-icon" />تاریخ پایان<span class="required" aria-hidden="true">*</span></label>
            <DatePicker
                id="end-date"
                v-model="form.end_date"
                format="YYYY-MM-DD"
                display-format="jYYYY/jMM/jDD"
                :class="{ 'p-invalid': errText('end_date') }"
                class="w-full"
                placeholder="انتخاب تاریخ"
            />
            <small v-if="errText('end_date')" class="field-error" role="alert"><i class="pi pi-exclamation-circle" />{{ errText('end_date') }}</small>
            <small v-else-if="durationDays !== null" class="field-hint">مدت قرارداد: {{ durationDays.toLocaleString('fa-IR') }} روز</small>
          </div>

          <div class="field col-span-2">
            <label for="description" class="field-label"><FileText class="label-icon" />توضیحات</label>
            <Textarea id="description" v-model="form.description" rows="3" auto-resize placeholder="توضیحات تکمیلی درباره قرارداد (اختیاری)" />
          </div>
        </div>
      </section>

      <!-- مرحله ۲: متن قرارداد -->
      <section v-if="currentStep === 1" class="surface mb-5 p-5 md:p-6" aria-labelledby="step-content">
        <h2 id="step-content" class="section-title"><FileEdit class="h-5 w-5 text-blue-600" />متن پیش‌نویس قرارداد</h2>

        <Message severity="info" :closable="false" class="mb-4">
          متن کامل قرارداد را وارد کنید. این متن پس از ارسال، توسط کارشناس حقوقی بررسی و تنظیم نهایی می‌شود. ثبت متن در این مرحله اختیاری است.
        </Message>

        <div class="editor-wrap">
          <Editor v-model="form.content" editorStyle="height: 440px" :modules="editorModules" />
        </div>
        <small v-if="errText('content')" class="field-error mt-2" role="alert"><i class="pi pi-exclamation-circle" />{{ errText('content') }}</small>

        <div v-if="pagesCount" class="mt-3 flex flex-wrap items-center gap-4 text-xs text-slate-500">
          <span class="inline-flex items-center gap-1"><FileText class="h-3.5 w-3.5" />حدود {{ pagesCount.toLocaleString('fa-IR') }} صفحه</span>
          <span class="inline-flex items-center gap-1"><AlignLeft class="h-3.5 w-3.5" />{{ plainContent.length.toLocaleString('fa-IR') }} کاراکتر</span>
        </div>
      </section>

      <!-- مرحله ۳: آیتم‌ها -->
      <section v-if="currentStep === 2" class="surface mb-5 p-5 md:p-6" aria-labelledby="step-items">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <h2 id="step-items" class="section-title !mb-0"><List class="h-5 w-5 text-blue-600" />آیتم‌های قرارداد</h2>
          <Button v-if="form.items.length" label="افزودن آیتم" icon="pi pi-plus" size="small" outlined @click="addItem" />
        </div>

        <div v-if="!form.items.length" class="empty-state">
          <List class="mx-auto mb-3 h-12 w-12 text-slate-300" />
          <p class="text-sm text-slate-500">هنوز آیتمی اضافه نشده است.</p>
          <Button label="افزودن اولین آیتم" icon="pi pi-plus" class="mt-4" @click="addItem" />
        </div>

        <div v-else class="space-y-3">
          <article v-for="(item, idx) in form.items" :key="item._key" class="item-card" data-item-card>
            <header class="mb-3 flex items-center justify-between">
              <span class="item-badge">آیتم {{ (idx + 1).toLocaleString('fa-IR') }}</span>
              <Button
                  icon="pi pi-trash"
                  severity="danger"
                  text
                  rounded
                  size="small"
                  :aria-label="`حذف آیتم ${idx + 1}`"
                  @click="removeItem(idx)"
              />
            </header>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-12">
              <div class="field col-span-2 md:col-span-12">
                <label :for="`item-desc-${item._key}`" class="field-label">شرح فعالیت<span class="required" aria-hidden="true">*</span></label>
                <InputText
                    :id="`item-desc-${item._key}`"
                    v-model="item.description"
                    placeholder="شرح فعالیت"
                    :class="{ 'p-invalid': errText(`items.${idx}.description`) }"
                />
                <small v-if="errText(`items.${idx}.description`)" class="field-error" role="alert">{{ errText(`items.${idx}.description`) }}</small>
              </div>

              <div class="field md:col-span-3">
                <label :for="`item-code-${item._key}`" class="field-label">کد آیتم</label>
                <InputText :id="`item-code-${item._key}`" v-model="item.item_code" dir="ltr" placeholder="ITEM-1" />
              </div>

              <div class="field md:col-span-2">
                <label :for="`item-unit-${item._key}`" class="field-label">واحد</label>
                <InputText :id="`item-unit-${item._key}`" v-model="item.unit" placeholder="مثلاً: ماه" />
              </div>

              <div class="field md:col-span-2">
                <label :for="`item-qty-${item._key}`" class="field-label">مقدار<span class="required" aria-hidden="true">*</span></label>
                <InputNumber
                    v-model="item.quantity"
                    :inputId="`item-qty-${item._key}`"
                    class="w-full"
                    :min="0"
                    :minFractionDigits="0"
                    :maxFractionDigits="2"
                    :invalid="!!errText(`items.${idx}.quantity`)"
                />
                <small v-if="errText(`items.${idx}.quantity`)" class="field-error" role="alert">{{ errText(`items.${idx}.quantity`) }}</small>
              </div>

              <div class="field md:col-span-3">
                <label :for="`item-price-${item._key}`" class="field-label">بهای واحد (ریال)<span class="required" aria-hidden="true">*</span></label>
                <InputNumber
                    v-model="item.unit_price"
                    :inputId="`item-price-${item._key}`"
                    class="w-full"
                    :min="0"
                    mode="decimal"
                    :minFractionDigits="0"
                    :maxFractionDigits="0"
                    :invalid="!!errText(`items.${idx}.unit_price`)"
                />
                <small v-if="errText(`items.${idx}.unit_price`)" class="field-error" role="alert">{{ errText(`items.${idx}.unit_price`) }}</small>
              </div>

              <div class="field col-span-2 md:col-span-2">
                <span class="field-label">مبلغ کل (ریال)</span>
                <div class="item-total">{{ formatNumber(calculateItemTotal(item)) }}</div>
              </div>
            </div>
          </article>

          <Button label="افزودن آیتم" icon="pi pi-plus" severity="secondary" outlined class="w-full" @click="addItem" />
        </div>

        <div v-if="form.items.length" class="total-banner mt-5">
          <span class="font-semibold text-slate-700">مبلغ کل قرارداد</span>
          <span class="text-left">
            <strong class="block text-xl text-blue-700">{{ formatNumber(totalAmount) }} ریال</strong>
            <span class="text-xs text-slate-500">معادل {{ formatNumber(totalAmount / 10) }} تومان</span>
          </span>
        </div>
      </section>

      <!-- مرحله ۴: تایید -->
      <section v-if="currentStep === 3" class="mb-5 space-y-4" aria-labelledby="step-review">
        <div class="surface p-5 md:p-6">
          <h2 id="step-review" class="section-title"><CheckCircle class="h-5 w-5 text-blue-600" />بررسی نهایی و ارسال به واحد حقوقی</h2>

          <Message v-if="warnings.length" severity="warn" :closable="false" class="mb-4">
            <ul class="list-inside list-disc space-y-1 text-sm">
              <li v-for="w in warnings" :key="w">{{ w }}</li>
            </ul>
          </Message>

          <div class="review-card">
            <header class="review-head">
              <h3>اطلاعات پایه</h3>
              <Button label="ویرایش" icon="pi pi-pencil" text size="small" @click="goToStep(0)" />
            </header>
            <dl class="review-grid">
              <div class="col-span-2"><dt>موضوع</dt><dd>{{ form.subject || '—' }}</dd></div>
              <div><dt>نوع قرارداد</dt><dd>{{ typeLabel }}</dd></div>
              <div><dt>{{ partyLabel }}</dt><dd>{{ selectedParty?.name || '—' }}</dd></div>
              <div><dt>تاریخ شروع</dt><dd>{{ formatDate(form.start_date) }}</dd></div>
              <div><dt>تاریخ پایان</dt><dd>{{ formatDate(form.end_date) }}</dd></div>
              <div v-if="durationDays !== null"><dt>مدت</dt><dd>{{ durationDays.toLocaleString('fa-IR') }} روز</dd></div>
              <div v-if="form.description" class="col-span-2"><dt>توضیحات</dt><dd class="font-normal">{{ form.description }}</dd></div>
            </dl>
          </div>

          <div class="review-card mt-3">
            <header class="review-head">
              <h3>متن قرارداد</h3>
              <Button label="ویرایش" icon="pi pi-pencil" text size="small" @click="goToStep(1)" />
            </header>
            <p class="text-sm" :class="pagesCount ? 'text-green-700' : 'text-slate-500'">
              <i :class="pagesCount ? 'pi pi-check-circle' : 'pi pi-minus-circle'" class="ml-1" />
              {{ pagesCount ? `ثبت شده (حدود ${pagesCount.toLocaleString('fa-IR')} صفحه)` : 'ثبت نشده' }}
            </p>
          </div>

          <div class="review-card mt-3">
            <header class="review-head">
              <h3>آیتم‌ها و مبلغ</h3>
              <Button label="ویرایش" icon="pi pi-pencil" text size="small" @click="goToStep(2)" />
            </header>
            <div class="flex flex-wrap items-end justify-between gap-3">
              <span class="text-sm text-slate-600">{{ form.items.length.toLocaleString('fa-IR') }} آیتم</span>
              <span class="text-left">
                <strong class="block text-lg text-blue-700">{{ formatNumber(totalAmount) }} ریال</strong>
                <span class="text-xs text-slate-500">معادل {{ formatNumber(totalAmount / 10) }} تومان</span>
              </span>
            </div>
          </div>
        </div>

        <Message severity="info" :closable="false">
          پس از ارسال، درخواست به واحد حقوقی و قراردادها ارجاع داده می‌شود و کارشناس حقوقی متن نهایی را تنظیم می‌کند.
        </Message>
      </section>

      <!-- نوار عملیات -->
      <footer class="action-bar">
        <span class="hidden text-sm text-slate-500 sm:inline">{{ progressText }}</span>
        <div class="flex flex-1 flex-wrap items-center justify-between gap-2 sm:flex-none sm:justify-end">
          <Button v-if="currentStep > 0" label="مرحله قبل" icon="pi pi-arrow-right" severity="secondary" outlined :disabled="submitting" @click="prevStep" />
          <Button label="انصراف" severity="secondary" text :disabled="submitting" @click="cancel" />
          <Button v-if="currentStep < steps.length - 1" label="مرحله بعد" icon="pi pi-arrow-left" @click="nextStep" />
          <Button v-else label="ارسال به واحد حقوقی" icon="pi pi-send" severity="success" :loading="submitting" @click="submitToLegal" />
        </div>
      </footer>
    </template>
  </div>
</template>

<style scoped>
.surface {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 1rem;
  box-shadow: 0 1px 2px rgb(15 23 42 / 4%);
}

.unit-chip {
  display: inline-flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
  padding: 0.35rem 0.75rem;
  font-size: 0.8rem;
  color: #475569;
  background: #eff6ff;
  border: 1px solid #dbeafe;
  border-radius: 9999px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
  font-size: 1.05rem;
  font-weight: 700;
  color: #1e293b;
}

/* فرم */
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.25rem;
}
.col-span-2 {
  grid-column: span 2;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  min-width: 0;
}
.field-label {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #475569;
}
.label-icon {
  width: 16px;
  height: 16px;
  color: #3b82f6;
}
.required {
  margin-right: 0.15rem;
  color: #ef4444;
}
.field-hint {
  font-size: 0.75rem;
  color: #94a3b8;
}
.field-error {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.78rem;
  color: #dc2626;
}

/* مراحل */
.stepper {
  display: flex;
  margin: 0;
  padding: 0;
  list-style: none;
}
.stepper-item {
  position: relative;
  flex: 1;
}
.stepper-item + .stepper-item::before {
  content: '';
  position: absolute;
  top: 19px;
  inset-inline-start: -50%;
  inset-inline-end: 50%;
  height: 2px;
  background: #e2e8f0;
  transition: background 0.25s;
}
.stepper-item.is-done::before,
.stepper-item.is-active::before {
  background: #3b82f6;
}
.stepper-btn {
  position: relative;
  z-index: 1;
  display: flex;
  width: 100%;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  background: none;
  border: 0;
  cursor: pointer;
}
.stepper-btn:disabled {
  cursor: default;
}
.stepper-circle {
  display: flex;
  width: 40px;
  height: 40px;
  align-items: center;
  justify-content: center;
  color: #64748b;
  background: #fff;
  border: 2px solid #cbd5e1;
  border-radius: 9999px;
  transition: all 0.25s;
}
.stepper-title {
  font-size: 0.8rem;
  font-weight: 500;
  color: #64748b;
  text-align: center;
}
.stepper-btn:focus-visible .stepper-circle {
  outline: 2px solid #93c5fd;
  outline-offset: 2px;
}
.is-active .stepper-circle {
  color: #2563eb;
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgb(59 130 246 / 15%);
}
.is-active .stepper-title {
  font-weight: 700;
  color: #2563eb;
}
.is-done .stepper-circle {
  color: #fff;
  background: #2563eb;
  border-color: #2563eb;
}

/* انتخاب طرف قرارداد */
.party-list {
  position: absolute;
  z-index: 30;
  inset-inline: 0;
  top: calc(100% + 4px);
  max-height: 240px;
  margin: 0;
  padding: 4px;
  overflow-y: auto;
  list-style: none;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgb(15 23 42 / 12%);
}
.party-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.75rem;
  cursor: pointer;
  border-radius: 8px;
}
.party-option.is-active {
  background: #eff6ff;
}
.selected-party {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.6rem 0.75rem;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 12px;
}

/* ادیتور */
.editor-wrap {
  overflow: hidden;
  border: 1px solid #cbd5e1;
  border-radius: 12px;
}
.editor-wrap :deep(.ql-toolbar) {
  direction: rtl;
  border: 0;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}
.editor-wrap :deep(.ql-container) {
  border: 0;
}
.editor-wrap :deep(.ql-editor) {
  direction: rtl;
  text-align: right;
  font-size: 0.95rem;
  line-height: 2;
}

/* آیتم‌ها */
.empty-state {
  padding: 3rem 1rem;
  text-align: center;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: 12px;
}
.item-card {
  padding: 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}
.item-badge {
  padding: 0.2rem 0.65rem;
  font-size: 0.78rem;
  font-weight: 700;
  color: #1d4ed8;
  background: #dbeafe;
  border-radius: 9999px;
}
.item-total {
  display: flex;
  min-height: 42px;
  align-items: center;
  padding: 0 0.75rem;
  font-weight: 700;
  color: #1e293b;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
}
.total-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border: 1px solid #bfdbfe;
  border-radius: 12px;
}

/* مرور نهایی */
.review-card {
  padding: 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}
.review-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}
.review-head h3 {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
}
.review-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem 1.25rem;
  margin: 0;
}
.review-grid dt {
  margin-bottom: 0.2rem;
  font-size: 0.75rem;
  color: #64748b;
}
.review-grid dd {
  margin: 0;
  font-size: 0.92rem;
  font-weight: 600;
  color: #1e293b;
}

/* نوار عملیات */
.action-bar {
  position: sticky;
  bottom: 0.75rem;
  z-index: 20;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: rgb(255 255 255 / 92%);
  backdrop-filter: blur(8px);
  border: 1px solid #e2e8f0;
  border-radius: 1rem;
  box-shadow: 0 8px 24px rgb(15 23 42 / 10%);
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  .col-span-2 {
    grid-column: span 1;
  }
  .review-grid {
    grid-template-columns: 1fr;
  }
  .review-grid .col-span-2 {
    grid-column: span 1;
  }
  .stepper-title {
    font-size: 0.7rem;
  }
}
</style>