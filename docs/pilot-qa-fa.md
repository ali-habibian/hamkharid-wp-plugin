# گزارش QA محلی هم‌خرید

تاریخ اجرا: ۲۰۲۶-۱۰-۲۸. نسخه runtime: `0.1.0` از source revision `7693821`؛ شناسه آخرین E2E: `168017ed316c`.

## نتیجه

روی source runtime revision `7693821`، `composer run test:all` با exit 0 تمام شد: PHPCS برابر ۲۸/۲۸، PHPUnit برابر ۸/۸ با ۱۳ assertion، و integration برابر ۱۲/۱۲ از جمله performance. `composer run cs` نیز پس از اصلاح alignment با ۲۸/۲۸ فایل پاک پاس شد؛ `npm run check:js` exit 0 داشت. اجرای E2E تازه با run=`168017ed316c`، Browser QA ۱۵/۱۵ را پاس کرد و `npm run e2e:cleanup` exit 0 داشت. E2E شامل تغییر آزمون‌محور برای QA-097 در عرض مؤثر ۳۲۰ CSS px با بزرگ‌نمایی صفحهٔ ۲۰۰٪ است؛ این تغییر در بسته runtime نیست و SHA آن همچنان `d6006eb80ebcaca16794ff55d73f873b72a46e07387323c3ccc056ea36304053` روی source runtime revision `7693821` است. مرور بصری layoutهای responsive انجام شد؛ QA مجزای قالب Twenty Twenty-Five (TT5) مشکل contrast را پیدا کرد و اصلاح آن در commit جاری ثبت شده است. چرخهٔ عمر ZIP نهایی در محیط ایزوله پاس شد و M4 بسته است؛ این گزارش آمادگی کلی پایلوت را اعلام نمی‌کند: حوزه‌های M3 و threshold پذیرش NFR-02 همچنان بازند.

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
| `composer run test:all` (source `7693821`) | exit 0؛ lint، PHPCS 28/28، PHPUnit 8/8 با 13 assertion، integration 12/12 از جمله `integration-performance` |
| `composer run cs` پس از alignment fix | exit 0؛ PHPCS 28/28 فایل پاک |
| `npm run check:js` | exit 0 |
| `npm run test:e2e` (اجرای تازه) | run `168017ed316c`؛ Browser QA ۱۵/۱۵, شامل QA-097 در عرض مؤثر ۳۲۰ CSS px با zoom صفحهٔ ۲۰۰٪ |
| `npm run e2e:cleanup` پس از اجرای تازه | exit 0؛ fixtureهای E2E پاک شدند |

در E2E کد جاری با شناسه `168017ed316c` موارد زیر پوشش داده شدند:

- Admin: QA-005/006 و QA-010 تا QA-019.
- Matching/rendering: QA-032/033، QA-050 تا QA-057، QA-070/071/076.
- خرید بومی: QA-080/081 و QA-083 تا QA-085.
- واکنش‌گرایی: QA-093 تا QA-097؛ مورد QA-097 با عرض مؤثر ۳۲۰ CSS px و zoom صفحهٔ ۲۰۰٪ اجرا شد.
- دسترس‌پذیری smoke: QA-100 تا QA-103؛ keyboard/focus، نام‌گذاری بخش و contrast محاسبه‌شده عنوان/قیمت/دکمه حداقل 4.5:1.
- مجوز: QA-130/131.
- جای‌گذاری: QA-205؛ حالت دستی و خاموش‌بودن auto placement، preview مدیریت، shortcode و dynamic block در editor/frontend روی قالب کلاسیک و Twenty Twenty-Five.

زبان انگلیسی/LTR و فارسی/RTL در مرورگر اجرا شد. screenshotهای responsive خارج از مخزن نگهداری شده‌اند و مرور بصری layoutهای responsive انجام شد:

- `hamkharid-e2e-final-desktop-1200.png`
- `hamkharid-e2e-final-mobile-420.png`
- `hamkharid-e2e-rtl-desktop-1200.png`
- `hamkharid-e2e-rtl-mobile-420.png`

مسیر محلی پوشه شواهد: `C:\Users\HP\.codex\visualizations\2026\10\27\01a17ea3-2fb8-7403-b60e-3f15d61b0241\qa`.

## موارد آزموده‌نشده و gateهای باز

- Elementor و سایر page builderها، ناحیه‌های widget و shortcode واقعی در widget.
- screen reader؛ smoke keyboard/focus و سنجش zoom معادل ارزیابی WCAG کامل نیستند.
- HPOS خاموش، WooCommerce غیرفعال و جریان Shop Manager.
- سناریوی چند embed برای یک محصول و رفتار duplicate در همه placementها.
- ماتریس گسترده نسخه‌های WordPress/WooCommerce/PHP و مرورگرها.
- تعیین pass/fail کارایی: ماتریس benchmark مقیاس اجرا و ثبت شده است؛ threshold پذیرش NFR-02 هنوز به انتخاب کاربر نیاز دارد، بنابراین gate را pass یا fail اعلام نمی‌کنیم.
- موارد باقی‌مانده برای آمادگی کلی: screen reader، builder/widget و دامنهٔ سازگاری گسترده آزموده نشده‌اند؛ threshold پذیرش NFR-02 در O-05 منتظر تصمیم کاربر است. چرخهٔ عمر package در M4 با شواهد پایین‌تر پاس شد.

