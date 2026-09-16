## یه تکرار مشکوک

فرض کن این دو کلاس رو داری:

```python
class Mammal:
    def eat(self):
        print("eat")

    def walk(self):
        print("walk")


class Fish:
    def eat(self):
        print("eat")

    def swim(self):
        print("swim")
```

هر دو کلاس یه متد `eat` دارن که دقیقاً یه کار می‌کنه. اینجا فقط یه خطه، ولی توی یه پروژه‌ی واقعی ممکنه ۵-۱۰ خط باشه و توی چندین کلاس تکرار بشه. این بده: اگه یه باگ توش باشه یا رفتارش عوض بشه، باید توی همه‌ی کلاس‌ها اصلاحش کنی. به این اصل می‌گن **DRY** (Don't Repeat Yourself).

## راه‌حل: وراثت (Inheritance)

وراثت یعنی رفتار مشترک رو توی یه کلاس تعریف کنی و بقیه‌ی کلاس‌ها اون رفتار رو **به ارث ببرن**:

```python
class Animal:
    def eat(self):
        print("eat")


class Mammal(Animal):
    def walk(self):
        print("walk")


class Fish(Animal):
    def swim(self):
        print("swim")
```

با نوشتن `Animal` داخل پرانتز جلوی اسم کلاس، می‌گیم «Mammal یه Animal هم هست». به `Animal` می‌گیم کلاس **والد** (parent/base class) و به `Mammal` می‌گیم کلاس **فرزند** (child/subclass).

```python
m = Mammal()
m.eat()   # eat  -- inherited from Animal
m.walk()  # walk -- defined on Mammal itself
```

## ویژگی‌ها هم به ارث می‌رسن

وراثت فقط متد نیست؛ ویژگی‌های تعریف‌شده توی `__init__` کلاس والد هم به فرزند می‌رسن:

```python
class Animal:
    def __init__(self):
        self.age = 1

    def eat(self):
        print("eat")


class Mammal(Animal):
    def walk(self):
        print("walk")


m = Mammal()
print(m.age)  # 1
```

با اینکه `Mammal` خودش هیچ `__init__`ی نداره، از `Animal` یکی به ارث برده و `age` رو روش داره.

## چرا مهمه

توی مثال‌های این درس از حیوون‌ها استفاده کردیم چون رابطه‌شون قابل‌فهمه، ولی توی برنامه‌های واقعی معمولاً حیوون یا mammal نمی‌سازیم — بعداً یه مثال واقعی‌تر از وراثت می‌بینیم. نکته‌ی اصلی اینه: وراثت جلوی تکرار کد رو می‌گیره و یه رفتار مشترک رو یه‌جا نگه می‌داره.
