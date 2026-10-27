# وضعیت، نقشه راه و بک‌لاگ اجرایی

مرجع: [فهرست](README.md)، [پذیرش محصول](prd-fa.md)، [تصمیم‌های نهایی](decisions-fa.md). بازبینی شواهد: ۲۰۲۶-۱۰-۲۸.

## وضعیت واقعی و حدود شاهد

مبنای runtime افزونه source revision `7693821` روی شاخه `feat/hamkharid-pilot` است؛ نسخه `0.1.0`. fixtureهای QA پاک‌سازی شدند؛ به دادهٔ واقعی یا هاست دست زده نشد. `composer run test:all` با exit 0 تمام شد: PHPCS 28/28، PHPUnit 8/8 با 13 assertion و integration 12/12؛ `composer run cs` پس از اصلاح alignment نیز 28/28 clean و `npm run check:js` exit 0 بود. E2E تازه `168017ed316c` با 15/15 Browser QA پاس شد و cleanup exit 0 داشت؛ QA-097 عرض مؤثر 320 CSS px را در zoom صفحهٔ 200٪ پوشش داد. این تغییر آزمون‌محور است و محتوای runtime را تغییر نمی‌دهد: ZIP با SHA-256 `d6006eb80ebcaca16794ff55d73f873b72a46e07387323c3ccc056ea36304053` و runtime revision `7693821` در clone ایزوله lifecycle را پاس کرد. مرور بصری layoutهای responsive انجام شد و contrast fix که QA ایزوله قالب TT5 پیدا کرد در commit جاری است. benchmark مقیاس تکمیل و مشاهداتش ثبت شده‌اند؛ NFR-02 تا انتخاب threshold پذیرش از سوی کاربر pass/fail نمی‌گیرد. M3 به‌دلیل موارد دسترس‌پذیری/سازگاری باز است؛ آمادگی کلی پایلوت از این شواهد نتیجه نمی‌شود.

