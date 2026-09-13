> این دوره روی پایتون 3.6 ضبط شده؛ این چند درس تغییرات مهم همان نسخه را مرور می‌کنند. فهرست کامل: [What's New in 3.6](https://docs.python.org/3/whatsnew/3.6.html). سه مورد اول هر کدام درس جداگانه دارند.

## dict ترتیب درج را نگه می‌دارد

پیاده‌سازی جدید dict در 3.6 کلیدها را به ترتیب درج نگه می‌دارد. در 3.6 «جزئیات پیاده‌سازی» بود؛ از **3.7 تضمین زبان** است. مراقب `pprint` باش که هنوز کلیدها را الفبایی مرتب می‌کند.

## ترتیب `**kwargs` حفظ می‌شود

مستقل از مورد قبل: ترتیبی که آرگومان‌های کلیدواژه‌ای پاس می‌شوند، در پیمایش `kwargs` داخل تابع حفظ می‌شود. به نظر کوچک است، ولی مثلاً ساختن factory برای named tuple با پیش‌فرض را ساده می‌کند (درس مربوط).

## زیرخط در literal عددی

```python
1_000_000          # 1000000
0x_FFFF_FFFF       # 4294967295
```

## f-string

```python
numerator, denominator = 10, 3
f'{numerator}/{denominator} = {numerator / denominator:0.3f}'    # '10/3 = 3.333'
```

هر عبارت پایتون داخل `{}`، با همان format specهای همیشگی — بسیار ساده‌تر از `.format`.

## type annotation (PEP 484, 526)

```python
from typing import List

def squares(l: List[int]) -> List[int]:
    return [e ** 2 for e in l]

my_list: List[int] = [1, 2, 3, 4]     # variable annotation (PEP 526)
squares(my_list)                      # [1, 4, 9, 16]

d = {1: 'a', 2: 'b', 3: 'c', 4: 'd'}
squares(d)                            # [1, 4, 9, 16] — a dict, not a list; Python doesn't care
```

پایتون به annotation کاری ندارد؛ ابزارهای **بیرونی** (mypy، PyCharm، pyright) از آنها برای type checking ایستا استفاده می‌کنند. به گفته‌ی PEP 484: «پایتون زبانی dynamically typed می‌ماند و نویسندگان قصدی برای اجباری کردن type hint ندارند.»

(از 3.9 می‌شود `list[int]` نوشت به‌جای `typing.List[int]`.)

## async

بهبودهای برنامه‌نویسی ناهم‌زمان (async generatorها، comprehensionهای async) — خارج از بخش ۱؛ در بخش‌های بعدی دوره.
