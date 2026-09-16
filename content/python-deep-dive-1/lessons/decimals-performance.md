## هزینه‌های Decimal

Decimal دقت می‌دهد، ولی مجانی نیست:

1. **ساختنش دردسر دارد** — رشته یا tuple، نه literal عددی.
2. **همه‌ی توابع math را ندارد** — مثلثاتی نیست؛ و `//`/`%` رفتار متفاوت دارند.
3. **حافظه**:

```python
import sys
sys.getsizeof(3.1415)              # 24 bytes
sys.getsizeof(Decimal('3.1415'))   # 104 bytes  ← more than 4x
```

4. **سرعت** — benchmark مدرس (۱۰ میلیون تکرار):

| عملیات | float | Decimal |
|---|---|---|
| ساخت شیء (`3.1415`) | ۰٫۳ ث | ۲٫۹ ث (~۱۰×) |
| `a + a` | ۰٫۵ ث | ۱٫۰ ث (~۲×) |
| `a * a` | ۰٫۵ ث | ۱٫۰ ث |
| `a / a` | ۰٫۵ ث | ۱٫۶ ث |
| جذر (۵ میلیون) | ۰٫۹ ث | ۱۸٫۸ ث (~۲۰×) |

```python
import time
from decimal import Decimal

def run_float(n=1):
    for i in range(n):
        3.1415

def run_decimal(n=1):
    for i in range(n):
        Decimal('3.1415')

n = 10_000_000
start = time.perf_counter(); run_float(n); print('float', time.perf_counter() - start)
start = time.perf_counter(); run_decimal(n); print('decimal', time.perf_counter() - start)
```

## نتیجه

Decimal را **فقط** وقتی به کار ببر که واقعاً دقت ده‌دهی دقیق لازم داری (مالی، جایی که خطای انباشته اهمیت دارد). در غیر این صورت float: سریع‌تر، کم‌حجم‌تر، ساده‌تر.
