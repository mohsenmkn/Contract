// src/composables/usePayment.js

export function usePayment() {
    // لیست روش‌های پرداخت
    const paymentMethods = [
        { value: 'bank_transfer', label: 'حواله بانکی', icon: 'pi-building' },
        { value: 'check', label: 'چک', icon: 'pi-money-bill' },
        { value: 'cash', label: 'نقدی', icon: 'pi-wallet' },
        { value: 'online', label: 'آنلاین', icon: 'pi-globe' },
    ]

    // لیست وضعیت‌های پرداخت
    const paymentStatuses = [
        { value: 'pending', label: 'در انتظار', color: 'warn', icon: 'pi-clock' },
        { value: 'completed', label: 'تکمیل شده', color: 'success', icon: 'pi-check-circle' },
        { value: 'cancelled', label: 'لغو شده', color: 'secondary', icon: 'pi-times-circle' },
        { value: 'bounced', label: 'برگشتی', color: 'danger', icon: 'pi-exclamation-triangle' },
    ]

    // دریافت اطلاعات روش پرداخت
    const getMethodInfo = (method) => {
        return paymentMethods.find(m => m.value === method) || paymentMethods[0]
    }

    // دریافت اطلاعات وضعیت
    const getStatusInfo = (status) => {
        return paymentStatuses.find(s => s.value === status) || paymentStatuses[0]
    }

    // گزینه‌های Select برای روش
    const methodOptions = paymentMethods.map(m => ({ label: m.label, value: m.value }))

    // گزینه‌های Select برای وضعیت
    const statusOptions = paymentStatuses.map(s => ({ label: s.label, value: s.value }))

    return {
        paymentMethods,
        paymentStatuses,
        methodOptions,
        statusOptions,
        getMethodInfo,
        getStatusInfo,
    }
}