// src/stores/auth.js

import { defineStore } from 'pinia'
import api from "@/api/axios.js";

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('token') || null,
        loaded: false,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        hasUser: (state) => !!state.user,
        permissions: (state) => state.user?.permissions || [],
        roles: (state) => state.user?.roles || [],
        displayName: (state) => state.user?.name || '',

        can: (state) => (permission) => {
            return (state.user?.permissions || []).includes(permission)
        },
        canAny: (state) => (permissions = []) => {
            return permissions.some(permission =>
                (state.user?.permissions || []).includes(permission)
            )
        },
        canAll: (state) => (permissions = []) => {
            return permissions.every(permission =>
                (state.user?.permissions || []).includes(permission)
            )
        },

        // ✅ متدهای جدید برای بررسی نقش
        hasRole: (state) => (role) => {
            const roles = state.user?.roles || []
            return roles.some(r => {
                if (typeof r === 'string') return r === role
                return r.name === role || r === role
            })
        },

        hasAnyRole: (state) => (roles = []) => {
            const userRoles = state.user?.roles || []
            return roles.some(role =>
                userRoles.some(r => {
                    if (typeof r === 'string') return r === role
                    return r.name === role || r === role
                })
            )
        },

        isContractor: (state) => {
            return state.user?.employee_type === 'contractor'
        },

        isUnitRepresentative: (state) => {
            return !!state.user?.unit_id
        },

        // ✅ داشبورد پیش‌فرض بر اساس نقش
        defaultDashboard: (state) => {
            const roles = state.user?.roles || []
            const roleNames = roles.map(r => typeof r === 'string' ? r : r.name)

            if (roleNames.includes('contractor')) return 'contractor.portal'
            if (roleNames.includes('unit_representative')) return 'contract.requests'
            return 'Dashboard'
        },
    },

    actions: {
        async login(credentials) {
            try {
                const { data } = await api.post('/auth/login', credentials)
                this.setToken(data.token)
                this.setUser(data.user)
                localStorage.setItem('token', this.token)
                await this.fetchMe()
                return data
            } catch (error) {
                this.clearAuth()
                throw error
            }
        },

        async initAuth() {
            if (!this.token) {
                this.loaded = true
                return
            }
            try {
                await this.fetchMe()
            } catch (error) {
                // interceptor handles this
            }
        },

        async fetchMe() {
            try {
                const { data } = await api.get(`/auth/me`)
                this.user = data.user
                this.loaded = true
            } catch (error) {
                if (error?.response?.status === 401) {
                    this.clearAuth()
                } else {
                    this.user = null
                }
                this.loaded = true
                throw error
            }
        },

        setToken(token) {
            this.token = token
            if (token) {
                localStorage.setItem('token', token)
            } else {
                localStorage.removeItem('token')
            }
        },

        setUser(user) {
            this.user = user
            this.loaded = true
        },

        clearAuth() {
            this.user = null
            this.token = null
            this.loaded = true
            localStorage.removeItem('token')
        },

        async logout() {
            try {
                await api.post('/auth/logout')
            } catch (error) {
                // silent
            } finally {
                this.clearAuth()
            }
        },
    },
})