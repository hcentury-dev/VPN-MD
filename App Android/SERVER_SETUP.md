# راهنمای اتصال اپ به سرور (alpping)

همهٔ نقاطی که اپ برای رسیدن به بک‌اند استفاده می‌کند، در **یک جا** جمع شده‌اند:
فایل `app/lib/helpers/pref.dart` → بلوکِ **SERVER CONNECTION POINTS**.
این سند می‌گوید هر مقدار کجاست و در سمت سرور با چه چیزی باید مچ شود.

---

## ۱) نقاط اتصال در اپ (`app/lib/helpers/pref.dart`)

| مقدار | متغیر | توضیح |
|---|---|---|
| کلید رمزنگاری | `key` | کلید AES-256-CBC. باید **عیناً** برابرِ `$key` در `API/postN.php` باشد. |
| منبع کانفیگ | `configBootstrapUrls` | لیست آدرس‌های `db.json`؛ به‌ترتیب امتحان می‌شوند. |
| نام فارسی | `nameAppFa` | `alpping` |
| نام انگلیسی | `nameAppEn` | `alpping` |
| پکیج | `packageName` | `com.md.alpping` |

مقادیری که از `db.json` خوانده می‌شوند (نه در سورس، بلکه ریموت): `post` (آدرس API)،
`server`، `user`، `version`، `timeData`. این‌ها را `RemoteConfig` در همان `pref.dart` می‌خواند.

---

## ۲) آدرس db.json — راو در برابر edit

آدرسی که دادی صفحهٔ **ویرایش** گیت‌هاب است و برای دانلود کار نمی‌کند:

```
❌ https://github.com/hcentury-dev/Alpha/edit/main/db.json      (صفحهٔ edit)
✅ https://raw.githubusercontent.com/hcentury-dev/Alpha/main/db.json   (raw — همین در اپ ست شده)
```

در `pref.dart` آدرسِ raw به‌عنوان منبع اصلی و دامنهٔ قبلی به‌عنوان fallback گذاشته شده.
اگر خواستی دامنهٔ اختصاصیِ خودت را جایگزین/اضافه کنی، فقط همان لیست را ویرایش کن.

> نکته: db.json روی ریپوی عمومی گیت‌هاب در دسترس همه است؛ چون کانفیگ‌های داخلش با همان
> `key` رمز می‌شوند اشکالی ندارد، ولی هر چیزی که آنجا بگذاری عملاً عمومی است.

---

## ۳) ساختار db.json (کپی-پیست آماده)

این دقیقاً چیزی است که `RemoteConfig` انتظار دارد (کلیدِ ریشه `address`، یک آرایه):

```json
{
  "address": [
    {
      "post": "https://YOUR-API-DOMAIN/postN.php",
      "server": "https://YOUR-API-DOMAIN/",
      "user": "https://YOUR-SUBSCRIPTION-OR-USER-ENDPOINT/",
      "version": "2.0.7",
      "timeData": ""
    }
  ]
}
```

- `post` → همان فایلِ `API/postN.php` روی سرور توست. اپ متدها را این‌طور صدا می‌زند:
  `?method=news`، `?method=questions`، `?method=massage`.
- بقیهٔ مقادیر را مطابق بک‌اند خودت پر کن.

---

## ۴) رمزنگاری — از قبل مچ است (کاری لازم نیست)

کانفیگ‌ها بین اپ و سرور با AES-256-CBC رد و بدل می‌شوند و **همین حالا هم‌خوان‌اند**:

- سرور — `API/postN.php`:
  `openssl_encrypt($data, "AES-256-CBC", $key, OPENSSL_RAW_DATA, $iv)` سپس `base64($iv . $data)`.
- اپ — `app/lib/services/crypto_service.dart` (`CryptoService.decryptConfig`):
  `base64` را باز می‌کند، ۱۶ بایت اولِ آن را به‌عنوان IV می‌گیرد و با `substr(key,0,32)` رمزگشایی می‌کند.

تنها شرط: مقدار `key` در این دو فایل یکی باشد. الان هر دو
`3154b04f…148fa536` هستند؛ اگر کلید را عوض کردی، **هر دو طرف** را با هم عوض کن.

---

## ۵) نکتهٔ امنیتی (سمت سرور — خودت)

`API/account.json` یک **private key سرویس‌اکانتِ Firebase** توست که داخل مخزن کامیت شده.
این کلیدِ خودِ توست (نه کسِ دیگر)، ولی بهتر است از ریپو برداشته شود و در
`.gitignore` برود و در صورت لو رفتن از کنسول Firebase روتیت شود.

---

## ۶) بیلد اندروید

در این محیط Flutter/Dart نصب نیست، پس APK اینجا ساخته نمی‌شود. روی سیستم خودت یا CI:

```bash
cd "App Android/app"
flutter pub get
flutter build apk --release        # یا: flutter build appbundle --release
# خروجی: build/app/outputs/flutter-apk/app-release.apk
```

امضا (keystore) و `applicationId = com.md.alpping` در `android/app/build.gradle` تنظیم می‌شوند.
