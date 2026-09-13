## پکیج بدون `__init__.py`

**implicit namespace package** (PEP 420، از پایتون 3.3): پوشه‌ای که `__init__.py` **ندارد** ولی می‌تواند ماژول، پکیج معمولی یا namespace package دیگر در خود داشته باشد. پایتون به‌طور ضمنی آن را پکیج می‌شناسد — و چون فایل کدی ندارد، هیچ کدی «برای خود پکیج» اجرا نمی‌شود.

```
utils/                 no __init__.py  → namespace package
    validators/        no __init__.py  → namespace package
        boolean.py     .py file        → module
        date.py        .py file        → module
        json/          has __init__.py → regular package
            __init__.py
            serializers.py
            validators.py
```

پایتون هنگام import این درخت را پیمایش می‌کند و برای هر پوشه بر اساس وجود/نبود `__init__.py` تصمیم می‌گیرد.

## مقایسه

| | پکیج معمولی | namespace package |
|---|---|---|
| `type()` | module | module |
| `__init__.py` | دارد | **ندارد** |
| `__file__` | مسیر `__init__.py` | تنظیم نمی‌شود |
| `__repr__` | `<module 'common' from '…/app/common/__init__.py'>` | `<module 'utils' (namespace)>` |
| `__path__` | `['…/app/common']` | `_NamespacePath(['…/app/utils'])` — پویا |
| مسیرها | با absolute import در `__init__` اگر پوشه‌ی والد عوض شود می‌شکند | پویا محاسبه می‌شود؛ کدی داخل پکیج نیست که بشکند (importهای خودت البته باید عوض شوند) |
| محل | یک پوشه | می‌تواند در **چند پوشه‌ی غیرتودرتو** باشد — حتی بخشی در zip |

نمونه‌ی هم‌ساختار:

```
app/
    utils/                      # namespace package
        validators/
            boolean.py
    common/                     # regular package
        __init__.py
        validators/
            boolean.py
```

```python
import utils, common
utils.__name__, common.__name__       # 'utils', 'common'
utils.__package__, common.__package__ # 'utils', 'common'
getattr(utils, '__file__', None)      # None
common.__file__                       # '.../app/common/__init__.py'
```

importها همان شکل همیشگی‌اند:

```python
import utils.validators.boolean
from utils.validators import date
import utils.validators.json.serializers
```

## توصیه

اول پکیج‌های معمولی را خوب یاد بگیر (تقریباً همه‌ی کدهایی که می‌بینی همان‌اند). بعد، اگر سناریوی «یک namespace از چند جا» داشتی — مثلاً چند پکیج مستقل که همه زیر `mycompany.*` نصب می‌شوند — سراغ PEP 420 برو؛ آن سند نقطه‌ی شروع است.

> نکته‌ی عملی: فراموش کردن `__init__.py` معمولاً عمدی نیست. اگر پکیجت `__init__.py` ندارد و import «عجیب» رفتار می‌کند (مثلاً کد `__init__` اجرا نمی‌شود چون وجود ندارد!)، اول همین را چک کن.
