## «تغییر» یعنی ساختن نمونه‌ی جدید

named tuple immutable است؛ «تغییر» آن مثل رشته است: از نمونه‌ی موجود، نمونه‌ی **جدیدی** می‌سازیم و معمولاً به همان نام نسبت می‌دهیم (`id` عوض می‌شود).

```python
Point2D = namedtuple('Point2D', 'x y')
pt = Point2D(0, 0)
pt.x = 100                    # AttributeError
pt = Point2D(100, pt.y)       # a NEW tuple bound to the same name
```

برای دو فیلد قابل تحمل است. برای رکورد بزرگ نه:

```python
Stock = namedtuple('Stock', 'symbol year month day open high low close')
djia = Stock('DJIA', 2018, 1, 25, 26_313, 26_458, 26_260, 26_393)

djia = Stock(djia.symbol, djia.year, djia.month, djia.day,
             djia.open, djia.high, djia.low, 26_394)      # painful, error-prone
```

### با unpacking یا slicing

```python
*current, _ = djia                 # current: a LIST of the first 7 values
current = djia[:7]                 # current: a TUPLE (slicing keeps the type)

djia = Stock(*current, 26_394)     # unpack into 8 positional args
```

یا با `_make` (class method) که یک **iterable** با همه‌ی مقدارها می‌گیرد:

```python
djia = Stock._make(current + (26_394,))     # tuple concatenation → new tuple with 8 values
```

اما برای فیلد میانی (مثلاً `day` در اندیس ۳) unpacking جواب نمی‌دهد — فقط یک `*` مجاز است. با slice می‌شود، ولی زشت:

```python
new_values = djia[:3] + (26,) + djia[4:]
new_values = djia[:3] + (26,) + djia[4:5] + (26_459,) + djia[6:]   # day AND high — unreadable
```

## راه درست: `_replace`

instance method؛ کپی می‌سازد و فیلدهای داده‌شده را با **keyword** جایگزین می‌کند:

```python
djia = djia._replace(day=26, high=26_459, close=26_394)
djia._replace(close=10_000)          # a new tuple; djia unchanged unless you rebind
djia._replace(clos=1)                # ValueError: unexpected field name
```

## گسترش کلاس: فیلد جدید

می‌خواهیم `StockExt` = `Stock` + `previous_close`. تایپ دوباره‌ی همه‌ی فیلدها خطاخیز است؛ از `_fields` استفاده کن:

```python
Point3D = namedtuple('Point3D', Point2D._fields + ('z',))
StockExt = namedtuple('StockExt', Stock._fields + ('previous_close',))
StockExt._fields      # ('symbol', ..., 'close', 'previous_close')
```

(این وراثت نیست — یک کلاس جدید است که فهرست فیلدهایش از قبلی ساخته شده.)

## گسترش مقدارها

از instance قبلی، instance کلاس گسترش‌یافته بساز — named tuple یک tuple است، پس unpack می‌شود:

```python
pt3d = Point3D(*pt, 100)                     # Point3D(x=100, y=20, z=100)
djia_ext = StockExt(*djia, 26_000)           # all 8 values + previous_close
djia_ext = StockExt._make(djia + (26_000,))  # same, via an iterable
```

## نکته‌ی جانبی: list در برابر tuple هنگام ساختن

- `values + [x]` یا `t + (x,)` **شیء جدید** می‌سازد.
- `values.append(x)` / `values.extend([...])` لیست موجود را تغییر می‌دهد (`id` ثابت) — کارآمدتر وقتی لیست داری.

| کار | ابزار |
|---|---|
| تغییر یکی‌دو فیلد | `nt._replace(field=...)` |
| ساخت از iterable آماده | `NT._make(iterable)` |
| ساخت از مقادیر جدا | `NT(*values, extra)` |
| کلاس با فیلد اضافه | `namedtuple('New', NT._fields + ('f',))` |
