## مشکل `None` به‌عنوان پیش‌فرض

رایج است که پیش‌فرض را `None` بگذاریم تا بفهمیم آرگومان داده شده یا نه. ولی سه حالت داریم:

1. مقداری غیر از `None` داده شده
2. **خودِ `None`** عمداً داده شده
3. اصلاً چیزی داده نشده

با `None` حالت ۲ و ۳ قابل تشخیص نیستند:

```python
def validate(a=None):
    if a is not None:
        print('Argument was provided')
    else:
        print('Argument was not provided')

validate(100)     # Argument was provided
validate()        # Argument was not provided
validate(None)    # Argument was not provided   ← but it WAS provided
```

## sentinel: مقداری که کاربر نمی‌تواند بدهد

رشته یا عدد «بعید» تضمینی نیست. اما یک **instance تازه از `object`** یکتاست و کسی جز ما به آن دسترسی ندارد (مگر عمداً از `__defaults__` بخواندش — پایتون همیشه اجازه می‌دهد پایت را هدف بگیری):

```python
_sentinel = object()

def validate(a=_sentinel):
    if a is not _sentinel:
        print('Argument was provided')
    else:
        print('Argument was not provided')

validate(100)         # provided
validate(None)        # provided   ← now distinguishable
validate()            # not provided
validate(object())    # provided   — a different object; `is` compares identity
```

مقایسه با `is`، نه `==`.

## بدون متغیر جدا: خواندن از `__defaults__`

پیش‌فرض یک بار هنگام `def` ساخته می‌شود (درس پیش‌فرض‌ها)، پس همان شیء در `__defaults__` هست:

```python
def validate(a=object()):
    default_a = validate.__defaults__[0]
    if a is not default_a:
        print('Argument was provided')
    else:
        print('Argument was not provided')
```

چند پارامتر، از جمله keyword-only (`__kwdefaults__`):

```python
def validate(a=object(), b=object(), *, kw=object()):
    default_a = validate.__defaults__[0]
    default_b = validate.__defaults__[1]
    default_kw = validate.__kwdefaults__['kw']
    print('a', 'provided' if a is not default_a else 'not provided')
    print('b', 'provided' if b is not default_b else 'not provided')
    print('kw', 'provided' if kw is not default_kw else 'not provided')

validate(100, 200, kw=None)    # all provided
validate(b=100)                # only b provided
validate()                     # none provided
```

## کِی لازم است؟

وقتی `None` خودش مقدار معناداری است: مثلاً «فیلد را به None به‌روز کن» در برابر «به این فیلد دست نزن»، یا `dict.get(key, default)` که باید بین «default داده نشده» و «default=None» فرق بگذارد. کتابخانه‌ی استاندارد همین الگو را دارد (`dataclasses.MISSING`, `inspect.Parameter.empty`).

نکته‌ی خوانایی: یک sentinel سراسری با نام گویا (`_MISSING = object()`) از `object()` درون امضا خواناتر است؛ نسخه‌ی `__defaults__` بیشتر برای نشان دادن مکانیزم بود.
