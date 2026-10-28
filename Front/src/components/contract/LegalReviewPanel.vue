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
  Building2, AlertCircle, History, Clock
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
})

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

      <div class="border border-slate-300 rounded-lg overflow-hidden">
        <Editor
            v-model="reviewForm.content"
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
    <div v-if="can('contracts.draft') && isPendingReview" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
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
.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.info-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
}

.info-icon-wrapper {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: #dbeafe;
  flex-shrink: 0;
}

.info-icon {
  width: 18px;
  height: 18px;
  color: #2563eb;
}

.info-content {
  flex: 1;
  min-width: 0;
}

.info-label {
  font-size: 0.75rem;
  color: #64748b;
