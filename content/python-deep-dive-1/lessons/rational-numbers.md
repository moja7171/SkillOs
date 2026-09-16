> این درس دو ویدیو دارد: بخش نظری و بخش کدنویسی.

## عدد گویا چیست؟

کسر دو عدد صحیح: `1/2`، `−22/7`. `√2` و `π` گویا نیستند. ولی **هر عدد حقیقی با تعداد رقم متناهی** گویاست: `0.45 = 45/100`، `0.123456789 = 123456789/10⁹`، حتی `8.3/1.4 = 83/14`. این جمله‌ی ساده بعداً مهم می‌شود: چون float در کامپیوتر همیشه تعداد رقم متناهی دارد، **هر float یک عدد گویاست**.

## کلاس Fraction

```python
from fractions import Fraction

Fraction(3, 4)        # Fraction(3, 4)
Fraction(22, 7)
Fraction(6, 10)       # Fraction(3, 5)  ← automatically simplified
Fraction(1, -4)       # Fraction(-1, 4) ← the sign always moves to the numerator
Fraction(numerator=1, denominator=2)   # by keyword; order doesn't matter
```

سازنده‌ها: `Fraction(num, den)` (صورت پیش‌فرض ۰، مخرج پیش‌فرض ۱)، از یک Fraction دیگر، از float، از Decimal، یا **از رشته** — حتی به شکل کسری:

```python
Fraction("10")        # Fraction(10, 1)
Fraction("0.125")     # Fraction(1, 8)
Fraction("22/7")      # Fraction(22, 7)
```

## عملیات

عملگرهای حسابی روی دو Fraction، Fraction برمی‌گردانند و نتیجه ساده می‌شود (پایتون خودش مخرج مشترک را پیدا می‌کند):

```python
x = Fraction(2, 3); y = Fraction(3, 4)
x + y       # Fraction(17, 12)
x * y       # Fraction(1, 2)
x / y       # Fraction(8, 9)
x.numerator, x.denominator    # (2, 3) — both int
```

## float → Fraction: جایی که چیزهای عجیب دیده می‌شود

```python
Fraction(0.75)     # Fraction(3, 4)
Fraction(0.125)    # Fraction(1, 8)
Fraction(0.3)      # Fraction(5404319552844595, 18014398509481984)  ← !
```

`0.3` در float **دقیقاً** ۰٫۳ نیست؛ `print(0.3)` فقط نمایش را تمیز می‌کند:

```python
format(0.3, '.5f')    # '0.30000'
format(0.3, '.25f')   # '0.2999999999999999888977698'
```

`0.125 = 1/8` مخرجش توان ۲ است و نمایش دودویی دقیق دارد؛ `0.3` نه. چرا، در درس بعد. همین اتفاق برای اعداد گنگ می‌افتد: `Fraction(math.pi)` یک کسر بزرگ می‌دهد — نه چون π گویاست، بلکه چون `math.pi` یک float با دقت متناهی است.

## محدود کردن مخرج: `limit_denominator`

نزدیک‌ترین کسر با مخرجی که از حدی بیشتر نباشد:

```python
Fraction(0.3).limit_denominator(10)    # Fraction(3, 10)

x = Fraction(math.pi)
x.limit_denominator(10)        # Fraction(22, 7)      → 3.142857
x.limit_denominator(100)       # Fraction(311, 99)    → 3.141414
x.limit_denominator(500)       # Fraction(355, 113)   → 3.1415929 (six correct digits)
```
