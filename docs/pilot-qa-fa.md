# گزارش QA محلی هم‌خرید

تاریخ بازبینی: ۲۰۲۶-۱۰-۲۸. commit `5d468e0c2909f3ef78088ebeccae8db008e33766` tracked و pushed است. پس از آن، CSS گرید در دو مورد اصلاح شد و regression test مرورگری افزوده شد؛ اجرای نهایی E2E `551465d97590` این تغییرها را ۱۷/۱۷ پاس کرد. ZIP SHA-256 `9d6b427da69510ceb89e82cc29d644219615b55e6c4ca54e54ca393cf71c6ee6` پیش از این اصلاحات lifecycle شد و artifact جاری محسوب نمی‌شود؛ جزئیات و محدودیت‌ها در بخش lifecycle قبلی و addendum M3 آمده‌اند.

## نتیجه

روی code/test commit `5d468e0c2909f3ef78088ebeccae8db008e33766`، `composer run test:all` با exit 0 تمام شد: PHPCS ۲۸/۲۸، PHPUnit ۸/۸ با ۱۳ assertion و integration برابر ۱۲/۱۲. `npm run check:js` و `node --check` نیز پاس شدند. E2E run=`1aa89a2c28a1`، Browser QA را ۱۷/۱۷ گذراند؛ اجرا پیش از commit بود و منطق تست یکسان است، با تفاوت صرفاً whitespace اصلاح‌شده با PHPCBF. پوشش شامل QA-097، QA-130 دسترسی واقعی Shop Manager به rules/settings، Subscriber denial و axe-core 4.13.0 در `en_US`/LTR و `fa_IR`/RTL بود. axe با tagهای `wcag2a`, `wcag2aa`, `wcag21a`, `wcag21aa`, `wcag22a`, `wcag22aa` تخلفی گزارش نکرد. بازبینی بصری screenshotهای ۱۲۰۰px و ۴۲۰px، contrast کارت و layout را تأیید کرد. fixtureها پس از اجرا پاک شدند. HPOS روشن/خاموش، WooCommerce فعال/غیرفعال، widget area و multi-embed نیز در QAهای ثبت‌شده پوشش دارند؛ Elementor page-level UI/render پس از shim QA ثبت شد، اما clean/default Elementor و Theme Builder تأیید نشده‌اند. کاربر گزارش Narrator را برای QA-207 اعلام کرد. M4 lifecycle artifact جاری SHA `7f4868b8bd54fd772d4bbf5fb8c5ef6044b4c028baf8c9de400c270dc62f884b` از build تمیز و reproducible نیز pass شد؛ جزئیات در addendum M4 آمده است. NFR-02/E-15 فقط در envelope محلی اندازه‌گیری‌شده PASS است و SLA عمومی نیست.

## محیط

| مورد | مقدار |
|---|---|
| WordPress | 7.1.3 |
| WooCommerce | 11.2.1 |
| PHP | 8.4.1 |
| Node / npm / Composer | 26.7.0 / 11.19.0 / 2.8.3 |
| قالب | Luxury Shop 1.9.4 (کلاسیک) و Twenty Twenty-Five (قالب بلوکی) |
| زبان/جهت | `en_US`/LTR و `fa_IR`/RTL |
| HPOS | روشن |
| مرورگر | Chromium با Playwright |

این QA روی WordPress محلی و fixtureهای آزمایشی اجرا شد. داده fixtureها پس از اجرا پاک شدند؛ به هاست یا داده واقعی دست زده نشد.

## آزمون‌های اجراشده

| فرمان | نتیجه |
|---|---|
| `composer run test:all` (commit `5d468e0`) | exit 0؛ lint، PHPCS 28/28، PHPUnit 8/8 با 13 assertion، integration 12/12 |
| `npm run check:js` و `node --check` | هر دو exit 0 |
| `npm run test:e2e` | run `1aa89a2c28a1`؛ Browser QA 17/17، شامل QA-097، QA-104 و دسترسی واقعی Shop Manager در QA-130 |
| axe-core 4.13.0 | صفر تخلف در `en_US`/LTR و `fa_IR`/RTL برای tagهای `wcag2a/aa`, `wcag21a/aa`, `wcag22a/aa` |
| Screenshot review | 1200px desktop و 420px mobile؛ contrast کارت و layout بازبینی بصری شدند |
| `npm run e2e:cleanup` پس از run | exit 0؛ fixtureها پاک شدند |

در E2E با شناسه `1aa89a2c28a1` موارد زیر روی working tree پیش از commit `5d468e0` پوشش داده شدند؛ منطق تست با نسخهٔ commit‌شده یکسان است و فقط whitespace اصلاح‌شده با PHPCBF تفاوت دارد:

