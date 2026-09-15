## جمع‌زدن دو تا آبجکت

```python
class Point:
    def __init__(self, x, y):
        self.x = x
        self.y = y


p1 = Point(1, 2)
p2 = Point(10, 20)

combined = p1 + p2
```

اجرای این کد خطا می‌ده:
```
TypeError: unsupported operand type(s) for +: 'Point' and 'Point'
```

پایتون نمی‌دونه «جمع‌زدن دو تا `Point`» یعنی چی — باید بهش یاد بدیم.

## متد جادویی `__add__`

```python
class Point:
    def __init__(self, x, y):
        self.x = x
        self.y = y

    def __add__(self, other):
        return Point(self.x + other.x, self.y + other.y)


p1 = Point(1, 2)
p2 = Point(10, 20)

combined = p1 + p2
print(combined.x, combined.y)
# 11 22
```

منطقش ساده‌ست: یه `Point` جدید می‌سازیم که مختصاتش، مجموع مختصات دو تا `Point` ورودیه.

## بقیه‌ی عملگرهای ریاضی

هر عملگر ریاضی، متد جادویی خودش رو داره — `__sub__` برای `-`، `__mul__` برای `*`، و بقیه. الگو همیشه یکیه: دو پارامتر (`self` و `other`)، و یه مقدار برگشتی که نتیجه‌ی اون عملیات رو نشون می‌ده. فهرست کامل این متدها توی مستندات رسمی پایتون هست؛ فقط اونایی رو تعریف کن که واقعاً برای کلاست معنا دارن — لازم نیست همه‌شون رو پیاده کنی.
