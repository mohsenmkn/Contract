<!-- src/components/contract/PersianDatePicker.vue -->
<script setup>
import { computed } from 'vue'
import PersianDatePicker from 'vue3-persian-datetime-picker'

const props = defineProps({
  modelValue: { type: [String, Date], default: null },
  label: { type: String, default: '' },
  placeholder: { type: String, default: 'انتخاب تاریخ' },
  disabled: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  // خروجی میلادی ISO برای بک‌اند
  outputFormat: { type: String, default: 'YYYY-MM-DD' },
  displayFormat: { type: String, default: 'jYYYY/jMM/jDD' },
  inputId: { type: String, default: () => `date-${Math.random().toString(36).slice(2, 9)}` },
})

const emit = defineEmits(['update:modelValue', 'change'])

const value = computed({
  get: () => props.modelValue || '',
  set: (val) => {
    emit('update:modelValue', val || null)
    emit('change', val || null)
  },
})

const inputClasses = [
  'w-full rounded-md border px-3 py-2 text-sm outline-none transition',
  'bg-surface-0 dark:bg-surface-900',
  'border-surface-300 dark:border-surface-600',
  'focus:border-primary focus:ring-1 focus:ring-primary',
  props.invalid ? '!border-red-500' : '',
].join(' ')
</script>

<template>
  <div class="flex flex-col gap-1.5 w-full">
    <label v-if="label" :for="inputId" class="text-sm font-medium text-surface-700 dark:text-surface-200">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <div :id="inputId" class="relative w-full" dir="rtl">
      <PersianDatePicker
          v-model="value"
          :format="outputFormat"
          :display-format="displayFormat"
          locale="fa"
          :first-day-of-week="6"
          :use-input="true"
          :readonly="disabled"
          :disabled="disabled"
          :placeholder="placeholder"
          :input-class="inputClasses"
      />
    </div>
  </div>
</template>

<style>
/* جلوگیری از برهم خوردن direction تقویم */
.persian-datepicker {
  direction: rtl;
  z-index: 1050 !important;
}
.persian-datepicker .p-datetimepicker-container {
  direction: rtl;
}
</style>