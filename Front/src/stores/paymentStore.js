// src/stores/contract/paymentStore.js (آپدیت شده - فقط بخش‌های اضافه شده)

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { paymentService } from '@/Services/paymentService'

export const usePaymentStore = defineStore('payment', () => {
    // State
    const payments = ref([])
    const allPayments = ref([]) // لیست کامل برای صفحه PaymentsList
    const currentPayment = ref(null)
    const loading = ref(false)
    const error = ref(null)
    const pagination = ref({
        currentPage: 1,
        lastPage: 1,
        perPage: 15,
        total: 0,
    })
    const filters = ref({
        status: null,
        method: null,
        from_date: null,
        to_date: null,
        search: '',
        sort_by: 'payment_date',
        sort_dir: 'desc',
    })

    // Getters
    const completedPayments = computed(() =>
        payments.value.filter(p => p.status === 'completed')
    )

    const pendingPayments = computed(() =>
        payments.value.filter(p => p.status === 'pending')
    )

    const totalCompletedAmount = computed(() =>
        completedPayments.value.reduce((sum, p) => sum + parseFloat(p.amount), 0)
    )

    // Actions
    const fetchAllPayments = async (filterParams = {}) => {
        loading.value = true
        error.value = null

        try {
            const params = {
                ...filters.value,
                ...filterParams,
                page: pagination.value.currentPage,
            }

            const response = await paymentService.getAll(params)
            allPayments.value = response.data
            pagination.value = {
                currentPage: response.meta.current_page,
                lastPage: response.meta.last_page,
                perPage: response.meta.per_page,
                total: response.meta.total,
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت پرداخت‌ها'
            throw err
        } finally {
            loading.value = false
        }
    }

    const fetchPaymentsByInvoice = async (invoiceId, params = {}) => {
        loading.value = true
        error.value = null

        try {
            const response = await paymentService.getByInvoice(invoiceId, params)
            payments.value = response.data
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت پرداخت‌ها'
            throw err
        } finally {
            loading.value = false
        }
    }

    const fetchPaymentById = async (id) => {
        loading.value = true
        error.value = null

        try {
            const response = await paymentService.getById(id)
            currentPayment.value = response.data
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت پرداخت'
            throw err
        } finally {
            loading.value = false
        }
    }

    const createPayment = async (data) => {
        loading.value = true
        error.value = null

        try {
            const response = await paymentService.create(data)
            payments.value.unshift(response.data)
            allPayments.value.unshift(response.data)
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در ثبت پرداخت'
            throw err
        } finally {
            loading.value = false
        }
    }

    const setFilters = (newFilters) => {
        filters.value = { ...filters.value, ...newFilters }
    }

    const resetFilters = () => {
        filters.value = {
            status: null,
            method: null,
            from_date: null,
            to_date: null,
            search: '',
            sort_by: 'payment_date',
            sort_dir: 'desc',
        }
    }

    const setPage = (page) => {
        pagination.value.currentPage = page
    }

    return {
        // State
        payments,
        allPayments,
        currentPayment,
        loading,
        error,
        pagination,
        filters,

        // Getters
        completedPayments,
        pendingPayments,
        totalCompletedAmount,

        // Actions
        fetchAllPayments,
        fetchPaymentsByInvoice,
        fetchPaymentById,
        createPayment,
        setFilters,
        resetFilters,
        setPage,
    }
})