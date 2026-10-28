<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { dashboardService } from '@/services/dashboardService.js'
import { useNumberFormatter } from '@/composables/useNumberFormatter'
import { useDateFormatter } from '@/composables/useDateFormatter'

const router = useRouter()
const { formatSmartAmount } = useNumberFormatter()
const { daysRemaining } = useDateFormatter()
// ═══════════════════════════════════════════════
// STATE
// ═══════════════════════════════════════════════
const loading = ref(true)
const period = ref('monthly')
const periodOptions = [
  { label: 'ماهانه', value: 'monthly' },
  { label: 'فصلی', value: 'quarterly' },
  { label: 'سالانه', value: 'yearly' },
]
const stats = ref({
  totalContracts: 0,
  activeContracts: 0,
  expiringContracts: 0,
  totalAmount: 0,
  pendingInvoices: 0,
  pendingPayments: 0,
  totalGuarantees: 0,
  expiringGuarantees: 0,
})
const statusChartData = ref(null)
const amountChartData = ref(null)
const typeChartData = ref(null)
const expiringContracts = ref([])
const expiringGuarantees = ref([])
const recentActivities = ref([])
// ═══════════════════════════════════════════════
// CHART OPTIONS
// ═══════════════════════════════════════════════
const getChartOptions = (title) => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: { font: { family: 'Vazirmatn', size: 12 }, padding: 16 },
    },
    title: {
      display: !!title,
      text: title,
      font: { family: 'Vazirmatn', size: 14, weight: 'bold' },
    },
    tooltip: {
      rtl: true,
      titleFont: { family: 'Vazirmatn' },
      bodyFont: { family: 'Vazirmatn' },
    },
  },
})
const pieOptions = computed(() => ({
  ...getChartOptions(),
  cutout: '60%',
}))
const lineOptions = computed(() => ({
  ...getChartOptions(),
  scales: {
    y: {
      beginAtZero: true,
      ticks: { font: { family: 'Vazirmatn' } },
      grid: { color: 'rgba(0,0,0,0.05)' },
    },
    x: {
      ticks: { font: { family: 'Vazirmatn' } },
      grid: { display: false },
    },
  },
}))
const barOptions = computed(() => ({
  ...getChartOptions(),
  scales: {
    y: {
      beginAtZero: true,
      ticks: { font: { family: 'Vazirmatn' } },
      grid: { color: 'rgba(0,0,0,0.05)' },
    },
    x: {
      ticks: { font: { family: 'Vazirmatn' } },
      grid: { display: false },
    },
  },
}))
// ═══════════════════════════════════════════════
// LOAD DASHBOARD
// ═══════════════════════════════════════════════
const loadDashboard = async () => {
  loading.value = true
  try {
    const [contractStats, invoiceStats, statusChart, amountChart, expiring, guarantees, activities] = await Promise.all([
      dashboardService.getContractStats().catch(() => ({ data: {} })),
      dashboardService.getInvoiceStats().catch(() => ({ data: {} })),
      dashboardService.getContractStatusChart().catch(() => ({ data: {} })),
      dashboardService.getContractAmountChart(period.value).catch(() => ({ data: {} })),
      dashboardService.getExpiringContracts(30).catch(() => ({ data: [] })),
      dashboardService.getExpiringGuarantees(30).catch(() => ({ data: [] })),
      dashboardService.getRecentActivities(10).catch(() => ({ data: [] })),
    ])
    stats.value = {
      totalContracts: contractStats.data.total || 0,
      activeContracts: contractStats.data.active || 0,
      expiringContracts: contractStats.data.expiring || 0,
      totalAmount: contractStats.data.total_amount || 0,
      pendingInvoices: invoiceStats.data.pending || 0,
      pendingPayments: invoiceStats.data.pending_amount || 0,
      totalGuarantees: contractStats.data.total_guarantees || 0,
      expiringGuarantees: contractStats.data.expiring_guarantees || 0,
    }

// نمودار وضعیت قراردادها - پالت هماهنگ با تم
    const statusData = statusChart.data
    statusChartData.value = {
      labels: statusData.labels || ['پیش‌نویس', 'فعال', 'در حال اتمام', 'خاتمه یافته', 'فسخ'],
      datasets: [{
        data: statusData.values || [0, 0, 0, 0, 0],
        backgroundColor: [
          'oklch(65% 0.02 250)',   // خاکستری آبی
          'oklch(55% 0.15 155)',   // سبز قرارداد
          'oklch(65% 0.15 75)',    // نارنجی
          'oklch(45% 0.15 250)',   // آبی اصلی
          'oklch(50% 0.20 25)',    // قرمز
        ],
        hoverBackgroundColor: [
          'oklch(58% 0.02 250)',
          'oklch(48% 0.16 155)',
          'oklch(58% 0.16 75)',
          'oklch(38% 0.17 250)',
          'oklch(43% 0.22 25)',
        ],
        borderWidth: 0,
      }],
    }

// نمودار مبالغ - پالت آبی-سبز
    const amtData = amountChart.data
    amountChartData.value = {
      labels: amtData.labels || ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور'],
      datasets: [
        {
          label: 'قراردادهای جدید',
          data: amtData.new_contracts || [0, 0, 0, 0, 0, 0],
          borderColor: 'oklch(45% 0.15 250)',
          backgroundColor: 'oklch(45% 0.15 250 / 0.12)',
          fill: true,
          tension: 0.4,
          pointBackgroundColor: 'oklch(45% 0.15 250)',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 4,
        },
        {
          label: 'پرداخت‌ها',
          data: amtData.payments || [0, 0, 0, 0, 0, 0],
          borderColor: 'oklch(55% 0.15 155)',
          backgroundColor: 'oklch(55% 0.15 155 / 0.12)',
          fill: true,
          tension: 0.4,
          pointBackgroundColor: 'oklch(55% 0.15 155)',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 4,
        },
      ],
    }

// نمودار نوع قراردادها
    typeChartData.value = {
      labels: ['بالادستی (کارفرما)', 'پایین‌دستی (پیمانکار)'],
      datasets: [{
        data: [
          contractStats.data.upstream || 0,
          contractStats.data.downstream || 0,
        ],
        backgroundColor: [
          'oklch(45% 0.15 250)',
          'oklch(55% 0.15 155)',
        ],
        hoverBackgroundColor: [
          'oklch(38% 0.17 250)',
          'oklch(48% 0.16 155)',
        ],
        borderWidth: 0,
      }],
    }

    expiringContracts.value = expiring.data || []
    expiringGuarantees.value = guarantees.data || []
    recentActivities.value = activities.data || []
  } catch (err) {
    console.error('Dashboard error:', err)
  } finally {
    loading.value = false
  }
}
// ═══════════════════════════════════════════════
// LIFECYCLE
// ═══════════════════════════════════════════════
onMounted(async () => {
  await loadDashboard()
})
watch(period, loadDashboard)
</script>
<template>
  <div class="dashboard-page" dir="rtl">
    <div class="dashboard-background">
    <div class="bg-shape shape-one"></div>
    <div class="bg-shape shape-two"></div>
    <div class="bg-shape shape-three"></div>
    <div class="bg-grid"></div>
    <div class="floating-icons">
    <i class="pi pi-file-signature floating-icon icon-1"></i>
    <i class="pi pi-handshake floating-icon icon-2"></i>
    <i class="pi pi-shield floating-icon icon-3"></i>
    <i class="pi pi-calendar floating-icon icon-4"></i>
    <i class="pi pi-chart-line floating-icon icon-5"></i>
    <i class="pi pi-wallet floating-icon icon-6"></i>
    </div>
    </div>
    <div class="dashboard-shell">
      <!-- ═══════ هدر داشبورد ═══════ -->
      <header class="dashboard-header">
        <div class="header-content">
          <div class="header-text">
            <div class="header-badge">
              <i class="pi pi-chart-bar"></i>
              <span>داشبورد تحلیلی</span>
            </div>
            <h1 class="header-title">مدیریت یکپارچه قراردادها</h1>
            <p class="header-subtitle">
              نمای کلی وضعیت قراردادها، تعهدات مالی و هشدارهای سامانه
            </p>
          </div>
          <div class="header-actions">
            <Select
                v-model="period"
                :options="periodOptions"
                option-label="label"
                option-value="value"
                class="period-select"
            />
            <Button
                label="گزارش‌گیری"
                icon="pi pi-file-pdf"
                class="report-btn"
                outlined
            />
          </div>
        </div>
      </header>

      <!-- Loading -->
      <div v-if="loading" class="loading-wrapper">
        <div class="loading-card">
          <i class="pi pi-spin pi-spinner loading-icon"></i>
          <p>در حال بارگذاری اطلاعات...</p>
        </div>
      </div>

      <template v-else>
        <!-- ═══════ آمار اصلی ═══════ -->
        <section class="stats-section">
          <div class="section-header">
            <div class="section-title-group">
              <i class="pi pi-chart-pie section-icon"></i>
              <h2 class="section-title">آمار کلیدی قراردادها</h2>
            </div>
          </div>

          <div class="stats-grid primary-stats">
            <StatCard
                title="کل قراردادها"
                :value="stats.totalContracts"
                icon="pi pi-file"
                color="primary"
                unit="count"
                clickable
                @click="router.push('/contracts')"
            />
            <StatCard
                title="قراردادهای فعال"
                :value="stats.activeContracts"
                icon="pi pi-check-circle"
                color="success"
                unit="count"
            />
            <StatCard
                title="در حال اتمام (۳۰ روز)"
                :value="stats.expiringContracts"
                icon="pi pi-exclamation-triangle"
                color="warn"
                unit="count"
            />
            <StatCard
                title="مجموع مبالغ قراردادها"
                :value="stats.totalAmount"
                icon="pi pi-dollar"
                color="info"
                unit="smart"
            />
          </div>

          <div class="stats-grid financial-stats">
            <StatCard
                title="صورت‌وضعیت‌های در انتظار"
                :value="stats.pendingInvoices"
                icon="pi pi-receipt"
                color="warn"
                unit="count"
                clickable
                @click="router.push('/invoices?status=submitted')"
            />
            <StatCard
                title="مبلغ در انتظار پرداخت"
                :value="stats.pendingPayments"
                icon="pi pi-wallet"
                color="danger"
                unit="smart"
            />
            <StatCard
                title="ضمانت‌نامه‌های فعال"
                :value="stats.totalGuarantees"
                icon="pi pi-shield"
                color="info"
                unit="count"
                clickable
                @click="router.push('/guarantees')"
            />
            <StatCard
                title="ضمانت‌نامه‌های در حال اتمام"
                :value="stats.expiringGuarantees"
                icon="pi pi-clock"
                color="danger"
                unit="count"
            />
          </div>
        </section>

        <!-- ═══════ نمودارها ردیف اول ═══════ -->
        <section class="charts-section">
          <div class="section-header">
            <div class="section-title-group">
              <i class="pi pi-chart-line section-icon"></i>
              <h2 class="section-title">تحلیل وضعیت قراردادها</h2>
            </div>
          </div>

          <div class="charts-grid">
            <!-- نمودار وضعیت قراردادها -->
            <Card class="chart-card">
              <template #title>
                <div class="card-title-wrapper">
                  <div class="card-title-icon blue">
                    <i class="pi pi-chart-pie"></i>
                  </div>
                  <span>وضعیت قراردادها</span>
                </div>
              </template>
              <div class="chart-container">
                <Chart
                    v-if="statusChartData"
                    type="doughnut"
                    :data="statusChartData"
                    :options="pieOptions"
                />
              </div>
            </Card>

            <!-- نمودار نوع قراردادها -->
            <Card class="chart-card">
              <template #title>
                <div class="card-title-wrapper">
                  <div class="card-title-icon green">
                    <i class="pi pi-chart-pie"></i>
                  </div>
                  <span>توزیع نوع قراردادها</span>
                </div>
              </template>
              <div class="chart-container">
                <Chart
                    v-if="typeChartData"
                    type="doughnut"
                    :data="typeChartData"
                    :options="pieOptions"
                />
              </div>
            </Card>

            <!-- خلاصه عملکرد -->
            <Card class="chart-card performance-card">
              <template #title>
                <div class="card-title-wrapper">
                  <div class="card-title-icon gold">
                    <i class="pi pi-info-circle"></i>
                  </div>
                  <span>خلاصه عملکرد</span>
                </div>
              </template>
              <div class="performance-list">
                <!-- نرخ فعال‌سازی -->
                <div class="performance-item">
                  <div class="performance-info">
                    <div class="performance-icon success">
                      <i class="pi pi-check"></i>
                    </div>
                    <div class="performance-text">
                      <p class="performance-title">نرخ فعال‌سازی</p>
                      <p class="performance-subtitle">قراردادهای فعال / کل</p>
                    </div>
                  </div>
                  <div class="performance-value success">
                    {{ stats.totalContracts ? Math.round((stats.activeContracts / stats.totalContracts) * 100) : 0 }}٪
                  </div>
                </div>

                <!-- نرخ هشدار -->
                <div class="performance-item">
                  <div class="performance-info">
                    <div class="performance-icon warn">
                      <i class="pi pi-exclamation-triangle"></i>
                    </div>
                    <div class="performance-text">
                      <p class="performance-title">نرخ هشدار</p>
                      <p class="performance-subtitle">در حال اتمام / فعال</p>
                    </div>
                  </div>
                  <div class="performance-value warn">
                    {{ stats.activeContracts ? Math.round((stats.expiringContracts / stats.activeContracts) * 100) : 0 }}٪
                  </div>
                </div>

                <!-- میانگین مبلغ -->
                <div class="performance-item">
                  <div class="performance-info">
                    <div class="performance-icon info">
                      <i class="pi pi-receipt"></i>
                    </div>
                    <div class="performance-text">
                      <p class="performance-title">میانگین مبلغ قرارداد</p>
                      <p class="performance-subtitle">مجموع / تعداد</p>
                    </div>
                  </div>
                  <div class="performance-value info">
                    <AmountDisplay
                        :amount="stats.totalContracts ? stats.totalAmount / stats.totalContracts : 0"
                        unit="smart"
                        size="sm"
                    />
                  </div>
                </div>
              </div>
            </Card>
          </div>
        </section>

        <!-- ═══════ نمودار خطی مبالغ ═══════ -->
        <section class="line-chart-section">
          <Card class="line-chart-card">
            <template #title>
              <div class="line-chart-header">
                <div class="card-title-wrapper">
                  <div class="card-title-icon blue">
                    <i class="pi pi-chart-line"></i>
                  </div>
                  <span>روند مبالغ قراردادها و پرداخت‌ها</span>
                </div>
                <span class="period-label">
              <i class="pi pi-calendar"></i>
              بر اساس دوره {{ period === 'monthly' ? 'ماهانه' : period === 'quarterly' ? 'فصلی' : 'سالانه' }}
            </span>
              </div>
            </template>
            <div class="line-chart-container">
              <Chart
                  v-if="amountChartData"
                  type="line"
                  :data="amountChartData"
                  :options="lineOptions"
              />
            </div>
          </Card>
        </section>

        <!-- ═══════ لیست‌های هشدار و فعالیت ═══════ -->
        <section class="alerts-section">
          <div class="section-header">
            <div class="section-title-group">
              <i class="pi pi-bell section-icon"></i>
              <h2 class="section-title">هشدارها و فعالیت‌ها</h2>
            </div>
          </div>

          <div class="alerts-grid">
            <!-- قراردادهای در حال اتمام -->
            <Card class="alert-card expiring-card">
              <template #title>
                <div class="alert-card-header">
                  <div class="card-title-wrapper">
                    <div class="card-title-icon warn">
                      <i class="pi pi-exclamation-triangle"></i>
                    </div>
                    <span>قراردادهای در حال اتمام</span>
                  </div>
                  <Button
                      label="همه"
                      icon="pi pi-arrow-left"
                      icon-pos="left"
                      size="small"
                      text
                      class="view-all-btn"
                      @click="router.push('/contracts?expiring_soon=30')"
                  />
                </div>
              </template>
              <EmptyState
                  v-if="!expiringContracts.length"
                  title="موردی نیست"
                  description="هیچ قراردادی در ۳۰ روز آینده منقضی نمی‌شود."
                  icon="pi pi-check-circle"
                  compact
              />
              <div v-else class="alert-list">
                <div
                    v-for="contract in expiringContracts"
                    :key="contract.id"
                    class="alert-item expiring-item"
                    @click="router.push(`/contracts/${contract.id}`)"
                >
                  <div class="alert-item-top">
                    <div class="alert-item-info">
                      <p class="alert-item-number">{{ contract.contract_number }}</p>
                      <p class="alert-item-subject">{{ contract.subject }}</p>
                    </div>
                    <ContractStatusBadge :status="contract.status" />
                  </div>
                  <div class="alert-item-bottom">
                <span class="alert-item-party">
                  <i class="pi pi-user"></i>
                  {{ contract.party?.name }}
                </span>
                    <span class="alert-item-days">
                  <i class="pi pi-clock"></i>
                  {{ daysRemaining(contract.end_date) }} روز مانده
                </span>
                  </div>
                </div>
              </div>
            </Card>

            <!-- ضمانت‌نامه‌های در حال اتمام -->
            <Card class="alert-card guarantees-card">
              <template #title>
                <div class="card-title-wrapper">
                  <div class="card-title-icon danger">
                    <i class="pi pi-shield"></i>
                  </div>
                  <span>ضمانت‌نامه‌های در حال اتمام</span>
                </div>
              </template>
              <EmptyState
                  v-if="!expiringGuarantees.length"
                  title="موردی نیست"
                  icon="pi pi-check-circle"
                  compact
              />
              <div v-else class="alert-list">
                <div
                    v-for="g in expiringGuarantees"
                    :key="g.id"
                    class="alert-item guarantee-item"
                >
                  <div class="alert-item-top">
                    <div class="alert-item-info">
                      <p class="alert-item-number">{{ g.guarantee_number }}</p>
                      <p class="alert-item-subject">{{ g.type_label }} - {{ g.bank_name }}</p>
                    </div>
                  </div>
                  <div class="alert-item-bottom">
                    <AmountDisplay :amount="g.amount" size="xs" />
                    <span class="alert-item-days">
                  <i class="pi pi-clock"></i>
                  {{ daysRemaining(g.expiry_date) }} روز مانده
                </span>
                  </div>
                </div>
              </div>
            </Card>

            <!-- فعالیت‌های اخیر -->
            <Card class="alert-card activities-card">
              <template #title>
                <div class="card-title-wrapper">
                  <div class="card-title-icon blue">
                    <i class="pi pi-history"></i>
                  </div>
                  <span>فعالیت‌های اخیر</span>
                </div>
              </template>
              <EmptyState
                  v-if="!recentActivities.length"
                  title="فعالیتی ثبت نشده"
                  icon="pi pi-clock"
                  compact
              />
              <div v-else class="activities-list">
                <div
                    v-for="(activity, index) in recentActivities"
                    :key="index"
                    class="activity-item"
                >
                  <div
                      :class="['activity-icon', `activity-${activity.color || 'default'}`]"
                  >
                    <i :class="[activity.icon || 'pi pi-circle']"></i>
                  </div>
                  <div class="activity-content">
                    <p class="activity-title">{{ activity.title }}</p>
                    <p class="activity-date">
                      <i class="pi pi-clock"></i>
                      <DateDisplay :date="activity.date" show-time />
                    </p>
                  </div>
                </div>
              </div>
            </Card>
          </div>
        </section>
      </template>
    </div>
  </div>
