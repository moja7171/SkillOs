> این درس دو ویدیو دارد: بخش نظری و بخش کدنویسی.

## سازنده‌ی Decimal

```python
import decimal
from decimal import Decimal

Decimal()                 # Decimal('0')
Decimal(10)               # from an int
Decimal('0.1')            # from a string — exactly 0.1
Decimal('-3.1415')
Decimal(Decimal('1.5'))   # from another Decimal
```

**از float نه.** مجاز است ولی بی‌فایده: `Decimal(0.1)` همان تقریب دودویی float را *دقیقاً* ذخیره می‌کند:

```python
Decimal(0.1)
# Decimal('0.1000000000000000055511151231257827021181583404541015625')
Decimal(0.1) == Decimal('0.1')     # False
Decimal(10) == Decimal('10')       # True — int is exact
```

پس برای literal، **رشته**؛ برای مقادیر تولیدشده در کد، **tuple**.

## سازنده‌ی tuple

هر عدد ده‌دهی را می‌شود به شکل `علامت × ارقام × 10^نما` نوشت: `1.23 = +123 × 10⁻²`. سه جزء → یک tuple `(sign, digits, exponent)`:

- `sign`: `0` برای نامنفی، `1` برای منفی
- `digits`: tuple ارقام
- `exponent`: توان ۱۰ (جای ممیز)

```python
Decimal((1, (3, 1, 4, 1, 5), -4))     # Decimal('-3.1415')
Decimal((0, (3, 1, 4, 1, 5), -3))     # Decimal('31.415')
```

دقت کن که **یک** آرگومان است — پرانتز دوتایی. `Decimal(1, (3,1,4,1,5), -4)` سه آرگومان است و خطا می‌دهد.

## precision سازنده را محدود نمی‌کند

این نکته‌ی اصلی درس است. `prec` فقط روی **عملیات حسابی** اثر دارد، نه روی ساخت:

```python
decimal.getcontext().prec = 2

a = Decimal('0.12345')
b = Decimal('0.12345')
a, b          # both stored with all their digits
a + b         # Decimal('0.25')  ← real sum is 0.24690; rounded to precision 2
```

## نتیجه‌ی محاسبه در context محلی، بعد از خروج «برنمی‌گردد»

```python
decimal.getcontext().prec = 6
a = Decimal('0.12345'); b = Decimal('0.12345')
print(a + b)                     # 0.24690

with decimal.localcontext() as ctx:
    ctx.prec = 2
    c = a + b
    print(c)                     # 0.25

print(c)                         # 0.25 — still
```

`c` با دقت ۲ *ساخته شده* و همان است؛ بیرون آمدن از `with` جادویی ندارد. precision سازوکار محاسبه است، نه نمایش.
