# راهنمای توسعه، آزمون و انتشار

مرجع اصلی: [فهرست مستندات](README.md). این سند فرمان‌ها و شواهد اجرای محلی QA را توضیح می‌دهد. کاربر اجرای محلی و داده آزمایشی را مجاز کرده است؛ به هاست یا داده واقعی دست زده نمی‌شود. انتشار عمومی، استقرار یا ارسال ZIP نیازمند مجوز جداگانه‌اند.

## محیط و وابستگی

- ریشه فعلی: `D:\laragon\www\hamkharid`؛ shell فعلی PowerShell است.
- نسخه‌های محیط محلی بررسی‌شده: PHP 8.4.1، WordPress 7.1.3، WooCommerce 11.2.1، Node 26.7.0، npm 11.19.0 و Composer 2.8.3؛ قالب فعال Luxury Shop 1.9.4، locale=`fa_IR`/RTL و HPOS روشن. این ماتریس ادعای پوشش همه نسخه‌های سربرگ نیست.
- PHP CLI، PHP وب و تنظیمات extension آن‌ها ممکن است متفاوت باشند. مسیر پوشه Laragon اثبات نمی‌کند PHP از Laragon اجرا می‌شود؛ در بررسی قبلی CLI از `C:\xampp\php\php.exe` resolve شد، پیش از اجرا دوباره بررسی شود.
- WordPress/WooCommerce و DB محلی برای integration لازم‌اند؛ root وردپرس خارج از ریشه افزونه است. نام DB، حساب و رمز در مستندات/مخزن ثبت نشوند.
- runtime به `vendor/autoload.php` نیاز دارد. ابزارهای dev از `composer.lock` و Playwright از `package-lock.json`/`package.json` می‌آیند. دسترسی اسناد و ابزارهای لازم در clone تمیز `a075c21fb10c359622eb96dc9b36cd7000daa766` تأیید شد؛ `git check-ignore` آن‌ها را ignoreشده گزارش نکرد. اصلاح `.gitignore` در `da914d3` ثبت شده است.

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

روی source/test commit `5d468e0c2909f3ef78088ebeccae8db008e33766` (tracked و pushed)، `composer run test:all` exit 0 داشت: PHPCS 28/28، PHPUnit 8/8 با 13 assertion، integration 12/12. `npm run check:js` و `node --check` نیز پاس شدند. E2E `1aa89a2c28a1` با Browser QA 17/17 پاس شد؛ شامل دسترسی واقعی Shop Manager به rules/settings، Subscriber denial، و axe-core 4.13.0 برای tagهای WCAG 2.0/2.1/2.2 A/AA در `en_US`/LTR و `fa_IR`/RTL بدون violation. E2E اجرا شد پیش از commit؛ منطق تست همان است و تفاوت commit فقط whitespace اصلاح‌شده با PHPCBF است. Screenshot review در 1200/420px کارت و layout را تأیید کرد؛ `npm run e2e:cleanup` exit 0 شد و fixtureها پاک شدند. QA ثبت‌شده شامل HPOS روشن/خاموش، WooCommerce فعال/غیرفعال، widget area و multi-embed است. Elementor/browser rendering و screen reader هنوز بازند. ZIP متناظر با commit هنوز rebuild/lifecycle نشده و SHA آن pending است. جزئیات در [گزارش QA پایلوت](pilot-qa-fa.md) آمده است.

### ثبت اجرای benchmark و گیت محلی NFR-02

ماتریس full-scale در ۲۰۲۶-۱۰-۲۸ روی Windows 11 Pro 10.0.26300، Intel Core i5-9500 @ 3.00 GHz، WP 7.1.3، WC 11.2.1، PHP 8.4.1 و MariaDB 10.4.32 اجرا شد؛ دیتابیس synthetic جدا با 61k simple product و 3k rule داشت و external object cache فعال نبود. ماتریس ۱k/۱۰k/۵۰k × ۱۰/۱۰۰/۱۰۰۰ با ۳۰ cold و ۳۰ warm نمونه برای matcher و هر sort کامل شد. Cold یعنی `wp_cache_flush()` پیش از نمونه؛ MariaDB buffer pool restart نشد و warm ماند.

بر اساس انتخاب مهندسی E-15، matcher p95 ≤125ms cold/≤10ms warm؛ recommendations ordered/random در 1k به‌ترتیب ≤125/50 و ≤250/125ms، در 10k ≤200/75 و ≤550/150ms، در 50k ≤750/75 و ≤1200/600ms؛ و peak matrix memory ≤120MiB تعیین شد. بیشینه‌های مشاهده‌شده به‌ترتیب matcher 107.08/7.06ms؛ ordered/random در 1k برابر 103/33.67 و 224.35/104.33ms، در 10k برابر 195.8/72.48 و 514.31/144.34ms، در 50k برابر 682.56/42.91 و 1143.92/568.68ms؛ حافظه 96MiB بودند. همهٔ بیشینه‌ها در حد محلی منتخب هستند؛ بنابراین NFR-02 فقط برای همین local envelope PASS است، نه SLA عمومی یا محصولی و نه تأیید کاربر.

