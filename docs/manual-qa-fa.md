# چک‌لیست QA دستی افزونه هم‌خرید

> مرجع فعلی محصول و توسعه: [فهرست مستندات](README.md)، [PRD](prd-fa.md) و [وضعیت و نقشه راه](roadmap-fa.md). شماره‌های Phase 15/16 در این سند نام‌گذاری تاریخی‌اند، نه شاهد تکمیل. E2E نهایی `551465d97590` پس از دو اصلاح CSS پاس شد و کاربر Narrator را برای QA-207 تأیید کرد. ZIP جاری SHA `7f4868b8bd54fd772d4bbf5fb8c5ef6044b4c028baf8c9de400c270dc62f884b` و native locale-driven `fa_IR` RTL در clone lifecycle/بررسی شدند؛ Chrome uploader آزموده نشد.
>
> اصلاح دامنه ۲۰۲۶-۱۰-۲۷: طبق D-08، بلوک پویا و shortcode در MVP قرار دارند و جای‌گذاری به قالب خاص محدود نمی‌شود. E2E run=`1aa89a2c28a1` روی working tree پیش از commit `5d468e0`، ۱۷/۱۷ سناریو را گذراند؛ منطق تست با commit tracked/pushed یکسان است و فقط whitespace اصلاح‌شده با PHPCBF تفاوت دارد. پوشش شامل QA-097، QA-130 دسترسی واقعی Shop Manager و axe-core 4.13.0 در `en_US`/LTR و `fa_IR`/RTL با صفر تخلف در tagهای WCAG 2.0/2.1/2.2 A/AA بود. بازبینی screenshotهای ۱۲۰۰/۴۲۰px contrast کارت و layout را تأیید کرد و fixtureها پاک شدند. M4 artifact جاری SHA `7f4868b8bd54fd772d4bbf5fb8c5ef6044b4c028baf8c9de400c270dc62f884b` را در clone lifecycle کرد؛ native `fa_IR` RTL نیز pass شد. Browser file chooser آزموده نشد.

این سند برای QA دستی MVP افزونه **Smart Category Complement – Hamkharid** است. هدف این مرحله، تأیید رفتار واقعی افزونه در WordPress/WooCommerce و ثبت یک اجرای قابل تکرار قبل از آماده‌سازی مارکت است.

> برچسب Phase 15/16 از چک‌لیست قدیمی مانده است. QA-200..204 در clone محلی مرورگر اجرا شدند؛ QA-202 پس از اصلاح ورودی عددی فارسی/عربی و حفظ target خالی پاس شد و QA-203 با ۵۰۱ variation واقعی به هشدار سقف رسید. QA-114 برای SiteOrigin Classic Text widget و Elementor ordinary-page در مسیرهای محدود ثبت‌شده pass است؛ default PHP8.4 deprecation خود Elementor و PHP8.1 reload spinner در [گزارش QA پایلوت](pilot-qa-fa.md) محدودیت‌های محیط‌اند. SiteOrigin Gutenberg Layout Block و Elementor Theme Builder (طبق تصمیم کاربر) آزموده‌نشده‌اند. Narrator طبق گزارش کاربر پاس است. ZIP جاری M4 فقط محلی است؛ ارسال/انتشار انجام نشده.

## 1. اطلاعات اجرای QA

قبل از شروع، این مقادیر را ثبت کنید:

- تاریخ QA: `________________`
- تست‌کننده: `________________`
- PHP: `________________`
- WordPress: `________________`
- WooCommerce: `________________`
- Theme اصلی: `________________`
- Theme دوم: `________________`
- زبان/Locale: `________________`
- جهت صفحه: `RTL / LTR`
- HPOS: `Enabled / Disabled`
- مرورگر Desktop: `________________`
- مرورگر Mobile/Device mode: `________________`
- Commit/Tag تحت تست: `________________`

## 2. Pre-flight

- [ ] **QA-001** افزونه `Smart Category Complement – Hamkharid` فعال است.
- [ ] **QA-002** WooCommerce فعال است و صفحه اصلی سایت و `wp-admin` بدون fatal error باز می‌شوند.
- [ ] **QA-003** در PowerShell مقدار `HAMKHARID_WP_ROOT` به نصب WordPress تست اشاره می‌کند.
- [ ] **QA-004** از ریشه افزونه `composer run test:all` بدون خطا پاس می‌شود.
- [ ] **QA-005** در `WooCommerce → Complement Rules` صفحه Ruleها باز می‌شود.
- [ ] **QA-006** Quick Edit و Bulk Edit برای Ruleها در دسترس نیستند؛ Trash بومی WordPress همچنان وجود دارد.

## 3. داده استاندارد تست

برای اینکه نتایج قابل تکرار باشند، fixture دستی زیر را بسازید.

### دسته‌ها

1. `QA Source Parent`
2. `QA Source Child` با والد `QA Source Parent`
3. `QA Target Parent`
4. `QA Target Child` با والد `QA Target Parent`
5. `QA Other`

### محصولات

همه محصولات زیر به‌جز مواردی که صراحتاً وضعیت دیگری دارند، Published و Catalog-visible باشند.

