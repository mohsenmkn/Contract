<template>
  <div class="data-table-wrapper">
    <!-- Header Section -->
    <div class="table-header">
      <div class="header-right">
        <h2 v-if="title" class="table-title">{{ title }}</h2>
        <span v-if="showCount" class="record-count">
          {{ totalRecords }} مورد
        </span>
      </div>

      <div class="header-left">
        <!-- Search -->
        <IconField v-if="searchable" iconPosition="left">
          <InputIcon class="pi pi-search" />
          <InputText
              v-model="searchQuery"
              :placeholder="searchPlaceholder"
              @input="onSearch"
              class="search-input"
          />
        </IconField>

        <!-- Custom Actions Slot -->
        <slot name="actions"></slot>

        <!-- Create Button -->
        <Button
            v-if="showCreate"
            :label="createLabel"
            icon="pi pi-plus"
            @click="$emit('create')"
            class="create-button"
        />

        <!-- Refresh Button -->
        <Button
            v-if="showRefresh"
            icon="pi pi-refresh"
            severity="secondary"
            text
            @click="refresh"
            :loading="loading"
        />
      </div>
    </div>

    <!-- Filters Section -->
    <div v-if="$slots.filters" class="filters-section">
      <slot name="filters"></slot>
    </div>

    <!-- DataTable -->
    <DataTable
        :value="data"
        :loading="loading"
        :paginator="paginated"
        :rows="rowsPerPage"
        :totalRecords="totalRecords"
        :lazy="lazy"
        :rowsPerPageOptions="rowsPerPageOptions"
        :currentPageReportTemplate="pageReportTemplate"
        :paginatorTemplate="paginatorTemplate"
        :selection="selection"
        :selectionMode="selectionMode"
        :dataKey="dataKey"
        :stripedRows="striped"
        :showGridlines="gridlines"
        :responsiveLayout="responsiveLayout"
        :class="tableClass"
        @page="onPage"
        @sort="onSort"
        @update:selection="$emit('update:selection', $event)"
        removableSort
        :sortField="sortField"
        :sortOrder="sortOrder"
    >
      <!-- Empty State -->
      <template #empty>
        <div class="empty-state">
          <i class="pi pi-inbox empty-icon"></i>
          <p class="empty-text">{{ emptyMessage }}</p>
        </div>
      </template>

      <!-- Loading -->
      <template #loading>
        <ProgressSpinner style="width: 50px; height: 50px" />
      </template>

      <!-- Selection Column -->
      <Column
          v-if="selectionMode"
          selectionMode="multiple"
          headerStyle="width: 3rem"
          :exportable="false"
      ></Column>

      <!-- Dynamic Columns -->
      <Column
          v-for="column in visibleColumns"
          :key="column.field"
          :field="column.field"
          :header="column.header"
          :sortable="column.sortable !== false"
          :style="column.style"
          :headerStyle="column.headerStyle"
          :bodyStyle="column.bodyStyle"
      >
        <!-- Custom Column Template -->
        <template #body="slotProps" v-if="$slots[`column-${column.field}`]">
          <slot
              :name="`column-${column.field}`"
              :data="slotProps.data"
              :field="column.field"
              :value="slotProps.data[column.field]"
          ></slot>
        </template>

        <!-- Default Column Rendering -->
        <template #body="slotProps" v-else>
          <!-- Boolean/Badge -->
          <Tag
              v-if="column.type === 'badge' || column.type === 'boolean'"
              :value="formatBadge(slotProps.data[column.field], column)"
              :severity="getBadgeSeverity(slotProps.data[column.field], column)"
          />

          <!-- Date -->
          <span v-else-if="column.type === 'date'">
            {{ formatDate(slotProps.data[column.field], column.format) }}
          </span>

          <!-- Money/Amount -->
          <span v-else-if="column.type === 'money' || column.type === 'amount'">
            {{ formatMoney(slotProps.data[column.field]) }}
          </span>

          <!-- Number -->
          <span v-else-if="column.type === 'number'">
            {{ formatNumber(slotProps.data[column.field]) }}
          </span>

          <!-- Image -->
          <Avatar
              v-else-if="column.type === 'image'"
              :image="slotProps.data[column.field]"
              shape="circle"
              size="normal"
          />

          <!-- Link -->
          <a
              v-else-if="column.type === 'link'"
              :href="slotProps.data[column.field]"
              target="_blank"
              class="table-link"
          >
            {{ slotProps.data[column.field] }}
          </a>

          <!-- Default Text -->
          <span v-else>
            {{ slotProps.data[column.field] }}
          </span>
        </template>
      </Column>

      <!-- Actions Column -->
      <Column
          v-if="showActions"
          :header="actionsHeader"
          :exportable="false"
          style="min-width: 120px"
          bodyStyle="text-align: center"
      >
        <template #body="slotProps">
          <!-- Custom Actions Slot -->
          <div v-if="$slots['row-actions']" class="action-buttons">
            <slot
                name="row-actions"
                :data="slotProps.data"
                :index="slotProps.index"
            ></slot>
          </div>

          <!-- Default Actions -->
          <div v-else class="action-buttons">
            <Button
                v-if="canEdit"
                icon="pi pi-pencil"
                severity="info"
                text
                rounded
                @click="$emit('edit', slotProps.data)"
                v-tooltip.top="'ویرایش'"
            />
            <Button
                v-if="canView"
                icon="pi pi-eye"
                severity="secondary"
                text
                rounded
                @click="$emit('view', slotProps.data)"
                v-tooltip.top="'مشاهده'"
            />
            <Button
                v-if="canDelete"
                icon="pi pi-trash"
                severity="danger"
                text
                rounded
                @click="confirmDelete(slotProps.data)"
                v-tooltip.top="'حذف'"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <!-- Bulk Actions -->
    <div v-if="selection && selection.length > 0" class="bulk-actions">
      <span class="bulk-count">{{ selection.length }} مورد انتخاب شده</span>
      <slot name="bulk-actions" :selection="selection"></slot>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import ProgressSpinner from 'primevue/progressspinner'
