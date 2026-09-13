## چرا `timeit`؟

decorator زمان‌سنج ما نتیجه را چاپ می‌کرد؛ گاهی می‌خواهیم زمان را **به‌صورت داده** داشته باشیم. ماژول استاندارد `timeit`:

- کد را **خارج از namespace ما** اجرا می‌کند (باید scope لازم را به آن بدهی)،
- فقط تکه‌کد کوچک (رشته) می‌گیرد،
- garbage collector را موقتاً غیرفعال می‌کند،
- از خط فرمان هم قابل استفاده است: `python -m timeit "..."`.

benchmark دام زیاد دارد؛ برای اکثر موارد این کافی است.

```python
from timeit import timeit
```

آرگومان‌های اصلی `timeit(stmt, setup, number, globals)`:

- `stmt`: دستور(ها)ی موردنظر (رشته)
- `setup`: کد آماده‌سازی (import و …) — **یک بار** اجرا می‌شود
- `number`: تعداد تکرار — **پیش‌فرض ۱٬۰۰۰٬۰۰۰!**
- `globals`: namespace‌ای که به‌عنوان global دستور استفاده شود

خروجی: **زمان کل** `number` اجرا، نه میانگین.

## namespace جدا

```python
timeit(stmt='math.sqrt(2)')       # NameError: name 'math' is not defined
```

سه راه:

```python
timeit(stmt='import math\nmath.sqrt(2)')          # 0.318s — BAD: times the import too, on every run
timeit(stmt='math.sqrt(2)', setup='import math')  # 0.156s — setup runs once
timeit(stmt='math.sqrt(2)', globals=globals())    # 0.166s — our namespace already has math
```

## مقایسه‌ی دو سبک import (باز هم)

```python
result_1 = timeit(stmt='math.sqrt(2)', setup='import math')          # ~0.150s
result_2 = timeit(stmt='sqrt(2)', setup='from math import sqrt')     # ~0.107s
```

۰٫۰۴ ثانیه روی یک میلیون فراخوانی. اگر قبل از profile کردن برنامه این را بهینه می‌کنی، مسیر اشتباهی می‌روی. «Explicit is better than implicit»: `math.sqrt` به خواننده می‌گوید از کجا آمده؛ اگر نام ماژول طولانی است alias کن. خوانایی، نه میکروثانیه.

## دسترسی به متغیرهای خودت

```python
import random
l = random.choices(list('python'), k=500)
timeit(stmt='random.choice(l)', setup='import random', globals=globals())     # l found via globals
```

داخل تابع، `locals()`:

```python
def random_choices():
    randoms = random.choices(list('python'), k=500)
    return timeit(stmt='random.choice(randoms)', setup='import random', globals=locals())

random_choices()          # works
# with globals=globals() instead: NameError — randoms is not global
```

(نکته‌ی جانبی: نسخه‌ی local کمی سریع‌تر بود؛ کد داخل تابع عموماً از کد سطح ماژول سریع‌تر اجرا می‌شود — دسترسی به local ارزان‌تر از global است.)

توابع سطح ماژول هم در `globals()` هستند، پس می‌شود تابع را زمان گرفت:

```python
def pick_random(lst):
    return random.choice(lst)

timeit(stmt='pick_random(l)', globals=globals())     # includes the cost of the lookup + call
```

## نکته‌ها

- `number` را متناسب انتخاب کن (یک میلیون برای کد سنگین بسیار زیاد است).
- `timeit.repeat(..., repeat=5)` چند دور می‌زند؛ **کمینه** را گزارش کن (نویز سیستم فقط زمان را زیاد می‌کند).
- در نوت‌بوک: `%timeit expr` همه‌ی اینها را خودکار انجام می‌دهد.
