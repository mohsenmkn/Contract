<template>
  <main class="forgot-page" dir="rtl">
    <div class="forgot-background">
    <div class="background-shape shape-one"></div>
    <div class="background-shape shape-two"></div>
    <div class="background-shape shape-three"></div>
    <div class="background-grid"></div>
    <div class="floating-icons">
    <i class="pi pi-key floating-icon icon-1"></i>
    <i class="pi pi-shield floating-icon icon-2"></i>
    <i class="pi pi-lock floating-icon icon-3"></i>
    <i class="pi pi-mobile floating-icon icon-4"></i>
    <i class="pi pi-check-circle floating-icon icon-5"></i>
    </div>
    </div>
    <div class="forgot-shell">
      <div class="forgot-card">
        <!-- هدر کارت -->
        <header class="card-header">
          <div class="brand-mark">
            <i :class="currentStepIcon"></i>
          </div>
          <h1 class="card-title">{{ currentStepTitle }}</h1>
          <p class="card-description">{{ currentStepDescription }}</p>
        </header>

        <!-- استپر مراحل -->
        <div class="stepper">
          <div class="stepper-track">
            <div class="stepper-progress" :style="{ width: progressWidth }"></div>
          </div>
          <div class="stepper-steps">
            <div
                v-for="s in steps"
                :key="s.number"
                class="step"
                :class="{
            active: step === s.number,
            completed: step > s.number
          }"
            >
              <div class="step-circle">
                <i v-if="step > s.number" class="pi pi-check"></i>
                <span v-else>{{ s.number }}</span>
              </div>
              <span class="step-label">{{ s.title }}</span>
            </div>
          </div>
        </div>

        <!-- پیام خطا -->
        <Transition name="message">
          <div v-if="errorMessage" class="error-alert" role="alert">
            <i class="pi pi-exclamation-triangle"></i>
            <span>{{ errorMessage }}</span>
          </div>
        </Transition>

        <!-- پیام موفقیت -->
        <Transition name="message">
          <div v-if="successMessage" class="success-alert" role="alert">
            <i class="pi pi-check-circle"></i>
            <span>{{ successMessage }}</span>
          </div>
        </Transition>

        <!-- مرحله ۱: ورود شماره موبایل -->
        <form v-if="step === 1" class="auth-form" @submit.prevent="sendOtp">
          <div class="field-group">
            <label for="mobile" class="field-label">
              شماره موبایل <span class="required">*</span>
            </label>
            <div class="input-wrapper">
              <i class="pi pi-mobile input-icon"></i>
              <InputText
                  id="mobile"
                  v-model="mobile"
                  type="tel"
                  inputmode="numeric"
                  dir="ltr"
                  maxlength="11"
                  placeholder="09123456789"
                  class="auth-input"
                  :disabled="loading"
                  @input="normalizeMobile"
                  :class="{ invalid: mobileError }"
              />
            </div>
            <p class="field-hint" v-if="!mobileError">
              کد تأیید به این شماره پیامک خواهد شد.
            </p>
            <p class="field-error" v-if="mobileError">{{ mobileError }}</p>
          </div>

          <button type="submit" class="submit-btn" :disabled="loading || mobile.length < 11">
            <template v-if="!loading">
              <i class="pi pi-send"></i>
              ارسال کد تأیید
            </template>
            <template v-else>
              در حال ارسال...
              <i class="pi pi-spin pi-spinner"></i>
            </template>
          </button>

          <div class="back-link-wrapper">
            <router-link to="/login" class="back-link">
              <i class="pi pi-arrow-right"></i>
              بازگشت به صفحه ورود
            </router-link>
          </div>
        </form>

        <!-- مرحله ۲: وارد کردن کد تأیید -->
        <form v-else-if="step === 2" class="auth-form" @submit.prevent="verifyOtp">
          <div class="mobile-summary">
            <div class="summary-content">
              <i class="pi pi-mobile"></i>
              <span>
            کد تأیید به شماره <strong>{{ formattedMobile }}</strong> ارسال شد.
          </span>
            </div>
            <button type="button" class="edit-btn" @click="editMobile" title="ویرایش شماره">
              <i class="pi pi-pencil"></i>
              ویرایش
            </button>
          </div>

          <div class="field-group">
            <label class="field-label center-label">کد ۵ رقمی پیامک شده را وارد کنید</label>
            <div class="otp-wrapper" dir="ltr">
              <input
                  v-for="(digit, idx) in otpDigits"
                  :key="idx"
                  :ref="el => setOtpRef(el, idx)"
                  v-model="otpDigits[idx]"
                  type="text"
                  inputmode="numeric"
                  pattern="[0-9]*"
                  maxlength="1"
                  class="otp-box"
                  :class="{ filled: digit !== '', error: errorMessage }"
                  @input="e => handleOtpInput(e, idx)"
                  @keydown="e => handleBackspace(e, idx)"
                  @paste="handlePaste"
                  :disabled="loading"
              />
            </div>
          </div>

          <!-- تایمر و ارسال مجدد -->
          <div class="timer-wrapper">
        <span v-if="timeLeft > 0" class="timer-countdown">
          <i class="pi pi-clock"></i>
          ارسال مجدد تا {{ formattedTime }}
        </span>
            <button
                v-else
                type="button"
                class="resend-btn"
                @click="resendOtp"
                :disabled="loading"
            >
              <i class="pi pi-refresh" :class="{ 'pi-spin': loading }"></i>
              ارسال مجدد کد تأیید
            </button>
          </div>

          <button type="submit" class="submit-btn" :disabled="loading || otp.length < 5">
            <template v-if="!loading">
              <i class="pi pi-check"></i>
              تأیید و ادامه
            </template>
            <template v-else>
              در حال تأیید...
              <i class="pi pi-spin pi-spinner"></i>
            </template>
          </button>

          <div class="back-link-wrapper">
            <button type="button" class="back-link" @click="editMobile">
              <i class="pi pi-arrow-right"></i>
              تغییر شماره موبایل
            </button>
          </div>
        </form>

        <!-- مرحله ۳: تعیین رمز عبور جدید -->
        <form v-else-if="step === 3" class="auth-form" @submit.prevent="resetPassword">
          <div class="field-group">
            <label for="new-password" class="field-label">
              رمز عبور جدید <span class="required">*</span>
            </label>
            <div class="input-wrapper password-wrapper">
              <i class="pi pi-lock input-icon left-icon"></i>
              <Password
                  id="new-password"
                  v-model="newPassword"
                  :feedback="false"
                  toggleMask
                  placeholder="••••••••"
                  inputClass="auth-input password-input"
                  dir="ltr"
              />
            </div>
          </div>

          <!-- قوانین رمز عبور -->
          <div class="password-rules">
            <div class="rule" :class="{ valid: passwordRules.minLength }">
              <i :class="passwordRules.minLength ? 'pi pi-check' : 'pi pi-circle'"></i>
              <span>حداقل ۶ کاراکتر</span>
            </div>
            <div class="rule" :class="{ valid: passwordRules.hasLetter }">
              <i :class="passwordRules.hasLetter ? 'pi pi-check' : 'pi pi-circle'"></i>
              <span>شامل حروف لاتین</span>
            </div>
            <div class="rule" :class="{ valid: passwordRules.hasNumber }">
              <i :class="passwordRules.hasNumber ? 'pi pi-check' : 'pi pi-circle'"></i>
              <span>شامل عدد</span>
            </div>
          </div>

          <div class="field-group">
            <label for="confirm-password" class="field-label">
              تکرار رمز عبور <span class="required">*</span>
            </label>
            <div class="input-wrapper password-wrapper">
              <i class="pi pi-lock input-icon left-icon"></i>
              <Password
                  id="confirm-password"
                  v-model="confirmPassword"
                  :feedback="false"
                  toggleMask
                  placeholder="••••••••"
                  inputClass="auth-input password-input"
                  dir="ltr"
                  :class="{ invalid: confirmPassword && newPassword !== confirmPassword }"
              />
            </div>
            <p
                v-if="confirmPassword && newPassword !== confirmPassword"
                class="field-error"
            >
              تکرار رمز عبور با رمز عبور جدید مطابقت ندارد.
            </p>
          </div>

          <button
              type="submit"
              class="submit-btn success-btn"
              :disabled="loading || !canResetPassword"
          >
            <template v-if="!loading">
              <i class="pi pi-lock"></i>
              ثبت رمز عبور جدید و ورود
            </template>
            <template v-else>
              در حال ثبت...
              <i class="pi pi-spin pi-spinner"></i>
            </template>
          </button>

          <div class="back-link-wrapper">
            <router-link to="/login" class="back-link">
              انصراف و بازگشت به صفحه ورود
            </router-link>
          </div>
        </form>

        <!-- فوتر -->
        <footer class="card-footer">
          <div class="footer-divider">
            <span></span>
            <i class="pi pi-shield"></i>
            <span></span>
          </div>
          <p class="footer-text">
            دسترسی شما به سامانه به‌صورت امن مدیریت می‌شود.
          </p>
          <p class="footer-version">سامانه مدیریت یکپارچه قراردادها</p>
        </footer>
      </div>
    </div>
  </main>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import axios from '@/api/axios.js'

