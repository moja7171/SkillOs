## وقتی همه‌چیز رو نمی‌خوای توی حافظه نگه داری

```python
values = [x * 2 for x in range(10)]

for value in values:
    print(value)
```

این کاملاً درسته — برای ۱۰ تا عدد. ولی اگه به‌جای `range(10)` یه `range(1_000_000_000)` بذاری چی؟ پایتون باید یه لیست با یه میلیارد عضو بسازه و کامل توی حافظه نگه داره — خیلی سنگین.

## راه‌حل: Generator

اگه به‌جای کروشه از **پرانتز** استفاده کنی، به‌جای یه لیست کامل، یه **generator** می‌گیری:

```python
values = (x * 2 for x in range(10))
print(values)
# <generator object <genexpr> at ...>
```

Generator هم پیمایش‌پذیره — می‌تونی روش `for` بزنی، دقیقاً مثل لیست:

```python
for value in values:
    print(value)
```

فرقش اینه: generator **همه‌ی مقادیر رو از قبل نمی‌سازه و ذخیره نمی‌کنه** — هر بار که بهش نیاز داری، فقط همون یکی رو «تولید» می‌کنه، توی لحظه.

## فرق حافظه‌ی مصرفی

```python
import sys

gen = (x * 2 for x in range(100_000))
lst = [x * 2 for x in range(100_000)]

print(sys.getsizeof(gen))   # roughly 100-200 bytes -- always about the same
print(sys.getsizeof(lst))   # hundreds of thousands of bytes -- grows with the count
```

اندازه‌ی generator تقریباً ثابته، فرقی نداره پشتش قراره ۱۰ تا مقدار تولید کنه یا یه میلیارد تا — چون فقط منطق تولید مقدار بعدی رو نگه می‌داره، نه خود مقادیر رو.

## قیمتی که می‌پردازی

```python
gen = (x * 2 for x in range(10))
print(len(gen))
# TypeError: object of type 'generator' has no len()
```

چون generator از قبل نمی‌دونه قراره چندتا مقدار تولید کنه (تا وقتی واقعاً پیمایشش نکنی)، نمی‌شه طولش رو پرسید. این معامله‌ست: حافظه‌ی کمتر، در ازای از دست‌دادن دسترسی فوری به طول یا ایندکس.

## کِی از generator استفاده کنیم

وقتی با یه دنباله‌ی خیلی بزرگ (یا حتی بی‌نهایت) سروکار داری و فقط می‌خوای یه‌بار پشت‌سرهم پیمایشش کنی — نه اینکه چندبار بهش رجوع کنی یا طول/ایندکسش رو بخوای.
