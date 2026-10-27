# وضعیت، نقشه راه و بک‌لاگ اجرایی

مرجع: [فهرست](README.md)، [پذیرش محصول](prd-fa.md)، [تصمیم‌های نهایی](decisions-fa.md). بازبینی شواهد: ۲۰۲۶-۱۰-۲۸.

## وضعیت واقعی و حدود شاهد

مبنای Git هنوز `b6bfab6` است؛ کد جاری نسخه `0.1.0` به‌همراه تغییرهای محلی است و commit این مجموعه هنوز انجام نشده. fixtureهای محلی پس از QA پاک‌سازی شدند؛ به هاست/داده واقعی دست زده نشده. اجرای تازه `composer run test:all` پس از regression ۵۰۱ variation با exit 0 تمام شد؛ `npm run check:js` نیز exit 0 بود. E2E روی کد به‌روز (`63f291d389df`) با ۱۴/۱۴ Browser QA پاس شد و cleanup exit 0 داشت. M1 و مسیرهای اصلی M2 از gateهای اجراشده عبور کردند؛ M3 به‌دلیل موارد دسترس‌پذیری/سازگاری/benchmark باز است. روی ZIP اولیه شواهد نصب/ارتقا/uninstall وجود دارد، اما آن بسته از revision پیش از اصلاح ممیزی بود؛ M4 تا بازسازی و تکرار QA با کد جاری pending می‌ماند.

| موضوع | شاهد فعلی | فاصله تا هدف |
|---|---|---|
| قواعد و مدیریت | اولویت، fallback، validation، حفظ فرم نامعتبر، تنظیمات و preview پیاده شده | integrationهای admin/security و suite کامل پاس شدند؛ تعامل Shop Manager جداگانه آزموده نشده |
| محصول واجد شرایط | simple/variable، purchasable/visible/stock، backorder تنظیم‌پذیر و refill محدود | integrationها پاس؛ عملکرد فقط با fixture کوچک سنجیده شده و پذیرش benchmark مقیاس نیست |
| نمایش | shortcode، dynamic block، renderer مشترک، manual/automatic و CSS خارج صفحه محصول | E2E run `63f291d389df` شامل shortcode، block editor/frontend، preview، حالت manual و قالب کلاسیک/بلوک پاس شد؛ widget area و builder مستقل آزموده نشده‌اند |
| فارسی | ترجمه PHP/JS به‌روز؛ E2E در `en_US`/LTR و `fa_IR`/RTL پاس شد | ۲۰۰٪ zoom، screen reader و ماتریس گسترده زبان/مرورگر آزموده نشده‌اند |
| سازگاری | WP 7.1.3، WC 11.2.1، PHP 8.4.1؛ Luxury Shop کلاسیک و Twenty Twenty-Five بلوکی در E2E | Elementor/builder، widget area، HPOS خاموش، WC غایب، Shop Manager و نسخه‌های دیگر هنوز آزموده نشده‌اند |
| کارایی | محصول و variation هر دو سقف اسکن و صفحه‌های محدود دارند؛ preview رسیدن به سقف variation را هشدار می‌دهد. regression مصنوعی ۵۰۱ variation در full suite پاس شد | benchmark PRD (۱k/۱۰k/۵۰k محصول، ۱۰/۱۰۰/۱۰۰۰ rule و ۳۰ نمونه cold/warm) هنوز اجرا نشده؛ fixture کوچک timings را ثبت می‌کند اما NFR-02 را پاس نمی‌کند؛ benchmark agent در حال بررسی است |
| آزمون | `composer run test:all` پس از regression exit 0: lint، PHPCS 28/28، PHPUnit 8/8 (13 assertion)، تمام ۱۲ integration از جمله `integration-performance`؛ `npm run check:js` exit 0 | E2E روی کد جاری `63f291d389df`، 14/14؛ cleanup exit 0. تاریخچه/موارد باز در [گزارش QA پایلوت](pilot-qa-fa.md) |
| تحویل | docs/tools از ignore محلی خارج؛ اسکریپت package، readme، changelog و license موجود؛ ZIP اولیه نصب/ارتقا و هر دو حالت uninstall را در clone جداگانه گذراند | ZIP اولیه از revision pre-review-fix است (SHA-256 در گزارش QA)؛ باید از کد جاری rebuild و M4 دوباره اجرا شود. پس از آن نیز تغییرات کد هنوز commit نشده‌اند |