| موضوع | شاهد فعلی | فاصله تا هدف |
|---|---|---|
| قواعد و مدیریت | اولویت، fallback، validation، حفظ فرم نامعتبر، تنظیمات و preview پیاده شده | integrationهای admin/security و suite کامل پاس شدند؛ تعامل Shop Manager جداگانه آزموده نشده |
| محصول واجد شرایط | simple/variable، purchasable/visible/stock، backorder تنظیم‌پذیر و refill محدود | integrationها پاس؛ عملکرد فقط با fixture کوچک سنجیده شده و پذیرش benchmark مقیاس نیست |
| نمایش | shortcode، dynamic block، renderer مشترک، manual/automatic و CSS خارج صفحه محصول | E2E run `168017ed316c` شامل shortcode، block editor/frontend، preview، حالت manual و قالب کلاسیک/بلوک پاس شد؛ widget area و builder مستقل آزموده نشده‌اند |
| فارسی | ترجمه PHP/JS به‌روز؛ E2E در `en_US`/LTR و `fa_IR`/RTL، و QA-097 در ۳۲۰ CSS px/zoom صفحهٔ ۲۰۰٪ پاس شد | screen reader و ماتریس گسترده زبان/مرورگر آزموده نشده‌اند |
| سازگاری | WP 7.1.3، WC 11.2.1، PHP 8.4.1؛ Luxury Shop کلاسیک و Twenty Twenty-Five بلوکی در E2E | Elementor/builder، widget area، HPOS خاموش، WC غایب، Shop Manager و نسخه‌های دیگر هنوز آزموده نشده‌اند |
| کارایی | محصول و variation هر دو سقف اسکن و صفحه‌های محدود دارند؛ preview رسیدن به سقف variation را هشدار می‌دهد؛ regression مصنوعی ۵۰۱ variation در full suite پاس شد | ماتریس کامل ۱k/۱۰k/۵۰k محصول × ۱۰/۱۰۰/۱۰۰۰ rule با ۳۰ cold و ۳۰ warm نمونه برای matcher و sort اجرا شد. در ۵۰k، ordered cold p95 برابر ۵۳۴–۶۸۳ms در برابر baseline برابر ۹۳۷–۱۲۸۶ms شد؛ SQL سرد ۱۶ در برابر ۱۸. random p95 بیشینه: ۱۰k برابر ۵۱۴ms cold/۱۴۴ms warm؛ ۵۰k برابر ۱۱۴۴ms cold/۵۶۹ms warm. بیشینه allocation ماتریس ۹۶ MiB. variable stress: ۱۰۰ parent/۱۲٬۰۰۰ variation، سقف ۵۰۰، cold ۸۴۱–۸۷۰ms/۲۴۴ SQL و warm ۵۷۴–۶۰۲ms/صفر SQL؛ pre-fix از ۱۲۰ ثانیه گذشت و قابل مقایسه نیست. این مشاهدات SLA نیستند؛ pass/fail NFR-02 threshold کاربر را می‌خواهد |
| آزمون | `composer run test:all` exit 0: PHPCS 28/28، PHPUnit 8/8 (13 assertion)، 12/12 integration؛ `composer run cs` پس از alignment fix، 28/28 clean؛ `npm run check:js` exit 0 | E2E تازه `168017ed316c`، 15/15؛ QA-097 در 320 CSS px/zoom 200٪ و cleanup exit 0. مرور بصری responsive و اصلاح contrast در commit جاری. تاریخچه/موارد باز در [گزارش QA پایلوت](pilot-qa-fa.md) |
| تحویل | ZIP تمیز از source `7693821` با SHA-256 `d6006eb80ebcaca16794ff55d73f873b72a46e07387323c3ccc056ea36304053` در دو build یکسان تولید شد؛ نصب/update/uninstall/rollback روی همین artifact در clone QA پاس شد | M4 artifact QA کامل است. گزارش و محدودیت شبیه‌سازی نسخه در [گزارش QA پایلوت](pilot-qa-fa.md) آمده؛ آمادگی کلی پایلوت همچنان به M3 و تصمیم O-05 وابسته است. |

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
| M2 | مسیرهای اصلی browser QA پاس | E2E تازه `168017ed316c` با 15/15 سناریو؛ shortcode و block/editor/frontend، manual mode، preview، دو قالب و QA-097. |
| M3 | بخشی پاس؛ باقی موارد pending | suite و regression variation پاس؛ E2E تازه 15/15؛ benchmark کامل ثبت شده ولی NFR-02 threshold کاربر ندارد؛ builder/widget/screen reader/HPOS off/WC absent/Shop Manager بازند. |
| M4 | artifact lifecycle QA پاس | همان ZIP با SHA ثبت‌شده از نصب تازه تا update، default/opt-in uninstall، rollback و نصب نهایی پاک آزموده شد. Upgrade با marker نسخهٔ 0.0.9 شبیه‌سازی شد، نه با ZIP تاریخی. Clone در پایان با افزونه فعال/manual و صفر fixture بود. جزئیات و گزارش خام در [گزارش QA پایلوت](pilot-qa-fa.md). |

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
| B-22 P1 | شورت‌کد و بلوک پویا با renderer مشترک، جست‌وجوی محصول، asset/editor/i18n و انتخاب دستی/خودکار؛ پیاده‌سازی و browser smoke پاس | FR-10؛ D-08؛ B-21 | QA-205 در اجرای `168017ed316c` پاس؛ widget area، Elementor/builder و multi-embed duplicate هنوز آزموده نشده‌اند. JS مستقیم runtime است و build step جداگانه لازم ندارد |
| B-23 P1 | گرید واکنش‌گرا، عنوان locale و accessible section در source/CSS پیاده شده | FR-08، NFR-03/05؛ B-22 | E2E responsive screenshot و QA-097 در 320 CSS px/zoom 200٪ پاس شدند؛ متن‌های ترکیبی طولانی، screen reader و ارزیابی بصری کامل بازند |
| B-30 P0 | runner E2E و fixture محلی موجود؛ اجرای کد جاری 15/15 پاس شد | NFR-01/03/04/05؛ M2 | run `168017ed316c` شامل QA-097 در 320 CSS px/zoom 200٪؛ موارد آزموده‌نشده در گزارش QA پایلوت؛ HPOS خاموش، WC غایب، Elementor/builder و Shop Manager همچنان بازند |
| B-31 P0 | ماتریس benchmark و variable stress پس از اصلاح انجام شد؛ ثبت مشاهده کامل است | NFR-02؛ B-21/22/E-13 | ۱k/۱۰k/۵۰k × ۱۰/۱۰۰/۱۰۰۰، هر matcher و sort دارای ۳۰ cold و ۳۰ warm؛ ثبت p50/p95/SQL/memory. ordered 50k cold p95=534–683ms در برابر baseline=937–1286ms؛ random 10k p95 max=514ms cold/144ms warm و 50k=1,144ms/569ms؛ ماتریس memory max=96MiB. variable stress 100 parent/12k variation با cap 500: 841–870ms cold/244 SQL، 574–602ms warm/0 SQL؛ baseline بیش از 120s و non-comparable. این‌ها observations‌اند، نه pass/fail؛ threshold NFR-02 در O-05 منتظر تصمیم کاربر است. گزارش و raw data در مسیر بیرونی pilot QA |
| B-32 P0 | `uninstall.php` و option پاک‌سازی opt-in پیاده شده؛ integration و ZIP lifecycle پاس | FR-09؛ D-06؛ B-21 | default uninstall داده‌های افزونه/Woo را نگه داشت؛ opt-in فقط rule/settings را پاک کرد؛ rollback clone نیز pass شد |
| B-40 P0 | اسکریپت/package آماده؛ ZIP جاری از source تمیز دوبار با hash یکسان تولید و lifecycle QA ایزوله پاس شد | NFR-07؛ M3 | SHA جاری، نصب/update/uninstall/rollback و cleanup در [گزارش QA پایلوت](pilot-qa-fa.md) ثبت‌اند؛ M4 artifact QA بسته است |
| B-50 P2 | ثبت زمان ساخت قانون و مشکلات واقعی پایلوت؛ سپس تصمیم درباره استثنا/انتخاب دستی و آمار | M4 | حداقل سه مشاهده مدیر و دسته‌بندی علت‌ها؛ معیار پیشنهادی ۵ دقیقه PRD با گزارش واقعی سنجیده شود |

