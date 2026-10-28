import { useToast } from 'primevue/usetoast'

export function useApiErrorHandler() {
    const toast = useToast()

    const handleApiError = (error, options = {}) => {
        const { setErrors = null, showToast = true } = options
        const status = error.response?.status
        const data = error.response?.data
        //detail: data.message
        switch (status) {
            case 400:
                toast.add({ severity: 'warn', summary: 'درخواست ناصحیح', detail:  'اطلاعات ارسالی معتبر نیست', life: 3000 });
                break;

            case 401:
                if (error.config?.url?.includes('/login')) {
                    toast.add({
                        severity: 'error',
                        summary: 'ورود ناموفق',
                        detail: data.message || 'نام کاربری یا رمز عبور اشتباه است',
                        life: 3000
                    });
                } else {
                    toast.add({
                        severity: 'warn',
                        summary: 'نشست منقضی شده',
                        detail: 'لطفاً دوباره وارد شوید',
                        life: 3000
                    });
                }
                break;

            case 403:
                toast.add({ severity: 'error', summary: 'ممنوع', detail:'شما اجازه دسترسی به این بخش را ندارید', life: 3000 });
                break;

            case 404:
                toast.add({ severity: 'error', summary: 'پیدا نشد', detail: data.message ||'سرویس یا منبع مورد نظر یافت نشد', life: 3000 });
                break;

            case 422:
                // خطای اعتبار سنجی (همان کدی که فرستادی)
                //if (setErrors) setErrors(data.errors || {});
                toast.add({ severity: 'error', summary: 'خطای اعتبار سنجی', detail:data.message || 'لطفاً فیلدهای فرم را بررسی کنید', life: 3000 });
                break;

            case 429:
                toast.add({ severity: 'warn', summary: 'درخواست بیش از حد', detail:data.message || 'لطفاً چند لحظه صبر کنید و دوباره تلاش کنید', life: 5000 });
                break;

            case 500:
                toast.add({ severity: 'error', summary: 'خطای سرور', detail:data.message || 'مشکلی در سمت سرور رخ داده است. کمی بعد تلاش کنید', life: 4000 });
                break;

            case 503:
                toast.add({ severity: 'info', summary: 'تعمیرات سایت', detail: data.message || 'سایت در حال بروزرسانی است. لطفا بعداً مراجعه کنید', life: 5000 });
                break;

            default:
                // مدیریت خطاهای شبکه (مثل قطع بودن اینترنت)
                if (!status) {
                    toast.add({ severity: 'error', summary: 'خطای شبکه', detail: data.message || 'اتصال اینترنت خود را بررسی کنید', life: 4000 });
                } else {
                    toast.add({ severity: 'error', summary: 'خطای ناشناخته', detail: data.message || 'خطایی رخ داد، لطفا دوباره تلاش کنید', life: 3000 });
                }
                break;
        }
        }
    return { handleApiError }
}
