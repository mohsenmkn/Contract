<!-- src/views/Signature/SignatureDetail.vue -->
<script setup>
import { ref, computed, onMounted, nextTick, watch, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useSignatureStore } from '@/stores/signatureStore'
import { showToast } from '@/plugins/toast'
import AmountDisplay from '@/components/contract/AmountDisplay.vue'
import DateDisplay from '@/components/contract/DateDisplay.vue'
import OtpInput from '@/components/common/OtpInput.vue'
import api from '@/api/axios'

const OTP_LENGTH = 6
const MAX_ATTEMPTS = 3

const router = useRouter()
const route = useRoute()
const signatureStore = useSignatureStore()

const loading = ref(true)
const submitting = ref(false)
const requestingOtp = ref(false)
const signature = ref(null)
// نام «document» با متغیر سراسری مرورگر تداخل داشت
const doc = ref(null)
const signers = ref([])

const otpDialog = ref(false)
const otpRef = ref(null)
const otpCode = ref('')
const otpId = ref(null)
const otpMobile = ref('')
const otpError = ref('')
const otpExpiresIn = ref(0)
const remainingAttempts = ref(MAX_ATTEMPTS)

let otpExpiresAt = 0
let otpTimer = null
let redirectTimer = null

const signatureId = computed(() => route.params.id)
const docLabel = computed(() => (doc.value?.type === 'contract' ? 'قرارداد' : 'الحاقیه'))
const isOtpExpired = computed(() => otpExpiresIn.value <= 0)
const isSigned = computed(() => signature.value?.status === 'signed' || !!signature.value?.signed_at)

const formatOtpTime = computed(() => {
  const secondsLeft = Number.isFinite(Number(otpExpiresIn.value)) ? Math.max(0, Math.floor(Number(otpExpiresIn.value))) : 0
  const m = Math.floor(secondsLeft / 60)
  const s = secondsLeft % 60
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
})

/* ---------- کمکی‌های نمایش امضاکنندگان ---------- */
const formatDateTime = (value) =>
    value ? new Date(value).toLocaleString('fa-IR', { dateStyle: 'medium', timeStyle: 'short' }) : ''
const signerImage = (s) => s.signature_image_url || s.signature_image || s.signature_url || null
const isSignerSigned = (s) => s.status === 'signed' || !!s.signed_at

/* ---------- دریافت اطلاعات ---------- */
onMounted(async () => {
  try {
    const response = await api.get(`/signatures/${signatureId.value}`)
    const data = response.data.data
    signature.value = data.signature
    doc.value = data.document
    signers.value = data.signers ?? data.document?.signers ?? []
  } catch (err) {
    showToast({
      severity: 'error',
      summary: 'خطا',
      detail: err.response?.data?.message || 'خطا در دریافت اطلاعات',
      life: 5000
    })
    router.push('/signatures')
  } finally {
    loading.value = false
  }
})

onBeforeUnmount(() => {
  clearInterval(otpTimer)
  clearTimeout(redirectTimer)
})

/* ---------- تایمر (بر اساس زمان پایان، نه شمارندهٔ کاهشی) ---------- */
const tick = () => {
  otpExpiresIn.value = Math.max(0, Math.ceil((otpExpiresAt - Date.now()) / 1000))
  if (otpExpiresIn.value === 0) {
    clearInterval(otpTimer)
    showToast({
      severity: 'warn',
      summary: 'کد منقضی شد',
      detail: 'لطفاً کد جدیدی درخواست کنید',
      life: 4000
    })
  }
}

const startOtpTimer = (seconds) => {
  clearInterval(otpTimer)
  const duration = Number(seconds)
  if (!Number.isFinite(duration) || duration <= 0) {
    otpExpiresIn.value = 0
    otpExpiresAt = 0
    return false
  }
  otpExpiresAt = Date.now() + duration * 1000
  tick()
  otpTimer = setInterval(tick, 1000)
  return true
}

/* ---------- دیالوگ ---------- */
const focusOtp = async () => {
  await nextTick()
  otpRef.value?.focus()
}

watch(otpDialog, (visible) => {
  if (visible) {
    otpCode.value = ''
    otpError.value = ''
  } else {
    clearInterval(otpTimer)
  }
})

// پاک شدن خطا هنگام تایپ مجدد
watch(otpCode, () => {
  if (otpError.value) otpError.value = ''
})

