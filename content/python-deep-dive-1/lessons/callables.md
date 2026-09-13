## callable یعنی چه؟

**callable** هر شیئی است که بتوان با عملگر `()` صدایش زد — با یا بدون آرگومان. و هر callable **همیشه یک مقدار برمی‌گرداند** (شاید `None`، ولی همیشه چیزی)؛ پس همیشه می‌شود نتیجه را به متغیری نسبت داد.

تابع built-in `callable(obj)` می‌گوید آیا می‌شود روی `obj` عملگر فراخوانی را اعمال کرد:

```python
callable(print)         # True
callable('abc'.upper)   # True   -- a bound method
callable(str.upper)     # True
callable(callable)      # True
callable(10)            # False
callable([1, 2])        # False
```

```python
result = print('hello')   # hello
result                    # None   -- print returns something: None

l = [1, 2, 3]
result = l.append(4)      # l is now [1, 2, 3, 4]; result is None

s = 'abc'
callable(s.upper)         # True   -- the method
callable(s.upper())       # False  -- the RESULT of calling it, a str
```

مراقب باش چه چیزی را چک می‌کنی: `s.upper` خود متد است (callable)؛ `s.upper()` نتیجه‌ی فراخوانی است (رشته، غیر callable).

## چه چیزهایی callable‌اند؟

- **توابع built-in:** `print`, `len`, `callable`.
- **متدهای built-in:** `str.upper`, `list.append`.
- **توابع کاربر:** با `def` یا `lambda`.
- **متدهای کاربر:** توابع bound به instance یا کلاس.
- **کلاس‌ها:** `MyClass(...)` یک فراخوانی است. پایتون اول `__new__` را صدا می‌زند (شیء را می‌سازد؛ اگر خودت تعریف نکرده باشی از `object` می‌آید)، بعد `__init__` را (شیء ساخته‌شده را در `self` می‌گیرد و مقداردهی می‌کند)، و در آخر شیء را برمی‌گرداند.
- **instanceهای کلاس:** اگر کلاس `__call__` را پیاده کرده باشد.
- generatorها، coroutineها، async generatorها (بعداً).

```python
from decimal import Decimal
callable(Decimal)     # True   -- a class
a = Decimal('10.5')   # calling the class returns the instance
callable(a)           # False  -- this instance is not callable
type(Decimal)         # <class 'type'>   -- a class is not a function, but it is callable
```

## instance قابل فراخوانی: `__call__`

```python
class MyClass:
    def __init__(self, x=0):
        print('initializing...')
        self.counter = x

    def __call__(self, x=1):
        print('updating counter...')
        self.counter += x

b = MyClass()        # initializing...
callable(b)          # True
b(10)                # updating counter...
b.counter            # 10
b()                  # +1 (default)
b.counter            # 11
b(100)
b.counter            # 111
```

بدون `__call__`، `callable(b)` `False` بود. (`MyClass.__call__(b, 10)` هم همان کار را می‌کند — هر متد instance را می‌توان این‌طور صدا زد — ولی راه طبیعی `b(10)` است.)

**جمع‌بندی:** در نهایت همیشه «یک تابع» اجرا می‌شود (`__new__`/`__init__` برای کلاس، `__call__` برای instance)، ولی خود شیء لزوماً تابع نیست: `type(MyClass)` `type` است، نه `function`. callable مفهومی وسیع‌تر از function است.
