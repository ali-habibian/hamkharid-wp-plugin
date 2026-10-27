# راهنمای توسعه، آزمون و انتشار

مرجع اصلی: [فهرست مستندات](README.md). این سند فرمان‌ها و شواهد اجرای محلی QA را توضیح می‌دهد. کاربر اجرای محلی و داده آزمایشی را مجاز کرده است؛ به هاست یا داده واقعی دست زده نمی‌شود. انتشار عمومی، استقرار یا ارسال ZIP نیازمند مجوز جداگانه‌اند.

## محیط و وابستگی

- ریشه فعلی: `D:\laragon\www\hamkharid`؛ shell فعلی PowerShell است.
- نسخه‌های محیط محلی بررسی‌شده: PHP 8.4.1، WordPress 7.1.3، WooCommerce 11.2.1، Node 26.7.0، npm 11.19.0 و Composer 2.8.3؛ قالب فعال Luxury Shop 1.9.4، locale=`fa_IR`/RTL و HPOS روشن. این ماتریس ادعای پوشش همه نسخه‌های سربرگ نیست.
- PHP CLI، PHP وب و تنظیمات extension آن‌ها ممکن است متفاوت باشند. مسیر پوشه Laragon اثبات نمی‌کند PHP از Laragon اجرا می‌شود؛ در بررسی قبلی CLI از `C:\xampp\php\php.exe` resolve شد، پیش از اجرا دوباره بررسی شود.
- WordPress/WooCommerce و DB محلی برای integration لازم‌اند؛ root وردپرس خارج از ریشه افزونه است. نام DB، حساب و رمز در مستندات/مخزن ثبت نشوند.
- runtime به `vendor/autoload.php` نیاز دارد. ابزارهای dev از `composer.lock` و Playwright از `package-lock.json`/`package.json` می‌آیند. فایل‌های لازم از ignore محلی خارج شده‌اند؛ دسترسی آن‌ها برای checkout تازه پس از commit اثبات می‌شود.

## شروع توسعه در workspace محلی QA

ابتدا `@C:\Users\HP\.codex\RTK.md` و [فهرست](README.md)، [وضعیت](roadmap-fa.md) و [تصمیم‌ها](decisions-fa.md) خوانده شوند. تغییرات محلی نامرتبط را حفظ کنید.

```powershell
Set-Location 'D:\laragon\www\hamkharid'
Get-Command php, composer, node, npm
php --version
composer --version
node --version
npm --version
rtk git status --short
```

در checkoutهای تازه، از lockها نصب کنید؛ update وابستگی وظیفه جداگانه است:

```powershell
composer install
npm ci
npm run e2e:install
```

در این workspace، WordPress/WooCommerce محلی خارج از ریشه افزونه است و افزونه به checkout وصل شده است. fixtureها فقط روی محیط محلی QA اجرا شوند. `npm run e2e:cleanup` fixtureهای علامت‌دار را حذف می‌کند؛ پیش از آن root درست را بررسی کنید.

## فرمان‌های بررسی و اثر آن‌ها

| فرمان موجود | پوشش | اثر و محدودیت |
|---|---|---|
| `composer run lint` | syntax فایل‌های فهرست‌شده | DB را لازم ندارد؛ فهرست صریح باید با فایل‌های جدید هماهنگ شود |
| `composer run cs` | WPCS/PHPCompatibilityWP | بررسی static، اثبات اجرای نسخه حداقل PHP نیست |
| `composer run test` | ۸ unit در آخرین `composer run check` گزارش‌شده | ممکن است cache تست محلی بنویسد؛ integration را اجرا نمی‌کند |
| `composer run check` | lint + cs + unit | آماده‌بودن فروشگاه را ثابت نمی‌کند |
| `composer run test:integration-all` | ۱۲ integration فعلی شامل uninstall | WordPress را load می‌کند و fixture/کاربر/محصول می‌سازد و پاک می‌کند؛ فقط محیط محلی QA |
| `composer run test:all` | check + integration-all | شامل E2E مرورگر نیست؛ ممکن است DB را تغییر دهد |
| `npm run test:e2e` | Chromium smoke | fixture می‌سازد و برای بازبینی نگه می‌دارد؛ در اجرای بعد fixtureهای علامت‌دار قبلی پاک می‌شوند |
| `npm run test:e2e:headed` | همان تست با مرورگر دیدنی | QA بصری دستی مستقل همچنان لازم است |
| `npm run e2e:cleanup` | حذف fixtureهای E2E | عملیات حذف DB است؛ تست read-only نیست |

