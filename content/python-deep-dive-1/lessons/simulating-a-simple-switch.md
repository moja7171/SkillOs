## پایتون `switch` ندارد (تا 3.10)

PEP 3103 که خود Guido پیشنهاد داد، رد شد — باز هم توسط Guido. اینجا `switch` ساده را در نظر می‌گیریم: هر `case` با `break` (بدون fall-through)، بر اساس یک مقدار. نمونه‌ی Java:

```java
switch (dow) {
    case 1: dowString = "Monday"; break;
    case 2: dowString = "Tuesday"; break;
    ...
    default: dowString = "Invalid day of week";
}
```

## راه ۱: `if / elif / else`

```python
def dow_switch_fn(dow):
    if dow == 1:
        fn = lambda: print('Monday')
    elif dow == 2:
        fn = lambda: print('Tuesday')
    ...
    elif dow == 7:
        fn = lambda: print('Sunday')
    else:
        fn = lambda: print('Invalid day of week')
    return fn()

dow_switch_fn(1)      # Monday
dow_switch_fn(100)    # Invalid day of week
```

هر شاخه هر قدر بخواهد کد می‌گیرد (if تودرتو و …)؛ ولی شاخه‌ها در زمان نوشتن کد ثابت‌اند.

## راه ۲: dict به‌عنوان جدول dispatch

```python
def dow_switch_dict(dow):
    dow_dict = {
        1: lambda: print('Monday'),
        2: lambda: print('Tuesday'),
        ...
        7: lambda: print('Sunday'),
        'default': lambda: print('Invalid day of week'),
    }
    return dow_dict.get(dow, dow_dict['default'])()
```

مزیت: جدول را می‌شود در **زمان اجرا** تغییر داد (اضافه/حذف case). ضعف: مقدارها باید callable/عبارت ساده باشند؛ برای منطق پیچیده، تابع جدا تعریف می‌کنی. (dict را بیرون از تابع بساز تا هر بار ساخته نشود.)

## راه ۳: decorator ثبت‌کننده (از ایده‌ی single dispatch)

`singledispatch` خودمان بر اساس **نوع** dispatch می‌کرد. switch بر اساس **مقدار** است — فقط کلید registry عوض می‌شود:

```python
def switcher(fn):
    registry = {'default': fn}

    def register(case):
        def inner(fn):
            registry[case] = fn
            return fn                 # so decorators can be stacked
        return inner

    def decorator(case):
        fn = registry.get(case, registry['default'])
        return fn()

    decorator.register = register
    return decorator
```

```python
@switcher
def dow():
    print('Invalid day of week')

@dow.register(1)
def dow_1():
    print('Monday')

dow.register(2)(lambda: print('Tuesday'))
dow.register(3)(lambda: print('Wednesday'))
...
dow.register(7)(lambda: print('Sunday'))

dow(1)      # Monday
dow(100)    # Invalid day of week
```

زیر پوست همان dict است؛ decorator فقط ثبت را تمیز و توزیع‌شده می‌کند (هر case می‌تواند در ماژول دیگری ثبت شود).

## و از 3.10: `match`

```python
match dow:
    case 1: print('Monday')
    case 2 | 3 | 4: print('Midweek')
    case _: print('Invalid day of week')
```

برای switch ساده روی مقدار، `match` خواناترین راه است (درس 3.10). dict/decorator وقتی می‌ماند که caseها پویا باشند.
