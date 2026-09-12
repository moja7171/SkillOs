## تعریف تابع

تابع یک تکه کد نام‌دار است که هر بار خواستی صدایش می‌زنی:

```python
def greet(name):
    return "Hello, " + name

message = greet("Sara")
print(message)   # Hello, Sara
```

سه جزء دارد: کلیدواژه‌ی `def`، **پارامتر**ها داخل پرانتز، و **بدنه** که با تورفتگی مشخص می‌شود.

## return در برابر print

`return` مقدار را به جایی که تابع صدا زده شده برمی‌گرداند. `print` فقط روی صفحه می‌نویسد و چیزی برنمی‌گرداند:

```python
def add(a, b):
    print(a + b)

result = add(2, 3)   # 5 چاپ می‌شود
print(result)        # None
```

اگر تابعی `return` نداشته باشد، مقدار برگشتی‌اش `None` است.

## مقدار پیش‌فرض

```python
def power(base, exponent=2):
    return base ** exponent

power(3)      # 9
power(3, 3)   # 27
```

پارامترهای با مقدار پیش‌فرض باید **بعد از** پارامترهای اجباری بیایند.
