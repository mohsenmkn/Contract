<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import Button from 'primevue/button'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Skeleton from 'primevue/skeleton'
import ToggleSwitch from 'primevue/toggleswitch'
import api from '@/api/axios'
import { showToast } from '@/plugins/toast'

/* ───────── ثابت‌ها ───────── */
const DEFAULTS = {
  days: [30, 7, 1],
  channels: ['in_app', 'email', 'sms'],
  sendTime: '08:00',
}
const PRESET_DAYS = [60, 30, 14, 7, 3, 1]
const MAX_DAYS = 10
const MAX_CONTACTS = 20

const channelOptions = [
  { id: 'in_app', title: 'اعلان داخل سامانه', description: 'هشدار در اعلان‌های کاربر مسئول قرارداد ثبت می‌شود.', icon: 'pi pi-bell' },
  { id: 'email', title: 'ایمیل', description: 'هشدار به نشانی‌های ایمیلی که در همین صفحه ثبت می‌کنید ارسال می‌شود.', icon: 'pi pi-envelope' },
  { id: 'sms', title: 'پیامک', description: 'هشدار با الگوی پیش‌فرض پیامک سامانه ارسال می‌شود.', icon: 'pi pi-mobile' },
]

/* ───────── وضعیت ───────── */
const form = ref({
  days: [...DEFAULTS.days],
  channels: [...DEFAULTS.channels],
  phones: [],
  emails: [],
  sendTime: DEFAULTS.sendTime,
})
const savedSnapshot = ref('')
const hasSaved = ref(true)

const loading = ref(false)
const saving = ref(false)
const loadError = ref('')
const saveError = ref('')
const submitted = ref(false)

const dayDraft = ref(null)
const phoneDraft = ref('')
const emailDraft = ref('')
const draftErrors = ref({ day: '', phone: '', email: '' })

/* ───────── ابزارها ───────── */
const fa = value => String(value).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d])

function normalizeDigits(value) {
  return String(value).replace(/[۰-۹]/g, digit => String(digit.charCodeAt(0) - 1776))
      .replace(/[٠-٩]/g, digit => String(digit.charCodeAt(0) - 1632))
}
const normalizePhone = value => normalizeDigits(value).replace(/[^+0-9]/g, '')
const isPhone = value => /^\+?\d{10,15}$/.test(value)
const isEmail = value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
const sortDesc = list => [...list].sort((a, b) => b - a)

function buildPayload() {
  const f = form.value
  return {
    days_before: sortDesc([...new Set(f.days.map(Number).filter(d => Number.isInteger(d) && d >= 1 && d <= 365))]),
    channels: [...f.channels].sort(),
    phone_numbers: [...new Set(f.phones.map(normalizePhone).filter(Boolean))],
    email_addresses: [...new Set(f.emails.map(v => v.trim().toLowerCase()).filter(Boolean))],
    send_time: f.sendTime,
  }
}

function applyServerState(state) {
  form.value = {
    days: sortDesc(state.days_before ?? DEFAULTS.days),
    channels: state.channels ?? [...DEFAULTS.channels],
    phones: state.phone_numbers ?? [],
    emails: state.email_addresses ?? [],
    sendTime: state.send_time ?? DEFAULTS.sendTime,
  }
  savedSnapshot.value = JSON.stringify(buildPayload())
}

/* ───────── محاسبه‌ها ───────── */
const dirty = computed(() => JSON.stringify(buildPayload()) !== savedSnapshot.value)
const canSave = computed(() => dirty.value || !hasSaved.value)

const dayOptions = computed(() => sortDesc([...new Set([...PRESET_DAYS, ...form.value.days])]))
const has = id => form.value.channels.includes(id)

const errors = computed(() => {
  const f = form.value
  const result = {}
  if (!f.days.length) result.days = 'حداقل یک زمان برای ارسال هشدار انتخاب کنید.'
  if (!f.channels.length) result.channels = 'حداقل یک روش ارسال را فعال کنید.'
  if (has('sms') && !f.phones.length) result.sms = 'برای ارسال پیامک، حداقل یک شماره موبایل اضافه کنید.'
  if (has('email') && !f.emails.length) result.email = 'برای ارسال ایمیل، حداقل یک نشانی ایمیل اضافه کنید.'
  if (!f.sendTime) result.time = 'ساعت ارسال را مشخص کنید.'
  return result
})
const visibleErrors = computed(() => (submitted.value ? errors.value : {}))

