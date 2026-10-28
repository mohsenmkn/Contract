// src/api/groups.js
import api from '@/api/axios.js'

/**
 * Composable برای مدیریت API گروه‌های کاربری
 */
export const useGroupsApi = () => {
    const BASE = '/acl/groups'

    /** لیست گروه‌ها با آمار */
    const getAll = () => api.get(BASE)

    /** لیست ساده برای dropdown */
    const getSimpleList = () => api.get(`${BASE}/all`)

    /** جزئیات یک گروه */
    const getById = (id) => api.get(`${BASE}/${id}`)

    /** ایجاد گروه */
    const create = (data) => api.post(BASE, data)

    /** ویرایش گروه */
    const update = (id, data) => api.put(`${BASE}/${id}`, data)

    /** حذف گروه */
    const remove = (id) => api.delete(`${BASE}/${id}`)

    /** اختصاص کاربران به گروه (sync) */
    const assignUsers = (id, userIds, mode = 'sync') =>
        api.post(`${BASE}/${id}/assign-users`, {
            user_ids: userIds,
            mode,
        })

    /** دریافت لیست کاربران قابل انتخاب برای گروه */
    const getSelectableUsers = (params = {}) =>
        api.get('/users/selectable', { params })

    /** دریافت لیست سمت‌های پرکاربرد */
    const getPositions = () => api.get('/users/positions')


    /** حذف کاربران از گروه */
    const removeUsers = (id, userIds) =>
        api.post(`${BASE}/${id}/remove-users`, { user_ids: userIds })

    /** دریافت اعضای گروه */
    const getUsers = (id) => api.get(`${BASE}/${id}/users`)

    return {
        getAll,
        getSimpleList,
        getById,
        create,
        update,
        remove,
        assignUsers,
        removeUsers,
        getUsers,
        getSelectableUsers,  // ← جدید
        getPositions,        // ← جدید
    }
}