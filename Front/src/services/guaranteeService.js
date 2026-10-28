// src/api/contract/guaranteeService.js

import api from '@/api/axios'

export const guaranteeService = {
    // لیست ضمانت‌نامه‌های یک قرارداد
    getByContract: (contractId, params = {}) => {
        return api.get(`/contracts/${contractId}/guarantees`, { params })
            .then(res => res.data)
    },

    // دریافت یک ضمانت‌نامه
    getById: (id) => {
        return api.get(`/guarantees/${id}`)
            .then(res => res.data)
    },

    // ثبت ضمانت‌نامه جدید
    create: (data) => {
        return api.post('/guarantees', data)
            .then(res => res.data)
    },

    // آزادسازی ضمانت‌نامه
    release: (id) => {
        return api.post(`/guarantees/${id}/release`)
            .then(res => res.data)
    },

    // دریافت ضمانت‌نامه‌های فعال
    getActive: (params = {}) => {
        return api.get('/guarantees', { params: { ...params, status: 'active' } })
            .then(res => res.data)
    },

    // دریافت ضمانت‌نامه‌های در حال اتمام
    getExpiringSoon: (days = 30) => {
        return api.get('/guarantees', { params: { expiring_soon: days } })
            .then(res => res.data)
    },
}