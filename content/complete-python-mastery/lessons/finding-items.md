## پیداکردن ایندکس یه مقدار

```python
letters = ["a", "b", "c"]

print(letters.index("a"))   # 0
```

`index()` ایندکس اولین جایی که مقدار مورد نظر پیدا می‌شه رو برمی‌گردونه.

## چی می‌شه اگه پیدا نشه؟

```python
letters.index("d")   # ValueError: 'd' is not in list
```

برخلاف خیلی از زبان‌های دیگه (که معمولاً `-1` برمی‌گردونن)، پایتون یه **خطا** پرتاب می‌کنه. برای جلوگیری از این خطا، اول با عملگر `in` چک کن که مقدار توی لیست هست یا نه:

```python
if "d" in letters:
    print(letters.index("d"))
else:
    print("not found")
```

## شمردن تعداد تکرار

اگه بخوای بدونی یه مقدار چندبار توی لیست تکرار شده، از `count()` استفاده کن:

```python
letters = ["a", "b", "a", "c", "a"]

print(letters.count("a"))   # 3
print(letters.count("z"))   # 0
```

برخلاف `index()`، اگه مقدار پیدا نشه `count()` خطا نمی‌ده — فقط `0` برمی‌گردونه.
