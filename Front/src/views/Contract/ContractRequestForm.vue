<!-- src/views/Contract/ContractRequestForm.vue -->
<template>
  <div class="min-h-screen bg-slate-50 p-4 md:p-6">
    <!-- هدر -->
    <div class="page-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5 md:p-6 mb-6">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
          <FileSignature class="w-7 h-7 text-blue-600" />
        </div>
        <div class="flex-1">
          <h1 class="text-2xl font-bold text-gray-800">
            {{ isEdit ? 'ویرایش درخواست قرارداد' : 'درخواست قرارداد جدید' }}
          </h1>
          <p class="text-gray-500 mt-1 text-sm">
            {{ isEdit ? 'ویرایش پیش‌نویس درخواست' : 'ثبت درخواست قرارداد جدید برای واحد شما' }}
          </p>
        </div>
        <Button
            label="بازگشت به لیست"
            icon="pi pi-arrow-right"
            severity="secondary"
            outlined
            @click="$router.push('/contract-requests')"
        />
      </div>
    </div>

    <!-- اطلاعات واحد -->
    <div v-if="myUnit" class="info-banner mb-6">
      <div class="info-banner-icon">
        <Building2 class="w-5 h-5" />
      </div>
      <div class="info-banner-content">
        <div class="info-banner-title">واحد درخواست‌کننده: {{ myUnit.name }}</div>
        <div class="info-banner-subtitle">
          کد واحد: <strong>{{ myUnit.code }}</strong>
        </div>
      </div>
    </div>

    <!-- Stepper -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-6">
      <div class="stepper-container">
        <div class="step-progress-track">
          <div class="step-progress-bar" :style="{ width: progressWidth }"></div>
        </div>
        <div class="steps-wrapper">
          <div
              v-for="(s, idx) in steps"
              :key="idx"
              class="step-item"
              :class="{
              'active': currentStep === idx,
              'completed': currentStep > idx
            }"
          >
            <div class="step-circle">
              <i v-if="currentStep > idx" class="pi pi-check"></i>
              <component v-else :is="s.icon" class="w-4 h-4" />
            </div>
            <span class="step-title">{{ s.title }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- مرحله ۱: اطلاعات پایه -->
    <div v-if="currentStep === 0" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">
      <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
        <Info class="w-5 h-5 text-blue-600" />
        اطلاعات پایه قرارداد
      </h2>

      <div class="form-grid">
        <div class="form-group full-width">
          <label class="form-label">
            <FileText class="label-icon" />
            موضوع قرارداد
            <span class="required">*</span>
          </label>
          <InputText
              v-model="form.subject"
              :class="{ 'p-invalid': errors.subject }"
              placeholder="موضوع قرارداد را وارد کنید"
              class="form-input"
          />
          <small class="error-message" v-if="errors.subject">{{ errors.subject }}</small>
        </div>

        <div class="form-group">
          <label class="form-label">
            <Handshake class="label-icon" />
            نوع قرارداد
            <span class="required">*</span>
          </label>
          <Select
              v-model="form.type"
              :options="typeOptions"
              optionLabel="label"
              optionValue="value"
              :class="{ 'p-invalid': errors.type }"
              placeholder="انتخاب کنید"
              class="w-full"
          />
          <small class="error-message" v-if="errors.type">{{ errors.type }}</small>
        </div>

        <div class="form-group">
          <label class="form-label">
            <Building2 class="label-icon" />
            طرف قرارداد (پیمانکار)
            <span class="required">*</span>
          </label>
          <div class="relative">
            <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 z-10" />
            <InputText
                v-model="partySearch"
                placeholder="جستجو در نام یا شناسه ملی..."
                class="w-full pr-10"
                @input="searchParties"
            />
          </div>
          <div class="parties-dropdown" v-if="partySuggestions.length > 0">
            <div
                v-for="party in partySuggestions"
                :key="party.id"
                class="party-option"
                :class="{ 'is-selected': form.party_id === party.id }"
                @click="selectParty(party)"
            >
              <div class="party-option-info">
                <div class="party-option-name">{{ party.name }}</div>
                <div class="party-option-id" dir="ltr">{{ party.national_id }}</div>
              </div>
              <CheckCircle v-if="form.party_id === party.id" class="w-5 h-5 text-blue-600" />
            </div>
          </div>
          <div v-if="selectedParty" class="selected-party-card">
            <div class="selected-party-info">
              <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <Building2 class="w-5 h-5 text-blue-600" />
              </div>
              <div>
                <div class="font-semibold text-slate-800">{{ selectedParty.name }}</div>
                <div class="text-xs text-slate-500" dir="ltr">{{ selectedParty.national_id }}</div>
              </div>
            </div>
            <Button
                icon="pi pi-times"
                severity="danger"
                text
                rounded
                size="small"
                @click="form.party_id = null"
            />
          </div>
          <small class="error-message" v-if="errors.party_id">{{ errors.party_id }}</small>
        </div>

        <div class="form-group">
          <label class="form-label">
            <Calendar class="label-icon" />
            تاریخ شروع
            <span class="required">*</span>
          </label>
          <DatePicker
              v-model="form.start_date"
              dateFormat="yy/mm/dd"
              :class="{ 'p-invalid': errors.start_date }"
              class="w-full"
              placeholder="انتخاب تاریخ"
          />
          <small class="error-message" v-if="errors.start_date">{{ errors.start_date }}</small>
        </div>

        <div class="form-group">
          <label class="form-label">
            <Calendar class="label-icon" />
            تاریخ پایان
            <span class="required">*</span>
          </label>
          <DatePicker
              v-model="form.end_date"
              dateFormat="yy/mm/dd"
              :class="{ 'p-invalid': errors.end_date }"
              class="w-full"
              placeholder="انتخاب تاریخ"
          />
          <small class="error-message" v-if="errors.end_date">{{ errors.end_date }}</small>
        </div>

        <div class="form-group full-width">
          <label class="form-label">
            <FileText class="label-icon" />
            توضیحات
          </label>
          <Textarea
              v-model="form.description"
              rows="3"
              placeholder="توضیحات تکمیلی درباره قرارداد..."
              class="form-input"
          />
        </div>
      </div>
    </div>

    <!-- مرحله ۲: متن قرارداد -->
    <div v-if="currentStep === 1" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">
      <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
        <FileEdit class="w-5 h-5 text-blue-600" />
        متن پیش‌نویس قرارداد
      </h2>

      <div class="info-banner mb-4">
        <div class="info-banner-icon">
          <Info class="w-5 h-5" />
        </div>
        <div class="info-banner-content">
          <div class="info-banner-title">راهنما</div>
          <div class="info-banner-subtitle">
            متن کامل قرارداد را در کادر زیر وارد کنید. این متن پس از ارسال به واحد حقوقی، توسط کارشناس حقوقی بررسی و تنظیم نهایی خواهد شد.
          </div>
        </div>
      </div>

      <div class="border border-slate-300 rounded-lg overflow-hidden">
        <Editor
            v-model="form.content"
            editorStyle="height: 500px"
            :modules="{
            toolbar: [
              [{ 'header': [1, 2, 3, false] }],
              ['bold', 'italic', 'underline', 'strike'],
              [{ 'list': 'ordered'}, { 'list': 'bullet' }],
              [{ 'indent': '-1'}, { 'indent': '+1' }],
              [{ 'color': [] }, { 'background': [] }],
              [{ 'align': [] }],
              ['link', 'image'],
              ['clean']
            ]
          }"
        />
      </div>

      <div v-if="form.content" class="flex items-center gap-4 text-xs text-surface-500 mt-3">
        <span>
          <FileText class="w-3 h-3 inline" />
          {{ pagesCount }} صفحه تقریبی
        </span>
        <span>
          <AlignLeft class="w-3 h-3 inline" />
          {{ stripHtml(form.content).length }} کاراکتر
        </span>
      </div>

      <Message v-if="!form.content" severity="warn" :closable="false" class="mt-4">
        <i class="pi pi-exclamation-triangle ml-2" />
        ثبت متن قرارداد اختیاری است اما برای تسریع فرآیند بررسی حقوقی توصیه می‌شود.
      </Message>
    </div>

    <!-- مرحله ۳: آیتم‌های قرارداد -->
    <div v-if="currentStep === 2" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">
      <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
        <List class="w-5 h-5 text-blue-600" />
        آیتم‌های قرارداد
      </h2>

      <div class="flex justify-end mb-4">
        <Button
            label="افزودن آیتم"
            icon="pi pi-plus"
            severity="secondary"
            outlined
            size="small"
            @click="addItem"
        />
      </div>

      <div v-if="form.items.length === 0" class="empty-state">
        <List class="w-12 h-12 text-gray-300 mx-auto mb-3" />
        <p class="text-gray-500 text-sm">هنوز آیتمی اضافه نشده است</p>
      </div>

      <DataTable
          v-else
          :value="form.items"
          stripedRows
          class="items-table"
      >
        <Column header="ردیف" style="width: 60px">
          <template #body="slotProps">
            <span class="font-mono text-sm">{{ slotProps.index + 1 }}</span>
          </template>
        </Column>
        <Column header="شرح فعالیت">
          <template #body="slotProps">
            <InputText
                v-model="slotProps.data.description"
                class="w-full"
                placeholder="شرح فعالیت"
            />
          </template>
        </Column>
        <Column header="واحد" style="width: 120px">
          <template #body="slotProps">
            <InputText
                v-model="slotProps.data.unit"
                class="w-full"
                placeholder="واحد"
            />
          </template>
        </Column>
        <Column header="مقدار" style="width: 120px">
          <template #body="slotProps">
            <InputNumber
                v-model="slotProps.data.quantity"
                class="w-full"
                :min="0"
                :minFractionDigits="0"
                :maxFractionDigits="2"
            />
          </template>
        </Column>
        <Column header="بهای واحد (ریال)" style="width: 160px">
          <template #body="slotProps">
            <InputNumber
                v-model="slotProps.data.unit_price"
                class="w-full"
                :min="0"
                mode="decimal"
                :minFractionDigits="0"
                :maxFractionDigits="0"
            />
          </template>
        </Column>
        <Column header="مبلغ کل" style="width: 160px">
          <template #body="slotProps">
            <span class="font-bold text-slate-800">
              {{ formatNumber(calculateItemTotal(slotProps.data)) }}
            </span>
          </template>
        </Column>
        <Column header="" style="width: 60px">
          <template #body="slotProps">
            <Button
                icon="pi pi-trash"
                severity="danger"
                text
                rounded
                size="small"
                @click="removeItem(slotProps.index)"
            />
          </template>
        </Column>
      </DataTable>

      <div v-if="form.items.length > 0" class="total-banner mt-4">
        <div class="total-label">مبلغ کل قرارداد:</div>
        <div class="total-value">{{ formatNumber(totalAmount) }} ریال</div>
      </div>
    </div>

    <!-- مرحله ۴: تایید و ارسال -->
    <div v-if="currentStep === 3" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">
      <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
        <CheckCircle class="w-5 h-5 text-blue-600" />
        تایید و ارسال به واحد حقوقی
      </h2>

      <div class="summary-grid">
        <div class="summary-card">
          <div class="summary-label">موضوع</div>
          <div class="summary-value">{{ form.subject || '—' }}</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">نوع قرارداد</div>
          <div class="summary-value">
            {{ typeOptions.find(t => t.value === form.type)?.label || '—' }}
          </div>
        </div>
        <div class="summary-card">
          <div class="summary-label">طرف قرارداد</div>
          <div class="summary-value">{{ selectedParty?.name || '—' }}</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">مدت قرارداد</div>
          <div class="summary-value">
            {{ form.start_date && form.end_date ? `${form.start_date} تا ${form.end_date}` : '—' }}
          </div>
        </div>
        <div class="summary-card">
          <div class="summary-label">تعداد آیتم‌ها</div>
          <div class="summary-value">{{ form.items.length }} مورد</div>
        </div>
        <div class="summary-card highlight">
          <div class="summary-label">مبلغ کل</div>
          <div class="summary-value total">{{ formatNumber(totalAmount) }} ریال</div>
        </div>
        <div class="summary-card full-width">
          <div class="summary-label">متن قرارداد</div>
          <div class="summary-value text-sm">
            {{ form.content ? `✅ ثبت شده (${pagesCount} صفحه)` : '❌ ثبت نشده' }}
          </div>
        </div>
      </div>

      <Message severity="info" :closable="false" class="mt-4">
        <i class="pi pi-info-circle ml-2" />
        پس از ارسال، درخواست شما به واحد حقوقی و قراردادها ارجاع داده می‌شود و کارشناس حقوقی متن نهایی را تنظیم خواهد کرد.
      </Message>
    </div>

    <!-- دکمه‌های ناوبری -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex items-center justify-between">
      <Button
          v-if="currentStep > 0"
          label="مرحله قبل"
          icon="pi pi-arrow-right"
          severity="secondary"
          outlined
          @click="currentStep--"
      />
      <div v-else></div>

      <div class="flex gap-3">
        <Button
            label="انصراف"
            severity="secondary"
            text
            @click="$router.push('/contract-requests')"
        />
        <Button
            v-if="currentStep < 3"
            label="مرحله بعد"
            icon="pi pi-arrow-left"
            iconPos="left"
            @click="nextStep"
            class="bg-blue-600 hover:bg-blue-700"
        />
        <Button
            v-else
            label="ارسال به واحد حقوقی"
            icon="pi pi-send"
            :loading="submitting"
            @click="submitToLegal"
            class="bg-green-600 hover:bg-green-700"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useApiErrorHandler } from '@/composables/useApiErrorHandler'
