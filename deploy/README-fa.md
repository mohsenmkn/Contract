# راهنمای استقرار سامانه مدیریت قراردادها روی Ubuntu/Debian

این راهنما برای سرور `192.168.5.109`، دامنه `TestPro.gttmco.ir` و کاربر SSH به نام `mkarimi` تنظیم شده است. فرانت Vue به‌صورت فایل‌های استاتیک و API لاراول پشت یک دامنه و Nginx اجرا می‌شود.

> دستورات را به‌ترتیب اجرا کنید. هرجا مقدار `CHANGE_ME` آمده، مقدار مخصوص محیط خودتان را جایگزین کنید. رمزها و کلیدها را در چت، Git یا فایل بسته استقرار قرار ندهید.

## ۱. بررسی سرور و دسترسی sudo

از PowerShell ویندوز:

```powershell
ssh mkarimi@192.168.5.109
```

پس از ورود به سرور:

```bash
cat /etc/os-release
sudo -v
df -h /
free -h
```

اگر `sudo -v` خطا داد، نصب سرویس‌ها را مدیر سرور باید انجام دهد. مطمئن شوید DNS نام `TestPro.gttmco.ir` در شبکه کاربران به `192.168.5.109` می‌رسد.

## ۲. ساخت بسته از نسخه فعلی پروژه در ویندوز

در PowerShell، از ریشه پروژه `E:\laragon\Contr` اجرا کنید. این دستورات فرانت را می‌سازند و سورس فعلی، از جمله تغییرات commit نشده، را بدون فایل‌های محیطی و وابستگی‌های محلی بسته‌بندی می‌کنند:

```powershell
Set-Location E:\laragon\Contr\Front
npm ci
$env:VITE_API_BASE_URL = '/api/v1'
npm run build

Set-Location E:\laragon\Contr
tar.exe -czf contract-deploy.tar.gz `
  --exclude=api/.env --exclude=api/.env.* --exclude=api/vendor --exclude=api/node_modules `
  --exclude=api/storage --exclude=Front/.env --exclude=Front/.env.* `
  --exclude=Front/node_modules --exclude=Front/.git `
  --exclude=Front/tests --exclude=.git --exclude=.idea --exclude=.vscode `
  api Front
```

فایل `contract-deploy.tar.gz` را به سرور بفرستید:

```powershell
scp .\contract-deploy.tar.gz mkarimi@192.168.5.109:/tmp/
```

بسته عمداً شامل `.env` یا `vendor` نیست. فایل نهایی را پس از استقرار از پوشه محلی حذف کنید.

## ۳. نصب سرویس‌ها و PHP

به سرور SSH بزنید و اجرا کنید:

```bash
sudo apt update
sudo apt install -y nginx mysql-server composer tar curl unzip \
  php-fpm php-cli php-mysql php-xml php-curl php-mbstring php-zip \
  php-gd php-bcmath php-intl
```

نسخه PHP نصب‌شده باید با نیاز پروژه (PHP 8.1 یا جدیدتر) سازگار باشد. نسخه و مسیر FPM را ببینید:

```bash
php -v
ls /run/php/
```

اگر نسخه پیش‌فرض توزیع کمتر از PHP 8.1 است، قبل از ادامه PHP پشتیبانی‌شده را از مخزن مورد تأیید سازمان نصب کنید. مسیر سوکت FPM در پیکربندی Nginx باید مطابق خروجی `ls /run/php/` باشد.

## ۴. ایجاد پایگاه داده و کاربر MySQL

```bash
sudo mysql
```

در محیط MySQL، رمز قوی دلخواه را جایگزین کنید و دستورات را وارد کنید:

```sql
CREATE DATABASE contract_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'contract_app'@'localhost' IDENTIFIED BY 'CHANGE_ME_WITH_A_LONG_RANDOM_PASSWORD';
GRANT ALL PRIVILEGES ON contract_app.* TO 'contract_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

رمز را در مدیر رمز سازمان نگهداری کنید؛ بعداً همان را در `api/.env` قرار دهید.

