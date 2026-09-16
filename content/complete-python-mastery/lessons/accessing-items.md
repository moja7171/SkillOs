## دسترسی با ایندکس

دقیقاً مثل رشته‌ها، با کروشه به هر عضو یه لیست دسترسی داری:

```python
letters = ["a", "b", "c", "d"]

print(letters[0])    # a
print(letters[-1])   # d
```

ایندکس منفی از آخر لیست می‌شماره: `letters[-1]` یعنی آخرین عضو.

## تغییر یه عضو

برخلاف رشته‌ها، لیست **قابل‌تغییره** (mutable) — می‌شه یه عضوش رو مستقیم عوض کرد:

```python
letters[0] = "A"
print(letters)
# ['A', 'b', 'c', 'd']
```

## برش لیست (Slicing)

همون نحو برش رشته‌ها اینجا هم کار می‌کنه:

```python
letters = ["a", "b", "c", "d"]

print(letters[0:3])   # ['a', 'b', 'c']
print(letters[:3])    # same as above -- start defaults to 0
print(letters[:])     # a full copy of the whole list
```

برش، یه لیست **جدید** برمی‌گردونه — لیست اصلی دست‌نخورده می‌مونه.

## گام (step) توی برش

می‌تونی یه گام سوم هم بدی — مثلاً برای گرفتن هر عضو دوم:

```python
numbers = list(range(20))

print(numbers[::2])    # [0, 2, 4, 6, ..., 18]  -- every other item
print(numbers[::-1])   # the whole list, reversed
```

با گام `2`، هر عضو دوم رو برمی‌داره؛ با گام `-1`، کل لیست رو معکوس می‌کنه — یه راه خیلی کوتاه برای وارونه‌کردن یه لیست.
