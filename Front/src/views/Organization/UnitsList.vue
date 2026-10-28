<!-- src/views/Organization/UnitsList.vue -->
<template>
  <div class="min-h-screen bg-slate-50 p-4 md:p-6">
    <!-- هدر صفحه -->
    <div class="page-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5 md:p-6 mb-6">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl md:text-3xl font-bold text-gray-800 flex items-center gap-3">
            <Building2 class="w-8 h-8 text-blue-600" />
            مدیریت واحدهای سازمانی
          </h1>
          <p class="text-gray-500 mt-2 text-sm">
            تعریف و مدیریت واحدهای سازمانی و نمایندگان آن‌ها
          </p>
        </div>
        <div class="flex gap-3">
          <Button
              v-if="can('units.create')"
              label="واحد جدید"
              icon="pi pi-plus"
              @click="openNew"
              class="bg-blue-600 hover:bg-blue-700"
          />
        </div>
      </div>
    </div>

    <!-- آمار واحدها -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
            <Building2 class="w-6 h-6 text-blue-600" />
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-800">{{ units.length }}</div>
            <div class="text-sm text-gray-500">کل واحدها</div>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
            <CheckCircle class="w-6 h-6 text-green-600" />
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-800">{{ activeUnitsCount }}</div>
            <div class="text-sm text-gray-500">واحدهای فعال</div>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
            <UserCheck class="w-6 h-6 text-purple-600" />
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-800">{{ unitsWithRepCount }}</div>
            <div class="text-sm text-gray-500">دارای نماینده</div>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
            <AlertTriangle class="w-6 h-6 text-orange-600" />
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-800">{{ unitsWithoutRepCount }}</div>
            <div class="text-sm text-gray-500">بدون نماینده</div>
          </div>
        </div>
      </div>
    </div>

    <!-- جستجو و فیلتر -->
    <div class="page-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5 md:p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
          <div class="relative">
            <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <InputText
                v-model="searchQuery"
                placeholder="جستجو در نام یا کد واحد..."
                class="w-full pr-10"
                @input="onSearchInput"
            />
            <button
                v-if="searchQuery"
                @click="searchQuery = ''"
                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            >
              <X class="w-4 h-4" />
            </button>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
          <Select
              v-model="statusFilter"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="همه وضعیت‌ها"
              class="w-full"
              showClear
              @change="onFilterChange"
          />
        </div>
      </div>
    </div>

    <!-- جدول واحدها -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
      <DataTable
          v-if="can('units.read')"
          :value="filteredUnits"
          :loading="loading"
          lazy
          paginator
          responsiveLayout="scroll"
          :rows="15"
          :totalRecords="filteredUnits.length"
          stripedRows
          class="units-table"
      >
        <template #header>
          <div class="flex justify-between items-center">
            <span class="text-lg font-bold text-gray-800">
              لیست واحدها ({{ filteredUnits.length }} مورد)
            </span>
          </div>
        </template>

        <Column field="name" header="نام واحد" style="min-width: 200px">
          <template #body="slotProps">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <Building2 class="w-5 h-5 text-blue-600" />
              </div>
              <div class="min-w-0">
                <div class="font-semibold text-slate-800 truncate">
                  {{ slotProps.data.name }}
                </div>
                <div class="text-xs text-slate-500 mt-1">
                  {{ slotProps.data.description || 'بدون توضیحات' }}
                </div>
              </div>
            </div>
          </template>
        </Column>

        <Column field="code" header="کد واحد" style="min-width: 120px">
          <template #body="slotProps">
            <Badge
                :value="slotProps.data.code"
                severity="info"
                class="font-mono"
            />
          </template>
        </Column>

        <Column header="نماینده واحد" style="min-width: 200px">
          <template #body="slotProps">
            <div v-if="slotProps.data.representative" class="flex items-center gap-2">
              <Avatar
                  :label="getInitials(slotProps.data.representative.user?.name)"
                  shape="circle"
                  size="small"
                  class="unit-avatar"
              />
              <div class="min-w-0">
                <div class="text-sm font-medium text-slate-800 truncate">
                  {{ slotProps.data.representative.user?.name || '—' }}
                </div>
                <div class="text-xs text-slate-500">
                  {{ slotProps.data.representative.user?.mobile }}
                </div>
              </div>
            </div>
            <div v-else class="flex items-center gap-2 text-gray-400">
              <UserX class="w-4 h-4" />
              <span class="text-sm">بدون نماینده</span>
            </div>
          </template>
        </Column>

        <Column field="is_active" header="وضعیت" style="min-width: 110px">
          <template #body="slotProps">
            <Tag
                :value="slotProps.data.is_active ? 'فعال' : 'غیرفعال'"
                :severity="slotProps.data.is_active ? 'success' : 'danger'"
            />
          </template>
        </Column>

        <Column header="تعداد قراردادها" style="min-width: 120px">
          <template #body="slotProps">
            <div class="flex items-center gap-2">
              <FileText class="w-4 h-4 text-gray-400" />
              <span class="text-sm font-medium">
                {{ slotProps.data.contracts_count || 0 }}
              </span>
            </div>
          </template>
        </Column>

        <Column header="عملیات" style="min-width: 200px" frozen alignFrozen="left">
          <template #body="slotProps">
            <div class="flex gap-2">
              <Button
                  v-if="can('units.assign_representative')"
                  icon="pi pi-user-plus"
                  class="p-button-rounded p-button-info p-button-text"
                  @click="openRepresentativeDialog(slotProps.data)"
                  tooltip="تعیین نماینده"
              />
              <Button
                  v-if="can('units.update')"
                  icon="pi pi-pencil"
                  class="p-button-rounded p-button-success p-button-text"
                  @click="editUnit(slotProps.data)"
                  tooltip="ویرایش"
              />
              <Button
                  v-if="can('units.delete')"
                  icon="pi pi-trash"
                  class="p-button-rounded p-button-danger p-button-text"
                  @click="confirmDelete(slotProps.data)"
                  tooltip="حذف"
              />
            </div>
          </template>
        </Column>
      </DataTable>

      <div v-else class="p-12 text-center">
        <Lock class="w-16 h-16 text-gray-300 mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-gray-600 mb-2">دسترسی محدود</h3>
        <p class="text-gray-500">شما مجاز به مشاهده لیست واحدها نیستید</p>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         دیالوگ ایجاد/ویرایش واحد
         ═══════════════════════════════════════════════════════ -->
    <Dialog
        v-model:visible="unitDialog"
        :header="isEdit ? 'ویرایش واحد' : 'واحد سازمانی جدید'"
        :modal="true"
        :closable="true"
        :draggable="false"
        class="unit-dialog-modern"
        :breakpoints="{ '992px': '90vw', '576px': '95vw' }"
        style="width: 520px"
    >
      <template #header>
        <div class="dialog-header-custom">
          <div class="header-icon-wrapper">
            <Building2 class="header-icon" />
          </div>
          <div class="header-text">
            <h2 class="header-title">{{ isEdit ? 'ویرایش واحد' : 'واحد سازمانی جدید' }}</h2>
            <p class="header-subtitle">{{ isEdit ? 'ویرایش اطلاعات واحد' : 'ایجاد واحد سازمانی جدید' }}</p>
          </div>
        </div>
      </template>

      <div class="dialog-body">
        <div class="form-grid">
          <div class="form-group full-width">
            <label for="name" class="form-label">
              <Building2 class="label-icon" />
              نام واحد
              <span class="required">*</span>
            </label>
            <InputText
                id="name"
                v-model.trim="form.name"
                required
                autofocus
                :class="{ 'p-invalid': errors.name }"
                placeholder="مثال: واحد فناوری اطلاعات"
                class="form-input"
            />
            <small class="error-message" v-if="errors.name">{{ errors.name }}</small>
          </div>

          <div class="form-group full-width">
            <label for="code" class="form-label">
              <Hash class="label-icon" />
              کد واحد
              <span class="required">*</span>
            </label>
            <InputText
                id="code"
                v-model.trim="form.code"
                required
                dir="ltr"
                :class="{ 'p-invalid': errors.code }"
                placeholder="مثال: IT"
                class="form-input"
            />
            <small class="error-message" v-if="errors.code">{{ errors.code }}</small>
          </div>

          <div class="form-group full-width">
            <label for="description" class="form-label">
              <FileText class="label-icon" />
              توضیحات
            </label>
            <Textarea
                id="description"
                v-model="form.description"
                rows="3"
                placeholder="توضیحات مربوط به واحد..."
                class="form-input"
            />
          </div>

          <div class="form-group full-width">
            <label class="form-label">
              <Activity class="label-icon" />
              وضعیت واحد
            </label>
            <div class="status-card" :class="{ 'is-active': form.is_active, 'is-inactive': !form.is_active }">
              <div class="status-info">
                <div class="status-icon-wrapper" :class="form.is_active ? 'active' : 'inactive'">
                  <CheckCircle v-if="form.is_active" class="status-icon" />
                  <XCircle v-else class="status-icon" />
                </div>
                <div class="status-text">
                  <div class="status-title">
                    {{ form.is_active ? 'واحد فعال است' : 'واحد غیرفعال است' }}
                  </div>
                  <div class="status-description">
                    {{ form.is_active ? 'کاربران می‌توانند برای این واحد قرارداد ثبت کنند' : 'امکان ثبت قرارداد برای این واحد وجود ندارد' }}
                  </div>
                </div>
              </div>
              <Checkbox
                  v-model="form.is_active"
                  :binary="true"
                  inputId="is_active"
                  class="status-checkbox"
              />
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <Button
              label="انصراف"
              icon="pi pi-times"
              severity="secondary"
              outlined
              @click="unitDialog = false"
              class="btn-cancel"
          />
          <Button
              label="ذخیره"
              icon="pi pi-check"
              @click="saveUnit"
              :loading="saving"
              class="btn-save"
          />
        </div>
      </template>
    </Dialog>

    <!-- ═══════════════════════════════════════════════════════
         دیالوگ تعیین نماینده
         ═══════════════════════════════════════════════════════ -->
    <Dialog
        v-model:visible="representativeDialog"
        header="تعیین نماینده واحد"
        :modal="true"
        :closable="true"
        :draggable="false"
        class="unit-dialog-modern"
        :breakpoints="{ '992px': '90vw', '576px': '95vw' }"
        style="width: 520px"
    >
      <template #header>
        <div class="dialog-header-custom">
          <div class="header-icon-wrapper view">
            <UserCheck class="header-icon" />
          </div>
          <div class="header-text">
            <h2 class="header-title">تعیین نماینده</h2>
            <p class="header-subtitle">
              {{ selectedUnit?.name }}
            </p>
          </div>
        </div>
      </template>

      <div class="dialog-body">
        <div v-if="selectedUnit" class="info-banner">
          <div class="info-banner-icon">
            <Building2 class="w-5 h-5" />
          </div>
          <div class="info-banner-content">
            <div class="info-banner-title">{{ selectedUnit.name }}</div>
            <div class="info-banner-subtitle">
              کد واحد: <strong>{{ selectedUnit.code }}</strong>
            </div>
          </div>
        </div>

        <div class="form-group full-width">
          <label for="representative" class="form-label">
            <UserCheck class="label-icon" />
            انتخاب نماینده
            <span class="required">*</span>
          </label>

          <div class="relative">
            <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 z-10" />
            <InputText
                v-model="repSearchQuery"
                placeholder="جستجو در نام یا موبایل کاربر..."
                class="w-full pr-10"
                @input="searchUsers"
            />
          </div>

          <div class="users-dropdown" v-if="repSearchResults.length > 0">
            <div
                v-for="user in repSearchResults"
                :key="user.id"
                class="user-option"
                :class="{ 'is-selected': form.representative_id === user.id }"
                @click="selectRepresentative(user)"
            >
              <Avatar
                  :label="getInitials(user.name)"
                  shape="circle"
                  size="small"
                  class="user-option-avatar"
              />
              <div class="user-option-info">
                <div class="user-option-name">{{ user.name }}</div>
                <div class="user-option-mobile" dir="ltr">{{ user.mobile }}</div>
              </div>
              <CheckCircle v-if="form.representative_id === user.id" class="w-5 h-5 text-blue-600" />
            </div>
          </div>

          <div v-if="form.representative_id" class="selected-rep-card">
            <div class="selected-rep-info">
              <Avatar
                  :label="getInitials(selectedRepName)"
                  shape="circle"
                  size="large"
                  class="selected-rep-avatar"
              />
              <div>
                <div class="font-semibold text-slate-800">{{ selectedRepName }}</div>
                <div class="text-sm text-slate-500" dir="ltr">{{ selectedRepMobile }}</div>
              </div>
            </div>
            <Button
                icon="pi pi-times"
                severity="danger"
                text
                rounded
                size="small"
                @click="form.representative_id = null"
            />
          </div>

          <small class="error-message" v-if="errors.representative_id">
            {{ errors.representative_id }}
          </small>
          <small class="hint-text">
            <Info class="w-3 h-3 inline" />
            نماینده واحد می‌تواند برای این واحد درخواست قرارداد ثبت کند
          </small>
        </div>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <Button
              label="انصراف"
              icon="pi pi-times"
              severity="secondary"
              outlined
              @click="representativeDialog = false"
              class="btn-cancel"
          />
          <Button
              label="ذخیره نماینده"
              icon="pi pi-check"
              @click="saveRepresentative"
              :loading="saving"
              class="btn-save"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useApiErrorHandler } from '@/composables/useApiErrorHandler'
