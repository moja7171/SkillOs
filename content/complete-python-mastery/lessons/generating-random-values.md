## عدد تصادفی ساده

ماژول `random` چند تا تابع آماده برای تولید مقادیر تصادفی داره:

```python
import random

print(random.random())  # a random float between 0 and 1
```

## عدد صحیح تصادفی توی یه بازه

```python
print(random.randint(1, 10))  # a random integer between 1 and 10 (both inclusive)
```

## انتخاب تصادفی از یه دنباله

```python
numbers = [1, 2, 3, 4, 5]

print(random.choice(numbers))          # one random item
print(random.choices(numbers, k=2))    # k random items (can repeat)
```

`choice` یه عضو تصادفی از هر دنباله‌ای (لیست، رشته، ...) برمی‌گردونه؛ `choices` همون کار رو می‌کنه ولی با آرگومان کلیدی `k`، چند تا عضو انتخاب می‌کنه.

## کاربرد: ساخت یه رمز عبور تصادفی

می‌شه از `choices` روی یه رشته هم استفاده کرد — چون رشته هم یه دنباله‌ست:

```python
import string

letters = string.ascii_letters + string.digits
password_chars = random.choices(letters, k=8)
password = "".join(password_chars)

print(password)
```

- `string.ascii_letters` رشته‌ای شامل همه‌ی حروف کوچیک و بزرگ انگلیسیه؛ `string.digits` شامل ارقام ۰ تا ۹.
- `random.choices(letters, k=8)` یه **لیست** از ۸ کاراکتر تصادفی برمی‌گردونه، نه یه رشته.
- `"".join(...)` این لیست رو با یه جداکننده‌ی خالی به یه رشته‌ی واحد تبدیل می‌کنه.

اگه به‌جای رشته‌ی خالی از یه جداکننده‌ی دیگه (مثلاً `,`) استفاده کنی، همون جداکننده بین کاراکترها ظاهر می‌شه — که برای رمز عبور نمی‌خوایم.

## به‌هم‌ریختن ترتیب یه لیست

```python
numbers = [1, 2, 3, 4]
random.shuffle(numbers)

print(numbers)  # e.g. [3, 1, 4, 2]
```

نکته: برخلاف `choice`/`choices` که یه مقدار **برمی‌گردونن**، `shuffle` خودِ لیست رو **جای‌به‌جا می‌کنه** (in place) و چیزی برنمی‌گردونه.

## جمع‌بندی

- `random.random()`: عدد اعشاری بین ۰ و ۱.
- `random.randint(a, b)`: عدد صحیح تصادفی توی یه بازه.
- `random.choice(seq)` / `random.choices(seq, k=n)`: یکی یا چند عضو تصادفی از یه دنباله.
- `random.shuffle(list)`: به‌هم‌ریختن ترتیب یه لیست، به‌شکل in place.
