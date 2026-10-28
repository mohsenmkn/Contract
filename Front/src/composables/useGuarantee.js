// src/composables/useGuarantee.js

export function useGuarantee() {
    // لیست انواع ضمانت‌نامه
    const guaranteeTypes = [
        { value: 'performance', label: 'حسن انجام کار', icon: 'pi-thumbs-up' },
        { value: 'advance_payment', label: 'پیش‌پرداخت', icon: 'pi-money-bill' },
        { value: 'bid', label: 'شرکت در مناقصه', icon: 'pi-file' },
        { value: 'retention', label: 'کسورات', icon: 'pi-percentage' },
    ]

    // لیست وضعیت‌های ضمانت‌نامه
    const guaranteeStatuses = [
        { value: 'active', label: 'فعال', color: 'success', icon: 'pi-check-circle' },
        { value: 'expired', label: 'منقضی شده', color: 'danger', icon: 'pi-times-circle' },
        { value: 'released', label: 'آزاد شده', color: 'secondary', icon: 'pi-lock-open' },
        { value: 'called', label: 'ضبط شده', color: 'danger', icon: 'pi-exclamation-triangle' },
    ]

    // دریافت اطلاعات نوع
    const getTypeInfo = (type) => {
        return guaranteeTypes.find(t => t.value === type) || guaranteeTypes[0]
    }

    // دریافت اطلاعات وضعیت
    const getStatusInfo = (status) => {
        return guaranteeStatuses.find(s => s.value === status) || guaranteeStatuses[0]
    }

    // بررسی اینکه آیا ضمانت‌نامه قابل آزادسازی است
    const isReleasable = (guarantee) => {
        if (!guarantee) return false
        return guarantee.status === 'active'
    }

    // گزینه‌های Select برای نوع
    const typeOptions = guaranteeTypes.map(t => ({ label: t.label, value: t.value }))

    // گزینه‌های Select برای وضعیت
    const statusOptions = guaranteeStatuses.map(s => ({ label: s.label, value: s.value }))

    return {
        guaranteeTypes,
        guaranteeStatuses,
        typeOptions,
        statusOptions,
        getTypeInfo,
        getStatusInfo,
        isReleasable,
    }
}