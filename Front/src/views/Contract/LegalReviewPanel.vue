<!-- src/components/contract/LegalReviewPanel.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useApiErrorHandler } from '@/composables/useApiErrorHandler'
import api from '@/api/axios.js'
import { useAuthStore } from '@/stores/auth.js'
import { useDateFormatter } from '@/composables/useDateFormatter'
import {
  FileEdit, CheckCircle, XCircle, Send, FileText, User, Calendar,
  Building2, AlertCircle, History, Clock, Info
} from 'lucide-vue-next'

const props = defineProps({
  contract: { type: Object, required: true },
})

const emit = defineEmits(['refresh', 'status-changed'])

const toast = useToast()
const confirm = useConfirm()
const { handleApiError } = useApiErrorHandler()
const authStore = useAuthStore()
const { toJalaliDateTime } = useDateFormatter()

const loading = ref(false)
const submitting = ref(false)
const history = ref([])

const reviewForm = ref({
  content: '',
  comments: '',
  requires_guarantee: false,
  guarantee_type: null,
  guarantee_instrument_type: null,
  requires_board_signature: false,
})

const guaranteeInstrumentOptions = [
  { label: 'چک ضمانت', value: 'check' },
  { label: 'سفته', value: 'promissory_note' },
  { label: 'ضمانت‌نامه بانکی', value: 'bank_guarantee' },
]
const guaranteeTypeOptions = [
  { label: 'حسن انجام کار', value: 'performance' },
  { label: 'پیش‌پرداخت', value: 'advance_payment' },
  { label: 'شرکت در مناقصه', value: 'bid' },
  { label: 'کسورات', value: 'retention' },
]

const can = (permission) => authStore.can(permission)

const isPendingReview = computed(() => props.contract.status === 'pending_legal_review')
const isLegalDraft = computed(() => props.contract.status === 'legal_draft')

const statusInfo = computed(() => {
  const statusMap = {
    'pending_legal_review': { label: 'در انتظار بررسی حقوقی', color: 'warn', icon: 'pi pi-clock' },
    'legal_draft': { label: 'پیش‌نویس حقوقی', color: 'info', icon: 'pi pi-file-edit' },
    'draft': { label: 'پیش‌نویس', color: 'secondary', icon: 'pi pi-file' },
  }
  return statusMap[props.contract.status] || { label: props.contract.status, color: 'secondary', icon: 'pi pi-file' }
})

const pagesCount = computed(() => {
  const content = reviewForm.value.content || props.contract.content
  if (!content) return 0
  const text = stripHtml(content)
  return Math.max(1, Math.ceil(text.length / 1500))
})

const stripHtml = (html) => {
  if (!html) return ''
  const doc = new DOMParser().parseFromString(html, 'text/html')
  return doc.body.textContent || ''
}

const formatDate = (date) => {
  if (!date) return '—'
  return toJalaliDateTime(date)
}

onMounted(async () => {
  // بارگذاری متن قرارداد در فرم
  reviewForm.value.content = props.contract.content || ''
  reviewForm.value.requires_guarantee = !!props.contract.requires_guarantee
  reviewForm.value.guarantee_type = props.contract.guarantee_type || null
  reviewForm.value.guarantee_instrument_type = props.contract.guarantee_instrument_type || null
  reviewForm.value.requires_board_signature = !!props.contract.requires_board_signature

  // بارگذاری تاریخچه
  await loadHistory()
})

const loadHistory = async () => {
  try {
    const response = await api.get(`/contracts/${props.contract.id}/workflow-history`)
    history.value = response.data.data || []
  } catch (error) {
    console.error('خطا در دریافت تاریخچه:', error)
  }
}

