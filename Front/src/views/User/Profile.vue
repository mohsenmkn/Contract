<!-- resources/js/views/User/Profile.vue -->
<template>
  <div class="profile-container">

    <!-- ═══════════════════════════════════════════
         HEADER - Glassmorphism
         ═══════════════════════════════════════════ -->
    <div class="profile-header">
      <div class="header-blob blob-1"></div>
      <div class="header-blob blob-2"></div>
      <div class="header-blob blob-3"></div>

      <div class="header-content">
        <div class="avatar-section">
          <div class="avatar-wrapper">
            <img
                v-if="profile.avatar"
                :src="avatarUrl"
                :alt="profile.name"
                class="avatar-img"
            />
            <div v-else class="avatar-placeholder">
              <span class="avatar-text">{{ userInitials }}</span>
            </div>
            <button class="avatar-edit-btn" @click="triggerFileInput" title="تغییر عکس">
              <Camera class="avatar-edit-icon" />
            </button>
            <input
                ref="fileInput"
                type="file"
                accept="image/*"
                class="hidden"
                @change="handleAvatarUpload"
            />
          </div>
          <div class="avatar-info">
            <h1 class="user-name">{{ profile.name || auth.displayName }}</h1>
            <p class="user-position">{{ profile.post?.title || profile.position || 'کاربر سیستم' }}</p>
            <div class="user-department" v-if="profile.department">
              <Building2 class="dept-icon" />
              <span>{{ profile.department }}</span>
            </div>
          </div>
        </div>

        <div class="header-stats">
          <div class="stat-item">
            <div class="stat-value">{{ profile.login_count || 0 }}</div>
            <div class="stat-label">تعداد ورود</div>
          </div>
          <div class="stat-divider"></div>
          <div class="stat-item">
            <div class="stat-value">{{ profile.days_since_join || 0 }}</div>
            <div class="stat-label">روز عضویت</div>
          </div>
          <div class="stat-divider"></div>
          <div class="stat-item">
            <div class="stat-value">{{ roles.length }}</div>
            <div class="stat-label">نقش فعال</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════
         TABS
         ═══════════════════════════════════════════ -->
    <div class="profile-content">
      <TabView class="profile-tabs">

        <!-- ─── تب 1: اطلاعات شخصی (فقط خواندنی) ─── -->
        <TabPanel>
          <template #header>
            <User class="tab-icon" />
            <span>اطلاعات شخصی</span>
          </template>

          <div class="tab-content">
            <div class="info-banner">
              <Info class="info-icon" />
              <span>این اطلاعات از سیستم منابع انسانی خوانده می‌شود و غیرقابل ویرایش است. برای تغییر با واحد IT تماس بگیرید.</span>
            </div>

            <div class="info-grid">
              <div class="info-card">
                <div class="info-label">
                  <User class="info-label-icon" />
                  نام و نام خانوادگی
                </div>
                <div class="info-value">{{ profile.name || '-' }}</div>
              </div>

              <div class="info-card">
                <div class="info-label">
                  <Mail class="info-label-icon" />
                  ایمیل
                </div>
                <div class="info-value" dir="ltr">{{ profile.email || '-' }}</div>
              </div>

              <div class="info-card">
                <div class="info-label">
                  <Phone class="info-label-icon" />
                  شماره موبایل
                </div>
                <div class="info-value" dir="ltr">{{ profile.mobile || '-' }}</div>
              </div>

              <div class="info-card">
                <div class="info-label">
                  <Hash class="info-label-icon" />
                  کد پرسنلی
                </div>
                <div class="info-value" dir="ltr">{{ profile.personnel_code || '-' }}</div>
              </div>

              <div class="info-card">
                <div class="info-label">
                  <CreditCard class="info-label-icon" />
                  کد ملی
                </div>
                <div class="info-value" dir="ltr">{{ profile.national_code || '-' }}</div>
              </div>

              <div class="info-card">
                <div class="info-label">
                  <Briefcase class="info-label-icon" />
                  سمت شغلی
                </div>
                <div class="info-value">{{ profile.post?.title || '-' }}</div>
              </div>

              <div class="info-card">
                <div class="info-label">
                  <Award class="info-label-icon" />
                  عنوان شغلی
                </div>
                <div class="info-value">{{ profile.job?.title || '-' }}</div>
              </div>

              <div class="info-card">
                <div class="info-label">
                  <Hash class="info-label-icon" />
                  شماره استخدام
                </div>
                <div class="info-value" dir="ltr">{{ profile.employment_number || '-' }}</div>
              </div>
            </div>
          </div>
        </TabPanel>

        <!-- ─── تب 2: امنیت (بدون 2FA) ── -->
        <TabPanel>
          <template #header>
            <ShieldCheck class="tab-icon" />
            <span>امنیت</span>
          </template>

          <div class="tab-content">
            <div class="security-section">
              <h3 class="section-title">
                <Lock class="section-icon" />
                تغییر رمز عبور
              </h3>
              <form @submit.prevent="changePassword" class="password-form">
                <div class="form-group">
                  <label class="form-label">رمز عبور فعلی</label>
                  <Password
                      v-model="passwordForm.current"
                      :feedback="false"
                      toggleMask
                      class="form-input"
                      inputClass="w-full"
                      placeholder="••••••••"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">رمز عبور جدید</label>
                  <Password
                      v-model="passwordForm.new_password"
                      toggleMask
                      class="form-input"
                      inputClass="w-full"
                      placeholder="حداقل 6 کاراکتر"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">تکرار رمز عبور جدید</label>
                  <Password
                      v-model="passwordForm.confirm"
                      :feedback="false"
                      toggleMask
                      class="form-input"
                      inputClass="w-full"
                      placeholder="••••••••"
                  />
                </div>
                <Button
                    type="submit"
                    label="تغییر رمز عبور"
                    icon="pi pi-lock"
                    :loading="changingPassword"
                    class="save-btn"
                />
              </form>
            </div>
          </div>
        </TabPanel>

        <!-- ─── تب 3: فعالیت‌ها (از بک‌اند) ─── -->
        <TabPanel>
          <template #header>
            <Activity class="tab-icon" />
            <span>فعالیت‌ها</span>
          </template>

          <div class="tab-content">
            <div class="activity-section">
              <h3 class="section-title">
                <Clock class="section-icon" />
                آخرین ورودها به سیستم
              </h3>

              <div v-if="loadingActivities" class="loading-state">
                <ProgressSpinner strokeWidth="3" />
                <p>در حال بارگذاری...</p>
              </div>

              <div v-else-if="recentLogins.length === 0" class="empty-state">
                <History class="empty-icon" />
                <p>سابقه‌ای یافت نشد</p>
              </div>

              <div v-else class="activity-list">
                <div
                    v-for="(login, index) in recentLogins"
                    :key="login.id || index"
                    class="activity-item"
                    :class="{ 'is-current': login.is_current }"
                >
                  <div class="activity-icon-wrapper">
                    <component :is="getDeviceIcon(login.device_type)" class="activity-icon" />
                  </div>
                  <div class="activity-info">
                    <div class="activity-title">
                      ورود به سیستم
                      <Tag
                          v-if="login.is_current"
                          value="نشست فعلی"
                          severity="success"
                          class="current-tag"
                      />
                    </div>
                    <div class="activity-meta">
                      <span class="activity-ip" dir="ltr">{{ login.browser }} - {{ login.os }}</span>
                      <span class="activity-separator">•</span>
                      <span class="activity-browser" dir="ltr">{{ login.ip }}</span>
                    </div>
                  </div>
                  <div class="activity-time">{{ login.time }}</div>
                </div>
              </div>
            </div>
          </div>
        </TabPanel>

        <!-- ─── تب 4: دستگاه‌ها (از بک‌اند) ─── -->
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

              <div v-if="loadingSessions" class="loading-state">
                <ProgressSpinner strokeWidth="3" />
                <p>در حال بارگذاری...</p>
              </div>

              <div v-else-if="sessions.length === 0" class="empty-state">
                <Monitor class="empty-icon" />
                <p>دستگاه فعالی یافت نشد</p>
              </div>

              <div v-else class="devices-list">
                <div
                    v-for="device in sessions"
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { useAuthStore } from '@/stores/auth.js';
import api from '@/api/axios.js';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import ProgressSpinner from 'primevue/progressspinner';
import {
  User, Mail, Phone, Hash, CreditCard, Briefcase, Award,
  Camera, Building2, ShieldCheck, Lock, Monitor, Smartphone,
  Activity, Clock, History, Info,
  Monitor as MonitorIcon, Smartphone as PhoneIcon, Tablet, Laptop,
} from 'lucide-vue-next';