</template>

<style scoped>
/* ═══════ توکن‌های رنگ (هماهنگ با Auth) ═══════ */
:root,
.dashboard-page {
  --accent: oklch(45% 0.15 250);
  --accent-dark: oklch(38% 0.17 250);
  --accent-soft: oklch(45% 0.15 250 / 0.10);
  --accent-ring: oklch(45% 0.15 250 / 0.14);
  --gold: oklch(55% 0.15 155);
  --gold-dark: oklch(48% 0.16 155);
  --gold-soft: oklch(55% 0.15 155 / 0.10);
  --surface: oklch(99% 0.004 250);
  --bg: oklch(97.5% 0.008 250);
  --border: oklch(88% 0.008 250);
  --text-main: oklch(15% 0.01 250);
  --text-muted: oklch(45% 0.012 250);
  --text-subtle: oklch(65% 0.008 250);
  --danger: oklch(50% 0.20 25);
  --danger-bg: oklch(97% 0.04 25);
  --danger-border: oklch(85% 0.08 25);
  --warn: oklch(65% 0.15 75);
  --warn-bg: oklch(97% 0.04 75);
  --warn-border: oklch(85% 0.08 75);
  --success: oklch(42% 0.14 145);
  --success-bg: oklch(97% 0.04 145);
  --success-border:oklch(82% 0.10 145);
  --info: oklch(55% 0.12 220);
  --info-bg: oklch(97% 0.04 220);
  --info-border: oklch(85% 0.08 220);
}

