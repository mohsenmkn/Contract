// src/api/contract/dashboardService.js

import api from '@/api/axios'

export const dashboardService = {
    // آمار کلی قراردادها (برای مدیر قراردادها)
    getContractStats: () => {
        return api.get('/dashboard/contract-stats')
            .then(res => res.data)
    },

    // آمار صورت‌وضعیت‌ها
    getInvoiceStats: () => {
        return api.get('/dashboard/invoice-stats')
            .then(res => res.data)
    },

    // آمار پرداخت‌ها
    getPaymentStats: () => {
        return api.get('/dashboard/payment-stats')
            .then(res => res.data)
    },

    // نمودار وضعیت قراردادها
    getContractStatusChart: () => {
        return api.get('/dashboard/contract-status-chart')
            .then(res => res.data)
    },

    // نمودار مبالغ قراردادها در طول زمان
    getContractAmountChart: (period = 'monthly') => {
        return api.get('/dashboard/contract-amount-chart', { params: { period } })
            .then(res => res.data)
    },

    // قراردادهای در حال اتمام
    getExpiringContracts: (days = 30) => {
        return api.get('/dashboard/expiring-contracts', { params: { days } })
            .then(res => res.data)
    },

    // ضمانت‌نامه‌های در حال اتمام
    getExpiringGuarantees: (days = 30) => {
        return api.get('/dashboard/expiring-guarantees', { params: { days } })
            .then(res => res.data)
    },

    // داشبورد پیمانکار
    getContractorDashboard: () => {
        return api.get('/dashboard/contractor')
            .then(res => res.data)
    },

    // فعالیت‌های اخیر
    getRecentActivities: (limit = 10) => {
        return api.get('/dashboard/recent-activities', { params: { limit } })
            .then(res => res.data)
    },
}