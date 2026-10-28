<!-- src/components/contract/StatCard.vue -->
<script setup>
import { computed } from 'vue'
import Skeleton from 'primevue/skeleton'
import { useNumberFormatter } from '@/composables/useNumberFormatter'

const props = defineProps({
  title: { type: String, required: true },
  value: { type: [Number, String], default: 0 },
  icon: { type: String, default: 'pi pi-chart-bar' },
  color: { type: String, default: 'primary' },
  unit: { type: String, default: '' }, // rial | toman | smart | count
  trend: { type: Number, default: null },
  trendLabel: { type: String, default: 'نسبت به دوره قبل' },
  loading: { type: Boolean, default: false },
  clickable: { type: Boolean, default: false },
})

defineEmits(['click'])
const { formatRial, formatSmartAmount, formatCount } = useNumberFormatter()

const colors = {
  primary: 'bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300',
  success: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
  warn: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
  danger: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
  info: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
}

const displayValue = computed(() => {
  switch (props.unit) {
    case 'rial': return formatRial(props.value)
    case 'smart': return formatSmartAmount(props.value)
    case 'count': return formatCount(props.value)
    default: return props.value
  }
})
</script>

<template>
  <div
      :class="[
      'relative rounded-xl border border-surface-200 dark:border-surface-700 p-5 transition-all duration-200',
      'bg-surface-0 dark:bg-surface-900 hover:shadow-md',
      clickable ? 'cursor-pointer hover:-translate-y-0.5 hover:border-primary/40' : '',
    ]"
      @click="clickable && $emit('click')"
  >
    <Skeleton v-if="loading" height="5rem" border-radius="12px" />

    <div v-else class="flex items-start justify-between gap-4">
      <div class="min-w-0">
        <p class="text-sm text-surface-500 mb-3">{{ title }}</p>

        <p class="text-xl font-bold text-surface-900 dark:text-surface-0 tabular-nums break-words">
          {{ displayValue }}
        </p>

        <div v-if="trend !== null" class="flex items-center gap-1.5 mt-3 text-xs">
          <i
              :class="[
              trend >= 0 ? 'pi pi-arrow-up text-green-600' : 'pi pi-arrow-down text-red-600',
            ]"
          />
          <span :class="trend >= 0 ? 'text-green-600' : 'text-red-600'">
            {{ Math.abs(trend).toLocaleString('fa-IR') }}٪
          </span>
          <span class="text-surface-400">{{ trendLabel }}</span>
        </div>
      </div>

      <span :class="[colors[color] || colors.primary, 'w-12 h-12 rounded-xl flex items-center justify-center shrink-0']">
        <i :class="[icon, 'text-xl']" />
      </span>
    </div>
  </div>
</template>