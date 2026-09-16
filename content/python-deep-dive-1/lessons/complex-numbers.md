> این درس دو ویدیو دارد: بخش نظری و بخش کدنویسی. اگر با اعداد مختلط کار نمی‌کنی، می‌توانی ردش کنی.

## ساخت

کلاس built-in `complex` (بدون import). مختصات **مستطیلی**: بخش حقیقی و موهومی. literal با `j` (یا `J`):

```python
a = complex(1, 2)
b = 1 + 2j
a == b            # True

a.real            # 1.0   ← float!
a.imag            # 2.0
type(a.real)      # <class 'float'>
a.conjugate()     # (1-2j)  — a method, not a property
```

بخش حقیقی و موهومی **float** ذخیره می‌شوند — با همه‌ی مشکلات float، حالا در دو بُعد.

## عملگرها

`+ - * / **` طبق حساب مختلط کار می‌کنند، از جمله در ترکیب با اعداد حقیقی:

```python
a = 1 + 2j; b = 10 + 8j
a + b     # (11+10j)
a * b     # (-6+28j)
a / b
a ** 2    # (-3+4j)
a + 2     # (3+2j)
```

**پشتیبانی نمی‌شود:** `//` و `%` (floor یک عدد مختلط معنی ندارد) و مقایسه‌های ترتیبی `< >` (ترتیب اعداد مختلط تعریف‌شده نیست). `==` و `!=` هست ولی با همان دام float:

```python
a = 0.1j
format(a.imag, '.25f')       # '0.1000000000000000055511151'
a + a + a == 0.3j            # False
```

## ماژول cmath

`math` روی complex کار نمی‌کند (`math.sqrt(1+2j)` → TypeError). معادلش **`cmath`** است: `sqrt`, `exp`, `log`, مثلثاتی و هذلولوی و معکوس‌هایشان، تبدیل قطبی/مستطیلی، و `isclose`. `cmath.pi` همان `math.pi` است (float) — برای راحتی.

## قطبی ↔ مستطیلی

```python
import cmath, math

a = 1 + 1j
cmath.phase(a)      # 0.7853981633974483  ≈ π/4 — the angle, in (−π, π]
abs(a)              # 1.4142135623730951  ≈ √2 — the magnitude (plain abs, polymorphic)

cmath.phase(-1 + 0j)     # π
cmath.phase(-1j)         # −π/2

cmath.rect(math.sqrt(2), math.pi / 4)    # (1.0000000000000002+1j)  ← approximate
```

در `rect` از `math.sqrt` استفاده کن نه `cmath.sqrt` — دومی complex برمی‌گرداند و `rect` برای `r` عدد حقیقی می‌خواهد.

## اتحاد اویلر و isclose

`e^{iπ} + 1 = 0`:

```python
rhs = cmath.exp(cmath.pi * 1j) + 1
rhs                             # 1.2246467991473532e-16j  ← not exactly zero
cmath.isclose(rhs, 0)           # False! — the default abs_tol is zero
cmath.isclose(rhs, 0, abs_tol=0.0001)   # True
```

همان درس float: نزدیک صفر، بدون `abs_tol` هیچ‌وقت «نزدیک» نمی‌شود.
