# 📋 README - سامانه مدیریت قراردادها (Contract Management System)

## 🎯 معرفی پروژه

سامانه مدیریت قراردادها یک پلتفرم جامع و یکپارچه برای مدیریت چرخه کامل قراردادها در سازمان است. این سیستم با رویکرد ماژولار و مقیاس‌پذیر طراحی شده و امکان مدیریت قراردادها از مرحله درخواست تا امضا و ابلاغ را فراهم می‌کند.

### ✨ ویژگی‌های کلیدی

- 🔄 **Workflow کامل قرارداد**: از درخواست تا امضا و ابلاغ
- 👥 **مدیریت واحدهای سازمانی**: با نمایندگان اختصاصی
- 🔐 **سیستم RBAC پیشرفته**: نقش‌ها و دسترسی‌های دقیق
- ️ **امضای الکترونیکی**: با OTP و ثبت امن
- 📊 **داشبوردهای تحلیلی**: برای مدیران و پیمانکاران
-  **پورتال پیمانکار**: دسترسی اختصاصی برای پیمانکاران
-  **رابط کاربری ریسپانسیو**: سازگار با موبایل و دسکتاپ
- 🔔 **سیستم اعلان‌ها**: اطلاع‌رسانی خودکار در هر مرحله

---

## ️ معماری سیستم

### بک‌اند (Backend)

```
Laravel 11 + Nwidart Modules
├── Modules/
│   ├── Auth/          # احراز هویت و کاربران
│   ├── Contract/      # مدیریت قراردادها
│   ├── Party/         # طرفین قرارداد
│   ├── Signature/     # امضای الکترونیکی
│   ├── Organization/  # واحدهای سازمانی
│   ├── Invoice/       # صورت‌وضعیت‌ها
│   ├── Payment/       # پرداخت‌ها
│   ├── Guarantee/     # ضمانت‌نامه‌ها
│   ├── Amendment/     # الحاقیه‌ها
│   └── Alert/         # هشدارها و اعلان‌ها
```

### فرانت‌اند (Frontend)

```
Vue 3 + Vite + PrimeVue
├── src/
│   ├── views/         # صفحات اصلی
│   ├── components/    # کامپوننت‌های قابل استفاده مجدد
│   ├── stores/        # Pinia Stores
│   ├── api/           # سرویس‌های API
│   ├── composables/   # Composition API
│   └── router/        # مسیریابی
```

---

## 🛠️ تکنولوژی‌های استفاده شده

### بک‌اند

| تکنولوژی | نسخه | کاربرد |
|----------|------|--------|
| **PHP** | 8.2+ | زبان برنامه‌نویسی |
| **Laravel** | 11.x | فریمورک اصلی |
| **Nwidart Modules** | 11.x | معماری ماژولار |
| **Spatie Permission** | 6.x | مدیریت نقش و دسترسی |
| **Laravel Sanctum** | 4.x | احراز هویت API |
| **MySQL** | 8.0+ | پایگاه داده |
| **Redis** | 7.x | کش و صف‌ها |

### فرانت‌اند

| تکنولوژی | نسخه | کاربرد |
|----------|------|--------|
| **Vue.js** | 3.5+ | فریمورک اصلی |
| **Vite** | 8.x | Build Tool |
| **PrimeVue** | 4.5+ | کامپوننت‌های UI |
| **Pinia** | 3.x | مدیریت State |
| **Vue Router** | 4.6+ | مسیریابی |
| **Axios** | 1.16+ | HTTP Client |
| **Tailwind CSS** | 3.4+ | استایل‌دهی |
| **Lucide Icons** | 1.x | آیکون‌ها |
| **Chart.js** | 4.5+ | نمودارها |

---

## 📦 نصب و راه‌اندازی

### پیش‌نیازها

- PHP 8.2 یا بالاتر
- Composer 2.x
- Node.js 20.19+ یا 22.12+
- MySQL 8.0+
- Redis 7.x (اختیاری)

### مراحل نصب

#### 1. کلون کردن پروژه

```bash
git clone <repository-url>
cd contract-management-system
```