| کد | نام پیشنهادی           | دسته             | نوع/وضعیت                              |
|----|------------------------|------------------|----------------------------------------|
| P1 | QA Current Product     | QA Source Child  | Simple، In stock                       |
| P2 | QA Target Simple       | QA Target Parent | Simple، In stock، قیمت عادی            |
| P3 | QA Target Sale         | QA Target Child  | Simple، In stock، Sale price           |
| P4 | QA Target Variable     | QA Target Child  | Variable، حداقل یک variation قابل خرید |
| P5 | QA Target Out Of Stock | QA Target Parent | Simple، Out of stock                   |
| P6 | QA Target Backorder    | QA Target Parent | Simple، On backorder                   |
| P7 | QA Target Hidden       | QA Target Parent | Catalog visibility = Hidden            |
| P8 | QA Target Draft        | QA Target Parent | Draft                                  |
| P9 | QA Outside Product     | QA Other         | Simple، In stock                       |

برای تست `Newest` تاریخ انتشار P2، P3 و P6 را عمداً متفاوت قرار دهید. برای تست `Best Selling` روی محیط QA مقدار فروش P2/P3/P6 را طوری تنظیم کنید که ترتیب مورد انتظار مشخص باشد.

## 4. Admin CRUD

- [ ] **QA-010** از `WooCommerce → Complement Rules → Add New Complement Rule` یک Rule جدید ایجاد می‌شود.
- [ ] **QA-011** فرم شامل `Rule Name`، `Enabled`، `Source Categories`، `Target Category`، `Recommendation Count` و `Sort Strategy` است.
- [ ] **QA-012** امکان انتخاب چند Source Category وجود دارد.
- [ ] **QA-013** Target Category فقط یک مقدار می‌پذیرد.
- [ ] **QA-014** Recommendation Count در استفاده عادی بین `1` تا `6` قابل ذخیره است و مقدار پیش‌فرض `3` است.
- [ ] **QA-015** Sort Strategy فقط `Random`، `Newest` و `Best Selling` را ارائه می‌کند.
- [ ] **QA-016** Rule ذخیره‌شده بعد از reload همان Source/Target/Count/Sort را نمایش می‌دهد.
- [ ] **QA-017** ستون‌های Source، Target، Count، Sort و Status در فهرست Ruleها مقدار صحیح نشان می‌دهند.
- [ ] **QA-018** اکشن `Disable` یک Rule فعال را غیرفعال می‌کند و Status به Disabled تغییر می‌کند.
- [ ] **QA-019** اکشن `Enable` Rule غیرفعال را دوباره فعال می‌کند.
- [ ] **QA-020** Trash/Restore بومی WordPress برای Rule بدون خراب شدن داده‌ها کار می‌کند.

## 5. Matching و Priority

### Rule پایه

Rule زیر را بسازید:

- Name: `QA Parent Rule`
- Enabled: Yes
- Source: `QA Source Parent`
- Target: `QA Target Parent`
- Count: `3`
- Sort: `Newest`

سپس:

- [ ] **QA-030** صفحه P1 را باز کنید؛ با وجود اینکه P1 مستقیماً در Child است، Rule والد match می‌شود.
- [ ] **QA-031** صفحه P9 را باز کنید؛ بخش پیشنهاد Hamkharid نمایش داده نمی‌شود.
- [ ] **QA-032** Rule را Disable کنید؛ در P1 هیچ بخش پیشنهادی نمایش داده نمی‌شود.
- [ ] **QA-033** Rule را دوباره Enable کنید؛ پیشنهادها برمی‌گردند.

### Priority

Rule دیگری با Source مستقیم `QA Source Child` و همان Target بسازید. نام آن را `QA Direct Rule` بگذارید و Count را `1` قرار دهید.

- [ ] **QA-034** روی P1، Rule مستقیم Child بر Rule عمومی Parent اولویت دارد و فقط 1 پیشنهاد نمایش داده می‌شود.
- [ ] **QA-035** Rule مستقیم را Trash کنید؛ Rule والد دوباره برنده می‌شود و Count آن اعمال می‌شود.
- [ ] **QA-036** دو Rule با specificity یکسان برای `QA Source Child` بسازید؛ Rule قدیمی‌تر/ID کمتر باید انتخاب شود.
- [ ] **QA-037** محصولی بدون هیچ product category بسازید؛ نباید Rule بگیرد و صفحه باید بدون خطا نمایش داده شود.

### Category حذف‌شده

این تست را فقط با دسته موقت انجام دهید تا fixture اصلی خراب نشود.

- [ ] **QA-038** یک Source Category موقت + Rule بسازید، سپس آن دسته را حذف کنید؛ صفحه محصول باید بدون fatal error باز شود و Rule حذف‌شده match نشود.
- [ ] **QA-039** یک Target Category موقت + Rule بسازید، سپس Target را حذف کنید؛ renderer باید بدون fatal error هیچ recommendationی برای آن Rule نمایش ندهد.

## 6. Recommendation Engine

با Rule فعال `QA Source Parent → QA Target Parent` تست کنید.

- [ ] **QA-050** محصولات موجود در `QA Target Child` نیز در candidateها قرار می‌گیرند؛ Target Category شامل descendants است.
- [ ] **QA-051** P1 هرگز خودش را در recommendationها نشان نمی‌دهد، حتی اگر موقتاً به Target Category هم اضافه شود.
- [ ] **QA-052** P5 (Out of stock) نمایش داده نمی‌شود.
- [ ] **QA-053** P6 (Backorder) می‌تواند نمایش داده شود.
- [ ] **QA-054** P7 (Hidden) نمایش داده نمی‌شود.
- [ ] **QA-055** P8 (Draft) نمایش داده نمی‌شود.
- [ ] **QA-056** با Count = 1 حداکثر یک محصول نمایش داده می‌شود.
- [ ] **QA-057** با Count = 6 خروجی از 6 محصول بیشتر نمی‌شود.
- [ ] **QA-058** اگر کمتر از Count محصول واجد شرایط وجود داشته باشد، فقط همان تعداد موجود نمایش داده می‌شود و layout خراب نمی‌شود.

