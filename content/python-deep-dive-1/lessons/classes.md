## تعریف کلاس و __init__

```python
class Rectangle:
    def __init__(self, width, height):
        self.width = width
        self.height = height

    def area(self):
        return self.width * self.height

r1 = Rectangle(10, 20)
r1.area()        # 200
```

- `__init__` موقع ساختن شیء (`Rectangle(10, 20)`) صدا زده می‌شود و ویژگی‌ها را روی `self` می‌گذارد.
- `self` همان شیءِ در حال کار است؛ پایتون خودش آن را به‌عنوان آرگومان اول هر متد می‌فرستد.

## متدهای ویژه (dunder)

پایتون برای رفتارهای «داخلی» نام‌های `__xxx__` دارد. با تعریف‌شان، کلاس تو با بقیه‌ی زبان همکاری می‌کند:

```python
class Rectangle:
    ...
    def __repr__(self):
        return f"Rectangle({self.width}, {self.height})"

    def __str__(self):
        return f"Rectangle: width={self.width}, height={self.height}"

    def __eq__(self, other):
        if isinstance(other, Rectangle):
            return self.width == other.width and self.height == other.height
        return False

    def __lt__(self, other):
        if isinstance(other, Rectangle):
            return self.area() < other.area()
        return NotImplemented
```

- `__repr__`: نمایش «برای برنامه‌نویس» (در REPL و لیست‌ها). قرارداد: چیزی که بتوان با آن شیء را دوباره ساخت.
- `__str__`: نمایش «برای کاربر» (`print`, `str()`). اگر نباشد، از `__repr__` استفاده می‌شود.
- `__eq__`: معنی `==` را تعریف می‌کند. بدون آن، `==` فقط وقتی `True` است که هر دو نام به *همان* شیء اشاره کنند.
- `__lt__`: معنی `<`. برگرداندن `NotImplemented` (نه `False`) به پایتون می‌گوید «بلد نیستم، از طرف مقابل بپرس».

## property: کنترل دسترسی بدون تغییر ظاهر

در پایتون ویژگی‌ها را مستقیم می‌خوانی و می‌نویسی (`r1.width = 5`)، نه با getter/setter مثل جاوا. ولی اگر روزی خواستی اعتبارسنجی اضافه کنی، **بدون تغییر رابط** با `@property` این کار را می‌کنی:

```python
class Rectangle:
    def __init__(self, width, height):
        self._width = width          # زیرخط: «به این مستقیم دست نزن»
        self._height = height

    @property
    def width(self):
        return self._width

    @width.setter
    def width(self, value):
        if value <= 0:
            raise ValueError("Width must be positive.")
        self._width = value
```

کد استفاده‌کننده همچنان `r1.width` و `r1.width = 5` می‌نویسد؛ فقط حالا `r1.width = -1` خطا می‌دهد. برای همین در پایتون از اول getter/setter نمی‌نویسند: هر وقت لازم شد، property اضافه می‌شود.
