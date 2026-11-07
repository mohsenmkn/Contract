<!-- src/views/Users/UsersView.vue -->
<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useApiErrorHandler } from '@/composables/useApiErrorHandler'
import api from '@/api/axios.js'
import { useAuthStore } from '@/stores/auth.js'
import {
  Users, UserCheck, UserPlus, Shield, ShieldCheck, User, Phone, CreditCard, Briefcase,
  Lock, Activity, AlertTriangle, Eye, Mail, Calendar, Search, X, Scale, Crown,
  Landmark, Wallet, HardHat, Building2, BadgeCheck
} from 'lucide-vue-next'

const confirm = useConfirm()
const toast = useToast()
const { handleApiError } = useApiErrorHandler()
const authStore = useAuthStore()

/* =====================================================================
 * نقش‌ها: عنوان فارسی، رنگ و آیکون
 * نقش‌های جدید را فقط کافی است همین‌جا اضافه کنید.
 * اگر نقشی در این جدول نبود، ابتدا فیلدهای فارسی API (title/label/...) و در نهایت نام لاتین نمایش داده می‌شود.
 * ===================================================================== */
const ROLE_META = {
  'super-admin': { severity: 'danger', icon: ShieldCheck },
  admin: { severity: 'danger', icon: ShieldCheck },
  unit_representative: { severity: 'info', icon: Building2 },
  legal_officer: { severity: 'info', icon: Scale },
  contract_manager: { severity: 'warn', icon: Briefcase },
  financial_expert: { severity: 'success', icon: Wallet },
  guarantee_officer: { severity: 'success', icon: BadgeCheck },
  ceo: { severity: 'contrast', icon: Crown },
  deputy_ceo: { severity: 'contrast', icon: Landmark },
  board_member: { severity: 'contrast', icon: Users },
  contractor: { severity: 'warn', icon: HardHat },
  manager: { severity: 'warn', icon: Briefcase },
  user: { severity: 'secondary', icon: User },
  banned: { severity: 'danger', icon: Lock }
}

const roleName = (role) => (typeof role === 'string' ? role : role?.name ?? role?.title ?? role?.label ?? null)

const roleMeta = (role) => {
  const name = roleName(role) ?? ''
  if (ROLE_META[name]) return ROLE_META[name]
  if (/admin/i.test(name)) return { severity: 'danger', icon: ShieldCheck }
  if (/manager/i.test(name)) return { severity: 'warn', icon: Briefcase }
  return { severity: 'info', icon: User }
}

const roleLabel = (role) => {
  const name = roleName(role)
  if (!name) return ''
  const source = typeof role === 'object' ? role : availableRoles.value.find((r) => r.name === name)
  for (const field of ['display_name', 'title', 'label', 'name_fa', 'persian_name']) {
    if (source?.[field] && source[field] !== name) return source[field]
  }
  return name
}

const getRoleNames = (u) => (Array.isArray(u?.roles) ? u.roles.map(roleName).filter(Boolean) : [])
const isAdminRole = (name) => /admin/i.test(String(name))

/* ---------- وضعیت ---------- */
const rows = ref(10)
const totalRecords = ref(0)
const currentPage = ref(1)
const users = ref([])
const availableRoles = ref([])
const loading = ref(false)
const saving = ref(false)
const userDialog = ref(false)
const viewDialog = ref(false)
const isEdit = ref(false)
const user = ref({ roles: [] })
const selectedUser = ref(null)
const errors = ref({})

const searchQuery = ref('')
const roleFilter = ref(null)

let searchTimeout = null
let loadSeq = 0

const can = (permission) => authStore.permissions?.includes(permission)
const isSelf = (u) => !!authStore.user?.id && u.id === authStore.user.id

const dialogPt = { root: { dir: 'rtl' } }

/* ---------- محاسبات ---------- */
const hasActiveFilters = computed(() => !!(searchQuery.value.trim() || roleFilter.value))

const roleOptions = computed(() =>
    availableRoles.value
        .map((r) => ({ value: r.name, label: roleLabel(r) }))
        .sort((a, b) => a.label.localeCompare(b.label, 'fa'))
)

const stats = computed(() => {
  const now = new Date()
  const monthStart = new Date(now.getFullYear(), now.getMonth(), 1)
  const list = users.value
  return [
    { key: 'total', label: 'کل کاربران', value: totalRecords.value, icon: Users, tone: 'blue' },
    { key: 'active', label: 'فعال (این صفحه)', value: list.filter((u) => u.is_active).length, icon: UserCheck, tone: 'green' },
    { key: 'admins', label: 'مدیران سیستم (این صفحه)', value: list.filter((u) => getRoleNames(u).some(isAdminRole)).length, icon: Shield, tone: 'purple' },
    { key: 'new', label: 'جدید این ماه (این صفحه)', value: list.filter((u) => u.created_at && new Date(u.created_at) >= monthStart).length, icon: UserPlus, tone: 'orange' }
  ]
})