### Sort

- [ ] **QA-060** با `Newest` ترتیب recommendationها با جدیدترین محصولات واجد شرایط شروع می‌شود.
- [ ] **QA-061** با `Best Selling` محصولی که معیار فروش بیشتری دارد قبل از موارد کم‌فروش‌تر قرار می‌گیرد.
- [ ] **QA-062** با `Random` فقط محصولات واجد شرایط نمایش داده می‌شوند؛ با چند reload تنوع خروجی را مشاهده کنید. تکرار اتفاقی یک ترکیب به‌تنهایی failure محسوب نمی‌شود.

## 7. Frontend Rendering

- [ ] **QA-070** در صفحه P1 فقط یک section با کلاس/ظاهر Hamkharid نمایش داده می‌شود؛ duplicate section وجود ندارد.
- [ ] **QA-071** عنوان section مطابق locale فعال WordPress نمایش داده می‌شود (در فارسی: `شاید این محصولات هم برای شما مناسب باشند`) و به heading همان section متصل است.
- [ ] **QA-072** thumbnail، title و price کارت‌ها با markup/استایل معمول WooCommerce نمایش داده می‌شوند.
- [ ] **QA-073** P3 با Sale price نمایش صحیح قیمت عادی/فروش ویژه دارد.
- [ ] **QA-074** برای محصول بدون تصویر، placeholder WooCommerce نمایش داده می‌شود و layout نمی‌شکند.
- [ ] **QA-075** یک نام بسیار طولانی فارسی و یک نام طولانی انگلیسی از کارت بیرون نمی‌زند.
- [ ] **QA-076** اگر Rule match نشود یا candidate واجد شرایط وجود نداشته باشد، section خالی نمایش داده نمی‌شود.

## 8. Native Add-to-Cart

- [ ] **QA-080** P2 به‌عنوان Simple Product دکمه native Add to cart دارد.
- [ ] **QA-081** افزودن P2 به سبد از کارت recommendation مطابق رفتار WooCommerce/Theme انجام می‌شود.
- [ ] **QA-082** بعد از افزودن Simple Product، Cart/Mini-cart فروشگاه خراب یا stale نمی‌شود.
- [ ] **QA-083** P4 به‌عنوان Variable Product دکمه/لینک native `Select options` یا ترجمه WooCommerce را نشان می‌دهد.
- [ ] **QA-084** کلیک روی Variable Product کاربر را برای انتخاب variation به صفحه همان محصول می‌برد.
- [ ] **QA-085** داخل کارت Hamkharid variation selector یا فرم variation اختصاصی وجود ندارد.

## 9. RTL / LTR / Responsive

### RTL

- [x] **QA-090** fixture فارسی/RTL در ۳۲۰، ۷۰۰ و ۱۲۰۰px مرور شد؛ heading، سه کارت و CTA در گرید RTL overlap نداشتند؛ run=`m3-content-b12cf7b46acf` و تصویر ارسالی کاربر.
- [x] **QA-091** عنوان بلند فارسی با عبارت‌های لاتین و عدد در section آزموده شد؛ متن می‌شکند و horizontal overflow ایجاد نمی‌کند؛ run=`m3-content-b12cf7b46acf`.

### LTR

- [x] **QA-092** همان section در `en_US`/LTR با heading و CTA انگلیسی در ۷۰۰px نمایش داده شد؛ گرید دو ستون ماند و overflow ایجاد نشد؛ run=`m3-content-b12cf7b46acf`.

### Viewport

از DevTools یا Device Mode استفاده کنید.

- [x] **QA-093** گرید بر اساس عرض واقعی کانتینر واکنش نشان می‌دهد؛ run=`m3-content-b12cf7b46acf` در ۳۲۰/۴۲۰/۷۰۰/۱۲۰۰ CSS px به‌ترتیب ۱/۱/۲/۳ ستون نشان داد.
- [x] **QA-094** در عرض متوسط ۷۰۰px دو ستون ۲۲۲px و در ۱۲۰۰px سه ستون ۲۴۹px شدند؛ حداقل عرض کارت رعایت شد؛ شواهد run=`m3-content-b12cf7b46acf`.
- [x] **QA-095** در viewportهای باریک ۳۲۰ و ۴۲۰ CSS px گرید تک‌ستونه شد؛ شواهد run=`m3-content-b12cf7b46acf`.
- [x] **QA-096** `document.documentElement.scrollWidth` در چهار عرض دقیقاً با viewport برابر بود؛ run=`m3-content-b12cf7b46acf` و اجرای نهایی TT5/Storefront این نتیجه را پس از رفع overflow دوپیکسلی تصویر placeholder بازآزمایی کردند. E2E نهایی `551465d97590` برابر ۱۷/۱۷ پاس شد.
- [x] **QA-097** در عرض مؤثر ۳۲۰ CSS px و zoom صفحهٔ ۲۰۰٪ کنترل‌ها و محتوای ضروری قابل‌استفاده می‌مانند؛ E2E run=`1aa89a2c28a1`.

