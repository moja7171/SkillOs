> این درس دو ویدیو دارد؛ زیرنویس ویدیوی نظری در دسترس نبود و متن از اسلایدها و ویدیوی کدنویسی نوشته شده.

## مسئله

دو نوع decorator دیده‌ایم:

```python
@timed                    # no call
def fib(n): ...

@wraps(fn)                # a CALL — with an argument
def inner(): ...

@lru_cache(maxsize=256)   # a call
def fact(n): ...
```

decorator `timed` ما ۱۰ تکرار را hard-code کرده بود. اضافه کردن پارامتر مستقیم:

```python
def timed(fn, reps):
    from time import perf_counter
    def inner(*args, **kwargs):
        total_elapsed = 0
        for i in range(reps):                  # reps: a free variable
            start = perf_counter()
            result = fn(*args, **kwargs)
            total_elapsed += perf_counter() - start
        print(f'avg run time: {total_elapsed / reps:.6f}s ({reps} reps)')
        return result
    return inner

fib = timed(fib, 5)     # works
@timed(5)               # TypeError: timed() missing 1 required positional argument
def fib(n): ...
```

با نحو `@`، پایتون `timed(5)` را **صدا می‌زند** و نتیجه‌اش را روی تابع اعمال می‌کند. پس `timed(5)` باید خودش یک **decorator** برگرداند.

## بازاندیشی: `@` دقیقاً چه می‌کند؟

```python
@dec
def f(): ...          # f = dec(f)

@timed(10)
def f(): ...          # tmp = timed(10);  f = tmp(f)
```

یعنی هر عبارتی بعد از `@` اول ارزیابی می‌شود و نتیجه باید تابعی باشد که یک تابع می‌گیرد. پس `timed(10)` باید decorator اصلی ما را برگرداند — closureهای تودرتو:

```python
def outer(reps):
    def timed(fn):                     # our ORIGINAL decorator
        from time import perf_counter
        def inner(*args, **kwargs):
            total_elapsed = 0
            for i in range(reps):      # reps: free variable bound in outer
                start = perf_counter()
                result = fn(*args, **kwargs)
                total_elapsed += perf_counter() - start
            print(total_elapsed / reps)
            return result
        return inner
    return timed

fib = outer(10)(fib)
# or
@outer(10)
def fib(n): ...
```

`outer` خودش decorator نیست؛ **decorator می‌سازد** — به آن **decorator factory** می‌گویند. هر بار صدا زده شود، یک decorator تازه با پارامترهای خودش برمی‌گرداند و آن پارامترها به‌عنوان متغیر آزاد در closure در دسترس‌اند.

## نسخه‌ی نهایی: نام‌ها را عوض کن

می‌خواهیم `@timed(10)` بنویسیم، نه `@outer(10)`. کافی است factory را `timed` و decorator داخلی را `dec` بنامیم:

```python
def timed(reps):
    def dec(fn):
        from time import perf_counter
        from functools import wraps

        @wraps(fn)
        def inner(*args, **kwargs):
            total_elapsed = 0
            for i in range(reps):
                start = perf_counter()
                result = fn(*args, **kwargs)
                total_elapsed += perf_counter() - start
            print(f'avg run time: {total_elapsed / reps:.6f}s ({reps} reps)')
            return result
        return inner
    return dec

@timed(15)
def fib(n):
    return calc_fib_recurse(n)

fib(28)     # avg run time: 0.1...s (15 reps)
```

## چه چیزی کِی اجرا می‌شود؟

```python
def dec_factory(a, b):
    print('running dec_factory')
    def dec(fn):
        print('running dec')
        def inner(*args, **kwargs):
            print('running inner')
            print(f'a={a}, b={b}')
            return fn(*args, **kwargs)
        return inner
    return dec

@dec_factory(100, 200)          # running dec_factory  →  running dec
def my_func():
    print('running my_func')

my_func()
# running inner
# a=100, b=200
# running my_func
```

- factory و decorator **در زمان تعریف** اجرا می‌شوند (یک بار).
- `inner` در **هر فراخوانی** اجرا می‌شود و پارامترهای factory را از closure دارد.

همه‌ی اینها با نحو بلند هم یکی است: `my_func = dec_factory(100, 200)(my_func)`.

**جمع‌بندی:** decorator پارامتردار در واقع وجود ندارد؛ چیزی که هست یک factory است که با پارامترهایش یک decorator (closure) می‌سازد. `wraps(fn)` و `lru_cache(maxsize=...)` هم دقیقاً همین‌اند.
