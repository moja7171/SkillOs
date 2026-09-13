## پنج شکل import — و چیزی که در همه یکی است

| نوشتار | در `sys.modules` | در namespace ماژول ما |
|---|---|---|
| `import math` | `math` بارگذاری/cache می‌شود | `math` → شیء ماژول |
| `import math as r_math` | همان | `r_math` → شیء ماژول (`math` نیست) |
| `from math import sqrt` | همان | `sqrt` → `math.sqrt` (`math` نیست) |
| `from math import sqrt as r_sqrt` | همان | `r_sqrt` → `math.sqrt` |
| `from math import *` | همان | همه‌ی نام‌های صادرشده‌ی `math` (`pi`, `sin`, …) |

در **هر پنج حالت** ماژول به‌طور کامل بارگذاری و در `sys.modules` ثبت می‌شود. تفاوت فقط در **نام‌هایی** است که در namespace ما ساخته می‌شود. اگر نامی از قبل وجود داشت، ارجاعش **جایگزین** می‌شود.

### سوءبرداشت ۱: `from x import y` فقط بخشی از ماژول را بار می‌کند

نه. پایتون نمی‌داند `sqrt` به چه چیزهای دیگری در ماژول وابسته است؛ کل ماژول را اجرا می‌کند.

```python
import sys
'cmath' in sys.modules            # False (a module Jupyter has not preloaded)
from cmath import exp
'cmath' in globals()              # False — only exp was bound
'cmath' in sys.modules            # True  — the WHOLE module is loaded
cmath = sys.modules['cmath']
cmath.sin, cmath.pi, cmath.cos    # all there
```

(با پکیج‌ها داستان کمی فرق دارد — بعداً.)

### سوءبرداشت ۲: مشکل فقط `import *` است

`import *` خطرناک است چون ده‌ها نام را بی‌آنکه ببینی وارد می‌کند و ممکن است نام‌های قبلی را بازنویسی کند:

```python
from cmath import *      # sqrt, sin, ... → cmath versions
from math import *       # sqrt, sin, ... → now the math versions!
sin(2 + 2j)              # TypeError: math.sin can't handle complex
```

ولی ریشه‌ی مشکل «آوردن نام به namespace» است، نه ستاره:

```python
from cmath import sin
from math import sin     # silently replaces the previous sin
```

راه‌حل وقتی هر دو را لازم داری: alias — `from cmath import sin as c_sin`، `from math import sin as r_sin`. `from time import perf_counter` مشکلی ندارد، چون احتمال تصادم نام کم است.

## کارایی

### `math.sqrt(2)` در برابر `sqrt(2)`

بارگذاری یکی است. تفاوت در فراخوانی: `math.sqrt` اول باید `sqrt` را در `math.__dict__` پیدا کند — یک lookup در dict، خیلی سریع.

اندازه‌گیری با ۱۰ میلیون تکرار:

```python
from time import perf_counter
from collections import namedtuple

Timings = namedtuple('Timings', 'timing_1 timing_2 abs_diff rel_diff_perc')
def compare_timings(t1, t2):
    rel = (t2 - t1) / t1 * 100
    return Timings(round(t1, 1), round(t2, 1), round(t2 - t1, 2), round(rel, 2))

test_repeats = 10_000_000

import math
start = perf_counter()
for _ in range(test_repeats):
    math.sqrt(2)
elapsed_fully_qualified = perf_counter() - start         # ~2.0s

from math import sqrt
start = perf_counter()
for _ in range(test_repeats):
    sqrt(2)
elapsed_direct_symbol = perf_counter() - start           # ~1.7s

compare_timings(elapsed_fully_qualified, elapsed_direct_symbol)
# abs_diff ≈ 0.4s over 10 MILLION calls; ~18% relative
```

نکته: **اختلاف مطلق** مهم است. ۱۸٪ روی ۱۰ میلیون فراخوانی می‌شود ۰٫۴ ثانیه؛ روی ۱۰ هزار فراخوانی عملاً صفر. انتخاب بین این دو شکل را با خوانایی و خطر تصادم نام بکن، نه با سرعت.

### import داخل تابع

```python
def func(a):
    import math                    # frowned upon
    return math.sqrt(a)
```

دلیل اصلیِ «بد بودن»: **خوانایی** — وابستگی‌های ماژول یک‌جا دیده نمی‌شوند. از نظر کارایی: بار اول ماژول بارگذاری می‌شود، بارهای بعد فقط یک lookup در `sys.modules` و افزودن نام به namespace محلی تابع (هر فراخوانی، چون namespace محلی هر بار تازه ساخته می‌شود).

اندازه‌گیری (همان ۱۰ میلیون تکرار، هر بار یک تابع wrapper):

| بدنه‌ی تابع | زمان |
|---|---|
| `import math` سطح ماژول؛ `math.sqrt(2)` | ~3.3s (baseline با overhead فراخوانی تابع) |
| `from math import sqrt` سطح ماژول؛ `sqrt(2)` | ~2.9s |
| `import math` **داخل** تابع | ~5s (+54%) |
| `from math import sqrt` **داخل** تابع | ~15s (+200% نسبت به بالایی!) |

چرا `from math import sqrt` داخل تابع این‌قدر بدتر است؟ هر فراخوانی باید ماژول را از cache بگیرد، **بعد** `sqrt` را در namespace ماژول پیدا کند و در namespace محلی جدید بگذارد — یک قدم بیشتر از `import math` که فقط ماژول را می‌گذارد.

جمع‌بندی: import داخل تابع را برای موارد خاص (مثلاً شکستن import دایره‌ای، یا decoratorهای خودکفا) نگه دار و بدان که اگر تابع میلیون‌ها بار صدا زده می‌شود، هزینه دارد. برای توابعی که گاهی صدا زده می‌شوند، تفاوت ناچیز است.