## 10. Accessibility پایه

- [x] **QA-100** فقط با `Tab` بین لینک محصول و دکمه‌های section حرکت کنید؛ E2E keyboard smoke پاس شد.
- [x] **QA-101** focus با `:focus-visible` واضح و قابل تشخیص است؛ در E2E بررسی شد.
- [x] **QA-102** ترتیب Tab منطقی است و focus trap ایجاد نمی‌شود؛ E2E smoke پاس شد.
- [x] **QA-103** heading section در hierarchy صفحه قابل تشخیص است و section label معتبر دارد.
- [x] **QA-104** axe-core 4.13.0 در run=`1aa89a2c28a1` برای WCAG 2.0/2.1/2.2 A/AA tags، در هر دو locale، zero violations گزارش کرد.

## 11. Theme و Block Compatibility

حداقل دو Theme را بررسی کنید: Storefront و یک Theme WooCommerce دیگر که برای بازار هدف/محیط تست مناسب است.

- [x] **QA-110** Storefront 4.6.2 + Classic single product: جای‌گذاری خودکار سه recommendation پس از summary یک‌بار نمایش داده شد؛ E2E=`4d1a0a7d67cb`، ۱۷/۱۷ در clone WP 7.1.3/WC 11.2.1/PHP 8.4.1. صفحهٔ shortcode Storefront نیز در ۳۲۰/۴۲۰/۷۰۰/۱۲۰۰px سه کارت بدون overflow یا bullet اضافه نشان داد.
- [x] **QA-111** قالب کلاسیک Luxury Shop + صفحهٔ محصول: auto placement در E2E قبلی `1aa89a2c28a1`؛ این run پیش از آخرین اصلاح CSS است. Storefront + shortcode و Twenty Twenty-Five shortcode/dynamic block نیز جداگانه بررسی شدند.
- [x] **QA-112** در clone مستقل WP 7.1.3/WC 11.2.1 با Twenty Twenty-Five، درخواست `woocommerce//single-product` به `twentytwentyfive//single-product` با `source=plugin` حل شد؛ صفحهٔ محصول با auto placement و سه محصول، native add-to-cart و بدون fatal error نمایش داده شد. E2E=`9d3c296f10e1`، ۱۷/۱۷؛ جزئیات در `m3-block-template-e2e/template-resolution.txt`.
- [x] **QA-113** در Twenty Twenty-Five، dynamic block با Rule منطبق در frontend نمایش داده شد؛ E2E نهایی `551465d97590`.
- [x] **QA-114 — PASS محدود به workflowهای آزموده‌شده:** SiteOrigin Page Builder 2.38.0 Classic Page Builder/Text widget مسیر Code→Visual→Done/Update→reload را حفظ کرد؛ frontend یک section/سه کارت/سه CTA، CSS 200 و صفر page/console error داشت. Elementor 4.3.5 رسمی در Apache/PHP8.4.1 + `display_errors=Off` ordinary-page Shortcode widget را ذخیره، reload/reselect و render کرد؛ default با deprecation خود افزونه editor را به login برگرداند و Apache/PHP8.1 reload/reselect spinner داشت. SiteOrigin Gutenberg Layout Block UI آزموده‌نشده و Elementor Theme Builder طبق تصمیم کاربر خارج از آزمون است. widget اختصاصی Hamkharid الزام نیست. جزئیات و screenshotها در addendum QA-114 [گزارش QA پایلوت](pilot-qa-fa.md) ثبت شده‌اند.

## 12. HPOS و Dependency Behavior

- [x] **QA-120** HPOS روشن/خاموش در QA سازگاری بررسی شد؛ Hamkharid incompatible گزارش نشد.
- [x] **QA-121** با HPOS روشن، Admin Ruleها و frontend recommendationها کار کردند.
- [x] **QA-122** در clone QA با WooCommerce غیرفعال، Hamkharid fatal error ایجاد نکرد.
- [x] **QA-123** در حالت WooCommerce غیرفعال، notice نیاز به WooCommerce دیده شد.
- [x] **QA-124** در clone محلی WP 7.1.3/WC 11.2.1، WooCommerce از API همان clone غیرفعال و دوباره فعال شد؛ storefront بدون Woo section را پنهان کرد و پس از فعال‌سازی section برگشت. هر دو پاسخ 200 و بدون fatal بودند؛ fixture پاک شد.

## 13. Permission و Admin Safety Smoke Test

- [x] **QA-130** در E2E run=`1aa89a2c28a1` حساب role=`shop_manager` توانست Rules/settings را باز کند و مدیریت کند.
- [x] **QA-131** در همان E2E کاربر Subscriber به مدیریت Ruleها دسترسی نداشت.
- [x] **QA-132** E2Eهای نهایی Firefox=`9c0587b75dfd` و Chromium=`e99dc306ab14` ساخت/ذخیره/Enable/Disable قانون و ذخیرهٔ تنظیمات manual را بدون fatal یا blank page گذراندند.

## 14. Regression نهایی End-to-End

یک Rule ساده نهایی بسازید:

```text
Name: QA Final Rule
Source: QA Source Child
Target: QA Target Parent
Count: 3
Sort: Newest
Enabled: Yes
```

سپس این جریان را کامل انجام دهید:

