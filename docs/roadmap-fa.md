# وضعیت، نقشه راه و بک‌لاگ اجرایی

مرجع: [فهرست](README.md)، [پذیرش محصول](prd-fa.md)، [تصمیم‌های نهایی](decisions-fa.md). بازبینی شواهد: ۲۰۲۶-۱۰-۲۸.

## وضعیت واقعی و حدود شاهد

روی source/test commit `5d468e0c2909f3ef78088ebeccae8db008e33766` (tracked و pushed)، `composer run test:all` exit 0 داشت (PHPCS 28/28، PHPUnit 8/8 با 13 assertion، integration 12/12)؛ `npm run check:js` و `node --check` نیز پاس شدند. E2E `1aa89a2c28a1` با Browser QA برابر 17/17 پاس شد، از جمله QA-097، دسترسی واقعی Shop Manager در QA-130 و axe-core 4.13.0 برای `en_US`/LTR و `fa_IR`/RTL با zero violations در tagهای WCAG 2.0/2.1/2.2 A/AA. اجرای E2E پیش از commit بود؛ منطق تست یکسان است و تنها تغییر commit‌شده whitespace اصلاح‌شده با PHPCBF است. بازبینی بصری screenshotهای 1200/420px، contrast کارت و layout را تأیید کرد؛ fixtureها پاک شدند و به داده واقعی یا هاست دست زده نشد. HPOS روشن/خاموش، WooCommerce فعال/غیرفعال، widget area و multi-embed در QAهای ثبت‌شده پوشش دارند. Elementor مرورگری و screen reader بازند. SHA/revision بستهٔ تازه pending است و M4 artifact جاری تا rebuild/lifecycle باز می‌ماند. NFR-02/E-15 فقط برای envelope محلی اندازه‌گیری‌شده پاس است، نه SLA عمومی/محصولی.

| موضوع | شاهد فعلی | فاصله تا هدف |
|---|---|---|
| قواعد و مدیریت | اولویت، fallback، validation، حفظ فرم نامعتبر، تنظیمات و preview پیاده شده | integrationهای admin/security پاس شدند؛ E2E `1aa89a2c28a1` دسترسی واقعی Shop Manager به rules/settings را تأیید کرد |
| محصول واجد شرایط | simple/variable، purchasable/visible/stock، backorder تنظیم‌پذیر و refill محدود | integrationها پاس؛ عملکرد فقط با fixture کوچک سنجیده شده و پذیرش benchmark مقیاس نیست |
| نمایش | shortcode، dynamic block، renderer مشترک، manual/automatic و CSS خارج صفحه محصول | E2E `1aa89a2c28a1` (17/17) shortcode/block editor/frontend، preview و حالت manual را در دو قالب smoke کرد؛ widget area و multi-embed هم در QA ثبت‌شده پوشش دارند. Elementor و page builderهای خاص pending هستند |
| فارسی | ترجمه PHP/JS؛ `en_US`/LTR و `fa_IR`/RTL، QA-097 در ۳۲۰ CSS px/zoom صفحهٔ ۲۰۰٪ | axe-core 4.13.0 در هر دو locale برای tagهای WCAG 2.0/2.1/2.2 A/AA بدون violation؛ screen reader و ماتریس گسترده زبان/مرورگر هنوز آزموده نشده‌اند |
| سازگاری | WP 7.1.3، WC 11.2.1، PHP 8.4.1؛ Luxury Shop کلاسیک و Twenty Twenty-Five بلوکی | HPOS روشن/خاموش، WooCommerce فعال/غیرفعال و حساب واقعی Shop Manager آزموده شدند؛ Elementor و rendering مرورگری builderهای خاص و نسخه‌های دیگر بازند |
| کارایی | محصول و variation هر دو سقف اسکن و صفحه‌های محدود دارند؛ preview رسیدن به سقف variation را هشدار می‌دهد؛ regression مصنوعی ۵۰۱ variation در full suite پاس شد | ماتریس کامل ۱k/۱۰k/۵۰k محصول × ۱۰/۱۰۰/۱۰۰۰ rule با ۳۰ cold و ۳۰ warm نمونه برای matcher و sort اجرا شد و بیشینه‌های matrix حد E-15 را رعایت کردند. Ordered 50k cold p95=534–683ms در برابر baseline=937–1286ms؛ cold SQL=16 در برابر 18. Random p95 بیشینه: 10k=514.31ms cold/144.34ms warm؛ 50k=1143.92ms cold/568.68ms warm. بیشینه حافظه=96MiB. Matcher p95 max=107.08ms cold/7.06ms warm. Variable stress (۱۰۰ parent/۱۲٬۰۰۰ variation، cap=500) exploratory و خارج از پذیرش است: cold 841–870ms/244 SQL، warm 574–602ms/0 SQL؛ uncapped pre-fix از 120s گذشت و قابل‌مقایسه نیست. NFR-02 فقط برای محیط محلی دقیق benchmark و limitهای E-15 PASS است؛ این SLA عمومی/محصولی نیست. جزئیات محیط/آستانه‌ها در [گزارش QA](pilot-qa-fa.md) |
| آزمون | commit `5d468e0` (tracked/pushed): `composer run test:all` (PHPCS 28/28، PHPUnit 8/8/13 assertions، integration 12/12)، `npm run check:js` و `node --check` پاس شدند | E2E `1aa89a2c28a1`، 17/17، شامل Shop Manager؛ axe-core 4.13.0 هر دو locale بدون violation؛ visual review کارت/layout در 1200/420px پاس؛ cleanup موفق. E2E پیش از commit و با همان منطق تست اجرا شد؛ تفاوت commit فقط whitespace PHPCBF بود. موارد باز در [گزارش QA پایلوت](pilot-qa-fa.md) |
| تحویل | ZIP قبلی lifecycle را در clone QA گذراند | artifact قبلی پس از اصلاح کنتراست، بستهٔ جاری محسوب نمی‌شود. SHA/revision ZIP تازه و install/update/uninstall/rollback آن هنوز pending است؛ M4 برای artifact تازه باز است. |

