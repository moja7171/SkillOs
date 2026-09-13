## بدون جای‌گذاری: `random.sample`

`choices` با جای‌گذاری است — تکرار ممکن، `k` بزرگ‌تر از جمعیت مجاز:

```python
random.choices(list('abc'), k=10)     # ['c', 'c', 'a', 'c', 'c', 'b', 'b', 'b', 'b', 'c']
```

گاهی می‌خواهیم **نمونه‌ی جمعیت**: هر عنصر حداکثر یک بار. این کار `sample` است:

```python
l = range(20)
random.sample(l, k=10)     # [15, 6, 3, 14, 17, 2, 13, 10, 12, 1]  — no repeats
random.sample(l, k=20)     # a shuffled copy of the whole population
random.sample(l, 50)       # ValueError: Sample larger than population or is negative
```

با seed، تکرارپذیر:

```python
random.seed(0); random.sample(l, k=5)     # [12, 13, 1, 8, 15]
random.seed(0); random.sample(l, k=5)     # [12, 13, 1, 8, 15]
```

## مثال: کارت از یک دست

```python
suits = 'C', 'D', 'H', 'S'
ranks = tuple(range(2, 11)) + tuple('JQKA')
deck = [str(rank) + suit for suit in suits for rank in ranks]    # 52 cards: '2C', ..., 'AS'

from collections import Counter
Counter(random.sample(deck, k=20))      # every count is 1
Counter(random.choices(deck, k=20))     # some cards appear twice — wrong for dealing cards
```

کارتی که برداشته شده دیگر در دست نیست؛ پس `sample`.

| | `choices` | `sample` |
|---|---|---|
| جای‌گذاری | دارد | ندارد |
| تکرار | ممکن | هرگز |
| `k` > جمعیت | مجاز | خطا |
| وزن | `weights`/`cum_weights` | ندارد (از 3.11 `counts` برای تکرار عناصر) |
| `k` = جمعیت | همان‌طور تصادفی | معادل shuffle (کپی) |

نکته: از 3.11 `sample` روی set کار نمی‌کند — اول `list(s)` بساز.
