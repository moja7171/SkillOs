## نصب یه پکیج

برای نصب پکیج‌ها از PyPI، از ابزار خط‌فرمان `pip` استفاده می‌کنیم (روی مک، مثل خود پایتون، معمولاً `pip3`):

```bash
pip install requests
```

این دستور آخرین نسخه‌ی پکیج `requests` (یه پکیج محبوب برای فرستادن درخواست‌های HTTP) رو نصب می‌کنه.

## نسخه‌بندی معنایی (Semantic Versioning)

نسخه‌ی هر پکیج معمولاً سه بخشه، مثلاً `2.20.1`:

- **نسخه‌ی اصلی** (major): تغییرات بزرگ، احتمالاً ناسازگار با نسخه‌های قبلی.
- **نسخه‌ی فرعی** (minor): قابلیت جدید، سازگار با قبلی.
- **پچ** (patch): رفع باگ، سازگار با قبلی.

## نصب یه نسخه‌ی مشخص

```bash
pip install requests==2.9.0        # exactly this version
pip install requests==2.9.*        # latest version compatible with 2.9 (including patches)
pip install requests~=2.9.0        # same as == with a star
```

`==` نسخه‌ی دقیق رو نصب می‌کنه؛ ستاره (`*`) یعنی «هر نسخه‌ای که با این پیشوند سازگاره» (مثلاً آخرین پچ روی `2.9`)؛ `~=` هم همین معنی رو می‌ده، فقط با نحو متفاوت.

## مدیریت پکیج‌های نصب‌شده

```bash
pip list                    # list every installed package and its version
pip install --upgrade pip   # upgrade pip itself
pip uninstall requests      # remove a package
```

## استفاده از پکیج نصب‌شده

بعد از نصب، پکیج رو دقیقاً مثل یه ماژول کتابخانه‌ی استاندارد import می‌کنیم:

```python
import requests

response = requests.get("https://google.com")
print(response)  # <Response [200]>
```

کد وضعیت `200` یعنی درخواست موفق بوده.

## جمع‌بندی

`pip install <name>` برای نصب، `pip list` برای دیدن چی نصبه، `pip uninstall <name>` برای حذف — و برای نسخه‌ی مشخص، از `==` یا `~=` استفاده کن.
