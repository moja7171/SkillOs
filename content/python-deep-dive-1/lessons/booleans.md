## bool زیرکلاس int است (PEP 285)

```python
issubclass(bool, int)          # True
isinstance(True, bool)         # True
isinstance(True, int)          # True
```

پس bool همه‌ی عملیات int را دارد (`+`, `-`, `//`, `%`, …) به‌علاوه‌ی چند عملگر خودش (`and`, `or`, `xor`).

## True و False: دو singleton

`True` فقط یک نام است برای شیئی از نوع `bool` با مقدار درونی `1`؛ `False` همین با `0`. **singleton** هستند — در کل عمر برنامه یک شیء، یک آدرس. نتیجه: برای مقایسه با آن‌ها `is` و `==` هم‌ارزند:

```python
id(True), id(3 < 4)       # یکسان — نتیجه‌ی مقایسه همان شیء True است
(3 < 4) == True           # True
(3 < 4) is True           # True
```

## True «برابر» ۱ است، ولی «همان» ۱ نیست

```python
int(True), int(False)     # (1, 0)
True == 1                 # True   — مقدار
True is 1                 # False  — شیء متفاوت، نوع متفاوت
id(True) == id(1)         # False
```

## آفتاب‌پرست: گاهی bool، گاهی int

چون int است، هر عمل عددی روی آن مجاز است — نه لزوماً کد خوبی، ولی کار می‌کند:

```python
True > False              # True   (1 > 0)
(1 == 2) == False         # True
(1 == 2) == 0             # True   — False همان 0
True + True + True        # 3
(True + True + True) % 2  # 1
-True                     # -1
100 * False               # 0
```

پرانتز در `(1 == 2) == False` مهم است — بدون آن یک **زنجیره‌ی مقایسه** می‌شود با معنی متفاوت (درس عملگرهای مقایسه).

## سازنده‌ی bool و «ارزش درستی»

`bool(x)` برای `True`، True و برای `False`، False برمی‌گرداند — بی‌فایده به نظر می‌رسد، ولی نکته این است که **هر شیء در پایتون یک ارزش درستی (truth value / truthiness) دارد** و `bool(x)` همان را می‌پرسد. برای int قاعده ساده است:

```python
bool(0)        # False
bool(1)        # True
bool(100)      # True
bool(-1)       # True   ← در پایتون −1 «غلط» نیست، برخلاف بعضی زبان‌ها
```

`bool(0)` «تبدیل int به bool» نیست؛ پرسیدن ارزش درستی ۰ است. سازوکارش در درس بعد.
