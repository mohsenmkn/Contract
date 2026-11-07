# ماژول صورت‌وضعیت (Invoice)

## هدف
ثبت و پیگیری صورت‌وضعیت‌های متصل به قرارداد، ارسال برای تأیید و مشاهدهٔ وضعیت.

## مسیرها و رابط کاربری
- `GET /api/v1/contracts/{contract}/invoices`
- `POST /api/v1/invoices`, `GET /api/v1/invoices/{invoice}`
- `POST /api/v1/invoices/{invoice}/submit` و `/approve`
- فرانت: `Front/src/views/Invoice/InvoicesList.vue`, `InvoiceCreate.vue`, `InvoiceShow.vue`.

## دسترسی
مجوزهای `invoices.read/create/update/delete/submit/approve` در RBAC وجود دارند. پیمانکار فقط داده‌های مربوط به قراردادهای مرتبط خود را می‌بیند؛ تأیید و ارسال تابع مجوز و وضعیت پرونده است.
