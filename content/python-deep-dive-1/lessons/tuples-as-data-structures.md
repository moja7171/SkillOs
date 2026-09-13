> این درس سه ویدیو دارد: معرفی بخش، درس نظری و کدنویسی.

## tuple، list، string: شباهت‌ها و تفاوت‌ها

هر سه **sequence**‌اند: container، ترتیب‌دار، قابل اندیس‌گذاری، قابل پیمایش، قابل برش. تفاوت‌ها:

| | tuple | list | str |
|---|---|---|---|
| mutable | نه | بله | نه |
| طول ثابت | بله | نه | بله |
| ترتیب ثابت | بله (بدون sort/reverse درجا) | نه | بله |
| نوع عناصر | معمولاً **ناهمگن** | معمولاً **همگن** | همیشه کاراکتر |

tuple به string شبیه‌تر است تا به list. دو ویژگی «طول ثابت» و «ترتیب ثابت» چیزی است که از آن استفاده می‌کنیم.

## tuple به‌عنوان رکورد داده

قبلاً این کار را کرده‌ای: مختصات `(10, 20)`. آنچه به این جفت معنی می‌دهد **موقعیت** است: اولی x، دومی y. همین‌طور:

```python
circle = (0, 0, 10)                   # (center x, center y, radius)
london = ('London', 'UK', 8_780_000)  # (city, country, population)
new_york = ('New York', 'USA', 8_500_000)
beijing = ('Beijing', 'China', 21_000_000)
```

این یک ساختار داده‌ی سبک است: به‌جای کلاس با سه attribute، یک قرارداد درباره‌ی معنای هر موقعیت. و چون tuple، رشته و int همه immutable‌اند، رکورد `london` **کاملاً قفل** است — هر جا پاسش بدهی کسی نمی‌تواند تغییرش دهد (فقط می‌توان نام را به tuple دیگری برد).

نکته‌ی نحوی: **کاما** tuple می‌سازد، نه پرانتز؛ `a = 10, 20, 30` هم tuple است. پرانتز وقتی لازم است که ابهام باشد — مثلاً `print_tuple((10, 20, 30))` در برابر سه آرگومان جدا.

### tuple immutable است، محتوایش لزوماً نه

```python
class Point2D:
    def __init__(self, x, y): self.x, self.y = x, y
    def __repr__(self): return f'{self.__class__.__name__}(x={self.x}, y={self.y})'

a = (Point2D(0, 0), Point2D(10, 20))
a[0] = Point2D(1, 1)     # TypeError: tuple does not support item assignment
a[0].x = 100             # fine — the OBJECT inside is mutable; a[0] still references the same object
```

و `a += (4, 5)` tuple را تغییر نمی‌دهد؛ tuple **جدیدی** می‌سازد (`id(a)` عوض می‌شود) — مثل `s += 'rocks'` برای رشته.

## بیرون کشیدن داده

**اندیس:** `city = london[0]`, `population = london[2]`.

**unpacking** (عکسِ packing):

```python
city, country, population = new_york
city, _, population = beijing        # _ is an ordinary name; convention for "don't care"
```

**extended unpacking:**

```python
record = ('DJIA', 2018, 1, 19, 25_987.35, 26_071.72, 25_942.83, 26_071.72)
symbol, year, month, day, *_, close = record      # the middle goes into a list we ignore
```

این از `symbol, year, close = record[0], record[1], record[7]` (که اول tuple می‌سازد و بعد باز می‌کند) بسیار تمیزتر است.

## list از tupleها

```python
cities = [london, new_york, beijing]     # list: homogeneous (all city records); each record: heterogeneous

total = 0
for city in cities:
    total += city[2]

sum(city[2] for city in cities)          # more Pythonic; only safe because every item IS a city record
```

اگر یک `100` بین شهرها باشد، `100[2]` خطا می‌دهد — به همین دلیل لیست‌ها معمولاً همگن‌اند.

### unpacking در حلقه

```python
for city, country, population in cities:
    print(f'{city} ({country}): {population:,}')

for index, city in enumerate(cities):    # enumerate yields (index, item) tuples
    print(index, city)
```

## برگرداندن چند مقدار از تابع

رایج‌ترین کاربرد: تابع یک tuple برمی‌گرداند و موقعیت‌ها معنی دارند.

```python
from random import uniform
from math import sqrt

def random_shot(radius):
    random_x = uniform(-radius, radius)
    random_y = uniform(-radius, radius)
    is_in_circle = sqrt(random_x ** 2 + random_y ** 2) <= radius
    return random_x, random_y, is_in_circle       # packed into a tuple

num_attempts = 1_000_000
count_inside = 0
for _ in range(num_attempts):
    *_, is_in_circle = random_shot(1)           # ignore the coordinates
    if is_in_circle:
        count_inside += 1

print(f'Pi is approximately: {4 * count_inside / num_attempts}')    # ~3.14 (Monte Carlo)
```

مشکل باقی‌مانده: صداکننده باید **بداند** موقعیت‌ها یعنی چه. named tuple (درس بعد) به موقعیت‌ها نام می‌دهد.
