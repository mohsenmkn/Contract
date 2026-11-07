<!-- src/views/Contract/ContractRequestsList.vue -->
<template>
  <div class="min-h-screen bg-slate-50 p-4 md:p-6">
    <!-- هدر صفحه -->
    <div class="page-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5 md:p-6 mb-6">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl md:text-3xl font-bold text-gray-800 flex items-center gap-3">
            <Send class="w-8 h-8 text-blue-600" />
            درخواست‌های قرارداد من
          </h1>
          <p class="text-gray-500 mt-2 text-sm">
            مشاهده و مدیریت درخواست‌های قرارداد ثبت‌شده توسط شما
          </p>
        </div>
        <div class="flex gap-3">
          <Button
              v-if="can('contracts.create')"
              label="درخواست جدید"
              icon="pi pi-plus"
              @click="$router.push('/contracts/create')"
              class="bg-blue-600 hover:bg-blue-700"
          />
        </div>
      </div>
    </div>

    <!-- آمار -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
            <FileText class="w-6 h-6 text-blue-600" />
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-800">{{ requests.length }}</div>
            <div class="text-sm text-gray-500">کل درخواست‌ها</div>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
            <Clock class="w-6 h-6 text-yellow-600" />
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-800">{{ pendingCount }}</div>
            <div class="text-sm text-gray-500">در انتظار بررسی</div>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
            <CheckCircle class="w-6 h-6 text-green-600" />
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-800">{{ approvedCount }}</div>
            <div class="text-sm text-gray-500">تایید شده</div>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
            <XCircle class="w-6 h-6 text-red-600" />
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-800">{{ rejectedCount }}</div>
            <div class="text-sm text-gray-500">رد شده</div>
          </div>
        </div>
      </div>
    </div>

    <!-- فیلترها -->
    <div class="page-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5 md:p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
          <div class="relative">
            <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <InputText
                v-model="searchQuery"
                placeholder="جستجو در موضوع یا شماره قرارداد..."
                class="w-full pr-10"
                @input="onSearchInput"
            />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت</label>
          <Select
              v-model="statusFilter"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="همه وضعیت‌ها"
              class="w-full"
              showClear
              @change="onFilterChange"
          />
        </div>
      </div>
    </div>

    <!-- جدول درخواست‌ها -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
      <DataTable
          v-if="can('contracts.read')"
          :value="filteredRequests"
          :loading="loading"
          lazy
          paginator
          responsiveLayout="scroll"
          :rows="15"
          :totalRecords="filteredRequests.length"
          stripedRows
          class="requests-table"
      >
        <template #header>
          <div class="flex justify-between items-center">
            <span class="text-lg font-bold text-gray-800">
              لیست درخواست‌ها ({{ filteredRequests.length }} مورد)
            </span>
          </div>
        </template>

        <Column field="contract_number" header="شماره قرارداد" style="min-width: 140px">
          <template #body="slotProps">
            <span class="font-mono text-sm font-semibold text-slate-800">
              {{ slotProps.data.contract_number || '—' }}
            </span>
          </template>
        </Column>

        <Column field="subject" header="موضوع" style="min-width: 200px">
          <template #body="slotProps">
            <div class="truncate max-w-xs" :title="slotProps.data.subject">
              {{ slotProps.data.subject || '—' }}
            </div>
          </template>
        </Column>

        <Column field="party.name" header="طرف قرارداد" style="min-width: 150px">
          <template #body="slotProps">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <Building2 class="w-4 h-4 text-blue-600" />
              </div>
              <div class="min-w-0">
                <div class="text-sm font-medium text-slate-800 truncate">
                  {{ slotProps.data.party?.name || '—' }}
                </div>
              </div>
            </div>
          </template>
        </Column>

        <Column field="status" header="وضعیت" style="min-width: 160px">
          <template #body="slotProps">
            <Tag
                :value="getStatusLabel(slotProps.data.status)"
                :severity="getStatusSeverity(slotProps.data.status)"
                :icon="getStatusIcon(slotProps.data.status)"
            />
          </template>
        </Column>

        <Column field="current_amount" header="مبلغ (ریال)" style="min-width: 140px">
          <template #body="slotProps">
            <span class="font-bold text-slate-800 text-sm">
              {{ formatNumber(slotProps.data.current_amount) }}
            </span>
          </template>
        </Column>

        <Column field="created_at" header="تاریخ ثبت" style="min-width: 120px">
          <template #body="slotProps">
            <span class="text-sm text-slate-600">
              {{ formatDate(slotProps.data.created_at) }}
            </span>
          </template>
        </Column>

        <Column header="عملیات" style="min-width: 200px" frozen alignFrozen="left">
          <template #body="slotProps">
            <div class="flex gap-2">
              <Button
                  icon="pi pi-eye"
                  class="p-button-rounded p-button-info p-button-text"
                  @click="viewRequest(slotProps.data)"
                  tooltip="مشاهده"
              />
              <Button
                  v-if="can('contracts.update') && canEdit(slotProps.data)"
                  icon="pi pi-pencil"
                  class="p-button-rounded p-button-success p-button-text"
                  @click="editRequest(slotProps.data)"
                  tooltip="ویرایش"
              />
              <Button
                  v-if="can('contracts.delete') && canDelete(slotProps.data)"
                  icon="pi pi-trash"
                  class="p-button-rounded p-button-danger p-button-text"
                  @click="confirmDelete(slotProps.data)"
                  tooltip="حذف"
              />
            </div>
          </template>
        </Column>
      </DataTable>

      <div v-else class="p-12 text-center">
        <Lock class="w-16 h-16 text-gray-300 mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-gray-600 mb-2">دسترسی محدود</h3>
        <p class="text-gray-500">شما مجاز به مشاهده لیست درخواست‌ها نیستید</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useApiErrorHandler } from '@/composables/useApiErrorHandler'
