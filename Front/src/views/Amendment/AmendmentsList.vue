<!-- src/views/Amendment/AmendmentsList.vue -->
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useConfirm } from 'primevue/useconfirm'
import { useAmendmentStore } from '@/stores/amendmentStore'
import { useAmendment } from '@/composables/useAmendment'
import { usePermission } from '@/composables/usePermission'
import AmountDisplay from '@/components/contract/AmountDisplay.vue'
import DateDisplay from '@/components/contract/DateDisplay.vue'
import EmptyState from '@/components/contract/EmptyState.vue'
import { showToast } from '@/plugins/toast'

const router = useRouter()
const amendmentStore = useAmendmentStore()
const { getStatusInfo } = useAmendment()
const { can } = usePermission()
const confirm = useConfirm()

const filters = ref({
  status: null,
  type: null,
})

onMounted(() => {
  // لیست الحاقیه‌ها از طریق قرارداد لود می‌شود
})

const handleApprove = (amendment) => {
  confirm.require({
    message: `آیا از تایید الحاقیه ${amendment.amendment_number} اطمینان دارید؟`,
    header: 'تایید الحاقیه',
    icon: 'pi pi-check-circle',
    acceptLabel: 'تایید',
    rejectLabel: 'انصراف',
    accept: async () => {
      try {
        await amendmentStore.approveAmendment(amendment.id)
        showToast({ severity: 'success', summary: 'تایید شد', life: 3000 })
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
        <i class="pi pi-file-edit ml-2 text-primary" />
        مدیریت الحاقیه‌ها
      </h1>
    </div>

    <!-- Filters -->
    <Card>
      <template #content>
      <div class="flex flex-wrap gap-3">
        <Select
            v-model="filters.status"
            :options="[
            { label: 'پیش‌نویس', value: 'draft' },
            { label: 'در انتظار تایید', value: 'pending' },
            { label: 'تایید شده', value: 'approved' },
            { label: 'رد شده', value: 'rejected' },
          ]"
            option-label="label"
            option-value="value"
            placeholder="وضعیت"
            class="w-48"
            show-clear
        />

        <Select
            v-model="filters.type"
            :options="[
            { label: 'تغییر مبلغ', value: 'amount' },
            { label: 'تمدید مدت', value: 'extension' },
            { label: 'تغییر دامنه', value: 'scope' },
            { label: 'فسخ', value: 'termination' },
          ]"
            option-label="label"
            option-value="value"
            placeholder="نوع الحاقیه"
            class="w-48"
            show-clear
        />
      </div>
      </template>
    </Card>

    <!-- Table -->
    <Card>
      <template #content>
      <EmptyState
          v-if="!amendmentStore.amendments.length && !amendmentStore.loading"
          title="الحاقیه‌ای ثبت نشده"
          description="هنوز الحاقیه‌ای در سیستم ثبت نشده است."
          icon="pi pi-file-edit"
      />

      <DataTable
          v-else
          :value="amendmentStore.amendments"
          :loading="amendmentStore.loading"
          striped-rows
          responsive-layout="scroll"
      >
        <Column field="amendment_number" header="شماره الحاقیه" class="min-w-36">
          <template #body="{ data }">
            <span class="font-mono text-sm font-medium">{{ data.amendment_number }}</span>
          </template>
        </Column>

        <Column field="contract.contract_number" header="قرارداد" class="min-w-32">
          <template #body="{ data }">
            <span class="text-sm">{{ data.contract?.contract_number }}</span>
          </template>
        </Column>

        <Column field="type" header="نوع" class="min-w-32">
          <template #body="{ data }">
            <Tag :value="data.type_label" severity="info" class="!text-xs" />
          </template>
        </Column>

        <Column field="old_amount" header="مبلغ قبلی" class="min-w-36 text-left">
          <template #body="{ data }">
            <AmountDisplay v-if="data.old_amount" :amount="data.old_amount" :show-unit="false" />
            <span v-else class="text-surface-400">—</span>
          </template>
        </Column>

        <Column field="new_amount" header="مبلغ جدید" class="min-w-36 text-left">
          <template #body="{ data }">
            <AmountDisplay v-if="data.new_amount" :amount="data.new_amount" :show-unit="false" />
            <span v-else class="text-surface-400">—</span>
          </template>
        </Column>

        <Column field="amount_change" header="تغییر" class="min-w-32 text-left">
          <template #body="{ data }">
            <AmountDisplay
                v-if="data.amount_change !== null"
                :amount="data.amount_change"
                :colorize="true"
                :show-unit="false"
            />
            <span v-else class="text-surface-400">—</span>
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

        <Column field="created_at" header="تاریخ ایجاد" class="min-w-32">
          <template #body="{ data }">
            <DateDisplay :date="data.created_at" />
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
                  @click="router.push(`/amendments/${data.id}`)"
              />
              <Button
                  v-if="can('amendments.approve') && data.status === 'pending'"
                  icon="pi pi-check"
                  severity="success"
                  text
                  rounded
                  size="small"
                  v-tooltip="'تایید'"
                  @click="handleApprove(data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>
      </template>
    </Card>
  </div>
</template>