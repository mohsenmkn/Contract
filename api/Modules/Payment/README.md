# ماژول پرداخت (Payment)

## هدف
ثبت و مشاهدهٔ پرداخت‌های مرتبط با صورت‌وضعیت و قرارداد.

## مسیرها و رابط کاربری
- `GET /api/v1/payments`
- `GET /api/v1/invoices/{invoice}/payments`
- `POST /api/v1/payments`, `GET /api/v1/payments/{payment}`
- فرانت: `Front/src/views/Payment/PaymentsList.vue`, `PaymentCreate.vue`.

## کنترل دسترسی
مجوزهای پایه `payments.read/create/update/delete` هستند. اعتبار مبلغ، ارز، صورت‌وضعیت مرجع و نقش ثبت‌کننده را پیش از ثبت/تأیید کنترل کنید. گزارش پرداخت باید به دادهٔ مالی مجاز محدود باشد.