const toast = useToast();
const confirm = useConfirm();
const auth = useAuthStore();

const loading = ref(true);
const loadingActivities = ref(false);
const loadingSessions = ref(false);
const changingPassword = ref(false);
const loggingOutAll = ref(false);
const fileInput = ref(null);

const profile = ref({});
const roles = ref([]);
const recentLogins = ref([]);
const sessions = ref([]);

const passwordForm = ref({
  current: '',
  new_password: '',
  confirm: '',
});

const userInitials = computed(() => {
  const name = profile.value.name || auth.displayName || '';
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
  return name.substring(0, 2).toUpperCase() || '?';
});

const avatarUrl = computed(() => {
  if (!profile.value.avatar) return null;
  const base = import.meta.env.VITE_API_BASE_URL || '';
  return `${base}/storage/${profile.value.avatar}`;
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

const fetchProfile = async () => {
  loading.value = true;
  loadingActivities.value = true;
  loadingSessions.value = true;

  try {
    const { data } = await api.get('/auth/profile');
    profile.value = data.data || {};
    roles.value = data.roles || [];
    recentLogins.value = data.recent_logins || [];
    sessions.value = data.sessions || [];
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'خطا',
      detail: 'خطا در دریافت اطلاعات پروفایل',
      life: 3000,
    });
  } finally {
    loading.value = false;
    loadingActivities.value = false;
    loadingSessions.value = false;
  }
};

