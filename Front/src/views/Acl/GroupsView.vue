<template>
  <div class="min-h-screen bg-slate-50 p-4 md:p-6">

    <!-- ═══════════════ هدر صفحه ═══════════════ -->
    <div class="page-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5 md:p-6 mb-6">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl md:text-3xl font-bold text-gray-800 flex items-center gap-3">
            <UsersRound class="w-8 h-8 text-indigo-600" />
            گروه‌های کاربری
          </h1>
          <p class="text-gray-500 mt-2 text-sm">
            مدیریت گروه‌ها و اختصاص نقش‌ها به هر گروه
          </p>
        </div>
        <div class="flex gap-3">
          <Button
              v-if="can('groups.create')"
              label="گروه جدید"
              icon="pi pi-plus"
              @click="openNew"
              class="bg-indigo-600 hover:bg-indigo-700"
          />
        </div>
      </div>
    </div>

    <!-- ═══════════════ آمار ═══════════════ -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <StatCard
          icon="pi pi-users"
          iconBg="bg-indigo-100"
          iconColor="text-indigo-600"
          :value="groups.length"
          label="کل گروه‌ها"
      />
      <StatCard
          icon="pi pi-user-plus"
          iconBg="bg-green-100"
          iconColor="text-green-600"
          :value="totalMembers"
          label="کل اعضا"
      />
      <StatCard
          icon="pi pi-shield"
          iconBg="bg-purple-100"
          iconColor="text-purple-600"
          :value="totalRoles"
          label="نقش‌های اختصاص‌یافته"
      />
      <StatCard
          icon="pi pi-check-circle"
          iconBg="bg-blue-100"
          iconColor="text-blue-600"
          :value="activeGroupsCount"
          label="گروه‌های فعال"
      />
    </div>

    <!-- ═══════════════ جستجو ═══════════════ -->
    <div class="page-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5 md:p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
          <div class="relative">
            <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <InputText
                v-model="searchQuery"
                placeholder="جستجو بر اساس نام گروه..."
                class="w-full pr-10"
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
          <label class="block text-sm font-medium text-gray-700 mb-2">فیلتر وضعیت</label>
          <Select
              v-model="statusFilter"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="همه"
              class="w-full"
              showClear
          />
        </div>
      </div>
    </div>

    <!-- ═══════════════ جدول گروه‌ها ═══════════════ -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
      <DataTable
          v-if="can('groups.read')"
          :value="filteredGroups"
          :rows="10"
          paginator
          responsiveLayout="scroll"
          stripedRows
          class="groups-table"
          :loading="loading"
      >
        <template #header>
          <div class="flex justify-between items-center">
            <span class="text-lg font-bold text-gray-800">
              لیست گروه‌ها ({{ filteredGroups.length }})
            </span>
          </div>
        </template>

        <Column header="گروه" style="min-width: 220px">
          <template #body="slotProps">
            <div class="flex items-center gap-3">
              <div
                  class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold"
                  :style="{ background: slotProps.data.color || '#3B82F6' }"
              >
                {{ getInitials(slotProps.data.title) }}
              </div>
              <div class="min-w-0">
                <div class="font-semibold text-slate-800 truncate">
                  {{ slotProps.data.title }}
                </div>
                <div class="text-xs text-slate-500 mt-1 font-mono" dir="ltr">
                  {{ slotProps.data.name }}
                </div>
              </div>
            </div>
          </template>
        </Column>

        <Column header="توضیحات" style="min-width: 200px">
          <template #body="slotProps">
            <span class="text-sm text-slate-600">
              {{ slotProps.data.description || '—' }}
            </span>
          </template>
        </Column>

        <Column header="نقش‌ها" style="min-width: 180px">
          <template #body="slotProps">
            <div class="flex flex-wrap gap-1">
              <Tag
                  v-for="role in (slotProps.data.roles || []).slice(0, 2)"
                  :key="role.id"
                  :value="role.name"
                  severity="info"
                  class="text-xs"
              />
              <Tag
                  v-if="(slotProps.data.roles || []).length > 2"
                  :value="`+${slotProps.data.roles.length - 2}`"
                  severity="secondary"
                  class="text-xs"
              />
              <span
                  v-if="!slotProps.data.roles?.length"
                  class="text-xs text-slate-400"
              >
                بدون نقش
              </span>
            </div>
          </template>
        </Column>

        <Column header="اعضا" style="min-width: 100px">
          <template #body="slotProps">
            <Badge
                :value="slotProps.data.users_count || 0"
                :severity="slotProps.data.users_count > 0 ? 'success' : 'secondary'"
            />
          </template>
        </Column>

        <Column header="دسترسی‌ها" style="min-width: 100px">
          <template #body="slotProps">
            <Badge
                :value="slotProps.data.permissions_count || 0"
                severity="info"
            />
          </template>
        </Column>

        <Column header="وضعیت" style="min-width: 100px">
          <template #body="slotProps">
            <Tag
                :value="slotProps.data.is_active ? 'فعال' : 'غیرفعال'"
                :severity="slotProps.data.is_active ? 'success' : 'danger'"
            />
          </template>
        </Column>

        <Column header="عملیات" style="min-width: 200px" frozen alignFrozen="right">
          <template #body="slotProps">
            <div class="flex gap-1">
              <Button
                  v-if="can('groups.read')"
                  icon="pi pi-users"
                  class="p-button-rounded p-button-text p-button-info"
                  v-tooltip.top="'مدیریت اعضا'"
                  @click="openMembersDialog(slotProps.data)"
              />
              <Button
                  v-if="can('groups.update')"
                  icon="pi pi-pencil"
                  class="p-button-rounded p-button-text p-button-success"
                  v-tooltip.top="'ویرایش'"
                  @click="editGroup(slotProps.data)"
              />
              <Button
                  v-if="can('groups.delete')"
                  icon="pi pi-trash"
                  class="p-button-rounded p-button-text p-button-danger"
                  v-tooltip.top="'حذف'"
                  @click="confirmDelete(slotProps.data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>

      <div v-else class="p-12 text-center">
        <Lock class="w-16 h-16 text-gray-300 mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-gray-600 mb-2">دسترسی محدود</h3>
        <p class="text-gray-500">شما مجاز به مشاهده گروه‌ها نیستید</p>
      </div>
    </div>

    <!-- ═══════════════ Dialog ایجاد/ویرایش گروه ═══════════════ -->
    <Dialog
        v-model:visible="groupDialog"
        :header="isEdit ? 'ویرایش گروه' : 'گروه جدید'"
        :modal="true"
        :closable="true"
        :draggable="false"
        class="group-dialog-modern"
        :breakpoints="{ '992px': '90vw', '576px': '95vw' }"
        style="width: 640px"
    >
      <template #header>
        <div class="dialog-header-custom">
          <div class="header-icon-wrapper">
            <UsersRound class="header-icon" />
          </div>
          <div class="header-text">
            <h2 class="header-title">{{ isEdit ? 'ویرایش گروه' : 'گروه جدید' }}</h2>
            <p class="header-subtitle">
              {{ isEdit ? 'ویرایش اطلاعات گروه' : 'ایجاد گروه کاربری جدید' }}
            </p>
          </div>
        </div>
      </template>

      <div class="dialog-body">
        <div class="form-grid">
          <!-- نام فارسی -->
          <div class="form-group">
            <label class="form-label">
              <span>نام نمایشی</span>
              <span class="required">*</span>
            </label>
            <InputText
                v-model.trim="group.title"
                placeholder="مثلاً: تیم فنی"
                :class="{ 'p-invalid': errors.title }"
            />
            <small class="error-message" v-if="errors.title">{{ errors.title }}</small>
          </div>

          <!-- نام انگلیسی -->
          <div class="form-group">
            <label class="form-label">
              <span>نام سیستمی (انگلیسی)</span>
              <span class="required">*</span>
            </label>
            <InputText
                v-model.trim="group.name"
                dir="ltr"
                placeholder="technical_team"
                :class="{ 'p-invalid': errors.name }"
                :disabled="isEdit"
            />
            <small class="error-message" v-if="errors.name">{{ errors.name }}</small>
          </div>

          <!-- رنگ -->
          <div class="form-group">
            <label class="form-label">رنگ گروه</label>
            <div class="flex items-center gap-2">
              <input
                  type="color"
                  v-model="group.color"
                  class="w-12 h-10 rounded-lg border border-gray-300 cursor-pointer"
              />
              <InputText
                  v-model="group.color"
                  dir="ltr"
                  placeholder="#3B82F6"
                  class="flex-1"
              />
            </div>
          </div>

          <!-- ترتیب -->
          <div class="form-group">
            <label class="form-label">ترتیب نمایش</label>
            <InputNumber
                v-model="group.sort_order"
                :min="0"
                placeholder="0"
                class="w-full"
            />
          </div>

          <!-- توضیحات -->
          <div class="form-group full-width">
            <label class="form-label">توضیحات</label>
            <Textarea
                v-model="group.description"
                rows="2"
                placeholder="توضیح مختصر درباره این گروه..."
            />
          </div>

          <!-- نقش‌ها -->
          <div class="form-group full-width">
            <label class="form-label">
              <Shield class="label-icon" />
              نقش‌های گروه
            </label>
            <div class="roles-grid">
              <div
                  v-for="role in allRoles"
                  :key="role.id"
                  class="role-card"
                  :class="{ 'is-selected': group.role_ids.includes(role.id) }"
                  @click="toggleRole(role.id)"
              >
                <input
                    type="checkbox"
                    :checked="group.role_ids.includes(role.id)"
                    class="role-checkbox"
                    @click.stop
                />
                <div class="role-content">
                  <div class="role-icon-wrapper">
                    <Shield class="role-icon" />
                  </div>
                  <label class="role-label">{{ role.name }}</label>
                </div>
              </div>
            </div>
            <small class="text-xs text-slate-500 mt-2">
              💡 اعضای این گروه به‌طور خودکار این نقش‌ها را دریافت می‌کنند.
            </small>
          </div>

          <!-- وضعیت -->
          <div class="form-group full-width">
            <label class="form-label">
              <Activity class="label-icon" />
              وضعیت گروه
            </label>
            <div
                class="status-card"
                :class="{ 'is-active': group.is_active, 'is-inactive': !group.is_active }"
            >
              <div class="status-info">
                <div class="status-icon-wrapper" :class="group.is_active ? 'active' : 'inactive'">
                  <UserCheck v-if="group.is_active" class="status-icon" />
                  <Lock v-else class="status-icon" />
                </div>
                <div class="status-text">
                  <div class="status-title">
                    {{ group.is_active ? 'گروه فعال است' : 'گروه غیرفعال است' }}
                  </div>
                  <div class="status-description">
                    {{ group.is_active
                      ? 'نقش‌های گروه به اعضا اعمال می‌شود'
                      : 'نقش‌های گروه به اعضا اعمال نمی‌شود' }}
                  </div>
                </div>
              </div>
              <Checkbox
                  v-model="group.is_active"
                  :binary="true"
                  inputId="is_active"
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
              @click="hideDialog"
          />
          <Button
              label="ذخیره"
              icon="pi pi-check"
              @click="saveGroup"
              :loading="saving"
              class="btn-save"
          />
        </div>
      </template>
    </Dialog>

    <!-- ═══════════════ Dialog مدیریت اعضا (نسخه پیشرفته) ═══════════════ -->
    <Dialog
        v-model:visible="membersDialog"
        :modal="true"
        :closable="true"
        :draggable="false"
        class="members-dialog"
        :breakpoints="{ '992px': '90vw', '576px': '95vw' }"
        style="width: 900px"
    >
      <template #header>
        <div class="dialog-header-custom">
          <div class="header-icon-wrapper members">
            <Users class="header-icon" />
          </div>
          <div class="header-text">
            <h2 class="header-title">مدیریت اعضای گروه</h2>
            <p class="header-subtitle" v-if="selectedGroup">
              گروه: <strong>{{ selectedGroup.title }}</strong>
            </p>
          </div>
        </div>
      </template>

      <div v-if="selectedGroup" class="members-body">

        <!-- ═══════ بخش فیلترها ═══════ -->
        <div class="filters-section">
          <div class="filters-grid">
            <!-- فیلتر سمت (پرکاربرد) -->
            <div class="filter-item">
              <label class="filter-label">
                <Briefcase class="filter-icon" />
                فیلتر بر اساس سمت
              </label>
              <AutoComplete
                  v-model="positionFilter"
                  :suggestions="positionSuggestions"
                  @complete="searchPositions"
                  placeholder="مثلاً: کارشناس، مدیر، سرپرست..."
                  class="w-full"
                  :forceSelection="false"
                  :dropdown="false"
                  @item-select="onPositionSelect"
                  @keyup.enter="loadSelectableUsers"
              />
            </div>

            <!-- فیلتر واحد سازمانی -->
            <div class="filter-item">
              <label class="filter-label">
                <Building2 class="filter-icon" />
                واحد سازمانی
              </label>
              <Select
                  v-model="unitFilter"
                  :options="availableUnits"
                  optionLabel="title"
                  optionValue="id"
                  placeholder="همه واحدها"
                  class="w-full"
                  showClear
                  :filter="true"
                  @change="loadSelectableUsers"
              />
            </div>

            <!-- جستجوی عمومی -->
            <div class="filter-item">
              <label class="filter-label">
                <Search class="filter-icon" />
                جستجو در کاربران
              </label>
              <InputText
                  v-model="userSearch"
                  placeholder="نام، موبایل، کد پرسنلی..."
                  class="w-full"
                  @input="onUserSearchInput"
              />
            </div>
          </div>

          <!-- دکمه اعمال فیلتر -->
          <div class="filters-actions">
            <Button
                label="اعمال فیلتر"
                icon="pi pi-filter"
                size="small"
                :loading="loadingUsers"
                @click="loadSelectableUsers"
            />
            <Button
                label="پاک کردن فیلترها"
                icon="pi pi-filter-slash"
                severity="secondary"
                text
                size="small"
                @click="clearFilters"
            />
            <span v-if="hasActiveFilters" class="filter-badge">
          فیلتر فعال
        </span>
          </div>
        </div>

        <!-- ═══════ انتخاب کاربران ═══════ -->
        <div class="select-users-section">
          <label class="form-label">
            <UserPlus class="label-icon" />
            انتخاب کاربران برای افزودن
            <small class="text-xs text-slate-500 mr-2">
              ({{ filteredSelectableUsers.length }} نفر یافت شد)
            </small>
          </label>

          <MultiSelect
              v-model="selectedUsersToAdd"
              :options="filteredSelectableUsers"
              optionLabel="display_label"
              optionValue="id"
              placeholder="کاربران مورد نظر را انتخاب کنید..."
              :filter="true"
              :loading="loadingUsers"
              class="w-full users-multiselect"
              display="chip"
              :maxSelectedLabels="5"
              :selectedItemsLabel="'{0} کاربر انتخاب شده'"
          >
            <template #option="slotProps">
              <div class="user-option">
                <Avatar
                    :label="getInitials(slotProps.option.name)"
                    shape="circle"
                    size="small"
                    class="user-option-avatar"
                />
                <div class="user-option-info">
                  <div class="user-option-name">{{ slotProps.option.name }}</div>
                  <div class="user-option-meta">
                <span class="user-option-position">
                  <Briefcase class="meta-icon" />
                  {{ slotProps.option.post_title || slotProps.option.job_title || 'بدون سمت' }}
                </span>
                    <span class="user-option-unit" v-if="slotProps.option.unit_title">
                  <Building2 class="meta-icon" />
                  {{ slotProps.option.unit_title }}
                </span>
                    <span class="user-option-mobile" dir="ltr">
                  <Phone class="meta-icon" />
                  {{ slotProps.option.mobile }}
                </span>
                  </div>
                </div>
              </div>
            </template>

            <template #chip="slotProps">
              <div class="user-chip">
                <Avatar
                    :label="getInitials(slotProps.value.name)"
                    shape="circle"
                    size="xsmall"
                    class="chip-avatar"
                />
                <span class="chip-name">{{ slotProps.value.name }}</span>
              </div>
            </template>

            <template #empty>
              <div class="empty-state">
                <Users class="empty-icon" />
                <p>کاربری یافت نشد. فیلترها را تغییر دهید.</p>
              </div>
            </template>
          </MultiSelect>

          <Button
              label="افزودن به گروه"
              icon="pi pi-plus"
              :loading="assigningUsers"
              :disabled="!selectedUsersToAdd.length"
              @click="addSelectedUsers"
              class="mt-3"
          />
        </div>

        <!-- ═══════ لیست اعضای فعلی ═══════ -->
        <div class="members-list mt-4">
          <div class="members-header">
        <span class="font-semibold text-slate-700">
          اعضای فعلی ({{ currentMembers.length }})
        </span>
          </div>

          <div v-if="loadingMembers" class="p-8 text-center">
            <ProgressSpinner style="width: 40px; height: 40px" />
          </div>

          <div v-else-if="!currentMembers.length" class="p-8 text-center text-slate-400">
            <Users class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p>هنوز عضوی به این گروه اختصاص داده نشده است</p>
          </div>

          <div v-else class="members-items">
            <div
                v-for="member in currentMembers"
                :key="member.id"
                class="member-item"
            >
              <Avatar
                  :label="getInitials(member.name)"
                  shape="circle"
                  class="member-avatar"
              />
              <div class="member-info">
                <div class="member-name">{{ member.name }}</div>
                <div class="member-meta">
              <span class="member-position" v-if="member.post_title">
                <Briefcase class="meta-icon" />
                {{ member.post_title }}
              </span>
                  <span class="member-unit" v-if="member.unit_title">
                <Building2 class="meta-icon" />
                {{ member.unit_title }}
              </span>
                  <span class="member-mobile" dir="ltr">
                <Phone class="meta-icon" />
                {{ member.mobile }}
              </span>
                </div>
              </div>
              <Button
                  v-if="can('groups.assign_users')"
                  icon="pi pi-times"
                  class="p-button-rounded p-button-text p-button-danger p-button-sm"
                  v-tooltip.top="'حذف از گروه'"
                  @click="confirmRemoveMember(member)"
              />
            </div>
          </div>
        </div>
      </div>
    </Dialog>

    <Toast />
    <ConfirmDialog />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useApiErrorHandler } from '@/composables/useApiErrorHandler'