import api from '@/api/axios.js'
import { useAuthStore } from '@/stores/auth.js'
import {
  FileSignature, Info, FileText, Handshake, Building2, Calendar,
  FileEdit, AlignLeft, List, CheckCircle, Search
} from 'lucide-vue-next'

const router = useRouter()
const toast = useToast()
const { handleApiError } = useApiErrorHandler()
const authStore = useAuthStore()

const currentStep = ref(0)
const submitting = ref(false)
const isEdit = ref(false)
const myUnit = ref(null)

const steps = [
  { title: 'اطلاعات پایه', icon: Info },
  { title: 'متن قرارداد', icon: FileEdit },
  { title: 'آیتم‌ها', icon: List },
  { title: 'تایید و ارسال', icon: CheckCircle },
]

const typeOptions = [
  { label: 'خرید کالا', value: 'purchase' },
  { label: 'خدمات', value: 'service' },
  { label: 'پیمانکاری', value: 'contracting' },
  { label: 'مشاوره', value: 'consulting' },
  { label: 'اجاره', value: 'rental' },
  { label: 'سایر', value: 'other' },
]

const form = ref({
  id: null,
  subject: '',
  type: 'service',
  party_id: null,
  start_date: null,
  end_date: null,
  description: '',
  content: '',
  items: [],
})

