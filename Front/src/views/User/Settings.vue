<!-- resources/js/views/User/Settings.vue -->
<template>
  <div class="settings-container">

    <!-- ═══════════════════════════════════════════════
         HEADER
         ═══════════════════════════════════════════════ -->
    <div class="settings-header">
      <div class="header-blob blob-1"></div>
      <div class="header-blob blob-2"></div>

      <div class="header-content">
        <div class="header-icon-wrapper">
          <Settings class="header-icon" />
        </div>
        <div class="header-text">
          <h1 class="page-title">تنظیمات</h1>
          <p class="page-subtitle">مدیریت تنظیمات حساب کاربری و سامانه</p>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════
         TABS (فقط 3 تب: عمومی، اعلان‌ها، دستگاه‌ها)
         ═══════════════════════════════════════════════ -->
    <div class="settings-content">
      <TabView class="settings-tabs">

        <!-- ── تب 1: عمومی ─── -->
        <TabPanel>
          <template #header>
            <SlidersHorizontal class="tab-icon" />
            <span>عمومی</span>
          </template>

          <div class="tab-content">
            <div class="settings-section">
              <h3 class="section-title">
                <Globe class="section-icon" />
                زبان و منطقه
              </h3>
              <div class="setting-row">
                <div class="setting-info">
                  <div class="setting-label">زبان رابط کاربری</div>
                  <div class="setting-desc">زبان نمایشی سامانه را انتخاب کنید</div>
                </div>
                <Select
                    v-model="generalSettings.language"
                    :options="languageOptions"
                    optionLabel="label"
                    optionValue="value"
                    class="setting-select"
                />
              </div>
              <div class="setting-row">
                <div class="setting-info">
                  <div class="setting-label">منطقه زمانی</div>
                  <div class="setting-desc">منطقه زمانی پیش‌فرض برای نمایش تاریخ و ساعت</div>
                </div>
                <Select
                    v-model="generalSettings.timezone"
                    :options="timezoneOptions"
                    optionLabel="label"
                    optionValue="value"
                    class="setting-select"
                />
              </div>
            </div>

            <Divider />

            <div class="settings-section">
              <h3 class="section-title">
                <Palette class="section-icon" />
                ظاهر
              </h3>
              <div class="setting-row">
                <div class="setting-info">
                  <div class="setting-label">حالت تاریک</div>
                  <div class="setting-desc">استفاده از تم تاریک برای کاهش خستگی چشم</div>
                </div>
                <ToggleSwitch v-model="generalSettings.darkMode" />
              </div>
              <div class="setting-row">
                <div class="setting-info">
                  <div class="setting-label">انیمیشن‌ها</div>
                  <div class="setting-desc">نمایش انیمیشن‌ها و افکت‌های حرکتی</div>
                </div>
                <ToggleSwitch v-model="generalSettings.animations" />
              </div>
              <div class="setting-row">
                <div class="setting-info">
                  <div class="setting-label">تراکم رابط</div>
                  <div class="setting-desc">میزان فشردگی عناصر رابط کاربری</div>
                </div>
                <SelectButton
                    v-model="generalSettings.density"
                    :options="densityOptions"
                    class="density-selector"
                />
              </div>
            </div>
          </div>
        </TabPanel>

        <!-- ─── تب 2: اعلان‌ها ─── -->
        <TabPanel>
          <template #header>
            <Bell class="tab-icon" />
            <span>اعلان‌ها</span>
          </template>

          <div class="tab-content">
            <div class="settings-section">
              <h3 class="section-title">
                <Mail class="section-icon" />
                اعلان‌های ایمیلی
              </h3>
              <div class="setting-row">
                <div class="setting-info">
                  <div class="setting-label">اعلان‌های سیستمی</div>
                  <div class="setting-desc">دریافت ایمیل برای رویدادهای مهم سیستم</div>
                </div>
                <ToggleSwitch v-model="notificationSettings.email.system" />
              </div>
              <div class="setting-row">
                <div class="setting-info">
                  <div class="setting-label">اعلان‌های فیش حقوقی</div>
                  <div class="setting-desc">اطلاع‌رسانی هنگام صدور فیش حقوقی جدید</div>
                </div>
                <ToggleSwitch v-model="notificationSettings.email.payslip" />
              </div>
              <div class="setting-row">
                <div class="setting-info">
                  <div class="setting-label">اعلان‌های کتابخانه</div>
                  <div class="setting-desc">اطلاع‌رسانی درباره رزرو کتاب‌ها</div>
                </div>
                <ToggleSwitch v-model="notificationSettings.email.library" />
              </div>
            </div>

            <Divider />

            <div class="settings-section">
              <h3 class="section-title">
                <Smartphone class="section-icon" />
                اعلان‌های پوش
              </h3>
              <div class="setting-row">
                <div class="setting-info">
                  <div class="setting-label">اعلان‌های فوری</div>
                  <div class="setting-desc">دریافت اعلان‌های فوری در مرورگر</div>
                </div>
                <ToggleSwitch v-model="notificationSettings.push.enabled" />
              </div>
              <div class="setting-row">
                <div class="setting-info">
                  <div class="setting-label">صدا</div>
                  <div class="setting-desc">پخش صدا هنگام دریافت اعلان جدید</div>
                </div>
                <ToggleSwitch v-model="notificationSettings.push.sound" />
              </div>
            </div>
          </div>
        </TabPanel>

        <!-- ── تب 3: دستگاه‌ها (از بک‌اند) ─── -->
        <TabPanel>
          <template #header>
            <Monitor class="tab-icon" />
            <span>دستگاه‌ها</span>
          </template>

          <div class="tab-content">
            <div class="devices-section">
              <h3 class="section-title">
                <Smartphone class="section-icon" />
                دستگاه‌های فعال
              </h3>

              <div v-if="loadingDevices" class="loading-state">
                <ProgressSpinner strokeWidth="3" />
                <p>در حال بارگذاری...</p>
              </div>

              <div v-else-if="devices.length === 0" class="empty-state">
                <Monitor class="empty-icon" />
                <p>دستگاه فعالی یافت نشد</p>
              </div>

              <div v-else class="devices-list">
                <div
                    v-for="device in devices"
                    :key="device.id"
                    class="device-item"
                    :class="{ 'is-current': device.is_current }"
                >
                  <div class="device-icon-wrapper">
                    <component :is="getDeviceIcon(device.device_type)" class="device-icon" />
                  </div>
                  <div class="device-info">
                    <div class="device-name">
                      {{ device.browser }} - {{ device.os }}
                      <Tag
                          v-if="device.is_current"
                          value="این دستگاه"
                          severity="success"
                          class="current-tag"
                      />
                    </div>
                    <div class="device-meta">
                      <span class="device-ip" dir="ltr">{{ device.ip }}</span>
                      <span class="device-separator">•</span>
                      <span class="device-location">{{ device.location }}</span>
                    </div>
                    <div class="device-time">
                      آخرین فعالیت: {{ device.last_used_at }}
                    </div>
                  </div>
                  <Button
                      v-if="!device.is_current"
                      icon="pi pi-times"
                      severity="danger"
                      text
                      rounded
                      @click="revokeDevice(device)"
                      :loading="device.revoking"
                      title="بستن نشست"
                  />
                </div>
              </div>

              <Divider />

              <div class="logout-all-section">
                <div class="logout-all-info">
                  <div class="logout-all-label">خروج از تمام دستگاه‌های دیگر</div>
                  <div class="logout-all-desc">
                    تمام نشست‌های فعال شما در دستگاه‌های دیگر بسته می‌شوند
                  </div>
                </div>
                <Button
                    label="خروج از همه"
                    icon="pi pi-sign-out"
                    severity="danger"
                    outlined
                    @click="confirmLogoutAll"
                    :loading="loggingOutAll"
                />
              </div>
            </div>
          </div>
        </TabPanel>

      </TabView>
    </div>

    <!-- ═══════════════════════════════════════════════
         SAVE BAR
         ═══════════════════════════════════════════════ -->
    <div class="save-bar" v-if="hasChanges">
      <span class="save-bar-text">تغییرات ذخیره نشده دارید</span>
      <div class="save-bar-actions">
        <Button
            label="انصراف"
            icon="pi pi-times"
            severity="secondary"
            text
            @click="resetSettings"
        />
        <Button
            label="ذخیره تغییرات"
            icon="pi pi-check"
            @click="saveSettings"
            :loading="saving"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { useAuthStore } from '@/stores/auth.js';
