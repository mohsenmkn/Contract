<template>
  <main class="contract-page" dir="rtl">
    <div class="page-background" aria-hidden="true">
      <div class="bg-shape shape-one"></div>
      <div class="bg-shape shape-two"></div>
      <div class="bg-shape shape-three"></div>
      <div class="bg-grid"></div>
      <div class="floating-icons">
        <i class="pi pi-file-edit floating-icon icon-1"></i>
        <i class="pi pi-handshake floating-icon icon-2"></i>
        <i class="pi pi-list floating-icon icon-3"></i>
        <i class="pi pi-check-circle floating-icon icon-4"></i>
        <i class="pi pi-calendar floating-icon icon-5"></i>
        <i class="pi pi-users floating-icon icon-6"></i>
      </div>
    </div>

    <div class="page-container">
      <header class="hero-card">
        <div class="hero-topline">
          <div class="hero-mark"><i class="pi pi-file-edit"></i></div>
          <div class="hero-copy">
            <span class="eyebrow">{{ isEdit ? 'مدیریت قراردادها / ویرایش پیش‌نویس' : 'مدیریت قراردادها / ثبت درخواست' }}</span>
            <h1>{{ isEdit ? 'ویرایش درخواست قرارداد' : 'ایجاد درخواست قرارداد' }}</h1>
            <p>اطلاعات قرارداد را مرحله‌به‌مرحله تکمیل کنید و پیش از ارسال، خلاصه نهایی را بررسی کنید.</p>
          </div>
          <Button label="بازگشت به فهرست" icon="pi pi-arrow-right" severity="secondary" outlined class="back-button" @click="router.push('/contract-requests')" />
        </div>
        <div v-if="myUnit" class="unit-chip">
          <i class="pi pi-building"></i>
          <span>واحد درخواست‌کننده</span>
          <strong>{{ myUnit.name }}</strong>
          <span class="unit-code">کد {{ myUnit.code }}</span>
        </div>
      </header>

      <section class="workflow-card" aria-label="مراحل ثبت قرارداد">
        <div class="workflow-heading">
          <div><span class="section-kicker">فرآیند ثبت</span><h2>تکمیل درخواست</h2></div>
          <span class="step-counter">مرحله {{ currentStep + 1 }} از {{ steps.length }}</span>
        </div>
        <div class="progress-track"><div class="progress-value" :style="{ width: progressWidth }"></div></div>
        <nav class="step-list">
          <button v-for="(step, index) in steps" :key="step.title" type="button" class="step-button"
                  :class="{ active: currentStep === index, completed: currentStep > index }"
                  @click="index < currentStep ? currentStep = index : null">
            <span class="step-icon"><i :class="currentStep > index ? 'pi pi-check' : step.icon"></i></span>
            <span class="step-text"><small>مرحله {{ index + 1 }}</small><strong>{{ step.title }}</strong></span>
          </button>
        </nav>
      </section>

      <div v-if="loadingContract" class="loading-card"><i class="pi pi-spin pi-spinner"></i><span>در حال دریافت اطلاعات قرارداد...</span></div>

      <section v-else class="content-card">
        <div v-if="currentStep === 0" class="step-content">
          <div class="content-heading">
            <div class="heading-icon"><i class="pi pi-clipboard"></i></div>
            <div><h2>اطلاعات پایه قرارداد</h2><p>مشخصات اصلی قرارداد و طرف قرارداد را وارد کنید.</p></div>
          </div>
          <div class="form-grid">
            <div class="field full-span">
              <label for="contract-subject">موضوع قرارداد <b>*</b></label>
              <InputText id="contract-subject" v-model="form.subject" :invalid="!!errors.subject" class="w-full" placeholder="مثلاً: تأمین و پشتیبانی تجهیزات..." />
              <small v-if="errors.subject" class="error-text">{{ errors.subject }}</small>
            </div>
            <div class="field">
              <label>نوع قرارداد <b>*</b></label>
              <Select v-model="form.type" :options="typeOptions" option-label="label" option-value="value" :invalid="!!errors.type" placeholder="نوع قرارداد را انتخاب کنید" class="w-full" />
              <small v-if="errors.type" class="error-text">{{ errors.type }}</small>
            </div>
            <div class="field full-span framework-toggle">
              <label>آیا این قرارداد پیمانکاری باید در چهارچوب یک قرارداد کارفرمایی باشد؟</label>
              <div class="guarantee-control">
                <ToggleSwitch v-model="withinEmployerFramework" @update:model-value="handleFrameworkToggle" />
                <span>{{ withinEmployerFramework ? 'بله، ردیف‌ها و مبالغ به قرارداد کارفرمایی متصل می‌شوند' : 'خیر، قرارداد مستقل است' }}</span>
              </div>
              <div v-if="withinEmployerFramework" class="framework-parent-picker">
                <label>قرارداد کارفرمایی مبنا <b>*</b></label>
                <Select
                  v-model="form.parent_contract_id"
                  :options="employerContractOptions"
                  option-label="label"
                  option-value="id"
                  :loading="loadingEmployerContracts || loadingEmployerItems"
                  :invalid="!!errors.parent_contract_id"
                  placeholder="قرارداد کارفرمایی فعال را انتخاب کنید"
                  class="w-full"
                  @change="loadEmployerContractItems(form.parent_contract_id)"
                />
                <small v-if="errors.parent_contract_id" class="error-text">{{ errors.parent_contract_id }}</small>
                <small v-else class="muted-note">برای هر ردیف، ردیف متناظر را انتخاب کنید؛ مقدار و بهای واحد از سقف باقیمانده قرارداد کارفرمایی کنترل می‌شود.</small>
              </div>
            </div>
            <div class="field">
              <label>طرف قرارداد / پیمانکار <b>*</b></label>
              <div class="search-field"><i class="pi pi-search"></i><InputText v-model="partySearch" class="w-full" placeholder="جستجو بر اساس نام یا شناسه ملی" @input="searchParties" /></div>
              <div v-if="partySuggestions.length" class="party-results">
                <button v-for="party in partySuggestions" :key="party.id" type="button" class="party-result" @click="selectParty(party)">
                  <span class="party-result-icon"><i class="pi pi-building"></i></span><span class="party-result-copy"><strong>{{ party.name }}</strong><small dir="ltr">{{ party.national_id }}</small></span><i class="pi pi-chevron-left result-arrow"></i>
                </button>
              </div>
              <div v-if="selectedParty" class="selected-party">
                <span class="selected-check"><i class="pi pi-check"></i></span><div><strong>{{ selectedParty.name }}</strong><small dir="ltr">{{ selectedParty.national_id }}</small></div>
                <button type="button" aria-label="حذف طرف قرارداد" @click="form.party_id = null; selectedParty = null"><i class="pi pi-times"></i></button>
              </div>
              <small v-if="errors.party_id" class="error-text">{{ errors.party_id }}</small>
            </div>
            <div class="field">
              <PersianDatePicker
                  v-model="form.start_date"
                  label="تاریخ شروع"
                  :required="true"
                  :invalid="!!errors.start_date"
                  icon="pi pi-calendar-plus"
              />
              <small v-if="errors.start_date" class="error-text">{{ errors.start_date }}</small>
            </div>
            <div class="field">
              <PersianDatePicker
                  v-model="form.end_date"
                  label="تاریخ پایان"
                  :required="true"
                  :invalid="!!errors.end_date"
                  icon="pi pi-calendar-times"
              />
              <small v-if="errors.end_date" class="error-text">{{ errors.end_date }}</small>
            </div>
            <div class="field full-span">
              <label>ضمانت قرارداد</label>
              <p class="text-sm text-slate-600">نیاز و نوع ضمانت پس از بررسی توسط واحد حقوقی و قراردادها تعیین می‌شود.</p>
            </div>
            <div class="field full-span">
              <label>توضیحات تکمیلی <span class="optional-label">اختیاری</span></label>
              <Textarea v-model="form.description" rows="3" auto-resize class="w-full" placeholder="در صورت نیاز، توضیحات یا نکات مهم قرارداد را درج کنید..." />
            </div>
          </div>
        </div>

        <div v-else-if="currentStep === 1" class="step-content">
          <div class="content-heading">
            <div class="heading-icon green"><i class="pi pi-file-edit"></i></div>
            <div><h2>متن پیش‌نویس قرارداد</h2><p>متن را با قالب‌بندی فارسی و راست‌چین وارد یا ویرایش کنید.</p></div>
          </div>
          <div class="editor-notice"><i class="pi pi-info-circle"></i><p><strong>راهنمای نگارش</strong><span>برای متن فارسی از راست‌چین، فهرست‌ها و تیترها استفاده کنید. متن ثبت‌شده برای بررسی و تکمیل به واحد حقوقی ارجاع می‌شود.</span></p></div>
          <div class="editor-meta"><span><i class="pi pi-language"></i> زبان نوشتار: فارسی</span><span><i class="pi pi-align-right"></i> جهت متن: راست‌به‌چپ</span><span><i class="pi pi-file"></i> حدود {{ pagesCount }} صفحه</span></div>
          <div class="contract-editor" dir="rtl">
            <Editor
                v-model="form.content"
                editorStyle="height: 500px"
                class="request-rich-editor"
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
          <div class="editor-footer"><span>{{ stripHtml(form.content).length.toLocaleString('fa-IR') }} نویسه</span><span v-if="!form.content" class="muted-note">متن قرارداد اختیاری است؛ با این حال ثبت آن به بررسی سریع‌تر کمک می‌کند.</span><span v-else class="success-note"><i class="pi pi-check-circle"></i> متن پیش‌نویس وارد شده است</span></div>
        </div>

        <div v-else-if="currentStep === 2" class="step-content">
          <div class="content-heading">
            <div class="heading-icon"><i class="pi pi-list-check"></i></div>
            <div><h2>آیتم‌ها و مبالغ قرارداد</h2><p>اقلام، خدمات یا فعالیت‌های مشمول قرارداد را ثبت کنید.</p></div>
            <Button label="افزودن آیتم" icon="pi pi-plus" @click="addItem" />
          </div>
          <div v-if="!form.items.length" class="empty-state"><span><i class="pi pi-inbox"></i></span><h3>هنوز آیتمی ثبت نشده است</h3><p>اگر قرارداد شامل اقلام یا ردیف‌های مالی است، از دکمه «افزودن آیتم» استفاده کنید.</p><Button label="افزودن اولین آیتم" icon="pi pi-plus" outlined @click="addItem" /></div>
          <DataTable v-else :value="form.items" class="items-table" responsive-layout="scroll" striped-rows>
            <Column header="ردیف" style="width: 65px"><template #body="{ index }"><span class="row-number">{{ (index + 1).toLocaleString('fa-IR') }}</span></template></Column>
            <Column v-if="withinEmployerFramework" header="ردیف کارفرمایی" style="min-width: 240px"><template #body="{ data, index }"><Select v-model="data.employer_contract_item_id" :options="employerContractItems" option-label="label" option-value="id" class="table-control" placeholder="ردیف مبنا را انتخاب کنید" @change="applyEmployerItem(data)" /><small v-if="errors[`items.${index}.employer_contract_item_id` ]" class="error-text">{{ errors[`items.${index}.employer_contract_item_id`] }}</small></template></Column>
            <Column header="شرح فعالیت" style="min-width: 210px"><template #body="{ data }"><InputText v-model="data.description" class="table-control" placeholder="شرح آیتم" /></template></Column>
            <Column header="کد آیتم" style="min-width: 120px"><template #body="{ data }"><InputText v-model="data.item_code" class="table-control" placeholder="کد" dir="ltr" /></template></Column>
            <Column header="واحد" style="min-width: 100px"><template #body="{ data }"><InputText v-model="data.unit" class="table-control" placeholder="واحد" /></template></Column>
            <Column header="مقدار" style="min-width: 120px"><template #body="{ data }"><InputNumber v-model="data.quantity" class="table-control" :min="0" :min-fraction-digits="0" :max-fraction-digits="2" placeholder="۰" /></template></Column>
            <Column header="بهای واحد (ریال)" style="min-width: 150px"><template #body="{ data }"><InputNumber v-model="data.unit_price" class="table-control" :min="0" :min-fraction-digits="0" :max-fraction-digits="0" placeholder="۰" /></template></Column>
            <Column header="مبلغ کل (ریال)" style="min-width: 140px"><template #body="{ data }"><strong class="item-total">{{ formatNumber(calculateItemTotal(data)) }}</strong></template></Column>
            <Column header="" style="width: 65px"><template #body="{ index }"><Button icon="pi pi-trash" severity="danger" text rounded aria-label="حذف آیتم" @click="removeItem(index)" /></template></Column>
          </DataTable>
          <div v-if="form.items.length" class="total-panel"><div><span class="total-icon"><i class="pi pi-calculator"></i></span><div><small>مبلغ کل برآوردی</small><strong>{{ formatNumber(totalAmount) }} <small>ریال</small></strong></div></div><span>{{ form.items.length.toLocaleString('fa-IR') }} ردیف</span></div>
          <p v-if="withinEmployerFramework && errors.items" class="error-text mt-3">{{ errorText(errors.items) }}</p>
        </div>

        <div v-else class="step-content">
          <div class="content-heading"><div class="heading-icon gold"><i class="pi pi-check-circle"></i></div><div><h2>بازبینی نهایی درخواست</h2><p>اطلاعات زیر را بررسی کنید؛ سپس درخواست را برای واحد حقوقی ارسال کنید.</p></div></div>
          <div class="review-grid">
            <article class="review-item"><span class="review-icon"><i class="pi pi-file"></i></span><div><small>موضوع قرارداد</small><strong>{{ form.subject || 'ثبت نشده' }}</strong></div></article>
            <article class="review-item"><span class="review-icon"><i class="pi pi-tags"></i></span><div><small>نوع قرارداد</small><strong>{{ typeOptions.find(t => t.value === form.type)?.label || '—' }}</strong></div></article>
            <article v-if="withinEmployerFramework" class="review-item full-span"><span class="review-icon"><i class="pi pi-sitemap"></i></span><div><small>قرارداد کارفرمایی مبنا</small><strong>{{ selectedEmployerContract?.label || 'انتخاب نشده' }}</strong></div></article>
            <article class="review-item"><span class="review-icon"><i class="pi pi-building"></i></span><div><small>طرف قرارداد</small><strong>{{ selectedParty?.name || 'انتخاب نشده' }}</strong></div></article>
            <article class="review-item"><span class="review-icon"><i class="pi pi-calendar"></i></span><div><small>بازه قرارداد</small><strong>{{ form.start_date ? formatDate(form.start_date) : '—' }} تا {{ form.end_date ? formatDate(form.end_date) : '—' }}</strong></div></article>
            <article class="review-item"><span class="review-icon"><i class="pi pi-shield"></i></span><div><small>ضمانت قرارداد</small><strong>پس از بررسی واحد حقوقی و قراردادها تعیین می‌شود</strong></div></article>
            <article class="review-item"><span class="review-icon"><i class="pi pi-list"></i></span><div><small>تعداد آیتم‌ها</small><strong>{{ form.items.length.toLocaleString('fa-IR') }} مورد</strong></div></article>
            <article class="review-item amount-review"><span class="review-icon"><i class="pi pi-wallet"></i></span><div><small>مبلغ کل قرارداد</small><strong>{{ formatNumber(totalAmount) }} ریال</strong></div></article>
            <article class="review-item full-span"><span class="review-icon"><i class="pi pi-file-edit"></i></span><div><small>متن پیش‌نویس</small><strong>{{ form.content ? `ثبت شده · حدود ${pagesCount} صفحه` : 'ثبت نشده (اختیاری)' }}</strong></div></article>
          </div>
          <div class="editor-notice final-notice"><i class="pi pi-send"></i><p><strong>پس از ارسال چه اتفاقی می‌افتد؟</strong><span>درخواست برای بررسی به واحد حقوقی و قراردادها ارجاع می‌شود. لطفاً پیش از ارسال، موضوع، طرف قرارداد، تاریخ‌ها و مبالغ را با دقت بررسی کنید.</span></p></div>
        </div>
      </section>

      <footer class="bottom-navigation">
        <Button v-if="currentStep > 0" label="مرحله قبل" icon="pi pi-arrow-right" severity="secondary" outlined @click="currentStep--" />
        <span v-else class="navigation-hint"><i class="pi pi-shield"></i> فیلدهای ستاره‌دار الزامی هستند</span>
        <div class="navigation-actions">
          <Button label="انصراف" severity="secondary" text @click="router.push('/contract-requests')" />
          <Button v-if="currentStep < 3" label="ادامه مرحله بعد" icon="pi pi-arrow-left" icon-pos="left" @click="nextStep" />
          <Button v-else :label="submitButtonLabel" icon="pi pi-send" :loading="submitting" @click="submitToLegal" />
        </div>
      </footer>
    </div>
  </main>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Editor from 'primevue/editor'