import { useGroupsApi } from '@/services/groupsService.js'
import api from '@/api/axios.js'
import { useAuthStore } from '@/stores/auth.js'
import {
  UsersRound, Search, X, Shield, Lock, UserCheck, Activity, Users,  Briefcase, Building2, Phone, UserPlus  // ← جدید
} from 'lucide-vue-next'
import StatCard from './StatCard.vue'

const confirm = useConfirm()
const toast = useToast()
const { handleApiError } = useApiErrorHandler()
const authStore = useAuthStore()
const groupsApi = useGroupsApi()

// ═══════════════ State ═══════════════
const loading = ref(false)
const saving = ref(false)
const groups = ref([])
const allRoles = ref([])
const groupDialog = ref(false)
const membersDialog = ref(false)
const isEdit = ref(false)
const errors = ref({})
const selectedGroup = ref(null)
const currentMembers = ref([])
const availableUsers = ref([])
const selectedUsersToAdd = ref([])
const loadingMembers = ref(false)
const loadingUsers = ref(false)
const assigningUsers = ref(false)

const group = ref({
  title: '',
  name: '',
  description: '',
  color: '#3B82F6',
  sort_order: 0,
  is_active: true,
  role_ids: [],
})

const searchQuery = ref('')
const statusFilter = ref(null)
const statusOptions = [
  { label: 'فعال', value: true },
  { label: 'غیرفعال', value: false },
]





