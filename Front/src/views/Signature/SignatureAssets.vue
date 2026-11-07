<!-- src/views/Signature/SignatureAssets.vue -->
<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import api from '@/api/axios.js'
import { useToast } from 'primevue/usetoast'

const toast = useToast()

/* =====================================================================
 * فهرست پرسنل
 * ===================================================================== */
const users = ref([])
const search = ref('')
const loading = ref(false)
const loadingExistingId = ref(null)
const savingUserId = ref(null)
const meta = ref({ current_page: 1, last_page: 1, total: 0 })

const roleNames = (user) => user.roles?.join('، ') || 'بدون نقش'

const loadUsers = async (page = 1) => {
  loading.value = true
  try {
    const { data } = await api.get('/signature-assets/users', {
      params: { search: search.value.trim(), page, per_page: 25 }
    })
    users.value = data.data || []
    meta.value = data.meta || meta.value
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'خطا',
      detail: error.response?.data?.message || 'دریافت فهرست کاربران انجام نشد.',
      life: 4000
    })
  } finally {
    loading.value = false
  }
}

let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => loadUsers(1), 400)
})

const clearSearch = () => {
  clearTimeout(searchTimer)
  search.value = ''
  loadUsers(1)
}

const selectFile = (userId, event) => {
  const file = event.target.files?.[0] || null
  event.target.value = ''
  if (!file) return

  if (!['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) {
    toast.add({ severity: 'warn', summary: 'فرمت نامعتبر', detail: 'فقط فایل PNG، JPG یا WebP قابل استفاده است.', life: 3500 })
    return
  }
  if (file.size > 5 * 1024 * 1024) {
    toast.add({ severity: 'warn', summary: 'حجم زیاد', detail: 'حجم تصویر باید کمتر از ۵ مگابایت باشد.', life: 3500 })
    return
  }
  openEditor(users.value.find((u) => u.id === userId), file)
}

// ویرایش امضای ثبت‌شده (در صورتی که API آدرس تصویر را برگرداند)
const editExisting = async (user) => {
  if (!user?.signature_image_url) return
  loadingExistingId.value = user.id
  try {
    const { data } = await api.get(user.signature_image_url, { responseType: 'blob' })
    openEditor(user, new File([data], 'signature.png', { type: data.type || 'image/png' }))
  } catch {
    toast.add({ severity: 'error', summary: 'خطا', detail: 'دریافت امضای فعلی انجام نشد.', life: 4000 })
  } finally {
    loadingExistingId.value = null
  }
}

/* =====================================================================
 * ویرایشگر امضا
 *
 * مراحل پردازش (هر مرحله فقط وقتی دوباره اجرا می‌شود که ورودی‌اش عوض شده):
 *  base    : چرخش، زمینه سفید، معکوس‌سازی
 *  bg      : تخمین رنگ کاغذ در هر ناحیه (برای حذف سایه و نور ناهمگون)
 *  core    : محاسبه «میزان جوهر» هر پیکسل، آلفا، رنگ جوهر، حذف لکه‌ها
 *  compose : شفافیت کلی، رنگ جوهر، پاک‌کن دستی، کادر برش
 * ===================================================================== */
const MAX_WORKING_SIZE = 1600

const defaultSettings = () => ({
  removeBackground: true,
  lightingCorrection: true,
  sensitivity: 40, // 0..100 — هرچه بیشتر، خطوط کم‌رنگ‌تر هم حذف می‌شوند
  softness: 40, // 0..100 — نرمی لبه‌ها
  specks: 20, // 0..100 — حذف لکه‌های ریز
  boldness: 0, // -100..100 — پررنگی خطوط
  opacity: 100,
  inkMode: 'original', // original | black | blue | custom
  customColor: '#1e3a8a',
  invert: false,
  rotation: 0,
  autoCrop: true,
  cropPadding: 3 // درصد از بعد بزرگ‌تر تصویر
})

const settings = reactive(defaultSettings())

const presets = [
  { key: 'scan', label: 'اسکن روی کاغذ سفید', icon: 'pi pi-file', values: { lightingCorrection: false, sensitivity: 35, softness: 35, specks: 15 } },
  { key: 'photo', label: 'عکس موبایل (سایه یا نور ناهمگون)', icon: 'pi pi-camera', values: { lightingCorrection: true, sensitivity: 45, softness: 40, specks: 30 } },
  { key: 'faint', label: 'خودکار کم‌رنگ', icon: 'pi pi-pencil', values: { lightingCorrection: true, sensitivity: 12, softness: 30, specks: 10, boldness: 40 } },
  { key: 'bold', label: 'ماژیک یا خط ضخیم', icon: 'pi pi-pen-to-square', values: { lightingCorrection: true, sensitivity: 50, softness: 45, specks: 40, boldness: -10 } }
]

const applyPreset = (preset) => {
  Object.assign(settings, { removeBackground: true, boldness: 0, ...preset.values })
}

// وضعیت نمایشی
const editorVisible = ref(false)
const editorUser = ref(null)
const sourceFile = ref(null)
const imageLoading = ref(false)
const hasImage = ref(false)
const hasInk = ref(true)
const activeTab = ref('bg')
const previewBg = ref('checker')
const showOriginal = ref(false)
const cropBox = ref(null)
const canvasSize = ref({ w: 0, h: 0 })

// پاک‌کن دستی
const tool = ref('none') // none | erase | restore
const brushSize = ref(28)
const maskTouched = ref(false)
const canUndo = ref(false)
const brushCursor = reactive({ x: 0, y: 0, visible: false })

// المان‌ها و داده‌های غیرواکنشی پردازش
const canvasElement = ref(null)
const canvasWrapper = ref(null)

let sourceUrl = ''
let loadToken = 0
let srcImage = null
let W = 0
let H = 0
let lastRotation = 0
let baseCanvas = null
let outCanvas = null
let outImage = null
let basePixels = null
let bg = null
let coreAlpha = null
let coreRgb = null
let mask = null
let undoStack = []
let frame = null
let pendingLevel = -1

const LEVELS = { compose: 0, core: 1, bg: 2, base: 3 }
const LEVEL_BY_KEY = {
  rotation: 'base',
  invert: 'base',
  lightingCorrection: 'bg',
  removeBackground: 'core',
  sensitivity: 'core',
  softness: 'core',
  specks: 'core',
  boldness: 'core',
  opacity: 'compose',
  inkMode: 'compose',
  customColor: 'compose',
  autoCrop: 'compose',
  cropPadding: 'compose'
}

/* ---------- ابزارهای کمکی عددی ---------- */
const clamp01 = (v) => (v < 0 ? 0 : v > 1 ? 1 : v)

const parseHex = (hex) => {
  const m = /^#?([0-9a-f]{6})$/i.exec(hex || '')
  if (!m) return [18, 18, 20]
  const n = parseInt(m[1], 16)
  return [(n >> 16) & 255, (n >> 8) & 255, n & 255]
}

const INK_COLORS = { black: [18, 18, 20], blue: [24, 52, 140] }

// ماکسیمم جداپذیر (برای نادیده گرفتن خطوط جوهر هنگام تخمین رنگ کاغذ)
const dilate = (src, w, h, r) => {
  const tmp = new Float32Array(src.length)
  const out = new Float32Array(src.length)
  for (let y = 0; y < h; y++) {
    for (let x = 0; x < w; x++) {
      let m = 0
      for (let k = -r; k <= r; k++) {
        const v = src[y * w + Math.min(w - 1, Math.max(0, x + k))]
        if (v > m) m = v
      }
      tmp[y * w + x] = m
    }
  }
  for (let y = 0; y < h; y++) {
    for (let x = 0; x < w; x++) {
      let m = 0
      for (let k = -r; k <= r; k++) {
        const v = tmp[Math.min(h - 1, Math.max(0, y + k)) * w + x]
        if (v > m) m = v
      }
      out[y * w + x] = m
    }
  }
  return out
}

const blur = (src, w, h, r) => {
  const tmp = new Float32Array(src.length)
  const out = new Float32Array(src.length)
  const n = r * 2 + 1
  for (let y = 0; y < h; y++) {
    for (let x = 0; x < w; x++) {
      let s = 0
      for (let k = -r; k <= r; k++) s += src[y * w + Math.min(w - 1, Math.max(0, x + k))]
      tmp[y * w + x] = s / n
    }
  }
  for (let y = 0; y < h; y++) {
    for (let x = 0; x < w; x++) {
      let s = 0
      for (let k = -r; k <= r; k++) s += tmp[Math.min(h - 1, Math.max(0, y + k)) * w + x]
      out[y * w + x] = s / n
    }
  }
  return out
}

/* ---------- مرحله base ---------- */
const rebuildBase = () => {
  const nw = srcImage.naturalWidth
  const nh = srcImage.naturalHeight
  const scale = Math.min(1, MAX_WORKING_SIZE / Math.max(nw, nh))
  const sw = Math.max(1, Math.round(nw * scale))
  const sh = Math.max(1, Math.round(nh * scale))
  const rotation = settings.rotation
  const swap = rotation % 180 !== 0
  const w = swap ? sh : sw
  const h = swap ? sw : sh

  const canvas = document.createElement('canvas')
  canvas.width = w
  canvas.height = h
  const ctx = canvas.getContext('2d', { willReadFrequently: true })
  // تصاویر شفاف (مثلاً امضای قبلی) روی زمینه سفید قرار می‌گیرند تا شفافیت به «سیاه» تعبیر نشود
  ctx.fillStyle = '#fff'
  ctx.fillRect(0, 0, w, h)
  ctx.translate(w / 2, h / 2)
  ctx.rotate((rotation * Math.PI) / 180)
  ctx.drawImage(srcImage, -sw / 2, -sh / 2, sw, sh)
  baseCanvas = canvas

  const pixels = ctx.getImageData(0, 0, w, h).data
  if (settings.invert) {
    for (let i = 0; i < pixels.length; i += 4) {
      pixels[i] = 255 - pixels[i]
      pixels[i + 1] = 255 - pixels[i + 1]
      pixels[i + 2] = 255 - pixels[i + 2]
    }
  }
  basePixels = pixels

  if (!mask || w !== W || h !== H || rotation !== lastRotation) {
    W = w
    H = h
    mask = new Uint8Array(W * H).fill(255)
    undoStack = []
    canUndo.value = false
    maskTouched.value = false
  }
  lastRotation = rotation
}

/* ---------- مرحله bg: تخمین رنگ کاغذ ---------- */
const rebuildBackground = () => {
  const s = Math.max(1, Math.round(Math.max(W, H) / 160))
  const sw = Math.ceil(W / s)
  const sh = Math.ceil(H / s)
  const blocks = [new Float32Array(sw * sh), new Float32Array(sw * sh), new Float32Array(sw * sh)]

  for (let y = 0; y < H; y++) {
    const row = ((y / s) | 0) * sw
    for (let x = 0; x < W; x++) {
      const p = (y * W + x) * 4
      const j = row + ((x / s) | 0)
      if (basePixels[p] > blocks[0][j]) blocks[0][j] = basePixels[p]
      if (basePixels[p + 1] > blocks[1][j]) blocks[1][j] = basePixels[p + 1]
      if (basePixels[p + 2] > blocks[2][j]) blocks[2][j] = basePixels[p + 2]
    }
  }

  const channels = blocks.map((block) => {
    if (settings.lightingCorrection) {
      return blur(blur(dilate(block, sw, sh, 2), sw, sh, 2), sw, sh, 2)
    }
    // بدون اصلاح نور: یک رنگ ثابت برای کل کاغذ
    const sorted = Float32Array.from(block).sort()
    return new Float32Array(block.length).fill(sorted[Math.floor(sorted.length * 0.75)])
  })

  bg = { s, sw, sh, channels }
}

/* ---------- مرحله core ---------- */
const removeSpecks = (alpha, minSize) => {
  const N = W * H
  const visited = new Uint8Array(N)
  const queue = new Int32Array(N)
  for (let start = 0; start < N; start++) {
    if (visited[start] || alpha[start] < 38) continue
    let head = 0
    let tail = 0
    queue[tail++] = start
    visited[start] = 1
    while (head < tail) {
      const cur = queue[head++]
      const cx = cur % W
      const cy = (cur - cx) / W
      for (let dy = -1; dy <= 1; dy++) {
        const ny = cy + dy
        if (ny < 0 || ny >= H) continue
        for (let dx = -1; dx <= 1; dx++) {
          if (!dx && !dy) continue
          const nx = cx + dx
          if (nx < 0 || nx >= W) continue
          const ni = ny * W + nx
          if (!visited[ni] && alpha[ni] >= 38) {
            visited[ni] = 1
            queue[tail++] = ni
          }
        }
      }
    }
    if (tail < minSize) for (let k = 0; k < tail; k++) alpha[queue[k]] = 0
  }
}

const rebuildCore = () => {
  const N = W * H
  const alpha = new Uint8ClampedArray(N)
  const rgb = new Uint8ClampedArray(N * 3)

  if (!settings.removeBackground) {
    for (let i = 0; i < N; i++) {
      rgb[i * 3] = basePixels[i * 4]
      rgb[i * 3 + 1] = basePixels[i * 4 + 1]
      rgb[i * 3 + 2] = basePixels[i * 4 + 2]
      alpha[i] = 255
    }
    coreAlpha = alpha
    coreRgb = rgb
    return
  }

  const { s, sw, sh, channels } = bg
  const [chR, chG, chB] = channels

  // جدول درون‌یابی افقی
  const x0 = new Int32Array(W)
  const x1 = new Int32Array(W)
  const wx = new Float32Array(W)
  for (let x = 0; x < W; x++) {
    const f = Math.min(sw - 1, Math.max(0, (x + 0.5) / s - 0.5))
    x0[x] = Math.floor(f)
    x1[x] = Math.min(sw - 1, x0[x] + 1)
    wx[x] = f - x0[x]
  }

  const low = (settings.sensitivity / 100) * 0.35
  const high = low + 0.02 + (settings.softness / 100) * 0.35
  const gain = Math.pow(2, settings.boldness / 40)

  const ink = new Float32Array(N)
  let sumR = 0
  let sumG = 0
  let sumB = 0
  let count = 0

  for (let y = 0; y < H; y++) {
    const fy = Math.min(sh - 1, Math.max(0, (y + 0.5) / s - 0.5))
    const y0 = Math.floor(fy)
    const y1 = Math.min(sh - 1, y0 + 1)
    const wy = fy - y0
    const o0 = y0 * sw
    const o1 = y1 * sw

    for (let x = 0; x < W; x++) {
      const i = y * W + x
      const p = i * 4
      const a = x0[x]
      const b = x1[x]
      const t = wx[x]

      const bgR = Math.max(25, (chR[o0 + a] * (1 - t) + chR[o0 + b] * t) * (1 - wy) + (chR[o1 + a] * (1 - t) + chR[o1 + b] * t) * wy)
      const bgG = Math.max(25, (chG[o0 + a] * (1 - t) + chG[o0 + b] * t) * (1 - wy) + (chG[o1 + a] * (1 - t) + chG[o1 + b] * t) * wy)
      const bgB = Math.max(25, (chB[o0 + a] * (1 - t) + chB[o0 + b] * t) * (1 - wy) + (chB[o1 + a] * (1 - t) + chB[o1 + b] * t) * wy)

      // «میزان جوهر»: چقدر پیکسل نسبت به کاغذِ همان ناحیه تیره‌تر است
      const ratio = Math.min(basePixels[p] / bgR, basePixels[p + 1] / bgG, basePixels[p + 2] / bgB)
      const v = clamp01(1 - ratio)
      ink[i] = v

      let k = clamp01((v - low) / (high - low))
      k = k * k * (3 - 2 * k)
      if (gain !== 1) k = 1 - Math.pow(1 - k, gain)
      alpha[i] = Math.round(k * 255)

      if (v > 0.6) {
        sumR += basePixels[p]
        sumG += basePixels[p + 1]
        sumB += basePixels[p + 2]
        count++
      }
    }
  }

  // رنگ غالب جوهر؛ پیکسل‌های لبه (مخلوط جوهر و کاغذ) به این رنگ می‌روند تا هاله سفید ایجاد نشود
  const inkR = count > 40 ? sumR / count : 25
  const inkG = count > 40 ? sumG / count : 25
  const inkB = count > 40 ? sumB / count : 35

  for (let i = 0; i < N; i++) {
    const p = i * 4
    let t = clamp01((ink[i] - 0.45) / 0.35)
    t = t * t * (3 - 2 * t)
    rgb[i * 3] = inkR * (1 - t) + basePixels[p] * t
    rgb[i * 3 + 1] = inkG * (1 - t) + basePixels[p + 1] * t
    rgb[i * 3 + 2] = inkB * (1 - t) + basePixels[p + 2] * t
  }

  if (settings.specks > 0) {
    removeSpecks(alpha, Math.round((settings.specks * W * H) / 600000))
  }

  coreAlpha = alpha
  coreRgb = rgb
}

/* ---------- مرحله compose ---------- */
const compose = () => {
  if (!coreAlpha) return

  if (!outCanvas || outCanvas.width !== W || outCanvas.height !== H) {
    outCanvas = document.createElement('canvas')
    outCanvas.width = W
    outCanvas.height = H
    outImage = null
  }
  const ctx = outCanvas.getContext('2d')
  if (!outImage) outImage = ctx.createImageData(W, H)
  const d = outImage.data

  const original = settings.inkMode === 'original'
  const solid = settings.inkMode === 'custom' ? parseHex(settings.customColor) : INK_COLORS[settings.inkMode] || INK_COLORS.black
  const opacity = settings.opacity / 100

  let minX = W
  let minY = H
  let maxX = -1
  let maxY = -1

  for (let y = 0; y < H; y++) {
    for (let x = 0; x < W; x++) {
      const i = y * W + x
      const p = i * 4
      const base = mask[i] === 0 ? 0 : coreAlpha[i]

      if (original) {
        d[p] = coreRgb[i * 3]
        d[p + 1] = coreRgb[i * 3 + 1]
        d[p + 2] = coreRgb[i * 3 + 2]
      } else {
        d[p] = solid[0]
        d[p + 1] = solid[1]
        d[p + 2] = solid[2]
      }
      d[p + 3] = base * opacity

      if (base >= 40) {
        if (x < minX) minX = x
        if (x > maxX) maxX = x
        if (y < minY) minY = y
        if (y > maxY) maxY = y
      }
    }
  }
  ctx.putImageData(outImage, 0, 0)

  canvasSize.value = { w: W, h: H }
  if (maxX >= 0) {
    const pad = Math.round((Math.max(W, H) * settings.cropPadding) / 100)
    const x = Math.max(0, minX - pad)
    const y = Math.max(0, minY - pad)
    cropBox.value = { x, y, w: Math.min(W, maxX + pad + 1) - x, h: Math.min(H, maxY + pad + 1) - y }
    hasInk.value = true
  } else {
    cropBox.value = null
    hasInk.value = !settings.removeBackground
  }

  drawDisplay()
}

const drawDisplay = () => {
  const canvas = canvasElement.value
  if (!canvas || !outCanvas) return
  if (canvas.width !== W || canvas.height !== H) {
    canvas.width = W
    canvas.height = H
  }
  const ctx = canvas.getContext('2d')
  ctx.clearRect(0, 0, W, H)
  ctx.drawImage(showOriginal.value ? baseCanvas : outCanvas, 0, 0)
}

/* ---------- زمان‌بندی ---------- */
const run = (level) => {
  if (!srcImage) return
  if (level >= LEVELS.base) rebuildBase()
  if (level >= LEVELS.bg) rebuildBackground()
  if (level >= LEVELS.core) rebuildCore()
  compose()
}

const schedule = (level) => {
  pendingLevel = Math.max(pendingLevel, LEVELS[level])
  if (frame) return
  frame = requestAnimationFrame(() => {
    frame = null
    const lv = pendingLevel
    pendingLevel = -1
    run(lv)
  })
}

watch(
    () => ({ ...settings }),
    (next, prev) => {
      if (!srcImage) return
      let level = null
      for (const key in next) {
        if (next[key] === prev[key]) continue
        const l = LEVEL_BY_KEY[key]
        if (l && (!level || LEVELS[l] > LEVELS[level])) level = l
      }
      if (level) schedule(level)
    }
)

watch(showOriginal, drawDisplay)

/* ---------- پاک‌کن دستی ---------- */
const pushUndo = () => {
  undoStack.push(mask.slice())
  if (undoStack.length > 10) undoStack.shift()
  canUndo.value = true
}

const undo = () => {
  if (!undoStack.length) return
  mask = undoStack.pop()
  canUndo.value = undoStack.length > 0
  maskTouched.value = mask.some((v) => v === 0)
  schedule('compose')
}

const resetMask = () => {
  if (!mask) return
  pushUndo()
  mask.fill(255)
  maskTouched.value = false
  schedule('compose')
}

const toImagePoint = (e) => {
  const rect = canvasElement.value.getBoundingClientRect()
  return {
    x: ((e.clientX - rect.left) * W) / rect.width,
    y: ((e.clientY - rect.top) * H) / rect.height,
    scale: W / rect.width
  }
}

const stamp = (pt) => {
  const r = (brushSize.value / 2) * pt.scale
  const value = tool.value === 'erase' ? 0 : 255
  const x0 = Math.max(0, Math.floor(pt.x - r))
  const x1 = Math.min(W - 1, Math.ceil(pt.x + r))
  const y0 = Math.max(0, Math.floor(pt.y - r))
  const y1 = Math.min(H - 1, Math.ceil(pt.y + r))
  const r2 = r * r
  for (let y = y0; y <= y1; y++) {
    for (let x = x0; x <= x1; x++) {
      const dx = x - pt.x
      const dy = y - pt.y
      if (dx * dx + dy * dy <= r2) mask[y * W + x] = value
    }
  }
  if (value === 0) maskTouched.value = true
}

let drawing = false
let lastPoint = null

const onPointerDown = (e) => {
  if (tool.value === 'none' || !mask) return
  e.preventDefault()
  canvasElement.value.setPointerCapture(e.pointerId)
  pushUndo()
  drawing = true
  lastPoint = toImagePoint(e)
  stamp(lastPoint)
  schedule('compose')
}

const onPointerMove = (e) => {
  if (tool.value === 'none') return
  const rect = canvasWrapper.value.getBoundingClientRect()
  brushCursor.x = e.clientX - rect.left
  brushCursor.y = e.clientY - rect.top
  brushCursor.visible = true

  if (!drawing) return
  const pt = toImagePoint(e)
  const dist = Math.hypot(pt.x - lastPoint.x, pt.y - lastPoint.y)
  const step = Math.max(1, (brushSize.value / 4) * pt.scale)
  const n = Math.max(1, Math.ceil(dist / step))
  for (let k = 1; k <= n; k++) {
    stamp({
      x: lastPoint.x + ((pt.x - lastPoint.x) * k) / n,
      y: lastPoint.y + ((pt.y - lastPoint.y) * k) / n,
      scale: pt.scale
    })
  }
  lastPoint = pt
  schedule('compose')
}

const onPointerUp = (e) => {
  drawing = false
  canvasElement.value?.releasePointerCapture?.(e.pointerId)
}

/* ---------- باز و بسته کردن ویرایشگر ---------- */
const disposeEditorResources = () => {
  if (frame) cancelAnimationFrame(frame)
  frame = null
  pendingLevel = -1
  loadToken++
  if (sourceUrl) URL.revokeObjectURL(sourceUrl)
  sourceUrl = ''
  srcImage = null
  baseCanvas = outCanvas = outImage = basePixels = bg = coreAlpha = coreRgb = mask = null
  W = H = 0
  undoStack = []
  hasImage.value = false
  cropBox.value = null
}

const openEditor = (user, file) => {
  if (!user || !file) return
  disposeEditorResources()

  editorUser.value = user
  sourceFile.value = file
  Object.assign(settings, defaultSettings())
  lastRotation = 0
  tool.value = 'none'
  showOriginal.value = false
  activeTab.value = 'bg'
  canUndo.value = false
  maskTouched.value = false
  hasInk.value = true
  editorVisible.value = true
  imageLoading.value = true

  sourceUrl = URL.createObjectURL(file)
  const token = loadToken
  const img = new Image()
  img.onload = () => {
    if (token !== loadToken) return
    srcImage = img
    imageLoading.value = false
    hasImage.value = true
    run(LEVELS.base)
    nextTick(drawDisplay)
  }
  img.onerror = () => {
    if (token !== loadToken) return
    imageLoading.value = false
    toast.add({ severity: 'error', summary: 'خطا', detail: 'خواندن تصویر انتخاب‌شده امکان‌پذیر نیست.', life: 3500 })
  }
  img.src = sourceUrl
}

const closeEditor = () => {
  disposeEditorResources()
  editorVisible.value = false
  editorUser.value = null
  sourceFile.value = null
}

const onDialogHide = () => {
  if (editorUser.value) closeEditor()
}

const resetEditor = () => {
  Object.assign(settings, defaultSettings())
  if (mask && maskTouched.value) {
    mask.fill(255)
    undoStack = []
    canUndo.value = false
    maskTouched.value = false
  }
  schedule('base')
}

const rotate = (delta) => {
  settings.rotation = (settings.rotation + delta + 360) % 360
}

/* ---------- خروجی ---------- */
const exportSize = computed(() => {
  const box = cropBox.value
  if (settings.autoCrop && box) return { w: box.w, h: box.h }
  return canvasSize.value.w ? { ...canvasSize.value } : null
})

const buildExportBlob = () =>
    new Promise((resolve) => {
      if (!outCanvas) return resolve(null)
      let source = outCanvas
      const box = cropBox.value
      if (settings.autoCrop && box) {
        source = document.createElement('canvas')
        source.width = box.w
        source.height = box.h
        source.getContext('2d').drawImage(outCanvas, box.x, box.y, box.w, box.h, 0, 0, box.w, box.h)
      }
      source.toBlob(resolve, 'image/png')
    })

const downloadEdited = async () => {
  const blob = await buildExportBlob()
  if (!blob) return
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `signature-${editorUser.value?.id ?? 'edited'}.png`
  link.click()
  URL.revokeObjectURL(url)
}

const saveSignature = async () => {
  const user = editorUser.value
  if (!user || !outCanvas) return

  if (!hasInk.value) {
    toast.add({
      severity: 'warn',
      summary: 'امضایی تشخیص داده نشد',
      detail: 'حساسیت حذف زمینه را کمتر کنید یا تصویر دیگری انتخاب کنید.',
      life: 4500
    })
    return
  }

  const blob = await buildExportBlob()
  if (!blob) return

  const form = new FormData()
  form.append('image', blob, `signature-${user.id}.png`)
  savingUserId.value = user.id
  try {
    await api.post(`/signature-assets/users/${user.id}`, form)
    user.has_signature_image = true
    toast.add({ severity: 'success', summary: 'ثبت شد', detail: `تصویر امضای ${user.name} ذخیره شد.`, life: 3000 })
    closeEditor()
    loadUsers(meta.value.current_page)
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'خطا',
      detail: error.response?.data?.message || 'ثبت امضا انجام نشد.',
      life: 4000
    })
  } finally {
    savingUserId.value = null
  }
}

