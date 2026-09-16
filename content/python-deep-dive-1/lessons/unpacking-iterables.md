> این درس دو ویدیو دارد: بخش نظری و بخش کدنویسی.

## اول یک نکته درباره‌ی tuple: کاما می‌سازد، نه پرانتز

```python
a = (1, 2, 3)
a = 1, 2, 3        # the same tuple — parentheses are just for clarity
type((1))          # int — parentheses around a number are just parentheses
a = (1,)           # a single-element tuple
a = 1,             # same
a = ()             # the only exception: an empty tuple needs parentheses (or tuple())
```

## مقدار «بسته‌بندی‌شده» و باز کردن

هر **iterable** یک مقدار بسته‌بندی‌شده است: tuple، list، str (`'python'` = شش کاراکتر)، set، dict. **باز کردن** یعنی پخش کردنش در چند متغیر، **بر اساس موقعیت**:

```python
a, b, c = [1, 2, 3]          # a=1, b=2, c=3
a, b, c = 10, 20, 'hello'    # tuple → tuple
a, b, c = 'XYZ'              # a string: a='X', b='Y', c='Z'
a, b = 10, 20                # assigning multiple variables in one line (instead of two)
a, b, c = 10, {1, 2}, ['a', 'b']   # the types don't need to match
```

سمت چپ یک tuple (یا list) از نام‌هاست. آشنا نیست؟ **دقیقاً همان چیزی است که موقع فراخوانی تابع اتفاق می‌افتد**: آرگومان‌ها در پارامترها باز می‌شوند. برای فهم ترتیب، به `for` فکر کن: `for e in 10, 20, 'hello'` همان ترتیب را می‌دهد.

## کاربرد: جابه‌جایی دو متغیر

روش کلاسیک (Java و…): `temp = a; a = b; b = temp`. پایتون:

```python
a, b = b, a
```

چرا کار می‌کند؟ چون پایتون **کل سمت راست را اول و کامل** ارزیابی می‌کند — یک tuple در حافظه با ارجاع‌های `b` و `a` می‌سازد — و *بعد* انتساب‌ها را انجام می‌دهد. موقع انتساب، دیگر به مقدار فعلی `a` و `b` نگاه نمی‌کند، به آن tuple نگاه می‌کند.

همین «سمت راست اول» توضیح می‌دهد چرا این هم درست کار می‌کند:

```python
d = {'a': 1, 'b': 2, 'c': 3, 'd': 4}
d, a, b, c = d        # the right side (dict) is captured first; then d refers to 'a'
```

## set و dict: باز می‌شوند، ولی بی‌ترتیب

set و dict **ترتیب ندارند** (نمی‌شود `s[0]` گرفت). باز کردنشان مجاز است ولی معلوم نیست چه چیزی به کدام متغیر می‌رسد:

```python
s = {'p', 'y', 't', 'h', 'o', 'n'}
print(s)                    # {'h', 'p', 't', ...}  — any order
a, b, c, d, e, f = s        # in whatever iteration order that is

d = {'key1': 1, 'key2': 2, 'key3': 3}
for e in d: print(e)        # the keys
a, b, c = d                 # keys only, unordered
a, b, c = d.values()        # the values
for k, v in d.items():      # pairs — this same unpacking, inside a for
    ...
```

(پایتون ۳٫۷+ ترتیب درج dict را حفظ می‌کند — درس آپدیت‌ها — ولی set همچنان بی‌ترتیب است.)
