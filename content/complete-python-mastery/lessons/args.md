## وقتی تعداد ورودی‌ها از قبل معلوم نیست

```python
def multiply(x, y):
    return x * y


print(multiply(2, 3))
```

این تابع همیشه دقیقاً دو تا عدد می‌گیره. ولی اگه بخوای گاهی ۳ تا عدد ضرب کنی، یا ۴ تا؟ با این تعریف نمی‌شه — `multiply(2, 3, 4)` خطا می‌ده.

## راه‌حل: `*args`

```python
def multiply(*numbers):
    total = 1
    for number in numbers:
        total *= number
    return total


print(multiply(2, 3, 4, 5))
```

خروجی:
```
120
```

به‌جای چند تا پارامتر ثابت، یه پارامتر با `*` جلوش می‌ذاریم — اسمش رو جمع می‌نویسیم (`numbers`) چون یه مجموعه‌ست. حالا می‌تونی هر تعداد آرگومان که بخوای بدی.

## این `numbers` واقعاً چیه؟

```python
def multiply(*numbers):
    print(numbers)


multiply(2, 3, 4, 5)
# (2, 3, 4, 5)
```

داخل تابع، `numbers` یه **تاپل** (tuple) می‌شه — شبیه لیسته (که بعداً کامل می‌بینیش)، با این فرق که تاپل رو نمی‌شه بعد از ساختنش تغییر داد. فرقش با لیست فقط توی نماد نوشتاریه: لیست با `[ ]`، تاپل با `( )`. تاپل هم مثل لیست پیمایش‌پذیره، پس می‌شه روش با `for` تکرار کرد.

## یه اشتباه رایج

```python
def multiply(*numbers):
    total = 1
    for number in numbers:
        total *= number
        return total   # wrong indentation
```

اینجا `return` تورفتگیش با بدنه‌ی حلقه یکیه، پس **توی هر تکرار** اجرا می‌شه — یعنی همون تکرار اول، تابع بلافاصله برمی‌گرده، بدون اینکه بقیه‌ی اعداد رو ضرب کنه. `return` باید هم‌تراز با `for` باشه، نه داخلش:

```python
def multiply(*numbers):
    total = 1
    for number in numbers:
        total *= number
    return total   # correct -- after the loop fully finishes
```
