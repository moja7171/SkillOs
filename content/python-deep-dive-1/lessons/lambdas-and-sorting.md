## `sorted`

`sorted(iterable, *, key=None, reverse=False)`: هر iterable‌ای می‌گیرد (لیست، tuple، set، dict، string …) و **همیشه یک لیست جدید** برمی‌گرداند که عناصر به ترتیب صعودیِ «ترتیب طبیعی»‌شان چیده شده‌اند — عددی برای اعداد، lexicographic برای رشته‌ها. **in-place نیست**؛ ورودی دست‌نخورده می‌ماند:

```python
l = [1, 5, 4, 10, 9, 6]
sorted(l)     # [1, 4, 5, 6, 9, 10]
l             # [1, 5, 4, 10, 9, 6]
```

## `key`: به هر عنصر یک «مقدار برای مرتب‌سازی» بده

`key` یک تابع است که روی **هر عنصر** اعمال می‌شود و مرتب‌سازی بر اساس **نتیجه‌ی آن** انجام می‌شود، نه خود عنصر. جای طبیعی lambda همین‌جاست.

**مرتب‌سازی بدون حساسیت به حروف بزرگ/کوچک:**

```python
l = ['c', 'B', 'D', 'a']
sorted(l)                          # ['B', 'D', 'a', 'c']   -- ord('B')=66 < ord('a')=97
sorted(l, key=lambda s: s.upper()) # ['a', 'B', 'c', 'D']
```

**dict را بر اساس مقدار مرتب کن** (پیمایش dict روی کلیدهاست، پس lambda کلید می‌گیرد):

```python
d = {'def': 300, 'abc': 200, 'ghi': 100}
sorted(d)                         # ['abc', 'def', 'ghi']         -- keys, by key
sorted(d, key=lambda e: d[e])     # ['ghi', 'abc', 'def']         -- keys, by value
```

**چیزی که اصلاً ترتیب ندارد:** اعداد مختلط با `<` مقایسه نمی‌شوند؛ `sorted([3+3j, 1-1j, 0, 3])` خطای `TypeError` می‌دهد. با `key` یک ترتیب تعریف می‌کنیم — مثلاً فاصله از مبدأ (مجذورش کافی است، چون جذر ترتیب را حفظ می‌کند):

```python
def dist_sq(x):
    return x.real ** 2 + x.imag ** 2

l = [3+3j, 1-1j, 0, 3]
sorted(l, key=dist_sq)                                      # [0, (1-1j), 3, (3+3j)]
sorted(l, key=lambda x: x.real ** 2 + x.imag ** 2)          # same, inline
```

`key` لازم نیست lambda باشد — هر تابعی که یک آرگومان بگیرد.

**بر اساس حرف آخر:**

```python
l = ['Cleese', 'Idle', 'Palin', 'Chapman', 'Gilliam', 'Jones']
sorted(l, key=lambda s: s[-1])
# ['Cleese', 'Idle', 'Palin', 'Chapman', 'Gilliam', 'Jones']  -> e e n n m s
```

## sort پایدار است

چرا `Palin` قبل از `Chapman` آمد، با اینکه C < P؟ چون از نظر key (حرف آخر `n`) **برابر**ند و مرتب‌سازی پایتون **stable** است: عناصر برابر، ترتیب اولیه‌شان را حفظ می‌کنند. اگر در لیست اصلی `Idle` را قبل از `Cleese` بگذاری، در خروجی هم `Idle` قبل می‌آید. این خاصیت وقتی چند بار پشت‌سرهم با keyهای مختلف مرتب می‌کنی به کار می‌آید.
