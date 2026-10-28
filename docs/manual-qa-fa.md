# چک‌لیست QA دستی افزونه هم‌خرید

> مرجع فعلی محصول و توسعه: [فهرست مستندات](README.md)، [PRD](prd-fa.md) و [وضعیت و نقشه راه](roadmap-fa.md). شماره‌های Phase 15/16 در این سند نام‌گذاری تاریخی‌اند، نه شاهد تکمیل. E2E نهایی `551465d97590` پس از دو اصلاح CSS پاس شد و کاربر Narrator را برای QA-207 تأیید کرد. ZIP قبلی M4 و native locale-driven `fa_IR` RTL در clone pass شدند؛ artifact ZIP جاری پس از CSS هنوز lifecycle نشده است. Chrome uploader آزموده نشد.
>
> اصلاح دامنه ۲۰۲۶-۱۰-۲۷: طبق D-08، بلوک پویا و shortcode در MVP قرار دارند و جای‌گذاری به قالب خاص محدود نمی‌شود. E2E run=`1aa89a2c28a1` روی working tree پیش از commit `5d468e0`، ۱۷/۱۷ سناریو را گذراند؛ منطق تست با commit tracked/pushed یکسان است و فقط whitespace اصلاح‌شده با PHPCBF تفاوت دارد. پوشش شامل QA-097، QA-130 دسترسی واقعی Shop Manager و axe-core 4.13.0 در `en_US`/LTR و `fa_IR`/RTL با صفر تخلف در tagهای WCAG 2.0/2.1/2.2 A/AA بود. بازبینی screenshotهای ۱۲۰۰/۴۲۰px contrast کارت و layout را تأیید کرد و fixtureها پاک شدند. M4 lifecycle برای ZIP SHA `9d6b427da69510ceb89e82cc29d644219615b55e6c4ca54e54ca393cf71c6ee6` از source تمیز `7b3ac0f4d7d8f1ca6bd5a7ef1c87e3842de1ae92` پاس شد؛ native `fa_IR` RTL در clone نیز pass شد. Chrome uploader آزموده نشد.

این سند برای QA دستی MVP افزونه **Smart Category Complement – Hamkharid** است. هدف این مرحله، تأیید رفتار واقعی افزونه در WordPress/WooCommerce و ثبت یک اجرای قابل تکرار قبل از آماده‌سازی مارکت است.

> برچسب Phase 15/16 از چک‌لیست قدیمی مانده است؛ ZIP قبلی M4 lifecycle شد اما CSS فعلی پس از آن تغییر کرده و ZIP جاری هنوز باید lifecycle شود. Elementor page-level smoke پاس است؛ Narrator، Elementor Theme Builder و چند gate دیگر M3 بازند.

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

- [ ] **QA-110** Storefront + Classic single product: recommendationها در محل صحیح و فقط یک بار نمایش داده می‌شوند. Storefront با صفحهٔ shortcode جداگانه smoke شد؛ سه کارت در ۳۲۰/۴۲۰/۷۰۰/۱۲۰۰px بدون overflow و bullet اضافه نمایش داده شدند، اما این معیار hook صفحهٔ محصول را می‌خواهد.
- [x] **QA-111** قالب کلاسیک Luxury Shop + صفحهٔ محصول: auto placement در E2E قبلی `1aa89a2c28a1`؛ این run پیش از آخرین اصلاح CSS است. Storefront + shortcode و Twenty Twenty-Five shortcode/dynamic block نیز جداگانه بررسی شدند.
- [ ] **QA-112** یک Block Theme / blockified Single Product را تست کنید؛ صفحه بدون fatal error باز می‌شود.
- [x] **QA-113** در Twenty Twenty-Five، dynamic block با Rule منطبق در frontend نمایش داده شد؛ E2E نهایی `551465d97590`.
- [ ] **QA-114** صفحهٔ Elementor با Shortcode widget و dynamic block مرورگری render شد؛ Product Template در Theme Builder بررسی نشد، چون Elementor Pro در clone در دسترس نیست؛ کاربر خواست این بخش آزموده‌نشده ثبت شود. Widget اختصاصی Hamkharid وجود ندارد.

## 12. HPOS و Dependency Behavior

- [x] **QA-120** HPOS روشن/خاموش در QA سازگاری بررسی شد؛ Hamkharid incompatible گزارش نشد.
- [x] **QA-121** با HPOS روشن، Admin Ruleها و frontend recommendationها کار کردند.
- [x] **QA-122** در clone QA با WooCommerce غیرفعال، Hamkharid fatal error ایجاد نکرد.
- [x] **QA-123** در حالت WooCommerce غیرفعال، notice نیاز به WooCommerce دیده شد.
- [ ] **QA-124** WooCommerce را دوباره فعال کنید؛ Hamkharid بدون نیاز به repair دستی به کار برمی‌گردد.