تغییرهای قبلی در `assets/css/frontend.css`، `composer.json`، `docs/manual-qa-fa.md` و `tests/e2e/` حفظ شده و با دامنه فعلی ادغام می‌شوند. نام Phase 15/16 در QA اصطلاح تاریخی است؛ معادل خودکار تکمیل milestoneهای زیر نیست.

## ترتیب و معیار تکمیل

P0=مانع پایلوت، P1=لازم در دامنه MVP، P2=پس از پایلوت، P3=اختیاری با تصمیم تازه. وضعیت زیر از شواهد در دسترس این ثبت است؛ تا بسته‌شدن gate، صرف وجود کد Done محسوب نمی‌شود.

| مرحله | هدف/خروجی | وابستگی | اولویت | معیار خروج |
|---|---|---|---|---|
| M0 | مبنا و محیط بازتولیدپذیر، ثبت اسناد و ابزارها، ماتریس نسخه‌ها | تصمیم‌های D-01..09 | P0 | checkout تازه ابزار و اسناد لازم را داشته باشد؛ نسخه دقیق WP/WC/PHP و منابع دسترسی QA ثبت شود |
| M1 | قرارداد قواعد و eligibility مصوب | M0 | P0/P1 | FR-01..06 با سناریوی DB/HTTP و ارتقای قواعد قدیمی پاس؛ هیچ mutation غیرمجاز موفق نشود |
| M2 | مدیریت، preview، تنظیمات و جای‌گذاری مستقل قالب | M1 | P1 | FR-07/08/10 و NFR-03/05 پاس؛ shortcode/block/ابزارک موجود خروجی صحیح و بدون تکرار ناخواسته بدهند |
| M3 | QA واقعی، کارایی، ترجمه و چرخه عمر | M1+M2 | P0 | FR-09 و NFRها روی ماتریس چندبستری با شاهد پاس؛ خطای بحرانی باز نماند؛ E-15 برای performance محلی پاس شود؛ accessibility و compatibility باز نماند |
| M4 | ZIP پایلوت، readme و changelog و گزارش | M3 | P0 | نصب تمیز، upgrade، uninstall و rollback آزموده؛ بسته checksum و گزارش داشته باشد؛ توزیع بیرونی فقط با مجوز |
| M5 | اصلاح بر مبنای پایلوت، اندازه‌گیری و استثناها | M4 و شواهد نیاز | P2 | مشکل مشخص کاربر و معیار موفقیت ثبت شود؛ آمار بدون قرارداد attribution یا privacy منتشر نشود |
| M6 | خرید گروهی/تخفیف/تحلیل سفارش/ادغام چندزبانه | تصمیم محصول جدا پس از M5 | P3 | قرارداد قیمت، cart، refunds، داده، performance و پذیرش مستقل تصویب شود |

