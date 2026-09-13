## PEP 468: ترتیب `**kwargs`

از 3.6، ترتیبی که آرگومان‌های کلیدواژه‌ای پاس می‌شوند، در dict `kwargs` داخل تابع **حفظ** می‌شود. (این مستقل از ترتیب درج dict است — PEP جدا، ولی پیاده‌سازی dict جدید آن را ممکن کرد.)

چرا مهم است؟ هر جا تابعی بر اساس **ترتیب** kwargs چیزی ترتیب‌دار می‌سازد. قبلاً باید با OrderedDict یا لیست tuple دور می‌زدی.

## کاربرد: factory برای named tuple با پیش‌فرض

```python
from collections import namedtuple

def defaulted_namedtuple(class_name, **fields):
    Struct = namedtuple(class_name, fields.keys())          # field ORDER = kwargs order
    Struct.__new__.__defaults__ = tuple(fields.values())    # defaults in the same order
    return Struct

Vector2D = defaulted_namedtuple('Vector2D',
                                x1=None, y1=None,
                                x2=None, y2=None,
                                origin_x=0, origin_y=0)

Vector2D._fields                 # ('x1', 'y1', 'x2', 'y2', 'origin_x', 'origin_y')
v1 = Vector2D(10, 10, 20, 20)    # Vector2D(x1=10, y1=10, x2=20, y2=20, origin_x=0, origin_y=0)
```

این فقط به این دلیل درست کار می‌کند که `fields.keys()` و `fields.values()` **به همان ترتیبی هستند که نوشتیم**. اگر ترتیب حفظ نمی‌شد، پیش‌فرض‌ها به فیلدهای اشتباه می‌چسبیدند.

(از 3.7 خود `namedtuple` پارامتر `defaults=` دارد، ولی این مثال نشان می‌دهد ترتیب kwargs چه امکانی می‌دهد.)
