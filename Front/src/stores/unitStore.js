// src/stores/organization/unitStore.js

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { unitService } from '@/api/organization/unitService'

export const useUnitStore = defineStore('unit', () => {
    const units = ref([])
    const currentUnit = ref(null)
    const myUnit = ref(null)
    const loading = ref(false)
    const error = ref(null)
    const pagination = ref({
        currentPage: 1,
        lastPage: 1,
        perPage: 15,
        total: 0,
    })

    const activeUnits = computed(() => units.value.filter(u => u.is_active))

    const fetchUnits = async (params = {}) => {
        loading.value = true
        error.value = null
        try {
            const response = await unitService.getAll({
                page: pagination.value.currentPage,
                ...params,
            })
            units.value = response.data
            pagination.value = {
                currentPage: response.meta.current_page,
                lastPage: response.meta.last_page,
                perPage: response.meta.per_page,
                total: response.meta.total,
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'خطا در دریافت واحدها'
            throw err
        } finally {
            loading.value = false
        }
    }

    const fetchMyUnit = async () => {
        try {
            const response = await unitService.getMyUnit()
            myUnit.value = response.data
            return response.data
        } catch (err) {
            // کاربر نماینده نیست
            myUnit.value = null
            return null
        }
    }

    const createUnit = async (data) => {
        loading.value = true
        try {
            const response = await unitService.create(data)
            units.value.unshift(response.data)
            return response.data
        } finally {
            loading.value = false
        }
    }

    const updateUnit = async (id, data) => {
        loading.value = true
        try {
            const response = await unitService.update(id, data)
            const index = units.value.findIndex(u => u.id === id)
            if (index !== -1) units.value[index] = response.data
            return response.data
        } finally {
            loading.value = false
        }
    }

    const deleteUnit = async (id) => {
        loading.value = true
        try {
            await unitService.delete(id)
            units.value = units.value.filter(u => u.id !== id)
        } finally {
            loading.value = false
        }
    }

    const setRepresentative = async (unitId, userId) => {
        try {
            const response = await unitService.setRepresentative(unitId, userId)
            const unit = units.value.find(u => u.id === unitId)
            if (unit) unit.representative = response.data
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message
            throw err
        }
    }

    const setPage = (page) => {
        pagination.value.currentPage = page
    }

    return {
        units,
        currentUnit,
        myUnit,
        loading,
        error,
        pagination,
        activeUnits,
        fetchUnits,
        fetchMyUnit,
        createUnit,
        updateUnit,
        deleteUnit,
        setRepresentative,
        setPage,
    }
})