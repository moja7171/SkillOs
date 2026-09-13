## سازمان‌دهی کد

فرض کن برنامه‌ات ۵۰ تابع و کلاس دارد: اتصال و query دیتابیس، تبدیل نوع‌ها، احراز هویت و مجوزها، مدل‌های `User`/`UserProfile`/`Users`، `BlogPost`ها، encoder/decoder JSON، لاگر، اعتبارسنجی ایمیل/تلفن، تست‌ها … .

**یک فایل؟** غیرقابل خواندن.

**ماژول‌ها** — قدم اول:

```
api/
    api.py
    dbutilities.py
    jsonutilities.py
    typeconversions.py
    validations.py
    authentication.py
    authorization.py
    users.py
    blogposts.py
    logging.py
    unittests.py
```

بهتر، ولی هنوز دست‌وپاگیر: همه‌چیز در سطح بالا، importهای زیاد (`import dbutilities, jsonutilities, typeconversions, ...`)، بعضی ماژول‌ها باز هم بزرگ‌اند (`dbutilities` → connections + queries؛ `users` → User, Users, UserProfile) و بعضی‌ها «به هم تعلق دارند» (authentication + authorization → security).

**پکیج‌ها** — سلسله‌مراتب:

```
api/
    api.py
    utilities/
        __init__.py
        database/
            __init__.py
            connections.py
            queries.py
        json/
            __init__.py
            encoders.py
            decoders.py
    security/
        __init__.py
        authentication.py
        authorization.py
    models/
        __init__.py
        users/
            __init__.py
            user.py
            userprofile.py
```

مثل کتابی که به فصل، بخش و پاراگراف تقسیم شده: کد کوچک‌تر با هدف مشخص، **آسان‌تر برای نوشتن، تست/debug، خواندن و مستند کردن**.

## دو دیدگاه: توسعه‌دهنده و کاربر پکیج

فرض کن کتابخانه‌ای می‌نویسی که برای کاربر فقط **یک تابع و یک کلاس** دارد، ولی آنها به ۲۰ تابع کمکی و ۲ کلاس داخلی نیاز دارند.

- **توسعه‌دهنده** می‌خواهد کد را به چند ماژول بشکند.
- **کاربر** می‌خواهد یک import ساده؛ انگار یک ماژول است.

```
mylib/
    __init__.py
    submod1.py           # my_func lives here
    submod2.py
    subpack1/
        __init__.py
        pack1mod1.py
        pack1mod2.py     # MyClass lives here
```

کاربر **نباید** مجبور باشد بنویسد:

```python
from mylib.submod1 import my_func
from mylib.subpack1.pack1mod2 import MyClass
```

باید بتواند بنویسد:

```python
from mylib import my_func, MyClass
# or
import mylib
mylib.my_func(); mylib.MyClass()
```

## `__init__.py` به‌عنوان «صادرکننده»

کد `__init__.py` پکیج می‌تواند دقیقاً چیزی را که کاربر لازم دارد در namespace پکیج بگذارد:

```python
# mylib/__init__.py
from mylib.submod1 import my_func
from mylib.subpack1.pack1mod2 import MyClass
```

حالا `import mylib` کافی است و پیاده‌سازی داخلی (ساختار پوشه‌ها، ماژول‌های کمکی) از کاربر **پنهان** می‌ماند؛ می‌توانی ساختار داخلی را عوض کنی بی‌آنکه کد کاربران بشکند. جزئیات و نمونه‌ی کامل در درس بعد.

**خلاصه:** پکیج = شکستن کد به قطعات کوچک برای خودمان + دوختن آنها به هم برای کاربر.