const errors = ref({})

const partySearch = ref('')
const partySuggestions = ref([])
const partySearchTimeout = ref(null)
const selectedParty = ref(null)

// Computed
const progressWidth = computed(() => {
  return `${(currentStep.value / (steps.length - 1)) * 100}%`
})

const pagesCount = computed(() => {
  if (!form.value.content) return 0
  const text = stripHtml(form.value.content)
  return Math.max(1, Math.ceil(text.length / 1500))
})

const totalAmount = computed(() => {
  return form.value.items.reduce((sum, item) => {
    const qty = parseFloat(item.quantity) || 0
    const price = parseFloat(item.unit_price) || 0
    return sum + (qty * price)
  }, 0)
})

// Methods
const stripHtml = (html) => {
  if (!html) return ''
  const doc = new DOMParser().parseFromString(html, 'text/html')
  return doc.body.textContent || ''
}

const formatNumber = (num) => {
  if (!num) return '0'
  return Number(num).toLocaleString('fa-IR')
}

const calculateItemTotal = (item) => {
  const qty = parseFloat(item.quantity) || 0
  const price = parseFloat(item.unit_price) || 0
  return qty * price
}

const loadMyUnit = async () => {
  try {
    const response = await api.get('/organizational-units/my-unit')
    myUnit.value = response.data.data || response.data
  } catch (error) {
    console.error('خطا در دریافت واحد:', error)
  }
}

