## یه باگ ساده: قیمت منفی

```python
class Product:
    def __init__(self, price):
        self.price = price


product = Product(10)
product.price = -100
print(product.price)
# -100
```

هیچی جلوی مقداردهی یه قیمت منفی رو نمی‌گیره. می‌خوایم وقتی کسی سعی می‌کنه قیمت منفی بذاره، خطا بگیره.

## راه‌حل غیرپایتونی: متدهای `get` و `set`

اولین فکری که به ذهن می‌رسه، مخفی‌کردن `price` (با یه زیرخط) و ساختن دو متد برای خوندن و نوشتنشه:

```python
class Product:
    def __init__(self, price):
        self.set_price(price)

    def get_price(self):
        return self._price

    def set_price(self, value):
        if value < 0:
            raise ValueError("Price cannot be negative")
        self._price = value
```

```python
product = Product(10)
product.set_price(-100)
# ValueError: Price cannot be negative
```

کار می‌کنه، ولی دیگه نمی‌شه مثل قبل ساده نوشت `product.price`؛ باید همه‌جا `product.get_price()` و `product.set_price(...)` صدا بزنی. این سبک بیشتر توی زبان‌هایی مثل جاوا رایجه، نه پایتون.

## راه‌حل پایتونی: `property`

پایتون یه تابع آماده به اسم `property()` داره که یه متد `get` و یه متد `set` رو به یه **ویژگی معمولی** تبدیل می‌کنه:

```python
class Product:
    def __init__(self, price):
        self.set_price(price)

    def get_price(self):
        return self._price

    def set_price(self, value):
        if value < 0:
            raise ValueError("Price cannot be negative")
        self._price = value

    price = property(fget=get_price, fset=set_price)
```

حالا می‌شه دوباره مثل یه ویژگی معمولی باهاش کار کرد، ولی پشت‌صحنه همون متدهای `get_price`/`set_price` صدا زده می‌شن:

```python
product = Product(10)
print(product.price)   # calls get_price() -> 10
product.price = -100   # calls set_price(-100) -> ValueError
```

## سبک رایج‌تر: دکوراتور `@property`

همین ایده رو معمولاً با دکوراتور `@property` می‌نویسن که تمیزتره:

```python
class Product:
    def __init__(self, price):
        self.price = price

    @property
    def price(self):
        return self._price

    @price.setter
    def price(self, value):
        if value < 0:
            raise ValueError("Price cannot be negative")
        self._price = value
```

نکته‌ی مهم: هر دو متد اسمشون `price`ه. اولی با `@property` تزئین شده (نقش `get` رو داره)، دومی با `@price.setter` (نقش `set` رو داره — و اسم دکوراتور دقیقاً از اسم متد اولی می‌آد).

```python
product = Product(10)
print(product.price)   # 10
product.price = -100   # ValueError: Price cannot be negative
```

## ویژگی فقط-خواندنی

اگه فقط `@property` رو تعریف کنی و `@price.setter` رو ننویسی، ویژگی **فقط-خواندنی** می‌شه — هر تلاشی برای مقداردهیش خطا می‌ده:

```python
class Product:
    @property
    def price(self):
        return 100


product = Product()
product.price = 200
# AttributeError: property 'price' of 'Product' object has no setter
```

## جمع‌بندی

`property` این امکان رو می‌ده که یه ویژگی رو از بیرون **مثل یه ویژگی معمولی** استفاده کنی، ولی پشت‌صحنه هر بار خوندن یا نوشتنش، یه متد اجرا بشه — جای مناسبی برای اعتبارسنجی یا محاسبه‌ی مقدار.