## ۵. انتقال فایل‌ها و نصب API

```bash
sudo mkdir -p /var/www/contract/current
sudo tar -xzf /tmp/contract-deploy.tar.gz -C /var/www/contract/current
cd /var/www/contract/current/api
sudo mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
sudo chown -R mkarimi:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
composer install --no-dev --prefer-dist --optimize-autoloader
cp .env.example .env
```

فایل محیطی را با ویرایشگر باز کنید:

```bash
sudo nano /var/www/contract/current/api/.env
```

مقادیر پایه را به شکل زیر تنظیم کنید و رمز واقعی دیتابیس را بگذارید:

```dotenv
APP_NAME="سامانه مدیریت قراردادها"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://TestPro.gttmco.ir

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=contract_app
DB_USERNAME=contract_app
DB_PASSWORD=CHANGE_ME_WITH_A_LONG_RANDOM_PASSWORD

LOG_LEVEL=warning
FILESYSTEM_DISK=public
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true

MSGWAY_API_KEY=
```

`MSGWAY_API_KEY` را فقط اگر OTP پیامکی باید فعال باشد، با کلید صادرشده برای محیط تولید تکمیل کنید. برای ارسال ایمیل نیز تنظیمات SMTP واقعی سازمان را وارد کنید؛ مقادیر mailpit فقط برای توسعه‌اند.

### اتصال‌های SQL Server سازمان

کد احراز هویت پروژه از اتصال `gtarabar` استفاده می‌کند. اطلاعات هاست، پایگاه داده، نام کاربری و رمز SQL Server را از مسئول زیرساخت بگیرید و متغیرهای `GTARABAR_*` را در `.env` تنظیم کنید. در صورت استفاده از Kasra یا اتوماسیون، متغیرهای `KASRA_*` و `AUTOMATION_DB_*` نیز لازم می‌شوند.

این پروژه از درایور PHP `sqlsrv` برای این اتصال‌ها استفاده می‌کند. نصب بسته‌های بالا به‌تنهایی درایور SQL Server را نصب نمی‌کند. مدیر سرور باید Microsoft ODBC Driver و افزونه‌های PHP `sqlsrv` و `pdo_sqlsrv` سازگار با نسخه PHP نصب کند. تا وقتی این سرویس/شبکه/اطلاعات اتصال در دسترس نباشند، ورود کاربران متکی بر دیتابیس سازمانی ممکن است کار نکند. MySQL محلی برای داده‌های خود سامانه لازم است.

سپس کلید برنامه و دسترسی فایل‌ها را تنظیم کنید:

```bash
cd /var/www/contract/current/api
php artisan key:generate
php artisan storage:link
```

## ۶. اجرای migrationها و seed اولیه

ابتدا پشتیبان بگیرید و migrationها را بررسی کنید. چون دیتابیس تازه است، سپس اجرا کنید:

```bash
cd /var/www/contract/current/api
php artisan migrate --force
```

Providerهای ماژول‌ها migrationها را ثبت می‌کنند، بنابراین `artisan migrate` باید migrationهای ماژولی را هم اجرا کند. Seeder اصلی پروژه `ContractManagementSeeder` را اجرا می‌کند و ممکن است نقش‌ها/داده‌های پایه بسازد؛ فقط اگر محیط کاملاً تازه است، آن را اجرا کنید:

```bash
php artisan db:seed --force
```

از اجرای seed روی دیتابیس دارای داده بدون بازبینی کد Seeder خودداری کنید. برای نخستین مدیر سامانه، از مسیر ایجاد کاربر/نقش مورد تأیید پروژه استفاده کنید؛ رمز پیش‌فرضی در این راهنما تعریف نشده است.

تنظیم cacheهای لاراول:

```bash
php artisan config:cache
php artisan view:cache
```

## ۷. تنظیم Nginx برای فرانت و API

نام سوکت PHP-FPM را از مرحله ۳ بردارید و به‌جای `php-fpm.sock` مقدار واقعی مثل `php8.3-fpm.sock` بگذارید:

