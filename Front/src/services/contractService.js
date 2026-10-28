
import api from '@/api/axios'

export const contractService = {
    // لیست قراردادها با فیلتر و pagination
    getAll: (params = {}) => {
        return api.get('/contracts', { params })
            .then(res => res.data)
    },

    // دریافت یک قرارداد با تمام جزئیات
    getById: (id) => {
        return api.get(`/contracts/${id}`)
            .then(res => res.data)
    },

    // ایجاد قرارداد جدید
    create: (data) => {
        return api.post('/contracts', data)
            .then(res => res.data)
    },

    // بروزرسانی قرارداد
    update: (id, data) => {
        return api.put(`/contracts/${id}`, data)
            .then(res => res.data)
    },

    // حذف قرارداد
    delete: (id) => {
        return api.delete(`/contracts/${id}`)
            .then(res => res.data)
    },

    // ارسال قرارداد برای تایید
    submit: (id) => {
        return api.post(`/contracts/${id}/submit`)
            .then(res => res.data)
    },

    // دریافت قراردادهای بالادستی
    getUpstream: (params = {}) => {
        return api.get('/contracts', { params: { ...params, type: 'upstream' } })
            .then(res => res.data)
    },

    // دریافت قراردادهای پایین‌دستی
    getDownstream: (params = {}) => {
        return api.get('/contracts', { params: { ...params, type: 'downstream' } })
            .then(res => res.data)
    },

    // دریافت قراردادهای در حال اتمام
    getExpiringSoon: (days = 30) => {
        return api.get('/contracts', { params: { expiring_soon: days } })
            .then(res => res.data)
    },

    // دریافت قراردادهای فعال
    getActive: (params = {}) => {
        return api.get('/contracts', { params: { ...params, status: 'active' } })
            .then(res => res.data)
    },

    // آمار قراردادها (برای داشبورد)
    getStatistics: () => {
        return api.get('/contracts/statistics')
            .then(res => res.data)
    },
    sendToContractor: (id) => {
        return api.post(`/contracts/${id}/send-to-contractor`)
            .then(res => res.data)
    },
}