// ═══════════════ State جدید ═══════════════
const positionFilter = ref('')
const positionSuggestions = ref([])
const availableUnits = ref([])
const unitFilter = ref(null)
const userSearch = ref('')
const selectableUsers = ref([])

let userSearchTimeout = null

// ═══════════════ Computed ═══════════════
const hasActiveFilters = computed(() =>
    positionFilter.value || unitFilter.value || userSearch.value
)

const filteredSelectableUsers = computed(() => {
  // فیلتر سمت کلاینت (برای اطمینان)
  let result = selectableUsers.value

  if (positionFilter.value) {
    const pos = positionFilter.value.toLowerCase()
    result = result.filter(u =>
        u.post_title?.toLowerCase().includes(pos) ||
        u.job_title?.toLowerCase().includes(pos)
    )
  }

  return result
})

// ═══════════════ Methods ═══════════════
const searchPositions = async (event) => {
  try {
    const res = await groupsApi.getPositions()
    const allPositions = res.data.data || []
    const query = event.query?.toLowerCase() || ''

    if (!query) {
      positionSuggestions.value = allPositions.slice(0, 20)
    } else {
      positionSuggestions.value = allPositions
          .filter(p => p.toLowerCase().includes(query))
          .slice(0, 20)
    }
  } catch (error) {
    console.error('خطا در دریافت سمت‌ها', error)
  }
}

