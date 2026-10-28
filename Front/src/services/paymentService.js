// src/api/contract/paymentService.js (آپدیت شده)

import api from '@/api/axios'

export const paymentService = {
    // لیست تمام پرداخت‌ها با فیلتر و pagination
    getAll: (params = {}) => {
        return api.get('/payments', { params })
            .then(res => res.data)
    },

    // لیست پرداخت‌های یک صورت‌وضعیت
    getByInvoice: (invoiceId, params = {}) => {
        return api.get(`/invoices/${invoiceId}/payments`, { params })
            .then(res => res.data)
    },

    // دریافت یک پرداخت
    getById: (id) => {
        return api.get(`/payments/${id}`)
            .then(res => res.data)
    },

    // ثبت پرداخت جدید
    create: (data) => {
        return api.post('/payments', data)
            .then(res => res.data)
    },

    // دریافت پرداخت‌های تکمیل شده
    getCompleted: (params = {}) => {
        return api.get('/payments', { params: { ...params, status: 'completed' } })
            .then(res => res.data)
    },

    // دریافت پرداخت‌های در انتظار
    getPending: (params = {}) => {
        return api.get('/payments', { params: { ...params, status: 'pending' } })
            .then(res => res.data)
    },
}