- Admin: QA-005/006 و QA-010 تا QA-019.
- Matching/rendering: QA-032/033، QA-050 تا QA-057، QA-070/071/076 و QA-145/146.
- خرید بومی: QA-080/081 و QA-083 تا QA-085.
- واکنش‌گرایی: QA-093 تا QA-097؛ مورد QA-097 با عرض مؤثر ۳۲۰ CSS px و zoom صفحهٔ ۲۰۰٪ اجرا شد.
- دسترس‌پذیری smoke: QA-100 تا QA-104؛ keyboard/focus، نام‌گذاری بخش، contrast متن/قیمت/دکمه و axe-core 4.13.0 با صفر violation برای tagهای WCAG 2.0/2.1/2.2 A/AA.
- مجوز: QA-130/131.
- جای‌گذاری: QA-205؛ حالت دستی و خاموش‌بودن auto placement، preview مدیریت، shortcode و dynamic block در editor/frontend روی قالب کلاسیک و Twenty Twenty-Five.

QAهای سازگاری ثبت‌شدهٔ جداگانه شامل HPOS روشن و خاموش، WooCommerce فعال و غیرفعال، ناحیهٔ widget موجود و چند embed/duplicate behavior است. در run=`1aa89a2c28a1`، حساب واقعی Shop Manager دسترسی rules/settings گرفت و Subscriber denied شد.

زبان انگلیسی/LTR و فارسی/RTL در مرورگر اجرا شد. screenshotهای responsive خارج از مخزن نگهداری شده‌اند و مرور بصری layoutهای responsive انجام شد:

- `hamkharid-e2e-final-desktop-1200.png`
- `hamkharid-e2e-final-mobile-420.png`
- `hamkharid-e2e-rtl-desktop-1200.png`
- `hamkharid-e2e-rtl-mobile-420.png`

مسیر محلی پوشه شواهد: `C:\Users\HP\.codex\visualizations\2026\10\27\01a17ea3-2fb8-7403-b60e-3f15d61b0241\qa`.

## اجرای تکمیلی M3 — محتوا، جهت و عرض

در ۲۰۲۶-۱۰-۲۸، روی WordPress محلی `wordpress.test` و fixture مصنوعی E2E با run=`b12cf7b46acf`، محصول توصیه‌شدهٔ fixture فقط برای آزمون محتوای بلند تغییر موقت کرد؛ نام ترکیبی فارسی/لاتین/عدد و SKU برابر `SKU-FA-EN-2026-ABCD-1234567890` داشت. خود fixture محافظت‌شده با `_hamkharid_e2e_run` بود؛ بعد از بررسی cleanup اجرا می‌شود. هیچ داده‌ای از هاست یا فروشگاه واقعی استفاده نشد.

| عرض CSS | ستون کارت | عرض section/card | عرض سند | نتیجه |
|---:|---:|---:|---:|---|
| ۳۲۰px | ۱ | ۲۴۸px | ۳۲۰px | عنوان بلند می‌شکند؛ قیمت و دکمه داخل کارت می‌مانند |
| ۴۲۰px | ۱ | ۳۴۸px | ۴۲۰px | بدون overflow |
| ۷۰۰px | ۲ | ۲۲۲px هر کارت | ۷۰۰px | متن مختلط و CTA بدون بریدگی |
| ۱۲۰۰px | ۳ | ۲۴۹px هر کارت | ۱۲۰۰px | متن و CTA داخل کارت می‌مانند |

در `fa_IR`, صفحه HTML و section هر دو RTL بودند؛ در `en_US`، `<html lang="en-US">` و section LTR شد و عنوان فارسی/لاتین در کارت همچنان قابل‌خواندن ماند. قیمت تست‌شده از WooCommerce با raw/regular price برابر `29` و currency=`IRR` آمد؛ renderer افزونه تبدیل مجددی به تومان/ریال انجام نداد. صفحهٔ محصول همان دادهٔ عنوان/SKU را در ۳۲۰/۴۲۰/۷۰۰/۱۲۰۰px نشان داد، `documentElement.scrollWidth` برابر viewport ماند و دکمهٔ اصلی ۱۵۷px عرض داشت. در traversal واقعی Tab، لینک محصول و دکمه‌های کارت در Tabهای ۲۳–۲۸ قابل‌دسترسی و outline محاسبه‌شده `solid 2px` بود؛ نام accessible دکمه عنوان کامل محصول را داشت. این شاهد DOM/keyboard است و تأیید گفتاری screen reader محسوب نمی‌شود.

شواهد بیرون مخزن: `m3-responsive-long-content.txt`, `m3-product-detail-metrics.txt`, `m3-keyboard-product-section.txt`, `m3-section-long-content-320-verified.png`, `m3-section-long-content-700.png`, `m3-section-long-content-700-ltr.png`, `m3-section-long-content-1200.png` و `m3-user-visual-confirmation.png` در پوشهٔ QA بالا. تصویر آخر و گزارش کاربر سه کارت، عنوان بلند و CTA در گرید RTL دسکتاپ بدون overflow را تأیید می‌کنند.

### بازآزمایی رفع نقص، قالب‌ها و builder — ۲۰۲۶-۱۰-۲۸

