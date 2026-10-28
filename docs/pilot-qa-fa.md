# گزارش QA محلی هم‌خرید

تاریخ بازبینی: ۲۰۲۶-۱۰-۲۸. code/test commit `5d468e0c2909f3ef78088ebeccae8db008e33766` tracked و pushed است. E2E run=`1aa89a2c28a1` پیش از commit اجرا شد؛ منطق تست با نسخه commit‌شده یکسان است و تفاوت فقط whitespace اصلاح‌شده با PHPCBF است. M4 برای ZIP SHA-256 `9d6b427da69510ceb89e82cc29d644219615b55e6c4ca54e54ca393cf71c6ee6` از source تمیز `7b3ac0f4d7d8f1ca6bd5a7ef1c87e3842de1ae92` پاس شد؛ جزئیات و محدودیت‌ها در بخش lifecycle آمده‌اند.

## نتیجه

روی code/test commit `5d468e0c2909f3ef78088ebeccae8db008e33766`، `composer run test:all` با exit 0 تمام شد: PHPCS ۲۸/۲۸، PHPUnit ۸/۸ با ۱۳ assertion و integration برابر ۱۲/۱۲. `npm run check:js` و `node --check` نیز پاس شدند. E2E run=`1aa89a2c28a1`، Browser QA را ۱۷/۱۷ گذراند؛ اجرا پیش از commit بود و منطق تست یکسان است، با تفاوت صرفاً whitespace اصلاح‌شده با PHPCBF. پوشش شامل QA-097، QA-130 دسترسی واقعی Shop Manager به rules/settings، Subscriber denial و axe-core 4.13.0 در `en_US`/LTR و `fa_IR`/RTL بود. axe با tagهای `wcag2a`, `wcag2aa`, `wcag21a`, `wcag21aa`, `wcag22a`, `wcag22aa` تخلفی گزارش نکرد. بازبینی بصری screenshotهای ۱۲۰۰px و ۴۲۰px، contrast کارت و layout را تأیید کرد. fixtureها پس از اجرا پاک شدند. HPOS روشن/خاموش، WooCommerce فعال/غیرفعال، widget area و multi-embed نیز در QAهای ثبت‌شده پوشش دارند؛ Elementor مرورگری، screen reader و ماتریس گستردهٔ سازگاری بازند. M4 lifecycle برای ZIP SHA `9d6b427da69510ceb89e82cc29d644219615b55e6c4ca54e54ca393cf71c6ee6` از source تمیز `7b3ac0f4d7d8f1ca6bd5a7ef1c87e3842de1ae92` پاس شد. NFR-02/E-15 فقط در envelope محلی اندازه‌گیری‌شده PASS است و SLA عمومی نیست.

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

## موارد آزموده‌نشده و gateهای باز

- Elementor و rendering مرورگری سایر page builderهای خاص.
- screen reader؛ smoke keyboard/focus و سنجش zoom معادل ارزیابی WCAG کامل نیستند.
- ماتریس گسترده نسخه‌های WordPress/WooCommerce/PHP و مرورگرها.
- پذیرش عمومی/محصولی performance: گیت محلی E-15 را پاس کرده‌ایم؛ این نتیجه SLA عمومی نیست. مقایسه با محصولات/محیط‌های دیگر یا تعهد عملکردی سراسری خارج از این شاهد است.
- موارد باقی‌مانده برای آمادگی کلی: Elementor مرورگری، screen reader و دامنهٔ سازگاری گسترده آزموده/تأیید نشده‌اند. M4 برای artifact ثبت‌شده پاس شد؛ این به‌تنهایی تأیید عمومی یا اعلام آمادگی کلی پایلوت نیست.

### شواهد اولیه M4 روی package قبلی

ZIP پیشین با SHA-256 `d026f297d533047b64f00faf884ccc8534b37c62513c0c242cb2051f2a512e4c` و source revision `da914d3257b24564a5aafaeea939d4dd07c05fe2` در clone مستقل QA بررسی شد. WordPress uploader، نصب تمیز/فعال‌سازی، تنظیم manual نصب تازه، shortcode، auto placement در upgrade، حفظ داده‌ها، uninstall پیش‌فرض و opt-in، و پاک‌سازی fixtureها پاس شدند؛ manifest با ZIP hash منطبق بود. محیط clone: WordPress 7.1.3، WooCommerce 11.2.1، PHP 8.4، Twenty Twenty-Five و `en_US`. گزارش خام در `C:\Users\HP\.codex\visualizations\2026\10\27\01a17ea3-2fb8-7403-b60e-3f15d61b0241\qa\zip-install\zip-qa-evidence.txt` و تصاویر در همان پوشه هستند.

