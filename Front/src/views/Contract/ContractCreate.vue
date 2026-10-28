<template>
  <div class="create-page" dir="rtl">
    <!-- پس‌زمینه تزئینی -->
    <div class="page-background">
      <div class="bg-shape shape-one"></div>
      <div class="bg-shape shape-two"></div>
      <div class="bg-shape shape-three"></div>
      <div class="bg-grid"></div>
      <div class="floating-icons">
        <i class="pi pi-file-plus floating-icon icon-1"></i>
        <i class="pi pi-handshake floating-icon icon-2"></i>
        <i class="pi pi-list floating-icon icon-3"></i>
        <i class="pi pi-check-circle floating-icon icon-4"></i>
        <i class="pi pi-calendar floating-icon icon-5"></i>
        <i class="pi pi-users floating-icon icon-6"></i>
      </div>
    </div>
    <div class="create-shell">
      <!-- ═══════ هدر صفحه ═══════ -->
      <header class="page-header">
        <div class="header-content">
          <div class="header-text">
            <div class="header-badge">
              <i class="pi pi-file-plus"></i>
              <span>ایجاد قرارداد جدید</span>
            </div>
            <h1 class="header-title">ثبت قرارداد</h1>
            <p class="header-subtitle">
              اطلاعات قرارداد جدید را در سه مرحله وارد و نهایی کنید
            </p>
          </div>
          <div class="header-actions">
            <Button
                label="انصراف"
                icon="pi pi-times"
                class="cancel-btn"
                @click="router.push('/contracts')"
            />
          </div>
        </div>
      </header>

      <!-- ═══════ استپر مراحل ═══════ -->
      <section class="stepper-section">
        <div class="stepper-card">
          <div class="stepper-track">
            <div class="stepper-progress" :style="{ width: progressWidth }"></div>
          </div>
          <div class="stepper-steps">
            <div
                v-for="(s, idx) in steps"
                :key="idx"
                class="step-item"
                :class="{
            active: currentStep === idx,
            completed: currentStep > idx,
          }"
                @click="goToStep(idx)"
            >
              <div class="step-circle">
                <i v-if="currentStep > idx" class="pi pi-check"></i>
                <i v-else :class="s.icon"></i>
              </div>
              <div class="step-info">
                <span class="step-number">مرحله {{ idx + 1 }}</span>
                <span class="step-label">{{ s.label }}</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══════ فرم ═══════ -->
      <form @submit.prevent="handleSubmit" class="create-form">
        <!-- ═══════ مرحله ۱: اطلاعات پایه ═══════ -->
        <div v-if="currentStep === 0" class="form-step">
          <div class="step-card">
            <div class="card-header">
              <div class="card-title-wrapper">
                <div class="card-title-icon blue">
                  <i class="pi pi-info-circle"></i>
                </div>
                <div>
                  <h2 class="card-title">اطلاعات پایه قرارداد</h2>
                  <p class="card-subtitle">مشخصات کلی و طرفین قرارداد را وارد کنید</p>
                </div>
              </div>
            </div>

            <div class="form-body">
              <!-- موضوع قرارداد -->
              <div class="field-group full-width">
                <label class="field-label">
                  <i class="pi pi-file field-icon"></i>
                  موضوع قرارداد <span class="required">*</span>
                </label>
                <InputText
                    v-model="form.subject"
                    :invalid="!!errors.subject"
                    class="form-input"
                    placeholder="مثال: قرارداد ساخت ساختمان اداری"
                />
                <p v-if="errors.subject" class="field-error">
                  <i class="pi pi-exclamation-circle"></i>
                  {{ errors.subject }}
                </p>
              </div>

              <!-- نوع و طرف قرارداد -->
              <div class="field-row">
                <div class="field-group">
                  <label class="field-label">
                    <i class="pi pi-tags field-icon"></i>
                    نوع قرارداد <span class="required">*</span>
                  </label>
                  <Select
                      v-model="form.type"
                      :options="typeOptions"
                      option-label="label"
                      option-value="value"
                      class="form-select"
                  />
                </div>

                <div class="field-group">
                  <label class="field-label">
                    <i class="pi pi-users field-icon"></i>
                    طرف قرارداد <span class="required">*</span>
                  </label>
                  <Select
                      v-model="form.party_id"
                      :options="partySuggestions"
                      option-value="id"
                      option-label="name"
                      :filter-fields="partyFilterFields"
                      placeholder="جستجوی نام یا شناسه ملی..."
                      class="w-full"
                      :invalid="!!errors.party_id"
                      filter
                      filter-placeholder="نام یا شناسه ملی را وارد کنید"
                      show-clear
                      :loading="partyStore.loadingContractors ?? false"
                  />
                  <p v-if="errors.party_id" class="field-error">
                    <i class="pi pi-exclamation-circle"></i>
                    {{ errors.party_id }}
                  </p>
                </div>
              </div>

              <!-- تاریخ‌ها -->
              <div class="field-row">
                <PersianDatePicker
                    v-model="form.start_date"
                    label="تاریخ شروع"
                    :required="true"
                    :invalid="!!errors.start_date"
                    icon="pi pi-calendar-plus"
                />
                <PersianDatePicker
                    v-model="form.end_date"
                    label="تاریخ پایان"
                    :required="true"
                    :invalid="!!errors.end_date"
                    icon="pi pi-calendar-times"
                />
              </div>

              <!-- توضیحات -->
              <div class="field-group full-width">
                <label class="field-label">
                  <i class="pi pi-align-right field-icon"></i>
                  توضیحات
                </label>
                <Textarea
                    v-model="form.description"
                    rows="4"
                    class="form-textarea"
                    placeholder="توضیحات تکمیلی درباره قرارداد (اختیاری)"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- ✅ Step 2: متن قرارداد (جدید) -->
        <Card v-if="currentStep === 1" class="form-step">
          <template #title>
          <span class="flex items-center gap-2">
            <i class="pi pi-file-edit text-primary" />
            متن کامل قرارداد
          </span>
          </template>
          <template #content>
            <div class="space-y-4">
              <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-500 rounded-lg p-4">
                <div class="flex items-start gap-3">
                  <i class="pi pi-info-circle text-blue-500 text-xl mt-1" />
                  <div class="text-sm text-blue-1000 dark:text-blue-1000">
                    <strong>راهنما:</strong>
                    متن کامل قرارداد را در کادر زیر وارد کنید. این متن در زمان امضا به پیمانکار نمایش داده می‌شود.
                    می‌توانید از قالب‌های آماده استفاده کنید یا متن را از فایل Word کپی نمایید.
                  </div>
                </div>
              </div>

              <!-- Quill Editor -->
              <div class="border border-surface-300 dark:border-surface-600 rounded-lg overflow-hidden">

                <Editor
                    v-model="form.content"
                    editorStyle="height: 500px"
                    class="contract-editor"
                    :formats="[
                    'header', 'bold', 'italic', 'underline', 'strike',
                    'script', 'list', 'bullet', 'indent', 'direction',
                    'color', 'background', 'align', 'blockquote',
                    'code-block', 'link', 'image'
                  ]"
                >
                  <template #toolbar>
                  <span class="ql-formats">
                    <select class="ql-header" aria-label="اندازه عنوان">
                      <option value="1">عنوان ۱</option>
                      <option value="2">عنوان ۲</option>
                      <option value="3">عنوان ۳</option>
                      <option value="4">عنوان ۴</option>
                      <option value="5">عنوان ۵</option>
                      <option value="6">عنوان ۶</option>
                      <option selected>متن عادی</option>
                    </select>
                  </span>
                    <span class="ql-formats">
                    <button type="button" class="ql-bold" aria-label="ضخیم"></button>
                    <button type="button" class="ql-italic" aria-label="ایتالیک"></button>
                    <button type="button" class="ql-underline" aria-label="زیرخط"></button>
                    <button type="button" class="ql-strike" aria-label="خط‌خورده"></button>
                  </span>
                    <span class="ql-formats">
                    <button type="button" class="ql-script" value="sub" aria-label="زیرنویس"></button>
                    <button type="button" class="ql-script" value="super" aria-label="بالانویس"></button>
                  </span>
                    <span class="ql-formats">
                    <button type="button" class="ql-list" value="ordered" aria-label="فهرست شماره‌دار"></button>
                    <button type="button" class="ql-list" value="bullet" aria-label="فهرست نشانه‌دار"></button>
                    <button type="button" class="ql-indent" value="-1" aria-label="کاهش تورفتگی"></button>
                    <button type="button" class="ql-indent" value="+1" aria-label="افزایش تورفتگی"></button>
                  </span>
                    <span class="ql-formats">
                    <select class="ql-color" aria-label="رنگ متن"></select>
                    <select class="ql-background" aria-label="رنگ پس‌زمینه"></select>
                  </span>
                    <span class="ql-formats">
                    <select class="ql-align" aria-label="تراز متن"></select>
                    <button type="button" class="ql-direction" value="rtl" aria-label="راست‌به‌چپ"></button>
                  </span>
                    <span class="ql-formats">
                    <button type="button" class="ql-blockquote" aria-label="نقل‌قول"></button>
                    <button type="button" class="ql-code-block" aria-label="بلوک کد"></button>
                  </span>
                    <span class="ql-formats">
                    <button type="button" class="ql-link" aria-label="افزودن پیوند"></button>
                    <button type="button" class="ql-image" aria-label="افزودن تصویر"></button>
                  </span>
                    <span class="ql-formats">
                    <button type="button" class="ql-clean" aria-label="پاک‌کردن قالب‌بندی"></button>
                  </span>
                  </template>
                </Editor>

              </div>

              <!-- آمار متن -->
              <div v-if="form.content" class="flex items-center gap-4 text-xs text-surface-500">
            <span>
              <i class="pi pi-file ml-1" />
              {{ Math.max(1, Math.ceil(stripHtml(form.content).length / 1500)) }} صفحه تقریبی
            </span>
                <span>
              <i class="pi pi-align-left ml-1" />
              {{ stripHtml(form.content).length }} کاراکتر
            </span>
              </div>

              <Message v-if="!form.content" severity="warn" :closable="false">
                <i class="pi pi-exclamation-triangle ml-2" />
                ثبت متن قرارداد اختیاری است اما برای شفافیت بیشتر توصیه می‌شود.
              </Message>
            </div>
          </template>
        </Card>

        <!-- ═══════ مرحله ۲: آیتم‌های قرارداد ═══════ -->
        <div v-if="currentStep === 2" class="form-step">
          <div class="step-card">
            <div class="card-header">
              <div class="card-title-wrapper">
                <div class="card-title-icon green">
                  <i class="pi pi-list"></i>
                </div>
                <div>
                  <h2 class="card-title">فهرست بها / آیتم‌های قرارداد</h2>
                  <p class="card-subtitle">
                    آیتم‌ها، مقادیر و قیمت واحد هر بخش را وارد کنید
                  </p>
                </div>
              </div>
            </div>

            <div class="form-body">
              <ContractItemsTable v-model="form.items" :readonly="false" />
              <p v-if="errors.items" class="field-error items-error">
                <i class="pi pi-exclamation-circle"></i>
                {{ errors.items }}
              </p>
            </div>
          </div>
        </div>

        <!-- ═══════ مرحله ۳: تایید نهایی ═══════ -->
        <div v-if="currentStep === 3" class="form-step">
          <div class="step-card">
            <div class="card-header">
              <div class="card-title-wrapper">
                <div class="card-title-icon gold">
                  <i class="pi pi-check-circle"></i>
                </div>
                <div>
                  <h2 class="card-title">تایید نهایی قرارداد</h2>
                  <p class="card-subtitle">
                    اطلاعات وارد شده را بررسی و در صورت صحت، قرارداد را ثبت کنید
                  </p>
                </div>
              </div>
            </div>

            <div class="form-body">
              <!-- خلاصه اطلاعات -->
              <div class="summary-grid">
                <div class="summary-card">
                  <div class="summary-icon blue">
                    <i class="pi pi-file"></i>
                  </div>
                  <div class="summary-content">
                    <span class="summary-label">موضوع قرارداد</span>
                    <span class="summary-value">{{ form.subject }}</span>
                  </div>
                </div>

                <div class="summary-card">
                  <div class="summary-icon green">
                    <i class="pi pi-tags"></i>
                  </div>
                  <div class="summary-content">
                    <span class="summary-label">نوع قرارداد</span>
                    <span class="summary-value">
                  {{ typeOptions.find(t => t.value === form.type)?.label }}
                </span>
                  </div>
                </div>

                <div class="summary-card">
                  <div class="summary-icon blue">
                    <i class="pi pi-users"></i>
                  </div>
                  <div class="summary-content">
                    <span class="summary-label">طرف قرارداد</span>
                    <span class="summary-value">{{ selectedParty?.name || '—' }}</span>
                  </div>
                </div>

                <div class="summary-card">
                  <div class="summary-icon green">
                    <i class="pi pi-calendar"></i>
                  </div>
                  <div class="summary-content">
                    <span class="summary-label">مدت قرارداد</span>
                    <span class="summary-value">
                  <DateDisplay :date="form.start_date" /> تا <DateDisplay :date="form.end_date" />
                </span>
                  </div>
                </div>

                <div class="summary-card">
                  <div class="summary-icon gold">
                    <i class="pi pi-list"></i>
                  </div>
                  <div class="summary-content">
                    <span class="summary-label">تعداد آیتم‌ها</span>
                    <span class="summary-value">{{ form.items.length }} آیتم</span>
                  </div>
                </div>

                <div class="summary-card">
                  <div class="summary-icon blue">
                    <i class="pi pi-dollar"></i>
                  </div>
                  <div class="summary-content">
                    <span class="summary-label">مبلغ کل</span>
                    <span class="summary-value amount">
                  <AmountDisplay :amount="totalAmount" unit="smart" />
                </span>
                  </div>
                </div>
              </div>

              <!-- پیام راهنما -->
              <div class="info-banner">
                <div class="info-icon">
                  <i class="pi pi-info-circle"></i>
                </div>
                <div class="info-content">
                  <p class="info-title">توجه</p>
                  <p class="info-text">
                    پس از ثبت، قرارداد در وضعیت <strong>«پیش‌نویس»</strong> قرار می‌گیرد
                    و می‌توانید آن را ویرایش یا برای تایید ارسال کنید.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══════ دکمه‌های ناوبری ═══════ -->
        <div class="form-navigation">
          <Button
              v-if="currentStep > 0"
              label="مرحله قبل"
              icon="pi pi-arrow-right"
              class="nav-btn prev-btn"
              @click="prevStep"
          />
          <div v-else class="nav-spacer"></div>

          <div class="nav-actions">
            <Button
                label="انصراف"
                icon="pi pi-times"
                class="nav-btn cancel-nav-btn"
                text
                @click="router.push('/contracts')"
            />
            <Button
                v-if="currentStep < steps.length - 1"
                label="مرحله بعد"
                icon="pi pi-arrow-left"
                icon-pos="left"
                class="nav-btn next-btn"
                @click="nextStep"
            />
            <Button
                v-else
                type="submit"
                label="ثبت قرارداد"
                icon="pi pi-check"
                :loading="loading"
                class="nav-btn submit-btn"
            />
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted , watch} from 'vue'
import { useRouter } from 'vue-router'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Editor from 'primevue/editor'
import { useContractStore } from '@/stores/contractStore'
import { usePartyStore } from '@/stores/partyStore'
import { useContract } from '@/composables/useContract'
import { usePermission } from '@/composables/usePermission'
import PersianDatePicker from '@/components/contract/PersianDatePicker.vue'
import AmountInput from '@/components/contract/AmountInput.vue'
import ContractItemsTable from '@/components/contract/ContractItemsTable.vue'
import DateDisplay from '@/components/contract/DateDisplay.vue'
import AmountDisplay from '@/components/contract/AmountDisplay.vue'
import { showToast } from '@/plugins/toast'

