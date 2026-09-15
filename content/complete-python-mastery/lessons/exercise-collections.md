## چالش: پرتکرارترین کاراکتر

این یکی از معروف‌ترین سؤال‌های مصاحبه‌ی برنامه‌نویسیه، توی شرکت‌های بزرگ هم زیاد ازش استفاده می‌شه:

> یه متن بگیر و پرتکرارترین کاراکترش رو پیدا کن.

۵ تا ۱۰ دقیقه روش وقت بذار، بعد بیا ادامه رو بخون.

## قدم اول: شمردن تکرار هر کاراکتر

اولین چیزی که لازم داریم: بدونیم هر کاراکتر چندبار توی متن تکرار شده. کدوم ساختار داده برای این کار مناسبه؟ یه **دیکشنری** — کاراکتر می‌شه کلید، تعداد تکرارش می‌شه مقدار.

```python
sentence = "hello world"

char_frequency = {}

for char in sentence:
    if char in char_frequency:
        char_frequency[char] += 1
    else:
        char_frequency[char] = 1

print(char_frequency)
```

خروجی (خوانا نیست، ولی درسته):
```
{'h': 1, 'e': 1, 'l': 3, 'o': 2, ' ': 1, 'w': 1, 'r': 1, 'd': 1}
```

## یه چاپ خواناتر

ماژول `pprint` (مخفف pretty print) یه تابع داره که خروجی رو بهتر فرمت می‌کنه:

```python
from pprint import pprint

pprint(char_frequency, width=1)
```

با `width=1`، هر جفت کلید-مقدار توی خط خودش چاپ می‌شه.

## قدم دوم: مرتب‌کردن بر اساس تعداد تکرار

دیکشنری (مثل مجموعه) **بی‌ترتیبه** — نمی‌شه مستقیم مرتبش کرد. باید اول جفت‌های کلید-مقدارش رو به یه لیست از تاپل تبدیل کنیم:

```python
sorted_chars = sorted(char_frequency.items(), key=lambda item: item[1])
print(sorted_chars)
```

`char_frequency.items()` یه لیست از تاپل‌های `(کاراکتر، تعداد)` می‌ده؛ `sorted()` با `key=lambda item: item[1]` بر اساس تعداد (عضو دومِ هر تاپل) مرتبش می‌کنه.

## قدم سوم: نزولی مرتب کن و جواب رو بگیر

```python
sorted_chars = sorted(char_frequency.items(), key=lambda item: item[1], reverse=True)

most_common = sorted_chars[0]
print(most_common)
# ('l', 3)
```

با `reverse=True`، پرتکرارترین کاراکتر میاد اول لیست — پس `sorted_chars[0]` دقیقاً جوابیه که دنبالش بودیم.

## نکته‌ی PEP8

وقتی یه خط خیلی طولانی می‌شه (PEP8 پیشنهاد می‌ده حداکثر ۷۹ کاراکتر)، می‌تونی آرگومان‌ها رو هرکدوم توی خط جدا بنویسی:

```python
sorted_chars = sorted(
    char_frequency.items(),
    key=lambda item: item[1],
    reverse=True,
)
```

## جمع‌بندی این بخش

این تمرین، خلاصه‌ی خیلی از چیزهاییه که توی این بخش دیدیم: دیکشنری برای شمردن، `items()` برای گرفتن جفت‌ها به‌شکل قابل‌مرتب‌سازی، و `sorted` با `key` و `reverse` برای رسیدن به جواب. بخش بعدی می‌ریم سراغ خطاها و استثناها.
