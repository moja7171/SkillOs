## بلوک `try`/`except`

بریم سراغ همون مثال قبلی — گرفتن سن از کاربر. برای جلوگیری از کرش، دستوری که ممکنه خطا بده رو توی یه بلوک `try` می‌ذاریم:

```python
try:
    age = int(input("Age: "))
    print(age)
except ValueError:
    print("You didn't enter a valid age")
```

پایتون اول همه‌ی دستورهای داخل `try` رو اجرا می‌کنه. اگه یکی‌شون استثنا صادر کنه، کنترل فوراً می‌ره سراغ بلوک `except` **متناظر** — یعنی همونی که نوع استثنا رو مشخص کرده باشه. اگه هیچ استثنایی رخ نده، بلوک `except` اصلاً اجرا نمی‌شه.

## اجرا ادامه پیدا می‌کنه

```python
try:
    age = int(input("Age: "))
except ValueError:
    print("You didn't enter a valid age")

print("execution continues")
```

فرق کلیدی با نداشتن `try`: وقتی استثنا رو **مدیریت می‌کنی**، برنامه کرش نمی‌کنه — اجرا بعد از بلوک `try`/`except` ادامه پیدا می‌کنه، فرقی نداره خطایی رخ داده باشه یا نه.

## بلوک اختیاری `else`

```python
try:
    age = int(input("Age: "))
except ValueError:
    print("You didn't enter a valid age")
else:
    print("no exceptions were thrown")
```

بلوک `else` فقط وقتی اجرا می‌شه که **هیچ استثنایی** توی `try` رخ نده — دقیقاً همون منطق `for...else` که قبلاً دیدی: `else` یعنی «اگه چیز غیرعادی‌ای اتفاق نیفتاد».

## گرفتن جزئیات خود استثنا

می‌تونی با `as` یه متغیر تعریف کنی که خود شیء استثنا رو نگه می‌داره:

```python
try:
    age = int(input("Age: "))
except ValueError as e:
    print("You didn't enter a valid age")
    print(e)
    print(type(e))
```

`e` جزئیات فنی خطا رو داره (مثل `invalid literal for int() with base 10: 'a'`) — این پیام معمولاً برای **دیباگ‌کردن** مفیده، نه برای نشون‌دادن مستقیم به کاربر نهایی؛ کاربر همون پیام دوستانه‌ای که خودت نوشتی رو باید ببینه.
