// src/api/contract/invoiceService.js

import api from '@/api/axios'

export const invoiceService = {
    // لیست صورت‌وضعیت‌های یک قرارداد
    getByContract: (contractId, params = {}) => {
        return api.get(`/contracts/${contractId}/invoices`, { params })
            .then(res => res.data)
    },

    // دریافت یک صورت‌وضعیت
    getById: (id) => {
        return api.get(`/invoices/${id}`)
            .then(res => res.data)
    },

    // ایجاد صورت‌وضعیت جدید
    create: (data) => {
        return api.post('/invoices', data)
            .then(res => res.data)
    },

    // ارسال صورت‌وضعیت برای تایید
    submit: (id) => {
        return api.post(`/invoices/${id}/submit`)
            .then(res => res.data)
    },

    // تایید صورت‌وضعیت
    approve: (id) => {
        return api.post(`/invoices/${id}/approve`)
            .then(res => res.data)
    },

    // دریافت صورت‌وضعیت‌های در انتظار تایید
    getPending: (params = {}) => {
        return api.get('/invoices', { params: { ...params, status: 'submitted' } })
            .then(res => res.data)
    },

    // دریافت صورت‌وضعیت‌های تایید شده (قابل پرداخت)
    getApproved: (params = {}) => {
        return api.get('/invoices', { params: { ...params, status: 'approved' } })
            .then(res => res.data)
    },
}