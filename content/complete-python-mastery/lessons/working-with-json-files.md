## JSON چیه؟

**JSON** (JavaScript Object Notation) یه فرمت محبوب برای نمایش داده به‌شکل خوانا برای انسانه. خیلی از سرویس‌های معروف (فیسبوک، توییتر، یوتیوب و غیره) داده‌هاشون رو با فرمت JSON در اختیار می‌ذارن — پس آشنایی باهاش خیلی کاربردیه.

```python
import json
```

## تبدیل یه آبجکت پایتونی به رشته‌ی JSON

فرض کن این لیست از دیکشنری‌ها رو داریم:

```python
movies = [
    {"id": 1, "title": "Terminator", "year": 1989},
    {"id": 2, "title": "Kindergarten Cop", "year": 1993},
]
```

با `json.dumps` این آبجکت پایتونی رو به یه رشته با فرمت JSON تبدیل می‌کنیم:

```python
data = json.dumps(movies)
print(data)
```

نکته‌ی جالب: توی این مثال، ساختار JSON دقیقاً شبیه سینتکس لیست/دیکشنری خود پایتونه — ولی این تصادفیه، نه یه قانون کلی. زبان‌های دیگه ممکنه این ساختار رو کمی متفاوت بنویسن.

## ذخیره‌کردن توی فایل

```python
from pathlib import Path

Path("movies.json").write_text(data)
```

## خوندن یه فایل JSON

فرض کن یه فایل `movies.json` از یه منبع دیگه (مثلاً یه API) داریم و می‌خوایم توی پایتون بخونیمش:

```python
data = Path("movies.json").read_text()
movies = json.loads(data)

print(movies)
# [{'id': 1, 'title': 'Terminator', 'year': 1989}, ...]
```

`json.loads` برعکس `json.dumps` عمل می‌کنه: یه رشته‌ی JSON رو می‌گیره و به ساختار پایتونی (لیست، دیکشنری) تبدیلش می‌کنه.

## دسترسی به مقادیر

چون `movies` الان یه لیست از دیکشنریه، با روش‌های معمولی بهش دسترسی داریم:

```python
first_movie = movies[0]
print(first_movie["title"])  # Terminator
```

## جمع‌بندی

- `json.dumps(obj)`: از پایتون به رشته‌ی JSON (**d**ump **s**tring).
- `json.loads(text)`: از رشته‌ی JSON به پایتون (**l**oad **s**tring).

این دو تابع، همراه با `Path.write_text`/`Path.read_text` برای ذخیره و خوندن از فایل، تقریباً همه‌ی چیزیه که برای کار با JSON لازم داری.
