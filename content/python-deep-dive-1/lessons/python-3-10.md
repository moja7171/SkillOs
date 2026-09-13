> خلاصه‌ی تغییرات 3.10 که به این دوره مربوط است — نه همه‌ی تغییرات. فهرست کامل: [What's New in 3.10](https://docs.python.org/3/whatsnew/3.10.html). پیام‌های خطای نحوی هم در این نسخه بسیار بهتر شده‌اند.

## Structural pattern matching: `match`

سؤال همیشگی «معادل `switch` در پایتون چیست؟» تا 3.10 جواب نداشت (فقط `if/elif`). حالا **pattern matching** داریم — که خیلی بیشتر از switch است.

```python
def respond(language):
    match language:
        case "Java":
            return "Hmm, coffee!"
        case "Python":
            return "I'm not scared of snakes!"
        case "Go":
            return "Collect $200"
        case _:                            # wildcard: the default
            return "I'm sorry..."

respond("Python")    # "I'm not scared of snakes!"
respond("COBOL")     # "I'm sorry..."
```

`_` الگوی **wildcard** است. چند مقدار با OR:

```python
case "Java" | "Javascript":
    return "Love those braces!"
```

### تطبیق ساختار

قدرت واقعی: تطبیق روی **ساختار** داده (لیست، tuple، dict، کلاس) به‌همراه **capture**. رباتی که فرمان می‌گیرد:

```python
symbols = {"F": "→", "B": "←", "L": "↑", "R": "↓", "pick": "⤣", "drop": "⤥"}

def op(command):
    match command:
        case ["move", ("F" | "B" | "L" | "R") as direction]:     # a 2-element list; capture the 2nd
            return symbols[direction]
        case "pick":
            return symbols["pick"]
        case "drop":
            return symbols["drop"]
        case _:
            raise ValueError(f"{command} does not compute!")

op(["move", "L"])    # '↑'
op("pick")           # '⤣'
```

مثل unpacking، با `*`:

```python
def op(command):
    match command:
        case ["move", *directions]:                              # "move" then any number of items
            return tuple(symbols[d] for d in directions)
        case "pick": return symbols["pick"]
        case "drop": return symbols["drop"]
        case _: raise ValueError(f"{command} does not compute!")

op(["move", "F", "F", "L"])     # ('→', '→', '↑')
op(["move", "up"])              # KeyError: 'up'  — matched, but the body failed
```

### guard

شرط اضافه روی case: فقط وقتی هم الگو بخورد و هم شرط `True` باشد:

```python
case ["move", *directions] if set(directions) < symbols.keys():
    return tuple(symbols[d] for d in directions)

op(["move", "up"])              # ValueError: ['move', 'up'] does not compute!  (fell through to _)
```

منابع: [reference](https://docs.python.org/3/reference/compound_stmts.html#the-match-statement)، PEP 634، و به‌ویژه آموزش PEP 636.

## `zip(..., strict=True)`

`zip` با کوتاه‌ترین iterable می‌ایستد و **بی‌صدا** بقیه را دور می‌ریزد:

```python
list(zip(['a', 'b', 'c'], [10, 20, 30, 40]))       # [('a', 10), ('b', 20), ('c', 30)] — 40 lost

from itertools import zip_longest
list(zip_longest(['a', 'b', 'c'], [10, 20, 30, 40], fillvalue='???'))   # pads the shorter one
```

اغلب انتظار داریم طول‌ها **برابر** باشند و ناهم‌طولی یک باگ است. چک کردن طول قبل از zip با iteratorها ممکن نیست — چک کردن، آنها را مصرف می‌کند:

```python
l1 = (i ** 2 for i in range(4))
l2 = (i ** 3 for i in range(3))
len(list(l1)) == len(list(l2))    # False — and now both are exhausted
list(zip(l1, l2))                 # []
```

3.10:

```python
l1 = (i ** 2 for i in range(4))
l2 = (i ** 3 for i in range(3))
list(zip(l1, l2, strict=True))    # ValueError: zip() argument 2 is shorter than argument 1
```

اگر طول‌ها برابر باشند مثل همیشه کار می‌کند. این همان رویکرد «اول انجام بده، بعد عذرخواهی کن» (EAFP) است که در مدیریت خطا ترجیح دارد، به‌جای «قبلش نگاه کن» (LBYL). هر جا zip می‌زنی و برابری طول را فرض می‌کنی، `strict=True` بگذار.