/* ═══════ صفحه ═══════ */
.dashboard-page {
  min-height: 100dvh;
  direction: rtl;
  background-color: var(--bg);
  position: relative;
  padding: 24px;
  overflow-x: hidden;
}

/* ═══════ پس‌زمینه تزئینی ═══════ */
.dashboard-background {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  overflow: hidden;
}

.bg-shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
}

.shape-one {
  width: 500px;
  height: 500px;
  top: -150px;
  right: -150px;
  background: oklch(45% 0.15 250 / 0.08);
}

.shape-two {
  width: 450px;
  height: 450px;
  bottom: -150px;
  left: -150px;
  background: oklch(55% 0.15 155 / 0.07);
}

.shape-three {
  width: 300px;
  height: 300px;
  top: 40%;
  left: 40%;
  background: oklch(50% 0.12 200 / 0.05);
}

.bg-grid {
  position: absolute;
  inset: 0;
  background-image:
      linear-gradient(oklch(58% 0.008 250 / 0.05) 1px, transparent 1px),
      linear-gradient(90deg, oklch(58% 0.008 250 / 0.05) 1px, transparent 1px);
  background-size: 50px 50px;
  mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 30%, transparent 80%);
}

.floating-icons {
  position: absolute;
  inset: 0;
}

.floating-icon {
  position: absolute;
  font-size: 32px;
  opacity: 0.06;
  color: var(--accent);
  animation: float 10s ease-in-out infinite;
}