در clone مستقل M4 با WordPress 7.1.3، WooCommerce 11.2.1، PHP 8.4.1 و Twenty Twenty-Five، اجرای E2E `551465d97590` پس از دو اصلاح CSS (جعبه‌بندی تصویر placeholder و حذف markerهای فهرست) **۱۷/۱۷** گذراند. اندازه‌گیری عنوان/SKU بلند در ۳۲۰/۴۲۰/۷۰۰/۱۲۰۰px، document width برابر viewport و overflow داخلی صفر بود؛ سه کارت حفظ شدند. regression تازه در E2E مقدار `list-style-type: none` را نیز کنترل می‌کند. تصاویر در `m3-final-artifacts/hamkharid-551465d97590-{420,700,1200}.png` و log در `m3-final-e2e-php84.log` هستند.

برای Storefront 4.6.2، قالب در clone فعال شد و E2E کامل `4d1a0a7d67cb` **۱۷/۱۷** گذراند؛ شامل صفحهٔ محصول کلاسیک، auto placement یکتا با سه recommendation واجد شرایط، خرید native، اندازه‌های responsive، keyboard و axe در دو locale. screenshotهای `m3-storefront-final-e2e/hamkharid-4d1a0a7d67cb-{420,700,1200}.png` بصری بازبینی شدند. این run QA-110 را می‌بندد.

PHP 8.1.10، WordPress 7.1.3، WooCommerce 11.2.1 و Twenty Twenty-Five: full PHP suite پس از تنظیم fixture به حالت automatic پاس شد (PHPCS 28/28، PHPUnit 8/8 با 13 assertion، integration 12/12) و E2E پس از رفع overflow تصویر ۱۷/۱۷ پاس شد. تغییر نهایی marker فهرست در همین clone PHP 8.4 تست شد، نه در PHP 8.1.

Storefront 4.6.2 در همان clone با صفحهٔ shortcode بررسی شد: سه کارت در چهار عرض، سرریز سند/section صفر و list marker برابر `none`. این نتیجه، بررسی classic single-product hook در Storefront نیست. سابقهٔ Elementor 4.3.5 در گزارش قدیمی به artifact/source قبلی مربوط است و شاهد پذیرش source جاری نیست؛ نتیجهٔ تازه و محدود source جاری در انتهای همین گزارش آمده است. شواهد تاریخی بیرون مخزن: `m3-storefront-responsive.txt`, `m3-elementor-browser-evidence.txt`, `m3-elementor-editor.png`, `m3-elementor-frontend.png` و `m3-dynamic-block-frontend.png`.

در clone Storefront، fixtureها پاک شدند؛ زبان به `en_US`، قالب به Twenty Twenty-Five و تنظیم mode به manual برگشت؛ markerهای fixture صفر و فقط Hamkharid/WooCommerce فعال بودند. قالب Storefront و WordPress `fa_IR` language pack رسمی (فایل‌هایی که در clone پیش‌تر غایب بودند) برای تکرار QA در clone باقی ماندند. اصلاح `tests/e2e/fixture.php` اکنون option زبان را وقتی از ابتدا وجود ندارد می‌سازد و در cleanup همان فقدان را restore می‌کند؛ همچنین runner پیش از E2E تنظیم mode قبلی را snapshot می‌کند، automatic را برای صفحهٔ محصول فعال می‌کند و در cleanup mode قبلی را برمی‌گرداند. پیش از اصلاح، switch-locale در clone بدون `fa_IR.mo` نمی‌توانست locale را عوض کند و صفحهٔ محصول در سایت تازه با تنظیم manual، خودکار section نداشت. اجرای main fixture کاربر روی `wordpress.test` پس از گزارش Narrator پاک شد.

### QA-112 — قالب بلوکی Single Product

در clone مستقل `zip-install` با WP 7.1.3، WC 11.2.1 و Twenty Twenty-Five، درخواست `get_block_template('woocommerce//single-product', 'wp_template')` به `twentytwentyfive//single-product` با `source=plugin` حل شد؛ این نشان می‌دهد محصول از fallback blockified ووکامرس render شده است. E2E=`9d3c296f10e1` برابر ۱۷/۱۷ شد. صفحهٔ محصول، auto placement یکتای سه محصول، خرید native، responsive، keyboard و axe را گذراند. screenshotهای `m3-block-template-e2e/hamkharid-9d3c296f10e1-{420,700,1200}.png` ثبت شدند و نمای ۱۲۰۰px بصری مرور شد. جزئیات template در `m3-block-template-e2e/template-resolution.txt` است. Clone پس از اجرا پاکسازی شد: fixtureهای محصول/صفحه/rule/user صفر، mode=`manual` و theme=`twentytwentyfive`.

## موارد آزموده‌نشده و gateهای باز

