## متدهای پایه‌ای برای پوشه‌ها

روی یه آبجکت `Path` که به یه پوشه اشاره می‌کنه، این متدها در دسترسن:

```python
from pathlib import Path

p = Path("ecommerce")

p.exists()   # does this directory exist?
p.mkdir()    # create this directory
p.rmdir()    # remove this directory
p.rename("new-name")  # rename the directory
```

اسم این متدها خودشون گویان، پس بیشتر وقتمون رو صرف یه متد جالب‌تر می‌کنیم: `iterdir`.

## لیست‌کردن محتوای یه پوشه با `iterdir`

```python
p = Path("ecommerce")

result = p.iterdir()
print(result)  # <generator object ...>
```

`iterdir` یه **جنراتور** برمی‌گردونه، نه یه لیست آماده. چرا؟ چون یه پوشه ممکنه میلیون‌ها فایل داشته باشه — نگه‌داشتن همه‌شون توی حافظه به‌یه‌باره اصلاً کارآمد نیست. جنراتور هر بار که ازش می‌خوای، فقط یه مقدار تولید می‌کنه.

```python
for item in p.iterdir():
    print(item)
```

اگه مطمئنی پوشه‌ت میلیون‌ها فایل نداره، می‌شه جنراتور رو با یه list comprehension به لیست تبدیل کرد:

```python
items = [item for item in p.iterdir()]
```

نتیجه یه لیست از آبجکت‌های `PosixPath` (روی مک/لینوکس) یا `WindowsPath` (روی ویندوز) هست — هر دو زیرکلاس همون `Path`ی که وارد کردیم.

## فیلترکردن با list comprehension

```python
directories = [item for item in p.iterdir() if item.is_dir()]
```

## جست‌وجو با الگو: `glob`

`iterdir` همه‌چیز رو برمی‌گردونه، ولی نمی‌شه باهاش فقط دنبال یه الگوی خاص گشت یا به‌شکل بازگشتی (recursive) توی زیرپوشه‌ها هم جست‌وجو کرد. برای این کار، `glob` به‌کار میاد:

```python
py_files = p.glob("*.py")
```

این یه جنراتوره که فقط فایل‌های با پسوند `.py` رو توی همون پوشه برمی‌گردونه (نه زیرپوشه‌ها).

## جست‌وجوی بازگشتی با `rglob`

برای جست‌وجو توی پوشه و **همه‌ی زیرپوشه‌هاش**، از `rglob` (مخفف recursive glob) استفاده می‌کنیم:

```python
py_files = p.rglob("*.py")
```

## جمع‌بندی

- `iterdir()`: همه‌ی اعضای یه پوشه (بدون فیلتر، بدون بازگشتی).
- `glob(pattern)`: فقط اعضایی که با یه الگو مطابقت دارن (بدون بازگشتی).
- `rglob(pattern)`: مثل `glob`، ولی توی زیرپوشه‌ها هم می‌گرده.