const summary = computed(() => {
  const f = form.value
  if (!f.days.length || !f.channels.length || !f.sendTime) return ''
  const daysText = sortDesc(f.days).map(fa).join('، ')
  const labels = channelOptions.filter(o => has(o.id)).map(o => o.title).join('، ')
  return `هشدار ${daysText} روز مانده به پایان قرارداد، هر روز ساعت ${fa(f.sendTime)} به وقت تهران و از طریق «${labels}» ارسال می‌شود.`
})

/* ───────── بارگذاری ───────── */
async function loadSettings() {
  loading.value = true
  loadError.value = ''
  try {
    const { data } = await api.get('/contract-settings')
    const alerts = data?.data?.expiry_alerts
    hasSaved.value = Boolean(alerts)
    applyServerState(alerts ?? {})
  } catch (e) {
    loadError.value = e?.response?.data?.message || 'دریافت تنظیمات هشدارها انجام نشد.'
  } finally {
    loading.value = false
  }
}

/* ───────── زمان‌ها ───────── */
function toggleDay(day) {
  const list = form.value.days
  if (list.includes(day)) {
    form.value.days = list.filter(d => d !== day)
  } else if (list.length < MAX_DAYS) {
    form.value.days = sortDesc([...list, day])
  } else {
    draftErrors.value.day = `حداکثر ${fa(MAX_DAYS)} زمان قابل انتخاب است.`
    return
  }
  draftErrors.value.day = ''
}

function addCustomDay() {
  const day = Number(dayDraft.value)
  if (!Number.isInteger(day) || day < 1 || day > 365) {
    draftErrors.value.day = 'عددی بین ۱ تا ۳۶۵ وارد کنید.'
    return false
  }
  if (form.value.days.includes(day)) {
    draftErrors.value.day = 'این زمان قبلاً انتخاب شده است.'
    return false
  }
  if (form.value.days.length >= MAX_DAYS) {
    draftErrors.value.day = `حداکثر ${fa(MAX_DAYS)} زمان قابل انتخاب است.`
    return false
  }
  form.value.days = sortDesc([...form.value.days, day])
  dayDraft.value = null
  draftErrors.value.day = ''
  return true
}

/* ───────── گیرنده‌ها ───────── */
function addPhone() {
  const value = normalizePhone(phoneDraft.value)
  if (!value) return true
  if (!isPhone(value)) {
    draftErrors.value.phone = 'شماره موبایل معتبر نیست. نمونه: 09123456789'
    return false
  }
  if (form.value.phones.map(normalizePhone).includes(value)) {
    draftErrors.value.phone = 'این شماره قبلاً اضافه شده است.'
    return false
  }
  if (form.value.phones.length >= MAX_CONTACTS) {
    draftErrors.value.phone = `حداکثر ${fa(MAX_CONTACTS)} شماره قابل ثبت است.`
    return false
  }
  form.value.phones.push(value)
  phoneDraft.value = ''
  draftErrors.value.phone = ''
  return true
}

function addEmail() {
  const value = emailDraft.value.trim().toLowerCase()
  if (!value) return true
  if (!isEmail(value)) {
    draftErrors.value.email = 'نشانی ایمیل معتبر نیست. نمونه: name@example.com'
    return false
  }
  if (form.value.emails.includes(value)) {
    draftErrors.value.email = 'این ایمیل قبلاً اضافه شده است.'
    return false
  }
  if (form.value.emails.length >= MAX_CONTACTS) {
    draftErrors.value.email = `حداکثر ${fa(MAX_CONTACTS)} ایمیل قابل ثبت است.`
    return false
  }
  form.value.emails.push(value)
  emailDraft.value = ''
  draftErrors.value.email = ''
  return true
}

function removeAt(list, index) {
  list.splice(index, 1)
}

function setChannel(id, enabled) {
  const current = form.value.channels
  form.value.channels = enabled ? [...new Set([...current, id])] : current.filter(c => c !== id)
}

/* ───────── ذخیره و لغو ───────── */
function resetForm() {
  const snapshot = savedSnapshot.value ? JSON.parse(savedSnapshot.value) : {}
  applyServerState(snapshot)
  phoneDraft.value = ''
  emailDraft.value = ''
  dayDraft.value = null
  draftErrors.value = { day: '', phone: '', email: '' }
  saveError.value = ''
  submitted.value = false
}

