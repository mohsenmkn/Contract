<!-- src/components/contract/SignaturePanel.vue -->
<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import { useSignatureStore } from '@/stores/signatureStore'
import { useAuthStore } from '@/stores/auth'
import { usePermission } from '@/composables/usePermission'
import { useDateFormatter } from '@/composables/useDateFormatter'
import { showToast } from '@/plugins/toast'
import DateDisplay from './DateDisplay.vue'

const props = defineProps({
  signature: { type: Object, default: null },
  compact: { type: Boolean, default: false },
  // ✅ متن قرارداد برای نمایش قبل از امضا
  contractContent: { type: String, default: '' },
  contractPagesCount: { type: Number, default: 0 },
  documentType: { type: String, default: 'contract' }, // contract | amendment
})

const emit = defineEmits(['signed', 'rejected'])

const confirm = useConfirm()
const signatureStore = useSignatureStore()
const authStore = useAuthStore()
const { can } = usePermission()
const { toJalaliDateTime } = useDateFormatter()

// State
const comments = ref('')
const rejectDialog = ref(false)
const rejecting = ref(false)
const rejectionError = ref('')

// OTP State
const otpDialog = ref(false)
const otpCode = ref('')
const otpId = ref(null)
const otpExpiresIn = ref(0)
const otpMobile = ref('')
const otpError = ref('')
const remainingAttempts = ref(3)
const requestingOtp = ref(false)
const submitting = ref(false)
let otpTimer = null

// Computed
const isPending = computed(() => props.signature?.status === 'pending')
const isSigned = computed(() => props.signature?.status === 'signed')
const isRejected = computed(() => props.signature?.status === 'rejected')

const isMine = computed(() =>
    props.signature?.signer_id === authStore.user?.id
)

const canSign = computed(() =>
    can('signatures.sign') && isPending.value && isMine.value
)

const documentLabel = computed(() => {
  const type = props.signature?.signable_type || ''
  if (type.includes('Contract')) return 'قرارداد'
  if (type.includes('Amendment')) return 'الحاقیه'
  return 'سند'
})

const hasContent = computed(() =>
    props.contractContent && props.contractContent.length > 50
)

