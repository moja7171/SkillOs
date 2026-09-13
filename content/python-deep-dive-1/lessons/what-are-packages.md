> این درس دو ویدیو دارد؛ زیرنویس ویدیوی نظری در دسترس نبود و متن از اسلایدها و ویدیوی کدنویسی نوشته شده.

## پکیج یک ماژول است

**پکیج، ماژولی است که می‌تواند ماژول‌ها (و پکیج‌های دیگر — sub-package) را در خود داشته باشد.** هر پکیجی ماژول است؛ هر ماژولی پکیج نیست. تفاوت فنی: پکیج attribute `__path__` دارد.

مثل ماژول‌ها، پکیج‌ها لزوماً موجودیت فایل‌سیستمی نیستند (finder/loader سفارشی)، ولی معمولاً هستند و در این دوره فقط با همان‌ها کار می‌کنیم (اگر کنجکاوی: PEP 302). پکیج یک **سلسله‌مراتب** است و با نقطه نوشته می‌شود: `pack1.mod1`, `pack1.pack1_1.mod1_1`.

## پکیج فایل‌محور: پوشه + `__init__.py`

- یک **پوشه** بساز؛ نامش نام پکیج است.
- داخلش فایل **`__init__.py`** بگذار — این فایل به پایتون می‌گوید «این پوشه پکیج است، نه یک پوشه‌ی معمولی». (بدون آن: implicit namespace package — دو درس بعد.)
- چون پکیج ماژول است و ماژول کد دارد، **کد پکیج داخل `__init__.py`** است — نمی‌شود کد را «داخل پوشه» نوشت.

```
app/
    module1.py
    pack1/
        __init__.py
        module1a.py
        module1b.py
        pack1_1/
            __init__.py
            module1_1a.py
            module1_1b.py
```

```python
# pack1/__init__.py
print('executing pack1...')
value = 'pack1 value'
```

```python
import pack1                # executing pack1...   ← __init__.py ran
pack1.value                 # 'pack1 value'
type(pack1)                 # module — same type as module1
```

### `__file__`, `__path__`, `__package__`

| | `module1` | `pack1` | `pack1.module1a` | `pack1.pack1_1` |
|---|---|---|---|---|
| `__file__` | `…/app/module1.py` | `…/app/pack1/__init__.py` | `…/app/pack1/module1a.py` | `…/app/pack1/pack1_1/__init__.py` |
| `__path__` | ندارد | `['…/app/pack1']` | ندارد | `['…/app/pack1/pack1_1']` |
| `__package__` | `''` | `'pack1'` | `'pack1'` | `'pack1.pack1_1'` |

`__package__` می‌گوید ماژول در کدام پکیج است (`''` = ریشه‌ی برنامه). `__path__` پوشه‌ی پکیج است و `PathFinder` برای پیدا کردن زیرماژول‌ها در آن می‌گردد.

## import تودرتو

```python
import pack1_1               # ModuleNotFoundError — finders only search sys.path, they don't dig into folders
import pack1.pack1_1         # executing pack1... / executing pack1_1...
```

`import pack1.pack1_1.module1_1a` سه چیز را به ترتیب import می‌کند: `pack1`، `pack1.pack1_1`، `pack1.pack1_1.module1_1a`. در `sys.modules` **سه** کلید با همین نام‌های نقطه‌دار ثبت می‌شود. ولی در namespace ما فقط **`pack1`** اضافه می‌شود:

```python
'pack1.pack1_1' in sys.modules       # True
'pack1_1' in globals()               # False
'pack1.pack1_1' in globals()         # False
pack1.pack1_1.module1_1a.value       # works: attribute chain from the symbol pack1
```

منطقی است: به زیرماژول از طریق نقطه می‌رسی. اگر نام کوتاه می‌خواهی:

```python
from pack1 import pack1_1            # now pack1_1 is a symbol in our namespace
pack1_1 is sys.modules['pack1.pack1_1']    # True
```

## import پکیج، زیرماژول‌ها را import نمی‌کند

```python
import pack1.pack1_1
pack1.pack1_1.module1_1a.value       # AttributeError: no attribute 'module1_1a'
```

فقط `__init__.py`های مسیر اجرا شده‌اند. زیرماژول‌ها فقط وقتی بار می‌شوند که صریحاً import شوند — یا `__init__.py` این کار را بکند:

```python
# pack1/pack1_1/__init__.py
print('executing pack1_1...')
import pack1.pack1_1.module1_1a      # full dotted path — `import module1_1a` would NOT be found
import pack1.pack1_1.module1_1b
```

```python
import pack1.pack1_1
# executing pack1... / executing pack1_1... / executing module1_1a... / executing module1_1b...
pack1.pack1_1.module1_1b.value       # works now
```

و همین‌طور `pack1/__init__.py` می‌تواند `import pack1.pack1_1` کند تا با `import pack1` کل درخت بار شود. این یکی از کاربردهای اصلی `__init__.py` است: تعیین اینکه با import پکیج چه چیزهایی در دسترس باشد. (راه کوتاه‌تر با relative import در درس «ساختاردهی پکیج».)
