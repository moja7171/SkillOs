## دو تابع آماده‌ی مفید

با کلاس‌های `Animal` و `Mammal` درس قبل، دو تا تابع آماده‌ی پایتون رو می‌بینیم که خیلی به‌کار میان.

`isinstance(obj, Class)` بررسی می‌کنه یه آبجکت، نمونه‌ی یه کلاس خاص هست یا نه:

```python
m = Mammal()
print(isinstance(m, Mammal))  # True
print(isinstance(m, Animal))  # True -- Mammal inherits from Animal
```

جالبه که `isinstance(m, Animal)` هم `True`ه، نه فقط `Mammal`. چون `m` یه `Mammal`ه و `Mammal` خودش یه `Animal`ه، پس `m` هم غیرمستقیم یه `Animal` حساب می‌شه.

## کلاس `object`: ریشه‌ی همه‌چیز

نکته‌ی جالب‌تر: حتی کلاس `Animal` که هیچ پرانتزی جلوش نذاشتیم، در واقع از یه کلاس دیگه به اسم `object` ارث می‌بره — به‌طور خودکار و بدون اینکه بنویسیمش:

```python
print(isinstance(m, object))  # True
```

`object` کلاس پایه‌ی **همه‌ی** کلاس‌های پایتونه. یعنی زنجیره‌ی وراثت اینجا اینه: `Mammal` → `Animal` → `object`. برای همینه که هر کلاسی که می‌سازی، از قبل یه سری متد جادویی (مثل `__str__`، `__eq__` و بقیه) داره — همه از `object` به ارث رسیدن.

## `issubclass`: رابطه‌ی بین دو کلاس

`isinstance` روی یه **آبجکت** کار می‌کنه؛ `issubclass(ClassA, ClassB)` روی خود **کلاس‌ها** چک می‌کنه که آیا یکی از دیگری ارث می‌بره:

```python
print(issubclass(Mammal, Animal))  # True
print(issubclass(Mammal, object))  # True -- indirect inheritance counts too
```

## جمع‌بندی

- `isinstance(obj, Class)`: آیا این آبجکت، نمونه‌ی این کلاسه (یا یکی از کلاس‌های والدش)؟
- `issubclass(ChildClass, ParentClass)`: آیا این کلاس از اون کلاس ارث می‌بره (مستقیم یا غیرمستقیم)؟
- همه‌ی کلاس‌های پایتون، در نهایت از `object` ارث می‌برن — حتی اگه صریح ننویسیش.
