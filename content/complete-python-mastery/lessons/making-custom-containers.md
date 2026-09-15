## یه کانتینر سفارشی

قبلاً با لیست، مجموعه و دیکشنری آشنا شدی — این‌ها **کانتینر** (container) هستن: ساختارهایی که یه مجموعه از مقادیر رو نگه می‌دارن. گاهی می‌خوای کانتینر سفارشی خودت رو بسازی. بیایم یه کلاس `TagCloud` بسازیم که تعداد تکرار هر تگ (مثلاً روی یه بلاگ) رو نگه می‌داره — و بشه باهاش کارهایی مثل این‌ها کرد:

```python
cloud = TagCloud()
cloud.add("python")

print(len(cloud))           # number of tags
print(cloud["python"])      # count for one tag
cloud["python"] = 10        # direct assignment
for tag, count in cloud:    # iteration
    print(tag, count)
```

## شروع: نگه‌داشتن داده‌ها با یه دیکشنری

```python
class TagCloud:
    def __init__(self):
        self.tags = {}

    def add(self, tag):
        self.tags[tag] = self.tags.get(tag, 0) + 1
```

داخل `add`، از همون الگوی شمردنی استفاده می‌کنیم که قبلاً دیدیم: با `get(tag, 0)` مقدار فعلیِ تگ رو می‌گیریم (یا صفر، اگه هنوز وجود نداره)، یکی بهش اضافه می‌کنیم، و دوباره ذخیره می‌کنیم.

```python
cloud = TagCloud()
cloud.add("python")
cloud.add("python")
cloud.add("python")

print(cloud.tags)
# {'python': 3}
```

## یه مشکل ظریف: حساسیت به بزرگی/کوچیکی حروف

```python
cloud.add("Python")
print(cloud.tags)
# {'python': 3, 'Python': 1}
```

از نظر منطقی، `"python"` و `"Python"` باید یه تگ حساب بشن. برای رفع این مشکل، همه‌چیز رو موقع ذخیره **و** خوندن، با `lower()` یکدست می‌کنیم:

```python
class TagCloud:
    def __init__(self):
        self.tags = {}

    def add(self, tag):
        tag = tag.lower()
        self.tags[tag] = self.tags.get(tag, 0) + 1
```

## دسترسی با کروشه: `__getitem__` و `__setitem__`

می‌خوایم بشه با `cloud["python"]` تعداد تکرارش رو خوند — دقیقاً مثل یه دیکشنری معمولی، ولی بدون نگرانی از بزرگی/کوچیکی حروف یا کلید ناموجود:

```python
class TagCloud:
    def __init__(self):
        self.tags = {}

    def add(self, tag):
        tag = tag.lower()
        self.tags[tag] = self.tags.get(tag, 0) + 1

    def __getitem__(self, tag):
        return self.tags.get(tag.lower(), 0)

    def __setitem__(self, tag, count):
        self.tags[tag.lower()] = count
```

`__getitem__` وقتی صدا زده می‌شه که از کروشه برای **خوندن** استفاده کنی (`cloud["python"]`)؛ `__setitem__` وقتی از کروشه برای **نوشتن** استفاده کنی (`cloud["python"] = 10`).

## طول کانتینر: `__len__`

```python
def __len__(self):
    return len(self.tags)
```

حالا `len(cloud)` تعداد تگ‌های یکتا رو برمی‌گردونه.

## پیمایش‌پذیرکردن: `__iter__`

برای اینکه بشه با `for` روی `cloud` پیمایش کرد، متد `__iter__` رو تعریف می‌کنیم — که باید یه **شیء تکرارگر** (iterator) برگردونه:

```python
def __iter__(self):
    return iter(self.tags.items())
```

تابع آماده‌ی `iter()` یه شیء تکرارگر از یه پیمایش‌پذیر می‌سازه. اینجا از `self.tags.items()` استفاده می‌کنیم تا هر تکرار، یه تاپل `(تگ، تعداد)` بده:

```python
cloud = TagCloud()
cloud.add("python")
cloud.add("python")
cloud.add("django")

for tag, count in cloud:
    print(tag, count)
# python 2
# django 1
```

## جمع‌بندی

با پیاده‌سازی همین چهار متد جادویی (`__getitem__`، `__setitem__`، `__len__`، `__iter__`)، کلاس `TagCloud` دقیقاً مثل یه کانتینر آماده‌ی پایتون رفتار می‌کنه — با این تفاوت که پیچیدگی خاص خودش (حساس‌نبودن به بزرگی/کوچیکی حروف) رو کاملاً از دید کاربرِ کلاس پنهان کرده.
