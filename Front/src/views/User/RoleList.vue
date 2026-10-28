<template>
  <div class="min-h-screen bg-gray-50 p-4 md:p-6">
    <!-- هدر صفحه -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl md:text-3xl font-bold text-gray-800 flex items-center gap-3">
            <ShieldCheck class="w-8 h-8 text-purple-600" />
            مدیریت نقش‌ها
          </h1>
          <p class="text-gray-500 mt-2 text-sm">
            تعریف و مدیریت نقش‌های کاربری و دسترسی‌ها
          </p>
        </div>
        <div class="flex gap-3">
          <Button
              v-if="can('roles.export')"
              label="خروجی Excel"
              icon="pi pi-file-excel"
              severity="success"
              outlined
              @click="exportToExcel"
          />
          <Button
              v-if="can('roles.create')"
              label="نقش جدید"
              icon="pi pi-plus"
              @click="openCreateModal"
              class="bg-purple-600 hover:bg-purple-700"
          />
        </div>
      </div>
    </div>

    <!-- بخش جستجو -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            🔍 جستجو
          </label>
          <div class="relative">
            <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <InputText
                v-model="searchQuery"
                placeholder="جستجو بر اساس نام نقش..."
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
        <div class="flex items-end">
          <Button
              label="اعمال فیلتر"
              icon="pi pi-filter"
              @click="applyFilters"
              class="w-full"
          />
        </div>
      </div>

      <!-- فیلترهای فعال -->
      <div v-if="searchQuery" class="mt-4 pt-4 border-t border-gray-200">
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-sm text-gray-600">فیلترهای فعال:</span>
          <Tag
              :value="`جستجو: ${searchQuery}`"
              severity="info"
              icon="pi pi-times"
              @click="searchQuery = ''"
              class="cursor-pointer"
          />
          <Button
              label="پاک کردن"
              icon="pi pi-filter-slash"
              size="small"
              text
              @click="clearFilters"
          />
        </div>
      </div>
    </div>

    <!-- آمار نقش‌ها -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
            <ShieldCheck class="w-6 h-6 text-purple-600" />
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-800">{{ totalRoles }}</div>
            <div class="text-sm text-gray-500">کل نقش‌ها</div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
            <Users class="w-6 h-6 text-blue-600" />
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-800">{{ totalUsersWithRoles }}</div>
            <div class="text-sm text-gray-500">کاربران دارای نقش</div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
            <KeyRound class="w-6 h-6 text-green-600" />
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-800">{{ totalPermissions }}</div>
            <div class="text-sm text-gray-500">کل دسترسی‌ها</div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
            <UserCheck class="w-6 h-6 text-orange-600" />
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-800">{{ mostUsedRole?.userCount || 0 }}</div>
            <div class="text-sm text-gray-500">پرکاربردترین نقش</div>
            <div class="text-xs text-gray-400 mt-1">{{ mostUsedRole?.name || '-' }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- جدول نقش‌ها -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
      <DataTable
          v-if="can('roles.read')"
          :value="filteredRoles"
          responsiveLayout="scroll"
          paginator
          :rows="rows"
          :totalRecords="filteredRoles.length"
          :loading="loading"
          stripedRows
          class="roles-table"
      >
        <template #header>
          <div class="flex justify-between items-center">
            <span class="text-lg font-bold text-gray-800">
              لیست نقش‌ها ({{ filteredRoles.length }} از {{ totalRoles }} مورد)
            </span>
          </div>
        </template>

        <Column field="name" header="نام نقش" sortable style="min-width: 200px">
          <template #body="slotProps">
            <div class="flex items-center gap-3">
              <div
                  class="w-10 h-10 rounded-lg flex items-center justify-center"
                  :class="getRoleColor(slotProps.data.name)"
              >
                <ShieldCheck class="w-5 h-5 text-white" />
              </div>
              <div>
                <div class="font-semibold text-gray-800">{{ slotProps.data.name }}</div>
                <div class="text-xs text-gray-500">
                  {{ slotProps.data.permissions?.length || 0 }} دسترسی
                </div>
              </div>
            </div>
          </template>
        </Column>

        <Column header="تعداد کاربران" sortable style="min-width: 150px">
          <template #body="slotProps">
            <div class="flex items-center gap-2">
              <div class="flex -space-x-2 space-x-reverse">
                <Avatar
                    v-for="user in slotProps.data.users?.slice(0, 3)"
                    :key="user.id"
                    :label="getInitials(user.name)"
                    shape="circle"
                    size="small"
                    class="border-2 border-white"
                    :class="getAvatarColor(user.id)"
                />
              </div>
              <Badge
                  :value="slotProps.data.userCount || 0"
                  :severity="getUserCountSeverity(slotProps.data.userCount)"
                  class="font-bold"
              />
              <span v-if="slotProps.data.userCount > 3" class="text-xs text-gray-500">
                +{{ slotProps.data.userCount - 3 }}
              </span>
            </div>
          </template>
        </Column>

        <Column header="دسترسی‌ها" style="min-width: 200px">
          <template #body="slotProps">
            <div class="flex flex-wrap gap-1">
              <Tag
                  v-for="perm in slotProps.data.permissions?.slice(0, 3)"
                  :key="perm.id || perm"
                  :value="typeof perm === 'object' ? perm.name : perm"
                  severity="secondary"
                  class="text-xs"
              />
              <Tag
                  v-if="slotProps.data.permissions?.length > 3"
                  :value="`+${slotProps.data.permissions.length - 3}`"
                  severity="info"
                  class="text-xs"
              />
              <span v-if="!slotProps.data.permissions || slotProps.data.permissions.length === 0" class="text-gray-400 text-sm">
                بدون دسترسی
              </span>
            </div>
          </template>
        </Column>

        <Column field="created_at" header="تاریخ ایجاد" sortable style="min-width: 120px">
          <template #body="slotProps">
            <div class="text-sm text-gray-600">
              {{ formatDate(slotProps.data.created_at) }}
            </div>
          </template>
        </Column>

        <Column header="عملیات" style="min-width: 150px" frozen alignFrozen="left">
          <template #body="slotProps">
            <div class="flex gap-2">
              <Button
                  v-if="can('roles.view')"
                  icon="pi pi-eye"
                  class="p-button-rounded p-button-info p-button-text"
                  @click="viewRole(slotProps.data)"
                  tooltip="مشاهده"
              />
              <Button
                  v-if="can('roles.update')"
                  icon="pi pi-pencil"
                  class="p-button-rounded p-button-success p-button-text"
                  @click="openEditModal(slotProps.data)"
                  tooltip="ویرایش"
              />
              <Button
                  v-if="can('roles.delete')"
                  icon="pi pi-trash"
                  class="p-button-rounded p-button-danger p-button-text"
                  @click="confirmDelete(slotProps.data)"
                  tooltip="حذف"
              />
            </div>
          </template>
        </Column>
      </DataTable>

      <!-- پیام عدم دسترسی -->
      <div v-else class="p-12 text-center">
        <Lock class="w-16 h-16 text-gray-300 mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-gray-600 mb-2">دسترسی محدود</h3>
        <p class="text-gray-500">شما مجاز به مشاهده لیست نقش‌ها نیستید</p>
      </div>
    </div>

    <!-- مودال مشاهده نقش -->
    <Dialog
        v-model:visible="viewDialog"
        :style="{ width: '600px' }"
        header="اطلاعات نقش"
        :modal="true"
    >
      <div v-if="selectedRole" class="space-y-4">
        <div class="flex items-center gap-4 p-4 bg-purple-50 rounded-lg">
          <div
              class="w-16 h-16 rounded-xl flex items-center justify-center"
              :class="getRoleColor(selectedRole.name)"
          >
            <ShieldCheck class="w-8 h-8 text-white" />
          </div>
          <div>
            <h3 class="text-xl font-bold text-gray-800">{{ selectedRole.name }}</h3>
            <p class="text-sm text-gray-500">
              {{ selectedRole.userCount || 0 }} کاربر | {{ selectedRole.permissions?.length || 0 }} دسترسی
            </p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="p-3 bg-blue-50 rounded-lg">
            <div class="text-xs text-gray-500 mb-1">تاریخ ایجاد</div>
            <div class="font-semibold text-gray-800">{{ formatDate(selectedRole.created_at) }}</div>
          </div>
          <div class="p-3 bg-green-50 rounded-lg">
            <div class="text-xs text-gray-500 mb-1">تاریخ بروزرسانی</div>
            <div class="font-semibold text-gray-800">{{ formatDate(selectedRole.updated_at) }}</div>
          </div>
        </div>

        <div>
          <div class="text-sm font-medium text-gray-700 mb-2">دسترسی‌های نقش:</div>
          <div class="flex flex-wrap gap-2 max-h-60 overflow-y-auto p-3 bg-gray-50 rounded-lg">
            <Tag
                v-for="perm in selectedRole.permissions"
                :key="perm.id || perm"
                :value="typeof perm === 'object' ? perm.name : perm"
                severity="info"
            />
            <span v-if="!selectedRole.permissions || selectedRole.permissions.length === 0" class="text-gray-400 text-sm">
              بدون دسترسی
            </span>
          </div>
        </div>

        <div v-if="selectedRole.users && selectedRole.users.length > 0">
          <div class="text-sm font-medium text-gray-700 mb-2">کاربران دارای این نقش:</div>
          <div class="space-y-2 max-h-60 overflow-y-auto">
            <div
                v-for="user in selectedRole.users"
                :key="user.id"
                class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <Avatar
                  :label="getInitials(user.name)"
                  shape="circle"
                  size="small"
                  class="bg-gradient-to-br from-blue-500 to-purple-600 text-white font-bold"
              />
              <div class="flex-1">
                <div class="font-semibold text-gray-800">{{ user.name }}</div>
                <div class="text-xs text-gray-500" dir="ltr">{{ user.mobile }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Dialog>

    <!-- مودال ایجاد/ویرایش نقش -->
    <RoleModal
        :is-open="isModalOpen"
        :role-to-edit="selectedRole"
        :permissions="allPermissions"
        @close="isModalOpen = false"
        @save="saveRole"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { useApiErrorHandler } from '@/composables/useApiErrorHandler';
import api from '@/api/axios.js';
import { useAuthStore } from '@/stores/auth.js';
import RoleModal from '@/components/layouts/RoleModal.vue';
import {
  ShieldCheck,
  Users,
  KeyRound,
  UserCheck,
  Lock,
  Search,
  X,
} from 'lucide-vue-next';

const confirm = useConfirm();
const rows = ref(10);
const toast = useToast();
const { handleApiError } = useApiErrorHandler();
const roles = ref([]);
const allPermissions = ref([]);
const allUsers = ref([]);
const isModalOpen = ref(false);
const viewDialog = ref(false);
const selectedRole = ref(null);
const loading = ref(false);
const authStore = useAuthStore();

// جستجو
const searchQuery = ref('');

let searchTimeout = null;

// آمار
const totalRoles = computed(() => roles.value.length);
const totalPermissions = computed(() => allPermissions.value.length);
const totalUsersWithRoles = computed(() => {
  return allUsers.value.filter(u => u.roles && u.roles.length > 0).length;
});

const mostUsedRole = computed(() => {
  if (roles.value.length === 0) return null;
  return roles.value.reduce((max, role) =>
          (role.userCount || 0) > (max.userCount || 0) ? role : max
      , roles.value[0]);
});

// فیلتر نقش‌ها
const filteredRoles = computed(() => {
  if (!searchQuery.value) return roles.value;

  const query = searchQuery.value.toLowerCase().trim();
  return roles.value.filter(role =>
      role.name?.toLowerCase().includes(query)
  );
});

onMounted(() => {
  fetchRoles();
  fetchPermissions();
  fetchUsers();
});

const can = (permission) => {
  return authStore.permissions?.includes(permission);
};

const fetchRoles = async () => {
  loading.value = true;
  try {
    const response = await api.get('/acl/roles/all');
    const rolesData = response.data.data || response.data;

    // اضافه کردن تعداد کاربران به هر نقش
    roles.value = rolesData.map(role => ({
      ...role,
      userCount: countUsersByRole(role.name),
      users: getUsersByRole(role.name)
    }));
  } catch (error) {
    console.error('خطا در دریافت نقش‌ها:', error);
  } finally {
    loading.value = false;
  }
};

const fetchPermissions = async () => {
  try {
    const response = await api.get('/acl/permissions/all');
    allPermissions.value = response.data.data || response.data;
  } catch (error) {
    console.error('خطا در دریافت دسترسی‌ها:', error);
  }
};

const fetchUsers = async () => {
  try {
    const response = await api.get('/users');
    allUsers.value = response.data.data || response.data;
  } catch (error) {
    console.error('خطا در دریافت کاربران:', error);
  }
};

const countUsersByRole = (roleName) => {
  return allUsers.value.filter(user =>
          user.roles && user.roles.some(r => {
            const rName = typeof r === 'object' ? r.name : r;
            return rName === roleName;
          })
  ).length;
};

const getUsersByRole = (roleName) => {
  return allUsers.value.filter(user =>
          user.roles && user.roles.some(r => {
            const rName = typeof r === 'object' ? r.name : r;
            return rName === roleName;
          })
  );
};

const onSearchInput = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 300);
};