const router = useRouter()
const contractStore = useContractStore()
const partyStore = usePartyStore()
const { typeOptions } = useContract()
const { can } = usePermission()

const currentStep = ref(0)
const loading = ref(false)
const errors = ref({})

const steps = [
  { label: 'اطلاعات پایه', icon: 'pi pi-info-circle' },
  { label: 'متن قرارداد', icon: 'pi pi-file-edit' },  // ✅ جدید
  { label: 'آیتم‌های قرارداد', icon: 'pi pi-list' },
  { label: 'تایید نهایی', icon: 'pi pi-check-circle' },

]

const form = ref({
  subject: '',
  party_id: null,
  type: 'downstream',
  start_date: null,
  end_date: null,
  amount: null,
  currency: 'IRR',
  description: '',
  content: '',
  items: [],
})

// فهرست پیمانکاران دریافتی از Store
// برای سازگاری با پاسخ‌های متفاوت API، نام و شناسه را یکدست می‌کنیم.
const getPartyId = (party) =>
    party?.id ??
    party?.party_id ??
    party?.partyId ??
    party?.PartyID ??
    party?.PartyRef ??
    party?.contractor_id ??
    party?.ContractorID ??
    null

const partyFilterFields = [
  'name',
  'national_id',
  'national_code',
  'nationalId',
  'nationalCode',
  'code',
  'title',
  'Title',
  'Name',
]

