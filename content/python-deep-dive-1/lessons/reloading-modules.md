## چرا بحث reload؟

فرض کن برنامه در حال اجراست (مثلاً نوت‌بوک یا REPL) و فایل یک ماژول را روی دیسک عوض می‌کنی. `import test` دوباره کاری نمی‌کند — از `sys.modules` برمی‌گردد. چطور نسخه‌ی جدید را بگیریم، و چرا نباید در برنامه‌ی واقعی این کار را کرد؟

برای آزمایش، تابعی که یک فایل ماژول با یک تابع `print_values` می‌نویسد:

```python
import os

def create_module_file(module_name, **kwargs):
    module_file_name = f'{module_name}.py'
    module_abs_file_path = os.path.abspath(module_file_name)
    with open(module_abs_file_path, 'w') as f:
        f.write(f'# {module_name}.py\n\n')
        f.write(f"print('running {module_file_name}...')\n\n")
        f.write('def print_values():\n')
        for key, value in kwargs.items():
            f.write(f"\tprint('{key}', '{value}')\n")

create_module_file('test', k1=10, k2='python')
import test                     # running test.py...
test.print_values()             # k1 10 / k2 python
```

حالا فایل را عوض کن:

```python
create_module_file('test', k1=10, k2='python', k3='cheese')
import test                     # nothing — cached
test.print_values()             # still k1, k2
```

## راه ۱ (بد): حذف از `sys.modules`

```python
import sys
id(test)                        # 0x...3128
del sys.modules['test']
import test                     # running test.py...
id(test)                        # 0x...283  — a NEW module object
test.print_values()             # k1, k2, k3
```

کار می‌کند — ولی فقط برای **ما**. هر ماژول دیگری که قبلاً `import test` کرده، هنوز به شیء قدیمی اشاره می‌کند (نامش در namespace خودش به آدرس قبلی bind شده). دو نسخه از یک ماژول در برنامه.

## راه ۲ (بهتر): `importlib.reload`

```python
create_module_file('test', k1=10, k2='python', k3='cheese', k4='parrots')
import importlib
importlib.reload(test)          # running test.py...
id(test) == id(sys.modules['test'])     # True — SAME object as before
test.print_values()             # k1..k4
```

`reload` شیء ماژول موجود را **جا‌به‌جا نمی‌کند**؛ کد جدید را در همان شیء اجرا می‌کند (namespace را بازنویسی می‌کند). پس هر کسی که به ماژول ارجاع دارد، نسخه‌ی جدید را می‌بیند.

## چرا باز هم ناامن است

مشکل با **نام‌های import‌شده از ماژول** برمی‌گردد:

```python
create_module_file('test2', k1='python')
from test2 import print_values      # our name → the OLD function object
print_values()                      # k1 python

create_module_file('test2', k1='python', k2='cheese')
importlib.reload(sys.modules['test2'])     # test2 is not in our globals; get it from the cache
print_values()                      # still k1 only!
```

`reload` ماژول را به‌روز کرد، ولی `print_values` در namespace ما به **شیء تابع قدیمی** اشاره دارد. باید دوباره bind کنیم: `from test2 import print_values` یا `print_values = sys.modules['test2'].print_values`. و باز، هر ماژول دیگری که `from test2 import ...` کرده بود، همچنان نسخه‌ی قدیمی را دارد.

جمع‌بندی: reload برای کار **تعاملی** (نوت‌بوک، REPL) وقتی نمی‌خواهی همه‌چیز را از نو شروع کنی به درد می‌خورد. در production نه — تقریباً حتماً جایی چیزی می‌شکند. تغییر کد = restart برنامه.
