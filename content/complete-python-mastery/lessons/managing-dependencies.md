## بخش ۱: مدیریت وابستگی‌های نصب‌شده

### دیدن درخت وابستگی‌ها

```bash
pipenv graph
```

این دستور همه‌ی پکیج‌های نصب‌شده رو نشون می‌ده، همراه با وابستگی‌های خودِ هر پکیج (مثلاً `requests` خودش چند تا پکیج دیگه رو لازم داره) و نسخه‌ی دقیق هرکدوم.

### حذف یه پکیج

```bash
pipenv uninstall requests
```

نکته: این فقط خودِ `requests` رو از `Pipfile` حذف می‌کنه — وابستگی‌های غیرمستقیمش (که `requests` بهشون نیاز داشت) همچنان نصب می‌مونن، چون Pipenv نمی‌دونه جای دیگه‌ای ازشون استفاده می‌شه یا نه. اگه پروژه رو از صفر روی یه سیستم دیگه نصب کنی، چون دیگه توی `Pipfile` نیستن، اونجا هم نصب نمی‌شن.

### پیدا کردن پکیج‌های قدیمی

```bash
pipenv update --outdated
```

این نشون می‌ده کدوم پکیج‌ها نسخه‌ی جدیدتری دارن. اگه `Pipfile` یه محدودیت نسخه داشته باشه (مثلاً `2.9.*`)، ممکنه Pipenv نتونه نسخه‌ی جدیدتر رو (که با اون محدودیت سازگار نیست) پیشنهاد بده — باید اول خود محدودیت رو توی `Pipfile` عوض کنی.

### به‌روزرسانی

```bash
pipenv update            # every package
pipenv update requests   # just one specific package
```

## بخش ۲: منتشرکردن پکیج خودت روی PyPI

### ساختار یه پکیج قابل‌انتشار

برای انتشار یه پکیج، طبق قرارداد، یه پوشه‌ی سطح‌بالا با همون اسم پکیج می‌سازیم، با یه `__init__.py` که پایتون اون رو به‌عنوان پکیج بشناسه:

```
mypdf/
├── mypdf/
│   ├── __init__.py
│   ├── pdf_to_text.py
│   └── pdf_to_image.py
├── setup.py
├── README.md
└── LICENSE
```

### فایل `setup.py`

```python
from pathlib import Path
from setuptools import setup, find_packages

setup(
    name="mypdf",
    version="1.0",
    long_description=Path("README.md").read_text(),
    packages=find_packages(exclude=["tests", "data"]),
)
```

- `name`: یه اسم یکتا (نباید با پکیج دیگه‌ای توی PyPI تداخل داشته باشه).
- `long_description`: معمولاً محتوای فایل `README.md` — همون چیزی که توی صفحه‌ی پکیج روی PyPI نمایش داده می‌شه.
- `packages`: با `find_packages()`، خودِ ابزار پکیج‌های پروژه رو پیدا می‌کنه؛ پوشه‌هایی که کد منبع نیستن (مثل `tests`, `data`) رو با `exclude` کنار می‌ذاریم.

### ساخت بسته‌ی توزیع (distribution)

```bash
python setup.py sdist bdist_wheel
```

این دستور دو تا فایل فشرده می‌سازه توی پوشه‌ی `dist/`: یه **source distribution** (کد منبع خام) و یه **wheel** (فرمت از‌پیش‌ساخته‌شده، سریع‌تر برای نصب).

### آپلود روی PyPI

```bash
twine upload dist/*
```

ابزار `twine` هر دو فایل توی `dist/` رو به حساب PyPI آپلود می‌کنه (نیاز به حساب کاربری و ورود داره). بعد از آپلود، پکیج دقیقاً مثل هر پکیج دیگه‌ای روی PyPI قابل‌نصب و import‌شدنه:

```bash
pip install mypdf
```

```python
from mypdf import pdf_to_text

pdf_to_text.convert()
```

## جمع‌بندی

- مدیریت وابستگی‌های روزمره: `pipenv graph`, `pipenv uninstall`, `pipenv update --outdated`.
- انتشار پکیج خودت: `setup.py` (با `find_packages`) → `python setup.py sdist bdist_wheel` → `twine upload dist/*`.