const TONES = {
  blue: 'bg-blue-100 text-blue-600',
  green: 'bg-green-100 text-green-600',
  purple: 'bg-purple-100 text-purple-600',
  orange: 'bg-orange-100 text-orange-600'
}

/* ---------- ابزارهای کمکی ---------- */
const toLatinDigits = (value) =>
    String(value ?? '')
        .replace(/[۰-۹]/g, (d) => String(d.charCodeAt(0) - 0x06f0))
        .replace(/[٠-٩]/g, (d) => String(d.charCodeAt(0) - 0x0660))

const getInitials = (name) => {
  const parts = String(name ?? '').trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return '?'
  return parts.length >= 2 ? `${parts[0][0]}‌${parts[1][0]}` : parts[0][0]
}

const formatDate = (date) => (date ? new Date(date).toLocaleDateString('fa-IR') : '-')

const errText = (key) => {
  let value = errors.value[key]
  if (!value && key === 'roles') {
    const nested = Object.keys(errors.value).find((k) => k.startsWith('roles.'))
    value = nested ? errors.value[nested] : null
  }
  return Array.isArray(value) ? value[0] : value
}

const clearError = (key) => {
  if (!errors.value[key]) return
  const next = { ...errors.value }
  delete next[key]
  errors.value = next
}

const focusFirstError = async () => {
  await nextTick()
  const el = document.querySelector('.user-form [aria-invalid="true"], .user-form .field-error')
  el?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  if (el?.matches?.('input,textarea')) el.focus({ preventScroll: true })
}

/* ---------- دریافت داده ---------- */
const loadRoles = async () => {
  try {
    const response = await api.get('/acl/roles/')
    availableRoles.value = response.data.data || response.data
  } catch (error) {
    console.error('خطا در دریافت لیست نقش‌ها', error)
  }
}

// پاسخ API ساختار تو در تو دارد ({ user: {...}, unit: {...} }) و برای جدول تخت می‌شود
const normalizeUser = (item = {}) => {
  const nested = item.user ?? {}
  const roles = item.roles ?? nested.roles ?? nested.role ?? []

  return {
    ...item,
    ...nested,
    id: nested.id ?? item.user_id ?? item.id,
    user_id: nested.id ?? item.user_id,
    name: nested.name ?? item.name ?? '',
    mobile: nested.mobile ?? item.mobile ?? '',
    national_code: nested.national_code ?? item.national_code ?? '',
    personnel_code: nested.personnel_code ?? item.personnel_code ?? '',
    email: nested.email ?? item.email ?? null,
    employee_type: nested.employee_type ?? item.employee_type ?? 'personnel',
    is_active: nested.is_active ?? item.is_active ?? false,
    created_at: nested.created_at ?? item.created_at ?? null,
    roles: Array.isArray(roles) ? roles : [],
    post_title: item.post_title ?? '',
    job_title: item.job_title ?? '',
    unit: item.unit ?? null,
    unit_title: item.unit?.name ?? item.unit?.title ?? item.unit_title ?? ''
  }
}

const loadUsers = async (page = currentPage.value) => {
  const seq = ++loadSeq
  loading.value = true

  try {
    const params = { page, per_page: rows.value }
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim()
    if (roleFilter.value) params.role = roleFilter.value

    const response = await api.get('/users', { params })
    if (seq !== loadSeq) return // پاسخ قدیمی

    users.value = (response.data.data ?? []).map(normalizeUser)
    totalRecords.value = response.data.total ?? response.data.meta?.total ?? 0
    currentPage.value = response.data.current_page ?? response.data.meta?.current_page ?? page
  } catch (error) {
    if (seq === loadSeq) handleApiError(error)
  } finally {
    if (seq === loadSeq) loading.value = false
  }
}

/* ---------- جستجو و فیلتر ---------- */
const onSearchInput = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadUsers(1), 400)
}

const clearSearch = () => {
  clearTimeout(searchTimeout)
  searchQuery.value = ''
  loadUsers(1)
}

const clearRoleFilter = () => {
  roleFilter.value = null
  loadUsers(1)
}

const clearFilters = () => {
  clearTimeout(searchTimeout)
  searchQuery.value = ''
  roleFilter.value = null
  loadUsers(1)
}

const onPage = (event) => {
  rows.value = event.rows
  loadUsers(event.page + 1)
}

