<!-- src/views/Signature/SignatureDetail.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useSignatureStore } from '@/stores/signatureStore'
import { showToast } from '@/plugins/toast'
import AmountDisplay from '@/components/contract/AmountDisplay.vue'
import DateDisplay from '@/components/contract/DateDisplay.vue'
import api from '@/api/axios'

const router = useRouter()
const route = useRoute()
const signatureStore = useSignatureStore()

const loading = ref(true)
const submitting = ref(false)
const requestingOtp = ref(false)
const signature = ref(null)
const document = ref(null)

const otpDialog = ref(false)
const otpCode = ref('')
const otpId = ref(null)
const otpExpiresAt = ref(null)
const otpExpiresIn = ref(0)
const otpMobile = ref('')
const otpError = ref('')
const remainingAttempts = ref(3)
let otpTimer = null

const signatureId = computed(() => route.params.id)

onMounted(async () => {
  try {
    const response = await api.get(`/signatures/${signatureId.value}`)
    signature.value = response.data.data.signature
    document.value = response.data.data.document
  } catch (err) {
    showToast({ severity: 'error', summary: 'خطا', detail: err.response?.data?.message || 'خطا در دریافت اطلاعات', life: 5000 })
    router.push('/signatures')
  } finally {
    loading.value = false
  }
})

// تایمر شمارش معکوس
const startOtpTimer = () => {
  clearInterval(otpTimer)
  otpTimer = setInterval(() => {
    if (otpExpiresIn.value > 0) {
      otpExpiresIn.value--
    } else {
      clearInterval(otpTimer)
      showToast({ severity: 'warn', summary: 'کد منقضی شد', detail: 'لطفاً کد جدیدی درخواست کنید', life: 4000 })
    }
  }, 1000)
}

