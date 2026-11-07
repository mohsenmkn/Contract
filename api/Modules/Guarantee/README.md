# ماژول ضمانت‌نامه (Guarantee)

## هدف
ثبت، مشاهده و مدیریت ضمانت‌نامه‌های مرتبط با قرارداد و پیگیری نوع، شماره، مبلغ، بانک، تاریخ صدور و انقضا، وضعیت و اطلاعات ثبت‌کننده.

## مسیرها و رابط کاربری
- `GET /api/v1/contracts/{contract}/guarantees`: ضمانت‌های یک قرارداد.
- `POST /api/v1/guarantees`: ثبت ضمانت.
- `GET /api/v1/guarantees/{guarantee}`: جزئیات ضمانت.
- `POST /api/v1/guarantees/{guarantee}/release`: آزادسازی.
- فرانت: `Front/src/views/Guarantee/GuaranteesList.vue`, `GuaranteeCreate.vue`, `GuaranteeShow.vue`, `GuaranteeDesk.vue`.

## تفکیک مجوزها
`guarantees.read/create/update/release/manage` برای کارکردهای مختلف تعریف شده‌اند. `guarantee_officer` برای میز دریافت حضوری از `guarantees.manage` استفاده می‌کند تا اطلاعات لازم را ببیند و دریافت را تأیید کند؛ این نقش نباید به متن قرارداد یا قابلیت‌های ویرایش عمومی دسترسی پیدا کند. کارشناس مالی مسئول عملیات آزادسازی است، اگر مجوز `guarantees.release` داشته باشد.

## نکتهٔ عملیاتی
ثبت در سامانه جایگزین دریافت و نگهداری فیزیکی چک/سفته نیست. شماره/نوع سند، رسید تحویل و وضعیت را با رویهٔ مالی سازمان هماهنگ کنید.
