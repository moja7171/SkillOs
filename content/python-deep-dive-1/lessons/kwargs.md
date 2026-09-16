## `**kwargs`: جمع کردن آرگومان‌های نام‌دار اضافه

`*args` آرگومان‌های **موقعیتی** اضافه را در یک tuple جمع می‌کند؛ `**kwargs` آرگومان‌های **نام‌دار** اضافه را در یک **dict** جمع می‌کند — کلید = نام آرگومان، مقدار = مقدارش. نام `kwargs` قرارداد است؛ دو ستاره مهم است.

```python
def func(*, d, **kwargs):
    print(d, kwargs)

func(d=1, a=2, b=3)    # 1 {'a': 2, 'b': 3}
func(d=1)              # 1 {}

def func(**kwargs): ...
func(a=1, b=2)         # {'a': 1, 'b': 2}
func()                 # {}

def func(*args, **kwargs): ...
func(1, 2, a=100, b=200)   # (1, 2) {'a': 100, 'b': 200}
func()                     # () {}
```

دو قاعده:
- `**kwargs` **همیشه آخر** است — بعدش هیچ پارامتری نمی‌آید.
- برخلاف keyword-onlyها، لازم نیست قبلش موقعیتی‌ها با `*` تمام شده باشند: `def func(a, b, **kwargs)` مجاز است — خود `**` می‌گوید از اینجا فقط نام‌دار.

```python
def func(a, b, **kwargs): ...
func(1, 2, x=100, y=200)          # a=1 b=2 kwargs={'x': 100, 'y': 200}

def func(a, b, *, **kwargs): ...   # SyntaxError: named arguments must follow bare *
def func(a, b, *, d, **kwargs): ...   # OK — * only makes sense when a keyword-only name follows it
func(1, 2, x=100, d=20, y=200)     # d=20, kwargs={'x': 100, 'y': 200} — the order of keyword arguments doesn't matter
```

و در فراخوانی همان قانون همیشگی: اول موقعیتی‌ها، بعد نام‌دارها؛ برگشت به موقعیتی ممنوع (`func(1, 2, x=100, 3)` → SyntaxError).

نام `**args` نگذار — خواننده انتظار `*args` دارد. اگر `kwargs` معنی ندارد، مثلاً `**options` یا `**others`.
