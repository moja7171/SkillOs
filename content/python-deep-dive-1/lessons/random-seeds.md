## اعداد تصادفی، تکرارپذیر

ماژول `random` اعداد **شبه‌تصادفی** تولید می‌کند: با الگوریتمی که به یک **seed** وابسته است. seed یکسان → دنباله‌ی یکسان. پیش‌فرض seed از زمان سیستم می‌آید، پس هر اجرا فرق دارد.

چرا مهم است؟ باگی که وسط یک اجرای تصادفی رخ می‌دهد، **تکرارپذیر** نیست — بدترین نوع باگ برای debug. با seed ثابت، همان دنباله را دوباره می‌سازی.

```python
import random

for _ in range(3):
    print(random.randint(10, 20), random.random())    # different every run

random.seed(0)
for _ in range(3):
    print(random.randint(10, 20), random.random())
# 16 0.7579544029403025
# 16 0.04048437818077755
# 18 0.48592769656281265

for _ in range(3):
    print(random.randint(10, 20), random.random())    # continues the sequence — different numbers

random.seed(0)                                        # reset → same as the first block again
```

seed را یک بار در **ابتدای برنامه** بگذار تا کل اجرا تکرارپذیر شود؛ یا قبل از بلوکی که می‌خواهی بازتولید کنی، دوباره تنظیمش کن.

## همه‌ی توابع random تحت تأثیرند

`shuffle`، `gauss`، `choice` … همه از همان generator می‌خوانند:

```python
def generate_random_stuff(seed=None):
    random.seed(seed)
    results = []
    for _ in range(5):
        results.append(random.randint(0, 5))
    characters = ['a', 'b', 'c']
    random.shuffle(characters)                # shuffles the same way for the same seed
    results.append(characters)
    for _ in range(5):
        results.append(random.gauss(0, 1))    # normal distribution, also seeded
    return results

generate_random_stuff()       # random each time (seed=None → system time)
generate_random_stuff(0)      # [3, 3, 0, 2, 4, ['c', 'a', 'b'], ...]  — identical on every call
generate_random_stuff(100)    # a different but repeatable sequence
```

## نکته‌ها

- seed هر مقدار hashable می‌تواند باشد (int، str، bytes)؛ رایج: یک عدد ثابت در تست‌ها/آزمایش‌ها.
- برای چند دنباله‌ی مستقل، instance جدا بساز: `rng = random.Random(42); rng.randint(...)` — seed سراسری را دست نمی‌زند.
- برای امنیت (توکن، رمز) از `random` استفاده نکن؛ ماژول `secrets`.
