## یه مسئله‌ی واقعی: جریان داده (Stream)

فرض کن می‌خوای مفهوم «جریان داده» رو مدل کنی — داده ممکنه از یه فایل بیاد، از شبکه، یا از حافظه. همه‌ی این جریان‌ها چند تا کار مشترک دارن: باز شدن، بسته شدن، خوندن داده. ولی **نحوه‌ی خوندن** داده بسته به نوع جریان فرق می‌کنه.

## کلاس پایه: `Stream`

بیایم یه کلاس `Stream` بسازیم که کارهای مشترک رو نگه می‌داره:

```python
class Stream:
    def __init__(self):
        self.opened = False

    def open(self):
        if self.opened:
            raise InvalidOperationError("Stream is already open")
        self.opened = True

    def close(self):
        if not self.opened:
            raise InvalidOperationError("Stream is already closed")
        self.opened = False
```

اگه بخوایم یه جریانِ ازقبل‌بازشده رو دوباره باز کنیم (یا یه جریانِ بسته رو دوباره ببندیم)، این یه عملیات نامعتبره. چون هیچ استثنای آماده‌ای توی پایتون دقیقاً این مفهوم رو نداره، خودمون یکی می‌سازیم:

```python
class InvalidOperationError(Exception):
    pass
```

هر استثنای سفارشی باید از کلاس آماده‌ی `Exception` ارث ببره، و طبق قرارداد، اسمش باید با `Error` تموم بشه.

## کلاس‌های فرزند: هرکدوم روش خوندن خودشون

حالا `FileStream` و `NetworkStream` رو می‌سازیم که هرکدوم کارهای مشترک (`open`/`close`) رو از `Stream` به ارث می‌برن، ولی متد `read` مخصوص خودشون رو دارن:

```python
class FileStream(Stream):
    def read(self):
        print("Reading data from a file")


class NetworkStream(Stream):
    def read(self):
        print("Reading data from a network")
```

```python
fs = FileStream()
fs.open()
fs.read()   # Reading data from a file
fs.close()
```

## چرا این یه مثال خوبه

این طراحی دو تا اصلی که درس‌های قبل درباره‌شون هشدار دادیم رو رعایت می‌کنه:

- **فقط یکی-دو سطح وراثت**: `Stream` در بالا، `FileStream`/`NetworkStream` زیرش — نه یه زنجیره‌ی طولانی.
- **بدون وراثت چندگانه**: هر کلاس فرزند دقیقاً یه والد داره.

و از همه مهم‌تر: کارهایی که به ارث می‌رسن (`open`، `close`) واقعاً بین همه‌ی جریان‌ها مشترکن — نه یه رابطه‌ی ظاهری و فلسفی مثل «مرغ یه پرنده‌ست».
