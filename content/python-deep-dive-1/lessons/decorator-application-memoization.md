## decorator می‌تواند رفتار تابع را عوض کند

تا اینجا decoratorها کاری «کنار» تابع می‌کردند (زمان، لاگ). اما می‌توانند رفتار خودش را تغییر دهند. **memoization**: نتایج تابع را بر اساس ورودی cache کن تا دوباره محاسبه نشود — مصرف حافظه بیشتر، زمان کمتر.

فیبوناچی بازگشتی، همان مشکل:

```python
def fib(n):
    print(f'Calculating fib({n})')
    return 1 if n < 3 else fib(n - 1) + fib(n - 2)

fib(10)    # fib(7) computed several times, fib(2) many more ...
```

## قدم ۱: cache با کلاس

```python
class Fib:
    def __init__(self):
        self.cache = {1: 1, 2: 1}
    def fib(self, n):
        if n not in self.cache:
            print(f'Calculating fib({n})')
            self.cache[n] = self.fib(n - 1) + self.fib(n - 2)
        return self.cache[n]

f = Fib()
f.fib(10)    # calculates 10, 9, ..., 3 once each → 55
f.fib(10)    # straight from the cache
```

## قدم ۲: همان با closure

```python
def fib():
    cache = {1: 1, 2: 1}
    def calc_fib(n):
        if n not in cache:               # cache is a free variable
            print(f'Calculating fib({n})')
            cache[n] = calc_fib(n - 1) + calc_fib(n - 2)
        return cache[n]
    return calc_fib

f = fib()
f(10)        # same behaviour
g = fib()    # a separate closure → a separate cache
```

## قدم ۳: decorator

فرق مهم: decorator **خودش بازگشت نمی‌زند**؛ فقط cache را چک می‌کند و اگر نبود، تابع اصلی را صدا می‌زند. بازگشت داخل خود تابع است — و چون نام `fib` بعد از decorate به closure اشاره می‌کند، فراخوانی‌های بازگشتی هم از cache می‌گذرند:

```python
def memoize(fn):
    cache = {}
    def inner(n):
        if n not in cache:
            cache[n] = fn(n)
        return cache[n]
    return inner

@memoize
def fib(n):
    print(f'Calculating fib({n})')
    return 1 if n < 3 else fib(n - 1) + fib(n - 2)

fib(10)     # calculates 10..1 once each
fib(10)     # cache
fib(11)     # only 11 is calculated
```

cache لازم نیست از قبل پر شود: `fib(1)` و `fib(2)` اگر نباشند، از خود تابع می‌آیند. و decorator به فیبوناچی وابسته نیست — روی هر تابعِ تک‌آرگومانی کار می‌کند:

```python
@memoize
def fact(n):
    print(f'Calculating {n}!')
    return 1 if n < 2 else n * fact(n - 1)

fact(6)     # 6!, 5!, ..., 1!
fact(7)     # only 7! — 6! comes from the cache
```

اثر روی سرعت:

```python
fib(35)     # ~0.0002s instead of ~3s
fib(200)    # instant
```

### محدودیت‌های نسخه‌ی ما

- فقط تابع **تک‌آرگومانی**. برای `*args` می‌شد `args` (یک tuple) را کلید کرد؛ با `**kwargs` باید کلید ترکیبی ساخت. کلید dict باید **hashable** باشد (بعداً).
- **cache بی‌حد** رشد می‌کند. معمولاً حدی می‌گذاریم و قدیمی‌ترین/کم‌استفاده‌ترین را دور می‌ریزیم — **LRU** (least recently used).

## `functools.lru_cache`

پایتون همه‌ی اینها را دارد:

```python
from functools import lru_cache

@lru_cache()                 # a parametrized decorator: note the parentheses
def fib(n):
    print(f'Calculating fib({n})')
    return 1 if n < 3 else fib(n - 1) + fib(n - 2)

fib(10)      # calculates 10..1
fib(10)      # cache
fib(11)      # only 11
```

پیش‌فرض `maxsize=128`. با `lru_cache(maxsize=8)` فقط ۸ نتیجه‌ی اخیر می‌ماند و بقیه حذف می‌شوند (توان ۲ بهینه‌تر است)؛ `maxsize=None` یعنی بی‌حد:

```python
@lru_cache(maxsize=8)
def fib(n): ...

fib(8)       # 8 items cached
fib(16)      # 16..9 calculated; 1..8 evicted (least recently used)
fib(8)       # recalculated
```

`lru_cache` هم مثل `wraps` یک decorator **پارامتردار** است — درس بعد می‌بینیم چطور خودمان چنین چیزی بسازیم.

> از پایتون 3.9 `functools.cache` هم هست: معادل `lru_cache(maxsize=None)`.