تغییرهای قبلی در `assets/css/frontend.css`، `composer.json`، `docs/manual-qa-fa.md` و `tests/e2e/` حفظ شده و با دامنه فعلی ادغام می‌شوند. نام Phase 15/16 در QA اصطلاح تاریخی است؛ معادل خودکار تکمیل milestoneهای زیر نیست.

## ترتیب و معیار تکمیل

P0=مانع پایلوت، P1=لازم در دامنه MVP، P2=پس از پایلوت، P3=اختیاری با تصمیم تازه. وضعیت زیر از شواهد در دسترس این ثبت است؛ تا بسته‌شدن gate، صرف وجود کد Done محسوب نمی‌شود.

| مرحله | هدف/خروجی | وابستگی | اولویت | معیار خروج |
|---|---|---|---|---|
| M0 | مبنا و محیط بازتولیدپذیر، ثبت اسناد و ابزارها، ماتریس نسخه‌ها | تصمیم‌های D-01..09 | P0 | checkout تازه ابزار و اسناد لازم را داشته باشد؛ نسخه دقیق WP/WC/PHP و منابع دسترسی QA ثبت شود |
| M1 | قرارداد قواعد و eligibility مصوب | M0 | P0/P1 | FR-01..06 با سناریوی DB/HTTP و ارتقای قواعد قدیمی پاس؛ هیچ mutation غیرمجاز موفق نشود |
| M2 | مدیریت، preview، تنظیمات و جای‌گذاری مستقل قالب | M1 | P1 | FR-07/08/10 و NFR-03/05 پاس؛ shortcode/block/ابزارک موجود خروجی صحیح و بدون تکرار ناخواسته بدهند |
| M3 | QA واقعی، کارایی، ترجمه و چرخه عمر | M1+M2 | P0 | FR-09 و NFRها روی ماتریس چندبستری با شاهد پاس؛ خطای بحرانی باز نماند؛ آستانه performance پس از baseline تعیین و رعایت شود |
| M4 | ZIP پایلوت، readme و changelog و گزارش | M3 | P0 | نصب تمیز، upgrade، uninstall و rollback آزموده؛ بسته checksum و گزارش داشته باشد؛ توزیع بیرونی فقط با مجوز |
| M5 | اصلاح بر مبنای پایلوت، اندازه‌گیری و استثناها | M4 و شواهد نیاز | P2 | مشکل مشخص کاربر و معیار موفقیت ثبت شود؛ آمار بدون قرارداد attribution یا privacy منتشر نشود |
| M6 | خرید گروهی/تخفیف/تحلیل سفارش/ادغام چندزبانه | تصمیم محصول جدا پس از M5 | P3 | قرارداد قیمت، cart، refunds، داده، performance و پذیرش مستقل تصویب شود |