import api from '@/api/axios.js'

const confirm = useConfirm()

const props = defineProps({
  // Data Source
  apiUrl: String,
  data: {
    type: Array,
    default: () => []
  },
  lazy: {
    type: Boolean,
    default: true
  },

  // Table Config
  title: String,
  columns: {
    type: Array,
    required: true
  },
  dataKey: {
    type: String,
    default: 'id'
  },

  // Pagination
  paginated: {
    type: Boolean,
    default: true
  },
  rowsPerPage: {
    type: Number,
    default: 10
  },
  rowsPerPageOptions: {
    type: Array,
    default: () => [5, 10, 20, 50]
  },

  // Selection
  selection: {
    type: [Object, Array],
    default: null
  },
  selectionMode: {
    type: String,
    default: null
  },

  // Search
  searchable: {
    type: Boolean,
    default: true
  },
  searchPlaceholder: {
    type: String,
    default: 'جستجو...'
  },
  searchDelay: {
    type: Number,
    default: 500
  },

  // Actions
  showActions: {
    type: Boolean,
    default: true
  },
  actionsHeader: {
    type: String,
    default: 'عملیات'
  },
  canEdit: {
    type: Boolean,
    default: true
  },
  canView: {
    type: Boolean,
    default: false
  },
  canDelete: {
    type: Boolean,
    default: true
  },

  // Header
  showCreate: {
    type: Boolean,
    default: true
  },
  createLabel: {
    type: String,
    default: 'ایجاد'
  },
  showRefresh: {
    type: Boolean,
    default: true
  },
  showCount: {
    type: Boolean,
    default: true
  },

  // Appearance
  striped: {
    type: Boolean,
    default: true
  },
  gridlines: {
    type: Boolean,
    default: false
  },
  responsiveLayout: {
    type: String,
    default: 'scroll'
  },
  tableClass: String,

  // Messages
  emptyMessage: {
    type: String,
    default: 'داده‌ای یافت نشد'
  },
  deleteConfirmMessage: {
    type: String,
    default: 'آیا از حذف این مورد اطمینان دارید؟'
  },

  // Custom delete URL pattern
  deleteUrlPattern: String
})

const emit = defineEmits([
  'create',
  'edit',
  'view',
  'delete',
  'refresh',
  'update:selection',
  'load'
])

// State
const loading = ref(false)
const internalData = ref([])
const totalRecords = ref(0)
const currentPage = ref(0)
const searchQuery = ref('')
const sortField = ref(null)
const sortOrder = ref(null)
let searchTimeout = null

// Computed
const data = computed(() => {
  return props.lazy ? internalData.value : props.data
})

const visibleColumns = computed(() => {
  return props.columns.filter(col => col.visible !== false)
})

const pageReportTemplate = computed(() => {
  return '{first} تا {last} از {totalRecords} مورد'
})

const paginatorTemplate = computed(() => {
  return 'FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown'
})

// Load Data
async function loadData() {
  if (!props.lazy || !props.apiUrl) return

  loading.value = true

  try {
    const params = {
      page: currentPage.value + 1,
      per_page: props.rowsPerPage
    }

    if (searchQuery.value) {
      params.search = searchQuery.value
    }

    if (sortField.value) {
      params.sort_by = sortField.value
      params.sort_order = sortOrder.value === 1 ? 'asc' : 'desc'
    }

    const response = await api.get(props.apiUrl, { params })

    // Support both Laravel pagination formats
    if (response.data.data) {
      internalData.value = response.data.data
      totalRecords.value = response.data.total || response.data.meta?.total || 0
    } else {
      internalData.value = response.data
      totalRecords.value = response.data.length
    }

    emit('load', response.data)

  } catch (error) {
    console.error('Error loading data:', error)
    internalData.value = []
    totalRecords.value = 0
  } finally {
    loading.value = false
  }
}

