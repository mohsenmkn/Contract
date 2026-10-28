// src/api/organization/unitService.js

import api from '@/api/axios'

export const unitService = {
    // لیست واحدها
    getAll: (params = {}) => {
        return api.get('/organizational-units', { params })
            .then(res => res.data)
    },

    // دریافت یک واحد
    getById: (id) => {
        return api.get(`/organizational-units/${id}`)
            .then(res => res.data)
    },

    // ایجاد واحد
    create: (data) => {
        return api.post('/organizational-units', data)
            .then(res => res.data)
    },

    // بروزرسانی واحد
    update: (id, data) => {
        return api.put(`/organizational-units/${id}`, data)
            .then(res => res.data)
    },

    // حذف واحد
    delete: (id) => {
        return api.delete(`/organizational-units/${id}`)
            .then(res => res.data)
    },

    // دریافت نماینده یک واحد
    getRepresentative: (unitId) => {
        return api.get(`/organizational-units/${unitId}/representative`)
            .then(res => res.data)
    },

    // تعیین/بروزرسانی نماینده
    setRepresentative: (unitId, userId) => {
        return api.post(`/organizational-units/${unitId}/representative`, { user_id: userId })
            .then(res => res.data)
    },

    // حذف نماینده
    removeRepresentative: (unitId) => {
        return api.delete(`/organizational-units/${unitId}/representative`)
            .then(res => res.data)
    },

    // دریافت واحد کاربر فعلی
    getMyUnit: () => {
        return api.get('/organizational-units/my-unit')
            .then(res => res.data)
    },
}