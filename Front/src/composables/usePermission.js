// src/composables/usePermission.js

import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function usePermission() {
    const authStore = useAuthStore()

    // بررسی دسترسی خاص (مثلاً 'contracts.create')
    const can = (permission) => {
        if (!authStore.user) return false

        // مدیر ارشد همه دسترسی‌ها را دارد
        if (authStore.user.hasRole?.('super_admin')) return true

        const permissions = authStore.user.permissions || []
        return permissions.includes(permission)
    }

    // بررسی یکی از دسترسی‌ها (OR)
    const canAny = (permissions = []) => {
        return permissions.some(p => can(p))
    }

    // بررسی همه دسترسی‌ها (AND)
    const canAll = (permissions = []) => {
        return permissions.every(p => can(p))
    }

    // بررسی نقش
    const hasRole = (role) => {
        if (!authStore.user) return false
        const roles = authStore.user.roles || []
        return roles.includes(role) || roles.some(r => r.name === role)
    }

    // بررسی یکی از نقش‌ها
    const hasAnyRole = (roles = []) => {
        return roles.some(r => hasRole(r))
    }

    // آیا کاربر پیمانکار است؟
    const isContractor = computed(() => {
        return hasRole('contractor')
    })

    // آیا کاربر مدیر است؟
    const isManager = computed(() => {
        return hasAnyRole(['super_admin', 'contract_manager'])
    })

    // آیا کاربر کارشناس مالی است؟
    const isFinancialExpert = computed(() => {
        return hasRole('financial_expert')
    })

    // آیا کاربر کارشناس حقوقی است؟
    const isLegalExpert = computed(() => {
        return hasRole('legal_expert')
    })

    // دسترسی‌های ماژول قراردادها
    const contractPermissions = computed(() => ({
        canView: can('contracts.read'),
        canCreate: can('contracts.create'),
        canUpdate: can('contracts.update'),
        canDelete: can('contracts.delete'),
        canSubmit: can('contracts.submit'),
        canApprove: can('contracts.approve'),
        canTerminate: can('contracts.terminate'),
    }))

    // دسترسی‌های ماژول طرفین
    const partyPermissions = computed(() => ({
        canView: can('parties.read'),
        canCreate: can('parties.create'),
        canUpdate: can('parties.update'),
        canDelete: can('parties.delete'),
    }))

    // دسترسی‌های ماژول الحاقیه‌ها
    const amendmentPermissions = computed(() => ({
        canView: can('amendments.read'),
        canCreate: can('amendments.create'),
        canApprove: can('amendments.approve'),
    }))

    // دسترسی‌های ماژول صورت‌وضعیت‌ها
    const invoicePermissions = computed(() => ({
        canView: can('invoices.read'),
        canCreate: can('invoices.create'),
        canUpdate: can('invoices.update'),
        canDelete: can('invoices.delete'),
        canSubmit: can('invoices.submit'),
        canApprove: can('invoices.approve'),
    }))

    // دسترسی‌های ماژول پرداخت‌ها
    const paymentPermissions = computed(() => ({
        canView: can('payments.read'),
        canCreate: can('payments.create'),
        canUpdate: can('payments.update'),
        canDelete: can('payments.delete'),
    }))

    // دسترسی‌های ماژول ضمانت‌نامه‌ها
    const guaranteePermissions = computed(() => ({
        canView: can('guarantees.read'),
        canCreate: can('guarantees.create'),
        canUpdate: can('guarantees.update'),
        canRelease: can('guarantees.release'),
    }))

    // دسترسی‌های ماژول امضاها
    const signaturePermissions = computed(() => ({
        canView: can('signatures.read'),
        canSign: can('signatures.sign'),
    }))

    return {
        can,
        canAny,
        canAll,
        hasRole,
        hasAnyRole,
        isContractor,
        isManager,
        isFinancialExpert,
        isLegalExpert,
        contractPermissions,
        partyPermissions,
        amendmentPermissions,
        invoicePermissions,
        paymentPermissions,
        guaranteePermissions,
        signaturePermissions,
    }
}