## dict برای «ساختار ثابت»؟

```python
data_dict = dict(key1=100, key2=200, key3=300)
data_dict['key1']
```

اگر در کل برنامه فقط با کلیدهای **ثابت و شناخته‌شده** می‌خوانی و dict را تغییر نمی‌دهی، named tuple مناسب‌تر است: سبک‌تر، immutable، و `d.key1` به‌جای `d['key1']`. دو پیش‌شرط: کلیدها رشته و identifier معتبر باشند (بدون underscore اول) و نیازی به تغییر نداشته باشی.

## تبدیل dict به named tuple

```python
Data = namedtuple('Data', data_dict.keys())     # keys() is an iterable of strings → fine as field names
Data._fields                                     # ('key1', 'key2', 'key3')

d1 = Data(*data_dict.values())                   # works, but fragile (relies on matching order)
d2 = Data(**data_dict)                           # robust: keyword args match by NAME
```

ترتیب کلیدهای dict از پایتون 3.6 (پیاده‌سازی) و 3.7 (تضمین زبان) همان ترتیب درج است، پس `keys()` و `values()` هم‌راستایند. ولی اگر named tuple را با ترتیب دیگری ساخته باشی (`'key3 key2 key1'`)، `Data(*values)` مقادیر را در فیلدهای اشتباه می‌ریزد. **همیشه `**dict`** — اسم‌ها با هم تطبیق می‌خورند.

## چیزهایی که فکر می‌کنی از دست می‌دهی

**کلید پویا** (`data_dict[key_name]`): با `getattr`:

```python
key_name = 'key2'
getattr(d2, key_name)              # 200
```

**`.get` با پیش‌فرض:**

```python
data_dict.get('key10', None)       # None
getattr(d2, 'key10', None)         # None  (without the default: AttributeError)
```

## کاربرد واقعی: لیست dictها → لیست named tupleها

مثلاً نتیجه‌ی query دیتابیس: هر ردیف یک dict، کلیدها نام ستون‌ها. ترجیح: `rows[0].import_date` به‌جای `rows[0]['import_date']` + autocomplete + immutability.

اما dictها لزوماً کلیدهای یکسانی ندارند:

```python
data_list = [
    {'key2': 2, 'key1': 1},
    {'key1': 3, 'key2': 4},
    {'key1': 5, 'key2': 6, 'key3': 7},
    {'key2': 100},
]
```

قدم‌ها:

1. **اجتماع همه‌ی کلیدها** — با set (بدون تکراری):

```python
keys = {key for dict_ in data_list for key in dict_.keys()}     # set comprehension
keys = sorted(keys)                                             # sets have no order → sort for stable fields
```

2. **ساختن named tuple:** `Struct = namedtuple('Struct', keys, rename=True)` (`rename=True` تا نام نامعتبر خطا ندهد).

3. **پیش‌فرض `None` برای همه‌ی فیلدها** — چون بعضی dictها کلید کم دارند:

```python
Struct.__new__.__defaults__ = (None,) * len(Struct._fields)
Struct(key3=10)     # Struct(key1=None, key2=None, key3=10)
```

4. **تبدیل هر dict با `**`:**

```python
tuple_list = [Struct(**dict_) for dict_ in data_list]
# [Struct(key1=1, key2=2, key3=None), Struct(key1=3, key2=4, key3=None),
#  Struct(key1=5, key2=6, key3=7),   Struct(key1=None, key2=100, key3=None)]
```

همه با هم، به‌صورت تابعی عمومی که هیچ‌چیز درباره‌ی محتوای dictها فرض نمی‌کند:

```python
def tuplify_dicts(dicts):
    keys = {key for dict_ in dicts for key in dict_.keys()}
    Struct = namedtuple('Struct', sorted(keys), rename=True)
    Struct.__new__.__defaults__ = (None,) * len(Struct._fields)
    return [Struct(**dict_) for dict_ in dicts]
```

## جمع‌بندی بخش

- dict با کلیدهای ثابت که فقط خوانده می‌شود → named tuple.
- کلاسی که فقط `__init__` و چند attribute دارد و نیازی به mutability نیست → named tuple.
- named tuple همه‌ی رفتار tuple را دارد، به‌علاوه‌ی نام‌ها، `repr`، `==`، `_replace`، `_make`، `_asdict`، `_fields`.
