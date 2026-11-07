# ماژول احراز هویت (Auth)

## هدف
ورود و خروج، دریافت اطلاعات نشست، پروفایل کاربر، تغییر گذرواژه و بازیابی گذرواژه با OTP.

## مسیرها
مسیرها در `routes/api.php` زیر `/api/v1/auth` تعریف شده‌اند؛ از جمله `login`, `logout`, `me`, `profile`, `avatar`, `password` و مسیرهای `forgot-password/*`. مسیرهای محافظت‌شده از Sanctum و کنترل `user.can_login` استفاده می‌کنند.

## فرانت
`Front/src/views/Auth/LoginView.vue`, `ForgotPasswordView.vue`, `Front/src/stores/auth.js` و `Front/src/api/axios.js` نشست و هدایت پس از ورود را مدیریت می‌کنند.

## امنیت
تولید و انقضای OTP، محدودیت تلاش و اتصال provider را کنترل کنید. کلیدها و رمزهای محیط را فقط در secretهای استقرار نگه دارید. بعد از تغییر نقش کاربر، نشست و مجوزهای برگشتی از `/me` را اعتبارسنجی کنید.
