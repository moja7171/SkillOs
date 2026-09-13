## decorator لاگر

می‌خواهیم هر فراخوانی تابع ثبت شود — اینجا با print، در عمل با ماژول `logging`، فایل یا دیتابیس. به‌جای تکرار کد لاگ در هر تابع، یک decorator:

```python
def logged(fn):
    from functools import wraps
    from datetime import datetime, timezone

    @wraps(fn)
    def inner(*args, **kwargs):
        run_dt = datetime.now(timezone.utc)
        result = fn(*args, **kwargs)              # call FIRST ...
        print(f'{run_dt}: called {fn.__name__}')  # ... then log
        return result
    return inner

@logged
def func_1(): pass

func_1()    # 2017-12-10 00:28:34.556797+00:00: called func_1
```

importها داخل decorator‌اند تا اگر آن را در ماژول دیگری گذاشتی، خودکفا باشد. به **ترتیبِ** «اول فراخوانی، بعد چاپ» دقت کن؛ الان مهم می‌شود.

## پشته کردن decoratorها

با `timed` درس قبل، دو decorator داریم. می‌شود هر دو را روی یک تابع گذاشت:

```python
from operator import mul
from functools import reduce

@logged
@timed
def fact(n):
    return reduce(mul, range(1, n + 1))

fact(3)
# fact(3) took 0.000012s to run.
# 2017-12-10 ...: called fact
```

معنی‌اش:

```python
fact = logged(timed(fact))
```

decorator **پایینی اول اعمال می‌شود** (نزدیک‌تر به تابع)، بعد بالایی روی نتیجه. و در **فراخوانی**، بیرونی‌ترین (`logged`) اول اجرا می‌شود؛ `fn` آن، تابعِ `timed`‌شده است.

پس چرا خروجی `timed` اول چاپ شد؟ چون `logged` اول `fn(...)` را صدا می‌زند (که `timed` است و چاپ می‌کند) و **بعد** خط لاگ را می‌نویسد. ترتیب چاپ به جای print در بدنه بستگی دارد، نه به ترتیب اجرا شدن decoratorها.

### مثال شفاف

```python
def dec_1(fn):
    def inner():
        print('running dec_1')
        return fn()
    return inner

def dec_2(fn):
    def inner():
        print('running dec_2')
        return fn()
    return inner

@dec_1
@dec_2
def my_func():
    print('running my_func')

my_func()
# running dec_1
# running dec_2
# running my_func
```

`my_func = dec_1(dec_2(my_func))`: `dec_1.inner` چاپ می‌کند، `fn` را صدا می‌زند که `dec_2.inner` است، آن هم چاپ می‌کند و بعد تابع اصلی. اگر print را **بعد** از `fn()` بگذاری، ترتیب چاپ معکوس می‌شود: `my_func`، `dec_2`، `dec_1`.

می‌شود چند تا یا حتی تکراری پشته کرد (`@dec_1 @dec_2 @dec_1 @dec_2`) — ترکیب تابع (function composition).

## چرا ترتیب مهم است؟

مثال واقعی: endpoint یک API با دو decorator — `auth` (آیا کاربر مجاز است؟ اگر نه، خطا برگردان و تابع را صدا نزن) و `logged`.

```python
@auth
@logged
def save_resource(): ...      # save_resource = auth(logged(save_resource))
```

اینجا اگر auth رد کند، هرگز به `logged` نمی‌رسیم → فقط فراخوانی‌های **مجاز** لاگ می‌شوند.

```python
@logged
@auth
def save_resource(): ...      # logged(auth(save_resource))
```

اینجا **همه** تلاش‌ها لاگ می‌شوند، حتی رد‌شده‌ها. هر دو معنی‌دارند؛ فقط باید بدانی چه می‌خواهی. برای `logged`/`timed` فرقی نمی‌کرد، ولی وقتی decorator تصمیم می‌گیرد تابع اصلاً اجرا شود یا نه، ترتیب رفتار را عوض می‌کند.
