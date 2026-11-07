<!-- src/views/Contract/ContractSettings.vue -->
<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import api from '@/api/axios'
import { showToast } from '@/plugins/toast'

const props = defineProps({
  section: { type: String, default: 'letterhead', validator: value => value === 'letterhead' },
  embedded: { type: Boolean, default: false },
})

/* ───────── ثابت‌ها ───────── */
const ACCEPTED_TYPES = ['image/png', 'image/jpeg', 'image/webp']
const MAX_SIZE_BYTES = 5 * 1024 * 1024

// این مقادیر باید با PRINT_MARGINS_MM در ContractShow.vue یکی باشد
// (فقط برای نمایش «ناحیهٔ متن» در پیش‌نمایش استفاده می‌شود)
const A4_WIDTH_MM = 210
const A4_HEIGHT_MM = 297
const PRINT_MARGINS_MM = { top: 45, bottom: 35, side: 20 }
const BANNER_GAP_MM = 8
const BANNER_BOTTOM_MM = 20
const A4_RATIO = A4_HEIGHT_MM / A4_WIDTH_MM // ≈ 1.414

/* ───────── وضعیت: سقف امضا ───────── */
/* ───────── وضعیت: سربرگ ───────── */
const fileInput = ref(null)
const savedUrl = ref(null)       // تصویر ذخیره‌شده روی سرور (Object URL)
const pendingUrl = ref(null)     // تصویر انتخاب‌شده، هنوز آپلودنشده (Object URL)
const pendingFile = ref(null)
const imageInfo = ref(null)      // { width, height } تصویر در حال نمایش
const previewError = ref('')     // خطای نمایش تصویر در مرورگر
const serverNote = ref('')       // مشکل در دریافت تصویر از سرور
const showGuides = ref(true)
const dragging = ref(false)
const uploadingLetterhead = ref(false)
const deletingLetterhead = ref(false)
const confirmingDelete = ref(false)

let savedObjectUrl = null
let pendingObjectUrl = null

const revoke = (url) => { if (url) URL.revokeObjectURL(url) }

const previewSrc = computed(() => pendingUrl.value || savedUrl.value)
const isPending = computed(() => !!pendingUrl.value)

const imageRatio = computed(() =>
    imageInfo.value?.width ? imageInfo.value.height / imageInfo.value.width : null
)
// هم‌راستا با ContractShow: نسبت کمتر از ۱٫۲ = سربرگ نواری، در غیر این صورت تمام‌صفحه
const layoutKind = computed(() =>
    imageRatio.value !== null && imageRatio.value < 1.2 ? 'banner' : 'page'
)
const layoutLabel = computed(() => (layoutKind.value === 'banner' ? 'سربرگ نواری (بالای صفحه)' : 'سربرگ تمام‌صفحه'))
const ratioWarning = computed(() =>
    imageRatio.value !== null &&
    layoutKind.value === 'page' &&
    Math.abs(imageRatio.value - A4_RATIO) / A4_RATIO > 0.08
)

// ناحیهٔ متن در پیش‌نمایش A4 (به درصد)
const textAreaStyle = computed(() => {
  let { top, bottom } = PRINT_MARGINS_MM
  const { side } = PRINT_MARGINS_MM
  if (layoutKind.value === 'banner' && imageRatio.value) {
    top = Math.ceil(A4_WIDTH_MM * imageRatio.value) + BANNER_GAP_MM
    bottom = BANNER_BOTTOM_MM
  }
  return {
    top: `${(top / A4_HEIGHT_MM) * 100}%`,
    bottom: `${(bottom / A4_HEIGHT_MM) * 100}%`,
    right: `${(side / A4_WIDTH_MM) * 100}%`,
    left: `${(side / A4_WIDTH_MM) * 100}%`,
  }
})

const sampleLines = [100, 96, 100, 88, 100, 94, 60, 0, 100, 98, 100, 92, 100, 72, 0, 100, 95, 100, 84]

const formatSize = (bytes) => {
  if (!bytes) return ''
  const mb = bytes / 1024 / 1024
  return mb >= 1
      ? `${mb.toLocaleString('fa-IR', { maximumFractionDigits: 1 })} مگابایت`
      : `${Math.max(1, Math.round(bytes / 1024)).toLocaleString('fa-IR')} کیلوبایت`
}

