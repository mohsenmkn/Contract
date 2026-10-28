<!-- src/components/contract/AmountInput.vue -->
<script setup>
import { ref, watch, computed } from 'vue'
import { useNumberFormatter } from '@/composables/useNumberFormatter'

const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  label: { type: String, default: '' },
  placeholder: { type: String, default: '۰' },
  disabled: { type: Boolean, default: false },
  invalid: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  min: { type: Number, default: 0 },
  inputId: { type: String, default: () => `amount-${Math.random().toString(36).slice(2, 9)}` },
})

const emit = defineEmits(['update:modelValue', 'change', 'blur'])
const { toEnglishDigits, formatNumber } = useNumberFormatter()

const focused = ref(false)
const text = ref('')

const syncText = () => {
  if (props.modelValue === null || props.modelValue === '' || props.modelValue === undefined) {
    text.value = ''
    return
  }
  const n = parseFloat(props.modelValue)
  text.value = isNaN(n) ? '' : n.toLocaleString('en-US', { maximumFractionDigits: 0 })
}

watch(() => props.modelValue, () => {
  if (!focused.value) syncText()
}, { immediate: true })

const handleFocus = () => {
  focused.value = true
  const n = parseFloat(toEnglishDigits(String(props.modelValue ?? '')))
  text.value = isNaN(n) ? '' : String(n)
}

const handleInput = (event) => {
  const raw = toEnglishDigits(event.target.value)
  const cleaned = raw.replace(/[^\d.]/g, '')
  text.value = cleaned

  if (cleaned === '') {
    emit('update:modelValue', null)
    return
  }

  const n = Number(cleaned)
  if (!isNaN(n)) emit('update:modelValue', n)
}

const handleBlur = () => {
  focused.value = false

  const n = parseFloat(toEnglishDigits(text.value))
  let value = isNaN(n) ? null : Math.max(props.min, n)

  emit('update:modelValue', value)
  emit('change', value)
  emit('blur')

  syncText()
}

const hasError = computed(() => {
  if (!props.required || props.modelValue === null) return props.invalid
  return props.invalid || Number(props.modelValue) < props.min
})
</script>

<template>
  <div class="flex flex-col gap-1.5 w-full">
    <label v-if="label" :for="inputId" class="text-sm font-medium text-surface-700 dark:text-surface-200">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <div class="relative">
      <input
          :id="inputId"
          :value="text"
          type="text"
          inputmode="numeric"
          autocomplete="off"
          dir="ltr"
          class="w-full rounded-md border px-3 py-2 pl-14 text-left text-sm outline-none transition
               bg-surface-0 dark:bg-surface-900
               border-surface-300 dark:border-surface-600
               focus:border-primary focus:ring-1 focus:ring-primary
               disabled:opacity-50 disabled:cursor-not-allowed"
          :class="{ '!border-red-500 focus:!ring-red-500': hasError }"
          :placeholder="placeholder"
          :disabled="disabled"
          :aria-invalid="hasError"
          @focus="handleFocus"
          @input="handleInput"
          @blur="handleBlur"
      />

      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-surface-500 pointer-events-none">
        ریال
      </span>
    </div>

    <small v-if="hasError" class="text-xs text-red-500">
      مبلغ باید معتبر و حداقل {{ formatNumber(min) }} باشد.
    </small>
  </div>
</template>