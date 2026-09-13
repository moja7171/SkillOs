## درون‌نگری یعنی چه؟

**introspection** یعنی بررسی کد با خود کد: با برنامه بپرسیم «این تابع چه پارامترهایی دارد؟ پیش‌فرض‌هایش چیست؟ کجا تعریف شده؟ سورسش چیست؟». چون تابع یک شیء درجه‌یک است، **attribute** دارد (اصطلاح چتری برای property و method) و می‌شود آنها را خواند — و حتی attribute جدید به آن چسباند:

```python
def my_func(a, b):
    return a + b

my_func.category = 'math'
my_func.sub_category = 'arithmetic'
my_func.category            # 'math'
```

`dir(my_func)` لیست همه‌ی attributeهای معتبر شیء را می‌دهد؛ در آن `__doc__`، `__annotations__`، `category` و چند تای مهم دیگر را می‌بینی.

## attributeهای اصلی تابع

```python
def my_func(a, b=2, c=3, *, kw1, kw2=2):
    pass

my_func.__name__        # 'my_func'
my_func.__defaults__    # (2, 3)           -- positional defaults only, as a tuple
my_func.__kwdefaults__  # {'kw2': 2}       -- keyword-only defaults, as a dict
```

`__name__` وقتی به درد می‌خورد که تابع را از طریق یک متغیر دیگر (مثلاً پارامتر `fn`) در دست داری: `fn.__name__` نام واقعی شیء را می‌دهد، چون هر دو به همان شیء اشاره می‌کنند.

`__defaults__` فقط پیش‌فرض‌های موجود را می‌دهد، بدون جای خالی برای پارامترهای بی‌پیش‌فرض؛ برای تطبیق با نام‌ها باید **از راست به چپ** بروی (چون پیش‌فرض‌ها همیشه در انتهای positionalها هستند).

### `__code__`

خود یک شیء از نوع `code` است با attributeهای خودش:

```python
def my_func(a, b=1, *args, **kwargs):
    i = 10
    b = min(i, b)
    return a * b

my_func.__code__.co_varnames   # ('a', 'b', 'args', 'kwargs', 'i')  -- params first, then locals
my_func.__code__.co_argcount   # 2   -- counts positional params only; not *args/**kwargs
```

می‌بینی که با اینها کار کردن پرزحمت است: `co_varnames` متغیرهای محلی را هم دارد، `co_argcount` بعضی پارامترها را نمی‌شمارد، `__defaults__` نام ندارد. راه راحت: ماژول `inspect`.

## ماژول `inspect`

### تابع یا متد؟

در پایتون **function** و **method** فرق دارند. متد، callable‌ای است که به یک شیء (instance) یا کلاس **bound** شده و آن را به‌عنوان آرگومان اول می‌گیرد؛ تابع، چیزی است که آزاد در ماژول تعریف شده:

```python
def my_func(): pass

class MyClass:
    def f(self): pass

my_obj = MyClass()

from inspect import isfunction, ismethod, isroutine
isfunction(my_func)      # True
ismethod(my_func)        # False
isfunction(MyClass.f)    # True   -- accessed through the class, it is still a plain function
isfunction(my_obj.f)     # False
ismethod(my_obj.f)       # True   -- bound to my_obj
isroutine(my_obj.f)      # True   -- either function or method
```

lambda هم تابع می‌سازد. (جزئیات bound/unbound در بخش شیءگرایی.)

### سورس، ماژول، commentها

```python
import inspect, math

inspect.getsource(my_func)     # the whole def as a string (header, docstring, body)
inspect.getmodule(my_func)     # <module '__main__'>
inspect.getmodule(print)       # <module 'builtins' (built-in)>
inspect.getmodule(math.sin)    # <module 'math' (built-in)>
```

```python
i = 100

# TODO: fix this function
# currently does nothing
def my_func():
    # this comment is inside; not returned
    pass

inspect.getcomments(my_func)   # '# TODO: fix this function\n# currently does nothing\n'
```

`getcomments` commentهای **بلافاصله قبل از** تعریف را برمی‌گرداند — همان چیزی که IDEها برای فهرست TODOها استفاده می‌کنند. docstring نیست؛ `my_func.__doc__` اینجا `None` است.

### امضا (signature)

```python
def my_func(a: 'mandatory positional', b: 'optional positional' = 1, c=2,
            *args: 'extra positionals', kw1, kw2=100, kw3=200,
            **kwargs: 'extra keyword') -> 'does nothing':
    """This function does nothing but has various parameters and annotations."""
    i = 10
    j = 20

sig = inspect.signature(my_func)
sig.return_annotation          # 'does nothing'
for param in sig.parameters.values():
    print(param.name, param.default, param.annotation, param.kind, sep=' | ')
```

`sig.parameters` یک dict ترتیب‌دار است: کلید نام پارامتر، مقدار یک شیء `Parameter` با `name`، `default` (یا `inspect._empty`)، `annotation` و **`kind`**:

| kind | یعنی |
|---|---|
| `POSITIONAL_OR_KEYWORD` | پارامتر معمولی (`a`, `b`, `c`) |
| `VAR_POSITIONAL` | `*args` |
| `KEYWORD_ONLY` | بعد از `*` یا `*args` (`kw1`, `kw2`, `kw3`) |
| `VAR_KEYWORD` | `**kwargs` |
| `POSITIONAL_ONLY` | فقط با موقعیت |

### positional-only

در `help(divmod)` می‌بینی `divmod(x, y, /)`. آن `/` یعنی پارامترهای قبلش **فقط positional**‌اند؛ `divmod(x=4, y=3)` خطا می‌دهد: `divmod() takes no keyword arguments`. با `inspect.signature(divmod)` هم `kind` آنها `POSITIONAL_ONLY` است.

> در زمان ضبط ویدیو (پایتون 3.6) ما نمی‌توانستیم چنین پارامتری تعریف کنیم و فقط توابع built-in داشتند. از **پایتون 3.8** (PEP 570) `/` در تعریف تابع مجاز است: `def f(a, b, /, c): ...` — `a` و `b` positional-only، `c` معمولی.
