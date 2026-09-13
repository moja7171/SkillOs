> این درس دو ویدیو دارد (بخش ۱ و ۲). فایل‌های نمونه در «فایل‌های درس».

## هدف

پکیج را طوری بسازیم که برای **کاربر** یک import ساده کافی باشد و ساختار داخلی پنهان بماند.

```
common/
    __init__.py
    validators/
        __init__.py
        boolean.py      # is_boolean, boolean_helper_1, boolean_helper_2
        date.py         # is_date, date_helper_1, ...
        json.py         # is_json, ...
        numeric.py      # is_integer, is_numeric, ...
    models/
        __init__.py
        users/
            __init__.py
            user.py     # class User, user_helper_1
        posts/
            __init__.py
            post.py     # class Post, ...
            posts.py    # class Posts, ...
main.py
```

## نسخه‌ی ۰: کاربر همه‌چیز را خودش import می‌کند

```python
# main.py
import common.validators.boolean
import common.validators.date
import common.validators.json
import common.validators.numeric

common.validators.json.is_json('{}')
common.validators.date.is_date('2018-01-01')
```

خسته‌کننده، و کاربر باید ساختار داخلی را بداند. autocomplete هم `json_helper_1` را نشان می‌دهد که به کاربر ربطی ندارد.

نکته‌ی جانبی: `for k in globals().keys()` خطا می‌دهد («dictionary changed size during iteration») چون خود `k` به globals اضافه می‌شود؛ روی کپی پیمایش کن: `for k in dict(globals()).keys()`.

## نسخه‌ی ۱: `__init__.py` زیرماژول‌ها را import کند

```python
# common/validators/__init__.py
import common.validators.boolean
import common.validators.date
import common.validators.json
import common.validators.numeric
```

```python
# main.py
import common.validators as validators
validators.boolean.is_boolean(True)      # works; still one level too deep
```

## نسخه‌ی ۲: import ستاره‌ای در `__init__`

یکی از معدود جاهایی که `import *` **به‌جا**ست:

```python
# common/validators/__init__.py
from common.validators.boolean import *
from common.validators.date import *
from common.validators.json import *
from common.validators.numeric import *
```

```python
validators.is_boolean(True)
validators.is_json('{}')
```

مشکل ۱: نام پکیج والد (`common`) در `__init__` hard-code شده؛ اگر پکیج را به `shared` تغییر نام دهی، همه‌ی اینها می‌شکند.
مشکل ۲: helperها هم وارد namespace `validators` شده‌اند.

## relative import

```python
# common/validators/__init__.py
from .boolean import *          # . = the package this __init__ lives in
from .date import *
from .json import *
from .numeric import *
```

`.` یعنی همین پکیج؛ `..` یعنی پکیج والد (مثلاً برای رسیدن به پکیج همسایه)، و به همین ترتیب. حالا تغییر نام `common` یا حتی `validators` این فایل را نمی‌شکند. (relative import فقط داخل پکیج معنی دارد — در اسکریپت اصلی نه.)

## کنترل آنچه صادر می‌شود

**راه ۱: underscore** — `import *` نام‌هایی را که با `_` شروع می‌شوند نمی‌آورد: `_boolean_helper_1`.

**راه ۲: `__all__`** — فهرست صریح نام‌هایی که `import *` باید بیاورد:

```python
# common/validators/numeric.py
__all__ = ['is_integer', 'is_numeric']

def is_integer(arg): ...
def is_numeric(arg): ...
def numeric_helper_1(): ...     # still in numeric's own namespace, but not exported
```

بعد از این، namespace `validators` فقط `is_*`ها را دارد (به‌علاوه‌ی خود زیرماژول‌ها `boolean`, `date`, …).

## `__all__` در سطح پکیج

اگر کسی `from common.validators import *` بزند، همه‌ی namespace پکیج (از جمله نام زیرماژول‌ها) وارد namespace او می‌شود. برای تمیز نگه داشتن، `__all__` پکیج را از `__all__` زیرماژول‌ها بساز — تا یک جا نگه‌داری شود:

```python
# common/validators/__init__.py
from .boolean import *
from .date import *
from .json import *
from .numeric import *

__all__ = (boolean.__all__ +
           date.__all__ +
           json.__all__ +
           numeric.__all__)
```

(نام‌های `boolean`, `date`, … به‌عنوان زیرماژول بعد از import در namespace پکیج هستند، پس `boolean.__all__` قابل دسترسی است.)

## همان الگو برای پکیج‌های تودرتو

```python
# common/models/posts/post.py
__all__ = ['Post']
class Post: pass
def post_helper_1(): pass

# common/models/posts/__init__.py
from .post import *
from .posts import *
__all__ = post.__all__ + posts.__all__

# common/models/users/__init__.py
from .user import *
__all__ = user.__all__

# common/models/__init__.py
from .users import *
from .posts import *
__all__ = users.__all__ + posts.__all__
```

```python
# main.py
import common.models as models
john = models.User()
john_post = models.Post()
john_posts = models.Posts()
```

کاربر نمی‌داند `Post` در `common/models/posts/post.py` است — و لازم هم نیست. توسعه‌دهنده می‌تواند فایل‌ها را جابه‌جا کند بی‌آنکه رابط عوض شود.

توصیه‌ی نهایی: با ساختارهای مختلف بازی کن؛ سیستم import پایتون یکی از پیچیده‌ترین بخش‌هاست و فقط با آزمایش جا می‌افتد.
