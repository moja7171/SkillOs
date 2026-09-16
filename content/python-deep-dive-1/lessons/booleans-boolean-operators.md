> این درس دو ویدیو دارد: بخش نظری و بخش کدنویسی.

## and و or شیء برمی‌گردانند، نه bool

در جبر بولی، `and`/`or` روی bool کار می‌کنند و bool می‌دهند. در پایتون، چون هر شیئی ارزش درستی دارد، می‌توانی `x or y` را با هر دو شیء بنویسی — و آنچه برمی‌گردد **یکی از خود عملوندهاست**:

```
x or y   →  if x is truthy, return x; otherwise evaluate y and return it
x and y  →  if x is falsy, return x; otherwise evaluate y and return it
```

با bool خالص همان جدول درستی را می‌دهد (خودت چهار حالت را چک کن). و «وگرنه y را ارزیابی کن» همان short-circuit است: `y` فقط وقتی ارزیابی می‌شود که لازم باشد.

```python
'a' or [1, 2]        # 'a'
'' or [1, 2]         # [1, 2]
1 or 1/0             # 1     ← 1/0 is never evaluated
0 or 1/0             # ZeroDivisionError

None and 'x'         # None  ← x is falsy → x itself
[] and [1, 2]        # []
```

## کاربرد or: مقدار پیش‌فرض

```python
s1, s2, s3 = None, '', 'abc'       # came from a database; could be null or empty
s1 = s1 or 'n/a'                   # 'n/a'
s2 = s2 or 'n/a'                   # 'n/a'
s3 = s3 or 'n/a'                   # 'abc'

a = s1 or s2 or s3 or 'n/a'        # first truthy one, left to right
a = a or 1                         # if a was zero, use 1
```

## کاربرد and: محافظ

```python
x = 10; y = 20 / x
x and 20 / x          # 2.0
x = 0
x and 20 / x          # 0 — the division isn't evaluated

avg = n and total / n     # the average, without a divide-by-zero error when n == 0
```

## ترکیب: اولین کاراکتر یا مقدار پیش‌فرض

می‌خواهیم `s[0]` را برگردانیم، و اگر `s` `None` یا خالی بود، `''`:

```python
# option 1
if s:
    return s[0]
else:
    return ''

# option 2 — almost
return s and s[0]          # for None, returns None, not ''

# option 3
return (s and s[0]) or ''  # None/'' → ''; 'abc' → 'a'
```

`(s and s[0])` برای `None` خودِ `None` و برای `''` خودِ `''` را می‌دهد — هر دو falsy — و `or ''` آن را به رشته‌ی خالی تبدیل می‌کند. مقدار پیش‌فرض هر چه می‌خواهی می‌تواند باشد. یک خط، بدون استثنا، و بعد از یک بار دیدن، الگویی آشنا.

## not استثناست

`not` یک عملگر built-in است که همیشه **bool واقعی** برمی‌گرداند (در `help(bool)` هم `__and__`/`__or__`/`__xor__` هست ولی `not` نیست):

```python
not 'abc'     # False
not ''        # True
not None      # True
not []        # True
type(not 'abc')   # <class 'bool'>
```