/* ---------- درخواست کد ---------- */
const handleRequestOtp = async () => {
  requestingOtp.value = true
  otpError.value = ''


  try {
    const response = await signatureStore.requestOtp(signatureId.value)

    // پشتیبانی از هر دو ساختار پاسخ
    const data = response?.data?.data ?? response?.data ?? response

    if (!data?.otp_id || !Number.isFinite(Number(data.expires_in_seconds)) || Number(data.expires_in_seconds) <= 0) {
      throw new Error('پاسخ سرور برای شناسه یا زمان انقضای کد تأیید معتبر نیست.')
    }

    otpId.value = data.otp_id
    otpExpiresIn.value = Number(data.expires_in_seconds)
    otpMobile.value = data.mobile

    otpDialog.value = true
    if (!startOtpTimer(data.expires_in_seconds)) {
      throw new Error('زمان انقضای کد تأیید معتبر نیست. لطفاً دوباره درخواست کد کنید.')
    }

    showToast({
      severity: 'success',
      summary: 'کد ارسال شد',
      detail: `کد 6 رقمی به شماره ${otpMobile.value} ارسال شد`,
      life: 4000
    })
  } catch (err) {
    console.error('OTP request error:', err)

    otpError.value = err.response?.data?.message
        || err.message
        || 'خطا در ارسال کد'

    showToast({
      severity: 'error',
      summary: 'خطا',
      detail: otpError.value,
      life: 5000
    })
  } finally {
    requestingOtp.value = false
  }
}

/* ---------- تایید کد و امضا ---------- */
const handleVerifyAndSign = async () => {
  if (submitting.value || isOtpExpired.value) return

  if (otpCode.value.length !== OTP_LENGTH) {
    otpError.value = `کد باید ${OTP_LENGTH} رقم باشد`
    return
  }

  submitting.value = true
  otpError.value = ''

  try {
    await signatureStore.verifyAndSign(signatureId.value, {
      otp_id: otpId.value,
      code: otpCode.value,
      comments: `امضا با کد تایید OTP در تاریخ ${new Date().toLocaleString('fa-IR')}`
    })

    showToast({ severity: 'success', summary: 'امضا ثبت شد', detail: 'سند با موفقیت امضا شد', life: 4000 })
    otpDialog.value = false
    redirectTimer = setTimeout(() => router.push('/signatures'), 1500)
  } catch (err) {
    const data = err.response?.data
    otpError.value = data?.message || 'خطا در تایید کد'
    remainingAttempts.value = data?.data?.remaining_attempts ?? remainingAttempts.value
    otpCode.value = ''

    if (data?.data?.attempts_exhausted) {
      otpDialog.value = false
      showToast({
        severity: 'warn',
        summary: 'تلاش‌ها به پایان رسید',
        detail: 'لطفاً کد جدیدی درخواست کنید',
        life: 5000
      })
    } else {
      showToast({ severity: 'error', summary: 'کد اشتباه', detail: otpError.value, life: 4000 })
    }
  } finally {
    submitting.value = false
    if (otpDialog.value) {
      await nextTick()
      otpRef.value?.focus()
    }
  }
}
</script>