const router = useRouter()

// --- حالت‌های عمومی ---
const step = ref(1)
const steps = [
  { number: 1, title: 'شماره همراه' },
  { number: 2, title: 'کد تأیید' },
  { number: 3, title: 'رمز جدید' }
]

const mobile = ref('')
const otp = ref('')
const otpDigits = reactive(['', '', '', '', ''])
const newPassword = ref('')
const confirmPassword = ref('')
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const resendCount = ref(0)
const timeLeft = ref(120)
const otpInputs = ref([])
let timerInterval = null

// --- مقادیر محاسباتی ---
const currentStepTitle = computed(() => {
  if (step.value === 1) return 'بازیابی رمز عبور'
if (step.value === 2) return 'تأیید شماره همراه'
return 'تعیین رمز عبور جدید'
})

const currentStepDescription = computed(() => {
  if (step.value === 1)
return 'جهت بازیابی حساب کاربری خود در سامانه قراردادها، شماره موبایل ثبت‌شده را وارد نمایید'
if (step.value === 2) return 'کد ۵ رقمی پیامک شده را وارد کنید'
return 'یک رمز عبور قوی و مطمئن برای حساب خود تعیین نمایید'
})

const currentStepIcon = computed(() => {
  if (step.value === 1) return 'pi pi-mobile'
if (step.value === 2) return 'pi pi-key'
return 'pi pi-lock'
})

