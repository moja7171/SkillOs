## break و continue

- `break`: از حلقه بیرون می‌رود.
- `continue`: بقیه‌ی بدنه‌ی این دور را رد می‌کند و به دور بعد می‌رود.

```python
i = 0
while i < 10:
    i += 1
    if i % 2 == 0:
        continue      # skip the even ones
    if i > 7:
        break         # stop after 7
    print(i)          # 1 3 5 7
```

هر دو فقط روی **درونی‌ترین** حلقه‌ای که در آن هستند اثر می‌گذارند.

## try / except / finally

```python
a = 10
b = 0
try:
    a / b
except ZeroDivisionError:
    print("division by 0")
finally:
    print("this always executes")
```

- `except` فقط وقتی اجرا می‌شود که استثنایی از نوع گفته‌شده رخ دهد.
- `finally` **همیشه** اجرا می‌شود — چه استثنا رخ بدهد چه نه، چه `return` شود چه نه.

## تعامل با حلقه: finally حتی با continue و break

نکته‌ای که مدرس رویش تأکید می‌کند: اگر داخل `try` یک `continue` یا `break` باشد، **`finally` باز هم قبل از پرش اجرا می‌شود**:

```python
a = 0
b = 2
while a < 4:
    print("-" * 10)
    a += 1
    b -= 1
    try:
        res = a / b
    except ZeroDivisionError:
        print(f"{a}, {b} - division by 0")
        res = 0
        continue          # ← jump to the next iteration
    finally:
        print(f"{a}, {b} - always executes")   # ← but this runs first
    print(f"{a}, {b} - main loop")
```

خروجی برای دوری که `b == 0` است: پیام «division by 0»، بعد «always executes»، و *بدون* «main loop». همین رفتار برای `break` هم برقرار است: `finally` اجرا می‌شود، بعد حلقه می‌شکند، و `else` حلقه (اگر داشت) اجرا نمی‌شود.

این ضمانت همان چیزی است که `finally` را برای «تمیزکاری» (بستن فایل، آزاد کردن قفل) قابل اعتماد می‌کند.
