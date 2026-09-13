## چند مقدار برگشتی، به‌صورت tuple ساده

```python
from random import randint, random

def random_color():
    red = randint(0, 255)
    green = randint(0, 255)
    blue = randint(0, 255)
    alpha = round(random(), 2)
    return red, green, blue, alpha          # packed into a plain tuple

color = random_color()                       # (239, 12, 87, 0.5)
red, green, blue, alpha = color
```

کار می‌کند، ولی صداکننده باید بداند موقعیت ۰ قرمز است و ۳ آلفا. و در ویرایشگر، `color.` هیچ راهنمایی‌ای نمی‌دهد جز `count` و `index` — چون فقط یک tuple است.

## همان، با named tuple

```python
from collections import namedtuple

Color = namedtuple('Color', 'red green blue alpha')

def random_color():
    red = randint(0, 255)
    green = randint(0, 255)
    blue = randint(0, 255)
    alpha = round(random(), 2)
    return Color(red, green, blue, alpha)

color = random_color()          # Color(red=239, green=12, blue=87, alpha=0.5)
color.red, color.alpha
red, green, blue, alpha = color # unpacking still works — it is a tuple
```

تغییر کد تابع فقط یک خط بود. در عوض:

- `repr` خوانا هنگام debug.
- دسترسی با نام (`color.alpha`) به‌جای `color[3]`.
- **autocomplete** در ویرایشگر (PyCharm، VS Code، …): حتی اگر فقط `random_color` را import کرده باشی و نه `Color` را، ویرایشگر نوع برگشتی را می‌فهمد و بعد از `.` فیلدها را پیشنهاد می‌دهد. لازم نیست به مستندات برگردی یا `_fields` را چک کنی.

قاعده‌ی سرانگشتی: هر جا تابعی بیش از دو-سه مقدار برمی‌گرداند و معنی موقعیت‌ها بدیهی نیست، یک named tuple تعریف کن.