- [ ] **QA-140** P1 را باز کنید و دقیقاً تا 3 recommendation واجد شرایط مشاهده کنید.
- [ ] **QA-141** Out-of-stock/Hidden/Draft در خروجی نیستند.
- [ ] **QA-142** Target Child در خروجی قابل استفاده است.
- [ ] **QA-143** Simple Product از کارت به سبد اضافه می‌شود.
- [ ] **QA-144** Variable Product به Select options هدایت می‌شود.
- [ ] **QA-145** Rule را Disable کنید؛ section ناپدید می‌شود.
- [ ] **QA-146** Rule را Enable کنید؛ section دوباره ظاهر می‌شود.
- [ ] **QA-147** Rule را Trash کنید؛ section ناپدید می‌شود و صفحه محصول بدون خطا باقی می‌ماند.
- [ ] **QA-148** Rule را Restore/Publish کنید؛ رفتار صحیح برمی‌گردد.
- [ ] **QA-149** پس از پایان QA، `composer run test:all` را یک بار دیگر اجرا کنید و نتیجه سبز را ثبت کنید.

## 15. ثبت نتیجه

هر failure را پیش از پذیرش نهایی با اطلاعات زیر ثبت کنید:

```text
QA ID:
Status: FAIL / BLOCKED
Environment:
Theme:
Browser + viewport:
Rule configuration:
Product/category IDs or names:
Steps to reproduce:
Expected:
Actual:
Console/PHP error (if any):
Screenshot reference (if useful):
```

### معیار قبولی Phase 15

Phase 15 فقط زمانی Pass است که:

- همه موارد Critical در Admin/Matching/Recommendation/Add-to-Cart پاس باشند؛
- هیچ fatal error، duplicate output یا recommendation نامعتبر باقی نمانده باشد؛
- RTL/LTR و سه viewport اصلی usable باشند؛
- Storefront و حداقل یک Theme دوم بررسی شده باشند؛
- HPOS و WooCommerce dependency smoke test پاس باشند؛
- failureهای غیر بحرانی با QA ID ثبت و برای تصمیم M4 مشخص شده باشند؛
- اجرای پایانی `composer run test:all` سبز باشد.

## 16. پوشش QA مرورگر خودکار (Phase 15.1)

این اتوماسیون جایگزین QA بصری Theme/Block/Elementor نیست. هدف آن گرفتن regressionهای پرتکرار در مرورگر واقعی Chromium است. وضعیت فعلی اجرای گزارش‌شده پایین همین بخش آمده است.

پیش‌نیاز یک‌باره:

```powershell
npm install
npm run e2e:install
```

runner به‌صورت پیش‌فرض Chromium را اجرا می‌کند. برای اجرای cross-browser، engineهای لازم را جداگانه نصب کنید و گزینهٔ مرورگر را بدهید:

```powershell
npx playwright install firefox webkit
npm run test:e2e -- --browser=firefox
npm run test:e2e -- --browser=webkit
```

اجرای headless:

```powershell
$env:HAMKHARID_WP_ROOT='D:\laragon\www\wordpress'
npm run test:e2e
```

برای مشاهده اجرای مرورگر:

```powershell
npm run test:e2e:headed
```

Runner داده‌های موقت، Administrator و Subscriber اختصاصی می‌سازد و برای بازبینی بصری بعد از تست نگه می‌دارد. داده‌های موجود فروشگاه را برای fixture استفاده نمی‌کند. اجرای بعدی ابتدا fixtureهای E2E قبلی را پاک می‌کند و مجموعه تازه می‌سازد. پس از پایان بازبینی بصری، داده‌های QA را با `npm run e2e:cleanup` حذف کنید.

آخرین پوشش smoke تأییدشده `1aa89a2c28a1` (۱۷/۱۷ سناریو؛ اجرا پیش از commit `5d468e0` با همان منطق تست و تفاوت whitespace PHPCBF):

- Admin: `QA-005`, `QA-006`, `QA-010` تا `QA-019`
- Matching/Rendering: `QA-032`, `QA-033`, `QA-050` تا `QA-057`, `QA-070`, `QA-071`, `QA-076`, `QA-145`, `QA-146`
- Add-to-Cart: `QA-080`, `QA-081`, `QA-083` تا `QA-085`
- Responsive: `QA-093` تا `QA-097`؛ QA-097 در عرض مؤثر ۳۲۰ CSS px و zoom صفحهٔ ۲۰۰٪.
- Accessibility smoke: `QA-100` تا `QA-104`؛ QA-104 اجرای axe-core 4.13.0 برای tagهای `wcag2a/aa`, `wcag21a/aa`, `wcag22a/aa` است.
- Permission smoke: `QA-130` (Shop Manager واقعی)، `QA-131` (Subscriber denial)

پوشش اجرا شامل admin preview، manual mode بدون auto placement، shortcode و dynamic block در editor و frontend، قالب کلاسیک Luxury Shop و Twenty Twenty-Five، `en_US`/LTR و `fa_IR`/RTL بود. Keyboard/focus smoke و سنجش contrast عنوان/قیمت/دکمه پاس شدند. axe-core 4.13.0 در هر دو locale برای tagهای `wcag2a/aa`, `wcag21a/aa`, `wcag22a/aa` صفر تخلف گزارش کرد. بازبینی screenshotهای ۱۲۰۰px desktop و ۴۲۰px mobile نیز contrast کارت و layout را تأیید کرد. Cleanup exit 0 داشت و fixtureها پاک شدند.

