## ترکیب چند شرط

سه تا عملگر منطقی داریم: `and`، `or` و `not`. باهاشون می‌تونی شرط‌های پیچیده‌تر بسازی. یه مثال واقعی: فرض کن داری یه برنامه برای بررسی وام می‌نویسی.

```python
high_income = True
good_credit = True

if high_income and good_credit:
    print("Eligible")
```

قانون: اگه متقاضی هم درآمد بالا داشته باشه هم اعتبار خوب، واجد شرایطه.

> نکته: اینجا `high_income` خودش یه بولیه — لازم نیست بنویسی `if high_income == True`. این کار هم زائده هم غیرحرفه‌ای؛ چون `high_income` خودش همین الانشم `True` یا `False`ه.

## `and` در مقابل `or`

- با `and`: نتیجه فقط وقتی `True`ه که **هر دو طرف** `True` باشن.
- با `or`: نتیجه `True`ه اگه **حداقل یکی** از طرف‌ها `True` باشه.

```python
high_income = False
good_credit = True

print(high_income and good_credit)  # False -- one side was False
print(high_income or good_credit)   # True -- at least one side was True
```

## `not`

`not` مقدار بولی رو معکوس می‌کنه:

```python
student = True
print(not student)  # False

student = False
print(not student)  # True
```

مثلاً اگه بخوایم بگیم «واجد شرایطه اگه دانشجو **نباشه**»:

```python
if not student:
    print("Eligible")
```

## ترکیب چندتایی

می‌تونی این سه‌تا رو باهم ترکیب کنی تا شرط‌های واقعی‌تری بسازی. فرض کن قانون اینه: «واجد شرایطه اگه درآمد بالا **یا** اعتبار خوب داشته باشه، **و** دانشجو نباشه»:

```python
high_income = True
good_credit = False
student = False

if (high_income or good_credit) and not student:
    print("Eligible")
```

پرانتز اینجا فقط برای خوانایی نیست، لازمه! پایتون `and` رو قبل از `or` حساب می‌کنه؛ یعنی اگه پرانتز رو برداری، `high_income or good_credit and not student` می‌شه `high_income or (good_credit and not student)` — یه قانون کاملاً متفاوت («واجد شرایطه اگه درآمد بالا داشته باشه، یا اگه اعتبار خوب داشته باشه و دانشجو نباشه»، نه اون قانونی که می‌خواستیم). پس وقتی می‌خوای `or` رو زودتر از `and` حساب کنی، حتماً دورش پرانتز بذار.