import api from '@/api/axios.js';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import SelectButton from 'primevue/selectbutton';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import ProgressSpinner from 'primevue/progressspinner';
import {
  Settings, SlidersHorizontal, Bell, Monitor,
  Globe, Palette, Mail, Smartphone,
  Monitor as MonitorIcon, Smartphone as PhoneIcon,
  Tablet, Laptop,
} from 'lucide-vue-next';

const toast = useToast();
const confirm = useConfirm();
const auth = useAuthStore();

const saving = ref(false);
const loggingOutAll = ref(false);
const loadingDevices = ref(false);

const generalSettings = ref({
  language: 'fa',
  timezone: 'Asia/Tehran',
  darkMode: false,
  animations: true,
  density: 'comfortable',
});

const notificationSettings = ref({
  email: {
    system: true,
    payslip: true,
    library: true,
  },
  push: {
    enabled: false,
    sound: true,
  },
});

const devices = ref([]);

const languageOptions = [
  { label: 'فارسی', value: 'fa' },
  { label: 'English', value: 'en' },
  { label: 'العربية', value: 'ar' },
];

const timezoneOptions = [
  { label: 'تهران (UTC+3:30)', value: 'Asia/Tehran' },
  { label: 'دبی (UTC+4)', value: 'Asia/Dubai' },
  { label: 'استانبول (UTC+3)', value: 'Europe/Istanbul' },
];

