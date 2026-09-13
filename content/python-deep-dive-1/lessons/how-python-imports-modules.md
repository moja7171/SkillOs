## import در زمان اجرا انجام می‌شود

برخلاف C که ماژول‌ها قبل از اجرا کامپایل و link می‌شوند، `import fractions` هنگام اجرای برنامه، پویا انجام می‌شود. مفهوم ساده است؛ پیچیدگی در **پیدا کردن** کد است، نه بارگذاری‌اش.

## ماژول `sys`

```python
import sys
sys.prefix          # where Python is installed (a virtual env changes this)
sys.exec_prefix     # where the C binaries live
sys.path            # list of directories searched for imports
```

`sys.path` فهرست جاهایی است که پایتون دنبال ماژول می‌گردد: پوشه‌ی اسکریپت اصلی، فایل zip کتابخانه، `lib`، `site-packages`، … . اگر «ModuleNotFoundError» گرفتی، اول `sys.path` را ببین. می‌شود به آن `append` کرد — هک است؛ راه درست‌تر بسته‌بندی/نصب است.

## فرایند import

وقتی `import x` اجرا می‌شود:

1. **cache را چک کن**: اگر `'x'` در `sys.modules` هست، همان را برگردان و تمام.
2. وگرنه: یک `ModuleType` جدید بساز.
3. کد منبع را از جایی بگیر (معمولاً فایل، ولی می‌تواند zip یا هر loader دیگری باشد).
4. ماژول را در `sys.modules` ثبت کن.
5. کد را **کامپایل و اجرا کن**؛ namespace ماژول (`__dict__`) پر می‌شود.
6. نام را در namespace فراخواننده bind کن.

نکته‌ی مهم: بار اول، **کد ماژول اجرا می‌شود**. هر `print` سطح ماژول چاپ می‌شود، هر `def` تابع می‌سازد.

### مثال با دو فایل

```python
# module1.py
print(f'Running {__name__}')
def pprint_dict(header, d):
    print(f'\n\n{header}')
    for key, value in d.items():
        print(key, value, sep=': ')
pprint_dict('module1.globals', globals())
print(f'End of {__name__}')
```

```python
# main.py
print('Running main.py')
import module1                       # module1's code runs HERE, once
print('importing module1 again...')
import module1                       # nothing printed: served from sys.modules
module1.pprint_dict('main.globals', globals())
```

مشاهده‌ها:

- فایلی که مستقیم اجرا می‌شود `__name__ == '__main__'` دارد؛ ماژولی که import می‌شود `__name__ == 'module1'`.
- `globals()` ماژول شامل `__name__`, `__file__`, `__loader__` (`SourceFileLoader`), `__spec__`, و توابع تعریف‌شده است.
- `del globals()['module1']` فقط **نام** را از namespace حذف می‌کند؛ ماژول در `sys.modules` زنده است و `import module1` بعدی فقط نام را برمی‌گرداند — بدون اجرای دوباره.
- import در وسط فایل مجاز است (فقط از نظر سبک، بالای فایل بهتر است) — کد ماژول همان‌جا اجرا می‌شود که به `import` می‌رسیم.

### cache اول از همه

```python
import sys
sys.modules['test'] = lambda: 'Hello!'     # DON'T do this — just to prove the point
import test
test()                                      # 'Hello!'
```

`import` هرچه در `sys.modules` زیر آن نام باشد برمی‌گرداند — بدون اینکه بپرسد ماژول است یا نه.

## شبیه‌سازی import با `compile` و `exec`

اجرای کد پایتون دو قدم است: `compile(source, filename, mode)` → code object (bytecode)، بعد `exec(code, globals_dict)` که می‌گوید متغیرهای سراسری کجا ذخیره شوند.

```python
# importer.py
import os.path, types, sys

def import_(module_name, module_file, module_path):
    if module_name in sys.modules:                      # 1. cache
        return sys.modules[module_name]

    module_rel_file_path = os.path.join(module_path, module_file)
    module_abs_file_path = os.path.abspath(module_rel_file_path)

    with open(module_rel_file_path, 'r') as code_file:  # 3. source
        source_code = code_file.read()

    mod = types.ModuleType(module_name)                 # 2. module object
    mod.__file__ = module_abs_file_path

    sys.modules[module_name] = mod                      # 4. register

    code = compile(source_code, filename=module_abs_file_path, mode='exec')
    exec(code, mod.__dict__)                            # 5. run; globals go into the module's namespace

    return sys.modules[module_name]
```

```python
# module1_source.py           (deliberately NOT named module1.py)
print('Running module1.py')
def hello():
    print('module1 says Hello!')
```

```python
# module2.py
print('Running module2.py')
import module1                 # no module1.py exists — but the cache has it
def hello():
    print('module2 says Hello!\nand...')
    module1.hello()
```

```python
# main.py
import sys, importer
module1 = importer.import_('module1', 'module1_source.py', '.')   # Running module1.py
print('sys says:', sys.modules.get('module1', 'module1 not found'))
import module2                 # Running module2.py — module1 NOT re-run
module2.hello()                # module2 says Hello! / and... / module1 says Hello!
```

ویرایشگر (PyCharm) روی `import module1` خط قرمز می‌کشد چون فایلی به آن نام نمی‌بیند؛ ولی در زمان اجرا پایتون آن را در `sys.modules` پیدا می‌کند. importer ما با import استاندارد سازگار است.

## کد از کجا می‌آید؟

سخت‌ترین بخش همین است و ما شبیه‌سازی‌اش نکردیم. پایتون **finder**ها و **loader**ها دارد: فایل `.py`، فایل‌های داخل zip (در `sys.path` یک `python3x.zip` هست)، built-inها، و حتی import hookهای سفارشی (مثلاً از دیتابیس). بعد از پیدا شدن منبع، بقیه‌ی فرایند همان است: ساخت ماژول، ثبت در cache، compile، exec.