import { useToast } from 'primevue/usetoast'
import { useApiErrorHandler } from '@/composables/useApiErrorHandler'
import api from '@/api/axios.js'
import { useAuthStore } from '@/stores/auth.js'
import PersianDatePicker from "@/components/contract/PersianDatePicker.vue";

const router = useRouter()
const route = useRoute()
const toast = useToast()
const { handleApiError } = useApiErrorHandler()
const authStore = useAuthStore()

// ═══════ STATE ═══════
const currentStep = ref(0)
const submitting = ref(false)
const isEdit = ref(false)
const loadingContract = ref(false)
const myUnit = ref(null)
const withinEmployerFramework = ref(false)
const employerContracts = ref([])
const employerContractItems = ref([])
const loadingEmployerContracts = ref(false)
const loadingEmployerItems = ref(false)
const selectedEmployerContract = ref(null)

const steps = [
  { title: 'اطلاعات پایه', icon: 'pi pi-info-circle' },
  { title: 'متن قرارداد', icon: 'pi pi-file-edit' },
  { title: 'آیتم‌ها', icon: 'pi pi-list' },
  { title: 'تایید و ارسال', icon: 'pi pi-check-circle' },
]

const typeOptions = computed(() => [
  { label: 'پیمانکار', value: 'downstream' },
  ...(authStore.can('contracts.review') ? [{ label: 'کارفرما (بدون گردش کار)', value: 'upstream' }] : []),
])