const onPositionSelect = () => {
  loadSelectableUsers()
}

const loadUnits = async () => {
  try {
    const res = await api.get('/hr/organizational-units')
    availableUnits.value = res.data.data || res.data || []
  } catch (error) {
    console.error('خطا در دریافت واحدها', error)
  }
}

const loadSelectableUsers = async () => {
  loadingUsers.value = true
  try {
    const params = {
      per_page: 200,
      exclude_group: selectedGroup.value?.id,
    }

    if (positionFilter.value) {
      params.position_search = positionFilter.value
    }
    if (unitFilter.value) {
      params.unit_id = unitFilter.value
    }
    if (userSearch.value) {
      params.search = userSearch.value
    }

    const res = await groupsApi.getSelectableUsers(params)
    selectableUsers.value = res.data.data || res.data || []
  } catch (error) {
    handleApiError(error)
  } finally {
    loadingUsers.value = false
  }
}

const onUserSearchInput = () => {
  clearTimeout(userSearchTimeout)
  userSearchTimeout = setTimeout(() => {
    loadSelectableUsers()
  }, 400)
}

const clearFilters = () => {
  positionFilter.value = ''
  unitFilter.value = null
  userSearch.value = ''
  loadSelectableUsers()
}

const openMembersDialog = async (data) => {
  selectedGroup.value = data
  currentMembers.value = []
  selectedUsersToAdd.value = []
  selectableUsers.value = []
  membersDialog.value = true

  await Promise.all([
    loadGroupMembers(data.id),
    loadSelectableUsers(),
    loadUnits(),
  ])
}

