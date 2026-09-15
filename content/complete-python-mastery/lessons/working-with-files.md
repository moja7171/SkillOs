## متدهای پایه‌ای روی یه فایل

روی یه آبجکت `Path` که به یه فایل اشاره می‌کنه:

```python
from pathlib import Path

p = Path("__init__.py")

p.exists()               # does this file exist?
p.rename("init.txt")     # actually rename the file
p.unlink()                # actually delete the file
```

## اطلاعات فایل با `stat()`

```python
info = p.stat()

print(info.st_size)    # file size in bytes
print(info.st_ctime)   # creation time
print(info.st_mtime)   # last modified time
```

مقادیر زمانی مثل `st_ctime` به‌شکل یه عدد (ثانیه از **epoch**، یعنی نقطه‌ی شروع زمان روی اون سیستم‌عامل — مثلاً روی یونیکس، اول ژانویه‌ی ۱۹۷۰) برمی‌گردن، که خودشون خونا نیستن. برای تبدیلشون به یه فرمت قابل‌خوندن:

```python
from time import ctime

print(ctime(info.st_ctime))
```

## خوندن محتوای فایل

```python
content_bytes = p.read_bytes()  # as bytes (for binary data)
content_text = p.read_text()    # as str (for text)
```

این متدها ساده‌تر از تابع آماده‌ی `open()` هستن — چون خودشون باز و بسته‌کردن فایل رو مدیریت می‌کنن. مقایسه کن:

```python
# with open() -- you must explicitly open and close it
with open("init.py") as f:
    content = f.read()

# with read_text() -- all of that happens behind the scenes
content = Path("init.py").read_text()
```

## نوشتن توی فایل

```python
p.write_text("hello")       # write text
p.write_bytes(b"hello")     # write binary data
```

این متدها هم خودشون باز و بسته‌کردن فایل رو مدیریت می‌کنن.

## کپی‌کردن فایل: `shutil`

برای کپی‌کردن، `Path` بهترین ابزار نیست. می‌شه با `read_text` و `write_text` دستی کپی کرد:

```python
target = Path("init_backup.py")
target.write_text(p.read_text())
```

ولی راه تمیزتری هم هست — ماژول `shutil` (shell utilities) عملیات‌های سطح‌بالا برای کپی/انتقال فایل و پوشه داره:

```python
import shutil

shutil.copy(p, target)
```

## جمع‌بندی

`Path` برای اکثر عملیات‌های روزمره روی فایل (چک‌کردن، خوندن، نوشتن، حذف) کافیه؛ برای کپی‌کردن، `shutil` تمیزتر و ساده‌تره.