این ZIP پیشین از revision پیش از اصلاح variation ساخته شده؛ شاهد چرخه‌عمر package نهایی نیست. به‌روزرسانی آن نیز با تغییر header به 0.0.9 شبیه‌سازی شده بود. artifact بعدی از source تمیز `7693821` ساخته شد؛ SHA-256 آن `d6006eb80ebcaca16794ff55d73f873b72a46e07387323c3ccc056ea36304053` بود، manifest با hash منطبق بود، `source_dirty=false` بود و runtime autoloader و `uninstall.php` داخل ZIP بودند. دو build تکراری hash یکسان دادند. این artifact پیش از contrast fix است و lifecycle جاری را نمایندگی نمی‌کند. artifact تاریخی در `C:\Users\HP\.codex\visualizations\2026\10\27\01a17ea3-2fb8-7403-b60e-3f15d61b0241\qa\release\hamkharid-0.1.0-pilot.zip` ثبت شده است.

### شواهد نهایی M4 برای ZIP پایلوت

artifact دقیق با SHA-256 `9d6b427da69510ceb89e82cc29d644219615b55e6c4ca54e54ca393cf71c6ee6` از source تمیز revision `7b3ac0f4d7d8f1ca6bd5a7ef1c87e3842de1ae92` ساخته و در clone QA محلی روی WordPress 7.1.3، WooCommerce 11.2.1، PHP 8.4 و Twenty Twenty-Five بررسی شد. هستهٔ WordPress `Plugin_Upgrader` همین artifact را نصب و دوباره نصب کرد. Chrome uploader به‌علت policy ریشهٔ انتخاب فایل محلی آن clone را نپذیرفت؛ پس UI uploader مرورگر باز/آزموده‌نشده است.

نصب تمیز و تنظیم پیش‌فرض manual پاس شدند. update با تغییر header نصب‌شده به marker مصنوعی `0.0.9` شبیه‌سازی شد، نه با ZIP تاریخی. update rule/settings و محصول/دسته/سفارش را حفظ کرد. uninstall پیش‌فرض تنظیمات و rule را نگه داشت؛ opt-in فقط rule/settings هم‌خرید را حذف کرد و محصولات، دسته‌ها، سفارش‌ها و داده‌های WooCommerce را حفظ کرد. rollback محافظت‌شده DB کلون ۵۲ جدولی و فایل‌های plugin قبلی را برگرداند؛ سپس همان ZIP با hash ثبت‌شده دوباره نصب و فعال شد. پاک‌سازی در پایان صفر HHQA fixture باقی گذاشت. contrast متن و دکمهٔ کارت renderشده 9.3608:1 بود.

M4 lifecycle برای همین SHA پاس است. پس از نصب language pack رسمی WordPress `fa_IR` در clone و بازگردانی تنظیم زبان، native RTL در `WPLANG=fa_IR` تأیید شد: `is_rtl=true`، HTML برابر `lang=fa-IR` و `dir=rtl`، نمایش shortcode و auto section با Persian target، CSS 200، بدون خطای مرورگر و computed direction کارت `rtl`. برای این آزمون DevTools override استفاده نشد. تنظیمات clone برای بازگشت به locale/manual و پاک‌سازی صفر fixture در حال restore است. Elementor مرورگری، screen reader، سازگاری گسترده و Chrome uploader باز/آزموده‌نشده‌اند. این نتیجه آمادگی عمومی پایلوت را به‌تنهایی اثبات نمی‌کند؛ NFR-02 فقط در envelope محلی E-15 پاس است. شواهد lifecycle: `C:\Users\HP\.codex\visualizations\2026\10\27\01a17ea3-2fb8-7403-b60e-3f15d61b0241\qa\zip-install\m4-final-9d6b427-evidence.txt`; شواهد native RTL در همان پوشهٔ QA محلی ثبت شده است.

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

M1 و مسیرهای اصلی M2 بر اساس شواهد پاس‌اند. E2E `1aa89a2c28a1` روی working tree پیش از commit `5d468e0`، ۱۷/۱۷ را گذراند و QA-097/QA-104/QA-130 و axe-core 4.13.0 در دو locale را پوشش داد؛ منطق تست با commit tracked/pushed یکسان است و فقط whitespace PHPCBF تفاوت دارد. بازبینی screenshotهای ۱۲۰۰/۴۲۰px کارت و layout را تأیید کرد و fixtureها پاک شدند. native `fa_IR` RTL نیز در clone با language pack رسمی بدون DevTools override پاس شد؛ تنظیمات clone به حالت اولیه برگشت و fixtureها پاک شدند. NFR-02 با E-15 در محدوده‌ای که کاربر تأیید کرده، برای همین محیط pass است و SLA عمومی یا تعهد محصولی نیست. M4 برای ZIP SHA `9d6b427da69510ceb89e82cc29d644219615b55e6c4ca54e54ca393cf71c6ee6` از source `7b3ac0f4d7d8f1ca6bd5a7ef1c87e3842de1ae92` پاس شد؛ Elementor مرورگری، screen reader، سازگاری گسترده و Chrome uploader هنوز بازند. این گزارش مجوز انتشار یا ارسال ZIP نیست.
