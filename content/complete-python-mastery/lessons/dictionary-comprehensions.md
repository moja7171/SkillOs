## comprehension فقط برای لیست نیست

قبلاً list comprehension رو دیدی. همون ایده روی **مجموعه** و **دیکشنری** هم کار می‌کنه — فقط باید کروشه رو با آکولاد عوض کنی.

## از حلقه به Set Comprehension

راه پایه:

```python
values = []
for x in range(5):
    values.append(x * 2)
```

با list comprehension:

```python
values = [x * 2 for x in range(5)]
```

اگه به‌جای کروشه از آکولاد استفاده کنی، به‌جای لیست یه **مجموعه** می‌گیری:

```python
values = {x * 2 for x in range(5)}
print(values)
# {0, 2, 4, 6, 8}
```

## Dictionary Comprehension

فرق نحوی مجموعه و دیکشنری اینه: توی دیکشنری، به‌جای فقط یه مقدار، یه **جفت کلید-مقدار** با `:` می‌ذاری:

```python
squares = {x: x * x for x in range(5)}
print(squares)
# {0: 0, 1: 1, 2: 4, 3: 9, 4: 16}
```

اینجا هر عدد (`x`) کلیده، و مربعش (`x * x`) مقداره. همون الگویی که قبلاً با map/list comprehension می‌دیدیم، این‌بار برای ساختن دیکشنری.

## کِی به‌کارش ببریم

هر جا که یه حلقه داری که یه دیکشنری خالی می‌سازه و بعد توش پر می‌کنه، احتمالاً می‌تونی با یه dictionary comprehension یک‌خطیش کنی:

```python
# before
squares = {}
for x in range(5):
    squares[x] = x * x

# after
squares = {x: x * x for x in range(5)}
```

## و اگه از پرانتز استفاده کنیم؟

```python
values = (x * 2 for x in range(5))
print(type(values))
# <class 'generator'>
```

اینجا نه لیست می‌گیری نه تاپل — یه **generator** می‌گیری. موضوع درس بعدی همینه.