- Product Template در Elementor Theme Builder بنا به تصمیم کاربر آزموده‌نشده است. Elementor 4.3.5 صفحهٔ معمولی/Shortcode widget را از UI ساخت و ذخیره کرد، اما فقط پس از shim محلی برای autoload داخلی MCP؛ clean/default Elementor pass نیست. SiteOrigin 2.38.0 در Classic Page Builder/Text widget مسیر Code→Visual، ذخیره، reload و frontend را گذراند؛ بازآزمایی نهایی در انتهای گزارش ثبت شده است. SiteOrigin Gutenberg Layout Block UI آزموده‌نشده است.
- screen readerهای دیگر؛ کاربر Narrator را برای QA-207 تأیید کرده است. smoke keyboard/focus و سنجش zoom معادل ارزیابی WCAG کامل نیستند.
- ماتریس گسترده نسخه‌های WordPress/WooCommerce/PHP و مرورگرها.
- پذیرش عمومی/محصولی performance: گیت محلی E-15 را پاس کرده‌ایم؛ این نتیجه SLA عمومی نیست. مقایسه با محصولات/محیط‌های دیگر یا تعهد عملکردی سراسری خارج از این شاهد است.
- موارد باقی‌مانده برای آمادگی کلی: Elementor Theme Builder و دامنهٔ سازگاری گسترده آزموده/تأیید نشده‌اند. QA-207 Narrator طبق گزارش کاربر پاس است. M4 برای artifact ثبت‌شده پاس شد؛ این به‌تنهایی تأیید عمومی یا اعلام آمادگی کلی پایلوت نیست.

### شواهد اولیه M4 روی package قبلی

ZIP پیشین با SHA-256 `d026f297d533047b64f00faf884ccc8534b37c62513c0c242cb2051f2a512e4c` و source revision `da914d3257b24564a5aafaeea939d4dd07c05fe2` در clone مستقل QA بررسی شد. WordPress uploader، نصب تمیز/فعال‌سازی، تنظیم manual نصب تازه، shortcode، auto placement در upgrade، حفظ داده‌ها، uninstall پیش‌فرض و opt-in، و پاک‌سازی fixtureها پاس شدند؛ manifest با ZIP hash منطبق بود. محیط clone: WordPress 7.1.3، WooCommerce 11.2.1، PHP 8.4، Twenty Twenty-Five و `en_US`. گزارش خام در `C:\Users\HP\.codex\visualizations\2026\10\27\01a17ea3-2fb8-7403-b60e-3f15d61b0241\qa\zip-install\zip-qa-evidence.txt` و تصاویر در همان پوشه هستند.

این ZIP پیشین از revision پیش از اصلاح variation ساخته شده؛ شاهد چرخه‌عمر package نهایی نیست. به‌روزرسانی آن نیز با تغییر header به 0.0.9 شبیه‌سازی شده بود. artifact بعدی از source تمیز `7693821` ساخته شد؛ SHA-256 آن `d6006eb80ebcaca16794ff55d73f873b72a46e07387323c3ccc056ea36304053` بود، manifest با hash منطبق بود، `source_dirty=false` بود و runtime autoloader و `uninstall.php` داخل ZIP بودند. دو build تکراری hash یکسان دادند. این artifact پیش از contrast fix است و lifecycle جاری را نمایندگی نمی‌کند. artifact تاریخی در `C:\Users\HP\.codex\visualizations\2026\10\27\01a17ea3-2fb8-7403-b60e-3f15d61b0241\qa\release\hamkharid-0.1.0-pilot.zip` ثبت شده است.

### شواهد M4 برای ZIP پایلوت جاری

artifact SHA-256 `7f4868b8bd54fd772d4bbf5fb8c5ef6044b4c028baf8c9de400c270dc62f884b`، version `0.1.0`، از checkout تمیز revision `6e9f94d7e5dffb31cf2697104cd23ebfda1ce201` build شد. دو اجرای مستقل `scripts/package-pilot.ps1` hash یکسان دادند؛ manifest hash را تأیید کرد و `source_dirty=false` بود. ZIP شامل ۳۶ فایل، `hamkharid/` در ریشه، `vendor/autoload.php` و `uninstall.php` بود و tests/node_modules/.git در آن نبود. بسته در clone مستقل WordPress 7.1.3/WooCommerce 11.2.1/PHP 8.4.1/Twenty Twenty-Five بررسی شد.

WordPress core `Plugin_Upgrader` ZIP دقیق را پس از uninstall پیش‌فرض نصب و فعال کرد؛ تنظیمات/rule و محصولات/دسته‌ها/سفارش synthetic باقی ماندند و در درخواست تازه shortcode یک section/هدف/CTA رندر کرد. update با تغییر header نصب‌شده به marker مصنوعی `0.0.9` و `overwrite_package=true` شبیه‌سازی شد؛ version به `0.1.0` برگشت و داده/تنظیمات ماندند. uninstall پیش‌فرض rule/settings و داده‌های WooCommerce را حفظ کرد؛ opt-in فقط rule/settings را پاک کرد. نصب پس از opt-in با activation عادی mode=`manual` را ساخت. rollback، plugin directory و snapshot ۵۳ جدول/۴۷۰۲ ردیف را بازیابی کرد؛ بررسی پایانی markerهای QA را صفر، locale=`en_US`، theme=`twentytwentyfive` و active plugins را فقط Hamkharid/WooCommerce نشان داد. جزئیات بی‌رمز در `C:\Users\HP\.codex\visualizations\2026\10\27\01a17ea3-2fb8-7403-b60e-3f15d61b0241\qa\release-2026-10-28-current\m4-current-evidence.txt` ثبت شده است.