const applyFilters = () => {
  // فیلتر به صورت خودکار اعمال می‌شود
};

const clearFilters = () => {
  searchQuery.value = '';
};

const openCreateModal = () => {
  if (!can('roles.create')) {
    toast.add({ severity: 'warn', summary: 'اطلاع', detail: 'شما مجاز به این عمل نیستید', life: 3000 });
    return;
  }
  selectedRole.value = null;
  isModalOpen.value = true;
};

const openEditModal = (role) => {
  if (!can('roles.update')) {
    toast.add({ severity: 'warn', summary: 'اطلاع', detail: 'شما مجاز به این عمل نیستید', life: 3000 });
    return;
  }
  selectedRole.value = JSON.parse(JSON.stringify(role));
  isModalOpen.value = true;
};

const viewRole = (role) => {
  selectedRole.value = role;
  viewDialog.value = true;
};

const saveRole = async (roleData) => {
  try {
    if (roleData.id) {
      await api.put(`/acl/roles/${roleData.id}`, {
        name: roleData.name,
        permissions: roleData.permissions
      });
      toast.add({ severity: 'success', summary: 'موفق', detail: 'نقش با موفقیت ویرایش شد', life: 3000 });
    } else {
      await api.post('/acl/roles', {
        name: roleData.name,
        permissions: roleData.permissions
      });
      toast.add({ severity: 'success', summary: 'موفق', detail: 'نقش جدید ایجاد شد', life: 3000 });
    }
    isModalOpen.value = false;
    await fetchRoles();
  } catch (error) {
    console.error('خطا در ذخیره نقش:', error);
    if (error.response && error.response.status === 422) {
      toast.add({ severity: 'error', summary: 'خطا', detail: 'اطلاعات وارد شده معتبر نیست.', life: 5000 });
    }
  }
};