| مرحله | وضعیت در این run | شاهد/مانع باقی‌مانده |
|---|---|---|
| M0 | انجام محلی؛ checkout تازه پس از commit باز است | دستورالعمل‌ها و اسناد خوانده، ignore محلی اصلاح و نسخه‌های محیط ثبت شدند. |
| M1 | gate کد و integration پاس | `composer run test:all` exit 0 پس از regression؛ تمام ۱۲ integration از جمله تست synthetic variation cap پاس شدند. |
| M2 | مسیرهای اصلی browser QA پاس | E2E روی کد جاری `63f291d389df` با 14/14 سناریو؛ shortcode و block/editor/frontend، manual mode، preview و دو قالب. |
| M3 | بخشی پاس؛ باقی موارد pending | suite و regression variation پاس؛ E2E تازه 14/14؛ builder/widget/zoom/screen reader/HPOS off/WC absent/Shop Manager و benchmark PRD بازند. |
| M4 | شواهد اولیه روی بسته stale؛ قبولی gate جاری pending | ZIP با SHA-256 ثبت‌شده در [گزارش QA پایلوت](pilot-qa-fa.md) از source revision قبلی نصب/به‌روزرسانی و uninstall را در clone گذراند؛ پس از اصلاح ممیزی باید package تازه ساخته و همان چرخه تکرار شود. |

وابستگی اصلی: M0 → M1 → M2 → M3 → M4. نوشتن متن و آماده‌سازی fixture درون مراحل قابل‌پیشبرد مستقل است؛ پذیرش به یکپارچگی نیاز دارد. به‌علت ورود block/shortcode به MVP، برآورد ۵ تا ۸ هفته بررسی قبلی دوباره قابل‌اتکا نیست؛ پس از B-01/B-22 ظرفیت و تخمین بازبینی شود، نه اینکه عدد قدیمی تعهد شود.

## بک‌لاگ با مرز فایل و اثبات