const form = ref({
  id: null,
  subject: '',
  type: 'downstream',
  parent_contract_id: null,
  party_id: null,
  start_date: null,
  end_date: null,
  requires_guarantee: false,
  description: '',
  content: '',
  items: [],
})

const submitButtonLabel = computed(() => isEdit.value ? 'ذخیره تغییرات' : (form.value.type === 'upstream' ? 'ثبت قرارداد کارفرمایی' : 'ارسال به واحد حقوقی'))

const errors = ref({})
const partySearch = ref('')
const partySuggestions = ref([])
const partySearchTimeout = ref(null)
const selectedParty = ref(null)

const employerContractOptions = computed(() => employerContracts.value.map(contract => ({
  ...contract,
  label: `${contract.contract_number} — ${contract.subject}`,
})))

// ═══════ Computed ═══════
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
    return sum + qty * price
  }, 0)
})

// ═══════ Methods ═══════
const stripHtml = (html) => {
  if (!html) return ''
  const doc = new DOMParser().parseFromString(html, 'text/html')
  return doc.body.textContent || ''
}

const formatNumber = (num) => {
  if (!num) return '۰'
  return Number(num).toLocaleString('fa-IR')
}

const formatDate = (date) => {
  if (!date) return ''
  const d = new Date(date)
  if (isNaN(d.getTime())) return String(date)
  return d.toLocaleDateString('fa-IR')
}

