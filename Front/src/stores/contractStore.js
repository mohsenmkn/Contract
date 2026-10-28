// src/stores/contract/contractStore.js

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { contractService } from '@/services/contractService'

export const useContractStore = defineStore('contract', () => {
    // State
    const contracts = ref([])
    const currentContract = ref(null)
    const loading = ref(false)
    const error = ref(null)
    const pagination = ref({
        currentPage: 1,
        lastPage: 1,
        perPage: 15,
        total: 0,
    })
    const filters = ref({
        type: null,
        status: null,
        party_id: null,
        search: '',
        expiring_soon: null,
        from_date: null,
        to_date: null,
        sort_by: 'created_at',
        sort_dir: 'desc',
    })

    // Getters
    const activeContracts = computed(() =>
        contracts.value.filter(c => c.status === 'active')
    )

    const expiringContracts = computed(() =>
        contracts.value.filter(c => c.is_expiring)
    )

    const upstreamContracts = computed(() =>
        contracts.value.filter(c => c.type === 'upstream')
    )

    const downstreamContracts = computed(() =>
        contracts.value.filter(c => c.type === 'downstream')
    )

    const totalAmount = computed(() =>
        contracts.value.reduce((sum, c) => sum + parseFloat(c.current_amount), 0)
    )

    // Actions
    const fetchContracts = async (filterParams = {}) => {
        loading.value = true
        error.value = null

        try {
            const params = {
                ...filters.value,
                ...filterParams,
                page: pagination.value.currentPage,
            }

            const response = await contractService.getAll(params)
            contracts.value = response.data
            pagination.value = {
                currentPage: response.meta.current_page,
                lastPage: response.meta.last_page,
                perPage: response.meta.per_page,
                total: response.meta.total,
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت قراردادها'
            throw err
        } finally {
            loading.value = false
        }
    }

    const fetchContractById = async (id) => {
        loading.value = true
        error.value = null

        try {
            const response = await contractService.getById(id)
            currentContract.value = response.data
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت قرارداد'
            throw err
        } finally {
            loading.value = false
        }
    }


    // ✅ Action جدید
    const sendToContractor = async (id) => {
        loading.value = true
        error.value = null

        try {
            const response = await contractService.sendToContractor(id)
            const index = contracts.value.findIndex(c => c.id === id)
            if (index !== -1) {
                contracts.value[index] = response.data
            }
            if (currentContract.value?.id === id) {
                currentContract.value = response.data
            }
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در ارسال قرارداد به پیمانکار'
            throw err
        } finally {
            loading.value = false
        }
    }



    const createContract = async (data) => {
        loading.value = true
        error.value = null

        try {
            const response = await contractService.create(data)
            contracts.value.unshift(response.data)
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در ایجاد قرارداد'
            throw err
        } finally {
            loading.value = false
        }
    }

    const updateContract = async (id, data) => {
        loading.value = true
        error.value = null

        try {
            const response = await contractService.update(id, data)
            const index = contracts.value.findIndex(c => c.id === id)
            if (index !== -1) {
                contracts.value[index] = response.data
            }
            if (currentContract.value?.id === id) {
                currentContract.value = response.data
            }
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در بروزرسانی قرارداد'
            throw err
        } finally {
            loading.value = false
        }
    }

    const deleteContract = async (id) => {
        loading.value = true
        error.value = null

        try {
            await contractService.delete(id)
            contracts.value = contracts.value.filter(c => c.id !== id)
            if (currentContract.value?.id === id) {
                currentContract.value = null
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در حذف قرارداد'
            throw err
        } finally {
            loading.value = false
        }
    }

    const submitContract = async (id) => {
        loading.value = true
        error.value = null

        try {
            const response = await contractService.submit(id)
            const index = contracts.value.findIndex(c => c.id === id)
            if (index !== -1) {
                contracts.value[index] = response.data
            }
            if (currentContract.value?.id === id) {
                currentContract.value = response.data
            }
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در ارسال قرارداد'
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
            type: null,
            status: null,
            party_id: null,
            search: '',
            expiring_soon: null,
            from_date: null,
            to_date: null,
            sort_by: 'created_at',
            sort_dir: 'desc',
        }
    }

    const setPage = (page) => {
        pagination.value.currentPage = page
    }

    return {
        // State
        contracts,
        currentContract,
        loading,
        error,
        pagination,
        filters,

        // Getters
        activeContracts,
        expiringContracts,
        upstreamContracts,
        downstreamContracts,
        totalAmount,

        // Actions
        fetchContracts,
        fetchContractById,
        createContract,
        updateContract,
        deleteContract,
        submitContract,
        setFilters,
        resetFilters,
        setPage,
        sendToContractor,
    }
})