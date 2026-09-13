## یادآوری: factory با closure

```python
def my_dec(a, b):
    def dec(fn):
        def inner(*args, **kwargs):
            print(f'decorated function called: a={a}, b={b}')
            return fn(*args, **kwargs)
        return inner
    return dec

@my_dec(10, 20)
def my_func(s):
    print(f'Hello {s}')

my_func('world')
# decorated function called: a=10, b=20
# Hello world
```

## instance قابل فراخوانی: `__call__`

از درس callableها: اگر کلاس `__call__` داشته باشد، instanceهایش با `()` صدا زده می‌شوند:

```python
class MyClass:
    def __init__(self, a, b):
        self.a = a
        self.b = b
    def __call__(self, c):
        print(f'called a={self.a}, b={self.b}, c={c}')

obj = MyClass(10, 20)
obj.__call__(100)     # works, but ...
obj(100)              # ... this is the point:  called a=10, b=20, c=100
```

شباهت را ببین: `__init__` همان پارامترهای factory را می‌گیرد (`a`, `b`)، و `__call__` … می‌تواند **خودِ decorator** باشد.

## کلاس به‌عنوان decorator factory

```python
class MyClass:
    def __init__(self, a, b):
        self.a = a
        self.b = b

    def __call__(self, fn):                    # this IS the decorator: takes fn, returns inner
        def inner(*args, **kwargs):
            print(f'decorated function called: a={self.a}, b={self.b}')
            return fn(*args, **kwargs)
        return inner

@MyClass(10, 20)                               # MyClass(10, 20) → a callable instance → applied to my_func
def my_func(s):
    print(f'Hello {s}')

my_func('world')
# decorated function called: a=10, b=20
# Hello world
```

نحو بلندش:

```python
obj = MyClass(10, 20)      # the "decorator"
my_func = obj(my_func)     # decorate
```

نگاشت بین دو روش:

| closure factory | کلاس |
|---|---|
| `my_dec(a, b)` | `MyClass(a, b)` → `__init__` پارامترها را در `self` نگه می‌دارد |
| `dec(fn)` | `__call__(self, fn)` |
| متغیرهای آزاد `a`, `b` | `self.a`, `self.b` |
| `inner` | `inner` (همان) |

هر دو یک decorator پارامتردار می‌سازند؛ کلاس فقط حالت را به‌جای cell در attribute نگه می‌دارد. این الگو را در کدهای دیگران زیاد می‌بینی — حالا می‌دانی چیزی جز factory + callable instance نیست.

نکته: اینجا `@wraps(fn)` را حذف کردیم تا کد کوتاه بماند؛ در عمل روی `inner` بگذار.