#### 2. نصب وابستگی‌های بک‌اند

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
```

#### 3. تنظیمات دیتابیس

فایل `.env` را ویرایش کنید:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=contract_management
DB_USERNAME=root
DB_PASSWORD=

# تنظیمات پیامک (اختیاری)
MSGWAY_API_KEY=your_api_key
MSGWAY_BASE_URL=https://api.msgway.ir/v1
MSGWAY_TEMPLATE_ID_OTP=12345
```

#### 4. اجرای Migration ها و Seeder ها

```bash
php artisan migrate --seed
php artisan db:seed --class=ContractManagementSeeder
php artisan db:seed --class=OrganizationSeeder
```

#### 5. ایجاد لینک Storage

```bash
php artisan storage:link
```

#### 6. نصب وابستگی‌های فرانت‌اند

```bash
cd ../frontend
npm install
```

#### 7. اجرای پروژه

**بک‌اند:**
```bash
cd api
php artisan serve
```

**فرانت‌اند:**
```bash
cd frontend
npm run dev
```

---

## 🗄️ ساختار دیتابیس

### جداول اصلی

#### `users` - کاربران سیستم
```sql
- id, name, mobile, email, password
- national_code, personnel_code
- employee_type (personnel/contractor)
- party_id, unit_id
- is_active, settings
```

#### `organizational_units` - واحدهای سازمانی
```sql
- id, name, code, description
- is_active
```

#### `unit_representatives` - نمایندگان واحدها
```sql
- id, unit_id, user_id
- is_active, assigned_at
```

#### `contracts` - قراردادها
```sql
- id, contract_number, subject
- party_id, unit_id, requesting_user_id
- type (upstream/downstream)
- status (workflow states)
- start_date, end_date
- initial_amount, current_amount, currency
- content, pages_count, content_version
- parent_contract_id
- approved_by, approved_at, notified_at
```

#### `contract_items` - آیتم‌های قرارداد
```sql
- id, contract_id, parent_item_id
- item_code, description, unit
- quantity, unit_price, total_price
```

#### `signatures` - امضاهای سند
```sql
- id, signable_type, signable_id
- signer_id, signer_type
- status, signed_at
- signature_hash, ip_address, user_agent
```

#### `executive_signatures` - امضاهای اجرایی
```sql
- id, contract_id, signer_id
- signer_role (ceo/deputy_ceo/board_member)
- signed_at, signature_hash
```

#### `otp_codes` - کدهای OTP
```sql
- id, otpable_type, otpable_id
- user_id, code, purpose
- status, attempts, max_attempts
- expires_at, verified_at
```

---

## 🔄 Workflow قراردادها

### چرخه کامل وضعیت‌ها

```
┌─────────────────────────────────────────────────────────────────┐
│  1. draft                    - پیش‌نویس (ثبت توسط واحد)          │
│     ↓ [ارسال به حقوقی]                                           │
│  2. pending_legal_review     - در انتظار بررسی حقوقی            │
│     ↓ [تنظیم توسط حقوقی]                                         │
│  3. legal_draft              - پیش‌نویس حقوقی (تکمیل شده)       │
│     ↓ [ارسال به پیمانکار]                                        │
│  4. pending_contractor_sign  - در انتظار امضای پیمانکار         │
│     ↓ [امضای پیمانکار با OTP]                                    │
│  5. pending_executive_sign   - در انتظار امضای مدیرعامل         │
│     ↓ [امضای مدیرعامل/معاون/هیئت مدیره]                         │
│  6. pending_notification     - در انتظار ابلاغ به پیمانکار      │
│     ↓ [ابلاغ]                                                    │
│  7. active                   - فعال                              │
│     ↓ [پایان مدت]                                                │
│  8. completed                - خاتمه یافته                      │
│                                                                    │
│  └── terminated              - فسخ شده (از هر وضعیتی)           │
│  └── expiring                - در حال اتمام (خودکار)            │
└─────────────────────────────────────────────────────────────────┘
```

### قوانین ترنزیشن

```php
const TRANSITIONS = [
    'draft' => ['pending_legal_review', 'terminated'],
    'pending_legal_review' => ['legal_draft', 'draft', 'terminated'],
    'legal_draft' => ['pending_contractor_sign', 'pending_legal_review', 'terminated'],
    'pending_contractor_sign' => ['pending_executive_sign', 'legal_draft', 'terminated'],
    'pending_executive_sign' => ['pending_notification', 'pending_contractor_sign', 'terminated'],
    'pending_notification' => ['active', 'pending_executive_sign', 'terminated'],
    'active' => ['expiring', 'completed', 'terminated'],
    'expiring' => ['completed', 'terminated', 'active'],
    'completed' => [],
    'terminated' => [],
];
```

