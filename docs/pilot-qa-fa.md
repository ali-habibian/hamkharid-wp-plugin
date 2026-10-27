# گزارش QA محلی هم‌خرید

تاریخ اجرا: ۲۰۲۶-۱۰-۲۸. نسخه کد: `0.1.0` به‌همراه تغییرهای محلی روی مبنای Git `b6bfab6`؛ شناسه E2E: `2a533f5a6c48`.

## نتیجه

آزمون‌های واحد، یکپارچگی و E2E ثبت‌شده در این گزارش موفق‌اند. این گزارش آمادگی انتشار را اعلام نمی‌کند: M4 تا تکمیل گزارش مستقل بسته، نصب/ارتقا و rollback باز می‌ماند؛ چند حوزه M3 هم آزموده نشده‌اند.

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
| `composer run test:all` | exit 0؛ lint موفق، PHPCS برای ۲۸ فایل، PHPUnit با ۸/۸ تست و ۱۳ assertion، و هر ۱۲ integration از جمله uninstall موفق |
| `npm run check:js` | exit 0 |
| `npm run test:e2e` | run `2a533f5a6c48`؛ ۱۴/۱۴ سناریوی مرورگر موفق |
| `npm run e2e:cleanup` | exit 0؛ fixtureهای E2E پاک شدند |

E2E شناسه‌های زیر را پوشش داد:

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
- M4: ساخت و بررسی ZIP، manifest/checksum واقعی، نصب تمیز، ارتقا از قواعد قدیمی، uninstall/rollback از بسته و بررسی نبود Composer در مقصد. نتیجه این موارد باید در همین سند بعداً تکمیل شود.

## جمع‌بندی gate

M1 و مسیرهای اصلی M2 در suiteهای گزارش‌شده پاس شدند. M3 ناقص است تا موارد دسترس‌پذیری، چندبستری و benchmark بالا بررسی شوند. تا تکمیل آزمون‌های M4 و ثبت شواهد بسته، این نتیجه فقط گزارش QA محلی است و مجوز انتشار یا ارسال ZIP نیست.