/* ---------- دیالوگ‌ها ---------- */
const noPermission = () =>
    toast.add({ severity: 'warn', summary: 'اطلاع', detail: 'شما مجاز به این عمل نیستید', life: 3000 })

const openNew = () => {
  if (!can('users.create')) return noPermission()
  user.value = {
    name: '',
    mobile: '',
    national_code: '',
    personnel_code: '',
    password: '',
    roles: [],
    is_active: true,
    employee_type: 'personnel'
  }
  errors.value = {}
  isEdit.value = false
  userDialog.value = true
}

const viewUser = (data) => {
  selectedUser.value = data
  viewDialog.value = true
}

const editUser = (data) => {
  if (!can('users.update')) return noPermission()
  user.value = {
    id: data.id,
    name: data.name,
    mobile: data.mobile,
    national_code: data.national_code,
    personnel_code: data.personnel_code,
    roles: getRoleNames(data),
    employee_type: data.employee_type ?? 'personnel',
    // پیمانکار همیشه غیرفعال است
    is_active: data.employee_type === 'contractor' ? false : Boolean(data.is_active ?? true)
  }
  errors.value = {}
  isEdit.value = true
  userDialog.value = true
}

const hideDialog = () => {
  userDialog.value = false
}

const isRoleSelected = (name) => user.value.roles.includes(name)

const toggleRole = (name) => {
  const index = user.value.roles.indexOf(name)
  if (index > -1) user.value.roles.splice(index, 1)
  else user.value.roles.push(name)
  clearError('roles')
}

/* ---------- ذخیره ---------- */
const buildPayload = () => {
  const u = user.value
  const payload = {
    name: String(u.name ?? '').trim(),
    mobile: toLatinDigits(u.mobile).replace(/[\s-]/g, ''),
    national_code: toLatinDigits(u.national_code).trim(),
    personnel_code: toLatinDigits(u.personnel_code).trim(),
    roles: [...u.roles],
    is_active: u.employee_type === 'contractor' ? false : !!u.is_active,
    employee_type: u.employee_type
  }
  if (!isEdit.value) payload.password = u.password
  return payload
}

const collectErrors = (p) => {
  const e = {}
  if (!p.name) e.name = 'نام و نام خانوادگی الزامی است'

  if (!p.mobile) e.mobile = 'شماره موبایل الزامی است'
  else if (!/^09\d{9}$/.test(p.mobile)) e.mobile = 'فرمت شماره موبایل نامعتبر است (مثال: 09123456789)'

  if (!p.national_code) e.national_code = 'کد ملی الزامی است'
  else if (!/^\d{10}$/.test(p.national_code)) e.national_code = 'کد ملی باید ۱۰ رقم باشد'

  if (!p.personnel_code) e.personnel_code = 'کد پرسنلی الزامی است'
  if (!isEdit.value && !p.password) e.password = 'رمز عبور الزامی است'
  if (!p.roles.length) e.roles = 'حداقل یک نقش باید انتخاب شود'
  return e
}

const saveUser = async () => {
  if (saving.value) return

  const payload = buildPayload()
  const clientErrors = collectErrors(payload)
  if (Object.keys(clientErrors).length) {
    errors.value = clientErrors
    focusFirstError()
    return
  }

  saving.value = true
  errors.value = {}
  try {
    if (isEdit.value) {
      await api.put(`/users/${user.value.id}`, payload)
      toast.add({ severity: 'success', summary: 'موفق', detail: 'کاربر با موفقیت ویرایش شد', life: 3000 })
    } else {
      await api.post('/users', payload)
      toast.add({ severity: 'success', summary: 'موفق', detail: 'کاربر جدید با موفقیت اضافه شد', life: 3000 })
    }
    userDialog.value = false
    await loadUsers()
  } catch (error) {
    handleApiError(error, {
      setErrors: (validationErrors) => {
        errors.value = validationErrors
        focusFirstError()
      }
    })
  } finally {
    saving.value = false
  }
}

/* ---------- حذف و خروجی ---------- */
const confirmDelete = (data) => {
  confirm.require({
    message: `آیا از حذف کاربر «${data.name}» اطمینان دارید؟`,
    header: 'تایید حذف',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: { label: 'خیر', severity: 'secondary', outlined: true },
    acceptProps: { label: 'بله، حذف کن', severity: 'danger' },
    accept: async () => {
      try {
        await api.delete(`/users/${data.id}`)
        toast.add({ severity: 'success', summary: 'موفق', detail: 'کاربر حذف شد', life: 3000 })
        const page = users.value.length === 1 && currentPage.value > 1 ? currentPage.value - 1 : currentPage.value
        await loadUsers(page)
      } catch (error) {
        handleApiError(error)
      }
    }
  })
}