<template>
  <div class="signature-page p-4 md:p-6 lg:p-8" dir="rtl">
    <div v-if="loading" class="text-center py-12">
      <ProgressSpinner />
    </div>

    <div v-else-if="signature && doc" class="max-w-7xl mx-auto space-y-6">
      <!-- سربرگ -->
      <header class="flex items-start justify-between flex-wrap gap-4">
        <div class="min-w-0">
          <h1 class="text-2xl md:text-3xl font-bold flex items-center gap-3">
            <i class="pi pi-pen-to-square text-primary" />
            بررسی و امضای {{ docLabel }}
          </h1>
          <p class="text-surface-600 dark:text-surface-400 text-sm mt-2 leading-7">
            {{ doc.subject }}
          </p>
        </div>

        <div class="flex items-center gap-3">
          <Tag
              :value="isSigned ? 'امضا شده' : 'در انتظار امضای شما'"
              :severity="isSigned ? 'success' : 'warn'"
          />
          <Button
              label="بازگشت"
              icon="pi pi-arrow-right"
              severity="secondary"
              outlined
              @click="router.push('/signatures')"
          />
        </div>
      </header>

      <!-- خلاصه سند -->
      <section
          class="grid grid-cols-2 xl:grid-cols-4 gap-px rounded-xl overflow-hidden border border-surface-200 dark:border-surface-700 bg-surface-200 dark:bg-surface-700"
          aria-label="اطلاعات سند"
      >
        <div class="bg-surface-0 dark:bg-surface-900 p-4">
          <div class="text-xs text-surface-500 mb-1">شماره {{ docLabel }}</div>
          <div class="font-mono font-semibold" dir="ltr">{{ doc.number }}</div>
        </div>
        <div class="bg-surface-0 dark:bg-surface-900 p-4">
          <div class="text-xs text-surface-500 mb-1">طرف قرارداد</div>
          <div class="font-semibold">{{ doc.party }}</div>
        </div>
        <div class="bg-surface-0 dark:bg-surface-900 p-4">
          <div class="text-xs text-surface-500 mb-1">مبلغ</div>
          <AmountDisplay :amount="doc.amount" />
        </div>
        <div class="bg-surface-0 dark:bg-surface-900 p-4">
          <div class="text-xs text-surface-500 mb-1">تاریخ پایان</div>
          <DateDisplay :date="doc.end_date" show-status />
        </div>
      </section>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- متن سند + امضاها -->
        <div class="lg:col-span-2 min-w-0">
          <Card v-if="doc.content">
            <template #title>
              <span class="flex items-center justify-between w-full">
                <span class="flex items-center gap-2">
                  <i class="pi pi-file-edit text-primary" />
                  متن کامل {{ docLabel }}
                </span>
                <Tag v-if="doc.pages_count" :value="`${doc.pages_count} صفحه`" severity="info" />
              </span>
            </template>

            <template #content>
              <Message severity="info" :closable="false" class="mb-4">
                پیش از امضا، متن را با دقت بخوانید. امضای شما به معنی پذیرش تمام مفاد این سند است.
              </Message>

              <article
                  class="contract-content bg-surface-50 dark:bg-surface-800/50 border border-surface-200 dark:border-surface-700 rounded-xl p-4 md:p-6 max-h-[640px] overflow-y-auto"
              >
                <div v-html="doc.content" class="ql-editor prose prose-sm max-w-none" dir="rtl" />
              </article>

              <!-- امضاها: انتهای متن سند -->
              <section v-if="signers.length" class="mt-8" aria-label="امضاکنندگان">
                <h2 class="font-semibold mb-3 flex items-center gap-2">
                  <i class="pi pi-users text-primary" />
                  امضاکنندگان
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                  <div
                      v-for="s in signers"
                      :key="s.id ?? s.name"
                      class="rounded-xl border border-surface-200 dark:border-surface-700 p-3 flex flex-col"
                  >
                    <div
                        class="signer-box flex-1 flex items-center justify-center rounded-lg mb-3 overflow-hidden"
                        :class="isSignerSigned(s) ? 'signer-box--signed' : 'signer-box--pending'"
                    >
                      <img
                          v-if="isSignerSigned(s) && signerImage(s)"
                          :src="signerImage(s)"
                          :alt="`امضای ${s.name}`"
                          class="max-h-24 max-w-full object-contain p-2"
                      />
                      <div
                          v-else-if="isSignerSigned(s)"
                          class="text-center text-xs text-green-700 dark:text-green-400 p-3"
                      >
                        <i class="pi pi-verified text-2xl block mb-1" />
                        امضای الکترونیکی با کد تایید
                      </div>
                      <div v-else class="text-center text-xs text-surface-400 p-3">
                        <i class="pi pi-clock text-xl block mb-1" />
                        در انتظار امضا
                      </div>
                    </div>

                    <div class="text-sm font-semibold">{{ s.name }}</div>
                    <div v-if="s.role_label || s.role" class="text-xs text-surface-500">
                      {{ s.role_label || s.role }}
                    </div>
                    <div v-if="isSignerSigned(s) && s.signed_at" class="text-xs text-surface-500 mt-1">
                      {{ formatDateTime(s.signed_at) }}
                    </div>
                  </div>
                </div>
              </section>
            </template>
          </Card>

          <EmptyState v-else title="متن قرارداد ثبت نشده" icon="pi pi-file-edit" />
        </div>

        <!-- پنل امضا -->
        <aside class="lg:sticky lg:top-6">
          <Card>
            <template #title>
              <span class="flex items-center gap-2">
                <i class="pi pi-pen-to-square text-primary" />
                امضای الکترونیکی
              </span>
            </template>

            <template #content>
              <div v-if="isSigned" class="space-y-3">
                <Message severity="success" :closable="false">شما این سند را امضا کرده‌اید.</Message>
                <p v-if="signature.signed_at" class="text-sm text-surface-500">
                  زمان ثبت امضا: {{ formatDateTime(signature.signed_at) }}
                </p>
              </div>

              <div v-else class="space-y-5">
                <ol class="space-y-3 text-sm">
                  <li class="flex gap-3">
                    <span class="step-dot">1</span>
                    <span>متن سند را مطالعه کنید.</span>
                  </li>
                  <li class="flex gap-3">
                    <span class="step-dot">2</span>
                    <span>کد تایید را دریافت کنید؛ به موبایل ثبت‌شده در پروفایل شما پیامک می‌شود.</span>
                  </li>
                  <li class="flex gap-3">
                    <span class="step-dot">3</span>
                    <span>کد {{ OTP_LENGTH }} رقمی را وارد کنید تا سند امضا شود.</span>
                  </li>
                </ol>

                <Button
                    label="دریافت کد تایید و امضا"
                    icon="pi pi-mobile"
                    class="w-full"
                    :loading="requestingOtp"
                    @click="handleRequestOtp"
                />
              </div>
            </template>
          </Card>
        </aside>
      </div>
    </div>

    <!-- دیالوگ ورود کد یکبار مصرف -->
    <Dialog
        v-model:visible="otpDialog"
        modal
        header="تأیید هویت و امضای الکترونیکی"
        :closable="false"
        :draggable="false"
        :close-on-escape="!submitting"
        :pt="{ root: { dir: 'rtl' } }"
        style="width: min(95vw, 460px)"
        @show="focusOtp"
    >
      <div class="space-y-5" dir="rtl">
        <div class="text-center">
          <div
              class="w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 flex items-center justify-center mx-auto mb-3"
          >
            <i class="pi pi-mobile text-3xl" />
          </div>
          <h3 class="font-semibold text-lg">کد تایید را وارد کنید</h3>
          <p class="text-sm text-surface-500 mt-2">
            کد {{ OTP_LENGTH }} رقمی به شماره
            <strong><bdi dir="ltr">{{ otpMobile }}</bdi></strong>
            ارسال شد
          </p>
        </div>

        <!-- ورود کد: از چپ به راست -->
        <OtpInput
            ref="otpRef"
            v-model="otpCode"
            :length="OTP_LENGTH"
            :disabled="submitting || isOtpExpired"
            :invalid="!!otpError"
            @complete="handleVerifyAndSign"
            @submit="handleVerifyAndSign"
        />

        <div class="text-center min-h-[1.5rem]" aria-live="polite">
          <div v-if="!isOtpExpired" class="text-sm text-surface-600 dark:text-surface-400">
            <i class="pi pi-clock ml-1" />
            زمان باقی‌مانده:
            <strong class="text-primary tabular-nums" dir="ltr">{{ formatOtpTime }}</strong>
          </div>
          <div v-else class="text-sm text-red-600 font-medium">
            <i class="pi pi-exclamation-triangle ml-1" />
            کد منقضی شده است
          </div>
        </div>

        <div v-if="remainingAttempts < MAX_ATTEMPTS" class="text-center text-xs text-amber-600">
          <i class="pi pi-info-circle ml-1" />
          {{ remainingAttempts }} تلاش دیگر باقی مانده است
        </div>

        <Message v-if="otpError" severity="error" :closable="false">{{ otpError }}</Message>

        <p
            class="text-xs text-surface-500 text-center bg-surface-50 dark:bg-surface-800/50 p-3 rounded-lg leading-6"
        >
          کد وارد شده همراه با IP و زمان ثبت، به عنوان امضای الکترونیکی شما ذخیره می‌شود.
        </p>
      </div>

      <template #footer>
        <div class="flex flex-col-reverse sm:flex-row sm:justify-between gap-3 w-full">
          <Button
              label="انصراف"
              severity="secondary"
              outlined
              :disabled="submitting"
              @click="otpDialog = false"
          />
          <div class="flex flex-col sm:flex-row gap-2">
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
                :disabled="otpCode.length !== OTP_LENGTH || isOtpExpired"
                @click="handleVerifyAndSign"
            />
          </div>
        </div>
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.step-dot {
  flex: none;
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 9999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--p-primary-color, #2563eb);
  background: color-mix(in srgb, var(--p-primary-color, #2563eb) 12%, transparent);
}

.signer-box {
  min-height: 6.5rem;
}

.signer-box--signed {
  background: color-mix(in srgb, #16a34a 6%, transparent);
  border: 1px solid color-mix(in srgb, #16a34a 30%, transparent);
}

.signer-box--pending {
  border: 1px dashed var(--p-content-border-color, #cbd5e1);
}

.contract-content :deep(h1),
.contract-content :deep(h2),
.contract-content :deep(h3) {
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
  font-weight: 700;
  color: inherit;
}

.contract-content :deep(p) {
  margin-bottom: 1rem;
  text-align: justify;
  line-height: 2;
}

.contract-content :deep(ul),
.contract-content :deep(ol) {
  margin-bottom: 1rem;
  padding-inline-start: 2rem;
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
  border: 1px solid var(--p-content-border-color, #e2e8f0);
  padding: 0.5rem;
}

.contract-content :deep(th) {
  background: color-mix(in srgb, currentColor 6%, transparent);
  font-weight: 600;
}

.contract-content :deep(strong),
.contract-content :deep(b) {
  font-weight: 700;
}
</style>
