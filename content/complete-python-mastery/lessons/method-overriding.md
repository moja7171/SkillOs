## یه سازنده‌ی جدید توی کلاس فرزند

کلاس `Animal` یه سازنده داره که `age` رو مقداردهی می‌کنه:

```python
class Animal:
    def __init__(self):
        self.age = 1

    def eat(self):
        print("eat")


class Mammal(Animal):
    pass
```

حالا می‌خوایم به `Mammal` هم یه سازنده اضافه کنیم که `weight` رو مقداردهی کنه:

```python
class Mammal(Animal):
    def __init__(self):
        self.weight = 2
```

```python
m = Mammal()
print(m.age)     # AttributeError!
```

خطا می‌گیریم: `'Mammal' object has no attribute 'age'`. چرا؟ چون سازنده‌ای که توی `Mammal` نوشتیم، سازنده‌ی `Animal` رو **جایگزین** کرد — دیگه اصلاً اجرا نشد. به این می‌گیم **بازنویسی متد** (method overriding): وقتی یه کلاس فرزند یه متد هم‌نام با کلاس والدش تعریف می‌کنه، نسخه‌ی فرزند اجرا می‌شه، نه والد.

## اجرای هر دو با `super()`

اگه می‌خوایم سازنده‌ی `Animal` هم اجرا بشه، باید صریح صداش بزنیم. تابع آماده‌ی `super()` بهمون دسترسی به کلاس والد می‌ده:

```python
class Mammal(Animal):
    def __init__(self):
        super().__init__()
        self.weight = 2
```

```python
m = Mammal()
print(m.age)     # 1
print(m.weight)  # 2
```

حالا هر دو سازنده اجرا می‌شن: اول `Animal.__init__` (که `age` رو ست می‌کنه)، بعد بقیه‌ی بدنه‌ی `Mammal.__init__` (که `weight` رو ست می‌کنه).

## ترتیب اجرا مهمه

می‌تونیم `super().__init__()` رو هر جای بدنه‌ی سازنده بذاریم — نه لزوماً اول:

```python
class Mammal(Animal):
    def __init__(self):
        self.weight = 2
        super().__init__()
```

اینجا اول `weight` ست می‌شه، بعد `age`. ترتیب دقیقاً همون ترتیبیه که خط‌ها نوشته شدن.

## بازنویسی یعنی جایگزینی یا گسترش

اگه توی سازنده‌ی `Mammal` اصلاً `super().__init__()` رو صدا نزنیم، سازنده‌ی `Animal` کاملاً **جایگزین** می‌شه و هیچ‌وقت اجرا نمی‌شه. اگه صداش بزنیم، داریم رفتار کلاس والد رو **گسترش** می‌دیم (کاری که خودش می‌کنه، به‌علاوه‌ی کار اضافه‌ی خودمون).
