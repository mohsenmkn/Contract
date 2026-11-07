<template>
  <header class="flex items-center justify-between gap-4 border-b bg-white px-5 py-3" dir="rtl">
    <div class="flex items-center gap-3">
      <button type="button" class="lg:hidden p-2" aria-label="باز کردن منو" @click="$emit('open-sidebar')"><Menu :size="22" /></button>
      <h1 class="font-bold text-slate-800">{{ route.meta.title || 'سامانه سازمانی' }}</h1>
    </div>
    <div class="flex items-center gap-3">
      <div class="relative">
        <button
          type="button"
          class="relative rounded-full p-2 text-slate-600 hover:bg-slate-100"
          aria-label="اعلان‌ها"
          @click="notificationsOpen = !notificationsOpen"
        >
          <Bell :size="20" />
          <span v-if="unreadCount" class="absolute -left-1 -top-1 min-w-5 rounded-full bg-red-600 px-1 text-center text-[10px] font-bold text-white">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
        </button>
        <div v-if="notificationsOpen" class="absolute left-0 top-12 z-50 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
          <div class="flex items-center justify-between border-b px-4 py-3">
            <strong class="text-sm">اعلان‌ها</strong>
            <button class="text-xs text-blue-700" @click="loadNotifications">به‌روزرسانی</button>
          </div>
          <div v-if="!notifications.length" class="p-5 text-center text-sm text-slate-500">اعلانی ندارید.</div>
          <button
            v-for="item in notifications"
            :key="item.id"
            type="button"
            class="block w-full border-b px-4 py-3 text-right hover:bg-slate-50"
            :class="{ 'bg-blue-50/60': !item.read_at }"
            @click="openNotification(item)"
          >
            <span class="block text-sm font-semibold text-slate-800">{{ item.data?.title || 'اعلان' }}</span>
            <span class="mt-1 block text-xs leading-5 text-slate-600">{{ item.data?.message }}</span>
            <span v-if="item.data?.comments" class="mt-1 block text-xs leading-5 text-red-700">دلیل: {{ item.data.comments }}</span>
          </button>
        </div>
      </div>
      <UserDropdown @profile="router.push('/profile')" @settings="router.push('/settings')" @logout="logout" />
    </div>
  </header>
</template>
<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Bell, Menu } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios.js'
import UserDropdown from './topbar/UserDropdown.vue'
defineEmits(['open-sidebar'])
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const notificationsOpen = ref(false)
const notifications = ref([])
const unreadCount = ref(0)
let notificationTimer

async function loadNotifications() {
  try {
    const response = await api.get('/notifications')
    notifications.value = response.data?.data?.items || []
    unreadCount.value = response.data?.data?.unread_count || 0
  } catch (error) {
    // Notification loading should not block navigation or the page itself.
  }
}

async function openNotification(item) {
  if (!item.read_at) {
    try {
      await api.post(`/notifications/${item.id}/read`)
      item.read_at = new Date().toISOString()
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch (error) {
      // Keep the notification visible if marking it read fails.
    }
  }
  notificationsOpen.value = false
  if (item.data?.url) await router.push(item.data.url)
}

onMounted(() => {
  loadNotifications()
  notificationTimer = window.setInterval(loadNotifications, 30000)
})

onBeforeUnmount(() => window.clearInterval(notificationTimer))
async function logout() {
  await auth.logout()
  await router.push({ name: 'login' })
}
</script>
