## راه دوم ساختن تابع

`def` تنها راه ساختن تابع نیست. **عبارت lambda** هم یک تابع می‌سازد — بدون نام:

```python
lambda [parameter list]: expression
```

- کلیدواژه‌ی `lambda`، بعد لیست پارامترها **بدون پرانتز** (می‌تواند خالی باشد)، بعد `:` که پایان پارامترهاست، بعد **یک عبارت**.
- خود این عبارت (کل lambda) یک **شیء تابع** برمی‌گرداند. عبارتِ بعد از `:` همان موقع ارزیابی نمی‌شود — مثل `def` که بدنه را اجرا نمی‌کند — بلکه **وقتی تابع صدا زده می‌شود** ارزیابی و برگردانده می‌شود. return ضمنی است.
- چون نامی ندارد، به آن **تابع بی‌نام (anonymous)** می‌گویند. اما یک تابع واقعی است: `type(lambda x: x**2)` → `function`.

```python
lambda x: x ** 2
lambda x, y: x + y
lambda: 'hello'
lambda s: s[::-1].upper()
```

> اشتباه رایج: «lambda یعنی closure». نه. lambda **می‌تواند** closure باشد، ولی الزاماً نیست؛ یک تابع معمولی است. (closure در بخش بعد.)

## با lambda چه می‌کنیم؟

**به متغیر نسبت می‌دهیم:**

```python
my_func = lambda x: x ** 2
type(my_func)     # <class 'function'>
my_func(3)        # 9
```

این دقیقاً معادل `def my_func(x): return x**2` است — با یک تفاوت: `def` نام `my_func` را روی شیء تابع می‌گذارد، ولی تابع lambda از دید پایتون نامش `<lambda>` می‌ماند:

```python
def sq(x): return x ** 2
sq                    # <function __main__.sq(x)>
lambda x: x ** 2      # <function __main__.<lambda>(x)>
```

**به تابع دیگر پاس می‌دهیم** — رایج‌ترین کاربرد:

```python
def apply_func(x, fn):
    return fn(x)

apply_func(3, lambda x: x ** 2)          # 9
apply_func(2, lambda x: x + 5)           # 7
apply_func('abc', lambda x: x[1:] * 3)   # 'bcbcbc'
```

به‌جای اینکه اول با `def` تابعی بسازی و بعد پاسش بدی، همان‌جا «در لحظه» می‌سازی. برای توابع کوتاهی که فقط یک بار لازم‌اند، ایده‌آل است.

## همه‌ی قواعد پارامترها برقرار است

پیش‌فرض، `*args`، keyword-only، `**kwargs` — همه:

```python
g = lambda x, y=10: x + y
g(1, 2)      # 3
g(1)         # 11

f = lambda x, *args, y, **kwargs: (x, args, y, kwargs)
f(1, 'a', 'b', y=100, a=10, b=20)
# (1, ('a', 'b'), 100, {'a': 10, 'b': 20})
```

و یک `apply_func` کاملاً عمومی که هر چیزی را به تابع پاس می‌دهد:

```python
def apply_func(fn, *args, **kwargs):     # fn must come first: nothing may follow **kwargs
    return fn(*args, **kwargs)

apply_func(lambda x, y: x + y, 1, 2)                 # 3
apply_func(lambda x, *, y: x + y, 1, y=20)           # 21
apply_func(lambda *args: sum(args), 1, 2, 3, 4, 5)   # 15
apply_func(sum, (1, 2, 3, 4, 5))                     # 15  (sum takes an iterable, not *args)
```

## محدودیت‌ها

- بدنه فقط **یک عبارت** است: نه انتساب (`lambda x: x = 5` خطاست)، نه چند statement، نه comment.
- **annotation ندارد** (`lambda x: int: x*2` معنی ندارد).
- می‌شود با line continuation چند خط فیزیکی نوشت، ولی اگر lambda‌ات طولانی شد، احتمالاً باید `def` بنویسی. lambda برای توابع کوتاه و ساده است.