.icon-1 { top: 10%; left: 8%; animation-delay: 0s; }
.icon-2 { top: 65%; left: 12%; animation-delay: 2s; color: var(--gold); }
.icon-3 { top: 15%; right: 10%; animation-delay: 4s; }
.icon-4 { bottom: 20%; right: 8%; animation-delay: 1s; color: var(--gold); }
.icon-5 { top: 45%; left: 3%; animation-delay: 3s; }
.icon-6 { top: 30%; right: 15%; animation-delay: 2.5s; color: var(--gold); }

@keyframes float {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-25px) rotate(5deg); }
}

/* ═══════ پوسته داشبورد ═══════ */
.dashboard-shell {
  position: relative;
  z-index: 1;
  max-width: 1600px;
  margin: 0 auto;
}

/* ═══════ هدر ═══════ */
.dashboard-header {
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  border-radius: 20px;
  padding: 28px 32px;
  margin-bottom: 24px;
  box-shadow:
      0 12px 30px oklch(45% 0.15 250 / 0.25),
      0 0 0 1px oklch(95% 0.004 250 / 0.1);
  position: relative;
  overflow: hidden;
}

.dashboard-header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, var(--gold) 0%, transparent 70%);
  opacity: 0.15;
  filter: blur(40px);
}

.dashboard-header::after {
  content: '';
  position: absolute;
  bottom: -50%;
  left: -10%;
  width: 250px;
  height: 250px;
  background: radial-gradient(circle, var(--gold) 0%, transparent 70%);
  opacity: 0.10;
  filter: blur(40px);
}