import api from '@/api/axios.js'
import { useAuthStore } from '@/stores/auth.js'
import {
  Building2, UserCheck, UserX, Search, X, CheckCircle, XCircle,
  FileText, Hash, Activity, Info, Lock
} from 'lucide-vue-next'

const toast = useToast()
const confirm = useConfirm()
const { handleApiError } = useApiErrorHandler()
const authStore = useAuthStore()

const can = (permission) => authStore.can(permission)

// State
const units = ref([])
const loading = ref(false)
const saving = ref(false)
const unitDialog = ref(false)
const representativeDialog = ref(false)
const isEdit = ref(false)
const selectedUnit = ref(null)
const searchQuery = ref('')
const statusFilter = ref(null)

const statusOptions = [
  { label: 'فعال', value: true },
  { label: 'غیرفعال', value: false },
]

const form = ref({
  id: null,
  name: '',
  code: '',
  description: '',
  is_active: true,
  representative_id: null,
})

const errors = ref({})

const repSearchQuery = ref('')
const repSearchResults = ref([])
const repSearchTimeout = ref(null)

// Computed
const filteredUnits = computed(() => {
  let result = units.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase().trim()
    result = result.filter(u =>
        u.name?.toLowerCase().includes(query) ||
        u.code?.toLowerCase().includes(query)
    )
  }

  if (statusFilter.value !== null && statusFilter.value !== undefined) {
    result = result.filter(u => u.is_active === statusFilter.value)
  }

  return result
})

