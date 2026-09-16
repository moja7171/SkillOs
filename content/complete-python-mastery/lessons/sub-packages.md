## وقتی یه پکیج هم بزرگ می‌شه

پکیج `ecommerce` که توی درس قبل ساختیم، ممکنه با گذشت زمان خیلی بزرگ بشه و ده‌ها ماژول توش جمع بشه. راه‌حل همون کاریه که با خود پوشه‌ی اصلی کردیم: تقسیمش به زیرپوشه‌های کوچیک‌تر.

فرض کن `sales.py` رو به یه زیرپوشه‌ی جدید به اسم `shopping` منتقل می‌کنیم:

```
project/
├── app.py
└── ecommerce/
    ├── __init__.py
    └── shopping/
        └── sales.py
```

## `shopping` هم باید پکیج بشه

الان `ecommerce` بالای زنجیره‌ست و زیرش `shopping` هست. ولی `shopping` هنوز یه پکیج حساب نمی‌شه، چون داخلش `__init__.py` نداره. دقیقاً مثل درس قبل، اضافه‌ش می‌کنیم:

```
project/
├── app.py
└── ecommerce/
    ├── __init__.py
    └── shopping/
        ├── __init__.py
        └── sales.py
```

حالا `shopping` یه **زیرپکیج** (sub-package) داخل `ecommerce`ه.

## import با مسیر کامل

برای دسترسی به `sales`، باید کل مسیر زیرپکیج رو با نقطه بنویسیم:

```python
from ecommerce.shopping import sales

sales.calculate_tax()
```

یا:

```python
from ecommerce.shopping.sales import calculate_tax

calculate_tax()
```

## جمع‌بندی

زیرپکیج فقط یه پکیج داخل یه پکیج دیگه‌ست — همون قانون همیشگی: هر پوشه‌ای که می‌خوای قابل‌import باشه، باید `__init__.py` داشته باشه، و مسیر import هم دقیقاً ساختار پوشه‌ها رو با نقطه دنبال می‌کنه.
