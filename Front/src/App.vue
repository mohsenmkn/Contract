<template>
 <router-view></router-view>

  <Toast position="top-left" />
  <ConfirmDialog />
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth.js'
import { setToastInstance } from '@/plugins/toast'
import {useToast} from "primevue/usetoast";
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

const authStore = useAuthStore()
const toast = useToast()

onMounted(async () => {
  setToastInstance(toast)


})


</script>