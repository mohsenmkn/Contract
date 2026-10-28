<!-- src/views/User/UsersView.vue -->

<template>
  <div class="page-container">
    <!-- هدر صفحه -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon">
          <Users class="w-6 h-6" />
        </div>
        <div class="header-text">
          <h1 class="page-title">مدیریت کاربران</h1>
          <p class="page-subtitle">مدیریت کاربران، نقش‌ها و دسترسی‌های سامانه</p>
        </div>
      </div>
      <Button
          v-if="can('users.create')"
          label="کاربر جدید"
          icon="pi pi-plus"
          class="add-button"
          @click="openNew"
      />
    </div>

    <!-- فیلترها -->
    <div class="filters-card">
      <div class="filters-row">
        <div class="search-box">
          <Search class="search-icon" />
          <InputText
              v-model="filters.search"
              placeholder="جستجو در نام، موبایل، کد ملی..."
              class="search-input"
              @input="debouncedSearch"
          />
        </div>

        <Select
            v-model="filters.employee_type"
            :options="employeeTypeOptions"
            option-label="label"
            option-value="value"
            placeholder="نوع کاربر"
            class="filter-select"
            show-clear
            @change="loadUsers"
        />

        <Button
            label="پاک کردن فیلترها"
            icon="pi pi-filter-slash"
            severity="secondary"
            outlined
            @click="resetFilters"
        />
      </div>
    </div>

    <!-- جدول -->
    <div class="table-card">
      <DataTable
          :value="users"
          :loading="loading"
          :paginator="true"
          :rows="15"
          :rowsPerPageOptions="[10, 15, 25, 50]"
          :totalRecords="totalRecords"
          :lazy="true"
          @page="onPage"
          responsiveLayout="scroll"
          class="users-table"
          stripedRows
      >
        <Column field="name" header="نام و نام خانوادگی" :sortable="true">
          <template #body="{ data }">
            <div class="user-cell">
              <div class="user-avatar" :class="{ 'is-contractor': data.employee_type === 'contractor' }">
                {{ getInitials(data.name) }}
              </div>
              <div class="user-info">
                <div class="user-name">{{ data.name }}</div>
                <div class="user-mobile">{{ data.mobile }}</div>
              </div>
            </div>
          </template>
        </Column>

        <Column field="employee_type" header="نوع کاربر" :sortable="true">
          <template #body="{ data }">
            <Tag
                :value="data.employee_type === 'contractor' ? 'پیمانکار' : 'پرسنل'"
                :severity="data.employee_type === 'contractor' ? 'warn' : 'info'"
                class="type-tag"
            >
              <template #icon>
                <Building2 v-if="data.employee_type === 'contractor'" class="w-3 h-3 ml-1" />
                <UserCheck v-else class="w-3 h-3 ml-1" />
              </template>
            </Tag>
          </template>
        </Column>

        <Column field="party" header="شرکت متصل">
          <template #body="{ data }">
            <template v-if="data.party">
              <div class="party-cell">
                <Building2 class="w-4 h-4 text-blue-600" />
                <div class="party-info">
                  <div class="party-name">{{ data.party.name }}</div>
                  <div class="party-id">{{ data.party.national_id }}</div>
                </div>
              </div>
            </template>
            <span v-else class="text-gray-400">—</span>
          </template>
        </Column>

        <Column field="roles" header="نقش‌ها">
          <template #body="{ data }">
            <div class="roles-cell">
              <Tag
                  v-for="role in (data.roles || []).slice(0, 2)"
                  :key="role"
                  :value="getRoleLabel(role)"
                  severity="secondary"
                  class="role-tag"
              />
              <Tag
                  v-if="(data.roles || []).length > 2"
                  :value="`+${data.roles.length - 2}`"
                  severity="info"
                  class="role-tag"
              />
            </div>
          </template>
        </Column>

        <Column field="is_active" header="وضعیت" :sortable="true">
          <template #body="{ data }">
            <Tag
                :value="data.is_active ? 'فعال' : 'غیرفعال'"
                :severity="data.is_active ? 'success' : 'danger'"
                class="status-tag"
            >
              <template #icon>
                <Activity v-if="data.is_active" class="w-3 h-3 ml-1" />
                <Lock v-else class="w-3 h-3 ml-1" />
              </template>
            </Tag>
          </template>
        </Column>

        <Column header="عملیات" :exportable="false" class="actions-column">
          <template #body="{ data }">
            <div class="action-buttons">
              <Button
                  v-if="can('users.update')"
                  icon="pi pi-pencil"
                  severity="warn"
                  text
                  rounded
                  @click="editUser(data)"
                  v-tooltip.top="'ویرایش'"
              />
              <Button
                  v-if="can('users.delete')"
                  icon="pi pi-trash"
                  severity="danger"
                  text
                  rounded
                  @click="confirmDelete(data)"
                  v-tooltip.top="'حذف'"
              />
            </div>
          </template>
        </Column>

        <template #empty>
          <div class="empty-state">
            <Users class="empty-icon" />
            <p>کاربری یافت نشد</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- دیالوگ ساخت/ویرایش کاربر -->
    <Dialog
        v-model:visible="userDialog"
        :header="isEdit ? 'ویرایش کاربر' : 'ایجاد کاربر جدید'"
        :modal="true"
        class="user-dialog"
        :style="{ width: 'min(95vw, 720px)' }"
    >
      <div class="dialog-content">
        <!-- نوع کاربر -->
        <div class="form-group full-width">
          <label class="form-label">
            <Briefcase class="label-icon" />
            نوع کاربر
            <span class="required">*</span>
          </label>
          <div class="user-type-selector">
            <div
                class="type-option"
                :class="{ 'is-selected': user.employee_type === 'personnel' }"
                @click="selectEmployeeType('personnel')"
            >
              <UserCheck class="type-icon" />
              <div class="type-info">
                <div class="type-title">پرسنل شرکت</div>
                <div class="type-desc">کارمند داخلی سازمان</div>
              </div>
            </div>
            <div
                class="type-option"
                :class="{ 'is-selected': user.employee_type === 'contractor' }"
                @click="selectEmployeeType('contractor')"
            >
              <Building2 class="type-icon" />
              <div class="type-info">
                <div class="type-title">پیمانکار</div>
                <div class="type-desc">نماینده شرکت پیمانکاری</div>
              </div>
            </div>
          </div>
        </div>

        <!-- انتخاب شرکت پیمانکاری (فقط برای پیمانکاران) -->
        <Transition name="slide-fade">
          <div v-if="user.employee_type === 'contractor'" class="form-group full-width party-selector">
            <label class="form-label">
              <Building2 class="label-icon" />
              شرکت پیمانکاری
              <span class="required">*</span>
            </label>
            <Select
                v-model="user.party_id"
                :options="availableParties"
                option-label="name"
                option-value="id"
                placeholder="انتخاب شرکت پیمانکاری"
                class="w-full"
                :loading="loadingParties"
                filter
                :class="{ 'p-invalid': errors.party_id }"
            >
              <template #option="slotProps">
                <div class="party-option">
                  <Building2 class="w-4 h-4 text-blue-600" />
                  <div class="party-option-info">
                    <div class="party-option-name">{{ slotProps.option.name }}</div>
                    <div class="party-option-id">{{ slotProps.option.national_id }}</div>
                  </div>
                  <Tag
                      v-if="slotProps.option.user_id"
                      value="متصل شده"
                      severity="warn"
                      class="party-connected-tag"
                  />
                </div>
              </template>
            </Select>
            <small v-if="errors.party_id" class="error-message">{{ errors.party_id }}</small>
            <small class="hint-text">
              <Info class="w-3 h-3 inline" />
              پیمانکار باید به یک شرکت حقوقی ثبت‌شده در سامانه متصل باشد
            </small>
          </div>
        </Transition>

        <!-- اطلاعات شخصی -->
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">
              <User class="label-icon" />
              نام و نام خانوادگی
              <span class="required">*</span>
            </label>
            <InputText
                v-model="user.name"
                placeholder="مثال: علی احمدی"
                :class="{ 'p-invalid': errors.name }"
                class="w-full"
            />
            <small v-if="errors.name" class="error-message">{{ errors.name }}</small>
          </div>

          <div class="form-group">
            <label class="form-label">
              <Phone class="label-icon" />
              شماره موبایل
              <span class="required">*</span>
            </label>
            <InputText
                v-model="user.mobile"
                placeholder="09123456789"
                :class="{ 'p-invalid': errors.mobile }"
                class="w-full ltr-input"
                maxlength="11"
            />
            <small v-if="errors.mobile" class="error-message">{{ errors.mobile }}</small>
          </div>

          <div class="form-group">
            <label class="form-label">
              <Mail class="label-icon" />
              ایمیل
            </label>
            <InputText
                v-model="user.email"
                type="email"
                placeholder="example@company.com"
                :class="{ 'p-invalid': errors.email }"
                class="w-full ltr-input"
            />
            <small v-if="errors.email" class="error-message">{{ errors.email }}</small>
          </div>

          <div class="form-group">
            <label class="form-label">
              <Key class="label-icon" />
              رمز عبور
              <span v-if="!isEdit" class="required">*</span>
            </label>
            <Password
                v-model="user.password"
                :feedback="false"
                toggleMask
                :class="{ 'p-invalid': errors.password }"
                class="w-full"
                :placeholder="isEdit ? 'در صورت تمایل به تغییر وارد کنید' : 'حداقل ۶ کاراکتر'"
            />
            <small v-if="errors.password" class="error-message">{{ errors.password }}</small>
          </div>

          <div class="form-group">
            <label class="form-label">
              <CreditCard class="label-icon" />
              کد ملی
            </label>
            <InputText
                v-model="user.national_code"
                placeholder="کد ملی ۱۰ رقمی"
                :class="{ 'p-invalid': errors.national_code }"
                class="w-full ltr-input"
                maxlength="10"
            />
            <small v-if="errors.national_code" class="error-message">{{ errors.national_code }}</small>
          </div>

          <div class="form-group">
            <label class="form-label">
              <Hash class="label-icon" />
              کد پرسنلی
            </label>
            <InputText
                v-model="user.personnel_code"
                placeholder="کد پرسنلی"
                :class="{ 'p-invalid': errors.personnel_code }"
                class="w-full ltr-input"
            />
            <small v-if="errors.personnel_code" class="error-message">{{ errors.personnel_code }}</small>
          </div>
        </div>

        <!-- نقش‌ها -->
        <div class="form-group full-width">
          <label class="form-label">
            <Shield class="label-icon" />
            نقش‌های کاربری
          </label>
          <div class="roles-selector">
            <div
                v-for="role in roles"
                :key="role.name"
                class="role-chip"
                :class="{ 'is-selected': user.roles.includes(role.name) }"
                @click="toggleRole(role.name)"
            >
              <Shield class="role-chip-icon" />
              <span>{{ role.label || role.name }}</span>
              <Check v-if="user.roles.includes(role.name)" class="role-chip-check" />
            </div>
          </div>
          <small v-if="errors.roles" class="error-message">{{ errors.roles }}</small>
        </div>

        <!-- وضعیت حساب -->
        <div class="form-group full-width">
          <label class="form-label">
            <Activity class="label-icon" />
            وضعیت حساب کاربری
          </label>
          <div class="status-card" :class="{ 'is-active': user.is_active, 'is-inactive': !user.is_active }">
            <div class="status-info">
              <div class="status-icon-wrapper" :class="user.is_active ? 'active' : 'inactive'">
                <UserCheck v-if="user.is_active" class="status-icon" />
                <Lock v-else class="status-icon" />
              </div>
              <div class="status-text">
                <div class="status-title">
                  {{ user.is_active ? 'کاربر فعال است' : 'کاربر غیرفعال است' }}
                </div>
                <div class="status-description">
                  <template v-if="user.employee_type === 'contractor'">
                    پیمانکار {{ user.is_active ? 'می‌تواند وارد پورتال پیمانکاران شود' : 'امکان ورود به پورتال را ندارد' }}
                  </template>
                  <template v-else>
                    {{ user.is_active ? 'کاربر می‌تواند وارد سیستم شود' : 'کاربر امکان ورود به سیستم را ندارد' }}
                  </template>
                </div>
              </div>
            </div>
            <Checkbox
                v-model="user.is_active"
                :binary="true"
                inputId="is_active"
                class="status-checkbox"
            />
          </div>
        </div>
      </div>

      <template #footer>
        <Button
            label="انصراف"
            icon="pi pi-times"
            severity="secondary"
            outlined
            @click="userDialog = false"
        />
        <Button
            :label="isEdit ? 'بروزرسانی' : 'ایجاد'"
            :icon="isEdit ? 'pi pi-check' : 'pi pi-plus'"
            :loading="submitting"
            @click="submitUser"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Checkbox from 'primevue/checkbox';
