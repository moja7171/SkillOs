> این درس دو ویدیو دارد: بخش نظری و بخش کدنویسی.

## امضای کامل یک تابع

ترتیب کامل هر چیزی که می‌تواند در پارامترهای تابع بیاید:

```
def f(a, b, c=10,   *args | *,   kw1, kw2=100,   **kwargs)
      └─ positional ─┘  └ all ┘   └ keyword-only ┘  └ rest of keywords ┘
```

| بخش | قاعده |
|---|---|
| موقعیتی (`a, b, c=10`) | بدون پیش‌فرض = اجباری؛ فراخوان *می‌تواند* با نام بدهد. بعد از اولین پیش‌فرض، همه پیش‌فرض. |
| `*args` یا `*` | `*args` موقعیتی‌های اضافه را جمع می‌کند؛ `*` می‌گوید موقعیتی اضافه ممنوع. یکی از این دو لازم است تا keyword-only معنی پیدا کند. |
| keyword-only (`kw1, kw2=100`) | *باید* با نام داده شوند. بدون پیش‌فرض = اجباری. پیش‌فرض‌ها ترتیب آزاد دارند. |
| `**kwargs` | نام‌دارهای اضافه در dict. همیشه آخر. |

چند امضای معتبر: `f(a, b=10)`، `f(a, b, *args)`، `f(a, b, *args, kw1, kw2=100)`، `f(a, b=10, *, kw1, kw2=100)`، `f(a, b, *args, kw1, kw2=100, **kwargs)`، `f(*args)`، `f(**kwargs)`، `f(*args, **kwargs)`.

## دام: پیش‌فرض موقعیتی + `*args`

```python
def func(a, b=2, *args, c=3): print(a, b, args, c)

func(1, 2, 3, 'x', 'y')          # a=1 b=2 c=3 args=('x','y')
func(1, 'x', 'y', 'z', b=4)      # TypeError: multiple values for argument 'b'
```

می‌خواستی `b` پیش‌فرض بماند و `'x','y','z'` به args بروند؛ ولی پایتون `'x'` را به `b` می‌دهد (موقعیت دوم) و بعد `b=4` تکراری می‌شود. **وقتی `*args` داری، پیش‌فرضِ موقعیتی‌های قبلش عملاً غیرقابل استفاده است** — نمی‌توانی از رویشان بپری. مدرس برای همین در `f(a, b, *args, kw1, kw2=100)` به `b` پیش‌فرض نداد.

همین‌طور: `func(a=1, b=2, 'x')` غلط است — بعد از نام‌دار، موقعیتی ممنوع؛ و آرگومان‌های `*args` موقعیتی‌اند.

## مثال کامل

```python
def func(a, b, *args, c=10, d=20, **kwargs):
    print(a, b, args, c, d, kwargs)

func(1, 2, 'x', 'y', 'z', c=100, d=200, x=0.1, y=0.2)
# 1 2 ('x', 'y', 'z') 100 200 {'x': 0.1, 'y': 0.2}
```

## کاربرد واقعی ۱: `print`

```
print(*objects, sep=' ', end='\n', file=sys.stdout, flush=False)
```

`*objects` همان `*args` است (اسمش را `objects` گذاشته‌اند). بعدش همه keyword-only با پیش‌فرض — پس `print(1, 2, 3)` کار می‌کند و وقتی خواستی رفتار را عوض می‌کنی:

```python
print(1, 2, 3, sep='-')                 # 1-2-3
print(1, 2, 3, sep='-', end=' *** ')    # no newline
```

**الگو:** موقعیتی‌ها کار اصلی را می‌کنند؛ keyword-onlyها رفتار را تغییر می‌دهند.

## کاربرد واقعی ۲: پرچم رفتاری

```python
def calc_hi_lo_avg(*args, log_to_console=False):
    hi = int(bool(args)) and max(args)      # empty args → 0, otherwise max
    lo = min(args) if len(args) > 0 else 0  # same, with a ternary
    avg = (hi + lo) / 2
    if log_to_console:
        print(f"high={hi}, low={lo}, avg={avg}")
    return avg

calc_hi_lo_avg(1, 2, 3, 4, 5)                        # 3.0
calc_hi_lo_avg(1, 2, 3, 4, 5, log_to_console=True)   # prints + 3.0
```

`int(bool(args)) and max(args)`: tuple خالی falsy است → `0`؛ وگرنه `max`. همان short-circuit درس boolها. اگر تابعی ۶–۷ پارامتر دارد و ترتیبشان را کسی یادش نمی‌ماند، شاید بهتر است بعد از یکی‌دو پارامتر اصلی، `*` بگذاری و بقیه را keyword-only کنی.