| مرحله | وضعیت در این run | شاهد/مانع باقی‌مانده |
|---|---|---|
| M0 | انجام محلی؛ checkout تازه پس از commit باز است | دستورالعمل‌ها و اسناد خوانده، ignore محلی اصلاح و نسخه‌های محیط ثبت شدند. |
| M1 | gate کد و integration پاس | `composer run test:all` exit 0 پس از regression؛ تمام ۱۲ integration از جمله تست synthetic variation cap پاس شدند. |
| M2 | مسیرهای اصلی browser QA پاس | E2E `1aa89a2c28a1` با 17/17، شامل QA-097، QA-104، QA-130، shortcode/block، دو locale و دو قالب؛ Elementor مرورگری و screen reader خارج از این pass هستند. |
| M3 | بخشی پاس؛ باقی موارد pending | PHP/JS suite و E2E پاس؛ NFR-02 در envelope محلی E-15 pass. Elementor، screen reader و matrix گسترده compatibility بازند. |
| M4 | artifact جاری pending | lifecycle ZIP قبلی pass بود؛ پس از اصلاح CSS، ZIP تازه با SHA جدید باید build و install/update/uninstall/rollback شود. |

وابستگی اصلی: M0 → M1 → M2 → M3 → M4. نوشتن متن و آماده‌سازی fixture درون مراحل قابل‌پیشبرد مستقل است؛ پذیرش به یکپارچگی نیاز دارد. به‌علت ورود block/shortcode به MVP، برآورد ۵ تا ۸ هفته بررسی قبلی دوباره قابل‌اتکا نیست؛ پس از B-01/B-22 ظرفیت و تخمین بازبینی شود، نه اینکه عدد قدیمی تعهد شود.

## بک‌لاگ با مرز فایل و اثبات