const selectedPartyId = ref(null)

const partySuggestions = computed(
    () => partyStore.contractors ?? []
)

const selectedParty = computed(() =>
    partySuggestions.value.find(
        party => String(party.id) === String(form.value.party_id)
    ) ?? null
)
onMounted(async () => {
  try {
    const contractors = await partyStore.fetchContractors()

    if (form.value.party_id != null) {
      selectedPartyId.value =
          contractors.find(
              party =>
                  String(party.id) === String(form.value.party_id)
          )?.id ?? null
    }
  } catch (err) {
    console.error('خطا در دریافت پیمانکاران:', err)
  }
})



onMounted(async () => {
  try {
    await partyStore.fetchContractors()
  } catch (error) {
    console.error('خطا در دریافت فهرست پیمانکاران:', error)
    showToast({
      severity: 'error',
      summary: 'خطا در دریافت پیمانکاران',
      detail: 'فهرست طرف‌های قرارداد دریافت نشد.',
      life: 5000,
    })
  }
})



const totalAmount = computed(() => {
  return form.value.items.reduce((sum, item) => {
    return sum + (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)
  }, 0)
})

const progressWidth = computed(() => {
  const lastStepIndex = steps.length - 1
  return `${(currentStep.value / lastStepIndex) * 100}%`
})