---

## 👥 نقش‌ها و دسترسی‌ها

### نقش‌های سیستم

| نقش | توضیح | دسترسی‌های کلیدی |
|-----|-------|------------------|
| **super_admin** | مدیر ارشد سیستم | دسترسی کامل به همه ماژول‌ها |
| **unit_representative** | نماینده واحد | ایجاد درخواست قرارداد برای واحد خود |
| **legal_officer** | کارشناس حقوقی | بررسی و تنظیم متن قراردادها |
| **contract_manager** | مدیر قراردادها | مدیریت کامل قراردادها |
| **financial_expert** | کارشناس مالی | مدیریت صورت‌وضعیت و پرداخت |
| **ceo** | مدیرعامل | امضای قراردادهای بالای 900M |
| **deputy_ceo** | معاون مالی | امضای قراردادهای زیر 900M |
| **board_member** | عضو هیئت مدیره | امضای قراردادهای بالای 900M |
| **contractor** | پیمانکار | مشاهده و امضای قراردادهای خود |

### قانون امضای اجرایی

```
مبلغ قرارداد < 900,000,000 تومان:
  ✅ معاون مالی (deputy_ceo)
  ✅ مدیرعامل (ceo)
  ✅ عضو هیئت مدیره (board_member)

مبلغ قرارداد >= 900,000,000 تومان:
  ❌ معاون مالی
  ✅ مدیرعامل (ceo)
  ✅ عضو هیئت مدیره (board_member)
```

---

## 🔌 API Endpoints

### احراز هویت

```
POST   /api/v1/auth/login              # ورود
POST   /api/v1/auth/logout             # خروج
GET    /api/v1/auth/me                 # اطلاعات کاربر فعلی
POST   /api/v1/auth/forgot-password/send-otp    # ارسال OTP
POST   /api/v1/auth/forgot-password/verify-otp  # تایید OTP
POST   /api/v1/auth/forgot-password/reset       # ریست رمز
```

### واحدهای سازمانی

```
GET    /api/v1/organizational-units              # لیست واحدها
POST   /api/v1/organizational-units              # ایجاد واحد
GET    /api/v1/organizational-units/{id}         # جزئیات واحد
PUT    /api/v1/organizational-units/{id}         # بروزرسانی
DELETE /api/v1/organizational-units/{id}         # حذف
GET    /api/v1/organizational-units/{id}/representative  # نماینده
POST   /api/v1/organizational-units/{id}/representative  # تعیین نماینده
GET    /api/v1/organizational-units/my-unit      # واحد کاربر فعلی
```

### قراردادها

```
GET    /api/v1/contracts                         # لیست قراردادها
POST   /api/v1/contracts                         # ایجاد قرارداد
GET    /api/v1/contracts/{id}                    # جزئیات قرارداد
PUT    /api/v1/contracts/{id}                    # بروزرسانی
DELETE /api/v1/contracts/{id}                    # حذف

# Workflow Endpoints
POST   /api/v1/contracts/{id}/submit-to-legal    # ارسال به حقوقی
POST   /api/v1/contracts/{id}/legal-draft        # تنظیم پیش‌نویس حقوقی
POST   /api/v1/contracts/{id}/send-to-contractor # ارسال به پیمانکار
POST   /api/v1/contracts/{id}/mark-contractor-signed # ثبت امضای پیمانکار
POST   /api/v1/contracts/{id}/executive-sign     # امضای اجرایی
GET    /api/v1/contracts/{id}/can-sign           # بررسی توانایی امضا
POST   /api/v1/contracts/{id}/notify             # ابلاغ به پیمانکار
GET    /api/v1/contracts/{id}/workflow-history   # تاریخچه workflow
```

### امضاها

```
GET    /api/v1/signatures/pending                # امضاهای در انتظار من
GET    /api/v1/signatures/{id}                   # جزئیات امضا
POST   /api/v1/signatures/{id}/request-otp       # درخواست OTP
POST   /api/v1/signatures/{id}/verify-and-sign   # تایید OTP و امضا
```

---

##  ساختار پروژه

