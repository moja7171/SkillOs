## چند نوع استثنا از یه بلوک

بریم مثال قبلی رو یه‌قدم پیچیده‌تر کنیم — این‌بار یه محاسبه هم اضافه می‌کنیم:

```python
try:
    age = int(input("Age: "))
    x_factor = 10 / age
    print(x_factor)
except ValueError:
    print("You didn't enter a valid age")
```

اگه کاربر `0` وارد کنه، چی می‌شه؟ `10 / 0` یه `ZeroDivisionError` صادر می‌کنه — ولی توی `except` فقط `ValueError` رو مدیریت کردیم. چون هیچ بلوکی برای `ZeroDivisionError` نداریم، برنامه بازم کرش می‌کنه.

## اضافه‌کردن یه `except` دیگه

```python
try:
    age = int(input("Age: "))
    x_factor = 10 / age
    print(x_factor)
except ValueError:
    print("You didn't enter a valid age")
except ZeroDivisionError:
    print("Age cannot be 0")
```

پایتون بلوک‌های `except` رو به ترتیب چک می‌کنه و فقط اونی که با نوع استثنای صادرشده مطابقت داره اجرا می‌شه — بقیه نادیده گرفته می‌شن.

## وقتی چند نوع استثنا، رفتار یکسان می‌خوان

فرض کن می‌خوای برای هر دوی این استثناها همون پیام رو نشون بدی. کپی‌کردن همون پیام توی دوتا بلوک جدا، تکراریه — اگه بعداً بخوای پیام رو عوض کنی، باید دو جا عوضش کنی. راه بهتر: چند نوع استثنا رو توی یه پرانتز، جدا با کاما، به یه `except` بدی:

```python
try:
    age = int(input("Age: "))
    x_factor = 10 / age
    print(x_factor)
except (ValueError, ZeroDivisionError):
    print("You entered an invalid age")
```

الان هر دو نوع استثنا — چه `ValueError` چه `ZeroDivisionError` — همین یه بلوک رو صدا می‌زنن.

## جمع‌بندی

- برای رفتار **متفاوت** بر اساس نوع خطا: چند بلوک `except` جدا بنویس.
- برای رفتار **یکسان** روی چند نوع خطا: یه `except` با یه تاپل از انواع خطا.