/* ───────── کمکی‌ها ───────── */
// وقتی responseType=blob است، بدنهٔ خطای سرور هم Blob می‌شود؛ اینجا پیام واقعی را بیرون می‌کشیم
const readErrorMessage = async (error, fallback) => {
  const status = error?.response?.status
  const data = error?.response?.data
  let message = ''
  try {
    if (data instanceof Blob) {
      const text = await data.text()
      try { message = JSON.parse(text)?.message || '' } catch { message = '' }
    } else {
      message = data?.message || (typeof data === 'string' ? data : '')
    }
  } catch { /* ignore */ }
  message = message || error?.message || fallback
  return status ? `${message} (کد ${status})` : message
}

// اگر سرور Content-Type درست نفرستد (مثلاً octet-stream)، نوع تصویر را از بایت‌های ابتدایی تشخیص می‌دهیم
const sniffImageType = async (blob) => {
  const bytes = new Uint8Array(await blob.slice(0, 12).arrayBuffer())
  const startsWith = (...sig) => sig.every((b, i) => bytes[i] === b)
  if (startsWith(0x89, 0x50, 0x4e, 0x47)) return 'image/png'
  if (startsWith(0xff, 0xd8, 0xff)) return 'image/jpeg'
  if (startsWith(0x52, 0x49, 0x46, 0x46) && bytes[8] === 0x57 && bytes[9] === 0x45 && bytes[10] === 0x42 && bytes[11] === 0x50) return 'image/webp'
  return null
}

const replaceSaved = (url) => {
  revoke(savedObjectUrl)
  savedObjectUrl = url
  savedUrl.value = url
}

const clearPending = () => {
  revoke(pendingObjectUrl)
  pendingObjectUrl = null
  pendingUrl.value = null
  pendingFile.value = null
  if (fileInput.value) fileInput.value.value = ''
}

/* ───────── دریافت سربرگ ذخیره‌شده ───────── */
const loadSavedLetterhead = async () => {
  const response = await api.get('/contract-settings/letterhead/image', {
    responseType: 'blob',
    params: { v: Date.now() }, // جلوگیری از نمایش نسخهٔ قدیمیِ کش‌شده بعد از آپلود
  })
  const payload = response instanceof Blob ? response : response?.data
  if (!(payload instanceof Blob) || payload.size === 0) {
    throw new Error('سرور تصویر خالی برگرداند.')
  }

  let blob = payload
  if (!blob.type.startsWith('image/')) {
    const sniffed = await sniffImageType(blob)
    if (sniffed) {
      blob = new Blob([blob], { type: sniffed })
    } else {
      let detail = 'پاسخ سرور تصویر معتبر نیست.'
      try { detail = JSON.parse(await blob.text())?.message || detail } catch { /* ignore */ }
      throw new Error(detail)
    }
  }

  replaceSaved(URL.createObjectURL(blob))
  serverNote.value = ''
}

const load = async () => {
  try {
    const { data } = await api.get('/contract-settings')
    if (data.data.has_letterhead) {
      try {
        await loadSavedLetterhead()
      } catch (error) {
        serverNote.value = `سربرگ ثبت شده است، اما دریافت تصویر از سرور انجام نشد: ${await readErrorMessage(error, 'خطای نامشخص')}`
      }
    }
  } catch (error) {
    showToast({ severity: 'error', summary: 'خطا', detail: await readErrorMessage(error, 'بارگذاری تنظیمات انجام نشد.') })
  }
}

/* ───────── انتخاب فایل (کلیک یا کشیدن و رها کردن) ───────── */
const setPending = (file) => {
  if (!file) return
  if (!ACCEPTED_TYPES.includes(file.type)) {
    showToast({ severity: 'warn', summary: 'فرمت نامعتبر', detail: 'فقط تصاویر PNG، JPG و WebP پذیرفته می‌شوند.', life: 4000 })
    return
  }
  if (file.size > MAX_SIZE_BYTES) {
    showToast({ severity: 'warn', summary: 'حجم زیاد', detail: 'حجم تصویر نباید بیشتر از ۵ مگابایت باشد.', life: 4000 })
    return
  }
  revoke(pendingObjectUrl)
  pendingObjectUrl = URL.createObjectURL(file)
  pendingUrl.value = pendingObjectUrl
  pendingFile.value = file
  imageInfo.value = null
  previewError.value = ''
  confirmingDelete.value = false
}

