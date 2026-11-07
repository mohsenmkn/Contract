<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/axios'
import { showToast } from '@/plugins/toast'

const router = useRouter()
const contracts = ref([])
const selected = ref(null)
const loading = ref(false)

const load = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/guarantee-desk')
    contracts.value = data.data || []
  } catch (error) {
    showToast({ severity: 'error', summary: 'خطا', detail: error.response?.data?.message || 'بارگذاری میز ضمانت انجام نشد.' })
  } finally {
    loading.value = false
  }
}

const open = async (contract) => {
  try {
    const { data } = await api.get(`/guarantee-desk/${contract.id}`)
    selected.value = data.data
  } catch (error) {
    showToast({ severity: 'error', summary: 'خطا', detail: error.response?.data?.message || 'دریافت جزئیات ضمانت انجام نشد.' })
  }
}

onMounted(load)
</script>

<template>
  <main class="p-6 space-y-5">
    <header>
      <h1 class="text-2xl font-bold">میز دریافت ضمانت</h1>
      <p class="text-surface-500 mt-1">پس از تحویل فیزیکی ضمانت از پیمانکار، آن را ثبت کنید. ثبت ضمانت شرط ابلاغ نهایی قرارداد است و امضاهای قبلی را متوقف نمی‌کند.</p>
    </header>

    <Card v-if="selected">
      <template #title>قرارداد {{ selected.contract_number }} — {{ selected.subject }}</template>
      <template #content>
        <p class="mb-4">پیمانکار: {{ selected.party?.name }} · مبلغ قرارداد: {{ selected.current_amount }} {{ selected.currency }}</p>
        <div v-if="selected.guarantees?.length" class="space-y-2 mb-5">
          <div v-for="item in selected.guarantees" :key="item.id" class="rounded border p-3">
            {{ item.instrument_type }} · شماره {{ item.instrument_number || '—' }} · مبلغ {{ item.amount }} {{ item.currency }}
          </div>
        </div>
        <div class="flex gap-2">
          <Button label="ثبت ضمانت دریافت‌شده" icon="pi pi-plus" @click="router.push(`/contracts/${selected.id}/guarantees/create`)" />
          <Button label="بستن" severity="secondary" outlined @click="selected = null" />
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <div v-if="loading" class="py-10 text-center">در حال بارگذاری…</div>
        <div v-else-if="!contracts.length" class="py-10 text-center text-surface-500">قراردادی برای ثبت ضمانت تحویل‌شده وجود ندارد.</div>
        <div v-else class="space-y-3">
          <button v-for="contract in contracts" :key="contract.id" class="w-full text-right rounded-xl border p-4 hover:border-primary" @click="open(contract)">
            <div class="font-semibold">{{ contract.contract_number }} · {{ contract.subject }}</div>
            <div class="text-sm text-surface-500 mt-1">{{ contract.party?.name }} · {{ contract.current_amount }} {{ contract.currency }}</div>
          </button>
        </div>
      </template>
    </Card>
  </main>
</template>