محدودیت‌ها: update با نسخهٔ تاریخی واقعی و downgrade آزموده نشدند؛ browser file chooser/uploader به‌دلیل policy مسیر محلی بررسی نشد. این artifact فقط در مسیر QA محلی ساخته شد؛ هیچ استقرار، انتشار یا ارسالی انجام نشد. نتیجهٔ native `fa_IR` RTL و E-15 در بخش‌های تاریخی خود ثبت است و به lifecycle این ZIP تعمیم داده نمی‌شود.

## یافته تازه ممیزی کد و وضعیت بازآزمایی

بازبینی تازه نشان داد variable productها می‌توانند تعداد variation زیادی داشته باشند. برای هر فراخوانی `RecommendationEngine::recommend()` اکنون سقف سراسری ۵۰۰ variation candidate در صفحه‌های حداکثر ۲۴تایی اعمال می‌شود؛ این سقف مستقل از بودجه ۵۰۰ محصول است. engine تعداد variationهای بررسی‌شده و رسیدن به سقف را در diagnostics می‌دهد، و preview مدیریت هشدار می‌دهد که برخی variable productها ممکن است از خروجی جا افتاده باشند.

برای regression یک fixture مصنوعی ۵۰۱ variation به `integration-performance` اضافه شد. کل `composer run test:all` در source revision `7693821` با exit 0 شد و تمام ۱۲ integration پاس شدند. performance fixture کوچک این زمان‌ها را ثبت کرد: match=3.83ms، newest=8.40ms، best-selling=7.21ms، random=8.43ms. این fixture هدفمند فقط سقف و query bound را می‌آزماید؛ benchmark مقیاس جداگانه در بخش بعد ثبت شده است.

## شواهد benchmark مقیاس

گزارش پس از اصلاح، از revision کامل `7693821d58e1139bc4b1bf45c890aa4b83149d20` با synthetic database/site مجزا اجرا شد. محیط دقیق: Windows 11 Pro 10.0.26300، Intel Core i5-9500 @ 3.00 GHz، WordPress 7.1.3، WooCommerce 11.2.1، PHP 8.4.1، MariaDB 10.4.32؛ synthetic DB با prefix `wpperf_` و بدون external object cache. ماتریس کامل ۱k/۱۰k/۵۰k محصول در برابر ۱۰/۱۰۰/۱۰۰۰ قانون پوشش داده شد؛ matcher و هر sort برای هر حالت ۳۰ نمونه cold و ۳۰ warm داشتند. Cold، flush کردن WP object cache پیش از نمونه است؛ buffer pool دیتابیس در اندازه‌گیری‌ها warm باقی ماند. این گیت فقط برای همین محیط است، نه SLA.

- در ۵۰k محصول، p95 مرتب‌سازی‌های ordered در کد اصلاح‌شده ۵۳۴–۶۸۳ms بود؛ baseline پیش از اصلاح ۹۳۷–۱۲۸۶ms ثبت کرده بود. SQL سرد برای ordered از ۱۸ به ۱۶ query رسید؛ warm ordered صفر query داشت.
- random در ۱۰k، بیشترین p95 برابر ۵۱۴ms cold و ۱۴۴ms warm بود؛ در ۵۰k، بیشترین p95 برابر ۱۱۴۴ms cold و ۵۶۹ms warm بود. random در cold برابر ۱۸ و warm برابر ۴ SQL ثبت کرد.
- بیشینه allocation حافظه در ماتریس ۹۶ MiB بود.
- آزمون variable-heavy شامل ۱۰۰ parent و ۱۲٬۰۰۰ variation بود؛ سقف ۵۰۰ اعمال شد. در اجرای اصلاح‌شده، cold برابر ۸۴۱–۸۷۰ms با ۲۴۴ SQL و warm برابر ۵۷۴–۶۰۲ms با صفر SQL بود. اجرای pre-fix از سقف ۱۲۰ ثانیه گذشت و متوقف شد، پس latency آن با اعداد اصلاح‌شده مقایسه‌پذیر نیست.

کاربر گزینهٔ «پذیرش محدود به همین محیط» را برای NFR-02 تأیید کرد. گیت E-15 اعمال شد: matcher سرد/گرم p95 ≤125/10ms؛ recommendationهای ordered/random: در ۱k محصول ≤125/50 و ≤250/125ms، در ۱۰k ≤200/75 و ≤550/150ms، و در ۵۰k ≤750/75 و ≤1200/600ms؛ peak matrix memory ≤120MiB. «سرد» یعنی WP object cache پیش از نمونه flush شد؛ DB buffer pool در تمام نمونه‌ها warm بود. بیشینهٔ ماتریس: matcher سرد/گرم 107.08/7.06ms؛ recommendation ordered/random در ۱k برابر 103/33.67 و 224.35/104.33ms، در ۱۰k برابر 195.8/72.48 و 514.31/144.34ms، در ۵۰k برابر 682.56/42.91 و 1143.92/568.68ms؛ peak memory برابر 96MiB. همهٔ بیشینه‌ها در این حد محلی جا شدند؛ پس NFR-02 در همین محیط/envelope PASS است. این gate عمومی یا تعهد محصولی نیست.

