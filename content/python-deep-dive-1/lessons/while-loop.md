## while: تا وقتی شرط برقرار است

```python
i = 0
while i < 5:
    print(i)
    i += 1
```

شرط **قبل از هر دور** چک می‌شود؛ اگر از همان اول `False` باشد، بدنه هیچ‌وقت اجرا نمی‌شود. پایتون حلقه‌ی `do...while` (اجرا، بعد چک) ندارد؛ الگوی جایگزینش این است:

```python
while True:
    name = input("Name: ")
    if len(name) >= 2 and name.isprintable() and name.isalpha():
        break
print(f"Hello, {name}")
```

`while True` + `break` یعنی «حداقل یک بار اجرا کن و هر وقت شرط خروج برقرار شد بیرون بیا». این الگو در پایتون کاملاً رایج و پذیرفته‌شده است.

## بند else حلقه

چیزی که کمتر کسی می‌داند: `while` (و `for`) می‌توانند `else` داشته باشند. بدنه‌ی `else` **فقط وقتی اجرا می‌شود که حلقه بدون `break` تمام شود**:

```python
numbers = [1, 2, 3]
value = 10

i = 0
while i < len(numbers):
    if numbers[i] == value:
        break
    i += 1
else:
    numbers.append(value)     # only if not found

print(numbers)   # [1, 2, 3, 10]
```

اسم `else` گمراه‌کننده است؛ بهتر است آن را «`nobreak`» بخوانی: «اگر break نشد، این را انجام بده». کاربرد کلاسیکش همین «جست‌وجو کن، اگر پیدا نشد کاری بکن» است — بدون نیاز به یک flag اضافه مثل `found = False`.
