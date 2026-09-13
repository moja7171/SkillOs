> خلاصه‌ی تغییرات 3.8 (و چند مورد 3.7) مرتبط با دوره. فهرست کامل: [What's New in 3.8](https://docs.python.org/3/whatsnew/3.8.html).

## پارامترهای positional-only: `/`

پارامتر معمولی را می‌شود هم با موقعیت داد هم با نام. بعضی built-inها اجازه‌ی نام نمی‌دهند:

```python
print(value="hello")     # TypeError: 'value' is an invalid keyword argument for print()
```

تا 3.8 نمی‌شد این را در تابع خودمان تعریف کرد (درس introspection). حالا با `/`: پارامترهای **قبل** از آن positional-only‌اند:

```python
def my_func(a, b, /):
    return a + b

my_func(1, 2)         # 3
my_func(a=1, b=2)     # TypeError: got some positional-only arguments passed as keyword arguments: 'a, b'

def my_func(a, b, /, *, c):       # combine with keyword-only
    return a + b + c
my_func(1, 2, c=10)   # 13
```

کاربرد: وقتی نام پارامتر بخشی از API نیست و می‌خواهی آزاد باشی بعداً عوضش کنی، یا وقتی تابع `**kwargs` می‌گیرد و نمی‌خواهی نام پارامترها با کلیدهای کاربر تداخل کند.

## f-string با `=`

```python
a, b = "hello", "world"
print(f"a={a}, b={b}")       # a=hello, b=world
print(f"{a=}, {b=}")         # a='hello', b='world'   — expression text + repr
print(f"{a=:s}, {b=:s}")     # a=hello, b=world       — with a format spec
```

با هر عبارت و format spec:

```python
from datetime import datetime
from math import pi
d, e = datetime.utcnow(), pi
print(f"{d=:%Y-%m-%d %H:%M:%S}, {e=:.3f}")     # d=2022-03-20 06:01:13, e=3.142
print(f"{1 + 2=}, {' '.join(['Python', 'rocks!'])=}")   # 1 + 2=3, ' '.join(...)='Python rocks!'
```

برای debug عالی است.

## `as_integer_ratio()` برای همه‌ی عددها

`float` و `Decimal` داشتند؛ حالا `bool`، `int` و `Fraction` هم — به نفع duck typing:

```python
Fraction(2, 3).as_integer_ratio()      # (2, 3)
(12).as_integer_ratio()                # (12, 1)
True.as_integer_ratio()                # (1, 1)
Decimal("0.33").as_integer_ratio()     # (33, 100)
(3.14).as_integer_ratio()              # (7070651414971679, 2251799813685248)
```

## `@lru_cache` بدون پرانتز

```python
@lru_cache(maxsize=3)     # explicit
@lru_cache()              # default 128
@lru_cache                # 3.8+: same as above; the decorator detects it was applied directly
def fib(n): ...
```

## `math.dist`

```python
a, b = (0, 0), (1, 1)
math.sqrt((b[0] - a[0]) ** 2 + (b[1] - a[1]) ** 2)   # 1.4142...
math.dist(a, b)                                       # 1.4142...  — any dimension
```

## named tuple (3.7)

- `_source` **حذف شد** (هزینه‌اش را بیش از فایده‌اش دانستند).
- پارامتر `defaults` اضافه شد — راست‌تراز، مثل `__defaults__`:

```python
NT = namedtuple("NT", "a b c", defaults=(10, 20, 30))
NT()                     # NT(a=10, b=20, c=30)
NT = namedtuple("NT", "a b c", defaults=(20, 30))
NT(10)                   # NT(a=10, b=20, c=30)
NT = namedtuple("NT", "a b c d e f", defaults=("xyz",) * 6)     # same default for all
NT._field_defaults       # {'a': 'xyz', ...}
```

**دام mutable:**

```python
NT = namedtuple("NT", "a b c", defaults=([],) * 3)    # ONE list object, referenced 3 times
nt = NT()
nt.a.append(10)
nt                       # NT(a=[10], b=[10], c=[10])
```

- (3.8) `_asdict()` حالا `dict` معمولی برمی‌گرداند نه `OrderedDict` — چون dict ترتیب درج را تضمین می‌کند.

## متفرقه

```python
d = {'a': 1, 'b': 2}
list(reversed(d.items()))     # [('b', 2), ('a', 1)]   — reversed works on dict views (3.8)
```

- `continue` داخل `finally` مجاز شد.
- **هشدار برای `is` با literal**: در دوره برای فهم interning از `a is 1` استفاده کردیم، ولی در کد واقعی `==` درست است. 3.8 هشدار می‌دهد: `SyntaxWarning: "is" with a literal. Did you mean "=="?` (برای `a is [1, 2, 3]` هشدار نمی‌دهد چون همیشه False است و ابهامی نیست).