.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
  position: relative;
  z-index: 1;
}

.header-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: oklch(100% 0 0 / 0.15);
  backdrop-filter: blur(10px);
  color: #fff;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 11.5px;
  font-weight: 600;
  margin-bottom: 10px;
  border: 1px solid oklch(100% 0 0 / 0.2);
}

.header-badge i {
  font-size: 12px;
}

.header-title {
  font-size: 24px;
  font-weight: 800;
  color: #fff;
  margin: 0 0 6px;
  letter-spacing: -0.01em;
}

.header-subtitle {
  font-size: 13px;
  color: oklch(100% 0 0 / 0.85);
  margin: 0;
  line-height: 1.7;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.period-select :deep(.p-select) {
  background: oklch(100% 0 0 / 0.15) !important;
  backdrop-filter: blur(10px);
  border: 1px solid oklch(100% 0 0 / 0.2) !important;
  color: #fff !important;
  min-width: 140px;
  border-radius: 10px !important;
}

.period-select :deep(.p-select-label) {
  color: #fff !important;
  font-weight: 600;
}

.period-select :deep(.p-select-dropdown-icon) {
  color: #fff !important;
}

.report-btn {
  background: oklch(100% 0 0 / 0.15) !important;
  backdrop-filter: blur(10px);
  border: 1px solid oklch(100% 0 0 / 0.2) !important;
  color: #fff !important;
  font-weight: 600;
  border-radius: 10px !important;
}

.report-btn:hover {
  background: oklch(100% 0 0 / 0.25) !important;
}

.report-btn :deep(.p-button-icon) {
  color: #fff !important;
}

/* ═══════ Loading ═══════ */
.loading-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 80px 20px;
}

