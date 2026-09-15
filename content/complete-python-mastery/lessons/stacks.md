## آخرینی که اضافه شد، اولین کسیه که برداشته می‌شه

**پشته** (stack) یه ساختار داده‌ست که رفتارش شبیه یه دسته کتابه: آخرین کتابی که روی دسته می‌ذاری، اولین کتابیه که می‌تونی برداری. به این رفتار می‌گیم **LIFO** (مخفف last in, first out).

یه مثال واقعی: تاریخچه‌ی مرورگر. هر سایت جدیدی که باز می‌کنی، بالای پشته اضافه می‌شه؛ وقتی دکمه‌ی «برگشت» رو می‌زنی، دقیقاً همون آخرین سایتی که باز کردی نشونت می‌ده.

## پیاده‌سازی پشته با لیست

توی پایتون، خود لیست می‌تونه نقش پشته رو بازی کنه:

```python
browsing_session = []

browsing_session.append("site1.com")
browsing_session.append("site2.com")
browsing_session.append("site3.com")

print(browsing_session)
# ['site1.com', 'site2.com', 'site3.com']
```

با `append`، هر سایت جدید **بالای پشته** (انتهای لیست) اضافه می‌شه.

## برگشتن به عقب

```python
last = browsing_session.pop()
print(last)
# site3.com

print(browsing_session)
# ['site1.com', 'site2.com']
```

`pop()` (بدون آرگومان) آخرین عضو رو هم حذف می‌کنه هم برمی‌گردونه — دقیقاً همون سایتی که باید بهش برگردیم.

## گرفتن سر پشته بدون حذف

اگه فقط بخوای ببینی سر پشته چیه، بدون اینکه حذفش کنی:

```python
print(browsing_session[-1])
```

## چک‌کردن خالی‌بودن

قبل از `pop()`، باید مطمئن بشی پشته خالی نیست — وگرنه خطا می‌گیری:

```python
if not browsing_session:
    print("back button disabled")
else:
    print(browsing_session.pop())
```

یادت باشه: یه لیست خالی، falsy‌ه — یعنی `not []` می‌شه `True`.