import {
  Users, Search, UserCheck, Building2, Activity, Lock,
  User, Phone, Mail, Key, CreditCard, Hash, Shield,
  Briefcase, Check, Info
} from 'lucide-vue-next';
import api from '@/api/axios';
import { useAuthStore } from '@/stores/auth';

const confirm = useConfirm();
const toast = useToast();
const authStore = useAuthStore();

// State
const users = ref([]);
const roles = ref([]);
const availableParties = ref([]);
const loading = ref(false);
const submitting = ref(false);
const loadingParties = ref(false);
const userDialog = ref(false);
const isEdit = ref(false);
const totalRecords = ref(0);
const errors = ref({});

const user = ref({
  name: '',
  mobile: '',
  email: '',
  password: '',
  national_code: '',
  personnel_code: '',
  employee_type: 'personnel',
  party_id: null,
  roles: [],
  is_active: true,
});

const filters = ref({
  search: '',
  employee_type: null,
  page: 1,
  per_page: 15,
});

const employeeTypeOptions = [
  { label: 'پرسنل شرکت', value: 'personnel' },
  { label: 'پیمانکار', value: 'contractor' },
];

// Computed
const can = (permission) => authStore.can(permission);

const getRoleLabel = (roleName) => {
  const role = roles.value.find(r => r.name === roleName);
  return role?.label || roleName;
};