const activeUnitsCount = computed(() => units.value.filter(u => u.is_active).length)
const unitsWithRepCount = computed(() => units.value.filter(u => u.representative).length)
const unitsWithoutRepCount = computed(() => units.value.filter(u => !u.representative).length)

const selectedRepName = computed(() => {
  if (!form.value.representative_id) return ''
  const user = repSearchResults.value.find(u => u.id === form.value.representative_id)
  return user?.name || ''
})

const selectedRepMobile = computed(() => {
  if (!form.value.representative_id) return ''
  const user = repSearchResults.value.find(u => u.id === form.value.representative_id)
  return user?.mobile || ''
})

// Methods
const loadUnits = async () => {
  loading.value = true
  try {
    const response = await api.get('/organizational-units')
    units.value = response.data.data || response.data || []
  } catch (error) {
    handleApiError(error)
  } finally {
    loading.value = false
  }
}

const onSearchInput = () => {
  // فیلتر سمت کلاینت
}

const onFilterChange = () => {
  // فیلتر سمت کلاینت
}

const openNew = () => {
  if (!can('units.create')) {
    toast.add({ severity: 'warn', summary: 'اطلاع', detail: 'شما مجاز به این عمل نیستید', life: 3000 })
    return
  }
  form.value = {
    id: null,
    name: '',
    code: '',
    description: '',
    is_active: true,
    representative_id: null,
  }
  errors.value = {}
  isEdit.value = false
  unitDialog.value = true
}

