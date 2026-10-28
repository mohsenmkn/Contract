// src/api/contract/alertService.js

import api from '@/api/axios'

export const alertService = {
    // دریافت هشدارهای من
    getMyAlerts: (params = {}) => {
        return api.get('/alerts', { params })
            .then(res => res.data)
    },

    // دریافت هشدارهای قراردادها
    getContractAlerts: (contractId) => {
        return api.get(`/contracts/${contractId}/alerts`)
            .then(res => res.data)
    },

    // علامت‌گذاری هشدار به عنوان خوانده شده
    markAsRead: (id) => {
        return api.post(`/alerts/${id}/read`)
            .then(res => res.data)
    },

    // دریافت تعداد هشدارهای خوانده نشده
    getUnreadCount: () => {
        return api.get('/alerts/unread-count')
            .then(res => res.data)
    },
}