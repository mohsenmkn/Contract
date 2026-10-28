<!-- src/components/contract/ContractItemsTable.vue -->
<script setup>
import { computed } from 'vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import AmountInput from './AmountInput.vue'
import AmountDisplay from './AmountDisplay.vue'
import EmptyState from './EmptyState.vue'
import { useNumberFormatter } from '@/composables/useNumberFormatter'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  readonly: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  title: { type: String, default: 'فهرست بها / آیتم‌های قرارداد' },
})

const emit = defineEmits(['update:modelValue', 'change'])
const { formatNumber } = useNumberFormatter()

const unitOptions = [
  'متر', 'متر مربع', 'متر مکعب', 'کیلوگرم', 'تن', 'عدد',
  'دستگاه', 'ساعت', 'روز', 'ماه', 'پایه', 'موضوع',
]

const rows = computed(() => props.modelValue || [])

const total = computed(() =>
    rows.value.reduce((sum, row) => {
      const q = parseFloat(row.quantity) || 0
      const p = parseFloat(row.unit_price) || 0
      return sum + q * p
    }, 0)
)

const emitRows = (next) => {
  const normalized = next.map(row => ({
    ...row,
    total_price: (parseFloat(row.quantity) || 0) * (parseFloat(row.unit_price) || 0),
  }))
  emit('update:modelValue', normalized)
  emit('change', normalized)
}

const addRow = () => {
  emitRows([
    ...rows.value,
    {
      item_code: `ITEM-${String(rows.value.length + 1).padStart(3, '0')}`,
      description: '',
      unit: 'متر',
      quantity: 1,
      unit_price: 0,
      total_price: 0,
    },
  ])
}

const updateRow = (index, field, value) => {
  const next = rows.value.map((row, i) => i === index ? { ...row, [field]: value } : row)
  emitRows(next)
}

const removeRow = (index) => {
  emitRows(rows.value.filter((_, i) => i !== index))
}

const rowTotal = (row) =>
    (parseFloat(row.quantity) || 0) * (parseFloat(row.unit_price) || 0)
</script>

<template>
  <section class="border border-surface-200 dark:border-surface-700 rounded-xl overflow-hidden">
    <header class="flex items-center justify-between gap-3 p-4 bg-surface-50 dark:bg-surface-800/50">
      <h3 class="font-semibold text-sm text-surface-800 dark:text-surface-100">
        <i class="pi pi-list ml-2 text-primary" />
        {{ title }}
        <span class="text-surface-400 text-xs mr-1">({{ formatNumber(rows.length) }} آیتم)</span>
      </h3>

      <Button
          v-if="!readonly && !disabled"
          type="button"
          label="افزودن آیتم"
          icon="pi pi-plus"
          size="small"
          severity="secondary"
          outlined
          @click="addRow"
      />
    </header>

    <EmptyState
        v-if="!rows.length && readonly"
        title="آیتمی ثبت نشده است"
        description="برای این قرارداد هنوز آیتمی تعریف نشده است."
        icon="pi pi-inbox"
    />

    <div v-else class="overflow-x-auto">
      <table class="w-full text-sm min-w-[900px]">
        <thead class="bg-surface-100/60 dark:bg-surface-800 text-surface-600 dark:text-surface-300">
        <tr>
          <th class="p-3 text-right font-medium w-28">کد آیتم</th>
          <th class="p-3 text-right font-medium min-w-52">شرح فعالیت</th>
          <th class="p-3 text-right font-medium w-32">واحد</th>
          <th class="p-3 text-right font-medium w-28">مقدار</th>
          <th class="p-3 text-right font-medium w-44">بهای واحد (ریال)</th>
          <th class="p-3 text-right font-medium w-44">مبلغ کل</th>
          <th v-if="!readonly" class="p-3 text-center w-16">حذف</th>
        </tr>
        </thead>

        <tbody class="divide-y divide-surface-200 dark:divide-surface-700">
        <tr
            v-for="(row, index) in rows"
            :key="index"
            class="hover:bg-surface-50 dark:hover:bg-surface-800/40 transition"
        >
          <!-- کد -->
          <td class="p-2">
            <InputText
                v-if="!readonly"
                :model-value="row.item_code"
                :disabled="disabled"
                class="!text-xs"
                placeholder="ITEM-001"
                @update:model-value="updateRow(index, 'item_code', $event)"
            />
            <span v-else class="text-xs font-mono">{{ row.item_code }}</span>
          </td>

          <!-- شرح -->
          <td class="p-2">
            <InputText
                v-if="!readonly"
                :model-value="row.description"
                :disabled="disabled"
                class="!text-xs w-full"
                placeholder="شرح فعالیت"
                @update:model-value="updateRow(index, 'description', $event)"
            />
            <span v-else>{{ row.description }}</span>
          </td>

          <!-- واحد -->
          <td class="p-2">
            <Select
                v-if="!readonly"
                :model-value="row.unit"
                :options="unitOptions"
                :disabled="disabled"
                class="!text-xs w-full"
                @update:model-value="updateRow(index, 'unit', $event)"
            />
            <span v-else class="text-xs">{{ row.unit }}</span>
          </td>

          <!-- مقدار -->
          <td class="p-2">
            <InputText
                v-if="!readonly"
                :model-value="row.quantity"
                type="number"
                min="0"
                step="0.001"
                :disabled="disabled"
                class="!text-xs w-full"
                @update:model-value="updateRow(index, 'quantity', $event)"
            />
            <span v-else class="tabular-nums">{{ formatNumber(row.quantity, 3) }}</span>
          </td>

          <!-- بها -->
          <td class="p-2">
            <AmountInput
                v-if="!readonly"
                :model-value="row.unit_price"
                :disabled="disabled"
                @update:model-value="updateRow(index, 'unit_price', $event)"
            />
            <AmountDisplay v-else :amount="row.unit_price" :show-unit="false" size="sm" />
          </td>

          <!-- مبلغ -->
          <td class="p-2">
            <AmountDisplay :amount="rowTotal(row)" :show-unit="false" size="sm" />
          </td>

          <td v-if="!readonly" class="p-2 text-center">
            <Button
                icon="pi pi-trash"
                severity="danger"
                text
                rounded
                size="small"
                :disabled="disabled"
                aria-label="حذف آیتم"
                @click="removeRow(index)"
            />
          </td>
        </tr>

        <tr v-if="!rows.length && !readonly">
          <td :colspan="readonly ? 6 : 7" class="p-8 text-center text-surface-500">
            <i class="pi pi-plus-circle text-2xl mb-2 block" />
            برای شروع، روی «افزودن آیتم» کلیک کنید.
          </td>
        </tr>
        </tbody>

        <tfoot class="bg-primary-50 dark:bg-primary-900/20">
        <tr>
          <td colspan="5" class="p-4 text-left font-semibold">مبلغ کل قرارداد</td>
          <td class="p-4">
            <AmountDisplay :amount="total" size="lg" />
          </td>
          <td v-if="!readonly" />
        </tr>
        </tfoot>
      </table>
    </div>
  </section>
</template>