const exportToExcel = () => {
  toast.add({ severity: 'info', summary: 'اطلاع', detail: 'این قابلیت به زودی اضافه می‌شود', life: 3000 })
}

onMounted(() => {
  loadUsers()
  loadRoles()
})

onBeforeUnmount(() => clearTimeout(searchTimeout))
</script>

<template>
  <div class="min-h-screen bg-slate-50 p-4 md:p-6" dir="rtl">
    <!-- هدر صفحه -->
    <header class="surface mb-5 flex flex-col gap-4 p-5 md:flex-row md:items-center md:justify-between md:p-6">
      <div class="flex items-center gap-4">
        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-100"><Users class="h-7 w-7 text-blue-600" /></span>
        <div>
          <h1 class="text-2xl font-bold text-slate-800 md:text-3xl">مدیریت کاربران</h1>
          <p class="mt-1 text-sm text-slate-500">مدیریت حساب‌ها، نقش‌ها و دسترسی کاربران سیستم</p>
        </div>
      </div>
      <div class="flex flex-wrap gap-3">
        <Button v-if="can('users.export')" label="خروجی Excel" icon="pi pi-file-excel" severity="success" outlined @click="exportToExcel" />
        <Button v-if="can('users.create')" label="کاربر جدید" icon="pi pi-plus" @click="openNew" />
      </div>
    </header>

    <!-- آمار -->
    <section class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4" aria-label="آمار کاربران">
      <div v-for="s in stats" :key="s.key" class="surface flex items-center gap-3 p-4 md:p-5">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl" :class="TONES[s.tone]">
          <component :is="s.icon" class="h-5 w-5" />
        </span>
        <div class="min-w-0">
          <div class="text-2xl font-bold tabular-nums text-slate-800">{{ s.value.toLocaleString('fa-IR') }}</div>
          <div class="truncate text-xs text-slate-500">{{ s.label }}</div>
        </div>
      </div>
    </section>

    <!-- جدول -->
    <section class="surface overflow-hidden">
      <template v-if="can('users.read')">
        <!-- جستجو و فیلتر -->
        <div class="border-b border-slate-100 p-4 md:p-5">
          <div class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,1fr)_260px]">
            <div class="relative">
              <Search class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
              <InputText
                  v-model="searchQuery"
                  class="w-full !pr-10 !pl-9"
                  placeholder="جستجو بر اساس نام، موبایل، کد ملی یا کد پرسنلی..."
                  aria-label="جستجوی کاربران"
                  @input="onSearchInput"
              />
              <button v-if="searchQuery" type="button" class="clear-btn" aria-label="پاک کردن جستجو" @click="clearSearch">
                <X class="h-4 w-4" />
              </button>
            </div>

            <Select
                v-model="roleFilter"
                :options="roleOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="همه نقش‌ها"
                class="w-full"
                showClear
                aria-label="فیلتر بر اساس نقش"
                @change="loadUsers(1)"
            />
          </div>

          <div v-if="hasActiveFilters" class="mt-3 flex flex-wrap items-center gap-2 text-sm">
            <span class="text-slate-500">فیلترهای فعال:</span>
            <button v-if="searchQuery.trim()" type="button" class="filter-pill" @click="clearSearch">
              جستجو: {{ searchQuery.trim() }}<i class="pi pi-times" />
            </button>
            <button v-if="roleFilter" type="button" class="filter-pill" @click="clearRoleFilter">
              نقش: {{ roleLabel(roleFilter) }}<i class="pi pi-times" />
            </button>
            <Button label="پاک کردن همه" icon="pi pi-filter-slash" size="small" text @click="clearFilters" />
          </div>
        </div>

        <DataTable
            :value="users"
            lazy
            paginator
            scrollable
            dataKey="id"
            stripedRows
            :first="(currentPage - 1) * rows"
            :rows="rows"
            :rowsPerPageOptions="[10, 25, 50]"
            :totalRecords="totalRecords"
            :loading="loading"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
            currentPageReportTemplate="{first} تا {last} از {totalRecords} کاربر"
            class="users-table"
            @page="onPage"
        >
          <template #empty>
            <div class="py-14 text-center">
              <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100"><Users class="h-7 w-7 text-slate-400" /></span>
              <h3 class="font-semibold text-slate-700">کاربری پیدا نشد</h3>
              <p class="mt-1 text-sm text-slate-500">{{ hasActiveFilters ? 'فیلترها را تغییر دهید یا پاک کنید.' : 'هنوز کاربری ثبت نشده است.' }}</p>
              <Button v-if="hasActiveFilters" label="پاک کردن فیلترها" icon="pi pi-filter-slash" size="small" outlined class="mt-4" @click="clearFilters" />
            </div>
          </template>

          <Column header="کاربر" style="min-width: 230px">
            <template #body="{ data }">
              <div class="flex items-center gap-3">
                <span class="user-avatar">{{ getInitials(data.name) }}</span>
                <div class="min-w-0">
                  <div class="truncate font-semibold text-slate-800">{{ data.name || 'بدون نام' }}</div>
                  <div class="mt-0.5 truncate text-xs text-slate-500">{{ data.email || data.job_title || data.post_title || 'بدون اطلاعات تکمیلی' }}</div>
                </div>
              </div>
            </template>
          </Column>

          <Column header="موبایل / کد ملی" style="min-width: 150px">
            <template #body="{ data }">
              <div class="space-y-1" dir="ltr">
                <div class="flex items-center gap-2 text-sm"><Phone class="h-3.5 w-3.5 text-slate-400" /><span class="font-mono">{{ data.mobile || '-' }}</span></div>
                <div class="flex items-center gap-2 text-xs text-slate-500"><CreditCard class="h-3.5 w-3.5 text-slate-400" /><span class="font-mono">{{ data.national_code || '-' }}</span></div>
              </div>
            </template>
          </Column>

          <Column header="کد پرسنلی" style="min-width: 110px">
            <template #body="{ data }"><span class="font-mono text-sm text-slate-700" dir="ltr">{{ data.personnel_code || '-' }}</span></template>
          </Column>

          <Column header="واحد سازمانی" style="min-width: 170px">
            <template #body="{ data }">
              <div class="flex items-center gap-2 text-sm text-slate-700">
                <Building2 class="h-4 w-4 shrink-0 text-slate-400" />
                <span class="truncate">{{ data.unit_title || 'بدون واحد سازمانی' }}</span>
              </div>
            </template>
          </Column>

          <Column header="نقش‌ها" style="min-width: 210px">
            <template #body="{ data }">
              <div v-if="getRoleNames(data).length" class="flex flex-wrap gap-1.5" :title="getRoleNames(data).map(roleLabel).join('، ')">
                <Tag v-for="name in getRoleNames(data).slice(0, 2)" :key="name" :value="roleLabel(name)" :severity="roleMeta(name).severity" />
                <Tag v-if="getRoleNames(data).length > 2" :value="`+${(getRoleNames(data).length - 2).toLocaleString('fa-IR')}`" severity="secondary" />
              </div>
              <span v-else class="text-xs text-slate-400">بدون نقش</span>
            </template>
          </Column>

          <Column header="نوع کاربر" style="min-width: 105px">
            <template #body="{ data }">
              <Tag :value="data.employee_type === 'contractor' ? 'پیمانکار' : 'پرسنل'" :severity="data.employee_type === 'contractor' ? 'warn' : 'success'" />
            </template>
          </Column>

          <Column header="وضعیت" style="min-width: 95px">
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'فعال' : 'غیرفعال'" :severity="data.is_active ? 'success' : 'danger'" />
            </template>
          </Column>

          <Column header="عملیات" style="min-width: 140px" frozen alignFrozen="left">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button v-if="can('users.view')" icon="pi pi-eye" severity="info" text rounded aria-label="مشاهده" title="مشاهده" @click="viewUser(data)" />
                <Button v-if="can('users.update')" icon="pi pi-pencil" severity="success" text rounded aria-label="ویرایش" title="ویرایش" @click="editUser(data)" />
                <Button
                    v-if="can('users.delete')"
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    rounded
                    :disabled="isSelf(data)"
                    aria-label="حذف"
                    :title="isSelf(data) ? 'امکان حذف حساب خودتان وجود ندارد' : 'حذف'"
                    @click="confirmDelete(data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>

      <div v-else class="p-12 text-center">
        <Lock class="mx-auto mb-4 h-16 w-16 text-slate-300" />
        <h3 class="mb-2 text-lg font-semibold text-slate-600">دسترسی محدود</h3>
        <p class="text-slate-500">شما مجاز به مشاهده لیست کاربران نیستید</p>
      </div>
    </section>

    <!-- ═════════ دیالوگ ایجاد / ویرایش ═════════ -->
    <Dialog
        v-model:visible="userDialog"
        modal
        :draggable="false"
        :closable="!saving"
        :close-on-escape="!saving"
        :pt="dialogPt"
        :breakpoints="{ '992px': '90vw', '576px': '96vw' }"
        style="width: 640px"
    >
      <template #header>
        <div class="flex items-center gap-3" dir="rtl">
          <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-blue-600"><User class="h-5 w-5" /></span>
          <div>
            <h2 class="text-lg font-bold text-slate-800">{{ isEdit ? 'ویرایش کاربر' : 'کاربر جدید' }}</h2>
            <p class="text-xs text-slate-500">{{ isEdit ? 'ویرایش اطلاعات و نقش‌های کاربر' : 'ایجاد حساب کاربری جدید' }}</p>
          </div>
        </div>
      </template>

      <div class="user-form space-y-6" dir="rtl">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div class="field sm:col-span-2">
            <label for="name" class="field-label"><User class="label-icon" />نام و نام خانوادگی<span class="req">*</span></label>
            <InputText id="name" v-model.trim="user.name" placeholder="نام کامل را وارد کنید" :invalid="!!errText('name')" :aria-invalid="!!errText('name')" autofocus @input="clearError('name')" />
            <small v-if="errText('name')" class="field-error" role="alert"><i class="pi pi-exclamation-circle" />{{ errText('name') }}</small>
          </div>

          <div class="field">
            <label for="mobile" class="field-label"><Phone class="label-icon" />شماره موبایل<span class="req">*</span></label>
            <InputText id="mobile" v-model.trim="user.mobile" dir="ltr" inputmode="tel" placeholder="09123456789" :invalid="!!errText('mobile')" :aria-invalid="!!errText('mobile')" @input="clearError('mobile')" />
            <small v-if="errText('mobile')" class="field-error" role="alert"><i class="pi pi-exclamation-circle" />{{ errText('mobile') }}</small>
          </div>

          <div class="field">
            <label for="national_code" class="field-label"><CreditCard class="label-icon" />کد ملی<span class="req">*</span></label>
            <InputText id="national_code" v-model.trim="user.national_code" dir="ltr" inputmode="numeric" maxlength="10" placeholder="کد ملی ۱۰ رقمی" :invalid="!!errText('national_code')" :aria-invalid="!!errText('national_code')" @input="clearError('national_code')" />
            <small v-if="errText('national_code')" class="field-error" role="alert"><i class="pi pi-exclamation-circle" />{{ errText('national_code') }}</small>
          </div>

          <div class="field">
            <label for="personnel_code" class="field-label"><Briefcase class="label-icon" />کد پرسنلی<span class="req">*</span></label>
            <InputText id="personnel_code" v-model.trim="user.personnel_code" dir="ltr" placeholder="کد پرسنلی" :invalid="!!errText('personnel_code')" :aria-invalid="!!errText('personnel_code')" @input="clearError('personnel_code')" />
            <small v-if="errText('personnel_code')" class="field-error" role="alert"><i class="pi pi-exclamation-circle" />{{ errText('personnel_code') }}</small>
          </div>

          <div v-if="!isEdit" class="field">
            <label for="password" class="field-label"><Lock class="label-icon" />رمز عبور<span class="req">*</span></label>
            <Password
                v-model="user.password"
                inputId="password"
                toggleMask
                :feedback="true"
                promptLabel="رمز عبور را وارد کنید"
                weakLabel="ضعیف"
                mediumLabel="متوسط"
                strongLabel="قوی"
                placeholder="رمز عبور"
                class="w-full"
                inputClass="w-full"
                :invalid="!!errText('password')"
                @update:modelValue="clearError('password')"
            />
            <small v-if="errText('password')" class="field-error" role="alert"><i class="pi pi-exclamation-circle" />{{ errText('password') }}</small>
          </div>
        </div>

        <!-- نقش‌ها -->
        <fieldset class="field" :aria-invalid="!!errText('roles')">
          <legend class="field-label mb-2 w-full justify-between">
            <span class="flex items-center gap-2"><Shield class="label-icon" />نقش‌های کاربر<span class="req">*</span></span>
            <span class="text-xs font-normal text-slate-500">{{ user.roles.length.toLocaleString('fa-IR') }} نقش انتخاب شده</span>
          </legend>

          <div v-if="availableRoles.length" class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label
                v-for="role in availableRoles"
                :key="role.id ?? role.name"
                class="role-card"
                :class="{ 'is-selected': isRoleSelected(role.name) }"
                :title="role.name"
            >
              <input type="checkbox" class="sr-only" :checked="isRoleSelected(role.name)" @change="toggleRole(role.name)" />
              <span class="role-icon"><component :is="roleMeta(role).icon" class="h-5 w-5" /></span>
              <span class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-800">{{ roleLabel(role) }}</span>
              <span class="role-check" aria-hidden="true"><i class="pi pi-check" /></span>
            </label>
          </div>
          <p v-else class="rounded-xl border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">نقشی برای انتخاب وجود ندارد.</p>

          <small v-if="errText('roles')" class="field-error" role="alert"><i class="pi pi-exclamation-circle" />{{ errText('roles') }}</small>
        </fieldset>

        <!-- وضعیت حساب -->
        <div class="field">
          <span class="field-label"><Activity class="label-icon" />وضعیت حساب کاربری</span>
          <label for="is_active" class="status-card" :class="user.is_active ? 'is-on' : 'is-off'">
            <span class="status-icon">
              <UserCheck v-if="user.is_active" class="h-5 w-5" />
              <Lock v-else class="h-5 w-5" />
            </span>
            <span class="min-w-0 flex-1">
              <span class="block text-sm font-semibold text-slate-800">{{ user.is_active ? 'کاربر فعال است' : 'کاربر غیرفعال است' }}</span>
              <span class="block text-xs text-slate-500">{{ user.is_active ? 'کاربر می‌تواند وارد سیستم شود' : 'کاربر امکان ورود به سیستم را ندارد' }}</span>
            </span>
            <Checkbox v-model="user.is_active" :binary="true" inputId="is_active" :disabled="user.employee_type === 'contractor'" />
          </label>
          <small v-if="user.employee_type === 'contractor'" class="mt-1 flex items-center gap-1 text-xs text-amber-600">
            <AlertTriangle class="h-3.5 w-3.5" />پیمانکاران امکان ورود به سیستم ندارند و نمی‌توان آن‌ها را فعال کرد.
          </small>
        </div>
      </div>

      <template #footer>
        <div class="flex w-full flex-col-reverse gap-2 sm:w-auto sm:flex-row sm:justify-end" dir="rtl">
          <Button label="انصراف" icon="pi pi-times" severity="secondary" outlined :disabled="saving" @click="hideDialog" />
          <Button label="ذخیره" icon="pi pi-check" :loading="saving" @click="saveUser" />
        </div>
      </template>
    </Dialog>

    <!-- ═════════ دیالوگ مشاهده ═════════ -->
    <Dialog
        v-model:visible="viewDialog"
        modal
        :draggable="false"
        :pt="dialogPt"
        :breakpoints="{ '992px': '90vw', '576px': '96vw' }"
        style="width: 600px"
    >
      <template #header>
        <div class="flex items-center gap-3" dir="rtl">
          <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><Eye class="h-5 w-5" /></span>
          <div>
            <h2 class="text-lg font-bold text-slate-800">اطلاعات کاربر</h2>
            <p class="text-xs text-slate-500">مشاهده جزئیات حساب کاربری</p>
          </div>
        </div>
      </template>

      <div v-if="selectedUser" class="space-y-5" dir="rtl">
        <div class="profile-card">
          <span class="profile-avatar">{{ getInitials(selectedUser.name) }}</span>
          <div class="min-w-0 flex-1">
            <h3 class="truncate text-lg font-bold text-slate-800">{{ selectedUser.name }}</h3>
            <p class="mt-1 flex items-center gap-2 text-sm text-slate-500"><Mail class="h-4 w-4" />{{ selectedUser.email || 'بدون ایمیل' }}</p>
            <p v-if="selectedUser.job_title || selectedUser.post_title" class="mt-1 text-xs text-slate-500">{{ selectedUser.job_title || selectedUser.post_title }}</p>
          </div>
          <div class="flex flex-col items-end gap-1.5">
            <Tag :value="selectedUser.is_active ? 'فعال' : 'غیرفعال'" :severity="selectedUser.is_active ? 'success' : 'danger'" />
            <Tag :value="selectedUser.employee_type === 'contractor' ? 'پیمانکار' : 'پرسنل'" :severity="selectedUser.employee_type === 'contractor' ? 'warn' : 'success'" />
          </div>
        </div>

        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div class="info-card"><Phone class="info-icon text-blue-600" /><div><dt>شماره موبایل</dt><dd dir="ltr" class="text-right">{{ selectedUser.mobile || '-' }}</dd></div></div>
          <div class="info-card"><CreditCard class="info-icon text-green-600" /><div><dt>کد ملی</dt><dd dir="ltr" class="text-right">{{ selectedUser.national_code || '-' }}</dd></div></div>
          <div class="info-card"><Briefcase class="info-icon text-purple-600" /><div><dt>کد پرسنلی</dt><dd dir="ltr" class="text-right">{{ selectedUser.personnel_code || '-' }}</dd></div></div>
          <div class="info-card"><Building2 class="info-icon text-sky-600" /><div><dt>واحد سازمانی</dt><dd>{{ selectedUser.unit_title || 'بدون واحد سازمانی' }}</dd></div></div>
          <div class="info-card sm:col-span-2"><Calendar class="info-icon text-orange-600" /><div><dt>تاریخ ثبت</dt><dd>{{ formatDate(selectedUser.created_at) }}</dd></div></div>
        </dl>

        <section>
          <h4 class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-600"><Shield class="h-4 w-4 text-blue-500" />نقش‌های کاربر</h4>
          <div v-if="getRoleNames(selectedUser).length" class="flex flex-wrap gap-2">
            <Tag v-for="name in getRoleNames(selectedUser)" :key="name" :severity="roleMeta(name).severity" class="!px-3 !py-1.5">
              <span class="flex items-center gap-1.5"><component :is="roleMeta(name).icon" class="h-3.5 w-3.5" />{{ roleLabel(name) }}</span>
            </Tag>
          </div>
          <span v-else class="text-sm text-slate-400"><i class="pi pi-shield ml-1" />بدون نقش</span>
        </section>
      </div>
    </Dialog>


  </div>