const progressWidth = computed(() => {
  if (step.value === 1) return '0%'
if (step.value === 2) return '50%'
return '100%'
})

const formattedTime = computed(() => {
  const m = Math.floor(timeLeft.value / 60).toString().padStart(2, '0')
  const s = (timeLeft.value % 60).toString().padStart(2, '0')
  return `${m}:${s}`
})

const formattedMobile = computed(() => {
  if (mobile.value.length === 11) {
  return `${mobile.value.slice(0, 4)} ${mobile.value.slice(4, 7)} ${mobile.value.slice(7)}`
}
return mobile.value
})

const passwordRules = computed(() => ({
  minLength: newPassword.value.length >= 6,
  hasLetter: /[a-zA-Zآ-ی]/.test(newPassword.value),
  hasNumber: /\d/.test(newPassword.value)
}))

const canResetPassword = computed(() => {
  return (
      passwordRules.value.minLength &&
      passwordRules.value.hasLetter &&
      passwordRules.value.hasNumber &&
      newPassword.value === confirmPassword.value
)
})

const mobileError = computed(() => {
  if (mobile.value && !/^09\d{9}$/.test(mobile.value)) {
  return 'شماره موبایل باید با 09 شروع شده و 11 رقم باشد.'
}
return ''
})

// --- توابع کمکی ---
const toLatinDigits = (str) => {
  if (!str) return ''
return str
    .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
    .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d))
}

const normalizeMobile = () => {
  mobile.value = toLatinDigits(mobile.value).replace(/\D/g, '')
}

