<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4 p-6 rtl" dir="rtl">

      <!-- هدر مودال -->
      <div class="flex justify-between items-center mb-6 border-b pb-3">
        <h2 class="text-xl font-bold text-gray-800">
          {{ isEditing ? 'ویرایش نقش' : 'ایجاد نقش جدید' }}
        </h2>
        <button @click="closeModal" class="text-gray-500 hover:text-red-500 text-2xl">&times;</button>
      </div>

      <form @submit.prevent="submitForm">

        <!-- نام نقش -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">نام نقش</label>
          <input
              v-model="form.name"
              type="text"
              class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
              placeholder="مثال: admin"
          />
        </div>

        <!-- دسترسی ها -->
        <div class="mb-6">

          <label class="block text-sm font-medium text-gray-700 mb-2">
            دسترسی‌ها
          </label>

          <!-- سرچ -->
          <input
              v-model="search"
              type="text"
              placeholder="جستجو در دسترسی‌ها..."
              class="w-full mb-3 border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
          />

          <!-- انتخاب همه -->
          <div class="flex items-center mb-3">
            <input
                type="checkbox"
                :checked="isAllSelected"
                @change="toggleAll"
                class="form-checkbox h-4 w-4 text-blue-600 rounded"
            >
            <span class="mr-2 text-sm">انتخاب همه</span>
          </div>

          <div class="max-h-56 overflow-y-auto p-3 border border-gray-200 rounded bg-gray-50 space-y-4">

            <div v-for="(modulePermissions,module) in filteredGroupedPermissions" :key="module">

              <!-- عنوان ماژول -->
              <div class="flex justify-between items-center border-b pb-1 mb-2">

                <div class="flex items-center gap-2">

                  <button
                      type="button"
                      class="text-xs text-gray-500"
                      @click="toggleCollapse(module)"
                  >
                    {{ collapsedModules.includes(module) ? '▶' : '▼' }}
                  </button>

                  <span class="font-semibold text-gray-800">
                    {{ getModuleLabel(module) }}
                  </span>

                </div>

                <input
                    type="checkbox"
                    :checked="isModuleSelected(modulePermissions)"
                    @change="toggleModule(modulePermissions)"
                    class="form-checkbox h-4 w-4 text-blue-600 rounded"
                />

              </div>

              <!-- لیست اکشن ها -->
              <div
                  v-show="!collapsedModules.includes(module)"
                  class="grid grid-cols-2 gap-3"
              >
                <label
                    v-for="permission in modulePermissions"
                    :key="permission.id"
                    class="flex items-center space-x-2 space-x-reverse cursor-pointer"
                >
                  <input
                      type="checkbox"
                      :value="permission.name"
                      v-model="form.permissions"
                      class="form-checkbox h-4 w-4 text-blue-600 rounded"
                  />

                  <span class="text-sm text-gray-700">
                    {{ getActionLabel(permission) }}
                  </span>

                </label>
              </div>

            </div>

          </div>

        </div>

        <!-- دکمه ها -->
        <div class="flex justify-end gap-2 border-t pt-4">
          <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
          >
            انصراف
          </button>

          <button
              type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
          >
            {{ isEditing ? 'ثبت تغییرات' : 'ذخیره' }}
          </button>
        </div>

      </form>

    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
  isOpen: Boolean,
  roleToEdit: Object,
  permissions: Array
});

const emit = defineEmits(['close','save']);

const search = ref('');
const collapsedModules = ref([]);

const isEditing = computed(() => !!props.roleToEdit?.id);

const form = ref({
  id:null,
  name:'',
  permissions:[]
});

watch(
    () => props.isOpen,
    (val)=>{
      if(!val) return;

      if(props.roleToEdit){
        form.value={
          id:props.roleToEdit.id,
          name:props.roleToEdit.name,
          permissions:props.roleToEdit.permissions
              ? props.roleToEdit.permissions.map(p=>p.name)
              : []
        };
      }else{
        form.value={id:null,name:'',permissions:[]};
      }
    }
);

const groupedPermissions = computed(()=>{
  const groups={};

  props.permissions.forEach(p=>{
    const [module]=p.name.split('.');

    if(!groups[module]) groups[module]=[];

    groups[module].push(p);
  });

  return groups;
});

const filteredGroupedPermissions = computed(()=>{

  if(!search.value) return groupedPermissions.value;

  const result={};

  Object.entries(groupedPermissions.value).forEach(([module,perms])=>{

    const filtered=perms.filter(p =>
        (p.display_name || p.name).includes(search.value)
    );

    if(filtered.length){
      result[module]=filtered;
    }

  });

  return result;
});

const toggleCollapse=(module)=>{
  if(collapsedModules.value.includes(module)){
    collapsedModules.value=
        collapsedModules.value.filter(m=>m!==module);
  }else{
    collapsedModules.value.push(module);
  }
};

const isAllSelected = computed(()=>{

  return props.permissions.length &&
      form.value.permissions.length === props.permissions.length;

});

const toggleAll=()=>{

  if(isAllSelected.value){
    form.value.permissions=[];
  }else{
    form.value.permissions=props.permissions.map(p=>p.name);
  }

};

const isModuleSelected=(modulePermissions)=>{

  return modulePermissions.every(p =>
      form.value.permissions.includes(p.name)
  );

};

const toggleModule=(modulePermissions)=>{

  const names=modulePermissions.map(p=>p.name);

  if(isModuleSelected(modulePermissions)){

    form.value.permissions=
        form.value.permissions.filter(p=>!names.includes(p));

  }else{

    form.value.permissions=[
      ...new Set([...form.value.permissions,...names])
    ];

  }

};

const getModuleLabel=(module)=>{

  const first=props.permissions.find(p =>
      p.name.startsWith(module+'.')
  );

  if(!first?.display_name) return module;

  const parts=first.display_name.split(' ');
  parts.shift();
  return parts.join(' ');
};

const getActionLabel=(permission)=>{

  if(!permission.display_name)
    return permission.name.split('.')[1];

  return permission.display_name.split(' ')[0];
};

const closeModal=()=>{
  emit('close');
};

const submitForm=()=>{
  emit('save',form.value);
};
</script>