const densityOptions = ['compact', 'comfortable', 'spacious'];

const originalSettings = ref(null);

const hasChanges = computed(() => {
  if (!originalSettings.value) return false;
  return JSON.stringify({
    general: generalSettings.value,
    notification: notificationSettings.value,
  }) !== JSON.stringify(originalSettings.value);
});

const getDeviceIcon = (type) => {
  const icons = {
    desktop: MonitorIcon,
    laptop: Laptop,
    tablet: Tablet,
    mobile: PhoneIcon,
  };
  return icons[type] || MonitorIcon;
};

const fetchSettings = async () => {
  loadingDevices.value = true;
  try {
    const { data } = await api.get('/auth/settings');
    const settings = data.data || {};

    if (settings.general) generalSettings.value = { ...generalSettings.value, ...settings.general };
    if (settings.notifications) notificationSettings.value = { ...notificationSettings.value, ...settings.notifications };
    if (data.sessions) devices.value = data.sessions;

    originalSettings.value = {
      general: { ...generalSettings.value },
      notification: { ...notificationSettings.value },
    };
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'خطا',
      detail: 'خطا در دریافت تنظیمات',
      life: 3000,
    });
  } finally {
    loadingDevices.value = false;
  }
};

const saveSettings = async () => {
  saving.value = true;
  try {
    await api.put('/auth/settings', {
      general: generalSettings.value,
      notifications: notificationSettings.value,
    });

    originalSettings.value = {
      general: { ...generalSettings.value },
      notification: { ...notificationSettings.value },
    };

    document.documentElement.classList.toggle('dark', generalSettings.value.darkMode);

    toast.add({
      severity: 'success',
      summary: 'موفق',
      detail: 'تنظیمات با موفقیت ذخیره شد',
      life: 3000,
    });
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'خطا',
      detail: 'خطا در ذخیره تنظیمات',
      life: 3000,
    });
  } finally {
    saving.value = false;
  }
};

const resetSettings = () => {
  if (originalSettings.value) {
    generalSettings.value = { ...originalSettings.value.general };
    notificationSettings.value = { ...originalSettings.value.notification };
  }
};

const revokeDevice = (device) => {
  confirm.require({
    message: `آیا از بستن نشست "${device.browser} - ${device.os}" مطمئن هستید؟`,
    header: 'بستن نشست',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'بله، ببند',
    rejectLabel: 'انصراف',
    acceptClass: 'p-button-danger',
    accept: async () => {
      device.revoking = true;
      try {
        await api.delete(`/auth/sessions/${device.id}`);
        devices.value = devices.value.filter(d => d.id !== device.id);
        toast.add({
          severity: 'success',
          summary: 'موفق',
          detail: 'نشست بسته شد',
          life: 3000,
        });
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'خطا',
          detail: error.response?.data?.message || 'خطا در بستن نشست',
          life: 3000,
        });
      } finally {
        device.revoking = false;
      }
    },
  });
};

const confirmLogoutAll = () => {
  confirm.require({
    message: 'آیا از خروج از تمام دستگاه‌های دیگر مطمئن هستید؟',
    header: 'خروج از همه دستگاه‌ها',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'بله، خارج شو',
    rejectLabel: 'انصراف',
    acceptClass: 'p-button-danger',
    accept: async () => {
      loggingOutAll.value = true;
      try {
        await api.post('/auth/logout-all');
        devices.value = devices.value.filter(d => d.is_current);
        toast.add({
          severity: 'success',
          summary: 'موفق',
          detail: 'از تمام دستگاه‌های دیگر خارج شدید',
          life: 3000,
        });
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'خطا',
          detail: 'خطا در خروج از دستگاه‌ها',
          life: 3000,
        });
      } finally {
        loggingOutAll.value = false;
      }
    },
  });
};

watch(() => generalSettings.value.darkMode, (val) => {
  document.documentElement.classList.toggle('dark', val);
});

onMounted(() => {
  fetchSettings();
});
</script>