const onFileInput = (event) => {
  setPending(event.target.files?.[0])
  event.target.value = '' // انتخاب دوبارهٔ همان فایل هم رویداد change بدهد
}

const onDrop = (event) => {
  dragging.value = false
  setPending(event.dataTransfer?.files?.[0])
}

const openFileDialog = () => fileInput.value?.click()

const onPreviewLoad = (event) => {
  imageInfo.value = { width: event.target.naturalWidth, height: event.target.naturalHeight }
  previewError.value = ''
}

const onPreviewError = () => {
  imageInfo.value = null
  previewError.value = 'مرورگر نتوانست این تصویر را نمایش دهد. فایل ممکن است خراب باشد.'
}

/* ───────── آپلود / حذف ───────── */
const uploadLetterhead = async () => {
  if (!pendingFile.value) return
  uploadingLetterhead.value = true

  try {
    const form = new FormData()
    form.append('image', pendingFile.value)
    await api.post('/contract-settings/letterhead', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  } catch (error) {
    showToast({ severity: 'error', summary: 'خطا', detail: await readErrorMessage(error, 'بارگذاری سربرگ انجام نشد.'), life: 6000 })
    uploadingLetterhead.value = false
    return
  }

  // آپلود موفق بود؛ حالا نسخهٔ ذخیره‌شده را از سرور می‌گیریم
  try {
    await loadSavedLetterhead()
    clearPending()
    showToast({ severity: 'success', summary: 'ذخیره شد', detail: 'سربرگ چاپ قرارداد به‌روزرسانی شد.' })
  } catch (error) {
    // تصویر ذخیره شده، ولی نمایش آن از سرور ممکن نشد: همان تصویر محلی را نگه می‌داریم تا پیش‌نمایش خالی نماند
    const reason = await readErrorMessage(error, 'خطای نامشخص')
    const localUrl = pendingObjectUrl
    pendingObjectUrl = null // مالکیت Object URL به تصویر ذخیره‌شده منتقل می‌شود
    pendingUrl.value = null
    pendingFile.value = null
    if (fileInput.value) fileInput.value.value = ''
    replaceSaved(localUrl)
    serverNote.value = `سربرگ ذخیره شد، اما دریافت دوبارهٔ آن از سرور ناموفق بود: ${reason}`
    showToast({ severity: 'warn', summary: 'ذخیره شد', detail: 'پیش‌نمایش از روی فایل انتخابی نشان داده می‌شود؛ جزئیات زیر تصویر آمده است.', life: 6000 })
  } finally {
    uploadingLetterhead.value = false
  }
}

const deleteLetterhead = async () => {
  deletingLetterhead.value = true
  try {
    await api.delete('/contract-settings/letterhead')
    replaceSaved(null)
    clearPending()
    imageInfo.value = null
    previewError.value = ''
    serverNote.value = ''
    confirmingDelete.value = false
    showToast({ severity: 'success', summary: 'حذف شد', detail: 'سربرگ چاپ حذف شد.' })
  } catch (error) {
    showToast({ severity: 'error', summary: 'خطا', detail: await readErrorMessage(error, 'حذف سربرگ انجام نشد.') })
  } finally {
    deletingLetterhead.value = false
  }
}

onMounted(load)
onBeforeUnmount(() => {
  revoke(savedObjectUrl)
  revoke(pendingObjectUrl)
})
</script>

<template>
  <main :class="[props.embedded ? 'space-y-5' : 'p-6 max-w-3xl mx-auto space-y-5', 'contract-settings']">
    <header v-if="!props.embedded">
      <h1 class="text-2xl font-bold">تنظیمات سربرگ چاپ قرارداد</h1>
      <p class="text-surface-500 mt-1">تصویر سربرگ رسمی مورد استفاده در چاپ قراردادها را مدیریت کنید.</p>
    </header>

    <!-- سقف امضا -->
    <!-- سربرگ -->
    <Card v-if="props.section === 'all' || props.section === 'letterhead'" id="letterhead">
      <template #title>سربرگ چاپ قرارداد</template>
      <template #content>
        <p class="lh-help">
          تصویر سربرگ رسمی شرکت را بارگذاری کنید. این تصویر هنگام چاپ <strong>پس‌زمینهٔ همهٔ صفحه‌های قرارداد</strong> می‌شود
          و متن و امضاها روی آن چاپ می‌شوند. برای بهترین نتیجه یک تصویر A4 (مثلاً ۲۴۸۰×۳۵۰۸ پیکسل) با بالا و پایین طراحی‌شده و وسط خالی بدهید.
          فرمت‌های PNG، JPG و WebP تا ۵ مگابایت پشتیبانی می‌شوند.
        </p>

        <div class="lh-layout">
          <!-- پیش‌نمایش A4 -->
          <div class="lh-preview-col">
            <div class="lh-a4" :class="{ 'is-empty': !previewSrc }">
              <img
                  v-if="previewSrc"
                  :key="previewSrc"
                  :src="previewSrc"
                  alt="پیش‌نمایش سربرگ قرارداد"
                  class="lh-a4__img"
                  :class="{ 'lh-a4__img--banner': layoutKind === 'banner' }"
                  @load="onPreviewLoad"
                  @error="onPreviewError"
              />
              <div v-if="previewSrc" class="lh-a4__text" :class="{ 'with-guides': showGuides }" :style="textAreaStyle" aria-hidden="true">
                <span class="lh-a4__line lh-a4__line--title"></span>
                <span
                    v-for="(width, index) in sampleLines"
                    :key="index"
                    class="lh-a4__line"
                    :class="{ 'lh-a4__line--gap': width === 0 }"
                    :style="{ width: width ? `${width}%` : '100%' }"
                ></span>
              </div>
              <div v-else class="lh-a4__empty">
                <i class="pi pi-image"></i>
                <span>هنوز سربرگی برای چاپ ثبت نشده است.</span>
              </div>
            </div>

            <label v-if="previewSrc" class="lh-guides-toggle">
              <input v-model="showGuides" type="checkbox" />
              نمایش ناحیهٔ متن
            </label>
          </div>

          <!-- کنترل‌ها -->
          <div class="lh-controls-col">
            <input
                ref="fileInput"
                type="file"
                accept="image/png,image/jpeg,image/webp"
                class="lh-file-input"
                @change="onFileInput"
            />

            <div
                class="lh-dropzone"
                :class="{ 'is-dragging': dragging }"
                role="button"
                tabindex="0"
                @click="openFileDialog"
                @keydown.enter.prevent="openFileDialog"
                @keydown.space.prevent="openFileDialog"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="onDrop"
            >
              <i class="pi pi-cloud-upload"></i>
              <strong>تصویر را اینجا رها کنید</strong>
              <span>یا برای انتخاب فایل کلیک کنید</span>
            </div>

            <div v-if="isPending" class="lh-status lh-status--pending">
              <i class="pi pi-info-circle"></i>
              <div>
                <strong>پیش‌نمایش فایل انتخابی — هنوز ذخیره نشده</strong>
                <span class="lh-file-name">{{ pendingFile?.name }} • {{ formatSize(pendingFile?.size) }}</span>
              </div>
            </div>
            <div v-else-if="savedUrl" class="lh-status lh-status--saved">
              <i class="pi pi-check-circle"></i>
              <strong>سربرگ ذخیره‌شده</strong>
            </div>

            <ul v-if="imageInfo" class="lh-meta">
              <li>ابعاد: <bdi>{{ imageInfo.width.toLocaleString('fa-IR') }} × {{ imageInfo.height.toLocaleString('fa-IR') }}</bdi> پیکسل</li>
              <li>نوع چیدمان در چاپ: {{ layoutLabel }}</li>
            </ul>

            <p v-if="ratioWarning" class="lh-note lh-note--warn">
              نسبت این تصویر با A4 تفاوت دارد و هنگام چاپ ممکن است کمی کشیده شود.
              پیشنهاد می‌شود تصویر را با نسبت A4 (عرض ۲۱۰ به ارتفاع ۲۹۷) آماده کنید.
            </p>
            <p v-if="previewError" class="lh-note lh-note--error">{{ previewError }}</p>
            <p v-if="serverNote" class="lh-note lh-note--warn">{{ serverNote }}</p>

            <div class="lh-actions">
              <Button
                  label="بارگذاری سربرگ"
                  icon="pi pi-upload"
                  :loading="uploadingLetterhead"
                  :disabled="!pendingFile"
                  @click="uploadLetterhead"
              />
              <Button
                  v-if="isPending"
                  label="انصراف"
                  icon="pi pi-times"
                  severity="secondary"
                  text
                  :disabled="uploadingLetterhead"
                  @click="clearPending"
              />
              <template v-if="savedUrl && !isPending">
                <Button
                    v-if="!confirmingDelete"
                    label="حذف سربرگ"
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    @click="confirmingDelete = true"
                />
                <template v-else>
                  <Button
                      label="تأیید حذف"
                      icon="pi pi-check"
                      severity="danger"
                      :loading="deletingLetterhead"
                      @click="deleteLetterhead"
                  />
                  <Button
                      label="انصراف"
                      severity="secondary"
                      text
                      :disabled="deletingLetterhead"
                      @click="confirmingDelete = false"
                  />
                </template>
              </template>
            </div>
          </div>
        </div>
      </template>
    </Card>
  </main>
</template>

<style scoped>

.lh-help { margin: 0 0 18px; font-size: 13px; line-height: 2; color: #64748b; }
.lh-help strong { color: #334155; }

.lh-layout { display: grid; grid-template-columns: 240px minmax(0, 1fr); gap: 24px; align-items: start; }

/* پیش‌نمایش A4 */
.lh-preview-col { display: flex; flex-direction: column; align-items: center; gap: 10px; }
.lh-a4 {
  position: relative;
  width: 100%;
  max-width: 240px;
  aspect-ratio: 210 / 297;
  overflow: hidden;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  background: #fff;
  box-shadow: 0 6px 20px rgba(15, 23, 42, .12);
}
.lh-a4.is-empty { display: grid; place-items: center; border-style: dashed; background: #f8fafc; box-shadow: none; }
.lh-a4__img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: fill; }
.lh-a4__img--banner { bottom: auto; height: auto; }
.lh-a4__text { position: absolute; display: flex; flex-direction: column; gap: 2.2%; overflow: hidden; }
.lh-a4__text.with-guides { outline: 1px dashed rgba(37, 99, 235, .55); outline-offset: 2px; background: rgba(37, 99, 235, .04); }
.lh-a4__line { align-self: flex-start; flex: 0 0 auto; height: 1.4%; min-height: 2px; border-radius: 99px; background: rgba(23, 32, 51, .22); }
.lh-a4__line--title { width: 55%; align-self: center; height: 2%; margin-bottom: 2%; background: rgba(18, 59, 93, .4); }
.lh-a4__line--gap { background: transparent; }
.lh-a4__empty { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 16px; text-align: center; font-size: 12px; line-height: 1.8; color: #94a3b8; }
.lh-a4__empty .pi { font-size: 28px; }
.lh-guides-toggle { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; cursor: pointer; }

/* کنترل‌ها */
.lh-controls-col { display: flex; flex-direction: column; gap: 14px; min-width: 0; }
.lh-file-input { display: none; }

.lh-dropzone {
  display: flex; flex-direction: column; align-items: center; gap: 4px;
  padding: 22px 16px; border: 2px dashed #cbd5e1; border-radius: 12px;
  background: #f8fafc; text-align: center; color: #64748b; cursor: pointer;
  transition: border-color .15s ease, background-color .15s ease;
}
.lh-dropzone:hover, .lh-dropzone:focus-visible, .lh-dropzone.is-dragging { border-color: #2563eb; background: #eff6ff; outline: none; }
.lh-dropzone .pi { font-size: 26px; color: #2563eb; margin-bottom: 4px; }
.lh-dropzone strong { font-size: 14px; color: #1e293b; }
.lh-dropzone span { font-size: 12px; }

.lh-status { display: flex; align-items: flex-start; gap: 8px; padding: 10px 12px; border-radius: 10px; font-size: 13px; line-height: 1.8; }
.lh-status .pi { margin-top: 4px; }
.lh-status > div { display: flex; flex-direction: column; min-width: 0; }
.lh-status--pending { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
.lh-status--saved { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.lh-file-name { font-size: 12px; overflow-wrap: anywhere; }

.lh-meta { margin: 0; padding: 0; list-style: none; font-size: 12.5px; line-height: 2; color: #475569; }

.lh-note { margin: 0; padding: 9px 12px; border-radius: 10px; font-size: 12.5px; line-height: 1.9; overflow-wrap: anywhere; }
.lh-note--warn { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
.lh-note--error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

.lh-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }

@media (max-width: 640px) {
  .lh-layout { grid-template-columns: minmax(0, 1fr); }
}
</style>
