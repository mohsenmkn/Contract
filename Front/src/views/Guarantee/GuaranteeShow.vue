<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useGuaranteeStore } from '@/stores/guaranteeStore'
import { useGuarantee } from '@/composables/useGuarantee'
import { usePermission } from '@/composables/usePermission'
import AmountDisplay from '@/components/contract/AmountDisplay.vue'
import DateDisplay from '@/components/contract/DateDisplay.vue'
import { showToast } from '@/plugins/toast'

const route = useRoute()
const router = useRouter()
const confirm = useConfirm()
const guaranteeStore = useGuaranteeStore()
const { getStatusInfo } = useGuarantee()
const { can } = usePermission()

const loading = ref(true)
const guarantee = ref(null)

const loadGuarantee = async () => {
  loading.value = true
  try {
    guarantee.value = await guaranteeStore.fetchGuaranteeById(route.params.id)
  } catch (error) {
    showToast({ severity: 'error', summary: 'خطا', detail: error.response?.data?.message || error.message || 'جزئیات ضمانت‌نامه دریافت نشد.' })
    router.push('/guarantees')
  } finally {
    loading.value = false
  }
}

const releaseGuarantee = () => {
  if (!guarantee.value) return

  confirm.require({
    message: `ضمانت‌نامه ${guarantee.value.guarantee_number} آزاد شود؟`,
    header: 'آزادسازی ضمانت‌نامه',
    icon: 'pi pi-lock-open',
    acceptLabel: 'آزادسازی',
    rejectLabel: 'انصراف',
    accept: async () => {
      try {
        guarantee.value = await guaranteeStore.releaseGuarantee(guarantee.value.id)
        showToast({ severity: 'success', summary: 'ضمانت‌نامه آزاد شد', life: 3000 })
      } catch (error) {
        showToast({ severity: 'error', summary: 'خطا', detail: error.response?.data?.message || error.message || 'آزادسازی انجام نشد.' })
      }
    },
  })
}

onMounted(loadGuarantee)
</script>

<template>
  <main class="mx-auto max-w-6xl space-y-6 p-6">
    <div v-if="loading" class="grid min-h-64 place-items-center">
      <ProgressSpinner />
    </div>

    <template v-else-if="guarantee">
      <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <div class="mb-2 flex flex-wrap items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-primary/10 text-primary">
              <i class="pi pi-shield text-xl" />
            </span>
            <h1 class="text-2xl font-bold">{{ guarantee.guarantee_number }}</h1>
            <Tag :value="guarantee.status_label" :severity="getStatusInfo(guarantee.status).color" />
          </div>
          <p class="text-surface-500">
            {{ guarantee.type_label }} · {{ guarantee.instrument_type_label }}
          </p>
        </div>

        <div class="flex flex-wrap gap-2">
          <Button
            v-if="guarantee.contract?.id"
            label="مشاهده قرارداد"
            icon="pi pi-file"
            severity="secondary"
            outlined
            @click="router.push(`/contracts/${guarantee.contract.id}`)"
          />
          <Button
            v-if="can('guarantees.release') && guarantee.status === 'active'"
            label="آزادسازی ضمانت‌نامه"
            icon="pi pi-lock-open"
            severity="success"
            outlined
            @click="releaseGuarantee"
          />
          <Button label="بازگشت" icon="pi pi-arrow-right" severity="secondary" text @click="router.back()" />
        </div>
      </header>

      <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <Card>
          <template #content>
            <div class="mb-2 text-sm text-surface-500">مبلغ ضمانت‌نامه</div>
            <AmountDisplay :amount="guarantee.amount" :currency="guarantee.currency" size="lg" />
          </template>
        </Card>
        <Card>
          <template #content>
            <div class="mb-2 text-sm text-surface-500">تاریخ صدور</div>
            <DateDisplay :date="guarantee.issue_date" />
          </template>
        </Card>
        <Card>
          <template #content>
            <div class="mb-2 text-sm text-surface-500">تاریخ انقضا</div>
            <DateDisplay :date="guarantee.expiry_date" show-status />
            <p v-if="guarantee.is_expiring && guarantee.days_remaining !== null" class="mt-2 text-xs text-orange-600">
              {{ Math.abs(guarantee.days_remaining).toLocaleString('fa-IR') }} روز تا انقضا
            </p>
          </template>
        </Card>
      </section>

      <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <Card>
          <template #title>اطلاعات ضمانت‌نامه</template>
          <template #content>
            <dl class="divide-y divide-surface-200 text-sm">
              <div class="flex justify-between gap-4 py-3"><dt class="text-surface-500">نوع ضمانت</dt><dd class="font-medium">{{ guarantee.type_label }}</dd></div>
              <div class="flex justify-between gap-4 py-3"><dt class="text-surface-500">نوع سند</dt><dd class="font-medium">{{ guarantee.instrument_type_label }}</dd></div>
              <div class="flex justify-between gap-4 py-3"><dt class="text-surface-500">شماره چک/سفته</dt><dd class="font-medium">{{ guarantee.instrument_number || '—' }}</dd></div>
              <div class="flex justify-between gap-4 py-3"><dt class="text-surface-500">بانک</dt><dd class="font-medium">{{ guarantee.bank_name || '—' }}</dd></div>
              <div class="flex justify-between gap-4 py-3"><dt class="text-surface-500">شعبه</dt><dd class="font-medium">{{ guarantee.bank_branch || '—' }}</dd></div>
              <div class="flex justify-between gap-4 py-3"><dt class="text-surface-500">وضعیت</dt><dd><Tag :value="guarantee.status_label" :severity="getStatusInfo(guarantee.status).color" /></dd></div>
            </dl>
            <div v-if="guarantee.description" class="mt-4 rounded-lg bg-surface-50 p-4 text-sm leading-7">
              <div class="mb-1 font-medium">توضیحات</div>
              {{ guarantee.description }}
            </div>
          </template>
        </Card>

        <Card>
          <template #title>قرارداد مرتبط</template>
          <template #content>
            <div v-if="guarantee.contract" class="space-y-4 text-sm">
              <div class="flex justify-between gap-4"><span class="text-surface-500">شماره قرارداد</span><span class="font-mono font-semibold">{{ guarantee.contract.contract_number || '—' }}</span></div>
              <div class="flex justify-between gap-4"><span class="text-surface-500">موضوع قرارداد</span><span class="text-left font-medium">{{ guarantee.contract.subject || '—' }}</span></div>
              <div class="flex justify-between gap-4"><span class="text-surface-500">وضعیت قرارداد</span><span>{{ guarantee.contract.status_label || '—' }}</span></div>
              <Button
                v-if="guarantee.contract.id"
                label="رفتن به جزئیات قرارداد"
                icon="pi pi-arrow-left"
                text
                class="!px-0"
                @click="router.push(`/contracts/${guarantee.contract.id}`)"
              />
            </div>
            <div v-else class="text-sm text-surface-500">اطلاعات قرارداد مرتبط در دسترس نیست.</div>
          </template>
        </Card>
      </section>

      <footer class="text-xs text-surface-500">
        ثبت‌کننده: {{ guarantee.creator?.name || '—' }}
        <span v-if="guarantee.created_at"> · تاریخ ثبت: <DateDisplay :date="guarantee.created_at" /></span>
      </footer>
    </template>
  </main>
</template>
