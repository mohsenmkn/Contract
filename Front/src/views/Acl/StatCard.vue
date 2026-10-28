<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center gap-4">
      <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', iconBg]">
        <i :class="[icon, 'text-xl', iconColor]"></i>
      </div>
      <div>
        <div class="text-2xl font-bold text-gray-800">{{ value }}</div>
        <div class="text-sm text-gray-500">{{ label }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  icon: { type: String, required: true },
  iconBg: { type: String, default: 'bg-blue-100' },
  iconColor: { type: String, default: 'text-blue-600' },
  value: { type: [Number, String], default: 0 },
  label: { type: String, default: '' },
})
</script>