// تابع کمکی برای محاسبه طول متن بدون HTML
const stripHtml = (html) => {
  if (!html) return ''
  const doc = new DOMParser().parseFromString(html, 'text/html')
  return doc.body.textContent || ''
}

// تغییر validateStep برای مرحله متن
const validateStep = (step) => {
  errors.value = {}

  if (step === 0) {
    if (!form.value.subject) errors.value.subject = 'موضوع الزامی است'
    if (!form.value.party_id) errors.value.party_id = 'طرف قرارداد الزامی است'
    if (!form.value.start_date) errors.value.start_date = 'تاریخ شروع الزامی است'
    if (!form.value.end_date) errors.value.end_date = 'تاریخ پایان الزامی است'
  }

  if (step === 2) {
    if (!form.value.items.length) {
      errors.value.items = 'حداقل یک آیتم باید ثبت شود'
    }
  }

  return Object.keys(errors.value).length === 0
}

const nextStep = () => {
  if (validateStep(currentStep.value)) {
    currentStep.value++
  }
}

const prevStep = () => {
  currentStep.value--
}

const goToStep = (idx) => {
  if (idx < currentStep.value) {
    currentStep.value = idx
  }
}

const handleSubmit = async () => {
  if (!validateStep(0)) {
    currentStep.value = 0
    return
  }

  if (!validateStep(2)) {
    currentStep.value = 2
    return
  }

  loading.value = true
  try {
    const data = {
      ...form.value,
      amount: totalAmount.value,
    }

    await contractStore.createContract(data)
    showToast({ severity: 'success', summary: 'قرارداد ایجاد شد', life: 3000 })
    router.push('/contracts')
  } catch (err) {
    showToast({ severity: 'error', summary: 'خطا', detail: err.message, life: 5000 })
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* ═══════ توکن‌های رنگ (هماهنگ با سایر صفحات) ═══════ */
:root,
.create-page {
  --accent: oklch(45% 0.15 250);
  --accent-dark: oklch(38% 0.17 250);
  --accent-soft: oklch(45% 0.15 250 / 0.10);
  --accent-ring: oklch(45% 0.15 250 / 0.14);
  --gold: oklch(55% 0.15 155);
  --gold-dark: oklch(48% 0.16 155);
  --gold-soft: oklch(55% 0.15 155 / 0.10);
  --surface: oklch(99% 0.004 250);
  --bg: oklch(97.5% 0.008 250);
  --border: oklch(88% 0.008 250);
  --text-main: oklch(15% 0.01 250);
  --text-muted: oklch(45% 0.012 250);
  --text-subtle: oklch(65% 0.008 250);
  --danger: oklch(50% 0.20 25);
  --danger-bg: oklch(97% 0.04 25);
  --danger-border: oklch(85% 0.08 25);
  --success: oklch(42% 0.14 145);
  --success-bg: oklch(97% 0.04 145);
  --success-border:oklch(82% 0.10 145);
  --info: oklch(55% 0.12 220);
  --info-bg: oklch(97% 0.04 220);
  --info-border: oklch(85% 0.08 220);
}

/* ═══════ صفحه ═══════ */
.create-page {
  min-height: 100dvh;
  direction: rtl;
  background-color: var(--bg);
  position: relative;
  padding: 24px;
  overflow-x: hidden;
}

/* ═══════ پس‌زمینه تزئینی ═══════ */
.page-background {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  overflow: hidden;
}

.bg-shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
}

.shape-one {
  width: 500px;
  height: 500px;
  top: -150px;
  right: -150px;
  background: oklch(45% 0.15 250 / 0.08);
}

.shape-two {
  width: 450px;
  height: 450px;
  bottom: -150px;
  left: -150px;
  background: oklch(55% 0.15 155 / 0.07);
}

.shape-three {
  width: 300px;
  height: 300px;
  top: 40%;
  left: 40%;
  background: oklch(50% 0.12 200 / 0.05);
}

.bg-grid {
  position: absolute;
  inset: 0;
  background-image:
      linear-gradient(oklch(58% 0.008 250 / 0.05) 1px, transparent 1px),
      linear-gradient(90deg, oklch(58% 0.008 250 / 0.05) 1px, transparent 1px);
  background-size: 50px 50px;
  mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 30%, transparent 80%);
}