const getInitials = (name) => {
  if (!name) return '?';
  const parts = name.trim().split(/\s+/);
  if (parts.length === 1) return parts[0].charAt(0);
  return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
};

// Methods
const loadUsers = async () => {
  loading.value = true;
  try {
    const params = {
      ...filters.value,
      page: filters.value.page,
    };

    const { data } = await api.get('/users', { params });
    users.value = data.data;
    totalRecords.value = data.meta.total;
  } catch (error) {
    console.error('Error loading users:', error);
    toast.add({
      severity: 'error',
      summary: 'خطا',
      detail: 'خطا در دریافت لیست کاربران',
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

const loadRoles = async () => {
  try {
    const { data } = await api.get('acl/roles/all');
    roles.value = data.data || [];
  } catch (error) {
    console.error('Error loading roles:', error);
  }
};

const loadParties = async () => {
  loadingParties.value = true;
  try {
    const { data } = await api.get('/parties', {
      params: {
        role: 'contractor',
        per_page: 1000,
      }
    });
    availableParties.value = data.data || [];
  } catch (error) {
    console.error('Error loading parties:', error);
  } finally {
    loadingParties.value = false;
  }
};

const onPage = (event) => {
  filters.value.page = event.page + 1;
  filters.value.per_page = event.rows;
  loadUsers();
};

const debouncedSearch = () => {
  clearTimeout(window.searchTimeout);
  window.searchTimeout = setTimeout(() => {
    filters.value.page = 1;
    loadUsers();
  }, 500);
};

const resetFilters = () => {
  filters.value = {
    search: '',
    employee_type: null,
    page: 1,
    per_page: 15,
  };
  loadUsers();
};

const selectEmployeeType = (type) => {
  user.value.employee_type = type;
  if (type === 'personnel') {
    user.value.party_id = null;
  }
};

const toggleRole = (roleName) => {
  const index = user.value.roles.indexOf(roleName);
  if (index === -1) {
    user.value.roles.push(roleName);
  } else {
    user.value.roles.splice(index, 1);
  }
};

const openNew = () => {
  if (!can('users.create')) {
    toast.add({
      severity: 'warn',
      summary: 'اطلاع',
      detail: 'شما مجاز به این عمل نیستید',
      life: 3000,
    });
    return;
  }

  user.value = {
    name: '',
    mobile: '',
    email: '',
    password: '',
    national_code: '',
    personnel_code: '',
    employee_type: 'personnel',
    party_id: null,
    roles: [],
    is_active: true,
  };
  errors.value = {};
  isEdit.value = false;
  userDialog.value = true;
  loadParties();
};

const editUser = (data) => {
  if (!can('users.update')) {
    toast.add({
      severity: 'warn',
      summary: 'اطلاع',
      detail: 'شما مجاز به این عمل نیستید',
      life: 3000,
    });
    return;
  }

  user.value = {
    ...data,
    roles: data.roles || [],
    employee_type: data.employee_type || 'personnel',
    party_id: data.party_id || null,
    password: '',
    is_active: Boolean(data.is_active),
  };
  errors.value = {};
  isEdit.value = true;
  userDialog.value = true;
  loadParties();
};

const validateForm = () => {
  errors.value = {};
  let isValid = true;

  if (!user.value.name?.trim()) {
    errors.value.name = 'نام الزامی است';
    isValid = false;
  }

  if (!user.value.mobile?.trim()) {
    errors.value.mobile = 'شماره موبایل الزامی است';
    isValid = false;
  } else if (!/^09\d{9}$/.test(user.value.mobile)) {
    errors.value.mobile = 'شماره موبایل معتبر نیست';
    isValid = false;
  }

  if (!isEdit.value && !user.value.password) {
    errors.value.password = 'رمز عبور الزامی است';
    isValid = false;
  } else if (user.value.password && user.value.password.length < 6) {
    errors.value.password = 'رمز عبور باید حداقل ۶ کاراکتر باشد';
    isValid = false;
  }

  if (user.value.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(user.value.email)) {
    errors.value.email = 'ایمیل معتبر نیست';
    isValid = false;
  }

  if (user.value.employee_type === 'contractor' && !user.value.party_id) {
    errors.value.party_id = 'انتخاب شرکت پیمانکاری الزامی است';
    isValid = false;
  }

  return isValid;
};

const submitUser = async () => {
  if (!validateForm()) return;

  submitting.value = true;
  try {
    const payload = { ...user.value };
    if (isEdit.value && !payload.password) {
      delete payload.password;
    }

    if (isEdit.value) {
      await api.put(`/users/${user.value.id}`, payload);
      toast.add({
        severity: 'success',
        summary: 'موفق',
        detail: 'کاربر با موفقیت بروزرسانی شد',
        life: 3000,
      });
    } else {
      await api.post('/users', payload);
      toast.add({
        severity: 'success',
        summary: 'موفق',
        detail: 'کاربر با موفقیت ایجاد شد',
        life: 3000,
      });
    }

    userDialog.value = false;
    loadUsers();
  } catch (error) {
    console.error('Error saving user:', error);

    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }

    toast.add({
      severity: 'error',
      summary: 'خطا',
      detail: error.response?.data?.message || 'خطا در ذخیره کاربر',
      life: 4000,
    });
  } finally {
    submitting.value = false;
  }
};

const confirmDelete = (userData) => {
  if (!can('users.delete')) {
    toast.add({
      severity: 'warn',
      summary: 'اطلاع',
      detail: 'شما مجاز به این عمل نیستید',
      life: 3000,
    });
    return;
  }

  confirm.require({
    message: `آیا از حذف کاربر "${userData.name}" اطمینان دارید؟`,
    header: 'تایید حذف',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/users/${userData.id}`);
        toast.add({
          severity: 'success',
          summary: 'موفق',
          detail: 'کاربر با موفقیت حذف شد',
          life: 3000,
        });
        loadUsers();
      } catch (error) {
        console.error('Error deleting user:', error);
        toast.add({
          severity: 'error',
          summary: 'خطا',
          detail: error.response?.data?.message || 'خطا در حذف کاربر',
          life: 4000,
        });
      }
    },
  });
};

// Lifecycle
onMounted(() => {
  loadUsers();
  loadRoles();
});
</script>

<style scoped>
.page-container {
  padding: 1.5rem;
  direction: rtl;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #1d4ed8, #3b82f6);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 12px rgba(29, 78, 216, 0.25);
}

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.page-subtitle {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0.25rem 0 0;
}

.add-button {
  background: linear-gradient(135deg, #1d4ed8, #3b82f6) !important;
  border: none !important;
  font-weight: 600 !important;
}

.filters-card, .table-card {
  background: white;
  border-radius: 12px;
  padding: 1.25rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
  margin-bottom: 1.25rem;
}

.filters-row {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  align-items: center;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 240px;
}

.search-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: #94a3b8;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding-right: 2.5rem !important;
}

.filter-select {
  min-width: 180px;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #1d4ed8, #3b82f6);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.875rem;
  flex-shrink: 0;
}

.user-avatar.is-contractor {
  background: linear-gradient(135deg, #f59e0b, #fbbf24);
}

.user-info {
  min-width: 0;
}

.user-name {
  font-weight: 600;
  color: #0f172a;
  font-size: 0.875rem;
}

.user-mobile {
  font-size: 0.75rem;
  color: #64748b;
  direction: ltr;
  text-align: right;
}

.type-tag, .status-tag, .role-tag {
  font-size: 0.7rem !important;
}

.party-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.party-info {
  min-width: 0;
}

.party-name {
  font-size: 0.8rem;
  font-weight: 600;
  color: #0f172a;
}

.party-id {
  font-size: 0.7rem;
  color: #64748b;
  direction: ltr;
  text-align: right;
}

.roles-cell {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

.role-tag {
  font-size: 0.65rem !important;
}

.actions-column {
  width: 100px;
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
  justify-content: center;
}

.empty-state {
  padding: 3rem 1rem;
  text-align: center;
  color: #94a3b8;
}

.empty-icon {
  width: 48px;
  height: 48px;
  margin: 0 auto 1rem;
  opacity: 0.5;
}

/* Dialog */
.dialog-content {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group.full-width {
  width: 100%;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
}

.label-icon {
  width: 14px;
  height: 14px;
  color: #64748b;
}

.required {
  color: #ef4444;
  margin-right: 2px;
}

.error-message {
  color: #ef4444;
  font-size: 0.75rem;
}

.hint-text {
  color: #64748b;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.ltr-input :deep(input) {
  direction: ltr;
  text-align: left;
}

/* User Type Selector */
.user-type-selector {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}

.type-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  background: white;
}

.type-option:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.type-option.is-selected {
  border-color: #1d4ed8;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  box-shadow: 0 2px 8px rgba(29, 78, 216, 0.15);
}

.type-icon {
  width: 32px;
  height: 32px;
  color: #1d4ed8;
  flex-shrink: 0;
}

.type-info {
  min-width: 0;
}

.type-title {
  font-weight: 700;
  color: #0f172a;
  font-size: 0.9rem;
  margin-bottom: 2px;
}

.type-desc {
  font-size: 0.75rem;
  color: #64748b;
}

/* Party Selector */
.party-selector {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  padding: 1rem;
  border-radius: 12px;
  border: 1px solid #fbbf24;
}

.party-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0;
}

.party-option-info {
  flex: 1;
  min-width: 0;
}

.party-option-name {
  font-weight: 600;
  color: #0f172a;
  font-size: 0.85rem;
}

.party-option-id {
  font-size: 0.75rem;
  color: #64748b;
  direction: ltr;
  text-align: right;
}

.party-connected-tag {
  font-size: 0.65rem !important;
}

/* Roles Selector */
.roles-selector {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.role-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.85rem;
  border: 1.5px solid #e2e8f0;
  border-radius: 20px;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.8rem;
  font-weight: 500;
  color: #475569;
}

.role-chip:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.role-chip.is-selected {
  background: linear-gradient(135deg, #1d4ed8, #3b82f6);
  border-color: #1d4ed8;
  color: white;
}

.role-chip-icon {
  width: 14px;
  height: 14px;
}

.role-chip-check {
  width: 14px;
  height: 14px;
}

/* Status Card */
.status-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  border-radius: 12px;
  border: 1.5px solid #e2e8f0;
  transition: all 0.2s;
}

.status-card.is-active {
  background: linear-gradient(135deg, #f0fdf4, #dcfce7);
  border-color: #86efac;
}

.status-card.is-inactive {
  background: linear-gradient(135deg, #fef2f2, #fee2e2);
  border-color: #fca5a5;
}

.status-info {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.status-icon-wrapper {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.status-icon-wrapper.active {
  background: #10b981;
  color: white;
}

.status-icon-wrapper.inactive {
  background: #ef4444;
  color: white;
}

.status-icon {
  width: 20px;
  height: 20px;
}

.status-title {
  font-weight: 700;
  color: #0f172a;
  font-size: 0.9rem;
  margin-bottom: 2px;
}

.status-description {
  font-size: 0.75rem;
  color: #64748b;
}

/* Transitions */
.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.2s ease-in;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Responsive */
@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .user-type-selector {
    grid-template-columns: 1fr;
  }

  .filters-row {
    flex-direction: column;
    align-items: stretch;
  }

  .search-box, .filter-select {
    width: 100%;
  }
}
</style>