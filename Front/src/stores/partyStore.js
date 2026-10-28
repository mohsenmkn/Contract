// src/stores/contract/partyStore.js

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { partyService } from '@/Services/partyService'

export const usePartyStore = defineStore('party', () => {
    // State
    const parties = ref([])
    const currentParty = ref(null)
    const contractorsList = ref([])
    const loading = ref(false)
    const error = ref(null)
    const pagination = ref({
        currentPage: 1,
        lastPage: 1,
        perPage: 15,
        total: 0,
    })

    // Getters
    const contractors = computed(() =>
        contractorsList.value.length
            ? contractorsList.value
            : parties.value.filter(p => p.role === 'contractor')
    )

    const employers = computed(() =>
        parties.value.filter(p => p.role === 'employer')
    )

    const activeParties = computed(() =>
        parties.value.filter(p => p.is_active)
    )

    // Actions
    const fetchParties = async (params = {}) => {
        loading.value = true
        error.value = null

        try {
            const response = await partyService.getAll({
                ...params,
                page: pagination.value.currentPage,
            })
            parties.value = response.data
            pagination.value = {
                currentPage: response.meta.current_page,
                lastPage: response.meta.last_page,
                perPage: response.meta.per_page,
                total: response.meta.total,
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت طرفین قرارداد'
            throw err
        } finally {
            loading.value = false
        }
    }

    const fetchPartyById = async (id) => {
        loading.value = true
        error.value = null

        try {
            const response = await partyService.getById(id)
            currentParty.value = response.data
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت طرف قرارداد'
            throw err
        } finally {
            loading.value = false
        }
    }

    const createParty = async (data) => {
        loading.value = true
        error.value = null

        try {
            const response = await partyService.create(data)
            parties.value.unshift(response.data)
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در ایجاد طرف قرارداد'
            throw err
        } finally {
            loading.value = false
        }
    }

    const updateParty = async (id, data) => {
        loading.value = true
        error.value = null

        try {
            const response = await partyService.update(id, data)
            const index = parties.value.findIndex(p => p.id === id)
            if (index !== -1) {
                parties.value[index] = response.data
            }
            if (currentParty.value?.id === id) {
                currentParty.value = response.data
            }
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در بروزرسانی طرف قرارداد'
            throw err
        } finally {
            loading.value = false
        }
    }

    const deleteParty = async (id) => {
        loading.value = true
        error.value = null

        try {
            await partyService.delete(id)
            parties.value = parties.value.filter(p => p.id !== id)
            if (currentParty.value?.id === id) {
                currentParty.value = null
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در حذف طرف قرارداد'
            throw err
        } finally {
            loading.value = false
        }
    }

    const searchParties = async (query) => {
        try {
            const response = await partyService.search(query)
            return response
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در جستجو'
            throw err
        }
    }

    const fetchContractors = async (params = {}) => {
        loading.value = true
        error.value = null

        try {
            const response = await partyService.getContractors(params)

            // پشتیبانی از ساختارهای متداول پاسخ API
            const payload = response?.data ?? response

            const rows = Array.isArray(payload)
                ? payload
                : payload?.data ??
                payload?.contractors ??
                payload?.items ??
                payload?.results ??
                []

            contractorsList.value = rows.map(party => ({
                ...party,
                id: party.id ?? party.party_id ?? party.partyId,
                name:
                    party.name ??
                    party.full_name ??
                    party.fullName ??
                    party.title ??
                    '',
                role: party.role ?? 'contractor',
            }))

            return contractorsList.value
        } catch (err) {
            error.value =
                err.response?.data?.message ??
                err.message ??
                'دریافت فهرست پیمانکاران ناموفق بود'

            contractorsList.value = []
            throw err
        } finally {
            loading.value = false
        }
    }

    const setPage = (page) => {
        pagination.value.currentPage = page
    }

    return {
        // State
        parties,
        currentParty,
        loading,
        error,
        pagination,

        // Getters
        contractors,
        employers,
        activeParties,

        // Actions
        fetchParties,
        fetchPartyById,
        createParty,
        updateParty,
        deleteParty,
        searchParties,
        fetchContractors,
        setPage,
        contractorsList,

    }
})