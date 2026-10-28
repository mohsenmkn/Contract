// src/stores/contract/workflowStore.js

import { defineStore } from 'pinia'
import { ref } from 'vue'
import { workflowService } from '@/api/contract/workflowService'

export const useWorkflowStore = defineStore('workflow', () => {
    const loading = ref(false)
    const error = ref(null)
    const canSignInfo = ref(null)
    const history = ref([])

    const submitToLegal = async (contractId) => {
        loading.value = true
        error.value = null
        try {
            const response = await workflowService.submitToLegal(contractId)
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message
            throw err
        } finally {
            loading.value = false
        }
    }

    const createLegalDraft = async (contractId, data) => {
        loading.value = true
        error.value = null
        try {
            const response = await workflowService.createLegalDraft(contractId, data)
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message
            throw err
        } finally {
            loading.value = false
        }
    }

    const sendToContractor = async (contractId) => {
        loading.value = true
        error.value = null
        try {
            const response = await workflowService.sendToContractor(contractId)
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message
            throw err
        } finally {
            loading.value = false
        }
    }

    const executiveSign = async (contractId, data) => {
        loading.value = true
        error.value = null
        try {
            const response = await workflowService.executiveSign(contractId, data)
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message
            throw err
        } finally {
            loading.value = false
        }
    }

    const checkCanSign = async (contractId) => {
        try {
            const response = await workflowService.canSign(contractId)
            canSignInfo.value = response.data
            return response.data
        } catch (err) {
            canSignInfo.value = null
            return null
        }
    }

    const notifyContractor = async (contractId) => {
        loading.value = true
        try {
            const response = await workflowService.notifyContractor(contractId)
            return response.data
        } finally {
            loading.value = false
        }
    }

    const fetchHistory = async (contractId) => {
        try {
            const response = await workflowService.getHistory(contractId)
            history.value = response.data
            return response.data
        } catch (err) {
            history.value = []
            return []
        }
    }

    return {
        loading,
        error,
        canSignInfo,
        history,
        submitToLegal,
        createLegalDraft,
        sendToContractor,
        executiveSign,
        checkCanSign,
        notifyContractor,
        fetchHistory,
    }
})