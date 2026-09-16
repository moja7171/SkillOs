## اجرای یه برنامه‌ی دیگه از داخل پایتون

گاهی می‌خوای از داخل کد پایتون، یه برنامه‌ی دیگه (یا یه دستور سیستم‌عامل) رو اجرا کنی — مثلاً دستور `ls` (لیست فایل‌ها روی مک/لینوکس، معادل `dir` روی ویندوز)، یا حتی یه اسکریپت پایتونی دیگه. ماژول `subprocess` دقیقاً برای همین کاره.

این ماژول چند تا تابع قدیمی‌تر داره (`call`, `check_call`, `check_output`) که امروزه منسوخ حساب می‌شن. روش استاندارد و توصیه‌شده، تابع `run` هست.

## اجرای یه دستور ساده

```python
import subprocess

subprocess.run(["ls", "-l"])
```

آرگومان اول `run`، یه **لیست از رشته‌ها**ست: اولی اسم دستور، بقیه آرگومان‌های اون دستور. خروجی دستور، مستقیم توی همون ترمینالی که برنامه رو اجرا کردی چاپ می‌شه.

## بررسی نتیجه‌ی اجرا

`run` یه آبجکت از نوع `CompletedProcess` برمی‌گردونه:

```python
completed = subprocess.run(["ls", "-l"])

print(completed.args)         # the command that ran
print(completed.returncode)   # 0 means success; any non-zero value means an error
print(completed.stderr)       # error message (if captured)
print(completed.stdout)       # command output (if captured)
```

به‌طور پیش‌فرض، `stdout`/`stderr` مقدار `None` دارن — چون خروجی مستقیم توی ترمینال چاپ شده، نه ضبط‌شده.

## گرفتن خروجی به‌جای چاپ مستقیم

اگه بخوای خروجی دستور رو توی برنامه پردازش کنی (مثلاً ذخیره‌ش کنی توی یه فایل)، باید صریح بگی خروجی رو **capture** کنه:

```python
completed = subprocess.run(["ls", "-l"], capture_output=True, text=True)

print(completed.stdout)
```

- `capture_output=True`: خروجی مستقیم چاپ نمی‌شه، بلکه توی `completed.stdout` ذخیره می‌شه.
- `text=True`: بدون این، `stdout` یه آبجکت `bytes` (با پیشوند `b` موقع چاپ) برمی‌گردونه؛ با `text=True`، یه رشته‌ی معمولی می‌گیری.

## اجرای یه اسکریپت پایتونی دیگه

همین تابع `run` رو می‌شه برای اجرای یه فایل پایتونی دیگه هم استفاده کرد:

```python
subprocess.run(["python3", "other.py"], capture_output=True, text=True)
```

نکته‌ی مهم: این با **import کردن** اون فایل کاملاً فرقی داره. اینجا `other.py` توی یه **پردازش (process) کاملاً جدا و مستقل** اجرا می‌شه — دو تا اسکریپت هیچ متغیری با هم به‌اشتراک نمی‌ذارن، برخلاف import که همه‌چیز توی همون پردازش و فضای حافظه‌ی برنامه‌ی اصلی اجرا می‌شه.

## مدیریت خطا

اگه دستور اجراشده با خطا مواجه بشه، `returncode` غیرصفر می‌شه:

```python
completed = subprocess.run(["false"])
print(completed.returncode)  # 1
```

یه روش برای مدیریتش، چک‌کردن دستی `returncode`:

```python
if completed.returncode != 0:
    print(completed.stderr)
```

روش تمیزتر: آرگومان کلیدی `check=True` — اگه دستور با خطا مواجه بشه، به‌جای برگردوندن یه `returncode` غیرصفر، یه استثنا صادر می‌کنه:

```python
try:
    subprocess.run(["false"], check=True)
except subprocess.CalledProcessError as e:
    print(e)
```

## جمع‌بندی

- `subprocess.run(command_list)` روش استاندارد برای اجرای یه دستور یا برنامه‌ی خارجیه.
- برای گرفتن خروجی به‌جای چاپ مستقیم: `capture_output=True, text=True`.
- برای صدورِ خودکارِ استثنا موقع خطا (به‌جای چک‌کردن دستی `returncode`): `check=True`.
