## کاربرد ۱: closure به‌جای کلاس

خیلی از کلاس‌های کوچک — یک حالت (state) و یک متد — با closure ساده‌تر نوشته می‌شوند.

### averager

با کلاس:

```python
class Averager:
    def __init__(self):
        self.numbers = []
    def add(self, number):
        self.numbers.append(number)
        return sum(self.numbers) / len(self.numbers)

a = Averager()
a.add(10), a.add(20), a.add(30)     # 10.0, 15.0, 20.0
```

با closure:

```python
def averager():
    numbers = []
    def add(number):
        numbers.append(number)        # numbers is a free variable (no assignment → no nonlocal needed)
        return sum(numbers) / len(numbers)
    return add

a = averager()
a(10), a(20), a(30)                 # 10.0, 15.0, 20.0
b = averager()
b(10)                               # 10.0 — its own cell; a and b are independent
```

هر بار جمع و طول کل لیست را می‌گیریم — بی‌خود. مجموع و تعداد جاری را نگه داریم:

```python
def averager():
    total = 0
    count = 0
    def add(number):
        nonlocal total, count         # assignment → must be declared nonlocal
        total += number
        count += 1
        return total / count
    return add
```

اینجا closure دو cell دارد (`count`, `total`). همان بهینه‌سازی در کلاس با `self.total`/`self.count` انجام می‌شود؛ انتخاب با توست، ولی closure معمولاً کوتاه‌تر است و overhead کمتری دارد.

### timer

```python
from time import perf_counter

class Timer:
    def __init__(self):
        self.start = perf_counter()
    def __call__(self):                 # makes the instance callable: t() instead of t.poll()
        return perf_counter() - self.start

def timer():
    start = perf_counter()
    def poll():
        return perf_counter() - start
    return poll

t1 = Timer(); t2 = timer()
t1(), t2()                              # both: seconds elapsed since creation
```

با `__call__` دو نسخه از بیرون کاملاً یک‌شکل صدا زده می‌شوند.

## کاربرد ۲: شمارنده و «شمردن فراخوانی‌ها»

شمارنده با مقدار اولیه و گام:

```python
def counter(initial_value=0):
    def inc(increment=1):
        nonlocal initial_value
        initial_value += increment
        return initial_value
    return inc

c = counter()
c(), c()          # 1, 2
```

(همان incrementer درس closure، بدون نیاز به سه لایه.)

حالا چیزی مفیدتر: **بشماریم یک تابع چند بار صدا زده شده**. تابع را می‌گیریم، closure‌ای برمی‌گردانیم که به‌جای آن صدا زده می‌شود:

```python
def counter(fn):
    cnt = 0
    def inner(*args, **kwargs):          # accept anything, forward everything
        nonlocal cnt
        cnt += 1
        print(f'{fn.__name__} has been called {cnt} times')
        return fn(*args, **kwargs)
    return inner

def add(a, b): return a + b
def mult(a, b): return a * b

counted_add = counter(add)
counted_add.__code__.co_freevars         # ('cnt', 'fn')
counted_add(10, 20)                      # add has been called 1 times → 30
counted_add(10, 20)                      # add has been called 2 times → 30
```

`*args, **kwargs` به این دلیل است که `inner` باید بتواند **هر** تابعی را با هر امضایی پوشش دهد.

### ذخیره‌ی شمارش در dict

به‌جای print، شمارش را در یک dict می‌نویسیم. اول با متغیر global:

```python
counters = {}

def counter(fn):
    cnt = 0
    def inner(*args, **kwargs):
        nonlocal cnt
        cnt += 1
        counters[fn.__name__] = cnt      # mutating the dict, not assigning to `counters` → no global needed
        return fn(*args, **kwargs)
    return inner
```

بهتر: dict را **پاس بده** تا closure به نام global وابسته نباشد:

```python
def counter(fn, counters):
    cnt = 0
    def inner(*args, **kwargs):
        nonlocal cnt
        cnt += 1
        counters[fn.__name__] = cnt      # counters is a free variable now
        return fn(*args, **kwargs)
    return inner

c = {}
counted_add = counter(add, c)
counted_mult = counter(mult, c)
counted_add(10, 20); counted_mult(2, 5); counted_mult(3, 6)
c        # {'add': 1, 'mult': 2}
```

## گام آخر: همان نام را نگه دار

```python
def fact(n):
    product = 1
    for i in range(2, n + 1):
        product *= i
    return product

fact = counter(fact, c)      # rebind the SAME name to the closure
fact(3), fact(5), fact(10)   # 6, 120, 3628800 — works exactly as before
c                            # {..., 'fact': 3}
fact.__closure__             # three cells: cnt (int), counters (dict), fn (the original function)
```

`fact` هنوز مثل قبل صدا زده می‌شود، ولی حالا closure‌ای است که تابع اصلی را اجرا می‌کند **به‌علاوه‌ی** یک کار اضافه. این دقیقاً ایده‌ی **decorator** است — درس بعد.
