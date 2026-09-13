## monkey patching

پایتون پویاست: در زمان اجرا می‌توان به کلاس‌ها و اشیاء attribute اضافه یا عوض کرد — نه به built-inهای نوشته‌شده با C، ولی به هر چیزی که با پایتون نوشته شده، از جمله کتابخانه‌ی استاندارد:

```python
from fractions import Fraction

Fraction.speak = 100                      # a plain attribute
Fraction(2, 3).speak                      # 100

Fraction.speak = lambda self, message: f'Fraction says: {message}'   # an instance method
f = Fraction(2, 3)
f.speak('This is a late parrot.')         # 'Fraction says: This is a late parrot.'

Fraction.is_integral = lambda self: self.denominator == 1
Fraction(64, 8).is_integral()             # True
Fraction(2, 3).is_integral()              # False
```

به این کار **monkey patching** می‌گویند. حالا همین را در قالب یک تابع که کلاس را می‌گیرد:

```python
def dec_speak(cls):
    cls.speak = lambda self, message: f'{self.__class__.__name__} says: {message}'
    return cls                            # not needed for the mutation — needed for the @ syntax

Fraction = dec_speak(Fraction)

class Person: pass
Person = dec_speak(Person)
Person().speak('this works!')             # 'Person says: this works!'
```

آشناست؟ این یک **decorator برای کلاس** است. `@dec_speak` بالای `class Person:` دقیقاً همین را می‌کند. اگر `cls` را برنگردانی، با نحو `@` نام کلاس به `None` bind می‌شود و `Person()` خطای `'NoneType' object is not callable` می‌دهد.

## کاربرد ۱: اطلاعات debug

```python
from datetime import datetime, timezone

def info(self):                           # will become an instance method; no free variables → not a closure
    results = []
    results.append(f'time: {datetime.now(timezone.utc)}')
    results.append(f'class: {self.__class__.__name__}')
    results.append(f'id: {hex(id(self))}')
    for k, v in vars(self).items():
        results.append(f'{k}: {v}')
    return results

def debug_info(cls):
    cls.debug = info
    return cls

@debug_info
class Person:
    def __init__(self, name, birth_year):
        self.name = name
        self.birth_year = birth_year
    def say_hi(self):
        return 'Hello there!'

p = Person('John', 1939)
p.debug()
# ['time: 2017-...', 'class: Person', 'id: 0x...', 'name: John', 'birth_year: 1939']
```

`info` بیرون از decorator تعریف شده تا هر بار decorate کردن دوباره ساخته نشود. decorator قابل استفاده‌ی مجدد است — روی هر کلاسی:

```python
@debug_info
class Automobile:
    def __init__(self, make, model, year, top_speed):
        self.make, self.model, self.year, self.top_speed = make, model, year, top_speed
        self._speed = 0

    @property
    def speed(self):
        return self._speed

    @speed.setter
    def speed(self, new_speed):
        if new_speed > self.top_speed:
            raise ValueError('Speed cannot exceed top_speed.')
        self._speed = new_speed

favorite = Automobile('Ford', 'Model T', 1908, 45)
favorite.speed = 100        # ValueError
favorite.speed = 40
favorite.debug()            # [..., 'make: Ford', ..., '_speed: 40']
```

(`@property` هم یک decorator است که متد را به property تبدیل می‌کند؛ `_speed` قرارداد «خصوصی» است.)

## کاربرد ۲: تکمیل عملگرهای مقایسه

```python
from math import sqrt

class Point:
    def __init__(self, x, y):
        self.x = x
        self.y = y
    def __abs__(self):
        return sqrt(self.x ** 2 + self.y ** 2)
    def __repr__(self):
        return f'Point({self.x}, {self.y})'
    def __eq__(self, other):
        if isinstance(other, Point):
            return self.x == other.x and self.y == other.y
        return False
    def __lt__(self, other):
        if isinstance(other, Point):
            return abs(self) < abs(other)
        return NotImplemented

p1, p2, p3 = Point(2, 3), Point(2, 3), Point(0, 0)
p1 is p2      # False
p1 == p2      # True   (without __eq__, == falls back to identity)
p3 < p1       # True
p1 > p3       # True   — Python reflects: tries p3.__lt__(p1)
p1 <= p3      # TypeError: '<=' not supported
```

با `==` و `<` می‌شود بقیه را ساخت: `a <= b` = `a < b or a == b`؛ `a > b` = `not (a < b) and a != b`؛ `a >= b` = `not (a < b)`. پس یک decorator که کلاس را «کامل» کند:

```python
def complete_ordering(cls):
    if '__eq__' in dir(cls) and '__lt__' in dir(cls):
        cls.__le__ = lambda self, other: self < other or self == other
        cls.__gt__ = lambda self, other: not (self < other) and not (self == other)
        cls.__ge__ = lambda self, other: not (self < other)
    return cls

@complete_ordering
class Point: ...

p1 <= p4, p4 >= p2, p1 != p2      # all work now
```

(ساده‌شده: بدون بررسی نوع و `NotImplemented`؛ و فقط وقتی `__lt__` داری.)

### `functools.total_ordering`

نسخه‌ی کامل در کتابخانه‌ی استاندارد: با `__eq__` و **هر یکی** از `__lt__`/`__le__`/`__gt__`/`__ge__`، بقیه را می‌سازد:

```python
from functools import total_ordering

@total_ordering
class Point:
    ...
    def __eq__(self, other): ...
    def __gt__(self, other): ...        # any one of the four is enough

p1 <= p2, p2 < p3, p1 >= p4            # all derived
```

`total_ordering` با پایتون نوشته شده — می‌توانی در PyCharm/VS Code روی آن «Go to declaration» بزنی و ببینی دقیقاً همین monkey patching با `setattr` است. خیلی از کتابخانه‌ی استاندارد (مثل `Fraction`) همین‌طور قابل خواندن است.