Elementor 4.3.5 در retry clean با ZIP رسمی، Apache/PHP8.4.1 + `display_errors=Off` save/reload/reselect/render شد؛ default deprecation خود افزونه پیش از header و Apache/PHP8.1 reload spinner جدا ثبت شده‌اند. SiteOrigin 2.38.0 Classic Page Builder/Text widget از مسیر Code→Visual، Done/Update و reload ذخیره شد؛ frontend یک section/سه کارت/سه CTA و CSS 200 داشت. QA-200..204 با browser preview/form پاس شدند؛ جزئیات در بخش ۱۸ و گزارش پایلوت ثبت شده‌اند. Gutenberg Layout Block UI آزموده‌نشده است و Theme Builder طبق تصمیم کاربر آزموده‌نشده می‌ماند. این ماتریس پوشش جامع همهٔ نسخه‌ها/قالب‌ها/builderها نیست. گزارش کاربر از Narrator برای QA-207 ثبت شده است. QAهای ثبت‌شده HPOS روشن/خاموش، WooCommerce فعال/غیرفعال و reactivation، widget area و multi-embed/duplicate را پوشش دادند؛ E2E دسترسی Shop Manager به rules/settings را گذراند. Benchmark مقیاس PASS محدود محلی E-15 است و SLA عمومی نیست؛ variable-heavy test فقط exploratory است.

## 17. تکمیل QA پس از اجرای نیازمندی‌های جدید

وضعیت تاریخی runهای قبلی در گزارش QA آمده؛ نتایج نهایی M3 با runهای بخش ۱۸ به‌روز شدند. QA-200..204 با شاهد مستقیم preview/form اجرا شده‌اند. SiteOrigin Classic Page Builder/Text widget مسیر Code→Visual save/reload/render را گذراند. Elementor clean با ZIP رسمی و بدون shim در Apache/PHP8.4 با `display_errors=Off` save/reload/reselect/render را گذراند؛ default deprecation خود افزونه و PHP8.1 reload spinner جدا ثبت شده‌اند. SiteOrigin Gutenberg Layout Block UI تأیید نشده است. Theme Builder به تصمیم کاربر آزموده‌نشده می‌ماند. ZIP SHA پیش از اصلاح count locale `7f4868b8bd54fd772d4bbf5fb8c5ef6044b4c028baf8c9de400c270dc62f884b` lifecycle شده بود؛ artifact جدید پس از تغییر source باید جداگانه build و lifecycle شود. Browser uploader و downgrade ZIP تاریخی آزموده‌نشده می‌مانند.

