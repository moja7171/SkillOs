> این درس دو ویدیو دارد: بخش نظری و بخش کدنویسی.

## هر شیء یک ارزش درستی دارد

نه فقط int. قاعده: **پیش‌فرض همه‌چیز True است**، به‌جز:

| falsy | مثال |
|---|---|
| `None` | |
| `False` | |
| صفرِ هر نوع عددی | `0`, `0.0`, `0j`, `Fraction(0, 1)`, `Decimal('0')` |
| دنباله‌ی خالی | `[]`, `()`, `''` |
| mapping/مجموعه‌ی خالی | `{}`, `set()`, `frozenset()` |
| کلاس خودت، اگر `__bool__` False یا `__len__` صفر برگرداند | |

```python
bool(10), bool(1.5), bool(Fraction(3, 4)), bool(Decimal('10.5'))   # همه True
bool(0), bool(0.0), bool(Fraction(0, 1)), bool(Decimal('0')), bool(0j)  # همه False
bool([1, 2, 3]), bool('abc'), bool({'a': 1})     # True
bool([]), bool(''), bool({}), bool(set())        # False
bool(None)                                       # False
```

## زیر کاپوت: `__bool__` و `__len__`

وقتی `bool(x)` را صدا می‌زنی، پایتون:
1. اگر کلاس `x` متد `__bool__` دارد، آن را صدا می‌زند و نتیجه‌اش را برمی‌گرداند.
2. وگرنه اگر `__len__` دارد، `len(x) != 0` را برمی‌گرداند.
3. وگرنه `True`.

int این‌طور پیاده شده:

```python
def __bool__(self):
    return self != 0

(100).__bool__()    # True
(0).__bool__()      # False
```

(این متدهای dunder را در بخش شی‌ءگرایی خودمان می‌نویسیم.)

## کاربرد: شرط‌ها

هر عبارتی داخل `if` با ارزش درستی‌اش سنجیده می‌شود. این دو معادل‌اند:

```python
if my_list:
    ...

if my_list is not None and len(my_list) > 0:
    ...
```

اولی هم `None` را رد می‌کند هم لیست خالی را. نسخه‌های ناقص می‌شکنند:

```python
a = ''
if a is not None:
    print(a[0])          # IndexError — رشته‌ی خالی None نیست

a = None
if len(a) > 0:           # TypeError — None طول ندارد
    print(a[0])
```

و **ترتیب** مهم است: `if len(a) > 0 and a is not None` با `a = None` باز هم خطا می‌دهد، چون `len(a)` اول ارزیابی می‌شود. چرا ترتیب نجات می‌دهد — درس بعد: short-circuit.
