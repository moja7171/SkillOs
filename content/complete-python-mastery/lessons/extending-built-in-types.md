## وراثت از انواع آماده‌ی پایتون

تا اینجا از کلاس‌های خودمون به‌عنوان والد استفاده کردیم، ولی می‌شه از انواع **آماده‌ی** پایتون (مثل `str` یا `list`) هم ارث برد و بهشون قابلیت اضافه کرد.

## گسترش `str`

```python
class Text(str):
    def duplicate(self):
        return self + self
```

`Text` از `str` ارث می‌بره، پس همه‌ی متدهای رشته رو داره — به‌علاوه‌ی یه متد جدید خودمون، `duplicate`:

```python
word = Text("Python")

print(word.lower())      # python -- built-in str method
print(word.duplicate())  # PythonPython -- our own method
```

اینجا `self` همون رشته‌ی فعلیه (چون `Text` خودش یه `str`ه)، پس `self + self` یعنی رشته رو با خودش الحاق کن.

## گسترش `list`: بازنویسیِ گسترشی (نه جایگزین)

می‌شه از `list` هم ارث برد. فرض کن می‌خوایم هر بار که یه عضو به لیست اضافه می‌شه، یه پیام لاگ چاپ بشه:

```python
class TrackableList(list):
    def append(self, item):
        print("append called")
        super().append(item)
```

اینجا متد `append` رو بازنویسی کردیم، ولی به‌جای جایگزین‌کردنش، با `super().append(item)` رفتار اصلی `list.append` رو هم صدا می‌زنیم — یعنی داریم **گسترشش** می‌دیم، نه جایگزینش می‌کنیم (دقیقاً همون الگویی که توی درس بازنویسی متد با `super()` دیدیم).

```python
tl = TrackableList()
tl.append(1)
# append called
print(tl)  # [1]
```

## جمع‌بندی

توسعه‌ی انواع درونی پایتون همون قواعد وراثت معمولیه: با ارث‌بری از `str`، `list`، `dict` و بقیه، می‌شه رفتار آماده‌شون رو حفظ کرد و رفتار دلخواه اضافه کرد — بدون اینکه از صفر یه ساختار داده بنویسیم.