const normalizeDateForPicker = (value) => {
  if (!value) return null
  if (value instanceof Date) {
    const year = value.getFullYear()
    const month = String(value.getMonth() + 1).padStart(2, '0')
    const day = String(value.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }
  return String(value).slice(0, 10)
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

const responseRecords = (response) => {
  const data = response.data?.data
  return Array.isArray(data) ? data : (data?.data || [])
}

const loadEmployerContracts = async () => {
  loadingEmployerContracts.value = true
  try {
    const response = await api.get('/contracts', { params: { type: 'upstream', per_page: 100 } })
    employerContracts.value = responseRecords(response).filter(contract => ['active', 'expiring'].includes(contract.status))
  } catch (error) {
    handleApiError(error)
  } finally {
    loadingEmployerContracts.value = false
  }
}

const loadEmployerContractItems = async (contractId) => {
  const previousContractId = selectedEmployerContract.value?.id
  if (previousContractId && Number(previousContractId) !== Number(contractId)) {
    form.value.items.forEach(item => { item.employer_contract_item_id = null })
  }
  selectedEmployerContract.value = employerContractOptions.value.find(item => Number(item.id) === Number(contractId)) || selectedEmployerContract.value
  if (!contractId) {
    employerContractItems.value = []
    form.value.items.forEach(item => { item.employer_contract_item_id = null })
    return
  }

  loadingEmployerItems.value = true
  try {
    const response = await api.get(`/contracts/${contractId}`)
    const employer = response.data?.data || response.data
    selectedEmployerContract.value = { ...employer, label: `${employer.contract_number} — ${employer.subject}` }
    if (!employerContracts.value.some(item => Number(item.id) === Number(employer.id))) {
      employerContracts.value.push(employer)
    }
    employerContractItems.value = (employer.items || []).filter(item => item.is_active !== false).map(item => ({
      ...item,
      label: `${item.item_code || '—'} · ${item.description} (${formatNumber(item.remaining_framework_quantity ?? item.quantity)} ${item.unit}، نرخ ${formatNumber(item.unit_price)} ریال)`,
    }))
  } catch (error) {
    handleApiError(error)
    employerContractItems.value = []
  } finally {
    loadingEmployerItems.value = false
  }
}

const handleFrameworkToggle = (enabled) => {
  if (!enabled) {
    form.value.parent_contract_id = null
    selectedEmployerContract.value = null
    employerContractItems.value = []
    form.value.items.forEach(item => { item.employer_contract_item_id = null })
    return
  }
  if (!employerContracts.value.length) loadEmployerContracts()
}

const applyEmployerItem = (item) => {
  const source = employerContractItems.value.find(option => Number(option.id) === Number(item.employer_contract_item_id))
  if (!source) return
  item.unit = source.unit
  const price = Number(item.unit_price) || Number(source.unit_price)
  item.unit_price = Math.min(price, Number(source.unit_price))
}

const errorText = (value) => Array.isArray(value) ? value[0] : value

const loadContractForEdit = async () => {
  const id = route.params.id
  if (!id) return
  isEdit.value = true
  loadingContract.value = true
  try {
    const response = await api.get(`/contracts/${id}`)
    const contract = response.data?.data || response.data
    if (!['draft', 'rejected_by_legal'].includes(contract.status) && !(contract.status === 'pending_contract_review' && authStore.can('contracts.review'))) {
      toast.add({
        severity: 'warn',
        summary: 'ویرایش امکان‌پذیر نیست',
        detail: 'فقط پیش‌نویس قرارداد قابل ویرایش است.',
        life: 4000,
      })
      await router.replace('/contract-requests')
      return
    }
    form.value = {
      ...form.value,
      id: contract.id,
      subject: contract.subject || '',
      type: contract.type || 'downstream',
      parent_contract_id: contract.parent_contract_id || contract.parent_contract?.id || null,
      party_id: contract.party_id || contract.party?.id || null,
      start_date: normalizeDateForPicker(contract.start_date),
      end_date: normalizeDateForPicker(contract.end_date),
      requires_guarantee: Boolean(contract.requires_guarantee),
      description: contract.description || '',
      content: contract.content || '',
      items: (contract.items || []).map((item, index) => ({
        item_code: item.item_code || `ITEM-${index + 1}`,
        description: item.description || '',
        unit: item.unit || '',
        quantity: Number(item.quantity) || 0,
        unit_price: Number(item.unit_price) || 0,
        employer_contract_item_id: item.employer_contract_item_id || null,
      })),
    }
    selectedParty.value = contract.party || null
    partySearch.value = ''
    withinEmployerFramework.value = Boolean(form.value.parent_contract_id)
    if (withinEmployerFramework.value) {
      const employer = contract.parent_contract
      if (employer) {
        selectedEmployerContract.value = { ...employer, label: `${employer.contract_number} — ${employer.subject}` }
        employerContracts.value = [selectedEmployerContract.value]
        employerContractItems.value = (employer.items || []).map(item => ({
          ...item,
          label: `${item.item_code || '—'} · ${item.description} (${formatNumber(item.remaining_framework_quantity ?? item.quantity)} ${item.unit}، نرخ ${formatNumber(item.unit_price)} ریال)`,
        }))
      } else {
        await loadEmployerContracts()
        await loadEmployerContractItems(form.value.parent_contract_id)
      }
    }
  } catch (error) {
    handleApiError(error)
    await router.replace('/contract-requests')
  } finally {
    loadingContract.value = false
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
        params: { search: partySearch.value, role: 'contractor', per_page: 10 },
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
    item_code: '',
    unit: '',
    quantity: 0,
    unit_price: 0,
    employer_contract_item_id: null,
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
    if (withinEmployerFramework.value && !form.value.parent_contract_id) {
      errors.value.parent_contract_id = 'انتخاب قرارداد کارفرمایی مبنا الزامی است.'
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

  if (step === 2 && withinEmployerFramework.value) {
    if (!form.value.items.length) {
      errors.value.items = 'برای قرارداد در چهارچوب، دست‌کم یک ردیف ثبت کنید.'
      isValid = false
    }
    form.value.items.forEach((item, index) => {
      if (!item.employer_contract_item_id) {
        errors.value[`items.${index}.employer_contract_item_id`] = 'ردیف متناظر قرارداد کارفرمایی را انتخاب کنید.'
        isValid = false
      }
    })
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
      toast.add({
        severity: 'success',
        summary: 'موفق',
        detail: 'درخواست با موفقیت ویرایش شد',
        life: 3000,
      })
    } else {
      await api.post('/contracts', payload)
      toast.add({
        severity: 'success',
        summary: 'موفق',
        detail: form.value.type === 'upstream'
          ? 'قرارداد کارفرمایی ثبت و برای استفاده به‌عنوان قرارداد مبنا فعال شد.'
          : 'درخواست قرارداد با موفقیت ثبت و به واحد حقوقی ارسال شد',
        life: 4000,
      })
    }
    setTimeout(() => {
      router.push('/contract-requests')
    }, 1500)
  } catch (error) {
    handleApiError(error, {
      setErrors: (validationErrors) => {
        errors.value = validationErrors
      },
    })
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadMyUnit(), loadContractForEdit()])
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap');

.contract-page {
  --accent: oklch(45% 0.15 250);
  --accent-dark: oklch(38% 0.17 250);
  --accent-soft: oklch(45% 0.15 250 / 0.10);
  --accent-ring: oklch(45% 0.15 250 / 0.14);
  --gold: oklch(55% 0.15 155);
  --gold-dark: oklch(48% 0.16 155);
  --gold-soft: oklch(55% 0.15 155 / 0.10);
  --surface: oklch(99.5% 0.003 250 / 0.97);
  --bg: oklch(97.5% 0.008 250);
  --border: oklch(88% 0.008 250);
  --text-main: oklch(15% 0.01 250);
  --text-muted: oklch(45% 0.012 250);
  --text-subtle: oklch(65% 0.008 250);
  --danger: oklch(50% 0.20 25);
  --success: oklch(42% 0.14 145);
  min-height: 100dvh;
  direction: rtl;
  background: var(--bg);
  color: var(--text-main);
  position: relative;
  padding: 24px;
  overflow-x: hidden;
  font-family: 'Vazirmatn', Tahoma, sans-serif;
}
.page-background { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
.bg-shape { position: absolute; border-radius: 50%; filter: blur(100px); }
.shape-one { width: 500px; height: 500px; top: -150px; right: -150px; background: oklch(45% 0.15 250 / .08); }
.shape-two { width: 450px; height: 450px; bottom: -150px; left: -150px; background: oklch(55% 0.15 155 / .07); }
.shape-three { width: 300px; height: 300px; top: 40%; left: 40%; background: oklch(50% 0.12 200 / .05); }
.bg-grid {
  position: absolute; inset: 0;
  background-image: linear-gradient(oklch(58% 0.008 250 / .05) 1px, transparent 1px), linear-gradient(90deg, oklch(58% 0.008 250 / .05) 1px, transparent 1px);
  background-size: 50px 50px;
  mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 30%, transparent 80%);
}
.floating-icons { position: absolute; inset: 0; }
.floating-icon { position: absolute; font-size: 32px; opacity: .055; color: var(--accent); animation: float 10s ease-in-out infinite; }
.icon-1 { top: 10%; left: 8%; }
.icon-2 { top: 65%; left: 12%; animation-delay: 2s; color: var(--gold); }
.icon-3 { top: 15%; right: 10%; animation-delay: 4s; }
.icon-4 { bottom: 20%; right: 8%; animation-delay: 1s; color: var(--gold); }
.icon-5 { top: 45%; left: 3%; animation-delay: 3s; }
.icon-6 { top: 30%; right: 15%; animation-delay: 2.5s; color: var(--gold); }
@keyframes float { 0%,100% { transform: translateY(0) rotate(0); } 50% { transform: translateY(-25px) rotate(5deg); } }

.page-container { width: min(1200px, 100%); margin: 0 auto; position: relative; z-index: 1; display: flex; flex-direction: column; gap: 24px; }
.hero-card {
  position: relative; overflow: hidden; padding: 28px 32px;
  border-radius: 20px; color: #fff;
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  box-shadow: 0 12px 30px oklch(45% 0.15 250 / .22), 0 0 0 1px oklch(95% 0.004 250 / .1);
}
.hero-card::before,.hero-card::after { content: ''; position: absolute; border-radius: 50%; background: radial-gradient(circle,var(--gold) 0%,transparent 70%); filter: blur(40px); pointer-events: none; }
.hero-card::before { top: -150px; right: -60px; width: 300px; height: 300px; opacity: .16; }
.hero-card::after { bottom: -160px; left: -60px; width: 250px; height: 250px; opacity: .1; }
.hero-topline { position: relative; z-index: 1; display: flex; align-items: center; gap: 18px; }
.hero-mark { width: 58px; height: 58px; flex: 0 0 58px; display: grid; place-items: center; border-radius: 16px; background: #ffffff24; border: 1px solid #ffffff35; color: #fff; font-size: 24px; backdrop-filter: blur(10px); }
.hero-copy { flex: 1; min-width: 0; }
.eyebrow,.section-kicker { display: inline-flex; align-items: center; color: #fff; font-size: 11.5px; font-weight: 600; background: #ffffff20; border: 1px solid #ffffff30; border-radius: 20px; padding: 5px 12px; }
.hero-copy h1 { margin: 8px 0 5px; color: #fff; font-size: clamp(22px,2.4vw,30px); font-weight: 800; line-height: 1.5; letter-spacing: -.3px; }
.hero-copy p { margin: 0; color: #ffffffd9; font-size: 13px; line-height: 1.9; }
.back-button { white-space: nowrap; color: #fff !important; background: #ffffff20 !important; border: 1px solid #ffffff35 !important; border-radius: 10px !important; }
.back-button:hover { background: #ffffff30 !important; }
.back-button :deep(.p-button-icon) { color: #fff !important; }
.unit-chip { position: relative; z-index: 1; display: flex; align-items: center; gap: 10px; margin-top: 22px; padding: 12px 15px; border: 1px solid #ffffff30; background: #ffffff14; border-radius: 12px; color: #ffffffd9; font-size: 12px; }
.unit-chip > i { color: #bcebdc; font-size: 16px; }
.unit-chip strong { color: #fff; font-size: 13px; }
.unit-code { margin-right: auto; color: #ffffffc9; padding-right: 12px; border-right: 1px solid #ffffff40; }

.workflow-card,.content-card,.bottom-navigation {
  background: var(--surface); border: 1px solid oklch(90% 0.006 250 / .9); border-radius: 16px;
  box-shadow: 0 8px 24px oklch(15% 0.01 250 / .06), 0 0 0 1px oklch(95% 0.004 250 / .5);
  backdrop-filter: blur(10px);
}
.workflow-card { padding: 24px 28px 20px; }
.workflow-heading { display: flex; align-items: center; justify-content: space-between; gap: 15px; }
.workflow-heading h2 { margin: 5px 0 0; font-size: 17px; font-weight: 800; }
.section-kicker { color: var(--accent); background: var(--accent-soft); border: 0; padding: 0; }
.step-counter { color: var(--accent); background: var(--accent-soft); border-radius: 30px; padding: 7px 12px; font-size: 12px; font-weight: 700; }
.progress-track { height: 3px; background: var(--border); border-radius: 3px; margin: 20px 0 18px; overflow: hidden; }
.progress-value { height: 100%; background: linear-gradient(90deg,var(--accent),var(--gold)); border-radius: 3px; transition: width .4s ease; }
.step-list { display: grid; grid-template-columns: repeat(4,minmax(0,1fr)); gap: 12px; }
.step-button { border: 1px solid var(--border); background: var(--surface); display: flex; align-items: center; gap: 10px; text-align: right; padding: 12px; min-width: 0; border-radius: 12px; cursor: pointer; color: var(--text-muted); font: inherit; transition: all .2s; }
.step-button:hover:not(.active) { background: oklch(98.5% 0.005 250); }
.step-icon { display: grid; place-items: center; flex: 0 0 42px; height: 42px; border-radius: 50%; border: 2px solid var(--border); background: var(--surface); color: var(--text-muted); font-size: 15px; transition: .2s; }
.step-text { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.step-text small { font-size: 10px; color: var(--text-subtle); }
.step-text strong { font-size: 12px; font-weight: 700; color: var(--text-main); white-space: nowrap; }
.step-button.active { background: linear-gradient(135deg,var(--accent-soft),var(--gold-soft)); border-color: var(--accent); box-shadow: 0 4px 12px oklch(45% 0.15 250 / .12); }
.step-button.active .step-icon { color: #fff; background: linear-gradient(135deg,var(--accent),var(--accent-dark)); border-color: var(--accent); box-shadow: 0 4px 12px oklch(45% 0.15 250 / .25); }
.step-button.active .step-text strong { color: var(--accent); }
.step-button.completed { background: oklch(97% 0.04 145); border-color: oklch(82% 0.10 145); }
.step-button.completed .step-icon { color: #fff; background: linear-gradient(135deg,var(--gold),var(--gold-dark)); border-color: var(--gold); }
.step-button.completed .step-text strong { color: var(--gold-dark); }

.content-card { padding: 0; overflow: hidden; min-height: 340px; }
.step-content { padding: 0 0 28px; animation: enter .3s ease; }
@keyframes enter { from { opacity: .55; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.content-heading { display: flex; align-items: center; gap: 14px; margin: 0 0 24px; padding: 20px 24px; background: linear-gradient(135deg,oklch(98% 0.005 250),oklch(97% 0.008 250)); border-bottom: 1px solid var(--border); }
.heading-icon { width: 44px; height: 44px; flex: 0 0 44px; display: grid; place-items: center; color: #fff; background: linear-gradient(135deg,var(--accent),var(--accent-dark)); border-radius: 12px; font-size: 18px; box-shadow: 0 4px 12px oklch(45% 0.15 250 / .2); }
.heading-icon.green { background: linear-gradient(135deg,var(--gold),var(--gold-dark)); box-shadow: 0 4px 12px oklch(55% 0.15 155 / .2); }
.heading-icon.gold { background: linear-gradient(135deg,oklch(65% 0.15 75),oklch(58% 0.16 75)); }
.content-heading h2 { margin: 0 0 4px; font-size: 17px; font-weight: 800; color: var(--text-main); }
.content-heading p { margin: 0; color: var(--text-muted); font-size: 12px; line-height: 1.9; }
.content-heading > button { margin-right: auto; }
.form-grid,.review-grid { padding: 0 24px; display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 22px 24px; }
.field { display: flex; flex-direction: column; gap: 8px; position: relative; min-width: 0; }
.field label { color: var(--text-main); font-size: 12.5px; font-weight: 700; }
.field label b { color: var(--danger); margin-right: 3px; }
.framework-toggle { border: 1px solid var(--border); border-radius: 14px; padding: 16px; background: oklch(98% 0.005 250); }
.framework-toggle > label { font-size: 13px; }
.guarantee-control { display: flex; align-items: center; gap: 10px; color: var(--text-muted); font-size: 12px; }
.framework-parent-picker { display: flex; flex-direction: column; gap: 8px; margin-top: 8px; padding-top: 14px; border-top: 1px solid var(--border); }
.optional-label { font-size: 10px; color: var(--text-subtle); font-weight: 500; margin-right: 6px; }
.full-span { grid-column: 1 / -1; }
.w-full { width: 100%; }
.field :deep(.p-inputtext),.field :deep(.p-select),.field :deep(.p-textarea),.field :deep(.p-datepicker),.field :deep(.p-inputnumber-input) { width: 100%; border-radius: 10px; border-color: var(--border); font-family: inherit; font-size: 13px; }
.field :deep(.p-inputtext),.field :deep(.p-select),.field :deep(.p-datepicker) { min-height: 44px; }
.field :deep(.p-inputtext:enabled:focus),.field :deep(.p-textarea:enabled:focus) { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-ring); }
.search-field { position: relative; }
.search-field > i { position: absolute; z-index: 2; right: 13px; top: 14px; color: var(--text-subtle); font-size: 13px; }
.search-field :deep(input) { padding-right: 36px; }
.party-results { position: absolute; top: 100%; right: 0; left: 0; z-index: 20; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 14px 35px #152c4b1a; overflow: hidden; margin-top: 4px; }
.party-result { width: 100%; display: flex; align-items: center; gap: 10px; text-align: right; border: 0; border-bottom: 1px solid var(--bg); padding: 12px; background: var(--surface); cursor: pointer; font: inherit; }
.party-result:hover { background: var(--accent-soft); }
.party-result-icon { width: 34px; height: 34px; border-radius: 10px; background: var(--accent-soft); color: var(--accent); display: grid; place-items: center; }
.party-result-copy { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.party-result-copy strong { font-size: 12px; color: var(--text-main); }
.party-result-copy small { color: var(--text-muted); font-size: 11px; }
.result-arrow { margin-right: auto; color: var(--text-subtle); font-size: 11px; }
.selected-party { display: flex; align-items: center; gap: 10px; border: 1px solid oklch(82% 0.10 145); border-radius: 11px; background: oklch(97% 0.04 145); padding: 10px 12px; }
.selected-check { width: 28px; height: 28px; display: grid; place-items: center; color: var(--gold-dark); background: oklch(90% 0.07 145); border-radius: 50%; }
.selected-party div { display: flex; flex-direction: column; gap: 3px; }
.selected-party strong { font-size: 12px; }
.selected-party small { font-size: 11px; color: var(--text-muted); }
.selected-party button { margin-right: auto; border: 0; background: transparent; color: var(--text-subtle); cursor: pointer; }
.error-text { color: var(--danger); font-size: 11px; }

.editor-notice { display: flex; align-items: flex-start; gap: 12px; margin: 0 24px 18px; padding: 15px 17px; border: 1px solid oklch(85% 0.08 220); border-radius: 12px; background: oklch(97% 0.04 220); color: oklch(45% 0.12 220); }
.editor-notice > i { font-size: 18px; margin-top: 2px; }
.editor-notice p { margin: 0; display: flex; flex-direction: column; gap: 5px; }
.editor-notice strong { font-size: 12px; }
.editor-notice p span { font-size: 12px; line-height: 1.9; color: var(--text-muted); }
.editor-meta { display: flex; flex-wrap: wrap; gap: 9px; margin: 0 24px 10px; }
.editor-meta span { display: inline-flex; align-items: center; gap: 7px; color: var(--text-muted); background: oklch(98% 0.005 250); border: 1px solid var(--border); padding: 7px 10px; border-radius: 8px; font-size: 11px; }
.editor-meta i { color: var(--accent); }
.contract-editor { margin: 0 24px; border: 1px solid var(--border); border-radius: 12px; overflow: hidden; background: #fff; }
.contract-editor :deep(.p-editor-toolbar) { direction: rtl; display: flex; flex-wrap: wrap; gap: 4px; border: 0; border-bottom: 1px solid var(--border); background: var(--bg); padding: 9px; }
.contract-editor :deep(.p-editor-toolbar .ql-formats) { margin: 2px 4px; }
.contract-editor :deep(.p-editor-content) { border: 0; }
.contract-editor :deep(.ql-editor) { min-height: 500px; direction: rtl; text-align: right; font-family: 'Vazirmatn',Tahoma,sans-serif; font-size: 14px; line-height: 2.1; color: var(--text-main); padding: 22px; }
.contract-editor :deep(.ql-editor.ql-blank::before) { right: 22px; left: auto; text-align: right; font-style: normal; color: var(--text-subtle); content: 'متن پیش‌نویس قرارداد را اینجا وارد کنید...'; }
.contract-editor :deep(.ql-editor ol),.contract-editor :deep(.ql-editor ul) { padding-right: 1.5em; padding-left: 0; }
.contract-editor :deep(.ql-editor a) { color: var(--accent); text-decoration: underline; }
.editor-footer { display: flex; align-items: center; flex-wrap: wrap; justify-content: space-between; gap: 10px; padding: 12px 24px 0; color: var(--text-subtle); font-size: 11px; }
.muted-note { color: var(--text-subtle); }
.success-note { color: var(--gold-dark); display: inline-flex; align-items: center; gap: 5px; }

.empty-state { display: flex; align-items: center; flex-direction: column; text-align: center; margin: 0 24px; padding: 45px 15px; border: 1px dashed var(--border); border-radius: 15px; background: oklch(99% 0.004 250); }
.empty-state > span { display: grid; place-items: center; width: 58px; height: 58px; border-radius: 18px; color: var(--accent); background: var(--accent-soft); font-size: 23px; }
.empty-state h3 { margin: 15px 0 5px; font-size: 15px; }
.empty-state p { margin: 0 0 18px; color: var(--text-muted); font-size: 12px; line-height: 1.9; }
.items-table { margin: 0 24px; border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
.items-table :deep(.p-datatable-thead > tr > th) { background: oklch(98% 0.005 250); color: var(--text-muted); font-size: 11px; font-weight: 700; padding: 13px 10px; }
.items-table :deep(.p-datatable-tbody > tr > td) { padding: 10px; }
.table-control { width: 100%; }
.items-table :deep(.p-inputtext),.items-table :deep(.p-inputnumber-input) { font-family: inherit; font-size: 12px; width: 100%; }
.row-number { display: inline-grid; place-items: center; min-width: 28px; height: 28px; border-radius: 8px; background: var(--accent-soft); color: var(--accent); font-weight: 700; font-size: 11px; }
.item-total { color: var(--accent); font-size: 12px; white-space: nowrap; }
.total-panel { display: flex; justify-content: space-between; align-items: center; gap: 15px; margin: 18px 24px 0; padding: 17px 20px; border: 1px solid oklch(85% 0.08 220); border-radius: 13px; background: linear-gradient(110deg,oklch(97% 0.04 220),oklch(98% 0.02 220)); }
.total-panel > div { display: flex; align-items: center; gap: 12px; }
.total-icon { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 12px; color: var(--accent); background: var(--accent-soft); font-size: 18px; }
.total-panel div div { display: flex; flex-direction: column; gap: 4px; }
.total-panel div div > small { color: var(--text-muted); font-size: 11px; }
.total-panel div div > strong { color: var(--accent-dark); font-size: 20px; font-weight: 800; }
.total-panel div div > strong small { font-size: 11px; font-weight: 500; }
.total-panel > span { font-size: 11px; color: var(--text-muted); }

.review-grid { padding-bottom: 0; }
.review-item { display: flex; align-items: flex-start; gap: 12px; padding: 16px; border: 1px solid var(--border); border-radius: 12px; background: linear-gradient(135deg,oklch(98% 0.005 250),oklch(97% 0.008 250)); min-width: 0; }
.review-icon { flex: 0 0 40px; width: 40px; height: 40px; display: grid; place-items: center; border-radius: 11px; background: var(--accent-soft); color: var(--accent); }
.review-item > div { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
.review-item small { color: var(--text-subtle); font-size: 11px; }
.review-item strong { color: var(--text-main); font-size: 13px; line-height: 1.8; overflow-wrap: anywhere; }
.amount-review { background: oklch(97% 0.04 145); border-color: oklch(82% 0.10 145); }
.amount-review .review-icon { background: oklch(90% 0.07 145); color: var(--gold-dark); }
.amount-review strong { color: var(--gold-dark); font-size: 16px; }
.final-notice { margin-top: 20px; background: oklch(98% 0.04 80); border-color: oklch(88% 0.10 80); color: oklch(45% 0.12 75); }
.final-notice p span { color: var(--text-muted); }

.bottom-navigation { display: flex; align-items: center; justify-content: space-between; gap: 15px; padding: 20px 24px; }
.navigation-hint { color: var(--text-subtle); font-size: 11px; display: flex; align-items: center; gap: 8px; }
.navigation-hint i { color: var(--accent); }
.navigation-actions { display: flex; align-items: center; gap: 8px; }
.bottom-navigation :deep(.p-button),.content-heading :deep(.p-button) { font-family: inherit; font-size: 12px; border-radius: 10px; }
.loading-card { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 50px; color: var(--text-muted); background: var(--surface); border: 1px solid var(--border); border-radius: 16px; }
.loading-card i { color: var(--accent); font-size: 20px; }

@media (max-width: 1024px) {
  .contract-page { padding: 16px; }
  .hero-card { padding: 22px 20px; border-radius: 16px; }
  .hero-topline { flex-wrap: wrap; }
  .back-button { margin-right: auto; }
  .workflow-card { padding: 20px; }
  .step-list { grid-template-columns: repeat(2,minmax(0,1fr)); }
}
@media (max-width: 760px) {
  .contract-page { padding: 14px 10px 28px; }
  .page-container { gap: 14px; }
  .hero-topline { align-items: flex-start; gap: 12px; }
  .hero-mark { width: 46px; height: 46px; flex-basis: 46px; border-radius: 13px; font-size: 19px; }
  .hero-copy { flex-basis: calc(100% - 60px); }
  .hero-copy h1 { font-size: 21px; }
  .hero-copy p { font-size: 12px; }
  .workflow-card { padding: 18px 14px; }
  .step-list { gap: 9px; }
  .step-button { padding: 10px 8px; gap: 8px; }
  .step-icon { flex-basis: 36px; height: 36px; }
  .step-text strong { white-space: normal; font-size: 11px; }
  .content-heading { align-items: flex-start; flex-wrap: wrap; padding: 17px 15px; margin-bottom: 18px; }
  .content-heading h2 { font-size: 15px; }
  .content-heading > button { margin-right: 0; width: 100%; }
  .form-grid,.review-grid { grid-template-columns: 1fr; gap: 18px; padding-left: 15px; padding-right: 15px; }
  .full-span { grid-column: auto; }
  .editor-notice,.editor-meta { margin-left: 15px; margin-right: 15px; }
  .contract-editor { margin-left: 15px; margin-right: 15px; }
  .contract-editor :deep(.p-editor-toolbar) { padding: 7px; }
  .contract-editor :deep(.ql-editor) { min-height: 360px; padding: 18px 15px; }
  .contract-editor :deep(.ql-editor.ql-blank::before) { right: 15px; }
  .editor-footer { padding-left: 15px; padding-right: 15px; }
  .empty-state,.items-table { margin-left: 15px; margin-right: 15px; }
  .total-panel { margin-left: 15px; margin-right: 15px; padding: 14px; }
  .total-panel div div > strong { font-size: 16px; }
  .bottom-navigation { align-items: stretch; flex-direction: column; padding: 14px; }
  .navigation-actions { width: 100%; justify-content: space-between; }
  .navigation-actions :deep(.p-button:last-child) { flex: 1; justify-content: center; }
}
@media (prefers-reduced-motion: reduce) {
  .floating-icon,.step-content { animation: none !important; }
  *,*::before,*::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
}
</style>
