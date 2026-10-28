// src/composables/useInvoice.js

import { useNumberFormatter } from './useNumberFormatter'

export function useInvoice() {
    const { formatRial } = useNumberFormatter()

    // لیست وضعیت‌های صورت‌وضعیت
    const invoiceStatuses = [
        { value: 'draft', label: 'پیش‌نویس', color: 'secondary', icon: 'pi-file-edit' },
        { value: 'submitted', label: 'ارسال شده', color: 'info', icon: 'pi-send' },
        { value: 'approved', label: 'تایید شده', color: 'success', icon: 'pi-check-circle' },
        { value: 'rejected', label: 'رد شده', color: 'danger', icon: 'pi-times-circle' },
        { value: 'paid', label: 'پرداخت شده', color: 'success', icon: 'pi-wallet' },
    ]

    // دریافت اطلاعات وضعیت
    const getStatusInfo = (status) => {
        return invoiceStatuses.find(s => s.value === status) || invoiceStatuses[0]
    }

    // محاسبه درصد پرداخت
    const getPaymentProgress = (invoice) => {
        if (!invoice) return 0

        const netAmount = parseFloat(invoice.net_amount) || 0
        const paidAmount = parseFloat(invoice.paid_amount) || 0

        if (netAmount === 0) return 0
        return Math.min(100, Math.round((paidAmount / netAmount) * 100))
    }

    // محاسبه مانده قابل پرداخت
    const getRemainingAmount = (invoice) => {
        if (!invoice) return 0

        const netAmount = parseFloat(invoice.net_amount) || 0
        const paidAmount = parseFloat(invoice.paid_amount) || 0

        return Math.max(0, netAmount - paidAmount)
    }

    // بررسی اینکه آیا صورت‌وضعیت قابل ارسال است
    const isSubmittable = (invoice) => {
        if (!invoice) return false
        return invoice.status === 'draft'
    }

    // بررسی اینکه آیا صورت‌وضعیت قابل تایید است
    const isApprovable = (invoice) => {
        if (!invoice) return false
        return invoice.status === 'submitted'
    }

    // بررسی اینکه آیا صورت‌وضعیت قابل پرداخت است
    const isPayable = (invoice) => {
        if (!invoice) return false
        return invoice.status === 'approved' && !invoice.is_fully_paid
    }

    // گزینه‌های Select برای وضعیت
    const statusOptions = invoiceStatuses.map(s => ({ label: s.label, value: s.value }))

    return {
        invoiceStatuses,
        statusOptions,
        getStatusInfo,
        getPaymentProgress,
        getRemainingAmount,
        isSubmittable,
        isApprovable,
        isPayable,
    }
}