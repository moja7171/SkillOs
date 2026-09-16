## «همه‌چیز شیء است»

در این دوره با انواع زیادی سروکار داریم: `int`, `bool`, `float`, `str`, `list`, `tuple`, `set`, `dict`, `NoneType`، عملگرها (`+`, `==`, `is`, حتی `...`)، توابع، کلاس‌ها و خود انواع. همه‌ی این‌ها یک چیز مشترک دارند: **همه شیء هستند**، یعنی همه **instance یک کلاس**‌اند.

- یک تابع، instance کلاس `function` است.
- کلاسی که خودت تعریف می‌کنی، خودش یک شیء است — instance کلاس `type`.
- `int` که `type(10)` برمی‌گرداند، یک کلاس است.

نتیجه‌ی مهم: همه‌ی این‌ها **آدرس حافظه** دارند — از جمله توابع.

## int یک کلاس است

```python
>>> a = 10
>>> type(a)
<class 'int'>
>>> b = int(10)          # creating an instance by calling the class
>>> b, type(b)
(10, <class 'int'>)
>>> int()                # the default value
0
>>> int("101", base=2)   # from a string in base 2
5
>>> help(int)            # the class's built-in docs
```

## توابع شیء هستند

```python
def square(a):
    return a ** 2

type(square)      # <class 'function'>
f = square        # assigning the function to another name — no parentheses!
id(f) == id(square), f is square    # True
square(2), f(2)   # (4, 4)
```

`square` بدون پرانتز **خود شیء تابع** است؛ `square(2)` **فراخوانی** آن است. وقتی تابعی را جابه‌جا می‌کنیم، پرانتز نمی‌گذاریم.

## پس تابع می‌تواند برگردانده شود…

```python
def cube(a):
    return a ** 3

def select_function(fn_id):
    if fn_id == 1:
        return square
    else:
        return cube

f = select_function(1)
f is square       # True
f(2)              # 4

f = select_function(2)
f is cube         # True
f(2)              # 8

select_function(2)(3)     # 27 — first gets the function, then calls it
```

## …و به تابع پاس داده شود

```python
def exec_function(fn, n):
    return fn(n)

exec_function(cube, 3)     # 27
exec_function(square, 3)   # 9
```

`fn` یک ارجاع مشترک به همان تابع است و داخل بدنه با `fn(n)` صدایش می‌زنیم.

این یعنی توابع در پایتون **first-class citizen** هستند: هر کاری با یک int می‌شود کرد — انتساب، پاس دادن، برگرداندن — با تابع هم می‌شود. کل بخش «توابع درجه‌یک» و بعدها decoratorها روی همین بنا شده‌اند.