.floating-icons {
  position: absolute;
  inset: 0;
}

.floating-icon {
  position: absolute;
  font-size: 32px;
  opacity: 0.06;
  color: var(--accent);
  animation: float 10s ease-in-out infinite;
}

.icon-1 { top: 10%; left: 8%; animation-delay: 0s; }
.icon-2 { top: 65%; left: 12%; animation-delay: 2s; color: var(--gold); }
.icon-3 { top: 15%; right: 10%; animation-delay: 4s; }
.icon-4 { bottom: 20%; right: 8%; animation-delay: 1s; color: var(--gold); }
.icon-5 { top: 45%; left: 3%; animation-delay: 3s; }
.icon-6 { top: 30%; right: 15%; animation-delay: 2.5s; color: var(--gold); }

@keyframes float {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-25px) rotate(5deg); }
}

/* ═══════ پوسته ═══════ */
.create-shell {
  position: relative;
  z-index: 1;
  max-width: 1200px;
  margin: 0 auto;
}

/* ═══════ هدر ═══════ */
.page-header {
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  border-radius: 20px;
  padding: 28px 32px;
  margin-bottom: 24px;
  box-shadow:
      0 12px 30px oklch(45% 0.15 250 / 0.25),
      0 0 0 1px oklch(95% 0.004 250 / 0.1);
  position: relative;
  overflow: hidden;
}