import api from '@/api/axios.js'
import { useAuthStore } from '@/stores/auth.js'
import {
  Send, FileText, Clock, CheckCircle, XCircle, Search, Building2, Lock
} from 'lucide-vue-next'

const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const { handleApiError } = useApiErrorHandler()
const authStore = useAuthStore()

const can = (permission) => authStore.can(permission)

// State
const requests = ref([])
const loading = ref(false)
const searchQuery = ref('')
const statusFilter = ref(null)

const statusOptions = [
  { label: 'پیش‌نویس', value: 'draft' },
  { label: 'در انتظار بررسی حقوقی', value: 'pending_legal_review' },
  { label: 'پیش‌نویس حقوقی', value: 'legal_draft' },
  { label: 'رد شده توسط حقوقی', value: 'rejected_by_legal' },
  { label: 'در انتظار امضای پیمانکار', value: 'pending_contractor_sign' },
  { label: 'در انتظار امضای واحد', value: 'pending_unit_sign' },
  { label: 'در انتظار امضای حقوقی', value: 'pending_legal_sign' },
  { label: 'در انتظار امضای اجرایی', value: 'pending_executive_sign' },
  { label: 'در انتظار ابلاغ', value: 'pending_notification' },
  { label: 'فعال', value: 'active' },
  { label: 'خاتمه یافته', value: 'completed' },
  { label: 'فسخ شده', value: 'terminated' },
]

// Computed
const filteredRequests = computed(() => {
  let result = requests.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase().trim()
    result = result.filter(r =>
        r.subject?.toLowerCase().includes(query) ||
        r.contract_number?.toLowerCase().includes(query)
    )
  }

  if (statusFilter.value) {
    result = result.filter(r => r.status === statusFilter.value)
  }

  return result
})

const pendingCount = computed(() =>
    requests.value.filter(r => ['draft', 'pending_legal_review', 'legal_draft'].includes(r.status)).length
)

const approvedCount = computed(() =>
    requests.value.filter(r => ['active', 'completed'].includes(r.status)).length
)

const rejectedCount = computed(() =>
    requests.value.filter(r => ['terminated', 'rejected', 'rejected_by_legal'].includes(r.status)).length
)