const handleSaveDraft = async () => {
  if (!reviewForm.value.content?.trim()) {
    toast.add({ severity: 'warn', summary: 'خطا', detail: 'متن قرارداد نمی‌تواند خالی باشد', life: 3000 })
    return
  }

  confirm.require({
    message: 'آیا از ذخیره پیش‌نویس حقوقی اطمینان دارید؟',
    header: 'تایید ذخیره',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'بله، ذخیره کن',
    rejectLabel: 'انصراف',
    accept: async () => {
      submitting.value = true
      try {
        await api.put(`/contracts/${props.contract.id}/guarantee-requirement`, {
          requires_guarantee: reviewForm.value.requires_guarantee,
          guarantee_type: reviewForm.value.guarantee_type,
          guarantee_instrument_type: reviewForm.value.guarantee_instrument_type,
          requires_board_signature: reviewForm.value.requires_board_signature,
        })
        await api.post(`/contracts/${props.contract.id}/legal-draft`, {
          content: reviewForm.value.content,
          comments: reviewForm.value.comments,
        })
        toast.add({ severity: 'success', summary: 'موفق', detail: 'پیش‌نویس حقوقی با موفقیت ذخیره شد', life: 3000 })
        emit('refresh')
        emit('status-changed')
      } catch (error) {
        handleApiError(error)
      } finally {
        submitting.value = false
      }
    }
  })
}

const handleSendToContractor = async () => {
  if (!reviewForm.value.content?.trim()) {
    toast.add({ severity: 'warn', summary: 'خطا', detail: 'ابتدا باید متن قرارداد تنظیم شود', life: 3000 })
    return
  }

  confirm.require({
    message: 'آیا از ارسال قرارداد به پیمانکار برای امضا اطمینان دارید؟',
    header: 'ارسال به پیمانکار',
    icon: 'pi pi-send',
    acceptLabel: 'بله، ارسال کن',
    rejectLabel: 'انصراف',
    accept: async () => {
      submitting.value = true
      try {
        await api.put(`/contracts/${props.contract.id}/guarantee-requirement`, {
          requires_guarantee: reviewForm.value.requires_guarantee,
          guarantee_type: reviewForm.value.guarantee_type,
          guarantee_instrument_type: reviewForm.value.guarantee_instrument_type,
          requires_board_signature: reviewForm.value.requires_board_signature,
        })
        if (isPendingReview.value) {
          await api.post(`/contracts/${props.contract.id}/legal-draft`, {
            content: reviewForm.value.content,
            comments: reviewForm.value.comments,
          })
        }
        await api.post(`/contracts/${props.contract.id}/send-to-contractor`)
        toast.add({ severity: 'success', summary: 'موفق', detail: 'قرارداد با موفقیت به پیمانکار ارسال شد', life: 4000 })
        emit('refresh')
        emit('status-changed')
      } catch (error) {
        handleApiError(error)
      } finally {
        submitting.value = false
      }
    }
  })
}

const handleReturnToRequester = async () => {
  if (!reviewForm.value.comments?.trim()) {
    toast.add({ severity: 'warn', summary: 'خطا', detail: 'ذکر دلیل بازگشت الزامی است', life: 3000 })
    return
  }

  confirm.require({
    message: 'آیا از بازگرداندن قرارداد به واحد درخواست‌کننده اطمینان دارید؟',
    header: 'بازگشت به واحد',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'بله، بازگردان',
    rejectLabel: 'انصراف',
    acceptClass: 'p-button-danger',
    accept: async () => {
      submitting.value = true
      try {
        await api.post(`/contracts/${props.contract.id}/return-to-requester`, {
          comments: reviewForm.value.comments,
        })
        toast.add({ severity: 'success', summary: 'موفق', detail: 'قرارداد به واحد درخواست‌کننده بازگردانده شد', life: 4000 })
        emit('refresh')
        emit('status-changed')
      } catch (error) {
        handleApiError(error)
      } finally {
        submitting.value = false
      }
    }
  })
}
</script>