| ID | کار، مالک تغییر پیشنهادی و خروجی | نیازمندی/وابستگی | اثبات و rollback |
|---|---|---|---|
| B-00 P0 | اصلاح ignore محلی docs/npm/ابزار و بازبینی تغییرات قبلی؛ انجام شده در working tree | NFR-07؛ M0 | فایل‌ها اکنون در status دیده می‌شوند؛ commit برای اثبات checkout تیمی باقی است |
| B-01 P0 | انتخاب و ثبت محیط QA، نسخه‌های واقعی و قالب/builderهای نمونه؛ runbook در development | O-03، NFR-04 | دسترسی واقعی بدون ثبت secret و ماتریس قابل‌اجرا؛ نسخه غایب Skip و gate باز بماند |
| B-10 P0 | حفظ پیکربندی سالم در خطای ذخیره؛ پیاده‌سازی و integration admin/security پاس | FR-01/02؛ M0 | suite شامل حفظ وضعیت/meta و مقادیر فرم نامعتبر و رد nonce/capability نامعتبر است؛ E2E `1aa89a2c28a1` نقش واقعی Shop Manager و access به rules/settings را نیز آزمود |
| B-11 P1 | priority در Rule/schema/repository/form/list و default برای داده قدیمی؛ پیاده‌سازی شده | FR-03؛ D-03/E-02 | `test:all` شامل تست matching/storage بود و پاس شد؛ بدون backfill مخرب |
| B-12 P0 | fallback از مقصد حذف‌شده با pagination محدود و diagnostics؛ پیاده‌سازی شده | FR-04؛ B-11 | integration matching پاس؛ سقف ۵۰۰ نامزد همچنان محدودیت ایمنی است؛ هزینه در matrix و local envelope E-15 مشاهده شده، بدون ادعای SLA عمومی |
| B-13 P0 | eligibility مشترک و refill محدود؛ پیاده‌سازی شده | FR-05/06؛ D-05/E-13 | integration و regression ۵۰۱ variation در full suite تازه پاس شدند. fixture محدود، NFR performance را پاس نمی‌کند |
| B-20 P1 | priority در فرم/جدول و تنظیمات نمایش پیاده شده؛ خطای فرم و حفظ ورودی هنوز گیت کامل ندارد | FR-01/02؛ B-11 | keyboard/اعتبارسنجی و POST نامعتبر باید rule قبلی و ورودی را حفظ کند؛ انتخاب‌گر دسته فعلی native است و جست‌وجوی فارسی پیشرفته ندارد |
| B-21 P1 | Preview مشترک و settings schema v1 با capability/nonce پیاده شده | FR-07/08؛ B-12/13 | admin preview و read-only behavior در E2E و auth/nonce در integration suite پاس شدند |
| B-22 P1 | شورت‌کد و بلوک پویا با renderer مشترک، جست‌وجوی محصول، asset/editor/i18n و انتخاب دستی/خودکار پیاده؛ browser smoke در `1aa89a2c28a1` پاس | FR-10؛ D-08؛ B-21 | dynamic block/shortcode، widget area و multi-embed پوشش دارند؛ Elementor و page builderهای خاص هنوز آزموده نشده‌اند. JS مستقیم runtime است و build step جداگانه لازم ندارد |
| B-23 P1 | گرید واکنش‌گرا، عنوان locale و accessible section در source/CSS پیاده؛ ایراد کنتراست کارت رفع و بازبینی شد | FR-08، NFR-03/05؛ B-22 | E2E، axe-core در دو locale و screenshots در 1200/420px پاس شدند؛ screen reader و متن‌های ترکیبی طولانی بازند |
| B-30 P0 | runner E2E و fixture محلی موجود؛ commit `5d468e0` tracked/pushed؛ run پیش از commit `1aa89a2c28a1`، 17/17 پاس شد | NFR-01/03/04/05؛ M2 | شامل QA-097، Shop Manager access و axe WCAG A/AA در LTR/RTL؛ E2E منطق commit را داشت و فقط whitespace PHPCBF متفاوت بود. HPOS دو حالت، WooCommerce فعال/غیرفعال و widget/multi-embed پوشش دارند؛ Elementor مرورگری و screen reader بازند |
| B-31 P0 | benchmark matrix در limit محلی E-15 پاس شد؛ variable-heavy stress exploratory ثبت شد | NFR-02؛ B-21/22/E-13 | حدود دقیق matcher/recommendation/memory در E-15؛ maxima ثبت‌شده از حدود عبور نکردند و SQL envelope نیز مشاهده شد. variable stress n=5 شاهد پذیرش نیست؛ uncapped baseline >120s و غیرقابل‌مقایسه. NFR-02 فقط برای محیط محلی اندازه‌گیری‌شده PASS است، نه SLA کلی. گزارش و raw data بیرون مخزن در [گزارش QA](pilot-qa-fa.md) |
| B-32 P0 | `uninstall.php` و option پاک‌سازی opt-in پیاده شده؛ integration پاس است و ZIP قبلی lifecycle را گذراند | FR-09؛ D-06؛ B-21 | default uninstall داده‌های افزونه/Woo را نگه داشت؛ opt-in فقط rule/settings را پاک کرد؛ rollback روی artifact قبلی pass شد. ZIP متناظر با commit `5d468e0` باید جداگانه build و lifecycle شود؛ M4 باز است |
| B-40 P0 | اسکریپت/package آماده؛ ZIP قبلی از source تمیز دوبار با hash یکسان تولید و lifecycle QA ایزوله پاس شد | NFR-07؛ M3 | SHA artifact قبلی در [گزارش QA پایلوت](pilot-qa-fa.md) به‌عنوان شاهد تاریخی ثبت است؛ پس از اصلاح کنتراست کارت، ZIP تازه و lifecycle آن pending است؛ M4 artifact جاری باز است |
| B-50 P2 | ثبت زمان ساخت قانون و مشکلات واقعی پایلوت؛ سپس تصمیم درباره استثنا/انتخاب دستی و آمار | M4 | حداقل سه مشاهده مدیر و دسته‌بندی علت‌ها؛ معیار پیشنهادی ۵ دقیقه PRD با گزارش واقعی سنجیده شود |

فرمان‌های موجود و اثرشان در [راهنمای توسعه](development-fa.md) مرجع‌اند. هر task در اجرا ابتدا diagnostic متناسب، سپس تغییر حداقلی و regression دارد؛ strict TDD الزام نشده است. فایل‌های جدید adapter/build در B-22 پس از بررسی ابزار موجود تعیین شوند؛ امروز ساختار executable حدسی ساخته نمی‌شود.

## gate کیفیت و قواعد شاهد

- Gate پایه: `composer run check`. برای B-10/11/12/13 تست integration مربوط نیز روی DB QA اجرا شود.
- Gate یکپارچگی: `composer run test:all` و جداگانه `npm run test:e2e`؛ این دو یکی نیستند.
- Gate تجربه: QA فارسی/انگلیسی و قالب کلاسیک/بلوکی ثبت شده؛ widget area smoke پوشش دارد، Elementor/browser rendering و screen reader بازند؛ تیک‌های گذشته بدون run معتبر پذیرفته نشوند.
- Gate بسته: نصب ZIP، ارتقا و uninstall واقعی روی QA، نه فقط checkout توسعه.
- Critical: fatal، تغییر غیرمجاز داده، خطای قیمت/سبد، از‌دست‌رفتن قواعد سالم یا قطع خرید اصلی. پذیرش بدون رفع این موارد ممنوع است. محدودیت غیربحرانی باید به‌صراحت در گزارش پایلوت و تصمیم پذیرش ثبت شود.
- benchmark با baseline و محیط مشخص اجرا شده است؛ گیت E-15 فقط برای همان محیط و datasetهای ثبت‌شده pass است. آن را به SLA عمومی/محصولی تعمیم ندهید؛ نبود SQL RAND و query محدود به‌تنهایی کافی نیست.