const editUnit = (unit) => {
  if (!can('units.update')) {
    toast.add({ severity: 'warn', summary: 'اطلاع', detail: 'شما مجاز به این عمل نیستید', life: 3000 })
    return
  }
  form.value = {
    id: unit.id,
    name: unit.name,
    code: unit.code,
    description: unit.description || '',
    is_active: unit.is_active,
    representative_id: unit.representative?.user?.id || null,
  }
  errors.value = {}
  isEdit.value = true
  unitDialog.value = true
}

const validateForm = () => {
  errors.value = {}
  let isValid = true

  if (!form.value.name?.trim()) {
    errors.value.name = 'نام واحد الزامی است'
    isValid = false
  }

  if (!form.value.code?.trim()) {
    errors.value.code = 'کد واحد الزامی است'
    isValid = false
  } else if (!/^[A-Z0-9_-]{2,20}$/i.test(form.value.code)) {
    errors.value.code = 'کد واحد باید 2 تا 20 کاراکتر و فقط شامل حروف، اعداد، خط تیره و زیرخط باشد'
    isValid = false
  }

  return isValid
}

const saveUnit = async () => {
  if (!validateForm()) return

  saving.value = true
  errors.value = {}

  try {
    if (isEdit.value) {
      await api.put(`/organizational-units/${form.value.id}`, form.value)
      toast.add({ severity: 'success', summary: 'موفق', detail: 'واحد با موفقیت ویرایش شد', life: 3000 })
    } else {
      await api.post('/organizational-units', form.value)
      toast.add({ severity: 'success', summary: 'موفق', detail: 'واحد جدید با موفقیت ایجاد شد', life: 3000 })
    }
    unitDialog.value = false
    await loadUnits()
  } catch (error) {
    handleApiError(error, {
      setErrors: (validationErrors) => {
        errors.value = validationErrors
      }
    })
  } finally {
    saving.value = false
  }
}

