## `timedelta`: یه مدت‌زمان، نه یه لحظه‌ی خاص

توی درس قبل دیدیم `datetime` یه **نقطه‌ی مشخص** روی خط زمانه (مثلاً «۱۵ مارس ۲۰۲۴»). کلاس `timedelta` (از همون ماژول `datetime`) یه **مدت‌زمان** رو نشون می‌ده — مثل «۵ روز» یا «۳ ساعت».

```python
from datetime import datetime, timedelta
```

## کم‌کردن دو `datetime` از هم

```python
dt1 = datetime(2018, 1, 1)
dt2 = datetime.now()

duration = dt2 - dt1
print(duration)
```

وقتی دو تا `datetime` رو از هم کم می‌کنیم، نتیجه به‌طور خودکار یه `timedelta` می‌شه.

## ویژگی‌های `timedelta`

```python
print(duration.days)      # number of full days
print(duration.seconds)   # remaining seconds (after removing full days)
```

`seconds` فقط ثانیه‌های **باقی‌مونده**ی داخل یه روزه، نه کل مدت‌زمان به ثانیه. مثلاً اگه `duration` معادل ۳۲۴ روز و ۱۲ ساعت و ۵۸ دقیقه باشه، `duration.seconds` چیزی حدود ۴۶۰۰۰ (معادل همون ۱۲ ساعت و ۵۸ دقیقه) می‌شه، نه معادل کل ۳۲۴ روز به‌اضافه‌ش.

## کل مدت‌زمان به ثانیه: `total_seconds()`

اگه بخوای کل مدت‌زمان رو یک‌جا به ثانیه داشته باشی (نه فقط باقی‌مونده‌ی داخل یه روز)، از متد `total_seconds()` استفاده کن:

```python
print(duration.total_seconds())
```

## چرا `timedelta` ماه یا سال نداره؟

برخلاف `year`/`month` توی `datetime`، کلاس `timedelta` **فقط** `days`, `seconds`, `microseconds` داره — نه ماه، نه سال. چرا؟ چون طول یه ماه یا سال ثابت نیست (بعضی ماه‌ها ۲۸ روزن، بعضی ۳۱؛ سال کبیسه ۳۶۶ روزه). یه مدت‌زمان مثل «یک ماه» معنای دقیق و ثابتی به تعداد روز نداره، پس `timedelta` فقط با واحدهای همیشه‌ثابت (روز، ثانیه، میکروثانیه) کار می‌کنه.

## اضافه‌کردن `timedelta` به یه `datetime`

می‌شه یه `timedelta` رو به یه `datetime` اضافه کرد تا یه `datetime` جدید به‌دست بیاد:

```python
dt1 = datetime(2018, 1, 1)

dt1 = dt1 + timedelta(days=1, seconds=1000)
print(dt1)
```

نکته: همیشه از **آرگومان‌های کلیدی** (`days=1`) استفاده کن، نه یه عدد بدون نام (`timedelta(1)`). چون بدون اسم، خواننده‌ی کد نمی‌تونه بفهمه اون عدد نماینده‌ی روزه یا ساعت یا چیز دیگه‌ای.

## جمع‌بندی

- `datetime2 - datetime1` یه `timedelta` می‌ده.
- `timedelta.days`/`.seconds` اجزای مدت‌زمان؛ `.total_seconds()` کل مدت‌زمان به ثانیه.
- `timedelta` فقط روز/ثانیه/میکروثانیه داره، چون واحدهای بزرگ‌تر (ماه/سال) طول ثابتی ندارن.