const changePassword = async () => {
  if (passwordForm.value.new_password !== passwordForm.value.confirm) {
    toast.add({
      severity: 'warn',
      summary: 'هشدار',
      detail: 'رمز عبور و تکرار آن یکسان نیستند',
      life: 3000,
    });
    return;
  }

  if (passwordForm.value.new_password.length < 6) {
    toast.add({
      severity: 'warn',
      summary: 'هشدار',
      detail: 'رمز عبور باید حداقل 6 کاراکتر باشد',
      life: 3000,
    });
    return;
  }

  changingPassword.value = true;
  try {
    await api.put('/auth/password', {
      current_password: passwordForm.value.current,
      password: passwordForm.value.new_password,
      password_confirmation: passwordForm.value.confirm,
    });
    passwordForm.value = { current: '', new_password: '', confirm: '' };
    toast.add({
      severity: 'success',
      summary: 'موفق',
      detail: 'رمز عبور با موفقیت تغییر کرد',
      life: 3000,
    });
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'خطا',
      detail: error.response?.data?.message || 'خطا در تغییر رمز',
      life: 3000,
    });
  } finally {
    changingPassword.value = false;
  }
};

const triggerFileInput = () => {
  fileInput.value?.click();
};

const handleAvatarUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('avatar', file);

  try {
    const { data } = await api.post('/auth/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    profile.value.avatar = data.avatar;
    toast.add({
      severity: 'success',
      summary: 'موفق',
      detail: 'عکس پروفایل بروزرسانی شد',
      life: 3000,
    });
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'خطا',
      detail: 'خطا در آپلود عکس',
      life: 3000,
    });
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
        sessions.value = sessions.value.filter(d => d.id !== device.id);
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
        sessions.value = sessions.value.filter(d => d.is_current);
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

onMounted(() => {
  fetchProfile();
});
</script>

<style scoped>
.profile-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1.5rem;
}

/* ═══════════════════════════════════════════════════════
   HEADER - Glassmorphism
   ═══════════════════════════════════════════════════════ */