## 13. Permission و Admin Safety Smoke Test

- [x] **QA-130** در E2E run=`1aa89a2c28a1` حساب role=`shop_manager` توانست Rules/settings را باز کند و مدیریت کند.
- [x] **QA-131** در همان E2E کاربر Subscriber به مدیریت Ruleها دسترسی نداشت.
- [ ] **QA-132** Enable/Disable و Save در استفاده معمول، redirect/fatal/blank page ایجاد نمی‌کنند.

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

موارد باز شامل Screen Reader گفتاری، Elementor Theme Builder و ماتریس گسترده مرورگر/نسخه است. Elementor 4.3.5 در صفحهٔ عادی با Shortcode widget و dynamic block smoke شد. QAهای ثبت‌شده HPOS روشن/خاموش، WooCommerce فعال/غیرفعال، widget area و multi-embed/duplicate را پوشش دادند؛ E2E دسترسی Shop Manager به rules/settings را گذراند. Benchmark مقیاس PASS محدود محلی E-15 است و SLA عمومی نیست؛ variable-heavy test فقط exploratory است.

## 17. تکمیل QA پس از اجرای نیازمندی‌های جدید

وضعیت: PHP full suite و JS checks پاس شدند؛ PHP 8.1 full suite و E2E ۱۷/۱۷ (پیش از اصلاح marker)، PHP 8.4 E2E نهایی `551465d97590` با دو اصلاح CSS و regression جدید نیز ۱۷/۱۷ پاس شدند. E2Eهای پایه axe/WCAG A/AA، Shop Manager و RTL/LTR را پوشش دادند. کاربر Narrator را برای QA-207 تأیید کرد. Storefront shortcode و Elementor page-level smoke پاس‌اند؛ QA-110 classic single product در Storefront، QA-112 blockified product template و Elementor Theme Builder بازند. ZIP SHA `9d6b427da69510ceb89e82cc29d644219615b55e6c4ca54e54ca393cf71c6ee6` مربوط به artifact پیش از CSS جاری است و lifecycle آن نتیجهٔ ZIP تازه را ثابت نمی‌کند. وجود تست یا تیک به‌تنهایی معیار پذیرش نیست.

