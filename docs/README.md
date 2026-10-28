# مرجع مستندات هم‌خرید

تاریخ بازبینی: ۲۰۲۶-۱۰-۲۸ — گیت‌های M3 در Chromium/Firefox/WebKit، هرکدام ۱۸/۱۸، روی stack جاری و حداقل بررسی شده‌اند. `composer run check` و `npm run check:js` سبز؛ integration admin روی clone نیز پاس است. QA-114/124/200..211 شواهد دارند؛ Narrator را کاربر تأیید کرد. SiteOrigin 2.38.0 در مسیر محدود Classic Page Builder/Text widget، با Code→Visual، ذخیره، reload و render پاس شد. Elementor 4.3.5 رسمی در Apache/PHP8.4.1 با `display_errors=Off` page-level save/reload/reselect/render را گذراند؛ default به‌علت deprecation خود Elementor header/login را شکست و PHP8.1 reload/reselect spinner داشت. QA-202 ورودی ارقام locale و حفظ target خالی را اصلاح/پاس کرد؛ QA-203 با ۵۰۱ variation واقعی warning سقف را نشان داد. SiteOrigin Gutenberg Layout Block authoring، Elementor Pro Theme Builder (به تصمیم کاربر) و Tab traversal در WebKit ادعا نمی‌شوند. اصلاح `.mo` برای وردپرس حداقل در گزارش ثبت شده است. M3 در دامنهٔ معیارهای ثبت‌شده بسته است. artifact M4 قبلی SHA-256 `7f4868b8bd54fd772d4bbf5fb8c5ef6044b4c028baf8c9de400c270dc62f884b` lifecycle شد اما پیش از اصلاح count locale بود؛ ZIP تازه باید پس از commit دوباره build/lifecycle شود. هیچ ارسال یا انتشار عمومی انجام نشده است. گیت E-15 فقط در محدودهٔ محلی تأییدشده است و SLA عمومی نیست.

این صفحه نقطه شروع و فهرست مرجع توسعه است. اسناد موجود شامل PRD مصوب، معماری، UX، دفتر تصمیم‌ها، roadmap، راهنمای توسعه و QA هستند. کد، target design و نتایج محیط QA شواهد جدا دارند؛ وضعیت جاری و موارد باز را از جدول وضعیت و گزارش QA بخوانید.

## مسیر مطالعه و مالک اطلاعات

| مرجع | کاربرد و مالکیت |
|---|---|
| [PRD](prd-fa.md) | مسئله، کاربران، محدوده، نیازمندی‌های FR/NFR و پذیرش محصول |
| [معماری و قراردادها](architecture-fa.md) | رفتار فعلی، مدل داده، نقاط اتصال و مرزهای تغییر فنی |
| [طراحی تجربه فارسی و انگلیسی](ux-fa.md) | صفحه‌ها، تعامل‌ها، متن‌ها، RTL/LTR و سیاست نمایش |
| [برنامه توسعه و وضعیت](roadmap-fa.md) | وضعیت شواهد، بک‌لاگ، ترتیب اجرا، وابستگی، ریسک و معیار خروج |
| [راهنمای توسعه و انتشار](development-fa.md) | محیط، فرمان‌های موجود، اثر تست‌ها و gate بسته انتشار |
| [دفتر تصمیم‌ها](decisions-fa.md) | تصمیم تأییدشده کاربر، انتخاب عادی مهندسی و تصمیم باز |
| [QA دستی موجود](manual-qa-fa.md) | سناریوهای QA با شناسه QA و فرم ثبت اجرای واقعی |
| [گزارش QA پایلوت](pilot-qa-fa.md) | شواهد اجرای ۲۰۲۶-۱۰-۲۸، benchmark محلی، ZIP lifecycle، نتایج و موارد آزموده‌نشده |

## قواعد خواندن و نگهداری

- «مشاهده‌شده در کد» با «آزموده در محیط واقعی» متفاوت است. رفتار هدفِ تأییدشده نیز تا اجرای پلن، قابلیت موجود نیست.
- PRD مالک چرایی و پذیرش است؛ معماری مالک قرارداد فعلی؛ دفتر تصمیم‌ها مالک علت و وضعیت تصمیم؛ برنامه توسعه مالک ترتیب و وضعیت شواهد است. اطلاعات را در چند فایل کپی نکنید.
- هر تغییر عملکردی آینده باید FR/NFR مرتبط، تست، قرارداد معماری و ردیف وضعیت مربوط را در همان تغییر هماهنگ کند؛ فقط اسناد متأثر به‌روزرسانی شوند.
- نتیجه QA با تاریخ، نسخه کد و تغییرات محلی، محیط، فرمان، نتیجه و شاهد ثبت شود. صرف وجود تست یا تیک‌زدن task، اثبات عبور gate نیست.
- اگر کد با هدف محصول اختلاف دارد، هر دو را با برچسب صحیح ثبت کنید؛ کد رفتار فعلی را تعیین می‌کند و PRD رفتار مطلوب را، نه اینکه یکی را بی‌صدا جای دیگری بنشانیم.
- تصمیم مهم باز در [دفتر تصمیم‌ها](decisions-fa.md) ثبت می‌شود. فرضیه بدون پاسخ کاربر «تأییدشده» نشود.
- اجرای جاری شامل پیاده‌سازی و QA محلی مجاز تا آماده‌سازی ZIP پایلوت است. به داده یا هاست واقعی دست زده نشده و انتشار، استقرار یا ارسال بیرونی بدون مجوز انجام نمی‌شود.
- E2Eهای M3 روی سه engine در stack جاری و حداقل، QA-114/124/200..211 و builder probes در [گزارش QA پایلوت](pilot-qa-fa.md) ثبت شده‌اند. SiteOrigin Classic Page Builder/Text widget مسیر Code→Visual save/reload/render را گذراند. Elementor 4.3.5 رسمی در Apache/PHP8.4 `display_errors=Off` page-level save/reload/reselect/render را گذراند؛ default deprecation خود Elementor و PHP8.1 reload spinner به‌عنوان محدودیت محیط ثبت‌اند. QA-200..204 مستقیم پاس شدند؛ QA-202 ورودی/نمایش ارقام locale را اصلاح کرد و QA-203 با ۵۰۱ variation واقعی cap warning را نشان داد. SiteOrigin Gutenberg Layout Block و Elementor Theme Builder (طبق تصمیم کاربر)، Tab traversal در WebKit و سازگاری جامع builderها ادعا نمی‌شوند. M3 در دامنهٔ ثبت‌شده تکمیل است؛ ZIP قبلی با `Plugin_Upgrader`/`delete_plugins` روی clone lifecycle شد، اما به‌علت اصلاح source جدید باید artifact دوباره build و lifecycle شود. ZIP بیرون از مسیر محلی ارسال/منتشر نشده است. NFR-02 فقط در envelope محلی E-15 pass است.

## نسخه‌پذیری و checkout تمیز

clone تمیز در revision `a075c21fb10c359622eb96dc9b36cd7000daa766` بدون تغییرات محلی بود و شامل اسناد مصوب، `scripts/package-pilot.ps1` و `package.json` بود. `git check-ignore` تأیید کرد فایل‌های لازم ignore نیستند؛ اصلاح `.gitignore` در commit `da914d3` ثبت شده است. index تولیدی وردپرس و خروجی‌های محلی QA خارج از نسخه‌بندی‌اند. ZIP پایلوت جاری جداگانه در گزارش QA ثبت شده است.
