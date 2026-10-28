// src/api/contract/workflowService.js

import api from '@/api/axios'

export const workflowService = {
    // ارسال قرارداد به حقوقی
    submitToLegal: (contractId) => {
        return api.post(`/contracts/${contractId}/submit-to-legal`)
            .then(res => res.data)
    },

    // تنظیم پیش‌نویس حقوقی
    createLegalDraft: (contractId, data) => {
        return api.post(`/contracts/${contractId}/legal-draft`, data)
            .then(res => res.data)
    },

    // ارسال به پیمانکار
    sendToContractor: (contractId) => {
        return api.post(`/contracts/${contractId}/send-to-contractor`)
            .then(res => res.data)
    },

    // ثبت امضای پیمانکار (پس از OTP)
    markContractorSigned: (contractId) => {
        return api.post(`/contracts/${contractId}/mark-contractor-signed`)
            .then(res => res.data)
    },

    // امضای اجرایی (مدیرعامل/معاون/هیئت مدیره)
    executiveSign: (contractId, data) => {
        return api.post(`/contracts/${contractId}/executive-sign`, data)
            .then(res => res.data)
    },

    // بررسی اینکه آیا کاربر می‌تواند این قرارداد را امضا کند
    canSign: (contractId) => {
        return api.get(`/contracts/${contractId}/can-sign`)
            .then(res => res.data)
    },

    // ابلاغ به پیمانکار
    notifyContractor: (contractId) => {
        return api.post(`/contracts/${contractId}/notify`)
            .then(res => res.data)
    },

    // دریافت تاریخچه workflow
    getHistory: (contractId) => {
        return api.get(`/contracts/${contractId}/workflow-history`)
            .then(res => res.data)
    },
}