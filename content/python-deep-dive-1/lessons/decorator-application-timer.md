## decorator زمان‌سنج

همان زمان‌سنجی که پیش‌تر با یک تابع مرتبه‌بالا نوشتیم، این بار به شکل decorator:

```python
def timed(fn):
    from time import perf_counter        # imported inside so the decorator is self-contained
    from functools import wraps

    @wraps(fn)
    def inner(*args, **kwargs):
        start = perf_counter()
        result = fn(*args, **kwargs)
        elapsed = perf_counter() - start

        args_ = [str(a) for a in args]                              # for display only
        kwargs_ = [f'{k}={v}' for k, v in kwargs.items()]
        args_str = ','.join(args_ + kwargs_)
        print(f'{fn.__name__}({args_str}) took {elapsed:.6f}s to run.')
        return result
    return inner
```

نکته‌ی ساختاری همان است: زمان بگیر، `fn` را صدا بزن، زمان بگیر، **نتیجه را برگردان**. بقیه فقط چاپ خوشگل آرگومان‌هاست.

## سه پیاده‌سازی فیبوناچی

اعداد فیبوناچی: ۱، ۱، ۲، ۳، ۵، ۸، ۱۳، … (هر عدد جمع دو قبلی؛ اینجا اندیس از ۱ شروع می‌شود).

### بازگشتی

```python
def calc_recursive_fib(n):
    if n <= 2:
        return 1
    return calc_recursive_fib(n - 1) + calc_recursive_fib(n - 2)

@timed
def fib_recursive(n):
    return calc_recursive_fib(n)
```

چرا خود تابع بازگشتی را decorate نکردیم؟ چون نام `calc_recursive_fib` داخل بدنه به **نسخه‌ی decorate‌شده** اشاره می‌کرد و هر فراخوانی بازگشتی جداگانه زمان‌سنجی و چاپ می‌شد. یک تابع پوششی غیربازگشتی را decorate می‌کنیم.

```python
fib_recursive(20)    # ~0.002s
fib_recursive(30)    # ~0.3s
fib_recursive(35)    # ~3s
fib_recursive(36)    # ~5s
```

رشد انفجاری: برای `fib(6)`، `fib(4)` دو بار و `fib(2)` پنج بار محاسبه می‌شود. الگوریتم زیباست ولی پر از محاسبه‌ی تکراری (درمان: memoization، دو درس بعد).

### حلقه

```python
@timed
def fib_loop(n):
    fib_1 = 1
    fib_2 = 1
    for i in range(3, n + 1):
        fib_1, fib_2 = fib_2, fib_1 + fib_2     # tuple unpacking — no temp variable
    return fib_2

fib_loop(36)    # ~0.000004s
```

### `reduce`

ایده: با tuple `(a, b)` شروع کن و هر قدم به `(a + b, a)` برو؛ بعد از `n` قدم، عنصر اول جواب است:

```
n=1: (1, 0) → (1, 1)                         → 1
n=2: (1, 0) → (1, 1) → (2, 1)                → 2
n=3: (1, 0) → (1, 1) → (2, 1) → (3, 2)       → 3
n=4: … → (5, 3)                              → 5
```

`reduce` دنباله می‌خواهد؛ یک `range(n)` قلابی می‌دهیم که فقط `n` بار تکرار کند:

```python
from functools import reduce

@timed
def fib_reduce(n):
    initial = (1, 0)
    dummy = range(n)
    fib_n = reduce(lambda prev, n: (prev[0] + prev[1], prev[0]),
                   dummy,
                   initial)
    return fib_n[0]
```

## نتیجه‌ی زمان‌سنجی — و یک درس

```python
fib_reduce(35)    # ~0.000013s
fib_loop(35)      # ~0.000007s
fib_loop(10_000)  # still about half the time of fib_reduce(10_000)
```

حلقه‌ی «خسته‌کننده» از `reduce`ِ «باحال» سریع‌تر و خواناتر است. اینکه پایتون ابزاری دارد دلیل نمی‌شود همه‌جا استفاده‌اش کنی.

## زمان‌سنجی بهتر: چند بار اجرا و میانگین

یک بار اجرا نویز دارد. decorator را طوری بنویسیم که ۱۰ بار اجرا و میانگین بگیرد:

```python
def timed(fn):
    ...
    def inner(*args, **kwargs):
        total_elapsed = 0
        for i in range(10):
            start = perf_counter()
            result = fn(*args, **kwargs)
            total_elapsed += perf_counter() - start
        avg = total_elapsed / 10
        print(f'{fn.__name__}({args_str}) took {avg:.6f}s (avg of 10 runs)')
        return result
    return inner
```

اما `10` hard-code شده. می‌خواهیم `timed(fn, reps)`:

```python
def timed(fn, reps): ...

fib_reduce = timed(fib_reduce, 15)    # works
@timed                                 # TypeError: missing required positional argument 'reps'
def fib_reduce(n): ...
```

با نحو `@` نمی‌شود آرگومان اضافه داد — همان چیزی که `@wraps(fn)` انجام می‌دهد. راه‌حل، **decorator factory** است؛ چند درس بعد.
