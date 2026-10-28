// src/stores/contract/guaranteeStore.js

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { guaranteeService } from '@/Services/guaranteeService'

export const useGuaranteeStore = defineStore('guarantee', () => {
    // State
    const guarantees = ref([])
    const currentGuarantee = ref(null)
    const loading = ref(false)
    const error = ref(null)

    // Getters
    const activeGuarantees = computed(() =>
        guarantees.value.filter(g => g.status === 'active')
    )

    const expiringGuarantees = computed(() =>
        guarantees.value.filter(g => g.is_expiring)
    )

    const totalGuaranteeAmount = computed(() =>
        activeGuarantees.value.reduce((sum, g) => sum + parseFloat(g.amount), 0)
    )

    // Actions
    const fetchGuaranteesByContract = async (contractId, params = {}) => {
        loading.value = true
        error.value = null

        try {
            const response = await guaranteeService.getByContract(contractId, params)
            guarantees.value = response.data
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت ضمانت‌نامه‌ها'
            throw err
        } finally {
            loading.value = false
        }
    }

    const fetchGuaranteeById = async (id) => {
        loading.value = true
        error.value = null

        try {
            const response = await guaranteeService.getById(id)
            currentGuarantee.value = response.data
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت ضمانت‌نامه'
            throw err
        } finally {
            loading.value = false
        }
    }

    const createGuarantee = async (data) => {
        loading.value = true
        error.value = null

        try {
            const response = await guaranteeService.create(data)
            guarantees.value.unshift(response.data)
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در ثبت ضمانت‌نامه'
            throw err
        } finally {
            loading.value = false
        }
    }

    const releaseGuarantee = async (id) => {
        loading.value = true
        error.value = null

        try {
            const response = await guaranteeService.release(id)
            const index = guarantees.value.findIndex(g => g.id === id)
            if (index !== -1) {
                guarantees.value[index] = response.data
            }
            if (currentGuarantee.value?.id === id) {
                currentGuarantee.value = response.data
            }
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در آزادسازی ضمانت‌نامه'
            throw err
        } finally {
            loading.value = false
        }
    }

    return {
        // State
        guarantees,
        currentGuarantee,
        loading,
        error,

        // Getters
        activeGuarantees,
        expiringGuarantees,
        totalGuaranteeAmount,

        // Actions
        fetchGuaranteesByContract,
        fetchGuaranteeById,
        createGuarantee,
        releaseGuarantee,
    }
})