const setOtpRef = (el, idx) => {
  if (el) otpInputs.value[idx] = el
}

const focusOtp = (idx) => {
  nextTick(() => {
  if (otpInputs.value[idx]) {
  otpInputs.value[idx].focus()
  otpInputs.value[idx].select?.()
}
})
}

const resetOtp = () => {
  for (let i = 0; i < 5; i++) otpDigits[i] = ''
otp.value = ''
}

const clearTimer = () => {
  if (timerInterval) clearInterval(timerInterval)
  timerInterval = null
}

const startTimer = (seconds = 120) => {
  clearTimer()
  timeLeft.value = seconds
  timerInterval = setInterval(() => {
    if (timeLeft.value > 0) {
  timeLeft.value--
} else {
  clearTimer()
}
}, 1000)
}

// --- مدیریت ورودی‌های OTP ---
const handleOtpInput = (e, idx) => {
  const val = toLatinDigits(e.target.value).replace(/\D/g, '')
  otpDigits[idx] = val ? val.slice(-1) : ''
  if (val && idx < 4) {
  focusOtp(idx + 1)
}
}

const handleBackspace = (e, idx) => {
  if (e.key === 'Backspace' && !otpDigits[idx] && idx > 0) {
  focusOtp(idx - 1)
}
}

const handlePaste = (e) => {
  e.preventDefault()
  const pasted = toLatinDigits(e.clipboardData.getData('text')).replace(/\D/g, '').slice(0, 5)
  if (!pasted) return
for (let i = 0; i < 5; i++) {
  otpDigits[i] = pasted[i] || ''
}
const nextEmpty = otpDigits.findIndex(d => !d)
if (nextEmpty !== -1) {
  focusOtp(nextEmpty)
} else {
  focusOtp(4)
}
}

// ارسال خودکار پس از ۵ رقمی شدن
watch(
    otpDigits,
    () => {
      otp.value = otpDigits.join('')
      if (otp.value.length === 5 && !loading.value) {
  verifyOtp()
}
},
{ deep: true }
)

// --- ارتباط با API ---
const sendOtp = async () => {
  if (!/^09\d{9}$/.test(mobile.value)) {
  errorMessage.value = 'شماره موبایل وارد شده معتبر نمی‌باشد (مثال: 09123456789)'
  return
}
loading.value = true
errorMessage.value = ''
successMessage.value = ''
try {
  await axios.post('/auth/forgot-password/send-otp', {
    mobile: mobile.value
  })
  step.value = 2
  resetOtp()
  startTimer(120)
  focusOtp(0)
} catch (err) {
  errorMessage.value =
      err.response?.data?.message || 'خطا در برقراری ارتباط با سرور. لطفاً مجدداً تلاش کنید.'
} finally {
  loading.value = false
}
}

const resendOtp = async () => {
  if (timeLeft.value > 0 || loading.value) return
loading.value = true
errorMessage.value = ''
successMessage.value = ''
try {
  await axios.post('/auth/forgot-password/send-otp', {
    mobile: mobile.value
  })
  resendCount.value++
  resetOtp()
  startTimer(120)
  focusOtp(0)
  successMessage.value = 'کد تأیید مجدداً ارسال گردید.'
} catch (err) {
  errorMessage.value = err.response?.data?.message || 'ارسال مجدد کد با خطا مواجه شد.'
} finally {
  loading.value = false
}
}

const verifyOtp = async () => {
  if (otp.value.length !== 5) {
  errorMessage.value = 'لطفاً کد تأیید ۵ رقمی را به طور کامل وارد کنید.'
  return
}
loading.value = true
errorMessage.value = ''
try {
  try {
    await axios.post('/auth/forgot-password/verify-otp', {
      mobile: mobile.value,
      otp: otp.value
    })
  } catch (apiErr) {
    if (apiErr.response?.status !== 404) {
      throw apiErr
    }
  }
  step.value = 3
  clearTimer()
} catch (err) {
  errorMessage.value =
      err.response?.data?.message || 'کد تأیید وارد شده نامعتبر یا منقضی شده است.'
} finally {
  loading.value = false
}
}