```
contract-management-system/
├── api/                          # بک‌اند Laravel
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── Modules/                  # ماژول‌های Nwidart
│   │   ├── Auth/
│   │   ├── Contract/
│   │   ├── Organization/
│   │   ├── Party/
│   │   ├── Signature/
│   │   └── ...
│   ├── routes/
│   └── tests/
│
├── frontend/                     # فرانت‌اند Vue.js
│   ├── public/
│   ├── src/
│   │   ├── api/                  # سرویس‌های API
│   │   ├── assets/               # فایل‌های استاتیک
│   │   ├── components/           # کامپوننت‌ها
│   │   ├── composables/          # Composition API
│   │   ├── config/               # تنظیمات
│   │   ├── plugins/              # پلاگین‌ها
│   │   ├── router/               # مسیریابی
│   │   ├── stores/               # Pinia Stores
│   │   ├── views/                # صفحات
│   │   ── App.vue
│   ├── package.json
│   └── vite.config.js
│
└── README.md
```

---

## 🚀 دستورالعمل‌های توسعه

### ایجاد ماژول جدید

```bash
cd api
php artisan module:make ModuleName
```

### ایجاد Migration

```bash
php artisan make:migration create_table_name --path=Modules/ModuleName/Database/Migrations
```

### ایجاد Controller

```bash
php artisan make:controller Modules/ModuleName/Http/Controllers/ControllerName
```

### اجرای تست‌ها

```bash
# بک‌اند
cd api
php artisan test

# فرانت‌اند
cd frontend
npm run test
```

### پاک کردن کش

```bash
cd api
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload
```

---

## 🔐 امنیت

### احراز هویت

- استفاده از **Laravel Sanctum** برای API Authentication
- توکن‌های JWT با انقضای خودکار
- Rate Limiting برای جلوگیری از حملات Brute Force
- Captcha برای لاگین پس از 5 تلاش ناموفق

### امضای الکترونیکی

- ثبت IP Address و User Agent
- Hash امضا با SHA-256
- کد OTP یکبار مصرف با انقضای 5 دقیقه
- حداکثر 3 تلاش برای OTP

### دسترسی‌ها

- بررسی Permission در Middleware
- Policy-based Authorization
- Role-based Access Control (RBAC)

---

##  داشبوردها

### داشبورد مدیریتی

- آمار کلی قراردادها
- نمودار وضعیت قراردادها
- قراردادهای در حال اتمام
- آخرین فعالیت‌ها

### پورتال پیمانکار

- قراردادهای مرتبط با شرکت
- امضاهای در انتظار
- صورت‌وضعیت‌ها
- پرداخت‌ها

---

##  تست

### بک‌اند

```bash
cd api
php artisan test --filter=ContractTest
```

### فرانت‌اند

```bash
cd frontend
npm run test
```

---

## 📝 مستندات API

برای مشاهده مستندات کامل API:

```bash
cd api
php artisan scribe:generate
```

سپس به آدرس `/docs/api` مراجعه کنید.

---

## 🤝 مشارکت در پروژه

1. Fork کردن پروژه
2. ایجاد Branch جدید (`git checkout -b feature/AmazingFeature`)
3. Commit تغییرات (`git commit -m 'Add some AmazingFeature'`)
4. Push به Branch (`git push origin feature/AmazingFeature`)
5. باز کردن Pull Request

---

## 📄 لایسنس

این پروژه تحت لایسنس MIT منتشر شده است. برای اطلاعات بیشتر به فایل `LICENSE` مراجعه کنید.

---

## 👥 تیم توسعه

- **تیم توسعه**: [نام تیم]
- **مدیر پروژه**: [نام مدیر]
- **توسعه‌دهندگان**: [لیست توسعه‌دهندگان]

---

## 📞 پشتیبانی

برای گزارش باگ یا درخواست ویژگی جدید:

- 📧 ایمیل: support@example.com
-  Issues: [GitHub Issues](https://github.com/your-repo/issues)
-  Wiki: [GitHub Wiki](https://github.com/your-repo/wiki)

---

##  تشکر

از تمام contributors و استفاده‌کنندگان این پروژه سپاسگزاریم.

---

**ساخته شده با ❤️ توسط تیم توسعه**

**نسخه**: 1.0.0  
**آخرین بروزرسانی**: 2026-10-28