// ═══════════════ آپدیت loadGroupMembers ═══════════════
const loadGroupMembers = async (groupId) => {
  loadingMembers.value = true
  try {
    const res = await groupsApi.getUsers(groupId)
    currentMembers.value = res.data.data || res.data || []
  } catch (error) {
    handleApiError(error)
  } finally {
    loadingMembers.value = false
  }
}


// ═══════════════ Computed ═══════════════
const can = (permission) => authStore.permissions?.includes(permission)

const filteredGroups = computed(() => {
  let result = groups.value
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase().trim()
    result = result.filter(g =>
        g.title?.toLowerCase().includes(q) ||
        g.name?.toLowerCase().includes(q) ||
        g.description?.toLowerCase().includes(q)
    )
  }
  if (statusFilter.value !== null && statusFilter.value !== undefined) {
    result = result.filter(g => g.is_active === statusFilter.value)
  }
  return result
})

const totalMembers = computed(() =>
    groups.value.reduce((sum, g) => sum + (g.users_count || 0), 0)
)

const totalRoles = computed(() =>
    groups.value.reduce((sum, g) => sum + (g.roles?.length || 0), 0)
)

const activeGroupsCount = computed(() =>
    groups.value.filter(g => g.is_active).length
)

// ═══════════════ Methods ═══════════════
const getInitials = (name) => {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  return parts.length >= 2
      ? `${parts[0][0]}${parts[1][0]}`
      : parts[0][0]
}

