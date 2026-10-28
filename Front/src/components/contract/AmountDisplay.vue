<!-- src/components/contract/AmountDisplay.vue -->
<script setup>
import { computed } from 'vue'
import { useNumberFormatter } from '@/composables/useNumberFormatter'

const props = defineProps({
  amount: { type: [Number, String], default: 0 },
  currency: { type: String, default: 'IRR' },
  // rial | toman | smart
  unit: { type: String, default: 'rial', validator: v => ['rial', 'toman', 'smart'].includes(v) },
  showUnit: { type: Boolean, default: true },
  size: { type: String, default: 'base', validator: v => ['xs', 'sm', 'base', 'lg', 'xl'].includes(v) },
  colorize: { type: Boolean, default: false },
  exactTitle: { type: Boolean, default: true },
})

const { formatRial, formatToman, formatSmartAmount } = useNumberFormatter()

const sizes = {
  xs: 'text-xs',
  sm: 'text-sm',
  base: 'text-base',
  lg: 'text-lg',
  xl: 'text-2xl font-bold',
}

const numericValue = computed(() => {
  const n = parseFloat(props.amount)
  return isNaN(n) ? 0 : n
})

const display = computed(() => {
  switch (props.unit) {
    case 'toman': return formatToman(numericValue.value, props.showUnit)
    case 'smart': return formatSmartAmount(numericValue.value, props.currency)
    default: return formatRial(numericValue.value, props.showUnit)
  }
})

const colorClass = computed(() => {
  if (!props.colorize) return 'text-surface-800 dark:text-surface-100'
  if (numericValue.value > 0) return 'text-green-600 dark:text-green-400'
  if (numericValue.value < 0) return 'text-red-600 dark:text-red-400'
  return 'text-surface-500'
})

const title = computed(() =>
    props.exactTitle && props.unit === 'smart'
        ? formatRial(numericValue.value)
        : null
)
</script>

<template>
  <span
      :class="[sizes[size], colorClass, 'whitespace-nowrap tabular-nums']"
      :title="title"
      dir="rtl"
  >
    {{ display }}
  </span>
</template>