## ترتیب کلیدها در dict

پیاده‌سازی جدید `dict` در 3.6 (کار Raymond Hettinger) ترتیب **درج** کلیدها را حفظ می‌کند. در 3.6 جزئیات پیاده‌سازی بود؛ از 3.7 رسمی و تضمین‌شده. یعنی در بسیاری موارد دیگر `OrderedDict` لازم نیست. (کدی که به این ترتیب تکیه می‌کند، در نسخه‌های قدیمی‌تر می‌شکند.)

```python
d = {'a': 1, 'b': 2}
d['x'] = 3
d.keys(), d.values(), d.items()    # (['a', 'b', 'x'], [1, 2, 3], [('a', 1), ('b', 2), ('x', 3)])

del d['b']
d['b'] = 4                         # re-inserted → goes to the END
d.keys()                           # ['a', 'x', 'b']

d['x'] = 100                       # replacing a value keeps the position
d.keys()                           # ['a', 'x', 'b']

d.popitem()                        # ('b', 4) — pops the LAST item (LIFO)
```

`keys()`، `values()` و `items()` از چپ به راست همان ترتیب را می‌دهند. `popitem` آخری را برمی‌دارد — شبیه stack، ولی برای stack از لیست استفاده کن.

`update` هم ترتیب را حفظ می‌کند؛ کلیدهای جدید به انتها، به ترتیب dict دوم:

```python
d1 = {'a': 1, 'b': 200}
d2 = {'a': 100, 'd': 300, 'c': 400}
d1.update(d2)
d1                                 # {'a': 100, 'b': 200, 'd': 300, 'c': 400}
```

**هشدار Jupyter:** نمایش خودکار (`d` در آخر سلول) ممکن است کلیدها را الفبایی نشان دهد (مثل `pprint`)؛ `print(d)` ترتیب واقعی را می‌دهد.

## کارهایی که `OrderedDict` هنوز راحت‌تر انجام می‌دهد

`OrderedDict` سه چیز دارد که `dict` معادل مستقیم ندارد: `move_to_end(key, last=True)`، `popitem(last=False)`، و پیمایش معکوس با `reversed()` (که از 3.8 روی dict هم هست). با dict معمولی:

```python
d = {'a': 1, 'b': 2, 'c': 3}
d['a'] = d.pop('a')                          # move to end:   {'b': 2, 'c': 3, 'a': 1}

d = {'a': 1, 'b': 2, 'c': 3, 'x': 100, 'y': 200}
d['c'] = d.pop('c')                          # move c to end
for _ in range(len(d) - 1):                  # then rotate everything else behind it
    key = next(iter(d))
    d[key] = d.pop(key)
d                                            # {'c': 3, 'a': 1, 'b': 2, 'x': 100, 'y': 200}  — move to front (clumsy)

d.popitem()                                  # pop last
d.pop(next(iter(d)))                         # pop first
```

اگر این عملیات را زیاد لازم داری، همچنان `OrderedDict` انتخاب درستی است.

نسخه‌ی پایتونیِ پیاده‌سازی C را Hettinger منتشر کرده: [recipe 578375](http://code.activestate.com/recipes/578375/).
