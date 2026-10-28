// src/composables/useAmendment.js

export function useAmendment() {
    // لیست انواع الحاقیه
    const amendmentTypes = [
        { value: 'amount', label: 'تغییر مبلغ', icon: 'pi-dollar' },
        { value: 'extension', label: 'تمدید مدت', icon: 'pi-calendar-plus' },
        { value: 'scope', label: 'تغییر دامنه', icon: 'pi-list' },
        { value: 'termination', label: 'فسخ', icon: 'pi-times-circle' },
    ]

    // لیست وضعیت‌های الحاقیه
    const amendmentStatuses = [
        { value: 'draft', label: 'پیش‌نویس', color: 'secondary', icon: 'pi-file-edit' },
        { value: 'pending', label: 'در انتظار تایید', color: 'warn', icon: 'pi-clock' },
        { value: 'approved', label: 'تایید شده', color: 'success', icon: 'pi-check-circle' },
        { value: 'rejected', label: 'رد شده', color: 'danger', icon: 'pi-times-circle' },
    ]

    // دریافت اطلاعات نوع
    const getTypeInfo = (type) => {
        return amendmentTypes.find(t => t.value === type) || amendmentTypes[0]
    }

    // دریافت اطلاعات وضعیت
    const getStatusInfo = (status) => {
        return amendmentStatuses.find(s => s.value === status) || amendmentStatuses[0]
    }

    // گزینه‌های Select برای نوع
    const typeOptions = amendmentTypes.map(t => ({ label: t.label, value: t.value }))

    // گزینه‌های Select برای وضعیت
    const statusOptions = amendmentStatuses.map(s => ({ label: s.label, value: s.value }))

    // بررسی اینکه آیا الحاقیه قابل تایید است
    const isApprovable = (amendment) => {
        if (!amendment) return false
        return amendment.status === 'pending'
    }

    return {
        amendmentTypes,
        amendmentStatuses,
        typeOptions,
        statusOptions,
        getTypeInfo,
        getStatusInfo,
        isApprovable,
    }
}