// src/stores/contract/amendmentStore.js

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { amendmentService } from '@/Services/amendmentService'

export const useAmendmentStore = defineStore('amendment', () => {
    // State
    const amendments = ref([])
    const currentAmendment = ref(null)
    const loading = ref(false)
    const error = ref(null)

    // Getters
    const pendingAmendments = computed(() =>
        amendments.value.filter(a => a.status === 'pending')
    )

    const approvedAmendments = computed(() =>
        amendments.value.filter(a => a.status === 'approved')
    )

    const amountAmendments = computed(() =>
        amendments.value.filter(a => a.type === 'amount')
    )

    const extensionAmendments = computed(() =>
        amendments.value.filter(a => a.type === 'extension')
    )

    // Actions
    const fetchAmendmentsByContract = async (contractId, params = {}) => {
        loading.value = true
        error.value = null

        try {
            const response = await amendmentService.getByContract(contractId, params)
            amendments.value = response.data
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت الحاقیه‌ها'
            throw err
        } finally {
            loading.value = false
        }
    }

    const fetchAmendmentById = async (id) => {
        loading.value = true
        error.value = null

        try {
            const response = await amendmentService.getById(id)
            currentAmendment.value = response.data
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت الحاقیه'
            throw err
        } finally {
            loading.value = false
        }
    }

    const createAmendment = async (data) => {
        loading.value = true
        error.value = null

        try {
            const response = await amendmentService.create(data)
            amendments.value.unshift(response.data)
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در ایجاد الحاقیه'
            throw err
        } finally {
            loading.value = false
        }
    }

    const approveAmendment = async (id) => {
        loading.value = true
        error.value = null

        try {
            const response = await amendmentService.approve(id)
            const index = amendments.value.findIndex(a => a.id === id)
            if (index !== -1) {
                amendments.value[index] = response.data
            }
            if (currentAmendment.value?.id === id) {
                currentAmendment.value = response.data
            }
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در تایید الحاقیه'
            throw err
        } finally {
            loading.value = false
        }
    }

    return {
        // State
        amendments,
        currentAmendment,
        loading,
        error,

        // Getters
        pendingAmendments,
        approvedAmendments,
        amountAmendments,
        extensionAmendments,

        // Actions
        fetchAmendmentsByContract,
        fetchAmendmentById,
        createAmendment,
        approveAmendment,
    }
})