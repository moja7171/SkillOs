> این درس دو ویدیو دارد: بخش نظری (اسلایدها) و بخش کدنویسی.

## عملگرهای بولی و جدول درستی

| X | Y | not X | X and Y | X or Y |
|---|---|---|---|---|
| 0 | 0 | 1 | 0 | 0 |
| 0 | 1 | 1 | 0 | 1 |
| 1 | 0 | 0 | 0 | 1 |
| 1 | 1 | 0 | 1 | 1 |

چند قانون جبر بولی که موقع ساده کردن شرط‌ها به کار می‌آید:

- جابه‌جایی: `A or B == B or A`، `A and B == B and A`
- شرکت‌پذیری: `A or (B or C) == (A or B) or C` → پایتون `A or B or C` را چپ‌به‌راست ارزیابی می‌کند
- توزیع‌پذیری: `A and (B or C) == (A and B) or (A and C)`
- **De Morgan**: `not (A or B) == (not A) and (not B)`، `not (A and B) == (not A) or (not B)`
- `not (x < y) == x >= y`، `not (not A) == A`

## تقدم

از بالا به پایین (بالاتر = زودتر):

1. `()`
2. مقایسه‌ها: `< > <= >= == != in is`
3. `not`
4. `and`
5. `or`

```python
True or True and False          # True   ← and اول: True or (True and False)
(True or True) and False        # False
```

توصیه‌ی مدرس: **وقتی شک داری — یا برای خواننده — پرانتز بگذار.** `a < b or a > c and not x or y` را بنویس `(a < b) or ((a > c) and (not x)) or y`. هزینه‌ای ندارد و ابهام را می‌کشد.

## ارزیابی اتصال‌کوتاه (short-circuit)

از جدول: اگر `X` درست باشد، `X or Y` بدون توجه به `Y` درست است؛ اگر `X` غلط باشد، `X and Y` بدون توجه به `Y` غلط است. پایتون در این حالت‌ها **`Y` را اصلاً ارزیابی نمی‌کند**.

مثال اسلاید: یک feed قیمت سهام؛ فقط برای نمادهای watch list و اگر قیمت بالای آستانه بود کاری بکن، و `price(symbol)` **هزینه دارد**. بدون short-circuit دو `if` تودرتو می‌نوشتی؛ با آن:

```python
if symbol in watch_list and price(symbol) > threshold:
    ...
```

اگر نماد در لیست نباشد، `price()` هرگز صدا زده نمی‌شود.

## short-circuit + ارزش درستی = کد کوتاه و امن

```python
a, b = 10, 0
if a / b > 2: ...                 # ZeroDivisionError

if b > 0 and a / b > 2: ...       # امن: با b == 0 سمت راست ارزیابی نمی‌شود
if b and a / b > 2: ...           # همان — b == 0 falsy است؛ با b = None هم امن (بدون TypeError)
```

مثال دوم مدرس: `name` از یک فیلد nullable دیتابیس می‌آید — ممکن است `None`، `''` یا `'abc'` باشد — و می‌خواهیم اگر با رقم شروع می‌شود هشدار بدهیم:

```python
import string

if name[0] in string.digits:                         # با '' → IndexError، با None → TypeError
if len(name) > 0 and name[0] in string.digits:       # با None → TypeError
if name is not None and len(name) > 0 and name[0] in string.digits:   # درست ولی طولانی
if name and name[0] in string.digits:                # همان، کوتاه
```

`name` به‌تنهایی هم `None` را رد می‌کند هم رشته‌ی خالی را (falsy)؛ و به لطف short-circuit، `name[0]` فقط وقتی ارزیابی می‌شود که `name` truthy باشد.

(`string.digits`، `string.ascii_lowercase`، `string.ascii_letters` … ثابت‌های آماده‌ی ماژول `string`اند.)
