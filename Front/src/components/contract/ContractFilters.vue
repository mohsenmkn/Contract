<!-- src/components/contract/ContractFilters.vue -->
<script setup>
import { ref, watch, computed } from 'vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import PersianDatePicker from './PersianDatePicker.vue'
import { useContract } from '@/composables/useContract'

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'apply', 'reset'])
const { typeOptions, statusOptions } = useContract()

const defaults = {
  type: null,
  status: null,
  party_id: null,
  search: '',
  expiring_soon: null,
  from_date: null,
  to_date: null,
}

const daysOptions = [
  { label: '۷ روز آینده', value: 7 },
  { label: '۱۵ روز آینده', value: 15 },
  { label: '۳۰ روز آینده', value: 30 },
  { label: '۶۰ روز آینده', value: 60 },
  { label: '۹۰ روز آینده', value: 90 },
]

const draft = ref({ ...defaults, ...props.modelValue })
const expanded = ref(false)

watch(() => props.modelValue, (val) => {
  draft.value = { ...defaults, ...val }
}, { deep: true })

const activeCount = computed(() =>
    Object.entries(draft.value).filter(([, v]) =>
        v !== null && v !== '' && v !== undefined
    ).length
)

const apply = () => {
  emit('update:modelValue', { ...draft.value })
  emit('apply', { ...draft.value })
}

const reset = () => {
  draft.value = { ...defaults }
  emit('update:modelValue', { ...defaults })
  emit('reset', { ...defaults })
}

watch(() => draft.value.search, (val, old) => {
  if (old === undefined) return
  clearTimeout(searchTimer)
  searchTimer = setTimeout(apply, 500)
})
let searchTimer = null
</script>

<template>
  <div class="space-y-4">
    <!-- ردیف اصلی -->
    <div class="flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-56">
        <i class="pi pi-search absolute right-3 top-1/2 -translate-y-1/2 text-surface-400 text-sm" />
        <InputText
            v-model="draft.search"
            placeholder="جستجو در شماره قرارداد یا موضوع..."
            class="w-full !pr-10"
            @keyup.enter="apply"
        />
      </div>

      <Select
          v-model="draft.type"
          :options="typeOptions"
          option-label="label"
          option-value="value"
          placeholder="نوع قرارداد"
          class="w-48"
          show-clear
          @change="apply"
      />

      <Select
          v-model="draft.status"
          :options="statusOptions"
          option-label="label"
          option-value="value"
          placeholder="وضعیت"
          class="w-56"
          show-clear
          @change="apply"
      />

      <Button
          :label="expanded ? 'بستن فیلترها' : 'فیلتر پیشرفته'"
          :icon="expanded ? 'pi pi-chevron-up' : 'pi pi-filter'"
          severity="secondary"
          outlined
          :badge="activeCount ? String(activeCount) : undefined"
          @click="expanded = !expanded"
      />

      <Button label="اعمال" icon="pi pi-check" :loading="loading" @click="apply" />
      <Button label="پاک‌کردن" icon="pi pi-refresh" severity="secondary" text @click="reset" />
    </div>

    <!-- فیلترهای پیشرفته -->
    <Transition
        enter-active-class="transition duration-200"
        enter-from-class="opacity-0 -translate-y-2"
        leave-active-class="transition duration-150"
        leave-to-class="opacity-0"
    >
      <div
          v-if="expanded"
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 p-4 rounded-lg bg-surface-50 dark:bg-surface-800/50"
      >
        <PersianDatePicker v-model="draft.from_date" label="از تاریخ شروع" />
        <PersianDatePicker v-model="draft.to_date" label="تا تاریخ پایان" />

        <div class="flex flex-col gap-1.5">
          <label class="text-sm font-medium text-surface-700 dark:text-surface-200">
            قراردادهای در حال اتمام
          </label>
          <Select
              v-model="draft.expiring_soon"
              :options="daysOptions"
              option-label="label"
              option-value="value"
              placeholder="همه قراردادها"
              show-clear
          />
        </div>

        <div class="flex items-end">
          <Button label="جستجوی پیشرفته" icon="pi pi-search" class="w-full" @click="apply" />
        </div>
      </div>
    </Transition>
  </div>
</template>