SQL ساختاری ثبت‌شده: matcher سرد 28 query با 10 rule و 108 با 100/1000 rule داشت؛ warm صفر. Recommendation ordered سرد/گرم 16/0، و random سرد/گرم 18/4 query داشت. variable-heavy stress بخشی از گیت E-15 نیست: فقط ۵ نمونه برای ۱۰۰ parent/۱۲٬۰۰۰ variation و سقف ۵۰۰ داشت؛ cold 841–870ms/244 SQL و warm 574–602ms/0 SQL. اجرای uncapped پیش از اصلاح از 120s گذشت و به‌سبب توقف timeout، خط مبنای قابل‌مقایسه‌ای ندارد. این stress شاهد exploratory برای bounded behavior است، نه pass/fail یا p95 پایدار.

دیتابیس QA پاک شد: فقط جدول‌های `wpperf_` حذف شدند (۵۲ جدول). فایل‌های سایت clone باقی مانده‌اند چون سیاست PowerShell حذف بازگشتی را مسدود کرد. گزارش و داده خام بیرون مخزن‌اند: `C:\Users\HP\.codex\visualizations\2026\10\27\01a17ea3-2fb8-7403-b60e-3f15d61b0241\qa\performance\summary-postfix-7693821.md`، `results-7693821.json` و `variable-stress-7693821.json`؛ baseline در `summary.md` و `results-baseline-da914d3.json` همان پوشه است. نتایج فقط به همین محیط محلی/نسخهٔ ثبت‌شده محدودند.

## جمع‌بندی gate

M1 و مسیرهای اصلی M2 بر اساس شواهد پاس‌اند. M3 در دامنهٔ مستندشده تکمیل شد: suite PHP/JS، سه engine مرورگر در stack جاری و حداقل، QA-114/124/205/206، reactivation، multi-embed و گزارش Narrator ثبت شدند. Elementor 4.3.5 UI author/save و frontend برای Shortcode widget صفحهٔ معمولی پس از shim محلی autoload MCP پاس شد؛ clean/default Elementor pass نیست. SiteOrigin 2.38.0 Classic Page Builder/Text widget با Code→Visual، save، reload و frontend pass شد؛ Gutenberg Layout Block UI تأیید نشده است. Theme Builder طبق تصمیم کاربر آزموده‌نشده می‌ماند. native `fa_IR` RTL در clone WP 6.2.14 پس از اصلاح `.mo` نیز پاس شد. fixtureهای run پاک شدند؛ راستی‌آزمایی تازهٔ clone صفر marker page/user، نبود صفحه‌های دستی 739/742/757، theme=`twentytwentyfive`، locale=`en_US` و active plugins فقط Hamkharid/WooCommerce را نشان داد. PHP server پورت 8844 بسته بود و `wordpress.test`/هاست واقعی دست‌نخورده ماند. NFR-02 با E-15 فقط برای همین محیط pass است و SLA عمومی نیست. M4 lifecycle ZIP جاری SHA `7f4868b8bd54fd772d4bbf5fb8c5ef6044b4c028baf8c9de400c270dc62f884b` pass شد؛ artifact فقط محلی است و هیچ انتشار یا ارسالی انجام نشد.

## Addendum M3 — cross-browser، حداقل نسخه و سازگاری

تاریخ اجرا: ۲۰۲۶-۱۰-۲۸. این ماتریس با PHP 8.4.1/WordPress 7.1.3/WooCommerce 11.2.1 و clone حداقل PHP 7.4.33/WordPress 6.2.14/WooCommerce 8.2.0 اجرا شد. نسخهٔ PHP/WP/Woo در هر run از محیط clone خوانده شد؛ قالب clone جاری Twenty Twenty-Five بود و حداقل stack از Twenty Twenty-Three استفاده کرد. Cloneهای مرورگر `WP_HTTP_BLOCK_EXTERNAL=true` داشتند تا تماس WordPress.org باعث timeout نشود.

| Browser engine | Version | Stack | E2E | Coverage highlights |
|---|---:|---|---:|---|
| Chromium | 151.0.7922.34 | WP 7.1.3 / WC 11.2.1 / PHP 8.4.1 | `e99dc306ab14`, 18/18 | rule CRUD, matching/render, cart, responsive/zoom, keyboard/focus, axe, roles, manual preview, block/shortcode, both locales, QA-206 |
| Firefox | 153.0 | WP 7.1.3 / WC 11.2.1 / PHP 8.4.1 | `9c0587b75dfd`, 18/18 | same full browser runner and QA-205/206 |
| WebKit | 26.5 | WP 7.1.3 / WC 11.2.1 / PHP 8.4.1 | `77ff991fe78b`, 18/18 | focus outline, Enter activation and axe passed; Tab traversal was not claimed in this WebKit environment |
| Chromium | 151.0.7922.34 | WP 6.2.14 / WC 8.2.0 / PHP 7.4.33 | `9e4eb28921dc`, 18/18 | complete E2E, including Persian RTL and editor block load after installing the official `fa_IR` core language pack in the clone |
| Firefox | 153.0 | WP 6.2.14 / WC 8.2.0 / PHP 7.4.33 | `82db66ce7382`, 18/18 | same minimum-stack E2E suite |
| WebKit | 26.5 | WP 6.2.14 / WC 8.2.0 / PHP 7.4.33 | `6fa74840fa1e`, 18/18 | same minimum-stack E2E suite |

