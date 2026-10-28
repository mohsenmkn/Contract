<template>
  <main class="auth-page" dir="rtl">
    <div class="auth-background">
    <div class="background-shape shape-one"></div>
    <div class="background-shape shape-two"></div>
    <div class="background-shape shape-three"></div>
    <div class="background-grid"></div>
    <div class="floating-icons">
    <i class="pi pi-file-signature floating-icon icon-1"></i>
    <i class="pi pi-handshake floating-icon icon-2"></i>
    <i class="pi pi-shield floating-icon icon-3"></i>
    <i class="pi pi-calendar floating-icon icon-4"></i>
    <i class="pi pi-check-circle floating-icon icon-5"></i>
    <i class="pi pi-file-pdf floating-icon icon-6"></i>
    <i class="pi pi-wallet floating-icon icon-7"></i>
    </div>
    </div>
    <div class="auth-shell">
    <div class="auth-card">
      <!-- هدر -->
  <header class="auth-header">
    <div class="brand-mark">
      <i class="pi pi-file-signature"></i>
    </div>
    <h1 class="auth-title">سامانه مدیریت قراردادها</h1>
    <p class="auth-subtitle">
      مدیریت یکپارچه قراردادها، الحاقیه‌ها، صورت‌وضعیت‌ها و تعهدات سازمانی
    </p>
  </header>

  <!-- پیام موفقیت ریست -->
  <Transition name="message">
    <div v-if="resetSuccess" class="success-alert" role="alert">
      <div class="alert-icon">
        <i class="pi pi-check"></i>
      </div>
      <div class="alert-content">
        <strong>رمز عبور با موفقیت تغییر کرد.</strong>
        <p>برای ادامه، رمز عبور جدید خود را وارد کنید.</p>
      </div>
      <button class="alert-close" @click="resetSuccess = false" aria-label="بستن">
        <i class="pi pi-times"></i>
      </button>
    </div>
  </Transition>

  <!-- فرم ورود -->
  <form class="auth-form" @submit.prevent="handleLogin" autocomplete="on">
    <!-- شماره موبایل -->
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
            class="auth-input mobile-input"
            @input="normalizeMobile"
            autocomplete="tel-national"
            :class="{ invalid: mobileError }"
        />
      </div>
      <p class="field-hint" v-if="!mobileError">شماره موبایل ثبت‌شده در سامانه را وارد کنید.</p>
      <p class="field-error" v-if="mobileError">{{ mobileError }}</p>
    </div>

    <!-- رمز عبور -->
    <div class="field-group password-field" :class="{ 'password-highlight': shouldHighlightPassword }">
      <div class="field-header">
        <label for="password" class="field-label">
          رمز عبور <span class="required">*</span>
        </label>
        <router-link to="/forgot-password" class="forgot-link">
          <i class="pi pi-key"></i>
          فراموشی رمز عبور؟
        </router-link>
      </div>
      <div class="input-wrapper password-wrapper">
        <i class="pi pi-lock password-icon" aria-hidden="true"></i>
        <Password
            ref="passwordRef"
            v-model="password"
            toggleMask
            :feedback="false"
            placeholder="••••••••"
            inputClass="auth-password-input"
            dir="ltr"
            autocomplete="current-password"
            @focus="shouldHighlightPassword = false"
        />
      </div>
      <p class="field-hint" v-if="shouldHighlightPassword">رمز عبور جدید خود را وارد کنید.</p>
    </div>

    <!-- کپچا -->
    <Transition name="captcha">
      <div v-if="requireCaptcha" class="captcha-box">
        <div class="captcha-header">
          <i class="pi pi-shield"></i>
          <span>تأیید امنیتی</span>
          <button
              type="button"
              class="captcha-refresh-button"
              @click="fetchCaptcha"
              aria-label="تازه‌سازی کپچا"
          >
            <i class="pi pi-refresh"></i>
          </button>
        </div>
        <div class="captcha-content">
          <button type="button" class="captcha-image-button" @click="fetchCaptcha">
            <img :src="captchaImg" alt="کپچا" width="145" height="48" />
          </button>
          <InputText
              v-model="captchaInput"
              dir="ltr"
              placeholder="کد امنیتی"
              class="auth-input captcha-input"
          />
        </div>
      </div>
    </Transition>

    <!-- خطا -->
    <Transition name="message">
      <div v-if="errorMessage" class="error-alert" role="alert">
        <i class="pi pi-exclamation-circle"></i>
        <span>{{ errorMessage }}</span>
      </div>
    </Transition>

    <!-- دکمه ورود -->
    <button
        type="submit"
        class="login-button"
        :disabled="loading"
    >
      <template v-if="!loading">
        <i class="pi pi-sign-in"></i>
        ورود به سامانه
      </template>
      <template v-else>
        در حال ورود...
        <i class="pi pi-spin pi-spinner"></i>
      </template>
    </button>
  </form>

  <!-- ویژگی‌های سامانه -->
  <div class="features-strip">
    <div class="feature-item">
      <i class="pi pi-file-signature"></i>
      <span>مدیریت قرارداد</span>
    </div>
    <div class="feature-item">
      <i class="pi pi-handshake"></i>
      <span>امضای الکترونیک</span>
    </div>
    <div class="feature-item">
      <i class="pi pi-bell"></i>
      <span>هشدار هوشمند</span>
    </div>
    <div class="feature-item">
      <i class="pi pi-calendar"></i>
      <span>پیگیری تعهدات</span>
    </div>
  </div>

  <!-- فوتر -->
  <footer class="auth-footer">
    <div class="footer-divider">
      <span></span>
      <i class="pi pi-shield"></i>
      <span></span>
    </div>
    <p class="footer-brand">
      دسترسی شما به سامانه به‌صورت امن مدیریت می‌شود.
    </p>
    <p class="footer-version">نسخه ۱.۰.۰ | سامانه مدیریت قراردادها</p>
  </footer>
