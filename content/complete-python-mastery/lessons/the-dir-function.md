## یه تابع آماده برای دیباگ

تابع آماده‌ی `dir()` لیست همه‌ی ویژگی‌ها و متدهای یه آبجکت (یا ماژول) رو بهت می‌ده. وقتی یه چیزی طبق انتظار کار نمی‌کنه، `dir()` یه ابزار خوب برای بررسیه.

```python
import sales

print(dir(sales))
```

خروجی یه لیست از رشته‌هاست — هم توابعی که خودمون تعریف کردیم (`calculate_tax`, `calculate_shipping`)، هم یه‌سری ویژگی جادویی که پایتون خودکار به هر ماژول اضافه می‌کنه.

## ویژگی‌های جادویی هر ماژول

هر ماژول، صرف‌نظر از اینکه خودت چی توش نوشتی، این ویژگی‌ها رو به‌طور خودکار داره:

```python
print(sales.__name__)     # full module name (including its package)
print(sales.__package__)  # the package this module lives in
print(sales.__file__)     # path to the module's file on disk
```

اگه `sales` داخل `ecommerce/shopping/` باشه:

```
sales.__name__     -> ecommerce.shopping.sales
sales.__package__  -> ecommerce.shopping
sales.__file__     -> /path/to/project/ecommerce/shopping/sales.py
```

## جمع‌بندی

`dir()` برای دیباگ و کاوش سریع توی محتوای یه آبجکت یا ماژول کاربردیه — به‌جای اینکه بری سورس‌کد رو بخونی، می‌تونی مستقیم توی خط فرمان یا کد ببینی چه اسم‌هایی روی اون آبجکت تعریف شدن.
