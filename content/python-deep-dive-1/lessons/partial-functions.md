## مسئله: کم کردن آرگومان‌های لازم

تابعی داریم که سه آرگومان اجباری می‌خواهد:

```python
def my_func(a, b, c):
    print(a, b, c)
```

می‌خواهیم بشود فقط با دو آرگومان صدایش زد — یعنی یکی از آنها را **از قبل ثابت** کنیم. راه‌های دستی:

```python
def fn(b, c):
    return my_func(10, b, c)

fn = lambda b, c: my_func(10, b, c)

fn(20, 30)     # 10 20 30
```

`my_func` همچنان سه آرگومان می‌خواهد — جادویی در کار نیست؛ فقط یک تابع واسط ساخته‌ایم که یکی را خودش پر می‌کند.

## `functools.partial`

همین کار، تمیزتر:

```python
from functools import partial

f = partial(my_func, 10)
f(20, 30)             # 10 20 30
```

`partial(callable, *args, **kwargs)` یک callable جدید می‌سازد؛ آرگومان‌های داده‌شده «ذخیره» می‌شوند و در فراخوانی، **positionalهای ذخیره‌شده اول**، بعد آرگومان‌های جدید، و keywordها ادغام می‌شوند. `partial(my_func, 10, 20)` تابعی یک‌آرگومانی می‌دهد؛ اگر با دو آرگومان صدایش بزنی، `my_func` چهار آرگومان می‌گیرد و خطا می‌دهد.

با امضاهای پیچیده فرق partial با lambda معلوم می‌شود:

```python
def my_func(a, b, *args, k1, k2, **kwargs):
    print(a, b, args, k1, k2, kwargs)

# fix a=10 and k1='a' by hand:
def f(x, *vars, kw, **kwvars):
    return my_func(10, x, *vars, k1='a', k2=kw, **kwvars)

# with partial:
f = partial(my_func, 10, k1='a')

f(20, 100, 200, k2='b', k3=1000, k4=2000)
# 10 20 (100, 200) a b {'k3': 1000, 'k4': 2000}
```

## لازم نیست از اولین پارامتر شروع کنی

پارامترهای positional را می‌شود با نام هم پاس داد؛ پس می‌توان پارامتر **دوم** را ثابت کرد:

```python
def pow(base, exponent):
    return base ** exponent

sq = partial(pow, 2)            # WRONG: 2 goes to base → sq(10) == 2**10 == 1024
sq = partial(pow, exponent=2)   # right
cube = partial(pow, exponent=3)
sq(5)               # 25
cube(5)             # 125
cube(base=5)        # 125   -- keyword still fine
```

**قابل override است:** `cube(5, exponent=2)` → `25`. مقدار ذخیره‌شده در partial یک keyword است و keyword جدید رویش می‌نشیند. گاهی مفید است، گاهی دام.

## مراقب متغیرها باش — همان داستان پیش‌فرض‌ها

چیزی که در partial ذخیره می‌شود **ارجاع به شیء** است، نه نام متغیر:

```python
a = 2
sq = partial(pow, exponent=a)
sq(5)          # 25
a = 3          # a now points elsewhere; the partial still references the int 2
sq(5)          # 25, not 125
```

و اگر شیء mutable باشد، تغییر درونی‌اش در partial دیده می‌شود:

```python
def my_func(a, b): print(a, b)
a = [1, 2]
f = partial(my_func, a)
f(100)              # [1, 2] 100
a.append(3)
f(100)              # [1, 2, 3] 100   -- same object, mutated
```

## کاربرد: وقتی جایی تابعی با آرگومان کمتر می‌خواهد

`key` در `sorted` باید تابعی **یک‌آرگومانی** باشد. تابع فاصله‌ی ما دو نقطه می‌گیرد:

```python
origin = (0, 0)
l = [(1, 1), (0, 2), (-3, 2), (0, 0), (10, 10)]

dist2 = lambda a, b: (a[0] - b[0]) ** 2 + (a[1] - b[1]) ** 2   # squared distance keeps ordering

sorted(l, key=dist2)                     # TypeError: key calls dist2 with ONE argument
sorted(l, key=partial(dist2, origin))    # [(0, 0), (1, 1), (0, 2), (-3, 2), (10, 10)]
sorted(l, key=lambda x: dist2(origin, x))   # equivalent
```

`partial` یک تابع مرتبه‌بالاست (تابع می‌گیرد، تابع می‌دهد). lambda هم جواب می‌دهد؛ partial وقتی برنده است که آرگومان‌ها زیاد یا ترکیبی باشند و بخواهی «فقط این چند تا را ثابت کن، بقیه را عبور بده».