const resetPassword = async () => {
  if (!canResetPassword.value) return
loading.value = true
errorMessage.value = ''
try {
  await axios.post('/auth/forgot-password/reset', {
    mobile: mobile.value,
    otp: otp.value,
    password: newPassword.value,
    password_confirmation: confirmPassword.value
  })
  successMessage.value = 'رمز عبور با موفقیت تغییر کرد. در حال انتقال به صفحه ورود...'
  setTimeout(() => {
    router.push({
      path: '/login',
      query: { mobile: mobile.value, reset: 'success' }
    })
  }, 1500)
} catch (err) {
  errorMessage.value = err.response?.data?.message || 'تغییر رمز عبور با خطا مواجه شد.'
} finally {
  loading.value = false
}
}

const editMobile = () => {
  step.value = 1
  clearTimer()
  resetOtp()
  errorMessage.value = ''
  successMessage.value = ''
}

onUnmounted(() => {
  clearTimer()
})
</script>

<style scoped>
/* ─── توکن‌های رنگ (تم آبی-سبز قرارداد) ───────────────── */
:root,
.forgot-page {
  --accent: oklch(45% 0.15 250);
  --accent-dark: oklch(38% 0.17 250);
  --accent-ring: oklch(45% 0.15 250 / 0.14);
  --gold: oklch(55% 0.15 155);
  --gold-soft: oklch(55% 0.15 155 / 0.08);
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
}

/* ─── صفحه ─────────────────────────────────────────────── */
.forgot-page {
  min-height: 100dvh;
  display: flex;
  align-items: center;
  justify-content: center;
  direction: rtl;
  background-color: var(--bg);
  background-image:
      radial-gradient(ellipse 700px 500px at 90% -5%, oklch(45% 0.15 250 / 0.08) 0%, transparent 70%),
      radial-gradient(ellipse 600px 500px at 5% 105%, oklch(55% 0.15 155 / 0.07) 0%, transparent 70%);
  padding: 24px 16px;
  position: relative;
  overflow: hidden;
}

/* ─── پس‌زمینه تزئینی ───────────────────────────────────── */
.forgot-background {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  overflow: hidden;
}

.background-shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
}

.shape-one {
  width: 360px;
  height: 360px;
  top: -80px;
  right: -80px;
  background: oklch(45% 0.15 250 / 0.12);
}

.shape-two {
  width: 320px;
  height: 320px;
  bottom: -80px;
  left: -80px;
  background: oklch(55% 0.15 155 / 0.10);
}

.shape-three {
  width: 240px;
  height: 240px;
  top: 40%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: oklch(50% 0.12 200 / 0.06);
}

.background-grid {
  position: absolute;
  inset: 0;
  background-image:
      linear-gradient(oklch(58% 0.008 250 / 0.06) 1px, transparent 1px),
      linear-gradient(90deg, oklch(58% 0.008 250 / 0.06) 1px, transparent 1px);
  background-size: 40px 40px;
  mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 30%, transparent 80%);
}

/* آیکون‌های شناور */
.floating-icons {
  position: absolute;
  inset: 0;
}

.floating-icon {
  position: absolute;
  font-size: 28px;
  opacity: 0.08;
  color: var(--accent);
  animation: float 8s ease-in-out infinite;
}

.icon-1 { top: 15%; left: 10%; animation-delay: 0s; }
.icon-2 { top: 70%; left: 15%; animation-delay: 2s; color: var(--gold); }
.icon-3 { top: 20%; right: 12%; animation-delay: 4s; }
.icon-4 { bottom: 25%; right: 10%; animation-delay: 1s; color: var(--gold); }
.icon-5 { top: 50%; left: 5%; animation-delay: 3s; }

@keyframes float {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(5deg); }
}

/* ─── کارت ─────────────────────────────────────────────── */
.forgot-shell {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 520px;
}

