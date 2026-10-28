<!-- src/components/contract/ContractTimeline.vue -->
<script setup>
import { computed } from 'vue'
import EmptyState from './EmptyState.vue'
import { useDateFormatter } from '@/composables/useDateFormatter'

const props = defineProps({
  contract: { type: Object, default: null },
  activities: { type: Array, default: () => [] },
  maxItems: { type: Number, default: 12 },
})

const { toJalaliDateTime } = useDateFormatter()

const items = computed(() => {
  if (props.activities.length) {
    return props.activities.map(a => ({
      title: a.title,
      description: a.description,
      date: a.date,
      icon: a.icon || 'pi pi-circle',
      color: a.color || 'bg-surface-400',
    }))
  }

  const c = props.contract
  if (!c) return []

  const list = []

  list.push({
    title: 'ایجاد قرارداد',
    description: `قرارداد ${c.contract_number} توسط ${c.creator?.name || 'سیستم'} ایجاد شد.`,
    date: c.created_at,
    icon: 'pi pi-file-plus',
    color: 'bg-blue-500',
  })

  if (c.approved_at) {
    list.push({
      title: 'تایید و فعال‌سازی',
      description: 'قرارداد با امضای طرفین فعال شد.',
      date: c.approved_at,
      icon: 'pi pi-check-circle',
      color: 'bg-green-500',
    })
  }

  ;(c.amendments || []).forEach(a => {
    list.push({
      title: `الحاقیه ${a.amendment_number}`,
      description: a.description,
      date: a.created_at,
      icon: 'pi pi-file-edit',
      color: a.status === 'approved' ? 'bg-green-500' : 'bg-orange-500',
    })
  })

  ;(c.invoices || []).forEach(i => {
    list.push({
      title: `صورت‌وضعیت ${i.invoice_number}`,
      description: `دوره ${i.invoice_period} - وضعیت: ${i.status_label}`,
      date: i.created_at,
      icon: 'pi pi-receipt',
      color: 'bg-purple-500',
    })
  })

  ;(c.signatures || []).filter(s => s.signed_at).forEach(s => {
    list.push({
      title: s.signer_type === 'contractor' ? 'امضای پیمانکار' : 'امضای داخلی',
      description: s.signer?.name || '',
      date: s.signed_at,
      icon: 'pi pi-signature',
      color: 'bg-teal-500',
    })
  })

  return list
      .sort((a, b) => new Date(b.date) - new Date(a.date))
      .slice(0, props.maxItems)
})
</script>

<template>
  <div class="border border-surface-200 dark:border-surface-700 rounded-xl p-4">
    <h3 class="font-semibold text-sm mb-5 flex items-center gap-2">
      <i class="pi pi-history text-primary" />
      تاریخچه فعالیت‌ها
    </h3>

    <EmptyState
        v-if="!items.length"
        title="فعالیتی ثبت نشده"
        icon="pi pi-clock"
    />

    <ol v-else class="relative border-r-2 border-surface-200 dark:border-surface-700 mr-2 space-y-6">
      <li
          v-for="(item, index) in items"
          :key="index"
          class="relative pr-5"
      >
        <span
            :class="[item.color, 'absolute -right-[9px] top-1 w-4 h-4 rounded-full ring-4 ring-surface-0 dark:ring-surface-900']"
        />

        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-sm font-medium flex items-center gap-2">
              <i :class="[item.icon, 'text-xs text-surface-400']" />
              {{ item.title }}
            </p>
            <p v-if="item.description" class="text-xs text-surface-500 mt-1 leading-6">
              {{ item.description }}
            </p>
          </div>

          <time class="text-[11px] text-surface-400 whitespace-nowrap">
            {{ toJalaliDateTime(item.date) }}
          </time>
        </div>
      </li>
    </ol>
  </div>
</template>