// Methods
const getStatusLabel = (status) => {
  const labels = {
    'draft': 'پیش‌نویس',
    'pending_legal_review': 'در انتظار بررسی حقوقی',
    'legal_draft': 'پیش‌نویس حقوقی',
    'rejected_by_legal': 'رد شده توسط حقوقی',
    'pending_contractor_sign': 'در انتظار امضای پیمانکار',
    'pending_guarantee': 'در انتظار دریافت ضمانت',
    'pending_unit_sign': 'در انتظار امضای واحد متقاضی',
    'pending_legal_sign': 'در انتظار امضای حقوقی',
    'pending_executive_sign': 'در انتظار امضای اجرایی',
    'pending_notification': 'در انتظار ابلاغ',
    'active': 'فعال',
    'completed': 'خاتمه یافته',
    'terminated': 'فسخ شده',
  }
  return labels[status] || status
}

const getStatusSeverity = (status) => {
  const severities = {
    'draft': 'secondary',
    'pending_legal_review': 'warn',
    'legal_draft': 'info',
    'rejected_by_legal': 'danger',
    'pending_contractor_sign': 'warn',
    'pending_guarantee': 'warn',
    'pending_unit_sign': 'warn',
    'pending_legal_sign': 'warn',
    'pending_executive_sign': 'warn',
    'pending_notification': 'info',
    'active': 'success',
    'completed': 'success',
    'terminated': 'danger',
  }
  return severities[status] || 'secondary'
}

const getStatusIcon = (status) => {
  const icons = {
    'draft': 'pi pi-file',
    'pending_legal_review': 'pi pi-clock',
    'legal_draft': 'pi pi-file-edit',
    'rejected_by_legal': 'pi pi-times-circle',
    'pending_contractor_sign': 'pi pi-signature',
    'pending_guarantee': 'pi pi-shield',
    'pending_unit_sign': 'pi pi-users',
    'pending_legal_sign': 'pi pi-briefcase',
    'pending_executive_sign': 'pi pi-user-tie',
    'pending_notification': 'pi pi-send',
    'active': 'pi pi-check-circle',
    'completed': 'pi pi-check',
    'terminated': 'pi pi-times-circle',
  }
  return icons[status] || 'pi pi-file'
}

const formatNumber = (num) => {
  if (!num) return '0'
  return Number(num).toLocaleString('fa-IR')
}

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('fa-IR')
}

const canEdit = (request) => {
  return ['draft', 'rejected_by_legal'].includes(request.status)
}

const canDelete = (request) => {
  return request.status === 'draft'
}

const loadRequests = async () => {
  loading.value = true
  try {
    const response = await api.get('/contracts', {
      params: {
        per_page: 100,
        sort_by: 'created_at',
        sort_dir: 'desc'
      }
    })
    requests.value = response.data.data || []
  } catch (error) {
    handleApiError(error)
  } finally {
    loading.value = false
  }
}

const onSearchInput = () => {
  // فیلتر سمت کلاینت
}

const onFilterChange = () => {
  // فیلتر سمت کلاینت
}

const viewRequest = (request) => {
  router.push(`/contracts/${request.id}`)
}

const editRequest = (request) => {
  router.push(`/contracts/${request.id}/edit`)
}

const confirmDelete = (request) => {
  confirm.require({
    message: `آیا از حذف درخواست "${request.subject}" اطمینان دارید؟`,
    header: 'تایید حذف',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'بله، حذف کن',
    rejectLabel: 'خیر',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/contracts/${request.id}`)
        toast.add({ severity: 'success', summary: 'موفق', detail: 'درخواست حذف شد', life: 3000 })
        await loadRequests()
      } catch (error) {
        handleApiError(error)
      }
    }
  })
}

onMounted(() => {
  loadRequests()
})
</script>

<style scoped>
.requests-table {
  border: 0;
}

:deep(.requests-table .p-datatable-tbody > tr > td) {
  padding: 0.9rem 0.85rem;
  vertical-align: middle;
}

:deep(.requests-table .p-datatable-thead > tr > th) {
  padding: 0.9rem 0.85rem;
  font-size: 0.78rem;
  white-space: nowrap;
  border-color: #e2e8f0;
  background: #f3f4f6;
  font-weight: 600;
  color: #374151;
}

:deep(.requests-table .p-datatable-tbody > tr) {
  border-color: #eef2f7;
  transition: background-color 0.2s;
}

:deep(.requests-table .p-datatable-tbody > tr:hover) {
  background: #f9fafb;
}

:deep(.requests-table .p-paginator) {
  border-top: 1px solid #eef2f7;
  padding: 0.75rem 1rem;
  background: #fff;
}
</style>
