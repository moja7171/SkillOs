> ده فایل نمونه (example1–10) در «فایل‌های درس».

## `sys.argv`

هر چه بعد از نام اسکریپت در خط فرمان بیاید، در `sys.argv` است — لیستی از **رشته‌ها** که عنصر اولش نام فایل است:

```python
# example1.py
import sys
print(sys.argv)
```

```
$ python example1.py 123 hello 456 goodbye
['example1.py', '123', 'hello', '456', 'goodbye']
$ python example1.py [1, 2, 3] [4, 5, 6]
['example1.py', '[1,', '2,', '3]', '[4,', '5,', '6]']      # shell splits on spaces
$ python example1.py --name John --years 1980 1981 1982
['example1.py', '--name', 'John', '--years', '1980', '1981', '1982']
```

همه رشته‌اند؛ خودت تبدیل کن:

```python
numbers = [int(a) for a in sys.argv[1:]]      # sum(sys.argv[1:]) would fail: strings
print(sum(numbers))
```

## پارس دستی — و چرا کافی نیست

قرارداد `--name value`:

```python
keys = sys.argv[1::2]
values = sys.argv[2::2]
args = {k: v for k, v in zip(keys, values)}
first_name = args.get('--first-name')
```

کار می‌کند، ولی به‌زودی می‌خواهی: نوع (int، لیست)، اجباری/اختیاری، نام کوتاه و بلند، flag بدون مقدار، و `-h` برای راهنما. همه در کتابخانه‌ی استاندارد: **`argparse`**.

## `argparse`: پایه

```python
# example6.py
import argparse

parser = argparse.ArgumentParser('Calculates the div a//b and mod a % b of two integers.')
parser.add_argument('a', help='first integer', type=int)      # positional
parser.add_argument('b', help='second integer', type=int)
args = parser.parse_args()                                     # reads sys.argv[1:]

print(f'{args.a}//{args.b} = {args.a // args.b}, {args.a}%{args.b} = {args.a % args.b}')
```

```
$ python example6.py 10 3          → 10//3 = 3, 10%3 = 1
$ python example6.py 10.5 3        → error: argument a: invalid int value: '10.5'
$ python example6.py -h            → auto-generated help
```

## آرگومان‌های نام‌دار

```python
parser.add_argument('-f', '--first', help='specify first name', type=str, required=False, dest='first_name')
parser.add_argument('-l', '--last',  help='specify last name',  type=str, required=True,  dest='last_name')
parser.add_argument('--yob', help='year of birth', type=int, dest='birth_year')
args = parser.parse_args()
args.first_name, args.last_name, args.birth_year
```

```
$ python example7.py -f Polly -l Parrot --yob 1969
```

`dest` نام attribute را تعیین می‌کند (پیش‌فرض: نام بلند با `_` به‌جای `-`). `required=True` نام‌دار را اجباری می‌کند.

## چند مقدار: `nargs`

```python
parser.add_argument('--sq', help='numbers to square', nargs='*', type=float)                  # 0 or more
parser.add_argument('--cu', help='numbers to cube',   nargs='+', type=float, required=True)   # 1 or more
```

```
$ python example8.py --sq 1.5 2 3 --cu 2.5 3 4
$ python example8.py --sq --cu 2 3 4        # sq = []
$ python example8.py --sq --cu              # error: --cu expected at least one argument
```

## پیش‌فرض، flag، action

```python
parser.add_argument('--monty', action='store_const', const='Python')          # --monty → 'Python', else None
parser.add_argument('-v', '--verbose', action='store_const', const=True, default=True)
parser.add_argument('-v2', '--verbose2', action='store_const', const=True)   # no default → None
parser.add_argument('-q', '--quiet', action='store_false')                   # flag: True by default, False if given
parser.add_argument('-n', '--name', default='John', type=str)
```

`action` می‌گوید با آرگومان چه شود: `store` (پیش‌فرض)، `store_const`، `store_true`/`store_false` برای flagها، `append`، `count`، و سفارشی ([docs](https://docs.python.org/3/library/argparse.html#action)).

## گروه‌های ناسازگار

```python
group = parser.add_mutually_exclusive_group()
group.add_argument('-v', '--verbose', action='store_true')
group.add_argument('-q', '--quiet', action='store_true')
parser.add_argument('-n', type=complex, help='some complex number', required=True)
```

```
$ python example10.py -v -n 3+4j         # verbose
$ python example10.py -q -n 3+4j         # quiet
$ python example10.py -v -q -n 3+4j      # error: argument -q/--quiet: not allowed with argument -v/--verbose
```

`type` هر callable‌ای می‌تواند باشد که رشته را تبدیل کند (`complex`, `int`, تابع خودت).

جمع‌بندی: `sys.argv` برای یکی‌دو مقدار ساده؛ برای هر چیز بیشتر، `argparse` — و برای CLIهای بزرگ، کتابخانه‌هایی مثل `click`/`typer`.