فرمان‌های موجود و اثرشان در [راهنمای توسعه](development-fa.md) مرجع‌اند. هر task در اجرا ابتدا diagnostic متناسب، سپس تغییر حداقلی و regression دارد؛ strict TDD الزام نشده است. فایل‌های جدید adapter/build در B-22 پس از بررسی ابزار موجود تعیین شوند؛ امروز ساختار executable حدسی ساخته نمی‌شود.

## gate کیفیت و قواعد شاهد

- Gate پایه: `composer run check`. برای B-10/11/12/13 تست integration مربوط نیز روی DB QA اجرا شود.
- Gate یکپارچگی: `composer run test:all` و جداگانه `npm run test:e2e`؛ این دو یکی نیستند.
- Gate تجربه: QA دستی فارسی/انگلیسی، قالب کلاسیک/بلوکی، widget و builder طبق FR-10؛ تیک‌های گذشته بدون run معتبر پذیرفته نشوند.
- Gate بسته: نصب ZIP، ارتقا و uninstall واقعی روی QA، نه فقط checkout توسعه.
- Critical: fatal، تغییر غیرمجاز داده، خطای قیمت/سبد، از‌دست‌رفتن قواعد سالم یا قطع خرید اصلی. پذیرش بدون رفع این موارد ممنوع است. محدودیت غیربحرانی باید به‌صراحت در گزارش پایلوت و تصمیم پذیرش ثبت شود.
- benchmark با baseline و محیط مشخص اجرا شده است؛ کاندید ۱۰۰ms افزایش p95 در ۱۰k محصول هنوز threshold مصوب نیست. تا انتخاب کاربر در O-05، «عبور performance gate» ثبت نشود. نبود SQL RAND و query محدود به‌تنهایی کافی نیست.

