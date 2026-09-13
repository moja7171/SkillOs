## یادآوری: تابع مرتبه‌بالا

تابعی که تابع می‌گیرد یا تابع برمی‌گرداند. `sorted` با پارامتر `key` یکی بود؛ `map` و `filter` دو تای built-in دیگرند. (`zip` مرتبه‌بالا نیست، ولی کنار اینها خیلی به کار می‌آید.)

## `map(func, *iterables)`

`func` را روی عناصر iterableها **به‌موازات** اعمال می‌کند: عنصر اول همه، بعد عنصر دوم همه، … . `func` باید به تعداد iterableها آرگومان بگیرد. با کوتاه‌ترین iterable متوقف می‌شود.

```python
l = [2, 3, 4]
def sq(x):
    return x ** 2
list(map(sq, l))                    # [4, 9, 16]

l1 = [1, 2, 3]
l2 = [10, 20, 30]
def add(x, y):
    return x + y
list(map(add, l1, l2))              # [11, 22, 33]
list(map(lambda x, y: x + y, l1, l2))   # same, inline
```

## `filter(func, iterable)`

فقط **یک** iterable می‌گیرد؛ `func` روی هر عنصر صدا زده می‌شود و عنصر فقط وقتی نگه داشته می‌شود که نتیجه truthy باشد. اگر `func` را `None` بدهی، truthiness خود عنصر ملاک است:

```python
l = [0, 1, 2, 3, 4]
list(filter(None, l))                        # [1, 2, 3, 4]    -- 0 is falsy
list(filter(lambda n: n % 2 == 0, l))        # [0, 2, 4]
list(filter(None, [1, 0, 4, 'a', '', None, True, False]))   # [1, 4, 'a', True]
```

## `zip(*iterables)`

عناصر متناظر iterableها را در tuple جمع می‌کند — مثل زیپ لباس. با کوتاه‌ترین متوقف می‌شود:

```python
list(zip([1, 2, 3], [10, 20, 30]))              # [(1, 10), (2, 20), (3, 30)]
list(zip([1, 2, 3], [10, 20, 30], ['a', 'b', 'c']))   # [(1, 10, 'a'), (2, 20, 'b'), (3, 30, 'c')]
list(zip([1, 2, 3], [10, 20, 30, 40], 'python'))      # [(1, 10, 'p'), (2, 20, 'y'), (3, 30, 't')]
```

ترفند مفید: اندیس + عنصر، بدون نگرانی از طول:

```python
list(zip(range(10_000), 'abcd'))   # [(0, 'a'), (1, 'b'), (2, 'c'), (3, 'd')]
```

## اینها لیست نمی‌دهند — تنبل‌اند و یک‌بارمصرف

در پایتون ۳، `map`/`filter`/`zip` **iterator** برمی‌گردانند، نه لیست. هیچ محاسبه‌ای موقع ساخت انجام نمی‌شود؛ هر عنصر وقتی درخواستش می‌کنی محاسبه می‌شود (lazy). دو پیامد:

```python
results = map(fact, range(6))
for x in results: print(x)   # 1 1 2 6 24 120
for x in results: print(x)   # nothing! the iterator is exhausted
```

- **یک بار پیمایش می‌شوند.** برای استفاده‌ی مکرر: `results = list(map(...))`. (`range` این‌طور نیست — بارها قابل پیمایش است.)
- **خطاها دیر ظاهر می‌شوند:** `map(lambda x, y: x + y, l1, l2, l3)` بدون خطا ساخته می‌شود؛ `TypeError` فقط وقتی شروع به پیمایش کنی می‌آید.

مزیت: اگر فقط ۳ عنصر اول را بخواهی، فقط ۳ تا محاسبه می‌شود.

## list comprehension: جایگزین خواناتر

```python
[expression for item in iterable if condition]
```

```python
[x ** 2 for x in [2, 3, 4]]                       # map:    [4, 9, 16]
[x + y for x, y in zip(l1, l2)]                   # map on two lists, via zip + unpacking
[x for x in [1, 2, 3, 4] if x % 2 == 0]           # filter: [2, 4]
```

وقتی map و filter با هم می‌آیند، فرق خوانایی چشمگیر است:

```python
list(filter(lambda y: y < 25, map(lambda x: x ** 2, range(10))))
[x ** 2 for x in range(10) if x ** 2 < 25]        # [0, 1, 4, 9, 16]

list(filter(lambda s: s % 2 == 0, map(lambda x, y: x + y, l1, l2)))
[x + y for x, y in zip(l1, l2) if (x + y) % 2 == 0]   # [22, 44]
```

list comprehension **بلافاصله** همه را محاسبه می‌کند و لیست می‌سازد. اگر تنبلی `map` را می‌خواهی، همان عبارت را در **پرانتز** بنویس — **generator expression**:

```python
results = (fact(n) for n in range(10))   # <generator object ...>; nothing computed yet
for x in results: ...                    # computed as requested; exhausted afterwards
```

`list((fact(n) for n in range(10)))` معنی ندارد — همان `[fact(n) for n in range(10)]` است. comprehensionها و generatorها بعداً مفصل می‌آیند.