## ریسک، بدهی و تصمیم باز

| ID | ریسک/شاهد | پاسخ و مالک |
|---|---|---|
| R-01 | دسته مکمل لزوماً سازگار با مدل کالا نیست | متن صادقانه، preview، بررسی استثناهای پایلوت؛ PRD/B-50 |
| R-02 | auto placement به hook متکی است؛ embed دستی مستقل از قالب در source پیاده شده، اما QA چندبستری کامل نیست | D-08 و B-22؛ عدم ادعای همه قالب‌ها قبل از QA |
| R-03 | ذخیره نامعتبر ممکن است پس از native post پیش از callback وضعیت را تغییر دهد | بازیابی عنوان/status و حفظ فرم اضافه شده و admin integration آن را پوشش می‌دهد؛ ZIP lifecycle روی artifact جاری جداگانه پاس شد |
| R-04 | ذخیره چند meta تراکنش ندارد و نتیجه meta write بررسی کامل نمی‌شود | diagnostic خطای ذخیره و طراحی رفتار شکست/بازیابی در B-10؛ ادعای atomic فعلاً مجاز نیست |
| R-05 | count/offset بزرگ و postmeta join هزینه دارند | B-31؛ schema/کش جدید فقط با شاهد |
| R-06 | CSS قالب و duplicate با چند embed | renderer مشترک، namespace و گرید کانتینری B-22/23 |
| R-07 | تنظیمات option افزونه با `autoload=false` ذخیره می‌شود؛ benchmark از حدهای محلی E-15 عبور کرد | B-31/E-15؛ نتیجه فقط گیت محلی است و به SLA عمومی/محصولی تعمیم نمی‌یابد |
| R-08 | اصلاح ignore در working tree انجام شده؛ نسخه‌بندی Git و دسترسی checkout تازه تا commit اثبات نشده | B-00؛ فایل محلی با فایل تحویل‌شده اشتباه نشود |
| R-09 | نسخه حداقل قدیمی و سربرگ tested-up-to بدون ماتریس کامل | O-03/B-01/30؛ حداقل‌ها بدون تصمیم سازگاری تغییر نکنند |
| R-10 | محصول متغیر ممکن است در سطح parent موجود ولی فاقد variation واجد شرایط باشد | B-13 و تست ترکیب variation/backorder/price |

تصمیم‌های محصول این مرحله پاسخ گرفته‌اند؛ O-03 (نسخه‌های دقیق و محیط QA) برای شروع اجرا و O-04 (مارکت/مدل تجاری) پس از پایلوت بازند. جزئیات در [دفتر تصمیم‌ها](decisions-fa.md).

## تغییر دامنه ثبت‌شده

۲۰۲۶-۱۰-۲۷ (ثبت تاریخی دامنه): کاربر تصمیم قبلی محدودیت قالب کلاسیک و تعویق embed را اصلاح کرد. D-08، بلوک پویا و shortcode را در MVP قرار داد و D-09 مسیر ZIP پایلوت را تعیین کرد. آخرین E2E `1aa89a2c28a1` روی working tree پیش از commit `5d468e0`، ۱۷/۱۷ را گذراند و axe/WCAG A/AA را در دو locale و دسترسی Shop Manager را پوشش داد؛ منطق تست با commit یکسان و فقط whitespace آن متفاوت است. commit `5d468e0c2909f3ef78088ebeccae8db008e33766` اکنون tracked و pushed است. HPOS روشن/خاموش، WooCommerce فعال/غیرفعال، widget area و multi-embed نیز در QAهای ثبت‌شده پوشش دارند؛ Elementor مرورگری هنوز باز است. SHA/runtime revision ZIP متناظر با کد جاری پس از build/lifecycle QA ثبت می‌شود. اجرای محلی و commit/push مجاز است؛ اجرای روی هاست واقعی، انتشار عمومی و ارسال ZIP مجاز نشده است.
