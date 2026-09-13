## متغیر آزاد و closure

درس قبل: تابع درونی می‌تواند متغیر تابع بیرونی را ببیند. آن متغیر (نه local، نه global) از دید تابع درونی یک **free variable** است:

```python
def outer():
    x = 'python'
    def inner():
        print(f'{x} rocks!')     # x is a free variable of inner
    return inner                 # return the function — do not call it

fn = outer()
fn()                             # python rocks!
```

`inner` به‌تنهایی معنی ندارد؛ `inner` **به‌همراه متغیرهای آزادش** یک واحد است. به این واحد **closure** می‌گویند: تابع + scope گسترش‌یافته‌ای که متغیرهای آزادش را نگه می‌دارد. وقتی `outer` تابع را برمی‌گرداند، closure را برمی‌گرداند، نه تابع خالی.

اما چطور `fn()` بعد از پایان `outer` هنوز `x` را می‌داند؟ scope تابع `outer` که با return از بین رفت!

## cell: راز کار

وقتی پایتون می‌بیند یک نام بین دو scope **مشترک** است (outer و inner)، به‌جای اشاره‌ی مستقیم، یک شیء واسط به نام **cell** می‌سازد:

```
outer.x ──┐
          ├──► cell (0xA500) ──► 'python' (0xFF100)
inner.x ──┘
```

هر دو `x` به cell اشاره می‌کنند و cell به شیء واقعی. خواندن مقدار یک «double hop» است که پایتون خودش انجام می‌دهد (حتی `id(x)` آدرس نهایی را می‌دهد، نه cell را). وقتی `outer` تمام می‌شود، `x`ِ آن می‌رود، ولی cell هنوز از closure ارجاع دارد و زنده می‌ماند. تغییر مقدار (با `nonlocal`) یعنی cell به شیء دیگری اشاره کند.

دو لحظه‌ی مهم: closure **هنگام ساخت تابع درونی** (اجرای `def` داخل `outer`) شکل می‌گیرد؛ ولی مقدار متغیر آزاد **هنگام فراخوانی** خوانده می‌شود.

### درون‌نگری

```python
fn.__code__.co_freevars    # ('x',)
fn.__closure__             # (<cell at 0xA500: str object at 0xFF100>,)
```

متغیرهای local خود `inner` (مثلاً `a = 10` داخلش) جزو closure نیستند.

## تغییر متغیر آزاد

```python
def counter():
    count = 0
    def inc():
        nonlocal count
        count += 1
        return count
    return inc

fn = counter()
fn()     # 1
fn()     # 2
```

`nonlocal` همان چیزی است که درس قبل دیدیم؛ اینجا فقط اثرش بعد از پایان `counter` هم می‌ماند، چون cell می‌ماند.

## هر فراخوانی، یک closure تازه

هر بار `counter()` صدا زده شود، scope جدید و **cell جدید** ساخته می‌شود:

```python
f1 = counter()
f2 = counter()
f1(); f1(); f1()     # 1, 2, 3
f2()                 # 1   -- its own cell
f1.__closure__ == f2.__closure__   # different cell addresses
```

همین‌طور `square = power(2)` و `cube = power(3)` دو closure مستقل با دو cell جدا برای `n` هستند.

## scope مشترک بین چند closure

اگر دو تابع درونی در **یک** فراخوانی از همان متغیر استفاده کنند، هر دو به یک cell وصل‌اند:

```python
def outer():
    count = 0
    def inc1():
        nonlocal count
        count += 1
        return count
    def inc2():
        nonlocal count
        count += 1
        return count
    return inc1, inc2

f1, f2 = outer()
f1()     # 1
f2()     # 2   -- same cell
```

## دام کلاسیک: closure در حلقه

```python
def adder(n):
    def inner(x):
        return x + n
    return inner

add_1, add_2, add_3 = adder(1), adder(2), adder(3)   # three closures, three cells
add_1(10), add_2(10), add_3(10)                        # 11, 12, 13
```

حالا با حلقه:

```python
def create_adders():
    adders = []
    for n in range(1, 4):
        adders.append(lambda x: x + n)     # free variable n — the SAME n every iteration
    return adders

adders = create_adders()
adders[0](10)    # 13  (!)
adders[1](10)    # 13
adders[2](10)    # 13
```

هر سه lambda به **همان** cell برای `n` وصل‌اند؛ حلقه `n` را تا ۳ جلو برد؛ و `n` تازه هنگام **فراخوانی** خوانده می‌شود. (اگر همین حلقه را مستقیم در ماژول بنویسی، `n` global است و closure‌ای در کار نیست، ولی مشکل همان است.)

> lambda ≠ closure. lambda فقط تابع می‌سازد؛ closure می‌شود اگر متغیر آزاد داشته باشد. اینجا lambda closure است؛ در راه‌حل زیر نیست.

**راه‌حل:** مقدار را همان لحظه‌ی ساخت capture کن — با پیش‌فرض، که در زمان تعریف ارزیابی می‌شود:

```python
for n in range(1, 4):
    adders.append(lambda x, y=n: x + y)   # y's default is evaluated NOW; no free variable, no closure

adders[0](10)    # 11
adders[0].__closure__     # None
```

## closure تودرتو

```python
def incrementer(n):
    def inner(start):
        current = start
        def inc():
            nonlocal current
            current += n          # current: inner's; n: incrementer's
            return current
        return inc
    return inner

fn = incrementer(2)
fn.__code__.co_freevars           # ('n',)
inc_2 = fn(100)
inc_2.__code__.co_freevars        # ('current', 'n')
inc_2()    # 102
inc_2()    # 104
```

توجه: `n` جزو متغیرهای آزاد `inner` هم هست، با اینکه مستقیم در بدنه‌ی `inner` استفاده نشده — چون تابع تودرتوی داخلش به آن نیاز دارد. closureهای تودرتو در decoratorها زیاد می‌آیند.
