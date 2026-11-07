# ماژول قرارداد (Contract)

## هدف
مرکز گردش قرارداد: ثبت، فهرست و جزئیات، پیش‌نویس حقوقی، ارسال و بازگشت، تاریخچه، اعلان، تنظیمات چاپ و سقف امضای نهایی.

## قابلیت‌ها و مسیرها
- CRUD پایه: `/api/v1/contracts` و `/api/v1/contracts/{id}`.
- گردش: `submit-to-contract-review`, `submit-to-legal`, `legal-draft`, `return-to-requester`, `send-to-contractor`, `notify`, `can-sign` و `workflow-history`.
- تنظیمات: `/api/v1/contract-settings`, `/contract-settings/letterhead` و `/contract-settings/letterhead/image`.
- میز ضمانت: `/api/v1/guarantee-desk` برای اطلاعات محدود دریافت و تأیید حضوری.
- منطق دامنه: `App/Models/Contract.php`, `App/Services/ContractWorkflowService.php`, `GuaranteeGateService.php`, `EmployerFrameworkService.php`.
- رابط کاربری: `Front/src/views/Contract/`؛ اصلی‌ترین صفحه‌ها `ContractsList.vue`, `ContractRequestForm.vue`, `ContractShow.vue`, `ContractSettings.vue`.

## چرخهٔ پیمانکاری
واحد متقاضی ثبت می‌کند، واحد قراردادها بررسی می‌کند، حقوقی متن را آماده می‌سازد، پیمانکار با OTP امضا می‌کند، در صورت نیاز ضمانت دریافت می‌شود، امضای واحد و حقوقی انجام می‌شود و سپس امضای نهایی بر اساس سقف مبلغ و ابلاغ دنبال می‌شود. جزئیات مرحله‌ها در Signing و `api/config/signing.php` تعریف شده‌اند.

## تنظیمات مهم
سقف امضای نهایی در `contract_settings` ذخیره می‌شود و مقدار پیش‌فرض از `config/signing.php` است. سربرگ چاپ روی دیسک عمومی Laravel ذخیره شده و مسیر آن در تنظیمات قرارداد ثبت می‌شود. دسترسی مدیریت تنظیمات از `contract-settings.manage` کنترل می‌شود.

## یادداشت
تاریخچهٔ گردش داخلی برای پیمانکار مجاز نیست. بررسی دامنهٔ پرونده باید همراه با مجوز انجام شود؛ داشتن `contracts.read` به‌تنهایی به معنی دسترسی به تمام قراردادها نیست.