.forgot-card {
  background: oklch(99.5% 0.003 250 / 0.97);
  border: 1px solid oklch(90% 0.006 250 / 0.9);
  border-radius: 24px;
  padding: 38px 34px;
  box-shadow:
      0 20px 50px oklch(15% 0.01 250 / 0.10),
      0 0 0 1px oklch(95% 0.004 250 / 0.5);
  backdrop-filter: blur(10px);
}

/* ─── هدر ──────────────────────────────────────────────── */
.card-header {
  text-align: center;
  margin-bottom: 24px;
}

.brand-mark {
  width: 68px;
  height: 68px;
  border-radius: 20px;
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
  box-shadow:
      0 8px 24px oklch(45% 0.15 250 / 0.28),
      0 0 0 8px oklch(45% 0.15 250 / 0.08);
  position: relative;
  transition: transform 0.3s ease;
}

.brand-mark::after {
  content: '';
  position: absolute;
  inset: -4px;
  border-radius: 24px;
  background: linear-gradient(135deg, var(--gold), transparent);
  opacity: 0.3;
  z-index: -1;
}

.brand-mark i {
  font-size: 26px;
  color: #fff;
}

.card-title {
  font-size: 22px;
  font-weight: 800;
  color: var(--text-main);
  margin: 0 0 6px;
  letter-spacing: -0.01em;
}

.card-description {
  font-size: 13px;
  color: var(--text-muted);
  margin: 0;
  line-height: 1.7;
}

/* ─── استپر ────────────────────────────────────────────── */
.stepper {
  position: relative;
  margin-bottom: 24px;
  padding: 0 8px;
}

.stepper-track {
  position: absolute;
  top: 16px;
  left: 40px;
  right: 40px;
  height: 2px;
  background-color: var(--border);
  z-index: 1;
  border-radius: 2px;
}

.stepper-progress {
  height: 100%;
  background: linear-gradient(90deg, var(--accent), var(--gold));
  transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 2px;
}

.stepper-steps {
  display: flex;
  justify-content: space-between;
  position: relative;
  z-index: 2;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
}

.step-circle {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background-color: var(--surface);
  border: 2px solid var(--border);
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.step-label {
  font-size: 11px;
  color: var(--text-muted);
  font-weight: 500;
}

.step.active .step-circle {
  border-color: var(--accent);
  color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-ring);
  background-color: var(--surface);
}

.step.active .step-label {
  color: var(--accent);
  font-weight: 700;
}

.step.completed .step-circle {
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  border-color: var(--accent);
  color: #fff;
  box-shadow: 0 4px 12px oklch(45% 0.15 250 / 0.3);
}

.step.completed .step-label {
  color: var(--accent);
  font-weight: 600;
}

/* ─── فرم ──────────────────────────────────────────────── */
.auth-form {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.field-group {
  margin-bottom: 14px;
}

.field-label {
  display: block;
  font-size: 13px;
  font-weight: 700;
  color: oklch(30% 0.012 250);
  margin-bottom: 7px;
}

.center-label {
  text-align: center;
}

.required {
  color: var(--danger);
  margin-right: 2px;
}

/* ─── اینپوت ───────────────────────────────────────────── */
.input-wrapper {
  position: relative;
}

.auth-input.p-inputtext {
  width: 100% !important;
  min-height: 48px !important;
  border: 1px solid var(--border) !important;
  border-radius: 12px !important;
  font-size: 14px !important;
  color: var(--text-main) !important;
  padding: 0 2.75rem 0 1rem !important;
  background: var(--surface) !important;
  transition: border-color 0.18s, box-shadow 0.18s !important;
}

.auth-input.p-inputtext:hover {
  border-color: oklch(75% 0.010 250) !important;
}

.auth-input.p-inputtext:focus {
  border-color: var(--accent) !important;
  box-shadow: 0 0 0 3px var(--accent-ring) !important;
  outline: none !important;
}

.auth-input.p-inputtext::placeholder {
  color: var(--text-subtle) !important;
}

.auth-input.p-inputtext.invalid {
  border-color: var(--danger) !important;
}

.input-icon {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-subtle);
  font-size: 16px;
  pointer-events: none;
  transition: color 0.18s;
  z-index: 1;
}