const formatOtpTime = computed(() => {
  const m = Math.floor(otpExpiresIn.value / 60)
  const s = otpExpiresIn.value % 60
  return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`
})

// درخواست کد OTP
const handleRequestOtp = async () => {
  requestingOtp.value = true
  otpError.value = ''

  try {
    // ✅ استفاده از متد جدید
    const response = await signatureStore.requestOtp(signatureId.value, 0)
    otpId.value = response.data.otp_id
    otpExpiresIn.value = response.data.expires_in_seconds
    otpMobile.value = response.data.mobile

    otpDialog.value = true
    startOtpTimer()

    showToast({ severity: 'success', summary: 'کد ارسال شد', detail: `کد 6 رقمی به شماره ${otpMobile.value} ارسال شد`, life: 4000 })
  } catch (err) {
    otpError.value = err.response?.data?.message || 'خطا در ارسال کد'
    showToast({ severity: 'error', summary: 'خطا', detail: otpError.value, life: 5000 })
  } finally {
    requestingOtp.value = false
  }
}


// تایید کد و امضا
const handleVerifyAndSign = async () => {
  if (otpCode.value.length !== 6) {
    otpError.value = 'کد باید 6 رقم باشد'
    return
  }

  submitting.value = true
  otpError.value = ''

  try {
    // ✅ استفاده از متد جدید
    await signatureStore.verifyAndSign(signatureId.value, {
      otp_id: otpId.value,
      code: otpCode.value,
      comments: `امضا با کد تایید OTP در تاریخ ${new Date().toLocaleString('fa-IR')}`
    })

    showToast({ severity: 'success', summary: 'امضا ثبت شد', detail: 'قرارداد با موفقیت امضا شد', life: 4000 })
    otpDialog.value = false
    setTimeout(() => router.push('/signatures'), 1500)
  } catch (err) {
    const data = err.response?.data
    otpError.value = data?.message || 'خطا در تایید کد'
    remainingAttempts.value = data?.data?.remaining_attempts ?? remainingAttempts.value

    if (data?.data?.attempts_exhausted) {
      otpDialog.value = false
      showToast({ severity: 'warn', summary: 'تلاش‌ها به پایان رسید', detail: 'لطفاً کد جدیدی درخواست کنید', life: 5000 })
    } else {
      showToast({ severity: 'error', summary: 'کد اشتباه', detail: otpError.value, life: 4000 })
    }
  } finally {
    submitting.value = false
  }
}



const isOtpExpired = computed(() => otpExpiresIn.value <= 0)
</script>

<template>
  <div class="p-6">
    <div v-if="loading" class="text-center py-12">
      <ProgressSpinner />
    </div>

    <div v-else-if="signature && document" class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 class="text-2xl font-bold flex items-center gap-2">
            <i class="pi pi-signature text-primary" />
            بررسی و امضای {{ document.type === 'contract' ? 'قرارداد' : 'الحاقیه' }}
          </h1>
          <p class="text-surface-600 dark:text-surface-400 text-sm mt-1">
            {{ document.number }} - {{ document.subject }}
          </p>
        </div>

        <Button label="بازگشت" icon="pi pi-arrow-right" severity="secondary" outlined @click="router.push('/signatures')" />
      </div>

      <!-- اطلاعات سند -->
      <Card>
        <template #title>
          <span class="flex items-center gap-2">
            <i class="pi pi-info-circle text-primary" />
            اطلاعات {{ document.type === 'contract' ? 'قرارداد' : 'الحاقیه' }}
          </span>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
          <div>
            <span class="text-surface-500 block mb-1">شماره:</span>
            <span class="font-mono font-medium">{{ document.number }}</span>
          </div>
          <div>
            <span class="text-surface-500 block mb-1">طرف قرارداد:</span>
            <span class="font-medium">{{ document.party }}</span>
          </div>
          <div>
            <span class="text-surface-500 block mb-1">مبلغ:</span>
            <AmountDisplay :amount="document.amount" />
          </div>
          <div>
            <span class="text-surface-500 block mb-1">تاریخ پایان:</span>
            <DateDisplay :date="document.end_date" show-status />
          </div>
        </div>
      </Card>

      <!-- ✅ متن قرارداد -->
      <Card v-if="document.content">
        <template #title>
          <span class="flex items-center justify-between w-full">
            <span class="flex items-center gap-2">
              <i class="pi pi-file-edit text-primary" />
              متن کامل {{ document.type === 'contract' ? 'قرارداد' : 'الحاقیه' }}
            </span>
            <Tag v-if="document.pages_count" :value="`${document.pages_count} صفحه`" severity="info" />
          </span>
        </template>

        <div class="contract-content bg-surface-50 dark:bg-surface-800/50 border border-surface-200 dark:border-surface-700 rounded-lg p-6 max-h-[600px] overflow-y-auto">
          <div v-html="document.content" class="ql-editor prose prose-sm max-w-none" dir="rtl" />
        </div>

        <Message severity="info" :closable="false" class="mt-4">
          <i class="pi pi-info-circle ml-2" />
          لطفاً پیش از امضا، متن فوق را به دقت مطالعه فرمایید. امضای شما به منزله پذیرش تمام مفاد این سند است.
        </Message>
      </Card>

      <EmptyState v-else title="متن قرارداد ثبت نشده" icon="pi pi-file-edit" />

      <!-- دکمه امضا -->
      <Card>
        <template #title>
          <span class="flex items-center gap-2">
            <i class="pi pi-signature text-primary" />
            امضای الکترونیکی
          </span>
        </template>

        <div class="space-y-4">
          <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
            <div class="flex items-start gap-3">
              <i class="pi pi-shield text-amber-600 text-xl mt-1" />
              <div class="text-sm text-amber-900 dark:text-amber-100">
                <strong>فرآیند امضا:</strong>
                <ol class="list-decimal list-inside mt-2 space-y-1">
                  <li>متن قرارداد را به دقت مطالعه کنید</li>
                  <li>روی دکمه «دریافت کد تایید» کلیک کنید</li>
                  <li>کد 6 رقمی ارسال شده به موبایل خود را وارد کنید</li>
                  <li>قرارداد به صورت الکترونیکی امضا می‌شود</li>
                </ol>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="text-sm text-surface-600">
              <i class="pi pi-mobile ml-2" />
              کد تایید به شماره موبایل ثبت‌شده در پروفایل شما ارسال خواهد شد.
            </div>

            <Button
                label="دریافت کد تایید و امضا"
                icon="pi pi-mobile"
                :loading="requestingOtp"
                @click="handleRequestOtp"
            />
          </div>
        </div>
      </Card>
    </div>

    <!-- ✅ دیالوگ OTP -->
    <Dialog v-model:visible="otpDialog" header="تایید با کد OTP" :modal="true" :closable="false" style="width: min(95vw, 450px)">
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
              class="otp-inputs"
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
                @click="handleRequestOtp"
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
  </div>
</template>

<style scoped>
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

.contract-content :deep(strong),
.contract-content :deep(b) {
  font-weight: 700;
}

.otp-inputs :deep(.p-inputotp-input) {
  width: 48px !important;
  height: 56px !important;
  font-size: 1.5rem !important;
  font-weight: 700 !important;
  text-align: center !important;
  border-radius: 10px !important;
  margin: 0 4px !important;
}

.otp-inputs :deep(.p-inputotp-input:focus) {
  border-color: #1d4ed8 !important;
  box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.15) !important;
}
</style>