### شواهد اولیه M4 روی package قبلی

ZIP پیشین با SHA-256 `d026f297d533047b64f00faf884ccc8534b37c62513c0c242cb2051f2a512e4c` و source revision `da914d3257b24564a5aafaeea939d4dd07c05fe2` در clone مستقل QA بررسی شد. WordPress uploader، نصب تمیز/فعال‌سازی، تنظیم manual نصب تازه، shortcode، auto placement در upgrade، حفظ داده‌ها، uninstall پیش‌فرض و opt-in، و پاک‌سازی fixtureها پاس شدند؛ manifest با ZIP hash منطبق بود. محیط clone: WordPress 7.1.3، WooCommerce 11.2.1، PHP 8.4، Twenty Twenty-Five و `en_US`. گزارش خام در `C:\Users\HP\.codex\visualizations\2026\10\27\01a17ea3-2fb8-7403-b60e-3f15d61b0241\qa\zip-install\zip-qa-evidence.txt` و تصاویر در همان پوشه هستند.

این ZIP پیشین از revision پیش از اصلاح variation ساخته شده؛ شاهد چرخه‌عمر package نهایی نیست. به‌روزرسانی آن نیز با تغییر header به 0.0.9 شبیه‌سازی شده بود. package جاری از source تمیز `7693821` ساخته شد؛ SHA-256 آن `d6006eb80ebcaca16794ff55d73f873b72a46e07387323c3ccc056ea36304053` است، manifest با hash منطبق است، `source_dirty=false` بود و runtime autoloader و `uninstall.php` داخل ZIP هستند. دو build تکراری hash یکسان دادند. artifact در `C:\Users\HP\.codex\visualizations\2026\10\27\01a17ea3-2fb8-7403-b60e-3f15d61b0241\qa\release\hamkharid-0.1.0-pilot.zip` است.

### شواهد نهایی lifecycle برای ZIP پایلوت

همین artifact روی clone محلی ایزوله با WordPress 7.1.3، WooCommerce 11.2.1، PHP 8.4 و قالب Twenty Twenty-Five آزموده شد. نصب از uploader، فعال‌سازی و تنظیم پیش‌فرض manual پاس شد. صفحهٔ آزمایشی shortcode و جای‌گذاری خودکار روی محصول هر دو render شدند؛ Playwright خطای صفحه گزارش نکرد. contrast متن/دکمه در TT5 برابر 9.36:1 اندازه‌گیری شد.

چرخهٔ update با marker مصنوعی انجام شد: نسخهٔ header نصب‌شده از 0.1.0 به 0.0.9 تغییر یافت و سپس همین ZIP آپلود شد؛ این downgrade با ZIP تاریخی واقعی نبود. پس از update، rule قدیمی و عنوان/مبدأ/مقصدش باقی ماند و نبودن priority meta به priority مؤثر 0 تبدیل شد؛ حالت automatic، heading سفارشی و داده‌های synthetic WooCommerce نیز حفظ شدند. uninstall پیش‌فرض rule/meta و تنظیمات را حفظ کرد؛ uninstall با opt-in فقط rule/settings هم‌خرید را حذف کرد و داده‌های WooCommerce را نگه داشت. rollback محلی با backup فایل‌های افزونه و DB clone، نسخهٔ synthetic 0.0.9 و frontend/admin را برگرداند؛ سپس ZIP نهایی دوباره نصب و فعال شد.