/* ---------- نمایش ---------- */
const cropOverlayStyle = computed(() => {
  const b = cropBox.value
  const s = canvasSize.value
  if (!settings.autoCrop || !b || !s.w || showOriginal.value) return null
  return {
    left: `${(b.x / s.w) * 100}%`,
    top: `${(b.y / s.h) * 100}%`,
    width: `${(b.w / s.w) * 100}%`,
    height: `${(b.h / s.h) * 100}%`
  }
})

const dialogPt = {
  root: { dir: 'rtl', class: 'overflow-hidden rounded-3xl' },
  header: { class: 'border-b border-slate-100 px-5 py-4 md:px-7' },
  content: { class: 'p-0' }
}

const tabs = [
  { key: 'bg', label: 'پس‌زمینه', icon: 'pi pi-eraser' },
  { key: 'ink', label: 'جوهر و رنگ', icon: 'pi pi-palette' },
  { key: 'tools', label: 'ابزارها', icon: 'pi pi-sliders-h' }
]

onMounted(() => loadUsers())
onBeforeUnmount(() => {
  clearTimeout(searchTimer)
  disposeEditorResources()
})
</script>

<template>
  <main class="signature-page mx-auto max-w-7xl space-y-6 p-4 md:p-7" dir="rtl">
    <!-- سربرگ -->
    <header class="hero-panel overflow-hidden rounded-3xl p-5 md:p-7">
      <div class="relative z-10 flex flex-col justify-between gap-5 md:flex-row md:items-center">
        <div class="max-w-2xl">
          <h1 class="text-2xl font-extrabold tracking-tight text-white md:text-3xl">مدیریت امضای پرسنل</h1>
          <p class="mt-3 max-w-xl text-sm leading-7 text-white/80 md:text-base">
            تصویر امضا را بارگذاری کنید؛ پیش از ذخیره، زمینه حذف می‌شود و می‌توانید پررنگی، رنگ جوهر و برش را تنظیم کنید.
          </p>
        </div>
        <div class="hero-stat flex items-center gap-4 rounded-2xl px-5 py-4 text-white">
          <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 text-2xl"><i class="pi pi-users" /></span>
          <div>
            <div class="text-sm text-white/75">تعداد پرسنل</div>
            <div class="mt-1 text-2xl font-bold tabular-nums">{{ meta.total }}</div>
          </div>
        </div>
      </div>
      <div class="hero-orb hero-orb-one" />
      <div class="hero-orb hero-orb-two" />
    </header>

    <Message severity="info" :closable="false" class="!rounded-2xl">
      <div class="flex flex-col gap-1 text-sm leading-6">
        <strong>راهنمای بارگذاری</strong>
        <span>
          فرمت‌های PNG، JPG و WebP تا ۵ مگابایت پشتیبانی می‌شوند. امضا را روی کاغذ سفید و با نور یکنواخت بنویسید و عکس بگیرید یا اسکن کنید؛
          تصویر نهایی به‌صورت PNG شفاف و بریده‌شده ذخیره می‌شود.
        </span>
      </div>
    </Message>

    <!-- جستجو -->
    <section class="surface-card rounded-3xl border border-slate-200/80 p-4 shadow-sm md:p-5">
      <form class="flex flex-col gap-3 md:flex-row" @submit.prevent="loadUsers(1)">
        <label class="search-field flex min-w-0 flex-1 items-center gap-3 rounded-xl border border-slate-200 px-4">
          <i class="pi pi-search text-slate-400" />
          <InputText
              v-model="search"
              class="w-full !border-0 !bg-transparent !p-3 !shadow-none focus:!ring-0"
              placeholder="جستجو بر اساس نام، شماره همراه یا ایمیل..."
          />
          <button v-if="search" type="button" class="clear-search" aria-label="پاک کردن جستجو" @click="clearSearch">
            <i class="pi pi-times" />
          </button>
        </label>
        <div class="flex gap-2">
          <Button type="submit" label="جستجو" icon="pi pi-search" :loading="loading" class="!rounded-xl" />
          <Button type="button" label="بازنشانی" icon="pi pi-refresh" severity="secondary" outlined class="!rounded-xl" @click="clearSearch" />
        </div>
      </form>
    </section>

    <!-- فهرست -->
    <section class="surface-card overflow-hidden rounded-3xl border border-slate-200/80 shadow-sm">
      <div class="flex flex-col justify-between gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center">
        <div>
          <h2 class="font-bold text-slate-800">فهرست پرسنل</h2>
          <p class="mt-1 text-xs text-slate-500">برای شروع، تصویر امضا را انتخاب کنید تا وارد ویرایشگر شوید.</p>
        </div>
        <Tag :value="`${users.length} نفر در این صفحه`" severity="secondary" />
      </div>

      <div v-if="loading" class="flex flex-col items-center justify-center gap-3 py-16 text-slate-500">
        <ProgressSpinner style="width: 38px; height: 38px" strokeWidth="4" />
        <span class="text-sm">در حال دریافت فهرست پرسنل...</span>
      </div>

      <div v-else-if="!users.length" class="flex flex-col items-center px-5 py-16 text-center">
        <span class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-2xl text-slate-400"><i class="pi pi-users" /></span>
        <h3 class="font-bold text-slate-700">پرسنلی پیدا نشد</h3>
        <p class="mt-2 text-sm text-slate-500">عبارت جستجو را تغییر دهید یا فهرست را بازنشانی کنید.</p>
      </div>

      <div v-else class="divide-y divide-slate-100">
        <article
            v-for="user in users"
            :key="user.id"
            class="person-row flex flex-col gap-4 px-5 py-5 lg:flex-row lg:items-center lg:justify-between"
        >
          <div class="flex min-w-0 items-center gap-4">
            <div class="person-avatar flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl font-bold">
              {{ (user.name || '?').trim().slice(0, 1) }}
            </div>
            <div class="min-w-0">
              <h3 class="truncate font-bold text-slate-800">{{ user.name }}</h3>
              <p class="mt-1 truncate text-sm text-slate-500">{{ user.mobile || user.email || 'اطلاعات تماس ثبت نشده' }}</p>
              <p class="mt-1 truncate text-xs text-slate-400">{{ roleNames(user) }}</p>
            </div>
          </div>

          <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <Tag
                :value="user.has_signature_image ? 'امضا ثبت شده' : 'امضا ثبت نشده'"
                :severity="user.has_signature_image ? 'success' : 'warn'"
                class="self-start sm:self-auto"
            />

            <Button
                v-if="user.has_signature_image && user.signature_image_url"
                label="ویرایش امضای فعلی"
                icon="pi pi-sliders-h"
                severity="secondary"
                outlined
                class="!rounded-xl"
                :loading="loadingExistingId === user.id"
                @click="editExisting(user)"
            />

            <label
                class="upload-trigger inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 focus-within:ring-2 focus-within:ring-blue-300"
            >
              <i class="pi pi-image" />
              {{ user.has_signature_image ? 'جایگزینی با تصویر جدید' : 'انتخاب تصویر امضا' }}
              <input type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="selectFile(user.id, $event)" />
            </label>
          </div>
        </article>
      </div>

      <footer v-if="meta.last_page > 1" class="flex flex-col items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/70 p-4 sm:flex-row">
        <span class="text-sm text-slate-500">صفحه {{ meta.current_page }} از {{ meta.last_page }}</span>
        <div class="flex gap-2">
          <Button label="قبلی" icon="pi pi-arrow-right" severity="secondary" outlined :disabled="meta.current_page <= 1 || loading" @click="loadUsers(meta.current_page - 1)" />
          <Button label="بعدی" icon="pi pi-arrow-left" icon-pos="right" severity="secondary" outlined :disabled="meta.current_page >= meta.last_page || loading" @click="loadUsers(meta.current_page + 1)" />
        </div>
      </footer>
    </section>

    <!-- ویرایشگر -->
    <Dialog
        v-model:visible="editorVisible"
        modal
        :draggable="false"
        :closable="!savingUserId"
        :close-on-escape="!savingUserId"
        :pt="dialogPt"
        style="width: min(1120px, 96vw)"
        @show="drawDisplay"
        @hide="onDialogHide"
    >
      <template #header>
        <div class="flex min-w-0 items-center gap-3" dir="rtl">
          <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-lg text-blue-700"><i class="pi pi-pencil" /></span>
          <div class="min-w-0">
            <h2 class="truncate text-lg font-extrabold text-slate-800">ویرایش تصویر امضا</h2>
            <p class="mt-1 truncate text-xs text-slate-500">{{ editorUser?.name }}</p>
          </div>
        </div>
      </template>

      <div class="grid min-w-0 grid-cols-1 lg:grid-cols-[minmax(0,1fr)_370px]" dir="rtl">
        <!-- پیش‌نمایش -->
        <section class="min-w-0 p-4 md:p-6">
          <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div class="seg" role="group" aria-label="پس‌زمینه پیش‌نمایش">
              <button type="button" class="seg-btn" :class="{ 'is-active': previewBg === 'checker' }" @click="previewBg = 'checker'">شفاف</button>
              <button type="button" class="seg-btn" :class="{ 'is-active': previewBg === 'white' }" @click="previewBg = 'white'">سفید</button>
              <button type="button" class="seg-btn" :class="{ 'is-active': previewBg === 'dark' }" @click="previewBg = 'dark'">تیره</button>
            </div>
            <Button
                :label="showOriginal ? 'نمایش نتیجه' : 'مقایسه با اصل'"
                icon="pi pi-eye"
                size="small"
                severity="secondary"
                :outlined="!showOriginal"
                :aria-pressed="showOriginal"
                :disabled="!hasImage"
                @click="showOriginal = !showOriginal"
            />
          </div>

          <div
              class="preview-stage flex min-h-[260px] items-center justify-center overflow-hidden rounded-2xl border border-slate-200 p-4 md:min-h-[360px]"
              :class="{ checkerboard: previewBg === 'checker', 'bg-white': previewBg === 'white', 'bg-slate-800': previewBg === 'dark' }"
          >
            <ProgressSpinner v-if="imageLoading" style="width: 36px; height: 36px" />

            <div v-show="hasImage" ref="canvasWrapper" class="relative inline-block max-w-full leading-none">
              <canvas
                  ref="canvasElement"
                  class="preview-canvas block"
                  :class="{ 'is-tool': tool !== 'none' }"
                  aria-label="پیش‌نمایش امضا"
                  @pointerdown="onPointerDown"
                  @pointermove="onPointerMove"
                  @pointerup="onPointerUp"
                  @pointercancel="onPointerUp"
                  @pointerleave="brushCursor.visible = false"
              />
              <div v-if="cropOverlayStyle" class="crop-overlay pointer-events-none absolute" :style="cropOverlayStyle" />
              <div
                  v-if="tool !== 'none' && brushCursor.visible"
                  class="brush-cursor pointer-events-none absolute"
                  :class="tool === 'erase' ? 'is-erase' : 'is-restore'"
                  :style="{ width: brushSize + 'px', height: brushSize + 'px', transform: `translate(${brushCursor.x - brushSize / 2}px, ${brushCursor.y - brushSize / 2}px)` }"
              />
            </div>
          </div>

          <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
            <span v-if="tool !== 'none'"><i class="pi pi-hand-pointer ml-1" />برای {{ tool === 'erase' ? 'پاک کردن' : 'بازگرداندن' }} روی تصویر بکشید.</span>
            <span v-else><i class="pi pi-info-circle ml-1" />پردازش روی مرورگر انجام می‌شود و تصویر اصلی ارسال نمی‌شود.</span>
            <span v-if="exportSize" class="tabular-nums">خروجی: {{ exportSize.w }} × {{ exportSize.h }} پیکسل</span>
          </div>

          <Message v-if="hasImage && !hasInk" severity="warn" :closable="false" class="mt-3 !text-xs">
            هیچ خطی تشخیص داده نشد. حساسیت حذف زمینه را کم کنید.
          </Message>
        </section>

        <!-- کنترل‌ها -->
        <aside class="editor-controls flex min-w-0 flex-col border-t border-slate-100 p-4 md:p-6 lg:border-r lg:border-t-0">
          <div class="mb-4 flex items-center justify-between">
            <h3 class="font-extrabold text-slate-800">تنظیمات تصویر</h3>
            <Button label="بازنشانی" icon="pi pi-refresh" text size="small" severity="secondary" @click="resetEditor" />
          </div>

          <div class="mb-4">
            <div class="mb-2 text-xs font-semibold text-slate-500">انتخاب سریع بر اساس نوع تصویر</div>
            <div class="flex flex-wrap gap-2">
              <button v-for="p in presets" :key="p.key" type="button" class="preset-chip" @click="applyPreset(p)">
                <i :class="p.icon" />{{ p.label }}
              </button>
            </div>
          </div>

          <div class="tabs mb-4" role="tablist">
            <button
                v-for="t in tabs"
                :key="t.key"
                type="button"
                role="tab"
                class="tab-btn"
                :class="{ 'is-active': activeTab === t.key }"
                :aria-selected="activeTab === t.key"
                @click="activeTab = t.key"
            >
              <i :class="t.icon" />{{ t.label }}
            </button>
          </div>

          <div class="min-h-0 flex-1 space-y-4 lg:max-h-[46vh] lg:overflow-y-auto lg:pl-1">
            <!-- پس‌زمینه -->
            <template v-if="activeTab === 'bg'">
              <div class="control-card">
                <div class="flex items-center justify-between gap-3">
                  <label for="remove-bg" class="font-semibold text-slate-700">حذف پس‌زمینه</label>
                  <ToggleSwitch inputId="remove-bg" v-model="settings.removeBackground" />
                </div>
                <p class="mt-2 text-xs leading-5 text-slate-500">کاغذ و سایه حذف می‌شود و فقط خطوط جوهر می‌ماند.</p>
              </div>

              <template v-if="settings.removeBackground">
                <div class="control-card">
                  <div class="flex items-center justify-between gap-3">
                    <label for="lighting" class="font-semibold text-slate-700">اصلاح سایه و نور ناهمگون</label>
                    <ToggleSwitch inputId="lighting" v-model="settings.lightingCorrection" />
                  </div>
                  <p class="mt-2 text-xs leading-5 text-slate-500">برای عکس موبایل روشن بماند. برای اسکن یکدست می‌توانید خاموش کنید.</p>
                </div>

                <div class="control-card">
                  <div class="mb-2 flex justify-between text-sm"><label for="sens" class="font-semibold text-slate-700">حساسیت حذف زمینه</label><strong class="tabular-nums text-slate-600">{{ settings.sensitivity }}</strong></div>
                  <Slider inputId="sens" v-model="settings.sensitivity" :min="0" :max="100" :step="1" />
                  <p class="mt-2 text-xs text-slate-500">کم: خطوط کم‌رنگ می‌مانند. زیاد: لکه و سایه بیشتری حذف می‌شود.</p>
                </div>

                <div class="control-card">
                  <div class="mb-2 flex justify-between text-sm"><label for="soft" class="font-semibold text-slate-700">نرمی لبه‌ها</label><strong class="tabular-nums text-slate-600">{{ settings.softness }}</strong></div>
                  <Slider inputId="soft" v-model="settings.softness" :min="0" :max="100" :step="1" />
                </div>

                <div class="control-card">
                  <div class="mb-2 flex justify-between text-sm"><label for="specks" class="font-semibold text-slate-700">حذف لکه‌های ریز</label><strong class="tabular-nums text-slate-600">{{ settings.specks }}</strong></div>
                  <Slider inputId="specks" v-model="settings.specks" :min="0" :max="100" :step="1" />
                  <p class="mt-2 text-xs text-slate-500">نقطه‌های جدا از امضا حذف می‌شوند. اگر نقطه‌های واقعی امضا پاک شدند، کمترش کنید.</p>
                </div>
              </template>
            </template>

            <!-- جوهر -->
            <template v-else-if="activeTab === 'ink'">
              <div class="control-card">
                <div class="mb-2 flex justify-between text-sm"><label for="bold" class="font-semibold text-slate-700">پررنگی خطوط</label><strong class="tabular-nums text-slate-600" dir="ltr">{{ settings.boldness }}</strong></div>
                <Slider inputId="bold" v-model="settings.boldness" :min="-100" :max="100" :step="1" />
                <p class="mt-2 text-xs text-slate-500">مثبت: خطوط ضخیم‌تر و پررنگ‌تر. منفی: نازک‌تر.</p>
              </div>

              <div class="control-card">
                <div class="mb-3 text-sm font-semibold text-slate-700">رنگ جوهر</div>
                <div class="grid grid-cols-4 gap-2">
                  <button type="button" class="ink-swatch" :class="{ 'is-active': settings.inkMode === 'original' }" @click="settings.inkMode = 'original'">
                    <span class="ink-dot ink-dot-original" />اصلی
                  </button>
                  <button type="button" class="ink-swatch" :class="{ 'is-active': settings.inkMode === 'black' }" @click="settings.inkMode = 'black'">
                    <span class="ink-dot" style="background: #121214" />مشکی
                  </button>
                  <button type="button" class="ink-swatch" :class="{ 'is-active': settings.inkMode === 'blue' }" @click="settings.inkMode = 'blue'">
                    <span class="ink-dot" style="background: #18348c" />آبی
                  </button>
                  <label class="ink-swatch cursor-pointer" :class="{ 'is-active': settings.inkMode === 'custom' }">
                    <span class="ink-dot" :style="{ background: settings.customColor }" />دلخواه
                    <input type="color" class="sr-only" v-model="settings.customColor" aria-label="رنگ دلخواه جوهر" @input="settings.inkMode = 'custom'" />
                  </label>
                </div>
              </div>

              <div class="control-card">
                <div class="mb-2 flex justify-between text-sm"><label for="opacity" class="font-semibold text-slate-700">شفافیت کلی امضا</label><strong class="tabular-nums text-slate-600">{{ settings.opacity }}٪</strong></div>
                <Slider inputId="opacity" v-model="settings.opacity" :min="10" :max="100" :step="1" />
                <p class="mt-2 text-xs text-slate-500">برای کم‌رنگ‌تر کردن کل امضا.</p>
              </div>

              <div class="control-card">
                <div class="flex items-center justify-between gap-3">
                  <div>
                    <label for="invert" class="font-semibold text-slate-700">معکوس کردن رنگ‌ها</label>
                    <p class="mt-1 text-xs text-slate-500">برای امضای روشن روی زمینه تیره</p>
                  </div>
                  <ToggleSwitch inputId="invert" v-model="settings.invert" />
                </div>
              </div>
            </template>

            <!-- ابزارها -->
            <template v-else>
              <div class="control-card">
                <div class="mb-3 text-sm font-semibold text-slate-700">چرخش</div>
                <div class="flex gap-2">
                  <Button label="۹۰° چپ" icon="pi pi-undo" size="small" severity="secondary" outlined class="flex-1" :disabled="!hasImage" @click="rotate(-90)" />
                  <Button label="۹۰° راست" icon="pi pi-refresh" size="small" severity="secondary" outlined class="flex-1" :disabled="!hasImage" @click="rotate(90)" />
                </div>
                <p v-if="maskTouched" class="mt-2 text-xs text-amber-600">با چرخش، پاک‌کردن‌های دستی بازنشانی می‌شود.</p>
              </div>

              <div class="control-card">
                <div class="flex items-center justify-between gap-3">
                  <label for="crop" class="font-semibold text-slate-700">برش خودکار حاشیه‌ها</label>
                  <ToggleSwitch inputId="crop" v-model="settings.autoCrop" />
                </div>
                <div v-if="settings.autoCrop" class="mt-4">
                  <div class="mb-2 flex justify-between text-xs"><label for="pad">فاصله از لبه</label><strong class="tabular-nums">{{ settings.cropPadding }}٪</strong></div>
                  <Slider inputId="pad" v-model="settings.cropPadding" :min="0" :max="15" :step="1" />
                </div>
              </div>

              <div class="control-card">
                <div class="mb-3 text-sm font-semibold text-slate-700">پاک‌کن دستی</div>
                <div class="seg w-full" role="group" aria-label="ابزار">
                  <button type="button" class="seg-btn flex-1" :class="{ 'is-active': tool === 'none' }" @click="tool = 'none'">بدون ابزار</button>
                  <button type="button" class="seg-btn flex-1" :class="{ 'is-active': tool === 'erase' }" @click="tool = 'erase'">پاک‌کن</button>
                  <button type="button" class="seg-btn flex-1" :class="{ 'is-active': tool === 'restore' }" @click="tool = 'restore'">بازگرداندن</button>
                </div>

                <div v-if="tool !== 'none'" class="mt-4">
                  <div class="mb-2 flex justify-between text-xs"><label for="brush">اندازه قلم</label><strong class="tabular-nums">{{ brushSize }}</strong></div>
                  <Slider inputId="brush" v-model="brushSize" :min="8" :max="120" :step="2" />
                </div>

                <div class="mt-4 flex gap-2">
                  <Button label="بازگشت" icon="pi pi-undo" size="small" severity="secondary" outlined class="flex-1" :disabled="!canUndo" @click="undo" />
                  <Button label="حذف همه" icon="pi pi-trash" size="small" severity="secondary" outlined class="flex-1" :disabled="!maskTouched" @click="resetMask" />
                </div>
                <p class="mt-2 text-xs text-slate-500">برای پاک کردن لکه‌ها یا خطوط اضافه کنار امضا.</p>
              </div>
            </template>
          </div>

          <div class="mt-5 grid grid-cols-1 gap-2">
            <Button
                label="ذخیره امضا"
                icon="pi pi-check"
                class="!w-full !rounded-xl"
                :loading="savingUserId === editorUser?.id"
                :disabled="!hasImage || imageLoading"
                @click="saveSignature"
            />
            <Button
                label="دانلود تصویر ویرایش‌شده"
                icon="pi pi-download"
                severity="secondary"
                outlined
                class="!w-full !rounded-xl"
                :disabled="!hasImage"
                @click="downloadEdited"
            />
            <Button label="انصراف" severity="secondary" text class="!w-full" :disabled="savingUserId === editorUser?.id" @click="closeEditor()" />
          </div>
        </aside>
      </div>
    </Dialog>
  </main>