const searchParties = () => {
  clearTimeout(partySearchTimeout.value)
  partySearchTimeout.value = setTimeout(async () => {
    if (!partySearch.value || partySearch.value.length < 2) {
      partySuggestions.value = []
      return
    }
    try {
      const response = await api.get('/parties', {
        params: { search: partySearch.value, role: 'contractor', per_page: 10 }
      })
      partySuggestions.value = response.data.data || []
    } catch (error) {
      console.error('خطا در جستجوی پیمانکاران:', error)
    }
  }, 300)
}

const selectParty = (party) => {
  form.value.party_id = party.id
  selectedParty.value = party
  partySearch.value = ''
  partySuggestions.value = []
}

const addItem = () => {
  form.value.items.push({
    description: '',
    unit: '',
    quantity: 0,
    unit_price: 0,
  })
}

const removeItem = (index) => {
  form.value.items.splice(index, 1)
}

const validateStep = (step) => {
  errors.value = {}
  let isValid = true

  if (step === 0) {
    if (!form.value.subject?.trim()) {
      errors.value.subject = 'موضوع قرارداد الزامی است'
      isValid = false
    }
    if (!form.value.type) {
      errors.value.type = 'نوع قرارداد الزامی است'
      isValid = false
    }
    if (!form.value.party_id) {
      errors.value.party_id = 'انتخاب طرف قرارداد الزامی است'
      isValid = false
    }
    if (!form.value.start_date) {
      errors.value.start_date = 'تاریخ شروع الزامی است'
      isValid = false
    }
    if (!form.value.end_date) {
      errors.value.end_date = 'تاریخ پایان الزامی است'
      isValid = false
    }
    if (form.value.start_date && form.value.end_date && form.value.start_date > form.value.end_date) {
      errors.value.end_date = 'تاریخ پایان باید بعد از تاریخ شروع باشد'
      isValid = false
    }
  }

  return isValid
}

