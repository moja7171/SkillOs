## کلاس `datetime`: تاریخ و زمان با ویژگی‌های خوانا

برخلاف تایم‌استمپ (یه عدد ساده)، کلاس `datetime` (از ماژول همنامش) یه آبجکت با ویژگی‌های خوانا مثل سال، ماه و روز می‌ده.

```python
from datetime import datetime
```

نکته: بهتره کلاس `datetime` رو مستقیم import کنی (نه فقط ماژول `datetime` رو)، وگرنه مجبوری هرجا بخوای ازش استفاده کنی بنویسی `datetime.datetime(...)` که زشت و تکراریه.

## ساخت یه آبجکت `datetime`

**۱. با مقادیر مشخص (سال، ماه، روز، و اختیاری ساعت/دقیقه/ثانیه):**

```python
dt = datetime(2018, 1, 1)
```

**۲. زمان فعلی:**

```python
dt = datetime.now()
```

**۳. تبدیل یه رشته به `datetime`:**

خیلی وقت‌ها تاریخ رو از ورودی کاربر یا یه فایل به‌شکل رشته می‌گیریم. `strptime` (str-parse-time) این رشته رو با توجه به یه فرمت مشخص، به `datetime` تبدیل می‌کنه:

```python
dt = datetime.strptime("2018/01/01", "%Y/%m/%d")
```

آرگومان دوم یه رشته‌ی فرمته که با **directive**های خاص (مثل `%Y` برای سال چهار رقمی، `%m` برای ماه دو رقمی، `%d` برای روز دو رقمی) مشخص می‌کنه هر بخش از رشته چی نشون می‌ده. لیست کامل directive‌ها توی مستندات رسمی پایتون (جست‌وجوی «python3 strptime») موجوده — نیازی به حفظ‌کردن نیست.

**۴. تبدیل یه تایم‌استمپ به `datetime`:**

```python
from time import time

ts = time()
dt = datetime.fromtimestamp(ts)
```

## دسترسی به اجزای تاریخ

```python
print(dt.year, dt.month, dt.day)
```

## فرمت‌بندی: `strftime`

اگه `strptime` رشته رو به `datetime` تبدیل می‌کنه، `strftime` (str-format-time) دقیقاً برعکسش عمل می‌کنه — یه `datetime` رو به یه رشته‌ی فرمت‌شده تبدیل می‌کنه:

```python
formatted = dt.strftime("%Y/%m")
print(formatted)  # e.g. 2018/01
```

## مقایسه‌ی دو `datetime`

```python
dt1 = datetime(2018, 1, 1)
dt2 = datetime.now()

print(dt2 > dt1)  # True
```

آبجکت‌های `datetime` رو می‌شه مستقیم با عملگرهای مقایسه‌ای (`>`, `<`, `==`) مقایسه کرد — دقیقاً چون هر دو یه نقطه‌ی مشخص روی خط زمان رو نشون می‌دن.

## جمع‌بندی

- ساخت: `datetime(y, m, d)`, `datetime.now()`, `datetime.strptime(text, format)`, `datetime.fromtimestamp(ts)`.
- خوندن: `.year`, `.month`, `.day`, ...
- تبدیل به رشته: `.strftime(format)`.
- مقایسه: مستقیم با `>`, `<`, `==`.
