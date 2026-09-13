## تابع داخل تابع

می‌شود داخل یک تابع، تابع دیگری تعریف کرد (**nested / inner function**). scope تابع درونی، **داخل** scope تابع بیرونی قرار می‌گیرد؛ و همه‌ی اینها داخل global و built-in. جست‌وجوی نام همان قاعده‌ی قبل است: از داخل به بیرون، هر جا اول پیدا شد.

```python
def outer_func():
    x = 'hello'                 # local to outer_func
    def inner_func():
        print(x)                # x only referenced → not local → found in the ENCLOSING scope
    inner_func()

outer_func()                    # hello
```

scope تابع بیرونی از دید `inner_func` نه local است نه global — به آن **nonlocal** (enclosing) می‌گویند. هر عمق تودرتویی کار می‌کند: `inner2` داخل `inner1` داخل `outer` هم `x` را در `outer` پیدا می‌کند. و اگر `x` فقط در ماژول باشد، جست‌وجو تا global ادامه می‌یابد.

`global` هم از داخل تابع درونی کار می‌کند: `global a` در `inner_func` مستقیم به namespace ماژول می‌رود.

## انتساب در تابع درونی = متغیر local جدید

همان قاعده‌ی درس قبل: انتساب، نام را local می‌کند.

```python
def outer_func():
    x = 'hello'
    def inner_func():
        x = 'python'            # assignment → LOCAL to inner_func; masks outer's x
        print('inner:', x)
    inner_func()
    print('outer:', x)

outer_func()
# inner: python
# outer: hello                  ← untouched
```

## `nonlocal`

برای اینکه انتساب در تابع درونی، متغیر تابع **بیرونی** را تغییر دهد:

```python
def outer_func():
    x = 'hello'
    def inner_func():
        nonlocal x              # "x means the nearest enclosing function's x"
        x = 'python'
    print('before:', x)         # hello
    inner_func()
    print('after:', x)          # python

outer_func()
```

قاعده‌ی `nonlocal x`: پایتون در scopeهای **تابعیِ** بیرونی، از نزدیک‌ترین به دورترین، دنبال `x` می‌گردد و به **اولین** جایی که پیدا کرد bind می‌شود. **هرگز به global نمی‌رسد**؛ اگر `x` فقط در ماژول باشد، `SyntaxError: no binding for nonlocal 'x' found`. برای global از `global` استفاده کن.

### چند لایه

```python
def outer():
    x = 'hello'
    def inner1():
        def inner2():
            nonlocal x          # inner1 has no x → binds to outer's x
            x = 'python'
        inner2()
    inner1()
    print(x)                    # python

outer()
```

اما اگر لایه‌ی میانی خودش `x` داشته باشد، `nonlocal` به **همان** می‌چسبد، نه به بیرونی‌تر:

```python
def outer():
    x = 'hello'
    def inner1():
        x = 'python'            # local to inner1
        def inner2():
            nonlocal x          # → inner1's x
            x = 'monty'
        print('inner1 before:', x)   # python
        inner2()
        print('inner1 after:', x)    # monty
    inner1()
    print('outer:', x)          # hello  ← never touched

outer()
```

و اگر لایه‌ی میانی هم `nonlocal x` گفته باشد، همه به `x`ِ `outer` اشاره می‌کنند (نه به‌صورت زنجیره، بلکه مستقیم — `x` در `inner1` اصلاً متغیر جدیدی نیست):

```python
def outer():
    x = 'hello'
    def inner1():
        nonlocal x
        x = 'python'
        def inner2():
            nonlocal x
            x = 'monty'
        print('inner1 before:', x)   # python
        inner2()
        print('inner1 after:', x)    # monty
    inner1()
    print('outer:', x)          # monty

outer()
```

## ترکیب `global` و `nonlocal`

```python
x = 100

def outer():
    x = 'python'                # local to outer
    def inner1():
        nonlocal x              # → outer's x
        x = 'monty'
        def inner2():
            global x            # → the module's x
            x = 'hello'
        print('inner1 before:', x)   # monty
        inner2()
        print('inner1 after:', x)    # monty  (inner2 touched the global, not this one)
    inner1()
    print('outer:', x)          # monty

outer()
print(x)                        # hello
```

و این کار نمی‌کند:

```python
x = 'python'
def outer():
    global x
    x = 'monty'
    def inner():
        nonlocal x              # SyntaxError: outer's x IS the global x; nonlocal only sees function scopes
        x = 'hello'
```

`global x` در `outer` یعنی «`x` اینجا متغیر ماژول است» — یک local به نام `x` نمی‌سازد، پس `nonlocal` چیزی برای پیدا کردن ندارد.

**در عمل:** ساده‌ترین راه پرهیز از این پیچیدگی‌ها، استفاده نکردن از یک نام در چند لایه است. ولی گاهی دقیقاً می‌خواهیم تابع درونی متغیر بیرونی را نگه دارد و تغییر دهد — این پایه‌ی **closure** است، درس بعد.
