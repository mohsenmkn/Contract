<script setup>
import ContractSettings from '@/views/Contract/ContractSettings.vue'
import SignatureAssets from '@/views/Signature/SignatureAssets.vue'
import ContractAlertSettings from '@/views/Contract/ContractAlertSettings.vue'

const tabs = [
  { id: 'signatures', label: 'مدیریت امضای کاربران', icon: 'pi pi-pen-to-square' },
  { id: 'letterhead', label: 'سربرگ چاپ قراردادها', icon: 'pi pi-image' },
  { id: 'alerts', label: 'مدیریت هشدارها', icon: 'pi pi-bell' },
]
</script>

<template>
  <main class="contract-settings-page mx-auto max-w-[1500px] space-y-6 p-4 md:p-6" dir="rtl">
    <header class="settings-header">
      <div class="settings-header__icon">
        <i class="pi pi-cog" aria-hidden="true" />
      </div>
      <div class="settings-header__content">
        <h1>تنظیمات سامانه قراردادها</h1>
        <p>امضاهای کاربران و سربرگ چاپ قراردادها را از این بخش مدیریت کنید.</p>
      </div>
    </header>

    <TabView class="settings-tabs">
      <TabPanel v-for="tab in tabs" :key="tab.id">
        <template #header>
          <span class="tab-head">
            <i :class="tab.icon" aria-hidden="true" />
            <span>{{ tab.label }}</span>
          </span>
        </template>

        <SignatureAssets v-if="tab.id === 'signatures'" />
        <ContractAlertSettings v-else-if="tab.id === 'alerts'" />
        <ContractSettings
            v-else
            :section="tab.id"
            :embedded="true"
        />
      </TabPanel>
    </TabView>
  </main>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap');

.contract-settings-page {
  --accent: oklch(45% 0.15 250);
  --accent-soft: oklch(45% 0.15 250 / .10);
  --surface: oklch(99.5% 0.003 250 / .97);
  --surface-2: oklch(96% 0.008 250);
  --border: oklch(89% 0.008 250);
  --text-muted: #64748b;
  color: #172033;
  font-family: 'Vazirmatn', sans-serif;
}

.settings-header {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 20px 22px;
  border: 1px solid var(--border);
  border-radius: 18px;
  background: var(--surface);
}

.settings-header__icon {
  display: grid;
  flex: 0 0 48px;
  width: 48px;
  height: 48px;
  place-items: center;
  border-radius: 14px;
  background: var(--accent-soft);
  color: var(--accent);
  font-size: 21px;
}

.settings-header__content h1 {
  margin: 0;
  color: #172033;
  font-size: 22px;
  font-weight: 800;
  line-height: 1.7;
}

.settings-header__content p {
  margin: 3px 0 0;
  color: var(--text-muted);
  font-size: 13px;
  line-height: 1.9;
}

/* چیدمان تب‌ها هماهنگ با صفحه نمایش قرارداد */
.contract-settings-page :deep(.p-tabview) {
  padding: 0;
  border: 0;
  background: transparent;
  box-shadow: none;
}

.contract-settings-page :deep(.p-tabview-nav-container) {
  margin-bottom: 20px;
  border: 0;
}

.contract-settings-page :deep(.p-tabview-nav-content) {
  overflow-x: auto;
  scrollbar-width: none;
}

.contract-settings-page :deep(.p-tabview-nav-content::-webkit-scrollbar) {
  display: none;
}

.contract-settings-page :deep(.p-tabview-nav) {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 4px;
  padding: 5px;
  border: 1px solid var(--border);
  border-radius: 15px;
  background: var(--surface-2);
}

.contract-settings-page :deep(.p-tabview-nav li .p-tabview-nav-link) {
  padding: 10px 14px;
  border: 0;
  border-radius: 11px;
  background: transparent;
  color: var(--text-muted);
  font-family: inherit;
  font-size: 13px;
  font-weight: 600;
  transition: background .2s ease, color .2s ease, box-shadow .2s ease;
}

.contract-settings-page :deep(.p-tabview-nav li:not(.p-highlight):not([data-p-active='true']) .p-tabview-nav-link:hover) {
  background: var(--accent-soft);
  color: var(--accent);
}

.contract-settings-page :deep(.p-tabview-nav li.p-highlight .p-tabview-nav-link),
.contract-settings-page :deep(.p-tabview-nav li[data-p-active='true'] .p-tabview-nav-link) {
  background: #fff;
  color: var(--accent);
  box-shadow: 0 3px 10px oklch(45% .15 250 / .14);
}

.contract-settings-page :deep(.p-tabview-ink-bar) {
  display: none;
}

.contract-settings-page :deep(.p-tabview-panels) {
  padding: 0;
  background: transparent;
}

.tab-head {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  white-space: nowrap;
}

.tab-head .pi {
  font-size: 13px;
}

@media (max-width: 700px) {
  .settings-header {
    align-items: flex-start;
    padding: 16px;
  }

  .settings-header__content h1 {
    font-size: 18px;
  }

  .contract-settings-page :deep(.p-tabview-nav) {
    flex-wrap: nowrap;
  }

  .contract-settings-page :deep(.p-tabview-nav li .p-tabview-nav-link) {
    padding: 10px 12px;
    white-space: nowrap;
  }
}
</style>
