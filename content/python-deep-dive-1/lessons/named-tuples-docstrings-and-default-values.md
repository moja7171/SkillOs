## docstring خودکار — و قابل تغییر

`namedtuple` برای کلاس و هر فیلد docstring می‌سازد:

```python
Point2D = namedtuple('Point2D', 'x y')
Point2D.__doc__       # 'Point2D(x, y)'
Point2D.x.__doc__     # 'Alias for field number 0'
Point2D.y.__doc__     # 'Alias for field number 1'
```

`__doc__` قابل نوشتن است (برای هر تابع/کلاسی):

```python
Point2D.__doc__ = 'Represents a 2D Cartesian coordinate'
Point2D.x.__doc__ = 'x-coordinate'
Point2D.y.__doc__ = 'y-coordinate'
help(Point2D)         # shows the new strings
```

## مقدار پیش‌فرض: مشکل

`namedtuple` جایی برای پیش‌فرض ندارد. با شش فیلد، مجبوریم همیشه همه را بدهیم:

```python
Vector2D = namedtuple('Vector2D', 'x1 y1 x2 y2 origin_x origin_y')
v1 = Vector2D(0, 0, 10, 10, 0, 0)      # origin is almost always (0, 0) — annoying
```

### راه ۱: prototype (روش مستندات پایتون)

یک instance با مقادیر پیش‌فرض بساز و بقیه را با `_replace` از آن مشتق کن:

```python
vector_zero = Vector2D(x1=0, y1=0, x2=0, y2=0, origin_x=0, origin_y=0)
v1 = vector_zero._replace(x1=10, y1=10, x2=20, y2=20)       # origin carried over: (0, 0)
v2 = vector_zero._replace(x1=1, y1=1, x2=2, y2=2, origin_x=-1, origin_y=-1)
```

کار می‌کند و می‌توان چند prototype داشت (مثلاً `alt_origin`). ایرادها: خواننده نمی‌بیند که `v1` یک `Vector2D` است؛ به‌جای فراخوانی کلاس، `_replace` صدا می‌زنیم؛ و **مجبوریم** keyword argument بدهیم (`_replace` positional نمی‌پذیرد).

### راه ۲: `__defaults__` روی `__new__`

یادآوری از توابع: پیش‌فرض‌ها در `__defaults__` نگه‌داری می‌شوند — یک tuple که از **راست** با پارامترها تراز می‌شود، و **قابل نوشتن** است:

```python
def func(a, b=10, c=20):
    print(a, b, c)

func.__defaults__            # (10, 20)   → aligned right: b=10, c=20
func.__defaults__ = (1, 2, 3)
func()                       # 1 2 3
```

(به همین دلیل نمی‌شود پارامتر بی‌پیش‌فرض بعد از پارامتر با پیش‌فرض داشت: موقعیت در این tuple تعیین می‌کند پیش‌فرض مال کیست. اگر tuple بلندتر از پارامترها باشد، اضافه‌ها نادیده گرفته می‌شوند — ولی این کار را نکن.)

instanceهای named tuple با `__new__(cls, x1, y1, x2, y2, origin_x, origin_y)` ساخته می‌شوند (نه `__init__`؛ `__new__` سازنده است و `__init__` مقداردهی بعد از ساخت). پس پیش‌فرض را همان‌جا بگذار:

```python
Vector2D = namedtuple('Vector2D', 'x1 y1 x2 y2 origin_x origin_y')
Vector2D.__new__.__defaults__ = (0, 0)      # right-aligned → origin_x=0, origin_y=0

v1 = Vector2D(10, 10, 20, 20)               # Vector2D(x1=10, y1=10, x2=20, y2=20, origin_x=0, origin_y=0)
v2 = Vector2D(10, 10, 20, 20, -1, -1)       # still explicit when needed
```

تمیزتر: کلاس همان‌طور که همه انتظار دارند صدا زده می‌شود، positional و keyword هر دو مجاز است، و فقط فیلدهای آخر پیش‌فرض دارند (مثل توابع معمولی).

> از **پایتون 3.7** `namedtuple` پارامتر `defaults=` دارد و همین کار را انجام می‌دهد:
> `Vector2D = namedtuple('Vector2D', 'x1 y1 x2 y2 origin_x origin_y', defaults=(0, 0))` — باز هم راست‌تراز. مقدارها در `Vector2D._field_defaults` دیده می‌شوند. این راه استاندارد امروز است؛ ترفند `__defaults__` همان مکانیزم زیر پوست است.