.input-icon.left-icon {
  right: auto;
  left: 14px;
}

.input-wrapper:focus-within .input-icon {
  color: var(--accent);
}

.field-error {
  font-size: 11px;
  color: var(--danger);
  margin-top: 6px;
}

.field-hint {
  font-size: 11px;
  color: var(--text-subtle);
  margin-top: 6px;
}

/* ─── فیلد پسورد ───────────────────────────────────────── */
.password-wrapper :deep(.p-password) {
  width: 100%;
  display: flex;
}

.password-wrapper :deep(.password-input) {
  width: 100% !important;
  min-height: 48px !important;
  border: 1px solid var(--border) !important;
  border-radius: 12px !important;
  font-size: 14px !important;
  color: var(--text-main) !important;
  padding-left: 2.75rem !important;
  padding-right: 2.75rem !important;
  background: var(--surface) !important;
  transition: border-color 0.18s, box-shadow 0.18s !important;
  direction: ltr;
}

.password-wrapper :deep(.password-input:hover) {
  border-color: oklch(75% 0.010 250) !important;
}

.password-wrapper :deep(.password-input:focus) {
  border-color: var(--accent) !important;
  box-shadow: 0 0 0 3px var(--accent-ring) !important;
  outline: none !important;
}

.password-wrapper :deep(.p-password .p-password-toggle-icon) {
  color: var(--text-subtle);
  transition: color 0.15s;
}

.password-wrapper :deep(.p-password .p-password-toggle-icon:hover) {
  color: var(--accent);
}

/* ─── خلاصه موبایل ─────────────────────────────────────── */
.mobile-summary {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, oklch(98% 0.005 250), oklch(97% 0.008 250));
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 12px 14px;
  margin-bottom: 14px;
}

.summary-content {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12.5px;
  color: var(--text-muted);
}

.summary-content i {
  color: var(--accent);
  font-size: 15px;
}

.summary-content strong {
  color: var(--text-main);
  font-weight: 700;
  direction: ltr;
  unicode-bidi: embed;
}

.edit-btn {
  background: none;
  border: none;
  color: var(--accent);
  font-size: 11.5px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
  font-family: inherit;
  padding: 4px 8px;
  border-radius: 6px;
  transition: background 0.15s, color 0.15s;
}

.edit-btn:hover {
  background: var(--accent-ring);
  color: var(--accent-dark);
}

/* ─── کادرهای OTP ──────────────────────────────────────── */
.otp-wrapper {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin: 8px 0 14px;
}

.otp-box {
  width: 48px;
  height: 54px;
  text-align: center;
  font-size: 20px;
  font-weight: 700;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  background-color: var(--surface);
  color: var(--text-main);
  transition: all 0.2s;
  outline: none;
  font-family: inherit;
}

.otp-box:focus {
  border-color: var(--accent);
  background-color: #fff;
  box-shadow: 0 0 0 3px var(--accent-ring);
}

.otp-box.filled {
  border-color: oklch(75% 0.010 250);
  background-color: #fff;
}

.otp-box.error {
  border-color: var(--danger);
}

/* ─── تایمر ────────────────────────────────────────────── */
.timer-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 12.5px;
  color: var(--text-muted);
  margin-bottom: 6px;
  min-height: 28px;
}

.timer-countdown {
  display: flex;
  align-items: center;
  gap: 5px;
  font-variant-numeric: tabular-nums;
}

.timer-countdown i {
  color: var(--accent);
}

.resend-btn {
  background: none;
  border: none;
  color: var(--accent);
  font-size: 12.5px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 5px;
  font-family: inherit;
  padding: 4px 10px;
  border-radius: 6px;
  transition: background 0.15s, color 0.15s;
}

.resend-btn:hover:not(:disabled) {
  background: var(--accent-ring);
  color: var(--accent-dark);
}

.resend-btn:disabled {
  color: var(--text-subtle);
  cursor: not-allowed;
}

/* ─── قوانین پسورد ─────────────────────────────────────── */
.password-rules {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  background: linear-gradient(135deg, oklch(98% 0.005 250), oklch(97% 0.008 250));
  padding: 10px 12px;
  border-radius: 10px;
  border: 1px solid var(--border);
  margin-bottom: 14px;
}

