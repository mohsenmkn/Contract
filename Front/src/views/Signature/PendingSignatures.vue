<!-- src/views/Signature/PendingSignatures.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSignatureStore } from '@/stores/signatureStore'
import { useDateFormatter } from '@/composables/useDateFormatter'
import DateDisplay from '@/components/contract/DateDisplay.vue'
import EmptyState from '@/components/contract/EmptyState.vue'
import Tag from 'primevue/tag'

const router = useRouter()
const signatureStore = useSignatureStore()
const { toJalaliDateTime } = useDateFormatter()

const activeTab = ref('all')

onMounted(() => {
  signatureStore.fetchMyPendingSignatures()
})

const tabs = computed(() => [
  { key: 'all', label: 'همه', icon: 'pi pi-list', count: signatureStore.pendingSignatures.length },
  { key: 'contract', label: 'قراردادها', icon: 'pi pi-file', count: signatureStore.contractSignatures.length },
  { key: 'amendment', label: 'الحاقیه‌ها', icon: 'pi pi-file-edit', count: signatureStore.amendmentSignatures.length },
])

const filteredSignatures = computed(() => {
  if (activeTab.value === 'all') return signatureStore.pendingSignatures
  if (activeTab.value === 'contract') return signatureStore.contractSignatures
  if (activeTab.value === 'amendment') return signatureStore.amendmentSignatures
  return []
})

const getDocumentLabel = (signature) => {
  const type = signature.signable_type || ''
  if (type.includes('Contract')) return { label: 'قرارداد', icon: 'pi pi-file', color: 'info' }
  if (type.includes('Amendment')) return { label: 'الحاقیه', icon: 'pi pi-file-edit', color: 'warn' }
  return { label: 'سند', icon: 'pi pi-file', color: 'secondary' }
}

const handleView = (signature) => {
  const type = signature.signable_type || ''
  if (type.includes('Contract')) {
    router.push(`/contracts/${signature.signable_id}?tab=signatures`)
  } else if (type.includes('Amendment')) {
    router.push(`/amendments/${signature.signable_id}`)
  }
}
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-2xl font-bold flex items-center gap-2">
          <i class="pi pi-signature text-primary" />
          امضاهای در انتظار
        </h1>
        <p class="text-surface-500 text-sm mt-1">
          اسنادی که نیاز به امضای شما دارند
        </p>
      </div>

      <Tag
          v-if="signatureStore.pendingCount > 0"
          :value="`${signatureStore.pendingCount} مورد در انتظار`"
          severity="warn"
          class="!text-sm"
      />
    </div>

    <!-- Tabs -->
    <Card>
      <template #content>
      <div class="flex flex-wrap gap-2 mb-4">
        <button
            v-for="tab in tabs"
            :key="tab.key"
            :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2',
            activeTab === tab.key
              ? 'bg-primary text-primary-contrast'
              : 'bg-surface-100 dark:bg-surface-800 text-surface-700 dark:text-surface-300 hover:bg-surface-200 dark:hover:bg-surface-700',
          ]"
            @click="activeTab = tab.key"
        >
          <i :class="tab.icon" />
          {{ tab.label }}
          <span
              v-if="tab.count"
              :class="[
              'px-1.5 py-0.5 rounded text-xs',
              activeTab === tab.key ? 'bg-white/20' : 'bg-surface-200 dark:bg-surface-700',
            ]"
          >
            {{ tab.count }}
          </span>
        </button>
      </div>

      <!-- Loading -->
      <div v-if="signatureStore.loading" class="flex justify-center py-12">
        <ProgressSpinner style="width: 40px; height: 40px" />
      </div>

      <!-- Empty -->
      <EmptyState
          v-else-if="!filteredSignatures.length"
          title="موردی در انتظار امضا نیست"
          description="تمام اسناد مورد نیاز امضا شده‌اند."
          icon="pi pi-check-circle"
      />

      <!-- List -->
      <div v-else class="space-y-3">
        <div
            v-for="signature in filteredSignatures"
            :key="signature.id"
            class="border border-surface-200 dark:border-surface-700 rounded-xl p-4 hover:border-primary/40 hover:shadow-sm transition-all"
        >
          <div class="flex items-start justify-between gap-4 flex-wrap">
            <div class="flex items-start gap-3 flex-1 min-w-0">
              <span
                  :class="[
                  'w-12 h-12 rounded-xl flex items-center justify-center shrink-0',
                  'bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300',
                ]"
              >
                <i :class="[getDocumentLabel(signature).icon, 'text-lg']" />
              </span>

              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                  <Tag
                      :value="getDocumentLabel(signature).label"
                      :severity="getDocumentLabel(signature).color"
                      class="!text-xs"
                  />
                  <span class="text-xs text-surface-500">
                    درخواست در {{ toJalaliDateTime(signature.created_at) }}
                  </span>
                </div>

                <h3 class="font-semibold text-surface-800 dark:text-surface-100 truncate">
                  سند شماره {{ signature.signable_id }}
                </h3>

                <p class="text-xs text-surface-500 mt-1">
                  <i class="pi pi-user ml-1" />
                  درخواست‌دهنده: {{ signature.signer?.name || 'سیستم' }}
                </p>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <Button
                  label="مشاهده و امضا"
                  icon="pi pi-signature"
                  size="small"
                  @click="handleView(signature)"
              />
            </div>
          </div>
        </div>
      </div>
      </template>
    </Card>
  </div>
</template>