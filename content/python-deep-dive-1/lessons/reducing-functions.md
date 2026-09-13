## تابع کاهنده چیست؟

**reducing function** (به آن accumulator، aggregator یا folding function هم می‌گویند) تابعی است که یک iterable را **به‌صورت تکرارشونده ترکیب می‌کند تا به یک مقدار واحد** برسد. «بیشترین عنصر یک لیست» یک reduction است: از یک دنباله به یک مقدار.

### پیدا کردن max، دستی

```python
l = [5, 8, 6, 10, 9]
_max = lambda a, b: a if a > b else b     # max of TWO values

def max_sequence(sequence):
    result = sequence[0]                  # start with the first element
    for x in sequence[1:]:
        result = _max(result, x)          # combine the running result with the next element
    return result

max_sequence(l)    # 10
```

قدم‌ها: `5` → `max(5, 8)=8` → `max(8, 6)=8` → `max(8, 10)=10` → `max(10, 9)=10`. تابع دوآرگومانی همیشه روی «نتیجه‌ی تا اینجا» و «عنصر بعدی» اعمال می‌شود.

برای min فقط تابع درونی عوض می‌شود (`a if a < b else b`)؛ برای sum، `a + b`: `5 → 13 → 19 → 29 → 38`. پس تابع را پارامتر کن:

```python
def _reduce(fn, sequence):
    result = sequence[0]
    for x in sequence[1:]:
        result = fn(result, x)
    return result

_reduce(lambda a, b: a if a > b else b, l)    # 10
_reduce(lambda a, b: a if a < b else b, l)    # 5
_reduce(lambda a, b: a + b, l)                # 38
```

نسخه‌ی ما فقط با **sequence** کار می‌کند (اندیس `[0]` و برش `[1:]`)؛ روی set خطا می‌دهد.

## `functools.reduce`

```python
from functools import reduce

reduce(lambda a, b: a if a > b else b, l)             # 10
reduce(lambda a, b: a if a < b else b, {10, 5, 2, 4})   # 2   -- any iterable, sets too
reduce(lambda a, b: a if a < b else b, 'python')      # 'h'  -- lexicographic min
reduce(lambda a, b: a + ' ' + b, ('python', 'is', 'awesome'))   # 'python is awesome'
```

(برای آن آخری `' '.join(...)` راه درست است؛ اینجا فقط برای نشان دادن الگوست.) اسم تابع خودت را `reduce` نگذار — تابع پایتون را shadow می‌کنی؛ به همین دلیل `_reduce`.

## reductionهای built-in

| تابع | معادل reduce |
|---|---|
| `min(l)`, `max(l)` | `a if a < b else b` / `a if a > b else b` |
| `sum(l)` | `a + b` |
| `any(l)` | `bool(a) or bool(b)` — `True` اگر **حداقل یکی** truthy باشد |
| `all(l)` | `bool(a) and bool(b)` — `True` اگر **همه** truthy باشند |

```python
s = {True, 1, 0, None}
all(s)        # False
any(s)        # True
any([False, 0, '', None])   # False

reduce(lambda a, b: bool(a) and bool(b), s)   # False  -- same as all(s)
reduce(lambda a, b: bool(a) or b, ...)        # careful: without bool() you get an element back, not a bool
```

چرا `bool()`؟ چون `0 or '' or None or 100` طبق درس عملگرهای بولی خودِ `100` را برمی‌گرداند نه `True`.

**ضرب built-in ندارد** — با reduce:

```python
reduce(lambda a, b: a * b, [1, 3, 5, 6])      # 90

def factorial(n):
    return reduce(lambda a, b: a * b, range(1, n + 1))   # 1*2*...*n
factorial(5)    # 120
```

## initializer

`reduce(fn, iterable, initializer)`: به‌جای اینکه نتیجه از **عنصر اول** شروع شود، از `initializer` شروع می‌شود و **همه‌ی** عناصر پردازش می‌شوند — انگار مقدار اولیه جلوی iterable چسبانده شده:

```python
def _reduce(fn, iterable, initial):
    result = initial
    for x in iterable:          # no indexing: works with any iterable now
        result = fn(result, x)
    return result
```

کاربرد اصلی: iterable خالی. `reduce(lambda a, b: a + b, [])` `TypeError` می‌دهد؛ با `initializer=0` می‌شود `0`. ولی مراقب باش:

```python
reduce(lambda a, b: a + b, [1, 2, 3], 1)      # 7    -- 1 + 1 + 2 + 3
reduce(lambda a, b: a + b, [1, 2, 3], 100)    # 106
```

initializer باید **عنصر خنثی** عمل باشد: `0` برای جمع، `1` برای ضرب، `''` برای الحاق رشته. (برای ضربِ لیست خالی `1` برمی‌گردد — اگر این را نمی‌خواهی، باید جدا چک کنی.)