Cleanup فقط fixtureهای علامت‌خوردهٔ HHQA را حذف کرد؛ clone در وضعیت نهایی با ZIP 0.1.0 فعال، حالت manual، صفر rule و صفر fixture باقی ماند. **M4 artifact lifecycle QA پاس شد.** این نتیجه آمادگی کلی پایلوت را ثابت نمی‌کند؛ NFR-02 تا انتخاب threshold از سوی کاربر pass/fail نمی‌گیرد و چند مورد M3 آزموده نشده‌اند. گزارش کامل: `C:\Users\HP\.codex\visualizations\2026\10\27\01a17ea3-2fb8-7403-b60e-3f15d61b0241\qa\zip-install\zip-qa-evidence-final-7693821.txt`. فایل‌های rollback در `C:\Users\HP\.codex\visualizations\2026\10\27\01a17ea3-2fb8-7403-b60e-3f15d61b0241\qa\zip-install\rollback-backup-7693821\` نگهداری شده‌اند؛ تصویرها هم کنار گزارش‌اند. PHP در محیط clone دربارهٔ cURL اختیاری هشدار startup داد، اما همهٔ flowهای ثبت‌شده موفق شدند.

## یافته تازه ممیزی کد و وضعیت بازآزمایی

بازبینی تازه نشان داد variable productها می‌توانند تعداد variation زیادی داشته باشند. برای هر فراخوانی `RecommendationEngine::recommend()` اکنون سقف سراسری ۵۰۰ variation candidate در صفحه‌های حداکثر ۲۴تایی اعمال می‌شود؛ این سقف مستقل از بودجه ۵۰۰ محصول است. engine تعداد variationهای بررسی‌شده و رسیدن به سقف را در diagnostics می‌دهد، و preview مدیریت هشدار می‌دهد که برخی variable productها ممکن است از خروجی جا افتاده باشند.

برای regression یک fixture مصنوعی ۵۰۱ variation به `integration-performance` اضافه شد. کل `composer run test:all` در source revision `7693821` با exit 0 شد و تمام ۱۲ integration پاس شدند. performance fixture کوچک این زمان‌ها را ثبت کرد: match=3.83ms، newest=8.40ms، best-selling=7.21ms، random=8.43ms. این fixture هدفمند فقط سقف و query bound را می‌آزماید؛ benchmark مقیاس جداگانه در بخش بعد ثبت شده است.

## شواهد benchmark مقیاس

گزارش پس از اصلاح، از revision کامل `7693821d58e1139bc4b1bf45c890aa4b83149d20` با synthetic database/site مجزا اجرا شد. ماتریس کامل ۱k/۱۰k/۵۰k محصول در برابر ۱۰/۱۰۰/۱۰۰۰ قانون پوشش داده شد؛ matcher و هر sort برای هر حالت ۳۰ نمونه cold و ۳۰ warm داشتند. Cold، flush کردن WP object cache پیش از نمونه است؛ buffer pool دیتابیس در اندازه‌گیری‌ها warm باقی ماند. این‌ها مشاهدات همان محیط‌اند، نه SLA.

- در ۵۰k محصول، p95 مرتب‌سازی‌های ordered در کد اصلاح‌شده ۵۳۴–۶۸۳ms بود؛ baseline پیش از اصلاح ۹۳۷–۱۲۸۶ms ثبت کرده بود. SQL سرد برای ordered از ۱۸ به ۱۶ query رسید؛ warm ordered صفر query داشت.
- random در ۱۰k، بیشترین p95 برابر ۵۱۴ms cold و ۱۴۴ms warm بود؛ در ۵۰k، بیشترین p95 برابر ۱۱۴۴ms cold و ۵۶۹ms warm بود. random در cold برابر ۱۸ و warm برابر ۴ SQL ثبت کرد.
- بیشینه allocation حافظه در ماتریس ۹۶ MiB بود.
- آزمون variable-heavy شامل ۱۰۰ parent و ۱۲٬۰۰۰ variation بود؛ سقف ۵۰۰ اعمال شد. در اجرای اصلاح‌شده، cold برابر ۸۴۱–۸۷۰ms با ۲۴۴ SQL و warm برابر ۵۷۴–۶۰۲ms با صفر SQL بود. اجرای pre-fix از سقف ۱۲۰ ثانیه گذشت و متوقف شد، پس latency آن با اعداد اصلاح‌شده مقایسه‌پذیر نیست.

هیچ آستانه‌ای برای NFR-02 اعمال نشده است. وضعیت pass/fail تا انتخاب threshold از سوی کاربر باز می‌ماند؛ مشاهدهٔ این اندازه‌گیری‌ها به‌تنهایی معیار قبولی نمی‌سازد. دیتابیس QA پاک شد: فقط جدول‌های `wpperf_` حذف شدند (۵۲ جدول). فایل‌های سایت clone باقی مانده‌اند چون سیاست PowerShell حذف بازگشتی را مسدود کرد. گزارش و داده خام بیرون مخزن‌اند: `C:\Users\HP\.codex\visualizations\2026\10\27\01a17ea3-2fb8-7403-b60e-3f15d61b0241\qa\performance\summary-postfix-7693821.md`، `results-7693821.json` و `variable-stress-7693821.json`؛ baseline در `summary.md` و `results-baseline-da914d3.json` همان پوشه است.

## جمع‌بندی gate

M1 و مسیرهای اصلی M2 پاس شدند؛ E2E با run=`168017ed316c`، ۱۵/۱۵ را گذراند و QA-097 عرض مؤثر ۳۲۰ CSS px را در zoom صفحهٔ ۲۰۰٪ پوشش داد. Benchmark مقیاس تکمیل شده، اما NFR-02 تا تصمیم کاربر دربارهٔ threshold نه pass است و نه fail؛ screen reader و چند مورد M3 دیگر آزموده نشده‌اند. M4 artifact lifecycle روی ZIP نهایی با SHA `d6006eb80ebcaca16794ff55d73f873b72a46e07387323c3ccc056ea36304053` پاس شد. این گزارش مجوز انتشار یا ارسال ZIP نیست.