SQLهای ساختاری مشاهده‌شده: matcher سرد 28 با 10 rule و 108 با 100/1000 rule، warm صفر؛ recommendations ordered سرد/گرم 16/0 و random 18/4. Variable-heavy با 100 parent/12k variation و cap 500، فقط n=5 و exploratory بود: 841–870ms cold/244 SQL و 574–602ms warm/0 SQL. uncapped pre-fix از timeout 120s عبور کرد و نتیجهٔ مقایسه‌پذیر تولید نکرد. گزارش، raw matrix و variable samples بیرون مخزن در [گزارش QA پایلوت](pilot-qa-fa.md) ثبت‌اند. دیتابیس پاک شد (۵۲ جدول `wpperf_` حذف شدند)؛ فایل‌های clone به‌دلیل مسدودشدن حذف recursive در PowerShell باقی ماندند.

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

## ساخت محلی ZIP پایلوت؛ QA بسته پاس شده است

1. بسته را پس از بازبینی diff و ثبت QA بسازید؛ ZIP فقط در مسیر محلی خروجی قرار می‌گیرد. ساخت/نصب/upgrade مستقل از source QA انجام می‌شود؛ artifact جاری چرخه QA را گذرانده است.
2. `scripts/package-pilot.ps1` در staging موقت، وابستگی‌ها را با lock و `composer install --no-dev --optimize-autoloader` می‌سازد و runtime allowlist را بسته‌بندی می‌کند. timestamp فایل‌های ZIP ثابت است تا build با source و lock یکسان hash یکسان داشته باشد؛ زمان ساخت واقعی فقط در manifest می‌آید. اسکریپت به‌طور پیش‌فرض dirty checkout را رد می‌کند؛ `-AllowDirty` فقط برای artifact محلی علامت‌خورده است.
3. بسته شامل `hamkharid.php`، `src/`، assetهای runtime، ترجمه‌ها، autoloader و وابستگی‌های runtime، `LICENSE`، `readme.txt` و `CHANGELOG.md` است. فایل‌های JS فعلی مستقیم runtime هستند و build step جداگانه ندارند.
4. `.git`، `.idea`، node_modules، tests، fixture، خروجی حساس، cache و index تولیدشده وارد ZIP نمی‌شوند؛ اسکریپت staging این موارد را رد می‌کند.
5. ZIP با پوشه بالایی `hamkharid/` روی سایت QA تمیز نصب شود؛ نبود vendor در مقصد، dependency ناقص و نبود WC بدون fatal و با پیام درست بررسی شوند.
6. ارتقای نسخه موجود به بسته، حفظ قواعد و defaults، جای‌گذاری دستی/خودکار و uninstall با حفظ/پاک‌سازی انتخابی تست شود؛ محصول/دسته/سفارش تغییر نکند.
7. manifest شامل نسخه، checksum ZIP و فایل‌ها، revision، dirty state، گزارش QA و راهنمای rollback باشد. artifact پیشین از source تمیز `7693821`، hash manifest برابر با SHA ZIP و دو build یکسان داشت؛ برای commit جاری `5d468e0c2909f3ef78088ebeccae8db008e33766` باید artifact تازه build و lifecycle شود. جزئیات در [گزارش QA پایلوت](pilot-qa-fa.md) آمده است.
8. انتشار ZIP به پایلوت طبق D-09؛ ارسال به کاربران یا انتشار بیرونی نیازمند مجوز همان اقدام است. انتخاب مارکت/مدل تجاری بعد از پایلوت باز است.

شواهد M4 artifact پیشین: ZIP با SHA-256 `d6006eb80ebcaca16794ff55d73f873b72a46e07387323c3ccc056ea36304053` و source `7693821` در clone ایزوله نصب، ارتقا، uninstall، rollback و دوباره نصب شد؛ default/opt-in data behavior مطابق انتظار بود و fixtureها پاک شدند. downgrade به `0.0.9` با header marker مصنوعی شبیه‌سازی شد، نه با ZIP تاریخی. پس از اصلاح کنتراست کارت، این SHA دیگر artifact جاری را مشخص نمی‌کند؛ build و lifecycle مجدد روی ZIP تازه تا دریافت شواهد pending است. گزارش قبلی در [گزارش QA پایلوت](pilot-qa-fa.md) ثبت شده است.

Rollback هدف: غیرفعال‌سازی افزونه باید افزودن عادی WC را سالم نگه دارد؛ بازگشت بسته قبلی با backup DB و schema سازگار در محیط QA تمرین شود. وجود default برای meta جدید جای آزمون downgrade را نمی‌گیرد. نسخه افزونه و assetها، readme/changelog و ترجمه هنگام release واقعی همسان شوند.

## ثبت شاهد

برای هر run این اطلاعات را کنار QA نتیجه ثبت کنید: تاریخ، commit و diff محلی، محیط/locale/HPOS، فرمان، تعداد و نتیجه، خطا، screenshot یا log بدون secret، موارد Skip با علت و تصمیم پذیرش.

شواهد و موارد آزموده‌نشده در [گزارش QA پایلوت](pilot-qa-fa.md) آمده‌اند. Suite PHP/JS پاس است؛ E2E 17/17 و axe-core دو locale نیز پاس شدند. commit جاری `5d468e0c2909f3ef78088ebeccae8db008e33766` tracked و pushed است؛ E2E پیش از commit روی همان منطق تست اجرا شد و commit فقط whitespace را با PHPCBF مرتب کرد. ZIP قبلی lifecycle را پاس کرد؛ artifact تازه و lifecycle مجدد pending است. benchmark فقط در local envelope منتخب E-15 پاس است، نه SLA عمومی/محصولی. Elementor/browser rendering، screen reader و compatibility گسترده بازند؛ HPOS دو حالت، WooCommerce فعال/غیرفعال، widget area و multi-embed پوشش دارند.
