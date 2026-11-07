<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
          v-if="isOpen"
          class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 backdrop-blur-sm overflow-y-auto py-8 px-4"
          @click.self="closeModal"
      >
        <!-- ✅ عرض از max-w-lg به max-w-5xl (1024px) افزایش یافت -->
        <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl rtl overflow-hidden"
            dir="rtl"
        >
          <!-- هدر مودال -->
          <div class="bg-gradient-to-l from-purple-600 to-indigo-600 px-8 py-5">
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                  <ShieldCheck class="w-6 h-6 text-white" />
                </div>
                <div>
                  <h2 class="text-xl font-bold text-white">
                    {{ isEditing ? 'ویرایش نقش' : 'ایجاد نقش جدید' }}
                  </h2>
                  <p class="text-white/80 text-sm mt-0.5">
                    {{ isEditing ? 'تغییر در تنظیمات و دسترسی‌های نقش' : 'تعریف یک نقش کاربری با دسترسی‌های مشخص' }}
                  </p>
                </div>
              </div>
              <button
                  @click="closeModal"
                  class="w-9 h-9 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors"
              >
                <X class="w-5 h-5" />
              </button>
            </div>
          </div>

          <form @submit.prevent="submitForm" class="p-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
              <!-- ✅ بخش چپ: اطلاعات پایه نقش -->
              <div class="lg:col-span-1 space-y-6">
                <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl p-6 border border-purple-100">
                  <h3 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <Info class="w-4 h-4 text-purple-600" />
                    اطلاعات نقش
                  </h3>

                  <!-- نام سیستمی نقش -->
                  <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                      نام سیستمی <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all font-mono"
                        dir="ltr"
                        required
                        :disabled="isEditing"
                        placeholder="مثال: warehouse_approver"
                    />
                    <p class="text-xs text-gray-500 mt-1">
                      کلید ثابت نقش برای استفاده سیستم؛ پس از ایجاد قابل تغییر نیست
                    </p>
                  </div>

                  <!-- نام نمایشی (فارسی) - ✅ اضافه شده -->
                  <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                      نام نمایشی (فارسی) <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.display_name"
                        type="text"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                        required
                        placeholder="مثال: دریافت / رد کالا پیش‌انبار"
                    />
                    <p class="text-xs text-gray-500 mt-1">
                      نامی که در لیست و گزارش‌ها نمایش داده می‌شود
                    </p>
                  </div>

                  <!-- توضیحات (اختیاری) -->
                  <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                      توضیحات
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none"
                        placeholder="توضیحات مختصر درباره این نقش..."
                    ></textarea>
                  </div>
                </div>

                <!-- ✅ آمار سریع دسترسی‌ها -->
                <div class="bg-white rounded-xl p-5 border border-gray-200">
                  <h3 class="text-sm font-bold text-gray-700 mb-3">خلاصه دسترسی‌ها</h3>
                  <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                      <span class="text-sm text-gray-600">انتخاب شده</span>
                      <span class="font-bold text-purple-600">{{ form.permissions.length }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                      <span class="text-sm text-gray-600">کل دسترسی‌ها</span>
                      <span class="font-bold text-gray-700">{{ permissions.length }}</span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                      <div
                          class="h-full bg-gradient-to-l from-purple-500 to-indigo-500 transition-all duration-300"
                          :style="{ width: progressPercent + '%' }"
                      ></div>
                    </div>
                    <div class="text-xs text-gray-500 text-center">
                      {{ progressPercent }}% تکمیل شده
                    </div>
                  </div>
                </div>
              </div>

              <!-- ✅ بخش راست: لیست دسترسی‌ها -->
              <div class="lg:col-span-2">
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                  <!-- هدر دسترسی‌ها -->
                  <div class="bg-gray-50 px-5 py-4 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
                      <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                        <KeyRound class="w-4 h-4 text-indigo-600" />
                        دسترسی‌های نقش
                      </h3>
                      <div class="flex items-center gap-2 w-full sm:w-auto">
                        <button
                            type="button"
                            @click="toggleAll"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors"
                            :class="isAllSelected
                            ? 'bg-red-100 text-red-700 hover:bg-red-200'
                            : 'bg-green-100 text-green-700 hover:bg-green-200'"
                        >
                          {{ isAllSelected ? '❌ لغو همه' : '✅ انتخاب همه' }}
                        </button>
                        <button
                            type="button"
                            @click="expandAll"
                            class="px-3 py-1.5 text-xs font-medium bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
                        >
                          {{ allExpanded ? 'بستن همه' : 'باز کردن همه' }}
                        </button>
                      </div>
                    </div>

                    <!-- جستجو -->
                    <div class="relative mt-3">
                      <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                      <input
                          v-model="search"
                          type="text"
                          placeholder="جستجو در دسترسی‌ها..."
                          class="w-full pr-9 pl-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                      />
                      <button
                          v-if="search"
                          type="button"
                          @click="search = ''"
                          class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                      >
                        <X class="w-4 h-4" />
                      </button>
                    </div>
                  </div>

                  <!-- لیست ماژول‌ها -->
                  <div class="max-h-[500px] overflow-y-auto p-5 space-y-3 bg-gradient-to-b from-gray-50/30 to-white">
                    <div v-if="Object.keys(filteredGroupedPermissions).length === 0" class="text-center py-12">
                      <Search class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                      <p class="text-gray-500">هیچ دسترسی‌ای با این جستجو یافت نشد</p>
                      <button
                          type="button"
                          @click="search = ''"
                          class="mt-3 text-sm text-indigo-600 hover:text-indigo-800"
                      >
                        پاک کردن جستجو
                      </button>
                    </div>

                    <div
                        v-for="(modulePermissions, module) in filteredGroupedPermissions"
                        :key="module"
                        class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm hover:shadow-md transition-shadow"
                    >
                      <!-- هدر ماژول -->
                      <div class="bg-gray-50 px-4 py-3 flex justify-between items-center border-b border-gray-200">
                        <div class="flex items-center gap-2 cursor-pointer flex-1" @click="toggleCollapse(module)">
                          <ChevronDown
                              class="w-4 h-4 text-gray-500 transition-transform duration-200"
                              :class="{ '-rotate-90': collapsedModules.includes(module) }"
                          />
                          <div>
                            <span class="font-bold text-gray-800 text-sm">
                              {{ getModuleLabel(module) }}
                            </span>
                            <span class="text-xs text-gray-400 mr-2">
                              ({{ modulePermissions.length }} دسترسی)
                            </span>
                          </div>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer px-2 py-1 rounded hover:bg-white transition-colors">
                          <span class="text-xs text-gray-500">
                            {{ getModuleSelectedCount(modulePermissions) }}/{{ modulePermissions.length }}
                          </span>
                          <input
                              type="checkbox"
                              :checked="isModuleSelected(modulePermissions)"
                              :indeterminate="isModuleIndeterminate(modulePermissions)"
                              @change="toggleModule(modulePermissions)"
                              class="form-checkbox h-4 w-4 text-indigo-600 rounded focus:ring-indigo-500"
                          />
                        </label>
                      </div>

                      <!-- لیست اکشن‌ها -->
                      <div v-show="!collapsedModules.includes(module)" class="p-3">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                          <label
                              v-for="permission in modulePermissions"
                              :key="permission.id"
                              class="flex items-center gap-2 p-2 rounded-lg hover:bg-indigo-50 cursor-pointer transition-colors group"
                              :class="{ 'bg-indigo-50': form.permissions.includes(permission.name) }"
                          >
                            <input
                                type="checkbox"
                                :value="permission.name"
                                v-model="form.permissions"
                                class="form-checkbox h-4 w-4 text-indigo-600 rounded focus:ring-indigo-500"
                            />
                            <span
                                class="text-sm text-gray-700 group-hover:text-indigo-700 select-none"
                                :title="permission.display_name || permission.name"
                            >
                              {{ getActionLabel(permission,module) }}
                            </span>
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- دکمه‌های عملیات -->
            <div class="flex justify-between items-center gap-3 border-t border-gray-200 mt-8 pt-6">
              <div class="text-sm text-gray-500">
                <span class="font-medium">{{ form.permissions.length }}</span> دسترسی انتخاب شده
              </div>
              <div class="flex gap-3">
                <button
                    type="button"
                    @click="closeModal"
                    class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium transition-colors"
                >
                  انصراف
                </button>
                <button
                    type="submit"
                    class="px-8 py-2.5 bg-gradient-to-l from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2"
                >
                  <Save class="w-4 h-4" />
                  {{ isEditing ? 'ثبت تغییرات' : 'ذخیره نقش' }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { ShieldCheck, X, Info, KeyRound, Search, ChevronDown, Save } from 'lucide-vue-next';

const props = defineProps({
  isOpen: Boolean,
  roleToEdit: Object,
  permissions: Array
});

const emit = defineEmits(['close', 'save']);

const search = ref('');
const collapsedModules = ref([]);

const isEditing = computed(() => !!props.roleToEdit?.id);

const form = ref({
  id: null,
  name: '',
  display_name: '',
  description: '',
  permissions: []
});

watch(
    () => props.isOpen,
    (val) => {
      if (!val) return;

      if (props.roleToEdit) {
        form.value = {
          id: props.roleToEdit.id,
          name: props.roleToEdit.name,
          display_name: props.roleToEdit.display_name || props.roleToEdit.name,
          description: props.roleToEdit.description || '',
          permissions: props.roleToEdit.permissions
              ? props.roleToEdit.permissions.map(p => typeof p === 'object' ? p.name : p)
              : []
        };
      } else {
        form.value = { id: null, name: '', display_name: '', description: '', permissions: [] };
      }
      search.value = '';
      collapsedModules.value = [];
    }
);

const groupedPermissions = computed(() => {
  const groups = {};
  props.permissions.forEach(p => {
    const [module] = p.name.split('.');
    if (!groups[module]) groups[module] = [];
    groups[module].push(p);
  });
  return groups;
});

const filteredGroupedPermissions = computed(() => {
  if (!search.value) return groupedPermissions.value;

  const result = {};
  const searchLower = search.value.toLowerCase();

  Object.entries(groupedPermissions.value).forEach(([module, perms]) => {
    const filtered = perms.filter(p =>
        (p.display_name || '').toLowerCase().includes(searchLower) ||
        (p.name || '').toLowerCase().includes(searchLower) ||
        getModuleLabel(module).toLowerCase().includes(searchLower)
    );

    if (filtered.length) {
      result[module] = filtered;
    }
  });

  return result;
});

const toggleCollapse = (module) => {
  const index = collapsedModules.value.indexOf(module);
  if (index > -1) {
    collapsedModules.value.splice(index, 1);
  } else {
    collapsedModules.value.push(module);
  }
};

const allExpanded = computed(() => {
  return Object.keys(filteredGroupedPermissions.value).every(
      m => !collapsedModules.value.includes(m)
  );
});

const expandAll = () => {
  if (allExpanded.value) {
    collapsedModules.value = Object.keys(filteredGroupedPermissions.value);
  } else {
    collapsedModules.value = [];
  }
};

const isAllSelected = computed(() => {
  return props.permissions.length > 0 &&
      form.value.permissions.length === props.permissions.length;
});

const toggleAll = () => {
  if (isAllSelected.value) {
    form.value.permissions = [];
  } else {
    form.value.permissions = props.permissions.map(p => p.name);
  }
};

const isModuleSelected = (modulePermissions) => {
  return modulePermissions.length > 0 &&
      modulePermissions.every(p => form.value.permissions.includes(p.name));
};

const isModuleIndeterminate = (modulePermissions) => {
  const selectedCount = modulePermissions.filter(p =>
      form.value.permissions.includes(p.name)
  ).length;
  return selectedCount > 0 && selectedCount < modulePermissions.length;
};

const getModuleSelectedCount = (modulePermissions) => {
  return modulePermissions.filter(p =>
      form.value.permissions.includes(p.name)
  ).length;
};

const toggleModule = (modulePermissions) => {
  const names = modulePermissions.map(p => p.name);
  if (isModuleSelected(modulePermissions)) {
    form.value.permissions = form.value.permissions.filter(p => !names.includes(p));
  } else {
    form.value.permissions = [...new Set([...form.value.permissions, ...names])];
  }
};

const getModuleLabel = (module) => {
  const first = props.permissions.find(p => p.name.startsWith(module + '.'));
  if (!first?.display_name) return module;
  const parts = first.display_name.split(' ');
  if (parts.length > 1) {
    parts.shift();
    return parts.join(' ');
  }
  return module.display_name;
};

const getActionLabel = (permission, module) => {
  //console.log('permission:', permission, 'module:', module)
  // اگر permission نال بود
  if (!permission?.display_name) {
    return permission?.name ?? '';
  }

  const displayName = permission.display_name;

  // module ممکنه آبجکت باشه یا رشته
  const moduleName = typeof module === 'string'
      ? module
      : module?.display_name;

  // اگه اسم ماژول نبود، همون display_name رو برگردون
  if (!moduleName) {
    return displayName.trim();
  }

  // escape کردن کاراکترهای خاص regex
  const escaped = moduleName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const regex = new RegExp(escaped, 'gi');

  return displayName
      .replace(regex, '')
      .replace(/\s+/g, ' ')
      .trim();
};

const progressPercent = computed(() => {
  if (props.permissions.length === 0) return 0;
  return Math.round((form.value.permissions.length / props.permissions.length) * 100);
});

const closeModal = () => {
  emit('close');
};

const submitForm = () => {
  emit('save', { ...form.value });
};
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.25s ease;
}
.modal-fade-enter-active .bg-white,
.modal-fade-leave-active .bg-white {
  transition: transform 0.25s ease, opacity 0.25s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
.modal-fade-enter-from .bg-white,
.modal-fade-leave-to .bg-white {
  transform: scale(0.95);
  opacity: 0;
}

/* اسکرول‌بار سفارشی */
.max-h-\[500px\]::-webkit-scrollbar {
  width: 6px;
}
.max-h-\[500px\]::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}
.max-h-\[500px\]::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}
.max-h-\[500px\]::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