.loading-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 32px 48px;
  text-align: center;
  box-shadow: 0 8px 24px oklch(15% 0.01 250 / 0.06);
}

.loading-icon {
  font-size: 36px;
  color: var(--accent);
  margin-bottom: 12px;
}

.loading-card p {
  color: var(--text-muted);
  font-size: 13px;
  margin: 0;
  font-weight: 500;
}

/* ═══════ بخش‌ها ═══════ */
.stats-section,
.charts-section,
.line-chart-section,
.alerts-section {
  margin-bottom: 24px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
  padding: 0 4px;
}

.section-title-group {
  display: flex;
  align-items: center;
  gap: 10px;
}

.section-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  box-shadow: 0 4px 12px oklch(45% 0.15 250 / 0.25);
}

.section-title {
  font-size: 16px;
  font-weight: 800;
  color: var(--text-main);
  margin: 0;
  letter-spacing: -0.01em;
}

/* ═══════ گرید آمار ═══════ */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 16px;
}

.stats-grid:last-child {
  margin-bottom: 0;
}

/* ═══════ گرید نمودارها ═══════ */
.charts-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.alerts-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

/* ═══════ کارت‌ها ═══════ */
:deep(.p-card) {
  background: oklch(99.5% 0.003 250 / 0.97) !important;
  border: 1px solid oklch(90% 0.006 250 / 0.9) !important;
  border-radius: 16px !important;
  box-shadow:
      0 8px 24px oklch(15% 0.01 250 / 0.06),
      0 0 0 1px oklch(95% 0.004 250 / 0.5) !important;
  backdrop-filter: blur(10px);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

:deep(.p-card:hover) {
  box-shadow:
      0 12px 32px oklch(15% 0.01 250 / 0.10),
      0 0 0 1px oklch(95% 0.004 250 / 0.7) !important;
}

:deep(.p-card-header) {
  padding: 16px 20px 0 !important;
}

:deep(.p-card-body) {
  padding: 12px 20px 20px !important;
}

:deep(.p-card-title) {
  font-size: 14px !important;
  font-weight: 700 !important;
  color: var(--text-main) !important;
  margin: 0 !important;
}

/* عنوان کارت با آیکون */
.card-title-wrapper {
  display: flex;
  align-items: center;
  gap: 10px;
}

.card-title-icon {
  width: 32px;
  height: 32px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  color: #fff;
  flex-shrink: 0;
}

.card-title-icon.blue {
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  box-shadow: 0 4px 10px oklch(45% 0.15 250 / 0.25);
}

.card-title-icon.green {
  background: linear-gradient(135deg, var(--gold), var(--gold-dark));
  box-shadow: 0 4px 10px oklch(55% 0.15 155 / 0.25);
}

.card-title-icon.gold {
  background: linear-gradient(135deg, oklch(65% 0.15 75), oklch(58% 0.16 75));
  box-shadow: 0 4px 10px oklch(65% 0.15 75 / 0.25);
}

.card-title-icon.warn {
  background: linear-gradient(135deg, var(--warn), oklch(58% 0.16 75));
  box-shadow: 0 4px 10px oklch(65% 0.15 75 / 0.25);
}

.card-title-icon.danger {
  background: linear-gradient(135deg, var(--danger), oklch(43% 0.22 25));
  box-shadow: 0 4px 10px oklch(50% 0.20 25 / 0.25);
}

/* ═══════ نمودارها ═══════ */
.chart-container {
  height: 280px;
  position: relative;
}

.line-chart-container {
  height: 320px;
  position: relative;
}

.line-chart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  gap: 12px;
  flex-wrap: wrap;
}

