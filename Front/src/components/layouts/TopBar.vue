<template>
  <header class="flex items-center justify-between gap-4 border-b bg-white px-5 py-3" dir="rtl">
    <div class="flex items-center gap-3">
      <button type="button" class="lg:hidden p-2" aria-label="باز کردن منو" @click="$emit('open-sidebar')"><Menu :size="22" /></button>
      <h1 class="font-bold text-slate-800">{{ route.meta.title || 'سامانه سازمانی' }}</h1>
    </div>
    <UserDropdown @profile="router.push('/profile')" @settings="router.push('/settings')" @logout="logout" />
  </header>
</template>
<script setup>
import { useRoute, useRouter } from 'vue-router'
import { Menu } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import UserDropdown from './topbar/UserDropdown.vue'
defineEmits(['open-sidebar'])
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
async function logout() {
  await auth.logout()
  await router.push({ name: 'login' })
}
</script>
