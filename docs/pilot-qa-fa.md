# گزارش QA محلی هم‌خرید

تاریخ اجرا: ۲۰۲۶-۱۰-۲۸. نسخه کد: `0.1.0` به‌همراه تغییرهای محلی روی مبنای Git `b6bfab6`؛ شناسه E2E: `63f291d389df`.

## نتیجه

اجرای تازه `composer run test:all` پس از افزودن regression variation با exit 0 تمام شد. E2E روی کد به‌روز با run=`63f291d389df`، Browser QA ۱۴/۱۴ را پاس کرد و cleanup exit 0 داشت. این گزارش آمادگی انتشار را اعلام نمی‌کند: M4 تا بازسازی و آزمون package روی کد جاری و تکمیل rollback باز است؛ چند حوزه M3 نیز آزموده نشده‌اند.

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
| `composer run test:all` (۲۰۲۶-۱۰-۲۸) | exit 0 پس از regression variation؛ lint، PHPCS برای ۲۸ فایل، PHPUnit با ۸/۸ تست و ۱۳ assertion، هر ۱۲ integration از جمله `integration-performance` و uninstall موفق |
| `npm run check:js` | exit 0 |
| `npm run test:e2e` (کد جاری) | run `63f291d389df`؛ Browser QA ۱۴/۱۴ سناریوی مرورگر موفق |
| `npm run e2e:cleanup` پس از run جاری | exit 0؛ fixtureهای E2E پاک شدند |

در E2E کد جاری با شناسه `63f291d389df` موارد زیر پوشش داده شدند:

- Admin: QA-005/006 و QA-010 تا QA-019.
- Matching/rendering: QA-032/033، QA-050 تا QA-057، QA-070/071/076.
- خرید بومی: QA-080/081 و QA-083 تا QA-085.
- واکنش‌گرایی: QA-093 تا QA-096.
- دسترس‌پذیری smoke: QA-100 تا QA-103؛ keyboard/focus، نام‌گذاری بخش و contrast محاسبه‌شده عنوان/قیمت/دکمه حداقل 4.5:1.
- مجوز: QA-130/131.
- جای‌گذاری: QA-205؛ حالت دستی و خاموش‌بودن auto placement، preview مدیریت، shortcode و dynamic block در editor/frontend روی قالب کلاسیک و Twenty Twenty-Five.

زبان انگلیسی/LTR و فارسی/RTL در مرورگر اجرا شد. screenshotهای responsive خارج از مخزن نگهداری شده‌اند:

- `hamkharid-e2e-final-desktop-1200.png`
- `hamkharid-e2e-final-mobile-420.png`
- `hamkharid-e2e-rtl-desktop-1200.png`
- `hamkharid-e2e-rtl-mobile-420.png`

مسیر محلی پوشه شواهد: `C:\Users\HP\.codex\visualizations\2026\10\27\01a17ea3-2fb8-7403-b60e-3f15d61b0241\qa`.

## موارد آزموده‌نشده و gateهای باز

- Elementor و سایر page builderها، ناحیه‌های widget و shortcode واقعی در widget.
- بزرگ‌نمایی ۲۰۰٪ و screen reader؛ smoke keyboard/focus معادل ارزیابی WCAG کامل نیست.
- HPOS خاموش، WooCommerce غیرفعال و جریان Shop Manager.
- سناریوی چند embed برای یک محصول و رفتار duplicate در همه placementها.
- ماتریس گسترده نسخه‌های WordPress/WooCommerce/PHP و مرورگرها.
- benchmark پذیرش PRD: مقیاس ۱k/۱۰k/۵۰k محصول، ۱۰/۱۰۰/۱۰۰۰ rule و نمونه‌های cold/warm. fixture کوچک performance در integrationها اجرا شده، اما برای عبور NFR-02 کافی نیست.
- M4 روی کد جاری: rebuild و بررسی ZIP، manifest/checksum، نصب تمیز، update/uninstall/rollback از همان artifact و بررسی نبود Composer در مقصد. package قبلی شواهد اولیه دارد، اما از revision قبل از آخرین اصلاح variation است.

### شواهد اولیه M4 روی package قبلی

یک ZIP با SHA-256 `d026f297d533047b64f00faf884ccc8534b37c62513c0c242cb2051f2a512e4c` و source revision `da914d3257b24564a5aafaeea939d4dd07c05fe2` در clone مستقل QA بررسی شد. WordPress uploader، نصب تمیز/فعال‌سازی، تنظیم manual نصب تازه، shortcode، auto placement در upgrade، حفظ داده‌ها، uninstall پیش‌فرض و opt-in، و پاک‌سازی fixtureها پاس شدند؛ manifest با ZIP hash منطبق بود. محیط clone: WordPress 7.1.3، WooCommerce 11.2.1، PHP 8.4، Twenty Twenty-Five و `en_US`. گزارش خام در `C:\Users\HP\.codex\visualizations\2026\10\27\01a17ea3-2fb8-7403-b60e-3f15d61b0241\qa\zip-install\zip-qa-evidence.txt` و تصاویر در همان پوشه هستند.

این ZIP از revision پیش از اصلاح variation ساخته شده؛ پس شاهد چرخه‌عمر اولیه است، نه قبولی package کد جاری. به‌روزرسانی با تغییر header همان کپی QA از 0.1.0 به 0.0.9 شبیه‌سازی شد، نه با نسخه تاریخی واقعی. package فعلی باید دوباره ساخته شود و install/update/uninstall/rollback روی همان SHA تکرار شود. هنگام اجرا، PHP برای curl هشدار extension گم‌شده داد؛ WordPress UI و چرخه‌های ZIP با وجود هشدار کامل شدند.

## یافته تازه ممیزی کد و وضعیت بازآزمایی

بازبینی تازه نشان داد variable productها می‌توانند تعداد variation زیادی داشته باشند. برای هر فراخوانی `RecommendationEngine::recommend()` اکنون سقف سراسری ۵۰۰ variation candidate در صفحه‌های حداکثر ۲۴تایی اعمال می‌شود؛ این سقف مستقل از بودجه ۵۰۰ محصول است. engine تعداد variationهای بررسی‌شده و رسیدن به سقف را در diagnostics می‌دهد، و preview مدیریت هشدار می‌دهد که برخی variable productها ممکن است از خروجی جا افتاده باشند.

برای regression یک fixture مصنوعی ۵۰۱ variation به `integration-performance` اضافه شد. کل `composer run test:all` با این regression exit 0 شد و تمام ۱۲ integration پاس شدند. performance fixture کوچک این زمان‌ها را ثبت کرد: match=3.65ms، newest=8.68ms، best-selling=8.71ms، random=7.15ms. این fixture هدفمند فقط سقف و query bound را می‌آزماید؛ benchmark مقیاس PRD نیست. benchmark agent جداگانه در حال کار است و NFR-02 همچنان پاس‌نشده است.

## جمع‌بندی gate

M1 و integrationهای مربوط به M2، از جمله regression variation، در suite تازه پاس شدند؛ E2E روی کد به‌روز نیز با run=`63f291d389df`، ۱۴/۱۴ را پاس کرد و cleanup exit 0 داشت. M3 ناقص است تا موارد دسترس‌پذیری، چندبستری و benchmark بالا بررسی شوند. M4 تا تکرار QA روی ZIP ساخته‌شده از code revision جاری باز می‌ماند؛ این گزارش مجوز انتشار یا ارسال ZIP نیست.