async function saveSettings() {
  saveError.value = ''
  submitted.value = true

  // مقدارهایی که تایپ شده ولی هنوز با Enter یا دکمه «افزودن» ثبت نشده‌اند، از بین نروند
  if (has('sms') && !addPhone()) return
  if (has('email') && !addEmail()) return
  if (Object.keys(errors.value).length) return

  saving.value = true
  try {
    const { data } = await api.put('/contract-settings/expiry-alerts', buildPayload())
    hasSaved.value = true
    applyServerState(data.data)
    submitted.value = false
    showToast({ severity: 'success', summary: 'ذخیره شد', detail: data.message, life: 3500 })
  } catch (e) {
    saveError.value = e?.response?.data?.message || 'ذخیره تنظیمات انجام نشد. دوباره تلاش کنید.'
  } finally {
    saving.value = false
  }
}

/* ───────── هشدار خروج با تغییرات ذخیره‌نشده ───────── */
function warnBeforeLeave(event) {
  if (!dirty.value) return
  event.preventDefault()
  event.returnValue = ''
}
onMounted(() => {
  loadSettings()
  window.addEventListener('beforeunload', warnBeforeLeave)
})
onBeforeUnmount(() => window.removeEventListener('beforeunload', warnBeforeLeave))
</script>

<template>
  <section class="alert-settings" dir="rtl">

    <header class="intro">
      <span class="icon-tile icon-tile--lg" aria-hidden="true"><i class="pi pi-bell" /></span>
      <div>
        <h2>هشدارهای پایان قرارداد</h2>
        <p>تعیین کنید چه زمانی، به چه کسانی و از چه راهی درباره نزدیک شدن پایان قراردادها خبر داده شود.</p>
      </div>
    </header>

    <!-- خطای دریافت: فرم نمایش داده نمی‌شود تا مقادیر پیش‌فرض به‌اشتباه جایگزین تنظیمات واقعی نشوند -->
    <Message v-if="loadError" severity="error" :closable="false">
      <div class="message-row">
        <span>{{ loadError }}</span>
        <Button label="تلاش دوباره" icon="pi pi-refresh" size="small" severity="secondary" outlined @click="loadSettings" />
      </div>
    </Message>

    <div v-else-if="loading" class="skeletons" aria-busy="true" aria-label="در حال دریافت تنظیمات">
      <Skeleton v-for="n in 3" :key="n" height="120px" border-radius="14px" />
    </div>

    <form v-else class="form" novalidate @submit.prevent="saveSettings">
      <aside v-if="summary" class="summary" aria-live="polite">
        <i class="pi pi-info-circle" aria-hidden="true" />
        <p>{{ summary }}</p>
      </aside>

      <!-- ۱) زمان‌ها -->
      <section class="card" aria-labelledby="days-title">
        <div class="card-head">
          <h3 id="days-title">چند روز مانده به پایان قرارداد؟</h3>
          <p>یک یا چند زمان را انتخاب کنید؛ برای هر زمان یک هشدار جداگانه ارسال می‌شود.</p>
        </div>

        <div class="chip-group" role="group" aria-labelledby="days-title">
          <button
              v-for="day in dayOptions"
              :key="day"
              type="button"
              class="chip-toggle"
              :class="{ 'is-on': form.days.includes(day) }"
              :aria-pressed="form.days.includes(day)"
              @click="toggleDay(day)"
          >
            <i :class="form.days.includes(day) ? 'pi pi-check' : 'pi pi-plus'" aria-hidden="true" />
            {{ fa(day) }} روز قبل
          </button>
        </div>

        <div class="inline-add">
          <label for="day-draft">زمان دلخواه (روز)</label>
          <div class="inline-add-row">
            <InputNumber
                v-model="dayDraft"
                input-id="day-draft"
                :min="1"
                :max="365"
                :use-grouping="false"
                placeholder="مثلاً ۴۵"
                :invalid="Boolean(draftErrors.day)"
                @keydown.enter.prevent="addCustomDay"
            />
            <Button type="button" label="افزودن" icon="pi pi-plus" severity="secondary" outlined :disabled="dayDraft === null" @click="addCustomDay" />
          </div>
        </div>

        <p v-if="draftErrors.day" class="field-error" role="alert">{{ draftErrors.day }}</p>
        <p v-if="visibleErrors.days" class="field-error" role="alert">{{ visibleErrors.days }}</p>
        <p v-else class="hint">مقدار مجاز از ۱ تا ۳۶۵ روز و حداکثر {{ fa(MAX_DAYS) }} زمان است. با ذخیره، زمان‌بندی هشدارهای ارسال‌نشده به‌روزرسانی می‌شود.</p>
      </section>

      <!-- ۲) روش‌های ارسال -->
      <section class="card" aria-labelledby="channels-title">
        <div class="card-head">
          <h3 id="channels-title">هشدار از چه راهی ارسال شود؟</h3>
          <p>هر روش را که می‌خواهید فعال کنید؛ برای ایمیل و پیامک باید گیرنده هم اضافه کنید.</p>
        </div>
        <p v-if="visibleErrors.channels" class="field-error" role="alert">{{ visibleErrors.channels }}</p>

        <div class="channels">
          <article v-for="option in channelOptions" :key="option.id" class="channel" :class="{ 'is-on': has(option.id) }">
            <label class="channel-head" :for="`channel-${option.id}`">
              <span class="icon-tile" aria-hidden="true"><i :class="option.icon" /></span>
              <span class="channel-copy">
                <strong>{{ option.title }}</strong>
                <small>{{ option.description }}</small>
              </span>
              <ToggleSwitch
                  :input-id="`channel-${option.id}`"
                  :model-value="has(option.id)"
                  @update:model-value="setChannel(option.id, $event)"
              />
            </label>

            <!-- گیرندگان پیامک -->
            <div v-if="option.id === 'sms' && has('sms')" class="channel-body">
              <label class="body-label" for="phone-draft">شماره موبایل گیرندگان</label>
              <div class="inline-add-row">
                <InputText
                    v-model="phoneDraft"
                    id="phone-draft"
                    type="tel"
                    inputmode="tel"
                    dir="ltr"
                    placeholder="09123456789"
                    autocomplete="tel"
                    :invalid="Boolean(draftErrors.phone || visibleErrors.sms)"
                    @keydown.enter.prevent="addPhone"
                />
                <Button type="button" label="افزودن" icon="pi pi-plus" severity="secondary" outlined :disabled="!phoneDraft.trim()" @click="addPhone" />
              </div>
              <ul v-if="form.phones.length" class="contacts" aria-label="شماره‌های ثبت‌شده">
                <li v-for="(phone, index) in form.phones" :key="phone + index" class="contact">
                  <span dir="ltr">{{ phone }}</span>
                  <button type="button" :aria-label="`حذف شماره ${phone}`" @click="removeAt(form.phones, index)"><i class="pi pi-times" /></button>
                </li>
              </ul>
              <p v-if="draftErrors.phone" class="field-error" role="alert">{{ draftErrors.phone }}</p>
              <p v-else-if="visibleErrors.sms" class="field-error" role="alert">{{ visibleErrors.sms }}</p>

              <div class="sms-preview">
                <span class="body-label">نمونه متن پیامک</span>
                <p>
                  هشدار پایان قرارداد: قرارداد شماره <b>[شماره قرارداد]</b> با موضوع <b>[موضوع]</b>،
                  <b>[تعداد روز]</b> روز دیگر در تاریخ <b>[تاریخ پایان]</b> به پایان می‌رسد.
                  لطفاً برای تمدید یا خاتمه آن اقدام کنید.
                </p>
              </div>
            </div>

            <!-- گیرندگان ایمیل -->
            <div v-if="option.id === 'email' && has('email')" class="channel-body">
              <label class="body-label" for="email-draft">نشانی ایمیل گیرندگان</label>
              <div class="inline-add-row">
                <InputText
                    v-model="emailDraft"
                    id="email-draft"
                    type="email"
                    inputmode="email"
                    dir="ltr"
                    placeholder="name@example.com"
                    autocomplete="email"
                    :invalid="Boolean(draftErrors.email || visibleErrors.email)"
                    @keydown.enter.prevent="addEmail"
                />
                <Button type="button" label="افزودن" icon="pi pi-plus" severity="secondary" outlined :disabled="!emailDraft.trim()" @click="addEmail" />
              </div>
              <ul v-if="form.emails.length" class="contacts" aria-label="ایمیل‌های ثبت‌شده">
                <li v-for="(mail, index) in form.emails" :key="mail + index" class="contact">
                  <span dir="ltr">{{ mail }}</span>
                  <button type="button" :aria-label="`حذف ایمیل ${mail}`" @click="removeAt(form.emails, index)"><i class="pi pi-times" /></button>
                </li>
              </ul>
              <p v-if="draftErrors.email" class="field-error" role="alert">{{ draftErrors.email }}</p>
              <p v-else-if="visibleErrors.email" class="field-error" role="alert">{{ visibleErrors.email }}</p>
            </div>
          </article>
        </div>
      </section>

      <!-- ۳) ساعت ارسال -->
      <section class="card card--row" aria-labelledby="time-title">
        <div class="card-head">
          <h3 id="time-title">ساعت ارسال</h3>
          <p>هشدارها هر روز در این ساعت و به وقت تهران ارسال می‌شوند.</p>
        </div>
        <div class="time-field">
          <label for="send-time">ساعت</label>
          <InputText id="send-time" v-model="form.sendTime" type="time" dir="ltr" required :invalid="Boolean(visibleErrors.time)" />
          <p v-if="visibleErrors.time" class="field-error" role="alert">{{ visibleErrors.time }}</p>
        </div>
      </section>

      <Message v-if="saveError" severity="error" :closable="false">{{ saveError }}</Message>

      <!-- نوار ذخیره -->
      <div class="action-bar">
        <p class="status" :class="{ 'is-dirty': dirty || !hasSaved }">
          <span class="dot" aria-hidden="true" />
          <template v-if="!hasSaved">هنوز تنظیماتی ذخیره نشده؛ مقادیر پیش‌فرض نمایش داده می‌شود.</template>
          <template v-else-if="dirty">تغییرات ذخیره‌نشده دارید.</template>
          <template v-else>همه تغییرات ذخیره شده است.</template>
        </p>
        <div class="bar-buttons">
          <Button type="button" label="لغو تغییرات" severity="secondary" text :disabled="!dirty || saving" @click="resetForm" />
          <Button type="submit" label="ذخیره تغییرات" icon="pi pi-check" :loading="saving" :disabled="!canSave" />
        </div>
      </div>
    </form>
  </section>
