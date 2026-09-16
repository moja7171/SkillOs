## چهار خانواده‌ی عملگر مقایسه

همه دوتایی‌اند (دو عملوند) و **bool** برمی‌گردانند:

| خانواده | عملگرها | روی چه چیزی |
|---|---|---|
| هویت | `is`, `is not` | هر نوعی — آدرس حافظه |
| مقدار | `==`, `!=` | نوع‌های سازگار؛ همه‌ی انواع عددی از جمله complex |
| ترتیب | `<`, `>`, `<=`, `>=` | همه‌ی انواع عددی **به‌جز complex**؛ رشته‌ها (lexicographic) |
| عضویت | `in`, `not in` | iterableها (بعداً مفصل) |

## مقایسه‌ی بین انواع عددی

مقایسه‌ی مقدار و ترتیب بین انواع مختلف مجاز است:

```python
10.0 == Decimal('10.0')            # True  — 10.0 is exact in float
0.1 == Decimal('0.1')              # False — 0.1 is approximate in float
Decimal('0.125') == Fraction(1, 8) # True
True == 1, True == Fraction(3, 3)  # True, True

1 < 3.14                           # True
Fraction(22, 7) > math.pi          # True
Decimal('0.5') < Fraction(2, 3)    # True
True < Decimal('3.14')             # True   (1 < 3.14)
Fraction(2, 3) > False             # True   (> 0)

(1 + 1j) < (3 + 4j)                # TypeError
4 == 4 + 0j                        # True
```

همان احتیاط float اینجا هم هست: `==` بین float و Decimal (یا float و float) فقط وقتی قابل اعتماد است که float دقیق باشد.

## عضویت

```python
'a' in 'this is a test'        # True
3 in [1, 2, 3]                 # True
'key1' in {'key1': 1}          # True  — checks keys
1 in {'key1': 1}               # False — not values
```

## زنجیره‌ی مقایسه

`a < b < c` **دقیقاً** یعنی `a < b and b < c`. پایتون هر جفت مجاور را با `and` به هم می‌بندد — و چون `and` است، **short-circuit** هم دارد:

```python
1 == Decimal('1.0') == Fraction(1, 1)     # True
1 == Decimal('1.5') == Fraction(3, 2)     # False (the first pair is false)
1 < math.pi < Fraction(22, 7)             # True

3 < 2 < 1/0        # False — 3 < 2 was false, 1/0 isn't evaluated
3 < 4 < 1/0        # ZeroDivisionError
```

عملگرها لازم نیست یکی باشند:

```python
5 < 6 > 2          # True  (5 < 6 and 6 > 2)
5 < 6 > 10         # False
1 < 2 > -5 == Decimal('-5.0')     # True
'A' < 'a' < 'z' > 'Z' in string.ascii_letters   # True — but don't do this
```

کجا واقعاً می‌درخشد: بازه.

```python
if MIN < age < MAX: ...
```

و کجا نه: `if my_min == cnt < val > other <= my_max not in lst` — درست است، ولی هیچ‌کس نمی‌خواندش. (این هم توضیح آن پرانتز درس bool: `1 == 2 == False` یعنی `1 == 2 and 2 == False` → False.)
