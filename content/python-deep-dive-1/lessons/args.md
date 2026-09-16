> این درس دو ویدیو دارد: بخش نظری و بخش کدنویسی.

## فراخوانی تابع = unpacking

```python
a, b, c = 10, 20, 30            # unpacking

def func1(a, b, c): ...
func1(10, 20, 30)                # same thing: (10, 20, 30) unpacks into (a, b, c)
```

پس تعجبی ندارد که `*` هم اینجا کار کند:

```python
a, b, *c = 10, 20, 'a', 'b'     # c = ['a', 'b']  (list)

def func1(a, b, *c):
    ...
func1(10, 20, 'a', 'b')          # c = ('a', 'b')  (tuple!)
func1(10, 20)                    # c = ()
func1(10, 20, 1, 2, 3)           # c = (1, 2, 3)
```

دو تفاوت با unpacking معمولی: (۱) نتیجه **tuple** است نه list؛ (۲) نام قراردادی‌اش **`args`** است — `*args` — ولی هر نامی می‌شود گذاشت؛ ستاره مهم است، نه نام.

## `*args` آرگومان‌های موقعیتی را «تمام» می‌کند

در unpacking می‌شد بعد از `*b` هم متغیر گذاشت (`a, *b, c`). در تابع نه:

```python
def func1(a, b, *args, d):
    ...
func1(10, 20, 'a', 'b', 100)     # TypeError — 100 goes into args, d stays empty
```

تعریف مجاز است، ولی `d` دیگر نمی‌تواند موقعیتی پر شود — بعد از `*args` چیز دیگری داریم: keyword-only (درس بعد).

## مثال: میانگین با تعداد دلخواه

```python
def avg(*args):
    count = len(args)
    total = sum(args)
    return total / count

avg(2, 2, 4, 4)     # 3.0
avg()               # ZeroDivisionError
```

سه راه برای حالت خالی:

```python
# 1. explicit
    if count == 0:
        return 0
    return total / count

# 2. short-circuit (the bools lesson): count == 0 is falsy and returns itself
    return count and total / count

# 3. make at least one argument required — a better error (TypeError: missing a) instead of divide-by-zero
def avg(a, *args):
    count = len(args) + 1
    total = a + sum(args)
    return total / count
```

## باز کردن iterable در آرگومان‌ها

```python
def func1(a, b, c): print(a, b, c)
l = [10, 20, 30]

func1(l)        # TypeError: you gave one argument, three are needed — the list itself is one object
func1(*l)       # 10 20 30 — unpack first, then pass
func1(*[10, 20, 30, 40])   # TypeError: four of them; three parameters

def func1(a, b, c, *args): ...
func1(*[10, 20, 30, 40, 50])    # args = (40, 50)
```

`*l` در فراخوانی همان unpacking سمت راست است که در درس قبل دیدی.
