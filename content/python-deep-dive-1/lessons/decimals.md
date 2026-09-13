> این درس دو ویدیو دارد: بخش نظری و بخش کدنویسی.

## چرا یک نوع دیگر برای اعداد حقیقی؟

`0.1` در ده‌دهی متناهی و دقیق است، ولی float دودویی آن را تقریبی ذخیره می‌کند. برای بیشتر کارها فرقی ندارد. ولی خطاها **انباشته** می‌شوند:

> `100.01` را یک میلیارد بار با float جمع کن. جمع واقعی `100,010,000,000.00` است؛ نتیجه‌ی float حدود **۱۲۳۸ دلار** کمتر. بورس نیویورک روزی ۲ تا ۶ میلیارد سهم معامله می‌کند — «یک میلیارد جمع» عدد عجیبی نیست.

در مالی و بانکداری نمایش **دقیق ده‌دهی** لازم است. چرا `Fraction` نه؟ چون کار می‌کند ولی گران است: برای `1/10 + 1/5` باید مخرج مشترک پیدا کند، جمع بزند، ساده کند — حافظه و زمان بیشتر از float. **`decimal.Decimal`** (PEP 327) راه میانه است: ده‌دهی دقیق، با دقت قابل تنظیم.

```python
import decimal
from decimal import Decimal    # تا هر بار decimal.Decimal ننویسیم
```

## Context: precision و rounding

رفتار Decimal با یک **context** تعیین می‌شود. دو ویژگی مهمش:

- `prec` — دقت: چند رقم معنادار در **عملیات حسابی** نگه داشته شود (پیش‌فرض ۲۸).
- `rounding` — الگوریتم گرد کردن. برخلاف float که فقط banker's داشت، اینجا انتخاب داری: `ROUND_HALF_EVEN` (پیش‌فرض، همان banker's)، `ROUND_HALF_UP` (تساوی دور از صفر)، `ROUND_HALF_DOWN`، `ROUND_UP`, `ROUND_DOWN`, `ROUND_CEILING`, `ROUND_FLOOR`, `ROUND_05UP`.

```python
decimal.getcontext()
# Context(prec=28, rounding=ROUND_HALF_EVEN, Emin=-999999, Emax=999999, ...)

g_ctx = decimal.getcontext()
g_ctx.prec = 6
g_ctx.rounding = decimal.ROUND_HALF_UP     # یا رشته‌ی 'ROUND_HALF_UP' — ثابت‌ها همان رشته‌اند
g_ctx.prec = 28; g_ctx.rounding = decimal.ROUND_HALF_EVEN   # بازگشت
```

## context سراسری در برابر محلی

`getcontext()` **context فعلی** را می‌دهد — در سطح ماژول همان سراسری است. اگر آن را برای یک محاسبه تغییر دهی، باید یادت باشد برگردانی. راه بهتر: **context محلی** که یک context manager است و با `with` استفاده می‌شود؛ بعد از بلوک، خودش دور ریخته می‌شود:

```python
x = Decimal('1.25')
y = Decimal('1.35')

with decimal.localcontext() as ctx:        # کپی از context فعلی
    ctx.prec = 6
    ctx.rounding = decimal.ROUND_HALF_UP
    print(round(x, 1), round(y, 1))        # 1.3 1.4  ← دور از صفر
    print(decimal.getcontext() is ctx)     # True: داخل with، «فعلی» همین است

print(round(x, 1), round(y, 1))            # 1.2 1.4  ← سراسری: banker's
```

`decimal.localcontext()` بدون آرگومان کپی context فعلی را می‌دهد؛ می‌شود context دیگری هم به آن داد. context managerها بعداً مفصل می‌آیند (همان الگوی `with open(...)` که فایل را خودش می‌بندد).
