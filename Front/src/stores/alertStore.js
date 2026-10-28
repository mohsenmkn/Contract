// src/stores/contract/alertStore.js

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { alertService } from '@/Services/alertService'

export const useAlertStore = defineStore('alert', () => {
    // State
    const alerts = ref([])
    const unreadCount = ref(0)
    const loading = ref(false)
    const error = ref(null)

    // Getters
    const contractAlerts = computed(() =>
        alerts.value.filter(a => a.alert_type === 'expiration')
    )

    const guaranteeAlerts = computed(() =>
        alerts.value.filter(a => a.alert_type === 'guarantee')
    )

    const paymentAlerts = computed(() =>
        alerts.value.filter(a => a.alert_type === 'payment')
    )

    // Actions
    const fetchMyAlerts = async (params = {}) => {
        loading.value = true
        error.value = null

        try {
            const response = await alertService.getMyAlerts(params)
            alerts.value = response.data
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت هشدارها'
            throw err
        } finally {
            loading.value = false
        }
    }

    const fetchUnreadCount = async () => {
        try {
            const response = await alertService.getUnreadCount()
            unreadCount.value = response.data.count
            return response.data.count
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت تعداد هشدارها'
            throw err
        }
    }

    const markAsRead = async (id) => {
        try {
            await alertService.markAsRead(id)
            const index = alerts.value.findIndex(a => a.id === id)
            if (index !== -1) {
                alerts.value.splice(index, 1)
            }
            unreadCount.value = Math.max(0, unreadCount.value - 1)
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در علامت‌گذاری هشدار'
            throw err
        }
    }

    return {
        // State
        alerts,
        unreadCount,
        loading,
        error,

        // Getters
        contractAlerts,
        guaranteeAlerts,
        paymentAlerts,

        // Actions
        fetchMyAlerts,
        fetchUnreadCount,
        markAsRead,
    }
})