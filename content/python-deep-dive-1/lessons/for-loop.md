## for در پایتون یعنی پیمایش

در C و جاوا `for` یک شمارنده دارد: `for (i = 0; i < 5; i++)`. در پایتون **چنین چیزی وجود ندارد**. `for` روی یک **iterable** پیمایش می‌کند — هر بار یک عنصر می‌دهد تا تمام شود:

```python
for i in range(5):
    print(i)             # 0 1 2 3 4

for c in "hello":
    print(c)             # h e l l o

for x in [1, 2, 3]:
    print(x)
```

`range(5)` هم فقط یک iterable است که اعداد ۰ تا ۴ را تولید می‌کند؛ `for` خودش نمی‌شمارد.

## enumerate: عنصر + ایندکس

اگر ایندکس هم لازم داری، شمارنده‌ی دستی نساز:

```python
s = "hello"
for i, c in enumerate(s):
    print(i, c)          # 0 h, 1 e, ...
```

`enumerate` هر بار یک tuple `(index, item)` می‌دهد و `i, c` آن را باز می‌کند (unpacking — بعداً مفصل).

## else و break/continue

مثل `while`: `else` حلقه‌ی `for` **فقط وقتی اجرا می‌شود که حلقه بدون `break` تمام شود**:

```python
for i in range(1, 5):
    print(i)
    if i % 7 == 0:
        print("multiple of 7 found")
        break
else:
    print("No multiples of 7 encountered")
```

`continue`, `break` و `try/finally` دقیقاً همان رفتاری را دارند که در درس قبل دیدی.

## متغیر حلقه بعد از حلقه زنده می‌ماند

```python
for i in range(3):
    pass
print(i)     # 2
```

`i` یک متغیر معمولی در همان دامنه است، نه چیزی محلیِ حلقه. بعد از حلقه، آخرین مقدار را دارد.
