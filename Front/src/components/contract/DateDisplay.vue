<!-- src/components/contract/DateDisplay.vue -->
<script setup>
import { computed } from 'vue'
import { useDateFormatter } from '@/composables/useDateFormatter'

const props = defineProps({
  date: { type: [String, Date, Number], default: null },
  format: { type: String, default: 'short' }, // short | medium | long | full
  showTime: { type: Boolean, default: false },
  relative: { type: Boolean, default: false },
  showStatus: { type: Boolean, default: false },
  warningDays: { type: Number, default: 30 },
})

const { toJalali, toJalaliDateTime, formatRelative, getTimeStatus } = useDateFormatter()

const display = computed(() => {
  if (!props.date) return '—'
  return props.showTime ? toJalaliDateTime(props.date) : toJalali(props.date, props.format)
})

const relativeText = computed(() => {
  if (!props.relative || !props.date) return null
  return formatRelative(props.date)
})

const timeStatus = computed(() => {
  if (!props.showStatus || !props.date) return null
  return getTimeStatus(props.date, props.warningDays)
})

const statusColor = computed(() => {
  const map = { red: 'bg-red-500', orange: 'bg-orange-500', yellow: 'bg-yellow-500', green: 'bg-green-500' }
  return map[timeStatus.value?.color] || 'bg-surface-300'
})
</script>

<template>
  <span class="inline-flex items-center gap-1.5 text-sm whitespace-nowrap" :title="display">
    <span v-if="timeStatus" :class="[statusColor, 'w-1.5 h-1.5 rounded-full shrink-0']" />
    <span class="tabular-nums">{{ display }}</span>
    <span v-if="relativeText" class="text-xs text-surface-500">({{ relativeText }})</span>
  </span>
</template>