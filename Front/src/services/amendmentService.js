// src/api/contract/amendmentService.js

import api from '@/api/axios'

export const amendmentService = {
    // لیست الحاقیه‌های یک قرارداد
    getByContract: (contractId, params = {}) => {
        return api.get(`/contracts/${contractId}/amendments`, { params })
            .then(res => res.data)
    },

    // دریافت یک الحاقیه
    getById: (id) => {
        return api.get(`/amendments/${id}`)
            .then(res => res.data)
    },

    // ایجاد الحاقیه جدید
    create: (data) => {
        return api.post('/amendments', data)
            .then(res => res.data)
    },

    // تایید الحاقیه
    approve: (id) => {
        return api.post(`/amendments/${id}/approve`)
            .then(res => res.data)
    },

    // دریافت الحاقیه‌های در انتظار تایید
    getPending: (params = {}) => {
        return api.get('/amendments', { params: { ...params, status: 'pending' } })
            .then(res => res.data)
    },
}