## وقتی reference counting کافی نیست

Reference counting ساده و کارآمد است: به محض اینکه شمارنده‌ی شیئی صفر شد، memory manager آن را از بین می‌برد. ولی یک حالت هست که هیچ‌وقت صفر نمی‌شود: **ارجاع حلقوی (circular reference)**.

فرض کن `my_var` به شیء A اشاره می‌کند و A ویژگی‌ای دارد که به شیء B اشاره می‌کند. اگر `my_var` را به `None` بگذاری: شمارنده‌ی A صفر → A نابود می‌شود → شمارنده‌ی B هم صفر → B نابود می‌شود. تا اینجا خوب.

حالا فرض کن B هم ویژگی‌ای دارد که **به A برمی‌گردد**. `my_var` را که برداری:
- شمارنده‌ی A از ۲ به ۱ می‌رسد (B هنوز به آن اشاره دارد)
- شمارنده‌ی B هم ۱ است (A به آن اشاره دارد)

هیچ‌کدام صفر نیست، هیچ‌کدام از بیرون قابل دسترسی نیست، و هیچ‌کدام هم آزاد نمی‌شود: **memory leak**. اینجاست که **garbage collector** وارد می‌شود: چرخه‌ها را پیدا می‌کند و پاکشان می‌کند.

## ماژول `gc`

- GC به‌طور پیش‌فرض **روشن** است و دوره‌ای اجرا می‌شود.
- می‌توانی خاموشش کنی (`gc.disable()`) — فقط برای کارایی، و فقط اگر **مطمئنی** کدت ارجاع حلقوی نمی‌سازد. ساختن ارجاع حلقوی ناخواسته خیلی آسان است؛ توصیه‌ی مدرس: روشن بگذار.
- می‌توانی دستی اجرایش کنی: `gc.collect()`.
- می‌توانی اشیای زیر نظرش را ببینی: `gc.get_objects()`.

## نکته‌ی تاریخی: قبل از ۳.۴

در نسخه‌های قدیمی، اگر حتی یکی از اشیای داخل چرخه **destructor** (`__del__`) داشت، GC نمی‌دانست destructorها را به چه ترتیبی اجرا کند (شاید بستن اتصال دیتابیس باید بعد از commit تراکنش شیء دیگر باشد) و آن اشیا را «uncollectable» علامت می‌زد → نشت حافظه. از **Python 3.4** این مشکل حل شده. از نسخه‌های قدیمی‌تر استفاده نکن.

## دیدن چرخه در کد

```python
import ctypes, gc

def ref_count(address):
    return ctypes.c_long.from_address(address).value

def object_by_id(object_id):
    for obj in gc.get_objects():
        if id(obj) == object_id:
            return "Object exists"
    return "Not found"

class A:
    def __init__(self):
        self.b = B(self)            # A → B
        print(f"A: self: {hex(id(self))}, b: {hex(id(self.b))}")

class B:
    def __init__(self, a):
        self.a = a                  # B → A  (چرخه)
        print(f"B: self: {hex(id(self))}, a: {hex(id(self.a))}")

gc.disable()
my_var = A()
a_id = id(my_var)
b_id = id(my_var.b)

ref_count(a_id), ref_count(b_id)    # (2, 1)
object_by_id(a_id), object_by_id(b_id)   # هر دو "Object exists"

my_var = None
ref_count(a_id), ref_count(b_id)    # (1, 1)  ← هنوز زنده‌اند
object_by_id(a_id)                  # "Object exists"

gc.collect()
object_by_id(a_id), object_by_id(b_id)   # "Not found", "Not found"
ref_count(a_id)                     # عددی بی‌معنی — آدرس آزاد شده و بازاستفاده شده
```

بعد از `gc.collect()` هر دو شیء نابود شده‌اند. عدد عجیبی که `ref_count(a_id)` می‌دهد همان هشدار درس قبل است: آن آدرس دیگر متعلق به A نیست.