<style scoped>
/* همان استایل‌های قبلی Settings.vue - فقط تب حریم خصوصی حذف شده */
.settings-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 1.5rem;
  padding-bottom: 100px;
}

.settings-header {
  position: relative;
  overflow: hidden;
  border-radius: 20px;
  padding: 2rem;
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
  margin-bottom: 1.5rem;
}

.header-blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  opacity: 0.5;
}

.blob-1 {
  top: -50px;
  right: -50px;
  width: 200px;
  height: 200px;
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.blob-2 {
  bottom: -80px;
  left: -50px;
  width: 250px;
  height: 250px;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.header-content {
  position: relative;
  z-index: 10;
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.header-icon-wrapper {
  width: 64px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  border-radius: 16px;
  box-shadow: 0 8px 24px rgba(139, 92, 246, 0.3);
  flex: 0 0 auto;
}

.header-icon { width: 32px; height: 32px; color: white; }

.header-text { flex: 1; }

.page-title {
  margin: 0 0 0.25rem;
  font-size: 1.75rem;
  font-weight: 700;
  color: #1e293b;
}

.page-subtitle {
  margin: 0;
  font-size: 0.95rem;
  color: #64748b;
}

.settings-content {
  background: white;
  border-radius: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

.settings-tabs :deep(.p-tabview-nav) {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  padding: 0 1rem;
}

.settings-tabs :deep(.p-tabview-nav-link) {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem 1.5rem;
  border: none;
  border-radius: 12px 12px 0 0;
  color: #64748b;
  font-weight: 500;
  transition: all 0.2s;
}

.settings-tabs :deep(.p-tabview-nav-link:hover) {
  background: rgba(139, 92, 246, 0.05);
  color: #8b5cf6;
}

.settings-tabs :deep(.p-tabview-nav-link.p-highlight) {
  background: white;
  color: #8b5cf6;
  box-shadow: 0 -2px 8px rgba(139, 92, 246, 0.1);
}

.tab-icon { width: 18px; height: 18px; }
.tab-content { padding: 2rem; }

.settings-section { margin-bottom: 2rem; }

.section-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.1rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 1.5rem;
}

.section-icon { width: 20px; height: 20px; color: #8b5cf6; }

.setting-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  padding: 1.25rem;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  margin-bottom: 0.75rem;
  transition: all 0.2s;
}

.setting-row:hover {
  background: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.setting-info { flex: 1; min-width: 0; }

.setting-label {
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.25rem;
  font-size: 0.95rem;
}

.setting-desc {
  font-size: 0.85rem;
  color: #64748b;
}

.setting-select { min-width: 200px; }

/* Devices */
.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 2rem;
  color: #94a3b8;
  gap: 1rem;
}

.empty-icon { width: 48px; height: 48px; opacity: 0.4; }

.devices-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.device-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  transition: all 0.2s;
}

.device-item:hover {
  background: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.device-item.is-current {
  background: rgba(34, 197, 94, 0.05);
  border-color: rgba(34, 197, 94, 0.2);
}

.device-icon-wrapper {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  border-radius: 12px;
  flex: 0 0 auto;
}

.device-icon { width: 24px; height: 24px; color: white; }

.device-info { flex: 1; min-width: 0; }

.device-name {
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.current-tag { font-size: 0.7rem; }

.device-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}

.device-separator { color: #cbd5e1; }

.device-time { font-size: 0.8rem; color: #94a3b8; }

/* Logout All */
.logout-all-section {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  padding: 1.25rem;
  background: rgba(220, 38, 38, 0.03);
  border: 1px solid rgba(220, 38, 38, 0.15);
  border-radius: 12px;
}

.logout-all-info { flex: 1; }

.logout-all-label {
  font-weight: 600;
  color: #dc2626;
  margin-bottom: 0.25rem;
}

.logout-all-desc {
  font-size: 0.85rem;
  color: #64748b;
}

/* Save Bar */
.save-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 100;
  padding: 1rem 2rem;
  background: white;
  border-top: 1px solid #e2e8f0;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

.save-bar-text {
  font-weight: 600;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.save-bar-text::before {
  content: '';
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #f59e0b;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.save-bar-actions { display: flex; gap: 0.75rem; }

/* Responsive */
@media (max-width: 768px) {
  .settings-container { padding: 1rem; padding-bottom: 100px; }
  .settings-header { padding: 1.5rem; }

  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .tab-content { padding: 1.5rem 1rem; }

  .setting-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .setting-select { width: 100%; }

  .logout-all-section {
    flex-direction: column;
    align-items: flex-start;
  }

  .save-bar {
    flex-direction: column;
    padding: 1rem;
  }

  .save-bar-actions { width: 100%; justify-content: flex-end; }
}
</style>