</template>

<style scoped>
.alert-settings {
  --ink: #172033;
  --muted: #64748b;
  --line: #dce5f0;
  --line-soft: #e7edf5;
  --brand: #315fba;
  --brand-soft: #edf4ff;
  --brand-line: #8fb2ed;
  --brand-wash: #f7faff;
  --danger: #dc2626;
  --warn: #d97706;
  --ok: #16a34a;

  display: grid;
  gap: 18px;
  max-width: 80%;
  color: var(--ink);
  font-family: 'Vazirmatn', sans-serif;
margin-right: 80px;
  margin-top: 20px;
}
.form { display: grid; gap: 18px; }
.skeletons { display: grid; gap: 18px; }

/* سربرگ */
.intro { display: flex; align-items: center; gap: 14px; padding: 18px; border: 1px solid var(--line); border-radius: 14px; background: #fff; }
.intro h2 { margin: 0; font-size: 18px; font-weight: 750; }
.intro p { margin: 4px 0 0; color: var(--muted); font-size: 13px; line-height: 1.9; }

.icon-tile { display: grid; place-items: center; flex: 0 0 40px; width: 40px; height: 40px; border-radius: 12px; color: var(--brand); background: var(--brand-soft); font-size: 16px; }
.icon-tile--lg { flex-basis: 46px; width: 46px; height: 46px; font-size: 19px; }

.message-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; width: 100%; }

