// src/stores/contract/signatureStore.js

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { signatureService } from '@/services/signatureService'

export const useSignatureStore = defineStore('signature', () => {
    const pendingSignatures = ref([])
    const currentSignature = ref(null)
    const currentDocument = ref(null)
    const loading = ref(false)
    const error = ref(null)

    const pendingCount = computed(() => pendingSignatures.value.length)

    const contractSignatures = computed(() =>
        pendingSignatures.value.filter(s => s.signable_type?.includes('Contract'))
    )

    const amendmentSignatures = computed(() =>
        pendingSignatures.value.filter(s => s.signable_type?.includes('Amendment'))
    )

    const fetchMyPendingSignatures = async () => {
        loading.value = true
        error.value = null
        try {
            const response = await signatureService.getMyPending()
            pendingSignatures.value = response.data
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت امضاها'
            throw err
        } finally {
            loading.value = false
        }
    }

    // ✅ متد جدید: دریافت جزئیات سند
    const fetchSignatureDetails = async (id) => {
        loading.value = true
        error.value = null
        try {
            const response = await signatureService.getSignatureDetails(id)
            currentSignature.value = response.data.signature
            currentDocument.value = response.data.document
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت جزئیات'
            throw err
        } finally {
            loading.value = false
        }
    }

    // ✅ متد جدید: درخواست OTP
    const requestOtp = async (id, templateId = 0) => {
        loading.value = true
        error.value = null
        try {
            const response = await signatureService.requestOtp(id, templateId)
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در ارسال کد'
            throw err
        } finally {
            loading.value = false
        }
    }

    // ✅ متد جدید: تایید OTP و امضا
    const verifyAndSign = async (id, data) => {
        loading.value = true
        error.value = null
        try {
            const response = await signatureService.verifyAndSign(id, data)
            // حذف از لیست در انتظار
            pendingSignatures.value = pendingSignatures.value.filter(s => s.id !== id)
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در تایید'
            throw err
        } finally {
            loading.value = false
        }
    }

    const rejectDocument = async (id, comments = null) => {
        loading.value = true
        error.value = null

        try {
            // فراخوانی API
            const response = await signatureService.RejectAndSign(id, { action: 'reject', comments })

            // ✅ حذف آیتم از لیست امضاهای در انتظار
            pendingSignatures.value = pendingSignatures.value.filter(s => s.id !== id)

            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در رد سند'
            throw err
        } finally {
            loading.value = false
        }
    }

    // ❌ حذف متدهای قدیمی
    // const signDocument = async (id, comments = null) => { ... }
    // const rejectDocument = async (id, comments = null) => { ... }

    return {
        pendingSignatures,
        currentSignature,
        currentDocument,
        loading,
        error,
        pendingCount,
        contractSignatures,
        amendmentSignatures,
        fetchMyPendingSignatures,
        fetchSignatureDetails,
        requestOtp,
        verifyAndSign,
        rejectDocument
    }
})