</template>

<style scoped>
.signature-page {
  color: #172033;
}
.hero-panel {
  position: relative;
  isolation: isolate;
  background: linear-gradient(125deg, #102a56 0%, #174b83 52%, #2369a6 100%);
  box-shadow: 0 18px 45px rgb(18 58 102 / 14%);
}
.hero-stat {
  background: rgb(255 255 255 / 10%);
  border: 1px solid rgb(255 255 255 / 16%);
  backdrop-filter: blur(12px);
}
.hero-orb {
  position: absolute;
  z-index: -1;
  border-radius: 9999px;
  pointer-events: none;
  background: rgb(255 255 255 / 5%);
}
.hero-orb-one {
  width: 280px;
  height: 280px;
  left: 14%;
  top: -190px;
}
.hero-orb-two {
  width: 190px;
  height: 190px;
  left: 32%;
  bottom: -140px;
}
.surface-card {
  background: #fff;
}
.search-field {
  background: #f8fafc;
  transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
.search-field:focus-within {
  border-color: #7aa7df;
  background: #fff;
  box-shadow: 0 0 0 3px rgb(59 130 246 / 10%);
}
.clear-search {
  display: inline-flex;
  width: 28px;
  height: 28px;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  color: #64748b;
}
.clear-search:hover {
  background: #e2e8f0;
}
.person-row {
  transition: background 0.18s ease;
}
.person-row:hover {
  background: #fbfdff;
}
.person-avatar {
  color: #245a94;
  background: linear-gradient(145deg, #e7f1ff, #f2f7ff);
  border: 1px solid #d9e8fb;
}
.upload-trigger {
  min-height: 42px;
}

/* پیش‌نمایش */
.checkerboard {
  background-color: #fff;
  background-image: linear-gradient(45deg, #e8edf3 25%, transparent 25%), linear-gradient(-45deg, #e8edf3 25%, transparent 25%),
  linear-gradient(45deg, transparent 75%, #e8edf3 75%), linear-gradient(-45deg, transparent 75%, #e8edf3 75%);
  background-size: 20px 20px;
  background-position: 0 0, 0 10px, 10px -10px, -10px 0;
}
.preview-canvas {
  max-width: 100%;
  max-height: 420px;
  width: auto;
  height: auto;
}
.preview-canvas.is-tool {
  cursor: none;
  touch-action: none;
}
.crop-overlay {
  border: 2px dashed rgb(37 99 235 / 75%);
  box-shadow: 0 0 0 9999px rgb(15 23 42 / 12%);
}
.brush-cursor {
  left: 0;
  top: 0;
  border-radius: 9999px;
  border: 2px solid #fff;
  box-shadow: 0 0 0 1.5px rgb(15 23 42 / 70%);
}
.brush-cursor.is-erase {
  background: rgb(239 68 68 / 18%);
}
.brush-cursor.is-restore {
  background: rgb(34 197 94 / 18%);
}

/* کنترل‌ها */
.editor-controls {
  background-color: #f8fafc;
}
.control-card {
  border: 1px solid #e5eaf1;
  border-radius: 16px;
  padding: 14px;
  background: #fff;
}
.seg {
  display: inline-flex;
  padding: 3px;
  gap: 2px;
  border-radius: 12px;
  background: #eef2f7;
}
.seg-btn {
  padding: 6px 14px;
  border-radius: 9px;
  font-size: 0.8rem;
  font-weight: 600;
  color: #475569;
  transition: background 0.15s, color 0.15s;
}
.seg-btn.is-active {
  background: #fff;
  color: #1d4ed8;
  box-shadow: 0 1px 3px rgb(15 23 42 / 12%);
}
.preset-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  border-radius: 9999px;
  border: 1px solid #dbe3ee;
  background: #fff;
  font-size: 0.75rem;
  font-weight: 600;
  color: #334155;
  transition: border-color 0.15s, background 0.15s, color 0.15s;
}
.preset-chip:hover {
  border-color: #93b4e6;
  background: #eff6ff;
  color: #1d4ed8;
}
.tabs {
  display: flex;
  gap: 4px;
  border-bottom: 1px solid #e2e8f0;
}
.tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  margin-bottom: -1px;
  font-size: 0.8rem;
  font-weight: 700;
  color: #64748b;
  border-bottom: 2px solid transparent;
}
.tab-btn.is-active {
  color: #1d4ed8;
  border-bottom-color: #1d4ed8;
}
.ink-swatch {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 8px 4px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  font-size: 0.72rem;
  font-weight: 600;
  color: #475569;
  background: #fff;
}
.ink-swatch.is-active {
  border-color: #2563eb;
  background: #eff6ff;
  color: #1d4ed8;
}
.ink-swatch:focus-within {
  outline: 2px solid #93b4e6;
}
.ink-dot {
  width: 22px;
  height: 22px;
  border-radius: 9999px;
  border: 1px solid rgb(15 23 42 / 15%);
}
.ink-dot-original {
  background: conic-gradient(#121214, #18348c, #7c3aed, #121214);
}

:deep(.p-dialog-mask) {
  backdrop-filter: blur(5px);
  padding: 12px;
}
:deep(.p-dialog) {
  max-height: 94vh;
}
:deep(.p-dialog-content) {
  overflow-y: auto;
}
:deep(.p-slider) {
  margin: 8px 2px;
}
:deep(.p-message-content) {
  align-items: flex-start;
}
@media (max-width: 640px) {
  .hero-panel {
    border-radius: 22px;
  }
}
</style>