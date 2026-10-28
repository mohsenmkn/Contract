<!-- src/components/contract/EmptyState.vue -->
<script setup>
import Button from 'primevue/button'

defineProps({
  title: { type: String, default: 'موردی یافت نشد' },
  description: { type: String, default: '' },
  icon: { type: String, default: 'pi pi-inbox' },
  actionLabel: { type: String, default: '' },
  compact: { type: Boolean, default: false },
})

defineEmits(['action'])
</script>

<template>
  <div
      :class="[
      'flex flex-col items-center justify-center text-center',
      compact ? 'py-6 px-4' : 'py-14 px-6',
    ]"
  >
    <span class="w-16 h-16 rounded-full bg-surface-100 dark:bg-surface-800 flex items-center justify-center mb-4">
      <i :class="[icon, 'text-2xl text-surface-400']" />
    </span>

    <h3 class="font-semibold text-surface-700 dark:text-surface-200 mb-2">{{ title }}</h3>
    <p v-if="description" class="text-sm text-surface-500 max-w-sm leading-6">{{ description }}</p>

    <Button
        v-if="actionLabel"
        :label="actionLabel"
        icon="pi pi-plus"
        class="mt-5"
        @click="$emit('action')"
    />
  </div>
</template>