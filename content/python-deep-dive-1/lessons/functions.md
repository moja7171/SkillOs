## توابع built-in

پایتون از قبل کلی تابع آماده دارد: `len`, `print`, `sum`, `max`, `sorted`, `type`, `range`, `isinstance`, ... این‌ها بدون `import` در دسترس‌اند. بعضی توابع دیگر داخل **ماژول** هستند و باید وارد شوند:

```python
import math
math.sqrt(16)      # 4.0
```

## تعریف تابع با def

```python
def func_1():
    print("running func_1")

func_1()           # صدا زدن: نام + پرانتز
```

نکته‌ی مهم: `func_1` (بدون پرانتز) **خود تابع** است — یک شیء. `func_1()` صدا زدن آن است. این تفاوت کل بخش «توابع درجه‌یک» را می‌سازد:

```python
>>> func_1
<function func_1 at 0x7f...>
>>> f = func_1     # تابع را به نام دیگری دادیم
>>> f()
running func_1
```

## پارامترها و annotationها

```python
def func_2(a: int, b: int) -> int:
    return a * b

func_2(3, 4)        # 12
func_2("a", 3)      # 'aaa'  — annotation فقط مستندسازی است، چیزی را چک نمی‌کند
```

پایتون نوع را **اجبار نمی‌کند**؛ `a: int` صرفاً به خواننده (و ابزارهایی مثل mypy) می‌گوید انتظار چیست.

## ترتیب تعریف

تابع باید قبل از **صدا زدن** تعریف شده باشد، نه قبل از اینکه در بدنه‌ی تابع دیگری *اسمش* بیاید. این کد درست است چون تا لحظه‌ی اجرای `func_3()`، `func_4` تعریف شده:

```python
def func_3():
    return func_4()

def func_4():
    return "func_4 called"

func_3()    # 'func_4 called'
```

## lambda

تابع بی‌نام یک‌خطی. بدنه‌اش فقط **یک عبارت** است که خودکار برگردانده می‌شود:

```python
square = lambda x: x ** 2
square(5)    # 25
```

معادل است با `def square(x): return x ** 2`. جای اصلی lambda جایی است که یک تابع کوچک را به تابع دیگری می‌دهی (`sorted(..., key=lambda ...)`)؛ منتسب کردن lambda به نام، طبق PEP 8 توصیه نمی‌شود — همان `def` را بنویس.
