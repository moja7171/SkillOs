## `__name__` ماژولی که اجرا می‌شود

```python
# run.py
print(f'loading run.py: __name__ = {__name__}')
import module1

# module1.py
print(f'loading module1: __name__ = {__name__}')
```

```
$ python run.py
loading run.py: __name__ = __main__
loading module1: __name__ = module1

$ python module1.py
loading module1: __name__ = __main__
```

فایلی که مستقیم اجرا می‌شود (نقطه‌ی ورود) `__name__ == '__main__'` می‌گیرد؛ هر ماژولی که import می‌شود، نام خودش را. پس این الگو معنی دارد:

```python
if __name__ == '__main__':
    print('module1 was run...')      # only when executed as a script, not when imported
```

## کاربرد: ماژول + ابزار خط فرمان

یک ماژول زمان‌سنجی که هم import می‌شود، هم از خط فرمان اجرا:

```python
# timing.py
"""
    Times how long a snippet of code takes to run
    over multiple iterations
"""
from time import perf_counter
from collections import namedtuple
import argparse

Timing = namedtuple('Timing', 'repeats elapsed average')

def timeit(code, repeats=10):
    code = compile(code, filename='<string>', mode='exec')     # compile once, not on every exec
    start = perf_counter()
    for _ in range(repeats):
        exec(code)
    elapsed = perf_counter() - start
    return Timing(repeats, elapsed, elapsed / repeats)

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)      # the module docstring becomes the -h text
    parser.add_argument('code', type=str, help='The Python code snippet to be timed.')
    parser.add_argument('-r', '--repeats', type=int, default=10, help='Number of times to repeat the test.')
    args = parser.parse_args()
    print(f'timing: {args.code}...')
    print(timeit(code=args.code, repeats=args.repeats))
```

استفاده به‌عنوان کتابخانه:

```python
# run.py
import timing
result = timing.timeit('[x**2 for x in range(1_000)]', repeats=100)
print(result)          # Timing(repeats=100, elapsed=..., average=...)
```

استفاده از خط فرمان (بلوک `__main__` اجرا می‌شود):

```
$ python timing.py "[x**2 for x in range(1000)]" -r 15
timing: [x**2 for x in range(1000)]...
Timing(repeats=15, elapsed=..., average=...)

$ python timing.py -h        # shows the docstring and the arguments
```

کتابخانه‌ی استاندارد هم همین کار را می‌کند: `zipfile` هم قابل import است، هم ابزار خط فرمان (`-m` برای اجرای ماژول با نام):

```
$ python -m zipfile -c app.zip module1.py run.py timing.py    # create
$ python -m zipfile -l app.zip                                # list
```

(`timeit` استاندارد هم همین الگو را دارد — در بخش تکمیلی.)

## `__main__.py`: اجرای یک پوشه

```
$ python main_usage/
python: can't find '__main__' module in 'main_usage/'
```

اگر فایل نقطه‌ی ورود را `__main__.py` بنامی، می‌توان **پوشه** را اجرا کرد:

```
main_usage/
    __main__.py      (was run.py)
    timing.py

$ python main_usage/
loading __main__: __name__ = __main__
```

و چون پایتون داخل zip را هم می‌خواند، کل برنامه را می‌شود در یک آرشیو گذاشت و اجرا کرد:

```
$ python -m zipfile -c myapp __main__.py timing.py
$ python myapp                    # runs __main__.py from inside the zip
```

درس import از zip در ادامه‌ی بخش.
