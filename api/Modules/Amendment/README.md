# ماژول الحاقیه (Amendment)

## هدف
ثبت و مشاهدهٔ الحاقیه‌های وابسته به قرارداد و تأیید آن‌ها.

## مسیرها و رابط کاربری
- `GET /api/v1/contracts/{contract}/amendments`: فهرست الحاقیه‌های قرارداد.
- `POST /api/v1/amendments`: ایجاد الحاقیه.
- `GET /api/v1/amendments/{amendment}`: جزئیات.
- `POST /api/v1/amendments/{amendment}/approve`: تأیید.
- فرانت: `Front/src/views/Amendment/AmendmentsList.vue`, `AmendmentCreate.vue` و جزئیات ارتباط از `ContractShow.vue`.

## دسترسی و وابستگی
مجوزهای پایه در RBAC با `amendments.read`, `amendments.create`, `amendments.approve` تعریف شده‌اند. هر الحاقیه باید به قرارداد و گردش تأیید معتبر وابسته بماند؛ پیش از بهره‌برداری، ترتیب امضا و اثر آن بر مبلغ/مدت قرارداد را در UI و API بررسی کنید.