Cross-browser current-stack run directories: `qa\m3-browser-chromium-final`, `qa\m3-browser-firefox-final`, `qa\m3-browser-webkit`. Minimum-stack screenshots: `qa\m3-minimum-stack\artifacts-final`. Browser full-page screenshots were visually inspected for Firefox; screenshot files remain outside the repository.

QA-124 was separately exercised in the isolated WP 7.1.3/WC 11.2.1 clone: with WooCommerce deactivated, the logged-in QA storefront returned 200 without a recommendation section or fatal; after activation, the section returned. The WordPress plugin-management screen was not used for this dependency test because its page attempted WordPress.org plugin-information calls in the network-isolated clone. No real host or store data was accessed.

Minimum-stack localization exposed a genuine compatibility issue: the previously bundled `hamkharid-fa_IR.mo` was rejected by WordPress 6.2 POMO, although it rendered on WordPress 7.1. The same `.po` source was compiled with the WordPress 6.2.14 POMO compiler; `hamkharid-fa_IR.mo` now parses on the declared minimum and returns the Persian heading at boot. New file SHA-256: `6e06c2d508095702e7cc16bfc9f4eef0be285019cb960cc75c3b451feb6d5676`. `composer run test:all` passed after this correction.

Accessibility boundary: keyboard Tab traversal passed in earlier Chromium/Firefox runs (`8c532c357888`, `5ef9edb25fc3`). The final shared smoke checks focus, a visible outline and Enter activation. WebKit's Tab sequence is not certified here. The user separately confirmed Narrator announced the heading, long product name, price and button name/role; that evidence is user-reported without an audio recording.

