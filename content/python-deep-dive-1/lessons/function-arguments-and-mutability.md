## چه چیزی به تابع پاس می‌شود؟

**ارجاع.** وقتی `process(my_var)` را صدا می‌زنی، آدرسِ شیئی که `my_var` به آن اشاره دارد به تابع می‌رود و پارامتر تابع (مثلاً `s`) به **همان شیء** اشاره می‌کند. دو دامنه داریم — دامنه‌ی ماژول و دامنه‌ی تابع — ولی یک شیء.

## اشیای immutable: امن از side effect

```python
def process(s):
    print(f"initial s #{id(s)}")
    s = s + " world"
    print(f"final   s #{id(s)}")

my_var = "hello"
print(f"my_var #{id(my_var)}")
process(my_var)
print(id(my_var), my_var)
```

ترتیب اتفاق‌ها:
1. `s` به همان شیء `"hello"` اشاره می‌کند (id یکسان با `my_var`).
2. `s + " world"` نمی‌تواند رشته را تغییر دهد (immutable)، پس رشته‌ی **جدیدی** ساخته می‌شود و `s` به آن اشاره می‌کند.
3. `my_var` در دامنه‌ی ماژول همچنان به `"hello"` اشاره دارد.

نتیجه: تابع **نمی‌تواند** مقدار `my_var` را عوض کند. رشته‌ها، اعداد و بقیه‌ی immutableها از side effect ناخواسته در امان‌اند.

## اشیای mutable: side effect دارند

```python
def modify_list(lst):
    print(f"initial lst #{id(lst)}")
    lst.append(100)
    print(f"final   lst #{id(lst)}")

my_list = [1, 2, 3]
modify_list(my_list)
my_list        # [1, 2, 3, 100]
```

`lst` و `my_list` به یک لیست اشاره می‌کنند. `append` **همان شیء** را تغییر می‌دهد (id ثابت). بعد از برگشت تابع، `my_list` عوض شده — بدون اینکه در کد اصلی چیزی نوشته باشیم. این یک **side effect** است. گاهی عمدی و مفید (sort درجا)، گاهی ناخواسته و منشأ باگ.

## باز هم tuple

```python
def modify_tuple(t):
    t[0].append(100)

my_tuple = ([1, 2], "a")
modify_tuple(my_tuple)
my_tuple       # ([1, 2, 100], 'a')
```

tuple immutable است و id آن تغییر نکرد؛ ولی عنصر اولش لیست بود و درجا تغییر کرد. «immutable بودن» container، محتوای mutable آن را محافظت نمی‌کند.

## جمع‌بندی

- همه‌چیز به‌صورت ارجاع پاس می‌شود.
- شیء immutable: تابع فقط می‌تواند پارامترش را به شیء *دیگری* اشاره دهد — اصل دست‌نخورده می‌ماند.
- شیء mutable: تابع می‌تواند state شیء اصلی را تغییر دهد. اگر این را نمی‌خواهی، از داخل تابع کپی بگیر یا شیء جدید برگردان.
