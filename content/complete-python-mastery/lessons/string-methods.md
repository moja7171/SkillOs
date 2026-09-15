## همه‌چی توی پایتون یه «آبجکت»ـه

اگه توی VS Code بعد از یه متغیر رشته‌ای نقطه بذاری (مثلاً `course.`)، یه لیست بلندبالا از کارایی که می‌تونی باهاش انجام بدی می‌بینی. به این کارها می‌گیم **متد (method)** — یه اسم دقیق‌تر برای تابع، وقتی که متعلق به یه آبجکته. فعلاً کافیه بدونی: همه‌چی توی پایتون یه آبجکته، و آبجکت‌ها متد دارن که با نقطه بهشون دسترسی داری. جزئیاتش رو بعداً توی بخش برنامه‌نویسی شی‌گرا کامل یاد می‌گیری.

بیا چندتا از پرکاربردهاشون رو ببینیم:

```python
course = "Python Programming"

print(course.upper())    # PYTHON PROGRAMMING
print(course.lower())    # python programming
print(course.title())    # Python Programming
```

نکته‌ی مهم: این متدها رشته‌ی اصلی رو تغییر نمی‌دن، یه رشته‌ی **جدید** برمی‌گردونن. اگه بعدش دوباره `print(course)` بزنی، می‌بینی هنوز همون رشته‌ی اولشه.

## strip — پاک‌کردن فاصله‌های اضافه

خیلی وقتا کاربر یه فضای خالی اضافه تایپ می‌کنه؛ `strip` این فضاهای اضافه رو از اول و آخر رشته حذف می‌کنه (`lstrip` فقط از چپ، `rstrip` فقط از راست):

```python
course = "   Python Programming   "
print(course.strip())   # "Python Programming"
```

## find — دنبال یه چیزی بگرد

```python
course = "Python Programming"
print(course.find("Pro"))   # 7
print(course.find("pro"))   # -1
```

خط اول ایندکسی که «Pro» ازش شروع می‌شه رو می‌ده. خط دوم `-1` می‌ده چون «pro» با حروف کوچیک پیدا نشد — حروف بزرگ/کوچیک مهمه!

## replace — جایگزین کن

```python
print(course.replace("Python", "Java"))   # Java Programming
```

## in و not in — فقط می‌خوای بدونی هست یا نه؟

```python
print("Python" in course)       # True
print("Swift" not in course)    # True
```

فرقش با `find` اینه: `find` ایندکس رو می‌ده، `in`/`not in` فقط یه `True`/`False` می‌ده — یعنی یه عبارت بولیه.
