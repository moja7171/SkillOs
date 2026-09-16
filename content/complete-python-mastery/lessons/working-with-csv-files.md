## CSV چیه؟

**CSV** (Comma-Separated Values) یه فرمت ساده برای ذخیره‌ی داده‌ی جدولی توی یه فایل متنی معمولیه — مثل یه صفحه‌گسترده‌ی خیلی ساده. برای کار باهاش، ماژول آماده‌ی `csv` رو وارد می‌کنیم:

```python
import csv
```

## نوشتن توی یه فایل CSV

برخلاف `Path`، برای کار با CSV باید از تابع آماده‌ی `open()` استفاده کنیم — چون کلاس `csv.writer` یه آبجکت **فایل** می‌خواد، نه یه آبجکت `Path`:

```python
with open("data.csv", "w") as file:
    writer = csv.writer(file)

    writer.writerow(["transaction_id", "product_id", "price"])
    writer.writerow([1000, 1, 5])
    writer.writerow([1001, 2, 8])
```

هر فراخوانی `writerow` یه ردیف جدید به فایل اضافه می‌کنه؛ آرگومانش یه لیست از مقادیره. نتیجه یه فایل متنی ساده‌ست که هر خطش یه ردیف، و هر مقدار با کاما جدا شده.

## خوندن از یه فایل CSV

```python
with open("data.csv") as file:
    reader = csv.reader(file)
    rows = list(reader)

print(rows)
# [['transaction_id', 'product_id', 'price'], ['1000', '1', '5'], ...]
```

نکته: همه‌ی مقادیر، حتی اعداد، به‌شکل **رشته** خونده می‌شن. اگه لازمه باهاشون محاسبات عددی انجام بدی، باید صریح تبدیلشون کنی (مثلاً با `int()` یا `float()`).

## یه نکته‌ی مهم: `reader` فقط یه‌بار قابل‌پیمایشه

```python
with open("data.csv") as file:
    reader = csv.reader(file)

    rows = list(reader)   # reader's position moves to the end of the file

    for row in reader:    # nothing gets printed!
        print(row)
```

آبجکت `reader` یه موقعیت داخلی داره که از ابتدای فایل شروع می‌شه. وقتی `list(reader)` رو صدا می‌زنیم، این موقعیت به انتهای فایل می‌ره — پس اگه دوباره روش پیمایش کنیم، دیگه چیزی برای خوندن نیست. اگه می‌خوای هم `list()` بگیری هم پیمایش کنی، فقط یکیش رو انتخاب کن.

## جمع‌بندی

CSV یه فرمت ساده و سبک برای تبادل داده‌ی جدولیه. با `csv.writer` می‌نویسیم، با `csv.reader` می‌خونیم — و همیشه باید فایل رو با `open()` باز کنیم، نه با `Path`.
