## کلاس `Path`: پایه‌ی کار با فایل و پوشه

برای کار با فایل و پوشه توی پایتون، همه‌چیز از کلاس `Path` (توی ماژول `pathlib`) شروع می‌شه:

```python
from pathlib import Path
```

## ساخت یه آبجکت `Path`

چند روش برای ساخت یه مسیر داریم:

```python
# Windows absolute path (raw string with r, so backslashes aren't escapes)
p = Path(r"C:\Program Files\Microsoft")

# Mac/Linux absolute path
p = Path("/usr/local/bin")

# relative path
p = Path("ecommerce/__init__.py")

# combining parts with a slash
p = Path("ecommerce") / "__init__.py"

# current user's home directory
p = Path.home()
```

نکته: توی رشته‌های معمولی، `\` یه کاراکتر escape حساب می‌شه (مثل `\n`). برای مسیرهای ویندوزی که پر از `\`ست، بهتره از یه **raw string** استفاده کنی (پیشوند `r`) تا بک‌اسلش‌ها همون‌طور که نوشتی خونده بشن، نه به‌عنوان escape.

## بررسی وضعیت یه مسیر

```python
p = Path("ecommerce/__init__.py")

print(p.exists())      # does this path actually exist?
print(p.is_file())     # is this a file?
print(p.is_dir())      # is this a directory?
```

## استخراج اجزای یه مسیر

```python
p = Path("ecommerce/__init__.py")

print(p.name)     # __init__.py  -- just the file name
print(p.stem)     # __init__     -- file name without the extension
print(p.suffix)   # .py          -- just the extension
print(p.parent)   # ecommerce    -- the parent directory
```

## ساخت یه مسیر جدید بر اساس مسیر فعلی

```python
p2 = p.with_name("file.txt")
print(p2)  # ecommerce/file.txt

p3 = p.with_suffix(".txt")
print(p3)  # ecommerce/__init__.txt

print(p.absolute())  # the full, absolute path of this file on disk
```

نکته‌ی مهم: `with_name` و `with_suffix` فایل رو واقعاً **تغییر نام نمی‌دن** — فقط یه آبجکت `Path` جدید با اسم متفاوت می‌سازن. این فایل هنوز با اسم قبلیش روی دیسکه؛ تغییرنام واقعی فایل، موضوع درس بعدیه.

## جمع‌بندی

کلاس `Path` یه راه مستقل از سیستم‌عامل برای نمایش و دستکاری مسیرهاست — به‌جای دست‌کاری دستی رشته‌ها با اسلش و بک‌اسلش. لیست کامل امکاناتش رو می‌تونی توی مستندات رسمی پایتون («python 3 pathlib») پیدا کنی.
