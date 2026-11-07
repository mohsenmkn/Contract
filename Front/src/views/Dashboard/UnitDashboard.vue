<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { dashboardService } from '@/services/dashboardService'
import { useNumberFormatter } from '@/composables/useNumberFormatter'

const router = useRouter()
const { formatSmartAmount } = useNumberFormatter()
const loading = ref(true)
const error = ref('')
const dashboard = ref({
  unit: {},
  stats: { total: 0, active: 0, pending: 0, expiring: 0, active_amount: 0 },
  statuses: {},
  recent_contracts: [],
})

const statusLabels = {
  pending_contract_review: 'بررسی واحد قراردادها',
  draft: 'پیش‌نویس',
  pending_legal_review: 'بررسی حقوقی',
  legal_draft: 'تنظیم متن حقوقی',
  rejected_by_legal: 'رد شده توسط حقوقی',
  pending_contractor_sign: 'امضای پیمانکار',
  pending_unit_sign: 'امضای واحد',
  pending_legal_sign: 'امضای حقوقی',
  pending_executive_sign: 'امضای مدیران',
  pending_notification: 'ابلاغ',
  active: 'فعال',
  expiring: 'در حال انقضا',
  completed: 'مختومه',
  terminated: 'فسخ‌شده',
}

const loadDashboard = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await dashboardService.getUnitDashboard()
    dashboard.value = response.data
  } catch (e) {
    error.value = e?.response?.data?.message || 'دریافت اطلاعات داشبورد با خطا مواجه شد.'
  } finally {
    loading.value = false
  }
}

const openContract = (id) => router.push({ name: 'contracts.show', params: { id } })

onMounted(loadDashboard)
</script>

<template>
  <section class="unit-dashboard" dir="rtl">
    <header class="hero">
      <div>
        <p class="eyebrow">مدیریت قراردادهای سازمانی</p>
        <h1>داشبورد {{ dashboard.unit?.name || 'واحد شما' }}</h1>
        <p v-if="dashboard.unit?.code" class="unit-code">کد واحد: {{ dashboard.unit.code }}</p>
      </div>
      <button class="primary-button" @click="router.push({ name: 'contracts.create' })">ثبت قرارداد جدید</button>
    </header>

    <div v-if="loading" class="message-card">در حال بارگذاری اطلاعات واحد…</div>
    <div v-else-if="error" class="message-card error" role="alert">{{ error }}</div>
    <template v-else>
      <div class="stats-grid">
        <article class="stat-card"><span>کل قراردادهای واحد</span><strong>{{ dashboard.stats.total }}</strong></article>
        <article class="stat-card"><span>قراردادهای فعال</span><strong>{{ dashboard.stats.active }}</strong></article>
        <article class="stat-card"><span>در جریان پیگیری</span><strong>{{ dashboard.stats.pending }}</strong></article>
        <article class="stat-card"><span>در حال انقضا</span><strong>{{ dashboard.stats.expiring }}</strong></article>
        <article class="stat-card amount"><span>مبلغ قراردادهای فعال</span><strong>{{ formatSmartAmount(dashboard.stats.active_amount || 0) }}</strong></article>
      </div>

      <div class="content-grid">
        <section class="panel">
          <div class="panel-heading">
            <div><h2>آخرین قراردادهای واحد</h2><p>فقط اطلاعات قراردادهای تخصیص‌یافته به واحد شما</p></div>
            <button class="text-button" @click="router.push({ name: 'contracts.index' })">مشاهده همه</button>
          </div>
          <div v-if="!dashboard.recent_contracts.length" class="empty-state">هنوز قراردادی برای این واحد ثبت نشده است.</div>
          <div v-else class="table-wrap">
            <table>
              <thead><tr><th>شماره</th><th>موضوع</th><th>وضعیت</th><th>مبلغ</th></tr></thead>
              <tbody>
                <tr v-for="contract in dashboard.recent_contracts" :key="contract.id" tabindex="0" @click="openContract(contract.id)" @keydown.enter="openContract(contract.id)">
                  <td>{{ contract.contract_number }}</td>
                  <td>{{ contract.subject }}</td>
                  <td><span class="status-pill">{{ statusLabels[contract.status] || contract.status }}</span></td>
                  <td>{{ formatSmartAmount(contract.current_amount || 0) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="panel status-panel">
          <div class="panel-heading"><div><h2>وضعیت قراردادها</h2><p>توزیع وضعیت در واحد شما</p></div></div>
          <div v-if="!Object.keys(dashboard.statuses).length" class="empty-state">داده‌ای برای نمایش وجود ندارد.</div>
          <div v-else class="status-list">
            <div v-for="(count, status) in dashboard.statuses" :key="status" class="status-row">
              <span>{{ statusLabels[status] || status }}</span><strong>{{ count }}</strong>
            </div>
          </div>
        </section>
      </div>
    </template>
  </section>
</template>

<style scoped>
.unit-dashboard{max-width:1440px;margin:0 auto;padding:12px;color:#172033}.hero{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:28px 32px;border-radius:20px;background:linear-gradient(120deg,#123b5d,#176b75);color:white;box-shadow:0 14px 32px #123b5d20}.eyebrow{margin:0 0 8px;color:#bfe9e3;font-size:.88rem}.hero h1{margin:0;font-size:1.7rem}.unit-code{margin:8px 0 0;color:#d8edf0}.primary-button{border:0;border-radius:10px;padding:12px 18px;background:white;color:#14515e;font:inherit;font-weight:700;cursor:pointer;white-space:nowrap}.stats-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin:20px 0}.stat-card,.panel,.message-card{background:white;border:1px solid #e5eaf0;border-radius:16px;box-shadow:0 4px 18px #13243a08}.stat-card{padding:20px;display:flex;flex-direction:column;gap:14px}.stat-card span,.panel-heading p{color:#738095;font-size:.88rem}.stat-card strong{font-size:1.55rem;color:#173e5d}.stat-card.amount strong{font-size:1.2rem}.content-grid{display:grid;grid-template-columns:minmax(0,2fr) minmax(260px,1fr);gap:16px}.panel{padding:20px;min-width:0}.panel-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px}.panel-heading h2{margin:0;font-size:1.08rem}.panel-heading p{margin:6px 0 0}.text-button{border:0;background:transparent;color:#16727a;font:inherit;cursor:pointer}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;text-align:right}th,td{padding:13px 10px;border-bottom:1px solid #edf0f4;white-space:nowrap}th{font-size:.82rem;color:#788599;font-weight:600}tbody tr{cursor:pointer}tbody tr:hover{background:#f7fafc}.status-pill{display:inline-block;background:#edf6f5;color:#28656b;padding:5px 9px;border-radius:20px;font-size:.8rem}.status-list{display:grid;gap:10px}.status-row{display:flex;justify-content:space-between;padding:10px 12px;background:#f7f9fb;border-radius:9px}.empty-state,.message-card{padding:24px;text-align:center;color:#778397}.message-card{margin-top:20px}.message-card.error{color:#a52828;background:#fff7f7}.empty-state{padding:28px 10px}@media(max-width:1000px){.stats-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.content-grid{grid-template-columns:1fr}}@media(max-width:600px){.unit-dashboard{padding:4px}.hero{align-items:flex-start;flex-direction:column;padding:22px}.hero h1{font-size:1.35rem}.stats-grid{grid-template-columns:1fr 1fr;gap:9px}.stat-card{padding:15px}.stat-card strong{font-size:1.25rem}.panel{padding:14px}}
</style>
