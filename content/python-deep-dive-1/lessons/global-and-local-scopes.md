> این درس سه ویدیو دارد: معرفی بخش، درس و کدنویسی. (زیرنویس ویدیوی معرفی در دسترس نبود.)

## نام، شیء، و «کجا»

`a = 10` یعنی نام `a` به شیء `10` **bound** شده. ولی این نام همه‌جا معنی ندارد: ممکن است جایی اصلاً وجود نداشته باشد، یا وجود داشته باشد ولی به چیز دیگری اشاره کند (مثل پارامتری به نام `a` داخل یک تابع). بخشی از کد که یک binding در آن معتبر است، **scope** (دامنه‌ی lexical) آن نام است، و جایی که این bindingها ذخیره می‌شوند **namespace** است — یک جدول نام → شیء. هر scope یک namespace دارد.

## سه لایه: built-in، global، local

- **built-in scope**: `True`, `None`, `print`, `dict`, … . در همه‌ی ماژول‌ها در دسترس است.
- **global scope** = **module scope**: هر فایل `.py` (و هر نوت‌بوک) یک global scope مستقل دارد. چیزی به نام «global در کل برنامه» وجود ندارد؛ global همیشه یعنی «سطح ماژول».
- **local scope**: scope یک تابع در حال اجرا.

این scopeها **تودرتو**اند و جست‌وجوی نام از داخل به بیرون است: local → global → built-in. اگر جایی پیدا نشد: `NameError`.

```python
# module1.py
print(True)     # neither print nor True is in the module's namespace → found in built-ins

# module2.py
print(a)        # NameError: name 'a' is not defined — nothing above built-ins to search

# module3.py
print = lambda x: 'hello {0}!'.format(x)   # 'print' now lives in the MODULE namespace
s = print('world')                          # calls ours, not the built-in: masking
del print                                   # remove ours; lookup falls back to the built-in again
```

**masking** (یا shadowing): نامی در scope داخلی‌تر، نام بیرونی را می‌پوشاند. تعریف `print` یا `list` یا `sum` خودت معمولاً ایده‌ی بدی است.

## scope تابع: در زمان فراخوانی ساخته می‌شود

وقتی ماژول بارگذاری می‌شود و پایتون به `def` می‌رسد، تابع را **کامپایل** می‌کند و نامش را در namespace ماژول می‌گذارد — ولی بدنه اجرا نمی‌شود و هیچ scope‌ای ساخته نمی‌شود. **هر فراخوانی** یک local scope و namespace تازه می‌سازد (به همین دلیل بازگشت کار می‌کند)، و با پایان تابع آن scope از بین می‌رود: نام‌هایش «out of scope» می‌شوند و reference count اشیاء کم می‌شود.

```python
def my_func(a, b):
    c = a * b
    return c

my_func('z', 2)     # local namespace: a='z', b=2, c='zz'   → destroyed on return
my_func(10, 5)      # a NEW local namespace: a=10, b=5, c=50
```

## تصمیم در زمان کامپایل: local است یا نه؟

نکته‌ی کلیدی این درس. وقتی پایتون تابع را کامپایل می‌کند، کل بدنه را نگاه می‌کند و **هر نامی که جایی در تابع مقدار می‌گیرد** (سمت چپ `=`، پارامترها) را **local** علامت می‌زند — مگر اینکه `global` اعلام شده باشد. نامی که فقط **خوانده** می‌شود، local نیست؛ در زمان اجرا از scopeهای بیرونی پیدا می‌شود.

```python
a = 10

def func1():
    print(a)          # a only referenced → non-local → prints the global 10

def func2():
    a = 100           # assignment → a is LOCAL; masks the global a
    print(a)          # 100;  the module's a is still 10 afterwards

def func3():
    global a          # "a means the module's a"
    a = 100           # modifies the global; even creates it if it did not exist

def func4():
    print(a)          # UnboundLocalError: local variable 'a' referenced before assignment
    a = 100           # ← this line made a local for the WHOLE function, at compile time
```

`func4` همان جایی است که همه یک بار گیر می‌کنند: پایتون «اول global را بخوان، بعد local بساز» نمی‌کند. چون در بدنه انتساب هست، `a` از اول تا آخر تابع local است و در `print` هنوز مقداری ندارد.

### `global`

`global a` به پایتون می‌گوید انتساب‌های `a` در این تابع به namespace ماژول بروند. اگر آن متغیر وجود نداشته باشد، ساخته می‌شود:

```python
def my_func():
    global var
    var = 'hello world'

my_func()
var          # 'hello world' — created in the module namespace by the function
```

## چند نکته‌ی دیگر

- **lambda** هم تابع است و همین قواعد را دارد: `f = lambda n: a ** n` متغیر global `a` را می‌خواند.
- خود **توابع هم نام‌اند** و در namespace زندگی می‌کنند؛ `print(True)` اول `print` و `True` را در scope فعلی می‌جوید، بعد built-in.
- **بلوک‌ها scope نمی‌سازند.** برخلاف Java/C#، متغیری که داخل `for` یا `if` تعریف می‌شود، بعد از بلوک هم هست:

```python
for i in range(10):
    x = 2 * i
x     # 18 — still there; only functions (and classes, modules) make scopes
```