// Pagination Handler
function onPage(event) {
  currentPage.value = event.page
  loadData()
}

// Sort Handler
function onSort(event) {
  sortField.value = event.sortField
  sortOrder.value = event.sortOrder
  loadData()
}

// Search Handler
function onSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 0
    loadData()
  }, props.searchDelay)
}

// Refresh
function refresh() {
  currentPage.value = 0
  searchQuery.value = ''
  sortField.value = null
  sortOrder.value = null
  loadData()
  emit('refresh')
}

// Delete Confirmation
function confirmDelete(rowData) {
  confirm.require({
    message: props.deleteConfirmMessage,
    header: 'تأیید حذف',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'انصراف',
    acceptLabel: 'حذف',
    acceptClass: 'p-button-danger',
    accept: () => deleteRow(rowData)
  })
}

// Delete Row
async function deleteRow(rowData) {
  try {
    const deleteUrl = props.deleteUrlPattern
        ? props.deleteUrlPattern.replace(':id', rowData[props.dataKey])
        : `${props.apiUrl}/${rowData[props.dataKey]}`

    await api.delete(deleteUrl)

    emit('delete', rowData)
    loadData()

  } catch (error) {
    console.error('Error deleting row:', error)
  }
}

// Formatters
function formatDate(value, format = 'jYYYY/jMM/jDD') {
  if (!value) return '-'
  // Add your date formatting logic here
  return new Date(value).toLocaleDateString('fa-IR')
}

function formatMoney(value) {
  if (value === null || value === undefined) return '-'
  return new Intl.NumberFormat('fa-IR').format(value) + ' ریال'
}

function formatNumber(value) {
  if (value === null || value === undefined) return '-'
  return new Intl.NumberFormat('fa-IR').format(value)
}

function formatBadge(value, column) {
  if (column.type === 'boolean') {
    return value ? 'فعال' : 'غیرفعال'
  }

  if (column.badges && column.badges[value]) {
    return column.badges[value].label || value
  }

  return value
}

function getBadgeSeverity(value, column) {
  if (column.type === 'boolean') {
    return value ? 'success' : 'danger'
  }

  if (column.badges && column.badges[value]) {
    return column.badges[value].severity || 'info'
  }

  return 'info'
}

// Initial Load
if (props.lazy && props.apiUrl) {
  loadData()
}

// Watch for external data changes
watch(() => props.data, (newData) => {
  if (!props.lazy && newData) {
    totalRecords.value = newData.length
  }
})

// Expose methods
defineExpose({
  refresh,
  loadData
})
</script>

<style scoped>
.data-table-wrapper {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.table-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.record-count {
  padding: 0.25rem 0.75rem;
  background: #f3f4f6;
  border-radius: 12px;
  font-size: 0.875rem;
  color: #6b7280;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.search-input {
  min-width: 250px;
}

.filters-section {
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 6px;
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
}

.empty-icon {
  font-size: 3rem;
  color: #d1d5db;
  margin-bottom: 1rem;
}

.empty-text {
  color: #6b7280;
  font-size: 1rem;
}

.action-buttons {
  display: flex;
  justify-content: center;
  gap: 0.25rem;
}

.table-link {
  color: #3b82f6;
  text-decoration: none;
}

.table-link:hover {
  text-decoration: underline;
}

.bulk-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  margin-top: 1rem;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 6px;
}

.bulk-count {
  font-weight: 600;
  color: #1e40af;
}

/* RTL Support */
.data-table-wrapper {
  direction: rtl;
}

/* Responsive */
@media (max-width: 768px) {
  .table-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-right,
  .header-left {
    width: 100%;
    justify-content: space-between;
  }

  .search-input {
    min-width: 100%;
  }
}

/* DataTable Customization */
:deep(.p-datatable) {
  font-size: 0.9rem;
}

:deep(.p-datatable-header) {
  background: transparent;
  border: none;
  padding: 0;
}

:deep(.p-datatable-thead > tr > th) {
  background: #f9fafb;
  color: #374151;
  font-weight: 600;
  border-color: #e5e7eb;
}

:deep(.p-datatable-tbody > tr > td) {
  border-color: #e5e7eb;
}

:deep(.p-datatable-tbody > tr:hover) {
  background: #f9fafb;
}

:deep(.p-paginator) {
  background: transparent;
  border: none;
  padding: 1rem 0 0 0;
}
</style>
