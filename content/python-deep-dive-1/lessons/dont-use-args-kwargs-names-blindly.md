## نام‌ها معنی دارند

در بیشتر مثال‌های دوره `*args` و `**kwargs` نوشتیم — تکه‌کدهای کوچک یا decoratorهایی که آرگومان‌ها را فقط **عبور** می‌دهند و معنایشان را نمی‌دانند. اما در کد واقعی، اگر آرگومان‌های متغیر **معنی** دارند، نام معنی‌دار بگذار. `*` و `**` مهم‌اند، نه اسم `args`/`kwargs`.

## مثال ۱: کجا `args` درست است و کجا نه

```python
def audit(f):
    def inner(*args, **kwargs):            # fine: a pass-through; inner has no idea what they are
        print(f'Called {f.__name__}')
        return f(*args, **kwargs)
    return inner

from operator import mul
from functools import reduce

@audit
def product(*values):                      # not *args: these ARE the values being multiplied
    return reduce(mul, values)

product(1, 2, 3, 4)      # Called product → 24
```

## مثال ۲

```python
def count_multi(lst, *item_values):        # "count how many times each of these values appears"
    return sum(lst.count(value) for value in item_values)

l = 1, 1, 2, 3, 4, 5, 6, 6, 7, 8, 9, 10
count_multi(l, 1, 6, 7)                    # 5
```

`*args` اینجا به خواننده هیچ‌چیز نمی‌گوید؛ `*item_values` می‌گوید.

## مثال ۳: attributeهای دلخواه

```python
class Person:
    def __init__(self, name, age, **custom_attributes):
        self.name = name
        self.age = age
        for attr_name, attr_value in custom_attributes.items():
            setattr(self, attr_name, attr_value)

parrot = Person('Polly', 101, status='stiff', vooms=False)
vars(parrot)     # {'name': 'Polly', 'age': 101, 'status': 'stiff', 'vooms': False}
```

`**custom_attributes` دقیقاً می‌گوید این keywordها چه‌اند. همان قاعده‌ی همیشگی نام‌گذاری: نام باید نقش را بگوید — برای پارامترهای متغیر هم.
