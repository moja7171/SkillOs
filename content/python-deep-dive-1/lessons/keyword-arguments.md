> این درس دو ویدیو دارد: بخش نظری و بخش کدنویسی.

## کاربر «می‌تواند» نام بدهد — گاهی می‌خواهیم «باید»

با `def func(a, b, c)` فراخوان می‌تواند `func(1, 2, 3)` یا `func(c=3, a=1, b=2)` بنویسد؛ انتخاب با اوست. برای **اجبار** به نام، باید اول آرگومان‌های موقعیتی «تمام» شوند — و هر پارامتری که بعد از آن بیاید فقط با نام قابل پر شدن است: **keyword-only**.

## راه اول: بعد از `*args`

```python
def func1(a, b, *args, d):
    print(a, b, args, d)

func1(1, 2, 'x', 'y', d=100)    # 1 2 ('x', 'y') 100
func1(1, 2, d=100)              # 1 2 () 100
func1(1, 2)                     # TypeError: missing keyword-only argument 'd'
func1(1, 2, 'x', 'y', 100)      # TypeError — 100 went into args

def func(*args, d): ...         # no positional is required; d is required by keyword
func(1, 2, 3, d=100); func(d=100)
```

## راه دوم: `*` تنها — «موقعیتی‌ها همین‌جا تمام»

```python
def func(*, d):
    print(d)

func(1, 2, 3, d=100)   # TypeError: takes 0 positional arguments but 3 were given
func(d=100)            # OK
```

`*` تنها چیزی جمع نمی‌کند؛ می‌گوید «بعد از این، موقعیتی قبول نمی‌کنم». می‌شود آن را بعد از چند موقعیتی گذاشت:

```python
def func(a, b, *, d): ...
func(1, 2, d=4)        # OK
func(1, 2, 3, d=4)     # TypeError: takes 2 positional arguments but 3 were given
```

## دو امضا کنار هم

```python
def func(a, b=1, *args, d, e=True): ...
def func(a, b=1, *,     d, e=True): ...
```

| | با `*args` | با `*` |
|---|---|---|
| `a` | موقعیتی اجباری (با نام هم می‌شود داد) | همان |
| `b` | موقعیتی اختیاری | همان |
| بعد از b | هر تعداد موقعیتی اضافه → `args` | **هیچ** موقعیتی اضافه‌ای مجاز نیست |
| `d` | keyword-only **اجباری** | همان |
| `e` | keyword-only اختیاری | همان |

## پیش‌فرض‌ها برای keyword-only آزادترند

قانون «بعد از اولین پیش‌فرض همه پیش‌فرض» فقط برای **موقعیتی‌ها**ست. keyword-onlyها با نام پاس می‌شوند، پس ابهامی نیست:

```python
def func(a, b=2, *args, d=0, e):     # d has a default, e after it doesn't — allowed
    ...
func(5, 4, 3, 2, 1, e='all engines running')   # d = 0
func(0, 600, d='good morning', e='python')     # args = ()
```

نوع پیش‌فرض هیچ محدودیتی روی نوع مقدار نمی‌گذارد (`b=2` ولی `b='m/s'` مجاز).

بازی کن: تابع‌های ساده‌ای بنویس که فقط پارامترها را چاپ کنند و ترکیب‌های مختلف فراخوانی را امتحان کن. درس بعد قطعه‌ی آخر: جمع کردن تعداد دلخواه آرگومان **نام‌دار**.
