<!-- src/components/contract/ContractTypeBadge.vue -->
<script setup>
import { computed } from 'vue'
import Tag from 'primevue/tag'
import { useContract } from '@/composables/useContract'

const props = defineProps({
  type: { type: String, default: 'downstream' },
  outlined: { type: Boolean, default: true },
})

const { getTypeInfo } = useContract()
const info = computed(() => getTypeInfo(props.type))
</script>

<template>
  <Tag
      :value="info.label"
      :severity="type === 'upstream' ? 'info' : 'success'"
      :icon="info.icon"
      :outlined="outlined"
      class="!text-xs rounded-md"
  />
</template>