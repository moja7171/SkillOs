> این درس دو ویدیو دارد: «مراقب مقدارهای پیش‌فرض باش» و «… دوباره». (زیرنویس انگلیسی ویدیوی دوم در دسترس نبود؛ از نوت‌بوک استفاده شده.)

## `def` خودش اجرا می‌شود — یک بار

وقتی ماژول بارگذاری می‌شود، **همه‌ی کدش اجرا می‌شود**. `a = 10` واضح است. ولی `def func(a): ...` هم اجرا می‌شود — نه بدنه‌ی تابع، بلکه خود `def`: شیء تابع ساخته می‌شود و نام `func` به آن اشاره می‌کند. و در همان لحظه **مقدارهای پیش‌فرض هم ارزیابی و ساخته می‌شوند**:

```python
def func(a=10): ...      # here the object 10 is created and stored as the default
func()                   # every call sees that same stored object; never re-evaluated
```

## دام ۱: پیش‌فرضی که قرار بود «هر بار» عوض شود

```python
from datetime import datetime

def log(msg, *, dt=datetime.utcnow()):
    print(f'{dt}: {msg}')

log('message 1')     # 2017-08-22 03:27:34.234566: message 1
# ... a few minutes later ...
log('message 2')     # 2017-08-22 03:27:34.234566: message 2   <- same time!
```

`datetime.utcnow()` **یک بار** موقع `def` صدا زده شد. هر فراخوانی همان شیء را می‌بیند.

**الگوی درست: پیش‌فرض `None`، و محاسبه داخل بدنه** — چون بدنه هر بار اجرا می‌شود:

```python
def log(msg, *, dt=None):
    dt = dt or datetime.utcnow()      # same as: if not dt: dt = datetime.utcnow()
    print(f'{dt}: {msg}')
```

`dt or ...` از درس عملگرهای بولی: اگر کاربر مقداری داد (truthy) همان؛ وگرنه الان. آرگومان همچنان اختیاری است.

## دام ۲: پیش‌فرض mutable

همان ریشه، جهت مخالف. پیش‌فرضی که به یک شیء **mutable** اشاره می‌کند، بین همه‌ی فراخوانی‌ها **مشترک** است:

```python
def add_item(name, quantity, unit, grocery_list=[]):
    grocery_list.append(f'{name} ({quantity} {unit})')
    return grocery_list

store_1 = add_item('bananas', 2, 'units')
add_item('grapes', 1, 'bunch', store_1)
store_1        # ['bananas (2 units)', 'grapes (1 bunch)']

store_2 = add_item('milk', 1, 'gallon')
store_2        # ['bananas (2 units)', 'grapes (1 bunch)', 'milk (1 gallon)']   <- !!!
```

`[]` یک بار ساخته شد؛ `store_1` و `store_2` **همان لیست**‌اند. راه‌حل، همان الگو:

```python
def add_item(name, quantity, unit, grocery_list=None):
    if not grocery_list:
        grocery_list = []          # a fresh list on every call
    grocery_list.append(f'{name} ({quantity} {unit})')
    return grocery_list
```

حالت دیگر همین دام: `def func(a=my_list)` — اگر بعداً کسی `my_list.append(...)` کند، پیش‌فرض تابع هم عوض می‌شود، چون پیش‌فرض *ارجاع* به همان شیء است. اگر می‌خواهی ثابت بماند، tuple بده.

## وقتی عمداً می‌خواهی: cache

اشتراک بین فراخوانی‌ها گاهی دقیقاً چیزی است که می‌خواهی — مثلاً برای به خاطر سپردن نتایج (memoization):

```python
def factorial(n, cache={}):
    if n < 1:
        return 1
    elif n in cache:
        return cache[n]
    else:
        print(f'calculating {n}!')
        result = n * factorial(n - 1)
        cache[n] = result
        return result

factorial(3)     # calculating 3! / 2! / 1!  -> 6
factorial(3)     # 6, no calculation
factorial(5)     # only 5! and 4! are calculated; 3! comes from the cache
```

راه بهتری برای این کار با closure و decorator هست (بخش بعد)، ولی سازوکار همین است.

**قاعده:** به پیش‌فرضی که mutable است یا نتیجه‌ی یک فراخوانی تابع است مشکوک باش. یک بار ساخته می‌شود و می‌ماند.