```bash
sudo nano /etc/nginx/sites-available/contract
```

محتوای زیر را قرار دهید:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name TestPro.gttmco.ir;

    root /var/www/contract/current/Front/dist;
    index index.html;
    client_max_body_size 30m;

    location ^~ /api/ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /var/www/contract/current/api/public/index.php;
        fastcgi_param SCRIPT_NAME /index.php;
        fastcgi_param DOCUMENT_ROOT /var/www/contract/current/api/public;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }

    location = /index.php { return 404; }

    location /storage/ {
        alias /var/www/contract/current/api/public/storage/;
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~* \.(env|ini|log|sql|bak)$ { deny all; }
}
```

فعال‌سازی و بررسی:

```bash
sudo ln -s /etc/nginx/sites-available/contract /etc/nginx/sites-enabled/contract
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl enable --now nginx mysql
# نام سرویس را با نسخه نصب‌شده جایگزین کنید؛ نمونه:
sudo systemctl enable --now php8.3-fpm
sudo systemctl reload nginx
```

اگر مسیر `/storage/...` لازم نیست برای مرورگر قابل دسترسی باشد، می‌توان آن location را حذف کرد. فایل‌های حساس مثل `.env` هرگز نباید در web root فرانت قرار بگیرند.

## ۸. HTTPS

دامنه فعلاً به IP خصوصی `192.168.5.109` اشاره می‌کند. اگر سرویس فقط داخل شبکه سازمان قابل دسترسی است، گواهی عمومی معمولاً با DNS-01 صادر می‌شود؛ برای آن باید به DNS عمومیِ دامنه دسترسی و امکان ساخت TXT record داشته باشید. برای تمدید خودکار، افزونه Certbot متناسب با DNS provider سازمان و API token محدود لازم است. روش manual DNS برای تمدید خودکار مناسب نیست. اگر سازمان CA داخلی دارد، گواهی را از همان CA بگیرید.

اگر دامنه از اینترنت عمومی قابل دسترسی است، شبکه/فایروال باید دسترسی لازم برای challenge و HTTPS را فراهم کند. پس از تنظیم گواهی و کلید طبق دستورالعمل DNS provider یا CA، بلاک HTTPS را با مسیر واقعی گواهی به Nginx اضافه کنید و سپس:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

از بازکردن پورت‌ها روی روتر یا فایروال بدون هماهنگی مسئول شبکه خودداری کنید. انتخاب challenge به عمومی یا داخلی بودن DNS و مسیر دسترسی سرور وابسته است. citeturn503554search0turn503554search2

## ۹. بررسی پس از استقرار

```bash
curl -I http://TestPro.gttmco.ir
curl -i http://TestPro.gttmco.ir/api/v1
sudo tail -n 100 /var/www/contract/current/api/storage/logs/laravel.log
sudo journalctl -u nginx -n 100 --no-pager
```

پاسخ `404` برای API root ممکن است طبیعی باشد؛ مهم این است که پاسخ از Laravel باشد ולא صفحه فرانت یا خطای Nginx. بعد از نصب گواهی، همین بررسی را با `https://` تکرار کنید. سپس ورود، ارسال OTP، بارگذاری فایل/سربرگ و گردش امضا را با کاربران آزمایشی سازمان بررسی کنید.

## مواردی که باید از زیرساخت سازمان بگیرید

- نسخه و توزیع دقیق Ubuntu/Debian و دسترسی `sudo`.
- تنظیم DNS داخلی و صادرکننده گواهی HTTPS (CA داخلی یا دسترسی DNS-01).
- اطلاعات اتصال امن SQL Server `Gtarabar` و در صورت نیاز Kasra/اتوماسیون، و مسیر شبکه از سرور.
- ODBC Driver و افزونه‌های PHP `sqlsrv` / `pdo_sqlsrv` سازگار با PHP.
- کلید SMS تولیدی برای OTP و تنظیمات SMTP، در صورت استفاده.
- سیاست پشتیبان‌گیری برای MySQL و `api/storage/app/public`.
