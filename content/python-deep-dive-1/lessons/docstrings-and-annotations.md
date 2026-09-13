> این درس دو ویدیو دارد: معرفی بخش (توابع درجه‌یک چیستند) و درس/کد docstring و annotation.

## توابع درجه‌یک (first-class)

یک شیء را **درجه‌یک** می‌گوییم اگر بتوان آن را: به تابع پاس داد، از تابع برگرداند، به متغیر نسبت داد، و در یک ساختار داده (لیست، tuple، dict …) ذخیره کرد. `int` و `float` و لیست همه چنین‌اند — و **تابع هم همین‌طور**. تابع یک شیء است و هر چهار ویژگی را دارد؛ پس در پایتون همه‌ی توابع «شهروند درجه‌یک»‌اند.

**تابع مرتبه‌بالا (higher-order)** تابعی است که یا تابعی را به‌عنوان آرگومان می‌گیرد (مثل `time_it` بخش قبل) یا تابعی برمی‌گرداند (بخش بعد: closure و decorator). این بخش درباره‌ی همین ابزارهاست: docstring/annotation، lambda، callable، introspection، `sorted`/`map`/`filter`/`zip`، `functools.reduce`، partial و ماژول `operator`.

## docstring

`help(x)` مستندات یک تابع/کلاس/ماژول را نشان می‌دهد. برای تابع خودمان: اگر **اولین خط بدنه‌ی تابع یک رشته‌ی تنها** باشد (نه انتساب، نه comment)، آن رشته docstring است (PEP 257):

```python
def my_func(a, b=1):
    '''returns a * b'''
    return a * b

help(my_func)
# my_func(a, b=1)
#     returns a * b
```

نکته‌ها:

- docstring **یک رشته‌ی معمولی** است که کامپایل می‌شود و در `my_func.__doc__` ذخیره می‌شود؛ comment در کد نمی‌ماند. به همین دلیل «رشته comment نیست».
- نوع کوتیشن مهم نیست، ولی مرسوم است از `"""..."""` استفاده کنیم — چون بعداً می‌شود بدون تغییر delimiter چند خطی‌اش کرد.
- comment قبل از docstring مشکلی ندارد (کد نیست)؛ ولی اگر قبلش یک رشته‌ی دیگر یا `a = 10` بیاید، دیگر docstring نیست.
- docstring هم موقع اجرای `def` به `__doc__` وصل می‌شود — یک بار، نه هر فراخوانی.

## annotation

راه دوم مستندسازی، روی خود پارامترها (PEP 3107): بعد از نام پارامتر `:` و یک **عبارت**؛ برای مقدار برگشتی `->` قبل از `:` انتهای هدر:

```python
def my_func(a: 'a string', b: 'a positive integer' = 1) -> 'a string':
    return a * b

help(my_func)
# my_func(a: 'a string', b: 'a positive integer' = 1) -> 'a string'
```

- پیش‌فرض بعد از annotation می‌آید: `b: int = 1`.
- `*args: 'extra positional'` و `**kwargs: 'extra keyword'` و پارامترهای keyword-only هم annotation می‌گیرند. هیچ‌چیز از قابلیت‌های امضا از دست نمی‌رود.
- annotation‌ها در `__doc__` نیستند؛ در `my_func.__annotations__` ذخیره می‌شوند — یک **dict** با کلید نام پارامتر و کلید ویژه‌ی `'return'`.

```python
def my_func(a: 'annotation for a', b: int = 1, *args: 'extras') -> str: ...
my_func.__annotations__
# {'a': 'annotation for a', 'b': <class 'int'>, 'args': 'extras', 'return': <class 'str'>}
```

### annotation هر عبارتی می‌تواند باشد — و یک بار ارزیابی می‌شود

```python
x = 3
y = 5

def my_func(a: 'some character') -> 'a repeated ' + str(max(x, y)) + ' times':
    return a * max(x, y)

my_func.__annotations__['return']   # 'a repeated 5 times'
x = 10
my_func('a')                        # 'aaaaaaaaaa'  (body re-evaluates max)
my_func.__annotations__['return']   # still 'a repeated 5 times'
```

مثل مقدارهای پیش‌فرض: عبارت annotation **هنگام `def`** ارزیابی می‌شود، نه هر فراخوانی.

## پایتون با اینها چه می‌کند؟ هیچ

نه docstring و نه annotation هیچ اثری بر اجرای کد ندارند. `a: int` یعنی «انتظار دارم int باشد»، نه «باید int باشد» — پایتون همچنان dynamically typed است. مصرف‌کننده‌ها ابزارهای بیرونی‌اند: `help`، تولیدکننده‌ی مستندات مثل Sphinx، و IDEها/type checkerها که با **type hint** (نسخه‌ی استانداردشده‌ی annotation از 3.5 به بعد؛ درس جداگانه) هشدار می‌دهند. عادت خوب: همان موقع نوشتن کد، مستندش کن.