const confirmDelete = (unit) => {
  confirm.require({
    message: `آیا از حذف واحد "${unit.name}" اطمینان دارید؟`,
    header: 'تایید حذف',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'بله، حذف کن',
    rejectLabel: 'خیر',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/organizational-units/${unit.id}`)
        toast.add({ severity: 'success', summary: 'موفق', detail: 'واحد حذف شد', life: 3000 })
        await loadUnits()
      } catch (error) {
        handleApiError(error)
      }
    }
  })
}

const openRepresentativeDialog = (unit) => {
  if (!can('units.assign_representative')) {
    toast.add({ severity: 'warn', summary: 'اطلاع', detail: 'شما مجاز به این عمل نیستید', life: 3000 })
    return
  }
  selectedUnit.value = unit
  form.value.representative_id = unit.representative?.user?.id || null
  repSearchQuery.value = ''
  repSearchResults.value = []
  representativeDialog.value = true
}

const searchUsers = () => {
  clearTimeout(repSearchTimeout.value)
  repSearchTimeout.value = setTimeout(async () => {
    if (!repSearchQuery.value || repSearchQuery.value.length < 2) {
      repSearchResults.value = []
      return
    }

    try {
      const response = await api.get('/users', {
        params: { search: repSearchQuery.value, per_page: 10 }
      })
      const rawUsers = response.data.data || []
      repSearchResults.value = rawUsers.map(item => {
        const nestedUser = item.user ?? item
        return {
          id: nestedUser.id ?? item.id,
          name: nestedUser.name || '',
          mobile: nestedUser.mobile || '',
        }
      })
    } catch (error) {
      console.error('خطا در جستجوی کاربران:', error)
    }
  }, 300)
}

const selectRepresentative = (user) => {
  form.value.representative_id = user.id
}

const saveRepresentative = async () => {
  if (!form.value.representative_id) {
    errors.value.representative_id = 'انتخاب نماینده الزامی است'
    return
  }

  saving.value = true
  errors.value = {}

  try {
    await api.post(`/organizational-units/${selectedUnit.value.id}/representative`, {
      user_id: form.value.representative_id
    })
    toast.add({ severity: 'success', summary: 'موفق', detail: 'نماینده با موفقیت تعیین شد', life: 3000 })
    representativeDialog.value = false
    await loadUnits()
  } catch (error) {
    handleApiError(error, {
      setErrors: (validationErrors) => {
        errors.value = validationErrors
      }
    })
  } finally {
    saving.value = false
  }
}

const getInitials = (name) => {
  if (!name) return '?'
  const parts = name.split(' ')
  return parts.length >= 2 ? `${parts[0][0]}${parts[1][0]}` : parts[0][0]
}

onMounted(() => {
  loadUnits()
})
</script>

<style scoped>
.units-table {
  border: 0;
}

.unit-avatar {
  background: linear-gradient(135deg, #2563eb, #7c3aed) !important;
  color: #fff !important;
  font-weight: 700;
  box-shadow: 0 5px 14px rgba(37, 99, 235, 0.18);
  flex-shrink: 0;
}

:deep(.units-table .p-datatable-tbody > tr > td) {
  padding: 0.9rem 0.85rem;
  vertical-align: middle;
}

:deep(.units-table .p-datatable-thead > tr > th) {
  padding: 0.9rem 0.85rem;
  font-size: 0.78rem;
  white-space: nowrap;
  border-color: #e2e8f0;
  background: #f3f4f6;
  font-weight: 600;
  color: #374151;
}

:deep(.units-table .p-datatable-tbody > tr) {
  border-color: #eef2f7;
  transition: background-color 0.2s;
}

:deep(.units-table .p-datatable-tbody > tr:hover) {
  background: #f9fafb;
}

:deep(.units-table .p-paginator) {
  border-top: 1px solid #eef2f7;
  padding: 0.75rem 1rem;
  background: #fff;
}

/* Dialog Styles */
:deep(.unit-dialog-modern .p-dialog-header) {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #e2e8f0;
  padding: 1.25rem 1.5rem;
}

:deep(.unit-dialog-modern .p-dialog-content) {
  padding: 1.5rem;
  background: #ffffff;
}

:deep(.unit-dialog-modern .p-dialog-footer) {
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  padding: 1rem 1.5rem;
}

.dialog-header-custom {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-icon-wrapper {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.header-icon-wrapper.view {
  background: linear-gradient(135deg, #10b981, #059669);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.header-icon {
  width: 24px;
  height: 24px;
  color: white;
}

.header-text {
  flex: 1;
}

.header-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.3;
}

.header-subtitle {
  margin: 0.25rem 0 0;
  font-size: 0.875rem;
  color: #64748b;
}

.dialog-body {
  max-height: 60vh;
  overflow-y: auto;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group.full-width {
  grid-column: span 2;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
}

.label-icon {
  width: 16px;
  height: 16px;
  color: #3b82f6;
}

.required {
  color: #ef4444;
  margin-right: 0.25rem;
}

.form-input {
  width: 100%;
}

.error-message {
  color: #ef4444;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.hint-text {
  color: #64748b;
  font-size: 0.75rem;
  margin-top: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

/* Status Card */
.status-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  border-radius: 10px;
  border: 2px solid;
  transition: all 0.2s ease;
}

.status-card.is-active {
  background: #f0fdf4;
  border-color: #86efac;
}

.status-card.is-inactive {
  background: #fef2f2;
  border-color: #fecaca;
}

.status-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.status-icon-wrapper {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
}

.status-icon-wrapper.active {
  background: #dcfce7;
}

.status-icon-wrapper.inactive {
  background: #fee2e2;
}

.status-icon {
  width: 20px;
  height: 20px;
}

.status-icon-wrapper.active .status-icon {
  color: #16a34a;
}

.status-icon-wrapper.inactive .status-icon {
  color: #dc2626;
}

.status-text {
  flex: 1;
}

.status-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.25rem;
}

.status-description {
  font-size: 0.75rem;
  color: #64748b;
}

/* Representative */
.info-banner {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border: 1px solid #bfdbfe;
  border-radius: 12px;
  margin-bottom: 1.25rem;
}

.info-banner-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #3b82f6;
  color: white;
  border-radius: 10px;
}

.info-banner-content {
  flex: 1;
}

.info-banner-title {
  font-weight: 700;
  color: #1e293b;
  font-size: 0.95rem;
}

.info-banner-subtitle {
  font-size: 0.8rem;
  color: #64748b;
  margin-top: 0.15rem;
}

.users-dropdown {
  margin-top: 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  max-height: 240px;
  overflow-y: auto;
  background: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.user-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  cursor: pointer;
  transition: background 0.15s;
  border-bottom: 1px solid #f1f5f9;
}

.user-option:last-child {
  border-bottom: none;
}

.user-option:hover {
  background: #f8fafc;
}

.user-option.is-selected {
  background: #eff6ff;
}

.user-option-avatar {
  background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
  color: white !important;
  font-weight: 700;
}

.user-option-info {
  flex: 1;
  min-width: 0;
}

.user-option-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
}

.user-option-mobile {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.1rem;
}

.selected-rep-card {
  margin-top: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 10px;
}

.selected-rep-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.selected-rep-avatar {
  background: linear-gradient(135deg, #10b981, #059669) !important;
  color: white !important;
  font-weight: 700;
}

/* Footer */
.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.btn-cancel {
  min-width: 100px;
}

.btn-save {
  min-width: 120px;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border: none;
}

.btn-save:hover {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  .form-group.full-width {
    grid-column: span 1;
  }
  .dialog-footer {
    flex-direction: column-reverse;
  }
  .btn-cancel, .btn-save {
    width: 100%;
  }
}
</style>