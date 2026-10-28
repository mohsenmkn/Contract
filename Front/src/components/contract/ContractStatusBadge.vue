<!-- src/components/contract/ContractStatusBadge.vue -->
<script setup>
import { computed } from 'vue'
import Tag from 'primevue/tag'
import { useContract } from '@/composables/useContract'

const props = defineProps({
  status: { type: String, default: 'draft' },
  size: { type: String, default: 'sm' },
  withIcon: { type: Boolean, default: true },
})

const { getStatusInfo } = useContract()

const info = computed(() => getStatusInfo(props.status))
</script>

<template>
  <Tag
      :value="info.label"
      :severity="info.color"
      :icon="withIcon ? info.icon : undefined"
      :class="[size === 'xs' ? '!text-[10px] !py-0.5' : '!text-xs', 'rounded-md']"
  />
</template>