const loadGroups = async () => {
  loading.value = true
  try {
    const res = await groupsApi.getAll()
    groups.value = res.data.data || res.data || []
  } catch (error) {
    handleApiError(error)
  } finally {
    loading.value = false
  }
}

const loadRoles = async () => {
  try {
    const res = await api.get('/acl/roles/all')
    allRoles.value = res.data.data || res.data || []
  } catch (error) {
    console.error('خطا در دریافت نقش‌ها', error)
  }
}

const openNew = () => {
  if (!can('groups.create')) {
    toast.add({ severity: 'warn', summary: 'اطلاع', detail: 'شما مجاز به این عمل نیستید', life: 3000 })
    return
  }
  group.value = {
    title: '',
    name: '',
    description: '',
    color: '#3B82F6',
    sort_order: 0,
    is_active: true,
    role_ids: [],
  }
  errors.value = {}
  isEdit.value = false
  groupDialog.value = true
}

const editGroup = (data) => {
  if (!can('groups.update')) return
  group.value = {
    id: data.id,
    title: data.title,
    name: data.name,
    description: data.description || '',
    color: data.color || '#3B82F6',
    sort_order: data.sort_order || 0,
    is_active: data.is_active,
    role_ids: (data.roles || []).map(r => r.id),
  }
  errors.value = {}
  isEdit.value = true
  groupDialog.value = true
}

const hideDialog = () => {
  groupDialog.value = false
}

const toggleRole = (roleId) => {
  const idx = group.value.role_ids.indexOf(roleId)
  if (idx > -1) {
    group.value.role_ids.splice(idx, 1)
  } else {
    group.value.role_ids.push(roleId)
  }
}

const validateForm = () => {
  errors.value = {}
  let valid = true
  if (!group.value.title?.trim()) {
    errors.value.title = 'نام نمایشی الزامی است'
    valid = false
  }
  if (!group.value.name?.trim()) {
    errors.value.name = 'نام سیستمی الزامی است'
    valid = false
  } else if (!/^[a-z0-9_]+$/i.test(group.value.name)) {
    errors.value.name = 'فقط حروف انگلیسی، عدد و _ مجاز است'
    valid = false
  }
  return valid
}

const saveGroup = async () => {
  if (!validateForm()) return
  saving.value = true
  try {
    if (isEdit.value) {
      await groupsApi.update(group.value.id, group.value)
      toast.add({ severity: 'success', summary: 'موفق', detail: 'گروه ویرایش شد', life: 3000 })
    } else {
      await groupsApi.create(group.value)
      toast.add({ severity: 'success', summary: 'موفق', detail: 'گروه ایجاد شد', life: 3000 })
    }
    groupDialog.value = false
    await loadGroups()
  } catch (error) {
    handleApiError(error, {
      setErrors: (validationErrors) => {
        errors.value = validationErrors
      },
    })
  } finally {
    saving.value = false
  }
}

const confirmDelete = (data) => {
  confirm.require({
    message: `آیا از حذف گروه "${data.title}" اطمینان دارید؟\nنقش‌های این گروه از ${data.users_count || 0} عضو آن برداشته خواهد شد.`,
    header: 'تایید حذف',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'بله، حذف کن',
    rejectLabel: 'انصراف',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await groupsApi.remove(data.id)
        toast.add({ severity: 'success', summary: 'موفق', detail: 'گروه حذف شد', life: 3000 })
        await loadGroups()
      } catch (error) {
        handleApiError(error)
      }
    },
  })
}