شواهد اجرای ۲۰۲۶-۱۰-۲۸: `composer run test:all` با exit 0 تمام شد؛ lint و PHPCS روی ۲۸ فایل، PHPUnit با ۸ تست/۱۳ assertion و هر ۱۲ integration (شامل uninstall) پاس شدند. `npm run check:js` نیز exit 0 داشت. E2E run=`2a533f5a6c48`، ۱۴/۱۴ سناریو را پاس کرد و `npm run e2e:cleanup` با exit 0 fixtureها را پاک کرد. جزئیات و محدودیت‌ها در [گزارش QA پایلوت](pilot-qa-fa.md) است.

برای unit بدون نوشتن cache نتیجه:

```powershell
php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml.dist --do-not-cache-result
php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml.dist
```

برای integration/E2E روی محیط محلی QA؛ آدرس زیر مسیر محیط فعلی است:

```powershell
$env:HAMKHARID_WP_ROOT='D:\laragon\www\wordpress'
composer run test:all
npm run test:e2e
```

اسکریپت‌های integration از `wp-load.php` همین root استفاده می‌کنند. پاک‌سازی finally معادل rollback تضمین‌شده نیست؛ قطع فرآیند می‌تواند داده بگذارد. fixture مرورگر userهای Administrator/Subscriber و محصول متغیر می‌سازد؛ خروجی خام `fixture.php setup` حاوی اطلاعات ورود موقت است و نباید در report یا Git ذخیره شود. runner JSON را داخلی مصرف می‌کند.

راهنمای سناریوها: [QA دستی](manual-qa-fa.md). اجرای `e2e:cleanup` فقط پس از پایان بازبینی و اطمینان از root درست انجام شود؛ marker=`_hamkharid_e2e_run` مبنای پاک‌سازی است. داده آزمایشی را با داده واقعی مخلوط نکنید.

## روال تغییر آینده

1. task و FR/NFR، تصمیم و baseline کد را مشخص کنید. تغییرات دیگران را حفظ کنید.
2. diagnostic/تست متناسب با رفتار واقعی آماده کنید؛ برای تغییر ذخیره WP و cart، سناریوی HTTP/DB بر تست صرف متد داخلی اولویت دارد.
3. کمترین تغییر در مالک‌های [معماری](architecture-fa.md)؛ قرارداد قدیمی و ارتقا را لحاظ کنید.
4. تست متمرکز و سپس gate مرتبط را اجرا کنید؛ پس از موفقیت، اجرای اضافی فقط با علت جدید.
5. اسناد متأثر و وضعیت را با شاهد به‌روز کنید. commit/release تنها در دامنه مجاز اجرای بعدی است.

## ماتریس پذیرش

نمونه مهندسی پیشنهادی برای اثبات D-08: Storefront کلاسیک، یک قالب بلوکی استاندارد نصب‌شده، یک قالب کلاسیک دیگر در صورت دسترسی، ناحیه ابزارک موجود و صفحه‌ساز دارای shortcode. انتخاب نمونه‌ها allowlist محصول نیست. نسخه دقیق WP/WC/PHP/theme/builder، زبان، HPOS و cache در هر run ثبت شود. اگر builder در دسترس نیست، مسیر آن «آزموده‌نشده» می‌ماند و gate مربوط بسته نیست.