const nextStep = () => {
  if (validateStep(currentStep.value)) {
    currentStep.value++
  }
}

const submitToLegal = async () => {
  if (!validateStep(0)) {
    currentStep.value = 0
    return
  }

  submitting.value = true
  errors.value = {}

  try {
    const payload = {
      ...form.value,
      unit_id: myUnit.value?.id,
      amount: totalAmount.value,
    }

    if (isEdit.value && form.value.id) {
      await api.put(`/contracts/${form.value.id}`, payload)
      toast.add({ severity: 'success', summary: 'موفق', detail: 'درخواست با موفقیت ویرایش شد', life: 3000 })
    } else {
      await api.post('/contracts', payload)
      toast.add({ severity: 'success', summary: 'موفق', detail: 'درخواست قرارداد با موفقیت ثبت و به واحد حقوقی ارسال شد', life: 4000 })
    }

    setTimeout(() => {
      router.push('/contract-requests')
    }, 1500)
  } catch (error) {
    handleApiError(error, {
      setErrors: (validationErrors) => {
        errors.value = validationErrors
      }
    })
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadMyUnit()
})
</script>

<style scoped>
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group.full-width {
  grid-column: span 2;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
}

.label-icon {
  width: 16px;
  height: 16px;
  color: #3b82f6;
}

