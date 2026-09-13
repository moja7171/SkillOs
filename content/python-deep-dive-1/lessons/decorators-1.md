> این درس دو ویدیو دارد؛ زیرنویس ویدیوی نظری در دسترس نبود و متن از اسلایدها و ویدیوی کدنویسی نوشته شده.

## decorator چیست؟

همان کاری که در پایان درس قبل کردیم:

```python
def counter(fn):
    count = 0
    def inner(*args, **kwargs):
        nonlocal count
        count += 1
        print(f'Function {fn.__name__} (id={id(fn)}) was called {count} times')
        return fn(*args, **kwargs)
    return inner

def add(a: int, b: int = 0):
    """adds two values"""
    return a + b

add = counter(add)
add(10, 20)       # Function add (...) was called 1 times → 30
add(10)           # b defaults to 0 → 10
```

می‌گوییم `add` را با `counter` **decorate** کردیم؛ `counter` یک **decorator** است. تعریف کلی:

- یک تابع می‌گیرد،
- یک closure برمی‌گرداند،
- closure معمولاً `*args, **kwargs` می‌پذیرد تا با **هر** امضایی کار کند،
- قبل/بعد از فراخوانی، کاری اضافه انجام می‌دهد،
- تابع اصلی را با همان آرگومان‌ها صدا می‌زند و نتیجه‌اش را برمی‌گرداند.

مثلاً `mult(a, b, c=1, *, d)` که keyword-only اجباری دارد هم decorate می‌شود: `mult(1, 2, 3, d=4)` → آرگومان‌ها در `args`/`kwargs` جمع می‌شوند و عیناً به `fn` می‌رسند → `24`.

## نحو `@`

`my_func = func(my_func)` آن‌قدر رایج است که پایتون نحو کوتاه دارد:

```python
@counter
def add(a, b):
    return a + b

# is exactly the same as:
def add(a, b):
    return a + b
add = counter(add)
```

فقط syntactic sugar است؛ همان دو مرحله.

## چیزی که از دست می‌رود: متادیتا

`add` بعد از decorate شدن **همان شیء نیست** — closure است. `id(add)` عوض می‌شود و `help(add)`:

```python
help(add)
# Help on function inner in module __main__:
# inner(*args, **kwargs)
add.__name__      # 'inner'
add.__doc__       # None (or inner's own docstring)
```

نام، docstring، annotationها، پیش‌فرض‌ها و امضا — همه متعلق به `inner` است، نه `add`. `inspect.signature` هم کمکی نمی‌کند.

### راه دستی (ناقص)

```python
def counter(fn):
    ...
    inner.__name__ = fn.__name__
    inner.__doc__ = fn.__doc__
    return inner
```

نام و docstring درست می‌شود، ولی امضا نه — بازسازی‌اش پیچیده است.

### راه درست: `functools.wraps`

```python
from functools import wraps

def counter(fn):
    count = 0
    @wraps(fn)                       # copy fn's metadata onto inner
    def inner(*args, **kwargs):
        nonlocal count
        count += 1
        print(count)
        return fn(*args, **kwargs)
    return inner

@counter
def mult(a: int, b: int, c: int = 1, *, d):
    """multiplies four values"""
    return a * b * c * d

help(mult)
# mult(a: int, b: int, c: int = 1, *, d)
#     multiplies four values
inspect.signature(mult)     # <Signature (a: int, b: int, c: int = 1, *, d)>
```

`wraps` خودش یک decorator **پارامتردار** است: `wraps(fn)` یک decorator برمی‌گرداند که روی `inner` اعمال می‌شود؛ معادلش `inner = wraps(fn)(inner)`. باید به آن بگویی تابع اصلی کدام است — چون از کجا بداند کدام آرگومان است؟ (decoratorهای پارامتردار در چند درس بعد.)

**توصیه:** تقریباً همیشه از `@wraps(fn)` استفاده کن؛ هزینه‌ای ندارد و introspection، help و ابزارهای مستندسازی را سالم نگه می‌دارد.
