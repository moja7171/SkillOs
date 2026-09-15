## یه کلاس پایه‌ی انتزاعی برای کنترل‌های رابط کاربری

بیایم از الگوی درس قبل استفاده کنیم — یه کلاس پایه‌ی انتزاعی به اسم `UIControl`:

```python
from abc import ABC, abstractmethod


class UIControl(ABC):
    @abstractmethod
    def draw(self):
        pass


class TextBox(UIControl):
    def draw(self):
        print("TextBox")


class DropDownList(UIControl):
    def draw(self):
        print("DropDownList")
```

هر کدوم از این کلاس‌ها متد `draw` خودشون رو دارن — یکی `TextBox` چاپ می‌کنه، یکی `DropDownList`.

## یه تابع که با هر نوع کنترل کار می‌کنه

حالا یه تابع می‌نویسیم که یه لیست از کنترل‌ها می‌گیره و همه‌شون رو رسم می‌کنه:

```python
def draw(controls):
    for control in controls:
        control.draw()
```

```python
controls = [DropDownList(), TextBox()]
draw(controls)
# DropDownList
# TextBox
```

## این یعنی چندریختی (Polymorphism)

نکته‌ی جالب اینجاست: تابع `draw` هیچ‌وقت نمی‌پرسه «تو دقیقاً چه‌جور کنترلی هستی؟» فقط `control.draw()` رو صدا می‌زنه — و بسته به اینکه اون آبجکت واقعاً یه `TextBox`ه یا `DropDownList`، رفتار متفاوتی اجرا می‌شه. این تصمیم توی زمان اجرا (runtime) گرفته می‌شه، نه موقع نوشتن کد.

به این می‌گیم **چندریختی** (Polymorphism — «poly» یعنی چند، «morph» یعنی شکل): یه متد مشترک (`draw`) که بسته به نوع آبجکت، شکل‌های متفاوتی به خودش می‌گیره.

## چرا این کاربردی‌ه

فکر کن یه فرم با ده‌ها `TextBox`، `DropDownList`، دکمه‌ی رادیویی و... داری. کافیه همه رو توی یه لیست بذاری و تابع `draw` رو صدا بزنی — بدون اینکه لازم باشه برای هر نوع کنترل، کد جداگانه بنویسی. این دقیقاً همون چیزیه که چندریختی بهت می‌ده: یه رابط مشترک، رفتارهای متفاوت.
