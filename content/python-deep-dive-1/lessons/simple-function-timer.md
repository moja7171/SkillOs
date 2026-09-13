## هدف: زمان‌سنجی هر تابعی با هر آرگومانی

می‌خواهیم `time_it(fn, ...)` تابعی دلخواه را با آرگومان‌های دلخواهش اجرا کند و زمانش را بگیرد. تابع یک شیء است، پس می‌شود پاسش داد. آرگومان‌هایش را با `*args` و `**kwargs` جمع می‌کنیم:

```python
import time

def time_it(fn, *args, **kwargs):
    print(args, kwargs)

time_it(print, 1, 2, 3, sep=' - ', end=' ***\n')
# (1, 2, 3) {'sep': ' - ', 'end': ' ***\n'}
```

`print` بدون پرانتز — خود تابع، نه نتیجه‌اش.

## دام: پاس دادن بدون باز کردن

```python
def time_it(fn, *args, **kwargs):
    fn(args, kwargs)

time_it(print, 1, 2, 3, sep=' - ')
# (1, 2, 3) {'sep': ' - '}   <- print دو شیء (یک tuple و یک dict) را چاپ کرد
```

`args` یک tuple است و `kwargs` یک dict؛ پاس دادنشان همان‌طور یعنی *دو آرگومان موقعیتی*. باید **باز** شوند — همان unpacking سمت راست:

```python
def time_it(fn, *args, **kwargs):
    fn(*args, **kwargs)

time_it(print, 1, 2, 3, sep=' - ', end=' ***\n')
# 1 - 2 - 3 ***
```

## تکرار و زمان

یک keyword-only به نام `rep` اضافه می‌کنیم. چون نامش مشخص است، به `kwargs` نمی‌رود:

```python
def time_it(fn, *args, rep=1, **kwargs):
    start = time.perf_counter()
    for i in range(rep):
        fn(*args, **kwargs)
    end = time.perf_counter()
    return (end - start) / rep      # average per run

time_it(print, 1, 2, 3, sep=' - ', end=' ***\n', rep=5)
```

## سه تابع برای مقایسه

توان‌های `n` از `start` تا `end` (بدون خود `end`)، با keyword-onlyها:

```python
def compute_powers_1(n, *, start=1, end):
    results = []
    for i in range(start, end):
        results.append(n ** i)
    return results

def compute_powers_2(n, *, start=1, end):
    return [n ** i for i in range(start, end)]      # list comprehension (later)

def compute_powers_3(n, *, start=1, end):
    return (n ** i for i in range(start, end))      # generator expression (later)

compute_powers_1(2, end=5)          # [2, 4, 8, 16]
list(compute_powers_3(2, end=5))    # generator must be consumed
```

```python
time_it(compute_powers_1, 2, start=0, end=20000, rep=5)    # ~0.49 s
time_it(compute_powers_2, n=2, start=0, end=20000, rep=5)  # ~0.49 s
time_it(compute_powers_3, 2, start=0, end=20000, rep=5)    # ~0.000002 s  <- ?!
```

دو نکته:
- `n=2` با نام هم کار می‌کند: به `kwargs` می‌رود و با `**kwargs` به `compute_powers_2` می‌رسد که `n` را به‌عنوان پارامتر موقعیتی می‌پذیرد. آرگومان‌ها فقط «عبور» می‌کنند.
- نسخه‌ی generator سریع نیست — **کاری نکرده**. generator مقادیر را وقتی حساب می‌کند که خواسته شوند؛ اینجا فقط شیء generator ساخته شد. اگر `list(...)` را داخل تابع بگذاری، زمانش با بقیه یکی می‌شود (≈ 0.48 s). generatorها بعداً مفصل.

این الگو — تابعی که تابعی را با `*args, **kwargs` عبور می‌دهد — پایه‌ی decoratorهاست.