<template>
  <div class="legal-review-panel space-y-6">
    <!-- هدر پنل -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
            <FileEdit class="w-6 h-6 text-purple-600" />
          </div>
          <div>
            <h2 class="text-lg font-bold text-gray-800">پنل بررسی حقوقی</h2>
            <p class="text-sm text-gray-500 mt-1">
              قرارداد شماره: <strong class="font-mono">{{ contract.contract_number }}</strong>
            </p>
          </div>
        </div>
        <Tag
            :value="statusInfo.label"
            :severity="statusInfo.color"
            :icon="statusInfo.icon"
        />
      </div>
    </div>

    <!-- اطلاعات قرارداد -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
      <h3 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
        <Info class="w-5 h-5 text-blue-600" />
        اطلاعات قرارداد
      </h3>
      <div class="info-grid">
        <div class="info-card">
          <div class="info-icon-wrapper">
            <FileText class="info-icon" />
          </div>
          <div class="info-content">
            <div class="info-label">موضوع</div>
            <div class="info-value">{{ contract.subject || '—' }}</div>
          </div>
        </div>
        <div class="info-card">
          <div class="info-icon-wrapper">
            <Building2 class="info-icon" />
          </div>
          <div class="info-content">
            <div class="info-label">طرف قرارداد</div>
            <div class="info-value">{{ contract.party?.name || '—' }}</div>
          </div>
        </div>
        <div class="info-card">
          <div class="info-icon-wrapper">
            <User class="info-icon" />
          </div>
          <div class="info-content">
            <div class="info-label">درخواست‌دهنده</div>
            <div class="info-value">{{ contract.requesting_user?.name || '—' }}</div>
          </div>
        </div>
        <div class="info-card">
          <div class="info-icon-wrapper">
            <Calendar class="info-icon" />
          </div>
          <div class="info-content">
            <div class="info-label">مدت قرارداد</div>
            <div class="info-value">
              {{ contract.start_date && contract.end_date ? `${contract.start_date} تا ${contract.end_date}` : '—' }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ویرایشگر متن قرارداد -->
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-indigo-200 bg-indigo-50 p-4">
      <div>
        <h3 class="font-semibold text-slate-800">نیاز به ضمانت</h3>
        <p class="mt-1 text-sm text-slate-600">واحد حقوقی و قراردادها نیاز و نوع ضمانت را تعیین می‌کنند.</p>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-sm">{{ reviewForm.requires_guarantee ? 'ضمانت لازم است' : 'ضمانت لازم نیست' }}</span>
        <ToggleSwitch v-model="reviewForm.requires_guarantee" :disabled="submitting" />
      </div>
      <Select
        v-if="reviewForm.requires_guarantee"
        v-model="reviewForm.guarantee_type"
        :options="guaranteeTypeOptions"
        option-label="label"
        option-value="value"
        placeholder="نوع ضمانت را انتخاب کنید"
        class="w-full md:w-72"
        :disabled="submitting"
      />
      <Select
        v-if="reviewForm.requires_guarantee"
        v-model="reviewForm.guarantee_instrument_type"
        :options="guaranteeInstrumentOptions"
        option-label="label"
        option-value="value"
        placeholder="نوع سند ضمانت را انتخاب کنید"
        class="w-full md:w-72"
        :disabled="submitting"
      />
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
      <div>
        <h3 class="font-semibold text-slate-800">نیاز به امضای عضو هیئت‌مدیره</h3>
        <p class="mt-1 text-sm text-slate-600">مدیرعامل در همه قراردادها امضا می‌کند؛ این گزینه امضای عضو هیئت‌مدیره را نیز به گردش اضافه می‌کند.</p>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-sm">{{ reviewForm.requires_board_signature ? 'امضای هیئت‌مدیره لازم است' : 'امضای هیئت‌مدیره لازم نیست' }}</span>
        <ToggleSwitch v-model="reviewForm.requires_board_signature" :disabled="submitting" />
      </div>
    </div>

    <div v-if="can('contracts.draft')" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
      <h3 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
        <FileEdit class="w-5 h-5 text-purple-600" />
        تنظیم متن حقوقی قرارداد
      </h3>

      <div class="info-banner mb-4">
        <div class="info-banner-icon warn">
          <AlertCircle class="w-5 h-5" />
        </div>
        <div class="info-banner-content">
          <div class="info-banner-title">توجه</div>
          <div class="info-banner-subtitle">
            متن وارد شده به عنوان متن نهایی قرارداد در نظر گرفته می‌شود. لطفاً با دقت کامل تنظیم شود.
          </div>
        </div>
      </div>

      <div class="legal-editor-shell">
        <Editor
            v-model="reviewForm.content"
            class="legal-editor"
            editorStyle="height: 500px"
        >
          <template #toolbar>
            <span class="ql-formats">
              <select class="ql-header" aria-label="سبک عنوان">
                <option value="1">عنوان ۱</option>
                <option value="2">عنوان ۲</option>
                <option value="3">عنوان ۳</option>
                <option selected>متن عادی</option>
              </select>
            </span>
            <span class="ql-formats">
              <button class="ql-bold" aria-label="ضخیم"></button>
              <button class="ql-italic" aria-label="مورب"></button>
              <button class="ql-underline" aria-label="زیرخط"></button>
              <button class="ql-strike" aria-label="خط‌خورده"></button>
            </span>
            <span class="ql-formats">
              <button class="ql-list" value="ordered" aria-label="فهرست شماره‌ای"></button>
              <button class="ql-list" value="bullet" aria-label="فهرست نشانه‌ای"></button>
              <button class="ql-indent" value="-1" aria-label="کاهش تورفتگی"></button>
              <button class="ql-indent" value="+1" aria-label="افزایش تورفتگی"></button>
            </span>
            <span class="ql-formats">
              <select class="ql-color" aria-label="رنگ متن"></select>
              <select class="ql-background" aria-label="رنگ زمینه"></select>
            </span>
            <span class="ql-formats">
              <select class="ql-align" aria-label="تراز متن"></select>
              <button class="ql-direction" value="rtl" aria-label="راست‌چین"></button>
            </span>
            <span class="ql-formats">
              <button class="ql-link" aria-label="افزودن پیوند"></button>
              <button class="ql-image" aria-label="افزودن تصویر"></button>
              <button class="ql-clean" aria-label="پاک‌کردن قالب‌بندی"></button>
            </span>
          </template>
        </Editor>
      </div>

      <div v-if="reviewForm.content" class="flex items-center gap-4 text-xs text-surface-500 mt-3">
        <span>
          <FileText class="w-3 h-3 inline" />
          {{ pagesCount }} صفحه تقریبی
        </span>
        <span>
          <FileText class="w-3 h-3 inline" />
          {{ stripHtml(reviewForm.content).length }} کاراکتر
        </span>
      </div>

      <div class="form-group mt-4">
        <label class="form-label">
          <FileText class="label-icon" />
          توضیحات کارشناس حقوقی
        </label>
        <Textarea
            v-model="reviewForm.comments"
            rows="3"
            placeholder="توضیحات یا نکات مهم درباره این قرارداد..."
            class="form-input"
        />
      </div>
    </div>

    <!-- اکشن‌ها -->
    <div v-if="can('contracts.draft') && (isPendingReview || isLegalDraft)" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
      <h3 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
        <CheckCircle class="w-5 h-5 text-green-600" />
        اقدامات
      </h3>

      <div class="actions-grid">
        <Button
            label="ذخیره پیش‌نویس حقوقی"
            icon="pi pi-save"
            severity="info"
            outlined
            :loading="submitting"
            @click="handleSaveDraft"
            class="action-btn"
        />
        <Button
            label="ارسال به پیمانکار برای امضا"
            icon="pi pi-send"
            severity="success"
            :loading="submitting"
            @click="handleSendToContractor"
            class="action-btn primary"
        />
        <Button
            label="بازگشت به واحد درخواست‌کننده"
            icon="pi pi-undo"
            severity="danger"
            outlined
            :loading="submitting"
            @click="handleReturnToRequester"
            class="action-btn"
        />
      </div>
    </div>

    <!-- فقط نمایشی (بدون دسترسی ویرایش) -->
    <div v-if="!can('contracts.draft') && contract.content" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
      <h3 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
        <FileText class="w-5 h-5 text-blue-600" />
        متن قرارداد
      </h3>
      <div class="contract-content bg-slate-50 border border-slate-200 rounded-lg p-6 max-h-[500px] overflow-y-auto">
        <div v-html="contract.content" class="ql-editor prose prose-sm max-w-none" dir="rtl" />
      </div>
    </div>

    <!-- تاریخچه -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
      <h3 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
        <History class="w-5 h-5 text-blue-600" />
        تاریخچه گردش کار
      </h3>

      <div v-if="history.length === 0" class="empty-state">
        <History class="w-12 h-12 text-gray-300 mx-auto mb-3" />
        <p class="text-gray-500 text-sm">هنوز سابقه‌ای ثبت نشده است</p>
      </div>

      <div v-else class="timeline">
        <div
            v-for="(item, idx) in history"
            :key="idx"
            class="timeline-item"
        >
          <div class="timeline-dot" :class="item.status_color || 'blue'"></div>
          <div class="timeline-content">
            <div class="timeline-header">
              <span class="timeline-title">{{ item.title || item.status_label }}</span>
              <span class="timeline-date">
                <Clock class="w-3 h-3 inline" />
                {{ formatDate(item.created_at) }}
              </span>
            </div>
            <div v-if="item.actor_name" class="timeline-actor">
              <User class="w-3 h-3 inline" />
              {{ item.actor_name }}
            </div>
            <div v-if="item.comments" class="timeline-comments">
              {{ item.comments }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.legal-review-panel {
  --legal-primary: #164e8a;
  --legal-primary-dark: #0f3158;
  --legal-accent: #8b5cf6;
  --legal-border: #dbe4ef;
  --legal-muted: #64748b;
  color: #1e293b;
  direction: rtl;
}
.legal-review-panel > div {
  transition: box-shadow .2s ease, border-color .2s ease;
}
.legal-review-panel > div.bg-white {
  background: linear-gradient(145deg, #ffffff 0%, #fbfdff 100%);
  border-color: var(--legal-border);
  box-shadow: 0 8px 24px rgba(15, 49, 88, .055);
}
.legal-review-panel > div.bg-white:first-child {
  position: relative;
  overflow: hidden;
  border-radius: 20px;
  border-top: 4px solid var(--legal-primary);
}
.legal-review-panel > div.bg-white:first-child::after {
  content: "";
  position: absolute;
  width: 190px;
  height: 190px;
  left: -90px;
  top: -110px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(59,130,246,.12), transparent 70%);
  pointer-events: none;
}
.legal-review-panel h2,
.legal-review-panel h3 { letter-spacing: -.02em; }
.legal-review-panel h2 { color: var(--legal-primary-dark); }
.legal-review-panel h3 {
  padding-bottom: .8rem;
  border-bottom: 1px solid #e8eef5;
}
.info-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}
.info-card {
  display: flex;
  align-items: center;
  gap: .85rem;
  min-width: 0;
  padding: 1rem;
  border-radius: 14px;
  border: 1px solid #e2eaf3;
  background: linear-gradient(135deg, #f8fbff, #f1f6fc);
  transition: transform .18s ease, box-shadow .18s ease;
}
.info-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 18px rgba(30, 64, 110, .07);
}
.info-icon-wrapper {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 13px;
  background: linear-gradient(145deg, #e1edff, #d3e4ff);
  flex-shrink: 0;
}
.info-icon { width: 20px; height: 20px; color: #2563a6; }
.info-content { flex: 1; min-width: 0; }
.info-label { font-size: .75rem; color: var(--legal-muted); margin-bottom: .35rem; }
.info-value {
  font-size: .9rem;
  font-weight: 700;
  color: #1e3552;
  overflow-wrap: anywhere;
}
.info-banner {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.1rem;
  background: linear-gradient(135deg, #fff9e9, #fff3d3);
  border: 1px solid #f2d48b;
  border-radius: 14px;
}
.info-banner-icon {
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #e6a323;
  color: white;
  border-radius: 12px;
  flex-shrink: 0;
}
.info-banner-content { flex: 1; }
.info-banner-title { font-weight: 800; color: #7c4a03; font-size: .95rem; }
.info-banner-subtitle { font-size: .83rem; color: #805d25; margin-top: .2rem; line-height: 1.9; }
.legal-editor-shell {
  overflow: hidden;
  border: 1px solid #cbd8e7;
  border-radius: 15px;
  background: white;
  box-shadow: 0 5px 18px rgba(15, 49, 88, .045);
}
.legal-editor :deep(.p-editor-toolbar),
.legal-editor :deep(.ql-toolbar) {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: .25rem;
  padding: .7rem .8rem;
  background: linear-gradient(180deg, #f8fbff, #eef4fa);
  border: 0;
  border-bottom: 1px solid #dce6f0;
}
.legal-editor :deep(.ql-toolbar .ql-formats) {
  margin: .15rem .25rem;
  padding-left: .5rem;
  border-left: 1px solid #d7e1ed;
}
.legal-editor :deep(.ql-toolbar button),
.legal-editor :deep(.ql-toolbar select) { border-radius: 6px; }
.legal-editor :deep(.ql-toolbar button:hover) { background: #e0ecfb; }
.legal-editor :deep(.ql-container) { border: 0; font-family: inherit; font-size: 14px; }
.legal-editor :deep(.ql-editor) {
  min-height: 500px;
  padding: 1.5rem 1.7rem;
  color: #25364b;
  line-height: 2.15;
  text-align: justify;
  direction: rtl;
}
.legal-editor :deep(.ql-editor.ql-blank::before) {
  right: 1.7rem;
  left: auto;
  color: #94a3b8;
  font-style: normal;
}
.form-group { display: flex; flex-direction: column; gap: .55rem; }
.form-label {
  display: flex;
  align-items: center;
  gap: .5rem;
  font-size: .875rem;
  font-weight: 700;
  color: #334155;
}
.label-icon { width: 16px; height: 16px; color: #3b82f6; }
.form-input { width: 100%; }
.form-group :deep(textarea) {
  border-radius: 12px;
  border-color: #d5e0ec;
  line-height: 1.9;
}
.form-group :deep(textarea:focus) {
  border-color: #6b9dd5;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
}
.actions-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: .85rem;
}
.action-btn { width: 100%; min-height: 48px; border-radius: 12px; }
.action-btn.primary {
  background: linear-gradient(135deg, #10b981, #047857);
  border: none;
  box-shadow: 0 5px 12px rgba(5, 150, 105, .18);
}
.contract-content {
  line-height: 2.1;
  font-size: 14px;
  background: #f8fbff;
  border-radius: 14px;
}
.contract-content :deep(h1),
.contract-content :deep(h2),
.contract-content :deep(h3) {
  margin-top: 1.5rem;
  margin-bottom: .75rem;
  color: #0f3158;
  font-weight: 800;
}
.contract-content :deep(p) { margin-bottom: 1rem; text-align: justify; line-height: 2.1; }
.contract-content :deep(ul),
.contract-content :deep(ol) { margin-bottom: 1rem; padding-right: 2rem; }
.contract-content :deep(li) { margin-bottom: .5rem; }
.timeline { position: relative; padding-right: 22px; }
.timeline::before {
  content: "";
  position: absolute;
  right: 7px;
  top: 5px;
  bottom: 5px;
  width: 2px;
  background: linear-gradient(#93c5fd, #dbeafe);
}
.timeline-item { position: relative; padding-bottom: 1.5rem; }
.timeline-item:last-child { padding-bottom: 0; }
.timeline-dot {
  position: absolute;
  right: -22px;
  top: 4px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 3px solid white;
  box-shadow: 0 0 0 2px currentColor;
}
.timeline-dot.blue { background: #3b82f6; color: #3b82f6; }
.timeline-dot.green { background: #10b981; color: #10b981; }
.timeline-dot.orange { background: #f59e0b; color: #f59e0b; }
.timeline-dot.red { background: #ef4444; color: #ef4444; }
.timeline-content {
  padding: .9rem 1rem;
  border: 1px solid #e5edf5;
  border-radius: 12px;
  background: #fbfdff;
}
.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: .5rem;
  margin-bottom: .35rem;
}
.timeline-title { font-weight: 800; font-size: .9rem; color: #1e3552; }
.timeline-date { font-size: .75rem; color: #64748b; display: flex; align-items: center; gap: .25rem; }
.timeline-actor { font-size: .78rem; color: #64748b; display: flex; align-items: center; gap: .25rem; margin-bottom: .25rem; }
.timeline-comments {
  font-size: .82rem;
  color: #475569;
  background: #f1f6fc;
  padding: .65rem .8rem;
  border-radius: 8px;
  border-right: 3px solid #3b82f6;
  margin-top: .6rem;
  line-height: 1.9;
}
.empty-state {
  text-align: center;
  padding: 2.5rem 1rem;
  color: #94a3b8;
  border: 1px dashed #cbd8e7;
  border-radius: 14px;
  background: #f8fbff;
}
@media (max-width: 768px) {
  .info-grid, .actions-grid { grid-template-columns: 1fr; }
  .legal-editor :deep(.ql-editor) { padding: 1rem; min-height: 380px; }
  .legal-review-panel > div.bg-white { padding: 1rem; }
  .timeline-header { align-items: flex-start; }
}
@media print {
  .legal-review-panel .actions-grid,
  .legal-review-panel .legal-editor-shell,
  .legal-review-panel .info-banner { display: none !important; }
  .legal-review-panel > div.bg-white { box-shadow: none; break-inside: avoid; }
}
</style>
