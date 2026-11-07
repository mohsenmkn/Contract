<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useSignatureStore } from '@/stores/signatureStore'

const router = useRouter()
const auth = useAuthStore()
const signatures = useSignatureStore()
const roleNames = computed(() => (auth.user?.roles || []).map(role => typeof role === 'string' ? role : role.name))
const title = computed(() => roleNames.value.includes('ceo') ? 'مدیرعامل' : roleNames.value.includes('deputy_ceo') ? 'معاون مالی' : 'عضو هیئت‌مدیره')

onMounted(async () => {
  try {
    await signatures.fetchMyPendingSignatures()
  } catch {
    // The store exposes the request error for the dashboard state.
  }
})

const openSignature = (signature) => router.push({ name: 'signatures.detail', params: { id: signature.id } })
</script>

<template>
  <main class="executive-dashboard" dir="rtl">
    <header class="hero">
      <div>
        <p>سامانه مدیریت قراردادها</p>
        <h1>داشبورد {{ title }}</h1>
        <span>قراردادها و اسنادی که برای اقدام به شما ارجاع شده‌اند</span>
      </div>
      <button class="primary" @click="router.push({ name: 'signatures.index' })">مشاهده همه امضاهای در انتظار</button>
    </header>

    <section class="panel">
      <div class="panel-heading">
        <div><h2>امضاهای در انتظار شما</h2><p>برای مشاهده سند و انجام امضا روی مورد موردنظر کلیک کنید.</p></div>
        <strong class="count">{{ signatures.pendingCount }}</strong>
      </div>

      <div v-if="signatures.loading" class="state">در حال دریافت امضاهای در انتظار…</div>
      <div v-else-if="signatures.error" class="state error" role="alert">{{ signatures.error }}</div>
      <div v-else-if="!signatures.pendingSignatures.length" class="state">در حال حاضر سندی برای امضای شما در انتظار نیست.</div>
      <div v-else class="signature-list">
        <article v-for="item in signatures.pendingSignatures" :key="item.id" class="signature-row">
          <div>
            <span class="kind">{{ item.signable_type?.includes('Contract') ? 'قرارداد' : item.signable_type?.includes('Amendment') ? 'الحاقیه' : 'سند' }}</span>
            <h3>سند شماره {{ item.signable_id }}</h3>
            <p>ارجاع‌شده در {{ new Date(item.created_at).toLocaleDateString('fa-IR') }}</p>
          </div>
          <button class="sign" @click="openSignature(item)">مشاهده و امضا <span aria-hidden="true">←</span></button>
        </article>
      </div>
    </section>
  </main>
</template>

<style scoped>
.executive-dashboard{max-width:1200px;margin:auto;padding:20px;color:#172033}.hero{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:30px;border-radius:18px;background:linear-gradient(120deg,#123b5d,#176b75);color:#fff}.hero p{margin:0 0 8px;color:#bfe9e3}.hero h1{margin:0 0 8px;font-size:1.65rem}.hero span{color:#e0eff0}.primary,.sign{border:0;border-radius:9px;padding:11px 16px;font:inherit;font-weight:700;cursor:pointer;white-space:nowrap}.primary{background:white;color:#14515e}.panel{margin-top:20px;padding:22px;background:white;border:1px solid #e5eaf0;border-radius:16px;box-shadow:0 4px 18px #13243a08}.panel-heading{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px}.panel-heading h2{margin:0;font-size:1.15rem}.panel-heading p{margin:6px 0 0;color:#738095}.count{display:grid;place-items:center;min-width:42px;height:42px;border-radius:50%;background:#eaf4f4;color:#176b75}.state{text-align:center;padding:36px 12px;color:#738095}.error{color:#a52828}.signature-list{display:grid;gap:10px}.signature-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px;border:1px solid #e8edf1;border-radius:12px}.kind{font-size:.8rem;color:#176b75}.signature-row h3{margin:5px 0;font-size:1rem}.signature-row p{margin:0;color:#738095;font-size:.85rem}.sign{background:#176b75;color:white}@media(max-width:650px){.executive-dashboard{padding:8px}.hero,.signature-row{align-items:flex-start;flex-direction:column}.hero{padding:22px}.primary,.sign{width:100%}.panel{padding:16px}}
</style>
