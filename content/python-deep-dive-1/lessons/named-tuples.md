## مشکل tuple به‌عنوان رکورد

```python
pt = (10, 20)
distance = sqrt(pt[0] ** 2 + pt[1] ** 2)      # reader must know 0 = x, 1 = y
```

با کلاس خواناتر می‌شود (`pt.x`, `pt.y`)، ولی برای یک «کیسه‌ی داده» باید `__init__`، `__repr__`، `__eq__` و … بنویسی، instanceها mutable می‌شوند، و iterable/unpackable نیستند (`max(pt)` خطا می‌دهد). از طرف دیگر tuple ساده شکننده است: اگر فیلدی به ابتدای رکورد اضافه کنی، هر `last_name, age = eric` در کد می‌شکند.

**named tuple** هر دو را ترکیب می‌کند: یک tuple که موقعیت‌هایش **نام** دارند.

## `namedtuple` یک class factory است

```python
from collections import namedtuple

Point2D = namedtuple('Point2D', ['x', 'y'])
```

`namedtuple` تابعی است که یک **کلاس** می‌سازد و برمی‌گرداند؛ کلاس تولیدشده از `tuple` ارث می‌برد و برای هر موقعیت یک property با نام مربوط دارد. دو آرگومان: نام کلاس (رشته) و نام فیلدها **به همان ترتیب موقعیت‌ها**.

نام متغیر سمت چپ با نام کلاس فرقی ندارد و می‌تواند متفاوت باشد (`Pt2D = namedtuple('Point2D', ...)`) — دقیقاً مثل `MyClassAlias = MyClass`؛ `repr` همیشه نام کلاس را نشان می‌دهد. برای گیج نشدن، همیشه یکی بگذار.

### شکل‌های مختلف فهرست فیلدها

```python
namedtuple('Point2D', ['x', 'y'])
namedtuple('Point2D', ('x', 'y'))
namedtuple('Point2D', 'x, y')
namedtuple('Point2D', 'x y')
Stock = namedtuple('Stock', '''symbol
                               year month day
                               open high low close''')
```

فیلدها باید identifier معتبر باشند و **با underscore شروع نشوند** (محدودیت namedtuple، نه کلاس‌ها):

```python
Person = namedtuple('Person', 'name age _ssn')                 # ValueError
Person = namedtuple('Person', 'name age _ssn', rename=True)    # invalid names become _<index>
Person._fields                                                 # ('name', 'age', '_2')
```

## ساختن و خواندن

```python
pt = Point2D(10, 20)
pt = Point2D(x=10, y=20)          # keyword args work: the generated __new__ uses the field names
pt                                # Point2D(x=10, y=20)   ← __repr__ for free
pt.x, pt.y                        # 10, 20
pt[0], pt[0:1]                    # 10, (10,)              ← still a tuple
x, y = pt                         # unpacking
for e in pt: ...                  # iterable
isinstance(pt, tuple)             # True
Point2D(10, 20) == Point2D(10, 20)    # True   ← __eq__ for free (tuple equality)
max(pt)                           # 20
```

چون tuple است، همه‌ی توابعِ روی دنباله‌ها کار می‌کنند. مثلاً ضرب داخلی که به تعداد بُعد وابسته نیست:

```python
def dot_product(a, b):
    return sum(e[0] * e[1] for e in zip(a, b))

Vector3D = namedtuple('Vector3D', 'x y z')
dot_product(Point2D(1, 2), Point2D(1, 1))          # 3
dot_product(Vector3D(1, 2, 3), Vector3D(1, 1, 1))  # 6
```

با کلاس معمولی مجبور بودی `a.x * b.x + a.y * b.y + ...` را برای هر بُعد جدا بنویسی.

### immutable

```python
pt.x = 100        # AttributeError: can't set attribute
```

مثل رشته: تغییر یعنی ساختن نمونه‌ی جدید (درس بعد).

## درون‌نگری

```python
Point2D._fields          # ('x', 'y')         — on the class
pt._asdict()             # {'x': 10, 'y': 20} — on the instance (OrderedDict before 3.8, dict since)
```

`Point2D._source` (تا پایتون 3.6) کد کلاس تولیدشده را نشان می‌داد: `class Point2D(tuple)` با `__new__(_cls, x, y)`، `__repr__`، و property برای هر فیلد که `itemgetter(i)` است. از 3.7 حذف شده، ولی همین ساختار است.

## سربار؟

تقریباً هیچ. نام فیلدها در **کلاس** است، نه در هر instance؛ هر instance فقط یک tuple است. دسترسی با `.x` از property می‌گذرد — همان هزینه‌ای که هر کلاسی دارد.

> از پایتون 3.6 `typing.NamedTuple` هم هست: `class Point2D(NamedTuple): x: int; y: int` — همان چیز با نحو کلاس و type hint.
