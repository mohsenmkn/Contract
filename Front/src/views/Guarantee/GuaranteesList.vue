<!-- src/views/Guarantee/GuaranteesList.vue -->
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useGuaranteeStore } from '@/stores/guaranteeStore'
import { useGuarantee } from '@/composables/useGuarantee'
import { usePermission } from '@/composables/usePermission'
import AmountDisplay from '@/components/contract/AmountDisplay.vue'
import DateDisplay from '@/components/contract/DateDisplay.vue'
import EmptyState from '@/components/contract/EmptyState.vue'
import { showToast } from '@/plugins/toast'

const router = useRouter()
const guaranteeStore = useGuaranteeStore()
const { getStatusInfo } = useGuarantee()
const { can } = usePermission()
const confirm = useConfirm()

onMounted(() => {
  // لیست ضمانت‌نامه‌ها
})

const handleRelease = (guarantee) => {
  confirm.require({
    message: `آیا از آزادسازی ضمانت‌نامه ${guarantee.guarantee_number} اطمینان دارید؟`,
    header: 'آزادسازی ضمانت‌نامه',
    icon: 'pi pi-lock-open',
    acceptLabel: 'آزادسازی',
    rejectLabel: 'انصراف',
    accept: async () => {
      try {
        await guaranteeStore.releaseGuarantee(guarantee.id)
        showToast({ severity: 'success', summary: 'آزاد شد', life: 3000 })
      } catch (err) {
        showToast({ severity: 'error', summary: 'خطا', detail: err.message, life: 5000 })
      }
    },
  })
}
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">
        <i class="pi pi-shield ml-2 text-primary" />
        مدیریت ضمانت‌نامه‌ها
      </h1>
    </div>

    <!-- Table -->
    <Card>
      <template #content>
      <EmptyState
          v-if="!guaranteeStore.guarantees.length && !guaranteeStore.loading"
          title="ضمانت‌نامه‌ای ثبت نشده"
          description="هنوز ضمانت‌نامه‌ای در سیستم ثبت نشده است."
          icon="pi pi-shield"
      />

      <DataTable
          v-else
          :value="guaranteeStore.guarantees"
          :loading="guaranteeStore.loading"
          striped-rows
          responsive-layout="scroll"
      >
        <Column field="guarantee_number" header="شماره" class="min-w-32">
          <template #body="{ data }">
            <span class="font-mono text-sm font-medium">{{ data.guarantee_number }}</span>
          </template>
        </Column>

        <Column field="contract.contract_number" header="قرارداد" class="min-w-32">
          <template #body="{ data }">
            <span class="text-sm">{{ data.contract?.contract_number }}</span>
          </template>
        </Column>

        <Column field="type" header="نوع" class="min-w-36">
          <template #body="{ data }">
            <Tag :value="data.type_label" severity="info" class="!text-xs" />
          </template>
        </Column>

        <Column field="amount" header="مبلغ" class="min-w-40 text-left">
          <template #body="{ data }">
            <AmountDisplay :amount="data.amount" />
          </template>
        </Column>

        <Column field="bank_name" header="بانک" class="min-w-32">
          <template #body="{ data }">
            <span>{{ data.bank_name }}</span>
          </template>
        </Column>

        <Column field="issue_date" header="تاریخ صدور" class="min-w-32">
          <template #body="{ data }">
            <DateDisplay :date="data.issue_date" />
          </template>
        </Column>

        <Column field="expiry_date" header="تاریخ انقضا" class="min-w-32">
          <template #body="{ data }">
            <DateDisplay :date="data.expiry_date" show-status />
          </template>
        </Column>

        <Column field="status" header="وضعیت" class="min-w-32">
          <template #body="{ data }">
            <Tag
                :value="data.status_label"
                :severity="getStatusInfo(data.status).color"
                class="!text-xs"
            />
          </template>
        </Column>

        <Column header="عملیات" class="min-w-32 text-center">
          <template #body="{ data }">
            <div class="flex items-center justify-center gap-1">
              <Button
                  icon="pi pi-eye"
                  severity="info"
                  text
                  rounded
                  size="small"
                  v-tooltip="'مشاهده'"
                  @click="router.push(`/guarantees/${data.id}`)"
              />
              <Button
                  v-if="can('guarantees.release') && data.status === 'active'"
                  icon="pi pi-lock-open"
                  severity="success"
                  text
                  rounded
                  size="small"
                  v-tooltip="'آزادسازی'"
                  @click="handleRelease(data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>
      </template>
    </Card>
  </div>
</template>