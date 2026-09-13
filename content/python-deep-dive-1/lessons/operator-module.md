## چرا ماژول `operator`؟

در درس‌های قبل بارها چنین چیزی نوشتیم:

```python
reduce(lambda a, b: a * b, [2, 3, 4])    # 24
```

آن lambda فقط «معادل تابعیِ عملگر `*`» است. توابع مرتبه‌بالا **تابع** می‌خواهند و نمی‌شود خود عملگر را پاس داد؛ ماژول `operator` همین معادل‌ها را آماده دارد:

```python
import operator
from functools import reduce

reduce(operator.mul, [2, 3, 4])          # 24
```

`dir(operator)` یا `help(operator)` فهرست کامل را می‌دهد. خلاصه‌ی مهم‌ها:

| دسته | توابع | معادل |
|---|---|---|
| حسابی | `add`, `sub`, `mul`, `truediv`, `floordiv`, `mod`, `pow`, `neg`, `abs` | `a + b`, `a - b`, `a * b`, `a / b`, `a // b`, `a % b`, `a ** b`, `-a`, `abs(a)` |
| مقایسه | `lt`, `le`, `gt`, `ge`, `eq`, `ne` | `<`, `<=`, `>`, `>=`, `==`, `!=` |
| هویت/بولی | `is_`, `is_not`, `and_`, `or_`, `not_`, `truth` | `is`, `is not`, `and`, `or`, `not`, `bool(a)` |
| دنباله | `concat`, `contains`, `countOf`, `getitem`, `setitem`, `delitem` | `s1 + s2`, `x in s`, شمارش، `s[i]`, `s[i] = v`, `del s[i]` |

نام‌هایی مثل `is_` و `and_` underscore دارند چون `is`/`and`/`not` کلیدواژه‌اند و `from operator import is` غیرممکن است. آنهایی که با `i` شروع می‌شوند (`iadd`, `imul` …) معادل عملگرهای in-place (`+=`, `*=`) هستند — بعداً در شیءگرایی.

```python
operator.add(1, 2)              # 3
operator.truediv(3, 2)          # 1.5
operator.floordiv(13, 2)        # 6
operator.lt(10, 3)              # False
operator.is_('abc', 'abc')      # True  (interning)
operator.truth([])              # False

my_list = [1, 2, 3, 4]
operator.getitem(my_list, 1)         # 2
operator.setitem(my_list, 1, 100)    # my_list == [1, 100, 3, 4]   (mutable sequences only)
operator.delitem(my_list, 3)         # my_list == [1, 100, 3]
```

## `itemgetter`: نسخه‌ی partial از `getitem`

`getitem(s, i)` دو آرگومان می‌خواهد و **مقدار** برمی‌گرداند. `itemgetter(i)` فقط اندیس را می‌گیرد و یک **callable** برمی‌گرداند که منتظر دنباله است — درست مثل partial:

```python
from operator import itemgetter

f = itemgetter(2)
f([1, 2, 3, 4])      # 3
f('python')          # 't'
f()                  # TypeError: itemgetter expected 1 argument, got 0
```

چند اندیس → tuple:

```python
f = itemgetter(2, 3)
f([1, 2, 3, 4])      # (3, 4)
f('python')          # ('t', 'h')
itemgetter(1, 3, 4)('python')    # ('y', 'h', 'o')
```

اندیس خارج از محدوده همچنان `IndexError` می‌دهد؛ جادویی در کار نیست.

## `attrgetter`: همان ایده برای attributeها

نام attribute را به‌صورت **رشته** می‌گیرد و callable‌ای برمی‌گرداند که روی هر شیء آن attribute را می‌خواند:

```python
from operator import attrgetter

class MyClass:
    def __init__(self):
        self.a, self.b, self.c = 10, 20, 30
    def test(self):
        print('test method running...')

obj = MyClass()
prop_a = attrgetter('a')
prop_a(obj)                       # 10
attrgetter('a', 'b')(obj)         # (10, 20)
```

چرا به‌جای `obj.a`؟ وقتی نام attribute در یک **متغیر** است: `obj.my_var` دنبال attributeای به نام `my_var` می‌گردد، ولی `attrgetter(my_var)(obj)` کار می‌کند. (مثل partial، مقدار همان لحظه ذخیره می‌شود؛ تغییر بعدی `my_var` روی getter ساخته‌شده اثری ندارد.)

اگر attribute یک متد باشد، attrgetter **خودِ متد bound** را برمی‌گرداند، نه نتیجه‌اش:

```python
f = attrgetter('test')
f(obj)          # <bound method MyClass.test of ...>
f(obj)()        # test method running...
attrgetter('upper')('python')()   # 'PYTHON'  -- convoluted
```

## `methodcaller`: بگیر و همان‌جا صدا بزن

```python
from operator import methodcaller

methodcaller('upper')('python')   # 'PYTHON'
methodcaller('test')(obj)         # test method running...
```

اگر متد آرگومان می‌خواهد، به خود `methodcaller` بده؛ هم positional هم keyword عبور داده می‌شوند:

```python
class MyClass:
    def __init__(self): self.a, self.b = 10, 20
    def test(self, c, d, *, e):
        print(self.a, self.b, c, d, e)

obj.test(100, 200, e=300)                          # 10 20 100 200 300
methodcaller('test', 100, 200, e=300)(obj)         # same
```

با `attrgetter('test')(obj)(100, 200, e=300)` هم می‌شود — فرق در این است که attrgetter صدا زدن را به بعد موکول می‌کند، methodcaller بلافاصله صدا می‌زند.

## کاربرد اصلی: `key`

```python
l = [5-10j, 3+3j, 2-100j]
sorted(l, key=lambda x: x.real)        # [(2-100j), (3+3j), (5-10j)]
sorted(l, key=attrgetter('real'))      # same

l = [(2, 3, 4), (1, 3, 5), (6,), (4, 100)]
sorted(l, key=lambda x: x[0])          # [(1, 3, 5), (2, 3, 4), (4, 100), (6,)]
sorted(l, key=itemgetter(0))           # same
```

همه‌ی اینها با lambda هم می‌شوند؛ `operator` فقط کوتاه‌تر و گویاتر است (و کمی سریع‌تر). مستندات: docs.python.org/3/library/operator.html.
