> خلاصه‌ی تغییرات 3.9 مرتبط با دوره. فهرست کامل: [What's New in 3.9](https://docs.python.org/3/whatsnew/3.9.html).

## `zoneinfo`: منطقه‌ی زمانی، بالاخره

تا قبل از 3.9 برای timezone و DST به `pytz` و `python-dateutil` (third-party) وابسته بودیم. حالا ماژول استاندارد `zoneinfo` (PEP 615) از پایگاه IANA استفاده می‌کند. (ویندوز: بسته‌ی `tzdata` را نصب کن.)

```python
import zoneinfo
from zoneinfo import ZoneInfo
from datetime import datetime, timezone

sorted(zoneinfo.available_timezones())        # 'Africa/Abidjan', ..., 'Asia/Tehran', ... (same list as pytz)
```

datetime بدون timezone **naive** است. اول aware کن، بعد تبدیل:

```python
now_utc_naive = datetime.utcnow()
now_utc_aware = now_utc_naive.replace(tzinfo=timezone.utc)     # or: datetime.now(timezone.utc)

# pytz way
import pytz
now_utc_aware.astimezone(pytz.timezone('Australia/Melbourne'))
# datetime(..., tzinfo=<DstTzInfo 'Australia/Melbourne' AEDT+11:00:00 DST>)

# zoneinfo way
now_utc_aware.astimezone(ZoneInfo('Europe/Dublin'))
# datetime(..., tzinfo=zoneinfo.ZoneInfo(key='Europe/Dublin'))
now_utc_aware.astimezone(ZoneInfo('Asia/Tehran'))
```

`ZoneInfo` مستقیم به‌عنوان `tzinfo` کار می‌کند (بدون `localize` مخصوص pytz). ارائه‌ی Paul Ganssle (نویسنده‌ی ماژول) خواندنی است.

## `math`

```python
import math
math.gcd(27, 45)            # 9
math.gcd(27, 45, 18, 15)    # 3   — now any number of arguments
math.lcm(2, 3, 4)           # 12  — new: least common multiple
```

## اجتماع dictها با `|`

راه‌های قبلی برای ترکیب دو dict:

```python
d1 = {'a': 1, 'b': 2, 'c': 3}
d2 = {'c': 30, 'd': 40}

{**d1, **d2}                # {'a': 1, 'b': 2, 'c': 30, 'd': 40}   — later wins

from collections import ChainMap
merged = ChainMap(d1, d2)
merged['c']                 # 3  — FIRST occurrence wins (opposite!)
```

کار می‌کنند ولی شهودی نیستند. لیست‌ها `+` دارند؛ dict به set شبیه است و set عملگر اجتماع `|` دارد. 3.9:

```python
d1 | d2                     # {'a': 1, 'b': 2, 'c': 30, 'd': 40}   — like {**d1, **d2}
d2 | d1                     # {'c': 3, 'd': 40, 'a': 1, 'b': 2}     — order controls who wins
```

ترتیب درج حفظ می‌شود: کلید `c` جایگاه اولیه‌اش (از `d1`) را نگه می‌دارد، فقط مقدارش از `d2` می‌آید:

```python
d1 = {'c': 3, 'a': 1, 'b': 2}
d2 = {'d': 40, 'c': 30}
d1 | d2                     # {'c': 30, 'a': 1, 'b': 2, 'd': 40}
```

(`|=` هم برای به‌روزرسانی درجا هست.)

## `removeprefix` / `removesuffix`

```python
data = ["(log) [2022-03-01T13:30:01] Log record 1", ...]
[s.replace("(log) ", '') for s in data]       # works (first occurrence only)
[s.lstrip("(log) ") for s in data]            # LOOKS like it works ...
```

اما `lstrip`/`rstrip` آرگومان را **مجموعه‌ی کاراکتر** می‌بیند، نه رشته — هر کاراکتری از `(`, `l`, `o`, `g`, `)`, ` ` را از ابتدا می‌کند:

```python
"(log) log: [2022...] Log record 1".lstrip("(log) ")     # ': [2022...] Log record 1'  — ate 'log: ' too
```

3.9:

```python
"(log) log: [2022...] Log record 1".removeprefix("(log) ")   # 'log: [2022...] Log record 1'
'Python rocks!'.removeprefix('Java')                         # 'Python rocks!' — no match, no error
```

`removesuffix` همین‌طور برای انتها.
