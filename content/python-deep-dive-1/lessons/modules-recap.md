## دو راه import

- دستور `import` (رایج)
- `importlib.import_module('name')` وقتی نام در متغیر است

## وقتی ماژول import می‌شود

1. **cache** (`sys.modules`) چک می‌شود؛ اگر بود، همان ارجاع برمی‌گردد و تمام.
2. وگرنه **finder**ها (`sys.meta_path`) به ترتیب پرسیده می‌شوند تا ماژول **پیدا** شود؛ finder یک `ModuleSpec` برمی‌گرداند که loader مناسب را مشخص می‌کند.
3. **loader** کد را می‌آورد.
4. یک شیء ماژول **خالی** ساخته می‌شود.
5. ارجاع آن **بلافاصله** در `sys.modules` گذاشته می‌شود — قبل از اجرای کد. دلیل: import دایره‌ای (module1 ← module2 ← module1)؛ import دوم شیء (فعلاً خالی) را از cache می‌گیرد و حلقه‌ی بی‌نهایت پیش نمی‌آید.
6. کد کامپایل می‌شود (اگر لازم باشد — بعضی منابع از قبل کامپایل‌شده‌اند).
7. کد اجرا می‌شود و namespace ماژول پر می‌شود: `module.__dict__` همان `globals()` ماژول است.

## finderها

```python
sys.meta_path
# BuiltinImporter   → built-ins like math
# FrozenImporter    → frozen modules
# PathFinder        → file-based modules
```

`PathFinder` در پوشه‌های `sys.path` می‌گردد (و در `<package>.__path__` برای پکیج‌ها):

```python
sys.path
# ['/home/me/my-app', '/usr/lib/python3x.zip', '/usr/lib/python3.x', '/usr/lib/python3.x/lib-dynload',
#  '/usr/local/lib/python3.x/dist-packages', '/usr/lib/python3/dist-packages']
collections.__path__      # ['/usr/lib/python3.x/collections']
```

## ویژگی‌های یک ماژول

| | built-in (`math`) | کتابخانه‌ی استاندارد (`fractions`) | ماژول خودت (`module1`) |
|---|---|---|---|
| `type()` | module | module | module |
| `__spec__.loader` | `BuiltinImporter` | `SourceFileLoader` | `SourceFileLoader` |
| `__spec__.origin` | `'built-in'` | `/usr/lib/python3.x/fractions.py` | `/home/me/my-app/module1.py` |
| `__name__` | `'math'` | `'fractions'` | `'module1'` |
| `__package__` | `''` | `''` | `''` |
| `__file__` | **ندارد** | مسیر فایل | مسیر فایل |

`__file__` را `PathFinder` در یکی از مسیرهای `sys.path` پیدا کرده است.

## چند نکته

- ماژول می‌تواند built-in باشد، فایل روی دیسک، از قبل کامپایل‌شده (`.pyc`)، frozen، داخل zip، یا هر جایی که یک finder/loader سفارشی برسد (دیتابیس، HTTP، …).
- ماژول‌های فایل‌محور باید در مسیری از `sys.path` یا `<package>.__path__` باشند.
- منابع: [tutorial/modules](https://docs.python.org/3/tutorial/modules.html)، [reference/import](https://docs.python.org/3/reference/import.html)، PEP 302 (import hooks).

نیمه‌ی اول بخش تمام شد؛ از درس بعد **پکیج‌ها**.