| ID | کار، مالک تغییر پیشنهادی و خروجی | نیازمندی/وابستگی | اثبات و rollback |
|---|---|---|---|
| B-00 P0 | اصلاح ignore محلی docs/npm/ابزار و بازبینی تغییرات قبلی؛ انجام شده در working tree | NFR-07؛ M0 | فایل‌ها اکنون در status دیده می‌شوند؛ commit برای اثبات checkout تیمی باقی است |
| B-01 P0 | انتخاب و ثبت محیط QA، نسخه‌های واقعی و قالب/builderهای نمونه؛ runbook در development | O-03، NFR-04 | دسترسی واقعی بدون ثبت secret و ماتریس قابل‌اجرا؛ نسخه غایب Skip و gate باز بماند |
| B-10 P0 | حفظ پیکربندی سالم در خطای ذخیره؛ پیاده‌سازی و integration admin/security پاس | FR-01/02؛ M0 | suite شامل حفظ وضعیت/meta و مقادیر فرم نامعتبر و رد nonce/capability نامعتبر است؛ Shop Manager flow آزموده نشده |
| B-11 P1 | priority در Rule/schema/repository/form/list و default برای داده قدیمی؛ پیاده‌سازی شده | FR-03؛ D-03/E-02 | `test:all` شامل تست matching/storage بود و پاس شد؛ بدون backfill مخرب |
| B-12 P0 | fallback از مقصد حذف‌شده با pagination محدود و diagnostics؛ پیاده‌سازی شده | FR-04؛ B-11 | integration matching پاس؛ سقف ۵۰۰ نامزد همچنان محدودیت ایمنی و benchmark scale باز است |
| B-13 P0 | eligibility مشترک و refill محدود؛ پیاده‌سازی شده | FR-05/06؛ D-05/E-13 | integration و regression ۵۰۱ variation در full suite تازه پاس شدند. fixture محدود، NFR performance را پاس نمی‌کند |
| B-20 P1 | priority در فرم/جدول و تنظیمات نمایش پیاده شده؛ خطای فرم و حفظ ورودی هنوز گیت کامل ندارد | FR-01/02؛ B-11 | keyboard/اعتبارسنجی و POST نامعتبر باید rule قبلی و ورودی را حفظ کند؛ انتخاب‌گر دسته فعلی native است و جست‌وجوی فارسی پیشرفته ندارد |
| B-21 P1 | Preview مشترک و settings schema v1 با capability/nonce پیاده شده | FR-07/08؛ B-12/13 | admin preview و read-only behavior در E2E و auth/nonce در integration suite پاس شدند |
| B-22 P1 | شورت‌کد و بلوک پویا با renderer مشترک، جست‌وجوی محصول، asset/editor/i18n و انتخاب دستی/خودکار؛ پیاده‌سازی و browser smoke پاس | FR-10؛ D-08؛ B-21 | QA-205 در اجرای `63f291d389df` پاس؛ widget area، Elementor/builder و multi-embed duplicate هنوز آزموده نشده‌اند. JS مستقیم runtime است و build step جداگانه لازم ندارد |
| B-23 P1 | گرید واکنش‌گرا، عنوان locale و accessible section در source/CSS پیاده شده | FR-08، NFR-03/05؛ B-22 | E2E responsive screenshot پاس شده؛ ۲۰۰٪ zoom، متن‌های ترکیبی طولانی، screen reader و ارزیابی بصری کامل بازند |
| B-30 P0 | runner E2E و fixture محلی موجود؛ اجرای کد جاری 14/14 پاس شد | NFR-01/03/04/05؛ M2 | run `63f291d389df` و موارد آزموده‌نشده در گزارش QA پایلوت؛ HPOS خاموش، WC غایب، Elementor/builder و Shop Manager همچنان بازند |
| B-31 P0 | regression محدود variation (۵۰۱ candidate) در suite پاس شد؛ benchmark مقیاس هنوز در حال انجام توسط agent است | NFR-02؛ B-21/22/E-13 | ۱k/۱۰k/۵۰k محصول، ۱۰/۱۰۰/۱۰۰۰ rule و عمق متنوع، حداقل ۳۰ نمونه warm و cold جدا، p50/p95/query/memory ثبت؛ WC query count با SQL کل تفکیک. regression مصنوعی benchmark نیست؛ fixture کوچک timings: match 3.65ms، newest 8.68ms، best-selling 8.71ms، random 7.15ms |
| B-32 P0 | `uninstall.php` و option پاک‌سازی opt-in پیاده شده؛ integration پاس | FR-09؛ D-06؛ B-21 | آزمون lifecycle نصب/حذف از ZIP و rollback همچنان به M4 تعلق دارد |
| B-40 P0 | اسکریپت/محتوای package آماده؛ ZIP اولیه چرخه نصب/ارتقا/uninstall را در clone گذراند | NFR-07؛ M3 | ZIP QA شده revision پیش از اصلاح ممیزی است؛ با source جاری rebuild/retest شود. M4 تا checksum، نصب و update/uninstall روی package جدید و rollback بسته نمانده |
| B-50 P2 | ثبت زمان ساخت قانون و مشکلات واقعی پایلوت؛ سپس تصمیم درباره استثنا/انتخاب دستی و آمار | M4 | حداقل سه مشاهده مدیر و دسته‌بندی علت‌ها؛ معیار پیشنهادی ۵ دقیقه PRD با گزارش واقعی سنجیده شود |

فرمان‌های موجود و اثرشان در [راهنمای توسعه](development-fa.md) مرجع‌اند. هر task در اجرا ابتدا diagnostic متناسب، سپس تغییر حداقلی و regression دارد؛ strict TDD الزام نشده است. فایل‌های جدید adapter/build در B-22 پس از بررسی ابزار موجود تعیین شوند؛ امروز ساختار executable حدسی ساخته نمی‌شود.

## gate کیفیت و قواعد شاهد