</div>
</div>
</main>
</template>

<!-- src/views/Auth/LoginView.vue (بخش script) -->

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import { useAuthStore } from '@/stores/auth'
import axios from '@/api/axios.js'
import { useApiErrorHandler } from '@/composables/useApiErrorHandler'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api/v1'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { handleApiError } = useApiErrorHandler()

const mobile = ref('')
const password = ref('')
const mobileError = ref('')
const errorMessage = ref('')
const loading = ref(false)
const passwordRef = ref(null)
const resetSuccess = ref(false)
const shouldHighlightPassword = ref(false)
const requireCaptcha = ref(false)
const captchaImg = ref('')
const captchaKey = ref('')
const captchaInput = ref('')

function normalizeMobile() {
  mobile.value = mobile.value
      .replace(/[۰-۹]/g, d => String.fromCharCode(d.charCodeAt(0) - 1728))
      .replace(/\D/g, '')
      .slice(0, 11)
}

function validateMobile() {
  if (!mobile.value) {
    mobileError.value = 'وارد کردن شماره موبایل الزامی است.'
    return false
  }
  if (!/^09\d{9}$/.test(mobile.value)) {
    mobileError.value = 'شماره موبایل باید با 09 شروع شده و 11 رقم باشد.'
    return false
  }
  mobileError.value = ''
  return true
}

async function fetchCaptcha() {
  try {
    const { data } = await axios.get(`${API_BASE_URL}/auth/captcha`)
    captchaImg.value = data.img
    captchaKey.value = data.key
  } catch {
    // silent
  }
}

async function handleLogin() {
  if (!validateMobile()) return
  loading.value = true
  errorMessage.value = ''

  const payload = { mobile: mobile.value, password: password.value }
  if (requireCaptcha.value) {
    payload.captcha = captchaInput.value
    payload.captcha_key = captchaKey.value
  }

  try {
    await authStore.login(payload)

    // ✅ اطمینان از آماده بودن auth store
    await nextTick()

    // ✅ هدایت هوشمند بر اساس نقش
    const targetRoute = authStore.defaultDashboard

    // اگر redirect در query بود، به آنجا برو
    const redirect = route.query.redirect
    if (redirect && redirect !== '/login') {
      router.replace(redirect)
    } else {
      router.replace({ name: targetRoute })
    }
  } catch (error) {
    handleApiError(error, errorMessage)
    if (error?.response?.status === 429) {
      requireCaptcha.value = true
      await fetchCaptcha()
    } else if (error?.response?.status === 400 && requireCaptcha.value) {
      await fetchCaptcha()
    }
  } finally {
    loading.value = false
  }
}

function focusOnPassword() {
  const input = passwordRef.value?.$el?.querySelector('input')
  if (input) { input.focus(); input.select() }
}

