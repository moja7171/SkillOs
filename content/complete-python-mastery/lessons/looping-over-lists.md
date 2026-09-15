## پیمایش ساده

لیست پیمایش‌پذیره، پس مستقیم می‌شه توی `for` ازش استفاده کرد:

```python
letters = ["a", "b", "c"]

for letter in letters:
    print(letter)
```

خروجی:
```
a
b
c
```

## وقتی ایندکس هم لازمت باشه: `enumerate`

اگه هم عضو رو بخوای هم ایندکسش رو، از تابع آماده‌ی `enumerate` استفاده کن:

```python
letters = ["a", "b", "c"]

for item in enumerate(letters):
    print(item)
```

خروجی:
```
(0, 'a')
(1, 'b')
(2, 'c')
```

`enumerate` یه شیء پیمایش‌پذیر برمی‌گردونه که توی هر تکرار یه **تاپل** (ایندکس، عضو) می‌ده.

## باز کردن مستقیم توی خود `for`

چون هر تکرار یه تاپل دو عضوی‌ه، می‌شه دقیقاً مثل درس قبل (list unpacking) بازش کرد — این‌بار مستقیم توی خط `for`:

```python
letters = ["a", "b", "c"]

for index, letter in enumerate(letters):
    print(index, letter)
```

خروجی:
```
0 a
1 b
2 c
```

این خیلی خواناتر از اینه که هر بار با کروشه `item[0]` و `item[1]` رو جدا بگیری. **قاعده‌ی کلی:** برای پیمایش ساده از `for x in list` استفاده کن؛ اگه ایندکس هم لازمت بود، `enumerate` رو با unpacking مستقیم توی `for` بذار.