.period-label {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: var(--text-subtle);
  background: var(--bg);
  padding: 4px 10px;
  border-radius: 14px;
  border: 1px solid var(--border);
  font-weight: 500;
}

.period-label i {
  font-size: 11px;
  color: var(--accent);
}

/* ═══════ کارت خلاصه عملکرد ═══════ */
.performance-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.performance-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  background: linear-gradient(135deg, oklch(98% 0.005 250), oklch(97% 0.008 250));
  border: 1px solid var(--border);
  border-radius: 12px;
  transition: border-color 0.2s, transform 0.2s;
}

.performance-item:hover {
  border-color: oklch(80% 0.010 250);
  transform: translateX(-2px);
}

.performance-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.performance-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  flex-shrink: 0;
}

.performance-icon.success {
  background: var(--success-bg);
  color: var(--success);
  border: 1px solid var(--success-border);
}

.performance-icon.warn {
  background: var(--warn-bg);
  color: var(--warn);
  border: 1px solid var(--warn-border);
}

.performance-icon.info {
  background: var(--info-bg);
  color: var(--info);
  border: 1px solid var(--info-border);
}

.performance-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-main);
  margin: 0 0 2px;
}

.performance-subtitle {
  font-size: 11px;
  color: var(--text-subtle);
  margin: 0;
}

.performance-value {
  font-size: 18px;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
}

