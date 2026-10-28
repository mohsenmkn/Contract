// src/stores/contract/invoiceStore.js

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { invoiceService } from '@/Services/invoiceService'

export const useInvoiceStore = defineStore('invoice', () => {
    // State
    const invoices = ref([])
    const currentInvoice = ref(null)
    const loading = ref(false)
    const error = ref(null)

    // Getters
    const pendingInvoices = computed(() =>
        invoices.value.filter(i => i.status === 'submitted')
    )

    const approvedInvoices = computed(() =>
        invoices.value.filter(i => i.status === 'approved')
    )

    const paidInvoices = computed(() =>
        invoices.value.filter(i => i.status === 'paid')
    )

    const totalPendingAmount = computed(() =>
        pendingInvoices.value.reduce((sum, i) => sum + parseFloat(i.net_amount), 0)
    )

    const totalApprovedAmount = computed(() =>
        approvedInvoices.value.reduce((sum, i) => sum + parseFloat(i.net_amount), 0)
    )

    // Actions
    const fetchInvoicesByContract = async (contractId, params = {}) => {
        loading.value = true
        error.value = null

        try {
            const response = await invoiceService.getByContract(contractId, params)
            invoices.value = response.data
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت صورت‌وضعیت‌ها'
            throw err
        } finally {
            loading.value = false
        }
    }

    const fetchInvoiceById = async (id) => {
        loading.value = true
        error.value = null

        try {
            const response = await invoiceService.getById(id)
            currentInvoice.value = response.data
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت صورت‌وضعیت'
            throw err
        } finally {
            loading.value = false
        }
    }

    const createInvoice = async (data) => {
        loading.value = true
        error.value = null

        try {
            const response = await invoiceService.create(data)
            invoices.value.unshift(response.data)
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در ایجاد صورت‌وضعیت'
            throw err
        } finally {
            loading.value = false
        }
    }

    const submitInvoice = async (id) => {
        loading.value = true
        error.value = null

        try {
            const response = await invoiceService.submit(id)
            const index = invoices.value.findIndex(i => i.id === id)
            if (index !== -1) {
                invoices.value[index] = response.data
            }
            if (currentInvoice.value?.id === id) {
                currentInvoice.value = response.data
            }
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در ارسال صورت‌وضعیت'
            throw err
        } finally {
            loading.value = false
        }
    }

    const approveInvoice = async (id) => {
        loading.value = true
        error.value = null

        try {
            const response = await invoiceService.approve(id)
            const index = invoices.value.findIndex(i => i.id === id)
            if (index !== -1) {
                invoices.value[index] = response.data
            }
            if (currentInvoice.value?.id === id) {
                currentInvoice.value = response.data
            }
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در تایید صورت‌وضعیت'
            throw err
        } finally {
            loading.value = false
        }
    }

    return {
        // State
        invoices,
        currentInvoice,
        loading,
        error,

        // Getters
        pendingInvoices,
        approvedInvoices,
        paidInvoices,
        totalPendingAmount,
        totalApprovedAmount,

        // Actions
        fetchInvoicesByContract,
        fetchInvoiceById,
        createInvoice,
        submitInvoice,
        approveInvoice,
    }
})