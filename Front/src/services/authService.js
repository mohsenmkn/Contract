import api from '@/api/axios'

export const authService = {
    login: (data) => api.post('/auth/login', data),
    logout: () => api.post('/auth/logout'),
    me: () => api.get('/auth/me'),
    sendOtp: (mobile) => api.post('/auth/forgot-password/send-otp', { mobile }),
    verifyOtp: (mobile, otp) => api.post('/auth/forgot-password/verify-otp', { mobile, otp }),
    resetPassword: (data) => api.post('/auth/forgot-password/reset', data),
    getCaptcha: () => api.get('/auth/captcha').then(r => r.data),
}


