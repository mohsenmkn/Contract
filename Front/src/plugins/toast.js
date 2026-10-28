let toastInstance = null

export const setToastInstance = (instance) => {
    toastInstance = instance
}

export const showToast = ({ severity = 'error', summary, detail, life = 3000 }) => {
    toastInstance?.add({
        severity,
        summary,
        detail,
        life,
    })
}