// ═══════════════ مدیریت اعضا ═══════════════

const loadAvailableUsers = async () => {
  loadingUsers.value = true
  try {
    const res = await api.get('/users', { params: { per_page: 500 } })
    const rawUsers = res.data.data || []
    availableUsers.value = rawUsers.map(u => {
      const nested = u.user || u
      return {
        id: nested.id ?? u.user_id ?? u.id,
        label: `${nested.name || 'بدون نام'} — ${nested.mobile || nested.personnel_code || ''}`.trim(),
      }
    }).filter(u => u.id)
  } catch (error) {
    console.error('خطا در دریافت کاربران', error)
  } finally {
    loadingUsers.value = false
  }
}

const addSelectedUsers = async () => {
  if (!selectedUsersToAdd.value.length) return
  assigningUsers.value = true
  try {
    await groupsApi.assignUsers(selectedGroup.value.id, selectedUsersToAdd.value, 'append')
    toast.add({
      severity: 'success',
      summary: 'موفق',
      detail: `${selectedUsersToAdd.value.length} کاربر به گروه اضافه شد`,
      life: 3000,
    })
    selectedUsersToAdd.value = []
    await Promise.all([
      loadGroupMembers(selectedGroup.value.id),
      loadGroups(),
    ])
  } catch (error) {
    handleApiError(error)
  } finally {
    assigningUsers.value = false
  }
}

const confirmRemoveMember = (member) => {
  confirm.require({
    message: `آیا از حذف "${member.name}" از گروه اطمینان دارید؟`,
    header: 'تایید حذف عضو',
    icon: 'pi pi-user-minus',
    acceptLabel: 'بله، حذف کن',
    rejectLabel: 'انصراف',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await groupsApi.removeUsers(selectedGroup.value.id, [member.id])
        toast.add({ severity: 'success', summary: 'موفق', detail: 'کاربر از گروه حذف شد', life: 3000 })
        await Promise.all([
          loadGroupMembers(selectedGroup.value.id),
          loadGroups(),
        ])
      } catch (error) {
        handleApiError(error)
      }
    },
  })
}

// ═══════════════ Init ═══════════════
onMounted(() => {
  loadGroups()
  loadRoles()
})
</script>

<style scoped>
/* ═══════════════ جدول ═══════════════ */
.groups-table { border: 0; }
:deep(.groups-table .p-datatable-tbody > tr > td) {
  padding: 0.9rem 0.85rem;
  vertical-align: middle;
}
:deep(.groups-table .p-datatable-thead > tr > th) {
  padding: 0.9rem 0.85rem;
  font-size: 0.78rem;
  white-space: nowrap;
  border-color: #e2e8f0;
  background: #f3f4f6;
  font-weight: 600;
  color: #374151;
}
:deep(.groups-table .p-datatable-tbody > tr) {
  border-color: #eef2f7;
  transition: background-color 0.2s;
}
:deep(.groups-table .p-datatable-tbody > tr:hover) {
  background: #f9fafb;
}