.profile-header {
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
  background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.blob-2 {
  bottom: -80px;
  left: -50px;
  width: 250px;
  height: 250px;
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.blob-3 {
  top: 50%;
  left: 40%;
  width: 150px;
  height: 150px;
  background: linear-gradient(135deg, #ec4899, #db2777);
  opacity: 0.3;
}

.header-content {
  position: relative;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
  flex-wrap: wrap;
}

.avatar-section {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex: 1;
  min-width: 0;
}

.avatar-wrapper {
  position: relative;
  flex: 0 0 auto;
}

.avatar-img,
.avatar-placeholder {
  width: 100px;
  height: 100px;
  border-radius: 20px;
  object-fit: cover;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.avatar-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
  font-size: 2rem;
  font-weight: 700;
}

.avatar-edit-btn {
  position: absolute;
  bottom: -8px;
  left: -8px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: white;
  border: 2px solid #3b82f6;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  transition: all 0.2s;
}

.avatar-edit-btn:hover {
  transform: scale(1.1);
  background: #3b82f6;
}

.avatar-edit-btn:hover .avatar-edit-icon {
  color: white;
}

.avatar-edit-icon {
  width: 18px;
  height: 18px;
  color: #3b82f6;
  transition: color 0.2s;
}

.hidden { display: none; }

.avatar-info { flex: 1; min-width: 0; }

.user-name {
  margin: 0 0 0.25rem;
  font-size: 1.75rem;
  font-weight: 700;
  color: #1e293b;
}

.user-position {
  margin: 0 0 0.5rem;
  font-size: 0.95rem;
  color: #64748b;
}

.user-department {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.4rem 0.9rem;
  background: rgba(59, 130, 246, 0.1);
  border-radius: 999px;
  font-size: 0.85rem;
  color: #2563eb;
  font-weight: 500;
}

.dept-icon { width: 14px; height: 14px; }

.header-stats {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1rem 1.5rem;
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.5);
}

.stat-item { text-align: center; }

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.25rem;
}

.stat-label {
  font-size: 0.8rem;
  color: #64748b;
}

.stat-divider {
  width: 1px;
  height: 40px;
  background: rgba(148, 163, 184, 0.3);
}

/* ═══════════════════════════════════════════════════════
   TABS
   ══════════════════════════════════════════════════════ */

.profile-content {
  background: white;
  border-radius: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

.profile-tabs :deep(.p-tabview-nav) {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  padding: 0 1rem;
}

.profile-tabs :deep(.p-tabview-nav-link) {
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

.profile-tabs :deep(.p-tabview-nav-link:hover) {
  background: rgba(59, 130, 246, 0.05);
  color: #3b82f6;
}

.profile-tabs :deep(.p-tabview-nav-link.p-highlight) {
  background: white;
  color: #3b82f6;
  box-shadow: 0 -2px 8px rgba(59, 130, 246, 0.1);
}

.tab-icon { width: 18px; height: 18px; }

.tab-content { padding: 2rem; }

/* ══════════════════════════════════════════════════════
   INFO BANNER (Read-only notice)
   ═══════════════════════════════════════════════════════ */

.info-banner {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(139, 92, 246, 0.08));
  border: 1px solid rgba(59, 130, 246, 0.2);
  border-radius: 12px;
  margin-bottom: 1.5rem;
  color: #1e40af;
  font-size: 0.9rem;
}

.info-icon {
  width: 20px;
  height: 20px;
  flex: 0 0 20px;
  color: #3b82f6;
}

/* ═══════════════════════════════════════════════════════
   INFO GRID (Read-only cards)
   ═══════════════════════════════════════════════════════ */

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
}

.info-card {
  padding: 1.25rem;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  transition: all 0.2s;
}

.info-card:hover {
  background: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
  transform: translateY(-2px);
}

.info-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 0.5rem;
}

.info-label-icon {
  width: 16px;
  height: 16px;
  color: #3b82f6;
}

.info-value {
  font-size: 1.05rem;
  font-weight: 600;
  color: #1e293b;
  word-break: break-word;
}

/* ═══════════════════════════════════════════════════════
   SECURITY
   ═══════════════════════════════════════════════════════ */

.security-section { margin-bottom: 2rem; }

.section-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.1rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 1.5rem;
}

.section-icon {
  width: 20px;
  height: 20px;
  color: #3b82f6;
}

.password-form {
  max-width: 500px;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #475569;
}

.form-input { width: 100%; }

.save-btn { min-width: 180px; align-self: flex-start; margin-top: 0.5rem; }

/* ═══════════════════════════════════════════════════════
   ACTIVITY & DEVICES
   ═══════════════════════════════════════════════════════ */

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

.empty-icon {
  width: 48px;
  height: 48px;
  opacity: 0.4;
}

.activity-list,
.devices-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.activity-item,
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

.activity-item:hover,
.device-item:hover {
  background: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.activity-item.is-current,
.device-item.is-current {
  background: rgba(34, 197, 94, 0.05);
  border-color: rgba(34, 197, 94, 0.2);
}

.activity-icon-wrapper,
.device-icon-wrapper {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border-radius: 12px;
  flex: 0 0 auto;
}

.activity-icon,
.device-icon {
  width: 22px;
  height: 22px;
  color: white;
}

.activity-info,
.device-info {
  flex: 1;
  min-width: 0;
}

.activity-title,
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

.activity-meta,
.device-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}

.activity-separator,
.device-separator { color: #cbd5e1; }

.activity-time,
.device-time {
  font-size: 0.8rem;
  color: #94a3b8;
}

/* ═══════════════════════════════════════════════════════
   LOGOUT ALL
   ═══════════════════════════════════════════════════════ */

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

/* ═══════════════════════════════════════════════════════
   RESPONSIVE
   ═══════════════════════════════════════════════════════ */

@media (max-width: 768px) {
  .profile-container { padding: 1rem; }
  .profile-header { padding: 1.5rem; }

  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .avatar-section { width: 100%; }

  .header-stats {
    width: 100%;
    justify-content: space-around;
  }

  .tab-content { padding: 1.5rem 1rem; }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .logout-all-section {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>