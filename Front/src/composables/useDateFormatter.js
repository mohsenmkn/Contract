// src/composables/useDateFormatter.js

export function useDateFormatter() {
    // تبدیل تاریخ میلادی به شمسی (فرمت کامل)
    const toJalali = (date, format = 'full') => {
        if (!date) return ''

        const d = date instanceof Date ? date : new Date(date)
        if (isNaN(d.getTime())) return ''

        try {
            const formatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian-nu-latn', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
            })

            const parts = formatter.formatToParts(d)
            const year = parts.find(p => p.type === 'year').value
            const month = parts.find(p => p.type === 'month').value
            const day = parts.find(p => p.type === 'day').value

            // تبدیل اعداد به فارسی
            const toPersian = (str) => str.replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d])

            switch (format) {
                case 'full':
                    return `${toPersian(year)}/${toPersian(month)}/${toPersian(day)}`
                case 'long':
                    const longFormatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        weekday: 'long',
                    })
                    return longFormatter.format(d)
                case 'medium':
                    const mediumFormatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                    })
                    return mediumFormatter.format(d)
                case 'short':
                    return `${toPersian(year)}/${toPersian(month)}/${toPersian(day)}`
                case 'year':
                    return toPersian(year)
                case 'month':
                    const monthFormatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
                        month: 'long',
                    })
                    return monthFormatter.format(d)
                default:
                    return `${toPersian(year)}/${toPersian(month)}/${toPersian(day)}`
            }
        } catch (err) {
            console.error('Error formatting date:', err)
            return ''
        }
    }

    // تبدیل تاریخ و ساعت
    const toJalaliDateTime = (date) => {
        if (!date) return ''

        const d = date instanceof Date ? date : new Date(date)
        if (isNaN(d.getTime())) return ''

        try {
            const formatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian-nu-latn', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
            })

            const parts = formatter.formatToParts(d)
            const year = parts.find(p => p.type === 'year').value
            const month = parts.find(p => p.type === 'month').value
            const day = parts.find(p => p.type === 'day').value
            const hour = parts.find(p => p.type === 'hour').value
            const minute = parts.find(p => p.type === 'minute').value

            const toPersian = (str) => str.replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d])

            return `${toPersian(year)}/${toPersian(month)}/${toPersian(day)} ${toPersian(hour)}:${toPersian(minute)}`
        } catch (err) {
            console.error('Error formatting datetime:', err)
            return ''
        }
    }

    // محاسبه اختلاف روزها
    const diffInDays = (date1, date2) => {
        const d1 = date1 instanceof Date ? date1 : new Date(date1)
        const d2 = date2 instanceof Date ? date2 : new Date(date2)

        if (isNaN(d1.getTime()) || isNaN(d2.getTime())) return 0

        const diffTime = d2.getTime() - d1.getTime()
        return Math.floor(diffTime / (1000 * 60 * 60 * 24))
    }

    // محاسبه روزهای باقیمانده تا تاریخ
    const daysRemaining = (date) => {
        return diffInDays(new Date(), date)
    }

    // بررسی اینکه آیا تاریخ گذشته است
    const isPast = (date) => {
        const d = date instanceof Date ? date : new Date(date)
        return d < new Date()
    }

    // بررسی اینکه آیا تاریخ آینده است
    const isFuture = (date) => {
        const d = date instanceof Date ? date : new Date(date)
        return d > new Date()
    }

    // فرمت نسبی (مثلاً "۳ روز دیگر" یا "۲ روز پیش")
    const formatRelative = (date) => {
        const days = daysRemaining(date)

        if (days === 0) return 'امروز'
        if (days === 1) return 'فردا'
        if (days === -1) return 'دیروز'

        const toPersian = (str) => str.toString().replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d])

        if (days > 0) {
            return `${toPersian(days)} روز دیگر`
        } else {
            return `${toPersian(Math.abs(days))} روز پیش`
        }
    }

    // وضعیت زمانی (برای نمایش رنگی)
    const getTimeStatus = (date, warningDays = 30) => {
        const days = daysRemaining(date)

        if (days < 0) {
            return { status: 'expired', label: 'منقضی شده', color: 'red', icon: 'pi-times-circle' }
        } else if (days <= warningDays) {
            return { status: 'expiring', label: 'در حال اتمام', color: 'orange', icon: 'pi-exclamation-triangle' }
        } else if (days <= warningDays * 2) {
            return { status: 'warning', label: 'نزدیک به اتمام', color: 'yellow', icon: 'pi-info-circle' }
        } else {
            return { status: 'active', label: 'فعال', color: 'green', icon: 'pi-check-circle' }
        }
    }

    // دریافت نام ماه شمسی
    const getPersianMonthName = (monthNumber) => {
        const months = [
            'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
            'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
        ]
        return months[monthNumber - 1] || ''
    }

    // دریافت نام روز هفته
    const getPersianWeekday = (date) => {
        const d = date instanceof Date ? date : new Date(date)
        const formatter = new Intl.DateTimeFormat('fa-IR', { weekday: 'long' })
        return formatter.format(d)
    }

    return {
        toJalali,
        toJalaliDateTime,
        diffInDays,
        daysRemaining,
        isPast,
        isFuture,
        formatRelative,
        getTimeStatus,
        getPersianMonthName,
        getPersianWeekday,
    }
}