.page-header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, var(--gold) 0%, transparent 70%);
  opacity: 0.15;
  filter: blur(40px);
}

.page-header::after {
  content: '';
  position: absolute;
  bottom: -50%;
  left: -10%;
  width: 250px;
  height: 250px;
  background: radial-gradient(circle, var(--gold) 0%, transparent 70%);
  opacity: 0.10;
  filter: blur(40px);
}

.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
  position: relative;
  z-index: 1;
}

.header-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: oklch(100% 0 0 / 0.15);
  backdrop-filter: blur(10px);
  color: #fff;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 11.5px;
  font-weight: 600;
  margin-bottom: 10px;
  border: 1px solid oklch(100% 0 0 / 0.2);
}

.header-badge i {
  font-size: 12px;
}

.header-title {
  font-size: 24px;
  font-weight: 800;
  color: #fff;
  margin: 0 0 6px;
  letter-spacing: -0.01em;
}

.header-subtitle {
  font-size: 13px;
  color: oklch(100% 0 0 / 0.85);
  margin: 0;
  line-height: 1.7;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.cancel-btn {
  background: oklch(100% 0 0 / 0.15) !important;
  backdrop-filter: blur(10px);
  border: 1px solid oklch(100% 0 0 / 0.2) !important;
  color: #fff !important;
  font-weight: 600 !important;
  border-radius: 10px !important;
}

.cancel-btn:hover {
  background: oklch(100% 0 0 / 0.25) !important;
}

.cancel-btn :deep(.p-button-icon) {
  color: #fff !important;
}

/* ═══════ استپر ═══════ */
.stepper-section {
  margin-bottom: 24px;
}

.stepper-card {
  background: oklch(99.5% 0.003 250 / 0.97);
  border: 1px solid oklch(90% 0.006 250 / 0.9);
  border-radius: 16px;
  padding: 24px 32px;
  box-shadow:
      0 8px 24px oklch(15% 0.01 250 / 0.06),
      0 0 0 1px oklch(95% 0.004 250 / 0.5);
  backdrop-filter: blur(10px);
}

.stepper-track {
  position: relative;
  height: 3px;
  background: var(--border);
  border-radius: 3px;
  margin-bottom: 20px;
  overflow: hidden;
}

.stepper-progress {
  height: 100%;
  background: linear-gradient(90deg, var(--accent), var(--gold));
  transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 3px;
}

.stepper-steps {
  display: flex;
  justify-content: space-between;
  gap: 16px;
}

.step-item {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 12px;
  background: var(--surface);
  border: 1px solid var(--border);
  cursor: pointer;
  transition: all 0.2s;
}

.step-item:hover:not(.active) {
  border-color: oklch(80% 0.010 250);
  background: oklch(98.5% 0.005 250);
}

.step-item.active {
  background: linear-gradient(135deg, var(--accent-soft), var(--gold-soft));
  border-color: var(--accent);
  box-shadow: 0 4px 12px oklch(45% 0.15 250 / 0.15);
}

.step-item.completed {
  background: var(--success-bg);
  border-color: var(--success-border);
}

.step-circle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--surface);
  border: 2px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  color: var(--text-muted);
  flex-shrink: 0;
  transition: all 0.2s;
}

.step-item.active .step-circle {
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  border-color: var(--accent);
  color: #fff;
  box-shadow: 0 4px 12px oklch(45% 0.15 250 / 0.3);
}

.step-item.completed .step-circle {
  background: linear-gradient(135deg, var(--success), oklch(35% 0.15 145));
  border-color: var(--success);
  color: #fff;
  box-shadow: 0 4px 12px oklch(42% 0.14 145 / 0.3);
}

.step-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.step-number {
  font-size: 10.5px;
  color: var(--text-subtle);
  font-weight: 500;
}

.step-label {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-main);
}

.step-item.active .step-label {
  color: var(--accent);
}

.step-item.completed .step-label {
  color: var(--success);
}

/* ═══════ فرم ═══════ */
.create-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.form-step {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.step-card {
  background: oklch(99.5% 0.003 250 / 0.97);
  border: 1px solid oklch(90% 0.006 250 / 0.9);
  border-radius: 16px;
  overflow: hidden;
  box-shadow:
      0 8px 24px oklch(15% 0.01 250 / 0.06),
      0 0 0 1px oklch(95% 0.004 250 / 0.5);
  backdrop-filter: blur(10px);
}

.card-header {
  padding: 20px 24px;
  background: linear-gradient(135deg, oklch(98% 0.005 250), oklch(97% 0.008 250));
  border-bottom: 1px solid var(--border);
}

.card-title-wrapper {
  display: flex;
  align-items: center;
  gap: 14px;
}

.card-title-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  color: #fff;
  flex-shrink: 0;
}

.card-title-icon.blue {
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  box-shadow: 0 4px 12px oklch(45% 0.15 250 / 0.25);
}