- [x] **QA-200 / FR-03** در admin preview، source مستقیم با priority 5 بر والد با priority 100 برنده شد؛ در tie هم‌سطح با priority برابر، rule قدیمی دارای missing priority (default legacy=0) برنده شد. screenshot/result در `qa/m3-current-build/admin-manual/qa-200-*`.
- [x] **QA-201 / FR-04** target مستقیم حذف‌شده ابتدا به rule معتبر بعدی همان سطح و سپس به والد رسید؛ target معتبر ولی خالی برنده ماند و notice no-eligible-products نشان داد، بدون fallback والد. screenshot/result در `admin-manual/qa-201-*`.
- [x] **QA-202 / FR-02** browser POST نام خالی، source خالی، target خالی و count نامعتبر، rule قبلی را publish/enabled نگه داشت؛ مقادیر نام/source/count حفظ شدند و پس از اصلاح، target خالی هم بعد از reload خالی ماند. `fa_IR`: ورودی count `۴` به integer 4 و priority عربی‌هندی `٢` به 2 تبدیل و در فرم/جدول با رقم فارسی نمایش داده شد؛ `en_US` مقدار ASCII `5` را ASCII نگه داشت. Chromium pageerrors=0. نتیجه و screenshotهای قبل/بعد در `qa/m3-current-build/admin-manual/` و `admin-manual-postfix/qa-202-postfix-*`.
- [x] **QA-203 / FR-05/06** preview ساده/متغیر واجد شرایط را در count=3 refill کرد؛ no-price، draft، external، grouped و variable با فقط variation خارج از stock حذف شدند؛ backorder با setting خاموش حذف و با روشن‌شدن تنظیم UI اضافه شد. با WooCommerce API یک parent متغیر purchasable و ۵۰۱ variation واقعی ساخته شد؛ ۵۰۰ مورد اول in-stock ولی بدون قیمت و مورد ۵۰۱ واجد قیمت بود. preview واقعی هشدار سقف ۵۰۰ candidate را نشان داد و گزینه‌های واجد شرایط را حفظ کرد؛ pageerrors=0. شاهد cap واقعی `admin-manual-postfix/qa-203-real-501-preview.json/png` است؛ probe قبلی query-hook فقط exploratory بود و معیار پذیرش محسوب نمی‌شود.
- [x] **QA-204 / FR-07** preview معتبر با همان engine فروشگاه سه گزینه را برگرداند؛ nonce نادرست/منقضی notice «preview expired» داد؛ محصول بدون rule پیام no-match داد؛ Subscriber پاسخ 403 گرفت. snapshot پیش/پس rule/settings یکسان و cart در هر دو حالت خالی ماند؛ app pageerrors=0. شواهد `admin-manual/qa-204-safety-result.json` و screenshotهای `qa-204-*`.
- [x] **QA-205 / FR-10 — browser smoke subset** runهای Chromium=`e99dc306ab14`، Firefox=`9c0587b75dfd` و WebKit=`77ff991fe78b` هرکدام ۱۸/۱۸؛ حالت manual، preview، shortcode و dynamic block در editor/frontend و fa_IR/RTL + en_US/LTR را گذراندند. حداقل stack نیز در Chromium=`9e4eb28921dc`، Firefox=`82db66ce7382` و WebKit=`6fa74840fa1e` روی WP 6.2.14/WC 8.2.0/PHP 7.4.33 هرکدام ۱۸/۱۸ پاس شد. Elementor page-level Shortcode widget در یک run قبلی با shim محلی به‌عنوان ذخیره/render گزارش شد؛ clean retry با ZIP رسمی در PHP 8.4.1 و variant `display_errors=Off` به save/render نرسید. SiteOrigin Classic Page Builder/Text widget با Code→Visual save/reload/render پاس شد. Gutenberg Layout Block UI، Theme Builder و پوشش جامع builderها تأیید نشده‌اند.
- [x] **QA-206 / FR-08/10 — subset** در همان سه run مرورگری، shortcode + block + shortcode تکراری روی دو محصول منبع آزمایش شد: دقیقاً دو section یکتا، هرکدام سه محصول؛ heading IDها با product ID مورد انتظار تطابق داشتند و `aria-labelledby` به heading همان section اشاره کرد؛ asset CSS حاضر بود. این شاهد رفتار چند embed است، نه پوشش همهٔ containerها یا builderهای ممکن. تمام موارد یکتایی/تکرار در این subset پاس شدند.
- [x] **QA-207 / NFR-03/05** در fixture محلی، عنوان بلند فارسی/لاتین/عدد و SKU در صفحهٔ محصول و کارت بدون overflow در ۳۲۰/۴۲۰/۷۰۰/۱۲۰۰px (`fa_IR`) و ۷۰۰px (`en_US`)؛ قیمت raw/regular=`29` با `currency=IRR`؛ دکمه‌ها با outline `solid 2px` و نام accessible کامل در Tab sequence. کاربر بصری جا شدن سه کارت/عنوان/دکمه‌ها و نبود overflow را تأیید کرد و سپس گزارش داد Narrator heading، نام بلند محصول، قیمت و نام/نقش دکمه را اعلام کرده است (۲۰۲۶-۱۰-۲۸). QA-097 zoom ۲۰۰٪ در عرض مؤثر ۳۲۰px نیز پاس است. شواهد DOM/keyboard/E2E و تأییدهای کاربر در گفتگو؛ فایل‌های `m3-responsive-long-content.txt`, `m3-product-detail-metrics.txt`, `m3-keyboard-product-section.txt`, `m3-final-e2e-php84.log` و `m3-user-visual-confirmation.png` در پوشهٔ QA.
- [x] **QA-208 / FR-09** در artifact جاری SHA `7f4868b8bd54fd772d4bbf5fb8c5ef6044b4c028baf8c9de400c270dc62f884b`، uninstall پیش‌فرض rule/settings و product/category/order را حفظ کرد؛ opt-in فقط rule/settings را حذف کرد و داده‌های WooCommerce ماندند.
- [x] **QA-209 / NFR-04/07 — ZIP lifecycle subset** ZIP SHA `7f4868b8bd54fd772d4bbf5fb8c5ef6044b4c028baf8c9de400c270dc62f884b` با WordPress core `Plugin_Upgrader` در clone نصب و با `overwrite_package` جایگزین شد؛ marker version مصنوعی `0.0.9`، حفظ داده، shortcode render، uninstall پیش‌فرض/opt-in و rollback snapshot pass شدند. دو build مستقل hash برابر دادند و manifest با ZIP منطبق بود. Browser uploader و downgrade با ZIP تاریخی آزموده نشدند. جزئیات در [گزارش QA پایلوت](pilot-qa-fa.md).
- [x] **QA-211 / NFR-04/07 — compatibility subset** WP 6.2.14/WC 8.2.0/PHP 7.4.33 و WP 7.1.3/WC 11.2.1/PHP 8.4.1؛ Playwright Chromium 151.0.7922.34, Firefox 153.0, WebKit 26.5. E2Eها در engineهای جاری و حداقل هرکدام ۱۸/۱۸ شدند؛ runهای حداقل Firefox=`82db66ce7382` و WebKit=`6fa74840fa1e` هستند. PHP 8.1 full suite قبلی، HPOS روشن/خاموش، WooCommerce خاموش/روشن (شامل QA-124)، Storefront، Luxury Shop و Twenty Twenty-Five نیز ثبت‌اند. SiteOrigin Classic Page Builder/Text widget در Code→Visual save/reload/render پاس شد. Elementor 4.3.5 رسمی در Apache/PHP8.4.1 + `display_errors=Off` page-level save/reload/reselect/render را گذراند؛ default deprecation خود افزونه و PHP8.1 reload spinner ثبت شده‌اند. بسته حداقل WP 6.8 می‌خواهد، پس clone حداقل WP6.2.14 برای Elementor معتبر نیست. این ماتریس تمام نسخه‌های مرورگر، سیستم‌عامل‌ها یا قالب‌ها را پوشش نمی‌دهد. Tab traversal در WebKit جداگانه پاس ادعا نمی‌شود؛ focus outline و فعال‌سازی Enter در آن engine بررسی شد، Tab traversal در runهای Chromium=`8c532c357888` و Firefox=`5ef9edb25fc3` بررسی شده است.
- [x] **QA-210 / NFR-02 — local envelope only** benchmark ماتریس با gate محلی E-15، طبق انتخاب صریح کاربر برای پذیرش محدود به همین محیط، PASS است؛ پیکربندی محیط، thresholdها و maxima در [گزارش QA پایلوت](pilot-qa-fa.md) آمده‌اند. این پذیرش SLA عمومی/محصولی نیست. Variable-heavy n=5 exploratory است و از پذیرش خارج است.