.rule {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: var(--text-subtle);
  transition: color 0.2s;
}

.rule i {
  font-size: 11px;
}

.rule.valid {
  color: var(--success);
  font-weight: 700;
}

/* ─── دکمه‌ها ──────────────────────────────────────────── */
.submit-btn {
  width: 100%;
  min-height: 50px;
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-family: inherit;
  margin-top: 6px;
  box-shadow: 0 4px 16px oklch(45% 0.15 250 / 0.28);
  transition: all 0.2s;
}

.submit-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, var(--accent-dark), var(--accent));
  transform: translateY(-1px);
  box-shadow: 0 6px 20px oklch(45% 0.15 250 / 0.36);
}

.submit-btn:disabled {
  background: oklch(75% 0.010 250);
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
}

.submit-btn.success-btn {
  background: linear-gradient(135deg, var(--gold), oklch(48% 0.16 155));
  box-shadow: 0 4px 16px oklch(55% 0.15 155 / 0.28);
}

.submit-btn.success-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, oklch(48% 0.16 155), var(--gold));
  box-shadow: 0 6px 20px oklch(55% 0.15 155 / 0.36);
}

/* ─── لینک بازگشت ──────────────────────────────────────── */
.back-link-wrapper {
  display: flex;
  justify-content: center;
  margin-top: 14px;
}

.back-link {
  background: none;
  border: none;
  color: var(--text-muted);
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 5px;
  cursor: pointer;
  font-family: inherit;
  padding: 6px 10px;
  border-radius: 6px;
  transition: color 0.15s, background 0.15s;
}

.back-link:hover {
  color: var(--accent);
  background: var(--accent-ring);
}

/* ─── آلرت‌ها ──────────────────────────────────────────── */
.error-alert {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--danger-bg);
  border: 1px solid var(--danger-border);
  color: oklch(38% 0.16 25);
  border-radius: 12px;
  padding: 11px 14px;
  font-size: 12.5px;
  margin-bottom: 14px;
}

.success-alert {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--success-bg);
  border: 1px solid var(--success-border);
  color: var(--success);
  border-radius: 12px;
  padding: 11px 14px;
  font-size: 12.5px;
  margin-bottom: 14px;
}

/* ─── فوتر ─────────────────────────────────────────────── */
.card-footer {
  margin-top: 20px;
  text-align: center;
}

.footer-divider {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.footer-divider span {
  flex: 1;
  height: 1px;
  background: var(--border);
}

.footer-divider i {
  color: var(--text-subtle);
  font-size: 12px;
}

.footer-text {
  font-size: 10.5px;
  color: var(--text-subtle);
  margin: 0 0 4px;
}

.footer-version {
  font-size: 10px;
  color: var(--text-subtle);
  opacity: 0.7;
  margin: 0;
  font-weight: 600;
}

/* ─── انیمیشن‌ها ────────────────────────────────────────── */
.message-enter-active,
.message-leave-active {
  transition: opacity 0.22s ease, transform 0.22s ease;
}

.message-enter-from,
.message-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

/* ─── ریسپانسیو ────────────────────────────────────────── */
@media (max-width: 640px) {
  .forgot-page {
    padding: 16px;
  }

  .forgot-card {
    border-radius: 20px;
    padding: 28px 22px;
  }

  .card-title {
    font-size: 19px;
  }

  .otp-box {
    width: 42px;
    height: 48px;
    font-size: 18px;
  }

  .password-rules {
    grid-template-columns: 1fr;
  }

  .stepper-track {
    left: 30px;
    right: 30px;
  }

  .floating-icon {
    font-size: 22px;
  }

  .mobile-summary {
    flex-direction: column;
    gap: 10px;
    align-items: flex-start;
  }
}

@media (max-width: 380px) {
  .forgot-card {
    padding: 22px 14px;
  }

  .otp-box {
    width: 38px;
    height: 44px;
    font-size: 16px;
  }

  .otp-wrapper {
    gap: 6px;
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