const confirmDelete = (role) => {
  confirm.require({
    message: `آیا از حذف نقش "${role.name}" اطمینان دارید؟`,
    header: 'تایید حذف',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'بله، حذف کن',
    rejectLabel: 'خیر',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/acl/roles/${role.id}`);
        toast.add({ severity: 'success', summary: 'موفق', detail: 'نقش با موفقیت حذف شد', life: 3000 });
        await fetchRoles();
      } catch (error) {
        handleApiError(error);
      }
    }
  });
};

const exportToExcel = () => {
  toast.add({ severity: 'info', summary: 'اطلاع', detail: 'این قابلیت به زودی اضافه می‌شود', life: 3000 });
};

const getInitials = (name) => {
  if (!name) return '?';
  const parts = name.split(' ');
  return parts.length >= 2 ? `${parts[0][0]}${parts[1][0]}` : parts[0][0];
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('fa-IR');
};

const getRoleColor = (roleName) => {
  const colors = {
    'admin': 'bg-gradient-to-br from-red-500 to-pink-600',
    'manager': 'bg-gradient-to-br from-purple-500 to-indigo-600',
    'user': 'bg-gradient-to-br from-blue-500 to-cyan-600',
    'viewer': 'bg-gradient-to-br from-green-500 to-emerald-600',
    'editor': 'bg-gradient-to-br from-orange-500 to-amber-600',
  };

  const role = roleName.toLowerCase();
  for (const [key, color] of Object.entries(colors)) {
    if (role.includes(key)) return color;
  }

  return 'bg-gradient-to-br from-gray-500 to-slate-600';
};

const getAvatarColor = (userId) => {
  const colors = [
    'bg-blue-500',
    'bg-green-500',
    'bg-purple-500',
    'bg-orange-500',
    'bg-pink-500',
    'bg-indigo-500',
    'bg-teal-500',
  ];
  return colors[userId % colors.length];
};

const getUserCountSeverity = (count) => {
  if (count >= 10) return 'danger';
  if (count >= 5) return 'warning';
  if (count >= 1) return 'success';
  return 'secondary';
};
</script>

<style scoped>
.roles-table :deep(.p-datatable-header) {
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.roles-table :deep(.p-datatable-thead > tr > th) {
  background: #f3f4f6;
  font-weight: 600;
  color: #374151;
}

.roles-table :deep(.p-datatable-tbody > tr) {
  transition: background-color 0.2s;
}

.roles-table :deep(.p-datatable-tbody > tr:hover) {
  background: #f9fafb;
}
</style>