.performance-value.success { color: var(--success); }
.performance-value.warn { color: var(--warn); }
.performance-value.info { color: var(--info); }

/* ═══════ کارت‌های هشدار ═══════ */
.alert-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  gap: 10px;
}

.view-all-btn {
  font-size: 11.5px !important;
  font-weight: 600 !important;
  color: var(--accent) !important;
  padding: 4px 10px !important;
  border-radius: 8px !important;
}

.view-all-btn:hover {
  background: var(--accent-soft) !important;
}

.alert-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 380px;
  overflow-y: auto;
  padding-left: 4px;
}

.alert-list::-webkit-scrollbar {
  width: 5px;
}

.alert-list::-webkit-scrollbar-track {
  background: transparent;
}

.alert-list::-webkit-scrollbar-thumb {
  background: var(--border);
  border-radius: 10px;
}

.alert-item {
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid;
  transition: transform 0.2s, box-shadow 0.2s;
}

.expiring-item {
  background: linear-gradient(135deg, var(--warn-bg), oklch(98% 0.02 75));
  border-color: var(--warn-border);
  cursor: pointer;
}

.expiring-item:hover {
  transform: translateX(-3px);
  box-shadow: 0 4px 12px oklch(65% 0.15 75 / 0.15);
  border-color: var(--warn);
}

.guarantee-item {
  background: linear-gradient(135deg, var(--danger-bg), oklch(98% 0.02 25));
  border-color: var(--danger-border);
}

.alert-item-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 8px;
}

.alert-item-info {
  flex: 1;
  min-width: 0;
}

.alert-item-number {
  font-size: 12.5px;
  font-weight: 700;
  color: var(--text-main);
  margin: 0 0 3px;
  font-family: 'Vazirmatn', monospace;
  direction: ltr;
  text-align: right;
}

.alert-item-subject {
  font-size: 11.5px;
  color: var(--text-muted);
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.alert-item-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  font-size: 11.5px;
}

.alert-item-party {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: var(--text-muted);
}

.alert-item-party i {
  font-size: 11px;
  color: var(--text-subtle);
}

.alert-item-days {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.expiring-item .alert-item-days {
  color: var(--warn);
}

.guarantee-item .alert-item-days {
  color: var(--danger);
}

.alert-item-days i {
  font-size: 11px;
}

/* ═══════ فعالیت‌ها ═══════ */
.activities-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-height: 380px;
  overflow-y: auto;
  padding-left: 4px;
}

.activities-list::-webkit-scrollbar {
  width: 5px;
}

.activities-list::-webkit-scrollbar-thumb {
  background: var(--border);
  border-radius: 10px;
}

.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border);
}

.activity-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.activity-icon {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #fff;
  font-size: 13px;
  box-shadow: 0 4px 10px oklch(0% 0 0 / 0.1);
}

.activity-green { background: linear-gradient(135deg, var(--gold), var(--gold-dark)); }
.activity-red { background: linear-gradient(135deg, var(--danger), oklch(43% 0.22 25)); }
.activity-orange { background: linear-gradient(135deg, var(--warn), oklch(58% 0.16 75)); }
.activity-blue { background: linear-gradient(135deg, var(--accent), var(--accent-dark)); }
.activity-default { background: linear-gradient(135deg, oklch(55% 0.02 250), oklch(48% 0.02 250)); }

.activity-content {
  flex: 1;
  min-width: 0;
}

.activity-title {
  font-size: 12.5px;
  font-weight: 600;
  color: var(--text-main);
  margin: 0 0 4px;
  line-height: 1.5;
}

.activity-date {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  color: var(--text-subtle);
  margin: 0;
}

.activity-date i {
  font-size: 10px;
}

/* ═══════ ریسپانسیو ═══════ */
@media (max-width: 1280px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .charts-grid,
  .alerts-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 900px) {
  .dashboard-page {
    padding: 16px;
  }
  .dashboard-header {
    padding: 22px 20px;
    border-radius: 16px;
  }
  .header-title {
    font-size: 20px;
  }
  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }
  .charts-grid,
  .alerts-grid {
    grid-template-columns: 1fr;
  }
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 560px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  .header-actions {
    width: 100%;
    flex-direction: column;
    align-items: stretch;
  }
  .period-select,
  .report-btn {
    width: 100%;
  }
  .floating-icon {
    font-size: 24px;
  }
}

@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    transition-duration: 0.01ms !important;
    animation-duration: 0.01ms !important;
  }
}
</style>