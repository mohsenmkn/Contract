<!-- src/components/contract/ExecutiveSignPanel.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useWorkflowStore } from '@/stores/workflowStore'
import { useAuthStore } from '@/stores/auth'
import { useNumberFormatter } from '@/composables/useNumberFormatter'
import { showToast } from '@/plugins/toast'
import { useRouter } from 'vue-router'

const props = defineProps({
  contract: { type: Object, required: true },
})

const workflowStore = useWorkflowStore()
const router = useRouter()
const authStore = useAuthStore()
const { formatToman } = useNumberFormatter()

const canSignInfo = ref(null)

const amountInToman = computed(() => {
  return (parseFloat(props.contract.current_amount) || 0) / 10
})

const requiresBoardSignature = computed(() => !!props.contract.requires_board_signature)

const canSign = computed(() => {
  if (!canSignInfo.value) return false
  return canSignInfo.value.can_sign
})

const cannotSignReason = computed(() => {
  if (!canSignInfo.value) return ''
  if (authStore.hasRole('deputy_ceo')) {
    return 'امضای نهایی قراردادها بر عهده مدیرعامل است.'
  }
  return canSignInfo.value.reason || ''
})

onMounted(async () => {
  const info = await workflowStore.checkCanSign(props.contract.id)
  canSignInfo.value = info
})

const handleSign = async () => {
  if (!canSignInfo.value?.signature_id) {
    showToast({ severity: 'info', summary: 'امضا', detail: 'در حال حاضر امضایی برای شما در انتظار نیست.', life: 3000 })
    return
  }
  router.push(`/signatures/${canSignInfo.value.signature_id}`)
}
</script>

<template>
  <div class="executive-sign-panel border border-surface-200 dark:border-surface-700 rounded-xl p-4">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-sm flex items-center gap-2">
        <i class="pi pi-user-tie text-primary" />
        امضای اجرایی
      </h3>
      <Tag
          v-if="contract.status === 'pending_executive_sign'"
          value="در انتظار امضا"
          severity="warn"
      />
      <Tag
          v-else-if="contract.status === 'pending_notification'"
          value="امضا شده"
          severity="success"
      />
    </div>

    <!-- اطلاعات مبلغ -->
    <div class="bg-surface-50 dark:bg-surface-800/50 rounded-lg p-3 mb-4">
      <div class="grid grid-cols-2 gap-3 text-sm">
        <div>
          <span class="text-surface-500 block mb-1">مبلغ قرارداد:</span>
          <span class="font-bold text-primary">{{ formatToman(amountInToman * 10) }}</span>
        </div>
        <div>
          <span class="text-surface-500 block mb-1">امضاکنندگان لازم:</span>
          <span class="text-primary font-bold">
            {{ requiresBoardSignature ? 'مدیرعامل و عضو هیئت‌مدیره' : 'مدیرعامل' }}
          </span>
        </div>
      </div>
    </div>

    <!-- پیام محدودیت -->
    <Message v-if="cannotSignReason" severity="warn" :closable="false" class="mb-4">
      <i class="pi pi-exclamation-triangle ml-2" />
      {{ cannotSignReason }}
    </Message>

    <!-- فرم امضا -->
    <template v-if="canSign && contract.status === 'pending_executive_sign'">
      <div class="space-y-3">
        <Button
            label="ادامه به صفحه امضا"
            icon="pi pi-arrow-left"
            class="w-full"
            @click="handleSign"
        />
      </div>
    </template>

    <!-- وضعیت امضا شده -->
    <div v-else-if="contract.executive_signatures?.length" class="space-y-2">
      <div
          v-for="sig in contract.executive_signatures"
          :key="sig.id"
          class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800"
      >
        <i class="pi pi-check-circle text-green-600 text-xl" />
        <div class="flex-1">
          <div class="font-medium text-sm">{{ sig.signer?.name }}</div>
          <div class="text-xs text-surface-500">
            {{ sig.signer_role_label }} • {{ sig.signed_at }}
          </div>
        </div>
      </div>
    </div>

    <Message v-else severity="info" :closable="false">
      این قرارداد هنوز امضای اجرایی دریافت نکرده است.
    </Message>
  </div>
</template>