دو زبان fa_IR/en_US، مهمان و مدیر و shop manager و subscriber، mobile/desktop و JS روشن/خاموش روی مسیرهای اصلی پوشش داده شوند. حداقل‌های سربرگ و نسخه جاری پشتیبانی‌شده به‌عنوان jobهای واقعی تعریف شوند؛ نسخه جاری از حدس یا خروجی قدیمی انتخاب نشود. تغییر حداقل پشتیبانی تصمیم سازگاری جدا می‌خواهد.

## ساخت محلی ZIP پایلوت؛ آزمون بسته هنوز pending است

1. بسته را پس از بازبینی diff و ثبت QA بسازید؛ ZIP فقط در مسیر محلی خروجی قرار می‌گیرد. ساخت/نصب/upgrade مستقل از source QA باقی می‌ماند تا گزارش M4 ثبت شود؛ benchmark PRD نیز gate باز است.
2. `scripts/package-pilot.ps1` در staging موقت، وابستگی‌ها را با lock و `composer install --no-dev --optimize-autoloader` می‌سازد و runtime allowlist را بسته‌بندی می‌کند. اسکریپت به‌طور پیش‌فرض dirty checkout را رد می‌کند؛ `-AllowDirty` فقط برای artifact محلی علامت‌خورده است.
3. بسته شامل `hamkharid.php`، `src/`، assetهای runtime، ترجمه‌ها، autoloader و وابستگی‌های runtime، `LICENSE`، `readme.txt` و `CHANGELOG.md` است. فایل‌های JS فعلی مستقیم runtime هستند و build step جداگانه ندارند.
4. `.git`، `.idea`، node_modules، tests، fixture، خروجی حساس، cache و index تولیدشده وارد ZIP نمی‌شوند؛ اسکریپت staging این موارد را رد می‌کند.
5. ZIP با پوشه بالایی `hamkharid/` روی سایت QA تمیز نصب شود؛ نبود vendor در مقصد، dependency ناقص و نبود WC بدون fatal و با پیام درست بررسی شوند.
6. ارتقای نسخه موجود به بسته، حفظ قواعد و defaults، جای‌گذاری دستی/خودکار و uninstall با حفظ/پاک‌سازی انتخابی تست شود؛ محصول/دسته/سفارش تغییر نکند.
7. manifest شامل نسخه، checksum ZIP و فایل‌ها، revision، dirty state، گزارش QA ارسالی و راهنمای rollback ساخته می‌شود. هنوز artifact یا checksum واقعی تولید نشده است.
8. انتشار ZIP به پایلوت طبق D-09؛ ارسال به کاربران یا انتشار بیرونی نیازمند مجوز همان اقدام است. انتخاب مارکت/مدل تجاری بعد از پایلوت باز است.

Rollback هدف: غیرفعال‌سازی افزونه باید افزودن عادی WC را سالم نگه دارد؛ بازگشت بسته قبلی با backup DB و schema سازگار در محیط QA تمرین شود. وجود default برای meta جدید جای آزمون downgrade را نمی‌گیرد. نسخه افزونه و assetها، readme/changelog و ترجمه هنگام release واقعی همسان شوند.

## ثبت شاهد

برای هر run این اطلاعات را کنار QA نتیجه ثبت کنید: تاریخ، commit و diff محلی، محیط/locale/HPOS، فرمان، تعداد و نتیجه، خطا، screenshot یا log بدون secret، موارد Skip با علت و تصمیم پذیرش.

شواهد ثبت‌شده در این اجرا (۲۰۲۶-۱۰-۲۸) در [گزارش QA پایلوت](pilot-qa-fa.md) آمده است. `composer run test:all`، `npm run check:js` و آخرین E2E سبز هستند. ZIP و install/upgrade از ZIP، Elementor/builder، widget areas، zoom ۲۰۰٪، screen reader، HPOS خاموش، WooCommerce غایب، Shop Manager، multi-embed duplicate و benchmark مقیاس هنوز آزموده نشده‌اند.
