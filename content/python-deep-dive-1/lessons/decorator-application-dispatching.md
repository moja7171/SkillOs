> این درس سه ویدیو دارد (سه بخش single dispatch).

## مسئله: overloading نداریم

در زبان‌های static-typed می‌شود چند تابع هم‌نام با امضاهای متفاوت تعریف کرد (overloading) و کامپایلر بر اساس نوع آرگومان انتخاب می‌کند. پایتون نوع‌ها را در امضا ندارد، پس overloading ندارد. جایگزین: **single dispatch generic function** — یک تابع که بر اساس **نوع آرگومان اول** به پیاده‌سازی مناسب dispatch می‌کند.

## نمونه: تبدیل به HTML

می‌خواهیم `htmlize(obj)` بسته به نوع، خروجی HTML متفاوتی بدهد:

```python
from html import escape
from decimal import Decimal

def html_escape(arg):
    return escape(str(arg))

def html_int(a):
    return f'{a}(<i>{hex(a)}</i>)'

def html_real(a):
    return f'{round(a, 2):.2f}'

def html_str(s):
    return html_escape(s).replace('\n', '<br/>\n')

def html_list(l):
    items = (f'<li>{htmlize(item)}</li>' for item in l)     # htmlize: defined later — fine, resolved at call time
    return '<ul>\n' + '\n'.join(items) + '\n</ul>'

def html_dict(d):
    items = (f'<li>{html_escape(k)}={htmlize(v)}</li>' for k, v in d.items())
    return '<ul>\n' + '\n'.join(items) + '\n</ul>'
```

(`html_list` تابعی را صدا می‌زند که هنوز تعریف نشده؛ اشکالی ندارد — نام هنگام **فراخوانی** پیدا می‌شود. اگر داخلش `html_escape(item)` می‌گذاشتیم، عناصر تودرتو درست رندر نمی‌شدند.)

### نسخه‌ی ۱: زنجیره‌ی `isinstance`

```python
def htmlize(arg):
    if isinstance(arg, int):
        return html_int(arg)
    elif isinstance(arg, (float, Decimal)):
        return html_real(arg)
    elif isinstance(arg, str):
        return html_str(arg)
    elif isinstance(arg, (list, tuple)):
        return html_list(arg)
    elif isinstance(arg, dict):
        return html_dict(arg)
    else:
        return html_escape(arg)

htmlize(100)                        # 100(<i>0x64</i>)
htmlize(['Python\nrocks', (10, 20), 100])   # nested list, tuple and int all handled
```

کار می‌کند، ولی برای هر نوع جدید باید **بدنه‌ی `htmlize` را ویرایش کنی**.

### نسخه‌ی ۲: registry

```python
def htmlize(arg):
    registry = {object: html_escape, int: html_int, float: html_real, Decimal: html_real,
                str: html_str, list: html_list, tuple: html_list, dict: html_dict}
    fn = registry.get(type(arg), registry[object])
    return fn(arg)
```

if/elif رفت، ولی dict هنوز داخل تابع hard-code است. می‌خواهیم از **بیرون** نوع جدید ثبت کنیم → closure + decorator.

## ساختن `singledispatch` خودمان

```python
def singledispatch(fn):
    registry = {object: fn}                       # the decorated function is the default

    def decorated(arg):
        return registry.get(type(arg), registry[object])(arg)

    def register(type_):                          # a decorator FACTORY: takes the type ...
        def inner(fn):                            # ... returns a decorator that takes the function
            registry[type_] = fn
            return fn                             # return fn unchanged → stackable, and the name stays usable
        return inner

    def dispatch(type_):
        return registry.get(type_, registry[object])

    decorated.register = register                 # expose the helpers as attributes of the returned function
    decorated.dispatch = dispatch
    return decorated
```

استفاده:

```python
@singledispatch
def htmlize(a):                    # default
    return escape(str(a))

@htmlize.register(int)
def html_int(a):
    return f'{a}(<i>{hex(a)}</i>)'

@htmlize.register(list)
@htmlize.register(tuple)           # stacked: register returns fn, so the second decorator gets the same function
def html_sequence(l):
    items = (f'<li>{htmlize(item)}</li>' for item in l)
    return '<ul>\n' + '\n'.join(items) + '\n</ul>'

htmlize(100)                       # 100(<i>0x64</i>)
htmlize([1, 2, 3])                 # <ul>...
htmlize.dispatch(int)              # <function html_int>
htmlize.dispatch(float)            # <function htmlize>  (the default)
```

`registry` متغیر آزادِ هر سه closure است. `register` تابع را **تغییر نمی‌دهد** — فقط ثبت می‌کند و همان را برمی‌گرداند. ثبت از هر جای کد (حتی ماژول دیگر) ممکن است.

### نقص: `type` در برابر `isinstance`

`type(arg)` نوع دقیق را می‌دهد و وراثت را نمی‌بیند:

```python
class Person: pass
class Student(Person): pass
p = Student()
type(p) is Person        # False
isinstance(p, Person)    # True
```

پس با نسخه‌ی ما نمی‌شود چیزهای عمومی ثبت کرد:

```python
from numbers import Integral
from collections.abc import Sequence

isinstance(10, Integral), isinstance(True, Integral)          # True, True
isinstance([1, 2], Sequence), isinstance((1, 2), Sequence)    # True, True

@htmlize.register(Integral)
def html_integral(a): ...
htmlize(10)          # uses the DEFAULT — type(10) is int, not Integral
```

باید `bool`، `int` و … را جدا ثبت کنیم. حل درستش (پیدا کردن نزدیک‌ترین کلاس در سلسله‌مراتب وراثت) پیچیده است — و آماده وجود دارد.

## `functools.singledispatch`

```python
from functools import singledispatch
from numbers import Integral
from collections.abc import Sequence

@singledispatch
def htmlize(a):
    return escape(str(a))

@htmlize.register(Integral)
def html_integral_number(a):
    return f'{a}(<i>{hex(a)}</i>)'

htmlize.dispatch(int)     # html_integral_number
htmlize.dispatch(bool)    # html_integral_number  — inheritance-aware
htmlize(True)             # True(<i>0x1</i>)

@htmlize.register(Sequence)
def html_sequence(l):
    items = (f'<li>{htmlize(item)}</li>' for item in l)
    return '<ul>\n' + '\n'.join(items) + '\n</ul>'

htmlize([1, 2, 3]); htmlize((1, 2, 3))    # both work
htmlize('python')                         # RecursionError!
```

**دام:** رشته هم `Sequence` است؛ هر کاراکترش هم رشته (و باز Sequence) → بازگشت بی‌نهایت. راه‌حل: پیاده‌سازی مخصوص `str` ثبت کن. `singledispatch` **نزدیک‌ترین** نوع را انتخاب می‌کند: `str` از `Sequence` خاص‌تر است، پس برنده می‌شود. همین‌طور می‌توان برای `tuple` نسخه‌ی خاصی ثبت کرد که بر `Sequence` مقدم شود.

```python
@htmlize.register(str)
def html_str(s):
    return escape(s).replace('\n', '<br/>\n')

@htmlize.register(tuple)
def html_tuple(t):
    items = (escape(str(item)) for item in t)
    return f"({', '.join(items)})"

htmlize.registry      # mapping of registered types → functions
```

### قرارداد `_`

چون بعد از ثبت، تابع مستقیم صدا زده نمی‌شود، بعضی‌ها نامش را `_` می‌گذارند:

```python
@htmlize.register(Integral)
def _(a): ...
@htmlize.register(Sequence)
def _(l): ...
@htmlize.register(str)
def _(s): ...
```

`htmlize.registry` سه تا `_` نشان می‌دهد که **سه شیء تابع متفاوت‌اند** — registry شیء را نگه می‌دارد، نه نام را. `_` فقط یک نام معمولی است (مثل unpacking) و در پایان به آخرین تابع اشاره می‌کند. کار می‌کند، ولی خواندنش سخت‌تر است.

> از پایتون 3.7 می‌توان به‌جای `register(int)` از type annotation استفاده کرد: `@htmlize.register` روی `def _(a: int)`.
