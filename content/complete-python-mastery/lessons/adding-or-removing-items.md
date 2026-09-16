## متد چیه؟

قبلاً یاد گرفتی هر چیزی توی پایتون یه شیء (object) ه. با نقطه، به توابعی که مخصوص اون شیء هستن دسترسی داری — به این توابع می‌گیم **متد** (method). لیست‌ها چندتا متد آماده برای اضافه/حذف کردن عضو دارن.

## اضافه‌کردن عضو

```python
letters = ["a", "b", "c"]

letters.append("d")
print(letters)
# ['a', 'b', 'c', 'd']

letters.insert(0, "-")
print(letters)
# ['-', 'a', 'b', 'c', 'd']
```

- `append(x)`: `x` رو **انتهای** لیست اضافه می‌کنه.
- `insert(index, x)`: `x` رو دقیقاً توی **ایندکس مشخص‌شده** اضافه می‌کنه.

## حذف عضو

چهار راه داری، بسته به اینکه چی می‌دونی:

```python
letters = ["-", "a", "b", "c", "d"]

letters.pop()        # removes and returns the last item
letters.pop(0)       # removes and returns the item at index 0
letters.remove("b")  # removes the first "b" found (no index needed)
del letters[0]       # removes by index, returns nothing
del letters[0:2]     # removes a range of indexes
letters.clear()      # empties the whole list
```

| متد/دستور | چی می‌خواد | چی برمی‌گردونه |
|---|---|---|
| `pop()` / `pop(i)` | (اختیاری) ایندکس | مقدار حذف‌شده |
| `remove(x)` | خود مقدار | چیزی برنمی‌گردونه |
| `del list[i]` | ایندکس (یا بازه) | چیزی برنمی‌گردونه |
| `clear()` | — | خالی می‌کنه کل لیست رو |

## نکته: `remove` فقط اولین مورد رو حذف می‌کنه

```python
letters = ["a", "b", "b", "c"]
letters.remove("b")
print(letters)
# ['a', 'b', 'c']  -- only one of the two "b"s was removed
```

اگه بخوای همه‌ی موارد تکراری رو حذف کنی، باید روی لیست پیمایش کنی و هرکدوم رو جدا حذف کنی.