gateهای PHP/JS، rendering، سه engine جاری و حداقل، QA-114/124/200..211، multi-embed subset و گزارش Narrator پاس شدند؛ M3 در معیارهای ثبت‌شده تکمیل است. QA-114 برای مسیرهای محدود SiteOrigin و Elementor ثبت شد؛ QA-200..204 با preview/form مستقیم اجرا و پس از اصلاح ورودی locale پاس شدند. SiteOrigin Gutenberg Layout Block UI آزموده‌نشده است. جزئیات/حدود در بخش ۱۸ و [گزارش QA](pilot-qa-fa.md) است. Elementor Theme Builder طبق تصمیم کاربر آزموده‌نشده می‌ماند؛ Tab traversal در WebKit و همهٔ containerها/page builderها نیز ادعا نمی‌شوند. ZIP قبلی lifecycle شده بود، اما source بعداً برای localized count اصلاح شد؛ artifact تازه باید build و lifecycle شود. Browser file chooser و downgrade ZIP تاریخی آزموده‌نشده‌اند. clone QA پس از اجرا از snapshot برگشت: markerها صفر، locale/theme به `en_US`/`twentytwentyfive` و فقط Hamkharid/WooCommerce فعال ماندند. fixture اصلی `wordpress.test` دست‌نخورده ماند. NFR-02 فقط در envelope محلی E-15 پاس است؛ این گزارش مجوز انتشار/تحویل نیست.

## 18. تکمیل M3 — مرورگر، نسخهٔ حداقل و وابستگی

تاریخ اجرا: ۲۰۲۶-۱۰-۲۸. همهٔ E2Eها روی cloneهای محلی با fixture مصنوعی اجرا شدند. در cloneهای مرورگر، `WP_HTTP_BLOCK_EXTERNAL=true` بود تا update/API وردپرس برای شبکهٔ بیرونی منتظر نماند؛ این تغییر فقط در clone QA بود.

| ماتریس | اجرا | نتیجه |
|---|---|---|
| WP 7.1.3 / WC 11.2.1 / PHP 8.4.1، Playwright Chromium 151.0.7922.34 | run=`e99dc306ab14`، ۱۸/۱۸ | CRUD، matching/render، cart، ۴ viewport + zoom ۲۰۰٪، keyboard/focus، axe WCAG A/AA، role smoke، manual/preview، shortcode/block editor/frontend، دو locale و QA-206 |
| همان stack، Firefox 153.0 | run=`9c0587b75dfd`، ۱۸/۱۸ | همان مسیرهای E2E؛ QA-205/206 پاس |
| همان stack، WebKit 26.5 | run=`77ff991fe78b`، ۱۸/۱۸ | focus outline، فعال‌سازی Enter و axe پاس؛ دنبالهٔ Tab در این محیط WebKit به‌عنوان pass ثبت نشده است |
| WP 6.2.14 / WC 8.2.0 / PHP 7.4.33 / Twenty Twenty-Three، Chromium | run=`9e4eb28921dc`، ۱۸/۱۸ | E2E کامل شامل admin، matching/cart، responsive/zoom، axe، role، RTL/LTR، editor و multi-embed |
| WP 7.1.3 / WC 11.2.1، reactivation | QA-124 local | با Woo غیرفعال frontend پاسخ 200، بدون section/fatal؛ پس از فعال‌سازی مجدد section برگشت. این probe برای storefront بود؛ admin Plugins screen به‌علت وابستگی API WordPress.org از این آزمون کنار گذاشته شد. |

در حداقل WordPress، `hamkharid-fa_IR.mo` قبلی با POMO parser وردپرس 6.2 خوانده نمی‌شد: جهت سند RTL می‌شد ولی heading انگلیسی می‌ماند. فایل از همان `languages/hamkharid-fa_IR.po` با MO compiler خود WP 6.2.14 دوباره تولید شد؛ hash نسخهٔ اصلاح‌شده `6e06c2d508095702e7cc16bfc9f4eef0be285019cb960cc75c3b451feb6d5676` است. پس از اصلاح، boot واقعی WP 6.2.14 مقدار `locale=fa_IR`, `rtl=true` و heading فارسی را داد؛ E2E حداقل‌نسخه نیز ۱۸/۱۸ پاس شد و کل `composer run test:all` دوباره سبز شد.

شواهد تصویری browser runها در `C:\Users\HP\.codex\visualizations\2026\10\27\01a17ea3-2fb8-7403-b60e-3f15d61b0241\qa\m3-browser-chromium-final`, `...\m3-browser-firefox-final`, `...\m3-browser-webkit` و `...\m3-minimum-stack\artifacts-final` هستند. در پایان cloneهای WP 7.1.3 و WP 6.2.14 fixture marker post/user هر دو صفر، WooCommerce فعال، locale=`en_US` و تنظیم mode/theme به مقدار snapshot بازگشته بودند. هیچ دادهٔ هاست یا واقعی استفاده نشد.
