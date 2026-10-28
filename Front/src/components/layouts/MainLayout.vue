<template>
  <div class="min-h-screen bg-gray-50 flex" dir="rtl">

    <!-- Sidebar -->
    <aside class="w-64 bg-white border-l border-gray-200 shadow-sm flex flex-col transition-all">
      <div class="h-16 flex items-center justify-center border-b border-gray-100">
        <span class="text-xl font-bold text-primary-600">پنل مدیریت</span>
      </div>
      <nav class="flex-1 p-4">
        <!-- لینک‌های سایدبار اینجا قرار می‌گیرند -->
        <router-link to="/" class="block p-3 rounded-lg hover:bg-gray-50 text-gray-700 mb-2 transition-colors">
          داشبورد
        </router-link>
        <!--User Manageer-->
        <router-link to="/users" class="block p-2 hover:bg-gray-200 rounded">مدیریت کاربران</router-link>

      </nav>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col">
      <!-- Header -->
      <header class="h-16 bg-white border-b border-gray-200 shadow-sm flex items-center justify-between px-6">
        <div class="text-gray-500 cursor-pointer">
          <!-- دکمه باز و بسته کردن سایدبار (اختیاری) -->
          <i class="pi pi-bars text-xl"></i>
        </div>

        <div class="flex items-center gap-4">
          <Button
              label="خروج"
              icon="pi pi-sign-out"
              severity="danger"
              text
              @click="handleLogout"
          />
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto">
        <RouterView />
      </main>
    </div>

  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import Button from 'primevue/button'


const router = useRouter()
const authStore = useAuthStore()

const handleLogout = async () => {
  await authStore.logout()
  router.push({ name: 'login' })
}

</script>
