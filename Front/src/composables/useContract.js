// src/composables/useContract.js

import { computed } from 'vue'
import { useDateFormatter } from './useDateFormatter'
import { useNumberFormatter } from './useNumberFormatter'

export function useContract() {
    const { toJalali, daysRemaining, getTimeStatus } = useDateFormatter()
    const { formatRial, formatSmartAmount } = useNumberFormatter()

    // لیست وضعیت‌های قرارداد
    const contractStatuses = [
        { value: 'draft', label: 'پیش‌نویس', color: 'secondary', icon: 'pi-file-edit' },
        { value: 'pending_internal_approval', label: 'در انتظار تایید داخلی', color: 'warn', icon: 'pi-clock' },
        { value: 'pending_contractor_sign', label: 'در انتظار امضای پیمانکار', color: 'info', icon: 'pi-signature' },
        { value: 'active', label: 'فعال', color: 'success', icon: 'pi-check-circle' },
        { value: 'expiring', label: 'در حال اتمام', color: 'warn', icon: 'pi-exclamation-triangle' },
        { value: 'completed', label: 'خاتمه یافته', color: 'secondary', icon: 'pi-flag' },
        { value: 'terminated', label: 'فسخ شده', color: 'danger', icon: 'pi-times-circle' },
    ]

    // لیست انواع قرارداد
    const contractTypes = [
        { value: 'upstream', label: 'بالادستی (کارفرما)', icon: 'pi-arrow-up' },
        { value: 'downstream', label: 'پایین‌دستی (پیمانکار)', icon: 'pi-arrow-down' },
    ]

    // دریافت اطلاعات وضعیت
    const getStatusInfo = (status) => {
        return contractStatuses.find(s => s.value === status) || contractStatuses[0]
    }

    // دریافت اطلاعات نوع
    const getTypeInfo = (type) => {
        return contractTypes.find(t => t.value === type) || contractTypes[0]
    }

    // محاسبه وضعیت نمایشی قرارداد
    const getDisplayStatus = (contract) => {
        if (!contract) return getStatusInfo('draft')

        // اگر قرارداد فعال است ولی در حال اتمام
        if (contract.status === 'active' && contract.is_expiring) {
            return getStatusInfo('expiring')
        }

        return getStatusInfo(contract.status)
    }

    // محاسبه روزهای باقیمانده قرارداد
    const getContractDaysRemaining = (contract) => {
        if (!contract?.end_date) return null
        return daysRemaining(contract.end_date)
    }

    // محاسبه درصد پیشرفت قرارداد (بر اساس زمان)
    const getContractProgress = (contract) => {
        if (!contract?.start_date || !contract?.end_date) return 0

        const start = new Date(contract.start_date)
        const end = new Date(contract.end_date)
        const now = new Date()

        const total = end.getTime() - start.getTime()
        const elapsed = now.getTime() - start.getTime()

        if (elapsed <= 0) return 0
        if (elapsed >= total) return 100

        return Math.round((elapsed / total) * 100)
    }

    // محاسبه تغییر مبلغ نسبت به مبلغ اولیه
    const getAmountChange = (contract) => {
        if (!contract) return { amount: 0, percent: 0, direction: 'none' }

        const initial = parseFloat(contract.initial_amount) || 0
        const current = parseFloat(contract.current_amount) || 0
        const change = current - initial
        const percent = initial > 0 ? (change / initial) * 100 : 0

        return {
            amount: change,
            percent: percent,
            direction: change > 0 ? 'increase' : change < 0 ? 'decrease' : 'none',
            formattedAmount: formatRial(Math.abs(change)),
            formattedPercent: `${Math.abs(percent).toFixed(2)}٪`,
        }
    }

    // بررسی اینکه آیا قرارداد قابل ویرایش است
    const isEditable = (contract) => {
        if (!contract) return false
        return ['draft', 'pending_internal_approval'].includes(contract.status)
    }

    // بررسی اینکه آیا قرارداد قابل حذف است
    const isDeletable = (contract) => {
        if (!contract) return false
        return contract.status === 'draft'
    }

    // بررسی اینکه آیا قرارداد قابل ارسال برای تایید است
    const isSubmittable = (contract) => {
        if (!contract) return false
        return contract.status === 'draft'
    }

    // بررسی اینکه آیا قرارداد قابل امضا توسط پیمانکار است
    const isSignableByContractor = (contract) => {
        if (!contract) return false
        return contract.status === 'pending_contractor_sign'
    }

    // محاسبه وضعیت زمانی قرارداد
    const getContractTimeStatus = (contract, warningDays = 30) => {
        if (!contract?.end_date) return null
        return getTimeStatus(contract.end_date, warningDays)
    }

    // فرمت مبلغ قرارداد
    const formatContractAmount = (contract, smart = false) => {
        if (!contract) return '۰ ریال'

        const amount = parseFloat(contract.current_amount) || 0
        return smart ? formatSmartAmount(amount, contract.currency) : formatRial(amount)
    }

    // فرمت تاریخ قرارداد
    const formatContractDates = (contract) => {
        if (!contract) return { start: '', end: '', duration: '' }

        return {
            start: toJalali(contract.start_date),
            end: toJalali(contract.end_date),
            duration: getContractDaysRemaining(contract),
        }
    }

    // خلاصه قرارداد (برای نمایش در کارت)
    const getContractSummary = (contract) => {
        if (!contract) return null

        const statusInfo = getDisplayStatus(contract)
        const typeInfo = getTypeInfo(contract.type)
        const daysRemaining = getContractDaysRemaining(contract)
        const progress = getContractProgress(contract)
        const amountChange = getAmountChange(contract)
        const timeStatus = getContractTimeStatus(contract)

        return {
            ...contract,
            statusInfo,
            typeInfo,
            daysRemaining,
            progress,
            amountChange,
            timeStatus,
            formattedAmount: formatContractAmount(contract),
            formattedSmartAmount: formatContractAmount(contract, true),
            formattedDates: formatContractDates(contract),
        }
    }

    // گزینه‌های Select برای وضعیت
    const statusOptions = computed(() =>
        contractStatuses.map(s => ({ label: s.label, value: s.value }))
    )

    // گزینه‌های Select برای نوع
    const typeOptions = computed(() =>
        contractTypes.map(t => ({ label: t.label, value: t.value }))
    )

    return {
        contractStatuses,
        contractTypes,
        statusOptions,
        typeOptions,
        getStatusInfo,
        getTypeInfo,
        getDisplayStatus,
        getContractDaysRemaining,
        getContractProgress,
        getAmountChange,
        isEditable,
        isDeletable,
        isSubmittable,
        isSignableByContractor,
        getContractTimeStatus,
        formatContractAmount,
        formatContractDates,
        getContractSummary,
    }
}