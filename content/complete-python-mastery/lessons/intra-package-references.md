## ارجاع به یه پکیج خواهر

فرض کن این ساختار رو داری — `ecommerce` دو تا زیرپکیج داره: `shopping` (که `sales.py` توشه) و یه زیرپکیج جدید `customer` (که یه ماژول `contact.py` توشه):

```
ecommerce/
├── __init__.py
├── shopping/
│   ├── __init__.py
│   └── sales.py
└── customer/
    ├── __init__.py
    └── contact.py
```

حالا می‌خوایم توی `sales.py`، از ماژول `contact.py` (که توی یه زیرپکیج **خواهر**، نه والد یا فرزند، هست) استفاده کنیم. دو راه داریم: import مطلق یا نسبی.

## import مطلق (Absolute Import)

از بالای زنجیره‌ی پکیج شروع می‌کنیم — دقیقاً همون چیزی که توی `app.py` می‌نوشتیم:

```python
# inside ecommerce/shopping/sales.py
from ecommerce.customer import contact

contact.send_email()
```

## import نسبی (Relative Import)

به‌جای شروع از بالای زنجیره، از **موقعیت فعلی** حرکت می‌کنیم. یه نقطه یعنی «همین پکیج فعلی»، دو نقطه یعنی «یه سطح بالاتر برو»:

```python
# inside ecommerce/shopping/sales.py
from ..customer import contact

contact.send_email()
```

`..` ما رو از `shopping` می‌بره بالا به سطح `ecommerce` — و از اونجا می‌شه به زیرپکیج خواهر `customer` رسید.

## کدوم رو استفاده کنیم؟

طبق PEP 8 (راهنمای رسمی سبک پایتون)، **import مطلق ترجیح داده می‌شه** — چون خواناتره و مسیر دقیق ماژول رو صریح نشون می‌ده. اما اگه مسیر مطلق خیلی طولانی و شلوغ بشه (مثل `a.b.c.d.e`)، استفاده از import نسبی می‌تونه کد رو ساده‌تر کنه.

## جمع‌بندی

- import مطلق: مسیر کامل از بالای پکیج (پیش‌فرض و توصیه‌شده).
- import نسبی: مسیر بر اساس موقعیت فعلی (`.` همین پکیج، `..` یه سطح بالاتر).
