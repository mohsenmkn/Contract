
import api from '@/api/axios'

export const partyService = {
    // لیست طرفین با فیلتر و pagination
    getAll: (params = {}) => {
        return api.get('/parties', { params })
            .then(res => res.data)
    },

    // دریافت یک طرف قرارداد
    getById: (id) => {
        return api.get(`/parties/${id}`)
            .then(res => res.data)
    },

    // ایجاد طرف قرارداد جدید
    create: (data) => {
        return api.post('/parties', data)
            .then(res => res.data)
    },

    // بروزرسانی طرف قرارداد
    update: (id, data) => {
        return api.put(`/parties/${id}`, data)
            .then(res => res.data)
    },

    // حذف طرف قرارداد
    delete: (id) => {
        return api.delete(`/parties/${id}`)
            .then(res => res.data)
    },

    // دریافت پیمانکاران
    getContractors: (params = {}) => {
        return api.get('/parties', { params: { ...params, role: 'contractor' } })
            .then(res => res.data)
    },

    // دریافت کارفرمایان
    getEmployers: (params = {}) => {
        return api.get('/parties', { params: { ...params, role: 'employer' } })
            .then(res => res.data)
    },

    // جستجو در طرفین (برای AutoComplete)
    search: (query) => {
        return api.get('/parties', { params: { search: query } })
            .then(res => res.data.data)
    },
}