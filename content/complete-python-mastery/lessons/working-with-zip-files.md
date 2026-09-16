## ساخت یه فایل zip

برای کار با فایل‌های فشرده، کلاس `ZipFile` رو از ماژول `zipfile` وارد می‌کنیم:

```python
from zipfile import ZipFile
```

برای ساخت یه zip جدید، حالت `"w"` (write) رو مشخص می‌کنیم:

```python
with ZipFile("files.zip", "w") as zip:
    ...
```

استفاده از `with` مهمه — دقیقاً مثل کار با فایل معمولی، اگه یه‌جا خطایی رخ بده، `with` مطمئن می‌شه فایل zip درست بسته می‌شه (به‌جای اینکه مجبور باشیم دستی `close()` رو توی یه بلوک `try/finally` صدا بزنیم).

## اضافه‌کردن فایل‌ها به zip

```python
from pathlib import Path
from zipfile import ZipFile

source = Path("ecommerce")

with ZipFile("files.zip", "w") as zip:
    for path in source.rglob("*"):
        zip.write(path)
```

با `rglob("*")` همه‌ی فایل‌های پوشه‌ی `ecommerce` و زیرپوشه‌هاش رو (به‌شکل بازگشتی) می‌گیریم و یکی‌یکی با `zip.write(path)` به فایل فشرده اضافه می‌کنیم.

## خوندن محتوای یه zip

```python
with ZipFile("files.zip") as zip:
    print(zip.namelist())
```

این‌بار حالت رو مشخص نکردیم — پیش‌فرض `ZipFile` حالت خوندنه. `namelist()` اسم همه‌ی فایل‌های داخل zip رو برمی‌گردونه.

## گرفتن اطلاعات یه فایل خاص داخل zip

```python
with ZipFile("files.zip") as zip:
    info = zip.getinfo("ecommerce/__init__.py")
    print(info.file_size)      # original file size
    print(info.compress_size)  # size after compression
```

## استخراج فایل‌ها از zip

```python
with ZipFile("files.zip") as zip:
    zip.extractall("extract")
```

این همه‌ی فایل‌های داخل zip رو توی پوشه‌ی `extract` استخراج می‌کنه (اگه پوشه رو مشخص نکنی، توی پوشه‌ی فعلی استخراج می‌کنه).

## جمع‌بندی

- ساخت zip: `ZipFile(name, "w")` + `zip.write(path)` برای هر فایل.
- خوندن zip: `ZipFile(name)` (پیش‌فرض خوندن) + `namelist()`, `getinfo()`, `extractall()`.
- همیشه از `with` استفاده کن تا فایل zip به‌درستی بسته بشه.