.card-title-icon.green {
  background: linear-gradient(135deg, var(--gold), var(--gold-dark));
  box-shadow: 0 4px 12px oklch(55% 0.15 155 / 0.25);
}

.card-title-icon.gold {
  background: linear-gradient(135deg, oklch(65% 0.15 75), oklch(58% 0.16 75));
  box-shadow: 0 4px 12px oklch(65% 0.15 75 / 0.25);
}

.card-title {
  font-size: 16px;
  font-weight: 800;
  color: var(--text-main);
  margin: 0 0 2px;
}

.card-subtitle {
  font-size: 12px;
  color: var(--text-muted);
  margin: 0;
}

.form-body {
  padding: 24px;
}

/* ═══════ فیلدها ═══════ */
.field-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
}

.field-group.full-width {
  width: 100%;
}

.field-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 16px;
}

.field-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 700;
  color: var(--text-main);
}

.field-icon {
  font-size: 13px;
  color: var(--accent);
}

.required {
  color: var(--danger);
  margin-right: 2px;
}

.form-input.p-inputtext,
.form-select.p-select,
.form-autocomplete.p-autocomplete,
.form-textarea.p-textarea {
  width: 100% !important;
  min-height: 46px !important;
  border: 1px solid var(--border) !important;
  border-radius: 10px !important;
  font-size: 13.5px !important;
  color: var(--text-main) !important;
  padding: 0 14px !important;
  background: var(--surface) !important;
  transition: border-color 0.18s, box-shadow 0.18s !important;
}

.form-textarea.p-textarea {
  padding: 12px 14px !important;
  min-height: 100px !important;
  resize: vertical !important;
}

.form-input.p-inputtext:hover,
.form-select.p-select:hover,
.form-autocomplete.p-autocomplete:hover,
.form-textarea.p-textarea:hover {
  border-color: oklch(75% 0.010 250) !important;
}

.form-input.p-inputtext:focus,
.form-select.p-select:focus,
.form-autocomplete.p-autocomplete:focus,
.form-textarea.p-textarea:focus {
  border-color: var(--accent) !important;
  box-shadow: 0 0 0 3px var(--accent-ring) !important;
  outline: none !important;
}

.form-input.p-inputtext::placeholder,
.form-textarea.p-textarea::placeholder {
  color: var(--text-subtle) !important;
}

.form-input.p-inputtext.p-invalid,
.form-select.p-invalid,
.form-autocomplete.p-invalid {
  border-color: var(--danger) !important;
}

.field-error {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11.5px;
  color: var(--danger);
  margin-top: 2px;
}

.field-error i {
  font-size: 11px;
}

.items-error {
  margin-top: 12px;
  padding: 10px 14px;
  background: var(--danger-bg);
  border: 1px solid var(--danger-border);
  border-radius: 10px;
}

/* ═══════ خلاصه مرحله ۳ ═══════ */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 14px;
  margin-bottom: 20px;
}

.summary-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  background: linear-gradient(135deg, oklch(98% 0.005 250), oklch(97% 0.008 250));
  border: 1px solid var(--border);
  border-radius: 12px;
  transition: border-color 0.2s, transform 0.2s;
}

.summary-card:hover {
  border-color: oklch(80% 0.010 250);
  transform: translateX(-2px);
}

.summary-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  color: #fff;
  flex-shrink: 0;
}

.summary-icon.blue {
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  box-shadow: 0 4px 10px oklch(45% 0.15 250 / 0.2);
}

.summary-icon.green {
  background: linear-gradient(135deg, var(--gold), var(--gold-dark));
  box-shadow: 0 4px 10px oklch(55% 0.15 155 / 0.2);
}

.summary-icon.gold {
  background: linear-gradient(135deg, oklch(65% 0.15 75), oklch(58% 0.16 75));
  box-shadow: 0 4px 10px oklch(65% 0.15 75 / 0.2);
}

.summary-content {
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
  flex: 1;
}

.summary-label {
  font-size: 11.5px;
  color: var(--text-subtle);
  font-weight: 500;
}

.summary-value {
  font-size: 13.5px;
  font-weight: 700;
  color: var(--text-main);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.summary-value.amount {
  color: var(--gold-dark);
  font-variant-numeric: tabular-nums;
  direction: ltr;
  text-align: right;
}

/* ═══════ بنر اطلاعات ═══════ */
.info-banner {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: linear-gradient(135deg, var(--info-bg), oklch(98% 0.02 220));
  border: 1px solid var(--info-border);
  border-radius: 12px;
}

.info-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--info);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  flex-shrink: 0;
  box-shadow: 0 4px 10px oklch(55% 0.12 220 / 0.25);
}

.info-content {
  flex: 1;
}

.info-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--info);
  margin: 0 0 4px;
}

.info-text {
  font-size: 12.5px;
  color: var(--text-muted);
  margin: 0;
  line-height: 1.7;
}

