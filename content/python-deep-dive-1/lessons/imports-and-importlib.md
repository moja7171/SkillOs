## import با نام پویا: `importlib`

`import mod_name` وقتی `mod_name = 'math'` است کار نمی‌کند — پایتون دنبال ماژولی به نام `mod_name` می‌گردد. برای نام داخل متغیر، `importlib`:

```python
import importlib, sys

mod_name = 'math'
importlib.import_module(mod_name)
'math' in sys.modules        # True
math.sqrt(2)                 # NameError: name 'math' is not defined
```

`import_module` ماژول را بارگذاری و در `sys.modules` ثبت می‌کند، ولی **نامی در namespace ما نمی‌سازد** (درست مثل importer دستی درس قبل). خودمان bind می‌کنیم — و بهتر است مقدار برگشتی را بگیریم:

```python
math2 = importlib.import_module(mod_name)      # cache-first, like the import statement
math2 is sys.modules['math']                    # True
math2.sqrt(2)
```

`importlib` خودش با پایتون نوشته شده (یک پکیج با `__init__.py`)؛ همان منطق importer زبان C است، قابل خواندن — ولی پیچیده. چرا؟

## finder و loader

کار سختِ import، **پیدا کردن** کد است. ماژول‌ها می‌توانند از جاهای مختلف بیایند: built-in (C)، فایل `.py`، `.pyc` (کامپایل‌شده)، `.so`/`.pyd` (extension باینری)، داخل آرشیو zip/egg/wheel، یا حتی از دیتابیس/شبکه با hook سفارشی.

- **finder**: می‌گوید «این ماژول را می‌شناسم؟ کجاست؟ با چه loaderی؟» → یک **ModuleSpec** برمی‌گرداند یا `None`.
- **loader**: کد را از منبع می‌آورد؛ بعد همان مراحل آشنا: ساخت ماژول، ثبت در cache، compile، exec.
- **importer** = شیئی که هر دو کار را می‌کند.

```python
sys.meta_path
# [<class '_frozen_importlib.BuiltinImporter'>,
#  <class '_frozen_importlib.FrozenImporter'>,
#  <class '_frozen_importlib_external.PathFinder'>]
```

پایتون به ترتیب از هر finder می‌پرسد. برای `math`، `BuiltinImporter` جواب می‌دهد؛ برای `fractions` و ماژول‌های خودت، `PathFinder` که در `sys.path` می‌گردد. اگر همه `None` برگردانند: `ModuleNotFoundError`.

```python
math.__spec__       # ModuleSpec(name='math', loader=<class 'BuiltinImporter'>, origin='built-in')
fractions.__spec__  # ModuleSpec(name='fractions', loader=<SourceFileLoader ...>, origin='.../fractions.py')
```

می‌شود finder/loader خودت را نوشت و به `sys.meta_path` اضافه کرد.

### `find_spec` بدون بارگذاری

```python
importlib.util.find_spec('decimal')     # ModuleSpec(...) — found, not loaded
importlib.util.find_spec('nope')        # None
```

## `sys.path` در عمل

ماژولی کنار نوت‌بوک/اسکریپت بساز:

```python
with open('module1.py', 'w') as code_file:
    code_file.write("print('running module1.py...')\n")
    code_file.write('a = 100\n')

importlib.util.find_spec('module1')     # found: SourceFileLoader, origin = ./module1.py
import module1                          # running module1.py...
module1.a                               # 100
```

حالا همان را در پوشه‌ای **خارج** از پروژه بگذار (مثلاً home):

```python
import os
ext_module_path = os.environ['HOME']              # HOMEPATH on Windows
file_abs_path = os.path.join(ext_module_path, 'module2.py')
with open(file_abs_path, 'w') as code_file:
    code_file.write("print('running module2.py...')\n")
    code_file.write("x = 'python'\n")

importlib.util.find_spec('module2')     # None — that directory is not on sys.path

sys.path.append(ext_module_path)
importlib.util.find_spec('module2')     # found
import module2                          # running module2.py...
module2.x                               # 'python'
```

قاعده: اگر import فایل‌محور شکست خورد، مسیرش در `sys.path` نیست — تمام. اضافه کردن دستی به `sys.path` یعنی hard-code کردن مسیر در برنامه؛ جایگزین‌ها: نصب پکیج (`pip install -e .`)، متغیر محیطی `PYTHONPATH`، یا فایل‌های `.pth` در `site-packages` (بخش «site-specific configuration hooks» مستندات).