const formatOtpTime = computed(() => {
  const m = Math.floor(otpExpiresIn.value / 60)
  const s = otpExpiresIn.value % 60
  return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`
})

const isOtpExpired = computed(() => otpExpiresIn.value <= 0)

// ✅ درخواست کد OTP
const handleRequestOtp = () => {
  if (!props.signature?.id) return

  // اگر متن قرارداد وجود دارد، ابتدا تایید مطالعه بگیر
  if (hasContent.value) {
    confirm.require({
      message: 'آیا متن قرارداد را به طور کامل مطالعه کرده‌اید و تمام مفاد آن را می‌پذیرید؟',
      header: 'تایید مطالعه متن قرارداد',
      icon: 'pi pi-file-edit',
      acceptLabel: 'بله، مطالعه کردم',
      rejectLabel: 'خیر',
      acceptClass: 'p-button-success',
      accept: () => openOtpDialog(),
    })
  } else {
    openOtpDialog()
  }
}

const openOtpDialog = async () => {
  requestingOtp.value = true
  otpError.value = ''
  otpCode.value = ''
  remainingAttempts.value = 3

  try {
    const response = await signatureStore.requestOtp(props.signature.id, 0)
    otpId.value = response.data.otp_id
    otpExpiresIn.value = response.data.expires_in_seconds
    otpMobile.value = response.data.mobile

    otpDialog.value = true
    startOtpTimer()

    showToast({
      severity: 'success',
      summary: 'کد ارسال شد',
      detail: `کد 6 رقمی به شماره ${otpMobile.value} ارسال شد`,
      life: 4000,
    })
  } catch (err) {
    otpError.value = err.response?.data?.message || 'خطا در ارسال کد'
    showToast({
      severity: 'error',
      summary: 'خطا',
      detail: otpError.value,
      life: 5000,
    })
  } finally {
    requestingOtp.value = false
  }
}

// ✅ تایید کد OTP و امضا
const handleVerifyAndSign = async () => {
  if (otpCode.value.length !== 6) {
    otpError.value = 'کد باید 6 رقم باشد'
    return
  }

  submitting.value = true
  otpError.value = ''

  try {
    await signatureStore.verifyAndSign(props.signature.id, {
      otp_id: otpId.value,
      code: otpCode.value,
      comments: comments.value || `امضا با کد تایید OTP در تاریخ ${new Date().toLocaleString('fa-IR')}`,
    })

    showToast({
      severity: 'success',
      summary: 'امضا ثبت شد',
      detail: `${documentLabel.value} با موفقیت امضا شد`,
      life: 4000,
    })

    otpDialog.value = false
    emit('signed', props.signature)
  } catch (err) {
    const data = err.response?.data
    otpError.value = data?.message || 'خطا در تایید کد'
    remainingAttempts.value = data?.data?.remaining_attempts ?? remainingAttempts.value

    if (data?.data?.attempts_exhausted) {
      otpDialog.value = false
      showToast({
        severity: 'warn',
        summary: 'تلاش‌ها به پایان رسید',
        detail: 'لطفاً کد جدیدی درخواست کنید',
        life: 5000,
      })
    } else {
      showToast({
        severity: 'error',
        summary: 'کد اشتباه',
        detail: otpError.value,
        life: 4000,
      })
    }
  } finally {
    submitting.value = false
  }
}

// رد سند (با دلیل)
const openReject = () => {
  rejectionError.value = ''
  rejectDialog.value = true
}

const confirmReject = async () => {
  if (!comments.value || comments.value.trim().length < 3) {
    rejectionError.value = 'درج دلیل رد سند (حداقل ۳ کاراکتر) الزامی است.'
    return
  }

  rejecting.value = true
  try {
    // ✅ فراخوانی واقعی API از طریق Store
    const result = await signatureStore.rejectDocument(props.signature.id, comments.value)

    // بستن دیالوگ و نمایش پیام موفقیت
    rejectDialog.value = false
    showToast({
      severity: 'warn',
      summary: 'سند رد شد',
      detail: 'سند با موفقیت رد شد و به حالت پیش‌نویس بازگشت.',
      life: 4000
    })

    // ارسال نتیجه به کامپوننت پدر برای به‌روزرسانی UI
    emit('rejected', result)
    comments.value = ''

  } catch (err) {
    showToast({
      severity: 'error',
      summary: 'خطا در رد سند',
      detail: err.response?.data?.message || err.message || 'خطای ناشناخته',
      life: 5000
    })
  } finally {
    rejecting.value = false
  }
}

// تایمر شمارش معکوس
const startOtpTimer = () => {
  clearInterval(otpTimer)
  otpTimer = setInterval(() => {
    if (otpExpiresIn.value > 0) {
      otpExpiresIn.value--
    } else {
      clearInterval(otpTimer)
    }
  }, 1000)
}

// پاکسازی تایمر
onUnmounted(() => {
  clearInterval(otpTimer)
})
</script>

<template>
  <div class="border border-surface-200 dark:border-surface-700 rounded-xl overflow-hidden">
    <!-- Header -->
    <header class="flex items-center justify-between gap-3 p-4 bg-surface-50 dark:bg-surface-800/50">
      <h3 class="font-semibold text-sm flex items-center gap-2">
        <i class="pi pi-signature text-primary" />
        امضای الکترونیکی {{ documentLabel }}
      </h3>
      <span
          v-if="signature"
          class="text-xs px-2 py-1 rounded-md"
          :class="{
          'bg-orange-100 text-orange-700': isPending,
          'bg-green-100 text-green-700': isSigned,
          'bg-red-100 text-red-700': isRejected,
        }"
      >
        {{ isSigned ? 'امضا شده' : isRejected ? 'رد شده' : 'در انتظار امضا' }}
      </span>
    </header>

    <div class="p-4 space-y-4">
      <Message v-if="!signature" severity="info" :closable="false">
        هنوز درخواست امضایی برای این سند ثبت نشده است.
      </Message>

      <template v-else>
        <!-- ✅ نمایش متن قرارداد قبل از امضا -->
        <div v-if="hasContent && isPending && isMine" class="space-y-2">
          <div class="flex items-center justify-between">
            <h4 class="text-sm font-semibold flex items-center gap-2">
              <i class="pi pi-file-edit text-primary" />
              متن {{ documentType === 'contract' ? 'قرارداد' : 'الحاقیه' }}
            </h4>
            <Tag
                v-if="contractPagesCount"
                :value="`${contractPagesCount} صفحه`"
                severity="info"
            />
          </div>

          <div
              class="contract-content bg-surface-50 dark:bg-surface-800/50 border border-surface-200 dark:border-surface-700 rounded-lg p-4 max-h-[400px] overflow-y-auto"
              dir="rtl"
          >
            <div v-html="contractContent" class="ql-editor prose prose-sm max-w-none" />
          </div>

          <Message severity="warn" :closable="false" class="text-xs">
            <i class="pi pi-exclamation-triangle ml-2" />
            لطفاً پیش از امضا، متن فوق را به دقت مطالعه فرمایید. امضای شما به منزله پذیرش تمام مفاد این سند است.
          </Message>
        </div>

        <!-- اطلاعات امضا کننده -->
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span class="text-surface-500 block mb-1">نوع امضا کننده</span>
            <span class="font-medium">
              {{ signature.signer_type === 'contractor' ? 'پیمانکار' : 'کارشناس داخلی' }}
            </span>
          </div>
          <div>
            <span class="text-surface-500 block mb-1">تاریخ امضا</span>
            <DateDisplay v-if="signature.signed_at" :date="signature.signed_at" show-time />
            <span v-else class="text-surface-400">—</span>
          </div>
        </div>

        <!-- لاگ ممیزی (بعد از امضا/رد) -->
        <div
            v-if="isSigned || isRejected"
            class="text-xs bg-surface-50 dark:bg-surface-800 p-3 rounded-lg space-y-1"
        >
          <div class="flex items-center gap-2">
            <i class="pi pi-shield text-green-600" />
            <span class="font-medium">ثبت امن در سامانه</span>
          </div>
          <p class="text-surface-500">IP: {{ signature.ip_address || '—' }}</p>
          <p v-if="signature.comments" class="text-surface-600 mt-2">
            <i class="pi pi-comment ml-1" />
            {{ signature.comments }}
          </p>
        </div>

        <template v-if="!compact">
          <Divider />

          <!-- نظر / دلیل رد -->
          <div v-if="canSign" class="space-y-2">
            <label class="text-sm font-medium">نظر امضا کننده (اختیاری)</label>
            <Textarea
                v-model="comments"
                rows="3"
                maxlength="1000"
                placeholder="در صورت نیاز، توضیحات خود را ثبت کنید..."
                class="w-full"
            />
          </div>

          <!-- ✅ دکمه‌های امضا با OTP -->
          <div v-if="canSign" class="space-y-3">
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
              <div class="flex items-start gap-2">
                <i class="pi pi-mobile text-amber-600 mt-1" />
                <div class="text-xs text-amber-900 dark:text-amber-100">
                  <strong>فرآیند امضا:</strong>
                  <ol class="list-decimal list-inside mt-1 space-y-0.5">
                    <li>متن سند را مطالعه کنید</li>
                    <li>روی «دریافت کد تایید» کلیک کنید</li>
                    <li>کد 6 رقمی ارسال شده به موبایل را وارد کنید</li>
                    <li>سند به صورت الکترونیکی امضا می‌شود</li>
                  </ol>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
              <Button
                  label="دریافت کد تایید و امضا"
                  icon="pi pi-mobile"
                  :loading="requestingOtp"
                  @click="handleRequestOtp"
              />
              <Button
                  label="رد سند"
                  icon="pi pi-times-circle"
                  severity="danger"
                  outlined
                  @click="openReject"
              />
              <small class="text-surface-500 text-xs">
                <i class="pi pi-info-circle ml-1" />
                امضای شما با IP، زمان و کد OTP ثبت و غیرقابل تغییر خواهد بود.
              </small>
            </div>
          </div>

          <Message v-else-if="isPending" severity="warn" :closable="false">
            منتظر امضای کاربر تعیین‌شده برای این سند هستید.
          </Message>
        </template>
      </template>
    </div>
  </div>

  <!-- ✅ دیالوگ OTP -->
  <Dialog
      v-model:visible="otpDialog"
      header="تایید با کد OTP"
      :modal="true"
      :closable="false"
      style="width: min(95vw, 450px)"
  >
    <div class="space-y-4">
      <div class="text-center">
        <div class="w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 flex items-center justify-center mx-auto mb-3">
          <i class="pi pi-mobile text-3xl" />
        </div>
        <h3 class="font-semibold text-lg">کد تایید را وارد کنید</h3>
        <p class="text-sm text-surface-500 mt-2">
          کد 6 رقمی به شماره <strong>{{ otpMobile }}</strong> ارسال شد
        </p>
      </div>

      <!-- Input OTP -->
      <div class="flex justify-center">
        <InputOtp
            v-model="otpCode"
            :length="6"
            :disabled="submitting"
            integer-only
        />
      </div>

      <!-- تایمر -->
      <div class="text-center">
        <div v-if="!isOtpExpired" class="text-sm text-surface-600">
          <i class="pi pi-clock ml-1" />
          زمان باقی‌مانده: <strong class="text-primary">{{ formatOtpTime }}</strong>
        </div>
        <div v-else class="text-sm text-red-600 font-medium">
          <i class="pi pi-exclamation-triangle ml-1" />
          کد منقضی شده است
        </div>
      </div>

      <!-- تلاش‌های باقی‌مانده -->
      <div v-if="remainingAttempts < 3" class="text-center text-xs text-amber-600">
        <i class="pi pi-info-circle ml-1" />
        {{ remainingAttempts }} تلاش دیگر باقی مانده است
      </div>

      <!-- خطا -->
      <Message v-if="otpError" severity="error" :closable="false">
        {{ otpError }}
      </Message>

      <!-- راهنما -->
      <div class="text-xs text-surface-500 text-center bg-surface-50 dark:bg-surface-800/50 p-3 rounded-lg">
        <i class="pi pi-info-circle ml-1" />
        کد وارد شده همراه با IP و زمان ثبت، به عنوان امضای الکترونیکی شما ثبت می‌شود.
      </div>
    </div>

    <template #footer>
      <div class="flex justify-between gap-2 w-full">
        <Button
            label="انصراف"
            severity="secondary"
            outlined
            :disabled="submitting"
            @click="otpDialog = false"
        />
        <div class="flex gap-2">
          <Button
              v-if="isOtpExpired"
              label="درخواست کد جدید"
              icon="pi pi-refresh"
              severity="warn"
              outlined
              :loading="requestingOtp"
              @click="openOtpDialog"
          />
          <Button
              label="تایید و امضا"
              icon="pi pi-check"
              :loading="submitting"
              :disabled="otpCode.length !== 6 || isOtpExpired"
              @click="handleVerifyAndSign"
          />
        </div>
      </div>
    </template>
  </Dialog>

  <!-- دیالوگ رد سند -->
  <Dialog
      v-model:visible="rejectDialog"
      header="دلیل رد سند"
      :style="{ width: 'min(92vw, 480px)' }"
      :modal="true"
      :closable="true"
  >
    <div class="space-y-4">
      <Message severity="warn" :closable="false">
        با رد این سند، فرآیند تایید متوقف می‌شود. دلیل رد الزامی است.
      </Message>

      <Textarea
          v-model="comments"
          rows="4"
          class="w-full"
          placeholder="دلیل رد سند را بنویسید..."
          maxlength="1000"
      />

      <p v-if="rejectionError" class="text-xs text-red-500">{{ rejectionError }}</p>
    </div>

    <template #footer>
      <div class="flex justify-end gap-2">
        <Button label="انصراف" severity="secondary" text @click="rejectDialog = false" />
        <Button
            label="ثبت رد"
            icon="pi pi-times"
            severity="danger"
            :loading="rejecting"
            @click="confirmReject"
        />
      </div>
    </template>
  </Dialog>
</template>

<style scoped>
.contract-content {
  line-height: 2;
  font-size: 14px;
}

.contract-content :deep(h1),
.contract-content :deep(h2),
.contract-content :deep(h3) {
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
  color: #0f172a;
  font-weight: 700;
}

.contract-content :deep(p) {
  margin-bottom: 1rem;
  text-align: justify;
  line-height: 2;
}

.contract-content :deep(ul),
.contract-content :deep(ol) {
  margin-bottom: 1rem;
  padding-right: 2rem;
}

.contract-content :deep(li) {
  margin-bottom: 0.5rem;
}

.contract-content :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin: 1rem 0;
}

.contract-content :deep(th),
.contract-content :deep(td) {
  border: 1px solid #e2e8f0;
  padding: 0.5rem;
}

.contract-content :deep(th) {
  background: #f8fafc;
  font-weight: 600;
}

/* استایل InputOtp */
:deep(.p-inputotp-input) {
  width: 48px !important;
  height: 56px !important;
  font-size: 1.5rem !important;
  font-weight: 700 !important;
  text-align: center !important;
  border-radius: 10px !important;
  margin: 0 4px !important;
}

:deep(.p-inputotp-input:focus) {
  border-color: #1d4ed8 !important;
  box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.15) !important;
}
</style>