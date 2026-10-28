<!-- src/views/Party/PartiesList.vue -->
<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0">
        <i class="pi pi-users ml-2 text-primary" />
        مدیریت طرفین قرارداد
      </h1>
      <Button
          v-if="can('parties.create')"
          label="طرف قرارداد جدید"
          icon="pi pi-plus"
          @click="router.push('/parties/create')"
      />
    </div>

    <!-- Filters -->
    <Card>
      <template #content>
        <div class="flex flex-wrap items-center gap-3">
          <div class="relative flex-1 min-w-64">
            <i class="pi pi-search absolute right-3 top-1/2 -translate-y-1/2 text-surface-400" />
            <InputText
                v-model="search"
                placeholder="جستجو در نام یا شناسه ملی..."
                class="w-full !pr-10"
                @input="loadParties"
            />
          </div>
          <Select
              v-model="roleFilter"
              :options="roleOptions"
              option-label="label"
              option-value="value"
              placeholder="نوع طرف"
              class="w-48"
              show-clear
              @change="loadParties"
          />
        </div>
      </template>
    </Card>

    <!-- Table -->
    <Card>
      <template #content>
        <DataTable
            :value="partyStore.parties"
            :loading="partyStore.loading"
            :paginator="true"
            :rows="15"
            :rows-per-page-options="[15, 30, 50]"
            striped-rows
            responsive-layout="scroll"
            :total-records="partyStore.pagination.total"
            lazy
            @page="partyStore.setPage($event.page + 1); loadParties()"
        >
          <Column field="name" header="نام" :sortable="true" class="min-w-48" />

          <Column field="national_id" header="شناسه ملی/کدملی" class="min-w-32">
            <template #body="{ data }">
              <span class="font-mono text-sm">{{ data.national_id || '—' }}</span>
            </template>
          </Column>

          <Column field="role" header="نوع" class="min-w-32">
            <template #body="{ data }">
              <Tag
                  :value="getRoleBadge(data.role).label"
                  :severity="getRoleBadge(data.role).severity"
                  class="!text-xs"
              />
            </template>
          </Column>

          <Column field="phone" header="تلفن" class="min-w-32">
            <template #body="{ data }">
              <a v-if="data.phone" :href="`tel:${data.phone}`" class="text-primary hover:underline">
                {{ data.phone }}
              </a>
              <span v-else class="text-surface-400">—</span>
            </template>
          </Column>

          <Column field="email" header="ایمیل" class="min-w-48">
            <template #body="{ data }">
              <a v-if="data.email" :href="`mailto:${data.email}`" class="text-primary hover:underline text-sm">
                {{ data.email }}
              </a>
              <span v-else class="text-surface-400">—</span>
            </template>
          </Column>

          <Column field="is_active" header="وضعیت" class="min-w-24">
            <template #body="{ data }">
              <Tag
                  :value="data.is_active ? 'فعال' : 'غیرفعال'"
                  :severity="data.is_active ? 'success' : 'danger'"
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
                    @click="router.push(`/parties/${data.id}`)"
                />
                <Button
                    v-if="can('parties.update')"
                    icon="pi pi-pencil"
                    severity="warn"
                    text
                    rounded
                    size="small"
                    v-tooltip="'ویرایش'"
                    @click="router.push(`/parties/${data.id}/edit`)"
                />
                <Button
                    v-if="can('parties.delete')"
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    rounded
                    size="small"
                    v-tooltip="'حذف'"
                    @click="handleDelete(data)"
                />
              </div>
            </template>
          </Column>

          <template #empty>
            <div class="text-center py-8 text-surface-500">
              <i class="pi pi-users text-4xl mb-3 block" />
              <p>طرف قراردادی یافت نشد</p>
            </div>
          </template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import { useConfirm } from 'primevue/useconfirm'
import { usePartyStore } from '@/stores/partyStore'
import { usePermission } from '@/composables/usePermission'
import { showToast } from '@/plugins/toast'

const router = useRouter()
const partyStore = usePartyStore()
const { can } = usePermission()
const confirm = useConfirm()

const search = ref('')
const roleFilter = ref(null)
const roleOptions = [
  { label: 'پیمانکار', value: 'contractor' },
  { label: 'کارفرما', value: 'employer' },
  { label: 'هر دو', value: 'both' },
]

const loadParties = () => {
  partyStore.fetchParties({
    search: search.value,
    role: roleFilter.value,
  })
}

onMounted(loadParties)

const handleDelete = (party) => {
  confirm.require({
    message: `آیا از حذف "${party.name}" اطمینان دارید؟`,
    header: 'تایید حذف',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await partyStore.deleteParty(party.id)
        showToast({ severity: 'success', summary: 'حذف شد', life: 3000 })
      } catch (err) {
        showToast({ severity: 'error', summary: 'خطا', detail: err.message, life: 5000 })
      }
    },
  })
}

const getRoleBadge = (role) => {
  const map = {
    contractor: { label: 'پیمانکار', severity: 'info' },
    employer:   { label: 'کارفرما', severity: 'success' },
    both:       { label: 'هر دو',   severity: 'warn' },
  }
  return map[role] || { label: role, severity: 'secondary' }
}
</script>