<!-- src/views/Contract/ContractShow.vue (ادامه) -->
<script setup>
import { ref, onMounted, onBeforeUnmount, computed, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useContractStore } from '@/stores/contractStore'
import { useAmendmentStore } from '@/stores/amendmentStore'
import { useInvoiceStore } from '@/stores/invoiceStore'
import { useGuaranteeStore } from '@/stores/guaranteeStore'
import { useSignatureStore } from '@/stores/signatureStore'
import { usePermission } from '@/composables/usePermission'
import { useContract } from '@/composables/useContract'
import { useAmendment } from '@/composables/useAmendment'
import { useInvoice } from '@/composables/useInvoice'
import { useGuarantee } from '@/composables/useGuarantee'
import ContractStatusBadge from '@/components/contract/ContractStatusBadge.vue'
import ContractTypeBadge from '@/components/contract/ContractTypeBadge.vue'
import AmountDisplay from '@/components/contract/AmountDisplay.vue'
import DateDisplay from '@/components/contract/DateDisplay.vue'
import ContractItemsTable from '@/components/contract/ContractItemsTable.vue'
import ContractTimeline from '@/components/contract/ContractTimeline.vue'
import LegalReviewPanel from '@/views/Contract/LegalReviewPanel.vue'
import SignaturePanel from '@/components/contract/SignaturePanel.vue'
import ExecutiveSignPanel from '@/components/contract/ExecutiveSignPanel.vue'
import EmptyState from '@/components/contract/EmptyState.vue'
import { showToast } from '@/plugins/toast'
import api from '@/api/axios.js'
import {useAuthStore} from "@/stores/auth.js";
const authStore = useAuthStore()

const router = useRouter()
const route = useRoute()
const contractStore = useContractStore()
const amendmentStore = useAmendmentStore()
const invoiceStore = useInvoiceStore()
const guaranteeStore = useGuaranteeStore()
const signatureStore = useSignatureStore()
const { can } = usePermission()
const { getContractProgress, getAmountChange, getDisplayStatus } = useContract()
const { getStatusInfo: getAmendmentStatusInfo } = useAmendment()
const { getStatusInfo: getInvoiceStatusInfo } = useInvoice()
const { getStatusInfo: getGuaranteeStatusInfo } = useGuarantee()

const loading = ref(true)
const contract = ref(null)
const requiresGuarantee = ref(false)
const guaranteeType = ref(null)
const guaranteeInstrumentType = ref(null)
const requiresBoardSignature = ref(false)
const savingGuarantee = ref(false)
const letterheadUrl = ref(null)
const letterheadLoadError = ref(false)
const letterheadErrorMessage = ref('')
const printDocumentType = ref('contract')
let letterheadObjectUrl = null

// ───────── تنظیمات چاپ روی سربرگ (میلی‌متر) ─────────
// اگر سربرگ شما ناحیهٔ بالا/پایین بزرگ‌تر یا کوچک‌تری دارد، فقط همین مقادیر را تغییر بدهید.
const A4_WIDTH_MM = 210
const PRINT_MARGINS_MM = { top: 45, bottom: 35, side: 20 } // سربرگ تمام‌صفحه (A4)
const PRINT_MARGINS_NO_LETTERHEAD_MM = { top: 20, bottom: 20, side: 20 }
const BANNER_GAP_MM = 8 // فاصلهٔ متن از سربرگ نواری
const BANNER_BOTTOM_MM = 20

// امضاها: نوار ثابت پایین هر صفحه، هر امضا ۱۰۰×۱۰۰ پیکسل
// PRINT_MARGINS_MM.bottom = ارتفاع ناحیهٔ طرح پایین سربرگ؛ نوار امضا درست بالای آن قرار می‌گیرد.
// اگر می‌خواهید امضاها پایین‌تر (روی طرح پایین سربرگ) بنشینند، مقدار bottom را کمتر کنید.
const SIGNATURE_SIZE_PX = 100
const SIGNATURE_GAP_PX = 6
const SIGNATURE_TEXT_GAP_MM = 6          // فاصلهٔ متن قرارداد از نوار امضا
const SIGNATURE_BOTTOM_NO_FOOTER_MM = 10 // فاصلهٔ نوار از لبهٔ صفحه وقتی سربرگ تمام‌صفحه نیست
const PX_TO_MM = 25.4 / 96

// نسبت ارتفاع به عرض تصویر سربرگ؛ برای تشخیص «سربرگ تمام‌صفحه» از «سربرگ نواری»
const letterheadRatio = ref(null)
const onLetterheadLoad = (event) => {
  const { naturalWidth, naturalHeight } = event.target
  if (naturalWidth && naturalHeight) letterheadRatio.value = naturalHeight / naturalWidth
}
// تصویر نزدیک به نسبت A4 (≈1.41) = تمام‌صفحه | تصویر پهن و کم‌ارتفاع = نواری
const letterheadIsBanner = computed(() => letterheadRatio.value !== null && letterheadRatio.value < 1.2)

const printLayoutStyle = computed(() => {
  const hasFullPageLetterhead = !!letterheadUrl.value && !letterheadIsBanner.value
  const { top: pageTop, bottom: pageFooterZone, side } = letterheadUrl.value ? PRINT_MARGINS_MM : PRINT_MARGINS_NO_LETTERHEAD_MM

  let top = pageTop
  if (letterheadUrl.value && letterheadIsBanner.value) {
    top = Math.ceil(A4_WIDTH_MM * letterheadRatio.value) + BANNER_GAP_MM
  }
  // فاصلهٔ نوار امضا از لبهٔ پایین صفحه
  const signatureBottom = hasFullPageLetterhead ? pageFooterZone : SIGNATURE_BOTTOM_NO_FOOTER_MM

  // ارتفاع نوار امضا: اگر امضاها از یک ردیف بیشتر شدند، ردیف‌ها روی هم جمع می‌شوند
  const slots = Math.max(printableSignatures.value.length, 3)
  const contentWidthPx = (A4_WIDTH_MM - side * 2) / PX_TO_MM
  const perRow = Math.max(1, Math.floor((contentWidthPx + SIGNATURE_GAP_PX) / (SIGNATURE_SIZE_PX + SIGNATURE_GAP_PX)))
  const rows = Math.ceil(slots / perRow)
  const stripHeightMm = (rows * SIGNATURE_SIZE_PX + (rows - 1) * SIGNATURE_GAP_PX) * PX_TO_MM

  const bottom = signatureBottom + stripHeightMm + SIGNATURE_TEXT_GAP_MM
  return {
    '--cp-top': `${top}mm`,
    '--cp-bottom': `${bottom.toFixed(2)}mm`,
    '--cp-side': `${side}mm`,
    '--cp-sig-bottom': `${signatureBottom}mm`,
    '--cp-sig-size': `${SIGNATURE_SIZE_PX}px`,
    '--cp-sig-gap': `${SIGNATURE_GAP_PX}px`,
  }
})

const formatSignDate = (value) => {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleDateString('fa-IR', { year: 'numeric', month: 'long', day: 'numeric' })
}

// صبر می‌کنیم تا فونت، سربرگ و تصاویر امضا کامل بارگذاری شوند، بعد پنجرهٔ چاپ باز می‌شود
const waitForPrintAssets = async () => {
  const root = document.querySelector('.contract-print-root')
  const images = root ? Array.from(root.querySelectorAll('img')) : []
  await Promise.all([
    document.fonts?.ready,
    ...images.map((img) => {
      if (img.complete && img.naturalWidth) return null
      if (img.decode) return img.decode()
      return new Promise((resolve) => { img.onload = img.onerror = resolve })
    }),
  ])

  const letterhead = root?.querySelector('.cp-letterhead')
  if (letterhead && (!letterhead.complete || !letterhead.naturalWidth)) {
    throw new Error('تصویر سربرگ بارگذاری نشد.')
  }
}

const printContent = async () => {
  try {
    printDocumentType.value = 'contract'
    if (letterheadLoadError.value) {
      // دریافت قبلی ناموفق بوده؛ قبل از انصراف یک بار دیگر تلاش می‌کنیم
      await loadLetterhead()
      if (letterheadLoadError.value) {
        throw new Error(`دریافت تصویر سربرگ ناموفق بود: ${letterheadErrorMessage.value || 'خطای نامشخص'}`)
      }
    }
    await nextTick()
    await waitForPrintAssets()
    requestAnimationFrame(() => window.print())
  } catch (error) {
    showToast({ severity: 'error', summary: 'چاپ قرارداد', detail: error.message, life: 8000 })
  }
}

const printNotificationLetter = async () => {
  try {
    if (!contract.value?.notification_letter) throw new Error('ابلاغیه‌ای برای این قرارداد ثبت نشده است.')
    printDocumentType.value = 'notification'
    if (letterheadLoadError.value) {
      await loadLetterhead()
      if (letterheadLoadError.value) throw new Error(`دریافت تصویر سربرگ ناموفق بود: ${letterheadErrorMessage.value || 'خطای نامشخص'}`)
    }
    await nextTick()
    await waitForPrintAssets()
    requestAnimationFrame(() => window.print())
  } catch (error) {
    printDocumentType.value = 'contract'
    showToast({ severity: 'error', summary: 'چاپ ابلاغیه', detail: error.message, life: 8000 })
  }
}

