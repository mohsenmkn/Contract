// src/composables/useNumberFormatter.js

import { computed } from 'vue'

export function useNumberFormatter() {
    // فرمت عدد با جداکننده هزارگان
    const formatNumber = (value, decimals = 0) => {
        if (value === null || value === undefined || value === '') return '۰'

        const num = parseFloat(value)
        if (isNaN(num)) return '۰'

        return num.toLocaleString('fa-IR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        })
    }

    // فرمت مبلغ به ریال
    const formatRial = (value, showUnit = true) => {
        if (value === null || value === undefined || value === '') return '۰ ریال'

        const num = parseFloat(value)
        if (isNaN(num)) return '۰ ریال'

        const formatted = num.toLocaleString('fa-IR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        })

        return showUnit ? `${formatted} ریال` : formatted
    }

    // فرمت مبلغ به تومان (تقسیم بر ۱۰)
    const formatToman = (value, showUnit = true) => {
        if (value === null || value === undefined || value === '') return '۰ تومان'

        const num = parseFloat(value) / 10
        if (isNaN(num)) return '۰ تومان'

        const formatted = num.toLocaleString('fa-IR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        })

        return showUnit ? `${formatted} تومان` : formatted
    }

    // فرمت مبلغ به صورت هوشمند (میلیون/میلیارد)
    const formatSmartAmount = (value, currency = 'IRR') => {
        if (value === null || value === undefined || value === '') return '۰'

        const num = parseFloat(value)
        if (isNaN(num)) return '۰'

        const absNum = Math.abs(num)
        let formatted, unit

        if (absNum >= 1_000_000_000_000) {
            // تریلیون
            formatted = (num / 1_000_000_000_000).toLocaleString('fa-IR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })
            unit = 'تریلیون'
        } else if (absNum >= 1_000_000_000) {
            // میلیارد
            formatted = (num / 1_000_000_000).toLocaleString('fa-IR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })
            unit = 'میلیارد'
        } else if (absNum >= 1_000_000) {
            // میلیون
            formatted = (num / 1_000_000).toLocaleString('fa-IR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })
            unit = 'میلیون'
        } else if (absNum >= 1_000) {
            // هزار
            formatted = (num / 1_000).toLocaleString('fa-IR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })
            unit = 'هزار'
        } else {
            formatted = num.toLocaleString('fa-IR', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            })
            unit = ''
        }

        const currencyLabel = currency === 'IRR' ? 'ریال' : 'تومان'
        return unit ? `${formatted} ${unit} ${currencyLabel}` : `${formatted} ${currencyLabel}`
    }

    // فرمت درصد
    const formatPercent = (value, decimals = 2) => {
        if (value === null || value === undefined || value === '') return '۰٪'

        const num = parseFloat(value)
        if (isNaN(num)) return '۰٪'

        return `${num.toLocaleString('fa-IR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        })}٪`
    }

    // فرمت تعداد (بدون اعشار)
    const formatCount = (value) => {
        return formatNumber(value, 0)
    }

    // تبدیل اعداد انگلیسی به فارسی
    const toPersianDigits = (value) => {
        if (value === null || value === undefined) return ''

        const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹']
        return value.toString().replace(/\d/g, d => persianDigits[d])
    }

    // تبدیل اعداد فارسی به انگلیسی
    const toEnglishDigits = (value) => {
        if (value === null || value === undefined) return ''

        return value
            .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
            .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d))
    }

    // فرمت مبلغ با رنگ (مثبت/منفی)
    const formatColoredAmount = (value, currency = 'IRR') => {
        const num = parseFloat(value)
        if (isNaN(num)) return { text: '۰', color: 'text-gray-500', icon: 'pi-minus' }

        const formatted = formatRial(num)

        if (num > 0) {
            return { text: `+${formatted}`, color: 'text-green-600', icon: 'pi-arrow-up' }
        } else if (num < 0) {
            return { text: formatted, color: 'text-red-600', icon: 'pi-arrow-down' }
        }

        return { text: formatted, color: 'text-gray-500', icon: 'pi-minus' }
    }

    // محاسبه تغییر درصدی
    const calculatePercentChange = (oldValue, newValue) => {
        const oldNum = parseFloat(oldValue)
        const newNum = parseFloat(newValue)

        if (isNaN(oldNum) || isNaN(newNum) || oldNum === 0) return 0

        return ((newNum - oldNum) / oldNum) * 100
    }

    return {
        formatNumber,
        formatRial,
        formatToman,
        formatSmartAmount,
        formatPercent,
        formatCount,
        toPersianDigits,
        toEnglishDigits,
        formatColoredAmount,
        calculatePercentChange,
    }
}