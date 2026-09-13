> این درس دو ویدیو دارد: معرفی بخش و «ماژول چیست».

## این بخش درباره‌ی چیست

ماژول‌ها (اشیائی از نوع `ModuleType`)، اینکه پایتون چطور آنها را پیدا و بارگذاری می‌کند (و شبیه‌سازی‌اش با کد)، cache سراسری ماژول‌ها، reload و چرا خطرناک است، انواع `import` و سوءبرداشت‌ها، `__main__`، import از فایل zip، پکیج‌ها و نقش `__init__.py`، و namespace packageها.

## ماژول یک شیء است

مثل هر چیز دیگر در پایتون: تابع instance از `function` است، ماژول instance از `types.ModuleType`.

```python
import math, fractions, types

type(math)                                 # <class 'module'>
math                                       # <module 'math' (built-in)>
fractions                                  # <module 'fractions' from '.../lib/python3.x/fractions.py'>
isinstance(fractions, types.ModuleType)    # True
```

`math` با C نوشته شده و **built-in** است؛ `fractions` بخشی از کتابخانه‌ی استاندارد است که با پایتون نوشته شده و `__file__` دارد (فایل `.py` در پوشه‌ی `lib` نصب پایتون — با virtual env، در همان env).

## namespaceها dict هستند

`globals()` دیکشنری namespace سراسری ماژول جاری است؛ `locals()` داخل تابع، namespace محلی:

```python
def func():
    a = 10
    b = 20
    print(locals())          # {'a': 10, 'b': 20}

globals()['func'] is func    # True — the name→object table itself
f = globals()['func']; f()
```

در سطح ماژول، `locals()` همان `globals()` است.

`import math` دو کار می‌کند: ماژول را در حافظه می‌سازد (اگر قبلاً نساخته) و نام `math` را در `globals()` به آن شیء bind می‌کند. `math` یک متغیر معمولی است: `junk = math; junk.sqrt(2)`.

## cache ماژول‌ها: `sys.modules`

```python
import sys
type(sys.modules)                              # dict
id(sys.modules['math']) == id(math)            # True
import math                                    # again — same id: not reloaded
```

هر ماژول فقط یک بار بارگذاری می‌شود و همه‌ی ماژول‌های برنامه به همان شیء اشاره می‌کنند (عملاً singleton).

## درون ماژول

```python
math.__name__            # 'math'
math.__dict__['sqrt']    # the sqrt function; math.sqrt is a lookup in this dict
fractions.__file__       # path of fractions.py
fractions.__spec__       # ModuleSpec(name='fractions', loader=..., origin='...')
```

`__dict__` namespace ماژول است (همان `globals()` از داخل خودش). `__spec__` متادیتا: نام، loader (built-in، SourceFileLoader، …)، origin.

## ماژول‌ها لزوماً از فایل نمی‌آیند

یک ماژول: شیئی با namespace، که می‌شود در آن کد اجرا کرد. پس می‌توانیم دستی بسازیم:

```python
from types import ModuleType

mod = ModuleType('test', 'This is a test module.')
mod.__dict__            # {'__name__': 'test', '__doc__': '...', '__package__': None, '__loader__': None, '__spec__': None}

mod.pi = 3.14
mod.hello = lambda: 'Hello!'
mod.hello()             # 'Hello!'

from collections import namedtuple
mod.Point = namedtuple('Point', 'x y')
p = mod.Point(0, 0)

hello = mod.hello       # mimics: from mod import hello
getattr(mod, 'Point') is mod.__dict__['Point']   # True — the dot is a dict lookup
```

پس ماژول = نوع داده‌ای معمولی + namespace (container متغیرهای global) + محیط اجرا. فایل فقط یکی از منابع کد است؛ درس بعد می‌بینیم import دقیقاً چه می‌کند.
