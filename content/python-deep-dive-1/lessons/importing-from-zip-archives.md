## پکیج داخل zip

پایتون می‌تواند ماژول‌ها و پکیج‌ها را مستقیم از داخل آرشیو zip import کند — بدون باز کردن آرشیو. (در `sys.path` پیش‌فرض هم یک `python3x.zip` هست.)

همان پکیج `common` درس ساختاردهی را zip می‌کنیم: `common.zip` (نام و پسوند دلخواه است). حالا:

```python
import common          # ModuleNotFoundError: No module named 'common'
import common.zip      # ModuleNotFoundError — this is not how it works either
```

پایتون نمی‌داند باید داخل این آرشیو را نگاه کند. راه‌حل: **خود آرشیو** را به `sys.path` اضافه کن — انگار یک پوشه است:

```python
import sys
sys.path.append('./common.zip')      # relative to the current working directory here

import common                        # works
import common.validators             # nested packages too
```

برنامه‌ی اصلی، بدون هیچ تغییر دیگری:

```python
# main.py
import sys
sys.path.append('./common.zip')

import common
import common.validators as validators
import common.models as models
import common.helpers as helpers

validators.is_boolean('true')
john = models.User()
print(helpers.factorial(5))
```

همه‌ی importها، `__init__.py`ها، relative importها و `__all__`ها از داخل آرشیو کار می‌کنند.

## چه کاربردی دارد؟

- توزیع یک کتابخانه یا کل برنامه به‌صورت **یک فایل** (به‌همراه `__main__.py` می‌شود `python app.zip` — درس `__main__`).
- ابزار استاندارد `zipapp` همین کار را رسمی می‌کند: `python -m zipapp myapp -m "main:run"`.
- `sys.path` می‌تواند ترکیبی از پوشه‌ها و آرشیوها باشد؛ `PathFinder` هر دو را می‌فهمد (با `zipimport`).

محدودیت: فقط فایل‌های `.py`/`.pyc` از zip بارگذاری می‌شوند؛ extensionهای باینری (`.so`/`.pyd`) نه. و برای پروژه‌های معمولی، نصب با `pip` راه استاندارد است؛ zip برای بسته‌بندی سبک و ابزارهای تک‌فایلی مفید است.