const notificationDate = (value) => {
  if (!value) return '—'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('fa-IR', { year: 'numeric', month: 'long', day: 'numeric' }).format(date)
}
const notificationAmount = (value, currency) => {
  const amount = Number(value)
  const currencyLabel = currency === 'IRR' ? 'ریال' : currency || ''
  return `${Number.isFinite(amount) ? new Intl.NumberFormat('fa-IR').format(amount) : value || '—'} ${currencyLabel}`.trim()
}
const notificationLetterBodyHtml = computed(() => {
  const body = String(contract.value?.notification_letter?.body || '')
  if (!body) return ''
  if (/<\/?(p|br|strong|b|em|i|u|s|ol|ul|li|blockquote|h1|h2|h3)(\s|\/?>)/i.test(body)) return body
  const escaped = body.replace(/[&<>"']/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  })[char])
  return `<p>${escaped.replace(/\r?\n/g, '<br>')}</p>`
})

// وقتی responseType=blob است بدنهٔ خطای سرور هم Blob می‌شود؛ پیام واقعی را از آن بیرون می‌کشیم
const readRequestError = async (error) => {
  const data = error?.response?.data
  let message = ''
  try {
    if (data instanceof Blob) {
      const text = await data.text()
      try { message = JSON.parse(text)?.message || '' } catch { message = '' }
    } else {
      message = data?.message || ''
    }
  } catch { /* ignore */ }
  message = message || error?.message || 'خطای نامشخص'
  const status = error?.response?.status
  return status ? `${message} (کد ${status})` : message
}

// نوع واقعی تصویر را از بایت‌های فایل تشخیص می‌دهد؛ اگر قبل از امضای تصویر بایت اضافی باشد
// (مثلاً خط خالی یا BOM که از یک فایل PHP در پاسخ می‌آید) آفست آن را هم برمی‌گرداند
const inspectImageBlob = async (blob) => {
  const bytes = new Uint8Array(await blob.slice(0, 256).arrayBuffer())
  const head = Array.from(bytes.slice(0, 16), (byte) => byte.toString(16).padStart(2, '0')).join(' ')
  const matchAt = (offset, ...signature) => signature.every((byte, index) => bytes[offset + index] === byte)

  for (let offset = 0; offset < bytes.length - 11; offset += 1) {
    if (matchAt(offset, 0x89, 0x50, 0x4e, 0x47)) return { type: 'image/png', offset, head }
    if (matchAt(offset, 0xff, 0xd8, 0xff)) return { type: 'image/jpeg', offset, head }
    if (matchAt(offset, 0x52, 0x49, 0x46, 0x46) && matchAt(offset + 8, 0x57, 0x45, 0x42, 0x50)) return { type: 'image/webp', offset, head }
  }
  return { type: null, offset: -1, head }
}

const loadImageElement = (url) => new Promise((resolve, reject) => {
  const image = new Image()
  image.onload = () => resolve(image)
  image.onerror = () => reject(new Error('مرورگر نتوانست تصویر سربرگ را بخواند؛ فایل دریافتی خراب یا ناقص است.'))
  image.src = url
})

const clearLetterhead = () => {
  letterheadUrl.value = null
  letterheadRatio.value = null
  if (letterheadObjectUrl) URL.revokeObjectURL(letterheadObjectUrl)
  letterheadObjectUrl = null
}

const loadLetterhead = async () => {
  letterheadLoadError.value = false
  letterheadErrorMessage.value = ''
  let objectUrl = null

  try {
    // فقط یک «راهنما» است و اگر خطا بدهد بی‌صدا نادیده گرفته می‌شود.
    // نکته: GET /contract-settings نیاز به دسترسی contract-settings.manage دارد (برای کاربران عادی ۴۰۳ می‌دهد)؛
    // endpoint متد letterhead() در کنترلر چنین محدودیتی ندارد.
    let hasLetterhead = null
    try {
      const { data: meta } = await api.get('/contract-settings/letterhead')
      if (typeof meta?.data?.has_letterhead === 'boolean') hasLetterhead = meta.data.has_letterhead
    } catch { /* ignore */ }

    if (hasLetterhead === false) {
      clearLetterhead()
      return
    }

    let response
    try {
      response = await api.get('/contract-settings/letterhead/image', {
        responseType: 'blob',
        params: { v: Date.now() }, // جلوگیری از کش
      })
    } catch (error) {
      // اگر سربرگی ثبت نشده و سرور ۴۰۴ می‌دهد، خطا نیست
      if (error?.response?.status === 404 && hasLetterhead !== true) {
        clearLetterhead()
        return
      }
      throw error
    }

    const payload = response instanceof Blob ? response : response?.data
    if (!(payload instanceof Blob) || payload.size === 0) {
      if (hasLetterhead !== true) {
        clearLetterhead()
        return
      }
      throw new Error('سرور تصویر خالی برگرداند.')
    }

    let blob = payload
    const declaredType = (blob.type || '').split(';')[0].trim().toLowerCase()
    const inspected = await inspectImageBlob(blob)
    if (!inspected.type) {
      throw new Error(
          `فایل دریافتی تصویر معتبر نیست (نوع اعلام‌شده: ${declaredType || 'نامشخص'}، حجم: ${blob.size} بایت، بایت‌های ابتدایی: ${inspected.head}).`
      )
    }
    if (inspected.offset > 0) {
      // بایت‌های اضافی قبل از تصویر (خروجی ناخواسته در بک‌اند)؛ نمایش را با حذف آن‌ها ادامه می‌دهیم
      console.warn(`پاسخ سربرگ ${inspected.offset} بایت اضافه قبل از تصویر دارد؛ بایت‌های ابتدایی: ${inspected.head}`)
      blob = blob.slice(inspected.offset, blob.size, inspected.type)
    } else if (declaredType !== inspected.type) {
      blob = new Blob([blob], { type: inspected.type })
    }

    objectUrl = URL.createObjectURL(blob)

    // قبل از دادن تصویر به قالب چاپ مطمئن می‌شویم مرورگر آن را کامل می‌خواند
    const probe = await loadImageElement(objectUrl)
    if (!probe.naturalWidth || !probe.naturalHeight) {
      throw new Error('فایل سربرگ دریافت شد، اما تصویر قابل‌خواندن نیست.')
    }

    if (letterheadObjectUrl) URL.revokeObjectURL(letterheadObjectUrl)
    letterheadObjectUrl = objectUrl
    letterheadRatio.value = probe.naturalHeight / probe.naturalWidth
    letterheadUrl.value = objectUrl
  } catch (error) {
    if (objectUrl) URL.revokeObjectURL(objectUrl)
    letterheadUrl.value = null
    letterheadLoadError.value = true
    letterheadErrorMessage.value = await readRequestError(error)
    console.error('دریافت سربرگ چاپ قرارداد ناموفق بود:', error)
  }
}

// فقط لحظهٔ چاپ: هر چیزی جز نسخهٔ چاپی قرارداد را پنهان می‌کنیم و بعد از چاپ برمی‌گردانیم.
// (به رویدادهای beforeprint/afterprint وصل است، پس هم دکمهٔ چاپ و هم Ctrl+P را پوشش می‌دهد)
const hiddenForPrint = []

const enterPrintMode = () => {
  document.body.classList.add('contract-printing')
  Array.from(document.body.children).forEach((element) => {
    if (element.classList.contains('contract-print-root')) return
    if (['SCRIPT', 'STYLE', 'LINK', 'TEMPLATE'].includes(element.tagName)) return
    element.setAttribute('data-print-hidden', '')
    hiddenForPrint.push(element)
  })
}

const leavePrintMode = () => {
  document.body.classList.remove('contract-printing')
  hiddenForPrint.splice(0).forEach((element) => element.removeAttribute('data-print-hidden'))
  printDocumentType.value = 'contract'
}

onMounted(async () => {
  window.addEventListener('beforeprint', enterPrintMode)
  window.addEventListener('afterprint', leavePrintMode)
  try {
    contract.value = await contractStore.fetchContractById(route.params.id)
    requiresGuarantee.value = !!contract.value?.requires_guarantee
    guaranteeType.value = contract.value?.guarantee_type || null
    guaranteeInstrumentType.value = contract.value?.guarantee_instrument_type || null
    requiresBoardSignature.value = !!contract.value?.requires_board_signature
    const relatedDataRequests = []
    relatedDataRequests.push(loadLetterhead())
    if (can('amendments.read')) {
      relatedDataRequests.push(amendmentStore.fetchAmendmentsByContract(route.params.id))
    }
    if (can('invoices.read')) {
      relatedDataRequests.push(invoiceStore.fetchInvoicesByContract(route.params.id))
    }
    if (can('guarantees.read')) {
      relatedDataRequests.push(guaranteeStore.fetchGuaranteesByContract(route.params.id))
    }
    if (can('signatures.sign')) {
      relatedDataRequests.push(signatureStore.fetchMyPendingSignatures().catch((error) => {
        console.error('خطا در دریافت امضاهای در انتظار:', error)
      }))
    }
    await Promise.all(relatedDataRequests)
  } catch (err) {
    showToast({ severity: 'error', summary: 'خطا', detail: err.message, life: 5000 })
    router.push('/contracts')
  } finally {
    loading.value = false
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('beforeprint', enterPrintMode)
  window.removeEventListener('afterprint', leavePrintMode)
  leavePrintMode()
  if (letterheadObjectUrl) URL.revokeObjectURL(letterheadObjectUrl)
})

const progress = computed(() => contract.value ? getContractProgress(contract.value) : 0)
const amountChange = computed(() => contract.value ? getAmountChange(contract.value) : null)
const statusInfo = computed(() => contract.value ? getDisplayStatus(contract.value) : null)
const isContractor = computed(() => authStore.hasRole('contractor'))
const contractorGuaranteeActionNeeded = computed(() =>
    isContractor.value &&
    !!contract.value?.requires_guarantee &&
    !contract.value?.has_guarantee &&
    ['pending_unit_sign', 'pending_legal_sign', 'pending_executive_sign', 'pending_notification'].includes(contract.value?.status)
)

const handleSignatureUpdated = async () => {
  // بارگذاری مجدد اطلاعات قرارداد برای نمایش وضعیت جدید (مثلاً بازگشت به draft)
  try {
    contract.value = await contractStore.fetchContractById(route.params.id)
    requiresGuarantee.value = !!contract.value?.requires_guarantee
    guaranteeType.value = contract.value?.guarantee_type || null
    guaranteeInstrumentType.value = contract.value?.guarantee_instrument_type || null
    requiresBoardSignature.value = !!contract.value?.requires_board_signature
  } catch (err) {
    console.error('خطا در به‌روزرسانی قرارداد:', err)
  }
}

// ترتیب امضاها در نوار چاپی (از راست به چپ):
// مدیریت حقوقی و قراردادها ← معاونت (واحد/مالی) ← مدیرعامل ← (عضو هیئت‌مدیره) ← پیمانکار
const signaturePrintRank = (signature) => {
  const text = `${signature.role} ${signature.roleKey || ''}`.toLowerCase()
  if (signature.signerType === 'contractor' || /پیمانکار|contractor/.test(text)) return 5
  if (/حقوقی|قراردادها|legal/.test(text)) return 1
  if (/معاون|deputy|unit/.test(text)) return 2 // قبل از مدیرعامل: «deputy_ceo» هم شامل ceo است
  if (/هیئت|هیات|board/.test(text)) return 4
  if (/مدیر\s*عامل|ceo/.test(text)) return 3
  return 2.5 // نقش‌های دیگر داخلی: بین معاونت و مدیرعامل
}

const sortSignaturesForPrint = (signatures) =>
    signatures
        .map((signature, index) => ({ signature, index, rank: signaturePrintRank(signature) }))
        .sort((a, b) => a.rank - b.rank || a.index - b.index)
        .map(({ signature }) => signature)

const printableSignatures = computed(() => sortSignaturesForPrint([
  ...(contract.value?.signatures || [])
      .filter(signature => signature.status === 'signed')
      .map(signature => ({
        id: `signature-${signature.id}`,
        name: signature.signer?.name || 'امضاکننده',
        role: signature.role_label || signature.signer_type_label || 'امضاکننده',
        roleKey: [signature.role, signature.role_key, signature.signer_role].filter(Boolean).join(' '),
        signerType: signature.signer_type || null,
        signedAt: signature.signed_at,
        signedAtLabel: formatSignDate(signature.signed_at),
        image: signature.signature_image || null,
      })),
  ...(contract.value?.executive_signatures || [])
      .filter(signature => signature.signed_at)
      .map(signature => ({
        id: `executive-${signature.id}`,
        name: signature.signer?.name || 'امضاکننده',
        role: signature.signer_role_label || ({ ceo: 'مدیرعامل', deputy_ceo: 'معاون مالی', board_member: 'عضو هیئت‌مدیره' }[signature.signer_role] || 'امضاکننده'),
        roleKey: signature.signer_role || '',
        signerType: null,
        signedAt: signature.signed_at,
        signedAtLabel: formatSignDate(signature.signed_at),
        image: null,
      })),
]))

const saveGuaranteeRequirement = async () => {
  savingGuarantee.value = true
  try {
    const response = await api.put(`/contracts/${contract.value.id}/guarantee-requirement`, {
      requires_guarantee: requiresGuarantee.value,
      guarantee_type: guaranteeType.value,
      guarantee_instrument_type: guaranteeInstrumentType.value,
      requires_board_signature: requiresBoardSignature.value,
    })
    contract.value = response.data?.data || contract.value
    requiresGuarantee.value = !!contract.value.requires_guarantee
    guaranteeType.value = contract.value.guarantee_type || null
    guaranteeInstrumentType.value = contract.value.guarantee_instrument_type || null
    requiresBoardSignature.value = !!contract.value.requires_board_signature
    showToast({ severity: 'success', summary: 'ذخیره شد', detail: 'نیاز به ضمانت قرارداد به‌روزرسانی شد.', life: 3000 })
  } catch (error) {
    showToast({ severity: 'error', summary: 'خطا', detail: error.response?.data?.message || 'ذخیره نیاز به ضمانت انجام نشد.', life: 4000 })
    requiresGuarantee.value = !!contract.value?.requires_guarantee
    guaranteeType.value = contract.value?.guarantee_type || null
    guaranteeInstrumentType.value = contract.value?.guarantee_instrument_type || null
    requiresBoardSignature.value = !!contract.value?.requires_board_signature
  } finally {
    savingGuarantee.value = false
  }
}

const pendingSignature = computed(() => {
  const actionableSignature = signatureStore.pendingSignatures.find((signature) =>
      Number(signature.signable_id) === Number(contract.value?.id) &&
      signature.signable_type?.includes('Contract') &&
      signature.status === 'pending'
  )
  if (actionableSignature) return actionableSignature

  if (!contract.value?.signatures) return null
  return contract.value.signatures.find(s =>
          s.status === 'pending' && (
              s.signer_id === authStore.user?.id ||
              (contract.value.status === 'pending_contractor_sign' && s.group === 'contractor')
          )
  )
})

// ───────── کمکی‌های نمایش (طراحی جدید صفحه) ─────────
const toFa = (value) => Number(value || 0).toLocaleString('fa-IR')

const partyInitial = computed(() => (contract.value?.party?.name || '').trim().charAt(0) || '؟')

// روزهای باقی‌مانده تا پایان قرارداد؛ فقط وقتی تاریخ میلادی/ISO معتبر باشد نمایش داده می‌شود
const remainingInfo = computed(() => {
  const endValue = contract.value?.end_date
  if (!endValue) return null
  const end = new Date(endValue)
  if (Number.isNaN(end.getTime()) || end.getFullYear() < 1900) return null

  const days = Math.ceil((end.getTime() - Date.now()) / 86400000)
  if (days < 0) return { text: `${toFa(Math.abs(days))} روز از پایان گذشته`, tone: 'danger' }
  if (days === 0) return { text: 'امروز پایان می‌یابد', tone: 'warn' }
  return { text: `${toFa(days)} روز مانده`, tone: days <= 30 ? 'warn' : 'ok' }
})

const signatureTone = (signature) =>
    signature.status === 'signed' ? 'signed' : signature.status === 'rejected' ? 'rejected' : 'pending'
</script>

<template>
  <div class="contract-show-page">
    <div class="page-background" aria-hidden="true">
      <div class="bg-shape shape-one"></div>
      <div class="bg-shape shape-two"></div>
      <div class="bg-grid"></div>
    </div>

    <div class="page-container">
      <!-- در حال بارگذاری -->
      <div v-if="loading" class="skeleton-wrap" aria-busy="true" aria-live="polite">
        <div class="sk sk-hero"></div>
        <div class="sk-grid">
          <div v-for="n in 4" :key="n" class="sk sk-card"></div>
        </div>
        <div class="sk sk-body"></div>
      </div>

      <div v-else-if="contract" class="contract-loaded-content">
        <!-- هدر -->
        <header class="hero-card">
          <div class="hero-topline">
            <div class="hero-mark"><i class="pi pi-file-check"></i></div>
            <div class="hero-copy">
              <div class="eyebrow"><i class="pi pi-briefcase"></i> پرونده و جزئیات قرارداد</div>
              <div class="hero-title-row">
                <h1 class="contract-number">{{ contract.contract_number }}</h1>
                <ContractStatusBadge :status="statusInfo.value" />
                <ContractTypeBadge :type="contract.type" />
              </div>
              <p class="hero-subtitle">{{ contract.subject }}</p>
              <div v-if="contract.creator?.name || contract.currency || contract.created_at" class="hero-meta">
                <span v-if="contract.creator?.name"><i class="pi pi-user"></i> {{ contract.creator.name }}</span>
                <span v-if="formatSignDate(contract.created_at)"><i class="pi pi-calendar"></i> ثبت: {{ formatSignDate(contract.created_at) }}</span>
                <span v-if="contract.currency"><i class="pi pi-wallet"></i> {{ contract.currency }}</span>
              </div>
            </div>
            <div class="hero-actions">
              <Button
                  v-if="can('contracts.update') && ['draft', 'rejected_by_legal', 'pending_internal_approval'].includes(contract.status)"
                  label="ویرایش"
                  icon="pi pi-pencil"
                  severity="warn"
                  outlined
                  @click="router.push(`/contracts/${contract.id}/edit`)"
              />
              <Button
                  label="بازگشت"
                  icon="pi pi-arrow-right"
                  severity="secondary"
                  outlined
                  @click="router.push('/contracts')"
              />
            </div>
          </div>
        </header>

        <!-- اعلان ضمانت برای پیمانکار -->
        <section v-if="contractorGuaranteeActionNeeded" class="notice notice--warn" role="status">
          <div class="notice__icon"><i class="pi pi-info-circle" aria-hidden="true"></i></div>
          <div>
            <h2>لطفاً ضمانت قرارداد را به واحد مالی تحویل دهید</h2>
            <p>
              گردش امضاهای قرارداد ادامه دارد. اصل {{ contract.guarantee_instrument_type === 'check' ? 'چک ضمانت' : contract.guarantee_instrument_type === 'promissory_note' ? 'سفته' : 'ضمانت‌نامه بانکی' }} را حضوری به واحد مالی تحویل دهید.
              ابلاغ نهایی قرارداد پس از ثبت دریافت ضمانت توسط واحد مالی انجام می‌شود.
            </p>
          </div>
        </section>

        <!-- خلاصه -->
        <div class="kpi-grid">
          <article class="kpi">
            <div class="kpi__label"><span class="kpi__icon"><i class="pi pi-wallet"></i></span> مبلغ قرارداد</div>
            <AmountDisplay :amount="contract.current_amount" unit="smart" size="lg" />
            <div
                v-if="amountChange && amountChange.amount !== 0"
                class="kpi__chip"
                :class="amountChange.direction === 'increase' ? 'kpi__chip--up' : 'kpi__chip--down'"
            >
              <i class="pi" :class="amountChange.direction === 'increase' ? 'pi-arrow-up' : 'pi-arrow-down'"></i>
              {{ amountChange.direction === 'increase' ? '+' : '' }}{{ amountChange.formattedPercent }}
              <span class="kpi__chip-note">نسبت به مبلغ اولیه</span>
            </div>
          </article>

          <article class="kpi">
            <div class="kpi__label"><span class="kpi__icon"><i class="pi pi-building"></i></span> طرف قرارداد</div>
            <div class="kpi__party">
              <span class="kpi__avatar" aria-hidden="true">{{ partyInitial }}</span>
              <div class="kpi__party-text">
                <strong>{{ contract.party?.name || 'ثبت نشده' }}</strong>
                <small class="font-mono">{{ contract.party?.national_id || '—' }}</small>
              </div>
            </div>
          </article>

          <article class="kpi">
            <div class="kpi__label"><span class="kpi__icon"><i class="pi pi-calendar"></i></span> مدت قرارداد</div>
            <div class="kpi__dates">
              <DateDisplay :date="contract.start_date" />
              <i class="pi pi-arrow-left" aria-hidden="true"></i>
              <DateDisplay :date="contract.end_date" />
            </div>
            <ProgressBar :value="progress" :show-value="false" class="kpi__progress" />
            <div class="kpi__foot">
              <span>{{ progress }}٪ از زمان سپری شده</span>
              <span v-if="remainingInfo" class="kpi__chip" :class="`kpi__chip--${remainingInfo.tone}`">{{ remainingInfo.text }}</span>
            </div>
          </article>

          <article class="kpi">
            <div class="kpi__label"><span class="kpi__icon"><i class="pi pi-chart-bar"></i></span> آمار</div>
            <div class="kpi__counts">
              <div class="kpi__count">
                <strong>{{ toFa(contract.amendments?.length) }}</strong>
                <span>الحاقیه</span>
              </div>
              <div class="kpi__count">
                <strong>{{ toFa(contract.invoices?.length) }}</strong>
                <span>صورت‌وضعیت</span>
              </div>
              <div class="kpi__count">
                <strong>{{ toFa(contract.guarantees?.length) }}</strong>
                <span>ضمانت‌نامه</span>
              </div>
            </div>
          </article>
        </div>

        <!-- تعیین نیاز به ضمانت -->
        <section v-if="can('contracts.review') && contract.status === 'pending_contract_review'" class="action-panel">
          <div class="action-panel__text">
            <h2><i class="pi pi-shield"></i> نیاز به ضمانت</h2>
            <p>واحد قراردادها می‌تواند نیاز به ضمانت را برای این قرارداد تعیین کند.</p>
          </div>
          <div class="action-panel__controls">
            <span class="action-panel__state" :class="{ 'is-on': requiresGuarantee }">
              {{ requiresGuarantee ? 'ضمانت لازم است' : 'ضمانت لازم نیست' }}
            </span>
            <ToggleSwitch v-model="requiresGuarantee" :disabled="savingGuarantee" />
            <Select
              v-if="requiresGuarantee"
              v-model="guaranteeType"
              :options="[
                { label: 'حسن انجام کار', value: 'performance' },
                { label: 'پیش‌پرداخت', value: 'advance_payment' },
                { label: 'شرکت در مناقصه', value: 'bid' },
                { label: 'کسورات', value: 'retention' },
              ]"
              option-label="label"
              option-value="value"
              placeholder="نوع ضمانت"
              :disabled="savingGuarantee"
              class="w-56"
            />
            <Select
              v-if="requiresGuarantee"
              v-model="guaranteeInstrumentType"
              :options="[
                { label: 'چک ضمانت', value: 'check' },
                { label: 'سفته', value: 'promissory_note' },
                { label: 'ضمانت‌نامه بانکی', value: 'bank_guarantee' },
              ]"
              option-label="label"
              option-value="value"
              placeholder="نوع سند ضمانت"
              :disabled="savingGuarantee"
              class="w-56"
            />
            <Button label="ذخیره" icon="pi pi-save" :loading="savingGuarantee" @click="saveGuaranteeRequirement" />
          </div>
          <label class="flex items-center gap-3 border-t border-surface-200 pt-4">
            <ToggleSwitch v-model="requiresBoardSignature" :disabled="savingGuarantee" />
            <span>امضای مدیرعامل الزامی است؛ عضو هیئت‌مدیره {{ requiresBoardSignature ? 'نیز باید امضا کند' : 'نیازی به امضا ندارد' }}.</span>
          </label>
        </section>

        <LegalReviewPanel
            v-if="can('contracts.draft') && ['pending_legal_review', 'legal_draft'].includes(contract.status)"
            :contract="contract"
            @refresh="handleSignatureUpdated"
        />

        <ExecutiveSignPanel
            v-if="contract.status === 'pending_executive_sign' && authStore.hasAnyRole(['ceo', 'board_member'])"
            :contract="contract"
        />

        <!-- تب‌ها -->
        <TabView>
          <!-- اطلاعات قرارداد -->
          <TabPanel>
            <template #header>
              <span class="tab-head"><i class="pi pi-info-circle"></i><span>اطلاعات قرارداد</span></span>
            </template>
            <div class="info-grid">
              <div class="info-main">
                <section class="panel">
                  <header class="panel__head"><i class="pi pi-id-card"></i><h3>اطلاعات کلی</h3></header>
                  <dl class="info-list">
                    <div class="info-row">
                      <dt>شماره قرارداد</dt>
                      <dd class="font-mono">{{ contract.contract_number }}</dd>
                    </div>
                    <div class="info-row">
                      <dt>موضوع</dt>
                      <dd>{{ contract.subject }}</dd>
                    </div>
                    <div class="info-row">
                      <dt>نوع</dt>
                      <dd><ContractTypeBadge :type="contract.type" /></dd>
                    </div>
                    <div class="info-row">
                      <dt>وضعیت</dt>
                      <dd><ContractStatusBadge :status="contract.status" /></dd>
                    </div>
                    <div class="info-row">
                      <dt>ارز</dt>
                      <dd>{{ contract.currency }}</dd>
                    </div>
                  </dl>
                </section>

                <section v-if="contract.description" class="panel">
                  <header class="panel__head"><i class="pi pi-align-right"></i><h3>توضیحات</h3></header>
                  <p class="panel__text">{{ contract.description }}</p>
                </section>
              </div>

              <div class="info-side">
                <section class="panel">
                  <header class="panel__head"><i class="pi pi-money-bill"></i><h3>مبالغ</h3></header>
                  <dl class="info-list">
                    <div class="info-row">
                      <dt>مبلغ اولیه</dt>
                      <dd><AmountDisplay :amount="contract.initial_amount" /></dd>
                    </div>
                    <div class="info-row">
                      <dt>مبلغ فعلی</dt>
                      <dd><AmountDisplay :amount="contract.current_amount" /></dd>
                    </div>
                    <div v-if="amountChange && amountChange.amount !== 0" class="info-row">
                      <dt>تغییر مبلغ</dt>
                      <dd><AmountDisplay :amount="amountChange.amount" :colorize="true" /></dd>
                    </div>
                  </dl>
                </section>

                <section class="panel">
                  <header class="panel__head"><i class="pi pi-calendar"></i><h3>تاریخ‌ها</h3></header>
                  <dl class="info-list">
                    <div class="info-row">
                      <dt>تاریخ شروع</dt>
                      <dd><DateDisplay :date="contract.start_date" /></dd>
                    </div>
                    <div class="info-row">
                      <dt>تاریخ پایان</dt>
                      <dd><DateDisplay :date="contract.end_date" show-status /></dd>
                    </div>
                    <div class="info-row">
                      <dt>تاریخ ایجاد</dt>
                      <dd><DateDisplay :date="contract.created_at" show-time /></dd>
                    </div>
                    <div v-if="contract.approved_at" class="info-row">
                      <dt>تاریخ تایید</dt>
                      <dd><DateDisplay :date="contract.approved_at" show-time /></dd>
                    </div>
                  </dl>
                </section>

                <section class="panel">
                  <header class="panel__head"><i class="pi pi-users"></i><h3>افراد</h3></header>
                  <dl class="info-list">
                    <div class="info-row">
                      <dt>ایجاد کننده</dt>
                      <dd>{{ contract.creator?.name }}</dd>
                    </div>
                    <div v-if="contract.approver" class="info-row">
                      <dt>تایید کننده</dt>
                      <dd>{{ contract.approver.name }}</dd>
                    </div>
                  </dl>
                </section>
              </div>
            </div>
          </TabPanel>

          <!-- ابلاغیه نهایی -->
          <TabPanel v-if="contract.notification_letter">
            <template #header>
              <span class="tab-head"><i class="pi pi-send"></i><span>چاپ ابلاغیه</span></span>
            </template>
            <div class="tab-toolbar">
              <div>
                <h3>ابلاغ قرارداد به پیمانکار</h3>
                <p class="text-sm text-surface-500">این نسخه هنگام تأیید ابلاغ ثبت شده و همراه قرارداد نگهداری می‌شود.</p>
              </div>
              <Button label="چاپ ابلاغیه" icon="pi pi-print" @click="printNotificationLetter" />
            </div>
            <section class="panel notice-preview" dir="rtl">
              <div class="notice-preview__top">
                <span>شماره قرارداد: <bdi>{{ contract.notification_letter.contract_number }}</bdi></span>
                <span>تاریخ ابلاغ: {{ notificationDate(contract.notification_letter.notified_at) }}</span>
              </div>
              <h2>ابلاغ قرارداد</h2>
              <div class="notice-preview__body" v-html="notificationLetterBodyHtml" />
              <dl class="notice-preview__details">
                <div><dt>مبلغ قرارداد</dt><dd>{{ notificationAmount(contract.notification_letter.current_amount, contract.notification_letter.currency) }}</dd></div>
                <div><dt>تاریخ شروع</dt><dd>{{ notificationDate(contract.notification_letter.start_date) }}</dd></div>
                <div><dt>تاریخ پایان</dt><dd>{{ notificationDate(contract.notification_letter.end_date) }}</dd></div>
              </dl>
              <p>با احترام،<br>{{ contract.notification_letter.employer_name }}</p>
              <p class="notice-preview__foot">ابلاغ شده توسط {{ contract.notification_letter.generated_by }} ـ {{ notificationDate(contract.notification_letter.notified_at) }}</p>
            </section>
          </TabPanel>

          <!-- متن قرارداد -->
          <TabPanel v-if="contract?.has_content || contract?.content">
            <template #header>
              <span class="tab-head"><i class="pi pi-file-edit"></i><span>متن قرارداد</span></span>
            </template>
            <div class="doc-viewer">
              <div class="doc-toolbar">
                <div class="doc-toolbar__title">
                  <span class="doc-toolbar__icon"><i class="pi pi-file-edit"></i></span>
                  <div>
                    <h3>متن کامل قرارداد</h3>
                    <p>
                      نسخه {{ contract.content_version || '1.0' }}
                      <span v-if="contract.pages_count"> • {{ contract.pages_count }} صفحه</span>
                    </p>
                  </div>
                </div>
                <div class="doc-toolbar__actions">
                  <Button
                      label="چاپ"
                      icon="pi pi-print"
                      severity="secondary"
                      outlined
                      size="small"
                      @click="printContent"
                  />
                  <Button
                      label="دانلود PDF"
                      icon="pi pi-file-pdf"
                      severity="secondary"
                      outlined
                      size="small"
                      disabled
                      title="به‌زودی"
                  />
                </div>
              </div>

              <div v-if="contract.content" class="doc-stage">
                <article class="doc-paper contract-content" dir="rtl">
                  <div v-html="contract.content" class="ql-editor" />
                </article>
              </div>

              <EmptyState
                  v-else
                  title="متن قرارداد ثبت نشده"
                  description="برای این قرارداد متنی ثبت نشده است."
                  icon="pi pi-file-edit"
              />
            </div>
          </TabPanel>

          <!-- آیتم‌های قرارداد -->
          <TabPanel>
            <template #header>
              <span class="tab-head"><i class="pi pi-list"></i><span>آیتم‌های قرارداد</span></span>
            </template>
            <ContractItemsTable
                :model-value="contract.items || []"
                :readonly="true"
                title="فهرست بها / آیتم‌های قرارداد"
            />
          </TabPanel>

          <!-- الحاقیه‌ها -->
          <TabPanel v-if="can('amendments.read')">
            <template #header>
              <span class="tab-head">
                <i class="pi pi-file-edit"></i><span>الحاقیه‌ها</span>
                <span v-if="amendmentStore.amendments.length" class="tab-count">{{ toFa(amendmentStore.amendments.length) }}</span>
              </span>
            </template>
            <div class="tab-toolbar">
              <h3>لیست الحاقیه‌ها</h3>
              <Button
                  v-if="can('amendments.create') && contract.status === 'active'"
                  label="الحاقیه جدید"
                  icon="pi pi-plus"
                  size="small"
                  @click="router.push(`/contracts/${contract.id}/amendments/create`)"
              />
            </div>

            <EmptyState
                v-if="!amendmentStore.amendments.length"
                title="الحاقیه‌ای ثبت نشده"
                description="برای این قرارداد هنوز الحاقیه‌ای ایجاد نشده است."
                icon="pi pi-file-edit"
            />

            <DataTable
                v-else
                :value="amendmentStore.amendments"
                :loading="amendmentStore.loading"
                striped-rows
                responsive-layout="scroll"
            >
              <Column field="amendment_number" header="شماره الحاقیه" class="min-w-36">
                <template #body="{ data }">
                  <span class="font-mono text-sm">{{ data.amendment_number }}</span>
                </template>
              </Column>

              <Column field="type" header="نوع" class="min-w-32">
                <template #body="{ data }">
                  <Tag :value="data.type_label" severity="info" class="!text-xs" />
                </template>
              </Column>

              <Column field="description" header="توضیحات" class="min-w-64">
                <template #body="{ data }">
                  <div class="truncate max-w-xs">{{ data.description }}</div>
                </template>
              </Column>

              <Column field="status" header="وضعیت" class="min-w-32">
                <template #body="{ data }">
                  <Tag
                      :value="data.status_label"
                      :severity="getAmendmentStatusInfo(data.status).color"
                      class="!text-xs"
                  />
                </template>
              </Column>

              <Column field="created_at" header="تاریخ ایجاد" class="min-w-36">
                <template #body="{ data }">
                  <DateDisplay :date="data.created_at" />
                </template>
              </Column>

              <Column header="عملیات" class="min-w-24 text-center">
                <template #body="{ data }">
                  <Button
                      icon="pi pi-eye"
                      severity="info"
                      text
                      rounded
                      size="small"
                      @click="router.push(`/amendments/${data.id}`)"
                  />
                </template>
              </Column>
            </DataTable>
          </TabPanel>

          <!-- صورت‌وضعیت‌ها -->
          <TabPanel v-if="can('invoices.read')">
            <template #header>
              <span class="tab-head">
                <i class="pi pi-receipt"></i><span>صورت‌وضعیت‌ها</span>
                <span v-if="invoiceStore.invoices.length" class="tab-count">{{ toFa(invoiceStore.invoices.length) }}</span>
              </span>
            </template>
            <div class="tab-toolbar">
              <h3>لیست صورت‌وضعیت‌ها</h3>
              <Button
                  v-if="can('invoices.create')"
                  label="صورت‌وضعیت جدید"
                  icon="pi pi-plus"
                  size="small"
                  @click="router.push(`/contracts/${contract.id}/invoices/create`)"
              />
            </div>

            <EmptyState
                v-if="!invoiceStore.invoices.length"
                title="صورت‌وضعیتی ثبت نشده"
                description="برای این قرارداد هنوز صورت‌وضعیتی ایجاد نشده است."
                icon="pi pi-receipt"
            />

            <DataTable
                v-else
                :value="invoiceStore.invoices"
                :loading="invoiceStore.loading"
                striped-rows
                responsive-layout="scroll"
            >
              <Column field="invoice_number" header="شماره" class="min-w-32">
                <template #body="{ data }">
                  <span class="font-mono text-sm">{{ data.invoice_number }}</span>
                </template>
              </Column>

              <Column field="invoice_period" header="دوره" class="min-w-20">
                <template #body="{ data }">
                  <span class="font-medium">{{ data.invoice_period }}</span>
                </template>
              </Column>

              <Column field="from_date" header="از تاریخ" class="min-w-32">
                <template #body="{ data }">
                  <DateDisplay :date="data.from_date" />
                </template>
              </Column>

              <Column field="to_date" header="تا تاریخ" class="min-w-32">
                <template #body="{ data }">
                  <DateDisplay :date="data.to_date" />
                </template>
              </Column>

              <Column field="net_amount" header="مبلغ خالص" class="min-w-40 text-left">
                <template #body="{ data }">
                  <AmountDisplay :amount="data.net_amount" />
                </template>
              </Column>

              <Column field="status" header="وضعیت" class="min-w-32">
                <template #body="{ data }">
                  <Tag
                      :value="data.status_label"
                      :severity="getInvoiceStatusInfo(data.status).color"
                      class="!text-xs"
                  />
                </template>
              </Column>

              <Column header="عملیات" class="min-w-24 text-center">
                <template #body="{ data }">
                  <Button
                      icon="pi pi-eye"
                      severity="info"
                      text
                      rounded
                      size="small"
                      @click="router.push(`/invoices/${data.id}`)"
                  />
                </template>
              </Column>
            </DataTable>
          </TabPanel>

          <!-- ضمانت‌نامه‌ها -->
          <TabPanel v-if="can('guarantees.read')">
            <template #header>
              <span class="tab-head">
                <i class="pi pi-shield"></i><span>ضمانت‌نامه‌ها</span>
                <span v-if="guaranteeStore.guarantees.length" class="tab-count">{{ toFa(guaranteeStore.guarantees.length) }}</span>
              </span>
            </template>
            <div class="tab-toolbar">
              <h3>لیست ضمانت‌نامه‌ها</h3>
              <Button
                  v-if="can('guarantees.create')"
                  label="ضمانت‌نامه جدید"
                  icon="pi pi-plus"
                  size="small"
                  @click="router.push(`/contracts/${contract.id}/guarantees/create`)"
              />
            </div>

            <EmptyState
                v-if="!guaranteeStore.guarantees.length"
                title="ضمانت‌نامه‌ای ثبت نشده"
                description="برای این قرارداد هنوز ضمانت‌نامه‌ای ثبت نشده است."
                icon="pi pi-shield"
            />

            <DataTable
                v-else
                :value="guaranteeStore.guarantees"
                :loading="guaranteeStore.loading"
                striped-rows
                responsive-layout="scroll"
            >
              <Column field="guarantee_number" header="شماره" class="min-w-32">
                <template #body="{ data }">
                  <span class="font-mono text-sm">{{ data.guarantee_number }}</span>
                </template>
              </Column>

              <Column field="type" header="نوع" class="min-w-36">
                <template #body="{ data }">
                  <Tag :value="data.type_label" severity="info" class="!text-xs" />
                </template>
              </Column>

              <Column field="amount" header="مبلغ" class="min-w-40 text-left">
                <template #body="{ data }">
                  <AmountDisplay :amount="data.amount" />
                </template>
              </Column>

              <Column field="bank_name" header="بانک" class="min-w-32">
                <template #body="{ data }">
                  <span>{{ data.bank_name }}</span>
                </template>
              </Column>

              <Column field="expiry_date" header="تاریخ انقضا" class="min-w-36">
                <template #body="{ data }">
                  <DateDisplay :date="data.expiry_date" show-status />
                </template>
              </Column>

              <Column field="status" header="وضعیت" class="min-w-32">
                <template #body="{ data }">
                  <Tag
                      :value="data.status_label"
                      :severity="getGuaranteeStatusInfo(data.status).color"
                      class="!text-xs"
                  />
                </template>
              </Column>

              <Column header="عملیات" class="min-w-24 text-center">
                <template #body="{ data }">
                  <Button
                      icon="pi pi-eye"
                      severity="info"
                      text
                      rounded
                      size="small"
                      @click="router.push(`/guarantees/${data.id}`)"
                  />
                </template>
              </Column>
            </DataTable>
          </TabPanel>

          <!-- امضاها -->
          <TabPanel>
            <template #header>
              <span class="tab-head">
                <i class="pi pi-pencil"></i><span>امضاها</span>
                <span v-if="contract.signatures?.length" class="tab-count">{{ toFa(contract.signatures.length) }}</span>
              </span>
            </template>

            <SignaturePanel
                v-if="pendingSignature"
                :signature="pendingSignature"
                :contract-content="contract?.content || ''"
                :contract-pages-count="contract?.pages_count || 0"
                document-type="contract"
                @signed="handleSignatureUpdated"
                @rejected="handleSignatureUpdated"
            />

            <EmptyState
                v-if="!contract.signatures?.length && !pendingSignature"
                title="امضایی ثبت نشده"
                description="هنوز هیچ امضایی برای این قرارداد ثبت نشده است."
                icon="pi pi-pencil"
            />

            <div v-if="contract.signatures?.length" class="sig-list" :class="{ 'sig-list--spaced': pendingSignature }">
              <article
                  v-for="sig in contract.signatures"
                  :key="sig.id"
                  class="sig-card"
                  :class="`sig-card--${signatureTone(sig)}`"
              >
                <div class="sig-card__icon" aria-hidden="true">
                  <i class="pi" :class="sig.status === 'signed' ? 'pi-check' : sig.status === 'rejected' ? 'pi-times' : 'pi-clock'"></i>
                </div>

                <div class="sig-card__body">
                  <div class="sig-card__top">
                    <strong class="sig-card__name">{{ sig.signer?.name || 'نام امضاکننده ثبت نشده' }}</strong>
                    <Tag
                        :value="sig.role_label || (sig.signer_type === 'contractor' ? 'پیمانکار' : 'امضاکننده داخلی')"
                        :severity="sig.signer_type === 'contractor' ? 'info' : 'success'"
                        class="!text-xs"
                    />
                    <Tag
                        :value="sig.status_label"
                        :severity="sig.status === 'signed' ? 'success' : sig.status === 'rejected' ? 'danger' : 'warn'"
                        class="!text-xs"
                    />
                  </div>

                  <div v-if="sig.signed_at || sig.ip_address" class="sig-card__meta">
                    <span v-if="sig.signed_at">
                      <i class="pi pi-calendar"></i> تاریخ امضا:
                      <DateDisplay :date="sig.signed_at" show-time />
                    </span>
                    <span v-if="sig.ip_address">
                      <i class="pi pi-globe"></i> IP: <bdi>{{ sig.ip_address }}</bdi>
                    </span>
                  </div>

                  <p v-if="sig.comments" class="sig-card__comment">
                    <i class="pi pi-comment"></i>
                    {{ sig.comments }}
                  </p>
                </div>

                <div v-if="sig.status === 'signed' && sig.signature_image" class="sig-card__image">
                  <img :src="sig.signature_image" :alt="`امضای ${sig.signer?.name || ''}`" />
                </div>
              </article>
            </div>
          </TabPanel>

          <!-- تاریخچه -->
          <TabPanel v-if="!isContractor">
            <template #header>
              <span class="tab-head"><i class="pi pi-history"></i><span>تاریخچه</span></span>
            </template>
            <ContractTimeline :contract="contract" />
          </TabPanel>
        </TabView>

        <!-- نسخهٔ چاپی: مستقیم زیر body منتقل می‌شود تا از استایل/overflow چیدمان داشبورد تأثیر نگیرد -->
        <Teleport to="body">
          <article class="contract-print-root" dir="rtl" :style="printLayoutStyle">
            <!-- سربرگ: پس‌زمینهٔ ثابت که روی تک‌تک صفحات تکرار می‌شود -->
            <img
                v-if="letterheadUrl"
                :src="letterheadUrl"
                alt=""
                class="cp-letterhead"
                :class="{ 'cp-letterhead--banner': letterheadIsBanner }"
                @load="onLetterheadLoad"
            />

            <!-- امضاها: نوار ثابت پایین صفحه که روی تک‌تک صفحات تکرار می‌شود (هر امضا ۱۰۰×۱۰۰ پیکسل) -->
            <div v-if="printDocumentType === 'contract'" class="cp-signature-strip">
              <template v-if="printableSignatures.length">
                <figure v-for="signature in printableSignatures" :key="signature.id" class="cp-signature">
                  <figcaption class="cp-signature__role">{{ signature.role }}</figcaption>
                  <div class="cp-signature__box">
                    <img v-if="signature.image" :src="signature.image" :alt="`امضای ${signature.name}`" />
                  </div>
                  <strong class="cp-signature__name">{{ signature.name }}</strong>
                </figure>
              </template>
              <template v-else>
                <figure v-for="slot in 3" :key="slot" class="cp-signature">
                  <figcaption class="cp-signature__role">امضاکنندهٔ مجاز</figcaption>
                  <div class="cp-signature__box"></div>
                  <strong class="cp-signature__name">محل امضا</strong>
                </figure>
              </template>
            </div>

            <!-- thead/tfoot در هر صفحه تکرار می‌شوند و فضای سربرگ را خالی نگه می‌دارند -->
            <table v-if="printDocumentType === 'contract'" class="cp-flow">
              <thead><tr><td><div class="cp-spacer cp-spacer--top"></div></td></tr></thead>
              <tfoot><tr><td><div class="cp-spacer cp-spacer--bottom"></div></td></tr></tfoot>
              <tbody>
              <tr>
                <td>
                  <div class="cp-title">
                    <h1>{{ contract.subject || 'قرارداد' }}</h1>
                    <div v-if="contract.contract_number" class="cp-meta">
                      شمارهٔ قرارداد: <bdi>{{ contract.contract_number }}</bdi>
                    </div>
                  </div>

                  <section class="cp-content" v-html="contract.content || 'متن قرارداد ثبت نشده است.'" />
                </td>
              </tr>
              </tbody>
            </table>
            <table v-else class="cp-flow cp-notice-flow">
              <thead><tr><td><div class="cp-spacer cp-spacer--top"></div></td></tr></thead>
              <tfoot><tr><td><div class="cp-spacer cp-spacer--bottom"></div></td></tr></tfoot>
              <tbody><tr><td>
                <article class="cp-notice" dir="rtl">
                  <div class="cp-title">
                    <h1>ابلاغ قرارداد</h1>
                    <div class="cp-meta">شماره قرارداد: <bdi>{{ contract.notification_letter?.contract_number }}</bdi></div>
                  </div>
                  <div class="cp-notice__meta"><span>تاریخ: {{ notificationDate(contract.notification_letter?.notified_at) }}</span><span>پیوست: ندارد</span></div>
                  <div class="cp-notice__body" v-html="notificationLetterBodyHtml" />
                  <table class="cp-notice__details">
                    <tbody>
                    <tr><th>مبلغ قرارداد</th><td>{{ notificationAmount(contract.notification_letter?.current_amount, contract.notification_letter?.currency) }}</td></tr>
                    <tr><th>تاریخ شروع</th><td>{{ notificationDate(contract.notification_letter?.start_date) }}</td></tr>
                    <tr><th>تاریخ پایان</th><td>{{ notificationDate(contract.notification_letter?.end_date) }}</td></tr>
                    </tbody>
                  </table>
                  <p class="cp-notice__closing">با احترام،<br>{{ contract.notification_letter?.employer_name }}<br>واحد حقوقی و قراردادها</p>
                </article>
              </td></tr></tbody>
            </table>
          </article>
        </Teleport>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap');

/* ───────── رنگ‌ها (همان پالت قبلی) ───────── */
.contract-show-page {
  --accent: oklch(45% 0.15 250);
  --accent-dark: oklch(38% 0.17 250);
  --accent-soft: oklch(45% 0.15 250 / .10);
  --gold: oklch(55% 0.15 155);
  --gold-soft: oklch(55% 0.15 155 / .12);
  --bg: oklch(97.5% 0.008 250);
  --surface: oklch(99.5% 0.003 250 / .97);
  --surface-2: oklch(96% 0.008 250);
  --border: oklch(89% 0.008 250);
  --border-soft: oklch(92% 0.006 250);
  --text-main: oklch(15% 0.01 250);
  --text-muted: oklch(45% 0.012 250);
  --text-faint: oklch(62% 0.01 250);
  --danger: oklch(55% 0.2 25);
  --danger-soft: oklch(55% 0.2 25 / .10);
  --warn: oklch(68% 0.15 75);
  --warn-ink: oklch(38% 0.09 70);
  --warn-soft: oklch(80% 0.14 85 / .22);
  --shadow-sm: 0 4px 14px oklch(15% .01 250 / .04);
  --shadow-md: 0 10px 28px oklch(15% .01 250 / .07);

  min-height: 100dvh;
  position: relative;
  overflow-x: hidden;
  padding: 24px;
  direction: rtl;
  color: var(--text-main);
  background: var(--bg);
  font-family: 'Vazirmatn', Tahoma, sans-serif;
}

.notice-preview { max-width: 900px; margin: 18px auto; padding: 34px 42px; line-height: 2.1; }
.notice-preview h2 { margin: 12px 0 24px; text-align: center; color: #123b5d; }
.notice-preview__top { display: flex; justify-content: space-between; gap: 16px; color: #526579; border-bottom: 1px solid #cbd5e1; padding-bottom: 12px; }
.notice-preview__details { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin: 24px 0; }
.notice-preview__details div { padding: 12px; border: 1px solid #dbe3eb; border-radius: 10px; }
.notice-preview__details dt { color: #64748b; font-size: .85rem; }
.notice-preview__details dd { margin: 4px 0 0; font-weight: 700; }
.notice-preview__foot { color: #64748b; font-size: .86rem; }
.notice-preview__body { text-align: justify; }
.notice-preview__body :deep(p) { margin: 0 0 14px; }
.notice-preview__body :deep(ol), .notice-preview__body :deep(ul) { padding-right: 24px; }
.contract-show-page,
.contract-show-page * { box-sizing: border-box; }

/* ───────── پس‌زمینه ───────── */
.page-background { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
.bg-shape { position: absolute; border-radius: 50%; filter: blur(100px); }
.shape-one { width: 500px; height: 500px; top: -170px; right: -160px; background: oklch(45% .15 250 / .08); }
.shape-two { width: 440px; height: 440px; bottom: -160px; left: -140px; background: oklch(55% .15 155 / .07); }
.bg-grid {
  position: absolute; inset: 0;
  background-image:
      linear-gradient(oklch(58% .008 250 / .045) 1px, transparent 1px),
      linear-gradient(90deg, oklch(58% .008 250 / .045) 1px, transparent 1px);
  background-size: 50px 50px;
  mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 30%, transparent 80%);
}

.page-container { position: relative; z-index: 1; width: min(1280px, 100%); margin: 0 auto; }
.contract-loaded-content { display: flex; flex-direction: column; gap: 22px; }

/* ───────── اسکلت بارگذاری ───────── */
.skeleton-wrap { display: flex; flex-direction: column; gap: 22px; }
.sk {
  border-radius: 18px;
  background: linear-gradient(90deg, var(--surface-2) 25%, oklch(99% .004 250) 50%, var(--surface-2) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s ease-in-out infinite;
}
.sk-hero { height: 150px; }
.sk-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
.sk-card { height: 128px; }
.sk-body { height: 360px; }
@keyframes shimmer { from { background-position: 200% 0; } to { background-position: -200% 0; } }

/* ───────── هدر ───────── */
.hero-card {
  position: relative; overflow: hidden; padding: 28px 30px; border-radius: 22px; color: #fff;
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  box-shadow: 0 14px 34px oklch(45% .15 250 / .24), 0 0 0 1px #ffffff18;
}
.hero-card::before, .hero-card::after {
  content: ''; position: absolute; border-radius: 50%; pointer-events: none; filter: blur(40px);
  background: radial-gradient(circle, var(--gold) 0%, transparent 70%);
}
.hero-card::before { top: -150px; right: -60px; width: 300px; height: 300px; opacity: .16; }
.hero-card::after { bottom: -160px; left: -60px; width: 250px; height: 250px; opacity: .1; }
.hero-topline { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 22px; }
.hero-mark {
  width: 64px; height: 64px; flex: 0 0 64px; display: grid; place-items: center; border-radius: 18px;
  background: #ffffff24; border: 1px solid #ffffff35; color: #fff; font-size: 27px; backdrop-filter: blur(10px);
}
.hero-copy { flex: 1; min-width: 0; overflow-wrap: anywhere; }
.eyebrow {
  display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 20px;
  background: #ffffff20; border: 1px solid #ffffff30; color: #fff; font-size: 11.5px; font-weight: 600;
}
.hero-title-row { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 12px; margin-top: 10px; }
.contract-number { margin: 0; color: #fff; font-size: clamp(21px, 2.2vw, 29px); font-weight: 800; line-height: 1.5; }
.hero-subtitle { margin: 4px 0 0; color: #ffffffd9; font-size: 13.5px; line-height: 1.9; }
.hero-meta { display: flex; flex-wrap: wrap; gap: 8px 18px; margin-top: 12px; color: #ffffffcc; font-size: 12px; }
.hero-meta span { display: inline-flex; align-items: center; gap: 6px; }
.hero-meta .pi { font-size: 12px; opacity: .85; }
.hero-actions { position: relative; z-index: 2; display: flex; flex-wrap: wrap; align-items: center; gap: 9px; flex-shrink: 0; }
.hero-actions :deep(.p-button) { border-radius: 11px; font-family: inherit; font-weight: 600; }
.hero-actions :deep(.p-button-outlined) { color: #fff; border-color: #ffffff60; background: #ffffff12; }
.hero-actions :deep(.p-button-outlined:hover) { background: #ffffff25; }
.hero-actions :deep(.p-button-icon) { color: inherit; }

/* ───────── اعلان‌ها و پنل اقدام ───────── */
.notice {
  display: flex; align-items: flex-start; gap: 14px; padding: 16px 18px;
  border: 1px solid var(--border); border-right-width: 4px; border-radius: 16px; background: var(--surface);
  box-shadow: var(--shadow-sm);
}
.notice h2 { margin: 0; font-size: 14px; font-weight: 800; }
.notice p { margin: 4px 0 0; font-size: 13px; line-height: 2; color: var(--text-muted); }
.notice__icon { display: grid; place-items: center; width: 38px; height: 38px; flex: 0 0 38px; border-radius: 12px; font-size: 18px; }
.notice--warn { border-color: oklch(85% .09 85); border-right-color: var(--warn); background: oklch(98% .02 90); }
.notice--warn h2 { color: var(--warn-ink); }
.notice--warn .notice__icon { background: var(--warn-soft); color: var(--warn-ink); }

.action-panel {
  display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;
  padding: 18px 20px; border: 1px solid var(--border); border-right: 4px solid var(--accent);
  border-radius: 16px; background: var(--surface); box-shadow: var(--shadow-sm);
}
.action-panel h2 { display: flex; align-items: center; gap: 8px; margin: 0; font-size: 14px; font-weight: 800; }
.action-panel h2 .pi { color: var(--accent); }
.action-panel p { margin: 4px 0 0; font-size: 12.5px; color: var(--text-muted); }
.action-panel__controls { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; }
.action-panel__state { padding: 4px 11px; border-radius: 99px; background: var(--surface-2); color: var(--text-muted); font-size: 12px; font-weight: 700; }
.action-panel__state.is-on { background: var(--gold-soft); color: oklch(40% .12 155); }

/* ───────── کارت‌های خلاصه ───────── */
.kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; align-items: stretch; }
.kpi {
  position: relative; isolation: isolate; min-width: 0; min-height: 138px; overflow: hidden;
  display: flex; flex-direction: column; justify-content: center; gap: 10px; padding: 22px 22px 20px;
  border: 1px solid var(--border-soft); border-radius: 18px; background: var(--surface);
  box-shadow: var(--shadow-sm); transition: transform .2s ease, box-shadow .2s ease;
}
.kpi::before {
  content: ''; position: absolute; inset: 0 0 auto; height: 3px; z-index: 1; opacity: .85;
  background: linear-gradient(90deg, var(--accent), var(--gold));
}
.kpi:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.kpi__label { display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 12px; font-weight: 700; }
.kpi__icon { display: grid; place-items: center; width: 26px; height: 26px; border-radius: 8px; background: var(--accent-soft); color: var(--accent); font-size: 12px; }
.kpi__chip {
  display: inline-flex; align-items: center; align-self: flex-start; gap: 5px; padding: 3px 10px; border-radius: 99px;
  background: var(--surface-2); color: var(--text-muted); font-size: 11.5px; font-weight: 700;
}
.kpi__chip .pi { font-size: 10px; }
.kpi__chip-note { font-weight: 500; opacity: .85; }
.kpi__chip--up, .kpi__chip--ok { background: var(--gold-soft); color: oklch(40% .12 155); }
.kpi__chip--down, .kpi__chip--danger { background: var(--danger-soft); color: var(--danger); }
.kpi__chip--warn { background: var(--warn-soft); color: var(--warn-ink); }

.kpi__party { display: flex; align-items: center; gap: 12px; min-width: 0; }
.kpi__avatar {
  display: grid; place-items: center; width: 44px; height: 44px; flex: 0 0 44px; border-radius: 14px;
  background: linear-gradient(135deg, var(--accent), var(--accent-dark)); color: #fff; font-size: 18px; font-weight: 800;
}
.kpi__party-text { display: flex; flex-direction: column; min-width: 0; }
.kpi__party-text strong { font-size: 14px; font-weight: 800; line-height: 1.7; overflow-wrap: anywhere; }
.kpi__party-text small { color: var(--text-faint); font-size: 12px; }

.kpi__dates { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; font-size: 13px; font-weight: 600; }
.kpi__dates .pi { color: var(--text-faint); font-size: 11px; }
.kpi__progress { height: 8px; }
.kpi__foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 6px; color: var(--text-muted); font-size: 11.5px; }

.kpi__counts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.kpi__count {
  display: flex; flex-direction: column; align-items: center; gap: 2px; padding: 9px 4px;
  border-radius: 12px; background: var(--surface-2); text-align: center;
}
.kpi__count strong { color: var(--accent); font-size: 20px; font-weight: 800; line-height: 1.3; }
.kpi__count span { color: var(--text-muted); font-size: 11px; font-weight: 600; }

/* ───────── تب‌ها ───────── */
.contract-show-page :deep(.p-tabview) { padding: 0; border: 0; background: transparent; box-shadow: none; }
.contract-show-page :deep(.p-tabview-nav-container) { margin-bottom: 20px; border: 0; }
.contract-show-page :deep(.p-tabview-nav-content) { overflow-x: auto; scrollbar-width: none; }
.contract-show-page :deep(.p-tabview-nav-content::-webkit-scrollbar) { display: none; }
.contract-show-page :deep(.p-tabview-nav) {
  display: inline-flex; flex-wrap: wrap; gap: 4px; padding: 5px;
  border: 1px solid var(--border); border-radius: 15px; background: var(--surface-2);
}
.contract-show-page :deep(.p-tabview-nav li .p-tabview-nav-link) {
  padding: 10px 14px; border: 0; border-radius: 11px; background: transparent; color: var(--text-muted);
  font-family: inherit; font-size: 13px; font-weight: 600; transition: background .2s ease, color .2s ease, box-shadow .2s ease;
}
.contract-show-page :deep(.p-tabview-nav li:not(.p-highlight):not([data-p-active="true"]) .p-tabview-nav-link:hover) { background: var(--accent-soft); color: var(--accent); }
.contract-show-page :deep(.p-tabview-nav li.p-highlight .p-tabview-nav-link),
.contract-show-page :deep(.p-tabview-nav li[data-p-active="true"] .p-tabview-nav-link) {
  background: #fff; color: var(--accent); box-shadow: 0 3px 10px oklch(45% .15 250 / .14);
}
.contract-show-page :deep(.p-tabview-ink-bar) { display: none; }
.contract-show-page :deep(.p-tabview-panels) { padding: 0; background: transparent; }

.tab-head { display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }
.tab-head .pi { font-size: 13px; }
.tab-count {
  display: inline-grid; place-items: center; min-width: 20px; height: 20px; padding: 0 6px; border-radius: 99px;
  background: var(--accent-soft); color: var(--accent); font-size: 11px; font-weight: 800;
}

/* ───────── پنل‌ها و اطلاعات ───────── */
.info-grid { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr); gap: 20px; align-items: start; }
.info-main, .info-side { display: flex; flex-direction: column; gap: 20px; min-width: 0; }
.panel {
  padding: 4px 22px 14px; border: 1px solid var(--border-soft); border-radius: 18px;
  background: var(--surface); box-shadow: var(--shadow-sm);
}
.panel__head { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; padding: 16px 0 12px; border-bottom: 1px solid var(--border-soft); }
.panel__head .pi { display: grid; place-items: center; width: 28px; height: 28px; border-radius: 9px; background: var(--accent-soft); color: var(--accent); font-size: 13px; }
.panel__head h3 { margin: 0; font-size: 14px; font-weight: 800; }
.panel__text { margin: 12px 0 4px; font-size: 13.5px; line-height: 2.1; color: oklch(30% .01 250); }
.info-list { margin: 0; }
.info-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 12px 0; border-bottom: 1px dashed var(--border); font-size: 13px; }
.info-row:last-child { border-bottom: 0; }
.info-row dt { flex: 0 0 auto; color: var(--text-muted); font-weight: 500; }
.info-row dd { margin: 0; min-width: 0; font-weight: 700; text-align: left; overflow-wrap: anywhere; }

/* ───────── نوار ابزار هر تب ───────── */
.tab-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
.tab-toolbar h3 { margin: 0; font-size: 15px; font-weight: 800; }

/* ───────── جدول‌ها ───────── */
.contract-show-page :deep(.p-datatable) { border: 1px solid var(--border-soft); border-radius: 16px; overflow: hidden; background: var(--surface); box-shadow: var(--shadow-sm); }
.contract-show-page :deep(.p-datatable-thead > tr > th) { padding: 14px; background: var(--surface-2); color: var(--text-muted); font-size: 12px; font-weight: 700; white-space: nowrap; }
.contract-show-page :deep(.p-datatable-tbody > tr > td) { padding: 13px 14px; border-color: var(--border-soft); font-size: 12.5px; }
.contract-show-page :deep(.p-datatable-tbody > tr:hover) { background: oklch(97% .01 250 / .8); }
.contract-show-page :deep(.p-button),
.contract-show-page :deep(.p-tag) { font-family: inherit; }
.contract-show-page :deep(.p-tag) { font-weight: 600; }
.contract-show-page :deep(.p-progressbar) { border-radius: 99px; background: oklch(91% .008 250); overflow: hidden; }
.contract-show-page :deep(.p-progressbar-value) { border-radius: 99px; background: linear-gradient(90deg, var(--accent), var(--gold)); }

/* ───────── نمایشگر متن قرارداد ───────── */
.doc-viewer { display: flex; flex-direction: column; gap: 16px; }
.doc-toolbar {
  display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px;
  padding: 14px 18px; border: 1px solid var(--border-soft); border-radius: 16px; background: var(--surface); box-shadow: var(--shadow-sm);
}
.doc-toolbar__title { display: flex; align-items: center; gap: 12px; }
.doc-toolbar__icon { display: grid; place-items: center; width: 42px; height: 42px; border-radius: 13px; background: var(--accent-soft); color: var(--accent); font-size: 18px; }
.doc-toolbar h3 { margin: 0; font-size: 14.5px; font-weight: 800; }
.doc-toolbar p { margin: 2px 0 0; color: var(--text-muted); font-size: 12px; }
.doc-toolbar__actions { display: flex; flex-wrap: wrap; gap: 8px; }

.doc-stage {
  display: flex; justify-content: center; padding: 30px 20px;
  border: 1px solid var(--border-soft); border-radius: 18px;
  background: linear-gradient(180deg, oklch(94.5% .01 250), oklch(96% .008 250));
}
.doc-paper {
  width: min(840px, 100%); min-height: 520px; padding: 52px 60px; border-radius: 6px; background: #fff;
  box-shadow: 0 1px 2px oklch(15% .01 250 / .08), 0 14px 38px oklch(15% .01 250 / .12);
  color: #1e293b; font-size: 14.5px; line-height: 2.15;
}
.doc-paper :deep(.ql-editor) { height: auto; padding: 0; overflow: visible; line-height: inherit; }
.doc-paper :deep(h1), .doc-paper :deep(h2), .doc-paper :deep(h3) { margin: 1.5rem 0 .75rem; color: #0f172a; font-weight: 800; }
.doc-paper :deep(p) { margin-bottom: 1rem; text-align: justify; }
.doc-paper :deep(ul), .doc-paper :deep(ol) { margin-bottom: 1rem; padding-right: 2rem; }
.doc-paper :deep(li) { margin-bottom: .5rem; }
.doc-paper :deep(table) { width: 100%; margin: 1rem 0; border-collapse: collapse; }
.doc-paper :deep(th), .doc-paper :deep(td) { padding: .65rem; border: 1px solid #e2e8f0; }
.doc-paper :deep(th) { background: #f8fafc; font-weight: 700; }
.doc-paper :deep(img) { max-width: 100%; height: auto; }
.doc-paper :deep(blockquote) { margin: 1rem 0; padding: .5rem 1rem; border-right: 4px solid var(--accent); background: #f8fafc; color: #475569; }
.doc-paper :deep(pre) { direction: ltr; text-align: left; padding: 1rem; border-radius: 10px; background: #0f172a; color: #e2e8f0; overflow: auto; }

/* ───────── امضاها ───────── */
.sig-list { display: flex; flex-direction: column; gap: 12px; }
.sig-list--spaced { margin-top: 20px; }
.sig-card {
  --tone: var(--warn); --tone-soft: var(--warn-soft);
  display: flex; align-items: flex-start; gap: 14px; padding: 16px 18px;
  border: 1px solid var(--border-soft); border-right: 4px solid var(--tone); border-radius: 16px;
  background: var(--surface); box-shadow: var(--shadow-sm);
}
.sig-card--signed { --tone: var(--gold); --tone-soft: var(--gold-soft); }
.sig-card--rejected { --tone: var(--danger); --tone-soft: var(--danger-soft); }
.sig-card__icon { display: grid; place-items: center; width: 38px; height: 38px; flex: 0 0 38px; border-radius: 12px; background: var(--tone-soft); color: var(--tone); font-size: 15px; }
.sig-card__body { flex: 1; min-width: 0; }
.sig-card__top { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 10px; }
.sig-card__name { font-size: 14px; font-weight: 800; }
.sig-card__meta { display: flex; flex-wrap: wrap; gap: 6px 22px; margin-top: 10px; color: var(--text-muted); font-size: 12.5px; }
.sig-card__meta > span { display: inline-flex; align-items: center; gap: 6px; }
.sig-card__meta .pi { color: var(--text-faint); font-size: 12px; }
.sig-card__comment { margin: 12px 0 0; padding: 10px 14px; border-radius: 12px; background: var(--surface-2); color: oklch(30% .01 250); font-size: 13px; line-height: 1.9; }
.sig-card__comment .pi { margin-left: 6px; color: var(--text-faint); }
.sig-card__image {
  display: grid; place-items: center; width: 120px; height: 72px; flex: 0 0 120px; padding: 6px;
  border: 1px dashed var(--border); border-radius: 12px; background: #fff;
}
.sig-card__image img { max-width: 100%; max-height: 100%; object-fit: contain; }

/* ───────── واکنش‌گرا ───────── */
@media (max-width: 1100px) {
  .kpi-grid, .sk-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .info-grid { grid-template-columns: minmax(0, 1fr); }
  .hero-topline { align-items: flex-start; }
  .hero-actions { justify-content: flex-end; }
}
@media (max-width: 700px) {
  .contract-show-page { padding: 12px; }
  .contract-loaded-content, .skeleton-wrap { gap: 15px; }
  .hero-card { padding: 20px 17px; border-radius: 18px; }
  .hero-topline { flex-wrap: wrap; gap: 12px; }
  .hero-mark { width: 48px; height: 48px; flex-basis: 48px; font-size: 21px; border-radius: 14px; }
  .hero-copy { flex-basis: calc(100% - 62px); }
  .hero-actions { width: 100%; justify-content: flex-start; }
  .kpi-grid, .sk-grid { grid-template-columns: minmax(0, 1fr); gap: 12px; }
  .kpi { min-height: 0; }
  .contract-show-page :deep(.p-tabview-nav) { flex-wrap: nowrap; }
  .contract-show-page :deep(.p-tabview-nav li .p-tabview-nav-link) { padding: 10px 12px; white-space: nowrap; }
  .panel { padding: 2px 16px 10px; }
  .doc-stage { padding: 12px 8px; }
  .doc-paper { padding: 26px 18px; font-size: 14px; }
  .sig-card { flex-wrap: wrap; }
  .sig-card__image { width: 100%; flex-basis: 100%; }
}
@media (prefers-reduced-motion: reduce) {
  .sk { animation: none; }
  .kpi, .kpi:hover { transition: none; transform: none; }
}
</style>

<!-- استایل چاپ: عمداً scoped نیست چون المان چاپی به body منتقل (Teleport) می‌شود -->
<style>
.contract-print-root { display: none; }

@media print {
  @page { size: A4 portrait; margin: 0; } /* حاشیه صفر = سربرگ تمام‌صفحه + حذف تاریخ/آدرس پیش‌فرض مرورگر */

  html,
  body {
    height: auto !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: visible !important;
    background: #fff !important;
  }

  /* هر چیز دیگر در صفحه (منو، هدر، اپ اصلی، toast و ...) چاپ نشود.
     برچسب data-print-hidden را جاوااسکریپت موقع beforeprint می‌گذارد؛ دو قانون جدا چون
     اگر مرورگر :has را نشناسد، قانون ترکیبی کلاً نادیده گرفته می‌شد. */
  [data-print-hidden] { display: none !important; }
  body.contract-printing > *:not(.contract-print-root):not(script):not(style):not(link) { display: none !important; }

  .contract-print-root {
    display: block;
    position: relative;
    width: 100%;
    color: #172033;
    font: 11pt/2 'Vazirmatn', Tahoma, sans-serif;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  /* ── سربرگ ── */
  .cp-letterhead {
    position: fixed;
    top: 0;
    right: 0;
    z-index: 0;
    width: 210mm;
    height: 297mm;
    object-fit: fill;
    pointer-events: none;
  }
  .cp-letterhead--banner { height: auto; }

  /* ── جریان متن (جدول برای تکرار فضای خالی بالا/پایین هر صفحه) ── */
  .cp-flow {
    position: relative;
    z-index: 1;
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
  }
  .cp-flow > thead { display: table-header-group; }
  .cp-flow > tfoot { display: table-footer-group; }
  .cp-flow > thead > tr > td,
  .cp-flow > tfoot > tr > td { padding: 0; border: 0; }
  .cp-flow > tbody > tr > td { padding: 0 var(--cp-side, 20mm); border: 0; vertical-align: top; }
  .cp-spacer--top { height: var(--cp-top, 45mm); }
  .cp-spacer--bottom { height: var(--cp-bottom, 35mm); }

  /* ── عنوان ── */
  .cp-title { margin: 0 0 6mm; padding-bottom: 3mm; border-bottom: 1px solid #c5d0dc; text-align: center; }
  .cp-title h1 { margin: 0; color: #123b5d; font-size: 15pt; font-weight: 800; line-height: 1.7; }
  .cp-meta { margin-top: 1mm; color: #5a6b7c; font-size: 9pt; line-height: 1.6; }
  .cp-meta bdi { direction: ltr; font-weight: 700; color: #123b5d; }

  .cp-notice { color: #172033; font-size: 11pt; line-height: 2.15; }
  .cp-notice > p { margin: 0 0 5mm; text-align: justify; }
  .cp-notice__body p { margin: 0 0 5mm; }
  .cp-notice__body ol, .cp-notice__body ul { padding-right: 8mm; }
  .cp-notice__meta { display: flex; justify-content: space-between; margin: 0 0 12mm; color: #475569; font-size: 9pt; }
  .cp-notice__details { width: 100%; margin: 8mm 0; border-collapse: collapse; }
  .cp-notice__details th, .cp-notice__details td { padding: 3mm; border: 1px solid #b8c4d0; text-align: right; }
  .cp-notice__details th { width: 34%; background: #edf3f7 !important; }
  .cp-notice__closing { margin-top: 16mm !important; font-weight: 700; text-align: left !important; }

  /* ── متن قرارداد (خروجی ویرایشگر) ── */
  .cp-content { color: #172033; overflow-wrap: anywhere; text-align: justify; }
  .cp-content p { margin: 0 0 3mm; widows: 3; orphans: 3; }
  .cp-content h1, .cp-content h2, .cp-content h3, .cp-content h4 {
    margin: 5mm 0 2mm; color: #123b5d; font-weight: 800; line-height: 1.7;
    break-after: avoid; page-break-after: avoid;
  }
  .cp-content h1 { font-size: 14pt; } .cp-content h2 { font-size: 12.5pt; } .cp-content h3 { font-size: 11.5pt; }
  .cp-content ul, .cp-content ol { margin: 0 0 3mm; padding-right: 8mm; padding-left: 0; }
  .cp-content li { margin-bottom: 1mm; }
  .cp-content table { width: 100%; margin: 3mm 0; border-collapse: collapse; break-inside: auto; }
  .cp-content tr, .cp-content img { break-inside: avoid; page-break-inside: avoid; }
  .cp-content th, .cp-content td { padding: 1.5mm 2mm; border: 1px solid #b8c4d0; }
  .cp-content th { background: #edf3f7 !important; font-weight: 700; }
  .cp-content img { max-width: 100%; height: auto; }
  .cp-content blockquote { margin: 3mm 0; padding: 1mm 4mm; border-right: 1mm solid #9fb3c8; color: #475569; }
  /* کلاس‌های Quill */
  .cp-content .ql-align-center { text-align: center; }
  .cp-content .ql-align-right { text-align: right; }
  .cp-content .ql-align-left { text-align: left; }
  .cp-content .ql-align-justify { text-align: justify; }
  .cp-content .ql-direction-rtl { direction: rtl; }
  .cp-content .ql-indent-1 { padding-right: 8mm; } .cp-content .ql-indent-2 { padding-right: 16mm; } .cp-content .ql-indent-3 { padding-right: 24mm; }
  .cp-content .ql-size-small { font-size: .85em; } .cp-content .ql-size-large { font-size: 1.25em; } .cp-content .ql-size-huge { font-size: 1.6em; }

  /* ── امضاها: نوار ثابت پایین هر صفحه ── */
  .cp-signature-strip {
    position: fixed;
    z-index: 2;
    right: var(--cp-side, 20mm);
    left: var(--cp-side, 20mm);
    bottom: var(--cp-sig-bottom, 10mm);
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: center;
    gap: var(--cp-sig-gap, 6px);
  }
  .cp-signature {
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
    flex: 0 0 auto;
    width: var(--cp-sig-size, 100px);
    height: var(--cp-sig-size, 100px);
    margin: 0;
    padding: 3px 4px;
    overflow: hidden;
    border: 1px solid #b8c4d0;
    border-radius: 4px;
    background: rgba(255, 255, 255, .88);
    text-align: center;
  }
  .cp-signature__role,
  .cp-signature__name {
    flex: 0 0 auto;
    overflow: hidden;
    font-size: 7pt;
    line-height: 1.5;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .cp-signature__role { color: #53708c; font-weight: 700; }
  .cp-signature__name { color: #123b5d; font-weight: 800; }
  .cp-signature__box {
    display: flex;
    flex: 1 1 auto;
    align-items: center;
    justify-content: center;
    min-height: 0;
    margin: 1px 0;
    border-bottom: 1px dotted #9aa8b5;
  }
  .cp-signature__box img { max-width: 100%; max-height: 100%; object-fit: contain; }
}
</style>
