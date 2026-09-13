> این درس دو ویدیو دارد: بخش نظری و بخش کدنویسی.

## دو سازنده‌ی int

`int` یک کلاس است و دو سازنده دارد (`help(int)` را ببین):

**۱. از یک مقدار عددی:** `int(10)`، `int(-10)`، یا هر نوع عددی دیگر — با **truncation** (بریدن اعشار، نه floor):

```python
int(10.9)      # 10
int(-10.9)     # -10   ← truncation، نه floor
int(True)      # 1
int(False)     # 0
int()          # 0

from fractions import Fraction
int(Fraction(22, 7))    # 3
```

**۲. از یک رشته، با مبنای اختیاری:** `int("123")` رشته را در **مبنای ۱۰** (پیش‌فرض) می‌خواند. پارامتر `base` می‌تواند ۲ تا ۳۶ باشد:

```python
int("1010", base=2)   # 10
int("101", 2)         # 5
int("FF", base=16)    # 255
int("ff", base=16)    # 255   — حروف حساس به بزرگی نیستند
int("534", base=8)    # 348
int("A", base=11)     # 10
int("B", base=11)     # ValueError: invalid literal for int() with base 11: 'B'
```

چرا حداکثر ۳۶؟ چون پایتون ارقام را با `0–9` و `A–Z` می‌نویسد: ۱۰ + ۲۶ = ۳۶ نماد. برای مبنای بالاتر باید خودت کد بزنی.

نکته‌ی مفهومی مدرس: این «تبدیل عدد» نیست — همان عدد است، فقط **نمایشش** در مبنایی دیگر. توی حافظه همیشه دودویی است.

## مسیر برعکس: از int به نمایش در مبنای دیگر

پایتون برای سه مبنا تابع آماده دارد؛ خروجی **رشته** است با پیشوندی که مبنا را مستند می‌کند:

```python
bin(10)     # '0b1010'
oct(10)     # '0o12'
hex(255)    # '0xff'
```

همین پیشوندها literal هم می‌سازند — بدون رشته و بدون سازنده:

```python
a = 0b1010   # 10
b = 0o12     # 10
c = 0xA      # 10   (0xa هم همان است)
```

## الگوریتم تغییر مبنا با div و mod

برای مبناهای دیگر باید خودت بنویسی — و این تمرین خوبی برای `//` و `%` است. ایده: از همان معادله‌ی همیشگی، هر بار `n % b` **رقم بعدی از راست** را می‌دهد و `n // b` عددی است که باید ادامه بدهی. مثال مدرس، `232` در مبنای ۵:

```
232 = 46·5 + 2        → رقم آخر: 2
 46 =  9·5 + 1        → رقم بعدی: 1
  9 =  1·5 + 4        → 4
  1 =  0·5 + 1        → 1، و div شد صفر: توقف
```

نتیجه: `1412₅`. الگوریتم:

```python
def from_base10(n, b):
    if b < 2:
        raise ValueError("base b must be >= 2")
    if n < 0:
        raise ValueError("number n must be >= 0")
    if n == 0:
        return [0]
    digits = []
    while n > 0:
        n, m = divmod(n, b)     # n //= b ; m = n % b — با ترتیب درست
        digits.insert(0, m)     # از راست به چپ می‌سازیم
    return digits

from_base10(10, 2)      # [1, 0, 1, 0]
from_base10(255, 16)    # [15, 15]
```

`divmod(n, b)` هر دو مقدار `//` و `%` را یک‌جا برمی‌گرداند. ترتیب مهم است: اگر اول `n` را تقسیم کنی و بعد mod بگیری، رقم غلط می‌گیری.

## رقم ≠ نویسه: encoding جداست

`[15, 15]` هنوز «FF» نیست. تبدیل رقم به نویسه یک قدم **جداگانه** است و نگاشتش دست خودت: `0–9A–Z` قرارداد است، ولی می‌توانی حروف کوچک و بزرگ را متمایز کنی (مبنای ۶۲) یا هر نویسه‌ای بگذاری — فقط خواننده باید نگاشت را بداند.

```python
def encode(digits, digit_map):
    if max(digits) >= len(digit_map):
        raise ValueError("digit_map is not long enough to encode the digits")
    return ''.join(digit_map[d] for d in digits)

encode([15, 15], '0123456789ABCDEF')   # 'FF'
```

(نسخه‌ی حلقه‌ای با `encoding += digit_map[d]` هم کار می‌کند ولی هر بار رشته‌ی جدید می‌سازد — رشته immutable است. `join` روی یک comprehension بهتر است؛ comprehension بعداً مفصل می‌آید.)

و همه با هم، با پشتیبانی از عدد منفی:

```python
def rebase_from_base10(number, base):
    digit_map = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'
    if base < 2 or base > 36:
        raise ValueError("invalid base: 2 <= base <= 36")
    sign = -1 if number < 0 else 1
    number *= sign                     # قدر مطلق
    digits = from_base10(number, base)
    encoding = encode(digits, digit_map)
    if sign == -1:
        encoding = '-' + encoding
    return encoding

e = rebase_from_base10(-314, 2)   # '-100111010'
int(e, base=2)                    # -314  ← رفت و برگشت
rebase_from_base10(3451, 16)      # 'D7B'
```