/* خلاصه */
.summary { display: flex; gap: 10px; padding: 12px 16px; border: 1px solid var(--brand-line); border-radius: 12px; color: #26427e; background: var(--brand-wash); }
.summary i { margin-top: 5px; color: var(--brand); }
.summary p { margin: 0; font-size: 13px; line-height: 2; }

/* کارت‌ها */
.card { padding: 20px; border: 1px solid var(--line); border-radius: 14px; background: #fff; }
.card-head { margin-bottom: 16px; }
.card-head h3 { margin: 0; font-size: 16px; font-weight: 750; }
.card-head p { margin: 4px 0 0; color: var(--muted); font-size: 13px; line-height: 1.9; }
.card--row { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; }
.card--row .card-head { margin-bottom: 0; }

/* زمان‌ها */
.chip-group { display: flex; flex-wrap: wrap; gap: 8px; }
.chip-toggle { display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; border: 1px solid var(--line); border-radius: 999px; color: #475569; background: #fff; font: inherit; font-size: 13px; cursor: pointer; transition: border-color .15s, background-color .15s, color .15s; }
.chip-toggle i { font-size: 11px; }
.chip-toggle:hover { border-color: var(--brand-line); }
.chip-toggle.is-on { border-color: var(--brand); color: var(--brand); background: var(--brand-soft); font-weight: 650; }

.inline-add { display: grid; gap: 6px; margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--line-soft); }
.inline-add label, .body-label, .time-field label { color: #475569; font-size: 12px; font-weight: 600; }
.inline-add-row { display: flex; align-items: center; gap: 8px; max-width: 460px; }
.inline-add-row :deep(.p-inputnumber), .inline-add-row :deep(.p-inputtext) { flex: 1; min-width: 0; width: 100%; }
.inline-add-row :deep(.p-inputnumber-input) { width: 100%; }

.hint { margin: 12px 0 0; color: var(--muted); font-size: 12px; line-height: 1.9; }
.field-error { margin: 8px 0 0; color: var(--danger); font-size: 12px; line-height: 1.8; }

/* روش‌های ارسال */
.channels { display: grid; gap: 10px; }
.channel { border: 1px solid var(--line); border-radius: 12px; background: #fff; transition: border-color .15s, background-color .15s; }
.channel.is-on { border-color: var(--brand-line); background: var(--brand-wash); }
.channel-head { display: flex; align-items: center; gap: 12px; padding: 14px; cursor: pointer; }
.channel-copy { display: grid; flex: 1; gap: 3px; min-width: 0; }
.channel-copy strong { font-size: 14px; }
.channel-copy small { color: var(--muted); font-size: 12px; line-height: 1.8; }
.channel-body { display: grid; gap: 10px; margin: 0 14px; padding: 14px 0 16px; border-top: 1px solid var(--line-soft); }

.contacts { display: flex; flex-wrap: wrap; gap: 8px; margin: 0; padding: 0; list-style: none; }
.contact { display: inline-flex; align-items: center; gap: 8px; padding: 5px 6px 5px 12px; border: 1px solid var(--line); border-radius: 999px; background: #fff; font-size: 13px; }
.contact button { display: grid; place-items: center; width: 22px; height: 22px; border: 0; border-radius: 50%; color: #94a3b8; background: transparent; cursor: pointer; }
.contact button:hover { color: var(--danger); background: #fef2f2; }

.sms-preview { display: grid; gap: 6px; padding: 12px; border-radius: 10px; background: #fff; border: 1px dashed var(--line); }
.sms-preview p { margin: 0; color: #43546d; font-size: 12px; line-height: 2; }
.sms-preview b { padding: 1px 6px; border-radius: 6px; color: var(--brand); background: var(--brand-soft); font-weight: 600; }

/* ساعت */
.time-field { display: grid; gap: 6px; min-width: 170px; }
.time-field :deep(.p-inputtext) { width: 100%; }

/* نوار ذخیره */
.action-bar { position: sticky; bottom: 0; z-index: 5; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; border: 1px solid var(--line); border-radius: 14px; background: rgba(255, 255, 255, .94); backdrop-filter: blur(6px); box-shadow: 0 -4px 16px rgba(23, 32, 51, .06); }
.status { display: flex; align-items: center; gap: 8px; margin: 0; color: var(--muted); font-size: 13px; }
.status .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--ok); }
.status.is-dirty { color: #92400e; }
.status.is-dirty .dot { background: var(--warn); }
.bar-buttons { display: flex; align-items: center; gap: 6px; }

/* دسترس‌پذیری */
.chip-toggle:focus-visible, .contact button:focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; }
@media (prefers-reduced-motion: reduce) { .chip-toggle, .channel { transition: none; } }

@media (max-width: 600px) {
  .intro { align-items: flex-start; padding: 14px; }
  .card { padding: 15px; }
  .card--row { flex-direction: column; align-items: stretch; }
  .inline-add-row { max-width: none; }
  .action-bar { flex-direction: column; align-items: stretch; }
  .bar-buttons { justify-content: flex-end; }
  .message-row { flex-direction: column; align-items: flex-start; }
}
</style>