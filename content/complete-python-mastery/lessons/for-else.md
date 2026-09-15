## خروج زودهنگام از حلقه با `break`

بریم سراغ همون مثال قبلی: می‌خوایم یه پیام رو تا ۳ بار امتحان کنیم بفرستیم. ولی اگه دفعه‌ی اول موفق شدیم، دیگه چه نیازی به تکرار داریم؟

```python
successful = True

for attempt in range(3):
    print("attempt")
    if successful:
        print("successful")
        break
```

خروجی:
```
attempt
successful
```

`break` یعنی «همین الان از حلقه بزن بیرون» — حتی اگه هنوز تکرارهای بیشتری مونده باشه. اینجا چون `successful` از همون تکرار اول `True`ه، حلقه فقط یه بار اجرا می‌شه.

> نکته: به تورفتگی دقت کن — خط `break` زیر `if` تورفتگی داره، پس فقط وقتی اجرا می‌شه که شرط `True` باشه.

## وقتی هیچ‌وقت موفق نمی‌شیم: `for...else`

حالا فرض کن بعد از ۳ بار تلاش، بازم موفق نشدیم. می‌خوایم یه پیام دیگه نشون بدیم:

```python
successful = False

for attempt in range(3):
    print("attempt")
    if successful:
        print("successful")
        break
else:
    print("attempted three times and failed")
```

خروجی:
```
attempt
attempt
attempt
attempted three times and failed
```

بلوک `else` بعد از یه حلقه یه معنای خاص داره: **فقط وقتی اجرا می‌شه که حلقه بدون `break` تموم بشه**. یعنی اگه حلقه کامل همه‌ی تکرارهاش رو طی کنه (بدون اینکه کسی وسطش بزنه بیرون)، `else` اجرا می‌شه. اگه یه جایی وسط حلقه `break` بزنیم، `else` نادیده گرفته می‌شه.

پس با `successful = True`، چون `break` می‌زنیم، هیچ‌وقت به `else` نمی‌رسیم. با `successful = False`، حلقه هر ۳ تکرار رو کامل طی می‌کنه، پس `else` هم اجرا می‌شه.
