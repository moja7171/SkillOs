## دو مشکل توی طراحی `Stream`

طراحی `Stream` از درس قبل خوبه، ولی دو تا مشکل داره.

**مشکل اول**: می‌شه مستقیم از خود `Stream` یه آبجکت ساخت:

```python
s = Stream()
s.open()  # runs, but what does that even mean?!
```

`Stream` یه مفهوم انتزاعیه — «باز کردن یه جریان» بدون اینکه بدونیم چه‌جور جریانیه (فایل؟ شبکه؟) اصلاً معنی نداره. نباید بشه مستقیم از `Stream` نمونه ساخت؛ همیشه باید از یکی از زیرکلاس‌هاش (`FileStream`, `NetworkStream`) استفاده کرد.

**مشکل دوم**: هیچ تضمینی نیست که هر کلاس فرزند حتماً متد `read` رو با همین اسم پیاده‌سازی کنه. اگه یه نفر فردا یه `MemoryStream` بسازه و به‌جای `read`، اسمش رو `read_data` بذاره، هیچ خطایی نمی‌گیریم — فقط یه ناسازگاری خاموش بین کلاس‌ها ایجاد می‌شه.

## راه‌حل: کلاس پایه‌ی انتزاعی (ABC)

یه **کلاس پایه‌ی انتزاعی** (Abstract Base Class) مثل یه کلوچه‌ی نیم‌پزه — قرار نیست مستقیم مصرف بشه؛ فقط قراره یه سری کد و یه قرارداد مشترک به فرزندهاش بده.

برای انتزاعی‌کردن `Stream`، از ماژول `abc` استفاده می‌کنیم:

```python
from abc import ABC, abstractmethod


class Stream(ABC):
    def __init__(self):
        self.opened = False

    def open(self):
        ...

    def close(self):
        ...

    @abstractmethod
    def read(self):
        pass
```

دو تغییر مهم اینجا هست:

1. `Stream` حالا از `ABC` ارث می‌بره — همین کافیه تا پایتون اجازه‌ی ساخت مستقیم آبجکت ازش رو نده.
2. متد `read` هیچ پیاده‌سازی‌ای نداره (فقط `pass`) و با `@abstractmethod` تزئین شده — یعنی «هر کلاس فرزند **باید** این متد رو پیاده‌سازی کنه».

## نتیجه: هر دو مشکل حل می‌شن

```python
s = Stream()
# TypeError: Can't instantiate abstract class Stream with abstract method read
```

دیگه نمی‌شه مستقیم از `Stream` نمونه ساخت.

```python
class MemoryStream(Stream):
    pass


ms = MemoryStream()
# TypeError: Can't instantiate abstract class MemoryStream with abstract method read
```

حتی `MemoryStream` هم، تا وقتی `read` رو پیاده‌سازی نکنه، خودش انتزاعی حساب می‌شه و نمی‌شه ازش نمونه ساخت — چون متد انتزاعی `read` رو از `Stream` به ارث برده و هنوز پرش نکرده.

```python
class MemoryStream(Stream):
    def read(self):
        print("Reading data from memory")


ms = MemoryStream()  # now this works
```

همین که `read` رو پیاده‌سازی کنیم، `MemoryStream` یه کلاس **مشخص** (concrete) می‌شه و می‌شه ازش نمونه ساخت.

## جمع‌بندی

کلاس پایه‌ی انتزاعی دو کار می‌کنه: جلوی ساخت مستقیم نمونه از یه مفهوم انتزاعی رو می‌گیره، و یه قرارداد اجباری (متدهایی که هر فرزند باید پیاده‌سازی کنه) تعریف می‌کنه.
