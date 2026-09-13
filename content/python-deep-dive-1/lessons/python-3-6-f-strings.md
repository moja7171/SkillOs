## formatted string literals (PEP 498)

قبلاً با `.format`:

```python
'{} % {} = {}'.format(10, 3, 10 % 3)                    # positional
'{1} % {2} = {0}'.format(10 % 3, 10, 3)                 # by index
'{a} % {b} = {mod}'.format(a=10, b=3, mod=10 % 3)       # by name
```

با f-string (پیشوند `f` یا `F`):

```python
a, b = 10, 3
f'{a} % {b} = {a % b}'      # '10 % 3 = 1'
```

هر **عبارت** پایتون داخل `{}` ارزیابی و درج می‌شود؛ همه‌ی format specهای قبلی کار می‌کنند:

```python
a = 10 / 3
f'{a:0.5f}'                 # '3.33333'
```

مقایسه‌ی تکرار:

```python
name = 'Python'
'{name} rocks'.format(name=name)    # 'name' written three times
f'{name} rocks!'                    # once
```

## با closure هم کار می‌کند

```python
def outer():
    name = 'Python'
    def inner():
        return f'{name} rocks!'     # name becomes a free variable just by appearing in the f-string
    return inner

outer()()      # 'Python rocks!'
```

## قابل سوءاستفاده

```python
sq = lambda x: x ** 2
a, b = 10, 1
f'{sq(a) if b > 5 else a}'                     # '10'
b = 10
f'{sq(a) if b > 5 else a}'                     # '100'
f'{(lambda x: x ** 2)(a) if b > 5 else a}'     # works ... please don't
```

عبارت‌های پیچیده را بیرون از رشته حساب کن و فقط نتیجه را درج کن — رشته باید خوانا بماند.

نکته‌های عملی:
- f-string در زمان اجرا ارزیابی می‌شود؛ برای رشته‌های ترجمه‌پذیر یا قالب‌هایی که بعداً پر می‌شوند، `.format` یا `Template` هنوز لازم است.
- کوتیشن داخل `{}`: تا 3.11 باید نوع کوتیشن متفاوت باشد (`f"{d['k']}"`)؛ از 3.12 محدودیت برداشته شده.
- 3.8 حالت `{x=}` را برای debug اضافه کرد (درس 3.8).