.info-text strong {
  color: var(--text-main);
  font-weight: 700;
}

/* ═══════ ناوبری ═══════ */
.form-navigation {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 20px 24px;
  background: oklch(99.5% 0.003 250 / 0.97);
  border: 1px solid oklch(90% 0.006 250 / 0.9);
  border-radius: 16px;
  box-shadow:
      0 8px 24px oklch(15% 0.01 250 / 0.06),
      0 0 0 1px oklch(95% 0.004 250 / 0.5);
  backdrop-filter: blur(10px);
}

.nav-spacer {
  flex: 1;
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.nav-btn {
  min-height: 44px !important;
  border-radius: 10px !important;
  font-weight: 600 !important;
  font-size: 13px !important;
}

.prev-btn {
  background: var(--surface) !important;
  color: var(--text-main) !important;
  border: 1px solid var(--border) !important;
}

.prev-btn:hover {
  background: var(--bg) !important;
  border-color: var(--accent) !important;
  color: var(--accent) !important;
}

.cancel-nav-btn {
  color: var(--text-muted) !important;
}

.cancel-nav-btn:hover {
  color: var(--danger) !important;
  background: var(--danger-bg) !important;
}

.next-btn {
  background: linear-gradient(135deg, var(--accent), var(--accent-dark)) !important;
  color: #fff !important;
  border: none !important;
  box-shadow: 0 4px 12px oklch(45% 0.15 250 / 0.25) !important;
}

.next-btn:hover {
  background: linear-gradient(135deg, var(--accent-dark), var(--accent)) !important;
  transform: translateY(-1px);
  box-shadow: 0 6px 16px oklch(45% 0.15 250 / 0.35) !important;
}

.submit-btn {
  background: linear-gradient(135deg, var(--gold), var(--gold-dark)) !important;
  color: #fff !important;
  border: none !important;
  box-shadow: 0 4px 12px oklch(55% 0.15 155 / 0.25) !important;
}

.submit-btn:hover {
  background: linear-gradient(135deg, var(--gold-dark), var(--gold)) !important;
  transform: translateY(-1px);
  box-shadow: 0 6px 16px oklch(55% 0.15 155 / 0.35) !important;
}

/* Editor styles: PrimeVue Editor / Quill */
.contract-editor {
  width: 100%;
  direction: rtl;
  border: 1px solid var(--border);
  border-radius: 10px;
  overflow: hidden;
  background: var(--surface);
}

.contract-editor :deep(.p-editor-toolbar) {
  direction: rtl;
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  border: 0;
  border-bottom: 1px solid var(--border);
  background: var(--bg);
  padding: 8px;
}

.contract-editor :deep(.p-editor-toolbar .ql-formats) {
  margin: 2px 4px;
}

.contract-editor :deep(.p-editor-content) {
  border: 0;
}

.contract-editor :deep(.ql-editor) {
  min-height: 500px;
  direction: rtl;
  text-align: right;
  font-family: Tahoma, Arial, sans-serif;
  font-size: 14px;
  line-height: 2;
  color: var(--text-main);
  padding: 20px;
}

.contract-editor :deep(.ql-editor.ql-blank::before) {
  right: 20px;
  left: auto;
  text-align: right;
  font-style: normal;
  color: var(--text-subtle);
  content: 'متن قرارداد را اینجا وارد کنید...';
}

.contract-editor :deep(.ql-editor ol),
.contract-editor :deep(.ql-editor ul) {
  padding-right: 1.5em;
  padding-left: 0;
}

.contract-editor :deep(.ql-editor a) {
  color: var(--accent);
  text-decoration: underline;
}

/* ═══════ ریسپانسیو ═══════ */
@media (max-width: 1024px) {
  .create-page {
    padding: 16px;
  }

  .page-header {
    padding: 22px 20px;
    border-radius: 16px;
  }

  .header-title {
    font-size: 20px;
  }

  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .stepper-card {
    padding: 20px;
  }

  .stepper-steps {
    flex-direction: column;
    gap: 10px;
  }

  .step-item {
    width: 100%;
  }

  .field-row {
    grid-template-columns: 1fr;
  }

  .summary-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .floating-icon {
    font-size: 24px;
  }

  .form-body {
    padding: 16px;
  }

  .card-header {
    padding: 16px;
  }

  .form-navigation {
    flex-direction: column;
    gap: 10px;
  }

  .nav-actions {
    width: 100%;
    flex-direction: column;
  }

  .nav-btn {
    width: 100%;
  }
}

@media (max-width: 560px) {
  .page-header {
    padding: 18px 16px;
  }

  .header-title {
    font-size: 18px;
  }

  .header-subtitle {
    font-size: 12px;
  }

  .step-circle {
    width: 38px;
    height: 38px;
    font-size: 14px;
  }

  .step-label {
    font-size: 12px;
  }
}

@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    transition-duration: 0.01ms !important;
    animation-duration: 0.01ms !important;
  }
}
</style>