## ریسک، بدهی و تصمیم باز

| ID | ریسک/شاهد | پاسخ و مالک |
|---|---|---|
| R-01 | دسته مکمل لزوماً سازگار با مدل کالا نیست | متن صادقانه، preview، بررسی استثناهای پایلوت؛ PRD/B-50 |
| R-02 | auto placement به hook متکی است؛ embed دستی مستقل از قالب در source پیاده شده، اما QA چندبستری کامل نیست | D-08 و B-22؛ عدم ادعای همه قالب‌ها قبل از QA |
| R-03 | ذخیره نامعتبر ممکن است پس از native post پیش از callback وضعیت را تغییر دهد | بازیابی عنوان/status و حفظ فرم اضافه شده و admin integration آن را پوشش می‌دهد؛ ZIP lifecycle روی artifact جاری جداگانه پاس شد |
| R-04 | ذخیره چند meta تراکنش ندارد و نتیجه meta write بررسی کامل نمی‌شود | diagnostic خطای ذخیره و طراحی رفتار شکست/بازیابی در B-10؛ ادعای atomic فعلاً مجاز نیست |
| R-05 | count/offset بزرگ و postmeta join هزینه دارند | B-31؛ schema/کش جدید فقط با شاهد |
| R-06 | CSS قالب و duplicate با چند embed | renderer مشترک، namespace و گرید کانتینری B-22/23 |
| R-07 | تنظیمات option افزونه با `autoload=false` ذخیره می‌شود؛ benchmark مقیاس‌دار اکنون اجرا شده ولی آستانه پذیرش تعیین نشده | B-31/O-05؛ اندازه‌گیری مشاهده‌ای است و بدون threshold نباید به pass/fail تعبیر شود |
| R-08 | اصلاح ignore در working tree انجام شده؛ نسخه‌بندی Git و دسترسی checkout تازه تا commit اثبات نشده | B-00؛ فایل محلی با فایل تحویل‌شده اشتباه نشود |
| R-09 | نسخه حداقل قدیمی و سربرگ tested-up-to بدون ماتریس کامل | O-03/B-01/30؛ حداقل‌ها بدون تصمیم سازگاری تغییر نکنند |
| R-10 | محصول متغیر ممکن است در سطح parent موجود ولی فاقد variation واجد شرایط باشد | B-13 و تست ترکیب variation/backorder/price |

تصمیم‌های محصول این مرحله پاسخ گرفته‌اند؛ O-03 (نسخه‌های دقیق و محیط QA) برای شروع اجرا و O-04 (مارکت/مدل تجاری) پس از پایلوت بازند. جزئیات در [دفتر تصمیم‌ها](decisions-fa.md).

## تغییر دامنه ثبت‌شده

۲۰۲۶-۱۰-۲۷: کاربر تصمیم قبلی محدودیت قالب کلاسیک و تعویق embed را اصلاح کرد. D-08، بلوک پویا و shortcode را در MVP قرار داد و D-09 مسیر ZIP پایلوت را تعیین کرد. E2E run=`168017ed316c` در ۲۰۲۶-۱۰-۲۸، QA-205 و QA-097 را گذراند؛ مورد آخر عرض مؤثر 320 CSS px/zoom 200٪ را می‌سنجد. قالب‌های builder/widget مستقل همچنان آزموده نشده‌اند. اجرای E2E تغییری در runtime package نداد؛ ZIP همچنان SHA-256 `d6006eb80ebcaca16794ff55d73f873b72a46e07387323c3ccc056ea36304053` با runtime revision `7693821` است. کاربر اجرای کامل محلی و commit/push را مجاز کرد؛ اجرای روی هاست واقعی، انتشار عمومی و ارسال ZIP مجاز نشده است.
