## SQLite: یه پایگاه‌داده‌ی سبک

**SQLite** یه پایگاه‌داده‌ی خیلی سبکه که معمولاً برای برنامه‌های کوچیک (مثل اپ‌های موبایل) استفاده می‌شه. برخلاف CSV یا JSON، داده رو به‌شکل ساخت‌یافته (جدول‌هایی با سطر و ستون) ذخیره می‌کنه.

```python
import sqlite3
```

## اتصال به پایگاه‌داده

```python
with sqlite3.connect("db.sqlite3") as conn:
    ...
```

`connect` یه آبجکت اتصال (connection) برمی‌گردونه. اگه فایل پایگاه‌داده وجود نداشته باشه، `connect` خودش می‌سازتش — ولی این فقط فایل خالی رو می‌سازه، نه هیچ جدولی داخلش.

## ساخت یه جدول

قبل از نوشتن یا خوندن داده، باید مطمئن باشیم جدول موردنظر وجود داره:

```python
with sqlite3.connect("db.sqlite3") as conn:
    conn.execute("""
        CREATE TABLE IF NOT EXISTS movies (
            id INTEGER PRIMARY KEY,
            title TEXT NOT NULL,
            year INTEGER NOT NULL
        )
    """)
```

اگه بدون داشتن این جدول مستقیم بریم سراغ نوشتن داده، با `sqlite3.OperationalError: no such table: movies` مواجه می‌شیم.

## نوشتن داده با placeholder

فرض کن این لیست از فیلم‌ها رو (مثلاً از یه فایل JSON) داریم:

```python
movies = [
    {"id": 1, "title": "Terminator", "year": 1989},
    {"id": 2, "title": "Kindergarten Cop", "year": 1993},
]
```

برای درج داده، به‌جای چسبوندن مستقیم مقادیر توی رشته‌ی SQL (که خطرناکه)، از **placeholder** (`?`) استفاده می‌کنیم:

```python
with sqlite3.connect("db.sqlite3") as conn:
    command = "INSERT INTO movies VALUES (?, ?, ?)"

    for movie in movies:
        conn.execute(command, tuple(movie.values()))

    conn.commit()
```

هر `?` جای یکی از مقادیری هست که به‌عنوان آرگومان دوم `execute` می‌فرستیم. `movie.values()` مقادیر دیکشنری رو می‌ده و `tuple(...)` اون‌ها رو به یه تاپل تبدیل می‌کنه. بعد از همه‌ی درج‌ها، `conn.commit()` رو صدا می‌زنیم تا تغییرات واقعاً روی دیسک ذخیره بشن.

## خوندن داده

```python
with sqlite3.connect("db.sqlite3") as conn:
    cursor = conn.execute("SELECT * FROM movies")

    for row in cursor:
        print(row)
```

`execute` روی یه دستور `SELECT`، یه آبجکت **cursor** برمی‌گردونه — که خودش پیمایش‌پذیره و هر سطر رو به‌شکل یه تاپل می‌ده.

می‌شه هم با `fetchall()` همه‌ی سطرها رو یه‌جا به‌شکل یه لیست گرفت:

```python
cursor = conn.execute("SELECT * FROM movies")
rows = cursor.fetchall()
```

نکته: دقیقاً مثل `csv.reader`، یه `cursor` رو فقط یه‌بار می‌شه پیمایش کرد — اگه اول `fetchall()` بگیری، دیگه چیزی برای پیمایش با `for` باقی نمی‌مونه.

## `commit` فقط برای نوشتن لازمه

توجه کن که برای خوندن داده (`SELECT`)، نیازی به `conn.commit()` نیست — `commit` فقط وقتی لازمه که داده رو تغییر می‌دیم (درج، به‌روزرسانی، حذف).

## جمع‌بندی

کار با SQLite توی پایتون شامل چهار مرحله‌ست: اتصال (`connect`)، اجرای دستور SQL (`execute`) با placeholder برای مقادیر، `commit`کردن تغییرات (فقط برای نوشتن)، و خوندن نتیجه از `cursor`. یادگیری خود زبان SQL خارج از محدوده‌ی این دوره‌ست، ولی نسبت به پایتون یادگیریش خیلی ساده‌تره.