- [ ] **QA-200 / FR-03** دسته مستقیم، والد نزدیک، priority هم‌سطح و tie-ID؛ قواعد قدیمی بدون meta همان نتیجه قبلی را بدهند.
- [ ] **QA-201 / FR-04** مقصد حذف‌شده به قانون معتبر بعدی همان سطح و سپس والد برسد؛ مقصد معتبر ولی خالی به قانون دیگر fallback نکند؛ دلیل در preview دیده شود.
- [ ] **QA-202 / FR-02** POST نامعتبر، نام/مبدأ/مقصد نامعتبر و مقدار تعداد فارسی؛ ورودی فرم حفظ و قانون سالم قبلی ناخواسته غیرفعال نشود. وجود normalization در Repository جایگزین پذیرش ورودی نامعتبر نیست.
- [ ] **QA-203 / FR-05/06** ساده/متغیر با variationهای موجود و ناموجود، backorder روشن/خاموش و محصول بی‌قیمت؛ خارجی/گروهی حذف؛ ظرفیت با محصول واجد شرایط و query محدود پر شود. سقف variation: حداکثر ۵۰۰ candidate در هر `recommend()`، page حداکثر ۲۴؛ رسیدن به سقف باید در preview هشدار دهد. regression مصنوعی ۵۰۱ variation در اجرای تازه `composer run test:all` پاس شد؛ آزمون دستی preview/لبه‌های دیگر همچنان جداست.
- [ ] **QA-204 / FR-07** preview همان موتور فروشگاه، بدون تغییر rule/cart، با احراز capability/nonce؛ نمایش علت no-match و no-eligible-products.
- [x] **QA-205 / FR-10 — browser smoke subset** E2E نهایی `551465d97590`، ۱۷/۱۷، manual mode، shortcode و block/editor/frontend را پوشش داد؛ smoke جداگانهٔ Elementor 4.3.5 نیز Shortcode widget و dynamic block را render کرد. product_id/contextهای لبه و Theme Builder هنوز جداگانه باز هستند.
- [ ] **QA-206 / FR-08/10 — subset** ناحیهٔ widget موجود و multi-embed/duplicate behavior در QAهای ثبت‌شده پوشش یافت؛ Elementor 4.3.5 در صفحهٔ معمولی Shortcode widget و dynamic block را render کرد. همهٔ کانتینرها و یکتایی heading ID در ماتریس کامل هنوز gate باز دارند.
- [x] **QA-207 / NFR-03/05** در fixture محلی، عنوان بلند فارسی/لاتین/عدد و SKU در صفحهٔ محصول و کارت بدون overflow در ۳۲۰/۴۲۰/۷۰۰/۱۲۰۰px (`fa_IR`) و ۷۰۰px (`en_US`)؛ قیمت raw/regular=`29` با `currency=IRR`؛ دکمه‌ها با outline `solid 2px` و نام accessible کامل در Tab sequence. کاربر بصری جا شدن سه کارت/عنوان/دکمه‌ها و نبود overflow را تأیید کرد و سپس گزارش داد Narrator heading، نام بلند محصول، قیمت و نام/نقش دکمه را اعلام کرده است (۲۰۲۶-۱۰-۲۸). QA-097 zoom ۲۰۰٪ در عرض مؤثر ۳۲۰px نیز پاس است. شواهد DOM/keyboard/E2E و تأییدهای کاربر در گفتگو؛ فایل‌های `m3-responsive-long-content.txt`, `m3-product-detail-metrics.txt`, `m3-keyboard-product-section.txt`, `m3-final-e2e-php84.log` و `m3-user-visual-confirmation.png` در پوشهٔ QA.
- [x] **QA-208 / FR-09** در ZIP با SHA `9d6b427da69510ceb89e82cc29d644219615b55e6c4ca54e54ca393cf71c6ee6`، uninstall پیش‌فرض rule/settings و product/category/order را حفظ کرد؛ opt-in فقط rule/settings را حذف کرد و داده‌های WooCommerce ماندند.
- [x] **QA-209 / NFR-04/07 — ZIP lifecycle subset** ZIP SHA `9d6b427da69510ceb89e82cc29d644219615b55e6c4ca54e54ca393cf71c6ee6` از source تمیز `7b3ac0f4d7d8f1ca6bd5a7ef1c87e3842de1ae92` با هستهٔ WordPress `Plugin_Upgrader` نصب/نصب مجدد، update شبیه‌سازی‌شده با header marker `0.0.9`، uninstall پیش‌فرض/opt-in، rollback clone و نصب نهایی شد؛ HHQA fixture صفر ماند. Chrome uploader به‌دلیل policy انتخاب فایل محلی آزموده نشد؛ downgrade با ZIP تاریخی واقعی نبود. شواهد در [گزارش QA پایلوت](pilot-qa-fa.md).
- [x] **QA-211 / NFR-04/07 — compatibility subset** HPOS روشن/خاموش و WooCommerce فعال/غیرفعال آزموده شدند؛ E2E run=`1aa89a2c28a1` دسترسی واقعی role=`shop_manager` را نیز تأیید کرد.
- [x] **QA-210 / NFR-02 — local envelope only** benchmark ماتریس با gate محلی E-15، طبق انتخاب صریح کاربر برای پذیرش محدود به همین محیط، PASS است؛ پیکربندی محیط، thresholdها و maxima در [گزارش QA پایلوت](pilot-qa-fa.md) آمده‌اند. این پذیرش SLA عمومی/محصولی نیست. Variable-heavy n=5 exploratory است و از پذیرش خارج است.

gateها: suite PHP/JS، E2Eهای ثبت‌شده، Storefront و Elementor page-level smoke پاس شدند؛ E2E نهایی `551465d97590` پس از اصلاح overflow تصویر و marker فهرست ۱۷/۱۷ است. کاربر تأیید Narrator را برای heading/نام/قیمت/دکمه گزارش کرد. Elementor Theme Builder و ماتریس نسخه بازند؛ Chrome uploader محدودیت دارد. ZIP قبلی SHA-256 `9d6b427da69510ceb89e82cc29d644219615b55e6c4ca54e54ca393cf71c6ee6` پیش از CSS جاری lifecycle شد و artifact جاری باید دوباره build/lifecycle شود. native RTL در clone با language pack رسمی `fa_IR` پاس شد. در clone Storefront fixtureها صفر، locale=`en_US`، theme=`twentytwentyfive`، mode=`manual` و فقط افزونهٔ هم‌خرید/WooCommerce فعال بود. fixture اصلی `wordpress.test` پس از گزارش Narrator پاک‌سازی شد؛ locale=`fa_IR`، theme=`luxury-shop`، mode=`manual` و markerهای fixture صفر تأیید شدند. NFR-02 فقط در envelope محلی E-15 پاس است؛ آمادگی کلی پایلوت اعلام نمی‌شود. اجرای integration/E2E و پاک‌سازی فقط روی محیط QA محلی و fixtureهای آزمایشی باشد.
