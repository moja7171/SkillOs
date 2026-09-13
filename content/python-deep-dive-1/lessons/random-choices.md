## انتخاب یک عنصر تصادفی

راه غیرپایتونیک: اندیس تصادفی بساز و بخوان:

```python
import random
random.seed(0)
l = [10, 20, 30, 40, 50, 60]
l[random.randrange(len(l))]      # 40
```

راه درست: `random.choice`:

```python
random.seed(0)
for _ in range(10):
    print(random.choice(l))      # same sequence as above, cleaner code
```

## چند انتخاب: `random.choices`

```python
list_1 = list(range(1000))
random.choices(list_1, k=5)      # [583, 908, 504, 281, 755]
```

### با جای‌گذاری (with replacement)

هر انتخاب مستقل است؛ یک عنصر می‌تواند تکرار شود — پس `k` می‌تواند از اندازه‌ی جمعیت **بزرگ‌تر** باشد:

```python
list_2 = ['a', 'b', 'c']
random.choices(list_2, k=2)      # ['c', 'c'] happens
random.choices(list_2, k=5)      # ['a', 'c', 'c', 'c', 'b']
```

### وزن‌دار

`weights` هم‌طول با جمعیت؛ احتمال هر عنصر متناسب با وزنش:

```python
random.choices(list_2, k=5, weights=[10, 1, 1])     # mostly 'a'
random.choices(list_2, k=5, weights=[100, 1, 1])    # almost all 'a'
```

بررسی توزیع با شمارش فراوانی:

```python
from collections import namedtuple
Freq = namedtuple('Freq', 'count freq')

def freq_counts(list_):
    total = len(list_)
    return {k: Freq(list_.count(k), 100 * list_.count(k) / total) for k in set(list_)}

freq_counts(random.choices(list_2, k=1000))
# {'a': Freq(count=331, freq=33.1), 'b': Freq(count=334, freq=33.4), 'c': Freq(count=335, freq=33.5)}

random.seed(0)
freq_counts(random.choices(list_2, k=1_000, weights=(8, 1, 1)))
# {'a': Freq(count=810, freq=81.0), 'b': Freq(count=86, freq=8.6), 'c': Freq(count=104, freq=10.4)}
```

وزن `8/(8+1+1) = 80%` برای `a` — همان‌طور که انتظار داشتیم. (`cum_weights` هم هست برای وزن‌های تجمعی.)

`collections.Counter` همین شمارش را آماده دارد: `Counter(random.choices(...))`.
