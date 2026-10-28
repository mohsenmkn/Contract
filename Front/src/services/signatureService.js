// src/api/contract/signatureService.js

import api from '@/api/axios'

export const signatureService = {
    // لیست امضاهای در انتظار من
    getMyPending: () => {
        return api.get('/signatures/pending').then(res => res.data)
    },

    // ✅ دریافت جزئیات سند برای امضا (شامل متن قرارداد)
    getSignatureDetails: (id) => {
        return api.get(`/signatures/${id}`).then(res => res.data)
    },

    // ✅ درخواست ارسال کد OTP
    requestOtp: (id, templateId = 0) => {
        return api.post(`/signatures/${id}/request-otp`, { template_id: templateId })
            .then(res => res.data)
    },

    // ✅ تایید کد OTP و امضای سند
    verifyAndSign: (id, data) => {
        return api.post(`/signatures/${id}/verify-and-sign`, data)
            .then(res => res.data)
    },

    //RejectSigContractor
    RejectAndSign: (id, data) => {
        return api.post(`signatures/${id}/reject`, data)
            .then(res => res.data)
    },


    // ❌ حذف متد قدیمی sign (جایگزین شده با verifyAndSign)
    // sign: (id, data) => { ... }
}