onMounted(() => {
  if (route.query.mobile) {
    mobile.value = route.query.mobile
  }
  if (route.query.reset === 'success') {
    resetSuccess.value = true
    shouldHighlightPassword.value = true
    setTimeout(() => { shouldHighlightPassword.value = false }, 5000)
    router.replace('/login')
    setTimeout(focusOnPassword, 350)
  }
})

watch(mobile, () => {
  shouldHighlightPassword.value = false
  if (mobileError.value) validateMobile()
})
</script>

<style scoped>
/* ─── توکن‌های رنگ (تم آبی-سبز قرارداد) ───────────────── */
:root,
.auth-page {
  --accent: oklch(45% 0.15 250); /* آبی تیره - اعتماد */
  --accent-dark: oklch(38% 0.17 250);
  --accent-ring: oklch(45% 0.15 250 / 0.14);
  --gold: oklch(55% 0.15 155); /* سبز زمردی - قرارداد */
  --gold-soft: oklch(55% 0.15 155 / 0.08);
  --surface: oklch(99% 0.004 250);
  --bg: oklch(97.5% 0.008 250);
  --border: oklch(88% 0.008 250);
  --border-focus: var(--accent);
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
.auth-page {
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
.auth-background {
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
.icon-6 { top: 35%; right: 20%; animation-delay: 2.5s; color: var(--gold); }
.icon-7 { bottom: 40%; left: 20%; animation-delay: 1.5s; }

@keyframes float {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(5deg); }
}

/* ─── کارت ─────────────────────────────────────────────── */
.auth-shell {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 480px;
}

.auth-card {
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
.auth-header {
  text-align: center;
  margin-bottom: 28px;
}

.brand-mark {
  width: 72px;
  height: 72px;
  border-radius: 20px;
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  box-shadow:
      0 8px 24px oklch(45% 0.15 250 / 0.28),
      0 0 0 8px oklch(45% 0.15 250 / 0.08);
  position: relative;
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
  font-size: 28px;
  color: #fff;
}

.auth-title {
  font-size: 24px;
  font-weight: 800;
  color: var(--text-main);
  margin: 0 0 8px;
  letter-spacing: -0.01em;
}

.auth-subtitle {
  font-size: 13px;
  color: var(--text-muted);
  margin: 0;
  line-height: 1.7;
}

/* ─── فرم ──────────────────────────────────────────────── */
.auth-form {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.field-group {
  margin-bottom: 18px;
}

.field-label {
  display: block;
  font-size: 13px;
  font-weight: 700;
  color: oklch(30% 0.012 250);
  margin-bottom: 7px;
}

.required {
  color: var(--danger);
  margin-right: 2px;
}

/* ─── ردیف لیبل + اکشن ─────────────────────────────────── */
.field-header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 7px;
}

.field-header .field-label {
  margin-bottom: 0;
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
.password-field {
  position: relative;
}

.password-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-subtle);
  font-size: 16px;
  pointer-events: none;
  z-index: 1;
}

.input-wrapper:focus-within .password-icon {
  color: var(--accent);
}

:deep(.auth-password-input) {
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

:deep(.auth-password-input:hover) {
  border-color: oklch(75% 0.010 250) !important;
}

:deep(.p-password) {
  width: 100%;
  display: flex;
}

:deep(.p-password .p-password-toggle-icon) {
  color: var(--text-subtle);
  transition: color 0.15s;
}

:deep(.p-password .p-password-toggle-icon:hover) {
  color: var(--accent);
}

:deep(.auth-password-input:focus) {
  border-color: var(--accent) !important;
  box-shadow: 0 0 0 3px var(--accent-ring) !important;
  outline: none !important;
}

.password-highlight :deep(.auth-password-input) {
  border-color: var(--accent) !important;
  box-shadow: 0 0 0 3px var(--accent-ring) !important;
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
  font-size: 13px;
  margin-bottom: 6px;
}

.success-alert {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: var(--success-bg);
  border: 1px solid var(--success-border);
  color: var(--success);
  border-radius: 12px;
  padding: 12px 14px;
  font-size: 13px;
  margin-bottom: 16px;
}

.alert-icon {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: oklch(88% 0.08 145);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.alert-content {
  flex: 1;
}

.alert-content p {
  margin: 4px 0 0;
  font-size: 12px;
  opacity: 0.85;
}

.alert-close {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  border-radius: 6px;
  color: var(--success);
  transition: background 0.15s;
}

.alert-close:hover {
  background: oklch(82% 0.10 145 / 0.3);
}

/* ─── لینک فراموشی ─────────────────────────────────────── */
.forgot-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11.5px;
  border-radius: 6px;
  font-weight: 600;
  color: var(--accent);
  text-decoration: none;
  transition: color 0.15s;
}

.forgot-link:hover {
  color: var(--accent-dark);
  text-decoration: underline;
}

/* ─── دکمه ورود ────────────────────────────────────────── */
.login-button {
  width: 100% !important;
  min-height: 50px !important;
  background: linear-gradient(135deg, var(--accent), var(--accent-dark)) !important;
  color: #fff !important;
  font-size: 14px !important;
  font-weight: 700 !important;
  border: none !important;
  border-radius: 12px !important;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: 0 4px 16px oklch(45% 0.15 250 / 0.28) !important;
  transition: background 0.18s, transform 0.15s, box-shadow 0.18s !important;
  margin-top: 8px;
}

.login-button:hover:not(:disabled) {
  background: linear-gradient(135deg, var(--accent-dark), var(--accent)) !important;
  transform: translateY(-1px);
  box-shadow: 0 6px 20px oklch(45% 0.15 250 / 0.36) !important;
}

.login-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ─── ویژگی‌های سامانه ─────────────────────────────────── */
.features-strip {
  display: flex;
  justify-content: center;
  gap: 16px;
  margin-top: 22px;
  padding: 14px 12px;
  background: linear-gradient(135deg, oklch(97% 0.01 250), oklch(97% 0.01 155));
  border-radius: 12px;
  border: 1px solid oklch(92% 0.008 250);
  flex-wrap: wrap;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: var(--text-muted);
  font-weight: 600;
}

.feature-item i {
  font-size: 14px;
  color: var(--accent);
}

.feature-item:nth-child(2) i {
  color: var(--gold);
}

.feature-item:nth-child(3) i {
  color: var(--accent);
}

.feature-item:nth-child(4) i {
  color: var(--gold);
}

/* ─── کپچا ─────────────────────────────────────────────── */
.captcha-box {
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 12px;
  margin-bottom: 12px;
}

.captcha-header {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted);
  margin-bottom: 10px;
}

.captcha-refresh-button {
  margin-right: auto;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  border: 1px solid oklch(80% 0.12 250 / 0.4);
  background: none;
  cursor: pointer;
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s, border-color 0.15s;
}

.captcha-refresh-button:hover {
  background: oklch(95% 0.06 250);
  border-color: oklch(70% 0.14 250 / 0.6);
}

.captcha-content {
  display: flex;
  align-items: center;
  gap: 10px;
}

.captcha-image-button {
  border: 1px solid var(--border);
  border-radius: 8px;
  background: none;
  cursor: pointer;
  padding: 0;
  overflow: hidden;
  flex-shrink: 0;
}

.captcha-image-button img {
  display: block;
  width: 145px;
  height: 48px;
}

.captcha-input.p-inputtext {
  min-height: 44px !important;
  text-align: center !important;
  letter-spacing: 4px !important;
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

.captcha-enter-active,
.captcha-leave-active {
  transition: opacity 0.22s ease, transform 0.22s ease;
}

.captcha-enter-from,
.captcha-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

/* ─── فوتر ─────────────────────────────────────────────── */
.auth-footer {
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

.footer-brand {
  font-size: 10.5px;
  color: var(--text-subtle);
  margin: 0 0 4px;
}

.footer-version {
  font-size: 10px;
  color: var(--text-subtle);
  opacity: 0.7;
  margin: 0;
  font-family: monospace;
}

/* ─── ریسپانسیو ────────────────────────────────────────── */
@media (max-width: 640px) {
  .auth-page {
    padding: 16px;
  }

  .auth-card {
    border-radius: 20px;
    padding: 28px 22px;
  }

  .auth-title {
    font-size: 21px;
  }

  .captcha-content {
    flex-direction: column;
  }

  .features-strip {
    flex-direction: column;
    gap: 10px;
    align-items: center;
  }

  .floating-icon {
    font-size: 22px;
  }
}

@media (max-width: 380px) {
  .auth-card {
    padding: 22px 14px;
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