- Gate پایه: `composer run check`. برای B-10/11/12/13 تست integration مربوط نیز روی DB QA اجرا شود.
- Gate یکپارچگی: `composer run test:all` و جداگانه `npm run test:e2e`؛ این دو یکی نیستند.
- Gate تجربه: QA دستی فارسی/انگلیسی، قالب کلاسیک/بلوکی، widget و builder طبق FR-10؛ تیک‌های گذشته بدون run معتبر پذیرفته نشوند.
- Gate بسته: نصب ZIP، ارتقا و uninstall واقعی روی QA، نه فقط checkout توسعه.
- Critical: fatal، تغییر غیرمجاز داده، خطای قیمت/سبد، از‌دست‌رفتن قواعد سالم یا قطع خرید اصلی. پذیرش بدون رفع این موارد ممنوع است. محدودیت غیربحرانی باید به‌صراحت در گزارش پایلوت و تصمیم پذیرش ثبت شود.
- برای زمان پاسخ، کاندید ۱۰۰ms افزایش p95 در ۱۰k محصول فقط پس از baseline و محیط مرجع تثبیت می‌شود؛ تا آن زمان «عبور performance gate» ثبت نشود. نبود SQL RAND و query محدود به‌تنهایی کافی نیست.

## ریسک، بدهی و تصمیم باز

| ID | ریسک/شاهد | پاسخ و مالک |
|---|---|---|
| R-01 | دسته مکمل لزوماً سازگار با مدل کالا نیست | متن صادقانه، preview، بررسی استثناهای پایلوت؛ PRD/B-50 |
| R-02 | auto placement به hook متکی است؛ embed دستی مستقل از قالب در source پیاده شده، اما QA چندبستری کامل نیست | D-08 و B-22؛ عدم ادعای همه قالب‌ها قبل از QA |
| R-03 | ذخیره نامعتبر ممکن است پس از native post پیش از callback وضعیت را تغییر دهد | بازیابی عنوان/status و حفظ فرم اضافه شده و admin integration آن را پوشش می‌دهد؛ ZIP lifecycle هنوز جداست |
| R-04 | ذخیره چند meta تراکنش ندارد و نتیجه meta write بررسی کامل نمی‌شود | diagnostic خطای ذخیره و طراحی رفتار شکست/بازیابی در B-10؛ ادعای atomic فعلاً مجاز نیست |
| R-05 | count/offset بزرگ و postmeta join هزینه دارند | B-31؛ schema/کش جدید فقط با شاهد |
| R-06 | CSS قالب و duplicate با چند embed | renderer مشترک، namespace و گرید کانتینری B-22/23 |
| R-07 | تنظیمات option افزونه با `autoload=false` ذخیره می‌شود؛ benchmark مقیاس‌دار نداریم | B-31؛ کوچک بودن fixture برای تصمیم performance کافی نیست |
| R-08 | اصلاح ignore در working tree انجام شده؛ نسخه‌بندی Git و دسترسی checkout تازه تا commit اثبات نشده | B-00؛ فایل محلی با فایل تحویل‌شده اشتباه نشود |
| R-09 | نسخه حداقل قدیمی و سربرگ tested-up-to بدون ماتریس کامل | O-03/B-01/30؛ حداقل‌ها بدون تصمیم سازگاری تغییر نکنند |
| R-10 | محصول متغیر ممکن است در سطح parent موجود ولی فاقد variation واجد شرایط باشد | B-13 و تست ترکیب variation/backorder/price |

تصمیم‌های محصول این مرحله پاسخ گرفته‌اند؛ O-03 (نسخه‌های دقیق و محیط QA) برای شروع اجرا و O-04 (مارکت/مدل تجاری) پس از پایلوت بازند. جزئیات در [دفتر تصمیم‌ها](decisions-fa.md).

## تغییر دامنه ثبت‌شده

۲۰۲۶-۱۰-۲۷: کاربر تصمیم قبلی محدودیت قالب کلاسیک و تعویق embed را اصلاح کرد. D-08، بلوک پویا و shortcode را در MVP قرار داد و D-09 مسیر ZIP پایلوت را تعیین کرد. E2E run=`63f291d389df` در ۲۰۲۶-۱۰-۲۸، QA-205 را پس از شکست اجرای پیشین پاس کرد؛ قالب‌های builder/widget مستقل همچنان آزموده نشده‌اند. کاربر اجرای کامل محلی و commit/push را مجاز کرد؛ اجرای روی هاست واقعی، انتشار عمومی و ارسال ZIP مجاز نشده است.
