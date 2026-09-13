> این درس دو ویدیو دارد: معرفی بخش «مطالب تکمیلی» و منابع.

## بخش تکمیلی چیست

مجموعه‌ای متفرقه: نکته‌ها و ترفندها، کد پایتونیک (idiomatic)، نظرهای شخصی مدرس درباره‌ی سبک کدنویسی، و چیزهای جالبی که در وب/توییتر پیدا می‌شوند — در محدوده‌ی همین بخش (توابع، closure، …)، نه metaprogramming. **درباره‌ی کتابخانه‌های third-party نیست** (PyPI بیش از ۱۰۰ هزار پکیج دارد؛ هر کس چیز متفاوتی استفاده می‌کند)؛ تمرکز روی خود زبان است.

## مستندات پایتون

نشانک شماره‌ی یک: [docs.python.org](https://docs.python.org). حواست به **نسخه** باشد (بالای صفحه قابل تغییر است) — حداقل 3.6، و ترجیحاً همان نسخه‌ای که استفاده می‌کنی.

## PEPها — Python Enhancement Proposals

بهترین منبع برای فهم «چرا» یک ویژگی این‌طور طراحی شده. همه‌ی PEPها پذیرفته نشده‌اند؛ خواندن ردشده/به‌تعویق‌افتاده‌ها هم آموزنده است — فکر زیادی پشت هر کدام است. بعضی ویژگی زبان‌اند، بعضی صرفاً اطلاعاتی.

فهرست: [peps.python.org](https://peps.python.org) (جست‌وجو در همان صفحه، یا جست‌وجوی وب مثل «Python PEP style guide»).

چند PEP مهم:

| PEP | موضوع |
|---|---|
| 8 | راهنمای سبک و کد idiomatic |
| 20 | Zen of Python — یا فقط `import this` |
| 484 | Type hints |
| 468 / 537 / … | زمان‌بندی انتشار هر نسخه (لینک به PEPهای مرتبط با آن نسخه) |

## کتاب‌ها (به ترتیب خاصی نیست)

- **Learning Python** — Mark Lutz
- **Fluent Python** — Luciano Ramalho
- **Python Cookbook** — David Beazley & Brian K. Jones
- **Effective Python** — Brett Slatkin
- **Python in a Nutshell** — Alex Martelli، Anna Ravenscroft، Steve Holden

## آنلاین

- **Raymond Hettinger** (@raymondh) — نکته‌های کوتاه و عالی. نمونه: transpose داده‌ی دوبعدی با `zip(*m)`:
  ```python
  m = [(1, 2, 3), (4, 5, 6)]
  list(zip(*m))        # [(1, 4), (2, 5), (3, 6)]
  ```
- **YouTube**: ویدیوهای PyCon؛ هر چیزی از GvR، Raymond Hettinger، Alex Martelli.
- Planet Python (planetpython.org)، Stack Overflow، و جست‌وجوی گوگل.
- Wikipedia برای مفاهیم عمومی علوم کامپیوتر.
