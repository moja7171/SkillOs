## for: پیمایش روی یک دنباله

```python
fruits = ["apple", "banana", "cherry"]
for fruit in fruits:
    print(fruit)

for i in range(3):
    print(i)   # 0, 1, 2
```

`for` روی هر چیزی که **iterable** باشد کار می‌کند: لیست، رشته، `range`، دیکشنری.

## while: تکرار تا برقراری شرط

```python
count = 0
while count < 3:
    print(count)
    count += 1
```

اگر `count += 1` را فراموش کنی، شرط هیچ‌وقت `False` نمی‌شود و حلقه **بی‌پایان** می‌ماند.

## break و continue

```python
for n in range(10):
    if n == 5:
        break        # حلقه تمام می‌شود
    if n % 2 == 0:
        continue     # زوج‌ها را رد کن
    print(n)         # 1, 3
```