/* ═══════════════ Dialog ═══════════════ */
:deep(.group-dialog-modern .p-dialog-header),
:deep(.members-dialog .p-dialog-header) {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #e2e8f0;
  padding: 1.25rem 1.5rem;
}
:deep(.group-dialog-modern .p-dialog-content),
:deep(.members-dialog .p-dialog-content) {
  padding: 1.5rem;
  background: #ffffff;
}
:deep(.group-dialog-modern .p-dialog-footer),
:deep(.members-dialog .p-dialog-footer) {
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
  background: linear-gradient(135deg, #6366f1, #4f46e5);
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}
.header-icon-wrapper.members {
  background: linear-gradient(135deg, #10b981, #059669);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.header-icon { width: 24px; height: 24px; color: white; }
.header-text { flex: 1; }
.header-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
}
.header-subtitle {
  margin: 0.25rem 0 0;
  font-size: 0.875rem;
  color: #64748b;
}

/* ═══════════════ Form ═══════════════ */
.dialog-body { max-height: 65vh; overflow-y: auto; }
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
}
.form-group { display: flex; flex-direction: column; gap: 0.5rem; }
.form-group.full-width { grid-column: span 2; }
.form-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
}
.label-icon { width: 16px; height: 16px; color: #6366f1; }
.required { color: #ef4444; margin-right: 0.25rem; }
.error-message { color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem; }

/* ═══════════════ Roles ═══════════════ */
.roles-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
  margin-top: 0.5rem;
  max-height: 240px;
  overflow-y: auto;
  padding: 0.25rem;
}
.role-card {
  position: relative;
  display: flex;
  align-items: center;
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
  background: white;
}
.role-card:hover { border-color: #cbd5e1; background: #f8fafc; }
.role-card.is-selected {
  border-color: #6366f1;
  background: #eef2ff;
}
.role-checkbox { position: absolute; opacity: 0; pointer-events: none; }
.role-content { display: flex; align-items: center; gap: 0.75rem; width: 100%; }
.role-icon-wrapper {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: rgba(99, 102, 241, 0.1);
  flex-shrink: 0;
}
.role-icon { width: 16px; height: 16px; color: #6366f1; }
.role-label {
  flex: 1;
  font-size: 0.875rem;
  font-weight: 500;
  color: #1e293b;
  cursor: pointer;
}

/* ═══════════════ Status ═══════════════ */
.status-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  border-radius: 10px;
  border: 2px solid;
  transition: all 0.2s ease;
}
.status-card.is-active { background: #f0fdf4; border-color: #86efac; }
.status-card.is-inactive { background: #fef2f2; border-color: #fecaca; }
.status-info { display: flex; align-items: center; gap: 1rem; }
.status-icon-wrapper {
  width: 40px; height: 40px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 10px;
}
.status-icon-wrapper.active { background: #dcfce7; }
.status-icon-wrapper.inactive { background: #fee2e2; }
.status-icon { width: 20px; height: 20px; }
.status-icon-wrapper.active .status-icon { color: #16a34a; }
.status-icon-wrapper.inactive .status-icon { color: #dc2626; }
.status-text { flex: 1; }
.status-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.25rem;
}
.status-description { font-size: 0.75rem; color: #64748b; }

/* ═══════════════ Members ═══════════════ */
.members-body { max-height: 65vh; overflow-y: auto; }
.members-list {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}
.members-header {
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}
.members-items { max-height: 360px; overflow-y: auto; }
.member-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.15s;
}
.member-item:last-child { border-bottom: 0; }
.member-item:hover { background: #f8fafc; }
.member-avatar {
  background: linear-gradient(135deg, #6366f1, #4f46e5) !important;
  color: #fff !important;
  font-weight: 700;
  flex-shrink: 0;
}
.member-info { flex: 1; min-width: 0; }
.member-name {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.9rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.member-meta {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.15rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ═══════════════ Footer ═══════════════ */
.dialog-footer { display: flex; justify-content: flex-end; gap: 0.75rem; }
.btn-save {
  min-width: 120px;
  background: linear-gradient(135deg, #6366f1, #4f46e5);
  border: none;
}
.btn-save:hover {
  background: linear-gradient(135deg, #4f46e5, #4338ca);
}

/* ═══════════════ Responsive ═══════════════ */
@media (max-width: 768px) {
  .form-grid { grid-template-columns: 1fr; }
  .form-group.full-width { grid-column: span 1; }
  .roles-grid { grid-template-columns: 1fr; }
}


/* ═══════════════ Filters ═══════════════ */
.filters-section {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.filter-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
}

.filter-icon {
  width: 16px;
  height: 16px;
  color: #6366f1;
}

.filters-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.filter-badge {
  background: #6366f1;
  color: white;
  font-size: 0.75rem;
  padding: 0.25rem 0.75rem;
  border-radius: 999px;
  font-weight: 600;
}

/* ═══════════════ MultiSelect Users ═══════════════ */
.users-multiselect {
  min-height: 42px;
}

.user-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem;
}

.user-option-avatar {
  background: linear-gradient(135deg, #6366f1, #4f46e5) !important;
  color: #fff !important;
  font-weight: 700;
  flex-shrink: 0;
}

.user-option-info {
  flex: 1;
  min-width: 0;
}

.user-option-name {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.9rem;
  margin-bottom: 0.25rem;
}

.user-option-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  font-size: 0.75rem;
  color: #64748b;
}

.user-option-position,
.user-option-unit,
.user-option-mobile {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.meta-icon {
  width: 12px;
  height: 12px;
}

.user-chip {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.5rem;
  background: #eef2ff;
  border-radius: 6px;
}

.chip-avatar {
  background: #6366f1 !important;
  color: #fff !important;
  font-size: 0.7rem;
}

.chip-name {
  font-size: 0.875rem;
  font-weight: 500;
  color: #1e293b;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: #94a3b8;
}

.empty-icon {
  width: 48px;
  height: 48px;
  margin: 0 auto 0.5rem;
  opacity: 0.5;
}

/* ═══════════════ Members (آپدیت‌شده) ═══════════════ */
.member-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.25rem;
}

.member-position,
.member-unit,
.member-mobile {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

/* ═══════════════ Responsive ═══════════════ */
@media (max-width: 768px) {
  .filters-grid {
    grid-template-columns: 1fr;
  }
}


</style>