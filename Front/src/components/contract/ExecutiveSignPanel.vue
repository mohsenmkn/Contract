<!-- src/components/contract/ExecutiveSignPanel.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useWorkflowStore } from '@/stores/contract/workflowStore'
import { useAuthStore } from '@/stores/auth'
import { useNumberFormatter } from '@/composables/useNumberFormatter'
import { showToast } from '@/plugins/toast'

const props = defineProps({
  contract: { type: Object, required: true },
})

const emit = defineEmits(['signed', 'refresh'])

const workflowStore = useWorkflowStore()
const authStore = useAuthStore()
const { formatToman } = useNumberFormatter()

const comments = ref('')
const submitting = ref(false)
const canSignInfo = ref(null)
const selectedRole = ref('')

const amountInToman = computed(() => {
  return (parseFloat(props.contract.current_amount) || 0) / 10
})

const requiresHighLevel = computed(() => {
  return amountInToman.value >= 900_000_000
})

const userRoles = computed(() => {
  return authStore.roles || []
})

const canSign = computed(() => {
  if (!canSignInfo.value) return false
  return canSignInfo.value.can_sign
})

const cannotSignReason = computed(() => {
  if (!canSignInfo.value) return ''
  if (requiresHighLevel.value && userRoles.value.includes('deputy_ceo')) {
    return 'قراردادهای ۹۰۰ میلیون تومان و بالاتر فقط توسط مدیرعامل یا عضو هیئت مدیره قابل امضا هستند.'
  }
  return canSignInfo.value.reason || ''
})

onMounted(async () => {
  const info = await workflowStore.checkCanSign(props.contract.id)
  canSignInfo.value = info

  // انتخاب خودکار نقش کاربر
  if (userRoles.value.includes('ceo')) selectedRole.value = 'ceo'
  else if (userRoles.value.includes('board_member')) selectedRole.value = 'board_member'
  else if (userRoles.value.includes('deputy_ceo')) selectedRole.value = 'deputy_ceo'
})

const handleSign = async () => {
  if (!selectedRole.value) {
    showToast({ severity: 'warn', summary: 'خطا', detail: 'لطفاً نقش خود را انتخاب کنید', life: 3000 })
    return
  }

  submitting.value = true
  try {
    await workflowStore.executiveSign(props.contract.id, {
      role: selectedRole.value,
      comments: comments.value,
    })

    showToast({ severity: 'success', summary: 'امضا ثبت شد', detail: 'قرارداد با موفقیت امضا شد', life: 4000 })
    emit('signed')
    emit('refresh')
  } catch (err) {
    showToast({
      severity: 'error',
      summary: 'خطا در امضا',
      detail: err.response?.data?.message || err.message,
      life: 5000
    })
  } finally {
    submitting.value = false
  }
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
          <span class="text-surface-500 block mb-1">سطح امضا:</span>
          <span :class="requiresHighLevel ? 'text-red-600 font-bold' : 'text-green-600 font-bold'">
            {{ requiresHighLevel ? 'سطح بالا (مدیرعامل/هیئت مدیره)' : 'معاون مالی' }}
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
        <div>
          <label class="block text-sm font-medium mb-2">نقش امضا کننده</label>
          <Select
              v-model="selectedRole"
              :options="[
              { label: 'مدیرعامل', value: 'ceo' },
              { label: 'معاون مالی', value: 'deputy_ceo' },
              { label: 'عضو هیئت مدیره', value: 'board_member' },
            ]"
              option-label="label"
              option-value="value"
              class="w-full"
              :disabled="userRoles.includes('deputy_ceo') && requiresHighLevel"
          />
        </div>

        <div>
          <label class="block text-sm font-medium mb-2">توضیحات (اختیاری)</label>
          <Textarea
              v-model="comments"
              rows="3"
              class="w-full"
              placeholder="توضیحات امضا..."
          />
        </div>

        <Button
            label="ثبت امضا"
            icon="pi pi-check"
            :loading="submitting"
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