.required {
  color: #ef4444;
  margin-right: 0.25rem;
}

.form-input {
  width: 100%;
}

.error-message {
  color: #ef4444;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

/* Info Banner */
.info-banner {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border: 1px solid #bfdbfe;
  border-radius: 12px;
}

.info-banner-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #3b82f6;
  color: white;
  border-radius: 10px;
}

.info-banner-content {
  flex: 1;
}

.info-banner-title {
  font-weight: 700;
  color: #1e293b;
  font-size: 0.95rem;
}

.info-banner-subtitle {
  font-size: 0.8rem;
  color: #64748b;
  margin-top: 0.15rem;
}

/* Stepper */
.stepper-container {
  position: relative;
  padding: 0 0.5rem;
}

.step-progress-track {
  position: absolute;
  top: 20px;
  left: 30px;
  right: 30px;
  height: 2px;
  background-color: #e2e8f0;
  z-index: 1;
}

.step-progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #2563eb);
  transition: width 0.3s ease;
}

.steps-wrapper {
  display: flex;
  justify-content: space-between;
  position: relative;
  z-index: 2;
}

.step-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
}

.step-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: #ffffff;
  border: 2px solid #cbd5e1;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.step-title {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 500;
  text-align: center;
}

.step-item.active .step-circle {
  border-color: #3b82f6;
  color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.step-item.active .step-title {
  color: #3b82f6;
  font-weight: 600;
}

.step-item.completed .step-circle {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border-color: #3b82f6;
  color: #ffffff;
}

/* Parties Dropdown */
.parties-dropdown {
  margin-top: 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  max-height: 200px;
  overflow-y: auto;
  background: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.party-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  cursor: pointer;
  transition: background 0.15s;
  border-bottom: 1px solid #f1f5f9;
}

.party-option:last-child {
  border-bottom: none;
}

.party-option:hover {
  background: #f8fafc;
}

.party-option.is-selected {
  background: #eff6ff;
}

.party-option-info {
  flex: 1;
  min-width: 0;
}

.party-option-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
}

.party-option-id {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.1rem;
}

.selected-party-card {
  margin-top: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 10px;
}

.selected-party-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

/* Items Table */
.items-table {
  border: 0;
}

:deep(.items-table .p-datatable-tbody > tr > td) {
  padding: 0.75rem 0.5rem;
  vertical-align: middle;
}

:deep(.items-table .p-datatable-thead > tr > th) {
  padding: 0.75rem 0.5rem;
  font-size: 0.78rem;
  background: #f3f4f6;
  font-weight: 600;
  color: #374151;
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: #94a3b8;
}

.total-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border: 1px solid #bfdbfe;
  border-radius: 10px;
}

.total-label {
  font-size: 0.95rem;
  font-weight: 600;
  color: #1e293b;
}

.total-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1d4ed8;
}

/* Summary */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.summary-card {
  padding: 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.summary-card.full-width {
  grid-column: span 3;
}

.summary-card.highlight {
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border-color: #bfdbfe;
}

.summary-label {
  font-size: 0.75rem;
  color: #64748b;
  margin-bottom: 0.5rem;
}

.summary-value {
  font-size: 0.95rem;
  font-weight: 600;
  color: #1e293b;
}

.summary-value.total {
  font-size: 1.1rem;
  color: #1d4ed8;
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  .form-group.full-width {
    grid-column: span 1;
  }
  .summary-grid {
    grid-template-columns: 1fr;
  }
  .summary-card.full-width {
    grid-column: span 1;
  }
  .steps-wrapper {
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  .step-title {
    font-size: 0.7rem;
  }
}
</style>