</template>

<style scoped>
.surface {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 1rem;
  box-shadow: 0 1px 2px rgb(15 23 42 / 4%);
}

.clear-btn {
  position: absolute;
  left: 0.6rem;
  top: 50%;
  display: inline-flex;
  width: 26px;
  height: 26px;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  border-radius: 8px;
  transform: translateY(-50%);
}
.clear-btn:hover {
  color: #475569;
  background: #f1f5f9;
}

.filter-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.75rem;
  font-size: 0.8rem;
  color: #1d4ed8;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 9999px;
}
.filter-pill i {
  font-size: 0.65rem;
}
.filter-pill:hover {
  background: #dbeafe;
}

.user-avatar,
.profile-avatar {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-weight: 700;
  background: linear-gradient(135deg, #2563eb, #7c3aed);
}
.user-avatar {
  width: 42px;
  height: 42px;
  font-size: 0.9rem;
  border-radius: 9999px;
  box-shadow: 0 4px 12px rgb(37 99 235 / 20%);
}
.profile-avatar {
  width: 60px;
  height: 60px;
  font-size: 1.3rem;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgb(59 130 246 / 30%);
}

:deep(.users-table .p-datatable-thead > tr > th) {
  font-size: 0.78rem;
  font-weight: 700;
  color: #475569;
  background: #f8fafc;
}
:deep(.users-table .p-datatable-tbody > tr > td) {
  padding-top: 0.8rem;
  padding-bottom: 0.8rem;
}
:deep(.users-table .p-paginator) {
  border-top: 1px solid #f1f5f9;
}

/* فرم */
.field {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 0.4rem;
}
.field-label {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #475569;
}
.label-icon {
  width: 16px;
  height: 16px;
  color: #3b82f6;
}
.req {
  margin-right: 0.15rem;
  color: #ef4444;
}
.field-error {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.78rem;
  color: #dc2626;
}
fieldset.field {
  min-width: 0;
  padding: 0;
  border: 0;
}

/* کارت نقش */
.role-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.7rem 0.8rem;
  cursor: pointer;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
}
.role-card:hover {
  border-color: #93c5fd;
}
.role-card:focus-within {
  outline: 2px solid #93c5fd;
  outline-offset: 1px;
}
.role-card.is-selected {
  background: #eff6ff;
  border-color: #3b82f6;
  box-shadow: 0 0 0 1px #3b82f6 inset;
}
.role-icon {
  display: inline-flex;
  width: 36px;
  height: 36px;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  color: #64748b;
  background: #f1f5f9;
  border-radius: 10px;
}
.role-card.is-selected .role-icon {
  color: #2563eb;
  background: #dbeafe;
}
.role-check {
  display: inline-flex;
  width: 22px;
  height: 22px;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  font-size: 0.65rem;
  color: transparent;
  border: 1.5px solid #cbd5e1;
  border-radius: 9999px;
}
.role-card.is-selected .role-check {
  color: #fff;
  background: #2563eb;
  border-color: #2563eb;
}

/* کارت وضعیت */
.status-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.8rem 1rem;
  cursor: pointer;
  border: 1px solid;
  border-radius: 12px;
}
.status-card.is-on {
  background: #f0fdf4;
  border-color: #bbf7d0;
}
.status-card.is-off {
  background: #fef2f2;
  border-color: #fecaca;
}
.status-icon {
  display: inline-flex;
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
}
.is-on .status-icon {
  color: #16a34a;
  background: #dcfce7;
}
.is-off .status-icon {
  color: #dc2626;
  background: #fee2e2;
}

/* مشاهده */
.profile-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.1rem;
  background: linear-gradient(135deg, #f8fafc, #f1f5f9);
  border: 1px solid #e2e8f0;
  border-radius: 14px;
}
.info-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.8rem 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}
.info-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}
.info-card dt {
  font-size: 0.72rem;
  color: #64748b;
}
.info-card dd {
  margin: 0.15rem 0 0;
  font-size: 0.9rem;
  font-weight: 600;
  color: #1e293b;
}
</style>
