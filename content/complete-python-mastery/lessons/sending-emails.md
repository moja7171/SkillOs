## ساخت پیام ایمیل

پایتون یه ماژول آماده به اسم `email` توی کتابخانه‌ی استانداردش داره که فرمت پیام‌های ایمیل (استاندارد **MIME**) رو می‌سازه — این استاندارد ربطی به پایتون نداره، فقط قالب پیام‌های ایمیله. زیرپکیج `email.mime.multipart` یه کلاس می‌ده که می‌شه باهاش پیامی ساخت که هم متن ساده هم HTML داره:

```python
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText

message = MIMEMultipart()
message["From"] = "your-name"
message["To"] = "recipient@example.com"
message["Subject"] = "This is a test"

message.attach(MIMEText("Hello!", "plain"))
```

- هدرهای پیام (فرستنده، گیرنده، موضوع) با کروشه مقداردهی می‌شن.
- برای تنظیم **بدنه**ی پیام، باید یه `MIMEText` بسازیم و با `message.attach(...)` بهش وصلش کنیم. آرگومان دوم `MIMEText` مشخص می‌کنه بدنه چه نوعیه — `"plain"` برای متن ساده، `"html"` برای HTML (توی درس بعدی می‌بینیم).

## ارسال با SMTP

برای ارسال واقعی، به یه سرور **SMTP** (پروتکل استاندارد ارسال ایمیل) وصل می‌شیم:

```python
from smtplib import SMTP

with SMTP(host="smtp.gmail.com", port=587) as smtp:
    smtp.ehlo()
    smtp.starttls()
    smtp.login("your-email@gmail.com", "your-app-password")
    smtp.send_message(message)

print("sent")
```

مراحل اتصال طبق پروتکل SMTP همیشه به همین ترتیبه:

1. `ehlo()`: یه پیام معرفی/احوال‌پرسی به سرور SMTP (بخشی از خود پروتکل).
2. `starttls()`: ارتباط رو وارد حالت رمزنگاری‌شده (TLS) می‌کنه — از این به بعد، هر دستوری که می‌فرستیم رمزنگاری‌شده‌ست.
3. `login(user, password)`: ورود با اطلاعات حساب ایمیل.
4. `send_message(message)`: ارسال واقعی پیام.

نکته‌ی امنیتی مهم: **هیچ‌وقت** ایمیل و رمز عبور واقعی رو مستقیم توی کد ننویس — این مثال فقط برای یادگیریه. توی یه برنامه‌ی واقعی، این مقادیر رو از متغیرهای محیطی (environment variables) یا یه فایل تنظیمات جدا از کد می‌خونیم؛ خیلی از سرویس‌های ایمیل (مثل Gmail) هم به‌جای رمز اصلی حساب، یه «App Password» جداگونه برای این کار می‌سازن.

مثل هر ارتباط شبکه‌ای دیگه، توی یه برنامه‌ی واقعی این کد رو باید داخل یه `try`/`except` بذاریم — خیلی چیزها می‌تونن اشتباه پیش برن (شبکه قطع بشه، رمز غلط باشه، و غیره).

## پیوست‌کردن فایل (مثلاً عکس)

می‌شه علاوه بر متن، فایل هم به پیام پیوست کرد:

```python
from email.mime.image import MIMEImage
from pathlib import Path

message.attach(MIMEImage(Path("photo.png").read_bytes()))
```

`MIMEImage` داده‌ی باینری عکس رو می‌گیره (با `read_bytes()`، همون‌طور که توی درس کار با فایل‌ها دیدیم) و به‌عنوان پیوست به پیام اضافه می‌کنه.

## جمع‌بندی

- `MIMEMultipart` برای ساخت پیام؛ `MIMEText`/`MIMEImage` برای اضافه‌کردن بدنه/پیوست.
- `smtplib.SMTP` برای اتصال به سرور و ارسال — ترتیب همیشه: `ehlo` → `starttls` → `login` → `send_message`.
- هیچ‌وقت اطلاعات ورود واقعی رو مستقیم توی کد هاردکد نکن.