Scope boundary: in the initial fresh-source Elementor 4.3.5 probe, REST returned 500 because its bundled MCP adapter could not autoload `WP\McpSchema\Server\Tools\DTO\Tool`. The complete schema existed under its nested vendor tree, so a QA-only MU shim added a PSR-4 loader for `WP\McpSchema\` from that local directory. With this test-only shim, the Elementor editor loaded; the ordinary-page Shortcode widget was added to the canvas, configured with synthetic product `668`, applied and published. `_elementor_data` held the expected widget/shortcode; a fresh editor reload showed one preview section/three cards, and frontend returned 200 with one section/three cards/three CTAs and Hamkharid CSS 200. The final reload had no editor/frontend JS errors. The first apply/publish attempt logged a transient `elementorFrontend.elements.window.jQuery is not a function`; this does not establish that the unmodified Elementor package works cleanly. Elementor dynamic block and Theme Builder were not tested. SiteOrigin Page Builder 2.38.0 frontend rendering of a serialized Layout Block + `WP_Widget_Text` remained one section/three cards/three CTAs, CSS 200 and no browser errors. A later UI probe in the isolated WP 7.1.3 clone inserted the SiteOrigin Layout Block and Text widget; the block serialized into `post_content`, and the Code tab showed the synthetic shortcode in the widget preview. However the widget's saved `panelsData.text` remained empty, the editor did not report a successful REST save, and reloaded editor content was blank. Thus SiteOrigin UI shortcode author/save/render is still unverified; this is a partial UI attempt, not evidence of a product-side defect. Screenshots: `m3-current-build/elementor-ui-final-editor.png`, `elementor-ui-final-frontend.png`, `siteorigin-text-widget-ready.png`, `siteorigin-ui-authored-fail.png`, and `siteorigin-ui-verify.stage.png`. These bounded checks do not certify clean/default compatibility or every builder. Elementor Pro Theme Builder was deliberately left untested per the user's instruction; no dedicated Hamkharid builder widget is required. QA-206 proves two exact source products, repeated shortcode de-duplication, heading IDs and matching `aria-labelledby`; it does not claim every third-party builder/container. None of these limits changes the shortcode/dynamic-block MVP requirement. The fresh ZIP that includes the corrected translation/CSS still requires the separate M4 lifecycle.

## شواهد تاریخی: بازآزمایی محدود builderها پیش از نتیجهٔ نهایی

تاریخ: ۲۰۲۶-۱۰-۲۸. ZIP محلی فقط به‌عنوان موضوع آزمون استفاده شد؛ source revision=`112477054a72560a922228c9611f9997dfcc0a20` و SHA-256 بسته=`7f4868b8bd54fd772d4bbf5fb8c5ef6044b4c028baf8c9de400c270dc62f884b`. این ZIP در clone محلی نصب/باز شد و برای انتشار یا ارسال بیرونی استفاده نشد.

- Elementor 4.3.5: clean/default editor در REST/MCP با fatal autoload باز نشد. پس از افزودن MU shim صرفاً در clone QA برای PSR-4 داخلی MCP، Shortcode widget از UI روی صفحهٔ معمولی اضافه، تنظیم و publish شد؛ reload editor آن را حفظ کرد و preview یک section/سه کارت داشت. Frontend پاسخ 200، یک section، سه کارت/سه CTA و CSS 200 داشت. اولین Apply/Publish یک خطای گذرای `elementorFrontend.elements.window.jQuery is not a function` ثبت کرد؛ reload نهایی خطای JS نداشت. این pass محدود و مشروط به shim است، نه تأیید clean/default؛ dynamic block و Theme Builder آزموده نشدند. تصاویر: `m3-current-build/elementor-ui-final-editor.png`, `elementor-ui-final-frontend.png`.
- SiteOrigin Page Builder 2.38.0: frontend probe قبلی با Layout Block حاوی `WP_Widget_Text` و shortcode محصول synthetic=`652` پاسخ 200، یک `section.hamkharid-complements`، سه کارت، سه CTA، stylesheet افزونه با 200 و صفر خطای console/page داشت. تلاش UI پیشین در صفحهٔ synthetic=`729` بلوک و widget را افزود؛ probe بعدی در صفحهٔ `733` مقدار shortcode را در Code tab/preview دید، ولی `panelsData.widgets[0].text` پس از reload خالی بود ویرایشگر پاسخ ذخیرهٔ REST را تأیید نکرد. این نتیجه فقط وضعیت همان probe ناموفق است و با بازآزمایی نهایی QA-114 در بخش بعد superseded شده؛ علت محصولی اثبات نشده و sync runner می‌توانست دخیل باشد. تصاویر: `m3-current-build/siteorigin-frontend.png`, `siteorigin-text-widget-ready.png`, `siteorigin-ui-authored-fail.png`, `siteorigin-ui-verify.stage.png`, `siteorigin-ui-publish.stage.png`; frontend marker قبلی=`e5831fa902e0`.
- cleanup اجرای تازه SiteOrigin: pageهای markerدار `725, 729, 732, 733, 736` و user markerدار `152,153,154` حذف شدند؛ query پایانی هیچ marker صفحه/کاربری باقی‌مانده نداشت، active plugins فقط Hamkharid/WooCommerce بود، locale=`en_US` و theme=`twentytwentyfive`. PHP server این اجرا متوقف شد. پوشهٔ کد SiteOrigin در clone باقی مانده اما غیرفعال است؛ درخواست حذف بازگشتی آن توسط policy ابزار رد شد. آزمون فقط clone/دادهٔ synthetic محلی را لمس کرد؛ `wordpress.test` و هاست واقعی دست‌نخورده ماندند.

## QA-114 بازآزمایی نهایی SiteOrigin — ۲۰۲۶-۱۰-۲۸

روی clone محلی WP 7.1.3/WC 11.2.1/PHP 8.4.1 با SiteOrigin Page Builder 2.38.0 و Twenty Twenty-Five، در Classic Page Builder یک Text widget تازه اضافه شد. shortcode محصول synthetic `745` در تب Code وارد شد؛ سپس تب Visual انتخاب شد تا مقدار TinyMCE به فرم پنهان widget همگام شود. پس از Done و Update صفحه، editor کامل reload شد و shortcode در widget باقی بود. Frontend یک section با ۳ کارت و ۳ CTA، stylesheet افزونه با HTTP 200، بدون shortcode خام و بدون خطاهای page/console داشت. دو shortcode یکسان در صفحه به یک section frontend deduplicate شدند. اسکرین‌شات‌های `m3-current-build/siteorigin-new-code-ui-result-widget.png`, `siteorigin-new-code-ui-result-before-save.png`, `siteorigin-new-code-ui-result-reload.png`, `siteorigin-new-code-ui-result-frontend.png` و یادداشت متنی `siteorigin-classic-code-visual-pass.txt` در پوشهٔ QA محلی بیرون repository نگهداری می‌شوند.

این شاهد فقط Classic Page Builder/Text widget و workflow صریح Code→Visual→Done/Update→reload را پوشش می‌دهد؛ SiteOrigin Gutenberg Layout Block authoring/save آزموده نشده است. اجرای clean/default Elementor هنوز به‌علت autoload داخلی REST/MCP باز نمی‌شود و شاهد صفحهٔ معمولی Elementor به shim محلی نیاز داشت. Theme Builder طبق تصمیم کاربر آزموده‌نشده است. cleanup مستقل clone: صفر SiteOrigin marker page/user، نبود pageهای دستی 739/742/757، locale=`en_US`، theme=`twentytwentyfive`، فقط Hamkharid/WooCommerce فعال و port 8844 بسته. بنابراین QA-114 در دامنهٔ محدود بالا PASS است و M3 در دامنهٔ تعریف‌شده تکمیل شده؛ محدودیت‌ها ادعای سازگاری جامع builderها نیستند.
